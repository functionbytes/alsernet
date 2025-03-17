<?php

namespace App\Models\Subscriber;

use App\Imports\SubscribersImport;
use App\Jobs\ExportSubscribersJob;
use App\Jobs\Subscribers\Subscribers\ImportSubscribersJob;
use App\Library\StringHelper;
use App\Library\Traits\HasCache;
use App\Library\Traits\QueryHelper;
use App\Library\Traits\TrackJobs;
use Illuminate\Database\Eloquent\Model;
use App\Library\Traits\HasUid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\Csv\Writer;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class SubscriberImport extends Model
{

    use QueryHelper;
    use TrackJobs;
    use HasUid;
    use HasCache;

    protected $table = "subscriber_imports";

    public const IMPORT_TEMP_DIR = 'tmp\import';
    public const EXPORT_TEMP_DIR = 'tmp\export';


    public static $itemsPerPage = 25;

    protected $fillable = [
        'uid',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public static function getAll()
    {
        return self::select('*');
    }

    public function dispatchImportJob($filepath)
    {
        $job = new ImportSubscribersJob($this, $filepath);
        $monitor = $this->dispatchWithMonitor($job);
        return $monitor;
    }
    public function import($file, $progressCallback = null, $invalidRecordCallback = null)
    {
        $processed = 0;
        $failed = 0;

        try {

            $message = 'Importación iniciada...';

            if (!is_null($progressCallback)) {
                $progressCallback($processed, null, $failed, $message);
            }

            Excel::import(new SubscribersImport(
                $progressCallback,
                null,
                $invalidRecordCallback
            ), $file);


        } catch (\Throwable $e) {
            DB::rollBack();
            $message = 'Error durante la importación: ' . $e->getMessage();

            if (!is_null($progressCallback)) {
                $progressCallback($processed, null, $failed, $message);
            }
            throw new Exception(substr($e->getMessage(), 0, 512));
        } finally {
            //if (!is_null($progressCallback)) {
              //  $progressCallback($processed, null, $failed, $message);
            //}
        }
    }



    public function export($progressCallback = null, $segment = null)
    {
        $processed = 0;
        $total = 0;
        $message = null;
        $failed = 0;

        $pageSize = 1000;

        if (!is_null($progressCallback)) {
            $progressCallback($processed, $total, $failed, $message = 'La tarea de exportación está en cola...');
        }

        $file = $this->getExportFilePath();

        if (!file_exists($file)) {
            touch($file);
            chmod($file, 0777);
        }

        // CSV writer
        $writer = Writer::createFromPath($file, 'w+');
        $query = is_null($segment) ? $this->subscribers() : null;
        $total = $query->count();

        $headers = [
            'status',
            'uid',
            'created_at',
            'updated_at'
        ];

        $writer->insertOne($headers);

        cursorIterate($query, $orderBy = 'campaigns_maillists_subscribers.id', $pageSize, function ($subscribers, $page) use ($writer, &$processed, &$total, &$failed, &$message, $pageSize, $progressCallback) {
            $records = collect($subscribers)->map(function ($item) {

                $attributes = [
                    'uid'         => $item->uid,
                    'status'      => $item->status,
                    'firstname'      => $item->subscriber->firstname,
                    'lastname'      => $item->subscriber->lastname,
                    'email'      => $item->subscriber->email,
                    'lang'      => $item->subscriber->lang_id,
                    'created_at'  => $item->created_at->toString(),
                    'updated_at'  => $item->updated_at->toString(),
                ];

                return $attributes;

            })->toArray();

            // Escribir el lote en el archivo (modo append)
            $writer->insertAll($records);

            // Incrementar contador de procesados
            $processed += sizeof($records);

            // Callback de progreso
            if (!is_null($progressCallback)) {
                $message = str_replace([':processed', ':total'], [$processed, $total],
                    'La exportación está en curso, :processed / :total de registros escritos'
                );

                $progressCallback($processed, $total, $failed, $message);
            }
        });


        if (!is_null($progressCallback)) {

            $message = str_replace([':procesada', ':total'], [$processed, $total],
                'Exportación completa, processed: :procesada / :total'
            );

            $progressCallback($processed, $total, $failed, $message);


        }
    }

    public function uploadCsv(\Illuminate\Http\UploadedFile $httpFile)
    {
        $filename = "import-".uniqid().".csv";

        // store it to storage/
        $httpFile->move($this->getImportFilePath(), $filename);

        // Example of outcome: /home/App/storage/app/tmp/import-000000.csv
        $filepath = $this->getImportFilePath($filename);

        // Make sure file is accessible
        chmod($filepath, 0775);

        return $filepath;
    }

    public function readCsv($file)
    {
        try {
            // Fix the problem with MAC OS's line endings
            if (!ini_get('auto_detect_line_endings')) {
                ini_set('auto_detect_line_endings', '1');
            }

            // return false or an encoding name
            $encoding = StringHelper::detectEncoding($file);

            if ($encoding == false) {
                // Cannot detect file's encoding
            } elseif ($encoding != 'UTF-8') {
                // Convert from {$encoding} to UTF-8";
                StringHelper::toUTF8($file, $encoding);
            } else {
                // File encoding is UTF-8
                StringHelper::checkAndRemoveUTF8BOM($file);
            }

            // Run this method anyway
            // to make sure mb_convert_encoding($content, 'UTF-8', 'UTF-8') is always called
            // which helps resolve the issue of
            //     "Error executing job. SQLSTATE[HY000]: General error: 1366 Incorrect string value: '\x83??s k...' for column 'company' at row 2562 (SQL: insert into `dlk__tmp_subscribers..."
            StringHelper::toUTF8($file, 'UTF-8');

            // Read CSV files
            $reader = \League\Csv\Reader::createFromPath($file);
            $reader->setHeaderOffset(0);
            // get the headers, using array_filter to strip empty/null header
            // to avoid the error of "InvalidArgumentException: Use a flat array with unique string values in /home/nghi/mailixa/vendor/league/csv/src/Reader.php:305"

            $headers = $reader->getHeader();

            // Make sure the headers are present
            // In case of duplicate column headers, an exception shall be thrown by League
            foreach ($headers as $index => $header) {
                if (is_null($header) || empty(trim($header))) {
                    throw new \Exception(trans('messages.list.import.error.header_empty', ['index' => $index]));
                }
            }

            // Remove leading/trailing spaces in headers, keep letter case
            $headers = array_map(function ($r) {
                return trim($r);
            }, $headers);

            /*
            $headers = array_filter(array_map(function ($value) {
                return strtolower(trim($value));
            }, $reader->getHeader()));


            // custom fields of the list
            $fields = collect($this->fields)->map(function ($field) {
                return strtolower($field->tag);
            })->toArray();

            // list's fields found in the input CSV
            $availableFields = array_intersect($headers, $fields);

            // Special fields go here
            if (!in_array('tags', $availableFields)) {
                $availableFields[] = 'tags';
            }
            // ==> email, first_name, last_name, tags
            */

            // split the entire list into smaller batches
            $results = $reader->getRecords($headers);

            return [$headers, iterator_count($results), $results];
        } catch (\Exception $ex) {
            // @todo: translation here
            // Common errors that will be catched: duplicate column, empty column
            throw new \Exception('Invalid headers. Original error message is: '.$ex->getMessage());
        }
    }

    public function getExportTempDir($file = null)
    {
        $base = storage_path(self::EXPORT_TEMP_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return $file ? $base . DIRECTORY_SEPARATOR . $file : $base;
    }

    public function getImportTempDir($file = null)
    {
        $base = storage_path(self::IMPORT_TEMP_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return $file ? $base . DIRECTORY_SEPARATOR . $file : $base;
    }

    public function getImportFilePath($filename = null)
    {
        return $this->getImportTempDir($filename);
    }

    public function getExportFilePath()
    {
        $name = preg_replace('/[^a-zA-Z0-9_\-\.]+/', '_', "{$this->uid}-{$this->title}.csv");
        return $this->getExportTempDir($name);
    }

    public function dispatchExportJob($segment = null)
    {
        return $this->dispatchWithMonitor(new ExportSubscribersJob($this, $segment));
    }

    private function validateCsvRecord($record, $emailFieldName = 'email')
    {
        //@todo: failed validate should affect the count showing up on the UI (currently, failed is also counted as success)
        $rules = [
            $emailFieldName => ['required', 'email:rfc,filter']
        ];

        $messages = [
            $emailFieldName => 'Invalid email address: '.$record[$emailFieldName]
        ];

        $validator = Validator::make($record, $rules, $messages);

        return [$validator->passes(), $validator->errors()->all()];
    }

    private function validateCsvHeader($headers)
    {
        // @todo: validation rules required here, currently hard-coded
        $missing = array_diff(['email'], $headers);
        if (!empty($missing)) {
            // @todo: I18n is required here
            throw new \Exception(trans('messages.import_missing_header_field', ['fields' => implode(', ', $missing)]));
        }

        return true;
    }

    public function parseCsvFile($file, $callback)
    {
        $processed = 0;
        $failed = 0;
        $total = 0;
        $message = null;

        // Read CSV files
        list($headers, $availableFields, $total, $results) = $this->readCsv($file);

        // validate headers, check for required fields
        // throw an exception in case of error
        $this->validateCsvHeader($availableFields);

        // update status, line count

        // process by batches
        each_batch($results, $batchSize = 100, false, function ($batch) use ($availableFields, $callback) {
            $data = collect($batch)->map(function ($r) use ($availableFields) {
                $record = array_only($r, $availableFields);
                if (!is_null($record['email'])) {
                    // replace the non-break space (not a normal space) as well as all other spaces
                    $record['email'] = strtolower(preg_replace('/[ \s*]*/', '', trim($record['email'])));
                }

                if (array_key_exists('tags', $record) && !empty($record['tags'])) {
                    $record['tags'] = json_encode(array_filter(preg_split('/\s*,\s*/', $record['tags'])));
                }

                return $record;
            })->toArray();

            $data = array_unique_by($data, function ($r) {
                return $r['email'];
            });

            foreach ($data as $record) {
                $callback($record);
            }
        });
    }

    public function getProgress($job)
    {
        if ($job->hasBatch()) {
            $progress = $job->getJsonData();
            $progress['status'] = $job->status;
            $progress['error'] = $job->error;
            $progress['percentage'] = $job->getBatch()->progress();
            $progress['total'] = $job->getBatch()->totalJobs;
            $progress['processed'] = $job->getBatch()->processedJobs();
            $progress['failed'] = $job->getBatch()->failedJobs;
        } else {
            $progress = $job->getJsonData();
            $progress['status'] = $job->status;
            $progress['error'] = $job->error;
            // The following attributes are already availble
            // $progress['percentage']
            // $progress['total']
            // $progress['processed']
            // $progress['failed']
        }

        return $progress;
    }

    public function importListsJobs()
    {
        return $this->jobMonitors()->orderBy('job_monitors.id', 'DESC')->whereIn('job_type',[ImportSubscribersJob::class]);
    }

    public function exportJobs()
    {
        return $this->jobMonitors()->orderBy('job_monitors.id', 'DESC')->where('job_type', ExportSubscribersJob::class);
    }

}

