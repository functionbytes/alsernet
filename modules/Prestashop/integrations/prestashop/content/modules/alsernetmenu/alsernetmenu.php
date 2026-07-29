<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Alsernetmenu  extends Module implements WidgetInterface
{

    public function __construct(){

        $this->name = 'alsernetmenu';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Alsernet - Menu');
        $this->description = $this->l('Alsernet - Menu');

        $this->confirmUninstall = $this->l('Estas seguro que deseas desistalar el modulo');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '8.0');
    }

    public function install(){
        return parent::install() && $this->registerHook('displayTop')  && $this->registerHook('displayBeforeBodyClosingTag')  && $this->registerHook('displayLeftColumn')  && $this->registerHook('header');
    }

    public function uninstall(){
        return parent::uninstall()&& $this->unregisterHook('displayTop')  && $this->unregisterHook('displayBeforeBodyClosingTag')   && $this->unregisterHook('displayLeftColumn')  && $this->unregisterHook('header');
    }

    public function renderWidget($hookName = null, array $configuration = []){

        if ($hookName == 'displayBeforeBodyClosingTag') {


            $iso_lang = $this->context->language->iso_code;

            $jsonFilePath = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/category.json';

                $categories = [];

                if (file_exists($jsonFilePath)) {

                    $jsonContent = file_get_contents($jsonFilePath);
                    $categories = json_decode($jsonContent, true);


                    foreach ($categories as &$category) {

                        if($category["id"] != 0){

                            $jsonFilePathSubcategory = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category['id'] . '/subcategory.json';

                            if (file_exists($jsonFilePathSubcategory)) {

                                $jsonContent = file_get_contents($jsonFilePathSubcategory);

                                $subcategories = json_decode($jsonContent, true);

                                $category["subcategories"] = $subcategories;
                            }

                            $jsonFilePathUrls = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category['id'] . '/url.json';

                            if (file_exists($jsonFilePathUrls)) {

                                $jsonContent = file_get_contents($jsonFilePathUrls);

                                $urls = json_decode($jsonContent, true);

                                $category["urls"] = $urls;
                            }

                        }
                    }


                } else {
                    $categories = null;
                }


                $smarty = $this->context->smarty;

                $smarty->assign(array(
                    'iso_lang' => $iso_lang,
                    'categories_mobile' => $categories,
                ));


                return $this->fetch('module:alsernetmenu/views/templates/hook/mobile.tpl');

        }elseif (isset($configuration['type'])) {

            if ($configuration['type'] == 'supernav') {

                $data = $this->getWidgetVariablesCategories($hookName, $configuration);
                $this->context->smarty->assign('categories', $data['categories']);

                return $this->fetch('module:alsernetmenu/views/templates/hook/supernav.tpl');
            }elseif($configuration['type'] == 'navs'){

                $iso_lang = Context::getContext()->language->iso_code;

                    $category = $this->getWidgetVariablesCategoriesDetail($hookName, $configuration);

                    if($category){

                        $subcategories = [];

                            if ($category['grandfather'] == 2) {

                                $id_rewrite = $category['category']->id;
                                $subcategories = $this->getWidgetVariablesCategory($id_rewrite);
                                $specials = $this->getWidgetVariablesSpecial($id_rewrite);

                                $this->smarty->assign(array(
                                    'category' => $category,
                                    'name' => $category['category']->name,
                                    'subcategories' => $subcategories,
                                    'specials' => $specials,
                                ));


                                return $this->fetch('module:alsernetmenu/views/templates/hook/nav.tpl');

                            } else {


                                if (count($category['subcategories']) == 0) {

                                    $subcategories = [];

                                    $parent = new Category($category['id'], $this->context->language->id);
                                    $link = new Link();
                                    $links = $link->getCategoryLink($parent->id, NULL, $this->context->language->id);
                                    $parent = $category['grandfather'];

                                }else {

                                    $grandfather = $this->getPenultimateParentCategory((int)$category['id']);
                                    $parent = new Category((int)$grandfather->id, $this->context->language->id);
                                    $subcategories = $category['category']->getSubCategories($this->context->language->id);
                                    $links = $parent->link_rewrite;
                                    $parent = $parent->id;
                                }


                                foreach ($subcategories as &$subcategory) {
                                    $link = new Link();
                                    $subcategory['link_rewrite'] = $link->getCategoryLink($subcategory['id_category'], NULL, $this->context->language->id);
                                }


                                $name = $this->searchJsonSubcategory($category['grandfather'] , $category);

                                if($name!=""){
                                    $name =  $name;
                                }else{
                                    $grandfather = new Category((int)$category['grandfather'], $this->context->language->id);
                                    $name =  $grandfather->name;
                                }

                                if($category['grandfather'] = 2821){
                                    $name = $category['category']->name;
                                }


                                $this->smarty->assign(array(
                                    'category' => $category['category']->name,
                                    'parent' => $parent,
                                    'name' => $name,
                                    'link' => $links,
                                    'categories' => $subcategories,
                                ));

                                return $this->fetch('module:alsernetmenu/views/templates/hook/category.tpl');


                            }
                    }

            }elseif($configuration['type'] == 'urls'){
                $category = $this->getWidgetVariablesCategoriesDetail($hookName, $configuration);
                $cache_id = 'alsernetmenu_urls_' . (isset($category['category']) ? $category['category']->id : 'default');

                if (!$this->isCached('module:alsernetmenu/views/templates/hook/url.tpl', $cache_id)) {

                    if ($category['grandfather'] == 2) {
                        $grandfather = new Category((int)$this->context->controller->getCategory()->id, $this->context->language->id);
                        $id_rewrite = $grandfather->id;
                    }else {
                        $grandfather = $this->getPenultimateParentCategory((int)$category['id']);
                        $parent = new Category((int)$grandfather->id, $this->context->language->id);
                        $id_rewrite = $parent->id;
                    }

                    $urls = $this->getWidgetVariablesCategoryUrls($id_rewrite);


                    // if ($urls != null) {
                        $this->context->smarty->assign("urls", $urls);
                    // }

                    return $this->fetch('module:alsernetmenu/views/templates/hook/url.tpl');

                }
            }elseif($configuration['type'] == 'images'){

                // $category = $this->getWidgetVariablesCategoriesDetail($hookName, $configuration);


                //     $link_rewrite = $category['category']->link_rewrite;
                //     $images = $this->getWidgetVariablesCategoryImages($link_rewrite);

                //     if ($images != null) {
                //         $this->context->smarty->assign('images', $images);
                //     }

                // return $this->fetch('module:alsernetmenu/views/templates/hook/images.tpl');

            }elseif($configuration['type'] == 'brands'){
                $this->context->smarty->assign('sports', $configuration['sports']);

                return $this->fetch('module:alsernetmenu/views/templates/hook/sports.tpl');
            }
        }

    }

    public function handleCategory($category_id, $iso_lang = false){

        $html = '';
        $column = '';
        $columns = '';

        if (!$iso_lang) $iso_lang = Context::getContext()->language->iso_code;

         $jsonFilePathSubcategory = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category_id . '/subcategory.json';

         $jsonFilePathUrl = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category_id . '/url.json';

         $jsonFilePathCategories = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang .'/category.json';

         $jsonContentCategories = file_get_contents($jsonFilePathCategories);

            $categories = json_decode($jsonContentCategories, true);

            foreach($categories as $key => $category) {

                if($category['id'] == $category_id) {
                    $column    = $category;
                    $columns = isset($column['column']) ? intval($column['column']) : 1;
                }
            }

         $html .= "<div class='panel-inventaries'>";


            if (file_exists($jsonFilePathSubcategory)) {

                $jsonContent = file_get_contents($jsonFilePathSubcategory);

                $subcategories = json_decode($jsonContent, true);

                        $col_width = 12 / $columns;
                        $col_class = ($col_width == 2.4) ? 'col-md-2-4' : 'col-md-' . $col_width;


                            $html .= "<div class='panel-submenu'>";

                                $subcategories_sorted = [];

                                    foreach ($subcategories as $subcategory) {
                                        $column = isset($subcategory['column']) ? intval($subcategory['column']) : 0;
                                        $subcategories_sorted[$column][] = $subcategory;
                                    }

                                    foreach ($subcategories_sorted as $column_index => $column_subcategory) {

                                        $html .= "<div class='$col_class'>";

                                            foreach ($column_subcategory as $subcategorie_index => $subcategorie) {

                                                    $sub_cat_class = (($column_index == 0) && ($subcategorie_index == 0)) ? 'first-column' : '';

                                                    $html .= "<div class='item_sub $sub_cat_class' data-column='$column_index' data-subcounter='$column_index'>";
                                                            $html .= "<ul class='subcategorie-title'>" . $subcategorie['title'];
                                                            $html .= "</ul>";
                                                            $html .= "<ul class='subcategorie-items'>";

                                                                    foreach ($subcategorie['items'] as $item_index => $item) {
                                                                        if($item['visible'] == 1 || $item['visible'] == 3){
                                                                            $html .= "<li><a href='" . $item["url"] . "' >" . $item["title"] . "</a></li>";
                                                                        }
                                                                    }

                                                            $html .= "</ul>";
                                                    $html .= "</div>";
                                        }

                                        $html .= "</div>";

                                    }


                            $html .= "</div>";


            }

        $html .= "</div>";

        if (file_exists($jsonFilePathUrl)) {

            $jsonContent = file_get_contents($jsonFilePathUrl);


            $urls = json_decode($jsonContent, true);


                    $col_width = 12 / $columns;
                    $col_class = ($col_width == 2.4) ? 'col-md-2-4' : 'col-md-' . $col_width;

                    $html .= "<div class='panel-itemmenu'>";
                        foreach ($urls as $index => $subcategory) {
                            $html .= "<div class='row'>";
                                foreach ($subcategory['items'] as $item) {
                                    $html .= "<div class='$col_class'><a href='" . $item["url"] . "' >" . $item["title"] . "</a></div>";
                                }
                                $html .= "</div>";
                                $html .= "</div>";
                        }


        }

        $html .= "</div>";

        return $html;

    }

    public function handleMobile($iso){

        $context = Context::getContext();

        $jsonFilePath = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso . '/category.json';

        $categories = [];

        if (file_exists($jsonFilePath)) {

            $jsonContent = file_get_contents($jsonFilePath);
            $categories = json_decode($jsonContent, true);

            foreach ($categories as &$category) {
                if($category["id"] != 0){

                    $jsonFilePathSubcategory = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso . '/' . $category['id'] . '/subcategory.json';

                    if (file_exists($jsonFilePathSubcategory)) {

                        $jsonContent = file_get_contents($jsonFilePathSubcategory);

                        $subcategories = json_decode($jsonContent, true);

                        $category["subcategories"] = $subcategories;
                    }

                    $jsonFilePathUrls = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso . '/' . $category['id'] . '/url.json';

                    if (file_exists($jsonFilePathUrls)) {

                        $jsonContent = file_get_contents($jsonFilePathUrls);

                        $urls = json_decode($jsonContent, true);

                        $category["urls"] = $urls;
                    }

                }
            }


        } else {
            $categories = null;
        }


        $smarty = $this->context->smarty;

        $smarty->assign(array(
            'iso_lang' => $iso,
            'categories_mobile' => $categories,
        ));

        return $this->fetch('module:alsernetmenu/views/templates/hook/mobile.tpl');

    }

    public function handleMobiles(){

        $html= "";
        $context = Context::getContext();
        $iso_lang = $context->language->iso_code;

        $jsonFilePath = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/category.json';

        $html .= "<div>";
        $title_products = "";

        if (file_exists($jsonFilePath)) {

            $jsonContent = file_get_contents($jsonFilePath);

            $categories = json_decode($jsonContent, true);

            $html .= "<nav class='nav mobile' role='navigation'>";

            $html .= "
                <div class='mm-header'>
                    <div class='col-sm-2'>
                        <div class='close-button'>
                            <i class='fa fa-times' aria-hidden='true'></i>
                        </div>
                    </div>
                    <div class='col-sm-8'>
                        <div class='h-logo'>
                            <a href='https://cristian.preproduccion.a-alvarez.com/'>
                                <img src='/themes/child_alvarez/assets/img/logo/es/logo-alvarez-es.jpg' alt='Alvarez' class='logo-img'>
                            </a>
                        </div>
                    </div>
                    <div class='col-sm-2'>
                        <div class='account-action'>
                            <a class='signin' href='/iniciar-sesion'>
                                <i class='fa fa-user' aria-hidden='true'></i>
                            </a>
                        </div>
                    </div>
                </div>";

            $html .= "<div class='mm-menu shadow'>
                        <ul class='menu-content back-menu' id='base-menu' data-id-category='0'>";

            foreach ($categories as $category) {

                if ($category['id'] == 0) {
                    $title_products = $category['title'];
                }else{

                    $html .= "<li class='nav-item'>
                            <a class='category-item' title='{$category['title']}'>
                                {$category['title']}
                                <span class='navbar-toggler collapse-icons'>
                                    <i class='fa fa-chevron-down down'></i>
                                    <i class='fa fa-chevron-up up'></i>
                                </span>
                            </a>
                            <div class='all-items'>
                                <a class='category-all'> {$title_products}
                                    <span class='navbar-toggler collapse-icons'>
                                        <i class='fa fa-chevron-down down'></i>
                                        <i class='fa fa-chevron-up up'></i>
                                    </span>
                                </a>
                                <div class='items-submenu'>";

                $jsonFilePathSubcategory = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category['id'] . '/subcategory.json';

                if (file_exists($jsonFilePathSubcategory)) {

                    $jsonContent = file_get_contents($jsonFilePathSubcategory);

                    $subcategories = json_decode($jsonContent, true);

                    $html .= "<div class='panel-submenu'>";

                    $subcategories_sorted = [];
                    foreach ($subcategories as $subcategory) {
                        $column = isset($subcategory['column']) ? intval($subcategory['column']) : 0;
                        $subcategories_sorted[$column][] = $subcategory;
                    }

                    foreach ($subcategories_sorted as $column_index => $column_subcategory) {
                        foreach ($column_subcategory as $subcategorie_index => $subcategorie) {
                            $html .= "<div class='item_sub'>
                                        <ul class='title-subcategory'>{$subcategorie['title']}";
                            if (count($subcategorie['items']) > 0) {
                                $html .= "<span class='navbar-toggler collapse-icons'>
                                            <i class='fa fa-chevron-down down'></i>
                                            <i class='fa fa-chevron-up up'></i>
                                        </span>";
                            }
                            $html .= "</ul>
                                    <ul class='item-subcategory'>";
                            foreach ($subcategorie['items'] as $item_index => $item) {
                                $html .= "<li><a href='{$item["url"]}' >{$item["title"]}</a></li>";
                            }
                            $html .= "</ul>
                                    </div>";
                        }
                    }
                    $html .= "</div>";
                }


                $html .= "</div>";

                $jsonFilePathUrl = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category['id'] . '/url.json';
                if (file_exists($jsonFilePathUrl)) {

                    $jsonContent = file_get_contents($jsonFilePathUrl);

                    $urls = json_decode($jsonContent, true);
                    $html .= "<div class='panel-urls'>";
                    foreach ($urls as $url) {
                        $html .= "<div class='item_sub'>";
                        foreach ($url['items'] as $item_index => $itemdemos) {
                            $html .= "<a href='{$itemdemos["url"]}' >{$itemdemos["title"]}</a>";
                        }
                        $html .= "</div>";
                    }
                    $html .= "</div>";
                    $html .= "</div>";
                }


                $html .= "</li>";

                }



            }

            $html .= "</ul>
                    </div>
                    </nav>";
        }

        $html .= "</div>";

        return $html;
    }

    public function handleSubcategory($category,$subcategory){

        $iso_lang = Context::getContext()->language->iso_code;
        $jsonFilePathCategory = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category . '/subcategory.json';

        if (!file_exists($jsonFilePathCategory)) {
            return '';
        }

        $jsonContent = file_get_contents($jsonFilePathCategory);
        $categories = json_decode($jsonContent, true);


        if ($categories === null) {
            return '';
        }

        foreach ($categories as $itemCategory) {
            if ($itemCategory['id'] == $subcategory) {
                $htmlItems = array_map(function ($item) {
                    if($item['visible'] == 1 || $item['visible'] == 2){
                        return "<div class='item'><a href='" . htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') . "' >" . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . "</a></div>";
                    }
                }, $itemCategory['items']);

                return implode('', $htmlItems);
            }
        }

        return '';

    }

    public function getTrees($resultParents, $resultIds, $maxDepth, $id_category = null, $category = null, $currentDepth = 0){

        $desc = '';
        $children = [];

        if (isset($resultParents[$id_category]) && count($resultParents[$id_category]) && ($maxDepth == 0 || $currentDepth < $maxDepth)) {
            foreach ($resultParents[$id_category] as $subcat) {
                $children[] = $this->getTrees($resultParents, $resultIds, $maxDepth, $subcat['id_category'],$category, $currentDepth + 1);
            }
        }

        if (isset($resultIds[$id_category])) {
            $link = $this->context->link->getCategoryLink($id_category, $resultIds[$id_category]['link_rewrite'], $this->context->language->id, null, null, false, 0, 0, 1);
            $name = $resultIds[$id_category]['name'];
            $id_parent = $resultIds[$id_category]['id_parent'];
        } else {
            $link = $name = $desc = '';
            $id_parent = null;
        }


        return [
            'link' => $link,
            'desc' => $desc,
            'subcategories' => $children,
            'category' => $category,
            'name' => $name,
            'parent' => (int)$id_parent,
            'id' => (int)$id_category,
            'grandfather' => (int)$category->id_parent,
        ];


    }

    function getPenultimateParentCategory($categoryId){

        $category = new Category($categoryId);

        if (!Validate::isLoadedObject($category)) {
            return false;
        }

        $parentCategories = $category->getParentsCategories();

        if (count($parentCategories) < 2) {
            return false;
        }

        $penultimateParentCategory = $parentCategories[count($parentCategories) - 2];

        return new Category($penultimateParentCategory['id_category']);

    }

    public function searchJsonSubcategory($category , $subcategory){
        $link = new Link();
        $link =  $link->getCategoryLink($subcategory['id'], NULL, NULL, NULL, $this->context->language->id);
        $url = preg_replace('/^https?:\/\/[^\/]+/i', '', $link);


        $context = Context::getContext();
        $iso_lang = $context->language->iso_code;

        $jsonFilePathSubcategory = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category . '/subcategory.json';

        if (file_exists($jsonFilePathSubcategory)) {

            $jsonContent = file_get_contents($jsonFilePathSubcategory);

            $subcategories = json_decode($jsonContent, true);
            foreach ($subcategories as $subcategory) {
                if (isset($subcategory['items'])) {
                    foreach ($subcategory['items'] as $item) {
                        if (isset($item['url']) && $item['url'] === $url) {
                            return $subcategory['title'];
                        }
                    }
                }
            }

        }


    }

    public function getFirstCategoryAtDepth($targetLevelDepth){
        $currentCategory = $this->context->controller->getCategory();

        while ($currentCategory->level_depth < $targetLevelDepth) {
            $subCategories = $currentCategory->getChildren();

            if (empty($subCategories)) {
                $category = null;
                break;
            }

            return $subCategories[0];
        }
    }

    public function getCategoryFullUrl($category, $context){
        $self =  $this->context->controller->getCategory();

        $resultIds = [];
        $resultParents = [];

        $category = new Category(
               (int)$self->id_category,
               $this->context->language->id
        );

        $categories = $category->getSubCategories($this->context->language->id);

        foreach ($categories as $row) {
            $resultParents[$row['id_parent']][] = $row;
            $resultIds[$row['id_category']] = $row;
        }

        return $this->getTrees($resultParents, $resultIds, 0, ($category ? $category->id : null),$category);
    }

    public function getWidgetVariablesCategoriesDetail($hookName = null, array $configuration = []){
        if (method_exists($this->context->controller, 'getCategory')) {
            $self =  $this->context->controller->getCategory();

            $resultIds = [];
            $resultParents = [];

            $category = new Category(
                (int)$self->id_category,
                $this->context->language->id
            );

            $categories = $category->getSubCategories($this->context->language->id);

            foreach ($categories as $row) {
                $resultParents[$row['id_parent']][] = $row;
                $resultIds[$row['id_category']] = $row;
            }

            return $this->getTrees($resultParents, $resultIds, 0, ($category ? $category->id : null),$category);
        }else{
            return false;
        }
    }

    public function getWidgetVariables($hookName, $configuration){
    }

    public function getWidgetVariablesCategories(){

        $iso_lang = Context::getContext()->language->iso_code;
        $jsonFilePath = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/category.json';

        if (file_exists($jsonFilePath)) {

            $jsonContent = file_get_contents($jsonFilePath);

            $categories = json_decode($jsonContent, true);

            $filteredCategories = array_filter($categories, function($category) {
                return $category['id'] != 0;
            });


            foreach($filteredCategories as $key => $category) {
                $url = $this->context->link->getModuleLink('alsernetmenu', 'menu');

                if($iso_lang != "es") {
                    $category['url'] = $iso_lang . '/' . $category['url'];
                }

                $category['action'] = $url . '?method=category&category=' . $category['id'];
                $filteredCategories[$key] = $category;
            }

        } else {
            $filteredCategories = array();
        }

        $data = [
            'categories' => $filteredCategories,
        ];

        return $data;
    }

    public function getWidgetVariablesCategory($category)
    {

        $iso_lang = Context::getContext()->language->iso_code;
        $jsonFilePathSubcategory = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category . '/subcategory.json';

        if (file_exists($jsonFilePathSubcategory)) {

            $jsonContent = file_get_contents($jsonFilePathSubcategory);

            $subcategories = json_decode($jsonContent, true);

            foreach ($subcategories as $key => $subcategorie) {
                $url = $this->context->link->getModuleLink(
                    'alsernetmenu',
                    'menu'
                );

                $subcategories[$key]['action'] = $url . '?method=subcategory&category=' . $category . '&subcategory=' . $subcategorie['id'];
            }

            usort($subcategories, function($a, $b) {
                return $a['position_category'] <=> $b['position_category'];
            });

            return $subcategories;

        } else {

            $categories = array();
            return $categories;

        }
    }

    public function getWidgetVariablesSpecial($category){

        $iso_lang = Context::getContext()->language->iso_code;
        $jsonFilePathSubcategory = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category . '/special.json';

        if (file_exists($jsonFilePathSubcategory)) {

            $jsonContent = file_get_contents($jsonFilePathSubcategory);

            $specials = json_decode($jsonContent, true);

            return $specials;

        }else {

            $specials = array();
            return $specials;

        }
    }

    public function getWidgetVariablesCategoryUrls($category){

        $iso_lang = Context::getContext()->language->iso_code;

        $jsonFilePath = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/' . $category . '/url.json';

        if (file_exists($jsonFilePath)) {

            $jsonContent = file_get_contents($jsonFilePath);
            $url = json_decode($jsonContent, true);

            return $url[0];


        } else {

            return array();

        }


    }

    public function getWidgetVariablesCategoryImages($category){

        $images = [];
        $iso_lang = Context::getContext()->language->iso_code;

        $jsonFilePath = _PS_MODULE_DIR_ . 'alsernetmenu/json/' . $iso_lang . '/categories2.json';


        if (file_exists($jsonFilePath)) {

            $jsonContent = file_get_contents($jsonFilePath);

            $categories = json_decode($jsonContent, true);

            foreach($categories as $key => $item) {

                if ($item['url'] == $category) {

                   foreach($item['subcategories'] as $key => $subcategory) {

                        if ($subcategory['type'] ==  "images") {

                            $images[]= $subcategory;

                        }
                    }
                }

            }

            return $images;


        } else {
            $categories = array();
        }


    }

    public function hookdisplayBeforeBodyClosingTag($params){
        return $this->renderWidget('displayBeforeBodyClosingTag', $params);
    }

    public function hookdisplayTop($params){
        return $this->renderWidget('displayTop', $params);
    }

    public function hookDisplayLeftColumn($params){
        return $this->renderWidget('displayLeftColumn', $params);
    }

    public function hookHeader($params){
        $this->context->controller->addCSS($this->_path . 'views/css/front/style.css', 'all');
        $this->context->controller->addJS($this->_path . 'views/js/front/scripts.js');
    }

}

