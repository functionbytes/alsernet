  
  <div class="sticky-content-wrapper" >  
    
    <div class="sticky-footer sticky-content fix-bottom">

        <a href="/" class="sticky-link active">
            <i class="fa-light fa-house"></i>
            <p>{l s='Home sticky' mod='alsernetcontents'}</p>
        </a>

            {if false}
                <a href="/categorias" class="sticky-link">
                    <i class="fa-light fa-layer-group"></i>
                    <p>{l s='Shop sticky' mod='alsernetcontents'}</p>
                </a>
            {/if}

             {if $logged}
                <a href="{$links}" class="sticky-link">
                   <i class="fa-light fa-user-headset"></i>
                    <p>{l s='Account sticky' mod='alsernetcontents'}</p>
                </a>
            {else}
                <a href="{$links}" class="sticky-link">
                    <i class="fa-light fa-user-headset"></i>
                    <p>{l s='Account sticky' mod='alsernetcontents'}</p>
                </a>
            {/if}

            {if $logged}

              <a href="{$link->getPageLink('wishlist', true)}" class="sticky-link sticky-wishlist">
                    <i class="fa-light fa-heart">
                        <span class="wishlist-count {if $isloggedwishlist == false} d-none {/if}">{$wishlist_product}</span>
                    </i>
                    <p>{l s='Wishlist sticky' mod='alsernetcontents'}</p>
                </a>
            {/if}
           
       
            <a href="{$link->getPageLink('cart')}" class="sticky-link sticky-cart">
                <i class="fa-light fa-bag-shopping ">
                    <span class="cart-count">0</span>
                </i>
                <p>{l s='Cart sticky' mod='alsernetcontents'}</p>
            </a>

            {if $sticky}
                <a href=""  class="sticky-link sticky-support">
                    <i class="fa-light fa-user-headset"></i>
                    <p>{l s='Support sticky' mod='alsernetcontents'}</p>
                </a>
            {/if}
            
            {if false}

                <div class="header-search hs-toggle dir-up">
                    <a href="#" class="search-toggle sticky-link">
                        <i class="fa-light fa-magnifying-glass"></i>
                        <p>{l s='Search sticky' mod='alsernetcontents'}</p>
                    </a>
                    <form action="#" class="input-wrapper">
                        <input type="text" class="form-control" name="search" autocomplete="off"
                            placeholder="Search" required />
                        <button class="btn btn-search" type="submit">
                            <i class="fa-light fa-magnifying-glass"></i>
                        </button>
                    </form>
                </div>

            {/if}
    </div>
    </div>