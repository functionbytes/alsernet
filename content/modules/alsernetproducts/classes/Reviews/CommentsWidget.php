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

class LGCommentsWidget
{
    public static function loadMedia($module_name)
    {
        $context = Context::getContext();

        if (version_compare(_PS_VERSION_, '1.7.0', '<')) {
            // Línea Gráfica - Carlos Utrera:
            // Se cargan los estilos y script mediante el controlador no en las plantillas
            $context->controller->addCSS(
                _PS_MODULE_DIR_ . $module_name .
                DIRECTORY_SEPARATOR . 'views' .
                DIRECTORY_SEPARATOR . 'css' .
                DIRECTORY_SEPARATOR . 'store_widget.css'
            );
            $context->controller->addCSS(_THEME_CSS_DIR_ . 'global.css', 'all');
            $context->controller->addJquery();
            $context->controller->addJS(
                _PS_MODULE_DIR_ . $module_name .
                DIRECTORY_SEPARATOR . 'views' .
                DIRECTORY_SEPARATOR . 'js' .
                DIRECTORY_SEPARATOR . 'store_widget.js'
            );
        } else {
            $context->controller->registerJavascript(
                'lgcomments_widget_script',
                'modules'.
                DIRECTORY_SEPARATOR.$module_name.
                DIRECTORY_SEPARATOR.'views'.
                DIRECTORY_SEPARATOR.'js'.
                DIRECTORY_SEPARATOR.'store_widget.js',
                array('position' => 'footer', 'priority' => 150)
            );
            $context->controller->registerStylesheet(
                'lgcomments_widget_css',
                'modules'.
                DIRECTORY_SEPARATOR.$module_name.
                DIRECTORY_SEPARATOR.'views'.
                DIRECTORY_SEPARATOR.'css'.
                DIRECTORY_SEPARATOR.'store_widget.css',
                array('media' => 'all', 'priority' => 150)
            );
        }
    }

    public static function getTemplate()
    {
        return DIRECTORY_SEPARATOR.'views'.
            DIRECTORY_SEPARATOR.'templates'.
            DIRECTORY_SEPARATOR.'front'.
            DIRECTORY_SEPARATOR.'store_widget.tpl';
    }

    /**
     * Returns an array of template vars needed to render de widget
     *
     * @param $module
     * @return array
     */
    public static function getTemplateVars($module)
    {
        $visualizar_en_columna = (Configuration::get('PS_LGCOMMENTS_DISPLAY_TYPE') == 2);

        if (Configuration::get('PS_LGCOMMENTS_DISPLAY_LANGUAGE') == 1) {
            $totalcomentarios  = LGStoreComment::getSumShopCommentsByLang();
            $numerocomentarios = LGStoreComment::getCountShopCommentsByLang();
            $comentarioazar    = LGStoreComment::getRandomShopCommentByLang();
        } else {
            $totalcomentarios  = LGStoreComment::getSumShopComments();
            $numerocomentarios = LGStoreComment::getCountShopComments();
            $comentarioazar    = LGStoreComment::getRandomShopComment();
        }

        $mediacomentarios  = 0;
        $mediacomentarios2 = 0;

        if ($numerocomentarios > 0) {
            $mediacomentarios  = @round($totalcomentarios / $numerocomentarios);
            $mediacomentarios2 = @round($totalcomentarios / $numerocomentarios, 1);
        }

        $reviewpage = _PS_BASE_URL_.__PS_BASE_URI__.'module/lgcomments/reviews';
        $css_config = unserialize(Configuration::get('PS_LGCOMMENTS_CSS_CONF', ''));
        if (empty($css_config)) {
            $css_config = LGUtils::getExtraRightCSSConfig('customer');
        }

        /* How to display the store review page */
        if (substr_count(_PS_VERSION_, '1.6') > 0) {
            $ps16 = true;
        } else {
            $ps16 = false;
        }

        $rating_scale = Configuration::get('PS_LGCOMMENTS_SCALE');
        foreach ($comentarioazar as $index => $c) {
            if ($rating_scale == 5) {
                $comentarioazar[$index]['stars'] = ceil($c['stars'] / 2);
            } elseif ($rating_scale == 5) {
                $comentarioazar[$index]['stars'] = $c['stars'] * 2;
            }
        }
        $context = Context::getContext();
        $shop_url = $context->shop->getBaseURL(true, true);

        $base = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ?
            'https://' . $context->shop->domain_ssl :
            'http://' . $context->shop->domain);

