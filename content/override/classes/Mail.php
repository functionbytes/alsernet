<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

/**
 * Class MailCore.
 */
class Mail extends MailCore
{
    /**
     * Send Email.
     *
     * @param int $idLang Language ID of the email (to translate the template)
     * @param string $template Template: the name of template not be a var but a string !
     * @param string $subject Subject of the email
     * @param array $templateVars Template variables for the email
     * @param string|array<string> $to To email
     * @param string|array<string> $toName To name
     * @param string $from From email
     * @param string $fromName To email
     * @param array $fileAttachment array with three parameters (content, mime and name).
     *                              You can use an array of array to attach multiple files
     * @param bool $mode_smtp SMTP mode (deprecated)
     * @param string $templatePath Template path
     * @param bool $die Die after error
     * @param int $idShop Shop ID
     * @param string $bcc Bcc recipient address. You can use an array of array to send to multiple recipients
     * @param string $replyTo Reply-To recipient address
     * @param string $replyToName Reply-To recipient name
     *
     * @return bool|int Whether sending was successful. If not at all, false, otherwise amount of recipients succeeded.
     */
    public static function send(
        $idLang,
        $template,
        $subject,
        $templateVars,
        $to,
        $toName = null,
        $from = null,
        $fromName = null,
        $fileAttachment = null,
        $mode_smtp = null,
        $templatePath = _PS_MAIL_DIR_,
        $die = false,
        $idShop = null,
        $bcc = null,
        $replyTo = null,
        $replyToName = null
    ) {
        if (!$idShop) {
            $idShop = Context::getContext()->shop->id;
        }

        if (!is_array($templateVars)) {
            $templateVars = [];
        }
        
        $subject = $subject;


        $templateVars['{shop_domain}'] = Tools::getShopDomain(true).'/';
        if (Configuration::get('PS_SSL_ENABLED')) {
            $templateVars['{shop_domain}'] = Tools::getShopDomainSsl(true).'/';
        }

        $templateVars['{shop_phone}'] = Tools::safeOutput(Configuration::get('PS_SHOP_PHONE'));

        $templateVars['{shop_email}'] = Tools::safeOutput(Configuration::get('PS_SHOP_EMAIL'));

        $templateVars['{stores_url}'] = Context::getContext()->link->getCMSLink(
            18,
            null,
            null,
            $idLang,
            $idShop
        );

        $templateVars['{delivery_times_cms_url}'] = Context::getContext()->link->getCMSLink(
            25,
            null,
            null,
            $idLang,
            $idShop
        );

        return parent::send($idLang, $template, $subject, $templateVars, $to, $toName, $from, $fromName, $fileAttachment, $mode_smtp, $templatePath, $die, $idShop, $bcc, $replyTo, $replyToName);
    }
}
