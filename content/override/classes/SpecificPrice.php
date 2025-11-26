<?php

class SpecificPrice extends SpecificPriceCore
{
    protected static function filterOutField($field_name, $field_value, $threshold = 1000)
    {
        $name = Db::getInstance()->escape($field_name, false, true);
        $query_extra = 'AND `' . $name . '` = 0 ';
        if ($field_value == 0 || array_key_exists($field_name, self::$_no_specific_values)) {
            return $query_extra;
        }
        $key_cache = __FUNCTION__ . '-' . $field_name . '-' . $threshold;
        $specific_list = [];
        if (!array_key_exists($key_cache, self::$_filterOutCache)) {
            /*
			$query_count = 'SELECT COUNT(*) FROM (SELECT DISTINCT `' . $name . '` FROM `' . _DB_PREFIX_ . 'specific_price` WHERE `' . $name . '` != 0 GROUP BY id_product ) AS counted';
            $specific_count = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query_count);
			*/
			$specific_count=$threshold;
			
            if ($specific_count == 0) {
                self::$_no_specific_values[$field_name] = true;

                return $query_extra;
            }
            if ($specific_count < $threshold) {
                $query = 'SELECT DISTINCT `' . $name . '` FROM `' . _DB_PREFIX_ . 'specific_price` WHERE `' . $name . '` != 0';
                $tmp_specific_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
                foreach ($tmp_specific_list as $value) {
                    $specific_list[] = $value[$field_name];
                }
            }
            self::$_filterOutCache[$key_cache] = $specific_list;
        } else {
            $specific_list = self::$_filterOutCache[$key_cache];
        }

        // $specific_list is empty if the threshold is reached
        if (empty($specific_list) || in_array($field_value, $specific_list)) {
            if ($name == 'id_product' && !self::$_hasGlobalProductRules) {
                $query_extra = 'AND `' . $name . '` = ' . (int) $field_value . ' ';
            } else {
                $query_extra = 'AND `' . $name . '` ' . self::formatIntInQuery(0, $field_value) . ' ';
            }
        }

        return $query_extra;
    }

    
}