        $configuration = array(
            'lgcomments_shop_url' => $shop_url,
            'ratingscale'         => $rating_scale,
            'display_side'        => self::getPosition(),
            'numericRating'       => self::getNumericRating($mediacomentarios),
            'ps16'                => $ps16,
            'numerocomentarios'   => $numerocomentarios,
            'mediacomentarios'    => $mediacomentarios,
            'mediacomentarios2'   => $mediacomentarios2,
            'comentarioazar'      => $comentarioazar,
            'reviewpage'          => $reviewpage,
            'starstyle'           => Configuration::get('PS_LGCOMMENTS_STARDESIGN1'),
            'starcolor'           => Configuration::get('PS_LGCOMMENTS_STARDESIGN2'),
            'starsize'            => Configuration::get('PS_LGCOMMENTS_STARSIZE'),
            'displaycross'        => Configuration::get('PS_LGCOMMENTS_CROSS') && !$visualizar_en_columna,
            'bgdesign1'           => Configuration::get('PS_LGCOMMENTS_BGDESIGN1'),
            'bgdesign2'           => Configuration::get('PS_LGCOMMENTS_BGDESIGN2'),
            'bgwidth'             => $css_config['widget']['width'],
            'bgheight'            => $css_config['widget']['height'],
            'top0'                => $css_config['title']['top'],
            'left0'               => $css_config['title']['left'],
            'color0'              => '#777777',
            'width0'              => $css_config['title']['width'],
            'textalign0'          => $css_config['title']['text-align'],
            'fontfamily0'         => $css_config['title']['font-family'],
            'fontsize0'           => $css_config['title']['font-size'],
            'fontweight0'         => $css_config['title']['font-weight'],
            'lineheight0'         => $css_config['title']['line-height'],
            'rotate0'             => $css_config['title']['rotate'],
            'top1'                => $css_config['rating']['top'],
            'left1'               => $css_config['rating']['left'],
            'color1'              => '#777777',
            'width1'              => $css_config['rating']['width'],
            'textalign1'          => $css_config['rating']['text-align'],
            'fontfamily1'         => $css_config['rating']['font-family'],
            'fontsize1'           => $css_config['rating']['font-size'],
            'fontweight1'         => $css_config['rating']['font-weight'],
            'top2'                => $css_config['review']['top'],
            'left2'               => $css_config['review']['left'],
            'color2'              => '#777777',
            'width2'              => $css_config['review']['width'],
            'textalign2'          => $css_config['review']['text-align'],
            'fontfamily2'         => $css_config['review']['font-family'],
            'fontsize2'           => $css_config['review']['font-size'],
            'fontweight2'         => $css_config['review']['font-weight'],
            'top3'                => $css_config['stars']['top'],
            'left3'               => $css_config['stars']['left'],
            'width3'              => $css_config['stars']['width'],
            'rotate3'             => $css_config['stars']['rotate'],
            'top4'                => $css_config['see-more']['top'],
            'left4'               => $css_config['see-more']['left'],
            'color4'              => '#777777',
            'width4'              => $css_config['see-more']['width'],
            'textalign4'          => $css_config['see-more']['text-align'],
            'fontfamily4'         => $css_config['see-more']['font-family'],
            'fontsize4'           => $css_config['see-more']['font-size'],
            'fontweight4'         => $css_config['see-more']['font-weight'],
            'background5'         => Configuration::get('PS_LGCOMMENTS_BACKGROUND5'),
            'bordersize5'         => Configuration::get('PS_LGCOMMENTS_BORDERSIZE5'),
            'bordercolor5'        => Configuration::get('PS_LGCOMMENTS_BORDERCOLOR5'),
            'ratecolor5'          => Configuration::get('PS_LGCOMMENTS_RATECOLOR5'),
            'ratesize5'           => Configuration::get('PS_LGCOMMENTS_RATESIZE5'),
            'ratefamily5'         => Configuration::get('PS_LGCOMMENTS_RATEFAMILY5'),
            'commentcolor5'       => Configuration::get('PS_LGCOMMENTS_COMMENTCOLOR5'),
            'commentsize5'        => Configuration::get('PS_LGCOMMENTS_COMMENTSIZE5'),
            'commentfamily5'      => Configuration::get('PS_LGCOMMENTS_COMMENTFAMILY5'),
            'commentalign5'       => Configuration::get('PS_LGCOMMENTS_COMMENTALIGN5'),
            'datecolor5'          => Configuration::get('PS_LGCOMMENTS_DATECOLOR5'),
            'datesize5'           => Configuration::get('PS_LGCOMMENTS_DATESIZE5'),
            'datefamily5'         => Configuration::get('PS_LGCOMMENTS_DATEFAMILY5'),
            'datealign5'          => Configuration::get('PS_LGCOMMENTS_DATEALIGN5'),
            'top6'                => Configuration::get('PS_LGCOMMENTS_TOP6'),
            'left6'               => Configuration::get('PS_LGCOMMENTS_LEFT6'),
            'top7'                => $css_config['cross']['top'],
            'right7'              => $css_config['cross']['right'],
            'widgettextcolor'     => Configuration::get('PS_LGCOMMENTS_TEXTCOLOR'),
            'path_lgcomments'     => _MODULE_DIR_.$module->name,
            'footer_mode'         => Configuration::get('PS_LGCOMMENTS_DISPLAY_TYPE') == 2 ? 1 : 0,
            'shop_name'           => Configuration::get('PS_SHOP_NAME'),
            'address_street1'     => Configuration::get('PS_SHOP_ADDR1'),
            'address_street2'     => Configuration::get('PS_SHOP_ADDR2'),
            'address_zip'         => Configuration::get('PS_SHOP_CODE'),
            'address_city'        => Configuration::get('PS_SHOP_CITY'),
            'address_state'       => Configuration::get('PS_SHOP_STATE'),
            'address_country'     => Configuration::get('PS_SHOP_COUNTRY'),
            'address_phone'       => Configuration::get('PS_SHOP_PHONE'),
            'price_range'         => Configuration::get('PS_LGCOMMENTS_PRICE_RANGE'),
            'displaysnippets'     => Configuration::get('PS_LGCOMMENTS_DISPLAY_SNIPPETS'),
            'base_url'            => $base,
        );

