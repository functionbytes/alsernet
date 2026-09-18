<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Jobs;

use Modules\Helpdesk\Models\Setting as HelpdeskGeneralSetting;
use Modules\HelpdeskTickets\Jobs\Helpdesks\FetchTicketEmailsJob;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use ReflectionMethod;
use Tests\TestCase;

/**
 * allowedAttachmentExtensions() en un archivo APARTE de
 * FetchTicketEmailsJobTest.php a propósito: ese archivo está en la lista de
 * tests que no se vuelven a correr esta sesión (toca
 * TicketEmailChannelsRepository/incoming_email, Setting real — ver
 * feedback_settings_tests_corrupted_real_channel_data). Este test solo
 * instancia el job por reflexión para probar UN método privado que no toca
 * esa zona en absoluto: ni IMAP, ni TicketEmailChannelsRepository, ni
 * incoming_email — solo Modules\Helpdesk\Models\Setting (conexión
 * 'helpdesk', tabla helpdesk_settings, sin relación con la corrupción ya
 * documentada).
 *
 * Bug real que motiva esto (4-sep-2026): Ajustes → Subida de archivos ya
 * guardaba uploading.allowed_extensions, pero ningún consumidor lo leía — un
 * admin podía "guardar" un cambio ahí sin ningún efecto real (un .mp3 real
 * se descartó en silencio sin forma de permitirlo desde la UI).
 */
class FetchTicketEmailsAllowedExtensionsTest extends TestCase
{
    use SharesHelpdeskPdo;

    protected function setUp(): void
    {
        parent::setUp();

        HelpdeskGeneralSetting::set('uploading.allowed_extensions', '', 'uploading');
    }

    public function test_usa_el_default_fijo_sin_nada_configurado(): void
    {
        $this->assertSame(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'zip'], $this->allowedExtensions());
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
        $job = new FetchTicketEmailsJob;
        $method = new ReflectionMethod($job, 'allowedAttachmentExtensions');

        return $method->invoke($job);
    }
}
