<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignSuppressionList extends Model
{
    protected $table = 'campaign_suppression_lists';

    protected $fillable = [
        'uid',
        'name',
        'description',
        'type',
        'value',
        'is_global',
    ];

    public static function isSuppressed(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);

        // Email exacto
        if (self::where('type', 'email')->where('value', $email)->exists()) {
            return true;
        }

        // Dominio
        if (self::where('type', 'domain')->where('value', $domain)->exists()) {
            return true;
        }

        // Pattern
        $patterns = self::where('type', 'pattern')->pluck('value');
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $email)) {
                return true;
            }
        }

        return false;
    }

    public static function globalSuppressedDomains(): array
    {
        return self::where('is_global', true)
            ->where('type', 'domain')
            ->pluck('value')
            ->all();
    }

    public static function globalSuppressedEmails(): array
    {
        return self::where('is_global', true)
            ->where('type', 'email')
            ->pluck('value')
            ->all();
    }
}
