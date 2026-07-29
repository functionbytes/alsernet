


<div class="col-xxl-12 col-lg-12 col-md-12 col-sm-12">
    <ol class="progtrckr">
        {foreach from=$rma_states item=state}
            <li class="{if $current_rma_state >= $state.id_rma_state}progtrckr-done{else}progtrckr-todo{/if}">
                <h5>{$state.name}</h5>
                {if $state.history}
                <h6 class='state-history'>
                    {assign var="show_date" value=false}

                        {foreach from=$state.history item=history}
                            {if (isset($is_customer_view) && ($is_customer_view == 0)) || ($history.shown_to_customer == 1)}
                            {assign var="show_date" value=true}
                                    {$history['rmastatus_name']}
                            {/if}
                        {/foreach}
                </h6>
                {/if}
            </li>
        {/foreach}
    </ol>
</div>
