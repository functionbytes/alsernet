<?php
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Link extends LinkCore
{
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getCategoryLink($category, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false, $es_bread = 0, $es_menu = 0, $es_lateral = 0)
	{
		$ender = Tools::substr($_SERVER['REQUEST_URI'], -1);
        if ($ender == '/') {
			$_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'], '/');
        }
		$uri_multilang = str_replace('/en/', '/', $_SERVER['REQUEST_URI']);
		$uri_multilang2 = str_replace('/fr/', '/', $uri_multilang);
		$uri_multilang3 = str_replace('/de/', '/', $uri_multilang2);
		$uri_multilang4 = str_replace('/pt/', '/', $uri_multilang3);
		$uri_multilang5 = str_replace('/es/', '/', $uri_multilang4);
        $uri_multilang6 = str_replace('/it/', '/', $uri_multilang5);
		$uri_multilang6 = explode('?', $uri_multilang6);
		$uri_multilang6 = $uri_multilang6[0];
		if (!is_array($uri_multilang6)) {
			$uri_multilang6 = explode('/', $uri_multilang6);
		}
		$rewrite_cat = end($uri_multilang6);
		$req_uri2 = explode('-', $rewrite_cat);
		$categorias = $req_uri2;
		// 20230828 - Comentado porque no vemos que se este usando
		// $deporte_id_1 = Db::getInstance()->getValue('SELECT id_category FROM '._DB_PREFIX_.'category_lang WHERE name = "'.$uri_multilang5[1].'"');
		if(!$id_lang){
			$id_lang = Context::getContext()->language->id;
		}
		if (!is_array($uri_multilang6)) {
			$req_uri_ev = explode('/', $uri_multilang6);
		}else{
			$req_uri_ev = $uri_multilang6;
		}


        // ORIGINAL
		// if (isset($req_uri_ev2[2])) {
		// 	$req_uri_ev2 = explode('-', $req_uri_ev[2]);
		// }

        // if (isset($req_uri_ev2[0])) {
        //     $calc_evento = $req_uri_ev2[0];
        // }
        // if(isset($req_uri_ev2[1])){
		//   $depo_evento = $req_uri_ev2[1];
		// }

        // AHORA SERA:
        if (isset($req_uri2[2])) {
            if(isset($req_uri_ev[2])){
                $req_uri2 = explode('-', $req_uri_ev[2]);
            }else{
                $req_uri2 = explode('-', $req_uri_ev[1]);
            }
		}

        // if (isset($req_uri2[0])) {
        //     $calc_evento = $req_uri2[0];
        // }
        // if(isset($req_uri2[1])){
		//   $depo_evento = $req_uri2[1];
		// }


        // if (isset($calc_evento)) {
        //     $es_evento = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND cl.name = "'.$calc_evento.'" AND c.id_parent = 2820');
        // }else{
        //     $es_evento =0;
        // }

        $es_evento =0;

		if ((is_object($category) && isset($uri_multilang6[1]) && count($uri_multilang6) > 2 && $es_bread != 1 && $es_menu != 1 && $es_lateral != 1 && !Tools::getValue('order')) || ($es_evento && $es_bread != 1 && $es_menu != 1 && $es_lateral != 1 && !Tools::getValue('order'))) {
            $category = $category->id_category;
			// if ($es_evento) {
			// 	$deporte_id = 2820;
			// 	$categorias = $req_uri2; // $req_uri_ev2; // AHORA SERA ASI
			// }else{
			// 	$deporte_id = Db::getInstance()->getValue('SELECT id_category FROM '._DB_PREFIX_.'category_lang WHERE name = "'.$uri_multilang5[1].'" AND id_lang = '.Context::getContext()->language->id);
			// }
			// if ($deporte_id <= 11 || $deporte_id == 2821) { //Es deporte, marca o evento
            //     if (count($categorias) == 1) {
            //         $category = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$deporte_id.' AND (cl.link_rewrite = "'.$categorias[0].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[0]).'")'. ' AND c.active = 1 AND id_lang = '.Context::getContext()->language->id);
            //     }else if(count($categorias) == 2){
            //         $padre_id = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$deporte_id.' AND (cl.link_rewrite = "'.$categorias[0].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[0]).'")'. ' AND c.active = 1 AND id_lang = '.Context::getContext()->language->id);
            //         $category = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id.' AND (cl.link_rewrite = "'.$categorias[1].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[1]).'") AND id_lang = '.Context::getContext()->language->id);
            //     }else if(count($categorias) == 3){
            //         $padre_id = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$deporte_id.' AND (cl.link_rewrite = "'.$categorias[0].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[0]).'") AND id_lang = '.Context::getContext()->language->id);
            //         $padre_id_2 = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id.' AND (cl.link_rewrite = "'.$categorias[1].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[1]).'") AND id_lang = '.Context::getContext()->language->id);
            //         $category = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id_2.' AND (cl.link_rewrite = "'.$categorias[2].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[2]).'") AND id_lang = '.Context::getContext()->language->id);
            //     }else if(count($categorias) == 4){
            //         $padre_id = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$deporte_id.' AND (cl.link_rewrite = "'.$categorias[0].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[0]).'") AND id_lang = '.Context::getContext()->language->id);
            //         $padre_id_2 = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id.' AND (cl.link_rewrite = "'.$categorias[1].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[1]).'") AND id_lang = '.Context::getContext()->language->id);
            //         $padre_id_3 = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id_2.' AND (cl.link_rewrite = "'.$categorias[2].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[2]).'") AND id_lang = '.Context::getContext()->language->id);
            //         $category = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id_3.' AND (cl.link_rewrite = "'.$categorias[3].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[3]).'") AND id_lang = '.Context::getContext()->language->id);
            //     }else if(count($categorias) == 5){
            //         $padre_id = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$deporte_id.' AND (cl.link_rewrite = "'.$categorias[0].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[0]).'") AND id_lang = '.Context::getContext()->language->id);
            //         $padre_id_2 = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id.' AND (cl.link_rewrite = "'.$categorias[1].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[1]).'") AND id_lang = '.Context::getContext()->language->id);
            //         $padre_id_3 = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id_2.' AND (cl.link_rewrite = "'.$categorias[2].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[2]).'") AND id_lang = '.Context::getContext()->language->id);
            //         $padre_id_4 = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id_3.' AND (cl.link_rewrite = "'.$categorias[3].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[3]).'") AND id_lang = '.Context::getContext()->language->id);
            //         $category = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = '.$padre_id_4.' AND (cl.link_rewrite = "'.$categorias[4].'" || cl.link_rewrite = "'.str_replace('_','-',$categorias[4]).'") AND id_lang = '.Context::getContext()->language->id);
            //     }
            // }
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);

		if (!is_object($category)) {
			$category = new Category($category, $id_lang);
		}

		if (!$category->id) {
			return;
		}
		$params = array();
		$params['id'] = $category->id;
		$_GET['id'] = $category->id;
		$_POST['id'] = $category->id;
		$_GET['id_category'] = $category->id;
		$_POST['id_category'] = $category->id;
        $params['rewrite'] = (!$alias) ? $category->link_rewrite : $alias;
        $_GET['rewrite'] = (!$alias) ? $category->link_rewrite : $alias;
        $_POST['rewrite'] = (!$alias) ? $category->link_rewrite : $alias;
        $rew = (!$alias) ? $category->link_rewrite : $alias;
		$params['meta_keywords'] =	@Tools::str2url($category->meta_keywords);
		$params['meta_title'] = @Tools::str2url($category->meta_title);
		$selected_filters = is_null($selected_filters) ? '' : $selected_filters;
		if (empty($selected_filters)) {
			$rule = 'category_rule';
		} else {
			$rule = 'layered_rule';
			$params['selected_filters'] = $selected_filters;
		}
                // 20230828 - Comentado porque no vemos que se esten usando en la funcion
		/*$cat_array = array();
		$parent_categories = $this->getAllParentCategories($category->id, $id_lang);
		$parent_categories = is_array($parent_categories) === true ? array_reverse($parent_categories) : $parent_categories;
		$skip_list = array(Configuration::get('PS_HOME_CATEGORY'), Configuration::get('PS_ROOT_CATEGORY'));
		$skip_list[] = $category->id;
		foreach ($parent_categories as $parent_cat) {
			if (!in_array($parent_cat['id_category'], $skip_list)) {
				$cat_array[] = $parent_cat['link_rewrite'];
			}
		}*/

        $dispatcher = Dispatcher::getInstance();
        $papa = '';

		if ($dispatcher->hasKeyword($rule, $id_lang, 'categories', $id_shop)) {
			$cats = array();
			foreach ($category->getParentsCategories($id_lang) as $cat) {
				if (!in_array($cat['id_category'], array(1, 2, $category->id)) && $cat['active'] && $cat['id_category'] != null) {
					$cats[] = ((int)strpos($cat['link_rewrite'], '-') > 0) ? str_replace('-', '_', $cat['link_rewrite']) : $cat['link_rewrite'];//remove root, home and current category from the URL
				}
			}
            $cats = array_reverse($cats);
            if (isset($cats[0])) {
	            $params['parent'] = $cats[0];
	            if ((int)count($cats) > 0) {
	                unset($cats[0]);
	            }
	        }

	        $params['categories'] = implode('-', $cats);
			$params['rewrite'] = ((int)strpos($params['rewrite'], '-') > 0) ? str_replace('-', '_', $params['rewrite']) : $params['rewrite'];

		}else{
            $categorias_prev = str_replace('-'.$rew, '', $rewrite_cat);
            $rewrite_cat2 = explode('-', $rewrite_cat);
            $rewrite_cat_last = end($rewrite_cat2); //ULTIMA DE LAS CATEGORIAS EN EL REWRITE
            $categorias_prev_addis = str_replace('-'.$rewrite_cat_last, '', $rewrite_cat);
            if ($rewrite_cat_last == $rew) {
                $categorias_prev = str_replace('-'.$rewrite_cat_last, '', $rewrite_cat);
                $categorias_prev = str_replace('-'.$rew, '', $categorias_prev);
            }else{
                if (strpos($rewrite_cat, $rew)) {
                    $categorias_prev = str_replace('-'.$rewrite_cat_last, '', $rewrite_cat);
                    $categorias_prev = str_replace('-'.$rew, '', $categorias_prev);
                }
                if($rewrite_cat_last != $rew){
                    if (!Category::getChildren($category->id,$id_lang)) {
                        $categorias_prev = str_replace('-'.$rewrite_cat_last, '', $categorias_prev);
                    }
                    if (strpos($categorias_prev, $rew)) {
                        $categorias_prev = str_replace('-'.$rew, '', $categorias_prev);
                    }
                }else{
                    if (strpos($categorias_prev, $rewrite_cat_last)) {
                        $categorias_prev = str_replace('-'.$rewrite_cat_last, '', $categorias_prev);
                    }
                }
            }
            if ($category->id <= 11 || $category->id_parent <= 11) {
                $params['rewrite'] = $rew;
                $params['categories'] = '';
                $_GET['rewrite'] = $rew;
                $_GET['categories'] = '';
            }else if($rewrite_cat_last != $rew){
                $params['rewrite'] = $rew;
                $params['categories'] = $rewrite_cat;
                $_GET['rewrite'] = $rew;
                $_GET['categories'] = $rewrite_cat;
            }else if(count($rewrite_cat2) > 3){
                $params['rewrite'] = $categorias_prev_addis.'-'.$rew;
                $params['categories'] = '';
                $_GET['rewrite'] = $categorias_prev_addis.'-'.$rew;
                $_GET['categories'] = '';
            }else{
                $params['rewrite'] = $rew;
                $params['categories'] = $categorias_prev;
                $_GET['rewrite'] = $rew;
                $_GET['categories'] = $categorias_prev;
            }
        }
		$r_url = $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow, '', $id_shop);
        $ender = Tools::substr($r_url, -1);
        if ($ender == '/') {
			$r_url = rtrim($r_url, '/');
        }
		return $r_url;
	}
	public function getProductLink(
        $product,
        $alias = null,
        $category = null,
        $ean13 = null,
        $idLang = null,
        $idShop = null,
        $idProductAttribute = null,
        $force_routes = false,
        $relativeProtocol = false,
        $withIdInAnchor = false,
        $extraParams = [],
        bool $addAnchor = true,
        $no_ficha = 0
    ) {
		$ender = Tools::substr($_SERVER['REQUEST_URI'], -1);
        if ($ender == '/') {
			$_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'], '/');
        }
		$req_uri = explode('/', $_SERVER['REQUEST_URI']);
		$rewrite_cat = end($req_uri);
		$req_uri2 = explode('-', $rewrite_cat);
		$id_p = $req_uri2[0];
		if (is_numeric($id_p) && $req_uri[1] != 'm') {
    		$product = new Product($id_p);
        }
        if ($no_ficha == 1) {
    		$product = new Product($product);
        }
        $dispatcher = Dispatcher::getInstance();
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }
        $url = $this->getBaseLink($idShop, null, $relativeProtocol) . $this->getLangLink($idLang, null, $idShop);
        $params = [];
        if (!is_object($product)) {
            if (is_array($product) && isset($product['id_product'])) {
                $params['id'] = $product['id_product'];
            } elseif ((int) $product) {
                $params['id'] = $product;
            } else {
                throw new PrestaShopException('Invalid product vars');
            }
        } else {
            $params['id'] = $product->id;
        }
        if (empty($idProductAttribute)) {
            $idProductAttribute = null;
        }
        $params['id_product_attribute'] = $idProductAttribute;
        if (!$alias) {
            $product = $this->getProductObject($product, $idLang, $idShop);
        }
        $params['rewrite'] = (!$alias) ? $product->getFieldByLang('link_rewrite', $idLang) : $alias;
        if (!$ean13) {
            $product = $this->getProductObject($product, $idLang, $idShop);
        }
        $params['ean13'] = (!$ean13) ? $product->ean13 : $ean13;
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'meta_keywords', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['meta_keywords'] = Tools::str2url($product->getFieldByLang('meta_keywords', $idLang));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'meta_title', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['meta_title'] = Tools::str2url($product->getFieldByLang('meta_title', $idLang));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'manufacturer', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['manufacturer'] = Tools::str2url($product->isFullyLoaded ? $product->manufacturer_name : Manufacturer::getNameById($product->id_manufacturer));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'supplier', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['supplier'] = Tools::str2url($product->isFullyLoaded ? $product->supplier_name : Supplier::getNameById($product->id_supplier));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'price', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['price'] = $product->isFullyLoaded ? $product->price : Product::getPriceStatic($product->id, false, null, 6, null, false, true, 1, false, null, null, null, $product->specificPrice);
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'tags', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['tags'] = Tools::str2url($product->getTags($idLang));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'category', $idShop)) {
            if (!$category) {
                $product = $this->getProductObject($product, $idLang, $idShop);
            }
            $params['category'] = (!$category) ? $product->category : $category;
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'reference', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['reference'] = Tools::str2url($product->reference);
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'categories', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['category'] = (!$category) ? $product->category : $category;
            $cats = [];
            foreach ($product->getParentCategories($idLang) as $cat) {
                if (!in_array($cat['id_category'], Link::$category_disable_rewrite)) {
                    $cats[] = $cat['link_rewrite'];
                }
            }
            $params['categories'] = implode('/', $cats);
        }
        if ($idProductAttribute) {
            $product = $this->getProductObject($product, $idLang, $idShop);
        }
        $anchor = ''; // $addAnchor && $idProductAttribute ? $product->getAnchor((int) $idProductAttribute, (bool) $withIdInAnchor) : '';
        return $url . $dispatcher->createUrl('product_rule', $idLang, array_merge($params, $extraParams), $force_routes, $anchor, $idShop);
    }
    public function getProductListLink(
        $product,
        $alias = null,
        $category = null,
        $ean13 = null,
        $idLang = null,
        $idShop = null,
        $idProductAttribute = null,
        $force_routes = false,
        $relativeProtocol = false,
        $withIdInAnchor = false,
        $extraParams = [],
        bool $addAnchor = true
    ) {
		$product = new Product($product);

        $dispatcher = Dispatcher::getInstance();
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }
        $url = $this->getBaseLink($idShop, null, $relativeProtocol) . $this->getLangLink($idLang, null, $idShop);
        $params = [];
        if (!is_object($product)) {
            if (is_array($product) && isset($product['id_product'])) {
                $params['id'] = $product['id_product'];
            } elseif ((int) $product) {
                $params['id'] = $product;
            } else {
                throw new PrestaShopException('Invalid product vars');
            }
        } else {
            $params['id'] = $product->id;
        }
        if (empty($idProductAttribute)) {
            $idProductAttribute = null;
        }
        $params['id_product_attribute'] = $idProductAttribute;
        if (!$alias) {
            $product = $this->getProductObject($product, $idLang, $idShop);
        }
        $params['rewrite'] = (!$alias) ? $product->getFieldByLang('link_rewrite') : $alias;
        if (!$ean13) {
            $product = $this->getProductObject($product, $idLang, $idShop);
        }
        $params['ean13'] = (!$ean13) ? $product->ean13 : $ean13;
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'meta_keywords', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['meta_keywords'] = Tools::str2url($product->getFieldByLang('meta_keywords'));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'meta_title', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['meta_title'] = Tools::str2url($product->getFieldByLang('meta_title'));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'manufacturer', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['manufacturer'] = Tools::str2url($product->isFullyLoaded ? $product->manufacturer_name : Manufacturer::getNameById($product->id_manufacturer));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'supplier', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['supplier'] = Tools::str2url($product->isFullyLoaded ? $product->supplier_name : Supplier::getNameById($product->id_supplier));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'price', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['price'] = $product->isFullyLoaded ? $product->price : Product::getPriceStatic($product->id, false, null, 6, null, false, true, 1, false, null, null, null, $product->specificPrice);
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'tags', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['tags'] = Tools::str2url($product->getTags($idLang));
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'category', $idShop)) {
            if (!$category) {
                $product = $this->getProductObject($product, $idLang, $idShop);
            }
            $params['category'] = (!$category) ? $product->category : $category;
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'reference', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['reference'] = Tools::str2url($product->reference);
        }
        if ($dispatcher->hasKeyword('product_rule', $idLang, 'categories', $idShop)) {
            $product = $this->getProductObject($product, $idLang, $idShop);
            $params['category'] = (!$category) ? $product->category : $category;
            $cats = [];
            foreach ($product->getParentCategories($idLang) as $cat) {
                if (!in_array($cat['id_category'], Link::$category_disable_rewrite)) {
                    $cats[] = $cat['link_rewrite'];
                }
            }
            $params['categories'] = implode('/', $cats);
        }
        if ($idProductAttribute) {
            $product = $this->getProductObject($product, $idLang, $idShop);
        }
        $anchor = ''; // $addAnchor && $idProductAttribute ? $product->getAnchor((int) $idProductAttribute, (bool) $withIdInAnchor) : '';
        return $url . $dispatcher->createUrl('product_rule', $idLang, array_merge($params, $extraParams), $force_routes, $anchor, $idShop);
    }
	public function getManufacturerCategoryLink($category, $manufacturer, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false, $es_bread = 0, $es_menu = 0)
	{
		if(!$id_lang){
			$id_lang = Context::getContext()->language->id;
		}
		$category = Db::getInstance()->getValue('SELECT c.id_category FROM '._DB_PREFIX_.'category_lang cl, '._DB_PREFIX_.'category c WHERE c.id_category = cl.id_category AND c.id_parent = 2821 AND cl.name = "'.$manufacturer.'" AND cl.id_lang = '.$id_lang);

		if (!$id_lang) {
			$id_lang = Context::getContext()->language->id;
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
		if (!is_object($category)) {
			$category = new Category($category, $id_lang);
		}
		if (!$category->id) {
			return;
		}
		$params = array();
		$params['id'] = $category->id;
		$params['rewrite'] = (!$alias) ? $category->link_rewrite : $alias;
		$params['meta_keywords'] =	@Tools::str2url($category->meta_keywords);
		$params['meta_title'] = @Tools::str2url($category->meta_title);
		$selected_filters = is_null($selected_filters) ? '' : $selected_filters;
		if (empty($selected_filters)) {
			$rule = 'category_rule';
		} else {
			$rule = 'layered_rule';
			$params['selected_filters'] = $selected_filters;
		}
                // 20230828 - Comentado porque no vemos que se esten usando en la funcion
		/*$cat_array = array();
		$parent_categories = $this->getAllParentCategories($category->id, $id_lang);
		$parent_categories = is_array($parent_categories) === true ? array_reverse($parent_categories) : $parent_categories;
		$skip_list = array(Configuration::get('PS_HOME_CATEGORY'), Configuration::get('PS_ROOT_CATEGORY'));
		$skip_list[] = $category->id;
		foreach ($parent_categories as $parent_cat) {
			if (!in_array($parent_cat['id_category'], $skip_list)) {
				$cat_array[] = $parent_cat['link_rewrite'];
			}
		}*/
        $dispatcher = Dispatcher::getInstance();
        $papa = '';
		if ($dispatcher->hasKeyword($rule, $id_lang, 'categories', $id_shop)) {
			$cats = array();
			foreach ($category->getParentsCategories($id_lang) as $cat) {
				if (!in_array($cat['id_category'], array(1, 2, $category->id))) {
					$cats[] = ((int)strpos($cat['link_rewrite'], '-') > 0) ? str_replace('-', '_', $cat['link_rewrite']) : $cat['link_rewrite'];//remove root, home and current category from the URL
				}
			}
            $cats = array_reverse($cats);
            if (isset($cats[0])) {
	            $params['parent'] = $cats[0];
	            if ((int)count($cats) > 0) {
	                unset($cats[0]);
	            }
	        }
			$params['categories'] = implode('-', $cats);
            $params['rewrite'] = ((int)strpos($params['rewrite'], '-') > 0) ? str_replace('-', '_', $params['rewrite']) : $params['rewrite'];
        }
		$r_url = $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow, '', $id_shop);
		return $r_url;
	}
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getAllParentCategories($id_current = null, $id_lang = null)
	{
		$context = Context::getContext()->cloneContext();
		$context->shop = clone($context->shop);
		if (is_null($id_lang)) {
			$id_lang = $context->language->id;
		}
		$categories = null;
		$cat_wo_parent = count(Category::getCategoriesWithoutParent());
		$multishop_feature = Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
		if ($cat_wo_parent > 1 && $multishop_feature && count(Shop::getShops(true, null, true)) != 1) {
			$context->shop->id_category = Category::getTopCategory()->id;
		}
		elseif (!$context->shop->id) {
			$context->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
		}
		$id_shop = $context->shop->id;
		while (true) {
			$sql = '
			SELECT c.*, cl.*
			FROM `'._DB_PREFIX_.'category` c
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
				ON (c.`id_category` = cl.`id_category`
				AND `id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')';
			if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP)
				$sql .= '
			LEFT JOIN `'._DB_PREFIX_.'category_shop` cs
				ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = '.(int)$id_shop.')';
			$sql .= '
			WHERE c.`id_category` = '.(int)$id_current;
			if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP) {
				$sql .= '
				AND cs.`id_shop` = '.(int)$context->shop->id;
			}
			$root_category = Category::getRootCategory();
			$f_active = Shop::isFeatureActive();
			$submit_id_cat = Tools::isSubmit('id_category');
			$g_id_cat = (int)Tools::getValue('id_category');
			$r_cat_id = (int)$root_category->id;
			$c_id_cat = (int)$context->shop->id_category;
			if ($f_active && Shop::getContext() == Shop::CONTEXT_SHOP && (!$submit_id_cat || $g_id_cat == $r_cat_id || $r_cat_id == $c_id_cat)) {
				$sql .= ' AND c.`id_parent` != 0';
			}
			$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
			if (Tools::getIsset($result[0])) {
				$categories[] = $result[0];
			}
			else if (!$categories) {
				$categories = array();
			}
			if (!$result || ($result[0]['id_category'] == $context->shop->id_category)) {
				return $categories;
			}
			$id_current = $result[0]['id_parent'];
		}
	}
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getPaginationLink($type, $id_object, $nb = false, $sort = false, $pagination = false, $array = false)
	{
		if (!$type && !$id_object) {
			$method_name = 'get'.Dispatcher::getInstance()->getController().'Link';
			if (method_exists($this, $method_name) && Tools::getIsset(Tools::getValue('id_'.Dispatcher::getInstance()->getController()))) {
				$type = Dispatcher::getInstance()->getController();
				$id_object = Tools::getValue('id_'.$type);
			}
		}
		if ($type && $id_object) {
			$url = $this->{'get'.$type.'Link'}($id_object, null);
		} else {
			if (Tools::getIsset(Context::getContext()->controller->php_self)) {
				$name = Context::getContext()->controller->php_self;
			} else {
				$name = Dispatcher::getInstance()->getController();
			}
			if ($name == 'category') {
				$url = $this->getCategoryLink(Tools::getValue('id_category'));
			} else {
				$url = $this->getPageLink($name);
			}
		}
		$vars = array();
		$vars_nb = array('n', 'search_query');
		$vars_sort = array('orderby', 'orderway');
		$vars_pagination = array('p');
		foreach ($_GET as $k => $value) {
			if ($k != 'id_'.$type && $k != $type.'_rewrite' && $k != 'controller') {
				if (Configuration::get('PS_REWRITING_SETTINGS') && ($k == 'isolang' || $k == 'id_lang')) {
					continue;
				}
				$if_nb = (!$nb || ($nb && !in_array($k, $vars_nb)));
				$if_sort = (!$sort || ($sort && !in_array($k, $vars_sort)));
				$if_pagination = (!$pagination || ($pagination && !in_array($k, $vars_pagination)));
				if ($if_nb && $if_sort && $if_pagination) {
					if (!is_array($value)) {
						$vars[urlencode($k)] = $value;
					} else {
						foreach (explode('&', http_build_query(array($k => $value), '', '&')) as $val) {
							$data = explode('=', $val);
							$vars[urldecode($data[0])] = $data[1];
						}
					}
				}
			}
		}
		if ($name == 'category') {
			unset($vars['categories_rewrite']);
			unset($vars['category_rewrite']);
		}
			$manuf_uri = explode('/', $_SERVER['REQUEST_URI']);
			$manuf_end = end($manuf_uri);
			if (preg_match('/\?/', $manuf_end)) {
				$manuf_end = explode('?', $manuf_end);
				$manuf_end = $manuf_end[0];
				$clearify_request = str_replace('-', ' ', $manuf_end);
				$manu_existance = (int)$this->getKeyExistanceManuf($clearify_request);
				$supp_existance = (int)$this->getKeyExistanceSup($clearify_request);
				if ($manu_existance > 0) {
					$vars['manufacturer_rewrite'] = $manuf_end;
				}
				elseif ($supp_existance > 0) {
					$vars['supplier_rewrite'] = $manuf_end;
				}
			} else {
				$clearify_request = str_replace('-', ' ', $manuf_end);
				$manu_existance = (int)$this->getKeyExistanceManuf($clearify_request);
				$supp_existance = (int)$this->getKeyExistanceSup($clearify_request);
				if ($manu_existance > 0) {
					$vars['manufacturer_rewrite'] = $manuf_end;
				}
				elseif ($supp_existance > 0) {
					$vars['supplier_rewrite'] = $manuf_end;
				}
			}
		if (!$array)
			if (count($vars))
				return $url.(($this->allow == 1 || $url == $this->url) ? '?' : '&').http_build_query($vars, '', '&');
			else
				return $url;
		$vars['requestUrl'] = $url;
		if ($type && $id_object) {
			$vars['id_'.$type] = (is_object($id_object) ? (int)$id_object->id : (int)$id_object);
		}
		if (!$this->allow == 1) {
			$vars['controller'] = Dispatcher::getInstance()->getController();
		}
		if ($name == 'newproducts' || $name == 'pricesdrop' || $name == 'bestsales') {
				if (preg_match('/index/', $vars['requestUrl'])) {
					if (array_key_exists('p', $vars)) {
						$get_controller_page = Context::getContext()->controller->php_self;
						$old_url = $vars['requestUrl'];
						$req_uri_new = explode('index', $old_url);
						$req_uri_new = $req_uri_new[0];
						$vars['requestUrl'] = $req_uri_new.$get_controller_page;
						unset($vars['category_rewrite']);
					} else {
						$get_controller_page = Context::getContext()->controller->php_self;
						$vars['category_rewrite'] = $get_controller_page;
						$old_url = $vars['requestUrl'];
						$req_uri_new = explode('index', $old_url);
						$req_uri_new = $req_uri_new[0];
						$vars['requestUrl'] = $req_uri_new.$vars['category_rewrite'];
						unset($vars['category_rewrite']);
					}
				}
		}
		return $vars;
	}
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getManufacturerLink($manufacturer, $alias = null, $id_lang = null, $id_shop = null, $relative_protocol = false)
	{
		if (!$id_lang) {
			$id_lang = Context::getContext()->language->id;
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
		$dispatcher = Dispatcher::getInstance();
		if (!is_object($manufacturer)) {
			// $d_man_rule_keywords = $dispatcher->hasKeyword('manufacturer_rule', $id_lang, 'meta_keywords', $id_shop);
			// $d_man_rule_title = $dispatcher->hasKeyword('manufacturer_rule', $id_lang, 'meta_title', $id_shop);
            $d_man_rule_keywords = $dispatcher->hasKeyword('manufacturer_short_rule', $id_lang, 'meta_keywords', $id_shop);
			$d_man_rule_title = $dispatcher->hasKeyword('manufacturer_short_rule', $id_lang, 'meta_title', $id_shop);

			if ($alias !== null && !$d_man_rule_keywords && !$d_man_rule_title) {
				$man_rewrite = array('id' => (int)$manufacturer, 'rewrite' => (string)$alias);
				// return $url.$dispatcher->createUrl('manufacturer_rule', $id_lang, $man_rewrite, $this->allow, '', $id_shop);
                return $url.$dispatcher->createUrl('manufacturer_short_rule', $id_lang, $man_rewrite, $this->allow, '', $id_shop);
			}
			$manufacturer = new Manufacturer($manufacturer, $id_lang);
		}
		$link_rewrite = (!$alias) ? $manufacturer->link_rewrite : $alias;
		$params = array();
		// $params['id'] = $manufacturer->id;
		$params['id_manufacturer'] = $manufacturer->id;
		// if (isset($_POST['deporte'])) {
		// 	$params['deporte'] = $_POST['deporte'];
		// }
		$params['rewrite'] = $link_rewrite;
		// $params['meta_keywords'] =	Tools::str2url($manufacturer->meta_keywords);
		// $params['meta_title'] = Tools::str2url($manufacturer->meta_title);
		$man_pattern = '/.*?([0-9]+)\_([_a-zA-Z0-9-\pL]*)/';
		preg_match($man_pattern, $_SERVER['REQUEST_URI'], $url_array);
		// if (!empty($url_array)) {
		// 	return $url.'manufacturer/'.$dispatcher->createUrl('manufacturer_rule', $id_lang, $params, $this->allow, '', $id_shop);
		// } else {
		// 	return $url.$dispatcher->createUrl('manufacturer_rule', $id_lang, $params, $this->allow, '', $id_shop);
		// }
        return $url.$dispatcher->createUrl('manufacturer_short_rule', $id_lang, $params, $this->allow, '', $id_shop);
	}
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getSupplierLink($supplier, $alias = null, $id_lang = null, $id_shop = null, $relative_protocol = false)
	{
		if (!$id_lang) {
			$id_lang = Context::getContext()->language->id;
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
		$dispatcher = Dispatcher::getInstance();
		if (!is_object($supplier)) {
			$sup_rule_keywords = $dispatcher->hasKeyword('supplier_rule', $id_lang, 'meta_keywords', $id_shop);
			if ($alias !== null && !$sup_rule_keywords && !$dispatcher->hasKeyword('supplier_rule', $id_lang, 'meta_title', $id_shop)) {
				return $url.$dispatcher->createUrl('supplier_rule', $id_lang, array('id' => (int)$supplier, 'rewrite' => (string)$alias),
				$this->allow, '', $id_shop);
			}
			$supplier = new Supplier($supplier, $id_lang);
		}
		$params = array();
		$params['id'] = $supplier->id;
		$params['rewrite'] = (!$alias) ? $supplier->link_rewrite : $alias;
		$params['meta_keywords'] =	Tools::str2url($supplier->meta_keywords);
		$params['meta_title'] = Tools::str2url($supplier->meta_title);
		$sup_pattern = '/.*?([0-9]+)\_\_([_a-zA-Z0-9-\pL]*)/';
		preg_match($sup_pattern, $_SERVER['REQUEST_URI'], $sup_array);
		if (!empty($sup_array)) {
			return $url.'supplier/'.$dispatcher->createUrl('supplier_rule', $id_lang, $params, $this->allow, '', $id_shop);
		} else {
			return $url.$dispatcher->createUrl('supplier_rule', $id_lang, $params, $this->allow, '', $id_shop);
		}
	}
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getLanguageLink($id_lang, Context $context = null)
	{

		if (!$context) {
			$context = Context::getContext();
		}
		$params = $_GET;
		unset($params['isolang'], $params['controller']);
		if (!$this->allow) {
			$params['id_lang'] = $id_lang;
		} else {
			unset($params['id_lang']);
		}
		$controller = Dispatcher::getInstance()->getController();
		if (!empty(Context::getContext()->controller->php_self)) {
			$controller = Context::getContext()->controller->php_self;
		}
		$def_page = (int)$this->checkKeyExistance($controller);
		if ($controller == 'manufacturer') {
			$manuf_uri = explode('/', $_SERVER['REQUEST_URI']);
			$manuf_end = end($manuf_uri);
			$clearify_request = str_replace('-', ' ', $manuf_end);
			$manu_existance = (int)$this->getKeyExistanceManuf($clearify_request);
			if ($manu_existance > 0) {
				$params['id_manufacturer'] = $manu_existance;
			}
		}
		elseif ($controller == 'supplier') {
			$supp_uri = explode('/', $_SERVER['REQUEST_URI']);
			$supp_end = end($supp_uri);
			$clearify_request = str_replace('-', ' ', $supp_end);
			$supp_existance = (int)$this->getKeyExistanceSup($clearify_request);
			if ($supp_existance > 0) {
				$params['id_supplier'] = $supp_existance;
			}
		}
		elseif ($controller == 'category' && isset($params['category_rewrite']) && empty($params['category_rewrite'])) {
			$this->request_uri = $_SERVER['REQUEST_URI'];
			if (preg_match('/\?/', $this->request_uri)) {
				$uri_split_w_q = explode('/', $this->request_uri);
				$uri_split_w_q = array_filter($uri_split_w_q);
				$uri_split_w_q = end($uri_split_w_q);
				$uri_split_w_q = explode('?', $uri_split_w_q);
				$uri_split_w_q = $uri_split_w_q[0];
				$_id = (int)$this->getCategoryId($uri_split_w_q, $id_lang);
			} else {
				$uri_split = explode('/', $this->request_uri);
				$uri_split = array_filter($uri_split);
				$uri_split = end($uri_split);
				$_id = (int)$this->getCategoryId($uri_split, $id_lang);
			}
			if ($_id > 0) {
				$params['id_category'] = (int)$_id;
			}
		}
		elseif ($controller == 'category' && isset($params['category_rewrite']) && !empty($params['category_rewrite'])) {
			$allow_accented_chars = (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
			if ($allow_accented_chars > 0) {
				$id_category = (int)Tools::getValue('id_category');
				if ($id_category > 0) {
					$params['id_category'] = $id_category;
				}
			}
		}
		elseif ($controller == 'product' && isset($params['product_rewrite']) && empty($params['product_rewrite'])) {
			$this->request_uri = $_SERVER['REQUEST_URI'];
			$uri_split = explode('/', $this->request_uri);
			$uri_split = array_filter($uri_split);
			$uri_split = end($uri_split);
			if (preg_match('/html/', $uri_split)) {
				$uri_split = str_replace('.html', '', $uri_split);
			}
			$uri_split = explode('-', $uri_split); //Añadido JOSE (addis)
			$_id = $uri_split[0]; //Modificado JOSE --> Cambia $uri_split por $uri_split[0]
			if ($_id > 0) {
				$params['id'] = (int)$_id; //Añadido JOSE
				$params['id_product'] = (int)$_id;
			}elseif ($_id <= 0 && preg_match('/\?/', $this->request_uri)) {
				$_uri_with_q = explode('?', $this->request_uri);
				$_uri_with_q = explode('/', $_uri_with_q[0]);
				$_uri_with_q = end($_uri_with_q);
				$_id = (int)$this->getProductExistance($_uri_with_q);
				if ($_id > 0) {
					$params['id'] = (int)$_id; //Añadido JOSE
					$params['id_product'] = (int)$_id;
				}
			}
		}
		elseif ($controller == 'product' && isset($params['product_rewrite']) && !empty($params['product_rewrite'])) {
			$allow_accented_chars = (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
			if ($allow_accented_chars > 0) {
				$id_product = (int)Tools::getValue('id_product');
				if ($id_product > 0) {
					$params['id'] = $id_product;
					$params['id_product'] = $id_product;
				}
			}
		}
		elseif ($controller == 'cms' && isset($params['cms_rewrite']) && !empty($params['cms_rewrite'])) {
			$allow_accented_chars = (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
			if ($allow_accented_chars > 0) {
				$id_cms = (int)Tools::getValue('id_cms');
				if ($id_cms > 0) {
					$params['id_cms'] = $id_cms;
				}
			}
		}
		elseif ($controller == 'product' && isset($params['category_rewrite']) && !empty($params['category_rewrite'])) {
            $this->request_uri = $_SERVER['REQUEST_URI'];
            $prod_pattern = '/.*?\/([0-9]+)\-([_a-zA-Z0-9-\pL]*)/';
            preg_match($prod_pattern, $this->request_uri, $url_array);
            if (isset($url_array[1]) && (int)$url_array[1] > 0) {
				$params['id_product'] = (int)$url_array[1];
                unset($params['category_rewrite']);
            }
		}
		elseif ($controller == 'cms' && isset($params['cms_rewrite']) && empty($params['cms_rewrite'])) {
			$this->request_uri = $_SERVER['REQUEST_URI'];
			$uri_split = explode('/', $this->request_uri);
			$uri_split = array_filter($uri_split);
			$uri_split = end($uri_split);
			$_id = (int)$this->getKeyExistanceCMS($uri_split);
			if ($_id > 0) {
				$params['id_cms'] = (int)$_id;
			}
			elseif ($_id <= 0 && preg_match('/\?/', $this->request_uri) && isset($params['SubmitCurrency'])) {
				$_uri_cms_clear = explode('?', $this->request_uri);
				$_uri_cms_clear = explode('/', $_uri_cms_clear[0]);
				$_uri_cms_clear = end($_uri_cms_clear);
				$_id = (int)$this->getKeyExistanceCMS($_uri_cms_clear);
				if ($_id > 0) {
					$params['id_cms'] = (int)$_id;
				}
			}
		}
        elseif ($controller == 'cms' && isset($params['category_rewrite']) && !empty($params['category_rewrite'])) {
            $this->request_uri = $_SERVER['REQUEST_URI'];
			$uri_split = explode('/', $this->request_uri);
			$uri_split = array_filter($uri_split);
			$uri_split = end($uri_split);
			$_id = (int)$this->getKeyExistanceCMS($uri_split);
			if ($_id > 0) {
				$params['id_cms'] = (int)$_id;
			}
        }
        elseif ($controller == 'cms' && !isset($params['category_rewrite'])) {
            $this->request_uri = $_SERVER['REQUEST_URI'];
			$uri_split = explode('/', $this->request_uri);
			$uri_split = array_filter($uri_split);
			$uri_split = end($uri_split);
			$_id = (int)$this->getKeyExistanceCMS($uri_split);
			if ($_id > 0) {
				$params['id_cms'] = (int)$_id;
			}
        }
		if ($controller == 'supplier' && isset($params['supplier_rewrite']) && !empty($params['supplier_rewrite'])) {
			$allow_accented_chars = (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
			if ($allow_accented_chars > 0) {
				$id_supp = (int)Tools::getValue('id_supplier');
				if ($id_supp > 0) {
					$params['id_supplier'] = $id_supp;
				}
			}
		}
		if ($controller == 'manufacturer' && isset($params['manufacturer_rewrite']) && !empty($params['manufacturer_rewrite'])) {
			$allow_accented_chars = (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
			if ($allow_accented_chars > 0) {
				$id_manufacturer = (int)Tools::getValue('id_manufacturer');
				if ($id_manufacturer > 0) {
					$params['id_manufacturer'] = $id_manufacturer;
				}
			}
		}
		if ($controller == 'list' && isset($params['module']) && $params['module'] == 'productlookbooks') {
			unset($params['category_rewrite']);
			unset($params['product_rewrite']);
		}
		elseif ($controller == 'display' && isset($params['module']) && $params['module'] == 'productlookbooks') {
			unset($params['category_rewrite']);
			unset($params['product_rewrite']);
		}
		if ($controller == 'product' && isset($params['id_product'])) {
			unset($params['id_category']);
			return $this->getProductLink((int)$params['id_product'], null, null, null, (int)$id_lang);
		}
		elseif ($controller == 'manufacturerdeporte' && isset($params['id_deporte'])) {
			return $this->getMarcasDeporteLink((int)$params['id_deporte'], null, (int)$id_lang);
		}
		elseif ($controller == 'category' && isset($params['id_category'])) {
			return $this->getCategoryLink((int)$params['id_category'], null, (int)$id_lang);
		}
		elseif ($controller == 'supplier' && isset($params['id_supplier'])) {
			return $this->getSupplierLink((int)$params['id_supplier'], null, (int)$id_lang);
		}
		elseif ($controller == 'manufacturer' && isset($params['id_manufacturer'])) {
			return $this->getManufacturerLink((int)$params['id_manufacturer'], null, (int)$id_lang);
		}
		elseif ($controller == 'boletines' && isset($params['id_deporte'])) {
			return $this->getBoletinesDeporteLink((int)$params['id_deporte'], null, (int)$id_lang);
		}
		elseif ($controller == 'cms' && isset($params['id_cms'])) {
			return $this->getCMSLink((int)$params['id_cms'], null, false, (int)$id_lang);
		}
		elseif ($controller == 'cms' && isset($params['id_cms_category'])) {
			return $this->getCMSCategoryLink((int)$params['id_cms_category'], null, (int)$id_lang);
		}
		elseif ($def_page > 0 && !isset($params['id'])) {
			return $this->getPageLink($controller, null, $id_lang, $params);
		}
		elseif (isset($params['fc']) && $params['fc'] == 'module')
		{
			$module = Validate::isModuleName(Tools::getValue('module')) ? Tools::getValue('module') : '';
			if ($controller == "appagebuilderhome") {
				$linkRewrive = explode('/', $_SERVER['REQUEST_URI']);
	            $linkRewrive = rtrim(end($linkRewrive), '.html');
	            if (strpos($linkRewrive, '?')) {
	                $temp_str = explode("?", $linkRewrive);
	                $linkRewrive = $temp_str[0];
	                $linkRewrive = rtrim($linkRewrive, '.html');
	            }
				$id_appagebuilder_profiles = Db::getInstance()->getValue("SELECT id_appagebuilder_profiles FROM "._DB_PREFIX_."appagebuilder_profiles_lang WHERE friendly_url = '".$linkRewrive."'");
				$rewrite_profile_lang = Db::getInstance()->executeS("SELECT friendly_url FROM "._DB_PREFIX_."appagebuilder_profiles_lang WHERE id_appagebuilder_profiles = ".$id_appagebuilder_profiles);
				$params['id_appagebuilder_profiles'] = $id_appagebuilder_profiles;
				unset($_GET['category_rewrite']);
				unset($_GET['module']);
			}
			if (!empty($module)) {
				unset($params['fc'], $params['module']);
				return $this->getModuleLink($module, $controller, $params, null, (int)$id_lang);
			}
		}
		return $this->getPageLink($controller, null, $id_lang, $params);
	}
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getPageLink($controller, $ssl = null, $idLang = null, $request = null, $requestUrlEncode = false, $idShop = null, $relativeProtocol = false)
    {
		if ($controller == 'page-not-found') {
			$controller = 'pagenotfound';
		}
        $p = strpos($controller, '&');
        if ($p !== false) {
            $request = substr($controller, $p + 1);
            $requestUrlEncode = false;
            $controller = substr($controller, 0, $p);
        }
        $controller = Tools::strReplaceFirst('.php', '', $controller);
        if (!$idLang) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if (is_array($request)) {
            if (isset($request['module'])) {
                unset($request['module']);
            }
            if (isset($request['controller'])) {
                unset($request['controller']);
            }
        } else {
            $request = html_entity_decode($request);
            if ($requestUrlEncode) {
                $request = urlencode($request);
            }
            parse_str($request, $request);
        }
        if ($controller === 'cart' && (!empty($request['add']) || !empty($request['delete'])) && Configuration::get('PS_TOKEN_ENABLE')) {
            $request['token'] = Tools::getToken(false);
        }
		$pm_advancedsearch_module_exists = (int)Module::isEnabled('pm_advancedsearch4');
		if (empty($request) && $pm_advancedsearch_module_exists > 0) {
			if (isset($_GET['id_search'])) {
				$request['id_search'] = $_GET['id_search'];
				$request['as4_sq'] = $_GET['as4_sq'];
			}
			elseif (isset($_GET['id_seo'])) {
				$request['id_seo'] = $_GET['id_seo'];
				$request['seo_url'] = $_GET['seo_url'];
			}
		}
        $uriPath = Dispatcher::getInstance()->createUrl($controller, $idLang, $request, false, '', $idShop);
        return $this->getBaseLink($idShop, $ssl, $relativeProtocol).$this->getLangLink($idLang, null, $idShop).ltrim($uriPath, '/');
    }

	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    private function checkKeyExistance($controller)
	{
			$sql = 'SELECT id_meta
					FROM '._DB_PREFIX_.'meta
					WHERE page = "'.pSQL($controller).'"';
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
	}
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    private function getKeyExistanceManuf($request)
	{
		$sql = 'SELECT `id_manufacturer`
					FROM '._DB_PREFIX_.'manufacturer
					WHERE `name` LIKE "'.pSQL($request).'"';
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
	}
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    private function getKeyExistanceSup($request)
	{
		$sql = 'SELECT `id_supplier`
					FROM '._DB_PREFIX_.'supplier
					WHERE `name` LIKE "'.pSQL($request).'"';
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
	}

	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    private function getCategoryId($request, $id_lang)
	{
		$id_shop = Context::getContext()->shop->id;
		$sql = 'SELECT id_category FROM '._DB_PREFIX_.'category_lang
				WHERE link_rewrite = "'.pSQL($request).'" AND id_lang = '.(int)$id_lang.' AND id_shop = '.(int)$id_shop;
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
	}

	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    private function getProductExistance($request)
	{
		$id_lang = Context::getContext()->language->id;
		$id_shop = Context::getContext()->shop->id;
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT `id_product`
				FROM '._DB_PREFIX_.'product_lang
				WHERE `link_rewrite` = "'.pSQL($request).'"'.'
				AND `id_lang` = '.(int)$id_lang.'
				AND `id_shop` = '.(int)$id_shop);
	}

	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    private function getKeyExistanceCMS($request)
	{
		$id_lang = Context::getContext()->language->id;
		$id_shop = Context::getContext()->shop->id;
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT `id_cms`
				FROM '._DB_PREFIX_.'cms_lang
				WHERE `link_rewrite` = "'.pSQL($request).'"'.'
				AND `id_lang` = '.(int)$id_lang.'
				AND `id_shop` = '.(int)$id_shop);
	}
    /*
    * module: removeiso
    * date: 2022-03-29 10:33:39
    * version: 1.4.3
    */

    /*
    * module: alvarezeventos
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    // public function getCategoryLinkEventos($id_cat, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false)
	// {
	// 	$logger = new FileLogger(0);
	// 	$logger->setFilename(_PS_ROOT_DIR_."/log/debug.log");
	// 	if (!$id_lang) {
	// 		$id_lang = Context::getContext()->language->id;
	// 	}
	// 	$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
	// 	if (!is_object($id_cat)) {
	// 		$category = new Category((int)$id_cat);
	// 	}
	// 	$params = array();
	// 	$params['id'] = $category->id;
	// 	$params['rewrite'] = (!$alias) ? $category->link_rewrite : $alias;
	// 	$params['meta_keywords'] =	@Tools::str2url($category->meta_keywords);
	// 	$params['meta_title'] = @Tools::str2url($category->meta_title);
	// 	$selected_filters = is_null($selected_filters) ? '' : $selected_filters;
	// 	if (empty($selected_filters)) {
	// 		$rule = 'category_rule';
	// 	} else {
	// 		$rule = 'layered_rule';
	// 		$params['selected_filters'] = $selected_filters;
	// 	}
    //     $dispatcher = Dispatcher::getInstance();
	// 	if ($dispatcher->hasKeyword($rule, $id_lang, 'categories', $id_shop)) {
	// 		$cats = array();
	// 		foreach ($category->getParentsCategories($id_lang) as $cat) {
	// 			if (!in_array($cat['id_category'], array(1, 2, $category->id))) {
	// 				$cats[] = ((int)strpos($cat['link_rewrite'], '-') > 0) ? str_replace('-', '_', $cat['link_rewrite']) : $cat['link_rewrite'];
	// 			}
	// 		}
    //         $cats = array_reverse($cats);
    //         $params['parent'] = $cats[0];
    //         if ((int)count($cats) > 0) {
    //             unset($cats[0]);
    //         }
	// 		$params['categories'] = implode('-', $cats);
    //         $params['rewrite'] = ((int)strpos($params['rewrite'], '-') > 0) ? str_replace('-', '_', $params['rewrite']) : $params['rewrite'];
	// 	}
	// 	$r_url = $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow, '', $id_shop);
	// 	return $r_url;
	// }
	/*
    * module: prettyurls
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    // public function getAllParentCategoriesEventos($id_current = null, $id_lang = null)
	// {
	// 	$context = Context::getContext()->cloneContext();
	// 	$context->shop = clone($context->shop);
	// 	if (is_null($id_lang)) {
	// 		$id_lang = $context->language->id;
	// 	}
	// 	$categories = null;
	// 	$cat_wo_parent = count(Category::getCategoriesWithoutParent());
	// 	$multishop_feature = Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
	// 	if ($cat_wo_parent > 1 && $multishop_feature && count(Shop::getShops(true, null, true)) != 1) {
	// 		$context->shop->id_category = Category::getTopCategory()->id;
	// 	}
	// 	elseif (!$context->shop->id) {
	// 		$context->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
	// 	}
	// 	$id_shop = $context->shop->id;
	// 	while (true) {
	// 		$sql = '
	// 		SELECT c.*, cl.*
	// 		FROM `'._DB_PREFIX_.'category` c
	// 		LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
	// 			ON (c.`id_category` = cl.`id_category`
	// 			AND `id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')';
	// 		if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP)
	// 			$sql .= '
	// 		LEFT JOIN `'._DB_PREFIX_.'category_shop` cs
	// 			ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = '.(int)$id_shop.')';
	// 		$sql .= '
	// 		WHERE c.`id_category` = '.(int)$id_current;
	// 		if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP) {
	// 			$sql .= '
	// 			AND cs.`id_shop` = '.(int)$context->shop->id;
	// 		}
	// 		$root_category = Category::getRootCategory();
	// 		$f_active = Shop::isFeatureActive();
	// 		$submit_id_cat = Tools::isSubmit('id_category');
	// 		$g_id_cat = (int)Tools::getValue('id_category');
	// 		$r_cat_id = (int)$root_category->id;
	// 		$c_id_cat = (int)$context->shop->id_category;
	// 		if ($f_active && Shop::getContext() == Shop::CONTEXT_SHOP && (!$submit_id_cat || $g_id_cat == $r_cat_id || $r_cat_id == $c_id_cat)) {
	// 			$sql .= ' AND c.`id_parent` != 0';
	// 		}
	// 		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
	// 		if (Tools::getIsset($result[0])) {
	// 			$categories[] = $result[0];
	// 		}
	// 		else if (!$categories) {
	// 			$categories = array();
	// 		}
	// 		if (!$result || ($result[0]['id_category'] == $context->shop->id_category)) {
	// 			return $categories;
	// 		}
	// 		$id_current = $result[0]['id_parent'];
	// 	}
	// }
    /*
    * module: removeiso
    * date: 2022-05-03 15:50:52
    * version: 1.4.3
    */
    public function getLangLink($id_lang = null, Context $context = null, $id_shop = null, $controller = null)
    {
        if (Language::isMultiLanguageActivated()) {
            if (!$id_lang) {
                if (is_null($context)) {
                    $context = Context::getContext();
                }
                $id_lang = $context->language->id;
            }
            if ($id_lang == Configuration::get('PS_LANG_DEFAULT', null, null, $id_shop) && $controller != 'appagebuilderhome') {
                return '';
            }
        }
        return parent::getLangLink($id_lang, $context, $id_shop);
    }
    /*
    * module: alvarezmarcasdeporte
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getMarcasDeporteLink($id_deporte, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false)
	{
		if (!$id_lang) {
			$id_lang = Context::getContext()->language->id;
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
		if (!is_object($id_deporte)) {
			$category = new Category((int)$id_deporte);
		}
		$params = array();
		$params['id_deporte'] = $category->id;
		$params['deporte'] = (!$alias) ? $category->link_rewrite : $alias;

		if (Context::getContext()->language->iso_code == 'es') {
			$rule = 'marcas_deporte_rule_es';
		}else if(Context::getContext()->language->iso_code == 'en'){
			$rule = 'marcas_deporte_rule_en';
		}else if(Context::getContext()->language->iso_code == 'fr'){
			$rule = 'marcas_deporte_rule_fr';
		}else if(Context::getContext()->language->iso_code == 'pt'){
			$rule = 'marcas_deporte_rule_pt';
		}else if(Context::getContext()->language->iso_code == 'de'){
			$rule = 'marcas_deporte_rule_de';
		}else if(Context::getContext()->language->iso_code == 'it'){
			$rule = 'marcas_deporte_rule_it';
		}
		if (!$params['deporte']) {
			$rule = 'marcas_deporte_rule_clean';
		}
		$r_url = $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow, '', $id_shop);
		$r_url_2 = explode('?', $r_url);
		return $r_url_2[0];
	}
	/*
    * module: alvarezmarcasdeporte
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getMarcaDeporteLink($id_deporte, $id_marca, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false)
	{
		if (!$id_lang) {
			$id_lang = Context::getContext()->language->id;
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
		if (!is_object($id_deporte)) {
			$category = new Category((int)$id_deporte);
		}
		$params = array();
		$params['id_deporte'] = $category->id;
		$params['deporte'] = (!$alias) ? $category->link_rewrite : $alias;
		$params['id_manufacturer'] = $id_marca;
		$manufacturer = new Manufacturer((int)$id_marca);
		$params['nombre_manu'] = $manufacturer->link_rewrite;
		$params['manufacturer_rewrite'] = $manufacturer->link_rewrite;

		if (Context::getContext()->language->iso_code == 'es') {
			$rule = 'marca_selec_deporte_rule_es';
		}else if(Context::getContext()->language->iso_code == 'en'){
			$rule = 'marca_selec_deporte_rule_en';
		}else if(Context::getContext()->language->iso_code == 'fr'){
			$rule = 'marca_selec_deporte_rule_fr';
		}else if(Context::getContext()->language->iso_code == 'pt'){
			$rule = 'marca_selec_deporte_rule_pt';
		}else if(Context::getContext()->language->iso_code == 'de'){
			$rule = 'marca_selec_deporte_rule_de';
		}else if(Context::getContext()->language->iso_code == 'it'){
			$rule = 'marca_selec_deporte_rule_it';
		}
		$r_url = $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow, '', $id_shop);
		$r_url_2 = explode('?', $r_url);
		return $r_url_2[0];
	}
	/*
    * module: alvarezmarcasdeporte
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getNovedadesDeporteLink($id_category, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false)
	{
		if (!$id_lang) {
			$id_lang = Context::getContext()->language->id;
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
		if (!is_object($id_category)) {
			$category = new Category((int)$id_category);
		}
		$params = array();
		$params['id_category'] = $category->id;
		$params['deporte'] = (!$alias) ? $category->link_rewrite : $alias;

		if (Context::getContext()->language->iso_code == 'es') {
			$rule = 'novedades_deporte_rule_es';
		}else if(Context::getContext()->language->iso_code == 'en'){
			$rule = 'novedades_deporte_rule_en';
		}else if(Context::getContext()->language->iso_code == 'fr'){
			$rule = 'novedades_deporte_rule_fr';
		}else if(Context::getContext()->language->iso_code == 'pt'){
			$rule = 'novedades_deporte_rule_pt';
		}else if(Context::getContext()->language->iso_code == 'de'){
			$rule = 'novedades_deporte_rule_de';
		}else if(Context::getContext()->language->iso_code == 'it'){
			$rule = 'novedades_deporte_rule_it';
		}
		$r_url = $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow, '', $id_shop);
		$r_url_2 = explode('?', $r_url);
		return $r_url_2[0];
	}
	/*
    * module: alvarezmarcasdeporte
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getOfertasDeporteLink($id_deporte, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false)
	{
		if (!$id_lang) {
			$id_lang = Context::getContext()->language->id;
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
		if (!is_object($id_deporte)) {
			$category = new Category((int)$id_deporte);
		}
		$params = array();
		$params['id_deporte'] = $category->id;
		$params['deporte'] = (!$alias) ? $category->link_rewrite : $alias;

		if (Context::getContext()->language->iso_code == 'es') {
			$rule = 'ofertas_deporte_rule_es';
		}else if(Context::getContext()->language->iso_code == 'en'){
			$rule = 'ofertas_deporte_rule_en';
		}else if(Context::getContext()->language->iso_code == 'fr'){
			$rule = 'ofertas_deporte_rule_fr';
		}else if(Context::getContext()->language->iso_code == 'pt'){
			$rule = 'ofertas_deporte_rule_pt';
		}else if(Context::getContext()->language->iso_code == 'de'){
			$rule = 'ofertas_deporte_rule_de';
		}else if(Context::getContext()->language->iso_code == 'it'){
			$rule = 'ofertas_deporte_rule_it';
		}
		$r_url = $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow, '', $id_shop);
		$r_url_2 = explode('?', $r_url);
		return $r_url_2[0];
	}
	/*
    * module: alvarezmarcasdeporte
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getBestSalesDeporteLink($id_category, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false)
	{
		if (!$id_lang) {
			$id_lang = Context::getContext()->language->id;
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
		if (!is_object($id_category)) {
			$category = new Category((int)$id_category);
		}
		$params = array();
		$params['id_category'] = $category->id;
		$params['deporte'] = (!$alias) ? $category->link_rewrite : $alias;

		if (Context::getContext()->language->iso_code == 'es') {
			$rule = 'bestsale_deporte_rule_es';
		}else if(Context::getContext()->language->iso_code == 'en'){
			$rule = 'bestsale_deporte_rule_en';
		}else if(Context::getContext()->language->iso_code == 'fr'){
			$rule = 'bestsale_deporte_rule_fr';
		}else if(Context::getContext()->language->iso_code == 'pt'){
			$rule = 'bestsale_deporte_rule_pt';
		}else if(Context::getContext()->language->iso_code == 'de'){
			$rule = 'bestsale_deporte_rule_de';
		}else if(Context::getContext()->language->iso_code == 'it'){
			$rule = 'bestsale_deporte_rule_it';
		}
		$r_url = $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow, '', $id_shop);
		$r_url_2 = explode('?', $r_url);
		return $r_url_2[0];
	}
	/*
    * module: alvarezmarcasdeporte
    * date: 2022-02-24 04:52:32
    * version: 2.2.6
    */
    public function getBoletinesDeporteLink($id_deport, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false)
	{
		if (!$id_lang) {
			$id_lang = Context::getContext()->language->id;
		}
		$url = $this->getBaseLink($id_shop, null, $relative_protocol).$this->getLangLink($id_lang, null, $id_shop);
		if (!is_object($id_deport)) {
			$category = new Category((int)$id_deport, $id_lang);
		}
		$params = array();
		$params['id_deport'] = $category->id;
		$params['deporte'] = (!$alias) ? $category->link_rewrite : $alias;
		$params['fc'] = 'module';
		$params['module'] = 'alvarezboletines';
		$params['controller'] = 'boletines';
		$id_deporte = Db::getInstance()->getValue("SELECT `id` FROM "._DB_PREFIX_."deporte_origen WHERE deporte SOUNDS LIKE '".$category->link_rewrite."'");
		$params['id_deporte'] = $id_deporte; //id_deporte_origen

		if (Context::getContext()->language->iso_code == 'es') {
			$rule = 'boletines_deporte_rule_es';
		}else if(Context::getContext()->language->iso_code == 'en'){
			$rule = 'boletines_deporte_rule_en';
		}else if(Context::getContext()->language->iso_code == 'fr'){
			$rule = 'boletines_deporte_rule_fr';
		}else if(Context::getContext()->language->iso_code == 'pt'){
			$rule = 'boletines_deporte_rule_pt';
		}else if(Context::getContext()->language->iso_code == 'de'){
			$rule = 'boletines_deporte_rule_de';
		}else if(Context::getContext()->language->iso_code == 'it'){
			$rule = 'boletines_deporte_rule_it';
		}
		$r_url = $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow, '', $id_shop);
		$r_url_2 = explode('?', $r_url);
		return $r_url_2[0];
	}

    public function getCMSLink(
        $cms,
        $alias = null,
        $ssl = null,
        $idLang = null,
        $idShop = null,
        $relativeProtocol = false
    ) {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }
        $url = $this->getBaseLink($idShop, $ssl, $relativeProtocol) . $this->getLangLink($idLang, null, $idShop);
        $dispatcher = Dispatcher::getInstance();
        if (!is_object($cms)) {
            if ($alias !== null && !$dispatcher->hasKeyword('cms_rule', $idLang, 'meta_keywords', $idShop) && !$dispatcher->hasKeyword('cms_rule', $idLang, 'meta_title', $idShop)) {
                return $url . $dispatcher->createUrl('cms_rule', $idLang, ['id' => (int) $cms, 'rewrite' => (string) $alias], $this->allow, '', $idShop);
            }
            $cms = new CMS($cms, $idLang);
        }
        $params = [];
        $params['id'] = $cms->id;
        $params['rewrite'] = (!$alias) ? (is_array($cms->link_rewrite) ? $cms->link_rewrite[(int) $idLang] : $cms->link_rewrite) : $alias;
        $params['meta_keywords'] = '';
        if (isset($cms->meta_keywords) && !empty($cms->meta_keywords)) {
            $params['meta_keywords'] = is_array($cms->meta_keywords) ? Tools::str2url($cms->meta_keywords[(int) $idLang]) : Tools::str2url($cms->meta_keywords);
        }
        $params['meta_title'] = '';
        if (isset($cms->meta_title) && !empty($cms->meta_title)) {
            $params['meta_title'] = is_array($cms->meta_title) ? Tools::str2url($cms->meta_title[(int) $idLang]) : Tools::str2url($cms->meta_title);
        }
        return $url . $dispatcher->createUrl('cms_rule', $idLang, $params, $this->allow, '', $idShop);
    }

    public function getModuleLink(
        $module,
        $controller = 'default',
        array $params = [],
        $ssl = null,
        $idLang = null,
        $idShop = null,
        $relativeProtocol = false
    ) {
        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }
        $url = $this->getBaseLink($idShop, $ssl, $relativeProtocol) . $this->getLangLink($idLang, null, $idShop, $controller);
        $params['module'] = $module;
        $params['controller'] = $controller ? $controller : 'default';
        if (Dispatcher::getInstance()->hasRoute('module-' . $module . '-' . $controller, $idLang, $idShop)) {
            return $this->getPageLink('module-' . $module . '-' . $controller, $ssl, $idLang, $params);
        } else {
        	return $url . Dispatcher::getInstance()->createUrl('module', $idLang, $params, $this->allow, '', $idShop);
        }
    }
    /*
    * module: ets_multilangimages
    * date: 2022-02-24 04:52:32
    * version: 1.0.6
    */
    public function getImageLink($name, $ids, $type = null,$id_lang = null)
    {
        if(!$id_lang)
            $id_lang = Context::getContext()->language->id;
        $splitIds = explode('-', $ids);
        if(!isset($splitIds[1]))
        {
            $image = new Image($ids);
            $ids = $image->id_product.'-'.$ids;
            $splitIds = explode('-', $ids);
        }
        if($id_lang != Configuration::get('PS_LANG_DEFAULT'))
        {
            $ets_multilang = Module::getInstanceByName('ets_multilangimages');
            $idImage = $splitIds[1];
            $id_image_lang = Db::getInstance()->getValue('SELECT id_image_lang FROM `'._DB_PREFIX_.'ets_image_lang` WHERE id_image="'.(int)$idImage.'" AND id_lang="'.(int)$id_lang.'"');
            if($id_image_lang) {
            	$link = $ets_multilang->getLangImageLink($splitIds[0].'-'.$id_image_lang,$type);
                return $link;
            }
        }

		if (!empty($name) && is_array($name)) {
			$name = $name[1];
		}

        if(!$name && $this->allow)
        {
            $this->allow =false;
            $link = parent::getImageLink($name, $ids, $type);
            $this->allow = true;
        }
        else
            $link = parent::getImageLink($name, $ids, $type);
        return $link;
    }

    // public function getCatEventoImageLink($name, $idCategory, $type = null)
    // {
    // 	$id_lang = Context::getContext()->language->id;
   	// 	$uriPath = __PS_BASE_URI__ . 'img/c/' . $idCategory .'.jpg';
   	// 	return $this->protocol_content . Tools::getMediaServer($uriPath) . $uriPath;
    // }


