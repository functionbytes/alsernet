
{block name='footer'}
	<div class="footer-top">
		{if isset($fullwidth_hook.displayFooterBefore) AND $fullwidth_hook.displayFooterBefore == 0}
	 		<div class="container">
	 	{/if}
	 		<div class="inner">{hook h='displayFooterBefore'}</div>
	 	{if isset($fullwidth_hook.displayFooterBefore) AND $fullwidth_hook.displayFooterBefore == 0}
	 		</div>
	 	{/if}
	</div>
	<div class="footer-center">
	 	{if isset($fullwidth_hook.displayFooter) AND $fullwidth_hook.displayFooter == 0}
	 		<div class="container">
	 	{/if}
	 		<div class="inner">{hook h='displayFooter'}</div>
	 	{if isset($fullwidth_hook.displayFooter) AND $fullwidth_hook.displayFooter == 0}
	 		</div>
	 	{/if}
	</div>
	<div class="footer-bottom">
	 	{if isset($fullwidth_hook.displayFooterAfter) AND $fullwidth_hook.displayFooterAfter == 0}
	 		<div class="container">
	 	{/if}
	 		<div class="inner">{hook h='displayFooterAfter'}</div>
	 	{if isset($fullwidth_hook.displayFooterAfter) AND $fullwidth_hook.displayFooterAfter == 0}
	 		</div>
		{/if}
	</div>
{/block}
