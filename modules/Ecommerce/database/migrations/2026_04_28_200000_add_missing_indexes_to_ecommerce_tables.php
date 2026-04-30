<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Core tables
        Schema::table('ecommerce_product_variations', function (Blueprint $table): void {
            $table->index(['product_id', 'configurable_product_id'], 'ec_prod_var_prod_conf_idx');
        });

        Schema::table('ecommerce_wish_lists', function (Blueprint $table): void {
            $table->index('customer_id', 'ec_wl_cust_idx');
            $table->index('product_id', 'ec_wl_prod_idx');
        });

        Schema::table('ecommerce_grouped_products', function (Blueprint $table): void {
            $table->index('parent_product_id', 'ec_grp_parent_idx');
            $table->index('product_id', 'ec_grp_prod_idx');
        });

        Schema::table('ecommerce_product_attributes', function (Blueprint $table): void {
            $table->index('attribute_set_id', 'ec_prod_attr_set_idx');
        });

        // Order/shipment tables
        Schema::table('ecommerce_order_addresses', function (Blueprint $table): void {
            $table->index('order_id', 'ec_ord_addr_ord_idx');
        });

        Schema::table('ecommerce_shipment_histories', function (Blueprint $table): void {
            $table->index('shipment_id', 'ec_ship_hist_ship_idx');
            $table->index('order_id', 'ec_ship_hist_ord_idx');
        });

        Schema::table('ecommerce_invoice_items', function (Blueprint $table): void {
            $table->index('invoice_id', 'ec_inv_item_inv_idx');
        });

        Schema::table('ecommerce_shipping_rules', function (Blueprint $table): void {
            $table->index('shipping_id', 'ec_ship_rule_ship_idx');
            $table->index('currency_id', 'ec_ship_rule_curr_idx');
        });

        Schema::table('ecommerce_shipping_rule_items', function (Blueprint $table): void {
            $table->index('shipping_rule_id', 'ec_ship_rule_item_idx');
        });

        // Discount/return tables
        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            $table->index('order_id', 'ec_ord_ret_ord_idx');
            $table->index('user_id', 'ec_ord_ret_user_idx');
        });

        Schema::table('ecommerce_order_return_items', function (Blueprint $table): void {
            $table->index('order_return_id', 'ec_ord_ret_item_idx');
        });

        Schema::table('ecommerce_order_referrals', function (Blueprint $table): void {
            $table->index('order_id', 'ec_ord_ref_ord_idx');
        });

        Schema::table('ecommerce_order_tax_information', function (Blueprint $table): void {
            $table->index('order_id', 'ec_ord_tax_ord_idx');
        });

        Schema::table('ecommerce_order_return_histories', function (Blueprint $table): void {
            $table->index('order_return_id', 'ec_ord_ret_hist_idx');
        });

        // Misc tables
        Schema::table('ecommerce_customer_recently_viewed_products', function (Blueprint $table): void {
            $table->index('customer_id', 'ec_cust_rv_cust_idx');
            $table->index('product_id', 'ec_cust_rv_prod_idx');
        });

        Schema::table('ecommerce_product_files', function (Blueprint $table): void {
            $table->index('product_id', 'ec_prod_file_prod_idx');
        });

        Schema::table('ecommerce_product_views', function (Blueprint $table): void {
            $table->index('product_id', 'ec_prod_view_prod_idx');
            $table->index(['product_id', 'date'], 'ec_prod_view_prod_date_idx');
        });

        Schema::table('ecommerce_customer_used_coupons', function (Blueprint $table): void {
            $table->index('customer_id', 'ec_cust_coupon_cust_idx');
            $table->index('discount_id', 'ec_cust_coupon_disc_idx');
        });

        Schema::table('ecommerce_flash_sale_products', function (Blueprint $table): void {
            $table->index('flash_sale_id', 'ec_fs_prod_fs_idx');
            $table->index('product_id', 'ec_fs_prod_prod_idx');
        });

        Schema::table('ecommerce_customer_deletion_requests', function (Blueprint $table): void {
            $table->index('customer_id', 'ec_cust_del_cust_idx');
        });

        Schema::table('ecommerce_shared_wishlists', function (Blueprint $table): void {
            $table->index('customer_id', 'ec_shared_wl_cust_idx');
        });

        // Options/spec tables
        Schema::table('ecommerce_option_values', function (Blueprint $table): void {
            $table->index('option_id', 'ec_opt_val_opt_idx');
        });

        Schema::table('ecommerce_specification_attributes', function (Blueprint $table): void {
            $table->index('group_id', 'ec_spec_attr_grp_idx');
        });

        // Composite indexes for high-traffic queries
        Schema::table('ecommerce_carts', function (Blueprint $table): void {
            $table->index(['customer_id', 'product_id'], 'ec_cart_cust_prod_idx');
            $table->index(['session_id', 'product_id'], 'ec_cart_sess_prod_idx');
        });

        Schema::table('ecommerce_reviews', function (Blueprint $table): void {
            $table->index(['product_id', 'status', 'star'], 'ec_rev_prod_stat_star_idx');
        });

        Schema::table('ecommerce_subscriptions', function (Blueprint $table): void {
            $table->index('product_id', 'ec_sub_prod_idx');
        });

        Schema::table('ecommerce_bundle_products', function (Blueprint $table): void {
            $table->index('product_id', 'ec_bundle_prod_prod_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_product_variations', function (Blueprint $table): void {
            $table->dropIndex('ec_prod_var_prod_conf_idx');
        });

        Schema::table('ecommerce_wish_lists', function (Blueprint $table): void {
            $table->dropIndex('ec_wl_cust_idx');
            $table->dropIndex('ec_wl_prod_idx');
        });

        Schema::table('ecommerce_grouped_products', function (Blueprint $table): void {
            $table->dropIndex('ec_grp_parent_idx');
            $table->dropIndex('ec_grp_prod_idx');
        });

        Schema::table('ecommerce_product_attributes', function (Blueprint $table): void {
            $table->dropIndex('ec_prod_attr_set_idx');
        });

        Schema::table('ecommerce_order_addresses', function (Blueprint $table): void {
            $table->dropIndex('ec_ord_addr_ord_idx');
        });

        Schema::table('ecommerce_shipment_histories', function (Blueprint $table): void {
            $table->dropIndex('ec_ship_hist_ship_idx');
            $table->dropIndex('ec_ship_hist_ord_idx');
        });

        Schema::table('ecommerce_invoice_items', function (Blueprint $table): void {
            $table->dropIndex('ec_inv_item_inv_idx');
        });

        Schema::table('ecommerce_shipping_rules', function (Blueprint $table): void {
            $table->dropIndex('ec_ship_rule_ship_idx');
            $table->dropIndex('ec_ship_rule_curr_idx');
        });

        Schema::table('ecommerce_shipping_rule_items', function (Blueprint $table): void {
            $table->dropIndex('ec_ship_rule_item_idx');
        });

        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            $table->dropIndex('ec_ord_ret_ord_idx');
            $table->dropIndex('ec_ord_ret_user_idx');
        });

        Schema::table('ecommerce_order_return_items', function (Blueprint $table): void {
            $table->dropIndex('ec_ord_ret_item_idx');
        });

        Schema::table('ecommerce_order_referrals', function (Blueprint $table): void {
            $table->dropIndex('ec_ord_ref_ord_idx');
        });

        Schema::table('ecommerce_order_tax_information', function (Blueprint $table): void {
            $table->dropIndex('ec_ord_tax_ord_idx');
        });

        Schema::table('ecommerce_order_return_histories', function (Blueprint $table): void {
            $table->dropIndex('ec_ord_ret_hist_idx');
        });

        Schema::table('ecommerce_customer_recently_viewed_products', function (Blueprint $table): void {
            $table->dropIndex('ec_cust_rv_cust_idx');
            $table->dropIndex('ec_cust_rv_prod_idx');
        });

        Schema::table('ecommerce_product_files', function (Blueprint $table): void {
            $table->dropIndex('ec_prod_file_prod_idx');
        });

        Schema::table('ecommerce_product_views', function (Blueprint $table): void {
            $table->dropIndex('ec_prod_view_prod_idx');
            $table->dropIndex('ec_prod_view_prod_date_idx');
        });

        Schema::table('ecommerce_customer_used_coupons', function (Blueprint $table): void {
            $table->dropIndex('ec_cust_coupon_cust_idx');
            $table->dropIndex('ec_cust_coupon_disc_idx');
        });

        Schema::table('ecommerce_flash_sale_products', function (Blueprint $table): void {
            $table->dropIndex('ec_fs_prod_fs_idx');
            $table->dropIndex('ec_fs_prod_prod_idx');
        });

        Schema::table('ecommerce_customer_deletion_requests', function (Blueprint $table): void {
            $table->dropIndex('ec_cust_del_cust_idx');
        });

        Schema::table('ecommerce_shared_wishlists', function (Blueprint $table): void {
            $table->dropIndex('ec_shared_wl_cust_idx');
        });

        Schema::table('ecommerce_option_values', function (Blueprint $table): void {
            $table->dropIndex('ec_opt_val_opt_idx');
        });

        Schema::table('ecommerce_specification_attributes', function (Blueprint $table): void {
            $table->dropIndex('ec_spec_attr_grp_idx');
        });

        Schema::table('ecommerce_carts', function (Blueprint $table): void {
            $table->dropIndex('ec_cart_cust_prod_idx');
            $table->dropIndex('ec_cart_sess_prod_idx');
        });

        Schema::table('ecommerce_reviews', function (Blueprint $table): void {
            $table->dropIndex('ec_rev_prod_stat_star_idx');
        });

        Schema::table('ecommerce_subscriptions', function (Blueprint $table): void {
            $table->dropIndex('ec_sub_prod_idx');
        });

        Schema::table('ecommerce_bundle_products', function (Blueprint $table): void {
            $table->dropIndex('ec_bundle_prod_prod_idx');
        });
    }
};
