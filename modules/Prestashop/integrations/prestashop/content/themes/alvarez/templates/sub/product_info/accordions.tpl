{* 
* @Module Name: AP Page Builder
* @Website: apollotheme.com - prestashop template provider
* @author Apollotheme <apollotheme@gmail.com>
* @copyright Apollotheme
* @description: ApPageBuilder is module help you can build content for your shop
*}
{block name='product_accordion'}
<div class="products-accordion" id="accordion" role="tablist" aria-multiselectable="true">
    {* Description Product Detail *}
    <div class="card" id="description">
      <div class="card-header" role="tab" id="headingdescription">
          <h5 class="h5">
            <a data-toggle="collapse" {*data-parent="#accordion"*} href="#collapsedescription" aria-expanded="true" aria-controls="collapsedescription">
              {l s='Features' d='Shop.Theme.Catalog'}
            </a>
         </h5>
      </div>
      <div id="collapsedescription" class="collapse in" role="tabpanel" aria-labelledby="headingdescription">
          <div class="card-block">
            {block name='product_description'}
              <div class="product-description">{$product.description nofilter}</div>
            {/block}
          </div>
      </div>
    </div>

    {* BEGIN - Bloque Información adicional *}
    {*{widget name='alvarezayuda' product=$product}*} {* Se ha movido al product footer *}
    {* END - Bloque Información adicional *}

    {* Attachments Product Detail *}
    {block name='product_attachments'}
    {if $product.attachments}
        <div class="card" id="attachments">
          <div class="card-header" role="tab" id="headingattachments">
              <h5 class="h5">
                <a class="collapse" data-toggle="collapse" {*data-parent="#accordion"*} href="#collapseattachments" aria-expanded="true" aria-controls="collapseattachments">
                    {l s='Attachments' d='Shop.Theme.Catalog'}
                </a>
              </h5>
          </div>
          <div id="collapseattachments" class="collapse in" role="tabpanel" aria-labelledby="headingattachments">
              <div class="card-block">
                <div class="attachments">
                  <section class="row product-attachments">
                      {*<h3 class="h5 text-uppercase">{l s='Download' d='Shop.Theme.Actions'}</h3>*}
                      {foreach from=$product.attachments item=attachment}
                        <div class="col-xl-3 col-lg-3 col-sm-4 col-xs-6 col-sp-6 attachment">
                            <h4><a href="{url entity='attachment' params=['id_attachment' => $attachment.id_attachment]}">{$attachment.name}</a></h4>
                            {*<p>{$attachment.description}</p>
                            <a href="{url entity='attachment' params=['id_attachment' => $attachment.id_attachment]}">
                             {l s='Download' d='Shop.Theme.Actions'} ({$attachment.file_size_formatted})
                            </a>*}
                        </div>
                      {/foreach}
                  </section>
                </div>
              </div>
          </div>
      </div>
    {/if}
  {/block}
  {* Extra Product *}
  {foreach from=$product.extraContent item=extra key=extraKey}
    <div class="card" id="extra-{$extraKey}">
       <div class="card-header" role="tab" id="headingextra-{$extraKey}">
            <h5 class="h5">
              <a class="collapse" data-toggle="collapse" {*data-parent="#accordion"*} href="#collapseextra-{$extraKey}" aria-expanded="true" aria-controls="collapseextra-{$extraKey}">
                  {$extra.title}
              </a>
            </h5>
        </div>
        <div id="collapseextra-{$extraKey}" class="collapse in" role="tabpanel" aria-labelledby="headingextra-{$extraKey}">
            <div class="card-block">
              <div class="{$extra.attr.class}" id="extra-content-{$extraKey}" {foreach $extra.attr as $key => $val} {$key}="{$val}"{/foreach}>
               {$extra.content nofilter}
            </div>
            </div>
        </div>
    </div>
  {/foreach}

  {* BEGIN - Bloque Consultas *}
  <a id="alvarezquestionblock"></a>
  {widget name="alvarezpquestions" product=$product}
  {* END - Bloque Consultas *}
</div>
{/block}