        return $configuration;
    }

    public static function isActive()
    {
        return Configuration::get('PS_LGCOMMENTS_DISPLAY');
    }

    public static function getPosition()
    {
        $position              = '';
        $visualizar_en_columna = (Configuration::get('PS_LGCOMMENTS_DISPLAY_TYPE') == 2);
        if (!$visualizar_en_columna) {
            switch (Configuration::get('PS_LGCOMMENTS_DISPLAY_SIDE')) {
                case 2:
                    $position = 'middleleft';
                    break;
                case 3:
                    $position = 'bottomleft';
                    break;
                case 4:
                    $position = 'topright';
                    break;
                case 5:
                    $position = 'middleright';
                    break;
                case 6:
                    $position = 'bottomright';
                    break;
                default:
                    $position = 'topleft';
                    break;
            }
        }
        return $position;
    }

    /**
     * Retunrs a representation of the rating for the rating scale stablished. For example if puntuation is 4 and
     * actual rating scale is 10 return 4/10
     *
     * @param $puntuation
     * @return string
     */
    public static function getNumericRating($puntuation)
    {
        $rating_scale = Configuration::get('PS_LGCOMMENTS_SCALE');
        return round(($puntuation*($rating_scale/10)), 1).'/'.$rating_scale;
    }
}
