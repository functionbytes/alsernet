{{--
  Partial: condition row
  Usage: cloned by JS. Do not render directly.
  Attributes are set by JS after cloning.
--}}
<div class="condition-row d-flex gap-2 align-items-start mb-2" data-index="">
    <select class="form-select form-select-sm condition-type" style="width:130px">
        <option value="property">Propiedad</option>
        <option value="event">Evento</option>
        <option value="rfm">RFM</option>
    </select>
    <select class="form-select form-select-sm condition-field" style="width:160px">
        <optgroup label="Propiedad" data-for="property">
            <option value="email">Email</option>
            <option value="country">País</option>
            <option value="status">Estado</option>
            <option value="total_spent">Total gastado</option>
            <option value="tags">Etiquetas</option>
        </optgroup>
        <optgroup label="Evento" data-for="event">
            <option value="page_view">Page view</option>
            <option value="product_view">Product view</option>
            <option value="add_to_cart">Add to cart</option>
            <option value="purchase">Compra</option>
        </optgroup>
        <optgroup label="RFM" data-for="rfm">
            <option value="rfm_score">Segmento RFM</option>
            <option value="rfm_recency">Recencia (días)</option>
            <option value="rfm_frequency">Frecuencia</option>
            <option value="rfm_monetary">Monetario (€)</option>
        </optgroup>
    </select>
    <select class="form-select form-select-sm condition-operator" style="width:130px">
        <option value="equals">Es igual a</option>
        <option value="not_equals">No es igual a</option>
        <option value="contains">Contiene</option>
        <option value="not_contains">No contiene</option>
        <option value="greater_than">Mayor que</option>
        <option value="less_than">Menor que</option>
        <option value="has_done">Ha hecho</option>
        <option value="has_not_done">No ha hecho</option>
    </select>
    <input type="text" class="form-control form-control-sm condition-value flex-grow-1" placeholder="Valor">
    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-condition" title="Eliminar condición">
        <i class="fas fa-times"></i>
    </button>
</div>
