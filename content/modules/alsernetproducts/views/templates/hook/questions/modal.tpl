{*
 *  Please read the terms of the CLUF license attached to this module(cf "licences" folder)
 *
 *  @author    Línea Gráfica E.C.E. S.L.
 *  @copyright Lineagrafica.es - Línea Gráfica E.C.E. S.L. all rights reserved.
 *  @license   https://www.lineagrafica.es/licenses/license_en.pdf
 *             https://www.lineagrafica.es/licenses/license_es.pdf
 *             https://www.lineagrafica.es/licenses/license_fr.pdf
 *}
<div id="form_review_popup" style="display: none;">
    {if !$logged}
        <p class="form-review-message">
            <a href="{$authentication_url|escape:'htmlall':'UTF-8'}">
                <i class="icon-sign-in"></i> {l s='Please, login to leave a review' mod='lgcomments'}
            </a>
        </p>
    {elseif $alreadyreviewed}
        <p class="form-review-message">
            <br>{l s='You have already written a review.' mod='lgcomments'}
        </p>
    {else}
        <input type="hidden" name="lg_iso" id="lg_iso" value="{$lang_iso|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="lg_id_customer" id="lg_id_customer" value="{$id_customer|escape:'htmlall':'UTF-8'}"/>
        {if !isset($is_shop_comment)}<input type="hidden" name="lg_id_product" id="lg_id_product" value="{$id_product|escape:'htmlall':'UTF-8'}"/>{/if}
        <div class="form-block">
            <div class="lg-label-error error hidden">{l s='Has been an error while trying send the comment. Please, reload the page and try again.' mod='lgcomments'}</div>
        </div>
        <h3>{l s='Write a review' mod='lgcomments'}</h3>
        <div class="form-block star-rating">
            <select name="lg_score" id="lg_score">
                <option value="0">{if $ratingscale == 5}0/5{elseif $ratingscale == 10}0/10{elseif $ratingscale == 20}0/20{else}0/10{/if}</option>
                <option value="1">{if $ratingscale == 5}0,5/5{elseif $ratingscale == 10}1/10{elseif $ratingscale == 20}2/20{else}1/10{/if}</option>
                <option value="2">{if $ratingscale == 5}1/5{elseif $ratingscale == 10}2/10{elseif $ratingscale == 20}4/20{else}2/10{/if}</option>
                <option value="3">{if $ratingscale == 5}1,5/5{elseif $ratingscale == 10}3/10{elseif $ratingscale == 20}6/20{else}3/10{/if}</option>
                <option value="4">{if $ratingscale == 5}2/5{elseif $ratingscale == 10}4/10{elseif $ratingscale == 20}8/20{else}4/10{/if}</option>
                <option value="5">{if $ratingscale == 5}2,5/5{elseif $ratingscale == 10}5/10{elseif $ratingscale == 20}10/20{else}5/10{/if}</option>
                <option value="6">{if $ratingscale == 5}3/5{elseif $ratingscale == 10}6/10{elseif $ratingscale == 20}12/20{else}6/10{/if}</option>
                <option value="7">{if $ratingscale == 5}3,5/5{elseif $ratingscale == 10}7/10{elseif $ratingscale == 20}14/20{else}7/10{/if}</option>
                <option value="8">{if $ratingscale == 5}4/5{elseif $ratingscale == 10}8/10{elseif $ratingscale == 20}16/20{else}8/10{/if}</option>
                <option value="9">{if $ratingscale == 5}4,5/5{elseif $ratingscale == 10}9/10{elseif $ratingscale == 20}18/20{else}9/10{/if}</option>
                <option value="10" selected>{if $ratingscale == 5}5/5{elseif $ratingscale == 10}10/10{elseif $ratingscale == 20}20/20{else}10/10{/if}</option>
            </select>
            <img style="width:{$starsize|escape:'htmlall':'UTF-8'}px!important"
                 src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/10stars.png"
                 id="lg_stars"
                 alt="rating">
        </div>
        <div class="form-block">
            <label for="lg_nick">{l s='Nick:' mod='lgcomments'}</label>
            <input type="text" maxlength="50" id="lg_nick" name="lg_nick" class="lg-required" value="" required />
        </div>
        <div class="form-block">
            <label for="lg_title">{l s='Title:' mod='lgcomments'}</label>
            <input type="text" maxlength="50" id="lg_title" name="lg_title" class="lg-required" value="" required />
        </div>
        <div class="form-block">
            <label for="lg_comment">{l s='Comment:' mod='lgcomments'}</label>
            <textarea id="lg_comment" name="lg_comment" class="lg-required" required></textarea>
        </div>
        {if isset($lgcomments_id_module)}
            {hook h='displayGDPRConsent' mod='psgdpr' id_module=$lgcomments_id_module}
        {/if}
        <div class="form-block">
            <span id="submit_review" class="lg-button-success">{l s='Send' mod='lgcomments'}</span>
        </div>
    {/if}
</div>