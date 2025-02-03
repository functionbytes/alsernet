<?php

namespace App\Models\Subscriber;

use App\Http\Resources\V1\NewsletterResource;
use Dflydev\DotAccessData\Data;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Subscriber extends Model
{
    use HasFactory , LogsActivity;

    protected $table = "subscribers";

    protected $fillable = [
        'slack',
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
        return $this->belongsToMany('App\Models\Categorie', 'subscriber_categories', 'subscriber_id', 'category_id');
    }

    public function categoriess() : HasMany
    {
        return $this->hasMany('App\Models\Subscriber\SubscriberCategorie');
    }

    public function lists(): HasMany
    {
        return $this->hasMany('App\Models\Subscriber\SubscriberListsUser');
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

    /**
     * Verificar con Índice HASH (Ideal para búsquedas exactas)
     */
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

    /**
     * Verificar con Particionamiento (Basado en dominio del email)
     */
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

    /**
     * Verificar con Vista Materializada (Tabla estática actualizada)
     */
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


    public function updateNewsletter($data)
    {
        $fieldsToTrack = SubscriberCondition::available()->get()->pluck('slack');
        $idUser = (int)$data['user'];

        // Obtener los datos actuales del usuario
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
                $historyEntries[] = new NewsletterResource([
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

        // Insertar los registros de historial
        if (!empty($historyEntries)) {
            NewsletterHistory::insert($historyEntries);

            // Crear trabajos para cada entrada del historial
            foreach ($historyEntries as $historyEntry) {
                $jobData = [
                    'id_history' => $historyEntry->id,
                    'action_type' => $historyEntry->action_type,
                    'created_at' => now(),
                ];

                NewsletterJob::create($jobData);
            }
        }

        // Actualizar los datos del usuario
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

                // Solo registrar cambios si el valor es diferente
                if ($oldValue != $newValue) {
                    $changes[$field] = [
                        'old_value' => $oldValue,
                        'new_value' => $newValue
                    ];
                    $updateData[$field] = $newValue;
                }
            }
        }

        // Solo registrar log y actualizar si hay cambios
        if (!empty($changes)) {
            SubscriberLog::create([
                'log_name' => 'subscriber_update',
                'description' => "Updated subscriber fields",
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
                'shop' => $auth->shop?->title
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

    public function updateCategoriesWithLog(array $categoriesIds, $auth)
    {
        $changes = [];
        $currentCategories = $this->categories->pluck('id')->toArray();

        $addedCategories = array_diff($categoriesIds, $currentCategories);
        $removedCategories = array_diff($currentCategories, $categoriesIds);

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

        // Solo registrar log y actualizar si hubo cambios
        if (!empty($changes)) {
            SubscriberLog::create([
                'log_name' => 'category_update',
                'description' => "Updated subscriber categories",
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

            $this->categories()->sync($categoriesIds);
        }
    }



}
