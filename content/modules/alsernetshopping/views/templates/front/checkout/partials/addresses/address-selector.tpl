{foreach from=$addresses item=address}
  <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-6 address-item">
    <div class="address-box-item"
         data-id-address="{$address.id|escape:'html':'UTF-8'}"
         data-type="{$type|default:'delivery'|escape:'html':'UTF-8'}">

      <div class="address-radio">
        {assign var=radioId value="`$name`_`$address.id`"}
        <input
                class="form-check-input"
                id="{$radioId|escape:'html':'UTF-8'}"
                type="radio"
                name="{$name|escape:'html':'UTF-8'}"
                value="{$address.id|escape:'html':'UTF-8'}"
                {if (int)$address.id == (int)$selected}checked="checked"{/if}
        />
      </div>

      <div class="address-box">
        <div class="address-header">
          <label class="name" for="{$radioId|escape:'html':'UTF-8'}">
            {$address.firstname|default:''|regex_replace:'/ .*/':''|capitalize|escape:'html':'UTF-8'}
            {$address.lastname|default:''|substr:0:1|upper|escape:'html':'UTF-8'}.
          </label>

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
          <a href="#"
             class="btn btn-sm add-button w-100 edit-address-checkout"
             data-id-address="{$address.id|escape:'html':'UTF-8'}"
             role="button">
            <i class="fa-solid fa-pencil" aria-hidden="true"></i>
            {$translations.update|escape:'html':'UTF-8'}
          </a>

          <a href="#"
             class="btn btn-sm add-button w-100 remove-address-checkout"
             data-id-address="{$address.id|escape:'html':'UTF-8'}"
             role="button">
            <i class="fa-solid fa-trash" aria-hidden="true"></i>
            {$translations.delete|escape:'html':'UTF-8'}
          </a>
        </div>
      </div>
    </div>
  </div>
{/foreach}
