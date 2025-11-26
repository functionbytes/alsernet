<?php


class OrderDetail extends OrderDetailCore
{
    /** @var int */
    public $id_order_detail;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_order_invoice;

    /** @var int */
    public $product_id;

    /** @var int */
    public $id_shop;

    /** @var int */
    public $product_attribute_id;

    /** @var int */
    public $id_customization;

    /** @var string */
    public $product_name;

    /** @var int */
    public $product_quantity;

    /** @var int */
    public $product_quantity_in_stock;

    /** @var int */
    public $product_quantity_return;

    /** @var int */
    public $product_quantity_refunded;

    /** @var int */
    public $product_quantity_reinjected;

    /**
     * @deprecated since 1.5 Use unit_price_tax_excl instead
     *
     * @var float Without taxes, includes ecotax
     */
    public $product_price;

    /** @var float */
    public $original_product_price;

    /** @var float With taxes, includes ecotax */
    public $unit_price_tax_incl;

    /** @var float Without taxes, includes ecotax */
    public $unit_price_tax_excl;

    /** @var float With taxes, includes ecotax */
    public $total_price_tax_incl;

    /** @var float Without taxes, includes ecotax */
    public $total_price_tax_excl;

    /** @var float */
    public $reduction_percent;

    /** @var float */
    public $reduction_amount;

    /** @var float */
    public $reduction_amount_tax_excl;

    /** @var float */
    public $reduction_amount_tax_incl;

    /** @var float */
    public $group_reduction;

    /** @var float */
    public $product_quantity_discount;

    /** @var string */
    public $product_ean13;

    /** @var string */
    public $product_isbn;

    /** @var string */
    public $product_upc;

    /** @var string */
    public $product_mpn;

    /** @var string */
    public $product_reference;

    /** @var string */
    public $product_supplier_reference;

    /** @var float */
    public $product_weight;

    /** @var float */
    public $ecotax;

    /** @var float */
    public $ecotax_tax_rate;

    /** @var int */
    public $discount_quantity_applied;

    /** @var string */
    public $download_hash;

    /** @var int */
    public $download_nb;

    /** @var datetime */
    public $download_deadline;

    /**
     * @var string @deprecated Order Detail Tax is saved in order_detail_tax table now
     */
    public $tax_name;

    /**
     * @var float @deprecated Order Detail Tax is saved in order_detail_tax table now
     */
    public $tax_rate;

    /** @var float */
    public $tax_computation_method;

    /** @var int Id tax rules group */
    public $id_tax_rules_group;

    /** @var int Id warehouse */
    public $id_warehouse;

    /** @var float additional shipping price tax excl */
    public $total_shipping_price_tax_excl;

    /** @var float additional shipping price tax incl */
    public $total_shipping_price_tax_incl;

    /** @var float */
    public $purchase_supplier_price;

    /** @var float */
    public $original_wholesale_price;

    /** @var float */
    public $total_refunded_tax_excl;

    /** @var float */
    public $total_refunded_tax_incl;


    public function createListDemoday(Order $order, Cart $cart, $id_order_state, $product, $id_order_invoice = 0, $use_taxes = true, $id_warehouse = 0)
    {
        $this->vat_address = new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $this->customer = new Customer((int) $order->id_customer);

        $this->id_order = $order->id;
        $this->outOfStock = false;

        $this->createDemoday($order, $cart, (array)$product, $id_order_state, $id_order_invoice, $use_taxes, $id_warehouse);
    }


    protected function createDemoday(Order $order, Cart $cart, $product, $id_order_state, $id_order_invoice, $use_taxes = true, $id_warehouse = 0)
    {
        if ($use_taxes) {
            $this->tax_calculator = new TaxCalculator();
        }

        $this->id = null;

        $this->product_id = (int) $product['id_product'];
        $this->product_attribute_id = $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : 0;
        //$this->id_customization = $product['id_customization'] ? (int) $product['id_customization'] : 0;
        $this->product_name = $product['name'] .
            ((isset($product['attributes']) && $product['attributes'] != null) ?
                ' (' . $product['attributes'] . ')' : '');

        $this->product_quantity = (int) $product['cart_quantity'];
        $this->product_price = $product['price'];
        $this->product_ean13 = empty($product['ean13']) ? null : pSQL($product['ean13']);
        $this->product_isbn = empty($product['isbn']) ? null : pSQL($product['isbn']);
        $this->product_upc = empty($product['upc']) ? null : pSQL($product['upc']);
        $this->product_mpn = empty($product['mpn']) ? null : pSQL($product['mpn']);
        $this->product_reference = empty($product['reference']) ? null : pSQL($product['reference']);
        $this->product_supplier_reference = empty($product['supplier_reference']) ? null : pSQL($product['supplier_reference']);
        //$this->product_weight = $product['id_product_attribute'] ? (float) $product['weight_attribute'] : (float) $product['weight'];
        $this->id_warehouse = $id_warehouse;

        $product_quantity = (int) Product::getQuantity($this->product_id, $this->product_attribute_id, null, $cart);
        $this->product_quantity_in_stock = ($product_quantity - (int) $product['cart_quantity'] < 0) ?
            $product_quantity : (int) $product['cart_quantity'];

        // Set order invoice id
        $this->id_order_invoice = (int) $id_order_invoice;

        // Set shop id
        $this->id_shop = (int) $this->context->shop->id;

        // Add new entry to the table
        $this->save();

        if ($use_taxes) {
            $this->saveTaxCalculator($order);
        }
        unset($this->tax_calculator);
    }


}