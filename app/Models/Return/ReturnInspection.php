<?php


namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnInspection extends Model
{
    protected $table = 'return_inspections';

    protected $fillable = [
        'return_item_id',
        'inspector_id',
        'inspection_date',
        'condition_grade',
        'checklist_results',
        'final_decision',
        'inspection_photos',
        'notes',
        'requires_review',
        'reviewed_by',
        'reviewed_at',
        'review_notes'
    ];

    protected $casts = [
        'inspection_date' => 'datetime',
        'checklist_results' => 'array',
        'inspection_photos' => 'array',
        'requires_review' => 'boolean',
        'reviewed_at' => 'datetime'
    ];

    // Constantes de grado
    const GRADE_A = 'A'; // Como nuevo
    const GRADE_B = 'B'; // Buen estado
    const GRADE_C = 'C'; // Defectos menores
    const GRADE_D = 'D'; // No vendible

    // Decisiones finales
    const DECISION_RESTOCK = 'restock';
    const DECISION_OUTLET = 'outlet';
    const DECISION_REPAIR = 'repair';
    const DECISION_DESTROY = 'destroy';
    const DECISION_RETURN_TO_SUPPLIER = 'return_to_supplier';

    // Relaciones
    public function returnItem(): BelongsTo
    {
        return $this->belongsTo(ReturnRequestProduct::class, 'return_item_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ReturnException::class);
    }

    // Scopes
    public function scopePendingReview($query)
    {
        return $query->where('requires_review', true)
            ->whereNull('reviewed_at');
    }

    public function scopeByGrade($query, $grade)
    {
        return $query->where('condition_grade', $grade);
    }

    public function scopeByDecision($query, $decision)
    {
        return $query->where('final_decision', $decision);
    }

    /**
     * Obtener decisión automática según el grado
     */
    public static function getAutomaticDecision($grade): string
    {
        $decisions = [
            self::GRADE_A => self::DECISION_RESTOCK,
            self::GRADE_B => self::DECISION_OUTLET,
            self::GRADE_C => self::DECISION_REPAIR,
            self::GRADE_D => self::DECISION_DESTROY
        ];

        return $decisions[$grade] ?? self::DECISION_DESTROY;
    }

    /**
     * Determinar si requiere revisión manual
     */
    public function determineIfRequiresReview(): bool
    {
        // Requiere revisión si:
        // 1. El valor del producto es alto
        $productValue = $this->returnItem->total_price;
        if ($productValue > config('returns.high_value_threshold', 500)) {
            return true;
        }

        // 2. Hay discrepancia entre condición reportada y encontrada
        if ($this->returnItem->return_condition !== $this->mapGradeToCondition()) {
            return true;
        }

        // 3. El producto está dañado (grado D)
        if ($this->condition_grade === self::GRADE_D) {
            return true;
        }

        // 4. Hay items del checklist fallidos
        $failedChecks = collect($this->checklist_results)
            ->where('passed', false)
            ->count();

        if ($failedChecks > 2) {
            return true;
        }

        return false;
    }

    /**
     * Mapear grado a condición
     */
    protected function mapGradeToCondition(): string
    {
        $mapping = [
            self::GRADE_A => 'unopened',
            self::GRADE_B => 'opened_unused',
            self::GRADE_C => 'used',
            self::GRADE_D => 'damaged'
        ];

        return $mapping[$this->condition_grade] ?? 'damaged';
    }

    /**
     * Obtener etiqueta del grado
     */
    public function getGradeLabel(): string
    {
        $labels = [
            self::GRADE_A => 'Como nuevo',
            self::GRADE_B => 'Buen estado',
            self::GRADE_C => 'Defectos menores',
            self::GRADE_D => 'No vendible'
        ];

        return $labels[$this->condition_grade] ?? 'Desconocido';
    }

    /**
     * Obtener etiqueta de decisión
     */
    public function getDecisionLabel(): string
    {
        $labels = [
            self::DECISION_RESTOCK => 'Reincorporar al stock',
            self::DECISION_OUTLET => 'Enviar a outlet',
            self::DECISION_REPAIR => 'Enviar a reparación',
            self::DECISION_DESTROY => 'Destruir',
            self::DECISION_RETURN_TO_SUPPLIER => 'Devolver al proveedor'
        ];

        return $labels[$this->final_decision] ?? 'Pendiente';
    }

    /**
     * Aprobar revisión
     */
    public function approveReview($reviewerId, $notes = null): bool
    {
        return $this->update([
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes
        ]);
    }
}
