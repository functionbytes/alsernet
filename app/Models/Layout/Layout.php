<?php

namespace App\Models\Layout;

use App\Library\ExtendedSwiftMessage;
use App\Library\Traits\HasUid;
use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Layout extends Model
{
    use HasUid;

    protected $fillable = [
        'name', 'alias', 'content', 'subject',
    ];
    public static $itemsPerPage = 25;

    public function pages()
    {
        return $this->hasMany('Acelle\Model\Page');
    }

    public function tags()
    {
        switch ($this->alias) {
            case 'sign_up_form':
                $tags = array(
                    array('name' => '{FIELDS}', 'required' => true),
                    array('name' => '{SUBSCRIBE_BUTTON}', 'required' => true),
                );
                break;
            case 'sign_up_thankyou_page':
                $tags = array(
                );
                break;
            case 'sign_up_confirmation_email':
                $tags = array(
                    array('name' => '{SUBSCRIBE_CONFIRM_URL}', 'required' => true),
                );
                break;
            case 'sign_up_confirmation_thankyou':
                $tags = array(
                );
                break;
            case 'sign_up_welcome_email':
                $tags = array(
                    array('name' => '{UNSUBSCRIBE_URL}', 'required' => true),
                );
                break;
            case 'unsubscribe_form':
                $tags = array(
                    array('name' => '{EMAIL_FIELD}', 'required' => true),
                    array('name' => '{UNSUBSCRIBE_BUTTON}', 'required' => true),
                );
                break;
            case 'sign_up_confirmation_thankyou':
                $tags = array(
                );
                break;
            case 'unsubscribe_success_page':
                $tags = array(
                );
                break;
            case 'unsubscribe_goodbye_email':
                $tags = array(
                );
                break;
            case 'profile_update_email_sent':
                $tags = array(
                );
                break;
            case 'profile_update_email':
                $tags = array(
                    array('name' => '{UPDATE_PROFILE_URL}', 'required' => true),
                );
                break;
            case 'profile_update_form':
                $tags = array(
                    array('name' => '{FIELDS}', 'required' => true),
                    array('name' => '{UPDATE_PROFILE_BUTTON}', 'required' => true),
                    array('name' => '{UNSUBSCRIBE_URL}', 'required' => true),
                );
                break;
            case 'profile_update_success_page':
                $tags = array(
                );
                break;
            default:
                $tags = array();
        }

        $tags = array_merge($tags, [
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

    public function getMessage(Closure $transform = null): ExtendedSwiftMessage
    {
        // Create a message
        $message = new ExtendedSwiftMessage();
        $message->setContentType('text/html; charset=utf-8');
        $message->setEncoder(new \Swift_Mime_ContentEncoder_PlainContentEncoder('8bit'));

        if (!is_null($transform)) {
            $htmlContent = $transform($this->content);
        } else {
            $htmlContent = $this->content;
        }

        $message->addPart($htmlContent, 'text/html');

        return $message;
    }

    public static function getAll()
    {
        return self::select('*');
    }

    public static function filter($request)
    {
        $user = $request->user();
        $query = self::select('layouts.*');

        return $query;
    }

    public static function search($request)
    {
        $query = self::filter($request);

        $query = $query->orderBy($request->sort_order, $request->sort_direction);

        return $query;
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
