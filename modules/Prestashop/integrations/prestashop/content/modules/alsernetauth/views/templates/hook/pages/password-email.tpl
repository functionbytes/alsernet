
  <div class="row justify-content-center">
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-8 col-xl-8">
                <div class="card">
                    <div class="card-body">
                       
                        <form class="form"  method="post" id="alsernet-resetpassword" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">
                            
                            <input type="hidden" name="_alsernetauth_language" value="{$language.iso_code}">
                            <input type="hidden" name="_alsernetauth_action" value="resetpassword">
                            <input type="hidden" name="_alsernetauth_link" value="modules/alsernetauth/controllers/routes.php">
                        
                            <h3 class="card-title">{l s='Forgot your password?' mod='alsernetauth'}</h3>
                            <p class="card-text">{l s='Please enter the email address you used to register. You will receive a temporary link to reset your password.' mod='alsernetauth'}</p>


                            <div class="mb-3">
                                <label for="email" class="form-label">{l s='Email' mod='alsernetauth'} </label>
                                <input type="text" class="form-control" id="email" name="email" placeholder="{l s='Placeholder Email' mod='alsernetauth'}" autocomplete="new-email">
                            </div>

                            <div class="g-recaptcha"  id="g-recaptcha-response-resetpassword"  data-sitekey="6LcRY40nAAAAAEFYHjowIjVbySvS7OBev7_mZsSh"></div>
                            <div class="response-output"></div>

                            <button type="submit" class="btn btn-primary w-100 mb-2"  >
                                {l s='Reset password' mod='alsernetauth'}
                            </button>
                            
                             <a href="/iniciar-sesion" class="btn btn-dark w-100"  >
                                {l s='Back to login' mod='alsernetauth'}
                             </a>
                            
                        </form>
                    </div>
                </div>

       </div>
</div>
      
