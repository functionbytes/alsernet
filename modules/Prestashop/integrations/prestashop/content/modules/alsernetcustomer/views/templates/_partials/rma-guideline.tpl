
<div class="col-xxl-12 col-lg-12 col-md-12 col-sm-12">
  <div class=' refund-guideline'>
    <p class='refund-text'>
      <i class="material-icons" style="color:#22C76F;">info</i>
      {$guidelineMessage nofilter}
      {if isset($guidelineCmsPageLink) && $guidelineCmsPageLink}
        {l s='For more info' mod='wkrma'}
        <a href="{$guidelineCmsPageLink}" class="refund_terms_link" target='_blank'>
          {l s='Click here' mod='wkrma'}
        </a>
      {/if}
    </p>
  </div>
</div>