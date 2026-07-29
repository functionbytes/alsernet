<?php

require_once(dirname(__FILE__) . '/../../../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../../../init.php');
require_once(dirname(__FILE__) . '/../../alsernetmenu.php');

class alsernetmenumenuModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        
        $menu = new Alsernetmenu();

        switch (Tools::getValue('method')) {


            case 'category':

                $language = Tools::getValue('language');
                $category = Tools::getValue('category');

                if (!$category) {
                    exit;
                }

                die(
                $menu->handleCategory(
                    $category,
                    $language
                )
                );
                break;
            case 'subcategory':

                $category = Tools::getValue('category');
                $subcategory = Tools::getValue('subcategory');

                if (!$subcategory || !$category) {
                    exit;
                }
                die(

                $menu->handleSubcategory(
                    $category,
                    $subcategory
                )
                );
                break;

            case 'mobile':

                $language = Tools::getValue('language');

                die(
                $menu->handleMobile($language)
                );
                break;

        }
        exit;
    }
}   
  
