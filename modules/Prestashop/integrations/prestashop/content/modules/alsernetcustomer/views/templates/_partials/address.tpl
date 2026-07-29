{foreach $addresses as $address}
    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 address-box-item" data-id-address="{$address.id_address}">
        <div class="address-box">
            <div class="address-header">
                <div class="name">
                    {$address.firstname|regex_replace:'/ .*/':''|capitalize} {$address.lastname|substr:0:1|upper}.
                </div>
                {if isset($address.default) && ($address.default == 1 || $address.default == '1')}
                    <div class="alias default">
                        {$translations.default|escape:'html':'UTF-8'}
                    </div>
                {/if}
            </div>
            <div class="address-body">
                <div class="table-responsive address-table">
                    <table class="table">
                        <tbody>
                        {if $address.address1|default:''}
                            <tr>
                                <td>
                                    {$translations.address|escape:'html':'UTF-8'}:
                                    <p>
                                        {$address.address1|escape:'html':'UTF-8'}
                                        {if $address.address2|default:''}
                                            {$address.address2|escape:'html':'UTF-8'}
                                        {/if}
                                    </p>
                                </td>
                            </tr>
                        {/if}

                        {if $address.postcode|default:''}
                            <tr>
                                <td>
                                    {$translations.postalcode|escape:'html':'UTF-8'}:
                                    <p>{$address.postcode|escape:'html':'UTF-8'}</p>
                                </td>
                            </tr>
                        {/if}

                        {if $address.city|default:''}
                            <tr>
                                <td>
                                    {$translations.city|escape:'html':'UTF-8'}:
                                    <p>{$address.city|escape:'html':'UTF-8'}</p>
                                </td>
                            </tr>
                        {/if}

                        {assign var=countryLabel value=$address.country_name|default:$address.country}
                        {if $countryLabel|default:''}
                            <tr>
                                <td>
                                    {$translations.country|escape:'html':'UTF-8'}:
                                    <p>{$countryLabel|escape:'html':'UTF-8'}</p>
                                </td>
                            </tr>
                        {/if}

                        {if $address.vat_number|default:''}
                            <tr>
                                <td>
                                    {$translations.dnivat|escape:'html':'UTF-8'}:
                                    {$address.vat_number|escape:'html':'UTF-8'}
                                </td>
                            </tr>
                        {/if}

                        {assign var=phoneToShow value=$address.phone|default:$address.phone_mobile}
                        {if $phoneToShow|default:''}
                            <tr>
                                <td>
                                    {$translations.phone|escape:'html':'UTF-8'}:
                                    {$phoneToShow|escape:'html':'UTF-8'}
                                </td>
                            </tr>
                        {/if}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="button-group">
                <button class="btn btn-sm add-button w-100 edit-add-addresses"   data-id-address="{$address.id_address}">
                    <i class="fa-solid fa-pencil"></i>
                    {$translations.update|escape:'html':'UTF-8'}
                </button>
                <button class="btn btn-sm add-button w-100 delete-add-addresses"    data-id-address="{$address.id_address}">
                    <i class="fa-solid fa-trash"></i>
                    {$translations.delete|escape:'html':'UTF-8'}
                </button>
            </div>
        </div>
    </div>
{/foreach}