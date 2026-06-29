<?php

namespace Modules\Campaign\Models\Automation;

use Illuminate\Database\Eloquent\Model;
use Modules\Campaign\Domain\Automation\Enum\NodeType;
use Modules\Campaign\Domain\Automation\Enum\TriggerKey;
use Modules\Campaign\Domain\Automation\Flow;
use Modules\Campaign\Domain\Automation\Node;
use Modules\Campaign\Library\Traits\HasUid;
use Modules\Campaign\Models\CampaignMaillist;

/**
 * Automation (DAG) — portado de acellemail App\Model\Automation2.
 * `data` guarda el grafo Flow (nodos+aristas) como JSON. Global (no-SaaS).
 *
 * Esta clase cubre el create + listado (Increment 1). El motor de ejecución y
 * el editor de nodos (Increment 2) añadirán métodos sobre el mismo modelo.
 */
class Automation2 extends Model
{
    use HasUid;

    protected $table = 'campaign_automations';

    public const ITEMS_PER_PAGE = 25;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** @var list<string> */
    protected $fillable = ['uid', 'name', 'mail_list_id', 'status', 'data', 'time_zone', 'segment_id'];

    public static function newDefault(): self
    {
        $a = new self;
        $a->status = self::STATUS_INACTIVE;

        return $a;
    }

    public function mailList()
    {
        return $this->belongsTo(CampaignMaillist::class, 'mail_list_id');
    }

    public function scopeSearch($query, $keyword)
    {
        if (! empty($keyword)) {
            $query->where('name', 'like', '%'.trim($keyword).'%');
        }

        return $query;
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'mail_list_uid' => 'required',
        ];
    }

    /** Grafo Flow decodificado desde `data`. */
    public function getFlow(): Flow
    {
        return Flow::fromJson($this->data);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function enable(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->save();
    }

    public function disable(): void
    {
        $this->status = self::STATUS_INACTIVE;
        $this->save();
    }

    /**
     * Crea la automatización desde el wizard. Persiste el trigger elegido como
     * grafo Flow JSON con un único nodo Trigger (portado de Automation2::createFromArray).
     */
    public function createFromArray(array $params)
    {
        $validator = \Validator::make($params, $this->rules());

        if ($validator->fails()) {
            return $validator;
        }

        $this->fill($params);

        $list = CampaignMaillist::where('uid', $params['mail_list_uid'] ?? null)->first();
        if (! $list) {
            throw new \Exception('Mail list not found.');
        }
        $this->mail_list_id = $list->id;

        $key = TriggerKey::tryFrom($params['trigger_type'] ?? '');
        $data = $key ? ['key' => $key->value] : ['title' => 'Click to choose a trigger'];
        $trigger = new Node(Flow::TRIGGER_ID, NodeType::Trigger, $data);
        $this->data = (new Flow([$trigger], []))->toJson();

        $this->save();

        return $validator;
    }

    public function deleteAndCleanup(): void
    {
        $this->delete();
    }
}
