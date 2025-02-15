<?php

namespace App\Models\Subscriber;

use App\Jobs\AddSuscriberListJob;
use App\Jobs\SyncSuscriberListJob;
use App\Jobs\RemoveSuscriberListJob;
use App\Models\Categorie;
use Dflydev\DotAccessData\Data;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Http\Resources\V1\SubscriberResource;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use App\Library\Traits\HasUid;

class Subscriber extends Model
{
    use LogsActivity , HasUid;

    protected $table = "subscribers";

    public const STATUS_SUBSCRIBED = 'subscribed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_BLACKLISTED = 'blacklisted';
    public const STATUS_SPAM_REPORTED = 'spam-reported';
    public const STATUS_UNCONFIRMED = 'unconfirmed';
    public const SUBSCRIPTION_TYPE_ADDED = 'added';
    public const SUBSCRIPTION_TYPE_DOUBLE_OPTIN = 'double';
    public const SUBSCRIPTION_TYPE_SINGLE_OPTIN = 'single';
    public const SUBSCRIPTION_TYPE_IMPORTED = 'imported';

    public const VERIFICATION_STATUS_DELIVERABLE = 'deliverable';
    public const VERIFICATION_STATUS_UNDELIVERABLE = 'undeliverable';
    public const VERIFICATION_STATUS_UNKNOWN = 'unknown';
    public const VERIFICATION_STATUS_RISKY = 'risky';
    public const VERIFICATION_STATUS_UNVERIFIED = 'unverified';

//
//    protected static function boot()
//    {
//        parent::boot();
//
//
//        static::saved(function ($subscriber) {
//
//            $subscriber->syncCategories();
//
//        });
//
//        static::updated(function ($subscriber) {
//
//            dd($subscriber);
//            if ($subscriber->isDirty('parties') && $subscriber->parties == 1) {
//                $subscriber->removeFromAllMailingLists();
//            }
//
//            $subscriber->syncCategories();
//
//        });
//
//        static::deleted(function ($subscriber) {
//            $subscriber->removeFromAllMailingLists();
//            $subscriber->categories()->detach();
//        });
//
//    }

