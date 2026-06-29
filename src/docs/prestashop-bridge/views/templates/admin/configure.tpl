{*
 * Remarketing Bridge - Configuración
 *}

<div class="panel">
    <h3><i class="icon icon-cogs"></i> {l s='Configuración del webhook' mod='remarketingbridge'}</h3>
    <p>{l s='Introduce los datos de conexión con tu panel de remarketing. Los encontrarás en Ajustes → Tiendas → tu tienda PrestaShop.' mod='remarketingbridge'}</p>

    <form method="post" action="{$current}&configure={$module_name}&token={$token}">
        <div class="form-group">
            <label for="REMARKETING_WEBHOOK_URL">{l s='URL base del webhook' mod='remarketingbridge'}</label>
            <input type="url" class="form-control" id="REMARKETING_WEBHOOK_URL" name="REMARKETING_WEBHOOK_URL"
                   value="{$REMARKETING_WEBHOOK_URL|escape:'htmlall':'UTF-8'}"
                   placeholder="https://tu-dominio.com/r/webhooks/prestashop">
            <p class="help-block">{l s='Ejemplo: https://system.test/r/webhooks/prestashop' mod='remarketingbridge'}</p>
        </div>

        <div class="form-group">
            <label for="REMARKETING_STORE_TOKEN">{l s='Store Token' mod='remarketingbridge'}</label>
            <input type="text" class="form-control" id="REMARKETING_STORE_TOKEN" name="REMARKETING_STORE_TOKEN"
                   value="{$REMARKETING_STORE_TOKEN|escape:'htmlall':'UTF-8'}"
                   placeholder="abc123...">
            <p class="help-block">{l s='Token único de la tienda en tu panel de remarketing.' mod='remarketingbridge'}</p>
        </div>

        <div class="form-group">
            <label for="REMARKETING_API_SECRET">{l s='API Secret (para firma HMAC)' mod='remarketingbridge'}</label>
            <input type="text" class="form-control" id="REMARKETING_API_SECRET" name="REMARKETING_API_SECRET"
                   value="{$REMARKETING_API_SECRET|escape:'htmlall':'UTF-8'}"
                   placeholder="sk_...">
            <p class="help-block">{l s='Secreto compartido para firmar los webhooks (HMAC-SHA256).' mod='remarketingbridge'}</p>
        </div>

        <button type="submit" name="submitRemarketingBridgeModule" class="btn btn-default pull-right">
            <i class="icon-save"></i> {l s='Guardar' mod='remarketingbridge'}
        </button>
    </form>
</div>

<div class="panel">
    <h3><i class="icon icon-info-circle"></i> {l s='Eventos enviados' mod='remarketingbridge'}</h3>
    <ul>
        <li><strong>order.validated</strong> — {l s='Nueva orden confirmada (con items, cliente, totales)' mod='remarketingbridge'}</li>
        <li><strong>cart.updated</strong> — {l s='Carrito actualizado (máx 1 por minuto para evitar spam)' mod='remarketingbridge'}</li>
        <li><strong>order.updated</strong> — {l s='Cambio de estado de una orden' mod='remarketingbridge'}</li>
        <li><strong>customer.created</strong> — {l s='Nuevo cliente registrado' mod='remarketingbridge'}</li>
        <li><strong>customer.updated</strong> — {l s='Cliente actualizado' mod='remarketingbridge'}</li>
    </ul>
</div>
