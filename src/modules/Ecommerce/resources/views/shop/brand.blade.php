@include('ecommerce::shop.products', [
    'pageTitle' => $brand->name,
    'products'  => $products,
    'ogData'    => [
        'type'        => 'website',
        'title'       => $brand->name,
        'description' => Str::limit(strip_tags($brand->description ?? 'Productos de la marca ' . $brand->name), 160),
        'image'       => $brand->logo ?: asset('modules/ecommerce/images/404.png'),
        'url'         => url()->current(),
    ],
])
