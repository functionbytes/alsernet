<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Analytics\Database\Seeders\AnalyticsPermissionSeeder;
use Modules\Attention\Database\Seeders\AttentionPermissionsSeeder;
use Modules\Auth\Database\Seeders\AuthPermissionsSeeder;
use Modules\Backup\Database\Seeders\BackupPermissionsSeeder;
use Modules\Blog\Database\Seeders\BlogPermissionsSeeder;
use Modules\Cookie\Database\Seeders\CookiePermissionsSeeder;
use Modules\Database\Database\Seeders\CreateDatabasePermissionsSeeder;
use Modules\Forms\Database\Seeders\FormsPermissionsSeeder;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Database\Seeders\TicketPermissionsSeeder;
use Modules\Mailer\Database\Seeders\MailerPermissionsSeeder;
use Modules\Mailrelay\Database\Seeders\MailrelayPermissionsSeeder;
use Modules\Media\Database\Seeders\MediaPermissionSeeder;
use Modules\Modules\Database\Seeders\ModulesPermissionsSeeder;
use Modules\Newsletter\Database\Seeders\NewsletterPermissionsSeeder;
use Modules\Notification\Database\Seeders\NotificationPermissionsSeeder;
use Modules\Page\Database\Seeders\CmsPermissionsSeeder;
use Modules\Page\Database\Seeders\PageDatabaseSeeder;
use Modules\PriceLabels\Database\Seeders\PriceLabelsPermissionsSeeder;
use Modules\Queue\Database\Seeders\QueuePermissionsSeeder;
use Modules\Reviews\Database\Seeders\ReviewsPermissionsSeeder;
use Modules\Role\Database\Seeders\CompleteRolesAndPermissionsSeeder;
use Modules\Seo\Database\Seeders\SeoPermissionsSeeder;
use Modules\Shortcode\Database\Seeders\ShortcodePermissionsSeeder;
use Modules\Sitemap\Database\Seeders\SitemapPermissionsSeeder;
use Modules\Storage\Database\Seeders\StoragePermissionsSeeder;
use Modules\Template\Database\Seeders\TemplateDatabaseSeeder;
use Modules\Template\Database\Seeders\TemplatePermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear roles y permisos primero
        $this->call(CompleteRolesAndPermissionsSeeder::class);

        // Permisos de módulos
        if (class_exists(AnalyticsPermissionSeeder::class)) {
            $this->call(AnalyticsPermissionSeeder::class);
        }

        if (class_exists(AttentionPermissionsSeeder::class)) {
            $this->call(AttentionPermissionsSeeder::class);
        }

        if (class_exists(AuthPermissionsSeeder::class)) {
            $this->call(AuthPermissionsSeeder::class);
        }

        if (class_exists(BackupPermissionsSeeder::class)) {
            $this->call(BackupPermissionsSeeder::class);
        }

        if (class_exists(BlogPermissionsSeeder::class)) {
            $this->call(BlogPermissionsSeeder::class);
        }

        if (class_exists(CookiePermissionsSeeder::class)) {
            $this->call(CookiePermissionsSeeder::class);
        }

        if (class_exists(CreateDatabasePermissionsSeeder::class)) {
            $this->call(CreateDatabasePermissionsSeeder::class);
        }

        if (class_exists(FormsPermissionsSeeder::class)) {
            $this->call(FormsPermissionsSeeder::class);
        }

        if (class_exists(PermissionsSeeder::class)) {
            $this->call(PermissionsSeeder::class);
        }

        if (class_exists(TicketPermissionsSeeder::class)) {
            $this->call(TicketPermissionsSeeder::class);
        }

        if (class_exists(MailerPermissionsSeeder::class)) {
            $this->call(MailerPermissionsSeeder::class);
        }

        if (class_exists(MailrelayPermissionsSeeder::class)) {
            $this->call(MailrelayPermissionsSeeder::class);
        }

        if (class_exists(MediaPermissionSeeder::class)) {
            $this->call(MediaPermissionSeeder::class);
        }

        if (class_exists(ModulesPermissionsSeeder::class)) {
            $this->call(ModulesPermissionsSeeder::class);
        }

        if (class_exists(NewsletterPermissionsSeeder::class)) {
            $this->call(NewsletterPermissionsSeeder::class);
        }

        if (class_exists(NotificationPermissionsSeeder::class)) {
            $this->call(NotificationPermissionsSeeder::class);
        }

        if (class_exists(CmsPermissionsSeeder::class)) {
            $this->call(CmsPermissionsSeeder::class);
        }

        if (class_exists(ReviewsPermissionsSeeder::class)) {
            $this->call(ReviewsPermissionsSeeder::class);
        }

        if (class_exists(SeoPermissionsSeeder::class)) {
            $this->call(SeoPermissionsSeeder::class);
        }

        if (class_exists(ShortcodePermissionsSeeder::class)) {
            $this->call(ShortcodePermissionsSeeder::class);
        }

        if (class_exists(SitemapPermissionsSeeder::class)) {
            $this->call(SitemapPermissionsSeeder::class);
        }

        if (class_exists(QueuePermissionsSeeder::class)) {
            $this->call(QueuePermissionsSeeder::class);
        }

        if (class_exists(PriceLabelsPermissionsSeeder::class)) {
            $this->call(PriceLabelsPermissionsSeeder::class);
        }

        if (class_exists(StoragePermissionsSeeder::class)) {
            $this->call(StoragePermissionsSeeder::class);
        }

        if (class_exists(TemplatePermissionsSeeder::class)) {
            $this->call(TemplatePermissionsSeeder::class);
        }

        // Crear templates
        if (class_exists(TemplateDatabaseSeeder::class)) {
            $this->call(TemplateDatabaseSeeder::class);
        }

        // Crear páginas de ejemplo
        if (class_exists(PageDatabaseSeeder::class)) {
            $this->call(PageDatabaseSeeder::class);
        }
    }
}
