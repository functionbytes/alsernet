<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
class Tag extends TagCore
{
    /*
    * module: idxrcustomproduct
    * date: 2025-10-07 10:54:09
    * version: 1.8.4
    */
    public function getProducts($associated = true, Context $context = null)
    {
        $list = parent::getProducts($associated,$context);
        if (!$list) {
            return $list;
        }
        foreach ($list as $index => $product) {
            if (IdxCustomizedProduct::isCustomizedById($product['id_product'])) {
                unset($list[$index]);
            }
        }
        return $list;
    }
}