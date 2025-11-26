<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

require_once _PS_MODULE_DIR_ . 'alsernetshopping/controllers/front/GtmController.php';

class OrderConfirmationController extends OrderConfirmationControllerCore
{
    public function initContent()
    {
        parent::initContent();

        $order = new Order(Order::getIdByCartId((int) $this->id_cart));
        $tradedoublerpixels = $order->getTradeDoublerPixels();

        // Mostrar documentos solo si la orden está pagada
        // Estado 2 = Pago recibido (PS_OS_PAYMENT)
        $document = '';

        $paidOrderStateId = (int)Configuration::get('PS_OS_PAYMENT');
        if ($order->current_state == $paidOrderStateId || $order->current_state == 2) {
            $document = $order->getUrlDocuments();
        }

        // Asegurar que el contexto tenga el cart correcto para productos customizados
        if ($order->id_cart && (!$this->context->cart || !$this->context->cart->id)) {
            $this->context->cart = new Cart($order->id_cart);
        }

        // Procesar productos del pedido con getProductProperties para aplicar correcciones de descripción
        $orderProducts = $order->getProducts();
        $processedProducts = [];

        foreach ($orderProducts as $product) {
            // Cargar datos completos del producto desde la BD
            $sql = 'SELECT p.*, product_shop.*, pl.*,
                    image_shop.`id_image` id_image,
                    il.`legend` as legend,
                    m.`name` AS manufacturer_name,
                    cl.`name` AS category_default,
                    DATEDIFF(product_shop.`date_add`, DATE_SUB("'.date('Y-m-d').' 00:00:00", INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY)) > 0 AS new,
                    product_shop.price AS orderprice
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
                    ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int)$this->context->language->id.Shop::addSqlRestrictionOnLang('pl').')
                LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
                    ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int)$this->context->shop->id.')
                LEFT JOIN `'._DB_PREFIX_.'image_lang` il
                    ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$this->context->language->id.')
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
                    ON m.`id_manufacturer` = p.`id_manufacturer`
                LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
                    ON (product_shop.`id_category_default` = cl.`id_category` AND cl.`id_lang` = '.(int)$this->context->language->id.Shop::addSqlRestrictionOnLang('cl').')
                WHERE p.`id_product` = '.(int)$product['id_product'];

            $productData = Db::getInstance()->getRow($sql);

            if ($productData) {
                // Combinar datos del pedido con datos completos del producto
                $fullProduct = array_merge($productData, $product);
                $fullProduct['id_cart'] = $order->id_cart;  // Agregar id_cart para detección

                // Procesar el producto con getProductProperties
                $processedProduct = Product::getProductProperties(
                    $this->context->language->id,
                    $fullProduct,
                    $this->context
                );

                if ($processedProduct) {
                    // Mantener datos originales del pedido (precio, cantidad, etc.)
                    $processedProduct['quantity'] = $product['product_quantity'];
                    $processedProduct['price'] = $product['product_price_wt'];
                    $processedProduct['total'] = $product['total_price_tax_incl'];
                    $processedProduct['product_reference'] = $product['product_reference'];

                    // Asegurar que las imágenes estén correctamente cargadas
                    if (isset($productData['id_image']) && $productData['id_image']) {
                        $processedProduct['id_image'] = $productData['id_image'];
                        $processedProduct['cover'] = [
                            'id_image' => $productData['id_image'],
                            'legend' => !empty($productData['legend']) ? $productData['legend'] : $processedProduct['name'],
                            'bySize' => []
                        ];

                        // Generar URLs para diferentes tamaños de imagen
                        $imageTypes = ImageType::getImagesTypes('products');
                        foreach ($imageTypes as $imageType) {
                            $imageUrl = $this->context->link->getImageLink(
                                $processedProduct['link_rewrite'],
                                $productData['id_image'],
                                $imageType['name']
                            );

                            $processedProduct['cover']['bySize'][$imageType['name']] = [
                                'url' => $imageUrl,
                                'width' => $imageType['width'],
                                'height' => $imageType['height'],
                                'sources' => [
                                    'avif' => str_replace('.jpg', '.avif', $imageUrl),
                                    'webp' => str_replace('.jpg', '.webp', $imageUrl)
                                ]
                            ];
                        }

                        // Asegurar que exista home_default
                        if (!isset($processedProduct['cover']['bySize']['home_default'])) {
                            $processedProduct['cover']['bySize']['home_default'] = [
                                'url' => $this->context->link->getImageLink(
                                    $processedProduct['link_rewrite'],
                                    $productData['id_image'],
                                    'home_default'
                                ),
                                'width' => 250,
                                'height' => 250,
                                'sources' => [
                                    'avif' => $this->context->link->getImageLink(
                                        $processedProduct['link_rewrite'],
                                        $productData['id_image'],
                                        'home_default'
                                    ),
                                    'webp' => $this->context->link->getImageLink(
                                        $processedProduct['link_rewrite'],
                                        $productData['id_image'],
                                        'home_default'
                                    )
                                ]
                            ];
                            // Reemplazar extensión .jpg por .avif y .webp
                            $processedProduct['cover']['bySize']['home_default']['sources']['avif'] =
                                str_replace('.jpg', '.avif', $processedProduct['cover']['bySize']['home_default']['url']);
                            $processedProduct['cover']['bySize']['home_default']['sources']['webp'] =
                                str_replace('.jpg', '.webp', $processedProduct['cover']['bySize']['home_default']['url']);
                        }

                        // Asegurar que exista large
                        if (!isset($processedProduct['cover']['large'])) {
                            $processedProduct['cover']['large'] = [
                                'url' => $this->context->link->getImageLink(
                                    $processedProduct['link_rewrite'],
                                    $productData['id_image'],
                                    'large_default'
                                )
                            ];
                        }
                    }

                    $processedProducts[] = $processedProduct;
                }
            }
        }

        // Prepare GTM purchase data using GtmController
        $gtmController = new GtmController();
        $gtmController->context = $this->context;
        $gtmData = $gtmController->prepareOrderPurchaseData($order);

        $query = 'SELECT `address1` as `delivery_address`
            FROM `' . _DB_PREFIX_ . 'address`
            WHERE id_address = ' . (int) $order->id_address_delivery;
        $deliveryAddress = Db::getInstance()->getValue($query);


        $this->context->smarty->assign([
            'order_obj' => $order,
            'document' => $document,
            'tradedoublerpixels' => $tradedoublerpixels,
            'gtm_purchase_data' => $gtmData,
            'delivery_address_text' => $deliveryAddress,
            'processed_products' => $processedProducts  // Productos procesados con descripciones corregidas
        ]);
    }

}
