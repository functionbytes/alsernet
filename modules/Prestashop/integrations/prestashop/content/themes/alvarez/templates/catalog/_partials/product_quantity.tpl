<script>
    function fillEmptyValue(input) {
    // Refuerzo en caso de que quede vacío al hacer blur
    if (input.value.trim() === "" || Number(input.value) <= 0 || isNaN(input.value)) {
        input.value = 1;
    }
}

function updateQuantityAction(sum, event) {
    if (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }

    const input = document.getElementById('product_quantity');

    if (sum) {
        input.value = Number(input.value) + 1;
    } else {
        if (Number(input.value) > 1) {
            input.value = Number(input.value) - 1;
        }
    }
}
</script>
<div class="product-variants">
    <div class="clearfix product-variants-item">
        <span class="control-label">{l s='Quantity' d='Shop.Theme.Catalog'}</span>
        <div class="product-quantity-container">
            <button type="button" class="button btn-cantidad" onclick="updateQuantityAction(false)">-</button>
            <input
                    type="number"
                    class="form-control product-quantity"
                    id="product_quantity"
                    name="product_quantity"
                    value="1"
                    min="1"
                    step="1"
                    onblur="fillEmptyValue(this)"/>
            <button type="button" class="button btn-cantidad" onclick="updateQuantityAction(true)">+</button>
        </div>

    </div>
</div>