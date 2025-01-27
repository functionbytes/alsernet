<?php

namespace App\Models\Newsletter;

use App\Http\Resources\V1\NewsletterResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Newsletter extends Model
{
    use HasFactory;

    protected $table = "newsletters";

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
        return $this->belongsToMany('App\Models\Categorie', 'newsletter_categories', 'newsletter_id', 'category_id');
    }

    public function categoriess() : HasMany
    {
        return $this->hasMany('App\Models\Newsletter\NewsletterCategorie');
    }

    public function lists(): HasMany
    {
        return $this->hasMany('App\Models\Newsletter\NewsletterListsUser');
    }

    public function records(): HasMany
    {
        return $this->hasMany('App\Models\Newsletter\NewsletterRecord');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang','lang_id','id');
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
        $fieldsToTrack = NewsletterCondition::available()->get()->pluck('slack');
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


}
