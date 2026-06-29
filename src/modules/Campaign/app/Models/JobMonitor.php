<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Throwable;

/**
 * Monitor de jobs / batches asociados a una entidad (campaña, automatización, etc.).
 *
 * Reemplaza la dependencia rota a Modules\Campaign\Models\JobMonitor. Mantiene los
 * mismos métodos públicos que el trait TrackJobs espera:
 *   - makeInstance($subject, $jobType)
 *   - setDone(), setFailed(\Throwable $e)
 *   - cancel(), cancelWithoutDeleteBatch()
 *   - scope byJobType($jobType)
 *
 * @property int $id
 * @property string $subject_name FQCN del modelo "dueño" (ej: Modules\Campaign\Models\Campaign)
 * @property int $subject_id
 * @property string $job_type FQCN del job (LoadCampaign, RunCampaign, etc.)
 * @property string|null $job_id UUID del job en la cola
 * @property string|null $batch_id UUID del batch (si aplica)
 * @property string $status queued|running|done|failed|cancelled
 * @property string|null $error
 */
class JobMonitor extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'campaign_job_monitors';

    protected $fillable = [
        'subject_name',
        'subject_id',
        'job_type',
        'job_id',
        'batch_id',
        'status',
        'error',
    ];

    /**
     * Crea una instancia (sin persistir) asociada a un sujeto y tipo de job.
     */
    public static function makeInstance(Model $subject, string $jobType): self
    {
        $monitor = new self;
        $monitor->subject_name = $subject::class;
        $monitor->subject_id = (int) $subject->getKey();
        $monitor->job_type = $jobType;
        $monitor->status = self::STATUS_QUEUED;

        return $monitor;
    }

    public function setRunning(): self
    {
        $this->status = self::STATUS_RUNNING;
        $this->save();

        return $this;
    }

    public function setDone(): self
    {
        $this->status = self::STATUS_DONE;
        $this->error = null;
        $this->save();

        return $this;
    }

    public function setFailed(Throwable $e): self
    {
        $this->status = self::STATUS_FAILED;
        $this->error = $e->getMessage();
        $this->save();

        return $this;
    }

    public function setCancelled(): self
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();

        return $this;
    }

    /**
     * Cancela el batch (si aplica) y elimina el registro.
     */
    public function cancel(): void
    {
        $this->cancelWithoutDeleteBatch();
        $this->delete();
    }

    /**
     * Cancela el batch (si aplica) pero conserva el registro como cancelado.
     */
    public function cancelWithoutDeleteBatch(): void
    {
        if (! empty($this->batch_id)) {
            try {
                $batch = Bus::findBatch($this->batch_id);
                $batch?->cancel();
            } catch (Throwable) {
                // Si el batch ya no existe, ignorar.
            }
        }
        $this->setCancelled();
    }

    public function scopeByJobType($query, string $jobType)
    {
        return $query->where('job_type', $jobType);
    }

    public function scopeBySubject($query, Model $subject)
    {
        return $query
            ->where('subject_name', $subject::class)
            ->where('subject_id', $subject->getKey());
    }
}
