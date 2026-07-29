{*
* MondialRelay Standard interface template (Carrier 100)
* Standard MondialRelay point selection service
*
* @var array $carrier_config Carrier specific configuration
* @var array $mondialrelay_config MondialRelay module configuration
* @var array $widget_config Widget configuration
* @var array $selected_relay Selected relay information
*}

<div class="mondialrelay-carrier-interface" data-carrier="100" data-service-type="standard">
    
    {* Header with MondialRelay branding *}
    <div class="mondialrelay-header mb-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1">
                    <i class="fa-solid fa-map-location-dot me-2 text-primary"></i>
                    {l s='MondialRelay' mod='alsernetshopping'}
                </h5>
                <p class="text-muted mb-0">
                    {l s='Select a convenient pickup point' mod='alsernetshopping'}
                </p>
            </div>
            <div class="col-md-4 text-end">
                <img src="https://www.mondialrelay.fr/media/126275/mr_logo_2017.png" 
                     alt="MondialRelay" class="mondialrelay-logo" style="max-height: 40px;">
            </div>
        </div>
    </div>

    {* Service benefits *}
    <div class="service-benefits mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <div class="benefit-item text-center p-2 bg-light rounded">
                    <i class="fa-solid fa-clock text-primary mb-1"></i>
                    <small>{l s='Extended Hours' mod='alsernetshopping'}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="benefit-item text-center p-2 bg-light rounded">
                    <i class="fa-solid fa-euro-sign text-primary mb-1"></i>
                    <small>{l s='Economical' mod='alsernetshopping'}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="benefit-item text-center p-2 bg-light rounded">
                    <i class="fa-solid fa-map-marker-alt text-primary mb-1"></i>
                    <small>{l s='Many Locations' mod='alsernetshopping'}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="benefit-item text-center p-2 bg-light rounded">
                    <i class="fa-solid fa-shield-alt text-primary mb-1"></i>
                    <small>{l s='Secure Delivery' mod='alsernetshopping'}</small>
                </div>
            </div>
        </div>
    </div>

    {* Widget container for MondialRelay *}
    <div id="mondialrelay-widget-container-100" class="widget-container mb-3">
        {if !$mondialrelay_config.webservice_key}
            <div class="alert alert-warning">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                {l s='MondialRelay service is not configured yet' mod='alsernetshopping'}
            </div>
        {else}
            {* El widget se inicializará vía JavaScript *}
            <div class="widget-loading text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">{l s='Loading...' mod='alsernetshopping'}</span>
                </div>
                <p class="mt-2">{l s='Loading MondialRelay pickup points...' mod='alsernetshopping'}</p>
            </div>
        {/if}
    </div>

    {* Alternative search form if widget fails *}
    <div id="mondialrelay-fallback-search" class="fallback-search-form bg-light p-3 rounded mb-3 d-none">
        <h6 class="mb-3">
            <i class="fa-solid fa-search me-2"></i>
            {l s='Search pickup points' mod='alsernetshopping'}
        </h6>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label for="mondialrelay-cp-100" class="form-label">
                    {l s='Postal Code' mod='alsernetshopping'}
                </label>
                <input type="text" 
                       class="form-control" 
                       id="mondialrelay-cp-100" 
                       placeholder="{l s='Enter postal code' mod='alsernetshopping'}"
                       maxlength="5">
            </div>
            <div class="col-md-6">
                <label for="mondialrelay-city-100" class="form-label">
                    {l s='City' mod='alsernetshopping'}
                </label>
                <input type="text" 
                       class="form-control" 
                       id="mondialrelay-city-100" 
                       placeholder="{l s='Enter city name' mod='alsernetshopping'}">
            </div>
            <div class="col-12">
                <button type="button" 
                        class="btn btn-primary w-100 search-mondialrelay-points" 
                        data-carrier="100">
                    <i class="fa-solid fa-search me-2"></i>
                    {l s='Search pickup points' mod='alsernetshopping'}
                </button>
            </div>
        </div>

        {* Results container *}
        <div id="mondialrelay-results-100" class="results-container mt-3 d-none">
            <h6 class="mb-3">{l s='Available pickup points' mod='alsernetshopping'}:</h6>
            <div id="mondialrelay-points-list-100" class="points-list">
                {* Results populated by JavaScript *}
            </div>
        </div>

        {* Error container *}
        <div id="mondialrelay-error-100" class="alert alert-danger d-none" role="alert">
            <i class="fa-solid fa-exclamation-triangle me-2"></i>
            <span class="error-message"></span>
        </div>
    </div>

    {* Selected point display *}
    {if isset($selected_relay) && $selected_relay}
        <div id="mondialrelay-selected-point-100" class="selected-point-container">
            <div class="alert alert-success">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">
                            <i class="fa-solid fa-map-marker-alt me-2"></i>
                            {l s='Selected pickup point' mod='alsernetshopping'}
                        </h6>
                        <div class="selected-point-details">
                            <strong>{$selected_relay.relay_name|escape:'htmlall':'UTF-8'}</strong><br>
                            {$selected_relay.relay_address|escape:'htmlall':'UTF-8'}<br>
                            {$selected_relay.relay_postcode|escape:'htmlall':'UTF-8'}, {$selected_relay.relay_city|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" 
                                class="btn btn-outline-primary btn-sm change-mondialrelay-point" 
                                data-carrier="100">
                            <i class="fa-solid fa-edit me-1"></i>
                            {l s='Change' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    {else}
        <div id="mondialrelay-selected-point-100" class="selected-point-container d-none">
            <div class="alert alert-success">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">
                            <i class="fa-solid fa-map-marker-alt me-2"></i>
                            {l s='Selected pickup point' mod='alsernetshopping'}
                        </h6>
                        <div class="selected-point-details">
                            {* Details populated by JavaScript *}
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" 
                                class="btn btn-outline-primary btn-sm change-mondialrelay-point" 
                                data-carrier="100">
                            <i class="fa-solid fa-edit me-1"></i>
                            {l s='Change' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    {* Hidden inputs for form data *}
    <input type="hidden" id="selected-mondialrelay-relay-100" name="selected_relay_100" value="{if isset($selected_relay)}{$selected_relay.relay_num|escape:'htmlall':'UTF-8'}{/if}">

</div>

{* Inline configuration for JavaScript *}
<script type="text/javascript">
    window.mondialrelayConfig = window.mondialrelayConfig || {ldelim}{rdelim};
    window.mondialrelayConfig[100] = {ldelim}
        carrierId: 100,
        serviceType: 'standard',
        widgetConfig: {if isset($widget_config)}{$widget_config|json_encode nofilter}{else}{ldelim}{rdelim}{/if},
        mondialrelayConfig: {if isset($mondialrelay_config)}{$mondialrelay_config|json_encode nofilter}{else}{ldelim}{rdelim}{/if},
        selectedRelay: {if isset($selected_relay)}{$selected_relay|json_encode nofilter}{else}null{/if}
    {rdelim};
</script>