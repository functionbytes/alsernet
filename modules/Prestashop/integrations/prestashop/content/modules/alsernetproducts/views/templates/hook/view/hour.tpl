
{if $product.hour}
<div class="hour-container">
    <div class="hour-content">
        <div class="icon-content">
            <img src="/themes/alvarez/assets/img/theme/product/hour.svg"></>
        </div>
        <div class="hour-text">
            <h4 class="icon-box-title">{l s='We send it to you in 48 hours' mod='alsernetproducts'}</h4>
            <a id="hourModal">({l s='See conditions' mod='alsernetproducts'})</a>
        </div>
    </div>
</div>

<div class="modal fade modal-hour" id="modal-hour" tabindex="-1" aria-labelledby="hourModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="HourModalLabel"></h5>
                <button type="button" class="close" id="hourClose" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="deadlines">
                    <div class="finanhead-details">
                        {assign var="finantext" value={l s='48 hours conditions' mod='alsernetproducts'}}
                        <p>{$finantext|replace:" || ":"<br><b>" nofilter}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{/if}
