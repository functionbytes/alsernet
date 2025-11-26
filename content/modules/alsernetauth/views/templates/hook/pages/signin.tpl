<div class="account">
  {if $logged}
    <a class="logout" href="{$links}" rel="nofollow" >
      <i class="fa fa-solid fa-user"></i>
      <span class="block">{l s='Account' mod='alsernetauth'}</span>
    </a>
  {else}
    <a class="login" href="{$links}" rel="nofollow" >
      <i class="fa fa-solid fa-user"></i>
      <span class="block">{l s='Account' mod='alsernetauth'}</span>
    </a>
  {/if}
</div>
