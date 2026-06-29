<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Models\Invoice;
use Modules\Ecommerce\Models\Shipment;
use Modules\Ecommerce\Models\Shipping;
use Modules\Ecommerce\Models\Tax;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SettingController extends Controller
{
    private const PREFIX = 'ecommerce.';

    private array $keys = [
        'ecommerce.store_name',
        'ecommerce.store_company',
        'ecommerce.store_phone',
        'ecommerce.store_email',
        'ecommerce.store_notification_email',
        'ecommerce.store_state',
        'ecommerce.store_city',
        'ecommerce.store_address',
        'ecommerce.store_tax_id',
        'ecommerce.whatsapp_number',
        'ecommerce.whatsapp_message',
    ];

    public function index(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load($this->keys);

        return view('ecommerce::settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'store_name' => ['nullable', 'string', 'max:100'],
            'store_company' => ['nullable', 'string', 'max:100'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'store_email' => ['nullable', 'email', 'max:100'],
            'store_notification_email' => ['nullable', 'email', 'max:100'],
            'store_state' => ['nullable', 'string', 'max:100'],
            'store_city' => ['nullable', 'string', 'max:100'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'store_tax_id' => ['nullable', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]*$/'],
            'whatsapp_message' => ['nullable', 'string', 'max:255'],
        ]);

        $this->save($request, $this->keys);

        return redirect()->route('settings.ecommerce.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Currencies
    // -------------------------------------------------------------------------

    public function currencies(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.currency_auto_detect',
            'ecommerce.currency_space_between',
            'ecommerce.currency_thousands_separator',
            'ecommerce.currency_decimal_separator',
            'ecommerce.currency_exchange_api_provider',
            'ecommerce.currency_exchange_api_key',
            'ecommerce.currency_open_exchange_app_id',
            'ecommerce.currency_use_api_exchange_rate',
        ]);

        $currenciesJson = Setting::get('ecommerce.currencies');
        $settings['currencies'] = $currenciesJson ? json_decode($currenciesJson, true) : [];

        return view('ecommerce::settings.currencies', compact('settings'));
    }

    public function updateCurrencies(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'currency_thousands_separator' => ['nullable', 'string', 'max:5'],
            'currency_decimal_separator' => ['nullable', 'string', 'max:5'],
            'currency_exchange_api_provider' => ['nullable', 'string', 'in:none,layer,open_exchange'],
            'currency_exchange_api_key' => ['nullable', 'string', 'max:255'],
            'currency_open_exchange_app_id' => ['nullable', 'string', 'max:255'],
            'currencies' => ['nullable', 'array'],
            'currencies.*.code' => ['nullable', 'string', 'max:10'],
            'currencies.*.symbol' => ['nullable', 'string', 'max:10'],
            'currencies.*.decimals' => ['nullable', 'integer', 'min:0', 'max:4'],
            'currencies.*.exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'currencies.*.position' => ['nullable', 'in:before,after'],
            'currency_default_index' => ['nullable', 'integer'],
        ]);

        $this->save($request, [
            'ecommerce.currency_auto_detect',
            'ecommerce.currency_space_between',
            'ecommerce.currency_thousands_separator',
            'ecommerce.currency_decimal_separator',
            'ecommerce.currency_exchange_api_provider',
            'ecommerce.currency_exchange_api_key',
            'ecommerce.currency_open_exchange_app_id',
            'ecommerce.currency_use_api_exchange_rate',
        ], ['currency_auto_detect', 'currency_space_between', 'currency_use_api_exchange_rate']);

        $defaultIndex = (int) $request->input('currency_default_index', 0);
        $currencies = collect($request->input('currencies', []))->map(function ($cur, $i) use ($defaultIndex) {
            return [
                'code' => strtoupper(trim($cur['code'] ?? '')),
                'symbol' => $cur['symbol'] ?? '',
                'decimals' => (int) ($cur['decimals'] ?? 0),
                'exchange_rate' => (float) ($cur['exchange_rate'] ?? 1),
                'position' => $cur['position'] ?? 'before',
                'is_default' => $i == $defaultIndex,
            ];
        })->filter(fn ($c) => $c['code'] !== '')->values()->toArray();

        Setting::set('ecommerce.currencies', json_encode($currencies));

        return redirect()->route('settings.ecommerce.currencies.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Products
    // -------------------------------------------------------------------------

    public function products(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.product_variation_images_type',
            'ecommerce.show_stock_count',
            'ecommerce.show_out_of_stock_products',
            'ecommerce.product_options_enabled',
            'ecommerce.cross_sell_enabled',
            'ecommerce.related_products_enabled',
            'ecommerce.specification_enabled',
            'ecommerce.auto_sku_enabled',
            'ecommerce.auto_sku_format',
            'ecommerce.barcode_required',
        ]);

        return view('ecommerce::settings.products', compact('settings'));
    }

    public function updateProducts(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'product_variation_images_type' => ['nullable', 'string', 'in:per_variation,all'],
            'auto_sku_format' => ['nullable', 'string', 'max:255'],
        ]);

        $this->save($request, [
            'ecommerce.product_variation_images_type',
            'ecommerce.show_stock_count',
            'ecommerce.show_out_of_stock_products',
            'ecommerce.product_options_enabled',
            'ecommerce.cross_sell_enabled',
            'ecommerce.related_products_enabled',
            'ecommerce.specification_enabled',
            'ecommerce.auto_sku_enabled',
            'ecommerce.auto_sku_format',
            'ecommerce.barcode_required',
        ], [
            'show_stock_count', 'show_out_of_stock_products', 'product_options_enabled',
            'cross_sell_enabled', 'related_products_enabled', 'specification_enabled',
            'auto_sku_enabled', 'barcode_required',
        ]);

        return redirect()->route('settings.ecommerce.products.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Product Search
    // -------------------------------------------------------------------------

    public function productSearch(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.search_exact_phrase',
            'ecommerce.search_field_name',
            'ecommerce.search_field_sku',
            'ecommerce.search_field_variation_sku',
            'ecommerce.search_field_description',
            'ecommerce.search_field_brand',
            'ecommerce.search_field_tags',
            'ecommerce.search_filter_category',
            'ecommerce.search_filter_brand',
            'ecommerce.search_filter_tags',
            'ecommerce.search_filter_attributes',
            'ecommerce.search_filter_price',
            'ecommerce.search_max_price',
        ]);

        return view('ecommerce::settings.product-search', compact('settings'));
    }

    public function updateProductSearch(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'search_max_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->save($request, [
            'ecommerce.search_exact_phrase',
            'ecommerce.search_field_name',
            'ecommerce.search_field_sku',
            'ecommerce.search_field_variation_sku',
            'ecommerce.search_field_description',
            'ecommerce.search_field_brand',
            'ecommerce.search_field_tags',
            'ecommerce.search_filter_category',
            'ecommerce.search_filter_brand',
            'ecommerce.search_filter_tags',
            'ecommerce.search_filter_attributes',
            'ecommerce.search_filter_price',
            'ecommerce.search_max_price',
        ], [
            'search_exact_phrase', 'search_field_name', 'search_field_sku',
            'search_field_variation_sku', 'search_field_description', 'search_field_brand',
            'search_field_tags', 'search_filter_category', 'search_filter_brand',
            'search_filter_tags', 'search_filter_attributes', 'search_filter_price',
        ]);

        return redirect()->route('settings.ecommerce.product-search.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Digital Products
    // -------------------------------------------------------------------------

    public function digitalProducts(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.digital_products_enabled',
            'ecommerce.digital_products_guest_checkout',
            'ecommerce.digital_products_disable_physical',
        ]);

        return view('ecommerce::settings.digital-products', compact('settings'));
    }

    public function updateDigitalProducts(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $this->save($request, [
            'ecommerce.digital_products_enabled',
            'ecommerce.digital_products_guest_checkout',
            'ecommerce.digital_products_disable_physical',
        ], [
            'digital_products_enabled',
            'digital_products_guest_checkout',
            'digital_products_disable_physical',
        ]);

        return redirect()->route('settings.ecommerce.digital-products.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Product Reviews
    // -------------------------------------------------------------------------

    public function productReviews(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.reviews_enabled',
            'ecommerce.review_buyers_only',
            'ecommerce.review_needs_approval',
            'ecommerce.review_show_full_name',
            'ecommerce.review_max_file_size',
            'ecommerce.review_max_files',
        ]);

        return view('ecommerce::settings.product-reviews', compact('settings'));
    }

    public function updateProductReviews(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'review_max_file_size' => ['nullable', 'numeric', 'min:0'],
            'review_max_files' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->save($request, [
            'ecommerce.reviews_enabled',
            'ecommerce.review_buyers_only',
            'ecommerce.review_needs_approval',
            'ecommerce.review_show_full_name',
            'ecommerce.review_max_file_size',
            'ecommerce.review_max_files',
        ], ['reviews_enabled', 'review_buyers_only', 'review_needs_approval', 'review_show_full_name']);

        return redirect()->route('settings.ecommerce.product-reviews.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Shopping
    // -------------------------------------------------------------------------

    public function shopping(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.cart_enabled',
            'ecommerce.quick_buy_enabled',
            'ecommerce.quick_buy_destination',
            'ecommerce.auto_confirm_order',
            'ecommerce.hide_price',
            'ecommerce.wishlist_enabled',
            'ecommerce.wishlist_share',
            'ecommerce.wishlist_duration',
            'ecommerce.order_tracking_enabled',
            'ecommerce.payment_proof_enabled',
            'ecommerce.compare_enabled',
        ]);

        return view('ecommerce::settings.shopping', compact('settings'));
    }

    public function updateShopping(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'quick_buy_destination' => ['nullable', 'string', 'in:cart,checkout'],
            'wishlist_duration' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->save($request, [
            'ecommerce.cart_enabled',
            'ecommerce.quick_buy_enabled',
            'ecommerce.quick_buy_destination',
            'ecommerce.auto_confirm_order',
            'ecommerce.hide_price',
            'ecommerce.wishlist_enabled',
            'ecommerce.wishlist_share',
            'ecommerce.wishlist_duration',
            'ecommerce.order_tracking_enabled',
            'ecommerce.payment_proof_enabled',
            'ecommerce.compare_enabled',
        ], [
            'cart_enabled', 'quick_buy_enabled', 'auto_confirm_order', 'hide_price',
            'wishlist_enabled', 'wishlist_share', 'order_tracking_enabled',
            'payment_proof_enabled', 'compare_enabled',
        ]);

        return redirect()->route('settings.ecommerce.shopping.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Checkout
    // -------------------------------------------------------------------------

    public function checkout(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.allow_guest_checkout',
            'ecommerce.order_minimum_amount',
            'ecommerce.order_minimum_quantity',
            'ecommerce.order_maximum_quantity',
            'ecommerce.checkout_required_phone',
            'ecommerce.checkout_required_email',
            'ecommerce.checkout_required_country',
            'ecommerce.checkout_required_state',
            'ecommerce.checkout_required_city',
            'ecommerce.checkout_required_address',
            'ecommerce.checkout_hidden_phone',
            'ecommerce.checkout_hidden_email',
            'ecommerce.checkout_hidden_country',
            'ecommerce.checkout_hidden_state',
            'ecommerce.checkout_hidden_city',
            'ecommerce.checkout_hidden_address',
            'ecommerce.checkout_zip_code_enabled',
            'ecommerce.checkout_billing_address',
            'ecommerce.checkout_fiscal_info',
            'ecommerce.checkout_terms_enabled',
            'ecommerce.checkout_terms_prechecked',
            'ecommerce.checkout_load_from_plugin',
            'ecommerce.checkout_city_free_text',
            'ecommerce.checkout_default_country',
            'ecommerce.checkout_filter_cities_by_state',
            'ecommerce.checkout_recently_viewed_enabled',
            'ecommerce.checkout_recently_viewed_max',
            'ecommerce.checkout_quantity_editable',
        ]);

        return view('ecommerce::settings.checkout', compact('settings'));
    }

    public function updateCheckout(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'order_minimum_amount' => ['nullable', 'numeric', 'min:0'],
            'order_minimum_quantity' => ['nullable', 'integer', 'min:0'],
            'order_maximum_quantity' => ['nullable', 'integer', 'min:0'],
            'checkout_load_from_plugin' => ['nullable', 'string', 'in:0,1'],
            'checkout_default_country' => ['nullable', 'string', 'max:10'],
            'checkout_recently_viewed_max' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->save($request, [
            'ecommerce.allow_guest_checkout',
            'ecommerce.order_minimum_amount',
            'ecommerce.order_minimum_quantity',
            'ecommerce.order_maximum_quantity',
            'ecommerce.checkout_required_phone',
            'ecommerce.checkout_required_email',
            'ecommerce.checkout_required_country',
            'ecommerce.checkout_required_state',
            'ecommerce.checkout_required_city',
            'ecommerce.checkout_required_address',
            'ecommerce.checkout_hidden_phone',
            'ecommerce.checkout_hidden_email',
            'ecommerce.checkout_hidden_country',
            'ecommerce.checkout_hidden_state',
            'ecommerce.checkout_hidden_city',
            'ecommerce.checkout_hidden_address',
            'ecommerce.checkout_zip_code_enabled',
            'ecommerce.checkout_billing_address',
            'ecommerce.checkout_fiscal_info',
            'ecommerce.checkout_terms_enabled',
            'ecommerce.checkout_terms_prechecked',
            'ecommerce.checkout_load_from_plugin',
            'ecommerce.checkout_city_free_text',
            'ecommerce.checkout_default_country',
            'ecommerce.checkout_filter_cities_by_state',
            'ecommerce.checkout_recently_viewed_enabled',
            'ecommerce.checkout_recently_viewed_max',
            'ecommerce.checkout_quantity_editable',
        ], [
            'allow_guest_checkout',
            'checkout_required_phone', 'checkout_required_email', 'checkout_required_country',
            'checkout_required_state', 'checkout_required_city', 'checkout_required_address',
            'checkout_hidden_phone', 'checkout_hidden_email', 'checkout_hidden_country',
            'checkout_hidden_state', 'checkout_hidden_city', 'checkout_hidden_address',
            'checkout_zip_code_enabled', 'checkout_billing_address', 'checkout_fiscal_info',
            'checkout_terms_enabled', 'checkout_terms_prechecked',
            'checkout_city_free_text', 'checkout_filter_cities_by_state',
            'checkout_recently_viewed_enabled', 'checkout_quantity_editable',
        ]);

        return redirect()->route('settings.ecommerce.checkout.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Return
    // -------------------------------------------------------------------------

    public function returnSettings(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.returns_enabled',
            'ecommerce.returnable_days',
            'ecommerce.returns_allow_custom_quantity',
        ]);

        return view('ecommerce::settings.return', compact('settings'));
    }

    public function updateReturn(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'returnable_days' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->save($request, [
            'ecommerce.returns_enabled',
            'ecommerce.returnable_days',
            'ecommerce.returns_allow_custom_quantity',
        ], ['returns_enabled', 'returns_allow_custom_quantity']);

        return redirect()->route('settings.ecommerce.return.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Invoices
    // -------------------------------------------------------------------------

    public function invoices(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.invoice_company_name',
            'ecommerce.invoice_address',
            'ecommerce.invoice_country',
            'ecommerce.invoice_state',
            'ecommerce.invoice_city',
            'ecommerce.invoice_email',
            'ecommerce.invoice_phone',
            'ecommerce.invoice_tax_id',
            'ecommerce.invoice_logo',
            'ecommerce.invoice_custom_font_enabled',
            'ecommerce.invoice_custom_font',
            'ecommerce.invoice_language_support',
            'ecommerce.invoice_pdf_library',
            'ecommerce.invoice_seal_enabled',
            'ecommerce.invoice_code_prefix',
            'ecommerce.invoice_disable_until_confirmed',
            'ecommerce.invoice_date_format',
        ]);

        return view('ecommerce::settings.invoices', compact('settings'));
    }

    public function updateInvoices(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'invoice_company_name' => ['nullable', 'string', 'max:255'],
            'invoice_address' => ['nullable', 'string', 'max:500'],
            'invoice_country' => ['nullable', 'string', 'max:100'],
            'invoice_state' => ['nullable', 'string', 'max:100'],
            'invoice_city' => ['nullable', 'string', 'max:100'],
            'invoice_email' => ['nullable', 'email', 'max:255'],
            'invoice_phone' => ['nullable', 'string', 'max:50'],
            'invoice_tax_id' => ['nullable', 'string', 'max:100'],
            'invoice_logo' => ['nullable', 'string', 'max:500'],
            'invoice_custom_font' => ['nullable', 'string', 'max:100'],
            'invoice_language_support' => ['nullable', 'string', 'in:default,arabic,bangladesh,chinese'],
            'invoice_pdf_library' => ['nullable', 'string', 'in:dompdf,mpdf'],
            'invoice_code_prefix' => ['nullable', 'string', 'max:50'],
            'invoice_date_format' => ['nullable', 'string', 'in:M d, Y,F j, Y,F d, Y,Y-m-d,Y-M-d,d-m-Y,d-M-Y,m/d/Y,M/d/Y,d/m/Y,d/M/Y'],
        ]);

        $this->save($request, [
            'ecommerce.invoice_company_name',
            'ecommerce.invoice_address',
            'ecommerce.invoice_country',
            'ecommerce.invoice_state',
            'ecommerce.invoice_city',
            'ecommerce.invoice_email',
            'ecommerce.invoice_phone',
            'ecommerce.invoice_tax_id',
            'ecommerce.invoice_logo',
            'ecommerce.invoice_custom_font_enabled',
            'ecommerce.invoice_custom_font',
            'ecommerce.invoice_language_support',
            'ecommerce.invoice_pdf_library',
            'ecommerce.invoice_seal_enabled',
            'ecommerce.invoice_code_prefix',
            'ecommerce.invoice_disable_until_confirmed',
            'ecommerce.invoice_date_format',
        ], ['invoice_custom_font_enabled', 'invoice_seal_enabled', 'invoice_disable_until_confirmed']);

        return redirect()->route('settings.ecommerce.invoices.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Invoice Template
    // -------------------------------------------------------------------------

    public function invoiceTemplate(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load(['ecommerce.invoice_template']);

        return view('ecommerce::settings.invoice-template', compact('settings'));
    }

    public function updateInvoiceTemplate(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'invoice_template' => ['nullable', 'string'],
        ]);

        $this->save($request, ['ecommerce.invoice_template']);

        return redirect()->route('settings.ecommerce.invoice-template.index')->with('success', 'Plantilla actualizada.');
    }

    public function previewInvoiceTemplate(): Response
    {
        $this->authorize('ecommerce.settings');
        $template = Setting::get('ecommerce.invoice_template', '');

        if (empty($template)) {
            return response('<p style="font-family:sans-serif;padding:2rem">No hay plantilla de factura configurada. Guarda una plantilla primero.</p>', 200);
        }

        $invoice = Invoice::query()->with('items')->latest()->first();

        $invoiceData = $invoice ? $invoice->toArray() : [
            'code' => 'INV-PREVIEW',
            'status' => 'paid',
            'sub_total' => '100.00',
            'tax_amount' => '19.00',
            'shipping_amount' => '10.00',
            'discount_amount' => '0.00',
            'amount' => '129.00',
            'customer_name' => 'Cliente Ejemplo',
            'customer_email' => 'cliente@ejemplo.com',
            'items' => [
                ['name' => 'Producto de ejemplo', 'qty' => 2, 'sub_total' => '100.00'],
            ],
        ];

        $data = [
            'invoice' => $invoiceData,
            'logo_full_path' => asset('images/logo.png'),
            'company_logo_full_path' => Setting::get('ecommerce.invoice_logo', ''),
            'site_title' => config('app.name'),
            'company_name' => Setting::get('ecommerce.invoice_company_name', config('app.name')),
            'company_address' => Setting::get('ecommerce.invoice_address', ''),
            'company_country' => Setting::get('ecommerce.invoice_country', ''),
            'company_state' => Setting::get('ecommerce.invoice_state', ''),
            'company_city' => Setting::get('ecommerce.invoice_city', ''),
            'company_zipcode' => '',
            'company_phone' => Setting::get('ecommerce.invoice_phone', ''),
            'company_email' => Setting::get('ecommerce.invoice_email', ''),
            'company_tax_id' => Setting::get('ecommerce.invoice_tax_id', ''),
            'payment_method' => $invoice?->shipping_method ?? 'Transferencia',
            'payment_status' => $invoice?->status ?? 'pending',
            'payment_description' => '',
            'html_attributes' => 'lang="es"',
            'body_attributes' => '',
        ];

        try {
            $twig = $this->makeTwig($template);
            $html = $twig->render('t', $data);
        } catch (\Throwable $e) {
            return response('Error en la plantilla: '.$e->getMessage(), 422);
        }

        return Pdf::loadHTML($html)->setPaper('a4')->stream('factura-preview.pdf');
    }

    // -------------------------------------------------------------------------
    // Shipping Label Template
    // -------------------------------------------------------------------------

    public function shippingLabelTemplate(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load(['ecommerce.shipping_label_template']);

        return view('ecommerce::settings.shipping-label-template', compact('settings'));
    }

    public function updateShippingLabelTemplate(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'shipping_label_template' => ['nullable', 'string'],
        ]);

        $this->save($request, ['ecommerce.shipping_label_template']);

        return redirect()->route('settings.ecommerce.shipping-label-template.index')->with('success', 'Plantilla actualizada.');
    }

    public function previewShippingLabelTemplate(): Response
    {
        $this->authorize('ecommerce.settings');
        $template = Setting::get('ecommerce.shipping_label_template', '');

        if (empty($template)) {
            return response('<p style="font-family:sans-serif;padding:2rem">No hay plantilla de etiqueta de envío configurada. Guarda una plantilla primero.</p>', 200);
        }

        $shipment = Shipment::query()
            ->with(['order.customer', 'order.shippingAddress', 'order.billingAddress'])
            ->latest()
            ->first();

        $order = $shipment?->order;
        $sa = $order?->shippingAddress;
        $ba = $order?->billingAddress;
        $customer = $order?->customer;

        $data = [
            'order' => $order ? $order->toArray() : ['code' => 'ORD-PREVIEW', 'total' => '129.00'],
            'customer' => [
                'name' => $customer?->name ?? 'Cliente Ejemplo',
                'email' => $customer?->email ?? 'cliente@ejemplo.com',
                'phone' => $customer?->phone ?? '+57 300 0000000',
            ],
            'shipping_address' => [
                'street' => $sa?->address ?? 'Calle 123 # 45-67',
                'city' => $sa?->city ?? 'Bogotá',
                'state' => $sa?->state ?? 'Cundinamarca',
                'country' => $sa?->country ?? 'Colombia',
            ],
            'billing_address' => [
                'street' => $ba?->address ?? 'Calle 123 # 45-67',
                'city' => $ba?->city ?? 'Bogotá',
                'state' => $ba?->state ?? 'Cundinamarca',
                'country' => $ba?->country ?? 'Colombia',
            ],
            'logo_full_path' => asset('images/logo.png'),
            'company_name' => Setting::get('ecommerce.invoice_company_name', config('app.name')),
            'company_address' => Setting::get('ecommerce.invoice_address', ''),
            'company_phone' => Setting::get('ecommerce.invoice_phone', ''),
            'company_email' => Setting::get('ecommerce.invoice_email', ''),
            'site_title' => config('app.name'),
        ];

        try {
            $twig = $this->makeTwig($template);
            $html = $twig->render('t', $data);
        } catch (\Throwable $e) {
            return response('Error en la plantilla: '.$e->getMessage(), 422);
        }

        return Pdf::loadHTML($html)->setPaper([0, 0, 283.46, 425.20], 'portrait')->stream('etiqueta-preview.pdf');
    }

    // -------------------------------------------------------------------------
    // Taxes
    // -------------------------------------------------------------------------

    public function taxesSettings(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.taxes_enabled',
            'ecommerce.default_tax_rate',
            'ecommerce.display_product_price_including_taxes',
            'ecommerce.display_tax_fields_at_checkout_page',
            'ecommerce.display_tax_in_product_price',
        ]);

        $taxes = Tax::query()->orderBy('title')->get();

        return view('ecommerce::settings.taxes', compact('settings', 'taxes'));
    }

    public function updateTaxes(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'default_tax_rate' => ['nullable', 'integer', 'exists:ecommerce_taxes,id'],
        ]);

        $this->save($request, [
            'ecommerce.taxes_enabled',
            'ecommerce.default_tax_rate',
            'ecommerce.display_product_price_including_taxes',
            'ecommerce.display_tax_fields_at_checkout_page',
            'ecommerce.display_tax_in_product_price',
        ], [
            'taxes_enabled',
            'display_product_price_including_taxes',
            'display_tax_fields_at_checkout_page',
            'display_tax_in_product_price',
        ]);

        return redirect()->route('settings.ecommerce.taxes.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Customers
    // -------------------------------------------------------------------------

    public function customers(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.enable_customer_registration',
            'ecommerce.verify_customer_email',
            'ecommerce.enabled_phone_field_in_registration_form',
            'ecommerce.make_customer_phone_number_required',
            'ecommerce.login_option',
            'ecommerce.keep_email_field_in_registration_form',
            'ecommerce.enabled_customer_dob_field',
            'ecommerce.enabled_customer_account_deletion',
            'ecommerce.customer_default_avatar',
        ]);

        return view('ecommerce::settings.customers', compact('settings'));
    }

    public function updateCustomers(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'login_option' => ['nullable', 'string', 'in:email,phone,email_or_phone'],
            'customer_default_avatar' => ['nullable', 'string', 'max:500'],
        ]);

        $this->save($request, [
            'ecommerce.enable_customer_registration',
            'ecommerce.verify_customer_email',
            'ecommerce.enabled_phone_field_in_registration_form',
            'ecommerce.make_customer_phone_number_required',
            'ecommerce.login_option',
            'ecommerce.keep_email_field_in_registration_form',
            'ecommerce.enabled_customer_dob_field',
            'ecommerce.enabled_customer_account_deletion',
            'ecommerce.customer_default_avatar',
        ], [
            'enable_customer_registration', 'verify_customer_email',
            'enabled_phone_field_in_registration_form', 'make_customer_phone_number_required',
            'keep_email_field_in_registration_form', 'enabled_customer_dob_field',
            'enabled_customer_account_deletion',
        ]);

        return redirect()->route('settings.ecommerce.customers.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Shipping Settings (display/behavior, NOT shipping methods)
    // -------------------------------------------------------------------------

    public function shippingSettings(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.hide_other_shipping_options_if_it_has_free_shipping',
            'ecommerce.sort_shipping_options_direction',
            'ecommerce.disable_shipping_options',
        ]);

        $zones = Shipping::query()->with('rules.items')->orderBy('title')->get();

        return view('ecommerce::settings.shipping-settings', compact('settings', 'zones'));
    }

    public function updateShippingSettings(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'sort_shipping_options_direction' => ['nullable', 'string', 'in:price_lower_to_higher,price_higher_to_lower'],
        ]);

        $this->save($request, [
            'ecommerce.hide_other_shipping_options_if_it_has_free_shipping',
            'ecommerce.sort_shipping_options_direction',
            'ecommerce.disable_shipping_options',
        ], ['hide_other_shipping_options_if_it_has_free_shipping', 'disable_shipping_options']);

        return redirect()->route('settings.ecommerce.shipping.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Webhook
    // -------------------------------------------------------------------------

    public function webhook(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load(['ecommerce.order_placed_webhook_url']);

        return view('ecommerce::settings.webhook', compact('settings'));
    }

    public function updateWebhook(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'order_placed_webhook_url' => ['nullable', 'string', 'max:500'],
        ]);

        $this->save($request, ['ecommerce.order_placed_webhook_url']);

        return redirect()->route('settings.ecommerce.webhook.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Tracking
    // -------------------------------------------------------------------------

    public function tracking(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.facebook_pixel_enabled',
            'ecommerce.facebook_pixel_id',
            'ecommerce.google_tag_manager_enabled',
        ]);

        return view('ecommerce::settings.tracking', compact('settings'));
    }

    public function updateTracking(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'facebook_pixel_id' => ['nullable', 'string', 'max:100'],
        ]);

        $this->save($request, [
            'ecommerce.facebook_pixel_enabled',
            'ecommerce.facebook_pixel_id',
            'ecommerce.google_tag_manager_enabled',
        ], ['facebook_pixel_enabled', 'google_tag_manager_enabled']);

        return redirect()->route('settings.ecommerce.tracking.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Standard and Format
    // -------------------------------------------------------------------------

    public function standardAndFormat(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.store_order_prefix',
            'ecommerce.store_order_suffix',
            'ecommerce.store_weight_unit',
            'ecommerce.store_width_height_unit',
        ]);

        return view('ecommerce::settings.standard-and-format', compact('settings'));
    }

    public function updateStandardAndFormat(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'store_order_prefix' => ['nullable', 'string', 'max:50'],
            'store_order_suffix' => ['nullable', 'string', 'max:50'],
            'store_weight_unit' => ['nullable', 'string', 'in:g,kg,lb,oz'],
            'store_width_height_unit' => ['nullable', 'string', 'in:cm,m,inch'],
        ]);

        $this->save($request, [
            'ecommerce.store_order_prefix',
            'ecommerce.store_order_suffix',
            'ecommerce.store_weight_unit',
            'ecommerce.store_width_height_unit',
        ]);

        return redirect()->route('settings.ecommerce.standard-and-format.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Flash Sale
    // -------------------------------------------------------------------------

    public function flashSale(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load(['ecommerce.flash_sale_enabled']);

        return view('ecommerce::settings.flash-sale', compact('settings'));
    }

    public function updateFlashSale(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $this->save($request, ['ecommerce.flash_sale_enabled'], ['flash_sale_enabled']);

        return redirect()->route('settings.ecommerce.flash-sale.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Abandoned Cart
    // -------------------------------------------------------------------------

    public function abandonedCart(): View
    {
        $this->authorize('ecommerce.settings');
        $settings = $this->load([
            'ecommerce.abandoned_cart_enabled',
            'ecommerce.abandoned_cart_email_template',
            'ecommerce.abandoned_cart_email_subject',
            'ecommerce.abandoned_cart_delay_hours',
            'ecommerce.abandoned_cart_max_hours',
            'ecommerce.abandoned_cart_email_limit',
            'ecommerce.abandoned_cart_max_emails',
            'ecommerce.abandoned_cart_email_interval_hours',
            'ecommerce.abandoned_cart_offer_free_shipping',
            'ecommerce.abandoned_cart_exclude_categories',
            'ecommerce.cart_destroy_on_logout',
        ]);

        return view('ecommerce::settings.abandoned-cart', compact('settings'));
    }

    public function updateAbandonedCart(Request $request): RedirectResponse
    {
        $this->authorize('ecommerce.settings');
        $request->validate([
            'abandoned_cart_email_template' => ['nullable', 'string', 'in:abandoned_cart,order_recover'],
            'abandoned_cart_email_subject' => ['nullable', 'string', 'max:255'],
            'abandoned_cart_delay_hours' => ['nullable', 'integer', 'min:1'],
            'abandoned_cart_max_hours' => ['nullable', 'integer', 'min:1'],
            'abandoned_cart_email_limit' => ['nullable', 'integer', 'min:1'],
            'abandoned_cart_max_emails' => ['nullable', 'integer', 'min:1'],
            'abandoned_cart_email_interval_hours' => ['nullable', 'integer', 'min:1'],
            'abandoned_cart_exclude_categories' => ['nullable', 'string'],
        ]);

        $this->save($request, [
            'ecommerce.abandoned_cart_enabled',
            'ecommerce.abandoned_cart_email_template',
            'ecommerce.abandoned_cart_email_subject',
            'ecommerce.abandoned_cart_delay_hours',
            'ecommerce.abandoned_cart_max_hours',
            'ecommerce.abandoned_cart_email_limit',
            'ecommerce.abandoned_cart_max_emails',
            'ecommerce.abandoned_cart_email_interval_hours',
            'ecommerce.abandoned_cart_offer_free_shipping',
            'ecommerce.abandoned_cart_exclude_categories',
            'ecommerce.cart_destroy_on_logout',
        ], ['abandoned_cart_enabled', 'abandoned_cart_offer_free_shipping', 'cart_destroy_on_logout']);

        return redirect()->route('settings.ecommerce.abandoned-cart.index')->with('success', 'Configuracion actualizada.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTwig(string $template): Environment
    {
        $twig = new Environment(new ArrayLoader(['t' => $template]), ['autoescape' => false]);

        $twig->registerUndefinedFilterCallback(
            fn (string $name) => new TwigFilter($name, fn ($v) => is_string($v) ? __($v) : ($v ?? ''))
        );

        $twig->registerUndefinedFunctionCallback(
            fn (string $name) => new TwigFunction($name, fn () => '')
        );

        return $twig;
    }

    private function load(array $keys): array
    {
        $settings = [];
        foreach ($keys as $key) {
            $settings[str_replace(self::PREFIX, '', $key)] = Setting::get($key);
        }

        return $settings;
    }

    private function save(Request $request, array $keys, array $booleans = []): void
    {
        foreach ($keys as $key) {
            $field = str_replace(self::PREFIX, '', $key);
            $value = in_array($field, $booleans)
                ? ($request->boolean($field) ? '1' : '0')
                : $request->input($field);

            Setting::set($key, $value);
        }
    }
}
