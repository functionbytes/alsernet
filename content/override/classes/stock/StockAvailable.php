<?php


class StockAvailable extends StockAvailableCore
{

    
    public static function getQuantityAvailableByProduct($id_product = null, $id_product_attribute = null, $id_shop = null)
    {
        
        if ($id_product_attribute === null) {
            $id_product_attribute = 0;
        }

        $key = 'StockAvailable::getQuantityAvailableByProduct_' . (int) $id_product . '-' . (int) $id_product_attribute . '-' . (int) $id_shop;
        if (!Cache::isStored($key)) {

            if ($id_product_attribute != 0){

                $sqlexiste = "SELECT id_tot_switch_attribute_disabled FROM " . _DB_PREFIX_ . "tot_switch_attribute_disabled WHERE id_product_attribute=".$id_product_attribute;
                $existedes = "".Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sqlexiste);

                if ($existedes==""){

		    //PrestaShopLogger::addLog("idproduct: ".$id_product." attributo ". $id_product_attribute . " pasa stock con".parent::getQuantityAvailableByProduct($id_product, $id_product_attribute, $id_shop));	

                    return parent::getQuantityAvailableByProduct($id_product, $id_product_attribute, $id_shop);    
                }
                else{
                    Cache::store($key, 0);
                    return 0;
                }
            }
            else{


                
                //ver si es producto con variantes y si es asi, calcular stock global, sino devolver lo que devolvia sin override
                $sqlexiste = "SELECT id_product FROM " . _DB_PREFIX_ . "product_attribute WHERE id_product=".$id_product;
                $existedes = "".Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sqlexiste);

                if ($existedes==""){
                    return parent::getQuantityAvailableByProduct($id_product, $id_product_attribute, $id_shop);   
                }
                else{
                    $sql = "SELECT SUM(quantity) as quantity FROM " . _DB_PREFIX_ . "stock_available WHERE id_product = " . $id_product . " AND id_product_attribute <> 0";
                    $sqldes = "SELECT SUM(quantity) as quantity FROM " . _DB_PREFIX_ . "stock_available a inner join " . _DB_PREFIX_ . "tot_switch_attribute_disabled b on a.id_product_attribute=b.id_product_attribute WHERE id_product = " . $id_product . " AND a.id_product_attribute <> 0";

                    $res = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
                    $resdes = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sqldes);

                    $result = $res-$resdes;
                    
		    

                    Cache::store($key, $result);

                    return $result;
                }    


                
             }
        }     
        return Cache::retrieve($key);
           
     
    }

  
}
