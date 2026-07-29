<div id="review-modal" class="modal-review modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">
                    {if !$logged}
                        {l s='Please, login to leave a review' d='Shop.Theme.Global'}
                    {elseif $alreadyreviewed}
                        {l s='You have already written a review.' d='Shop.Theme.Global'}
                    {else}
                        {l s='Write a review' d='Shop.Theme.Actions'}
                    {/if}
                </h4>
                <button type="button" class="close" data-dismiss="modal" >
                    <i class="fa-light fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                {if !$logged}
                    <p class="form-review-message">
                        <a href="{$authentication_url|escape:'htmlall':'UTF-8'}">
                            <i class="icon-sign-in"></i> {l s='Please, login to leave a review' d='Shop.Theme.Global'}
                        </a>
                    </p>
                {elseif $alreadyreviewed}
                    <p class="form-review-message">
                        {l s='You have already written a review.' d='Shop.Theme.Global'}
                    </p>
                {else}
                    <input type="hidden" name="lg_iso" id="lg_iso" value="{$lang_iso|escape:'htmlall':'UTF-8'}"/>
                    <input type="hidden" name="lg_id_customer" id="lg_id_customer" value="{$id_customer|escape:'htmlall':'UTF-8'}"/>
                    <input id="lg_email" name="lg_email" type="hidden" value="{Context::getContext()->customer->email}" />

                    {if !isset($is_shop_comment)}<input type="hidden" name="lg_id_product" id="lg_id_product" value="{$id_product|escape:'htmlall':'UTF-8'}"/>{/if}

                    <div class=" star-rating">

                            <div class="row">
                             <div class="col-md-6 mb-4">
                                 <select class="form-control form-control-select select2" id="lg_score"  name="lg_score" >

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
                             </div>
                               <div class="col-md-6 mb-4 align-items-center justify-content-end row">
                                <img style="width:{$starsize|escape:'htmlall':'UTF-8'}px!important"
                                     src="{*{$module_dir|escape:'htmlall':'UTF-8'}*}/themes/alvarez/modules/lgcomments/views/img/stars/{$starstyle|escape:'htmlall':'UTF-8'}/{$starcolor|escape:'htmlall':'UTF-8'}/10stars.png"
                                     id="lg_stars"
                                     alt="rating">
                            </div>
                        </div>
                    </div>
                    <div class="clearfix ">

                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Nick:' d='Shop.Theme.Global'}</label>
                            <input type="text" class="form-control" maxlength="50" id="lg_nick" name="lg_nick" class="lg-required" value="" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Title:' d='Shop.Theme.Global'}</label>
                            <input type="text" class="form-control" maxlength="50" id="lg_title" name="lg_title" class="lg-required" value="" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{l s='Comment:' d='Shop.Theme.Global'}</label>
                            <textarea id="lg_comment" name="lg_comment" class="form-control" required></textarea>
                        </div>

                        {if isset($lgcomments_id_module)}
                                {hook h='displayGDPRConsent' mod='psgdpr' id_module=$lgcomments_id_module}
                        {/if}

                        <div class="mb-3">
                            <button id="submit_review" class="btn btn-primary w-100">{l s='Send' d='Shop.Theme.Actions'}</button>
                        </div>

                        <div class="form-block">
                            <label for="lg_comment"></label>
                        </div>
                {/if}
            </div>
        </div>
    </div>
</div>


