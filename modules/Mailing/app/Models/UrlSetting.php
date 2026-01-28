<?php

namespace Modules\Mailing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailing\Traits\HasUid;

class UrlSetting extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailing_url_settings';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'user_id',
        'app_url',
        'webhook_url',
        'api_url',
        'callback_url',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this URL setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the application URL.
     */
    public function getAppUrl(): string
    {
        return $this->app_url ?? config('app.url');
    }

    /**
     * Get the webhook URL.
     */
    public function getWebhookUrl(): string
    {
        return $this->webhook_url ?? route('mailing.webhooks.handle');
    }

    /**
     * Get the API URL.
     */
    public function getApiUrl(): string
    {
        return $this->api_url ?? route('api.mailing.base');
    }

    /**
     * Get the callback URL.
     */
    public function getCallbackUrl(): string
    {
        return $this->callback_url ?? route('mailing.callbacks.handle');
    }
}