    protected $fillable = [
        'uid',
        'firstname',
        'lastname',
        'email',
        'ids_sports',
        'erp',
        'lopd',
        'none',
        'sports',
        'send',
        'parties',
        'suscribe',
        'check',
        'lang_id',
        'check_at',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
    {
            return $query->where('uid', $uid)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Categorie', 'subscriber_categories', 'subscriber_id', 'categorie_id');
    }

    public function subcategorie() : HasMany
    {
        return $this->hasMany('App\Models\Subscriber\SubscriberCategorie');
    }

    public function lists(): HasMany
    {
        return $this->hasMany('App\Models\Subscriber\SubscriberListUser');
    }

    public function records(): HasMany
    {
        return $this->hasMany('App\Models\Subscriber\SubscriberLog');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang','lang_id','id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany('App\Models\Subscriber\SubscriberLog', 'subject_id')->latest();
    }

    public static function checkWithBTree($email)
    {
        $email = strtolower($email);
        $exists = self::where('email', $email)->exists();
        $data = $exists ? self::where('email', $email)->first() : null;
        return ['exists' => $exists, 'data' => $data];
    }

    public static function checkWithHash($email)
    {
        $email = strtolower($email);
        $exists = self::whereRaw('MD5(email) = MD5(?)', [$email])->exists();
        $data = $exists ? self::whereRaw('MD5(email) = MD5(?)', [$email])->first() : null;
        return ['exists' => $exists, 'data' => $data];
    }

    /**
     * Verificar con Comparación BINARY (Comparación exacta)
     */
    public static function checkWithBinary($email)
    {
        $email = strtolower($email);
        $exists = self::whereRaw('BINARY email = ?', [$email])->exists();
        $data = $exists ? self::whereRaw('BINARY email = ?', [$email])->first() : null;
        return ['exists' => $exists, 'data' => $data];
    }

    public static function checkWithPartition($email)
    {
        $email = strtolower($email);
        $domain = substr(strrchr($email, "@"), 1);
        $exists = self::where('email', $email)
            ->where('email', 'LIKE', "%@$domain")
            ->exists();
        $data = $exists ? self::where('email', $email)
            ->where('email', 'LIKE', "%@$domain")
            ->first() : null;
        return ['exists' => $exists, 'data' => $data];
    }

    public static function checkWithMaterializedView($email)
    {
        $email = strtolower($email);
        $exists = self::from('subscriber_materialized_view')
            ->where('email', $email)
            ->exists();
        $data = $exists ? self::from('subscriber_materialized_view')
            ->where('email', $email)
            ->first() : null;
        return ['exists' => $exists, 'data' => $data];
    }


    public function updateSubscriber($data)
    {
        $fieldsToTrack = SubscriberCondition::available()->get()->pluck('uid');
        $idUser = (int)$data['user'];

        $currentData = $this->find($idUser);

        $updateData = [];
        $historyEntries = [];

        foreach ($fieldsToTrack as $field) {
            if (isset($data[$field]) && (int)$currentData[$field] !== (int)$data[$field]) {

                if ($field === 'sports') {
                    $this->updateSportsDelete($data);
                } elseif ($field === 'none') {
                    $this->removeNone($data);
                }

                // Crear un registro de historial
                $historyEntries[] = new SubscriberResource([
                    'id_susc_newsletter' => $idUser,
                    'action_type' => $field,
                    'old_value' => $currentData[$field],
                    'new_value' => $data[$field],
                    'changed_at' => now(), // Usa la fecha y hora actual
                ]);

                $updateData[$field] = $data[$field];
                $this->sendEmail($data, $field);
            }
        }

        if (!empty($historyEntries)) {

            SubscriberHistory::insert($historyEntries);

            foreach ($historyEntries as $historyEntry) {
                $jobData = [
                    'id_history' => $historyEntry->id,
                    'action_type' => $historyEntry->action_type,
                    'created_at' => now(),
                ];

                SubscriberJob::create($jobData);
            }
        }

        if (!empty($updateData)) {
            $this->where('id_susc_newsletter', $idUser)->update($updateData);
        }
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable()->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");
    }


    public function updateWithLog(array $data, $auth)
    {
        $changes = [];
        $updateData = [];

        foreach ($data as $field => $newValue) {
            if ($this->isFillable($field)) {
                $oldValue = $this->$field;

                if ($oldValue != $newValue) {
                    $changes[$field] = [
                        'old_value' => $oldValue,
                        'new_value' => $newValue
                    ];
                    $updateData[$field] = $newValue;
                }
            }
        }

        if (!empty($changes)) {

            SubscriberLog::create([
                'log_name' => 'subscriber_update',
                'description' => "Updated subscriber fields",
                'observation' => $data['observation'] ?? null,
                'properties' => json_encode($changes),
                'user_properties' => json_encode([
                    'user' => $auth->getFullNameAttribute(),
                    'shop' => $auth->shop?->title
                ]),
                'subject_type' => self::class,
                'subject_id' => $this->id,
                'causer_type' => auth()->check() ? get_class(auth()->user()) : null,
                'causer_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->update($updateData);
        }
    }

    public static function createWithLog(array $data, $auth)
    {
        $subscriber = self::create($data);

        SubscriberLog::create([
            'log_name' => 'subscriber_created',
            'description' => "Created new subscriber",
            'properties' => json_encode($data),
            'user_properties' => json_encode([
                'user' => $auth->getFullNameAttribute(),
                'shop' => $auth->shop->title
            ]),
            'subject_type' => self::class,
            'subject_id' => $subscriber->id,
            'causer_type' => auth()->check() ? get_class(auth()->user()) : null,
            'causer_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $subscriber;
    }

    public function updateCategoriesWithLog($categoriesIds, $auth)
    {
        $changes = [];

        // Verificar y normalizar $categoriesIds
        $categoriesIds = is_string($categoriesIds)
            ? array_filter(array_map('trim', explode(',', $categoriesIds)))
            : (array) $categoriesIds;

        $categoriesIds = array_map('strval', $categoriesIds);
        $defaultCategoriesOriginal = $this->subcategorie()->pluck('categorie_id')->toArray();

        // Obtener listas predeterminadas de suscriptores según el idioma
        $defaultSubscriberLists = SubscriberList::default()->lang($this->lang_id)->pluck('id')->toArray();
        $originalLists = $this->lists()->pluck('id')->unique()->toArray();

        // Determinar categorías añadidas y eliminadas
        $addedCategories = array_diff($categoriesIds, $defaultCategoriesOriginal);
        $removedCategories = array_diff($defaultCategoriesOriginal, $categoriesIds);

        // 🔥 Determinar listas de suscriptores a eliminar
        if ($this->parties == 1) {
            // 🔥 Caso 1: Eliminar TODAS las listas activas
            $subscriberListsToRemove = $originalLists;
            $subscriberListsToAdd = []; // No agregamos listas en este caso

        } elseif (empty($categoriesIds) && $this->parties==0) {
            // 🔥 Caso 2: Si no hay categorías, eliminar solo listas predeterminadas
            $subscriberListsToRemove = $defaultSubscriberLists;
            $subscriberListsToAdd = [];
        } else {
            // Caso normal: Agregar listas predeterminadas si es necesario
            $subscriberListsToRemove = [];
            $subscriberListsToAdd = array_diff($defaultSubscriberLists, $originalLists);
        }

        // Registrar cambios en categorías
        foreach ($addedCategories as $categoryId) {
            $changes[] = [
                'old_value' => null,
                'new_value' => $categoryId,
                'description' => "Added category ID {$categoryId}"
            ];
        }

        foreach ($removedCategories as $categoryId) {
            $changes[] = [
                'old_value' => $categoryId,
                'new_value' => null,
                'description' => "Removed category ID {$categoryId}"
            ];
        }

        // Registrar cambios en listas de suscriptores
        foreach ($subscriberListsToAdd as $listId) {
            $changes[] = [
                'old_value' => null,
                'new_value' => $listId,
                'description' => "Added to subscriber list ID {$listId}"
            ];
        }

        foreach ($subscriberListsToRemove as $listId) {
            $changes[] = [
                'old_value' => $listId,
                'new_value' => null,
                'description' => "Removed from subscriber list ID {$listId}"
            ];
        }

        // Registrar log solo si hubo cambios
        if (!empty($changes)) {

            SubscriberLog::create([
                'log_name' => 'category_update',
                'description' => "Updated subscriber categories",
                'properties' => json_encode($changes),
                'user_properties' => json_encode([
                    'user' => $auth->getFullNameAttribute(),
                    'shop' => optional($auth->shop)->title,
                ]),
                'subject_type' => self::class,
                'subject_id' => $this->id,
                'causer_type' => isset($auth) ? get_class($auth) : null,
                'causer_id' => $auth->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Procesar suscripciones y desuscripciones a categorías
            $this->processListUpdates($addedCategories, 'subscribeToCategorie');
            $this->processListUpdates($removedCategories, 'unsubscribeFromCategorie');

            // Procesar suscripciones y desuscripciones a listas de suscriptores
            if (!empty($subscriberListsToAdd)) {
                AddSuscriberListJob::dispatch($this->id, $subscriberListsToAdd);
            }

            if (!empty($subscriberListsToRemove)) {
                RemoveSuscriberListJob::dispatch($this->id, $subscriberListsToRemove);
            }
        }
    }

    private function processListUpdates($categories, $method)
    {
        if (empty($categories)) {
            return;
        }

        $newLists = SubscriberList::whereIn('id', function ($query) use ($categories) {
            $query->select('list_id')
                ->distinct()
                ->from('subscriber_list_categories')
                ->whereIn('categorie_id', $categories)
                ->where('lang_id', $this->lang_id);
        })->pluck('id')->toArray();

        $this->$method($categories, $newLists);
    }

    /**
     * Suscribirse a una categoría y asignar listas de suscriptores.
     */
    public function subscribeToCategorie($categoriesIds, $mailingLists)
    {
        if (empty($categoriesIds)) {
            return;
        }

        foreach ($categoriesIds as $id) {
            $this->categories()->syncWithoutDetaching([$id]);
        }

        if (!empty($mailingLists)) {
            AddSuscriberListJob::dispatch($this->id, $mailingLists);
        }
    }

    /**
     * Desuscribirse de una categoría y quitar listas de suscriptores.
     */
    public function unsubscribeFromCategorie($categoriesIds, $mailingLists)
    {
        if (empty($categoriesIds)) {
            return;
        }

        foreach ($categoriesIds as $id) {
            $this->categories()->detach($id);
        }

        if (!empty($mailingLists)) {
            RemoveSuscriberListJob::dispatch($this->id, $mailingLists);
        }
    }

    /**
     * Remover todas las listas de suscriptores.
     */
    public function removeFromAllMailingLists()
    {
        $mailingListIds = $this->mailingLists()->pluck('list_id')->toArray();

        if (!empty($mailingListIds)) {
            RemoveSuscriberListJob::dispatch($this->id, $mailingListIds);
        }
    }

}
