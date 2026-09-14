{*
* Index principal para modales de carrito abandonado
* Incluye todos los tipos de modal separados
* AlsernetShopping Module
*}

{* Incluir todos los modales por separado *}
{include file='module:alsernetshopping/views/templates/front/abandonment/modals/simple_reminder.tpl'}
{include file='module:alsernetshopping/views/templates/front/abandonment/modals/discount_offer.tpl'}
{include file='module:alsernetshopping/views/templates/front/abandonment/modals/urgency_alert.tpl'}
{include file='module:alsernetshopping/views/templates/front/abandonment/modals/related_products.tpl'}
{include file='module:alsernetshopping/views/templates/front/abandonment/modals/session_recovery.tpl'}

{* Incluir templates comunes *}
{include file='module:alsernetshopping/views/templates/front/abandonment/modals/common_templates.tpl'}

{* Incluir CSS si no está ya incluido *}
{if !$css_loaded}
<link rel="stylesheet" href="{$module_dir}views/css/front/abandonment/modals.css">
{/if}