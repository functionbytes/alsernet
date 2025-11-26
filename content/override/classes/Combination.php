<?php

class Combination extends CombinationCore
{

    public function getAttributesName($idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT al.*, a.*
			FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
            JOIN ' . _DB_PREFIX_ . 'attribute a ON (pac.id_attribute = a.id_attribute)
            JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (pac.id_attribute = al.id_attribute AND al.id_lang=' . (int) $idLang . ')
			WHERE pac.id_product_attribute=' . (int) $this->id);
    }

    static public function setDefaultAttribute($id_product_attribute) {

        $current_id_default_attribute = Product::getDefaultAttribute($id_product);
        $combination = new Combination($current_id_default_attribute);
        $combination->default_on = 0;
        if (!$combination->save()) {
            return false;
        }

        $combination = new Combination($id_product_attribute);
        $combination->default_on = 1;
        if (!$combination->save()) {
            $combination = new Combination($current_id_default_attribute);
            $combination->default_on = 1;
            $combination->save();
            return false;
        }

        $id_default_attribute = (int) Product::updateDefaultAttribute($id_product);
        if ($id_default_attribute) {
            $this->cache_default_attribute = $id_default_attribute;
        }

        return true;

    }

    public function deleteAssociations()
    {
        if ((int) $this->id === 0) {
            return false;
        }
        $result = Db::getInstance()->delete('product_attribute_combination', '`id_product_attribute` = ' . (int) $this->id);
        // $result &= Db::getInstance()->delete('product_attribute_image', '`id_product_attribute` = ' . (int) $this->id);

        if ($result) {
            Hook::exec('actionAttributeCombinationDelete', ['id_product_attribute' => (int) $this->id]);
        }

        return $result;
    }


}
