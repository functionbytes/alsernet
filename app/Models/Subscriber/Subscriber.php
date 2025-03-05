<?php

namespace App\Models\Subscriber;

use App\Http\Resources\V1\SubscriberResource;
use App\Jobs\Subscribers\AddSuscriberListJob;
use App\Jobs\Subscribers\RemoveSuscriberListJob;
use App\Library\Traits\HasUid;
use Dflydev\DotAccessData\Data;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Subscriber extends Model
{
    use LogsActivity , HasUid;

    protected $table = "subscribers";

    public const CONDITION_UNCONFIRMED = 'unconfirmed';
    public const CONDITION_SUBSCRIBED = 'subscribed';
    public const CONDITION_UNSUBSCRIBED = 'unsubscribed';
    public const CONDITION_BLACKLISTED = 'blacklisted';
    public const CONDITION_SPAM_REPORTED = 'spam-reported';
    public const SUBSCRIPTION_TYPE_ADDED = 'added';
    public const SUBSCRIPTION_TYPE_DOUBLE_OPTIN = 'double';
    public const SUBSCRIPTION_TYPE_SINGLE_OPTIN = 'single';
    public const SUBSCRIPTION_TYPE_IMPORTED = 'imported';
    public const VERIFICATION_CONDITION_DELIVERABLE = 'deliverable';
    public const VERIFICATION_CONDITION_UNDELIVERABLE = 'undeliverable';
    public const VERIFICATION_CONDITION_UNKNOWN = 'unknown';
    public const VERIFICATION_CONDITION_RISKY = 'risky';
    public const VERIFICATION_CONDITION_UNVERIFIED = 'unverified';

    protected $fillable = [
        'uid',
        'firstname',
        'lastname',
        'email',
        'sports',
        'parties',
        'condition',
        'commercial',
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

    public function isPendingVerification(): bool
    {
        return is_null($this->check_at);
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

    public function scopeSearch($query, $searchKey)
    {
        if (!$searchKey) {
            return $query;
        }

        if (filter_var($searchKey, FILTER_VALIDATE_EMAIL)) {
            $emailCheck = self::checkWithBTree($searchKey);
            return $emailCheck['exists'] ? $query->where('email', $searchKey) : $query->whereRaw('1=0'); // No coincide, devolver vacío
        }

        return $query->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
            $query->where('subscribers.firstname', 'like', '%' . $searchKey . '%')
                ->orWhere('subscribers.lastname', 'like', '%' . $searchKey . '%')
                ->orWhere(DB::raw("CONCAT(subscribers.firstname, ' ', subscribers.lastname)"), 'like', '%' . $searchKey . '%')
                ->orWhere('subscribers.email', 'like', '%' . $searchKey . '%');
        });
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

                $historyEntries[] = new SubscriberResource([
                    'id_susc_newsletter' => $idUser,
                    'action_type' => $field,
                    'old_value' => $currentData[$field],
                    'new_value' => $data[$field],
                    'changed_at' => now(),
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

    public function updateWithLog(array $data, $auth = null)
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

            $userName = $auth ? $auth->getFullNameAttribute() : 'System';
            $shopTitle = $auth && optional($auth->shop)->title ? $auth->shop->title : 'N/A';

            SubscriberLog::create([
                'log_name' => 'subscriber_update',
                'description' => "Updated subscriber fields",
                'observation' => $data['observation'] ?? null,
                'properties' => json_encode($changes),
                'user_properties' => json_encode([
                    'user' => $userName,
                    'shop' => $shopTitle
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

        $categoriesIds = is_array($categoriesIds) ? $categoriesIds : explode(',', $categoriesIds);
        $categoriesIds = array_filter(array_map('intval', $categoriesIds), fn($id) => $id > 0);

        $currentCategories = $this->subcategorie()->pluck('categorie_id')->toArray();

        $addedCategories = array_diff($categoriesIds, $currentCategories);
        $removedCategories = array_diff($currentCategories, $categoriesIds);

        $defaultLangLists = SubscriberList::query()->where('lang_id', $currentLangId)->where('default', true)->pluck('id')->toArray();

        if ($hasLangChanged) {

            RemoveSuscriberListJob::dispatch($this, [], true);

            $this->categories()->sync($categoriesIds);

            $listsForAddedCategories = SubscriberList::where('lang_id', $currentLangId)
                ->whereHas('listcategories', function ($query) use ($categoriesIds) {
                    $query->whereIn('categories.id', $categoriesIds);
                })->pluck('id')->toArray();

            $newLangLists = array_unique(array_merge($defaultLangLists, $listsForAddedCategories));

            if (!empty($newLangLists)) {
                AddSuscriberListJob::dispatch($this, $newLangLists);
            }

            $changes[] = [
                'old_value' => $currentCategories,
                'new_value' => $categoriesIds,
                'description' => 'Idioma cambiado, listas actualizadas según las nuevas categorías y listas predeterminadas.'
            ];

        } else {

            if (!empty($categoriesIds)) {

                $this->categories()->sync($categoriesIds);

                $listsToAdd = SubscriberList::whereIn('id', function ($query) use ($addedCategories, $currentLangId) {
                    $query->select('list_id')
                        ->from('subscriber_list_categories')
                        ->whereIn('categorie_id', $addedCategories)
                        ->where('lang_id', $currentLangId);
                })->pluck('id')->toArray();

                $listsToAdd = array_unique(array_merge($defaultLangLists, $listsToAdd));

                if (!empty($listsToAdd)) {

                    AddSuscriberListJob::dispatch($this, $listsToAdd);
                    $changes[] = [
                        'old_value' => null,
                        'new_value' => $listsToAdd,
                        'description' => 'Listas añadidas debido a la adición de categorías y listas predeterminadas.'
                    ];

                }

                if (!empty($removedCategories)) {

                    $listsToRemove = SubscriberList::whereIn('id', function ($query) use ($removedCategories, $currentLangId) {
                        $query->select('list_id')
                            ->from('subscriber_list_categories')
                            ->whereIn('categorie_id', $removedCategories)
                            ->where('lang_id', $currentLangId)
                            ->where('lang_id', $currentLangId);;
                    })->pluck('id')->toArray();

                    if (!empty($listsToRemove)) {
                        RemoveSuscriberListJob::dispatch($this, $listsToRemove, false);
                        $changes[] = [
                            'old_value' => $listsToRemove,
                            'new_value' => null,
                            'description' => 'Listas eliminadas debido a la eliminación de categorías.'
                        ];
                    }
                }

            } else {

                $this->categories()->detach();
                $this->removeAllSubscriptions();

                $changes[] = [
                    'old_value' => $this->lists()->pluck('id')->toArray(),
                    'new_value' => null,
                    'description' => 'Todas las listas y categorías eliminadas porque el usuario no tiene categorías asignadas.'
                ];
            }
        }


        if (!empty($changes)) {
            SubscriberLog::create([
                'log_name' => 'category_update',
                'description' => 'Actualización de categorías y listas del suscriptor.',
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


    public function removeFromBlacklist(): void
    {
        $blacklist = SubscriberList::getBlacklistByLang($this->lang_id);

        if ($blacklist) {
            $this->removeSpecificLists([$blacklist->id]);
        }
    }

    public function getInitialList()
    {

        if ($this->isPendingVerification()) {
            return SubscriberList::where('code', 'standby')->where('lang_id', $this->lang_id)->first();
        }

        $blacklist = SubscriberList::getBlacklistByLang($this->lang_id);
        if ($blacklist) {
            return $blacklist;
        }

        $categoriesIds = $this->categories()->pluck('id')->toArray();

        if (!empty($categoriesIds)) {
            $categoryLists = SubscriberList::whereHas('listcategories', function ($query) use ($categoriesIds) {
                $query->whereIn('categories.id', $categoriesIds);
            })->pluck('id')->toArray();

            return !empty($categoryLists) ? $categoryLists : null;
        }

        return SubscriberList::where('default', true)->where('lang_id', $this->lang_id)->pluck('id')->toArray();
    }



    public function suscriberCheckatWithLog($categoriesIds)
    {

        $changes = [];

        $categoriesIds = is_array($categoriesIds) ? $categoriesIds : explode(',', $categoriesIds);
        $categoriesIds = array_filter(array_map('intval', $categoriesIds), fn($id) => $id > 0);

        $defaultLangLists = SubscriberList::query()->where('lang_id', $this->lang_id)->where('default', true)->pluck('id')->toArray();

        if (!empty($categoriesIds)) {

            $listsToAdd = SubscriberList::whereIn('id', function ($query) use ($categoriesIds) {
                $query->select('list_id')
                    ->from('subscriber_list_categories')
                    ->whereIn('categorie_id', $categoriesIds)
                    ->where('lang_id', $this->lang_id);
            })->pluck('id')->toArray();

            $listsToAdd = array_unique(array_merge($defaultLangLists, $listsToAdd));

            if (!empty($listsToAdd)) {
                AddSuscriberListJob::dispatch($this, $listsToAdd);
                $changes[] = [
                    'old_value' => null,
                    'new_value' => $listsToAdd,
                    'description' => 'Listas añadidas debido a la adición de categorías y listas predeterminadas.'
                ];
            }

            if (!empty($changes)) {
                SubscriberLog::create([
                    'log_name' => 'category_update',
                    'description' => 'Website.',
                    'properties' => json_encode($changes),
                    'user_properties' => json_encode([
                        'user' => 'System',
                        'shop' => 'N/A'
                    ]),
                    'subject_type' => self::class,
                    'subject_id' => null,
                    'causer_type' => null,
                    'causer_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

        }

    }

    public function suscriberCategoriesWithLog($categoriesIds)
    {

        $changes = [];

        $categoriesIds = is_array($categoriesIds) ? $categoriesIds : explode(',', $categoriesIds);
        $categoriesIds = array_filter(array_map('intval', $categoriesIds), fn($id) => $id > 0);
        $currentCategories = $this->subcategorie()->pluck('categorie_id')->toArray();
        $categories = is_array($categoriesIds)  ? $categoriesIds : (empty($categoriesIds) ? [] : explode(',', $categoriesIds));
        $hasCategoryChanges = $this->categories()->count() !== count($categories);

        $addedCategories = array_diff($categoriesIds, $currentCategories);
        $removedCategories = array_diff($currentCategories, $categoriesIds);

        $defaultLangLists = SubscriberList::query()
            ->where('lang_id', $this->lang_id)
            ->where('default', true)->pluck('id')->toArray();

        if (!empty($categoriesIds)) {

            $this->categories()->sync($categoriesIds);

            $listsToAdd = SubscriberList::whereIn('id', function ($query) use ($addedCategories) {
                $query->select('list_id')
                    ->from('subscriber_list_categories')
                    ->whereIn('categorie_id', $addedCategories);
            })->pluck('id')->toArray();

            $listsToAdd = array_unique(array_merge($defaultLangLists, $listsToAdd));

            if (!empty($listsToAdd)) {
                AddSuscriberListJob::dispatch($this, $listsToAdd);
                $changes[] = [
                    'old_value' => null,
                    'new_value' => $listsToAdd,
                    'description' => 'Listas añadidas debido a la adición de categorías y listas predeterminadas.'
                ];
            }

            if (!empty($removedCategories)) {
                $listsToRemove = SubscriberList::whereIn('id', function ($query) use ($removedCategories) {
                    $query->select('list_id')
                        ->from('subscriber_list_categories')
                        ->whereIn('categorie_id', $removedCategories);
                })->pluck('id')->toArray();

                if (!empty($listsToRemove)) {
                    RemoveSuscriberListJob::dispatch($this, $listsToRemove, false);
                    $changes[] = [
                        'old_value' => $listsToRemove,
                        'new_value' => null,
                        'description' => 'Listas eliminadas debido a la eliminación de categorías.'
                    ];
                }
            }

            if ($hasCategoryChanges) {

                $changes[] = [
                    'old_value' => $currentCategories,
                    'new_value' => $categoriesIds,
                    'description' => 'Idioma cambiado, listas actualizadas según las nuevas categorías y listas predeterminadas.'
                ];

            }


        } else {

            $this->categories()->detach();
            $this->removeAllSubscriptions();

            $changes[] = [
                'old_value' => $this->lists()->pluck('id')->toArray(),
                'new_value' => null,
                'description' => 'Todas las listas y categorías eliminadas porque el usuario no tiene categorías asignadas.'
            ];
        }


        if (!empty($changes)) {

            SubscriberLog::create([
                'log_name' => 'category_update',
                'description' => 'Website.',
                'properties' => json_encode($changes),
                'user_properties' => json_encode([
                    'user' => 'System',
                    'shop' => 'N/A'
                ]),
                'subject_type' => self::class,
                'subject_id' => null,
                'causer_type' => null,
                'causer_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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
    }

    public function removeSpecificLists(array $listIds): void
    {
        if (!empty($listIds)) {
            SubscriberListUser::where('subscriber_id', $this->id)->whereIn('list_id', $listIds)->delete();
        }
    }

    public function addToList(int $listId): void
    {
        if (!$listId) {
            return;
        }

        $exists = SubscriberListUser::where('subscriber_id', $this->id)->where('list_id', $listId)->exists();

        if (!$exists) {
            SubscriberListUser::create([
                'subscriber_id' => $this->id,
                'list_id' => $listId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    public function addToLists(array $listIds): void
    {
        if (empty($listIds)) {
            return;
        }

        $existingLists = SubscriberListUser::where('subscriber_id', $this->id)->whereIn('list_id', $listIds)->pluck('list_id')->toArray();

        $newLists = array_diff($listIds, $existingLists);

        if (!empty($newLists)) {

            $batchInsert = array_map(fn($listId) => [
                'subscriber_id' => $this->id,
                'list_id' => $listId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $newLists);

            SubscriberListUser::insert($batchInsert);

        }
    }

}
