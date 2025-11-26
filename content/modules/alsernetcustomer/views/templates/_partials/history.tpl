<hr>
    <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">

        <div class="detail-messages">

            <div class="form-message">
                <form action="{$link->getModuleLink('wkrma', 'rmarequestdetail', ['id_rma_request' => $rma_request_detail['id'], 'id_customer' => $rma_request_detail['id_customer']])}"  method="post" enctype="multipart/form-data"
                      accept-charset="UTF-8,ISO-8859-1,UTF-16">
                    <h5 class="mb-0">{l s='Tracking title' d='Shop.Theme.Customer'}</h5>
                    <p>{l s='Tracking description' d='Shop.Theme.Customer'}</p>


                    <div class=" w-100 border position-relative overflow-hidden">
                        <div class="mb-3">
                            <label for="msgText" class="form-label">{l s='Message' mod='wkrma'}</label>
                            <textarea rows="3" id="txt_msg" class="form-control" name="message" class="form-control">{Tools::getValue('message')|escape:'html':'UTF-8'}</textarea>
                            <p id="nbchars"></p>
                        </div>
                        <div class="mb-3">
                            <div class="custom-upload-container">
                                <input type="file" name="fileUpload" id="fileUpload" class="file-input" accept=".png, .jpeg, .gif, .jpg">
                                <label for="fileUpload" class="upload-box">
                                    <div class="upload-icon"><i class="fa-solid fa-upload"></i></div>
                                    <div class="upload-text">{l s='Add files' mod='wkrma'}</div>
                                    <div class="upload-subtext">{l s='Image size must be less than 2MB' mod='wkrma'}</div>
                                </label>
                                <div class="file-name">{l s='No file selected' mod='wkrma'}</div>
                            </div>
                        </div>

                        <button id="submitMessage" name="submitMessage" class="btn btn-primary form-control-submit">
                            {l s='Send' mod='wkrma'}
                        </button>
                    </div>

                </form>
            </div>

            {if $mergeHistory}
                <div class="mt-40">
                    <ul class="comment-wrap list-unstyled">
                        {foreach from=$mergeHistory item=message}
                            {if !isset($message['shown_to_customer']) || (isset($message['shown_to_customer']) && ($message['shown_to_customer'] == 1))}
                                <li class="comment-item">
                                <div class="ms-2">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1 comment-customer"> </h6>

                                            <small class="ms-2 comment-date"></small>
                                    </div>
                                    {if isset($message['message'])}
                                        <p class="small mb-0 comment-message">{$message['message']|nl2br|escape:'html':'UTF-8'}</p>
                                    {else}

                                    {/if}
                            </div
                            </li>
                            {/if}
                        {/foreach}
                    </ul>
                </div>
            {/if}
        </div>
</div>

