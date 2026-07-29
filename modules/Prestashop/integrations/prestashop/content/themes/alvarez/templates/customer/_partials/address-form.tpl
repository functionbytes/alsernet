
{block name="address_form"}
  <div class="js-address-form">

    {include file='_partials/form-errors.tpl' errors=$errors['']}

    {block name="address_form_url"}
    <div method="POST"  action="{url entity='address' params=['id_address' => $id_address]}"  data-id-address="{$id_address}" data-refresh-url="{url entity='address' params=['ajax' => 1, 'action' => 'addressForm']}">
      {/block}

        <section class="form-fields">
      {block name="address_form_fields"}
          {block name='form_fields'}
            {foreach from=$formFields item="field"}
              {if ($field.name != "company") }
                {block name='form_field'}
                  {form_field field=$field}
                {/block}
              {/if}
            {/foreach}
          {/block}

      {/block}

        {block name="address_form_footer"}
            <div class="form-group ">
                <div class="row"><div class="col-md-12  ">
            <input type="hidden" name="submitAddress" value="1">

                <button class="btn btn-primary continue form-control-submit float-xs-right" type="submit">
                    {l s='Continue' d='Shop.Customer.Address'}
                </button>
                    </div>
    </div>
      </div>
        {/block}


        </section>
    </form>
  </div>
{/block}
