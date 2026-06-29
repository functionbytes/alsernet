<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SeoRedirectHit extends Model
{
    public $timestamps = false;

    protected $fillable = ['seo_redirect_id', 'hit_date', 'hit_count'];

    protected $casts = [
        'hit_date' => 'date',
        'hit_count' => 'integer',
    ];

    public function redirect(): BelongsTo
    {
        return $this->belongsTo(SeoRedirect::class, 'seo_redirect_id');
    }

    public static function recordHit(int $redirectId): void
    {
        static::upsert(
            [['seo_redirect_id' => $redirectId, 'hit_date' => today()->toDateString(), 'hit_count' => 1]],
            ['seo_redirect_id', 'hit_date'],
            ['hit_count' => DB::raw('hit_count + 1')]
        );
    }
}
