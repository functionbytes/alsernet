<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Manufacturer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'contact_email',
        'contact_phone',
        'website',
        'address',
        'warranty_policies',
        'api_endpoint',
        'api_key',
        'api_config',
        'has_api_integration',
        'auto_warranty_registration',
        'warranty_lookup_url',
        'default_warranty_months',
        'support_contact_info',
        'is_active',
    ];

    protected $casts = [
        'warranty_policies' => 'array',
        'api_config' => 'array',
        'has_api_integration' => 'boolean',
        'auto_warranty_registration' => 'boolean',
        'support_contact_info' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * Productos de este fabricante
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Garantías asociadas a este fabricante
     */
    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }

    /**
     * Scope para fabricantes activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para fabricantes con integración API
     */
    public function scopeWithApi($query)
    {
        return $query->where('has_api_integration', true);
    }

    /**
     * Registrar garantía con el fabricante via API
     */
    public function registerWarranty(Warranty $warranty): array
    {
        if (!$this->has_api_integration || !$this->api_endpoint) {
            return [
                'success' => false,
                'message' => 'Fabricante no tiene integración API configurada',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ])->post($this->api_endpoint . '/warranties/register', [
                'product_serial' => $warranty->product_serial_number,
                'product_model' => $warranty->product_model,
                'purchase_date' => $warranty->purchase_date->format('Y-m-d'),
                'customer_info' => [
                    'name' => $warranty->user->name,
                    'email' => $warranty->user->email,
                    'phone' => $warranty->user->phone,
                ],
                'retailer_info' => [
                    'name' => config('app.name'),
                    'id' => config('app.retailer_id'),
                ],
                'warranty_details' => [
                    'duration_months' => $warranty->warranty_duration_months,
                    'type' => $warranty->warrantyType->code,
                ],
                'external_warranty_id' => $warranty->warranty_number,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'manufacturer_warranty_id' => $data['warranty_id'] ?? null,
                    'confirmation_number' => $data['confirmation_number'] ?? null,
                    'message' => 'Garantía registrada exitosamente con el fabricante',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al registrar con fabricante: ' . $response->body(),
                    'http_code' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión con fabricante: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Consultar estado de garantía con el fabricante
     */
    public function lookupWarranty(string $serialNumber): array
    {
        if (!$this->has_api_integration || !$this->api_endpoint) {
            return [
                'success' => false,
                'message' => 'Fabricante no tiene integración API configurada',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->api_key,
            ])->get($this->api_endpoint . '/warranties/lookup', [
                'serial_number' => $serialNumber,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'warranty_info' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Garantía no encontrada o expirada',
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando fabricante: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crear reclamo de garantía con el fabricante
     */
    public function createWarrantyClaim(WarrantyClaim $claim): array
    {
        if (!$this->has_api_integration || !$this->api_endpoint) {
            return [
                'success' => false,
                'message' => 'Fabricante no tiene integración API configurada',
            ];
        }

        try {
            $warranty = $claim->warranty;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ])->post($this->api_endpoint . '/warranties/claims', [
                'warranty_id' => $warranty->manufacturer_warranty_id,
                'claim_details' => [
                    'issue_category' => $claim->issue_category,
                    'issue_description' => $claim->issue_description,
                    'issue_occurred_date' => $claim->issue_occurred_date->format('Y-m-d'),
                    'symptoms' => $claim->symptoms,
                ],
                'customer_info' => [
                    'name' => $claim->user->name,
                    'email' => $claim->user->email,
                    'phone' => $claim->user->phone,
                ],
                'external_claim_id' => $claim->claim_number,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'manufacturer_claim_id' => $data['claim_id'] ?? null,
                    'status' => $data['status'] ?? 'submitted',
                    'estimated_resolution_days' => $data['estimated_resolution_days'] ?? null,
                    'message' => 'Reclamo enviado exitosamente al fabricante',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error enviando reclamo al fabricante: ' . $response->body(),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión con fabricante: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener estado de reclamo del fabricante
     */
    public function getClaimStatus(string $manufacturerClaimId): array
    {
        if (!$this->has_api_integration || !$this->api_endpoint) {
            return [
                'success' => false,
                'message' => 'Fabricante no tiene integración API configurada',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->api_key,
            ])->get($this->api_endpoint . '/warranties/claims/' . $manufacturerClaimId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'claim_status' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Reclamo no encontrado',
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error consultando estado del reclamo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener información de contacto de soporte
     */
    public function getSupportContact(): array
    {
        return $this->support_contact_info ?? [
            'email' => $this->contact_email,
            'phone' => $this->contact_phone,
            'website' => $this->website,
        ];
    }

    /**
     * Verificar si puede manejar un tipo de problema
     */
    public function canHandleIssue(string $issueType): bool
    {
        if (!$this->warranty_policies) {
            return true; // Por defecto maneja todos
        }

        $handledIssues = $this->warranty_policies['handled_issues'] ?? [];
        return empty($handledIssues) || in_array($issueType, $handledIssues);
    }
}
