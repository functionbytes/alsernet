<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Services\TicketCategoryValidationBuilder;

/**
 * Public, unauthenticated request — the ticket-forms widget (and any other
 * embed) submits here without auth. Rate limiting is set on the route group.
 * Rules are dynamic per category custom fields, built by
 * TicketCategoryValidationBuilder, so they cannot be declared statically.
 */
class SubmitPublicTicketRequest extends FormRequest
{
    /**
     * @var array{rules: array<string, array<string>>, attributes: array<string, string>}|null
     */
    private ?array $built = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->buildValidation()['rules'];
    }

    public function attributes(): array
    {
        return $this->buildValidation()['attributes'];
    }

    /**
     * @return array{rules: array<string, array<string>>, attributes: array<string, string>}
     */
    private function buildValidation(): array
    {
        return $this->built ??= app(TicketCategoryValidationBuilder::class)
            ->buildForSubmission($this->resolveCategory());
    }

    private function resolveCategory(): TicketCategory
    {
        return TicketCategory::where('slug', $this->route('slug'))
            ->where('active', true)
            ->firstOrFail();
    }
}