public static function getUrlSmarty($params)
    {
        $context = Context::getContext();

        if (!isset($params['params'])) {
            $params['params'] = [];
        }

        if (isset($params['id'])) {
            $entity = str_replace('-', '_', $params['entity']);
            $id = ['id_' . $entity => $params['id']];
            $params['params'] = array_merge($id, $params['params']);
        }

        $default = [
            'id_lang' => isset($params['lang']) ? $params['lang'] : $context->language->id,
            'id_shop' => null,
            'alias' => null,
            'ssl' => null,
            'relative_protocol' => true,
            'with_id_in_anchor' => false,
            'extra_params' => [],
            'add_anchor' => true,
        ];
        $params = array_merge($default, $params);

        $urlParameters = http_build_query($params['params']);

        switch ($params['entity']) {
            case 'language':
                $link = $context->link->getLanguageLink($params['id']);

                break;
            case 'product':
                $link = $context->link->getProductLink(
                    $params['id'],
                    $params['alias'],
                    (isset($params['category']) ? $params['category'] : null),
                    (isset($params['ean13']) ? $params['ean13'] : null),
                    $params['id_lang'],
                    $params['id_shop'],
                    (isset($params['ipa']) ? (int) $params['ipa'] : 0),
                    false,
                    $params['relative_protocol'],
                    $params['with_id_in_anchor'],
                    $params['extra_params'],
                    $params['add_anchor']
                );

                break;
            case 'category':
                $params = array_merge(['selected_filters' => null], $params);
                // 20230817 - Al tener getCategoryLink un override, no se le pasa un object, sino el ID de la categoria
                $link = $context->link->getCategoryLink(
                    //new Category($params['id'], $params['id_lang']),
                    $params['id'],
                    $params['alias'],
                    $params['id_lang'],
                    $params['selected_filters'],
                    $params['id_shop'],
                    $params['relative_protocol']
                );

                break;
            case 'categoryImage':
                $params = array_merge(['selected_filters' => null], $params);
                $link = $context->link->getCatImageLink(
                    $params['name'],
                    $params['id'],
                    $params['type'] = (isset($params['type']) ? $params['type'] : null)
                );

                break;
            case 'cms':
                $link = $context->link->getCMSLink(
                    new CMS($params['id'], $params['id_lang']),
                    $params['alias'],
                    $params['ssl'],
                    $params['id_lang'],
                    $params['id_shop'],
                    $params['relative_protocol']
                );

                break;
            case 'module':
                $params = array_merge([
                    'selected_filters' => null,
                    'params' => [],
                    'controller' => 'default',
                ], $params);
                $link = $context->link->getModuleLink(
                    $params['name'],
                    $params['controller'],
                    $params['params'],
                    $params['ssl'],
                    $params['id_lang'],
                    $params['id_shop'],
                    $params['relative_protocol']
                );

                break;
            case 'sf':
                if (!array_key_exists('route', $params)) {
                    throw new \InvalidArgumentException('You need to setup a `route` attribute.');
                }

                $sfContainer = SymfonyContainer::getInstance();
                if (null !== $sfContainer) {
                    /** @var UrlGeneratorInterface $sfRouter */
                    $sfRouter = $sfContainer->get('router');

                    if (array_key_exists('sf-params', $params)) {
                        return $sfRouter->generate($params['route'], $params['sf-params'], UrlGeneratorInterface::ABSOLUTE_URL);
                    }
                    $link = $sfRouter->generate($params['route'], [], UrlGeneratorInterface::ABSOLUTE_URL);
                } else {
                    throw new \InvalidArgumentException('You can\'t use Symfony router in legacy context.');
                }

                break;
            default:
                $link = $context->link->getPageLink(
                    $params['entity'],
                    $params['ssl'],
                    $params['id_lang'],
                    $urlParameters,
                    false,
                    $params['id_shop'],
                    $params['relative_protocol']
                );

                break;
        }

        return $link;
    } // function getUrlSmarty





}
