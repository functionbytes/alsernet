{*
* InPost Locker interface template (Carrier 108)
* Integrates with MondialRelay module for InPost locker selection
*
* @var array $carrier Carrier information
* @var array $mondialrelay_config MondialRelay configuration
* @var array $inpost_config InPost specific configuration
* @var array $widget_config Widget configuration
* @var array $selected_relay Selected relay information
*}

<div class="inpost-carrier-interface" data-carrier="108" data-service-type="locker">
    
    {* Header with InPost Locker branding *}
    <div class="inpost-header mb-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1">
                    <i class="fa-solid fa-archive me-2 text-success"></i>
                    {l s='InPost Locker' mod='alsernetshopping'}
                </h5>
                <p class="text-muted mb-0">
                    {l s='24/7 automated parcel lockers' mod='alsernetshopping'}
                </p>
            </div>
            <div class="col-md-4 text-end">
                <img src="https://inpost.es/wp-content/uploads/2021/11/logo-inpost-1.png" 
                     alt="InPost" class="inpost-logo" style="max-height: 40px;">
            </div>
        </div>
    </div>

    {* Locker benefits info *}
    <div class="locker-benefits mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="benefit-item text-center p-2 bg-light rounded">
                    <i class="fa-solid fa-clock text-success mb-1"></i>
                    <small>{l s='24/7 Available' mod='alsernetshopping'}</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-item text-center p-2 bg-light rounded">
                    <i class="fa-solid fa-shield-alt text-success mb-1"></i>
                    <small>{l s='Secure Storage' mod='alsernetshopping'}</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-item text-center p-2 bg-light rounded">
                    <i class="fa-solid fa-mobile-alt text-success mb-1"></i>
                    <small>{l s='Mobile Access' mod='alsernetshopping'}</small>
                </div>
            </div>
        </div>
    </div>

    {* Widget container for InPost Locker *}
    <div id="inpost-widget-container-108" class="widget-container mb-3">
        {if !$mondialrelay_config.webservice_key}
            <div class="alert alert-warning">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                {l s='InPost service is not configured yet' mod='alsernetshopping'}
            </div>
        {else}
            {* El widget se inicializará vía JavaScript *}
            <div class="widget-loading text-center py-4">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">{l s='Loading...' mod='alsernetshopping'}</span>
                </div>
                <p class="mt-2">{l s='Loading InPost lockers...' mod='alsernetshopping'}</p>
            </div>
        {/if}
    </div>

    {* Alternative search form if widget fails *}
    <div id="inpost-fallback-search" class="fallback-search-form bg-light p-3 rounded mb-3 d-none">
        <h6 class="mb-3">
            <i class="fa-solid fa-search me-2"></i>
            {l s='Search lockers' mod='alsernetshopping'}
        </h6>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label for="inpost-cp-108" class="form-label">
                    {l s='Postal Code' mod='alsernetshopping'}
                </label>
                <input type="text" 
                       class="form-control" 
                       id="inpost-cp-108" 
                       placeholder="{l s='Enter postal code' mod='alsernetshopping'}"
                       maxlength="5">
            </div>
            <div class="col-md-6">
                <label for="inpost-city-108" class="form-label">
                    {l s='City' mod='alsernetshopping'}
                </label>
                <input type="text" 
                       class="form-control" 
                       id="inpost-city-108" 
                       placeholder="{l s='Enter city name' mod='alsernetshopping'}">
            </div>
            <div class="col-12">
                <button type="button" 
                        class="btn btn-success w-100 search-inpost-points" 
                        data-carrier="108">
                    <i class="fa-solid fa-search me-2"></i>
                    {l s='Search lockers' mod='alsernetshopping'}
                </button>
            </div>
        </div>

        {* Results container *}
        <div id="inpost-results-108" class="results-container mt-3 d-none">
            <h6 class="mb-3">{l s='Available lockers' mod='alsernetshopping'}:</h6>
            <div id="inpost-points-list-108" class="points-list">
                {* Results populated by JavaScript *}
            </div>
        </div>

        {* Error container *}
        <div id="inpost-error-108" class="alert alert-danger d-none" role="alert">
            <i class="fa-solid fa-exclamation-triangle me-2"></i>
            <span class="error-message"></span>
        </div>
    </div>

    {* Selected locker display *}
    {if isset($selected_relay) && $selected_relay}
        <div id="inpost-selected-point-108" class="selected-point-container">
            <div class="alert alert-success">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">
                            <i class="fa-solid fa-archive me-2"></i>
                            {l s='Selected locker' mod='alsernetshopping'}
                        </h6>
                        <div class="selected-point-details">
                            <strong>{$selected_relay.relay_name|escape:'htmlall':'UTF-8'}</strong><br>
                            {$selected_relay.relay_address|escape:'htmlall':'UTF-8'}<br>
                            {$selected_relay.relay_postcode|escape:'htmlall':'UTF-8'}, {$selected_relay.relay_city|escape:'htmlall':'UTF-8'}
                            <br>
                            <small class="text-muted">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                {l s='You will receive SMS/email with pickup code' mod='alsernetshopping'}
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" 
                                class="btn btn-outline-success btn-sm change-inpost-point" 
                                data-carrier="108">
                            <i class="fa-solid fa-edit me-1"></i>
                            {l s='Change' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    {else}
        <div id="inpost-selected-point-108" class="selected-point-container d-none">
            <div class="alert alert-success">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">
                            <i class="fa-solid fa-archive me-2"></i>
                            {l s='Selected locker' mod='alsernetshopping'}
                        </h6>
                        <div class="selected-point-details">
                            {* Details populated by JavaScript *}
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" 
                                class="btn btn-outline-success btn-sm change-inpost-point" 
                                data-carrier="108">
                            <i class="fa-solid fa-edit me-1"></i>
                            {l s='Change' mod='alsernetshopping'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    {* Hidden inputs for form data *}
    <input type="hidden" id="selected-inpost-relay-108" name="selected_relay_108" value="{if isset($selected_relay)}{$selected_relay.relay_num|escape:'htmlall':'UTF-8'}{/if}">

</div>

{* Inline configuration for JavaScript *}
<script type="text/javascript">
    window.inpostConfig = window.inpostConfig || {ldelim}{rdelim};
    window.inpostConfig[108] = {ldelim}
        carrierId: 108,
        serviceType: 'locker',
        widgetConfig: {if isset($widget_config)}{$widget_config|json_encode nofilter}{else}{ldelim}{rdelim}{/if},
        mondialrelayConfig: {if isset($mondialrelay_config)}{$mondialrelay_config|json_encode nofilter}{else}{ldelim}{rdelim}{/if},
        selectedRelay: {if isset($selected_relay)}{$selected_relay|json_encode nofilter}{else}null{/if}
    {rdelim};
</script>