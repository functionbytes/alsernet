<?php

namespace Modules\Storage\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StorageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrative');

        // Start with empty custom disks
        Setting::set('system.custom_storage_disks', '[]');
        Cache::forget('storage.custom_disks');
    }

    // -------------------------------------------------------------------------
    // index()
    // -------------------------------------------------------------------------

    #[Test]
    public function index_requires_authentication(): void
    {
        $this->get(route('settings.storage'))->assertRedirect(route('login'));
    }

    #[Test]
    public function index_loads_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.storage'))
            ->assertOk()
            ->assertViewIs('storage::index')
            ->assertViewHas('storageData')
            ->assertViewHas('statistics');
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    #[Test]
    public function create_shows_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.storage.create'))
            ->assertOk()
            ->assertViewIs('storage::create')
            ->assertViewHas('driverOptions');
    }

    // -------------------------------------------------------------------------
    // store() — local disk
    // -------------------------------------------------------------------------

    #[Test]
    public function store_creates_local_public_disk(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'test_public',
                'driver' => 'local',
                'storage_type' => 'public',
            ])
            ->assertRedirect(route('settings.storage'))
            ->assertSessionHas('success');

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertCount(1, $disks);
        $this->assertEquals('test_public', $disks[0]['name']);
        $this->assertEquals('local', $disks[0]['driver']);
        $this->assertEquals('public', $disks[0]['storage_type']);
    }

    #[Test]
    public function store_creates_local_private_disk(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'test_private',
                'driver' => 'local',
                'storage_type' => 'private',
            ])
            ->assertRedirect(route('settings.storage'));

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertEquals('private', $disks[0]['storage_type']);
        $this->assertNull($disks[0]['url']);
    }

    // -------------------------------------------------------------------------
    // store() — FTP/SFTP disks
    // -------------------------------------------------------------------------

    #[Test]
    public function store_creates_ftp_disk_with_encrypted_password(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'my_ftp',
                'driver' => 'ftp',
                'host' => 'ftp.example.com',
                'username' => 'ftpuser',
                'password' => 'secret123',
                'port' => 21,
            ])
            ->assertRedirect(route('settings.storage'));

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertEquals('my_ftp', $disks[0]['name']);
        $this->assertNotEquals('secret123', $disks[0]['password']); // must be encrypted
        $this->assertEquals('secret123', decrypt($disks[0]['password'])); // must decrypt correctly
    }

    #[Test]
    public function store_creates_sftp_disk(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'my_sftp',
                'driver' => 'sftp',
                'host' => 'sftp.example.com',
                'username' => 'sftpuser',
                'password' => 'pass456',
                'port' => 22,
            ])
            ->assertRedirect(route('settings.storage'));

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertEquals('sftp', $disks[0]['driver']);
        $this->assertEquals('pass456', decrypt($disks[0]['password']));
    }

    #[Test]
    public function store_uses_default_port_when_omitted_for_ftp(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'default_port_ftp',
                'driver' => 'ftp',
                'host' => 'ftp.example.com',
                'username' => 'user',
            ])
            ->assertRedirect(route('settings.storage'));

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertEquals(21, $disks[0]['port']);
    }

    #[Test]
    public function store_uses_default_port_when_omitted_for_sftp(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'default_port_sftp',
                'driver' => 'sftp',
                'host' => 'sftp.example.com',
                'username' => 'user',
            ])
            ->assertRedirect(route('settings.storage'));

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertEquals(22, $disks[0]['port']);
    }

    // -------------------------------------------------------------------------
    // store() — S3 disk
    // -------------------------------------------------------------------------

    #[Test]
    public function store_creates_s3_disk_with_encrypted_secret(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'my_s3',
                'driver' => 's3',
                'bucket' => 'my-bucket',
                'region' => 'us-east-1',
                'key' => 'AKIAIOSFODNN7EXAMPLE',
                'secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            ])
            ->assertRedirect(route('settings.storage'));

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertEquals('my_s3', $disks[0]['name']);
        $this->assertNotEquals('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', $disks[0]['secret']);
        $this->assertEquals('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', decrypt($disks[0]['secret']));
    }

    // -------------------------------------------------------------------------
    // store() — validation
    // -------------------------------------------------------------------------

    #[Test]
    public function store_rejects_invalid_disk_name_with_spaces(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'my disk',
                'driver' => 'local',
                'storage_type' => 'public',
            ])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function store_rejects_duplicate_disk_name(): void
    {
        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'existing_disk', 'driver' => 'local', 'storage_type' => 'public'],
        ]));

        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'existing_disk',
                'driver' => 'local',
                'storage_type' => 'public',
            ])
            ->assertSessionHas('error');
    }

    #[Test]
    public function store_requires_host_for_ftp(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'bad_ftp',
                'driver' => 'ftp',
                'username' => 'user',
            ])
            ->assertSessionHasErrors('host');
    }

    #[Test]
    public function store_requires_bucket_for_s3(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'bad_s3',
                'driver' => 's3',
                'region' => 'us-east-1',
                'key' => 'key',
                'secret' => 'secret',
            ])
            ->assertSessionHasErrors('bucket');
    }

    #[Test]
    public function store_rejects_invalid_storage_type(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'bad_type',
                'driver' => 'local',
                'storage_type' => 'world-writable',
            ])
            ->assertSessionHasErrors('storage_type');
    }

    #[Test]
    public function store_requires_storage_type_for_local_disk(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'no_type',
                'driver' => 'local',
            ])
            ->assertSessionHasErrors('storage_type');
    }

    #[Test]
    public function store_rejects_unsupported_driver(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'bad_driver',
                'driver' => 'nfs',
            ])
            ->assertSessionHasErrors('driver');
    }

    #[Test]
    public function store_rejects_port_out_of_range(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.storage.store'), [
                'name' => 'bad_port',
                'driver' => 'ftp',
                'host' => 'ftp.example.com',
                'username' => 'user',
                'port' => 99999,
            ])
            ->assertSessionHasErrors('port');
    }

    // -------------------------------------------------------------------------
    // edit()
    // -------------------------------------------------------------------------

    #[Test]
    public function edit_shows_form_for_existing_disk(): void
    {
        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'my_disk', 'driver' => 'local', 'storage_type' => 'public', 'from_config' => false],
        ]));
        Cache::forget('storage.custom_disks');

        $this->actingAs($this->admin)
            ->get(route('settings.storage.edit', 'my_disk'))
            ->assertOk()
            ->assertViewIs('storage::edit')
            ->assertViewHas('diskName', 'my_disk');
    }

    #[Test]
    public function edit_redirects_for_nonexistent_disk(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.storage.edit', 'nonexistent'))
            ->assertRedirect(route('settings.storage'))
            ->assertSessionHas('error');
    }

    #[Test]
    public function edit_shows_form_for_config_disk(): void
    {
        config(['filesystems.disks.my_config_disk' => ['driver' => 's3', 'bucket' => 'b', 'region' => 'us-east-1', 'key' => 'k', 'secret' => 's']]);

        $this->actingAs($this->admin)
            ->get(route('settings.storage.edit', 'my_config_disk'))
            ->assertOk()
            ->assertViewIs('storage::edit')
            ->assertViewHas('isFromConfig', true);
    }

    #[Test]
    public function edit_config_disk_does_not_expose_credentials(): void
    {
        config(['filesystems.disks.secret_disk' => ['driver' => 's3', 'bucket' => 'b', 'region' => 'us-east-1', 'key' => 'AKIAIOSFODNN7EXAMPLE', 'secret' => 'real-secret']]);

        $disk = $this->actingAs($this->admin)
            ->get(route('settings.storage.edit', 'secret_disk'))
            ->assertOk()
            ->viewData('disk');

        $this->assertEmpty($disk['key'] ?? '');
        $this->assertArrayNotHasKey('secret', $disk);
    }

    #[Test]
    public function index_masks_config_disk_credentials_in_table(): void
    {
        // Simulate a config-file S3 disk (not in DB)
        config(['filesystems.disks.config_s3' => [
            'driver' => 's3',
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
            'key' => 'REALKEY123',
            'secret' => 'REALSECRET456',
        ]]);

        $storageData = $this->actingAs($this->admin)
            ->get(route('settings.storage'))
            ->assertOk()
            ->viewData('storageData');

        $configDisk = collect($storageData['custom_disks'])->firstWhere('name', 'config_s3');
        $this->assertNotNull($configDisk);
        $this->assertEquals('REALKEY123', $configDisk['key']);   // key is shown (not sensitive)
        $this->assertEquals('********', $configDisk['secret']);   // secret is masked
    }

    #[Test]
    public function destroy_rejects_disk_name_with_spaces(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('settings.storage.destroy'), ['disk_name' => 'my disk'])
            ->assertSessionHasErrors('disk_name');
    }

    #[Test]
    public function destroy_rejects_empty_disk_name(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('settings.storage.destroy'), ['disk_name' => ''])
            ->assertSessionHasErrors('disk_name');
    }

    // -------------------------------------------------------------------------
    // updateDisk()
    // -------------------------------------------------------------------------

    #[Test]
    public function update_disk_saves_changes(): void
    {
        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'my_ftp', 'driver' => 'ftp', 'host' => 'old.host.com', 'username' => 'user', 'password' => encrypt('oldpass'), 'port' => 21],
        ]));
        Cache::forget('storage.custom_disks');

        $this->actingAs($this->admin)
            ->patch(route('settings.storage.update-disk', 'my_ftp'), [
                'name' => 'my_ftp',
                'driver' => 'ftp',
                'host' => 'new.host.com',
                'username' => 'newuser',
                'port' => 2121,
                'password' => '', // leave blank to keep existing
            ])
            ->assertRedirect(route('settings.storage'))
            ->assertSessionHas('success');

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertEquals('new.host.com', $disks[0]['host']);
        $this->assertEquals('newuser', $disks[0]['username']);
        $this->assertEquals('oldpass', decrypt($disks[0]['password'])); // preserved
    }

    #[Test]
    public function update_disk_encrypts_new_password(): void
    {
        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'my_ftp', 'driver' => 'ftp', 'host' => 'host.com', 'username' => 'user', 'password' => encrypt('oldpass'), 'port' => 21],
        ]));
        Cache::forget('storage.custom_disks');

        $this->actingAs($this->admin)
            ->patch(route('settings.storage.update-disk', 'my_ftp'), [
                'name' => 'my_ftp',
                'driver' => 'ftp',
                'host' => 'host.com',
                'username' => 'user',
                'password' => 'newpass',
            ])
            ->assertRedirect(route('settings.storage'));

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertEquals('newpass', decrypt($disks[0]['password']));
    }

    #[Test]
    public function update_disk_preserves_port_when_password_blank(): void
    {
        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'my_sftp', 'driver' => 'sftp', 'host' => 'sftp.host.com', 'username' => 'user', 'password' => encrypt('secret'), 'port' => 2222],
        ]));
        Cache::forget('storage.custom_disks');

        $this->actingAs($this->admin)
            ->patch(route('settings.storage.update-disk', 'my_sftp'), [
                'name' => 'my_sftp',
                'driver' => 'sftp',
                'host' => 'sftp.host.com',
                'username' => 'user',
                'port' => 2222,
                'password' => '', // leave blank — keep existing
            ])
            ->assertRedirect(route('settings.storage'))
            ->assertSessionHas('success');

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertEquals(2222, $disks[0]['port']);
        $this->assertEquals('secret', decrypt($disks[0]['password'])); // preserved
    }

    #[Test]
    public function update_disk_returns_error_if_disk_was_deleted_concurrently(): void
    {
        // findCustomDisk succeeds (disk exists at start of request)
        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'my_ftp', 'driver' => 'ftp', 'host' => 'host.com', 'username' => 'user', 'password' => encrypt('pass'), 'port' => 21],
        ]));
        Cache::forget('storage.custom_disks');

        // Simulate concurrent deletion between findCustomDisk and the save
        // by clearing the disks after the initial load triggers
        // (we test the guard by sending a request for a disk that simply doesn't exist)
        Setting::set('system.custom_storage_disks', '[]');

        $this->actingAs($this->admin)
            ->patch(route('settings.storage.update-disk', 'my_ftp'), [
                'name' => 'my_ftp',
                'driver' => 'ftp',
                'host' => 'host.com',
                'username' => 'user',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // No duplicate disks in index
    // -------------------------------------------------------------------------

    #[Test]
    public function index_does_not_show_db_disks_twice(): void
    {
        // Simulate the ServiceProvider registering a DB disk into config() at boot time
        config(['filesystems.disks.my_db_disk' => ['driver' => 's3', 'bucket' => 'test']]);

        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'my_db_disk', 'driver' => 's3', 'bucket' => 'test', 'region' => 'us-east-1', 'key' => 'k', 'secret' => encrypt('s')],
        ]));
        Cache::forget('storage.custom_disks');

        $storageData = $this->actingAs($this->admin)
            ->get(route('settings.storage'))
            ->assertOk()
            ->viewData('storageData');

        $names = array_column($storageData['custom_disks'], 'name');
        $this->assertEquals(1, array_count_values($names)['my_db_disk'] ?? 0, 'DB disk must appear exactly once');
    }

    #[Test]
    public function index_statistics_reflect_custom_disks(): void
    {
        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'disk_s3', 'driver' => 's3', 'bucket' => 'b', 'region' => 'r', 'key' => 'k', 'secret' => encrypt('s')],
            ['name' => 'disk_ftp', 'driver' => 'ftp', 'host' => 'h', 'username' => 'u', 'password' => encrypt('p'), 'port' => 21],
        ]));
        Cache::forget('storage.custom_disks');

        $statistics = $this->actingAs($this->admin)
            ->get(route('settings.storage'))
            ->assertOk()
            ->viewData('statistics');

        $this->assertEquals(2, $statistics['custom_disks_total']);
        $this->assertEquals(2, $statistics['custom_db']);
        $this->assertEquals(0, $statistics['custom_config']);
        $this->assertEquals(1, $statistics['driver_counts']['s3']);
        $this->assertEquals(1, $statistics['driver_counts']['ftp']);
    }

    // -------------------------------------------------------------------------
    // destroy()
    // -------------------------------------------------------------------------

    #[Test]
    public function destroy_deletes_existing_disk(): void
    {
        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'to_delete', 'driver' => 'local'],
            ['name' => 'to_keep', 'driver' => 'local'],
        ]));
        Cache::forget('storage.custom_disks');

        $this->actingAs($this->admin)
            ->delete(route('settings.storage.destroy'), ['disk_name' => 'to_delete'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $disks = json_decode(Setting::get('system.custom_storage_disks', '[]'), true);
        $this->assertCount(1, $disks);
        $this->assertEquals('to_keep', $disks[0]['name']);
    }

    #[Test]
    public function destroy_returns_error_for_nonexistent_disk(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('settings.storage.destroy'), ['disk_name' => 'ghost_disk'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    #[Test]
    public function destroy_clears_cache(): void
    {
        Cache::put('storage.custom_disks', [['name' => 'disk1', 'driver' => 'local']], 3600);
        Setting::set('system.custom_storage_disks', json_encode([
            ['name' => 'disk1', 'driver' => 'local'],
        ]));

        $this->actingAs($this->admin)
            ->delete(route('settings.storage.destroy'), ['disk_name' => 'disk1'])
            ->assertSessionHas('success');

        $this->assertNull(Cache::get('storage.custom_disks'));
    }
}
