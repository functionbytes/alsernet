<?php
/**
 * Custom Cart Confirmation Controller
 * Shows product details after adding to cart
 */

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;

class CartConfirmationController extends FrontController
{
    public $php_self = 'cart-confirmation';
    public $ssl = true;

    protected $id_product;
    protected $id_product_attribute;

    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();

        // Get product parameters
        $this->id_product = (int) Tools::getValue('id_product', null);
        $this->id_product_attribute = (int) Tools::getValue('id_product_attribute', 0);

        // Validate product ID
        if (!$this->id_product) {
            Tools::redirect('index.php?controller=404');
        }
    }

    /**
     * Assign template vars related to page content
     */
    public function initContent()
    {
        parent::initContent();

        // Get product information
        $product = new Product($this->id_product, true, $this->context->language->id);

        if (!Validate::isLoadedObject($product)) {
            Tools::redirect('index.php?controller=404');
        }

        // Get product presenter
        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever($this->context->link),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        // Assemble and present product
        $productForTemplate = $presenter->present(
            $presentationSettings,
            $assembler->assembleProduct(['id_product' => $this->id_product]),
            $this->context->language
        );

        // Get combination information if exists
        $productAttributes = [];
        if ($this->id_product_attribute) {
            $combination = new Combination($this->id_product_attribute);
            if (Validate::isLoadedObject($combination)) {
                $productAttributes = $combination->getAttributesName($this->context->language->id);
            }
        }

        // Get cart information
        $presenter = new CartPresenter();
        $presented_cart = $presenter->present($this->context->cart, $shouldSeparateGifts = true);

        // Assign variables to template
        $this->context->smarty->assign([
            'product' => $productForTemplate,
            'cart' => $presented_cart,
            'id_product' => $this->id_product,
            'id_product_attribute' => $this->id_product_attribute,
            'product_attributes' => $productAttributes,
        ]);

        $this->setTemplate('checkout/cart-confirmation');
    }

    /**
     * Canonical redirection disabled
     */
    public function canonicalRedirection($canonicalURL = '')
    {
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => $this->trans('Cart Confirmation', [], 'Shop.Theme.Checkout'),
            'url' => '',
        ];

        return $breadcrumb;
    }
}
