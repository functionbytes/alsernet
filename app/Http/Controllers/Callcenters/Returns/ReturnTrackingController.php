<?php

// 1. CONTROLLER: app/Http/Controllers/CustomerPortal/ReturnTrackingController.php
namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Return;
use App\Models\ReturnStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReturnTrackingController extends Controller
{
    /**
     * Mostrar formulario de búsqueda
     */
    public function index()
    {
        return view('customer-portal.returns.search');
    }

    /**
     * Buscar devolución por código
     */
    public function search(Request $request)
    {
        $request->validate([
            'tracking_code' => 'required|string',
            'email' => 'required|email'
        ]);

        $return = Return::where('number', $request->tracking_code)
        ->where('customer_email', $request->email)
        ->first();

        if (!$return) {
            return back()
                ->withInput()
                ->withErrors(['tracking_code' => 'No se encontró ninguna devolución con esos datos.']);
        }

        // Generar token temporal para acceso sin autenticación
        $token = $this->generateAccessToken($return);

        return redirect()->route('customer.returns.show', [
            'return' => $return->id,
            'token' => $token
        ]);
    }

    /**
     * Mostrar detalles de la devolución
     */
    public function show(Request $request, Return $return)
    {
        // Validar acceso con token
        if (!$this->validateAccess($request, $return)) {
            return redirect()->route('customer.returns.search')
                ->withErrors(['access' => 'Acceso denegado. Por favor, busque su devolución nuevamente.']);
        }

// Cargar relaciones necesarias
$return->load([
    'items.product',
    'statusHistory',
    'costs',
    'communications' => function ($query) {
        $query->where('type', 'email')
            ->orderBy('created_at', 'desc');
    }
]);

// Preparar timeline
$timeline = $this->prepareTimeline($return);

// Documentos disponibles
$documents = $this->getAvailableDocuments($return);

// Calcular progreso
$progress = $this->calculateProgress($return);

return view('customer-portal.returns.show', compact(
    'return',
    'timeline',
    'documents',
    'progress'
));
}

/**
 * Descargar documento
 */
public function downloadDocument(Request $request, Return $return, string $type)
    {
        if (!$this->validateAccess($request, $return)) {
            abort(403);
        }

        $allowedTypes = ['label', 'receipt', 'invoice', 'form'];

        if (!in_array($type, $allowedTypes)) {
            abort(404);
        }

        $document = $this->getDocument($return, $type);

        if (!$document || !Storage::exists($document['path'])) {
            abort(404, 'Documento no encontrado');
        }

        return Storage::download($document['path'], $document['filename']);
    }

    /**
     * Actualizar email de notificaciones
     */
    public function updateEmail(Request $request, Return $return)
    {
        if (!$this->validateAccess($request, $return)) {
            abort(403);
        }

        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $return->update([
            'customer_email' => $request->email
        ]);

        return back()->with('success', 'Email actualizado correctamente.');
    }

    /**
     * Obtener actualizaciones vía AJAX
     */
    public function checkUpdates(Request $request, Return $return)
    {
        if (!$this->validateAccess($request, $return)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $lastCheck = $request->input('last_check', now()->subMinutes(5));

        $updates = [
            'status' => $return->status,
            'status_label' => $return->status_label,
            'has_updates' => $return->updated_at > $lastCheck,
            'new_messages' => $return->communications()
                ->where('created_at', '>', $lastCheck)
                ->count(),
            'progress' => $this->calculateProgress($return),
            'last_update' => $return->updated_at->format('Y-m-d H:i:s')
        ];

        return response()->json($updates);
    }

    // Métodos privados de apoyo

    private function generateAccessToken(Return $return): string
    {
        $token = Str::random(32);

        // Guardar token en caché por 24 horas
        cache()->put(
            "return_access_{$return->id}_{$token}",
            true,
            now()->addHours(24)
        );

        return $token;
    }

    private function validateAccess(Request $request, Return $return): bool
    {
        $token = $request->input('token') ?? $request->session()->get("return_token_{$return->id}");

        if (!$token) {
            return false;
        }

        $isValid = cache()->get("return_access_{$return->id}_{$token}");

        if ($isValid) {
            // Guardar en sesión para no requerir token en cada request
            $request->session()->put("return_token_{$return->id}", $token);
            return true;
        }

        return false;
    }

    private function prepareTimeline(Return $return): array
    {
        $timeline = collect();

        // Agregar creación
        $timeline->push([
            'date' => $return->created_at,
            'type' => 'created',
            'title' => 'Solicitud creada',
            'description' => 'Se ha creado la solicitud de devolución',
            'icon' => 'file-plus',
            'color' => 'blue'
        ]);

        // Agregar cambios de estado
        foreach ($return->statusHistory as $history) {
            $timeline->push([
                'date' => $history->created_at,
                'type' => 'status_change',
                'title' => $this->getStatusChangeTitle($history),
                'description' => $history->notes,
                'icon' => $this->getStatusIcon($history->new_status),
                'color' => $this->getStatusColor($history->new_status)
            ]);
        }

        // Agregar comunicaciones importantes
        $return->communications()
            ->where('type', 'email')
            ->whereIn('template_used', ['approved', 'rejected', 'completed'])
            ->each(function ($communication) use ($timeline) {
                $timeline->push([
                    'date' => $communication->sent_at ?? $communication->created_at,
                    'type' => 'communication',
                    'title' => 'Email enviado',
                    'description' => $communication->subject,
                    'icon' => 'mail',
                    'color' => 'gray'
                ]);
            });

        // Ordenar por fecha descendente
        return $timeline->sortByDesc('date')->values()->toArray();
    }

    private function getAvailableDocuments(Return $return): array
    {
        $documents = [];

        // Etiqueta de devolución
        if ($return->status === 'approved' && $return->label_path) {
            $documents[] = [
                'type' => 'label',
                'name' => 'Etiqueta de Envío',
                'description' => 'Etiqueta prepagada para enviar su devolución',
                'icon' => 'tag',
                'available' => true
            ];
        }

        // Recibo de devolución
        if (in_array($return->status, ['processing', 'completed'])) {
            $documents[] = [
                'type' => 'receipt',
                'name' => 'Recibo de Devolución',
                'description' => 'Comprobante de su devolución',
                'icon' => 'file-text',
                'available' => true
            ];
        }

        // Formulario de devolución
        $documents[] = [
            'type' => 'form',
            'name' => 'Formulario de Devolución',
            'description' => 'Resumen de su solicitud de devolución',
            'icon' => 'clipboard',
            'available' => true
        ];

        return $documents;
    }

    private function calculateProgress(Return $return): array
    {
        $steps = [
            'created' => ['label' => 'Solicitud Creada', 'completed' => true],
            'reviewed' => ['label' => 'En Revisión', 'completed' => false],
            'approved' => ['label' => 'Aprobada', 'completed' => false],
            'shipped' => ['label' => 'Enviada', 'completed' => false],
            'received' => ['label' => 'Recibida', 'completed' => false],
            'completed' => ['label' => 'Completada', 'completed' => false]
        ];

        $currentStep = 1;

        switch ($return->status) {
            case 'pending':
                $steps['reviewed']['completed'] = true;
                $currentStep = 2;
                break;
            case 'approved':
                $steps['reviewed']['completed'] = true;
                $steps['approved']['completed'] = true;
                $currentStep = 3;
                break;
            case 'shipped':
                $steps['reviewed']['completed'] = true;
                $steps['approved']['completed'] = true;
                $steps['shipped']['completed'] = true;
                $currentStep = 4;
                break;
            case 'processing':
                $steps['reviewed']['completed'] = true;
                $steps['approved']['completed'] = true;
                $steps['shipped']['completed'] = true;
                $steps['received']['completed'] = true;
                $currentStep = 5;
                break;
            case 'completed':
                foreach ($steps as &$step) {
                    $step['completed'] = true;
                }
                $currentStep = 6;
                break;
            case 'rejected':
            case 'cancelled':
                $steps['reviewed']['completed'] = true;
                $currentStep = -1; // Estado final alternativo
                break;
        }

        return [
            'steps' => $steps,
            'current' => $currentStep,
            'percentage' => $currentStep > 0 ? round(($currentStep / 6) * 100) : 0
        ];
    }

    private function getStatusChangeTitle($history): string
{
    $titles = [
        'pending' => 'Solicitud en revisión',
        'approved' => 'Solicitud aprobada',
        'rejected' => 'Solicitud rechazada',
        'shipped' => 'Producto enviado',
        'processing' => 'Producto recibido',
        'completed' => 'Devolución completada',
        'cancelled' => 'Devolución cancelada'
    ];

    return $titles[$history->new_status] ?? 'Estado actualizado';
}

    private function getStatusIcon($status): string
{
    $icons = [
        'pending' => 'clock',
        'approved' => 'check-circle',
        'rejected' => 'x-circle',
        'shipped' => 'truck',
        'processing' => 'package',
        'completed' => 'check-square',
        'cancelled' => 'slash'
    ];

    return $icons[$status] ?? 'info';
}

    private function getStatusColor($status): string
{
    $colors = [
        'pending' => 'yellow',
        'approved' => 'green',
        'rejected' => 'red',
        'shipped' => 'blue',
        'processing' => 'indigo',
        'completed' => 'green',
        'cancelled' => 'gray'
    ];

    return $colors[$status] ?? 'gray';
}

    private function getDocument(Return $return, string $type): ?array
    {
        switch ($type) {
            case 'label':
                return [
                    'path' => $return->label_path,
                    'filename' => "etiqueta_devolucion_{$return->number}.pdf"
                ];
            case 'receipt':
                // Generar PDF dinámicamente o obtener de storage
                return [
                    'path' => "returns/receipts/{$return->id}.pdf",
                    'filename' => "recibo_devolucion_{$return->number}.pdf"
                ];
            case 'form':
                // Generar resumen de la solicitud
                return [
                    'path' => "returns/forms/{$return->id}.pdf",
                    'filename' => "formulario_devolucion_{$return->number}.pdf"
                ];
            default:
                return null;
        }
    }
}
