<?php

namespace App\Models\Campaign;

use App;
use App\Events\MailListImported;
use App\Events\MailListSubscription;
use App\Events\MailListUpdated;
use App\Jobs\ExportSubscribersJob;
use App\Jobs\Subscribers\ImportSubscribers2;
use App\Jobs\Subscribers\ImportSubscribersJob;
use App\Jobs\Subscribers\ImportSubscribersListsJob;
use App\Jobs\VerifyMailListJob;
use App\Library\ExtendedSwiftMessage;
use App\Library\MailListFieldMapping;
use App\Library\RouletteWheel;
use App\Library\StringHelper;
use App\Library\Traits\HasCache;
use App\Library\Traits\HasUid;
use App\Library\Traits\QueryHelper;
use App\Library\Traits\TrackJobs;
use App\Models\Setting;
use App\Models\Subscriber\Subscriber;
use App\Models\Subscriber\SubscriberList;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use League\Csv\Writer;


class CampaignMaillist extends Model
{
    use QueryHelper;
    use TrackJobs;
    use HasUid;
    use HasCache;

    protected $table = "campaigns_maillists";

    public const SOURCE_EMBEDDED_FORM = 'embedded-form';
    public const SOURCE_WEB = 'web';
    public const SOURCE_API = 'api';

    public const IMPORT_TEMP_DIR = 'tmp/import';
    public const EXPORT_TEMP_DIR = 'tmp/export';

    protected $fillable = [
        'title',
        'from_email',
        'from_name',
        'default_subject',
        'remind_message',
        'send_to',
        'email_daily',
        'email_subscribe',
        'email_unsubscribe',
        'send_welcome_email',
        'unsubscribe_notification',
        'subscribe_confirmation',
        'all_sending_servers',
    ];

    public static $rules = array(
        'title' => 'required',
        'from_email' => 'required|email',
        'from_name' => 'required',
        'email_subscribe' => 'nullable|regex:"^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$"',
        'email_unsubscribe' => 'nullable|regex:"^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$"',
        'email_daily' => 'nullable|regex:"^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$"',
    );

    // Server pools
    public static $serverPools = array();
    public static $itemsPerPage = 25;
    protected $currentSubscription;
    protected $sendingSevers = null;


    public function fields()
    {
        return $this->hasMany('App\Models\Campaign\CampaignField');
    }

    public function segments()
    {
        return $this->hasMany('App\Models\Campaign\CampaignSegment', 'maillist_id');
    }

    public function automations()
    {
        return $this->hasMany('App\Models\Automation\Automation');
    }

    public function pages()
    {
        return $this->hasMany('App\Models\Page');
    }

    public function page($layout)
    {
        return $this->pages()->where('layout_id', $layout->id)->first();
    }

    public function subscribers()
    {
        return $this->hasMany('App\Models\Campaign\CampaignMailListsSubscriber', 'maillist_id');
    }

    public function campaigns()
    {
        return $this->belongsToMany('App\Models\Campaign\Campaign', 'campaigns_lists_segments', 'maillist_id', 'campaign_id');
    }

    public function subscriberFields()
    {
        return $this->hasManyThrough(SubscriberField::class, CampaignField::class);
    }

    /**
     * Bootstrap any application services.
     */
    public static function boot()
    {
        parent::boot();

        // Create uid when creating list.
        static::creating(function ($item) {
            // Create new uid
            $uid = uniqid();
            $item->uid = $uid;
        });

        // Create uid when list created.
        static::created(function ($item) {
            //  Create list default fields
            // $item->createDefaultFieds();
        });

        // detele
        static::deleted(function ($item) {
            $item->exportJobs()->delete();
        });
    }

