<?php

namespace Modules\Horizon\Models;

use app\Library\Lockable;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Modules\Core\Models\Jobs\Job;

/**
 * @property int $id
 * @property string $uid
 * @property string $subject_name
 * @property int $subject_id
 * @property string|null $batch_id
 * @property int|null $job_id
 * @property string|null $job_type
 * @property string|null $error
 * @property string|null $data
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Job|null $job
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor byJobType($jobType)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereJobType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereSubjectName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobMonitor whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class JobMonitor extends Model
{
    use HasFactory;
    use HasUid;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    public static function makeInstance($subject, $jobType)
    {
        $monitor = new self;
        $monitor->status = self::STATUS_QUEUED;
        $monitor->subject_name = get_class($subject);
        $monitor->subject_id = $subject->id;
        $monitor->job_type = $jobType;

        // Return
        return $monitor;
    }

    public function scopeByJobType($query, $jobType)
    {
        return $query->where('job_type', $jobType);
    }

    public function getBatch()
    {
        if (is_null($this->batch_id)) {
            return;
        }

        return Bus::findBatch($this->batch_id);
    }

    public function withExclusiveLock($closure)
    {
        $lockFile = storage_path('app/lock-job-monitor-'.$this->uid);
        $lock = new Lockable($lockFile);
        $lock->getExclusiveLock(function ($lockReader) use ($closure) {
            $closure($this->refresh());
        }, $timeout = 60);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function getJob()
    {
        return $this->job;
    }

    public function hasJob()
    {
        return ! is_null($this->job_id);
    }

    public function hasBatch()
    {
        return ! is_null($this->batch_id);
    }

    public function setFailed($exception)
    {
        $this->status = self::STATUS_FAILED;
        $errorMsg = 'Error executing job. '.$exception->getMessage();
        $this->error = $errorMsg;
        $this->save();
    }

    public function setRunning()
    {
        $this->status = self::STATUS_RUNNING;
        $this->save();
    }

    public function setDone()
    {
        $this->status = self::STATUS_DONE;
        $this->save();
    }

    public function setQueued()
    {
        $this->status = self::STATUS_QUEUED;
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_RUNNING, self::STATUS_QUEUED]);
    }

    public function getJsonData()
    {
        // Also convert null or empty string to an empty array ([])
        return json_decode($this->data, true) ?: [];
    }

    public function setJsonData($data)
    {
        $this->data = json_encode($data);
        $this->save();
    }

    public function updateJsonData($data)
    {
        $json = $this->getJsonData();
        $json = array_merge($json, $data);
        $this->setJsonData($json);
    }

    public function cancelWithoutDeleteBatch()
    {
        $this->cancelBatch();
    }

    public function cancel()
    {
        $this->cancelJob(); // if any
        $this->cancelBatch(); // if any

        // For now, do not store cancelled job
        // So, just delete the record
        $this->delete();
    }

    private function cancelJob()
    {
        // Get the job record in the `jobs` database table
        $job = $this->getJob();

        // Remove it from queue, if any
        if (! is_null($job)) {
            $job->delete();
        }
    }

    private function cancelBatch()
    {
        // Then get the batch
        // This is not the batch record in job_batches model
        // So we can just cancel it to have its remaining jobs perish!
        // It will be pruned with queue:prune-batches command
        $batch = $this->getBatch();
        if (! is_null($batch)) {
            $batch->cancel();
        }
    }

    public function isDone()
    {
        return $this->status == self::STATUS_DONE;
    }

    public function isFailed()
    {
        return $this->status == self::STATUS_FAILED;
    }
}
