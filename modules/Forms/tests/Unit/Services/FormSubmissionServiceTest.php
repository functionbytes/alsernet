<?php

namespace Modules\Forms\Tests\Unit\Services;

use Illuminate\Http\Request;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Models\FormSubmissionValue;
use Modules\Forms\Services\FormSubmissionService;
use Modules\Forms\Tests\TestCase;

class FormSubmissionServiceTest extends TestCase
{
    private FormSubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FormSubmissionService::class);
    }

    public function test_process_creates_submission_record(): void
    {
        $form = Form::factory()->active()->create(['access_control' => 'public']);

        $request = Request::create('/forms/'.$form->slug.'/submit', 'POST', []);

        $submission = $this->service->process($form, $request);

        $this->assertInstanceOf(FormSubmission::class, $submission);
        $this->assertSame($form->id, $submission->form_id);
        $this->assertSame('new', $submission->status);
        $this->assertFalse($submission->is_read);
        $this->assertFalse($submission->is_spam);
    }

    public function test_process_saves_field_values(): void
    {
        $form = Form::factory()->active()->create(['access_control' => 'public']);
        FormField::factory()->for($form)->create([
            'type' => 'text',
            'key' => 'nombre',
            'label' => 'Nombre',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $request = Request::create('/forms/'.$form->slug.'/submit', 'POST', [
            'nombre' => 'Test User',
        ]);

        $submission = $this->service->process($form, $request);

        $this->assertDatabaseHas('form_submission_values', [
            'submission_id' => $submission->id,
            'field_key' => 'nombre',
            'value' => 'Test User',
        ]);
    }

    public function test_process_handles_checkbox_array(): void
    {
        $form = Form::factory()->active()->create(['access_control' => 'public']);
        FormField::factory()->for($form)->create([
            'type' => 'checkbox',
            'key' => 'opciones',
            'label' => 'Opciones',
            'is_visible' => true,
            'sort_order' => 1,
            'options' => [
                ['value' => 'a', 'label' => 'Opcion A'],
                ['value' => 'b', 'label' => 'Opcion B'],
            ],
        ]);

        $request = Request::create('/forms/'.$form->slug.'/submit', 'POST', [
            'opciones' => ['a', 'b'],
        ]);

        $submission = $this->service->process($form, $request);

        $value = FormSubmissionValue::where('submission_id', $submission->id)
            ->where('field_key', 'opciones')
            ->first();

        $this->assertNotNull($value);
        $decoded = json_decode($value->value, true);
        $this->assertIsArray($decoded);
        $this->assertContains('a', $decoded);
        $this->assertContains('b', $decoded);
    }

    public function test_utm_parameters_extracted_from_request(): void
    {
        $form = Form::factory()->active()->create(['access_control' => 'public']);

        $request = Request::create('/forms/'.$form->slug.'/submit', 'POST', [
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'promo_2026',
            'utm_term' => 'formularios',
            'utm_content' => 'cta_button',
        ]);

        $submission = $this->service->process($form, $request);

        $this->assertSame('newsletter', $submission->utm_source);
        $this->assertSame('email', $submission->utm_medium);
        $this->assertSame('promo_2026', $submission->utm_campaign);
        $this->assertSame('formularios', $submission->utm_term);
        $this->assertSame('cta_button', $submission->utm_content);
    }

    public function test_layout_fields_are_skipped_when_saving_values(): void
    {
        $form = Form::factory()->active()->create(['access_control' => 'public']);
        FormField::factory()->for($form)->create([
            'type' => 'section_header',
            'key' => 'seccion_titulo',
            'label' => 'Sección 1',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $request = Request::create('/forms/'.$form->slug.'/submit', 'POST', []);

        $submission = $this->service->process($form, $request);

        $this->assertDatabaseMissing('form_submission_values', [
            'submission_id' => $submission->id,
            'field_key' => 'seccion_titulo',
        ]);
    }
}
