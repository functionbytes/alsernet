<?php

namespace App\Http\Controllers;

use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantyType;
use App\Services\WarrantyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarrantyController extends Controller
{
    protected $warrantyService;

    public function __construct(WarrantyService $warrantyService)
    {
        $this->warrantyService = $warrantyService;
    }

    /**
     * Mostrar garantías del usuario
     */
    public function index(Request $request)
    {
        $query = Warranty::forUser(Auth::id())
            ->with(['product', 'warrantyType', 'manufacturer'])
            ->orderBy('warranty_end_date', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->expired();
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('product_name')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product_name . '%');
            });
        }

        $warranties = $query->paginate(10);

        return view('warranties.index', compact('warranties'));
    }

    /**
     * Mostrar detalles de garantía
     */
    public function show(Warranty $warranty)
    {
        $this->authorize('view', $warranty);

        $warranty->load([
            'product',
            'warrantyType',
            'manufacturer',
            'order',
            'claims.assignedUser',
            'extensions'
        ]);

        $canExtend = $this->warrantyService->canExtendWarranty($warranty);
        $availableExtensions = $canExtend ? WarrantyType::where('code', 'EXTENDED')->active()->get() : collect();

        return view('warranties.show', compact('warranty', 'canExtend', 'availableExtensions'));
    }

    /**
     * Activar garantía
     */
    public function activate(Request $request, Warranty $warranty)
    {
        $this->authorize('update', $warranty);

        $request->validate([
            'serial_number' => 'nullable|string|max:255',
            'activation_notes' => 'nullable|string|max:500',
        ]);

        if ($warranty->activation_date) {
            return redirect()->back()->withErrors(['activation' => 'La garantía ya está activada']);
        }

        $activationDetails = [];
        if ($request->filled('serial_number')) {
            $warranty->update(['product_serial_number' => $request->serial_number]);
            $activationDetails['serial_number'] = $request->serial_number;
        }

        if ($request->filled('activation_notes')) {
            $activationDetails['notes'] = $request->activation_notes;
        }

        $warranty->activate(Auth::user(), $activationDetails);

        return redirect()->back()->with('success', 'Garantía activada exitosamente');
    }

    /**
     * Crear reclamo de garantía
     */
    public function createClaim(Request $request, Warranty $warranty)
    {
        $this->authorize('createClaim', $warranty);

        if (!$warranty->isActive()) {
            return redirect()->back()->withErrors(['warranty' => 'La garantía no está activa']);
        }

        return view('warranties.create-claim', compact('warranty'));
    }

    /**
     * Guardar reclamo de garantía
     */
    public function storeClaim(Request $request, Warranty $warranty)
    {
        $this->authorize('createClaim', $warranty);

        $request->validate([
            'issue_category' => 'required|string|in:hardware,software,defect,damage,performance',
            'issue_description' => 'required|string|min:10|max:1000',
            'issue_occurred_date' => 'required|date|before_or_equal:today|after_or_equal:' . $warranty->warranty_start_date,
            'symptoms' => 'array',
            'attachments' => 'array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,mp4,mov|max:10240', // 10MB
        ]);

        if (!$warranty->isActive()) {
            return redirect()->back()->withErrors(['warranty' => 'La garantía no está activa']);
        }

        // Manejar archivos adjuntos
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('warranty-claims', 'public');
                $attachments[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $claimData = [
            'issue_category' => $request->issue_category,
            'issue_description' => $request->issue_description,
            'issue_occurred_date' => $request->issue_occurred_date,
            'symptoms' => $request->symptoms ?? [],
            'attachments' => $attachments,
        ];

        $claim = $this->warrantyService->createWarrantyClaim($warranty, Auth::user(), $claimData);

        return redirect()->route('warranties.claims.show', $claim)
            ->with('success', 'Reclamo de garantía creado exitosamente. Número: ' . $claim->claim_number);
    }

    /**
     * Extender garantía
     */
    public function extend(Request $request, Warranty $warranty)
    {
        $this->authorize('extend', $warranty);

        $request->validate([
            'warranty_type_id' => 'required|exists:warranty_types,id',
            'additional_months' => 'required|integer|min:1|max:60',
        ]);

        if (!$this->warrantyService->canExtendWarranty($warranty)) {
            return redirect()->back()->withErrors(['extend' => 'Esta garantía no puede ser extendida']);
        }

        $warrantyType = WarrantyType::findOrFail($request->warranty_type_id);

        try {
            $extension = $this->warrantyService->extendWarranty(
                $warranty,
                $request->additional_months,
                $warrantyType
            );

            return redirect()->route('warranties.show', $warranty)
                ->with('success', 'Garantía extendida exitosamente por ' . $request->additional_months . ' meses');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['extend' => $e->getMessage()]);
        }
    }

    /**
     * Transferir garantía
     */
    public function transfer(Request $request, Warranty $warranty)
    {
        $this->authorize('transfer', $warranty);

        $request->validate([
            'new_owner_email' => 'required|email|exists:users,email',
            'transfer_reason' => 'required|string|max:500',
        ]);

        if (!$warranty->warrantyType->transferable) {
            return redirect()->back()->withErrors(['transfer' => 'Esta garantía no es transferible']);
        }

        $newOwner = \App\Models\User::where('email', $request->new_owner_email)->first();

        if ($newOwner->id === $warranty->user_id) {
            return redirect()->back()->withErrors(['transfer' => 'No puedes transferir a ti mismo']);
        }

        try {
            $this->warrantyService->transferWarranty($warranty, $newOwner, [
                'reason' => $request->transfer_reason,
                'transferred_by' => Auth::user()->name,
            ]);

            return redirect()->route('warranties.index')
                ->with('success', 'Garantía transferida exitosamente a ' . $newOwner->name);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['transfer' => $e->getMessage()]);
        }
    }

    /**
     * Buscar garantía por número de serie
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|string',
        ]);

        $warranty = Warranty::where('product_serial_number', $request->serial_number)
            ->with(['product', 'warrantyType', 'manufacturer', 'user'])
            ->first();

        if (!$warranty) {
            return response()->json([
                'found' => false,
                'message' => 'No se encontró garantía para este número de serie',
            ]);
        }

        // Verificar permisos
        if ($warranty->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json([
                'found' => false,
                'message' => 'No tienes permisos para ver esta garantía',
            ]);
        }

        return response()->json([
            'found' => true,
            'warranty' => [
                'warranty_number' => $warranty->warranty_number,
                'product_name' => $warranty->product->name,
                'status' => $warranty->status,
                'is_active' => $warranty->isActive(),
                'remaining_days' => $warranty->getRemainingDays(),
                'warranty_end_date' => $warranty->warranty_end_date->format('d/m/Y'),
                'manufacturer' => $warranty->manufacturer?->name,
                'warranty_type' => $warranty->warrantyType->name,
            ],
        ]);
    }

    /**
     * Descargar certificado de garantía
     */
    public function downloadCertificate(Warranty $warranty)
    {
        $this->authorize('view', $warranty);

        // Generar PDF del certificado
        $pdf = \PDF::loadView('warranties.certificate', compact('warranty'));

        return $pdf->download('garantia-' . $warranty->warranty_number . '.pdf');
    }

    /**
     * Registrar con fabricante manualmente
     */
    public function registerWithManufacturer(Warranty $warranty)
    {
        $this->authorize('update', $warranty);

        if ($warranty->is_registered_with_manufacturer) {
            return redirect()->back()->withErrors(['register' => 'Ya está registrada con el fabricante']);
        }

        if (!$warranty->manufacturer) {
            return redirect()->back()->withErrors(['register' => 'No hay fabricante asociado']);
        }

        $result = $this->warrantyService->registerWarrantyWithManufacturer($warranty);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->withErrors(['register' => $result['message']]);
        }
    }

    /**
     * Obtener garantías próximas a vencer (API)
     */
    public function getExpiringWarranties(Request $request)
    {
        $days = $request->get('days', 30);
        $notifications = $this->warrantyService->checkExpiringWarranties($days);

        return response()->json([
            'expiring_warranties' => $notifications,
            'total' => count($notifications),
        ]);
    }
}
