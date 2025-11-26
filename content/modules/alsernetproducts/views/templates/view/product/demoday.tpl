<div class="page-product-demoday page-product-default">
        <div class="row">
            <div class="col-md-12 mb-4 ">
                    {$product.description_short nofilter}
            </div>
            <div class="col-md-12">
                <div class="details">
                    {if count($groups) > 0}
                        <div class="product-actions demoday" >
                            {widget name="alsernetforms" forms="demodayorder"}
                        </div>                    
                    {else}
                        <div class="alerts">
                            {widget name="alsernetforms" forms="demoday"}
                        </div>
                    {/if}
                </div>
            </div>
</div>

