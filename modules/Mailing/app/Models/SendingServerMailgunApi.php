<?php

/**
 * SendingServerMailgunApi class.
 *
 * Abstract class for Mailgun API sending server
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

use Modules\Mailing\Library\StringHelper;

class SendingServerMailgunApi extends SendingServerMailgun
{
    protected $table = 'sending_servers';

    /**
     * Send the provided message.
     *
     *
     * @param message
     * @return bool
     */
    public function send($message, $params = [])
    {
        $toEmail = array_keys($message->getTo())[0];
        $result = $this->client()->messages()->sendMime($this->domain, [$toEmail], $message->toString(), []);

        return [
            'runtime_message_id' => StringHelper::cleanupMessageId($result->getId()),
            'status' => self::DELIVERY_STATUS_SENT,
        ];
    }
}
