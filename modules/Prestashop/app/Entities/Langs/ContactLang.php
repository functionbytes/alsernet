<?php

namespace Modules\Prestashop\Entities\Langs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Contact;
use Modules\Prestashop\Entities\Language;
use Modules\Prestashop\Entities\Shop\Shop;

class ContactLang extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_contact_lang';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_contact',
        'id_lang',
        'id_shop',
        'name',
        'description',
    ];

    protected $casts = [
        'id_contact' => 'integer',
        'id_lang' => 'integer',
        'id_shop' => 'integer',
    ];

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('id_contact', $this->getAttribute('id_contact'))
            ->where('id_lang', $this->getAttribute('id_lang'))
            ->where('id_shop', $this->getAttribute('id_shop'));
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'id_contact');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }
}
