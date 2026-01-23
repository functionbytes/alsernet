<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Modules\Mailer\Traits\HasUid;
use Closure;

class MailerLayout extends Model
{
    use HasUid;
    protected $table = 'mailer_layouts';

    protected $fillable = [
        'name',
        'alias',
        'code',
        'type',
        'group_name',
        'is_protected',
        'is_enabled',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public static $itemsPerPage = 25;

    /**
     * Get all translations for this layout
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MailerLayoutLang::class, 'layout_id', 'id');
    }

    /**
     * Get translation for a specific language
     */
    public function translate(?int $langId = null): ?MailerLayoutLang
    {
        if ($langId === null) {
            // Try to get from session, request, or default to first available
            $langId = session('lang_id') ?? request()->get('lang_id') ?? 1;
        }

        return $this->translations()->where('lang_id', $langId)->first()
            ?? $this->translations()->first(); // Fallback to first available translation
    }

    /**
     * Magic getter for subject (backwards compatibility)
     */
    public function getSubjectAttribute(): ?string
    {
        return $this->translate()?->subject;
    }

    /**
     * Magic getter for content (backwards compatibility)
     */
    public function getContentAttribute(): ?string
    {
        return $this->translate()?->content;
    }

    public function scopeAlias($query, $alias)
    {
        return $query->where('alias', $alias);
    }

    public function scopeCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function tags()
    {
        switch ($this->alias) {
            case 'sign_up_form':
                $tags = [
                    ['name' => '{FIELDS}', 'required' => true],
                    ['name' => '{SUBSCRIBE_BUTTON}', 'required' => true],
                ];
                break;
            case 'sign_up_thankyou_page':
                $tags = [];
                break;
            case 'sign_up_confirmation_email':
                $tags = [
                    ['name' => '{SUBSCRIBE_CONFIRM_URL}', 'required' => true],
                ];
                break;
            case 'sign_up_confirmation_thankyou':
                $tags = [];
                break;
            case 'sign_up_welcome_email':
                $tags = [
                    ['name' => '{UNSUBSCRIBE_URL}', 'required' => true],
                ];
                break;
            case 'unsubscribe_form':
                $tags = [
                    ['name' => '{EMAIL_FIELD}', 'required' => true],
                    ['name' => '{UNSUBSCRIBE_BUTTON}', 'required' => true],
                ];
                break;
            case 'unsubscribe_success_page':
                $tags = [];
                break;
            case 'unsubscribe_goodbye_email':
                $tags = [];
                break;
            case 'profile_update_email_sent':
                $tags = [];
                break;
            case 'profile_update_email':
                $tags = [
                    ['name' => '{UPDATE_PROFILE_URL}', 'required' => true],
                ];
                break;
            case 'profile_update_form':
                $tags = [
                    ['name' => '{FIELDS}', 'required' => true],
                    ['name' => '{UPDATE_PROFILE_BUTTON}', 'required' => true],
                    ['name' => '{UNSUBSCRIBE_URL}', 'required' => true],
                ];
                break;
            case 'profile_update_success_page':
                $tags = [];
                break;
            default:
                $tags = [];
        }

        $tags = array_merge($tags, [
            ['name' => '{UNSUBSCRIBE_URL}', 'required' => false],
            ['name' => '{UNSUBSCRIBE_CODE}', 'required' => false],
            ['name' => '{UID}', 'required' => false],
            ['name' => '{EMAIL}', 'required' => false],
            ['name' => '{FIRSTNAME}', 'required' => false],
            ['name' => '{LASTNAME}', 'required' => false],
            ['name' => '{LIST_NAME}', 'required' => false],
            ['name' => '{CONTACT_NAME}', 'required' => false],
            ['name' => '{CONTACT_STATE}', 'required' => false],
            ['name' => '{CONTACT_ADDRESS_1}', 'required' => false],
            ['name' => '{CONTACT_ADDRESS_2}', 'required' => false],
            ['name' => '{CONTACT_CITY}', 'required' => false],
            ['name' => '{CONTACT_ZIP}', 'required' => false],
            ['name' => '{CONTACT_COUNTRY}', 'required' => false],
            ['name' => '{CONTACT_PHONE}', 'required' => false],
            ['name' => '{CONTACT_EMAIL}', 'required' => false],
            ['name' => '{CONTACT_URL}', 'required' => false],
        ]);

        return $tags;
    }

    public function getMessage(?Closure $transform = null): ExtendedSwiftMessage
    {
        // Create a message
        $message = new ExtendedSwiftMessage;
        $message->setContentType('text/html; charset=utf-8');
        $message->setEncoder(new \Swift_Mime_ContentEncoder_PlainContentEncoder('8bit'));

        if (! is_null($transform)) {
            $htmlContent = $transform($this->content);
        } else {
            $htmlContent = $this->content;
        }

        $message->addPart($htmlContent, 'text/html');

        return $message;
    }

    public static function allTags($list = null)
    {
        $tags = [];

        $tags[] = ['name' => 'SUBSCRIBER_EMAIL', 'required' => false];

        // List field tags
        if (isset($list)) {
            foreach ($list->fields as $field) {
                if ($field->tag != 'EMAIL') {
                    $tags[] = ['name' => 'SUBSCRIBER_'.$field->tag, 'required' => false];
                }
            }
        }

        $tags = array_merge($tags, [
            ['name' => 'UNSUBSCRIBE_CODE', 'required' => false],
            ['name' => 'UNSUBSCRIBE_URL', 'required' => false],
            ['name' => 'SUBSCRIBER_UID', 'required' => false],
            ['name' => 'WEB_VIEW_URL', 'required' => false],
            ['name' => 'UPDATE_PROFILE_URL', 'required' => false],
            ['name' => 'CAMPAIGN_NAME', 'required' => false],
            ['name' => 'CAMPAIGN_UID', 'required' => false],
            ['name' => 'CAMPAIGN_SUBJECT', 'required' => false],
            ['name' => 'CAMPAIGN_FROM_EMAIL', 'required' => false],
            ['name' => 'CAMPAIGN_FROM_NAME', 'required' => false],
            ['name' => 'CAMPAIGN_REPLY_TO', 'required' => false],
            ['name' => 'CURRENT_YEAR', 'required' => false],
            ['name' => 'CURRENT_MONTH', 'required' => false],
            ['name' => 'CURRENT_DAY', 'required' => false],
            ['name' => 'LIST_NAME', 'required' => false],
            ['name' => 'LIST_FROM_NAME', 'required' => false],
            ['name' => 'LIST_FROM_EMAIL', 'required' => false],
        ]);

        return $tags;
    }
}
