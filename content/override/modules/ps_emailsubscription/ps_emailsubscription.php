<?php
/**
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ps_EmailsubscriptionOverride extends Ps_Emailsubscription
{

    /**
     * Returns a email by token.
     *
     * @param string $token
     *
     * @return string email
     */
    protected function getEmailByToken($token)
    {
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'susc_newsletter`
                WHERE MD5(CONCAT( `email` , \'' . pSQL(Configuration::get('NW_SALT')) . '\')) = \'' . pSQL($token) . '\'
                AND `lopd` = 0 order by `id_susc_newsletter` desc limit 1';

        $result = Db::getInstance()->ExecuteS($sql);
        return $result[0];
    }

    /**
     * Ends the registration process to the newsletter.
     *
     * @param string $token
     *
     * @return string
     */
    public function confirmEmail($token)
    {

        $email = $this->getEmailByToken($token);

        if (is_null($email)) {
            return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');
        }else{
            $sql = "UPDATE `"._DB_PREFIX_."susc_newsletter` set `lopd` = 1 where `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'";
            Db::getInstance()->execute($sql);

            $susc_newsletter_data = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."susc_newsletter where `id_susc_newsletter` = '".$email["id_susc_newsletter"]."'")[0];
            $id_lang_gestion = AlvarezERP::getIdiomaGestion($susc_newsletter_data['id_lang']);
            $id_pais_gestion = AlvarezERP::getPaisGestion($susc_newsletter_data['id_lang']);
            $existe_cliente_erp = AlvarezERP::recuperarclienteerpAlsernet($susc_newsletter_data['email']);
            AddisLogger::log(__FILE__, __FUNCTION__, null, 'Nombre => '.$susc_newsletter_data['nombre'].", Email =>".$susc_newsletter_data['email'].", id_lang=>".$id_lang_gestion.", Existe ERP =>".$existe_cliente_erp);
            if (!$existe_cliente_erp) {
                $response = AlvarezERP::guardardatosclienteerp(null, 
                                                    $susc_newsletter_data['nombre'], 
                                                    $susc_newsletter_data['apellidos'], 
                                                    null, 
                                                    $susc_newsletter_data['email'], 
                                                    null, 
                                                    null, 
                                                    $id_lang_gestion, 
                                                    null, 
                                                    null, 
                                                    null, 
                                                    null, 
                                                    null, 
                                                    $id_pais_gestion, 
                                                    null, 
                                                    null, 
                                                    null, 
                                                    null, 
                                                    null, 
                                                    null, 
                                                    null, 
                                                    str_replace(" ", "T", $susc_newsletter_data['fecha']), 
                                                    $susc_newsletter_data['cliente_no_info_comercial'],
                                                    $susc_newsletter_data['cliente_no_datos_a_terceros'], 
                                                    $susc_newsletter_data['ids_alta_baja'], 
                                                    null, 
                                                    0);
                AddisLogger::log(__FILE__, __FUNCTION__, null, 'Alta de cliente en gestión mediante suscripción => '.$response);
            }else{
                AddisLogger::log(__FILE__, __FUNCTION__, null, 'El cliente ya existe en gestión => '.$susc_newsletter_data['email']);
                return $this->trans('This email is already registered and/or invalid.', [], 'Modules.Emailsubscription.Shop');
            }
            $res = AlvarezERP::savelopd($email["email"], str_replace(' ', 'T', date('Y-m-d H:i:s')), 0,0);
            return $this->trans('Thank you for subscribing to our newsletter.', [], 'Modules.Emailsubscription.Shop');
        }
    }

    /**
     * Send a confirmation email.
     *
     * @param string $email
     *
     * @return bool
     */
    protected function sendConfirmationEmail($email)
    {
        $language = new Language($this->context->language->id);

        return Mail::send(
            $this->context->language->id,
            'newsletter_conf',
            $this->trans(
                'Newsletter confirmation',
                [],
                'Emails.Subject',
                $language->locale
            ),
            [],
            pSQL($email),
            null,
            null,
            null,
            null,
            null,
            dirname(__FILE__) . '/mails/',
            false,
            $this->context->shop->id
        );
    }

}
