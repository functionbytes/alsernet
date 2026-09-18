<?php

namespace Modules\Supplier\Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Services\Integrations\ErpProviderSyncService;
use Modules\Supplier\Services\SourceConfigurationService;

/**
 * Importa proveedores desde el Excel "Proveedores Gestión" (erp_id, nombre,
 * web) y les crea/actualiza una fuente Web con esa URL. Para los proveedores
 * que aún no existen localmente, los trae en vivo desde el ERP real por su
 * erp_id, usando el endpoint liviano GET /api/erp/suppliers/{id} (solo
 * label/cif/email/available) en vez de /detailed (~30s porque además arma
 * productos/categorías/deportes con 9 niveles de relaciones, que no hacen
 * falta aquí). Reusa ErpProviderSyncService::syncProvider() para persistir
 * con el mismo mapeo que usa el resto del sistema.
 *
 * Idempotente: reejecutar solo reintenta los erp_id que sigan sin existir y
 * solo actualiza la URL de la fuente 'Web proveedor' de cada proveedor.
 *
 * Ejecutar con: php artisan db:seed --class="Modules\Supplier\Database\Seeders\SupplierWebSourceImportSeeder"
 */
class SupplierWebSourceImportSeeder extends Seeder
{
    private const SOURCE_LABEL = 'Web proveedor';

