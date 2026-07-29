<div class="menu-images">
      {foreach from=$images item=item}

            {if $item.option == 'background'} 
                  <div class="content-images">
                        <a class="background-images"  style="background-image: url({$item.img});" {if $item.url != ''} href="{$item.url}" {/if} title="{l s=$item.title}">
                                    <div class="content-background" >
                                          <div class="overlay" style="{$item.style}"></div>
                                          <p> {$item.title}</p>
                                    </div>
                        </a>
                  </div>
            {else}
                  <div class="content-images">
                        <a class="item-image" {if $item.url != ''} href="{$item.url}" {/if} title="{l s=$item.title}">
                              <img class="img-fluid" src="{$item.img}">
                        </a>
                  </div>
            {/if}

          
      {/foreach}
</div>