    public static function getAll()
    {
        return self::select('*');
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

    public function dispatchVerificationJob($server, $subscription)
    {
        if (is_null($server)) {
            throw new Exception('Cannot start job: empty verification server');
        }

        $job = new VerifyMailListJob($this, $server, $subscription);
        $monitor = $this->dispatchWithBatchMonitor(
            $job,
            $then = null,
            $catch = null,
            $finally = null
        );

        return $monitor;
    }

    public function dispatchImportJob($filepath, $map)
    {
        // Example: /home/App/storage/app/tmp/import-000000.csv
        $job = new ImportSubscribersJob($this, $filepath, $map);
        $monitor = $this->dispatchWithMonitor($job);
        return $monitor;
    }

    public function dispatchImportListsJob($filepath, $lists)
    {
        // Example: /home/App/storage/app/tmp/import-000000.csv
        $job = new ImportSubscribersListsJob($this, $filepath, $lists);
        $monitor = $this->dispatchWithMonitor($job);
        return $monitor;
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

    public static function scopeSearch($query, $keyword)
    {
        // Keyword
        if (!empty(trim($keyword))) {
            $query = $query->where('name', 'like', '%'.$keyword.'%');
        }
    }

    public function getFields()
    {
        return $this->fields();
    }

    public function getDateOrDateTimeFields()
    {
        return $this->getFields()->whereIn('type', ['datetime', 'date']);
    }

    public function createDefaultFieds()
    {
        $this->fields()->create([
            'maillist_id' => $this->id,
            'type' => 'text',
            'label' => trans('messages.email'),
            'tag' => 'EMAIL',
            'is_email' => true,
            'required' => true,
            'visible' => true,
        ]);

        $this->fields()->create([
            'maillist_id' => $this->id,
            'type' => 'text',
            'label' => trans('messages.first_name'),
            'tag' => 'FIRST_NAME',
            'required' => false,
            'visible' => true,
        ]);

        $this->fields()->create([
            'maillist_id' => $this->id,
            'type' => 'text',
            'label' => trans('messages.last_name'),
            'tag' => 'LAST_NAME',
            'required' => false,
            'visible' => true,
        ]);
    }

    public function getEmailField()
    {
        return $this->fields()->where('is_email', true)->first();
    }

    public function getFieldByTag($tag)
    {
        // Case insensitive search
        return $this->fields()->where(DB::raw('LOWER(tag)'), '=', strtolower($tag))->first();
    }

    public function getFieldRules()
    {
        $rules = [];
        foreach ($this->getFields as $field) {
            if ($field->tag == 'EMAIL') {
                $rules[$field->tag] = 'required|email:rfc,filter';
            } elseif ($field->required) {
                $rules[$field->tag] = 'required';
            }
        }

        return $rules;
    }

    public static function resetServerPools()
    {
        self::$serverPools = array();
    }

    public function checkExsitEmail($email)
    {
        $valid = !filter_var($email, FILTER_VALIDATE_EMAIL) === false &&
            !empty($email) &&
            $this->subscribers()->where('email', '=', $email)->count() == 0;

        return $valid;
    }

    public function getSegmentSelectOptions($cache = false)
    {
        $options = $this->segments->map(function ($item) use ($cache) {
            return ['value' => $item->uid, 'text' => $item->name.' ('.$item->subscribersCount($cache).' '.strtolower(trans('messages.subscribers')).')'];
        });

        return $options;
    }

    public function unsubscribeCount()
    {
        // return distinctCount($this->subscribers()->unsubscribed(), 'subscribers.email');
        return $this->subscribers()->unsubscribed()->count();
    }

    public function unsubscribeRate($cache = false)
    {
        $count = $this->subscribersCount($cache);
        if ($count == 0) {
            return 0;
        }

        return round($this->unsubscribeCount() / $count, 2);
    }

    public function subscribeCount()
    {
        // return distinctCount($this->subscribers()->where('status', '=', 'subscribed'), 'subscribers.email');
        return $this->subscribers()->where('status', '=', 'subscribed')->count();
    }

    public function subscribeRate($cache = false)
    {
        $count = $this->subscribersCount($cache);
        if ($count == 0) {
            return 0;
        }

        return round($this->subscribeCount() / $count, 2);
    }

    public function unconfirmedCount()
    {
        // return distinctCount($this->subscribers()->where('status', '=', 'unconfirmed'), 'subscribers.email');
        return $this->subscribers()->where('status', '=', 'unconfirmed')->count();
    }

    public function blacklistedCount()
    {
        // return distinctCount($this->subscribers()->where('status', '=', 'blacklisted'), 'subscribers.email');
        return $this->subscribers()->where('status', '=', 'blacklisted')->count();
    }

    public function spamReportedCount()
    {
        // return distinctCount($this->subscribers()->where('status', '=', 'spam-reported'), 'subscribers.email');
        return $this->subscribers()->where('status', '=', 'spam-reported')->count();
    }

    public function log($name, $customer, $add_datas = [])
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        $data = array_merge($data, $add_datas);

        Log::create([
            'customer_id' => $customer->id,
            'type' => 'list',
            'name' => $name,
            'data' => json_encode($data)
        ]);
    }

    public function openCount()
    {
        $query = CampaignOpenLog::join('tracking_logs', 'tracking_logs.message_id', '=', 'campaign_open_logs.message_id')
            ->whereIn('tracking_logs.subscriber_id', function ($query) {
                $query->select('campaigns_maillists_subscribers.id')
                    ->from('campaigns_maillists_subscribers')
                    ->where('campaigns_maillists_subscribers.maillist_id', '=', $this->id);
            });

        return $query->count();
    }

    public function clickLogs()
    {
        $query = CampaignClickLog::join('tracking_logs', 'tracking_logs.message_id', '=', 'click_logs.message_id')
            ->whereIn('tracking_logs.subscriber_id', function ($query) {
                $query->select('campaigns_maillists_subscribers.id')
                    ->from('campaigns_maillists_subscribers')
                    ->where('campaigns_maillists_subscribers.maillist_id', '=', $this->id);
            });

        return $query;
    }

    public function clickCount()
    {
        $query = $this->clickLogs();

        return $query->distinct('url')->count('url');
    }

    public function openUniqCount()
    {
        $query = CampaignOpenLog::join('tracking_logs', 'tracking_logs.message_id', '=', 'campaign_open_logs.message_id')
            ->whereIn('tracking_logs.subscriber_id', function ($query) {
                $query->select('campaigns_maillists_subscribers.id')
                    ->from('campaigns_maillists_subscribers')
                    ->where('campaigns_maillists_subscribers.maillist_id', '=', $this->id);
            });

        return $query->distinct('subscriber_id')->count('subscriber_id');
    }
    public function trackingCount()
    {
        $query = TrackingLog::whereIn('tracking_logs.subscriber_id', function ($query) {
            $query->select('campaigns_maillists_subscribers.id')
                ->from('campaigns_maillists_subscribers')
                ->where('campaigns_maillists_subscribers.maillist_id', '=', $this->id);
        });

        return $query->count();
    }

    public function openUniqRate()
    {
        $subscribersCount = $this->subscribers()->count();
        if ($subscribersCount == 0) {
            return 0.0;
        }

        return round(($this->openUniqCount() / $subscribersCount), 2);
    }

    public function clickRate()
    {
        $open_count = $this->openCount();
        if ($open_count == 0) {
            return 0;
        }

        return round(($this->clickedEmailsCount() / $open_count), 2);
    }

    public function clickedEmailsCount()
    {
        $query = $this->clickLogs();

        return $query->distinct('subscriber_id')->count('subscriber_id');
    }

    public function otherLists()
    {
        return $this->customer
            ->mailLists()
            ->orderBy('mail_lists.name', 'asc')
            ->where('id', '!=', $this->id)->get();
    }

    public function longName($cache = false)
    {
        $count = $this->subscribersCount($cache);

        return $this->name.' - '.$count.' '.trans('messages.'.\App\Library\Tool::getPluralPrase('subscriber', $count)).'';
    }

    public function copy($name, $customer = null)
    {
        $copy = $this->replicate(['cache']);
        $copy->uid = uniqid();
        $copy->name = $name;
        $copy->created_at = \Carbon\Carbon::now();
        $copy->updated_at = \Carbon\Carbon::now();

        if ($customer) {
            $copy->customer_id = $customer->id;
        }

        $copy->save();

        if ($this->contact) {
            $new_contact = $this->contact->replicate();
            $new_contact->uid = uniqid();
            $new_contact->save();

            // update contact
            $copy->contact_id = $new_contact->id;
            $copy->save();
        }

        // Remove default fields
        $copy->fields()->delete();
        // Fields
        foreach ($this->fields as $field) {
            $new_field = $field->replicate();
            $new_field->uid = uniqid();
            $new_field->maillist_id = $copy->id;
            $new_field->save();

            // Copy field options
            foreach ($field->fieldOptions as $option) {
                $new_option = $option->replicate();
                $new_option->field_id = $new_field->id;
                $new_option->save();
            }
        }

        // update cache
        $copy->updateCache();

        return $copy;
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

    public static function exportSegments($list, $customer, $job){
    }

    public function sendSubscriptionConfirmationEmail($subscriber)
    {
        if ($subscriber->isListedInBlacklist()) {
            throw new \Exception(trans('messages.subscriber.blacklisted'));
        }

        if (Setting::isYes('verify_subscriber_email')) {
            // @important: the user must have its own verification server, this will not work for system verification server (even if the user has access to)
            $verifier = $this->customer->getEmailVerificationServers()->first();

            if (is_null($verifier)) {
                throw new \Exception(trans('messages.subscriber.email.fail_to_verify'));
            }

            if (!$subscriber->verify($verifier)->isDeliverable()) {
                throw new \Exception(trans('messages.subscriber.email.invalid'));
            }
        }

        $layout = \App\Models\Layout::where('alias', 'sign_up_confirmation_email')->first();
        $send_page = \App\Models\Page::findPage($this, $layout);
        $send_page->renderContent(null, $subscriber);
        $this->sendMail($subscriber, $send_page, $send_page->getTransformedSubject($subscriber));
    }

    public function send($message, $params = [])
    {
        $server = $this->pickSendingServer();
        $message->getHeaders()->addTextHeader('X-App-Message-Id', StringHelper::generateMessageId(StringHelper::getDomainFromEmail($this->from_email)));

        return $server->send($message, $params);
    }

    public function sendSubscriptionWelcomeEmail($subscriber)
    {
        $list = $this;

        $layout = \App\Models\Layout::where('alias', 'sign_up_welcome_email')->first();
        $send_page = \App\Models\Page::findPage($list, $layout);
        $this->sendMail($subscriber, $send_page, $send_page->getTransformedSubject($subscriber));
    }

    public function sendUnsubscriptionNotificationEmail($subscriber)
    {
        $list = $this;

        $layout = \App\Models\Layout::where('alias', 'unsubscribe_goodbye_email')->first();
        $send_page = \App\Models\Page::findPage($list, $layout);
        $this->sendMail($subscriber, $send_page, $send_page->getTransformedSubject($subscriber));
    }

    public function sendSubscriptionNotificationEmailToListOwner($subscriber)
    {
        $template = Layout::where('alias', 'subscribe_notification_for_list_owner')->first();

        $message = $template->getMessage(function ($html) use ($subscriber) {
            $html = str_replace('{LIST_NAME}', $this->name, $html);
            $html = str_replace('{EMAIL}', $subscriber->email, $html);
            $html = str_replace('{FULL_NAME}', $subscriber->getFullName(), $html);
            return $html;
        });

        $message->setSubject($template->subject);
        $message->setTo([ $this->customer->user->email => $this->customer->displayName()]);
        $mailer = App::make('xmailer');
        $result = $mailer->sendWithDefaultFromAddress($message);

        if (array_key_exists('error', $result)) {
            throw new \Exception("Error sending unsubscribe notification: ".$result['error']);
        }
    }

    public function sendUnsubscriptionNotificationEmailToListOwner($subscriber)
    {
        // Create a message
        $message = new ExtendedSwiftMessage();
        $message->setContentType('text/html; charset=utf-8');
        $message->setEncoder(new \Swift_Mime_ContentEncoder_PlainContentEncoder('8bit'));
        $message->setTo([ $this->customer->user->email => $this->customer->displayName()]);

        $template = \App\Models\Layout::where('alias', 'unsubscribe_notification_for_list_owner')->first();
        $htmlContent = $template->content;

        $htmlContent = str_replace('{LIST_NAME}', $this->name, $htmlContent);
        $htmlContent = str_replace('{EMAIL}', $subscriber->email, $htmlContent);
        $htmlContent = str_replace('{FULL_NAME}', $subscriber->getFullName(), $htmlContent);

        $message->setSubject($template->subject);
        $message->addPart($htmlContent, 'text/html');

        $mailer = App::make('xmailer');
        $result = $mailer->sendWithDefaultFromAddress($message);

        if (array_key_exists('error', $result)) {
            throw new \Exception("Error sending unsubscribe notification: ".$result['error']);
        }
    }

    public function sendProfileUpdateEmail($subscriber)
    {
        $list = $this;

        $layout = \App\Models\Layout::where('alias', 'profile_update_email')->first();
        $send_page = \App\Models\Page::findPage($list, $layout);
        $this->sendMail($subscriber, $send_page, $send_page->getTransformedSubject($subscriber));
    }

    public function getDateFields()
    {
        return $this->getFields()->whereIn('type', ['date', 'datetime'])->get();
    }

    public function getSubscriberFieldSelectOptions()
    {
        $options = [];
        $options[] = ['text' => trans('messages.subscriber_subscription_date'), 'value' => 'subscription_date'];
        foreach ($this->getDateFields() as $field) {
            $options[] = ['text' => trans('messages.subscriber_s_field', ['name' => $field->label]), 'value' => $field->uid];
        }

        return $options;
    }

    public function getFieldSelectOptions()
    {
        $options = [];
        foreach ($this->getFields()->get() as $field) {
            $options[] = ['text' => $field->label, 'value' => $field->uid];
        }

        return $options;
    }

    public function getRemainingAddSubscribersQuota()
    {
        $max = get_tmp_quota($this->customer, 'subscriber_max');
        $maxPerList = get_tmp_quota($this->customer, 'subscriber_per_list_max');

        $remainingForList = $maxPerList - $this->refresh()->subscribers->count();
        $remaining = $max - $this->refresh()->customer->subscribersCount(); // no cache

        if ($maxPerList == -1) {
            return ($max == -1) ? -1 : $remaining;
        }

        if ($max == -1) {
            return ($maxPerList == -1) ? -1 : $remainingForList;
        }

        return ($remainingForList > $remaining) ? $remaining : $remainingForList;
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

    public function validateFieldsMap($map)
    {
        // Check if EMAIL (required) is included in the map
        $fieldIds = array_values($map);
        $emailFieldId = $this->getEmailField()->id;

        if (!in_array($emailFieldId, $fieldIds)) {
            throw new Exception(trans('messages.list.import.error.email_missing'));
        }

        // Check if field id is valid
        foreach ($map as $header => $fieldId) {
            if (!CampaignField::where('id', $fieldId)->exists()) {
                throw new Exception(trans('messages.list.import.error.field_id_invalid', ['id' => $fieldId, 'header' => $header]));
            }
        }
    }

    public function importLists($file, $lists = null ,$progressCallback = null, $invalidRecordCallback = null)
    {


        if (is_null($file) && !is_null($lists) && is_array($lists) && count($lists) > 0) {

            $processed = 0;
            $failed = 0;
            $total = 0;
            $message = null;

            if (!is_null($progressCallback)) {
                $progressCallback($processed, $total, $failed, $message = trans('messages.list.import.starting'));
            }

            try {

                $subscribersCollection = collect();

                foreach ($lists as $listId) {

                    $listSubscriber = SubscriberList::find($listId);

                    if (!$listSubscriber) {
                        continue;
                    }

                    $listSubscriber->subscribers()
                        ->select(
                            'subscribers.id',  // Especificamos la tabla para evitar ambigüedad
                            'subscribers.*',
                            'subscriber_list_users.list_id as pivot_list_id',
                            'subscriber_list_users.subscriber_id as pivot_subscriber_id'
                        )
                        ->distinct()
                        ->chunk(1000, function ($subscribers) use (&$subscribersCollection) {
                            $subscribersCollection = $subscribersCollection->merge($subscribers->pluck('id'));
                        });
                }

                // Eliminamos duplicados
                $subscribersCollection = $subscribersCollection->unique();
                $total = $subscribersCollection->count();

                if (!is_null($progressCallback)) {
                    $progressCallback($processed, $total, $failed, $message = 'Validando cabeceras de suscriptores...');
                }

                $batchSize = max(1, config('app.import_batch_size', 1000));

                each_batch($subscribersCollection, $batchSize, false, function ($batch) use (&$processed, &$failed, $total, &$message, $progressCallback) {


                    DB::beginTransaction();
                    $importBatchId = uniqid('', true);

                    if (!is_null($progressCallback)) {
                        $progressCallback($processed, $total, $failed, $message = 'Insertando suscriptores en la base de datos...');
                    }

                    $data = collect($batch)->map(fn($subscriberId) => [
                        'uid' => uniqid('', true),
                        'maillist_id' => $this->id,
                        'subscriber_id' => $subscriberId,
                        'tags' => json_encode([]),
                        'import_batch_id' => $importBatchId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->toArray();

                    DB::table('campaigns_maillists_subscribers')->insert($data);
                    DB::commit();

                    $processed += count($batch);

                    if (!is_null($progressCallback)) {
                        $progressCallback($processed, $total, $failed, trans('messages.list.import.completed'));
                    }

                });

                if (!is_null($progressCallback)) {
                    $progressCallback($processed, $total, $failed, $message = "Procesado: {$processed}/{$total} registros, omitidos: {$failed}.");
                }

            } catch (\Throwable $e) {

                DB::rollBack();

                if (!is_null($progressCallback)) {
                    $progressCallback($processed, $total, $failed, $e->getMessage());
                }

                throw new Exception(substr($e->getMessage(), 0, 512));
            } finally {
                if (!is_null($progressCallback)) {
                    $progressCallback($processed, $total, $failed, $message);
                }
            }


        }elseif (!is_null($file) && !is_null($lists) && is_array($lists) && count($lists) > 0) {


            $subscribersCollection = collect();
            $subscribersCollectionRemover = collect();

            if (!is_null($file)) {

                $processed = 0;
                $failed = 0;
                $total = 0;
                $message = null;

                list($headers, $total, $results) = $this->readCsv($file);

                if (!is_null($progressCallback)) {
                    $progressCallback($processed, $total, $failed, $message = trans('messages.list.import.starting'));
                }

                $batchSize = max(1, config('app.import_batch_size', 1000));

                $subscribersCollectionRemover = collect(); // Asegurar que la colección existe

                each_batch($results, $batchSize, false, function ($batch) use (&$processed,  &$subscribersCollectionRemover) {
                    foreach ($batch as $row) {
                        $email = strtolower($row['EMAIL']);

                        // Verifica si el email existe en la base de datos
                        $subscriberData = Subscriber::checkWithPartition($email);
                        if ($subscriberData['exists']) {
                            $subscribersCollectionRemover->push($subscriberData['data']->id ?? null);
                        }

                        $processed += count($batch);

                    }
                });

                // Removemos valores nulos si hay algún problema con los IDs
                $subscribersCollectionRemover = $subscribersCollectionRemover->filter();
                if (!is_null($progressCallback)) {
                    $progressCallback($processed, $total, $failed, $message = trans('messages.list.import.starting'));
                }


            }


            if (!is_null($lists) && is_array($lists) && count($lists) > 0) {

                $processed = 0;
                $failed = 0;
                $total = 0;
                $message = null;

                if (!is_null($progressCallback)) {
                    $progressCallback($processed, $total, $failed, $message = trans('messages.list.import.starting'));
                }

                try {

                    foreach ($lists as $listId) {

                        $listSubscriber = SubscriberList::find($listId);

                        if (!$listSubscriber) {
                            continue;
                        }

                        $listSubscriber->subscribers()
                            ->select(
                                'subscribers.id',  // Especificamos la tabla para evitar ambigüedad
                                'subscribers.*',
                                'subscriber_list_users.list_id as pivot_list_id',
                                'subscriber_list_users.subscriber_id as pivot_subscriber_id'
                            )
                            ->distinct()
                            ->chunk(1000, function ($subscribers) use (&$subscribersCollection) {
                                $subscribersCollection = $subscribersCollection->merge($subscribers->pluck('id'));
                            });
                    }

                    $subscribersCollection = $subscribersCollection->unique();

                    $subscribersCollection = $subscribersCollection->diff($subscribersCollectionRemover);
                    $total = $subscribersCollection->count();


                    if (!is_null($progressCallback)) {
                        $progressCallback($processed, $total, $failed, $message = 'Validando cabeceras de suscriptores...');
                    }

                    $batchSize = max(1, config('app.import_batch_size', 1000));

                    each_batch($subscribersCollection, $batchSize, false, function ($batch) use (&$processed, &$failed, $total, &$message, $progressCallback) {


                        DB::beginTransaction();
                        $importBatchId = uniqid('', true);

                        if (!is_null($progressCallback)) {
                            $progressCallback($processed, $total, $failed, $message = 'Insertando suscriptores en la base de datos...');
                        }

                        $data = collect($batch)->map(fn($subscriberId) => [
                            'uid' => uniqid('', true),
                            'maillist_id' => $this->id,
                            'subscriber_id' => $subscriberId,
                            'tags' => json_encode([]),
                            'import_batch_id' => $importBatchId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])->toArray();

                        DB::table('campaigns_maillists_subscribers')->insert($data);
                        DB::commit();

                        $processed += count($batch);

                        if (!is_null($progressCallback)) {
                            $progressCallback($processed, $total, $failed, trans('messages.list.import.completed'));
                        }

                    });

                    if (!is_null($progressCallback)) {
                        $progressCallback($processed, $total, $failed, $message = "Procesado: {$processed}/{$total} registros, omitidos: {$failed}.");
                    }

                } catch (\Throwable $e) {

                    DB::rollBack();

                    if (!is_null($progressCallback)) {
                        $progressCallback($processed, $total, $failed, $e->getMessage());
                    }

                    throw new Exception(substr($e->getMessage(), 0, 512));
                } finally {
                    if (!is_null($progressCallback)) {
                        $progressCallback($processed, $total, $failed, $message);
                    }
                }
            }

        }
    }

    public function import($file, $mapArray = null, $progressCallback = null, $invalidRecordCallback = null)
    {

        if (is_null($mapArray)) {
            list($headers, $total, $results) = $this->readCsv($file);
            $mapArray = [];
            foreach ($this->fields as $field) {
                foreach ($headers as $header) {
                    if (strtolower($field->tag) == strtolower($header)) {
                        $mapArray[$header] = $field->id;
                    }
                }
            }
        }

        /* END trick */

        $map = MailListFieldMapping::parse($mapArray, $this);

        $processed = 0;
        $failed = 0;
        $total = 0;
        $message = null;

        $overQuotaAttempt = false;

        if (!is_null($progressCallback)) {
            $progressCallback($processed, $total, $failed, $message = trans('messages.list.import.starting'));
        }

        // Read CSV files, keep original headers' case (do not lowercase or uppercase)
        list($headers, $total, $results) = $this->readCsv($file);

        /*
        if (!is_null($progressCallback)) {
            $progressCallback($processed, $total, $failed, $message = 'Validating file headers...');
        }

        if (!is_null($progressCallback)) {
            $progressCallback($processed, $total, $failed, $message = 'Loading records from file...');
        }
        */

        try {
            // process by batches
            each_batch($results, config('app.import_batch_size'), false, function ($batch) use ($map, &$processed, &$failed, $total, &$overQuotaAttempt, &$message, $progressCallback, $invalidRecordCallback) {
                if ($this->customer->user->can('addMoreSubscribers', [$this, sizeof($batch)])) {
                    $insertLimit = sizeof($batch);
                } else {
                    $insertLimit = $this->getRemainingAddSubscribersQuota();
                }

                $insertLimit = ($insertLimit < 0) ? 0 : $insertLimit;

                // if ($insertLimit == 0) { // only == 0 is enough
                //     throw new \Exception(trans('messages.import.notice.over_quota'));
                // }

                // Create a tmp table like: __tmp_subscribers(field_1, field_2, field_3, field_4, tags)
                list($tmpTable, $emailFieldName)  = $map->createTmpTableFromMapping();

                // Insert subscriber fields from the batch to the temporary table
                // extract only fields whose name matches TAG NAME of MailList
                $data = collect($batch)->map(function ($r) use ($emailFieldName, $map) {
                    // Transform a record like:   [ 'First Name' => 'Joe', 'Email' => 'joe@america.us', 'tags' => 'SOME TAGS', 'others' => 'Others' ]
                    // to something like
                    //
                    //     [ 'field_1' => 'Joe', 'field_2' => 'joe@america.us',  'tags' => 'SOME TAGS']
                    //
                    // i.e. Change header based on mapped field, remove other fields (not in map)
                    $record = $map->updateRecordHeaders($r);

                    // replace the non-break space (not a normal space) as well as all other spaces
                    $record[$emailFieldName] = strtolower(preg_replace('/[ \s*]*/', '', trim($record[$emailFieldName])));

                    // Process tag values
                    // @important: tags field must be lower-case
                    if (array_key_exists('tags', $record) && !empty($record['tags'])) {
                        $record['tags'] = json_encode(array_filter(preg_split('/\s*,\s*/', $record['tags'])));
                    }

                    // In certain cases, a UTF-8 BOM is added to email
                    // For example: "﻿madxperts@gmail.com" (open with Sublime to see the char)
                    // So we need to remove it, at least for email field
                    $record[$emailFieldName] = StringHelper::removeUTF8BOM($record[$emailFieldName]);

                    return $record;
                })->toArray();

                // make the import data table unique by email
                $data = array_unique_by($data, function ($r) use ($emailFieldName) {
                    return $r[$emailFieldName];
                });

                // validate and filter out invalid records
                $data = array_where($data, function ($record) use (&$failed, $invalidRecordCallback, $emailFieldName) {
                    list($valid, $errors) = $this->validateCsvRecord($record, $emailFieldName);
                    if (!$valid) {
                        $failed += 1;
                        if (!is_null($invalidRecordCallback)) {
                            $invalidRecordCallback($record, $errors);
                        }
                    }

                    return $valid;
                });

                // INSERT TO tmp TABLE
                DB::table('__tmp_subscribers')->insert($data);
                $newRecordCount = DB::select("SELECT COUNT(*) AS count FROM {$tmpTable} tmp LEFT JOIN ".table('subscribers')." main ON (tmp.{$emailFieldName} = main.email AND main.maillist_id = {$this->id}) WHERE main.email IS NULL")[0]->count;

                if ($newRecordCount > 0 && $insertLimit == 0) {
                    // Only warning at this time
                    // when there is new records to INSERT but there is no more insert credit
                    // It is just fine if $newRecordCount == 0, then only update existing subscribers
                    // Just let it proceed until finishing
                    $overQuotaAttempt = true;
                }

                // processing for every batch,
                // using transaction to only commit at the end of the batch execution
                DB::beginTransaction();

                // Generate a batch id
                $importBatchId = uniqid();

                // Insert new subscribers from temp table to the main table
                // Use SUBSTRING(MD5(UUID()), 1, 13) to produce a UNIQUE ID which is similar to the output of PHP uniqid()
                // @TODO LIMITATION: tags are not updated if subscribers already exist
                $insertToSubscribersSql = strtr(
                    '
                    INSERT INTO %subscribers (uid, maillist_id, email, status, subscription_type, tags, import_batch_id, created_at, updated_at)
                    SELECT SUBSTRING(MD5(UUID()), 1, 13), %list_id, uniq.email, %status, %type, uniq.tags, \'%import_batch_id\', NOW(), NOW()
                    FROM (
                        SELECT tmp.%email_field AS email, tmp.tags
                        FROM %tmp tmp
                        LEFT JOIN %subscribers main ON (tmp.%email_field = main.email AND main.maillist_id = %list_id)
                        WHERE main.email IS NULL LIMIT %insert_limit) uniq',
                    [
                        '%subscribers' => table('subscribers'),
                        '%list_id' => $this->id,
                        '%status' => db_quote(Subscriber::STATUS_SUBSCRIBED),
                        '%type' => db_quote(Subscriber::SUBSCRIPTION_TYPE_IMPORTED),
                        '%import_batch_id' => $importBatchId,
                        '%tmp' => $tmpTable,
                        '%insert_limit' => $insertLimit,
                        '%email_field' => $emailFieldName
                    ]
                );

                DB::statement($insertToSubscribersSql);

                // Insert subscribers' custom fields to the fields table
                // Before inserting, delete records from `subscriber_fields` whose email matches that of the $tmpTable
                // As a result, any existing attributes of subscribers shall be overwritten

                // OPTION 1: DELETE WHERE IN
                // DB::statement('DELETE FROM '.table('subscriber_fields').' WHERE subscriber_id IN (SELECT main.id FROM '.table('subscribers')." main JOIN {$tmpTable} tmp ON main.email = tmp.email WHERE maillist_id = ".$this->id.')');

                // OPTION 2: DELETE JOIN
                DB::statement(sprintf(
                    'DELETE f
                    FROM %s main
                    JOIN %s tmp ON main.email = tmp.%s
                    JOIN %s f ON main.id = f.subscriber_id
                    WHERE maillist_id = %s;',
                    table('subscribers'),
                    $tmpTable,
                    $emailFieldName,
                    table('subscriber_fields'),
                    $this->id
                ));

                foreach ($map->mapping as $header => $fieldId) {
                    $fieldName = $map->generateFieldNameFromId($fieldId);
                    $insertToSubscriberFieldsSql = strtr('
                        INSERT INTO %subscriber_fields (subscriber_id, field_id, value, created_at, updated_at)
                        SELECT t.subscriber_id, %fid, t.value, NOW(), NOW()
                        FROM (
                            SELECT main.id AS subscriber_id, tmp.`%field_name` AS value
                            FROM %subscribers main JOIN %tmp tmp ON tmp.`%email_field` = main.email
                        ) t
                    ', [
                        '%subscribers' => table('subscribers'),
                        '%subscriber_fields' => table('subscriber_fields'),
                        '%fid' => $fieldId,
                        '%field_name' => $fieldName,
                        '%tmp' => $tmpTable,
                        '%email_field' => $emailFieldName
                    ]);

                    DB::statement($insertToSubscriberFieldsSql);
                }

                // Sample:
                //     INSERT INTO subscribers_fields (subscriber_id, field_id, value, created_at, updated_at)
                //     SELECT t.subscriber_id, f.id, t."First Name", NOW(), NOW()
                //     FROM (
                //              SELECT main.id AS subscriber_id, tmp."First Name"
                //              FROM subscribers main JOIN __tmp_subscribers tmp ON tmp.email = main.email
                //              WHERE main.maillist_id = 1089
                //     ) t

                // update status, finish one batch
                $processed += sizeof($batch);
                if (!is_null($progressCallback)) {
                    $progressCallback($processed, $total, $failed, $message = 'Inserting contacts to database...');
                }

                // Actually write to the database
                DB::commit();

                // Cleanup
                DB::statement("DROP TABLE IF EXISTS {$tmpTable};");

                // Trigger updating related campaigns cache
                $this->updateCachedInfo();

                // blacklist new emails (if any)
                Blacklist::doBlacklist($this->customer);

                // Event MailListImported($list)
                MailListImported::dispatch($this, $importBatchId);
            });

            if (!is_null($progressCallback)) {
                $progressCallback($processed, $total, $failed, $message = "Processed: {$processed}/{$total} records, skipped: {$failed} records.");
            }
        } catch (\Throwable $e) {
            // IMPORTANT: rollback first before throwing, otherwise it will be a deadlock
            DB::rollBack();

            if (!is_null($progressCallback)) {
                $progressCallback($processed, $total, $failed, $message = $e->getMessage());
            }

            // Is is weird that, in certain case, the $e->getMessage() string is too long, making the job "hang";
            throw new Exception(substr($e->getMessage(), 0, 512));
        } finally {
            // @IMPORTANT: if process fails here, something weird occurs
            $this->reformatDateFields();
            $this->updateCachedInfo();

            if (!is_null($progressCallback)) {
                $progressCallback($processed, $total, $failed, $message);
            }
        }
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

            // make the import data table unique by email
            $data = array_unique_by($data, function ($r) {
                return $r['email'];
            });

            foreach ($data as $record) {
                $callback($record);
            }
        });
    }

    public function addSubscriberFromArray($attributes)
    {
        list($valid, $errors) = $this->validateCsvRecord($attributes);
        if (!$valid) {
            throw new Exception("Invalid record: ".implode(";", $errors));
        }

        // Create or update the subscriber record
        $subscriber = $this->subscribers()->where('email', $attributes['email'])->firstOrNew();
        $subscriber->fill($attributes);
        $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
        $subscriber->save();

        // Create or update subscriber fields
        $subscriber->updateFields2($attributes);

        return $subscriber;
    }

    public function updateCachedInfo()
    {
        // Update list's cached information
        $this->updateCache();

        // Update segments cached information
        foreach ($this->segments as $segment) {
            $segment->updateCache();
        }

        // Update user's cached information
        $this->customer->updateCache();
    }

    public function mailListsSendingServers()
    {
        return $this->hasMany('App\Models\MailListsSendingServer');
    }

    public function activeMailListsSendingServers()
    {
        return $this->mailListsSendingServers()
            ->join('sending_servers', 'sending_servers.id', '=', 'mail_lists_sending_servers.sending_server_id')
            ->where('sending_servers.status', '=', SendingServer::STATUS_ACTIVE);
    }

    public function updateSendingServers($servers)
    {
        $this->mailListsSendingServers()->delete();
        foreach ($servers as $key => $param) {
            if ($param['check']) {
                $server = SendingServer::findByUid($key);
                $row = new CampaignMaillistsSendingServer();
                $row->maillist_id = $this->id;
                $row->sending_server_id = $server->id;
                $row->fitness = $param['fitness'];
                $row->save();
            }
        }
    }

    public function getCacheIndex()
    {
        return [
            // @note: SubscriberCount must come first as its value shall be used by the others
            'SubscriberCount' => function () {
                return $this->subscribersCount(false);
            },
            'VerifiedSubscriberCount' => function () {
                return $this->subscribers()->verified()->count();
            },
            'ClickedRate' => function () {
                return $this->clickRate();
            },
            'UniqOpenRate' => function () {
                return $this->openUniqRate();
            },
            'SubscribeRate' => function () {
                return $this->subscribeRate(true);
            },
            'SubscribeCount' => function () {
                return $this->subscribeCount();
            },
            'UnsubscribeRate' => function () {
                return $this->unsubscribeRate(true);
            },
            'UnsubscribeCount' => function () {
                return $this->unsubscribeCount();
            },
            'UnconfirmedCount' => function () {
                return $this->unconfirmedCount();
            },
            'BlacklistedCount' => function () {
                return $this->blacklistedCount();
            },
            'SpamReportedCount' => function () {
                return $this->spamReportedCount();
            },
            'SegmentSelectOptions' => function () {
                return $this->getSegmentSelectOptions(true);
            },
            'LongName' => function () {
                return $this->longName(true);
            },
            'VerifiedSubscribersPercentage' => function () {
                return $this->getVerifiedSubscribersPercentage(true);
            },

        ];
    }

    public function sendMail($subscriber, $page, $title)
    {
        $page->renderContent(null, $subscriber);

        $body = view('pages._email_content', ['page' => $page])->render();

        // Create a message
        $message = new ExtendedSwiftMessage($title);
        $message->setFrom(array($subscriber->mailList->from_email => $subscriber->mailList->from_name));
        $message->setTo(array($subscriber->email, $subscriber->email => $subscriber->getFullName(trans('messages.to_email_name'))));
        $message->addPart($body, 'text/html');

        if (config('app.demo')) {
            return;
        }

        try {
            $this->send($message, [
                'subscriber' => $subscriber,
            ]);
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
            $msg = trans('messages.list.error.cannot_send_notification_email', [ 'title' => $message->getSubject(), 'email' => $subscriber->email, 'error' => $error ]);
            throw new \Exception($msg);
        }
    }

    public function pickSendingServer()
    {
        $selection = $this->getSendingServers();

        // raise an exception if no sending servers are available
        if (empty($selection)) {
            throw new \Exception(sprintf('No sending server available for Mail List ID %s', $this->id));
        }

        // do not raise an exception, just wait if sending servers are available but exceeding sending limit
        $blacklisted = [];

        while (true) {
            $id = RouletteWheel::take($selection);
            if (empty(self::$serverPools[$id])) {
                $server = SendingServer::find($id);
                $server = SendingServer::mapServerType($server);
                self::$serverPools[$id] = $server;
            }

            return self::$serverPools[$id];
        }
    }

    public function getSendingServers()
    {
        if (!is_null($this->sendingSevers)) {
            return $this->sendingSevers;
        }

        $result = [];

        // Check the customer has permissions using sending servers and has his own sending servers
        if (!config('app.saas')) {
            $server = get_tmp_primary_server();
            if ($server) {
                $result = [
                    [ $server->id, '100' ],
                ];
            }
        } elseif ($this->customer->getCurrentActiveGeneralSubscription()->planGeneral->useOwnSendingServer()) {
            if ($this->all_sending_servers) {
                if ($this->customer->activeSendingServers()->count()) {
                    $result = $this->customer->activeSendingServers()->get()->map(function ($server) {
                        return [$server->id, '100'];
                    });
                }
            } elseif ($this->activeMailListsSendingServers()->count()) {
                $result = $this->activeMailListsSendingServers()->get()->map(function ($server) {
                    return [$server->sending_server_id, $server->fitness];
                });
            }
            // If customer dont have permission creating sending servers
        } else {
            $subscription = $this->customer->getNewOrActiveGeneralSubscription();
            // Check if has sending servers for current subscription
            if ($subscription) {
                $result = $subscription->planGeneral->activeSendingServers->map(function ($server) {
                    return [$server->sending_server_id, $server->fitness];
                });
            }
            //} elseif ($subscription->useSubAccount()) {
            //    $result[] = [$subscription->subAccount->sending_server_id, '100'];
        }

        $assoc = [];
        foreach ($result as $server) {
            list($key, $fitness) = $server;
            $assoc[(int) $key] = $fitness;
        }

        $this->sendingSevers = $assoc;

        return $this->sendingSevers;
    }

    public function resetVerification()
    {
        DB::statement(sprintf('UPDATE %s s SET verification_status = NULL, last_verification_by = NULL, last_verification_at = NULL, last_verification_result = NULL WHERE s.maillist_id = %s', table('subscribers'), $this->id));
    }

    public function getVerifiedSubscribersPercentage($cache = false)
    {
        $count = $this->subscribersCount($cache);
        if ($count == 0) {
            return 0.0;
        } else {
            return (float) $this->subscribers()->verified()->count() / $count;
        }
    }

    public function subscribersCount($cache = false)
    {
        if ($cache) {
            return $this->readCache('SubscriberCount', 0);
        }

        return $this->subscribers()->count();
    }

    public function segmentsCount()
    {
        return $this->segments()->count();
    }

    public function copyAll($name, $customer = null)
    {
        $copy = $this->replicate(['cache']);
        $copy->name = $name;
        $copy->created_at = \Carbon\Carbon::now();
        $copy->updated_at = \Carbon\Carbon::now();

        if ($customer) {
            $copy->customer_id = $customer->id;
        }

        $copy->save();

        // Contact
        if ($this->contact) {
            $new_contact = $this->contact->replicate();
            $new_contact->save();

            // update contact
            $copy->contact_id = $new_contact->id;
            $copy->save();
        }

        // Remove default fields
        $copy->fields()->delete();
        // Fields
        foreach ($this->fields as $field) {
            $new_field = $field->replicate();
            $new_field->maillist_id = $copy->id;
            $new_field->save();

            // Copy field options
            foreach ($field->fieldOptions as $option) {
                $new_option = $option->replicate();
                $new_option->field_id = $new_field->id;
                $new_option->save();
            }
        }

        // copy all subscribers
        foreach ($this->subscribers as $subscriber) {
            $subscriber->copy($copy);
        }

        // update cache
        $copy->updateCache();
    }

    public function cloneForCustomers($customers)
    {
        foreach ($customers as $customer) {
            $this->copyAll($this->name, $customer);
        }
    }

    public function subscribe($request, $source)
    {
        // Validation
        // It is ok to have subscriber subscribe again without any confusing message
        //     if ($subscriber && $subscriber->status == \App\Models\Subscriber::STATUS_SUBSCRIBED) {
        //        $rules['email_already_subscribed'] = 'required';
        //     }

        $messages = [];
        foreach ($this->getFields as $field) {
            if ($field->tag == 'EMAIL') {
                $messages[$field->tag . '.required' ] = trans('messages.list.validation.required', ['field' => $field->label]);
                $messages[$field->tag . '.email' ] = trans('messages.list.validation.not_email', ['field' => $field->label]);
            } elseif ($field->required) {
                $messages[$field->tag . '.required' ] = trans('messages.list.validation.required', ['field' => $field->label]);
            }
        }

        // List rules
        $rules = $this->getFieldRules();

        // Do not allow duplicate email if added by admin (throw an exception)
        // If imported / api / web / embedded-form THEN just overwrite
        if ($source == Subscriber::SUBSCRIPTION_TYPE_ADDED || $source == self::SOURCE_API) {
            // @important
            // DO NOT USE "UNIQUE" validator of Laravel
            // Otherwise, it will fails after the subscriber with given email address is added
            $rules['EMAIL'] = [
                'required',
                'email',
                Rule::unique('subscribers')->where(function ($query) {
                    return $query->where('maillist_id', $this->id);
                }),
            ];
        }

        $validator = \Validator::make($request->all(), $rules, $messages);

        // Validated, proceed
        $subscriber = $this->subscribers()->firstOrNew(['email' => strtolower(trim($request->EMAIL))]);
        $subscriber->from = $source;

        // Check if subscriber already in blacklist
        if ($subscriber->isListedInBlacklist()) {
            // validate service
            $validator->after(function ($validator) {
                $validator->errors()->add('email', trans('messages.subscriber.blacklisted'));
            });
        }

        if ($validator->fails()) {
            return [$validator, null];
        }

        if ($source == Subscriber::SUBSCRIPTION_TYPE_ADDED || $source == self::SOURCE_API) {
            $subscriber->status = 'subscribed';
        } elseif ($this->subscribe_confirmation) {
            if ($subscriber->isSubscribed()) {
                //
            } else {
                $subscriber->status = 'unconfirmed';
            }
        } else {
            $subscriber->status = 'subscribed';
        }
        $subscriber->ip = $request->ip();
        $subscriber->save();

        // @IMPORTANT
        // After the $subscriber->save(), $validator->fails() becomes TRUE!!!!
        // Because the email address is now not available
        // This is a problem of Laravel

        $subscriber->updateFields($request->all());

        // update MailList cache
        MailListUpdated::dispatch($this);

        if ($subscriber->isSubscribed()) {
            // Only trigger MailListSubscription event if subscribe is immediately subscribed
            MailListSubscription::dispatch($subscriber);
        }

        if ($this->subscribe_confirmation && !$subscriber->isSubscribed() && $source != Subscriber::SUBSCRIPTION_TYPE_ADDED) {
            $this->sendSubscriptionConfirmationEmail($subscriber);
        }

        return [$validator, $subscriber];
    }

    public function reformatDateFields($subscriber_id = null)
    {
        $type = 'date';

        $query = $this->subscriberFields()
            ->where('fields.type', $type)
            ->select('subscriber_fields.id', 'subscriber_fields.value');

        if (!is_null($subscriber_id)) {
            $query = $query->where('subscriber_id', $subscriber_id);
        }

        $query->perPage($pageSize = 1000, function ($batch) {
            $fixedValues = $batch->get()->map(function ($r) {
                return [
                    'id' => $r->id,
                    'value' => $this->customer->parseDateTime($r->value, true)->format(config('custom.date_format')),
                ];
            })->toArray();

            $this->createTemporaryTableFromArray(
                "_tmp_{$this->customer->uid}_date_values",
                $fixedValues,
                [
                    'id BIGINT',
                    'value VARCHAR(191) CHARACTER SET utf8 COLLATE utf8_unicode_ci'
                ],
                function ($table) {
                    DB::statement(sprintf("UPDATE %s sf INNER JOIN %s t ON sf.id = t.id SET sf.value = t.value", table('subscriber_fields'), table($table)));
                }
            );
        });

        /****** The following method is not working

        $sql = "DROP TABLE IF EXISTS __tmp__;
        CREATE TABLE __tmp__ AS
        SELECT sf.id, DATE_FORMAT(COALESCE(
        STR_TO_DATE(sf.value,'{$normalizedDateFormat}'),
        STR_TO_DATE(sf.value,'%Y:%m:%d'),
        STR_TO_DATE(sf.value,'%Y.%m.%d'),
        STR_TO_DATE(sf.value,'%Y/%m/%d')
        ), '{$normalizedDateFormat}') as pretty_date
        FROM ".table('subscriber_fields')." sf
        INNER JOIN ".table('fields')." f ON sf.field_id = f.id
        WHERE f.`type` = 'date'
        AND DATE_FORMAT(STR_TO_DATE(sf.value,'{$normalizedDateFormat}'), '{$normalizedDateFormat}') != sf.value
        AND f.maillist_id = {$this->id};

        UPDATE ".table('subscriber_fields')." sf
        INNER JOIN __tmp__ tmp ON sf.id = tmp.id
        SET value = tmp.pretty_date
        WHERE value <> tmp.pretty_date";
        DB::statement($sql);
         **********/
    }

    public function verificationJobs()
    {
        return $this->jobMonitors()->orderBy('job_monitors.id', 'DESC')->where('job_type', VerifyMailListJob::class);
    }

    public function importJobs()
    {
        return $this->jobMonitors()->orderBy('job_monitors.id', 'DESC')->whereIn('job_type', [ ImportSubscribersJob::class, ImportSubscribers2::class ]);
    }
    public function importListsJobs()
    {
        return $this->jobMonitors()->orderBy('job_monitors.id', 'DESC')->whereIn('job_type', [ ImportSubscribersListsJob::class]);
    }

    public function exportJobs()
    {
        return $this->jobMonitors()->orderBy('job_monitors.id', 'DESC')->where('job_type', ExportSubscribersJob::class);
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

    public function updateOrCreateFieldsFromRequest($request)
    {
        $rules = [];
        $messages = [];

        // Check if filed does not have EMAIL tag
        $conflict_tag = false;
        $tags = [];
        foreach ($request->fields as $key => $item) {
            // If email field
            if ($this->getEmailField()->uid != $item['uid']) {
                // check required input
                $rules['fields.'.$key.'.label'] = 'required';
                $rules['fields.'.$key.'.tag'] = 'required|alpha_dash';

                $messages['fields.'.$key.'.label.required'] = trans('messages.field.label.required', ['number' => $key]);
                $messages['fields.'.$key.'.tag.required'] = trans('messages.field.tag.required', ['number' => $key]);
                $messages['fields.'.$key.'.tag.alpha_dash'] = trans('messages.field.tag.alpha_dash', ['number' => $key]);

                // check field options
                if (isset($item['options'])) {
                    foreach ($item['options'] as $key2 => $item2) {
                        $rules['fields.'.$key.'.options.'.$key2.'.label'] = 'required';
                        $rules['fields.'.$key.'.options.'.$key2.'.value'] = 'required';

                        $messages['fields.'.$key.'.options.'.$key2.'.label.required'] = trans('messages.field.option.label.required', ['number' => $key]);
                        $messages['fields.'.$key.'.options.'.$key2.'.value.required'] = trans('messages.field.option.value.required', ['number' => $key]);
                    }
                }

                // Check tag exsit
                $tag = App\Models\Campaign\CampaignField::formatTag($item['tag']);
                if (in_array($tag, $tags)) {
                    $conflict_tag = true;
                }
                $tags[] = $tag;
            }
        }
        if ($conflict_tag) {
            $rules['conflict_field_tags'] = 'required';
        }

        $validator = \Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $validator;
        }

        // Save fields
        $saved_ids = [];
        foreach ($request->fields as $uid => $item) {
            $field = App\Models\Campaign\CampaignField::findByUid($item['uid']);
            if (!$field) {
                $field = new App\Models\Campaign\CampaignField();
                $field->maillist_id = $this->id;
            }

            // If email field
            if ($this->getEmailField()->uid != $field->uid) {
                // save exsit field
                $item['tag'] = App\Models\Campaign\CampaignField::formatTag($item['tag']);
                $field->fill($item);
                $field->save();

                // save field options
                $field->fieldOptions()->delete();
                if (isset($item['options'])) {
                    foreach ($item['options'] as $key2 => $item2) {
                        $option = new App\Models\Campaign\CampaignFieldOption($item2);
                        $option->field_id = $field->id;
                        $option->save();
                    }
                }
            } else {
                $field->label = $item['label'];
                $field->default_value = $item['default_value'];
                $field->save();
            }

            // store save ids
            $saved_ids[] = $field->uid;
        }

        // Delete fields
        foreach ($this->getFields as $field) {
            if (!in_array($field->uid, $saved_ids) && $field->uid != $this->getEmailField()->uid) {
                $field->delete();
            }
        }

        return $validator;
    }

    public function getFieldsFromParams($params)
    {
        $fields = collect();
        // Get old post values
        if (isset($params['fields'])) {
            foreach ($params['fields'] as $key => $item) {
                $field = App\Models\Campaign\CampaignField::findByUid($item['uid']);
                if (!$field) {
                    $field = new App\Models\Campaign\CampaignField();
                    $field->uid = $key;
                }
                $field->fill($item);

                // If email field
                if ($this->getEmailField()->uid == $field->uid) {
                    $field = $this->getEmailField();
                    $field->label = $item['label'];
                    $field->default_value = $item['default_value'];
                }

                // Field options
                if (isset($item['options'])) {
                    $field->fieldOptions = collect();
                    foreach ($item['options'] as $key2 => $item2) {
                        $option = new App\Models\Campaign\CampaignFieldOption($item2);
                        $option->uid = $key2;
                        $field->fieldOptions->push($option);
                    }
                }

                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function defaultEmbeddedFormOptions()
    {
        return [
            'form_title' => trans('messages.Subscribe_to_our_mailing_list'),
            'redirect_url' => '',
            'only_required_fields' => 'no',
            'stylesheet' => 'yes',
            'javascript' => 'yes',
            'show_invisible' => 'no',
            'enable_term' => 'yes',
            'term' => trans('messages.embedded_form.enable_term.example'),
            'custom_css' => '.subscribe-embedded-form {
    color: #333
}
.subscribe-embedded-form label {
    color: #555
}',
        ];
    }

    public function getEmbeddedFormOptions()
    {
        if ($this->embedded_form_options == null) {
            return [];
        }

        return json_decode($this->embedded_form_options, true);
    }

    public function getEmbeddedFormOption($key)
    {
        $defaults = $this->defaultEmbeddedFormOptions();
        $options = $this->getEmbeddedFormOptions();

        if (isset($options[$key])) {
            return $options[$key];
        } else {
            return $defaults[$key];
        }
    }

    public function setEmbeddedFormOptions($params)
    {
        $this->embedded_form_options = json_encode($params);
        $this->save();
    }

    public function generateAutoMapping($headers)
    {
        $exactMatching = function ($text1, $text2) {
            $matchRegx = '/[^a-zA-Z0-9]/';
            if (strtolower(trim(preg_replace($matchRegx, ' ', $text1))) == strtolower(trim(preg_replace($matchRegx, ' ', $text2)))) {
                return true;
            } else {
                return false;
            }
        };

        $relativeMatching = function ($text1, $text2) {
            $minMatchScore = 62.5;
            similar_text(strtolower(trim($text1)), strtolower(trim($text2)), $percentage);
            return $percentage >= $minMatchScore;
        };

        $automap = [];

        foreach ($this->fields as $field) {
            // Check for exact matching
            foreach ($headers as $key => $header) {
                if ($exactMatching($field->tag, $header) || $exactMatching($field->label, $header)) {
                    $automap[$header] = $field->id;
                    unset($headers[$key]);
                    break;
                }
            }

            if (in_array($field->id, array_values($automap))) {
                continue;
            }

            // Fall back to relative match
            foreach ($headers as $key => $header) {
                if ($relativeMatching($field->tag, $header) || $exactMatching($field->label, $header)) {
                    $automap[$header] = $field->id;
                    unset($headers[$key]);
                    break;
                }
            }
        }

        return $automap;
    }
}
