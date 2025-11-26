<?php

class CmsController extends CmsControllerCore
{
    public function canonicalRedirection($canonicalURL = '')
    {
        if (Validate::isLoadedObject($this->cms) && ($canonicalURL = $this->context->link->getCMSLink($this->cms, $this->cms->link_rewrite, $this->ssl))) {
            parent::canonicalRedirection($canonicalURL);
        } elseif (Validate::isLoadedObject($this->cms_category) && ($canonicalURL = $this->context->link->getCMSCategoryLink($this->cms_category))) {
            parent::canonicalRedirection($canonicalURL);
        }
    }

    public function init()
    {
        if ($id_cms = (int)Tools::getValue('id_cms')) {
            $this->cms = new CMS($id_cms, $this->context->language->id, $this->context->shop->id);
        } elseif ($id_cms_category = (int)Tools::getValue('id_cms_category')) {
            $this->cms_category = new CMSCategory($id_cms_category, $this->context->language->id, $this->context->shop->id);
        }

        if (Configuration::get('PS_SSL_ENABLED') && Tools::getValue('content_only') && $id_cms && Validate::isLoadedObject($this->cms)
            && in_array($id_cms, $this->getSSLCMSPageIds())) {
            $this->ssl = true;
        }

        parent::init();

        $this->canonicalRedirection();

        // assignCase (1 = CMS page, 2 = CMS category)
        if (Validate::isLoadedObject($this->cms)) {
            $adtoken = Tools::getAdminToken('AdminCmsContent' . (int)Tab::getIdFromClassName('AdminCmsContent') . (int)Tools::getValue('id_employee'));
            if (!$this->cms->isAssociatedToShop() || !$this->cms->active && Tools::getValue('adtoken') != $adtoken) {
                $this->redirect_after = '404';
                $this->redirect();
            } else {
                $this->assignCase = 1;
            }
        } elseif (Validate::isLoadedObject($this->cms_category) && $this->cms_category->active) {
            $this->assignCase = 2;
        } else {
            $this->redirect_after = '404';
            $this->redirect();
        }
    }

    public function getAlternativeLangsUrl()
    {
        $alternativeLangs = parent::getAlternativeLangsUrl();
        $languages = Language::getLanguages(true, $this->context->shop->id);
        foreach ($languages as $lang) {
            $alternativeLangs[$lang['language_code']] = $this->context->link->getCMSLink($this->cms, null, $lang['id_lang']);
        }
        return $alternativeLangs;
    }

