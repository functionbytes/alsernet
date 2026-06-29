<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Models\Template\SystemEmailTemplate;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Models\Template\TemplateCategory;
use Modules\Campaign\Services\SystemEmailTemplateService;

/**
 * Siembra las plantillas de email Base/Extended desde
 * resources/themes/default/master/sample/email. Idempotente.
 * Espejo de SeedPageTemplatesCommand.
 */
class SeedEmailTemplatesCommand extends Command
{
    protected $signature = 'campaign:seed-email-templates {--fresh : Borra todas las email templates antes de sembrar}';

    protected $description = 'Siembra las plantillas de email (Base + Extended) para el builder';

    /** file (en master/sample/email) => [tier, name] */
    private array $templates = [
        // Base — bloques/layouts canónicos
        ['tier' => 'base', 'file' => 'Blank', 'name' => 'Blank'],
        ['tier' => 'base', 'file' => 'Minimal', 'name' => 'Minimal'],
        ['tier' => 'base', 'file' => 'SimpleText', 'name' => 'Simple Text'],
        ['tier' => 'base', 'file' => 'Gallery', 'name' => 'Gallery'],
        ['tier' => 'base', 'file' => 'SellProducts', 'name' => 'Sell Products'],
        ['tier' => 'base', 'file' => '1-2-1_Column', 'name' => '1:2:1 Column'],
        ['tier' => 'base', 'file' => '1-3_Column', 'name' => '1:3 Column'],
        ['tier' => 'base', 'file' => 'TellAStory', 'name' => 'Tell a story'],
        ['tier' => 'base', 'file' => 'FeaturedProduct', 'name' => 'Featured Product'],
        ['tier' => 'base', 'file' => 'PromoteProducts', 'name' => 'Promote products'],
        ['tier' => 'base', 'file' => 'EventInvitation', 'name' => 'Event invitation'],
        ['tier' => 'base', 'file' => 'RetailServices', 'name' => 'Retail services'],
        ['tier' => 'base', 'file' => 'MakeAnAnnouncement', 'name' => 'Make an announcement'],
        // Extended — diseños ricos pre-hechos
        ['tier' => 'extended', 'file' => 'SitewideSale', 'name' => 'Sitewide sale'],
        ['tier' => 'extended', 'file' => 'WelcomeCustomers', 'name' => 'Welcome customers'],
        ['tier' => 'extended', 'file' => 'AdvertiseApp', 'name' => 'Advertise app'],
        ['tier' => 'extended', 'file' => 'ClassThankYou', 'name' => 'Class thank you'],
        ['tier' => 'extended', 'file' => 'AboutOurServices', 'name' => 'About our services'],
        ['tier' => 'extended', 'file' => 'ThankYouPersonalized', 'name' => 'Thank you personalized'],
        ['tier' => 'extended', 'file' => 'Editorial_Newsletter', 'name' => 'Editorial Newsletter'],
        ['tier' => 'extended', 'file' => 'MemorialDay', 'name' => 'Memorial Day'],
        ['tier' => 'extended', 'file' => 'RemembranceDayRun', 'name' => 'Remembrance Day Run'],
        ['tier' => 'extended', 'file' => 'ThanksgivingOffer', 'name' => 'Thanksgiving Offer'],
        ['tier' => 'extended', 'file' => 'OpenHouseInvite', 'name' => 'Open House Invite'],
        ['tier' => 'extended', 'file' => 'Educational', 'name' => 'Educational Theme'],
        ['tier' => 'extended', 'file' => 'OrderAgain', 'name' => 'Order Again'],
        ['tier' => 'extended', 'file' => 'EventLineup', 'name' => 'Event Lineup'],
        ['tier' => 'extended', 'file' => 'RealEstateInvite', 'name' => 'Real estate invite'],
        ['tier' => 'extended', 'file' => 'EventAgendaDark', 'name' => 'Event Agenda Dark'],
        ['tier' => 'extended', 'file' => 'AboutUs', 'name' => 'About Us'],
        ['tier' => 'extended', 'file' => 'ProductPromotion', 'name' => 'Product Promotion'],
        ['tier' => 'extended', 'file' => 'FathersDaySpecials', 'name' => 'Fathers Day Specials'],
        ['tier' => 'extended', 'file' => 'InternationalWomensDay', 'name' => 'International Womens Day'],
        ['tier' => 'extended', 'file' => 'WorkspaceAIInvite', 'name' => 'Workspace AI Invite'],
    ];

    public function handle(SystemEmailTemplateService $service): int
    {
        TemplateCategory::firstOrCreate(['name' => 'Base']);
        TemplateCategory::firstOrCreate(['name' => 'Extended']);

        if ($this->option('fresh')) {
            foreach (SystemEmailTemplate::with('template')->get() as $t) {
                $t->template?->deleteAndCleanup();
                $t->delete();
            }
            $this->warn('Email templates existentes borradas (--fresh).');
        }

        $basePath = module_path('Campaign', 'resources/themes/default/master/sample/email');
        $seeded = 0;

        foreach ($this->templates as $meta) {
            $jsonFile = $basePath.'/'.$meta['file'].'.json';
            $htmlFile = $basePath.'/'.$meta['file'].'.html';

            if (! file_exists($jsonFile) || ! file_exists($htmlFile)) {
                $this->warn("Saltando {$meta['name']} — falta json/html.");

                continue;
            }

            foreach (SystemEmailTemplate::with('template')->where('name', $meta['name'])->get() as $old) {
                $old->template?->deleteAndCleanup();
                $old->delete();
            }

            $template = Template::createBuilderTemplate(
                'default',
                $meta['name'],
                (string) file_get_contents($jsonFile),
                (string) file_get_contents($htmlFile),
            );

            foreach (['svg', 'png', 'jpg'] as $ext) {
                $thumb = $basePath.'/'.$meta['file'].'.'.$ext;
                if (file_exists($thumb)) {
                    $template->updateThumbnailFromPath($thumb);
                    break;
                }
            }

            $categoryName = $meta['tier'] === 'base' ? 'Base' : 'Extended';
            $service->seedBaseTemplate($meta['name'], $template, $categoryName);
            $template->deleteAndCleanup();

            $seeded++;
            $this->line("  ✓ {$meta['name']} ({$categoryName})");
        }

        $this->info("Sembradas {$seeded} plantillas de email.");

        return self::SUCCESS;
    }
}
