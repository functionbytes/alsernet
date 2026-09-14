<?php

class v_sinc_w_navegacionClass // [HACER]
{
    public function Procesar_v_sinc_w_navegacion($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Dato nulo en data para fila '.$fila);

                return 1;
            }

            $idnav = $data['id'];
            $idpadre = $data['id_padre'];
            $elemento = $data['elemento'];

            // ver si existe idnav en la tabla

            $catps = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$idnav);
            if ($catps == '') {

                // crear categoria: problema si el elemento no existe. Por ahora ver en la tabla category_import
                $catidparanombreps = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_origen='.$elemento);

                if ($catidparanombreps != '') {
                    $catparanombre = new Category((int) $catidparanombreps);

                    $catpadres = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$idpadre);

                    foreach ($catpadres as $catpadreitem) {

                        $category = new Category;
                        $category->id_parent = $catpadreitem['id_cat'];

                        $category->active = 1;

                        $category->id_shop_default = 1;
                        $category->name = $catparanombre->name;

                        $category->meta_title = $catparanombre->meta_title;

                        $category->link_rewrite = str_replace('-', '_', $catparanombre->link_rewrite);

                        $category->add();
                        $category->addGroupsIfNoExist(1);
                        $category->addGroupsIfNoExist(2);
                        $category->addGroupsIfNoExist(3);
                        $orden = ''.$data['orden'];
                        if ($orden == '') {
                            $orden = '0';
                        }

                        Db::getInstance()->Execute('INSERT INTO aalv_category_import(id_cat, id_origen, id_padre, url, orden, id_nav) VALUES ('.$category->id.','.$data['elemento'].','.$data['id_padre'].",'".$data['url']."',".$orden.','.$idnav.')');
                        $category = new Category($category->id);
                        $category->update();
                    }
                } else {
                    // recogerla desde valores_nav

                    $nombrecat = ''.Db::getInstance()->getValue('select nombre from  aalv_valores_nav_import where id_origen='.$elemento);

                    if ($nombrecat != '') {

                        $catpadres = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$idpadre);

                        foreach ($catpadres as $catpadreitem) {

                            $category = new Category;
                            $category->id_parent = $catpadreitem['id_cat'];

                            $category->active = 1;

                            $category->id_shop_default = 1;
                            $category->name[1] = $nombrecat;

                            // $category->meta_title = $catparanombre->meta_title;

                            // $category->link_rewrite = $catparanombre->link_rewrite;
                            $category->link_rewrite[1] = str_replace('-', '_', auxiliares::safeName($category->name[1]));

                            $category->add();
                            $category->addGroupsIfNoExist(1);
                            $category->addGroupsIfNoExist(2);
                            $category->addGroupsIfNoExist(3);
                            $orden = ''.$data['orden'];
                            if ($orden == '') {
                                $orden = '0';
                            }

                            Db::getInstance()->Execute('INSERT INTO aalv_category_import(id_cat, id_origen, id_padre, url, orden, id_nav) VALUES ('.$category->id.','.$data['elemento'].','.$data['id_padre'].",'".$data['url']."',".$orden.','.$idnav.')');
                            $category = new Category($category->id);
                            $category->update();
                        }
                    }
                    // else{
                    //     throw new Exception("[" . __FUNCTION__ . "] - [" . __LINE__ . "] Esta navegacion no existe, agregarla ELEMENTO => " . $elemento);
                    //     return 1;
                    // }
                }
            } else {

                if ($idpadre != 0) {

                    $idcats = Db::getInstance()->ExecuteS('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$data['id']);

                    if (count($idcats) == 1) {
                        foreach ($idcats as $idcatsitem) {

                            // recuperar padre
                            $category = new Category((int) $idcatsitem['id_cat']);
                            $actualparent = $category->id_parent;
                            $idnavactualparent = ''.Db::getInstance()->getValue('SELECT id_nav FROM aalv_category_import WHERE id_cat='.$actualparent);

                            if ($idnavactualparent != '') {

                                if ($idnavactualparent != $idpadre) {

                                    $catpadre = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$idpadre);

                                    if ($catpadre != '') {

                                        $category->id_parent = $catpadre;
                                        $category->update();
                                        $id = ''.Db::getInstance()->getValue('SELECT id FROM aalv_category_import WHERE id_nav='.$idnav);
                                        if ($id != '') {
                                            Db::getInstance()->Execute('UPDATE aalv_category_import SET id_padre='.$idpadre.' WHERE id='.$id);
                                            Db::getInstance()->Execute('UPDATE aalv_category_import SET id_origen='.$elemento.' WHERE id='.$id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return 1;
        } else {
            return 1;
        }
    }
}
