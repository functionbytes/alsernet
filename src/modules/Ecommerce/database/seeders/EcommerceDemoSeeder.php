<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerAddress;
use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Models\FlashSale;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Models\ProductCollection;
use Modules\Ecommerce\Models\ProductTag;
use Modules\Ecommerce\Models\Review;
use Modules\Ecommerce\Models\Shipping;
use Modules\Ecommerce\Models\StoreLocator;
use Modules\Ecommerce\Models\Tax;

class EcommerceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBrands();
        $this->seedCategories();
        $this->seedTags();
        $this->seedCollections();
        $this->seedProducts();
        $this->seedCustomers();
        $this->seedOrders();
        $this->seedDiscounts();
        $this->seedTaxes();
        $this->seedShipping();
        $this->seedStoreLocators();
        $this->seedFlashSales();
        $this->seedReviews();
    }

    protected function seedBrands(): void
    {
        $brands = [
            ['name' => 'Sony', 'slug' => 'sony', 'website' => 'https://sony.com', 'status' => 'published'],
            ['name' => 'Samsung', 'slug' => 'samsung', 'website' => 'https://samsung.com', 'status' => 'published'],
            ['name' => 'Apple', 'slug' => 'apple', 'website' => 'https://apple.com', 'status' => 'published'],
            ['name' => 'Nike', 'slug' => 'nike', 'website' => 'https://nike.com', 'status' => 'published'],
            ['name' => 'Adidas', 'slug' => 'adidas', 'website' => 'https://adidas.com', 'status' => 'published'],
        ];

        foreach ($brands as $brand) {
            Brand::query()->create($brand);
        }

        $this->command->info('Brands seeded: '.count($brands));
    }

    protected function seedCategories(): void
    {
        $categories = [
            ['name' => 'Electronica', 'slug' => 'electronica', 'status' => 'published', 'order' => 1],
            ['name' => 'Ropa', 'slug' => 'ropa', 'status' => 'published', 'order' => 2],
            ['name' => 'Hogar', 'slug' => 'hogar', 'status' => 'published', 'order' => 3],
            ['name' => 'Deportes', 'slug' => 'deportes', 'status' => 'published', 'order' => 4],
        ];

        foreach ($categories as $category) {
            ProductCategory::query()->create($category);
        }

        $this->command->info('Categories seeded: '.count($categories));
    }

    protected function seedTags(): void
    {
        $tags = [
            ['name' => 'Nuevo', 'status' => 'published'],
            ['name' => 'Oferta', 'status' => 'published'],
            ['name' => 'Popular', 'status' => 'published'],
        ];

        foreach ($tags as $tag) {
            ProductTag::query()->create($tag);
        }

        $this->command->info('Tags seeded: '.count($tags));
    }

    protected function seedCollections(): void
    {
        $collections = [
            ['name' => 'Verano 2025', 'slug' => 'verano-2025', 'status' => 'published'],
            ['name' => 'Black Friday', 'slug' => 'black-friday', 'status' => 'published'],
        ];

        foreach ($collections as $collection) {
            ProductCollection::query()->create($collection);
        }

        $this->command->info('Collections seeded: '.count($collections));
    }

    protected function seedProducts(): void
    {
        $products = [
            [
                'name' => 'Audifonos Sony WH-1000XM5',
                'slug' => 'audifonos-sony-wh-1000xm5',
                'description' => 'Audifonos inalambricos con cancelacion de ruido',
                'status' => 'published',
                'price' => 349.99,
                'sale_price' => 299.99,
                'sku' => 'SONY-001',
                'quantity' => 50,
                'brand_id' => 1,
                'weight' => 0.25,
                'stock_status' => 'in_stock',
            ],
            [
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'description' => 'El ultimo iPhone con chip A17 Pro',
                'status' => 'published',
                'price' => 999.00,
                'sku' => 'APL-001',
                'quantity' => 30,
                'brand_id' => 3,
                'weight' => 0.19,
                'stock_status' => 'in_stock',
            ],
            [
                'name' => 'Zapatillas Nike Air Max',
                'slug' => 'zapatillas-nike-air-max',
                'description' => 'Comodidad y estilo para tus entrenamientos',
                'status' => 'published',
                'price' => 129.99,
                'sale_price' => 99.99,
                'sku' => 'NIK-001',
                'quantity' => 100,
                'brand_id' => 4,
                'weight' => 0.8,
                'stock_status' => 'in_stock',
            ],
            [
                'name' => 'Smart TV Samsung 55"',
                'slug' => 'smart-tv-samsung-55',
                'description' => 'Televisor 4K UHD con Tizen OS',
                'status' => 'published',
                'price' => 599.00,
                'sku' => 'SAM-001',
                'quantity' => 15,
                'brand_id' => 2,
                'weight' => 15.5,
                'stock_status' => 'in_stock',
            ],
            [
                'name' => 'Camiseta Adidas Originals',
                'slug' => 'camiseta-adidas-originals',
                'description' => 'Camiseta clasica de algodon',
                'status' => 'published',
                'price' => 35.00,
                'sku' => 'ADI-001',
                'quantity' => 200,
                'brand_id' => 5,
                'weight' => 0.3,
                'stock_status' => 'in_stock',
            ],
        ];

        foreach ($products as $product) {
            $p = Product::query()->create($product);
            $p->categories()->sync([rand(1, 4)]);
        }

        $this->command->info('Products seeded: '.count($products));
    }

    protected function seedCustomers(): void
    {
        $customers = [
            ['name' => 'Juan Perez', 'email' => 'juan@example.com', 'phone' => '555-0101', 'status' => 'active', 'password' => Hash::make('password')],
            ['name' => 'Maria Garcia', 'email' => 'maria@example.com', 'phone' => '555-0102', 'status' => 'active', 'password' => Hash::make('password')],
            ['name' => 'Carlos Lopez', 'email' => 'carlos@example.com', 'phone' => '555-0103', 'status' => 'active', 'password' => Hash::make('password')],
        ];

        foreach ($customers as $customer) {
            $c = Customer::query()->create($customer);
            CustomerAddress::query()->create([
                'customer_id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'address' => 'Calle Principal 123',
                'city' => 'Ciudad de Mexico',
                'country' => 'Mexico',
                'is_default' => true,
            ]);
        }

        $this->command->info('Customers seeded: '.count($customers));
    }

    protected function seedOrders(): void
    {
        $orders = [
            ['customer_id' => 1, 'status' => 'completed', 'sub_total' => 349.99, 'tax_amount' => 55.99, 'shipping_amount' => 15.00, 'discount_amount' => 0, 'total' => 420.98, 'payment_method' => 'card', 'payment_status' => 'paid'],
            ['customer_id' => 2, 'status' => 'pending', 'sub_total' => 999.00, 'tax_amount' => 159.84, 'shipping_amount' => 0, 'discount_amount' => 50.00, 'total' => 1108.84, 'payment_method' => 'transfer', 'payment_status' => 'pending'],
            ['customer_id' => 3, 'status' => 'processing', 'sub_total' => 129.99, 'tax_amount' => 20.79, 'shipping_amount' => 10.00, 'discount_amount' => 0, 'total' => 160.78, 'payment_method' => 'cash', 'payment_status' => 'paid'],
        ];

        foreach ($orders as $orderData) {
            $order = Order::query()->create($orderData);
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => rand(1, 5),
                'product_name' => 'Producto demo',
                'qty' => 1,
                'price' => $orderData['sub_total'],
                'total' => $orderData['sub_total'],
            ]);
        }

        $this->command->info('Orders seeded: '.count($orders));
    }

    protected function seedDiscounts(): void
    {
        $discounts = [
            ['title' => 'Descuento de bienvenida', 'code' => 'WELCOME10', 'type' => 'percentage', 'value' => 10, 'quantity' => 100, 'total_used' => 0, 'is_active' => true],
            ['title' => 'Envio gratis', 'code' => 'FREESHIP', 'type' => 'free_shipping', 'value' => 0, 'quantity' => 50, 'total_used' => 0, 'is_active' => true],
        ];

        foreach ($discounts as $discount) {
            Discount::query()->create($discount);
        }

        $this->command->info('Discounts seeded: '.count($discounts));
    }

    protected function seedTaxes(): void
    {
        $taxes = [
            ['title' => 'IVA Mexico', 'percentage' => 16, 'priority' => 1, 'status' => 'published'],
            ['title' => 'IEPS', 'percentage' => 8, 'priority' => 2, 'status' => 'published'],
        ];

        foreach ($taxes as $tax) {
            Tax::query()->create($tax);
        }

        $this->command->info('Taxes seeded: '.count($taxes));
    }

    protected function seedShipping(): void
    {
        $methods = [
            ['title' => 'Envio estandar', 'country' => 'Mexico'],
            ['title' => 'Envio express', 'country' => 'Mexico'],
        ];

        foreach ($methods as $method) {
            Shipping::query()->create($method);
        }

        $this->command->info('Shipping methods seeded: '.count($methods));
    }

    protected function seedStoreLocators(): void
    {
        $locators = [
            ['name' => 'Tienda Centro', 'address' => 'Av. Reforma 100', 'phone' => '555-1000', 'city' => 'Ciudad de Mexico', 'country' => 'Mexico', 'is_primary' => true],
            ['name' => 'Tienda Norte', 'address' => 'Blvd. Norte 200', 'phone' => '555-2000', 'city' => 'Monterrey', 'country' => 'Mexico'],
        ];

        foreach ($locators as $locator) {
            StoreLocator::query()->create($locator);
        }

        $this->command->info('Store locators seeded: '.count($locators));
    }

    protected function seedFlashSales(): void
    {
        $sales = [
            ['name' => 'Flash Sale Electronica', 'start_date' => now()->subDay(), 'end_date' => now()->addDays(3), 'status' => 'published'],
            ['name' => 'Ofertas de fin de semana', 'start_date' => now()->addDays(2), 'end_date' => now()->addDays(4), 'status' => 'draft'],
        ];

        foreach ($sales as $sale) {
            FlashSale::query()->create($sale);
        }

        $this->command->info('Flash sales seeded: '.count($sales));
    }

    protected function seedReviews(): void
    {
        $reviews = [
            ['product_id' => 1, 'customer_name' => 'Juan Perez', 'customer_email' => 'juan@example.com', 'star' => 5, 'comment' => 'Excelentes audifonos, la cancelacion de ruido es increible.', 'status' => 'approved'],
            ['product_id' => 1, 'customer_name' => 'Maria Garcia', 'customer_email' => 'maria@example.com', 'star' => 4, 'comment' => 'Muy buenos, aunque un poco caros.', 'status' => 'approved'],
            ['product_id' => 2, 'customer_name' => 'Carlos Lopez', 'customer_email' => 'carlos@example.com', 'star' => 5, 'comment' => 'El mejor telefono que he tenido.', 'status' => 'pending'],
        ];

        foreach ($reviews as $review) {
            Review::query()->create($review);
        }

        $this->command->info('Reviews seeded: '.count($reviews));
    }
}
