
    {block name='hook_footer_before'}
      {hook h='displayFooterBefore'}
    {/block}
<div class="footer-container">
  <div class="container">
    <div class="footer-top">
      {block name='hook_footer'}
        {hook h='displayFooter'}
      {/block}
    </div>
    <div class="footer-bottom">
      {block name='hook_footer_after'}
        {hook h='displayFooterAfter'}
      {/block}
    </div>
  </div>
</div>
