<?php

/**
 * SendingServerAmazonApi class.
 *
 * Model class for Amazon API sending server
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

class SendingServerAmazonApi extends SendingServerAmazon
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
        $sent = $this->sesClient()->sendRawEmail([
            'RawMessage' => [
                'Data' => $message->toString(),
            ],
        ]);

        return [
            'runtime_message_id' => $sent['MessageId'],
            'status' => self::DELIVERY_STATUS_SENT,
        ];
    }
}
