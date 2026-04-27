<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Review;
use ZipArchive;

class GenerateGdprExportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(public readonly int $customerId)
    {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $customer = Customer::query()
            ->with(['orders.items', 'addresses', 'wishlists.product'])
            ->find($this->customerId);

        if (! $customer) {
            return;
        }

        $token = Str::random(40);
        $filename = "gdpr-export-{$customer->id}-{$token}.zip";
        $absolutePath = storage_path("app/private/gdpr-exports/{$filename}");

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $zip = new ZipArchive;
        $zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('profile.json', $this->buildProfileJson($customer));
        $zip->addFromString('orders.json', $this->buildOrdersJson($customer));
        $zip->addFromString('addresses.json', $this->buildAddressesJson($customer));
        $zip->addFromString('wishlist.json', $this->buildWishlistJson($customer));
        $zip->addFromString('reviews.json', $this->buildReviewsJson($customer));
        $zip->addFromString('README.txt', $this->buildReadme($customer));

        $zip->close();

        $downloadUrl = URL::temporarySignedRoute(
            'shop.gdpr.download',
            now()->addHours(24),
            ['token' => $token, 'customer' => $customer->id]
        );

        Cache::put(
            "gdpr_export_{$customer->id}_{$token}",
            $absolutePath,
            now()->addHours(24)
        );

        $this->sendEmail($customer, $downloadUrl);

        Cache::forget("gdpr_export_pending_{$customer->id}");
    }

    public function failed(\Throwable $exception): void
    {
        Cache::forget("gdpr_export_pending_{$this->customerId}");

        Log::error('GenerateGdprExportJob failed', [
            'customer_id' => $this->customerId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function buildProfileJson(Customer $customer): string
    {
        return json_encode([
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'avatar' => $customer->avatar,
            'created_at' => $customer->created_at?->toIso8601String(),
            'email_verified_at' => $customer->email_verified_at?->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function buildOrdersJson(Customer $customer): string
    {
        $orders = $customer->orders->map(fn ($order) => [
            'id' => $order->id,
            'code' => $order->code,
            'status' => $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
            'payment_status' => $order->payment_status,
            'total' => (float) $order->total,
            'subtotal' => (float) $order->sub_total,
            'tax_amount' => (float) $order->tax_amount,
            'shipping_amount' => (float) $order->shipping_amount,
            'discount_amount' => (float) $order->discount_amount,
            'created_at' => $order->created_at?->toIso8601String(),
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'qty' => $item->qty,
                'price' => (float) $item->price,
            ])->toArray(),
        ]);

        return json_encode($orders->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function buildAddressesJson(Customer $customer): string
    {
        $addresses = $customer->addresses->map(fn ($address) => [
            'name' => $address->name,
            'phone' => $address->phone,
            'country' => $address->country,
            'state' => $address->state,
            'city' => $address->city,
            'address' => $address->address,
            'zip_code' => $address->zip_code,
            'is_default' => $address->is_default,
        ]);

        return json_encode($addresses->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function buildWishlistJson(Customer $customer): string
    {
        $wishlist = $customer->wishlists->map(fn ($item) => [
            'product_name' => $item->product?->name,
            'product_sku' => $item->product?->sku,
            'added_at' => $item->created_at?->toIso8601String(),
        ]);

        return json_encode($wishlist->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function buildReviewsJson(Customer $customer): string
    {
        $reviews = Review::query()
            ->where('customer_id', $customer->id)
            ->get()
            ->map(fn ($review) => [
                'product_id' => $review->product_id,
                'star' => $review->star,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
            ]);

        return json_encode($reviews->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function buildReadme(Customer $customer): string
    {
        return implode("\n", [
            'Exportación de datos personales - GDPR',
            '',
            "Cliente: {$customer->name} ({$customer->email})",
            'Generado: '.now()->format('Y-m-d H:i:s'),
            '',
            'Contenido:',
            '- profile.json: Datos personales',
            '- orders.json: Historial de órdenes',
            '- addresses.json: Direcciones guardadas',
            '- wishlist.json: Lista de deseos',
            '- reviews.json: Reseñas escritas',
            '',
            'Si tienes preguntas, contáctanos.',
        ]);
    }

    private function sendEmail(Customer $customer, string $downloadUrl): void
    {
        try {
            Mail::raw(
                "Hola {$customer->name},\n\n".
                "Tu exportación de datos está lista. Descárgala en:\n\n{$downloadUrl}\n\n".
                "El enlace expira en 24 horas.\n\n".
                'Si no solicitaste esta exportación, ignora este correo.',
                fn ($message) => $message->to($customer->email)->subject('Tu exportación de datos GDPR está lista')
            );
        } catch (\Throwable $e) {
            Log::warning('GDPR export email failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
