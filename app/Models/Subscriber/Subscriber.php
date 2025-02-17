<?php

namespace App\Models\Subscriber;

use App\Jobs\AddSuscriberListJob;
use App\Jobs\SyncSuscriberListJob;
use App\Jobs\RemoveSuscriberListJob;
use App\Library\Log;
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
        'erp',
        'lopd',
        'none',
        'sports',
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
                    'shop' => optional($auth->shop)->title
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


    public function updateCategoriesWithLog($categoriesIds, $auth, $hasLangChanged, $currentLangId, $previousLangId)
    {
        $changes = [];

        // Normalizar IDs de categorías
        $categoriesIds = is_array($categoriesIds) ? $categoriesIds : explode(',', $categoriesIds);
        $categoriesIds = array_map('strval', array_filter($categoriesIds));

        // 🔹 Obtener listas antes de hacer modificaciones
        $defaultCategoriesOriginal = $this->subcategorie()->pluck('categorie_id')->toArray();
        $originalLists = $this->lists()->pluck('id')->unique()->toArray();
        $newDefaultSubscriberLists = SubscriberList::default()->lang($currentLangId)->pluck('id')->toArray();
        $previousLangLists = SubscriberList::default()->lang($previousLangId)->pluck('id')->toArray();

        // Detectar cambios en categorías
        $addedCategories = array_diff($categoriesIds, $defaultCategoriesOriginal);
        $removedCategories = array_diff($defaultCategoriesOriginal, $categoriesIds);

        // 🔹 Manejo de cambio de idioma
        if ($hasLangChanged) {


            Log::info("📌 Cambio de idioma detectado: Eliminando listas anteriores y actualizando categorías.");

            if (!empty($originalLists)) {
                RemoveSuscriberListJob::dispatch($this, $originalLists);
            }

            $this->categories()->sync($categoriesIds); // Solo una sincronización de categorías

            if (!empty($newDefaultSubscriberLists)) {
                AddSuscriberListJob::dispatch($this, $newDefaultSubscriberLists);
            }

            Log::info("📌 Categorías actualizadas al nuevo idioma ({$currentLangId}): " . implode(', ', $categoriesIds));
            Log::info("📌 Listas del nuevo idioma añadidas: " . implode(', ', $newDefaultSubscriberLists));
        } else {
            // 🔹 Manejo sin cambio de idioma
            if (!empty($addedCategories) || !empty($removedCategories)) {
                Log::info("📌 Actualizando categorías: Añadidas " . implode(', ', $addedCategories) . " | Eliminadas " . implode(', ', $removedCategories));
                $this->categories()->sync($categoriesIds);
            }

            // 🔹 Eliminar listas relacionadas con categorías eliminadas
            $subscriberListsToRemove = SubscriberList::whereIn('id', function ($query) use ($removedCategories, $currentLangId) {
                $query->select('list_id')
                    ->from('subscriber_list_categories')
                    ->whereIn('categorie_id', $removedCategories)
                    ->where('lang_id', $currentLangId);
            })->pluck('id')->toArray();

            $subscriberListsToRemove = array_diff($subscriberListsToRemove, $newDefaultSubscriberLists);

            if (!empty($subscriberListsToRemove)) {
                Log::info("📌 Eliminando listas relacionadas con categorías eliminadas: " . implode(', ', $subscriberListsToRemove));
                RemoveSuscriberListJob::dispatch($this, $subscriberListsToRemove);
            }

            // 🔹 Agregar listas relacionadas con nuevas categorías
            $subscriberListsToAdd = SubscriberList::whereIn('id', function ($query) use ($addedCategories, $currentLangId) {
                $query->select('list_id')
                    ->from('subscriber_list_categories')
                    ->whereIn('categorie_id', $addedCategories)
                    ->where('lang_id', $currentLangId);
            })->pluck('id')->toArray();

            if (!empty($subscriberListsToAdd)) {
                Log::info("📌 Agregando listas relacionadas con nuevas categorías: " . implode(', ', $subscriberListsToAdd));
                AddSuscriberListJob::dispatch($this, $subscriberListsToAdd);
            }
        }

        // 🔹 Registrar cambios en listas
        $changes = array_merge(
            array_map(fn($id) => ['old_value' => null, 'new_value' => $id, 'description' => "Added to subscriber list ID {$id}"], $subscriberListsToAdd ?? []),
            array_map(fn($id) => ['old_value' => $id, 'new_value' => null, 'description' => "Removed from subscriber list ID {$id}"], $subscriberListsToRemove ?? [])
        );

        if (!empty($changes)) {
            SubscriberLog::create([
                'log_name' => 'category_update',
                'description' => "Updated subscriber categories and lists",
                'properties' => json_encode($changes),
                'user_properties' => json_encode([
                    'user' => $auth->getFullNameAttribute(),
                    'shop' => optional($auth->shop)->title,
                ]),
                'subject_type' => self::class,
                'subject_id' => $this->id,
                'causer_type' => get_class($auth),
                'causer_id' => $auth->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }




    private function processListUpdates($categories, $method)
    {
        if (empty($categories)) return;

        $newLists = SubscriberList::whereIn('id', function ($query) use ($categories) {
            $query->select('list_id')
                ->from('subscriber_list_categories')
                ->whereIn('categorie_id', $categories)
                ->where('lang_id', $this->lang_id);
        })->pluck('id')->toArray();

        $this->$method($categories, $newLists);
    }

    public function subscribeToCategorie($categoriesIds, $mailingLists)
    {
        foreach ($categoriesIds as $id) {
            $this->categories()->syncWithoutDetaching([$id]);
        }

        if (!empty($mailingLists)) {
            AddSuscriberListJob::dispatch($this, $mailingLists);
        }
    }

    public function unsubscribeFromCategorie($categoriesIds, $mailingLists)
    {
        foreach ($categoriesIds as $id) {
            $this->categories()->detach($id);
        }

        if (!empty($mailingLists)) {
            RemoveSuscriberListJob::dispatch($this, $mailingLists);
        }
    }

    public function removeFromAllMailingLists()
    {
        $mailingListIds = $this->mailingLists()->pluck('list_id')->toArray();
        if (!empty($mailingListIds)) {
            RemoveSuscriberListJob::dispatch($this, $mailingListIds);
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

    public function removeAllSubscriptions(): void
    {
        SubscriberListUser::where('subscriber_id', $this->id)->delete();
        SubscriberCategorie::where('subscriber_id', $this->id)->delete();
    }

    public function removeSpecificLists(array $listIds): void
    {
        if (!empty($listIds)) {
            SubscriberListUser::where('subscriber_id', $this->id)
                ->whereIn('list_id', $listIds)
                ->delete();
        }
    }

    public function addToLists(array $listIds): void
    {
        if (empty($listIds)) {
            return;
        }

        $existingLists = SubscriberListUser::where('subscriber_id', $this->id)
            ->whereIn('list_id', $listIds)
            ->pluck('list_id')
            ->toArray();

        $newLists = array_diff($listIds, $existingLists);

        if (!empty($newLists)) {
            $batchInsert = array_map(fn($listId) => [
                'subscriber_id' => $this->id,
                'list_id' => $listId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $newLists);

            SubscriberListUser::insert($batchInsert);
            Log::info("Suscriptor ID {$this->id} añadido a listas: " . implode(', ', $newLists));
        }
    }

}
