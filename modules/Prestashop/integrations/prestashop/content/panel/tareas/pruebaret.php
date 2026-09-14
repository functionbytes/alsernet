<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

$categoriesExcluded = Configuration::get('RR_FEED_EXCLUDED_CATEGORIES');
$categoriesExcludedIds = (empty($categoriesExcluded) || empty(unserialize($categoriesExcluded))) ? [] : unserialize($categoriesExcluded);

dump($categoriesExcludedIds);

$categoriesInFeedIds = [];
$categories = Category::getCategories(1, true);
foreach ($categories as $cats) {
    foreach ($cats as $category) {
        if (! in_array($category['infos']['id_category'], $categoriesExcludedIds)) {
            $categoriesInFeedIds[] = $category['infos']['id_category'];
        }
    }
}

dump($categoriesInFeedIds);
exit();

$categories = Category::getCategories(1, true);

foreach ($categories as $cats) {
    foreach ($cats as $category) {

        dump($category);
        echo $category['infos']['id_category'].' '.$category['infos']['id_parent'].' '.$category['infos']['name'];
        exit();
    }
}

foreach ($categories as $cats) {

    dump($cats);
    echo $cats['id_category'].' '.$cats['id_parent'].' '.$cats['name'];
    exit();
}
