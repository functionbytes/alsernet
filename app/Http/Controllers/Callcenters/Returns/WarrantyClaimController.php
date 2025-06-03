<?php

namespace App\Http\Controllers;

use App\Models\WarrantyClaim;
use App\Services\WarrantyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarrantyClaimController extends Controller
{
    protected $warrantyService;

    public function __construct(WarrantyService $warrantyService)
    {
        $this->warrantyService = $warrantyService;
    }

    /**
     * Mostrar reclamos del usuario
     */
    public function index(Request $request)
    {
        $query = WarrantyClaim::where('user_id', Auth::id())
            ->with(['warranty.product', 'assignedUser'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $claims = $query->paginate(10);

        return view('warranty-claims.index', compact('claims'));
    }

    /**
     * Mostrar detalles del reclamo
     */
    public function show(WarrantyClaim $claim)
    {
        $this->authorize('view', $claim);

        $claim->load([
            'warranty.product',
            'warranty.warrantyType',
            'warranty.manufacturer',
            'user',
            'assignedUser',
            'resolvedBy'
        ]);

        return view('warranty-claims.show', compact('claim'));
    }

    /**
     * Actualizar reclamo (solo usuario propietario)
     */
    public function update(Request $request, WarrantyClaim $claim)
    {
        $this->authorize('update', $claim);

        $request->validate([
            'additional_info' => 'nullable|string|max:1000',
            'attachments' => 'array|max:3',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);

        if (!$claim->isActive()) {
            return redirect()->back()->withErrors(['claim' => 'El reclamo ya no puede ser modificado']);
        }

        $updateData = [];

        // Agregar información adicional a la descripción
        if ($request->filled('additional_info')) {
            $currentDescription = $claim->issue_description;
            $updateData['issue_description'] = $currentDescription . "\n\n[Información adicional del cliente]\n" . $request->additional_info;
        }

        // Manejar archivos adjuntos adicionales
        if ($request->hasFile('attachments')) {
            $currentAttachments = $claim->attachments ?? [];

            foreach ($request->file('attachments') as $file) {
                $path = $file->store('warranty-claims', 'public');
                $currentAttachments[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'uploaded_at' => now(),
                ];
            }

            $updateData['attachments'] = $currentAttachments;
        }

        if (!empty($updateData)) {
            $claim->update($updateData);

            $claim->addCommunicationLog([
                'type' => 'customer_update',
                'message' => 'Cliente agregó información adicional',
                'user_id' => Auth::id(),
                'timestamp' => now(),
            ]);

            return redirect()->back()->with('success', 'Reclamo actualizado exitosamente');
        }

        return redirect()->back();
    }

    /**
     * Cancelar reclamo
     */
    public function cancel(Request $request, WarrantyClaim $claim)
    {
        $this->authorize('update', $claim);

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        if (!$claim->isActive()) {
            return redirect()->back()->withErrors(['cancel' => 'El reclamo ya no puede ser cancelado']);
        }

        $claim->changeStatus(WarrantyClaim::STATUS_CANCELLED, Auth::user(), $request->cancellation_reason);

        return redirect()->route('warranty-claims.index')
            ->with('success', 'Reclamo cancelado exitosamente');
    }

    /**
     * Evaluar resolución (calificación del cliente)
     */
    public function evaluate(Request $request, WarrantyClaim $claim)
    {
        $this->authorize('view', $claim);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'satisfaction_notes' => 'nullable|string|max:500',
        ]);

        if ($claim->status !== WarrantyClaim::STATUS_COMPLETED) {
            return redirect()->back()->withErrors(['evaluate' => 'Solo se pueden evaluar reclamos completados']);
        }

        if ($claim->customer_rating) {
            return redirect()->back()->withErrors(['evaluate' => 'Ya has evaluado este reclamo']);
        }

        $claim->update([
            'customer_rating' => $request->rating,
            'customer_satisfaction_notes' => $request->satisfaction_notes,
        ]);

        $claim->addCommunicationLog([
            'type' => 'customer_evaluation',
            'message' => 'Cliente evaluó la resolución',
            'rating' => $request->rating,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ]);

        return redirect()->back()->with('success', 'Evaluación registrada exitosamente');
    }

    /**
     * Obtener estado de reclamo con fabricante
     */
    public function getManufacturerStatus(WarrantyClaim $claim)
    {
        $this->authorize('view', $claim);

        if (!$claim->manufacturer_claim_id || !$claim->warranty->manufacturer) {
            return response()->json([
                'success' => false,
                'message' => 'No hay reclamo asociado con el fabricante',
            ]);
        }

        $result = $claim->warranty->manufacturer->getClaimStatus($claim->manufacturer_claim_id);

        if ($result['success']) {
            // Actualizar estado local si es diferente
            $manufacturerStatus = $result['claim_status']['status'] ?? null;
            if ($manufacturerStatus && $manufacturerStatus !== $claim->manufacturer_status) {
                $claim->update([
                    'manufacturer_status' => $manufacturerStatus,
                    'manufacturer_response' => $result['claim_status'],
                    'manufacturer_response_at' => now(),
                ]);
            }
        }

        return response()->json($result);
    }

    /**
     * Reabrir reclamo
     */
    public function reopen(Request $request, WarrantyClaim $claim)
    {
        $this->authorize('update', $claim);

        $request->validate([
            'reopen_reason' => 'required|string|max:500',
        ]);

        if ($claim->status !== WarrantyClaim::STATUS_COMPLETED) {
            return redirect()->back()->withErrors(['reopen' => 'Solo se pueden reabrir reclamos completados']);
        }

        // Verificar tiempo límite para reapertura (ej: 7 días)
        if ($claim->resolved_at && $claim->resolved_at->diffInDays(now()) > 7) {
            return redirect()->back()->withErrors(['reopen' => 'El tiempo límite para reabrir este reclamo ha expirado']);
        }

        $claim->changeStatus(WarrantyClaim::STATUS_UNDER_REVIEW, Auth::user(), 'Reabierto por cliente: ' . $request->reopen_reason);

        // Limpiar resolución anterior
        $claim->update([
            'resolved_at' => null,
            'resolved_by' => null,
            'customer_rating' => null,
            'customer_satisfaction_notes' => null,
        ]);

        return redirect()->back()->with('success', 'Reclamo reabierto exitosamente');
    }

    /**
     * Obtener historial de comunicación
     */
    public function getCommunicationLog(WarrantyClaim $claim)
    {
        $this->authorize('view', $claim);

        $log = $claim->communication_log ?? [];

        // Ordenar por fecha más reciente
        usort($log, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return response()->json([
            'success' => true,
            'communication_log' => $log,
        ]);
    }

    /**
     * Estadísticas de reclamos del usuario
     */
    public function getStats()
    {
        $userId = Auth::id();

        $stats = [
            'total_claims' => WarrantyClaim::where('user_id', $userId)->count(),
            'active_claims' => WarrantyClaim::where('user_id', $userId)->active()->count(),
            'completed_claims' => WarrantyClaim::where('user_id', $userId)->where('status', WarrantyClaim::STATUS_COMPLETED)->count(),
            'average_resolution_time' => WarrantyClaim::where('user_id', $userId)
                ->where('status', WarrantyClaim::STATUS_COMPLETED)
                ->whereNotNull('total_resolution_hours')
                ->avg('total_resolution_hours'),
            'satisfaction_rating' => WarrantyClaim::where('user_id', $userId)
                ->whereNotNull('customer_rating')
                ->avg('customer_rating'),
        ];

        return response()->json($stats);
    }
}