    /*
    * module: prettyurls
    * date: 2022-02-24 07:44:40
    * version: 2.2.6
    */
    public function inits()
    {
        $link_rewrite = Tools::safeOutput(urldecode(Tools::getValue('cms_rewrite')));
        $cms_pattern = '/.*?\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)/';
        preg_match($cms_pattern, $_SERVER['REQUEST_URI'], $url_array);
        if (isset($url_array[2]) && $url_array[2] != '') {
            $link_rewrite = $url_array[2];
        }
        $cms_category_rewrite = Tools::safeOutput(urldecode(Tools::getValue('cms_category_rewrite')));
        $cms_cat_pattern = '/.*?\/category\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)/';
        preg_match($cms_cat_pattern, $_SERVER['REQUEST_URI'], $url_cat_array);
        if (isset($url_cat_array[2]) && $url_cat_array[2] != '') {
            $cms_category_rewrite = $url_cat_array[2];
        }
        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;
        if ($link_rewrite) {
            $sql = 'SELECT tl.id_cms
					FROM ' . _DB_PREFIX_ . 'cms_lang tl
					LEFT OUTER JOIN ' . _DB_PREFIX_ . 'cms_shop t ON (t.id_cms = tl.id_cms)
					WHERE tl.link_rewrite = \'' . pSQL($link_rewrite) . '\' AND tl.id_lang = ' . (int)$id_lang . ' AND t.id_shop = ' . (int)$id_shop;
            $id_cms = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
            if ($id_cms != '') {
                $_POST['id_cms'] = $id_cms;
                $_GET['cms_rewrite'] = '';
            }
        } elseif ($cms_category_rewrite) {
            $sql = 'SELECT id_cms_category
					FROM ' . _DB_PREFIX_ . 'cms_category_lang
					WHERE link_rewrite = \'' . pSQL($cms_category_rewrite) . '\' AND id_lang = ' . (int)$id_lang;
            $id_cms_category = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
            if ($id_cms_category != '') {
                $_GET['cms_category_rewrite'] = '';
                $_POST['id_cms_category'] = $id_cms_category;
            }
        }
        $allow_accented_chars = (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
        if ($allow_accented_chars > 0) {
            $id_cms = (int)Tools::getValue('id_cms');
            if ($id_cms <= 0) {
                $id = (int)$this->crawlDbForId($_GET['cms_rewrite']);
                if ($id > 0) {
                    $_POST['id_cms'] = $id;
                }
            }
        }
        parent::init();
    }

    /*
    * module: prettyurls
    * date: 2022-02-24 07:44:40
    * version: 2.2.6
    */
    protected function crawlDbForIds($rew)
    {
        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;
        $sql = new DbQuery();
        $sql->select('`id_cms`');
        $sql->from('cms_lang');
        $sql->where('`id_lang` = ' . (int)$id_lang . ' AND `id_shop` = ' . (int)$id_shop . ' AND `link_rewrite` = "' . pSQL($rew) . '"');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    public function initContents()
    {
        parent::initContent();
        if ($this->assignCase == 1) {
            $referrer = [];
            $referrer_model = '';
            $referrer_id = '';
            $referrer_name = '';
            if (Tools::getValue('referrer-id') && Tools::getValue('referrer-model') && Tools::getValue('referrer-name')) {
                $referrer_model = Tools::getValue('referrer-model');
                $referrer_id = Tools::getValue('referrer-id');
                $referrer_name = Tools::getValue('referrer-name');
                if (is_numeric($referrer_id)) {
                    $referrer['model'] = $referrer_model;
                    $referrer['id'] = (int)$referrer_id;
                    $referrer['name'] = $referrer_name;
                }
            }
            $this->context->smarty->assign([
                'referrer' => $referrer,
            ]);
        }
    }

    public function initContent()
    {
        if ($this->assignCase == 1) {
            $cmsVar = $this->objectPresenter->present($this->cms);

            $filteredCmsContent = Hook::exec(
                'filterCmsContent',
                ['object' => $cmsVar],
                $id_module = null,
                $array_return = false,
                $check_exceptions = true,
                $use_push = false,
                $id_shop = null,
                $chain = true
            );
            if (!empty($filteredCmsContent['object'])) {
                $cmsVar = $filteredCmsContent['object'];
            }

            $this->context->smarty->assign([
                'cms' => $cmsVar,
            ]);

            if ($this->cms->indexation == 0) {
                $this->context->smarty->assign('nobots', true);
            }

            $this->setTemplate(
                'cms/page',
                ['entity' => 'cms', 'id' => $this->cms->id]
            );
        } elseif ($this->assignCase == 2) {
            $cmsCategoryVar = $this->getTemplateVarCategoryCms();

            $filteredCmsCategoryContent = Hook::exec(
                'filterCmsCategoryContent',
                ['object' => $cmsCategoryVar],
                $id_module = null,
                $array_return = false,
                $check_exceptions = true,
                $use_push = false,
                $id_shop = null,
                $chain = true
            );
            if (!empty($filteredCmsCategoryContent['object'])) {
                $cmsCategoryVar = $filteredCmsCategoryContent['object'];
            }

            $this->context->smarty->assign($cmsCategoryVar);
            $this->setTemplate('cms/category');
        }
        parent::initContent();
    }

}
