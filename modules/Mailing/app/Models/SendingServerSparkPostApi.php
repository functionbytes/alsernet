<?php

/**
 * SendingServerSparkPostApi class.
 *
 * Abstract class for SparkPost API sending server
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

class SendingServerSparkPostApi extends SendingServerSparkPost
{
    protected $table = 'sending_servers';

    /**
     * Send the provided message.
     *
     *
     * @param message
     * @return array sending results
     */
    public function send($message, $params = [])
    {
        $response = $this->client()->transmissions->post($this->prepareEmail($message));

        if ($response->getStatusCode() == 200) {
            $body = $response->getBody();

            return [
                'status' => self::DELIVERY_STATUS_SENT,
            ];
        } else {
            // throw the exception with body string
            throw new \Exception($response->getBody(true));
        }
    }

    /**
     * Prepare the email message to be sent by SparkPost API.
     *
     *
     * @param SwiftMessage message
     * @return array SparkPost formatted message
     */
    private function prepareEmail($message)
    {
        $toEmail = array_keys($message->getTo())[0];
        $toName = array_values($message->getTo())[0];

        // to track bounce/feedback notification
        $msgId = $message->getHeaders()->get('X-Acelle-Message-Id')->getFieldBody();

        return [
            'content' => [
                'email_rfc822' => $message->toString(),
            ],
            'recipients' => [
                [
                    'address' => [
                        'name' => $toName,
                        'email' => $toEmail,
                    ],
                ],
            ],
            'metadata' => [
                'runtime_message_id' => $msgId,
            ],
        ];
    }
}
