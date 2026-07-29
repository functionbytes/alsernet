  <div class="row">
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6">
                    
                <div class="card">
                    <div class="card-body">

                        <form class="form alsernet-login"  method="post" id="alsernet-login" enctype="multipart/form-data" autocomplete="false" onsubmit="return false" novalidate="novalidate">

                            <input type="hidden" name="_alsernetauth_language" value="{$language.iso_code}">
                            <input type="hidden" name="_alsernetauth_action" value="login">
                            <input type="hidden" name="_alsernetauth_link" value="modules/alsernetauth/controllers/routes.php">

                            <h3 class="card-title">{l s='Title login' mod='alsernetauth'}</h3>
                            <p class="card-text"></p>

                            <div class="mb-3">
                                <label for="email" class="form-label">{l s='Email' mod='alsernetauth'} </label>
                                <input type="text" class="form-control" id="email" name="email" placeholder="{l s='Placeholder email' mod='alsernetauth'}">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">{l s='Password' mod='alsernetauth'}</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="{l s='Placeholder password' mod='alsernetauth'}" >
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                        <div class="check">
                                            <input class="form-check-input fixed-size-input" type="checkbox" value="0" id="check" checked="" >
                                            <label class="form-check-label" for="check">
                                                {l s='Remeber this Device' mod='alsernetauth'}
                                            </label>
                                        </div>
                                        <a class="" href="{$urls.pages.password}">
                                        {l s='Forgot your password?' mod='alsernetauth'}
                                        </a>
                                </div>
                            </div>

                            <div class="response-output"></div>

                            <button type="submit" class="btn btn-primary w-100" class="form-control-submit">
                                {l s='Sign in' mod='alsernetauth'}
                            </button>
                            
                        </form>
                    </div>
                </div>

       </div>
       <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6">
        <div class="card">
            <div class="card-body d">
                  <h3 class="card-title">{l s='Title login register' mod='alsernetauth'} </h3>
                  <p class="card-text">{l s='Description login register' mod='alsernetauth'}</p>

                  <ul class="registration-benefits">
                    <li><p>{l s='Item 1 login register' mod='alsernetauth'}</p></li>
                    <li><p>{l s='Item 2 login register' mod='alsernetauth'}</p></li>
                    <li><p>{l s='Item 3 login register' mod='alsernetauth'}</p></li>
                  </ul>

                  <a href="{$urls.pages.register}" class="btn btn-primary w-100">
                     {l s='No account? Create one here' mod='alsernetauth'}
                  </a>

              </div>
         </div>
       </div>
      </div>
      