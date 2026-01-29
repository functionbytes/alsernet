<?php

/**
 * Layout class.
 *
 * Model class for layouts
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   MVC Model
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Modules\Mailing\Models;

use Modules\Mailing\Library\ExtendedSwiftMessage;
use Modules\Mailing\Library\Traits\HasUid;
use Closure;
use Illuminate\Database\Eloquent\Model;

class Layout extends Model
{
    use HasUid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'alias', 'content', 'subject',
    ];

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;

    /**
     * Associations.
     *
     * @var object | collect
     */
    public function pages()
    {
        return $this->hasMany('Acelle\Model\Page');
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
                $tags = [
                ];
                break;
            case 'sign_up_confirmation_email':
                $tags = [
                    ['name' => '{SUBSCRIBE_CONFIRM_URL}', 'required' => true],
                ];
                break;
            case 'sign_up_confirmation_thankyou':
                $tags = [
                ];
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
            case 'sign_up_confirmation_thankyou':
                $tags = [
                ];
                break;
            case 'unsubscribe_success_page':
                $tags = [
                ];
                break;
            case 'unsubscribe_goodbye_email':
                $tags = [
                ];
                break;
            case 'profile_update_email_sent':
                $tags = [
                ];
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
                $tags = [
                ];
                break;
            default:
                $tags = [];
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

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        return self::select('*');
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $user = $request->user();
        $query = self::select('layouts.*');

        return $query;
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public static function search($request)
    {
        $query = self::filter($request);

        $query = $query->orderBy($request->sort_order, $request->sort_direction);

        return $query;
    }
}
