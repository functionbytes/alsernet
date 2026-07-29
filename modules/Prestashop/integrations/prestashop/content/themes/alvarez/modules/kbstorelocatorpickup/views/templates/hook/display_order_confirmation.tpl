<div class="kb-pickup-confirmation-block box">
    {if isset($order_history)} 
        <p>
            {l s='Your Pickup Location:' mod='kbstorelocatorpickup'}
            <strong>
                {*{if is_array($store->name) && isset($store->name[$id_lang]) && !empty($store->name[$id_lang])}
                    {$store->name[$id_lang]|escape:'htmlall':'UTF-8'},
                {else}
                    {$store->name|escape:'htmlall':'UTF-8'},
                {/if}*}
                {if !empty($store->address1)}
                    {if is_array($store->address1) && isset($store->address1[$id_lang])}
                        {if !empty($store->address1[$id_lang])}
                            {$store->address1[$id_lang]|escape:'htmlall':'UTF-8'},
                        {/if}
                    {else}
                        {$store->address1|escape:'htmlall':'UTF-8'},
                    {/if}
                {/if}
                {if !empty($store->address2)}
                    {if is_array($store->address2) && isset($store->address2[$id_lang])}
                        {if !empty($store->address2[$id_lang])}
                            {$store->address2[$id_lang]|escape:'htmlall':'UTF-8'},
                        {/if}
                    {else}
                        {$store->address2|escape:'htmlall':'UTF-8'},
                    {/if}
                {/if}
                {if !empty($store->postcode)}{$store->postcode|escape:'htmlall':'UTF-8'},{/if}
                {if !empty($store->city)}{$store->city|escape:'htmlall':'UTF-8'},{/if}
                {if !empty($store->id_state)}{State::getNameById($store->id_state)},{/if}
            {if !empty($store->id_country)}{Country::getNameById($id_lang, $store->id_country)}{/if}
            </strong>
        </p>
        {*<p>
            {l s='Your Preferred Pickup Date:' mod='kbstorelocatorpickup'} <strong>{$pickup_time|escape:'htmlall':'UTF-8'}</strong>
        </p>*}

    {else}
        <p>- {l s='You have selected the following pickup location:' mod='kbstorelocatorpickup'} 
            <strong>
                {*{if !empty($store->name)}
                    {$store->name|escape:'htmlall':'UTF-8'},
                {else}
                    {$store->name|escape:'htmlall':'UTF-8'},
                {/if}*}
                {if !empty($store->address1)}
                   {* {if isset($store->address1[$id_lang])}*}
                      {*  {if !empty($store->address1[$id_lang])}*}
                           {* {$store->address1[$id_lang]|escape:'htmlall':'UTF-8'},*}
                      {*  {/if}*}
                    {*{else}*}
                        {$store->address1|escape:'htmlall':'UTF-8'},
                    {*{/if}*}
                {/if}
                
                {if !empty($store->address2)}
                   {* {if isset($store->address2[$id_lang])}
                        {if !empty($store->address2[$id_lang])}
                            {$store->address2[$id_lang]|escape:'htmlall':'UTF-8'},
                        {/if}
                    {else}*}
                        {$store->address2|escape:'htmlall':'UTF-8'},
                    {*{/if}*}
                {/if}
                {if !empty($store->city)}{$store->city|escape:'htmlall':'UTF-8'},{/if}
                {if !empty($store->postcode)}{$store->postcode|escape:'htmlall':'UTF-8'},{/if}
                {if !empty($store->id_state)}{State::getNameById($store->id_state)},{/if}
            {if !empty($store->id_country)}{Country::getNameById($id_lang, $store->id_country)}{/if}
        </strong>
        {*<br/>
        - {l s='You have selected the following pickup date: ' mod='kbstorelocatorpickup'} <strong>{$pickup_time|escape:'htmlall':'UTF-8'}</strong>*}
    </p>
{/if}
</div>