@include('ecommerce::shop.products', [
    'pageTitle' => $category->name,
    'products'  => $products,
    'ogData'    => [
        'type'        => 'website',
        'title'       => $category->name,
        'description' => Str::limit(strip_tags($category->description ?? 'Productos de la categoria ' . $category->name), 160),
        'image'       => $category->image ?: asset('modules/ecommerce/images/404.png'),
        'url'         => url()->current(),
    ],
])
