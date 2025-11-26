
  <div class="row justify-content-center">
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-8 col-xl-8">
                    
                <div class="card">
                    <div class="card-body">
                       
                        <form class="form"  method="post" id="alsernet-changepassword" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">
                            
                            <input type="hidden" name="_alsernetauth_language" value="{$language.iso_code}">
                            <input type="hidden" name="_alsernetauth_action" value="changepassword">
                            <input type="hidden" name="_alsernetauth_link" value="modules/alsernetauth/controllers/routes.php">
                            <input type="hidden" name="token" id="token" value="{$customer_token}">
                            <input type="hidden" name="id_customer" id="id_customer" value="{$id_customer}">
                            <input type="hidden" name="reset_token" id="reset_token" value="{$reset_token}">
                        
                            <h3 class="card-title">{l s='Reset your password' mod='alsernetauth'}</h3>
                            <p class="card-text">{l s='Please reset password.' mod='alsernetauth'}</p>


                            <div class="mb-3">
                                <label for="email" class="form-label">{l s='Email' mod='alsernetauth'} </label>
                                <input type="text" class="form-control" id="email" name="email" placeholder="{l s='Placeholder Email' mod='alsernetauth'}" value="{$customer_email}" disabled>
                            </div>


                            <div class="mb-3">
                                <label for="password" class="form-label">{l s='New password' mod='alsernetauth'} </label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="{l s='Placeholder New Password' mod='alsernetauth'}" autocomplete="new-password"> 
                            </div>


                            <div class="mb-3">
                                <label for="password" class="form-label">{l s='Confirmation' mod='alsernetauth'} </label>
                                <input type="password" class="form-control" id="confirmation" name="confirmation" placeholder="{l s='Placeholder confirmation' mod='alsernetauth'}" autocomplete="new-password"> 
                            </div>


                            <div class="g-recaptcha"  id="g-recaptcha-response-changepassword"  data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"></div>
                            <div class="response-output"></div>

                            <button type="submit" class="btn btn-primary w-100"  >
                                {l s='Change Password' mod='alsernetauth'}
                            </button>
                            
                        </form>
                    </div>
                </div>

       </div>
</div>
      
