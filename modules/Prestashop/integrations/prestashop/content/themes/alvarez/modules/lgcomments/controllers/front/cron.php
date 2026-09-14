<?php

/**
 *  Please read the terms of the CLUF license attached to this module(cf "licences" folder)
 *
 * @author    Línea Gráfica E.C.E. S.L.
 * @copyright Lineagrafica.es - Línea Gráfica E.C.E. S.L. all rights reserved.
 * @license   https://www.lineagrafica.es/licenses/license_en.pdf
 *            https://www.lineagrafica.es/licenses/license_es.pdf
 *            https://www.lineagrafica.es/licenses/license_fr.pdf
 */
class LgCommentsCronModuleFrontControllerOverride extends LgCommentsCronModuleFrontController
{
    private function executeCron()
    {
        $lgMailer = new LGMailer;
        $orders = $lgMailer->getOrders();
        $email_sended_number = 0;
        $orders_mail = '';
        $response = [];
        $response['status'] = 'OK'; // Por defecto será ok excepto que haya algún error

        // Trace for debugging
        if ($this->lgdebug && $this->lgdebugtrace) {
            $response['debug']['trace']['START'] = 'CRON PROCCESS START';
        }

        if ($this->lgdebug) {
            $response['debug']['order'] = $orders;
        }

        if (count($orders) > 0) {
            foreach ($orders as $order) {
                if (! self::isValidEmail($order['email'])) {
                    continue;
                }

                // Todo: OJO esta llamada paar obtener el hash es necesario antes para asignarlo si no lo tiene
                $order['hash'] = $lgMailer->getHash($order);
                $template_vars = $lgMailer->getTemplateVars($order);
                $subject = $lgMailer->getSubject($order['id_lang']);
                $mail_dir = $lgMailer->getTemplatePath($order['id_lang']);
                $mail_lang = $lgMailer->getTemplateLang($order['id_lang']);
                $shop = new Shop((int) $order['id_shop'], (int) $mail_lang);

                /* JLP - 07/04/2022 - enlace reseñas google */
                $template_vars['{link_google}'] = 'https://g.page/alvarez-deporte-y-tiempo-libre/review?gm';

                if ($this->lgdebug) {
                    $response['debug']['configuration']['dias_desde'] = $lgMailer->dias_desde;
                    $response['debug']['configuration']['dias_hasta'] = $lgMailer->dias_hasta;
                    $response['debug']['configuration']['sendtwice'] = $lgMailer->sendtwice;
                    $response['debug']['configuration']['daysafter'] = $lgMailer->daysafter;
                    $response['debug']['configuration']['email_cron'] = $lgMailer->email_cron;
                    $response['debug']['configuration']['email_alerts'] = $lgMailer->email_alerts;

                    $response['debug']['hash'] = $order['hash'];
                    $response['debug']['$emplate_vars'] = $template_vars;
                    $response['debug']['subject'] = $subject;
                    $response['debug']['mail_dir'] = $mail_dir;
                    $response['debug']['mail_lang'] = $mail_lang;
                }

                // Trace for debugging
                if ($this->lgdebug && $this->lgdebugtrace) {
                    $response['debug']['trace']['CRON'][$order['id_order']][] = 'PROCESING ORDER: '.$order['id_order'];
                }

                if ($lgMailer->needSendFirstTime($order)) {
                    // Trace for debugging
                    if ($this->lgdebug && $this->lgdebugtrace) {
                        $response['debug']['trace']['CRON'][$order['id_order']][] = 'THIS ORDER HAVE NOT COMMENTS';
                    }

                    if (Mail::Send(
                        $mail_lang, // $idLang,
                        'opinion-request', // $template,
                        $subject, // $subject,
                        $template_vars, // $templateVars,
                        $order['email'], // $to,
                        null, // $toName = null,
                        null, // $from = null,
                        $shop->name, // $fromName = null,
                        null, // $fileAttachment = null,
                        null, // $mode_smtp = null,
                        $mail_dir, // $templatePath = _PS_MAIL_DIR_,
                        $this->lgdebug, // $die = false,
                        $order['id_shop'], // $idShop = null,
                        null, // $bcc = null,
                        null, // $replyTo = null,
                        null // $replyToName = null
                    )) {
                        // Trace for debugging
                        if ($this->lgdebug && $this->lgdebugtrace) {
                            $response['debug']['trace']['CRON'][$order['id_order']][] = 'MAIL SENT';
                        }

                        $this->saveCommentOrder($order['id_order'], $order['id_customer'], $order['hash']);
                        $email_sended_number++;
                        $orders_mail .= $order['id_order'].', ';
                        $response['orders_sended']['first_time'][$order['id_order']] =
                            $this->module->l('Order').' #'.$order['id_order'];
                    } else {
                        // Trace for debugging
                        if ($this->lgdebug && $this->lgdebugtrace) {
                            $response['debug']['trace']['CRON'][$order['id_order']][] = 'MAIL NOT SENT';
                        }

                        $response['orders_sended']['first_time_errors'][$order['id_order']] = $this->module->l('Order')
                            .' #'.$order['id_order'].': '
                            .$this->module->l('Email not sent: problem with your email configuration');
                    }
                } elseif ($lgMailer->needSendAgain($order)) { // Todo esto se llevará a la clase
                    // Trace for debugging
                    if ($this->lgdebug && $this->lgdebugtrace) {
                        $response['debug']['trace']['CRON'][$order['id_order']][] = 'FIRTS MAIL SENDED, SENDING TWICE';
                    }

                    if (Mail::Send(
                        $mail_lang,
                        'opinion-request',
                        $subject,
                        $template_vars,
                        $order['email'],
                        null,
                        null,
                        $shop->name, // $fromName = null,
                        null,
                        null,
                        $mail_dir,
                        $this->lgdebug, // $die = false,
                        $order['id_shop'], // $idShop = null,
                        null, // $bcc = null,
                        null, // $replyTo = null,
                        null // $replyToName = null
                    )) {
                        // Trace for debugging
                        if ($this->lgdebug && $this->lgdebugtrace) {
                            $response['debug']['trace']['CRON'][$order['id_order']][] = 'MAIL SENDED';
                        }

                        $this->markOrderAsSendTwice($order['id_order']);
                        $email_sended_number++;
                        $orders_mail .= $order['id_order'].', ';
                        $response['orders_sended']['second_time'][$order['id_order']] =
                            $this->module->l('Order').' #'.$order['id_order'];
                    } else {
                        // Trace for debugging
                        if ($this->lgdebug && $this->lgdebugtrace) {
                            $response['debug']['trace']['CRON'][$order['id_order']][] = 'MAIL NOT SENDED';
                        }

                        $response['orders_sended']['second_time_errors'][$order['id_order']] = $this->module->l('Order')
                            .' #'.$order['id_order'].': '
                            .$this->module->l('Email not sent: problem with your email configuration');
                    }
                } else {
                    // Trace for debugging
                    if ($this->lgdebug && $this->lgdebugtrace) {
                        $response['debug']['trace']['CRON'][$order['id_order']][] = 'MAILS SENDED, DO NOT DO NOTHING';
                        $response['debug']['trace']['CRON'][$order['id_order']][] = [
                            'FIRSTIME CONDITION' => [
                                'order sent number of times' => (int) $order['sent'],
                                'loidorder' => (int) isset($order['loidorder']),
                                'CONDITION' => '',
                                'loidorder is not setted' => (int) ! isset($order['loidorder']),
                                'OR' => '',
                                'loidorder is setted AND order sent number of times == 0' => (int) isset($order['loidorder']) && ((int) $order['sent'] == 0),
                                'RESULT' => (int) $lgMailer->needSendFirstTime($order),
                            ],
                            'SECONDTIME CONDITION' => [
                                'order voted' => (int) $order['voted'],
                                'order sent' => (int) $order['sent'],
                                'date-email' => DateTime::createFromFormat(
                                    'Y-m-d H:i:s',
                                    $order['date_email']
                                )->format('d-m-Y H:i:s'),
                                'RESULT' => (int) $lgMailer->needSendAgain($order),
                            ],
                        ];
                    }

                    $order_already_sended = $this->module->l('Order').' #'.$order['id_order'].': '.
                        $this->module->l('Email already send');

                    if ($order['date_email2'] != '0000-00-00 00:00:00') {
                        $order_already_sended .= ' - '.
                            date('d/m/Y H:i', strtotime($order['date_email2'])).'';
                    }
                    $response['orders_already_sended'][$order['id_order']] = $order_already_sended;
                }
            }

            /** Email confirmación cron */
            if ($email_sended_number && $lgMailer->email_cron && $lgMailer->email_alerts == 1) {
                // Trace for debugging
                if ($this->lgdebug && $this->lgdebugtrace) {
                    $response['debug']['trace']['ADMIN'][] = 'SENDING MAIL FOR ADMINISTRATOR';
                }

                $template_vars = [
                    '{pedidos}' => $orders_mail,
                ];

                $mail_dir = $lgMailer->getTemplatePath();
                $mail_lang = $lgMailer->getTemplateLang();

                Mail::Send(
                    $mail_lang,
                    'cron-confirmation',
                    Configuration::get('PS_LGCOMMENTS_SUBJECT_CRON'),
                    $template_vars,
                    $lgMailer->email_cron,
                    null,
                    null,
                    $shop->name, // $fromName = null,
                    null,
                    null,
                    $mail_dir,
                    $this->lgdebug, // $die = false,
                    $order['id_shop'], // $idShop = null,
                    null, // $bcc = null,
                    null, // $replyTo = null,
                    null // $replyToName = null
                );
            }
        } else {
            $response['orders_sended'] = $this->module->l('No email sent:').
                $this->module->l('you don\'t have any order that corresponds to the selected criteria.').
                $this->module->l('Please modify your backups and expand your range of selection.');
        }

        // Trace for debugging
        if ($this->lgdebug && $this->lgdebugtrace) {
            $response['debug']['trace']['END'] = 'CRON PROCCESS ENDS';
        }

        LGCommentsAjax::returnResponse($response);
    }

    private function saveCommentOrder($id_order, $id_customer, $hash, $date = null)
    {
        if (is_null($date)) {
            $date = 'NOW()';
        } else {
            $date = '"'.$date.'"';
        }

        $sql = 'REPLACE INTO `'._DB_PREFIX_.'lgcomments_orders` '.
            '('.
            '   `id_order`, '.
            '   `id_customer`, '.
            '   `hash`, '.
            '   `voted`, '.
            '   `sent`, '.
            '   `date_email`, '.
            '   `date_email2`'.
            ') VALUES ('.
                (int) $id_order.', '.
                (int) $id_customer.', "'.
                pSQL($hash).'", '.
                0 .', '.
                1 .', '.
                'NOW(), '.
                0 .
            ')';

        return Db::getInstance()->execute($sql);
    }

    private function markOrderAsSendTwice($id_order)
    {
        $sql = 'UPDATE `'._DB_PREFIX_.'lgcomments_orders`
                SET sent = "2", date_email2 = NOW()
                WHERE id_order = '.(int) $id_order;

        Db::getInstance()->execute($sql);
    }
}
