{extends 'customer/page.tpl'}

{block name='page_content'}

  <button class="btn btn-dashboard-show mb-4 d-lg-none">
      {l s='Show menu' d='Shop.Customer.Dashboard'}
  </button>

  <div class="dashboard-right-sidebar">

      <div class="dashboard-information">

          <div class="dashboard-title d-flex justify-content-between align-items-center">
              <div class="title">
                  <h2>{l s='Your personal information' d='Shop.Customer.Identity'}</h2>
              </div>
          </div>

          <form class="form"  method="post" id="customer-information" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">

             <input type="hidden" id="customer-sports" value="{$customer_sports}" />

             <div class="row">
                    {foreach from=$customer_form_fields item=field}
                      {if $field.type == 'hidden'}
                        <input type="hidden" name="{$field.name}" value="{$field.value}">
                      {else}
                        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                          <div class="mb-3">
                              {if $field.type !== 'checkbox'}
                                <label class="form-label" for="field-{$field.name}">
                                  {$field.label}
                                  {if $field.required}
                                    <span>*</span>
                                  {/if}
                                  {if $field.name == 'birthday'}
                                    <span>({l s='Optional' d='Shop.Customer.Identity'})</span>
                                  {/if}
                                </label>
                              {/if}
                              {if $field.type == 'select' || $field.type == 'countrySelect'}
                                <select id="field-{$field.name}" name="{$field.name}" class="form-control select2 {if $field.type == 'countrySelect'}js-country{/if}" {if $field.required}required{/if}>
                                  <option value="" disabled selected>{l s='Please choose' d='Shop.Customer.Identity'}</option>
                                  {foreach from=$field.availableValues item=label key=value}
                                    <option value="{$value}" {if $value == $field.value}selected{/if}>{$label}</option>
                                  {/foreach}
                                </select>
                              {elseif $field.type == 'radio-buttons'}
                                {foreach from=$field.availableValues item=label key=value}
                                  <label class="form-control" for="field-{$field.name}-{$value}">
                                    <span class="custom-radio">
                                      <input type="radio" name="{$field.name}" id="field-{$field.name}-{$value}"value="{$value}" {if $field.required}required{/if}{if $value == $field.value}checked{/if}>
                                    </span>
                                    {$label}
                                  </label>
                                {/foreach}
                              {elseif $field.type == 'checkbox'}
                                <div class="form-check">
                                  <div class="check">
                                    <input class="form-check-input fixed-size-input" type="checkbox" id="{$field.name}" name="{$field.name}" {if $field.value}checked{/if} {if $field.required}required{/if} >
                                    <label class="form-check-label" for="condition">
                                      {$field.label nofilter} {l s='I have read and expressly accept the conditions' d='Shop.Customer.Identity'} <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' d='Shop.Customer.Identity'}</a>
                                    </label>
                                  </div>
                                </div>
                              {elseif $field.type == 'date'}
                                <input id="field-{$field.name}" name="{$field.name}" class="form-control" type="date" value="{$field.value}" {if isset($field.availableValues.placeholder)}placeholder="{$field.availableValues.placeholder}"{/if}>
                              {elseif $field.type == 'password'}
                                <div class="input-group js-parent-focus">
                                    <input id="field-{$field.name}" class="form-control js-visible-password" autocomplete="new-password" name="{$field.name}" type="password" value="" {if isset($field.autocomplete)}autocomplete="{$field.autocomplete}"{/if}  {literal}pattern=".{5,}"{/literal} {if $field.required}required{/if} >
                                    <button class="btn btn-outline-secondary toggle-passwords" type="button" data-target="field-{$field.name}" data-text-show="{l s='Show' d='Shop.Customer.Identity'}" data-text-hide="{l s='Hide' d='Shop.Customer.Identity'}" >
                                        {l s='Show' d='Shop.Customer.Identity'}
                                    </button>
                                </div>
                                  <label for="field-{$field.name}" class="error" style="display: none;"></label>
                              {else}
                                <input id="field-{$field.name}" class="form-control {if $field.name == 'birthday'}cumple{/if}" name="{$field.name}" type="{$field.type}" value="{$field.value}"  {if isset($field.autocomplete)}autocomplete="{$field.autocomplete}"{/if} {if isset($field.availableValues.placeholder)}placeholder="{$field.availableValues.placeholder}"{/if} {if isset($field.maxLength)}maxlength="{$field.maxLength}"{/if} {if $field.required}required{/if}>
                                {if $field.name == 'birthday'}
                                  <span class="texto-cheque-regalo">{l s='We will send you a gift voucher for your birthday' d='Shop.Customer.Identity'}</span>
                                {/if}
                              {/if}
                              {if isset($field.errors) && $field.errors|count}
                                <div class="text-danger mt-1">
                                  {foreach from=$field.errors item=error}
                                    <div>{$error}</div>
                                  {/foreach}
                                </div>
                              {/if}
                          </div>
                        </div>
                      {/if}
                    {/foreach}

                     <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                          <div class="mb-3">
                              <label for="email" class="form-label">{l s='Select sports of your interest' d='Shop.Customer.Identity'}</label>
                              <div class="sports-container">
                                  <label>
                                      <div class="sports-wrap">
                                          <div class="sport-select" id="sports">
                                              {foreach from=$sports item=sport}
                                                  {assign var="id" value="sports_"|cat:$sport.id|replace:' ':'_'}
                                                  <div class="sport-item">
                                                      <input type="checkbox"
                                                             name="sports[]"
                                                             value="{$sport.id}"
                                                             id="registersports-{$id}"
                                                             {if in_array((string)$sport.id, $customer_sports_fields)}checked{/if} />
                                                      <label for="registersports-{$id}">
                                                          <span class="sport-label">{$sport.name|upper}</span>
                                                      </label>
                                                  </div>
                                              {/foreach}
                                          </div>
                                      </div>
                                  </label>
                              </div>
                              <label for="sports[]" class="error"></label>
                          </div>
                     </div>

                     <div class="response-output"></div>

                     <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                         <div class="mb-3">
                             <div class="form-check">
                                 <div class="check">
                                     <input class="form-check-input fixed-size-input" type="checkbox" id="commercial" name="commercial" required >
                                     <label class="form-check-label" for="commercial">
                                         {l s='I have read and expressly accept the conditions' d='Shop.Customer.Identity'} <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' d='Shop.Customer.Identity'}</a>
                                     </label>
                                 </div>
                                 <label for="commercial" class="error" style="display: none;"></label>
                             </div>
                         </div>
                     </div>

                     <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                         <div class="form-check">
                             <div class="check">
                                 <input class="form-check-input fixed-size-input" type="checkbox" id="parties" name="parties" >
                                 <label class="form-check-label" for="parties">
                                     {l s='I agree to receive information about other inventaries and services of interest to me' d='Shop.Customer.Identity'}  <a href="/politica-de-privacidad" target="_blank">{l s='Data Protection' d='Shop.Customer.Identity'} </a>
                                 </label>
                             </div>
                             <label for="parties" class="error" style="display: none;"></label>
                         </div>
                     </div>
                    </div>


              <button type="submit" class="btn btn-primary w-100 mt-3" disabled id="submit-information" >
                  {l s='Update' d='Shop.Customer.Identity'}
              </button>
             </div>

          </form>

      </div>

  </div>


{/block}
