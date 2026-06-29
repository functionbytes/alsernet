<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentSlaPolicy;

class DocumentSLAPoliciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds SLA policies with different time requirements and business hour configurations.
     */
    public function run(): void
    {
        $policies = [
            [
                'name' => 'Política estándar',
                'description' => 'Política de SLA estándar con tiempos normales de procesamiento',
                'upload_request_time' => 1440, // 24 hours
                'review_time' => 2880, // 48 hours
                'approval_time' => 4320, // 72 hours
                'business_hours_only' => false,
                'business_hours' => null,
                'timezone' => 'America/Argentina/Buenos_Aires',
                'document_type_multipliers' => [
                    'corta' => 2.0,
                    'rifle' => 1.0,
                    'escopeta' => 1.5,
                    'balines' => 0.5,
                    'dni' => 0.75,
                    'general' => 1.0,
                ],
                'enable_escalation' => false,
                'escalation_threshold_percent' => null,
                'escalation_recipients' => null,
                'active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Política acelerada',
                'description' => 'Política de SLA acelerada con tiempos reducidos de procesamiento',
                'upload_request_time' => 720, // 12 hours
                'review_time' => 1440, // 24 hours
                'approval_time' => 2880, // 48 hours
                'business_hours_only' => true,
                'business_hours' => [
                    'monday' => ['start' => '09:00', 'end' => '18:00'],
                    'tuesday' => ['start' => '09:00', 'end' => '18:00'],
                    'wednesday' => ['start' => '09:00', 'end' => '18:00'],
                    'thursday' => ['start' => '09:00', 'end' => '18:00'],
                    'friday' => ['start' => '09:00', 'end' => '18:00'],
                    'saturday' => ['start' => '10:00', 'end' => '14:00'],
                    'sunday' => null,
                ],
                'timezone' => 'America/Argentina/Buenos_Aires',
                'document_type_multipliers' => [
                    'corta' => 1.5,
                    'rifle' => 0.8,
                    'escopeta' => 1.0,
                    'balines' => 0.4,
                    'dni' => 0.5,
                    'general' => 0.8,
                ],
                'enable_escalation' => true,
                'escalation_threshold_percent' => 75,
                'escalation_recipients' => [
                    ['email' => 'supervisor@example.com', 'role' => 'supervisor'],
                ],
                'active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Política de emergencia',
                'description' => 'Política de SLA para casos de emergencia con tiempos críticos',
                'upload_request_time' => 240, // 4 hours
                'review_time' => 480, // 8 hours
                'approval_time' => 1440, // 24 hours
                'business_hours_only' => false,
                'business_hours' => null,
                'timezone' => 'America/Argentina/Buenos_Aires',
                'document_type_multipliers' => [
                    'corta' => 1.0,
                    'rifle' => 0.5,
                    'escopeta' => 0.75,
                    'balines' => 0.3,
                    'dni' => 0.3,
                    'general' => 0.5,
                ],
                'enable_escalation' => true,
                'escalation_threshold_percent' => 50,
                'escalation_recipients' => [
                    ['email' => 'manager@example.com', 'role' => 'manager'],
                    ['email' => 'supervisor@example.com', 'role' => 'supervisor'],
                ],
                'active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Política extendida',
                'description' => 'Política de SLA con tiempos extendidos para documentos complejos',
                'upload_request_time' => 2880, // 48 hours
                'review_time' => 5760, // 96 hours (4 days)
                'approval_time' => 10080, // 7 days
                'business_hours_only' => false,
                'business_hours' => null,
                'timezone' => 'America/Argentina/Buenos_Aires',
                'document_type_multipliers' => [
                    'corta' => 3.0,
                    'rifle' => 1.5,
                    'escopeta' => 2.0,
                    'balines' => 0.75,
                    'dni' => 1.0,
                    'general' => 1.5,
                ],
                'enable_escalation' => false,
                'escalation_threshold_percent' => null,
                'escalation_recipients' => null,
                'active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($policies as $policyData) {
            DocumentSlaPolicy::updateOrCreate(
                ['name' => $policyData['name']],
                $policyData
            );
        }
    }
}
