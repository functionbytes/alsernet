<?php return array (
  'name' => 'alvarez',
  'display_name' => 'Alvarez',
  'version' => '1.0.0',
  'theme_key' => '2170d1b6541ce293b34b6c1eb18e002b',
  'author' => 
  array (
    'name' => 'ADDIS Network',
    'email' => 'soporte@addis.es',
    'url' => 'https://www.addis.es/',
  ),
  'meta' => 
  array (
    'compatibility' => 
    array (
      'from' => '1.7.8.0',
      'to' => NULL,
    ),
    'available_layouts' => 
    array (
      'layout-full-width' => 
      array (
        'name' => 'Full Width',
        'description' => 'No side columns, ideal for distraction-free pages such as product pages.',
      ),
      'layout-both-columns' => 
      array (
        'name' => 'Three Columns',
        'description' => 'One large central column and 2 side columns.',
      ),
      'layout-left-column' => 
      array (
        'name' => 'Two Columns, small left column',
        'description' => 'Two columns with a small left column',
      ),
      'layout-right-column' => 
      array (
        'name' => 'Two Columns, small right column',
        'description' => 'Two columns with a small right column',
      ),
    ),
  ),
  'assets' => NULL,
  'dependencies' => 
  array (
    'modules' => 
    array (
      0 => 'appagebuilder',
      1 => 'leobootstrapmenu',
      2 => 'leoslideshow',
      3 => 'leoblog',
      4 => 'leofeature',
      5 => 'blockgrouptop',
      6 => 'leoquicklogin',
      7 => 'leoproductsearch',
      8 => 'leoextratab',
    ),
  ),
  'global_settings' => 
  array (
    'configuration' => 
    array (
      'PS_IMAGE_QUALITY' => 'png',
    ),
    'modules' => 
    array (
      'to_disable' => 
      array (
        0 => 'ps_contactinfo',
        1 => 'ps_mainmenu',
        2 => 'ps_imageslider',
        3 => 'ps_featuredproducts',
        4 => 'ps_banner',
        5 => 'ps_customtext',
        6 => 'productcomments',
        7 => 'ps_linklist',
        8 => 'blockwishlist',
      ),
    ),
    'hooks' => 
    array (
      'modules_to_hook' => 
      array (
        'displayNav1' => 
        array (
          0 => 'appagebuilder',
        ),
        'displayNav2' => 
        array (
          0 => 'appagebuilder',
        ),
        'displayTop' => 
        array (
          0 => 'appagebuilder',
        ),
        'displayHome' => 
        array (
          0 => 'leoblog',
        ),
        'displayFooterBefore' => 
        array (
          0 => 'appagebuilder',
        ),
        'displayFooter' => 
        array (
          0 => 'appagebuilder',
        ),
        'actionAdminBefore' => 
        array (
          0 => 'appagebuilder',
          1 => 'leobootstrapmenu',
          2 => 'leoslideshow',
          3 => 'leoblog',
          4 => 'leofeature',
          5 => 'blockgrouptop',
          6 => 'leoquicklogin',
          7 => 'leoproductsearch',
          8 => 'leoextratab',
        ),
      ),
    ),
    'image_types' => 
    array (
      'cart_default' => 
      array (
        'width' => 125,
        'height' => 157,
        'scope' => 
        array (
          0 => 'products',
        ),
      ),
      'small_default' => 
      array (
        'width' => 98,
        'height' => 123,
        'scope' => 
        array (
          0 => 'products',
          1 => 'categories',
          2 => 'manufacturers',
          3 => 'suppliers',
        ),
      ),
      'medium_default' => 
      array (
        'width' => 378,
        'height' => 472,
        'scope' => 
        array (
          0 => 'products',
          1 => 'manufacturers',
          2 => 'suppliers',
        ),
      ),
      'home_default' => 
      array (
        'width' => 378,
        'height' => 472,
        'scope' => 
        array (
          0 => 'products',
        ),
      ),
      'large_default' => 
      array (
        'width' => 800,
        'height' => 1000,
        'scope' => 
        array (
          0 => 'products',
          1 => 'manufacturers',
          2 => 'suppliers',
        ),
      ),
      'category_default' => 
      array (
        'width' => 480,
        'height' => 360,
        'scope' => 
        array (
          0 => 'categories',
        ),
      ),
      'stores_default' => 
      array (
        'width' => 170,
        'height' => 115,
        'scope' => 
        array (
          0 => 'stores',
        ),
      ),
      'manu_default' => 
      array (
        'width' => 130,
        'height' => 87,
        'scope' => 
        array (
          0 => 'manufacturers',
        ),
      ),
    ),
  ),
  'theme_settings' => 
  array (
    'default_layout' => 'layout-full-width',
    'layouts' => 
    array (
      'category' => 'layout-left-column',
      'best-sales' => 'layout-left-column',
      'new-products' => 'layout-left-column',
      'prices-drop' => 'layout-left-column',
      'contact' => 'layout-left-column',
      'manufacturer' => 'layout-left-column',
      'supplier' => 'layout-left-column',
      'module-leoblog-blog' => 'layout-left-column',
      'module-leoblog-category' => 'layout-left-column',
      'module-leoblog-list' => 'layout-left-column',
    ),
  ),
);
