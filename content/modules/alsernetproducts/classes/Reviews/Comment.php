<?php
/**
 *  Please read the terms of the CLUF license attached to this module(cf "licences" folder)
 *
 * @author    Línea Gráfica E.C.E. S.L.
 * @copyright Lineagrafica.es - Línea Gráfica E.C.E. S.L. all rights reserved.
 * @license   https://www.lineagrafica.es/licenses/license_en.pdf
 *            https://www.lineagrafica.es/licenses/license_es.pdf
 *            https://www.lineagrafica.es/licenses/license_fr.pdf
 */

class Comment extends ObjectModel
{
    const PRODUCT_REVIEW_TAB = 1;
    const PRODUCT_REVIEW_CONTENT = 2;
    const PRODUCT_EXTRA_RIGHT = 3;

    public $id_product;
    public $id_product_attribute;
    public $id_customer;
    public $id_lang;
    public $stars;
    public $nick;
    public $title;
    public $comment;
    public $answer;
    public $active;
    public $position;
    public $date;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'     => 'alsernetproducts_productcomments',
        'primary'   => 'id_productcomment',
        'multilang' => false,
        'fields'    => array(
            'id_product'           => array('type' => self::TYPE_INT, 'required' => true),
            'id_product_attribute' => array('type' => self::TYPE_INT, 'required' => true),
            'id_customer'          => array('type' => self::TYPE_INT, 'required' => true),
            'id_lang'              => array('type' => self::TYPE_INT, 'required' => true),
            'stars'                => array('type' => self::TYPE_INT, 'required' => true),
            'nick'                 => array('type' => self::TYPE_STRING, 'size' => 255),
            'title'                => array('type' => self::TYPE_STRING, 'size' => 255),
            'comment'              => array('type' => self::TYPE_HTML),
            'answer'               => array('type' => self::TYPE_HTML),
            'active'               => array('type' => self::TYPE_INT, 'required' => true),
            'position'             => array('type' => self::TYPE_INT, 'required' => true),
            'date'                 => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),
        )
    );

    public static function install()
    {
        $sql = array(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::$definition['table'] . '` (
                `' . self::$definition['primary'] . '` INT(11) NOT NULL AUTO_INCREMENT,
                `id_product` INT(11) NOT NULL,
                `id_product_attribute` INT(11) NOT NULL,
                `id_customer` INT(11) NOT NULL,
                `id_lang` INT(11) NOT NULL,
                `stars` INT(11) NOT NULL,
                `nick` VARCHAR(255) NOT NULL,
                `title` VARCHAR(255),
                `comment` TEXT,
                `answer` TEXT,
                `active` TINYINT(1) NOT NULL,
                `position` INT(11) NOT NULL,
                `date` DATETIME NOT NULL,
                PRIMARY KEY (`' . self::$definition['primary'] . '`),
                KEY `date` (`date`,`id_customer`,`id_product`,`stars`,`id_lang`,`active`,`position`)
            ) ENGINE='.(defined('ENGINE_TYPE') ? ENGINE_TYPE : 'Innodb').' CHARSET=utf8',

            'ALTER TABLE `' . _DB_PREFIX_ . self::$definition['table'] . '` '
            .'ADD INDEX `lgcomments_id_product_index` (`id_product`)',
        );

        return self::processQueries($sql);
    }

    public static function uninstall()
    {
        $sql = array(
            'DROP TABLE IF EXISTS `'._DB_PREFIX_.self::$definition['table'].'`',
        );

        return self::processQueries($sql);
    }

    public static function upgrade()
    {
        return true;
    }

    protected static function processQueries($queries)
    {
        $res = true;
        foreach ($queries as $query) {
            try {
                $res &= Db::getInstance()->execute($query);
            } catch (Exception $e) {
                Tools::dieObject($e->getMessage());
            }
        }

        return $res;
    }

    public static function getNummberOfReviews($id_product, $id_lang = null)
    {
        $by_lang = (bool)(Configuration::get('PS_LGCOMMENTS_DISPLAY_LANGUAGE2') == 1);

        if ($by_lang) {
            if (is_null($id_lang)) {
                $id_lang = (int)Context::getCOntext()->language->id;
            }
        }

        $sql =  'SELECT COUNT(*) '.
            'FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` '.
            'WHERE id_product = '.(int)$id_product.' '.
            '  AND active = 1 ';
        if ($by_lang) {
            $sql .= '  AND id_lang = '.(int)$id_lang;
        }

        return Db::getInstance()->getvalue($sql);
    }

    public static function getSumOfReviews($id_product, $id_lang = null)
    {
        $by_lang = (bool)(Configuration::get('PS_LGCOMMENTS_DISPLAY_LANGUAGE2') == 1);

        if ($by_lang) {
            if (is_null($id_lang)) {
                $id_lang = (int)Context::getCOntext()->language->id;
            }
        }

        $sql =  'SELECT SUM(stars) '.
            'FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` '.
            'WHERE id_product = '.(int)$id_product.' '.
            '  AND active = 1 ';
        if ($by_lang) {
            $sql .= '  AND id_lang = '.(int)$id_lang;
        }

        return Db::getInstance()->getvalue($sql);
    }

    public static function getProductReviews()
    {
        $lang = '';
        $way = 'DESC';
        $id_product = (int)Tools::getValue('id_product', 0);
        $lang = 'AND pc.id_lang = '.(int)Context::getContext()->language->id;
        $way = 'ASC';

        $comments = Db::getInstance()->executeS(
            'SELECT pc.*, CONCAT(c.firstname, \' \', (SUBSTRING(c.lastname,1,1)), \'.\') as customer, '.
            (
                Configuration::get('PS_LGCOMMENTS_SCALE') == 20
                    ? '( pc.stars * 2 ) '
                    : ( Configuration::get('PS_LGCOMMENTS_SCALE') == 5
                        ? ' ROUND( ( pc.stars / 2 ), 1) '
                        : ' pc.stars '
                    )
            ).
            'AS rating '.
            'FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` pc '.
            'LEFT JOIN '._DB_PREFIX_.'customer as c ON pc.id_customer = c.id_customer '.
            'WHERE pc.id_product = '.(int)$id_product.' '.
            $lang.' '.
            'AND pc.active = 1 '.
            'ORDER BY pc.position '. $way
        );
        return $comments;
    }


    public static function getProductReviewss($id_product)
    {
        $lang = '';
        $way = 'DESC';
        $id_product = (int)$id_product;
        $lang = 'AND pc.id_lang = '.(int)Context::getContext()->language->id;
        $way = 'ASC';

        $comments = Db::getInstance()->executeS(
            'SELECT pc.*, CONCAT(c.firstname, \' \', (SUBSTRING(c.lastname,1,1)), \'.\') as customer, '.
            (
                Configuration::get('PS_LGCOMMENTS_SCALE') == 20
                    ? '( pc.stars * 2 ) '
                    : ( Configuration::get('PS_LGCOMMENTS_SCALE') == 5
                        ? ' ROUND( ( pc.stars / 2 ), 1) '
                        : ' pc.stars '
                    )
            ).
            'AS rating '.
            'FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` pc '.
            'LEFT JOIN '._DB_PREFIX_.'customer as c ON pc.id_customer = c.id_customer '.
            'WHERE pc.id_product = '.(int)$id_product.' '.
            $lang.' '.
            'AND pc.active = 1 '.
            'ORDER BY pc.position '. $way
        );
        return $comments;
    }


    public static function getCountProdComments()
    {
        $id_product = (int)Tools::getValue('id_product', 0);
        $lang = '';

        if (Configuration::get('PS_LGCOMMENTS_DISPLAY_LANGUAGE2') == 1) {
            $lang = ' AND id_lang = '.(int)Context::getContext()->language->id;
        }

        $countL = Db::getInstance()->getvalue(
            'SELECT COUNT(*) '.
            'FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` '.
            'WHERE id_product = '.(int)$id_product.' '.
            'AND active = 1 '.
            $lang
        );
        return $countL;
    }

    public static function getSumProdComments()
    {
        $id_product = (int)Tools::getValue('id_product', 0);
        $lang = '';

        if (Configuration::get('PS_LGCOMMENTS_DISPLAY_LANGUAGE2') == 1) {
            $lang = ' AND id_lang = '.(int)Context::getContext()->language->id;
        }

        $total = Db::getInstance()->getvalue(
            'SELECT SUM(stars) AS totalcomentarios '.
            'FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` '.
            'WHERE id_product = '.(int)$id_product.''.
            $lang .
            ' AND active = 1'
        );
        return $total;
    }

    public static function getCountProdByRate($min = 0, $max = 0)
    {
        $id_product = (int)Tools::getValue('id_product', 0);
        $lang = '';

        if (Configuration::get('PS_LGCOMMENTS_DISPLAY_LANGUAGE2') == 1) {
            $lang = 'AND id_lang = '.(int)Context::getContext()->language->id;
        }

        $last_and =             'AND stars > '.(int)$min.' AND stars <= '.(int)$max.'';
        if ($min == 0 && $max == 0) {
            $last_and = 'AND stars = '.(int)$min;
        }

        $ratesL = Db::getInstance()->getvalue(
            'SELECT COUNT(*) '.
            'FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` '.
            'WHERE id_product = '.(int)$id_product.' '.
            'AND active = 1 '.
            $lang.' '.$last_and
        );
        return $ratesL;
    }

    public static function checkIfProdAlreadyReviewed()
    {
        $id_product = (int)Tools::getValue('id_product', 0);

        $check = Db::getInstance()->getValue(
            'SELECT COUNT(' . self::$definition['primary'] . ') '.
            'FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` '.
            'WHERE id_customer = '.(int)Context::getContext()->customer->id.' '.
            'AND id_product = '.(int)$id_product
        );
        return $check;
    }

    public static function getProductRewrite()
    {
        $id_product = (int)Tools::getValue('id_product', 0);

        $rewrite = Db::getInstance()->getValue(
            'SELECT link_rewrite '.
            'FROM '._DB_PREFIX_.'product_lang '.
            'WHERE id_product = '.(int)$id_product.
            ' AND id_lang = '.(int)Context::getContext()->language->id
        );
        return $rewrite;
    }

    public static function getProductReviewsDetailsShow($id_product)
    {
        $product = Context::getContext()->controller->getProduct();
        $lang = Context::getContext()->language->id;
        

        $rating_scale = 5;
        $reviews = self::getProductReviews();
        
        if ($rating_scale == 5) {
            foreach ($reviews as $index => $r) {
                $reviews[$index]['rating'] = ceil($r['rating']);
                $reviews[$index]['stars'] = ceil($r['rating']) * 2; // Calificación en una escala de 10
            }
        }
        
        $number_of_reviews = (int)self::getNummberOfReviews((int)$id_product);
        $sum_of_reviews = self::getSumOfReviews((int)$id_product);
        $averagecomments = $number_of_reviews > 0 ? round($sum_of_reviews / $number_of_reviews, 1) : 0;
        
        // Calificación en porcentaje para el ancho de las estrellas llenas
        $calification = ceil($averagecomments)  * 10;
        $averagecomments = ceil($averagecomments) / 2;

        $id_product = (int)Context::getContext()->controller->getProduct()->id;

        $params = array (
            'back' => Context::getContext()->link->getProductLink(
                (int)$id_product,
                null,
                null,
                null,
                (int)Context::getContext()->language->id
            ),
        );


        $fivestars = self::getCountProdByRate(8, 10);
        $fourstars = self::getCountProdByRate(6, 8);
        $threestars = self::getCountProdByRate(4, 6);
        $twostars = self::getCountProdByRate(2, 4);
        $onestar = self::getCountProdByRate(0, 2);
        $zerostar = self::getCountProdByRate(0, 0);

       $fiveStarsPercent = ($number_of_reviews > 0) ? round(($fivestars / $number_of_reviews) * 100, 2) : 0;
        $fourStarsPercent = ($number_of_reviews > 0) ? round(($fourstars / $number_of_reviews) * 100, 2) : 0;
        $threeStarsPercent = ($number_of_reviews > 0) ? round(($threestars / $number_of_reviews) * 100, 2) : 0;
        $twoStarsPercent = ($number_of_reviews > 0) ? round(($twostars / $number_of_reviews) * 100, 2) : 0;
        $oneStarPercent = ($number_of_reviews > 0) ? round(($onestar / $number_of_reviews) * 100, 2) : 0;
        $zeroStarPercent = ($number_of_reviews > 0) ? round(($zerostar / $number_of_reviews) * 100, 2) : 0;

        $data = array (
            'product'            => $product,
            'lang_iso'           => pSQL(Context::getContext()->language->iso_code),
            'id_customer'        => (int)Context::getContext()->customer->id,
            'id_product'         => (int)$id_product,
            'logged'             => (bool)Context::getContext()->customer->id,
            'fivestars'          => $fivestars,
            'fourstars'          => $fourstars,
            'threestars'         => $threestars,
            'twostars'           => $twostars,
            'onestar'            => $onestar,
            'zerostar'           => $zerostar,
            'fiveStarsPercent'          => $fiveStarsPercent,
            'fourStarsPercent'          => $fourStarsPercent,
            'threeStarsPercent'         => $threeStarsPercent,
            'twoStarsPercent'           => $twoStarsPercent,
            'oneStarPercent'            => $oneStarPercent,
            'zeroStarPercent'           => $zeroStarPercent,
            'reviews'         => $reviews,
            'dateformat'         => LGUtils::getDateFormat(),
            'numlgcomments'      => self::getCountProdComments(),
            'alreadyreviewed'    => self::checkIfProdAlreadyReviewed(),
            'authentication_url' => Context::getContext()->link->getPageLink('authentication', true, null, $params),
            'ratingscale'        => $rating_scale,
            'number'    => $number_of_reviews,
            'average'    => $averagecomments,
            'calification'    => $calification,
            
        );

        return $data;
    }

    public static function getProductReviewsDetails()
    {
        $product = Context::getContext()->controller->getProduct();
        $lang = Context::getContext()->language->id;
        
        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $id_product = $product->id;

        $rating_scale = 5;
        $reviews = self::getProductReviews();
        
        if ($rating_scale == 5) {
            foreach ($reviews as $index => $r) {
                $reviews[$index]['rating'] = ceil($r['rating']);
                $reviews[$index]['stars'] = ceil($r['rating']) * 2; // Calificación en una escala de 10
            }
        }
        
        $number_of_reviews = (int)self::getNummberOfReviews((int)$id_product);
        $sum_of_reviews = self::getSumOfReviews((int)$id_product);
        $averagecomments = $number_of_reviews > 0 ? round($sum_of_reviews / $number_of_reviews, 1) : 0;
        
        // Calificación en porcentaje para el ancho de las estrellas llenas
        $calification = ceil($averagecomments)  * 10;
        $averagecomments = ceil($averagecomments) / 2;

        $id_product = (int)Context::getContext()->controller->getProduct()->id;

        $params = array (
            'back' => Context::getContext()->link->getProductLink(
                (int)$id_product,
                null,
                null,
                null,
                (int)Context::getContext()->language->id
            ),
        );


        $fivestars = self::getCountProdByRate(8, 10);
        $fourstars = self::getCountProdByRate(6, 8);
        $threestars = self::getCountProdByRate(4, 6);
        $twostars = self::getCountProdByRate(2, 4);
        $onestar = self::getCountProdByRate(0, 2);
        $zerostar = self::getCountProdByRate(0, 0);

       $fiveStarsPercent = ($number_of_reviews > 0) ? round(($fivestars / $number_of_reviews) * 100, 2) : 0;
        $fourStarsPercent = ($number_of_reviews > 0) ? round(($fourstars / $number_of_reviews) * 100, 2) : 0;
        $threeStarsPercent = ($number_of_reviews > 0) ? round(($threestars / $number_of_reviews) * 100, 2) : 0;
        $twoStarsPercent = ($number_of_reviews > 0) ? round(($twostars / $number_of_reviews) * 100, 2) : 0;
        $oneStarPercent = ($number_of_reviews > 0) ? round(($onestar / $number_of_reviews) * 100, 2) : 0;
        $zeroStarPercent = ($number_of_reviews > 0) ? round(($zerostar / $number_of_reviews) * 100, 2) : 0;

        $data = array (
            'product'            => $product,
            'lang_iso'           => pSQL(Context::getContext()->language->iso_code),
            'id_customer'        => (int)Context::getContext()->customer->id,
            'id_product'         => (int)$id_product,
            'logged'             => (bool)Context::getContext()->customer->id,
            'fivestars'          => $fivestars,
            'fourstars'          => $fourstars,
            'threestars'         => $threestars,
            'twostars'           => $twostars,
            'onestar'            => $onestar,
            'zerostar'           => $zerostar,
            'fiveStarsPercent'          => $fiveStarsPercent,
            'fourStarsPercent'          => $fourStarsPercent,
            'threeStarsPercent'         => $threeStarsPercent,
            'twoStarsPercent'           => $twoStarsPercent,
            'oneStarPercent'            => $oneStarPercent,
            'zeroStarPercent'           => $zeroStarPercent,
            'reviews'         => $reviews,
            'dateformat'         => LGUtils::getDateFormat(),
            'numlgcomments'      => self::getCountProdComments(),
            'alreadyreviewed'    => self::checkIfProdAlreadyReviewed(),
            'authentication_url' => Context::getContext()->link->getPageLink('authentication', true, null, $params),
            'ratingscale'        => $rating_scale,
            'number'    => $number_of_reviews,
            'average'    => $averagecomments,
            'calification'    => $calification,
            
        );

        return $data;
    }


    public static function getProductDetails($id_product)
    {


        $rating_scale = 5;
        $reviews      = self::getProductReviewss($id_product);
        if ($rating_scale == 5) {
            foreach ($reviews as $index => $r) {
                if ($rating_scale == 5) {
                    $reviews[$index]['rating'] = ceil($r['rating']);
                    $reviews[$index]['stars'] = ceil($r['rating']) * 2;
                }
            }
        }

        //Tools::dieObject($reviews);

        $number_of_reviews = (int)self::getNummberOfReviews((int)$id_product);
        $sum_of_reviews    = self::getSumOfReviews((int)$id_product);
        $averagecomments   = $number_of_reviews > 0 ? round($sum_of_reviews / $number_of_reviews, 1) : 0;

        $averagecomments = ceil($averagecomments) / 2 * 2;

        $id_product = (int)Context::getContext()->controller->getProduct()->id;

        $params = array (
            'back' => Context::getContext()->link->getProductLink(
                (int)$id_product,
                null,
                null,
                null,
                (int)Context::getContext()->language->id
            ),
        );


        $data = array (
            'product'            => $product,
            'modules_dir'        => _MODULE_DIR_ . 'lgcomments/',
            'lang_iso'           => pSQL(Context::getContext()->language->iso_code),
            'id_customer'        => (int)Context::getContext()->customer->id,
            'id_product'         => (int)$id_product,
            'logged'             => (bool)Context::getContext()->customer->id,
            'fivestars'          => self::getCountProdByRate(8, 10),
            'fourstars'          => self::getCountProdByRate(6, 8),
            'threestars'         => self::getCountProdByRate(4, 6),
            'twostars'           => self::getCountProdByRate(2, 4),
            'onestar'            => self::getCountProdByRate(0, 2),
            'zerostar'           => self::getCountProdByRate(0, 0),
            'reviews'         => $reviews,
            'dateformat'         => LGUtils::getDateFormat(),
            'numlgcomments'      => self::getCountProdComments(),
            'alreadyreviewed'    => self::checkIfProdAlreadyReviewed(),
            'productform'        => Configuration::get('PS_LGCOMMENTS_PRODUCT_FORM'),
            'starstyle'          => Configuration::get('PS_LGCOMMENTS_STARDESIGN1'),
            'starcolor'          => Configuration::get('PS_LGCOMMENTS_STARDESIGN2'),
            'starsize'           => Configuration::get('PS_LGCOMMENTS_STARSIZE'),
            'tab_type'           => Configuration::get('PS_LGCOMMENTS_TAB_CONTENT'),
            'defaultdisplay'     => Configuration::get('PS_LGCOMMENTS_DISPLAY_DEFAULT'),
            'productfilter'      => Configuration::get('PS_LGCOMMENTS_PRODUCT_FILTER'),
            'productfilternb'    => Configuration::get('PS_LGCOMMENTS_PRODUCT_FILTER_NB'),
            'authentication_url' => Context::getContext()->link->getPageLink('authentication', true, null, $params),
            'ratingscale'        => $rating_scale,
            'numberofreviews'    => $number_of_reviews,
            'averagecomments'    => $averagecomments,
            'display_product_rich_snippets' => Configuration::get('PS_LGCOMMENTS_DISPLAY_SNIPPETS2'),
            'display_product_schema_in_product_sheet' =>
                Configuration::get('PS_LGCOMMENTS_DISPLAY_PROD_SCHE2'),
        );

        return $data;
    }


    public static function getExtraRightDetails()
    {
        $id_product = (int)Tools::getValue('id_product', 0);
        $product    = new ProductCore($id_product, false, Context::getContext()->language->id);

        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $number_of_reviews = (int)self::getNummberOfReviews($id_product);
        $sum_of_reviews    = self::getSumOfReviews($id_product);
        $averagecomments   = $number_of_reviews > 0 ? round($sum_of_reviews / $number_of_reviews, 1) : 0;

        if (Configuration::get('PS_LGCOMMENTS_SCALE') == 5) {
            $averagecomments = ceil($averagecomments) / 2 * 2;
        }

        $data = array (
            'lgcomments_content_dir' => _MODULE_DIR_ . 'lgcomments/',
            'numberofreviews'        => $number_of_reviews,
            'averagecomments'        => $averagecomments,
            'starstyle'              => Configuration::get('PS_LGCOMMENTS_STARDESIGN1'),
            'starcolor'              => Configuration::get('PS_LGCOMMENTS_STARDESIGN2'),
            'starsize'               => Configuration::get('PS_LGCOMMENTS_STARSIZE'),
            'ratingscale'            => Configuration::get('PS_LGCOMMENTS_SCALE'),
            'displayzerostar'        => Configuration::get('PS_LGCOMMENTS_DISPLAY_ZEROSTAR'),
            'prodtopmargin'          => Configuration::get('PS_LGCOMMENTS_PRODTOPMARGIN'),
            'prodbotmargin'          => Configuration::get('PS_LGCOMMENTS_PRODBOTMARGIN'),
        );

        return $data;
    }

    public static function getAllProductComments()
    {
        $productComments = Db::getInstance()->ExecuteS(
            'SELECT * ' .
            'FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` '.
            'ORDER BY ' . self::$definition['primary'] . ' ASC'
        );
        return $productComments;
    }



    public static function allowProductsComments()
    {
        return (Configuration::get('PS_LGCOMMENTS_OPINION_FORM') == 1
            || Configuration::get('PS_LGCOMMENTS_OPINION_FORM') == 3);
    }

    public static function getLastPosition()
    {
        $sql = 'SELECT MAX(`position`) FROM `'._DB_PREFIX_.self::$definition['table'].'`';
        return (int)Db::getInstance()->getValue($sql) + 1;
    }

    public static function getRatingConversion($rating)
    {
        $multiplier = 1;
        switch (Configuration::get('PS_LGCOMMENTS_SCALE')) {
            case 20:
                $multiplier = 2;
                break;
            case 5:
                $multiplier = 0.5;
                break;
            default:
                $multiplier = 1;
                break;
        }
        return $rating * $multiplier;
    }

    /*************************************************************************************************************/
    /*                                                                                                           */
    /*                                                GDPR Methods                                               */
    /*                                                                                                           */
    /*************************************************************************************************************/

    public static function anonymize($id_customer)
    {
        $customer = new Customer($id_customer);
        if (Validate::isLoadedObject($customer)) {
            $sql = 'UPDATE `'._DB_PREFIX_.self::$definition['table'].'` '.
                'SET '.
                '   `id_customer` = 0, '.
                '   `nick` = "" '.
                'WHERE `id_customer` = '.$customer->id;
            return Db::getInstance()->execute($sql);
        } else {
            $module = Module::getInstanceByName('lgcomments');
            throw new Exception(
                $module->l(
                    'Customer does not exists.'
                )
            );
        }
    }

    public static function exportData($id_customer)
    {
        $customer = new Customer($id_customer);
        if (Validate::isLoadedObject($customer)) {
            $query = new DbQuery();
            $query->from(self::$definition['table']);
            $query->where('`id_customer` = ' . (int)$customer->id);
            $res = Db::getInstance()->executeS($query);

            $data = array();
            foreach ($res as $msg) {
                $data[] = array(
                    'Type'    => 'Product Comment',
                    'Id'      => $msg[self::$definition['primary']],
                    'Nick'    => $msg['nick'],
                    'Title'   => $msg['title'],
                    'Message' => $msg['comment'],
                    'Answer'  => $msg['answer'],
                    'Date'    => $msg['date'],
                    'Product' => ProductCore::getProductName($msg['id_product'], $msg['id_product_attribute']),
                );
            }

            return $data;
        } else {
            $module = Module::getInstanceByName('lgcomments');
            throw new Exception(
                $module->l(
                    'Customer does not exists.'
                )
            );
        }
    }

    public static function validate($products = array())
    {
        $errors = array();

        if (!Tools::getValue('sendcomments') || !self::allowProductsComments()) {
            return $errors;
        }

        $module = Module::getInstanceByName('lgcomments');

        foreach ($products as $product) {
            $pcode = $product['id_order_detail'] . '_' . (int)$product['product_id'];
            if (empty(Tools::getValue('product_score_' . $pcode))) {
                $errors[$product['product_id']]['score'] = $module->l(
                    'Please insert a score for the product'
                );
            }
            if (empty(Tools::getValue('product_title_' . $pcode, ''))) {
                $errors[$product['product_id']]['title'] = $module->l(
                    'Please insert a title for the product'
                );
            }

            if (empty(Tools::getValue('product_comment_' . $pcode, ''))) {
                $errors[$product['product_id']]['comment'] = $module->l(
                    'Please insert a comment for the product'
                );
            }
        }
        return array_unique($errors);
    }


    public static function getReviewsByRatings($id_product, $min = null, $max = null, $offset = 0, $limit = null, $id_lang = null)
    {
        // CARLOS: Aunque parecza absurdo la columna position (innecesaria por cierto porque va a coincidir con
        //         la columna id_storecomment que es la key) está ordenada por orden de inserccion
        //         Por lo que para que salgan ordenados por fecha, hay que invertir los órdenes
        $sort_order = ((int)Configuration::get('PS_LGCOMMENTS_DISPLAY_ORDER') == 2)?'ASC':'DESC';
        $sql = 'SELECT st.*, '.
            (
                Configuration::get('PS_LGCOMMENTS_SCALE') == 20
                    ? '( st.stars * 2 ) '
                    : ( Configuration::get('PS_LGCOMMENTS_SCALE') == 5
                    ? ' ROUND( ( st.stars / 2 ), 1) '
                    : ' st.stars '
                )
            ).
            'AS rating '.
            'FROM `'._DB_PREFIX_.self::$definition['table'].'` st '.
            'WHERE st.active = 1 AND st.id_product='.$id_product;
        if (Configuration::get('PS_LGCOMMENTS_DISPLAY_LANGUAGE') == 1) {
            if (is_null($id_lang)) {
                $id_lang = (int)Context::getContext()->language->id;
            }
            $sql .= 'AND st.id_lang = '.(int)$id_lang;
        }
        if (!is_null($min)) {
            $sql .= ' AND st.stars >= '.(int)$min.' ';
        }
        if (!is_null($max)) {
            $sql .= ' AND st.stars < '.(int)$max.' ';
        }
        $sql .= 'ORDER BY st.position '.$sort_order.' ';
        $sql .= 'LIMIT '.((int)$offset * (int)$limit).','.(int)$limit;
//        if (!Tools::getIsset('ajax') && !Tools::getIsset('from-xhr')) {
//            Tools::dieObject($sql, false);
//        }
        $rates = Db::getInstance()->ExecuteS($sql);
//        if (!Tools::getIsset('ajax') && !Tools::getIsset('from-xhr')) {
//            Tools::dieObject($rates, false);
//        }
        return $rates;
    }


    /**
     * @param $min
     * @param $max
     * @param null $id_lang
     * @return false|null|string
     */
    public static function getNumReviewsByRatings($id_product, $min, $max, $id_lang = null)
    {
        $sql = 'SELECT COUNT('.self::$definition['primary'].') AS total '.
            'FROM '._DB_PREFIX_.self::$definition['table'].' '.
            'WHERE active = 1 AND id_product='.$id_product.
            '  AND stars >= '.(int)$min.' '.
            '  AND stars < '.(int)$max.'';

        if (Configuration::get('PS_LGCOMMENTS_DISPLAY_LANGUAGE') == 1) {
            if (is_null($id_lang)) {
                $id_lang = (int)Context::getContext()->language->id;
            }
            $sql .= '  AND id_lang = '.(int)$id_lang;
        }
        $count = Db::getInstance()->getValue($sql);
        return $count;
    }


    /**
     * Get the total sum of all star values
     *
     * @param null $id_lang
     * @return false|null|string
     */
    public static function getSumStarsValues($id_product, $id_lang = null)
    {
        $sql = 'SELECT SUM(stars) AS totalcomentarios '.
            'FROM '._DB_PREFIX_.self::$definition['table'].' '.
            'WHERE active = 1 AND id_product='.$id_product;

        if (Configuration::get('PS_LGCOMMENTS_DISPLAY_LANGUAGE') == 1) {
            if (is_null($id_lang)) {
                $id_lang = (int)Context::getContext()->language->id;
            }
            $sql .= ' AND id_lang = '.(int)$id_lang;
        }

        $sum = Db::getInstance()->getValue($sql);

        return $sum;
    }
}


