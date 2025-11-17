<?php

namespace App\Jobs\Subscribers;

use App\Mail\Subscribers\SubscriberCheckMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Subscriber\Subscriber;
use Illuminate\Support\Facades\Mail;
use App\Models\Layout\Layout;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;

class SubscriberCheckatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Subscriber $subscriber;
    public ?Layout $layout = null;

    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function handle(): void
    {

        $this->layout = Layout::alias('suscription_check_email')->lang($this->subscriber->lang_id)->first();

        if (!$this->layout) {
            Log::warning('Layout not found for subscriber', [
                'subscriber_id' => $this->subscriber->id,
                'lang_id' => $this->subscriber->lang_id
            ]);
            return;
        }

        Mail::send(new SubscriberCheckMail($this->subscriber, $this->layout));
    }
}
