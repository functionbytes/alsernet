
{if $categories}
   <div class="navs">
      <div class="title">
            {$name}
      </div>
      <div class="menu-content">
         {foreach from=$categories item=$children}
               <div class="nav-item">
                  <a   href="{$children.link_rewrite}"   title="{l s=$children.name}">
                     {l s=$children.name}
                  </a>
               </div>
         {/foreach}
      </div>
   </div>
{else}

        {assign var='explourl' value=explode('/',$link)}
        {assign var='explocate' value=explode('-',$explourl[count($explourl)-1])}
        {if count($explocate) == 1}
            {assign var='urlantes' value=str_replace($explocate[count($explocate)-1],'',$link)}
        {else}
            {assign var='urlantes' value=str_replace('-'|cat:$explocate[count($explocate)-1],'',$link)}
        {/if}

        {assign var='namecategory' value='-'|cat:str_replace(' ','_',strtolower({str_replace(',','',$name)}))}
        {assign var='nuevaurl' value=str_replace($namecategory,'',$link)}
        {assign var='prenombre' value=explode('/',$nuevaurl)}

        {assign var='nuevaurlmarca' value=str_replace($prenombre[count($prenombre)-1],'',$link)}

        {assign var='name' value=str_replace($prenombre[count($prenombre)-1],'',$link)}
        {assign var='url' value=str_replace($prenombre[count($prenombre)-1],'',$link)|regex_replace:'/\/$/' : ''}
        {assign var='urlidioma' value=explode('/',$name)}

        <div class="block-categories block block-highlighted">
                {if $parent == 2820 }
                {else if $parent == NULL}
                    <a class="btn btn-primary btn-rounded w-100 text-left" href="/"><i class="fa-solid fa-left"></i> {l s="Back HOME" mod='alsernetmenu'}</a>
                {else if $parent == 2821}
                    <a class="btn btn-primary btn-rounded w-100 v" href="{$nuevaurlmarca}"><i class="fa-solid fa-left"></i> {l s="Back to Marcas" mod='alsernetmenu'}</a>
                {else if $parent == 3 || $parent == 4 || $parent == 5 || $parent == 6 || $parent == 7 || $parent == 8 || $parent == 9 || $parent == 10 || $parent == 11}
                    <a class="btn btn-primary btn-rounded w-100 text-left" href="{$url}"><i class="fa-solid fa-left"></i> {l s="Back to" mod='alsernetmenu'} {$urlidioma[count($urlidioma)-2]}</a>
                {else}
                    <a class="btn btn-primary btn-rounded w-100 text-left" href="{$urlantes}"><i class="fa-solid fa-left"></i> {l s="Back to" mod='alsernetmenu'} {str_replace('_',' ',ucfirst($explocate[count($explocate)-2]))}</a>
                {/if}
        </div>
{/if}
