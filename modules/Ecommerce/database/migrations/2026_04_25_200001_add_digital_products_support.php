<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ecommerce_products', 'product_type')) {
            Schema::table('ecommerce_products', function (Blueprint $table) {
                $table->string('product_type', 60)->nullable()->default('physical')->after('status');
            });
        }

        Schema::table('ecommerce_product_files', function (Blueprint $table) {
            if (! Schema::hasColumn('ecommerce_product_files', 'url')) {
                $table->string('url', 400)->nullable()->after('product_id');
            }

            if (! Schema::hasColumn('ecommerce_product_files', 'mime_type')) {
                $table->string('mime_type', 100)->nullable()->after('name');
            }

            if (! Schema::hasColumn('ecommerce_product_files', 'size')) {
                $table->unsignedBigInteger('size')->nullable()->after('mime_type');
            }
        });

        if (! Schema::hasColumn('ecommerce_order_items', 'product_type')) {
            Schema::table('ecommerce_order_items', function (Blueprint $table) {
                $table->string('product_type', 60)->default('physical')->after('product_id');
            });
        }

        if (! Schema::hasColumn('ecommerce_order_items', 'times_downloaded')) {
            Schema::table('ecommerce_order_items', function (Blueprint $table) {
                $table->integer('times_downloaded')->default(0)->after('product_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecommerce_order_items', 'times_downloaded')) {
            Schema::table('ecommerce_order_items', function (Blueprint $table) {
                $table->dropColumn('times_downloaded');
            });
        }

        if (Schema::hasColumn('ecommerce_order_items', 'product_type')) {
            Schema::table('ecommerce_order_items', function (Blueprint $table) {
                $table->dropColumn('product_type');
            });
        }

        Schema::table('ecommerce_product_files', function (Blueprint $table) {
            $columns = array_filter(['url', 'mime_type', 'size'], fn ($col) => Schema::hasColumn('ecommerce_product_files', $col));

            if (! empty($columns)) {
                $table->dropColumn(array_values($columns));
            }
        });

        if (Schema::hasColumn('ecommerce_products', 'product_type')) {
            Schema::table('ecommerce_products', function (Blueprint $table) {
                $table->dropColumn('product_type');
            });
        }
    }
};
