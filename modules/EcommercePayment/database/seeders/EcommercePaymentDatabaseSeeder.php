<?php

namespace Modules\EcommercePayment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EcommercePaymentDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedPermissions();
    }

    private function seedSettings(): void
    {
        $settings = [
            // Wompi
            'ecommerce_payment.wompi.status' => '0',
            'ecommerce_payment.wompi.name' => 'Wompi',
            'ecommerce_payment.wompi.description' => 'Paga con tarjeta de crédito, débito o PSE.',
            'ecommerce_payment.wompi.mode' => 'sandbox',
            'ecommerce_payment.wompi.public_key' => '',
            'ecommerce_payment.wompi.private_key' => '',
            'ecommerce_payment.wompi.integrity_secret' => '',
            'ecommerce_payment.wompi.event_secret' => '',
            'ecommerce_payment.wompi.notification_email' => '',

            // COD - Pago contra entrega
            'ecommerce_payment.cod.status' => '0',
            'ecommerce_payment.cod.name' => 'Pago contra entrega',
            'ecommerce_payment.cod.description' => 'Paga al momento de recibir tu pedido.',
            'ecommerce_payment.cod.instructions' => 'Ten el dinero listo al recibir tu pedido.',
            'ecommerce_payment.cod.fee_type' => 'none',
            'ecommerce_payment.cod.fee_value' => '0',

            // Transferencia bancaria
            'ecommerce_payment.bank_transfer.status' => '0',
            'ecommerce_payment.bank_transfer.name' => 'Transferencia bancaria',
            'ecommerce_payment.bank_transfer.description' => 'Realiza una transferencia bancaria para completar tu pago.',
            'ecommerce_payment.bank_transfer.bank_name' => '',
            'ecommerce_payment.bank_transfer.account_type' => 'Ahorros',
            'ecommerce_payment.bank_transfer.account_number' => '',
            'ecommerce_payment.bank_transfer.account_holder' => '',
            'ecommerce_payment.bank_transfer.document_type' => 'NIT',
            'ecommerce_payment.bank_transfer.document_number' => '',
            'ecommerce_payment.bank_transfer.instructions' => 'Envía el comprobante de pago al correo indicado con el número de orden como referencia.',
        ];

        foreach ($settings as $key => $value) {
            if (! Setting::where('key', $key)->exists()) {
                Setting::set($key, $value);
            }
        }
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'ecommerce-payment.view',
            'ecommerce-payment.refund',
            'ecommerce-payment.confirm',
            'ecommerce-payment.export',
            'ecommerce-payment.settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles(): void
    {
        $allPermissions = [
            'ecommerce-payment.view',
            'ecommerce-payment.refund',
            'ecommerce-payment.confirm',
            'ecommerce-payment.export',
            'ecommerce-payment.settings',
        ];

        $managerPermissions = [
            'ecommerce-payment.view',
            'ecommerce-payment.confirm',
            'ecommerce-payment.export',
        ];

        try {
            $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
            if ($adminRole) {
                $adminRole->givePermissionTo($allPermissions);
            }

            $ecommerceAdmin = Role::where('name', 'ecommerce-admin')->where('guard_name', 'web')->first();
            if ($ecommerceAdmin) {
                $ecommerceAdmin->givePermissionTo($allPermissions);
            }

            $ecommerceManager = Role::where('name', 'ecommerce-manager')->where('guard_name', 'web')->first();
            if ($ecommerceManager) {
                $ecommerceManager->givePermissionTo($managerPermissions);
            }
        } catch (\Exception $e) {
            // Roles may not exist yet; permissions will be assigned when roles are created
        }
    }
}
