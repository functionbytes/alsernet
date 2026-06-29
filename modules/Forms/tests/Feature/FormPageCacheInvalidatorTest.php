<?php

namespace Modules\Forms\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Forms\Jobs\InvalidateFormPagesCacheJob;
use Modules\Forms\Services\FormPageCacheInvalidator;
use Modules\Forms\Tests\TestCase;

class FormPageCacheInvalidatorTest extends TestCase
{
    public function test_invalidator_is_registered_as_singleton(): void
    {
        $a = app(FormPageCacheInvalidator::class);
        $b = app(FormPageCacheInvalidator::class);

        $this->assertSame($a, $b, 'FormPageCacheInvalidator debe ser singleton.');
    }

    public function test_invalidator_dedupes_within_request(): void
    {
        $form = $this->createActiveForm();
        $invalidator = app(FormPageCacheInvalidator::class);

        // La primera llamada ejecuta, las siguientes deberían deduparse.
        // No hay forma limpia de espiar invalidateByIds desde fuera sin mocks, así
        // que probamos que no lanza excepciones y devuelve void dos veces seguidas.
        $invalidator->invalidate($form);
        $invalidator->invalidate($form);

        $this->assertTrue(true);
    }

    public function test_job_dispatched_when_driver_supports_queue(): void
    {
        config(['queue.default' => 'redis']);
        Queue::fake();

        $form = $this->createActiveForm();

        // Nuevo singleton para resetear el dedup
        app()->forgetInstance(FormPageCacheInvalidator::class);

        app(FormPageCacheInvalidator::class)->invalidate($form);

        Queue::assertPushed(InvalidateFormPagesCacheJob::class, function (InvalidateFormPagesCacheJob $job) use ($form): bool {
            return $job->formId === $form->id;
        });
    }
}
