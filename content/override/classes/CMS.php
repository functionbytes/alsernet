<?php

class CMS extends CMSCore
{

	/**
     * @param null $idLang
     * @param bool $idBlock
     * @param bool $active
     *
     * @return array|false|mysqli_result|PDOStatement|resource|null
     */
    public static function listCms($idLang = null, $idBlock = false, $active = true)
    {
        if (empty($idLang)) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT c.id_cms, l.meta_title, concat_ws(" - ", c.id_cms, l.meta_title) as titulo_mostrar
		FROM  ' . _DB_PREFIX_ . 'cms c
		JOIN ' . _DB_PREFIX_ . 'cms_lang l ON (c.id_cms = l.id_cms)
		' . Shop::addSqlAssociation('cms', 'c') . '
		' . (($idBlock) ? 'JOIN ' . _DB_PREFIX_ . 'block_cms b ON (c.id_cms = b.id_cms)' : '') . '
		WHERE l.id_lang = ' . (int) $idLang . (($idBlock) ? ' AND b.id_block = ' . (int) $idBlock : '') . ($active ? ' AND c.`active` = 1 ' : '') . '
		GROUP BY c.id_cms
		ORDER BY c.`position`');
    }



}

