<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Support;

use Modules\Helpdesk\Models\Setting as HelpdeskGeneralSetting;
use Modules\HelpdeskTickets\Support\InboundEmailAttachmentStorer;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * Movido de FetchTicketEmailsJobTest/FetchTicketEmailsAllowedExtensionsTest
 * (30-sep-2026, refactor estructural que extrajo el guardado de adjuntos de
 * FetchTicketEmailsJob a InboundEmailAttachmentStorer) a apuntar
 * directamente a esa clase: allowedAttachmentExtensions() es público ahí, así
 * que ya no hace falta reflexión ni instanciar el job.
 *
 * En un archivo APARTE del resto de tests de InboundEmail* a propósito,
 * igual que el original: solo toca Modules\Helpdesk\Models\Setting (conexión
 * 'helpdesk', tabla helpdesk_settings), sin relación con la corrupción de
 * incoming_email ya documentada (ver
 * feedback_settings_tests_corrupted_real_channel_data).
 *
 * Bug real que motiva esto (4-sep-2026): Ajustes → Subida de archivos ya
 * guardaba uploading.allowed_extensions, pero ningún consumidor lo leía — un
 * admin podía "guardar" un cambio ahí sin ningún efecto real (un .mp3 real
 * se descartó en silencio sin forma de permitirlo desde la UI).
 */
class InboundEmailAttachmentStorerTest extends TestCase
{
    use SharesHelpdeskPdo;

    protected function setUp(): void
    {
        parent::setUp();

        HelpdeskGeneralSetting::set('uploading.allowed_extensions', '', 'uploading');
    }

    public function test_usa_el_default_del_config_con_el_ajuste_vacio(): void
    {
        $this->assertSame(config('helpdesk.attachments.allowed_extensions'), $this->allowedExtensions());
        $this->assertContains('pdf', $this->allowedExtensions());
    }

    public function test_usa_lo_configurado_en_ajustes_subida_de_archivos(): void
    {
        HelpdeskGeneralSetting::set('uploading.allowed_extensions', 'pdf, MP3 ,zip', 'uploading');

        $this->assertSame(['pdf', 'mp3', 'zip'], $this->allowedExtensions());
    }

    /**
     * @return list<string>
     */
    private function allowedExtensions(): array
    {
        return (new InboundEmailAttachmentStorer)->allowedAttachmentExtensions();
    }
}
