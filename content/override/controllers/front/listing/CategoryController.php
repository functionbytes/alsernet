<?php
/**
 * FMM PrettyURLs
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    FMM Modules
 * @copyright Copyright 2018 © fmemodules All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  FMM Modules
 * @package   PrettyURLs
 */

class CategoryController extends CategoryControllerCore
{

    public function init()
    {
        $id_category = (int) Tools::getValue('id_category');

        $this->category = new Category(
            $id_category,
            $this->context->language->id
        );

        parent::init();

        if (!Validate::isLoadedObject($this->category) || !$this->category->active) {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
            $this->setTemplate('errors/404');
            $this->notFound = true;

            return;
        } elseif (!$this->category->checkAccess($this->context->customer->id)) {
            header('HTTP/1.1 403 Forbidden');
            header('Status: 403 Forbidden');
            $this->errors[] = $this->trans('You do not have access to this category.', [], 'Shop.Notifications.Error');
            $this->setTemplate('errors/forbidden');

            return;
        }

        $categoryVar = $this->getTemplateVarCategory();

        $filteredCategory = Hook::exec(
            'filterCategoryContent',
            ['object' => $categoryVar],
            $id_module = null,
            $array_return = false,
            $check_exceptions = true,
            $use_push = false,
            $id_shop = null,
            $chain = true
        );
        if (!empty($filteredCategory['object'])) {
            $categoryVar = $filteredCategory['object'];
        }

        $this->context->smarty->assign([
            'category' => $categoryVar,
            'subcategories' => $this->getTemplateVarSubCategories(),
        ]);
    }
    public function canonicalRedirection($canonicalURL = '')
    {
        if (Validate::isLoadedObject($this->category)) {
            parent::canonicalRedirection($this->context->link->getCategoryLink($this->category));
            if ($this->getCurrentURL() != $this->context->link->getCategoryLink($this->category->id, null, $this->context->language->id) && !Tools::getValue('categorias') && !Tools::getValue('manufacturers') && !Tools::getValue('page') && !Tools::getValue('order') && !Tools::getValue('diametro') && !Tools::getValue('talla') && !Tools::getValue('size') && !Tools::getValue('precio') && !Tools::getValue('price')) {
                Tools::redirectLink($this->context->link->getCategoryLink($this->category));
            }
        }
    }

    public function getAlternativeLangsUrl()
    {
        $alternativeLangs = parent::getAlternativeLangsUrl();

        $languages = Language::getLanguages(true, $this->context->shop->id);
        foreach ($languages as $lang) {
            $alternativeLangs[$lang['language_code']] = $this->context->link->getCategoryLink($this->category->id, null, $lang['id_lang']);
        }

        return $alternativeLangs;
    }


    public function initContent()
    {
        parent::initContent();

        if (Validate::isLoadedObject($this->category) && $this->category->active && $this->category->checkAccess($this->context->customer->id)) {

            if ($this->category->id == 3 ||
                $this->category->id == 4 ||
                $this->category->id == 5 ||
                $this->category->id == 6 ||
                $this->category->id == 7 ||
                $this->category->id == 8 ||
                $this->category->id == 9 ||
                $this->category->id == 10 ||
                $this->category->id == 11) {

                $this->context->smarty->assign([
                    'listing' => [],
                    'category_parent_name' => $this->category->name,
                    'category_parent_id' => $this->category->id_parent,
                ]);

                $this->setTemplate('catalog/listing/category/main', [
                    'entity' => 'categorymain',
                    'id' => $this->category->id,
                ]);

            } else {

                $this->context->smarty->assign([
                    'category_parent_name' => $this->category->name,
                    'category_parent_id' => $this->category->id_parent,
                ]);

                $this->doProductSearch(
                    'catalog/listing/category/list', [
                    'entity' => 'category',
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'category_parent_name' => $this->category->name,
                    'category_parent_id' => $this->category->id_parent,
                ]);
            }
        }
    }

    public function getBreadcrumbLinks()
    {
        $id_lang = Context::getContext()->language->id;
        $lang_iso_code = Language::getIsoById($id_lang);
        $breadcrumb = ['links' => []];

        foreach ($this->category->getAllParents() as $category) {
            if ($category->id_parent != 0 && !$category->is_root_category && $category->active) {
                $path = ltrim($category->category_url_path, '/');
                if ($lang_iso_code !== 'es') {
                    $path = $lang_iso_code . '/' . $path;
                }
                $breadcrumb['links'][] = [
                    'title' => $category->name,
                    'url' => $path,
                ];
            }
        }

        if ($this->category->id_parent != 0 && !$this->category->is_root_category && $this->category->active) {
            $path = ltrim($this->category->category_url_path, '/');
            if ($lang_iso_code !== 'es') {
                $path = $lang_iso_code . '/' . $path;
            }
            $breadcrumb['links'][] = [
                'title' => $this->category->name,
                'url' => $path,
            ];
        }

        return $breadcrumb;
    }



    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();

        if (Validate::isLoadedObject($this->category) && $this->category->active) {
            $page['title'] = $this->category->name;
            $page['meta']['title'] = $this->category->meta_title;
            $page['meta']['keywords'] = $this->category->meta_keywords;
            $page['meta']['description'] = $this->category->meta_description;
            if ($page['meta']['title'] == '') {
                $page['meta']['title'] = $this->category->name;
            }
        }

        return $page;
    }


}