    /**
     * [erp_id, nombre, web (puede ser null o traer varias URLs separadas por espacios)]
     */
    private const DATA = [
        [17, 'EDICIONES TUTOR, S.A.', 'www.edicionestutor.com'],
        [21, 'KIROL ESKLUSIVAK S.L.', 'https://www.kirolgolf.es/tienda/'],
        [24, 'CARSER SPORT (LA JOLLA)', 'https://carsersports.com/catalogos-2/'],
        [1019, 'INFAC, S.L.', 'https://www.infac-sl.com/'],
        [1022, 'PAREDES (PACAL SHOES, S.L.U. )', 'https://www.paredes.es/es/'],
        [1027, 'SADIRA', 'https://sadira.es/'],
        [1030, 'TARRAGO BRANDS INTERNATIONAL, S.L', 'https://www.tarrago.com/es/'],
        [1050, 'MARJOMAN, S.L.', 'https://marjoman.es/'],
        [1053, 'CRESSI-SUB ESPAÑA, S.A.', 'https://www.cressi.es/'],
        [1057, 'HEAD WATERSPORTS .S.P.A (MARES)', 'https://www.mares.com/es_ES/about-head-group'],
        [1058, 'TECNOMAR DIVING', 'https://www.tecnomar.es/'],
        [1059, 'BEUCHAT SUB ESPAÑA, S.A.', 'https://www.beuchat-sports.com/es/'],
        [1066, 'ARCEA EUROSPORT,S.L.', 'https://www.arcea.es/'],
        [1068, 'MANUFACTURE D\'APPEAUX (S.A.R.L. BAUD)', 'https://www.appeaux-helen-baud.com/'],
        [1103, 'ZALDI S.A.', 'https://zaldi.com/'],
        [1125, 'ROC IMPORT', 'https://roc-import.com/en/'],
        [1147, 'NELSON MARK FOREST, S.A', 'https://somlys.com/es/'],
        [1167, 'MUNDOIMPORT, S.L. (CALCETINES MUND)', 'www.mundsocks.com'],
        [1187, 'VIEWWAY OPTICS ENTERPRISES CO., LTD', 'https://www.viewwayoptics.com/'],
        [1196, 'ARTFISHING, S.L', 'www.yukicompeticion.com'],
        [1208, 'SEAC SUB S.p.A.', 'https://www.seacsub.com/es/'],
        [1225, 'MEINDL SHOES FOR ACTIVES', 'https://meindl.de/?lang=en'],
        [1226, 'HERMANOS PEDRAZA (LUIS PEDRAZA LOPEZ)', 'https://www.guarnicioneriapedraza.com/'],
        [1231, 'CURZON CLASSICS, S.L.', 'www.curzonclassics.com'],
        [1232, 'KLEVER GmbH', 'https://ballistol.de/en/home/'],
        [1236, 'VFG FILZ KANN MEHR', 'https://www.vfg.de/'],
        [1254, 'EVIA SUMINISTROS EVIA, S.L.', 'https://evia.es/es/'],
        [1257, 'SUMINISTROS SALPER,S.L.', 'https://salpersl.com/'],
        [3001, 'JAUSUN IMPORT EXPORT S.L.', 'https://www.jausun.com/'],
        [3004, 'BM SPORTECH S.A.', 'https://bmsportech.es/'],
        [3009, 'CARVING SPORT (NORDICA)', 'https://www.carving-sport.com/'],
        [7001, 'AGUIRRE Y CIA, S.A.', 'https://www.aguirreycia.es/'],
        [7002, 'ARDESA, S.A.', 'https://ardesa.com/'],
        [7003, 'BENISPORT (JOSE MONLLOR, S.L.)', 'https://benisport.es/'],
        [7006, 'CALICO, S.A.', 'https://casacalico.es/'],
        [7007, 'CANTOS DE PAJAROS DIGITALES S.L.', 'https://capadi.com/'],
        [7010, 'COMERCIAL MUELA S.A.', 'https://www.comercialmuela.com/'],
        [7013, 'CORSIVIA S.A.', 'https://corsivia.com/'],
        [7019, 'EISPORT S.L.', 'https://www.eisport.com/'],
        [7020, 'ESTELLER S.L.', 'https://esteller.com/'],
        [7033, 'LAKEN S.A.', 'https://www.laken.es/'],
        [7043, 'GAMO OUTDOOR S.L.', 'https://www.gamo.com/'],
        [7046, 'BORCHERS S.A.', 'https://www.borchers.es/'],
        [8007, 'BERETTA BENELLI IBERICA, S.A.', 'https://www.bbi.es/'],
        [9009, 'CALZADOS FAL S.A.', 'https://fal.es/'],
        [9010, 'CARMUSA SPORT, S.A.', 'https://www.cartuchosarmusa.com/'],
        [9020, 'GARMIN IBERIA', 'https://www.garmin.com/es-ES/'],
        [9039, 'HOLIDAY GOLF, S.L.', 'https://holidaygolf.com/es/'],
        [9041, 'NOBEL SPORT EXCOPESA, S.A.U', 'https://www.excopesa.es/'],
        [9050, 'MANUFACTURAS DEPORTIVAS VIPER, S.A.', 'https://www.viper-sport.com/'],
        [9054, 'NORMARK-RAPALA VMC SPAIN, S.A.U.', 'https://www.rapala.eu/eu_en/rapala'],
        [9066, 'TRUST EIBARRES, S.A.', 'https://www.cartuchostrust.com/es'],
        [9071, 'AMER SPORT SPAIN S.A. (WILSON-ATOMIC-SALOMON)', 'https://www.amersports.com/'],
        [9079, 'FRANKONIA JAGD', 'https://www.frankonia.de/'],
        [9116, 'STIL CRIN S.R.L.', 'https://www.stilcrin.it/en/'],
        [9240, 'SKYWAY TECHNOLOGY, S.A.', 'https://skwairsoft.com/es/'],
        [9244, 'HISPANO HIPICA, S.A.', 'https://www.hispanohipica.com/home/index.php'],
        [9288, 'INDUSTRIAS PLASTICAS CASTRO, S.A.', 'https://www.ipcastro.com/es/nautica'],
        [72744, 'IMPLEMENTOS,S.A.', 'https://www.implementos.com/'],
        [72769, 'INDUSTRIAS MERCURY S.A- REUSCH', 'www.industriasmercury.com  https://www.reusch.com/'],
        [72782, 'ESPORTIVA AKSA, S.L', 'https://www.esportivaaksa.com/'],
        [72793, 'SIDAS SPAIN S.L.', 'https://www.sidas.com/es'],
        [72810, 'ARMERIA MARCOS, S.C.', 'https://www.armeriamarcos.es/'],
        [72824, 'MARINE BUSINESS, S.A.', 'https://marinebusiness.net/'],
        [72846, 'GARRETT METAL DETECTORS', 'https://garrett.com/es/'],
        [72888, 'MARTINEZ ALBAINOX, S.L.U.', 'https://www.albainox.com/'],
        [72917, 'LA VIEJA ESPAÑA, S.L', 'https://laviejaespana.com/'],
        [72928, 'OLD TEDDY\'S COMPANY, S.L.', 'https://oldteddys.com/'],
        [72932, 'BOCARDE, S.L', 'https://bocarde.com/'],
        [72952, 'AVENTURALIA (PIELCU, S.L.)', 'https://www.grupopielcu.com/portal/portada/index.aspx'],
        [72998, 'ARMERIA A. IZQUIERDO S.L.', 'https://a-izquierdo.es/es/'],
        [73008, 'OUTFIT INTERNATIONAL A/S (ANTES SEELAND)', 'https://www.outfitinternational.com/'],
        [73009, 'GSM, LLC', 'https://www.gsmoutdoors.com/'],
        [73050, 'MUNDINAUTICA INTERNACIONAL FISHING TACKLE, S.L. (antes VEGA)', null],
        [73052, 'ALAN COMMUNICATIONS S.A.', 'https://es.midlandeurope.com/es_ES/'],
        [73066, 'LALIZAS ESPAÑA, S.L.', 'https://www.lalizas.es/'],
        [100000247, 'SPORT-JAGD S.L.', 'https://sport-jagd.com/'],
        [100000306, 'GAREMAR-92 SL', 'https://garemar.com/'],
        [100000327, 'PAUL & ESTHER,  S.L.', 'https://www.spagnolo.es/'],
        [100000366, 'REIBERCO INTERNACIONAL (LED LENSER)', 'https://www.reiberco.es/'],
        [100000406, 'MUNDIARMAS', 'https://mundiarmas.es/'],
        [100000411, 'ZASDAR S.L', 'https://www.zasdar.com/es/'],
        [100000428, 'HAGOPUR AG', 'https://www.hagopur.de/'],
        [100000431, 'DIKAR, S. COOP. (RIFLES BERGARA)', 'https://www.bergara.online/en/esp/'],
        [100000546, 'NORDIK PREDATOR AB', 'https://nordikpredator.se/en/'],
        [100000586, 'BOSMA LTD', 'https://www.bosma.com.cn/'],
        [100000706, 'YUKI 92, S.L', 'https://www.descente-international.com/es_ES/home/'],
        [100000847, 'ACUSHNET ESPAÑA, S.L.U.', 'https://www.titleist.com/  https://www.footjoy.com/  https://www.pinnaclegolf.com/  https://www.vokey.com/     https://www.scottycameron.com/  https://www.kjus.com/us/en/home/'],
        [100000926, 'TREESCO', 'https://www.percussion-europe.com/en/'],
        [100000988, 'ELECTRONICA OLAIZ, S.L.', 'https://electronicaolaiz.com/'],
        [100000990, 'ZERO INDUSTRY S.R.L', 'https://rhthelookofsport.com/en-eu'],
        [100001007, 'KINGSLAND DK APS', 'https://kingslandequestrian.com/'],
        [100001047, 'ARC TERYX EQUIPMENT INC', 'https://arcteryx.com/es/es'],
        [100001108, 'VETNOVA', 'https://es.vetnova.net/'],
        [100001112, 'THE ALLEN COMPANY, INC.', 'https://byallen.com/'],
        [100001114, 'GEORG FRITZMANN & SÖHNE GMBH', 'https://www.fritzmann.org/'],
        [100001124, 'HANGZHOU ZHONGKAI SPORTS TECHNOLOGY CO., LTD', null],
        [100001131, 'MONTALVO Y SANZ, S.L', 'https://spscajasfuertes.com/'],
        [100001193, 'VANGUARD ESPAÑA (GUARDFORCE ACC.,S.L.)', 'https://www.vanguardworld.es/'],
        [100001199, 'IBERICA DE ARMERIAS S.C.C.L.', 'http://www.ibericadearmerias.com/'],
        [100001201, 'DISMARINA MEDITERRANEA S.L.', 'https://www.dismarina.com/'],
        [100001228, 'COMERCIAL EL CALDEN, S.L.', 'https://elcalden.es/'],
        [100001229, 'ARMERIA TRELLES S.L.', 'https://www.armeriatrelles.com/tienda/es/'],
        [100001230, 'PRONAUTIC (MARINE EQUIPMENT)', 'https://www.pronautic.net/'],
        [100001231, 'MONTEGREEN S.L.', 'https://montegreen.com/'],
        [100001288, 'ENGANCHES Y REMOLQUES ARAGON, S.L', 'https://www.enganchesaragon.com/es'],
        [100001289, 'NATURE DE BRENNE S.A.R.L.', 'https://www.peluche-nature-de-brenne.com/'],
        [100001326, 'FENIX LINTERNAS Y FRONTALES', 'https://www.fenixlinternas.com/'],
        [100001427, 'CISAS (CILVC)', 'https://www.verney-carron.com/fr/  https://www.prohunt-europe.com/en/'],
        [100001430, 'SALMO (MIGUEL SANZ RODRIGUEZ)', 'https://www.salmo-fishing.com/'],
        [100001490, 'EIBAR AIRGUN TRADING COMPANY, S.L (NORICA)', 'https://noricaairguns.com/'],
        [100001688, 'KLARUS LIGHTING TECHNOLOGY CO., LIMITED', 'https://www.klaruslight.com/'],
        [100001706, 'PARABELLUM, S.L.U.', 'https://para-bellum.es/'],
        [100001707, 'CONFECCIONES GARRIDO Y MUÑOZ, S.L', 'https://www.confeccionesgarrido.com/'],
        [100001711, 'DICOTEX INNOVA, S.L.', 'http://www.xn--hattrickespaa-tkb.com/'],
        [100001714, 'CARABINAS COMETA, S.A', 'https://www.cometaairgun.com/'],
        [100001759, 'COBRA-PUMA GOLF (PUMA FRANCE S.A.S.)', 'https://cobrapumagolf.com/'],
        [100001778, 'BAITSFISHING (EDUARDO GONZALEZ JIMENEZ)', 'https://www.baitsfishing.com/'],
        [100001817, 'DAIWA FRANCE SAS', 'https://daiwa-es.com/'],
        [100001818, 'VAVAAL 749 S.L (POWAKADDY)', 'https://powakaddy.es/'],
        [100001838, 'BR.HANDELSON DERNEMING BIEMAN DE HAAS', 'https://b2b.cavalli.group/en'],
        [100001839, 'BCN OUTDOOR, S.L.', 'https://www.bcnoutdoor.com/'],
        [100001842, 'OUTDOORSTOCKS, S.L', 'https://outdoorstocks.com/'],
        [100001846, 'PIHERNZ COMUNICACIONES S.A', 'https://pihernz.com/'],
        [100001886, 'TUBERTINI IBERICA S.L.', 'https://tubertini.es/es/'],
        [100001903, 'VALENTO TEXTILE, S.L. (RG PUBLICIDAD Y REGALOS DE EMPRESA, S.L)', 'https://www.valento.es/'],
        [100001939, 'ADIDAS ESPAÑA S.A.U.', 'https://www.adidas.es/golf'],
        [100002037, 'TOURON, S.A.', 'https://www.touron.es/'],
        [100002057, 'KEP ITALIA s.r.l.', 'https://www.kepitalia.com/es/'],
        [100002118, 'JULIAN BELTRAN ESTEVEZ (BRUNI GOLF)', 'https://www.golfstream.es/'],
        [100002159, 'EKKIA S.A.S', 'https://www.ekkia.com/'],
        [100002161, 'VEREDUS SRL', 'https://www.veredus.com/'],
        [100002162, 'PRESTIGE ITALIA S.P.A', 'https://www.prestigeitalia.com/'],
        [100002218, 'HIT AIR IBERICA', 'https://www.hitairiberica.com/'],
        [100002238, 'KOMPERDELL', 'https://www.komperdell.com/en/'],
        [100002421, 'JOLUVI, S.A.', 'https://www.joluvi.com/'],
        [100002499, 'FLEX ON', 'https://www.flex-on.fr/?lang=en'],
        [100002538, 'IZAS OUTDOOR, S.L', 'https://www.izas-outdoor.com/'],
        [100002579, 'RIDERS TREND', 'https://www.riders-trend.com/'],
        [100002618, 'SHIMANO IBERIA, S.L.', 'https://fish.shimano.com/es-ES'],
        [100002678, 'NEOPRENE SOLUTIONS S.L.', 'https://seland.com/'],
        [100002699, 'EQUILINE S.R.L.', 'https://www.equiline.it/eu/'],
        [100002738, 'EXCENS SPORTS, S.L.', 'https://excensports.com/'],
        [100002758, 'MAKERS VISION TECHNOLOGY , SL.', 'https://www.makers-vision.com/'],
        [100002778, 'JOHNSON-OUTDOORS', 'https://scubapro.johnsonoutdoors.com/eu/es-es'],
        [100002798, 'UNUS GOLF DISTRIBUCIONES,S.', 'https://www.unusgolf.es'],
        [100002838, 'SKECHERS IBERIA S.L.U.', 'https://www.skechers.es/'],
        [100002858, 'PING EUROPE LIMITED', 'https://eu.ping.com/es-es'],
        [100002860, 'SRIXON SPORTS EUROPE LTD', 'https://eu.dunlopsports.com/'],
        [100002878, 'LINEAEFFE s.p.a.', 'https://www.lineaeffe.it/en/'],
        [100003018, 'REGATTA LTD', 'https://www.regatta.com/row/'],
        [100003038, 'MASTERS 247 EUROPE', 'https://www.masters247.eu/'],
        [100003039, 'SWEET & TRENDY (CRISTINA MARSELLÉS IGLESIAS)', 'https://sweetandtrendy.es/'],
        [100003118, 'COTTAGE TEXTIL,S.A.', 'https://www.jorigu.es/'],
        [100003141, 'ELYMAN IBERICA S.L.U.', 'https://www.elymaniberica.com/'],
        [100003180, 'RODATEX & BRITISH COTTON S.L.', 'https://britishcotton.com/'],
        [100003181, 'GOLUG SPORTS S.L.', 'https://uskidsgolfesp.com/'],
        [100003222, 'O.MUSTAD & SON PORTUGAL -EQUIP.DE PESCA Lda.', 'https://mustad-fishing.com/eu'],
        [100003224, 'TAYLORMADE GOLF Ltd', 'https://www.taylormadegolf.eu/'],
        [100003240, 'VIRGINIA CARIDAD ARRIBAS MORILLA- HOYO 7', 'https://hoyo7.com/'],
        [100003300, 'ASPARLA INTOVA S.L.', 'https://aquas.es/'],
        [100003340, 'PRO ELITE BAITS INTERNATIONAL .SL.', 'https://proelitebaits.com/'],
        [100003440, 'ADVANCE FISHING SL', 'http://ad-fishing.com/es/'],
        [100003481, 'FREEJUMPSYSTEM', 'https://www.freejumpsystem.com/en/'],
        [100003501, 'VICE SPORTING GOODS GmbH', 'https://www.vicegolf.eu/'],
        [100003540, 'AGUIRRE Y CIA, S.A. (+8000 y BULLPADEL)', 'Bull Padel    lsantos@aguirreycia.es'],
        [100003560, 'LEMIEUX LTD', 'https://www.lemieux.com/'],
        [100003580, 'WESPORTED SLU', 'https://wesported.com/'],
        [100003600, 'ADS HUNTERWORLD S.L.', 'https://www.hunterworld.es/tienda/  https://hwoutdoor.es/'],
        [100003601, 'CALLAWAY GOLF EU BV', 'https://eu.callawaygolf.com/en/'],
        [100003620, 'AMARA GOLF, S.L.', 'https://www.amaragolf.es/'],
        [100003640, 'PASION MORENA .SL.', 'https://pasionmorena.com/'],
        [100003681, 'NSG APPAREL INTERNATIONAL ESPAÑA, S.L.', 'https://www.northsails.com/es-es'],
        [100003682, 'KASK SPA', 'https://www.kask.com/es-es/home'],
        [100003700, 'BOWLAND ARCHERY SL', 'https://www.bowlandhunting.es/'],
        [100003722, 'FOSHAN QINGZHOU ARTIFICIAL GRASS CO., Ltd  (PGM GOLF)', 'https://www.pgmgolf.com/'],
        [100003725, 'LEICA CAMERA IBERIA', 'https://leica-camera.com/es-ES'],
        [100003820, 'ALAN PAINE KNITWEAR LTD', 'https://www.es.alanpaine.co.uk/'],
        [100003840, 'CAIRN SPORT S.A.S.', 'https://cairn-sport.com/'],
        [100003841, 'SHENZHEN SPERAS LIGHTING CO., LTD', 'www.speraslight.com/'],
        [100003900, 'NORGOLFE LDA', 'https://www.norgolfe.com/es/'],
        [100003920, 'POC -DSV SOLUTIONS HOLDING B.V.', 'https://poc.com/en/categories/snow'],
        [100003940, 'BLASER OUTDOOR GROUP, S.L.', 'https://www.blaser-group.com/en/'],
        [100003980, 'HELLY HANSEN SPORTSWEAR SPAIN S.L.U.', 'https://www.hellyhansen.com/es_es'],
        [100004020, 'MICEBO - CASAS NOYA, MIGUEL ENRIQUE', 'https://www.micebo.es/'],
        [100004100, 'LONG XIANG (VISIOTECH)', 'https://www.visiotechsecurity.com/es/'],
        [100004160, 'SLAM COM S.P.A.', 'https://www.slam.com/es'],
        [100004180, 'LUXOTTICA SPAIN S.L.U', 'https://www.oakley.com/es-es'],
        [100004261, 'EQUIT\'ANA', 'https://www.samshield.com/en/'],
        [100004302, 'SC DISTRIBUTION EUROPE GMBH', 'www.secondchance.co.uk'],
        [100004306, 'JOREAR SO. COOP', null],
        [100004307, 'OLYMPUS SRL', null],
        [100004365, 'PURE FISHING NETHERLANDS BV', 'https://www.purefishing.com/'],
        [100004385, 'COLUMBIA SPORTWEAR SPAIN S.L.U.', 'https://www.columbiasportswear.es/'],
        [100004405, 'LEATHERMAN EUROPE GmbH', 'https://www.gmtoutdoor.fr/'],
    ];

