<section class="breadcrumb-section pt-0">
    <div class="container">
        <div class="breadcrumb-contain">
            <h2>{$title|escape:'html':'UTF-8'}</h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{$urls.base_url|escape:'html':'UTF-8'}">
                            <i class="fa-solid fa-house"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {$title|escape:'html':'UTF-8'}
                    </li>
                </ol>
            </nav>
        </div>
    </div>
</section>
