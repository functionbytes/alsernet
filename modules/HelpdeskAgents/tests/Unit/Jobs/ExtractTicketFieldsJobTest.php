<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Jobs;

use Modules\HelpdeskAgents\Jobs\ExtractTicketFieldsJob;
use Modules\HelpdeskTickets\Models\TicketCategoryField;
use Tests\TestCase;

/**
 * Extracción de campos personalizados de la categoría.
 *
 * Lo que se protege es que un valor deducido no rompa el formulario: un email
 * malformado o una opción inventada en un `select` no dan error al guardar,
 * dan error cuando el agente abre el ticket, y averiguar de dónde salió ese
 * valor cuesta más que no haberlo puesto.
 */
class ExtractTicketFieldsJobTest extends TestCase
{
    private function coerce(TicketCategoryField $field, string $value): mixed
    {
        $job = new ExtractTicketFieldsJob(1);
        $method = new \ReflectionMethod($job, 'coerce');
        $method->setAccessible(true);

        return $method->invoke($job, $field, $value);
    }

    private function field(string $type, array $options = []): TicketCategoryField
    {
        return new TicketCategoryField(['type' => $type, 'key' => 'k', 'label' => 'L', 'options' => $options]);
    }

    public function test_a_number_field_rejects_non_numeric_text(): void
    {
        $this->assertSame('12345', $this->coerce($this->field('number'), '12345'));
        $this->assertNull($this->coerce($this->field('number'), 'no lo recuerdo'));
    }

    public function test_an_email_field_rejects_a_malformed_address(): void
    {
        $this->assertSame('a@b.test', $this->coerce($this->field('email'), 'a@b.test'));
        $this->assertNull($this->coerce($this->field('email'), 'arroba b punto test'));
    }

    public function test_a_date_field_only_accepts_iso_format(): void
    {
        $this->assertSame('2026-03-14', $this->coerce($this->field('date'), '2026-03-14'));
        // "14 de marzo" es ambiguo y el formulario no lo entiende.
        $this->assertNull($this->coerce($this->field('date'), '14 de marzo'));
        $this->assertNull($this->coerce($this->field('date'), '14/03/2026'));
    }

    public function test_a_select_only_accepts_an_existing_option(): void
    {
        $field = $this->field('select', ['Grande', 'Mediano', 'Pequeño']);

        $this->assertSame('Mediano', $this->coerce($field, 'Mediano'));
        // Case-insensitive, pero devuelve el valor EXACTO de la lista.
        $this->assertSame('Grande', $this->coerce($field, 'grande'));
        // Inventado: fuera.
        $this->assertNull($this->coerce($field, 'Extragrande'));
    }

    public function test_a_select_supports_label_value_options(): void
    {
        $field = $this->field('select', [['value' => 'xl', 'label' => 'Extra grande']]);

        $this->assertSame('xl', $this->coerce($field, 'xl'));
    }

    public function test_a_checkbox_only_accepts_affirmative_values(): void
    {
        $this->assertSame('1', $this->coerce($this->field('checkbox'), 'sí'));
        $this->assertSame('1', $this->coerce($this->field('checkbox'), 'true'));
        $this->assertNull($this->coerce($this->field('checkbox'), 'no'));
    }

    public function test_a_text_field_is_capped(): void
    {
        $value = $this->coerce($this->field('text'), str_repeat('x', 2000));

        $this->assertSame(500, mb_strlen($value));
    }

    public function test_an_empty_value_is_never_stored(): void
    {
        $this->assertNull($this->coerce($this->field('text'), '   '));
    }

    public function test_it_is_disabled_by_default(): void
    {
        // No es gratis como el sentimiento: es una llamada propia por ticket.
        $this->assertFalse(config('helpdeskagents.ticket_ai.field_extraction'));
    }
}
