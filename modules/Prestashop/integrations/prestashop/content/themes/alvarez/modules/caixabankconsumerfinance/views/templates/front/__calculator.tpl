{*
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2018 PrestaShop SA
    * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

    <form action="{$action}">
        {capture name=path}
        {/capture}

        <div style="display:none">
            <div class="paguro_legal_conditions" id="legaldata">
                <p>{l s='Con arreglo al Reglamento (UE) 2016/679 del Parlamento Europeo y del Consejo de 27 de abril de 2016 relativo a la protección de las personas físicas en lo que respecta al tratamiento de datos personales y a la libre circulación de estos datos y por el que se deroga la Directiva 95/46/CE (Reglamento general de protección de datos), a la normativa que lo desarrolla, y a cuanta otra sea de aplicación en materia de secreto bancario y confidencialidad:' d='Shop.Theme.Global'}</p>
                <p>{l s='A.- CaixaBank Payments & Consumer, E.F.C., E.P., S.A.U., Sociedad Unipersonal (CaixaBank Payments & Consumer), perteneciente al grupo CaixaBank, informa al solicitante y, si lo hay, al avalista: (i) que recabará datos de carácter personal (número del documento personal de identidad, dirección postal y electrónica, número de teléfono fijo y móvil, fecha de nacimiento, estado civil, nacionalidad, régimen laboral y, en su caso, identificación de la empresa en la que trabaja, cuenta bancaria de domiciliación, entre otros) y los tratará para el estudio sobre la viabilidad de esta solicitud de financiación (ii) del derecho de este establecimiento financiero de crédito a consultar sus datos a la Central de Información de Riesgos del Banco de España (CIR), a los efectos del estudio y análisis sobre la viabilidad de la operación de financiación que le ha/n solicitado; (iii) de la obligación de CaixaBank Payments & Consumer de declarar a la CIR los datos necesarios para identificar a las personas con quienes se mantengan, directa o indirectamente, riesgos de crédito, incluyendo sus características y, en particular, las que afecten al importe y a su recuperabilidad;- y, si se trata de empresarios individuales actuando en el ejercicio de su actividad empresarial, haciendo constar esa condición; (iv) del derecho que le/s asiste para obtener de la CIR informes sobre los riesgos que puedan tener allí registrados; (v) de la posibilidad de que CaixaBank Payments & Consumer ceda sus datos a CONFIRMA SISTEMAS DE INFORMACIÓN, S.L. (CONFIRMA), con la finalidad de prevenir el fraude. Las entidades adheridas al Reglamento que regula dicho tratamiento, todas ellas identificadas en www.confirmasistemas.es, son responsables del mismo y su encargada del tratamiento es CONFIRMA, ante la que el solicitante y el avalista, si lo hay, puede/n ejercer sus derechos de acceso, rectificación, oposición, supresión, limitación del tratamiento y portabilidad de datos, previstos en el Reglamento general de protección de datos mediante escrito dirigido a Avda. Industria, 18 de (28760) Tres Cantos, (vi) del derecho de este establecimiento financiero a consultar sus datos en ficheros comunes de solvencia patrimonial y crédito y (vii) que los tratamientos indicados podrán ser realizados de manera automatizada y conllevar la elaboración de perfiles, con las finalidades ya señaladas. A este efecto, CaixaBank Payments & Consumer le informa de su derecho a obtener la intervención humana en los tratamientos, a expresar su punto de vista, a obtener una explicación acerca de la decisión tomada en base al tratamiento automatizado y a impugnar dicha decisión.' d='Shop.Theme.Global'}</p>
                <p>{l s='B.- El solicitante y, si lo hay, el avalista, si es/son cliente/s de CaixaBank, S.A. (del grupo CaixaBank), autoriza/n expresamente a CaixaBank Payments & Consumer para que requiera a CaixaBank, S.A., y para que esta última le ceda la información y los datos sobre su solvencia patrimonial y crédito, incluyendo datos referentes a movimientos en sus cuentas o libretas de ahorro, y también al cumplimiento de las obligaciones dinerarias contraídas; permitiendo integrarlos en los ficheros del citado establecimiento financiero (CaixaBank Payments & Consumer) para su tratamiento a los fines indicados en el apartado A.- de este documento.' d='Shop.Theme.Global'}</p>
                <p>{l s='C.- El solicitante y, si lo hay, el avalista, para el caso de ser considerado/s persona/s física/s, ha/n sido informado/s: (i) que CaixaBank Payments & Consumer, con domicilio en c/ Caleruega 102, (28033) Madrid, como destinataria de la información y/o de los datos obtenidos, es la Responsable del Tratamiento, y también (ii) de los derechos de acceso, rectificación, oposición, supresión, limitación del tratamiento y portabilidad de los datos, que puede/n ejercer ante la misma (CaixaBank Payments & Consumer) sobre sus datos facilitados con ocasión de esta solicitud de financiación. Igualmente, tal/es firmante/s, si es/son considerado/s persona/s física/s, manifiesta/n haber sido informado/os sobre el ejercicio de esos derechos de acceso, rectificación, oposición, supresión, limitación del tratamiento y portabilidad de los datos, de acuerdo con los términos previstos legalmente, respecto de los datos declarados a dicha CIR con ocasión de esta operación de financiación, mediante escrito dirigido a Banco de España, calle Alcalá 50 de (28014) Madrid; y, si el riesgo declarado pertenece a una persona jurídica, también de la posibilidad de ejercitar derechos, dirigiéndose a la entidad declarante.' d='Shop.Theme.Global'}</p>
                <p>{l s='D.- Tratándose de persona/s física/s, en el caso de incumplimiento de las obligaciones asumidas en este documento por parte de CaixaBank Payments & Consumer y/o del personal que en ella presta servicios, se ejecutarán todas las actuaciones previstas en el Reglamento general de protección de datos y/o normativa que la desarrolla.' d='Shop.Theme.Global'}</p>
            </div>
        </div>
        <input type="hidden" name="fc" value="module" />
        <input type="hidden" name="controller" value="validation" />
        <input type="hidden" name="module" value="caixabankconsumerfinance" />
        {if $serviceAvailable == 0 || $nbProducts <= 0 || $nbRates <=0} {if $serviceAvailable==0 } <p class="warning">{l s='Financing service not available from 00:00h to 03:00h.' d='Shop.Theme.Global'}</p>
            {else}
                {if $nbProducts <= 0 } <p class="warning">{l s='Your shopping cart is empty.' d='Shop.Theme.Global'}</p>
                    {/if}
                    {if $nbRates <= 0} <p class="warning">{l s='Financing service not available.' d='Shop.Theme.Global'}</p>
                        {/if}
                    {/if}
                    {else}
                    <p class="paguro_safe_pay">{l s='Pay your purchase in comfortable terms in a safe way' d='Shop.Theme.Global'}</p>
                    <div class="clearfix"></div>
                    
                    {if $nbRates_cuotaFija > 0}
                    <div>
                        <ul class="nav nav-pills justify-content-center" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="cuotaFija-tab" data-toggle="tab" href="#cuotaFija" role="tab" aria-controls="cuotaFija" aria-selected="true">Cuota Fija</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="plazoMensual-tab" data-toggle="tab" href="#plazoMensual" role="tab" aria-controls="plazoMensual" aria-selected="false">Plazo Mensual</a>
                            </li>
                        </ul>
                    </div>
                    {/if}
                    <div id="paguro_rates" class="noUniform">
                        <div class="tab-content" id="myTabContent">
                            {assign var="counter" value="0"}
                        {if $nbRates_cuotaFija > 0}
                            <div class="tab-pane show active" id="cuotaFija" role="tabpanel" aria-labelledby="cuotaFija-tab">
                        {else}
                            <div class="tab-pane fade" id="cuotaFija" role="tabpanel" aria-labelledby="cuotaFija-tab">
                        {/if}
                                {foreach from=$rates_cuotaFija item=rate name=rates}
                                    <div class="paguro_summary_rate noUniform rate_{$counter|escape:'htmlall':'UTF-8'}" id="{$counter|escape:'htmlall':'UTF-8'}">
                                        <input id="rate_{$counter|escape:'htmlall':'UTF-8'}" value="{$rate.codTarifa}_{$rate.versionTarifa}_{$rate.numPlazos}" name="rate" class="paguro_input_rate noUniform" type="radio" onClick="mostrarDetalles(this.id);" required />
                                        {* value="{$rate.id}_{$rate.codigoTarifa}" *}
                                        <label for="rate_{$counter|escape:'htmlall':'UTF-8'}" class="paguro_description_rate noUniform">
                                            <span class="paguro_circle noUniform"></span>
                                            Por {Tools::displayPrice($rate.cuotasRestantes)}/{l s='month' d='Shop.Theme.Global'} en
                                            {$rate.numPlazos|escape:'htmlall':'UTF-8'} {l s='months' d='Shop.Theme.Global'}
                                            <span class="paguro_tintae">({l s='TIN' d='Shop.Theme.Global'} {$rate.porTin|replace:".":","|escape:'htmlall':'UTF-8'}&#37;, {l s='TAE' d='Shop.Theme.Global'} {$rate.porTae|replace:".":","}&#37;)</span>
                                        </label>
                                        <div id="paguro_chevron_rate_{$counter|escape:'htmlall':'UTF-8'}" class="paguro_chevron-down paguro_chevron noUniform" onClick="showDetailsChevron($(this).parent());">&nbsp;</div>
                                    </div>
                                    <div class="paguro_rate_details noUniform" id="div_rate_{$counter|escape:'htmlall':'UTF-8'}" style="display: none;">
                                        <div class="paguro_div_detail">
                                            <ul>
                                                <li>
                                                    <div class="paguro_primera">
                                                        <p class="title">{l s='First quote' d='Shop.Theme.Global'}</p>
                                                        <p class="price">{Tools::displayPrice($rate.primeraCuota)}</p>
                                                        <p class="commissions">
                                                            ({Tools::displayPrice($rate.cuotasRestantes|escape:'htmlall':'UTF-8')} + {l s='Opening commission' d='Shop.Theme.Global'} {$rate.comisionApertura|replace:".":","}&#37;: {Tools::displayPrice($rate.valorApertura)})
                                                        </p>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="paguro_segunda">
                                                        <p class="title">{l s='Remaining quotes' d='Shop.Theme.Global'}</p>
                                                        <p class="price">{Tools::displayPrice($rate.cuotasRestantes)}</p>
                                                        <p class="ultima_cuota">{l s='Last cuote' d='Shop.Theme.Global'}: {Tools::displayPrice($rate.ultimaCuota)}</p>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="paguro_tercera">
                                                        <p class="title">{l s='Total purchase' d='Shop.Theme.Global'}</p>
                                                        <p class="price">{Tools::displayPrice($rate.impAFinanciar)}</p>
                                                    </div>
                                                </li>
                                                <li class="paguro_last">
                                                    <div class="paguro_cuarta">
                                                        <p class="title">{l s='Total owed' d='Shop.Theme.Global'}</p>
                                                        <p class="price">{Tools::displayPrice($rate.impTotalAdeudado)}</p>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
    
                                    </div>
                                    {assign var="counter" value="`$counter+1`"}
                                {/foreach}
                            </div>
                        {if $nbRates_cuotaFija == 0}
                            <div class="tab-pane show active" id="plazoMensual" role="tabpanel" aria-labelledby="plazoMensual-tab">
                        {else}
                            <div class="tab-pane fade" id="plazoMensual" role="tabpanel" aria-labelledby="plazoMensual-tab">
                        {/if}
                                {foreach from=$rates item=rate name=rates}
                                    <div class="paguro_summary_rate noUniform rate_{$counter|escape:'htmlall':'UTF-8'}" id="{$counter|escape:'htmlall':'UTF-8'}">
                                        <input id="rate_{$counter|escape:'htmlall':'UTF-8'}" value="{$rate.codTarifa}_{$rate.versionTarifa}_{$rate.numPlazos}" name="rate" class="paguro_input_rate noUniform" type="radio" onClick="mostrarDetalles(this.id);" required />
                                        {* value="{$rate.id}_{$rate.codigoTarifa}" *}
                                        <label for="rate_{$counter|escape:'htmlall':'UTF-8'}" class="paguro_description_rate noUniform">
                                            <span class="paguro_circle noUniform"></span>
                                            Por {Tools::displayPrice($rate.cuotasRestantes)}/{l s='month' d='Shop.Theme.Global'} en
                                            {$rate.numPlazos|escape:'htmlall':'UTF-8'} {l s='months' d='Shop.Theme.Global'}
                                            <span class="paguro_tintae">({l s='TIN' d='Shop.Theme.Global'} {$rate.porTin|replace:".":","|escape:'htmlall':'UTF-8'}&#37;, {l s='TAE' d='Shop.Theme.Global'} {$rate.porTae|replace:".":","}&#37;)</span>
                                        </label>
                                        <div id="paguro_chevron_rate_{$counter|escape:'htmlall':'UTF-8'}" class="paguro_chevron-down paguro_chevron noUniform" onClick="showDetailsChevron($(this).parent());">&nbsp;</div>
                                    </div>
                                    <div class="paguro_rate_details noUniform" id="div_rate_{$counter|escape:'htmlall':'UTF-8'}" style="display: none;">
                                        <div class="paguro_div_detail">
                                            <ul>
                                                <li>
                                                    <div class="paguro_primera">
                                                        <p class="title">{l s='First quote' d='Shop.Theme.Global'}</p>
                                                        <p class="price">{Tools::displayPrice($rate.primeraCuota)}</p>
                                                        <p class="commissions">
                                                            ({Tools::displayPrice($rate.cuotasRestantes|escape:'htmlall':'UTF-8')} + {l s='Opening commission' d='Shop.Theme.Global'} {$rate.comisionApertura|replace:".":","}&#37;: {Tools::displayPrice($rate.valorApertura)})
                                                        </p>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="paguro_segunda">
                                                        <p class="title">{l s='Remaining quotes' d='Shop.Theme.Global'}</p>
                                                        <p class="price">{Tools::displayPrice($rate.cuotasRestantes)}</p>
                                                        <p class="ultima_cuota">{l s='Last cuote' d='Shop.Theme.Global'}: {Tools::displayPrice($rate.ultimaCuota)}</p>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="paguro_tercera">
                                                        <p class="title">{l s='Total purchase' d='Shop.Theme.Global'}</p>
                                                        <p class="price">{Tools::displayPrice($rate.impAFinanciar)}</p>
                                                    </div>
                                                </li>
                                                <li class="paguro_last">
                                                    <div class="paguro_cuarta">
                                                        <p class="title">{l s='Total owed' d='Shop.Theme.Global'}</p>
                                                        <p class="price">{Tools::displayPrice($rate.impTotalAdeudado)}</p>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
    
                                    </div>
                                    {assign var="counter" value="`$counter+1`"}
                                {/foreach}
                            </div>
                        </div>
                    </div>


                    <div id="paguro_legal_conditions" class="paguro_legal_conditions noUniform">
                        <p id="cff_product_lc_no_time_limit">{l s='Financial offer provided by Caixabank Consumer Finance, E.F.C., E.P., S.A.U. And subject to its approval.' d='Shop.Theme.Global'}</p>
                        <p>
                            <input id="cl" class="paguro_input_cl noUniform" type="checkbox" name="cl" required />
                            <label class="ccf" for="cl">
                                <span class="paguro_square"></span>
                                {l s='I authorize the transfer of my personal data to CaixaBank Payments & Consumer E.F.C., S.A.U., with the purpose of completing the request of said financial credit establishment.' sprintf=[$shop_name] d='Shop.Theme.Global'}
                            </label>
                        </p>
                    </div>
    </form>

    {/if}

    <script type="text/javascript">
        var errorCaixaFinanceAccept = "{l s='You must accept the transfer of personal data in order to continue.' d='Shop.Theme.Global'}";
        var errorCaixaFinanceOption = "{l s='You must choose a financing option.' d='Shop.Theme.Global'}";

        // This gets executed as soon as the rates calculator is painted and is ready for work
        document.addEventListener("DOMContentLoaded", function() {
            // Prepare the legal conditions fancybox
            $("a#legalinline").fancybox({
                'hideOnContentClick': true
            });
        });

        function checkPaymentAvailabilty() {
            if (window.jQuery) {
                $('.warning').each(function() {
                    if ($(this).is(':visible')) {
                        $('#payment-confirmation button').prop('disabled', true); //attr("disabled","disabled")
                    }
                });
            } else {
                setTimeout(checkPaymentAvailabilty, 1000);
            }
        };
        window.setInterval(checkPaymentAvailabilty, 100);
    </script>