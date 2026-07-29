{*
* 2021 Inespay
*
* NOTICE OF LICENSE
*
* @author INESPAY <soporte@inespay.com>
    * @copyright inespay.com 2021
    * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
    *}
    <section>

        <img src="{$this_path|escape:'html':'UTF-8'}views/img/logo_inespay_color.svg" style="height: 45px; margin-bottom: 5px">
        <br><br>

        {if $iso_code == "es"}
        - Acepta nuestra Protección de datos y pulsa en <strong>Finalizar el pedido</strong>, serás redirigido a tu banco, para
            autorizar el pago en tiempo real y <strong>con total seguridad</strong>.<br>
        - Sigue los pasos y, para finalizar, en la página de “Orden de pago completada”, pulsa en CONTINUAR,
            serás redigidido de nuevo a nuestra web, con la confirmación final de tu compra.
        {elseif $iso_code == "pt"}
        - Aceite a nossa Política de Privacidade e clique em <strong>Checkout</strong>, será redireccionado para o seu banco para
            autorizar o pagamento em tempo real e com <strong>total segurança.</strong>.<br><br>
        - Siga os passos e, para finalizar, na página "Ordem de pagamento concluída", clique em CONTINUAR,
            será redireccionado para o nosso site, com a confirmação final da sua compra.
        {elseif $iso_code == "fr"}
        - Acceptez notre protection des données et cliquez sur <strong>Finaliser la commande</strong>, vous serez redirigé vers votre banque pour
            autoriser le paiement en temps réel et en <strong>toute sécurité.</strong>.<br><br>
        - Suivez les étapes et, pour terminer, sur la page "Ordre de paiement terminé", cliquez sur CONTINUER,
            vous serez redirigé vers notre site web, avec la confirmation finale de votre achat.

        {/if}
        <p style="margin-top: 20px;">


        </p>
    </section>
