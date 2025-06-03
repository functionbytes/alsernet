<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReturnRule;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductReturnRuleController extends Controller
{
    /**
     * Mostrar lista de reglas de devolución
     */
    public function index(Request $request)
    {
        $query = ProductReturnRule::with(['category', 'product'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('rule_type')) {
            $query->where('rule_type', $request->rule_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $rules = $query->paginate(20);
        $categories = Category::orderBy('name')->get();

        return view('admin.return-rules.index', compact('rules', 'categories'));
    }

    /**
     * Mostrar formulario para crear nueva regla
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::with('category')->orderBy('name')->get();

        return view('admin.return-rules.create', compact('categories', 'products'));
    }

    /**
     * Guardar nueva regla
     */
    public function store(Request $request)
    {
        $request->validate([
            'rule_type' => 'required|in:category,product,global',
            'category_id' => 'nullable|required_if:rule_type,category|exists:categories,id',
            'product_id' => 'nullable|required_if:rule_type,product|exists:products,id',
            'is_returnable' => 'required|boolean',
            'return_period_days' => 'nullable|integer|min:1|max:365',
            'max_return_percentage' => 'required|numeric|min:0|max:100',
            'requires_original_packaging' => 'boolean',
            'requires_receipt' => 'boolean',
            'allow_partial_return' => 'boolean',
            'priority' => 'required|integer|min:0|max:100',
            'conditions' => 'nullable|array',
            'excluded_reasons' => 'nullable|array',
        ]);

        // Validar que no exista duplicado
        $existingRule = ProductReturnRule::where('rule_type', $request->rule_type)
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->rule_type === 'global', fn($q) => $q->whereNull('category_id')->whereNull('product_id'))
            ->where('is_active', true)
            ->first();

        if ($existingRule) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['duplicate' => 'Ya existe una regla activa para este elemento']);
        }

        ProductReturnRule::create($request->all());

        return redirect()->route('admin.return-rules.index')
            ->with('success', 'Regla de devolución creada exitosamente');
    }

    /**
     * Mostrar regla específica
     */
    public function show(ProductReturnRule $returnRule)
    {
        $returnRule->load(['category', 'product', 'returnValidations.order.user']);

        // Estadísticas de uso de la regla
        $stats = [
            'total_validations' => $returnRule->returnValidations()->count(),
            'passed_validations' => $returnRule->returnValidations()->where('validation_status', 'passed')->count(),
            'failed_validations' => $returnRule->returnValidations()->where('validation_status', 'failed')->count(),
            'manual_reviews' => $returnRule->returnValidations()->where('validation_status', 'manual_review')->count(),
        ];

        return view('admin.return-rules.show', compact('returnRule', 'stats'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(ProductReturnRule $returnRule)
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::with('category')->orderBy('name')->get();

        return view('admin.return-rules.edit', compact('returnRule', 'categories', 'products'));
    }

    /**
     * Actualizar regla
     */
    public function update(Request $request, ProductReturnRule $returnRule)
    {
        $request->validate([
            'is_returnable' => 'required|boolean',
            'return_period_days' => 'nullable|integer|min:1|max:365',
            'max_return_percentage' => 'required|numeric|min:0|max:100',
            'requires_original_packaging' => 'boolean',
            'requires_receipt' => 'boolean',
            'allow_partial_return' => 'boolean',
            'priority' => 'required|integer|min:0|max:100',
            'conditions' => 'nullable|array',
            'excluded_reasons' => 'nullable|array',
            'special_instructions' => 'nullable|string|max:1000',
        ]);

        $returnRule->update($request->all());

        return redirect()->route('admin.return-rules.show', $returnRule)
            ->with('success', 'Regla de devolución actualizada exitosamente');
    }

    /**
     * Activar/Desactivar regla
     */
    public function toggleStatus(ProductReturnRule $returnRule)
    {
        $returnRule->update(['is_active' => !$returnRule->is_active]);

        $status = $returnRule->is_active ? 'activada' : 'desactivada';

        return redirect()->back()
            ->with('success', "Regla {$status} exitosamente");
    }

    /**
     * Eliminar regla
     */
    public function destroy(ProductReturnRule $returnRule)
    {
        if ($returnRule->returnValidations()->count() > 0) {
            return redirect()->back()
                ->withErrors(['delete' => 'No se puede eliminar una regla que tiene validaciones asociadas']);
        }

        $returnRule->delete();

        return redirect()->route('admin.return-rules.index')
            ->with('success', 'Regla de devolución eliminada exitosamente');
    }

    /**
     * Clonar regla existente
     */
    public function clone(ProductReturnRule $returnRule)
    {
        $newRule = $returnRule->replicate();
        $newRule->priority = 0;
        $newRule->is_active = false;
        $newRule->save();

        return redirect()->route('admin.return-rules.edit', $newRule)
            ->with('success', 'Regla clonada exitosamente. Ajusta los parámetros y actívala.');
    }

    /**
     * Exportar reglas a CSV
     */
    public function export()
    {
        $rules = ProductReturnRule::with(['category', 'product'])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="reglas_devolucion.csv"',
        ];

        $callback = function () use ($rules) {
            $file = fopen('php://output', 'w');

            // Encabezados
            fputcsv($file, [
                'ID', 'Tipo', 'Categoría', 'Producto', 'Retornable',
                'Días', 'Max %', 'Empaque Original', 'Recibo',
                'Prioridad', 'Activa', 'Creada'
            ]);

            foreach ($rules as $rule) {
                fputcsv($file, [
                    $rule->id,
                    $rule->rule_type,
                    $rule->category?->name ?? 'N/A',
                    $rule->product?->name ?? 'N/A',
                    $rule->is_returnable ? 'Sí' : 'No',
                    $rule->return_period_days ?? 'Sin límite',
                    $rule->max_return_percentage . '%',
                    $rule->requires_original_packaging ? 'Sí' : 'No',
                    $rule->requires_receipt ? 'Sí' : 'No',
                    $rule->priority,
                    $rule->is_active ? 'Sí' : 'No',
                    $rule->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Probar regla con datos simulados
     */
    public function test(Request $request, ProductReturnRule $returnRule)
    {
        $request->validate([
            'test_data' => 'required|array',
        ]);

        $results = $returnRule->validateReturn($request->test_data);

        return response()->json([
            'success' => true,
            'results' => $results,
            'rule_description' => $returnRule->description,
        ]);
    }
}s
