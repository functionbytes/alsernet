
<div class="ratings-container">
    <div class="ratings-full">
        <span class="ratings" style="width: {$comment.calification}%;"></span>
        <span class="tooltiptext tooltip-top">{$comment.average}</span>
    </div>
    <a class="rating-reviews scroll-to view-reviews-{if $logged}notauth{else}notauth{/if} ">({$comment.number} {l s='Reviews' mod='alsernetproducts'})</a>
</div>
