<?php
/**
*  @author    Amazzing <mail@amazzing.ru>
*  @copyright Amazzing
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

class AmazzingFilterOverride extends AmazzingFilter
{
    /**
     * JLP - 09/03/2022 - Quitar la opción de ordenar por stock y aleatorio
     */
    public function getAvailableSortingOptions($default_option = '')
    {
        $options = parent::getAvailableSortingOptions($default_option);

        foreach ($options as $key => $option) {
            if ($key == 'product.quantity.desc' || $key == 'product.random.desc') {
                unset($options[$key]);
            }
        }

        return $options;
    }

    public function getProductSales($id)
    {
        //SUBIR EN PRIORIDAD CIERTOS PRODUCTOS/
        $productos_prioritarios = [55,58,60,61,62,63,64,14518,14520,61293];

        if (in_array((int)$id, $productos_prioritarios)) {
            $this->sales_data[$id] = 999999999;
        }
        else {
            if (!isset($this->sales_data)) {
                $this->sales_data = array();

                $raw_data = $this->db->executeS('
                SELECT ps.id_product, ps.quantity FROM ' . _DB_PREFIX_ . 'product_sale ps
                ' . Shop::addSqlAssociation('product', 'ps') . '
                WHERE product_shop.active = 1 AND product_shop.visibility <> "none"
            ');

                foreach ($raw_data as $d) {
                    $this->sales_data[$d['id_product']] = $d['quantity'];
                }
            }
        }
        return isset($this->sales_data[$id]) ? $this->sales_data[$id] : 0;
    }
}
