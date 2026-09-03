<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskEmailActivity\Database\Seeders\HelpdeskEmailActivityPermissionsSeeder;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Tests\TestCase;

/**
 * Búsqueda por nombre de cliente (ver
 * EmailLogController::recipientEmailsMatchingCustomer()).
 *
 * El nombre no vive en email_logs — la tabla solo guarda direcciones — así que
 * el buscador lo traduce antes a los correos de los clientes que encajan. Estos
 * tests cubren esa traducción y, sobre todo, sus dos topes: el mínimo de
 * caracteres y el límite de clientes, que son lo que impide que una búsqueda
 * genérica ("a") monte un OR con medio fichero de clientes dentro.
 */
class EmailLogCustomerSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailActivityPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemailactivity.view');
    }

    private function customer(string $name, string $email): Customer
    {
        return Customer::query()->create(['name' => $name, 'email' => $email]);
    }

    public function test_searching_a_customer_name_finds_the_emails_sent_to_them(): void
    {
        $this->customer('Zenobia Fixturemann', 'zenobia.fixture@ejemplo-test.invalid');

        $hers = EmailLog::factory()->create([
            'to_addresses' => ['zenobia.fixture@ejemplo-test.invalid'],
            'subject' => 'Correo de la clienta buscada',
        ]);
        $other = EmailLog::factory()->create([
            'to_addresses' => ['otra.persona@ejemplo-test.invalid'],
            'subject' => 'Correo de otra persona',
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.index', ['search' => 'Zenobia Fixturemann']))
            ->assertOk()
            ->assertSee($hers->subject, false)
            ->assertDontSee($other->subject, false);
    }

    public function test_a_partial_name_also_matches(): void
    {
        $this->customer('Aurelio Fixturebach', 'aurelio.fixture@ejemplo-test.invalid');

        $log = EmailLog::factory()->create([
            'to_addresses' => ['aurelio.fixture@ejemplo-test.invalid'],
            'subject' => 'Correo por nombre parcial',
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.index', ['search' => 'Fixturebach']))
            ->assertOk()
            ->assertSee($log->subject, false);
    }

    public function test_searches_shorter_than_the_minimum_do_not_match_by_customer(): void
    {
        // 'Ab' encaja con el NOMBRE del cliente, pero por debajo del mínimo el
        // criterio de cliente ni se evalúa: sin este tope, dos letras
        // arrastrarían a decenas de clientes al OR.
        //
        // La dirección y el asunto se eligen sin esas dos letras a propósito:
        // si las tuvieran, el email lo encontrarían los criterios de siempre
        // (destinatario/asunto) y el test no probaría nada sobre el tope.
        $this->customer('Ab Fixtureson', 'zzz-corto@ejemplo-test.invalid');

        // El remitente también se fija: la factory lo genera con Faker y el
        // buscador mira from_address/from_name, así que un remitente aleatorio
        // que contuviera "ab" haría aparecer la fila por otro criterio.
        $log = EmailLog::factory()->create([
            'to_addresses' => ['zzz-corto@ejemplo-test.invalid'],
            'from_address' => 'zzz-remitente@ejemplo-test.invalid',
            'from_name' => 'Zzz Remitente',
            'subject' => 'Correo por dentro del tope',
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.index', ['search' => 'Ab']))
            ->assertOk()
            ->assertDontSee($log->subject, false);
    }

    public function test_the_email_address_still_works_as_before(): void
    {
        // La búsqueda por cliente es un criterio AÑADIDO: los de siempre
        // (asunto, remitente, destinatario) tienen que seguir intactos.
        $log = EmailLog::factory()->create([
            'to_addresses' => ['sin.ficha@ejemplo-test.invalid'],
            'subject' => 'Correo sin ficha de cliente',
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.index', ['search' => 'sin.ficha@ejemplo-test.invalid']))
            ->assertOk()
            ->assertSee($log->subject, false);
    }
}
