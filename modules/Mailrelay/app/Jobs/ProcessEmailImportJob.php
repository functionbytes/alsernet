<?php

namespace Modules\Mailrelay\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Mailrelay\Entities\ImportJob;
use Modules\Mailrelay\Entities\MailrelayGroup;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Enums\SubscriberStatus;

class ProcessEmailImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * @var string
     */
    protected $importJobId;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $listId;

    /**
     * @var bool
     */
    protected $validateEmails;

    /**
     * Create a new job instance.
     */
    public function __construct(string $importJobId, string $filePath, string $listId, bool $validateEmails = true)
    {
        $this->importJobId = $importJobId;
        $this->filePath = $filePath;
        $this->listId = $listId;
        $this->validateEmails = $validateEmails;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Find the import job
            $importJob = ImportJob::findOrFail($this->importJobId);

            // Start the import job
            $importJob->start();

            Log::info('Starting email import processing', [
                'import_job_id' => $this->importJobId,
                'file_path' => $this->filePath,
                'list_id' => $this->listId,
            ]);

            // Verify file exists
            if (! Storage::exists($this->filePath)) {
                throw new Exception("Import file not found: {$this->filePath}");
            }

            // Get the full file path
            $fullPath = Storage::path($this->filePath);

            // Read and process the file
            $rows = Excel::toCollection(null, $fullPath)->first();

            if ($rows->isEmpty()) {
                throw new Exception('Import file is empty');
            }

            // Get headers from first row
            $headers = $rows->first();

            // Find email column index
            $emailColumnIndex = $this->findEmailColumn($headers);

            if ($emailColumnIndex === null) {
                throw new Exception("Email column not found in file. Expected column named 'email', 'e-mail', or 'correo'");
            }

            // Remove header row
            $rows->shift();

            // Update total emails count
            $importJob->update(['total_emails' => $rows->count()]);

            // Track validation results
            $validEmails = [];
            $invalidEmails = [];
            $errors = [];

            // Process each row
            foreach ($rows as $index => $row) {
                try {
                    $rowNumber = $index + 2; // +2 because we removed header and Excel is 1-indexed

                    // Extract email from row
                    $email = $row[$emailColumnIndex] ?? null;

                    if (empty($email)) {
                        $errors[] = "Row {$rowNumber}: Empty email";
                        $importJob->incrementProcessed(false);

                        continue;
                    }

                    // Trim and sanitize email
                    $email = trim(strtolower($email));

                    // Validate email
                    if ($this->validateEmails && ! $this->isValidEmail($email)) {
                        $errors[] = "Row {$rowNumber}: Invalid email format - {$email}";
                        $invalidEmails[] = $email;
                        $importJob->incrementProcessed(false);

                        continue;
                    }

                    // Extract additional fields
                    $name = $this->extractName($row, $headers);

                    // Create or update subscriber
                    $subscriber = $this->createOrUpdateSubscriber($email, $name, $row, $headers);

                    // Attach subscriber to list/group if specified
                    if ($this->listId) {
                        $this->attachToList($subscriber, $this->listId);
                    }

                    // Dispatch sync job for this subscriber
                    $this->dispatchSyncJob($subscriber);

                    $validEmails[] = $email;
                    $importJob->incrementProcessed(true);

                    Log::debug('Processed subscriber', [
                        'email' => $email,
                        'subscriber_id' => $subscriber->id,
                    ]);

                } catch (Exception $e) {
                    $rowNumber = $index + 2;
                    $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                    $importJob->incrementProcessed(false);

                    Log::error('Error processing row', [
                        'row_number' => $rowNumber,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Generate report
            $report = [
                'total_rows' => $rows->count(),
                'valid_emails' => count($validEmails),
                'invalid_emails' => count($invalidEmails),
                'errors' => $errors,
                'processed_at' => now()->toDateTimeString(),
            ];

            // Update import job with report
            $importJob->update(['report' => $report]);

            // Mark import as completed
            $importJob->complete();

            // Clean up file
            Storage::delete($this->filePath);

            Log::info('Email import completed successfully', [
                'import_job_id' => $this->importJobId,
                'valid_emails' => count($validEmails),
                'invalid_emails' => count($invalidEmails),
            ]);

        } catch (Exception $e) {
            $this->handleFailure($e);
        }
    }

    /**
     * Find the email column index in headers
     *
     * @param  Collection  $headers
     */
    protected function findEmailColumn($headers): ?int
    {
        $emailAliases = ['email', 'e-mail', 'correo', 'correo electrónico', 'mail', 'correo electronico'];

        foreach ($headers as $index => $header) {
            $normalizedHeader = trim(strtolower($header));
            if (in_array($normalizedHeader, $emailAliases)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Extract name from row
     *
     * @param  Collection  $row
     * @param  Collection  $headers
     */
    protected function extractName($row, $headers): ?string
    {
        $nameAliases = ['name', 'nombre', 'full name', 'fullname', 'full_name'];

        foreach ($headers as $index => $header) {
            $normalizedHeader = trim(strtolower($header));
            if (in_array($normalizedHeader, $nameAliases)) {
                return $row[$index] ?? null;
            }
        }

        return null;
    }

    /**
     * Validate email address
     */
    protected function isValidEmail(string $email): bool
    {
        $validator = Validator::make(
            ['email' => $email],
            ['email' => 'required|email:rfc,dns']
        );

        return ! $validator->fails();
    }

    /**
     * Create or update subscriber
     *
     * @param  Collection  $row
     * @param  Collection  $headers
     */
    protected function createOrUpdateSubscriber(string $email, ?string $name, $row, $headers): Subscriber
    {
        // Extract custom fields
        $customFields = $this->extractCustomFields($row, $headers);

        // Find or create subscriber
        $subscriber = Subscriber::firstOrNew(['email' => $email]);

        // Update subscriber data
        $subscriber->fill([
            'name' => $name ?? $subscriber->name,
            'status' => $subscriber->exists ? $subscriber->status : SubscriberStatus::ACTIVE,
            'custom_fields' => array_merge($subscriber->custom_fields ?? [], $customFields),
            'subscribed_at' => $subscriber->subscribed_at ?? now(),
        ]);

        $subscriber->save();

        return $subscriber;
    }

    /**
     * Extract custom fields from row
     *
     * @param  Collection  $row
     * @param  Collection  $headers
     */
    protected function extractCustomFields($row, $headers): array
    {
        $customFields = [];
        $skipColumns = ['email', 'e-mail', 'correo', 'name', 'nombre', 'mail'];

        foreach ($headers as $index => $header) {
            $normalizedHeader = trim(strtolower($header));

            // Skip standard columns
            if (in_array($normalizedHeader, $skipColumns)) {
                continue;
            }

            $value = $row[$index] ?? null;

            if ($value !== null && $value !== '') {
                $customFields[$header] = $value;
            }
        }

        return $customFields;
    }

    /**
     * Attach subscriber to list/group
     */
    protected function attachToList(Subscriber $subscriber, string $listId): void
    {
        try {
            $group = MailrelayGroup::findOrFail($listId);

            // Attach subscriber to group if not already attached
            if (! $subscriber->groups()->where('mailrelay_groups.id', $group->id)->exists()) {
                $subscriber->groups()->attach($group->id);
            }
        } catch (Exception $e) {
            Log::warning('Could not attach subscriber to list', [
                'subscriber_id' => $subscriber->id,
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatch sync job for subscriber
     */
    protected function dispatchSyncJob(Subscriber $subscriber): void
    {
        // Check if SyncSubscriberJob exists before dispatching
        if (class_exists('App\Jobs\SyncSubscriberJob')) {
            SyncSubscriberJob::dispatch($subscriber->id);
        } else {
            Log::warning('SyncSubscriberJob class not found, skipping sync dispatch', [
                'subscriber_id' => $subscriber->id,
            ]);
        }
    }

    /**
     * Handle job failure
     */
    protected function handleFailure(Exception $exception): void
    {
        Log::error('Email import job failed', [
            'import_job_id' => $this->importJobId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        try {
            $importJob = ImportJob::find($this->importJobId);
            if ($importJob) {
                $importJob->fail($exception->getMessage());
            }
        } catch (Exception $e) {
            Log::error('Could not update import job status', [
                'import_job_id' => $this->importJobId,
                'error' => $e->getMessage(),
            ]);
        }

        // Re-throw exception to mark job as failed
        throw $exception;
    }

    /**
     * The job failed to process.
     */
    public function failed(Exception $exception): void
    {
        $this->handleFailure($exception);
    }
}
