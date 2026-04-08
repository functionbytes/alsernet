<?php

namespace Modules\Newsletter\Services;

use Illuminate\Support\Facades\Mail;
use Modules\Core\Models\Setting;
use Modules\Newsletter\Enums\SubscriberStatus;
use Modules\Newsletter\Mail\NewSubscriberAdminMail;
use Modules\Newsletter\Models\Subscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberService
{
    public function __construct(
        private readonly MailjetService $mailjet
    ) {}

    public function subscribe(string $email, ?string $name, string $ip): Subscriber
    {
        $subscriber = Subscriber::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'status' => SubscriberStatus::Subscribed,
                'ip_address' => $ip,
                'subscribed_at' => now(),
            ]
        );

        if (! $subscriber->wasRecentlyCreated && $subscriber->status === SubscriberStatus::Unsubscribed) {
            $subscriber->subscribe();
        }

        if (Setting::get('newsletter.email_notifications') === '1') {
            Mail::send(new NewSubscriberAdminMail($subscriber));
        }

        $this->mailjet->addContact($email, $name);

        return $subscriber;
    }

    public function bulkDelete(array $ids): int
    {
        return Subscriber::query()->whereIn('id', $ids)->delete();
    }

    public function bulkUnsubscribe(array $ids): int
    {
        return Subscriber::query()->whereIn('id', $ids)->update([
            'status' => SubscriberStatus::Unsubscribed->value,
            'unsubscribed_at' => now(),
        ]);
    }

    public function bulkResubscribe(array $ids): int
    {
        return Subscriber::query()->whereIn('id', $ids)->update([
            'status' => SubscriberStatus::Subscribed->value,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="subscribers_'.now()->format('Y-m-d').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['ID', 'Email', 'Nombre', 'Estado', 'Fecha suscripción', 'Creado']);

            Subscriber::query()
                ->orderBy('id')
                ->each(function (Subscriber $subscriber) use ($handle) {
                    fputcsv($handle, [
                        $subscriber->id,
                        $subscriber->email,
                        $subscriber->name ?? '',
                        $subscriber->status->label(),
                        $subscriber->subscribed_at?->format('Y-m-d H:i:s') ?? '',
                        $subscriber->created_at?->format('Y-m-d H:i:s') ?? '',
                    ]);
                });

            fclose($handle);
        }, 200, $headers);
    }
}
