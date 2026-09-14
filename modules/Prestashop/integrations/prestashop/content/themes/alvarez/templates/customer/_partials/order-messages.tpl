    <hr>
    <div class="row">
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="detail-messages">
                <div class="form-message">
                      <form action="{$urls.pages.order_detail}" method="post">
                          <input type="hidden" name="id_order" value="{$order.details.id}">
                            <div class=" w-100 border position-relative overflow-hidden">
                                <h4 class="card-title">{l s='Add a message' d='Shop.Customer.Orders'}</h4>
                                <p class="card-subtitle mb-4">{l s='If you would like to add a comment about your order, please write it in the field below.' d='Shop.Customer.Orders'}</p>
                                  <div class="mb-3">
                                    <label for="exampleInputPassword1" class="form-label">{l s='Product' d='Shop.Customer.Orders'}</label>
                                    <select name="id_product" class="form-control select2">
                                      <option value="0">{l s='-- please choose --' d='Shop.Customer.Orders'}</option>
                                      {foreach from=$order.products item=product}
                                        <option value="{$product.id_product}">{$product.name}</option>
                                      {/foreach}
                                    </select>
                                  </div>
                                  <div class="mb-3">
                                    <label for="msgText" class="form-label">{l s='Product' d='Shop.Customer.Orders'}</label>
                                    <textarea rows="3" name="msgText" class="form-control"></textarea>
                                  </div>
                                  <button type="submit" name="submitMessage" class="btn btn-primary form-control-submit">
                                    {l s='Send' d='Shop.Customer.Orders'}
                                  </button>
                            </div>

                      </form>
                </div>
                {if $order.messages}
                    <div class="mt-40">
                         <ul class="comment-wrap list-unstyled">
                            {foreach from=$order.messages item=message}
                                <li class="comment-item">
                                   <div class="ms-2">
                                        <div class="d-flex justify-content-between">
                                          <h6 class="mb-1 comment-customer">{$message.name}</h6>
                                          <small class="ms-2 comment-date">{$message.message_date}</small>
                                        </div>
                                        <p class="small mb-0 comment-message">{$message.message nofilter}</p>
                                   </div>
                                </li>
                            {/foreach}
                          </ul>
                    </div>
                {/if}
            </div>
    </div>
</div>