    public function run(ErpProviderSyncService $erpSync, SourceConfigurationService $sourceConfigService): void
    {
        $internalBaseUrl = config('supplier.erp_internal_url', 'http://nginx').'/api/erp';

        $stats = [
            'existing' => 0,
            'synced_from_erp' => 0,
            'erp_failed' => [],
            'sources_created' => 0,
            'sources_updated' => 0,
            'without_url' => 0,
        ];

        foreach (self::DATA as [$erpId, $label, $rawWeb]) {
            $supplier = Supplier::where('erp_id', $erpId)->first();

            if ($supplier) {
                $stats['existing']++;
            } else {
                try {
                    // El endpoint va detrás de throttle:60,1 — 1.1s entre llamadas
                    // se queda por debajo del límite y evita ráfagas de 429.
                    usleep(1_100_000);

                    $response = Http::timeout(15)->get("{$internalBaseUrl}/suppliers/{$erpId}");

                    if (! $response->successful()) {
                        throw new Exception("ERP API error {$response->status()}");
                    }

                    $body = $response->json();

                    if (! ($body['success'] ?? false) || empty($body['data'])) {
                        throw new Exception('Proveedor no encontrado en el ERP');
                    }

                    $result = $erpSync->syncProvider($body['data']);
                    $supplier = $result['supplier'];
                    $stats['synced_from_erp']++;
                } catch (Exception $e) {
                    $stats['erp_failed'][] = "{$erpId} ({$label}): {$e->getMessage()}";
                    $this->command?->warn("ERP {$erpId} ({$label}): {$e->getMessage()}");

                    continue;
                }
            }

            $urls = $this->extractUrls($rawWeb);

            if ($urls === []) {
                $stats['without_url']++;

                continue;
            }

            $wasRecentlyCreated = false;
            $source = $supplier->sources()->where('label', self::SOURCE_LABEL)->first();

            if (! $source) {
                $source = $supplier->sources()->create([
                    'source_type' => Source::SOURCE_TYPE_WEBSITE,
                    'trust_level' => Source::TRUST_LEVEL_MEDIUM,
                    'priority' => 10,
                    'is_active' => true,
                    'extraction_mode' => 'ai',
                    'label' => self::SOURCE_LABEL,
                ]);
                $wasRecentlyCreated = true;
            }

            $sourceConfigService->setConfiguration($source, 'connection', [
                'urls' => array_map(fn (string $url) => ['url' => $url], $urls),
                'timeout' => 30,
                'rate_limit' => 10,
                'user_agent' => '',
            ]);

            $wasRecentlyCreated ? $stats['sources_created']++ : $stats['sources_updated']++;
        }

        $this->command?->info('Import de proveedores + fuentes Web completado:');
        $this->command?->table(
            ['Métrica', 'Valor'],
            [
                ['Proveedores ya existentes', $stats['existing']],
                ['Proveedores sincronizados desde ERP', $stats['synced_from_erp']],
                ['Proveedores fallidos en ERP', count($stats['erp_failed'])],
                ['Fuentes Web creadas', $stats['sources_created']],
                ['Fuentes Web actualizadas', $stats['sources_updated']],
                ['Filas sin URL', $stats['without_url']],
            ]
        );

        if ($stats['erp_failed'] !== []) {
            $this->command?->warn('Proveedores que fallaron al sincronizar desde ERP (reintentar re-ejecutando el seeder):');
            foreach ($stats['erp_failed'] as $line) {
                $this->command?->line(" - {$line}");
            }
        }
    }

    /**
     * Extrae una o más URLs válidas de una celda del Excel. Filtra tokens
     * que no son URLs (emails, texto libre) y antepone https:// cuando
     * falta el esquema (p.ej. "www.dominio.com").
     *
     * @return list<string>
     */
    private function extractUrls(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $urls = [];

        foreach (preg_split('/\s+/', trim($raw)) as $token) {
            if ($token === '' || str_contains($token, '@')) {
                continue;
            }

            if (preg_match('#^https?://#i', $token)) {
                $urls[] = $token;
            } elseif (preg_match('#^(www\.)?[a-z0-9-]+(\.[a-z0-9-]+)+(/\S*)?$#i', $token)) {
                $urls[] = 'https://'.$token;
            }
        }

        return array_values(array_unique($urls));
    }
}
