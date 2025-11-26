<?php

class PricesDropSportControllerCore extends FrontController
{
    public $php_self = 'pricesdropsport';

    public $sports;
    public $id_sport;
    public $sport;

    // ISO -> keyword SEO
    protected $keywords = [
        'es' => 'ofertas',
        'en' => 'deals',
        'fr' => 'offres',
        'pt' => 'promocoes',
        'de' => 'angebote',
        'it' => 'offerte',
    ];

    public function init()
    {
        $iso = $this->context->language->iso_code;
        $id_lang = (int)Language::getIdByIso($iso);

        // 1) Cargar deportes
        $this->sports = $this->formatAllSports($this->getAllSports($id_lang));

        // 2) Leer ambos slugs
        $slugA = Tools::getValue('sport');
        $slugB = Tools::getValue('offer_keyword');

        // 3) Resolver deporte por cualquiera de los dos
        $this->sport = $this->getDeporteBySlug($slugA) ?: $this->getDeporteBySlug($slugB);
        $this->id_sport = $this->getIdDeporteBySlug($slugA) ?: $this->getIdDeporteBySlug($slugB);

        if (!$this->id_sport && !empty($this->sport['id_category'])) {
            $this->id_sport = (int)$this->sport['id_category'];
        }

        // Solo hacer redirección si tenemos un deporte válido
        if (!$this->id_sport) {
            parent::init();
            return;
        }

        // Verificar si ya estamos en una URL limpia (sin index.php ni parámetros)
        $hasIndexPhp = strpos($_SERVER['REQUEST_URI'], 'index.php') !== false;
        $hasQuery = !empty($_SERVER['QUERY_STRING']);

        // Solo intentar redirección si estamos en una URL "sucia"
        if ($hasIndexPhp || $hasQuery) {
            list($lid, $liso, $langDir) = $this->getLangFromPath();
            $canonical = $this->buildCanonicalUrl($lid, $liso, $langDir);

            if ($canonical) {
                $expectedPath = $this->normPath(parse_url($canonical, PHP_URL_PATH));
                $currentPathNorm = $this->normPath(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

                if ($currentPathNorm !== $expectedPath) {
                    $canonicalQuery = parse_url($canonical, PHP_URL_QUERY);
                    if (empty($canonicalQuery)) {
                        while (ob_get_level() > 0) {
                            @ob_end_clean();
                        }
                        header('Location: ' . $canonical, true, 301);
                        exit;
                    }
                }
            }
        }

        // Marcar que ya manejamos la canonicalización
        $this->canonical_handled = true;

        parent::init();
    }

    public function canonicalRedirection($canonical_url = '')
    {
        // Si ya manejamos la canonicalización en init(), no hacer nada más
        if (isset($this->canonical_handled) && $this->canonical_handled) {
            return;
        }

        // Si estamos en una URL limpia de ofertas deportivas, no redirigir
        $uri = $_SERVER['REQUEST_URI'];
        $hasIndexPhp = strpos($uri, 'index.php') !== false;
        $hasQuery = !empty($_SERVER['QUERY_STRING']);

        if (!$hasIndexPhp && !$hasQuery && $this->id_sport) {
            return;
        }

        // En otros casos, usar la redirección normal
        parent::canonicalRedirection($canonical_url);
    }

    /* =========================
       HELPERS URL / SLUG / LANG
       ========================= */

    protected function joinUrl($base /*, ...$segments */)
    {
        $segments = func_get_args();
        $base = array_shift($segments);

        $clean = [];
        foreach ($segments as $seg) {
            $seg = trim((string)$seg, '/');
            if ($seg !== '') $clean[] = $seg;
        }
        return rtrim($base, '/') . '/' . implode('/', $clean);
    }

    protected function normalizeSlug($raw, $fallbackName = '')
    {
        $raw = trim((string)$raw);
        if ($raw === '') return Tools::link_rewrite((string)$fallbackName);

        // Si viene con rutas, tomar el último segmento
        $raw = preg_replace('#/+#', '/', $raw);
        if (strpos($raw, '/') !== false) {
            $parts = explode('/', trim($raw, '/'));
            $raw = end($parts);
        }

        // Sustituir espacios/underscores por guion antes del rewrite
        $raw = str_replace([' ', '_'], '-', $raw);
        $slug = Tools::link_rewrite($raw);

        // Colapsar guiones y limpiar bordes
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') $slug = Tools::link_rewrite((string)$fallbackName);
        return $slug;
    }

    protected function getLangFromPath()
    {
        $shopId = (int)$this->context->shop->id;
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = $path !== '' ? explode('/', $path) : [];
        $first = !empty($segments) ? $segments[0] : '';

        // Verificar si el primer segmento es un código de idioma
        foreach (Language::getLanguages(true, $shopId) as $lang) {
            if (Tools::strtolower($lang['iso_code']) === Tools::strtolower($first)) {
                $id_lang = (int)$lang['id_lang'];
                $iso = $lang['iso_code'];
                $langDir = trim($this->context->link->getLangLink($id_lang, null, $shopId), '/');
                return [$id_lang, $iso, $langDir];
            }
        }

        // Fallback al idioma del contexto
        $id_lang = (int)$this->context->language->id;
        $iso = $this->context->language->iso_code;

        // Verificar si es el idioma por defecto
        $defaultLangId = (int)Configuration::get('PS_LANG_DEFAULT');

        if ($id_lang == $defaultLangId) {
            $langLink = $this->context->link->getLangLink($id_lang, null, $shopId);
            $langDir = trim($langLink, '/');
            if ($langDir === '' || $langDir === '/') {
                $langDir = '';
            }
        } else {
            $langDir = trim($this->context->link->getLangLink($id_lang, null, $shopId), '/');
        }

        return [$id_lang, $iso, $langDir];
    }

    protected function buildCanonicalUrl($forceIdLang = null, $forceIso = null, $forceLangDir = null)
    {
        if (!$this->id_sport) {
            return '';
        }

        $shopId = (int)$this->context->shop->id;

        if ($forceIdLang === null || $forceIso === null || $forceLangDir === null) {
            list($id_lang, $iso, $langDir) = $this->getLangFromPath();
        } else {
            $id_lang = (int)$forceIdLang;
            $iso = $forceIso;
            $langDir = $forceLangDir;
        }

        $cat = new Category((int)$this->id_sport, $id_lang);
        if (!Validate::isLoadedObject($cat)) {
            return '';
        }

        $rawSlug = $cat->category_url_path ?: Tools::link_rewrite($cat->name);
        $slug = $this->normalizeSlug($rawSlug, $cat->name);
        $keyword = $this->keywords[$iso] ?? 'ofertas';

        $base = rtrim($this->context->link->getBaseLink($shopId, null, false), '/');

        if (empty($langDir) || $langDir === '/') {
            $canonicalUrl = $this->joinUrl($base, $slug, $keyword);
        } else {
            $canonicalUrl = $this->joinUrl($base, $langDir, $slug, $keyword);
        }

        return $canonicalUrl;
    }

    protected function normPath($p)
    {
        $p = (string)$p;
        $p = preg_replace('#/+#', '/', $p);
        return rtrim($p, '/');
    }

    /* =========================
       BÚSQUEDAS / RESOLUCIÓN
       ========================= */

    protected function findCategoryIdBySlugAnyLang($slug)
    {
        if (!$slug) {
            return 0;
        }

        $idShop = (int)$this->context->shop->id;
        $slugSql = pSQL($slug);

        $sql = "
            SELECT c.id_category
            FROM `" . _DB_PREFIX_ . "category_lang` cl
            INNER JOIN `" . _DB_PREFIX_ . "category` c ON c.id_category = cl.id_category AND c.active = 1
            INNER JOIN `" . _DB_PREFIX_ . "category_shop` cs ON cs.id_category = c.id_category AND cs.id_shop = {$idShop}
            WHERE cl.category_url_path = '{$slugSql}' OR cl.link_rewrite = '{$slugSql}'
        ";

        return (int)Db::getInstance()->getValue($sql);
    }

    protected function getIdDeporteBySlug($slug)
    {
        if (!$slug) {
            return 0;
        }

        // 1) intenta en la lista precargada del idioma actual
        foreach ($this->sports as $sport) {
            $rewritten = Tools::link_rewrite($sport['category_name']);
            if ($rewritten === $slug) {
                return (int)$sport['id_category'];
            }
        }

        // 2) fallback: buscar por SQL en cualquier idioma
        return $this->findCategoryIdBySlugAnyLang($slug);
    }

    protected function getDeporteBySlug($slug)
    {
        if (!$slug) {
            return null;
        }

        // 1) intenta en la lista precargada
        foreach ($this->sports as $sport) {
            $rewritten = Tools::link_rewrite($sport['category_name']);
            if ($rewritten === $slug) {
                return $sport;
            }
        }

        // 2) fallback: resolver id y construir estructura mínima para continuar
        $id = $this->findCategoryIdBySlugAnyLang($slug);
        if ($id) {
            $langId = (int)$this->context->language->id;
            $cat = new Category((int)$id, $langId);
            if (Validate::isLoadedObject($cat)) {
                return [
                    'deporte' => Tools::strtolower($cat->name),
                    'id_category' => (int)$id,
                    'category_name' => Tools::strtolower($cat->name),
                ];
            }
        }

        return null;
    }

    /* =========================
       LANGUAGE SWITCH / LINKS
       ========================= */

    protected function getLanguageSwitchUrls($id_sport)
    {
        $urls = [];
        $shopId = (int)$this->context->shop->id;
        $base = rtrim($this->context->link->getBaseLink($shopId, null, false), '/');
        $defaultLangId = (int)Configuration::get('PS_LANG_DEFAULT');

        foreach (Language::getLanguages(true, $shopId) as $lang) {
            $id_lang = (int)$lang['id_lang'];
            $iso = $lang['iso_code'];

            $keyword = $this->keywords[$iso] ?? 'ofertas';
            $category = new Category((int)$id_sport, $id_lang);
            if (!Validate::isLoadedObject($category)) {
                continue;
            }

            $rawSlug = $category->category_url_path ?: Tools::link_rewrite($category->name);
            $slug = $this->normalizeSlug($rawSlug, $category->name);

            if ($id_lang == $defaultLangId) {
                $langDir = '';
            } else {
                $langDir = $iso;
            }

            if (empty($langDir)) {
                $urls[$id_lang] = $base . '/' . $slug . '/' . $keyword;
            } else {
                $urls[$id_lang] = $base . '/' . $langDir . '/' . $slug . '/' . $keyword;
            }
        }

        return $urls;
    }

    public function getOfertasDeporteLink($id_deporte, $iso = 'es')
    {
        $shopId = (int)$this->context->shop->id;
        $id_lang = (int)Language::getIdByIso($iso);
        if (!$id_lang) {
            return '#';
        }

        $category = new Category((int)$id_deporte, $id_lang);
        if (!Validate::isLoadedObject($category)) {
            return '#';
        }

        $slug = $this->normalizeSlug($category->category_url_path ?: Tools::link_rewrite($category->name), $category->name);
        $keyword = $this->keywords[$iso] ?? 'ofertas';

        $base = rtrim($this->context->link->getBaseLink($shopId, null, false), '/');

        $defaultLangId = (int)Configuration::get('PS_LANG_DEFAULT');
        if ($id_lang == $defaultLangId) {
            $langDir = '';
        } else {
            $langDir = $iso;
        }

        if (empty($langDir)) {
            return $base . '/' . $slug . '/' . $keyword;
        } else {
            return $base . '/' . $langDir . '/' . $slug . '/' . $keyword;
        }
    }

    /* =========================
       RENDER
       ========================= */

    public function initContent()
    {
        parent::initContent();

        if (!$this->id_sport) {
            Tools::redirect($this->context->link->getPageLink('index'));
            return;
        }

        $products = $this->getRelevantProducts($this->id_sport);
        $categories = $this->getCategoryData($this->id_sport);

        // URLs para el selector de idioma
        $this->context->smarty->assign([
            'languages' => Language::getLanguages(true, (int)$this->context->shop->id),
            'language_switch_urls' => $this->getLanguageSwitchUrls((int)$this->id_sport),
        ]);

        $this->context->smarty->assign([
            'sport' => $this->sport,
            'id_sport' => $this->id_sport,
            'products' => $products,
            'categories' => $categories,
        ]);

        $this->setTemplate('catalog/prices-drop-sport');
    }

    /* =========================
       DATA
       ========================= */

    protected function getAllSports($idLang = null)
    {
        $idShop = (int)$this->context->shop->id;
        $idLang = $idLang ?: (int)$this->context->language->id;

        $sql = "
            SELECT
                LOWER(dor.`deporte`) AS deporte,
                d.`id_category`,
                LOWER(cl.`name`) AS category_name
            FROM `" . _DB_PREFIX_ . "deportes` d
            INNER JOIN `" . _DB_PREFIX_ . "deporte_origen` dor ON dor.`id` = d.`id_deporte_origen`
            INNER JOIN `" . _DB_PREFIX_ . "category` c ON c.`id_category` = d.`id_category` AND c.`active` = 1
            INNER JOIN `" . _DB_PREFIX_ . "category_shop` cs ON cs.`id_category` = c.`id_category` AND cs.`id_shop` = {$idShop}
            INNER JOIN `" . _DB_PREFIX_ . "category_lang` cl ON cl.`id_category` = c.`id_category` AND cl.`id_shop` = cs.`id_shop` AND cl.`id_lang` = {$idLang}
            ORDER BY cs.`position` ASC
        ";

        return Db::getInstance()->executeS($sql);
    }

    protected function formatAllSports(array $data)
    {
        $sports = [];

        $shopId = (int)$this->context->shop->id;
        $langId = (int)$this->context->language->id;
        $iso = $this->context->language->iso_code;
        $keyword = $this->keywords[$iso] ?? 'ofertas';

        $base = rtrim($this->context->link->getBaseLink($shopId, null, false), '/');

        $defaultLangId = (int)Configuration::get('PS_LANG_DEFAULT');
        if ($langId == $defaultLangId) {
            $langDir = '';
        } else {
            $langDir = $iso;
        }

        foreach ($data as $sport) {
            $idCategory = (int)$sport['id_category'];

            $rawSlug = (string)Db::getInstance()->getValue(
                'SELECT COALESCE(NULLIF(category_url_path, \'\'), link_rewrite) ' .
                'FROM ' . _DB_PREFIX_ . 'category_lang ' .
                'WHERE id_category=' . (int)$idCategory . ' AND id_lang=' . (int)$langId
            );

            $slug = $this->normalizeSlug($rawSlug, $sport['category_name']);

            $sports[$idCategory] = $sport;

            if (empty($langDir)) {
                $sports[$idCategory]['url'] = $base . '/' . $slug . '/' . $keyword;
            } else {
                $sports[$idCategory]['url'] = $base . '/' . $langDir . '/' . $slug . '/' . $keyword;
            }

            if (empty($langDir)) {
                $sports[$idCategory]['category_name'] = $slug;
                $sports[$idCategory]['category_url'] = $base . '/' . $slug;
            } else {
                $sports[$idCategory]['category_url'] = $base . '/' . $langDir . '/' . $slug;
                $sports[$idCategory]['category_name'] = $slug;
            }

            $sports[$idCategory]['title'] = $this->trans('Prices drop', [], 'Shop.Theme.Catalog') . ' ' . Tools::strtolower($sport['category_name']);
            $sports[$idCategory]['current'] = ($idCategory === $this->id_sport);


            if ($sports[$idCategory]['current']) {
                $this->sport = $sports[$idCategory];
                $this->sport["category_name"] = $sport['category_name'];
                $this->sport["category_url"] = $base . '/' . $langDir . $slug;
            }
        }

        return $sports;
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        if ($this->sport) {

            $breadcrumb['links'][] = [
                'title' => ucfirst($this->sport['category_name']),
                'url' => $this->sport['category_url'],
            ];

            $breadcrumb['links'][] = [
                'title' => $this->sport['title'],
                'url' => '',
            ];
        }

        return $breadcrumb;
    }

    public function getCategoryData($id_deporte)
    {
        switch ($id_deporte) {
            case 3:
                $data = [
                    "deporte" => "golf",
                    "title" => [
                        "es" => "Oferta especial golf",
                        "pt" => "Oferta especial golfe",
                        "fr" => "Offre spéciale golf",
                        "de" => "Spezialangebot Golf",
                        "it" => "Offerta speciale golf",
                        "en" => "Special Golf Offer",
                    ],
                    "h1" => [
                        "es" => "OFERTAS GOLF",
                        "pt" => "PROMOÇÕES GOLFE",
                        "fr" => "OFFRES GOLF",
                        "de" => "ANGEBOTE GOLF",
                        "it" => "OFFERTE GOLF",
                        "en" => "GOLF OFFERS",
                    ],
                    "h3" => [
                        "es" => "OFERTAS GOLF",
                        "pt" => "PROMOÇÕES GOLFE",
                        "fr" => "OFFRES GOLF",
                        "de" => "ANGEBOTE GOLF",
                        "it" => "OFFERTE GOLF",
                        "en" => "GOLF OFFERS",
                    ],
                    "texts" => [
                        "es" => "<p><strong><h3>Ofertas en material de golf: ¡Tu parada obligatoria para equiparte al mejor precio!</h3></strong> Bienvenido a la sección de <strong>Ofertas de golf</strong> de Álvarez, el lugar que todo aficionado al golf debe visitar. Ahorro y Calidad se dan la mano para que puedas equipararte con lo mejor, sin sacrificar tu presupuesto. Nuestro objetivo: ofrecerte las <strong>mejores ofertas y descuentos exclusivos en palos de golf, bolsas, ropa, calzado, bolas y accesorios</strong> de las marcas más prestigiosas del sector.</p><p><strong><h3>Ofertas en palos de golf y equipamiento de las mejores marcas:</h3></strong> Actualizamos constantemente nuestra selección para que siempre encuentres las últimas promociones en las principales marcas como <strong>TaylorMade, Callaway, Titleist, Cobra, Ping</strong> y muchas más. Tanto si buscas un nuevo driver para ganar distancia, un juego de hierros para mejorar tu precisión o una bolsa ultraligera para el recorrido, te garantizamos que nuestras ofertas te sorprenderán.</p><p><strong><h3>Renueva tu equipo con la máxima calidad y al mejor precio:</h3></strong> Sabemos que el golf es una pasión que requiere el mejor equipo. Por eso, en esta página no solo encontrarás precios inmejorables, sino también la tranquilidad de estar comprando productos de alta calidad y de las colecciones más recientes. Nuestro equipo de expertos revisa cada oferta para asegurar que cumple con los estándares de excelencia que nos caracterizan.</p><p><strong><h3>Un lugar de consulta obligatoria para el golfista inteligente:</h3></strong> No dejes pasar la oportunidad de conseguir ese producto que tanto deseas a un precio excepcional. Nuestra sección de <strong>ofertas de golf</strong> es un recurso indispensable que te recomendamos guardar en favoritos y consultar con regularidad. ¡Cada día es una nueva oportunidad para encontrar la oferta perfecta que elevará tu juego al siguiente nivel!</p><p><strong>Aprovecha ahora nuestras ofertas y equípate como un profesional; ¡Solo en Álvarez!</strong></p>",
                        "pt" => "<p><strong><h3>Ofertas em material de golfe: a sua pa ragem obrigatória para se equipar ao melhor preço!</h3></strong> Bem-vindo à secção de <strong>Ofertas de golfe</strong> da Álvarez, o local que todos os aficionados de golfe devem visitar. Poupança e qualidade andam de mãos dadas para que possa equipar-se com o melhor, sem sacrificar o seu orçamento. O nosso objetivo: oferecer-lhe <strong>as melhores ofertas e descontos exclusivos em tacos de golfe, sacos, roupa, calçado, bolas e acessórios</strong> das marcas mais prestigiadas do setor.</p><p><strong><h3>Ofertas em tacos de golfe e equipamento das melhores marcas:</h3></strong> Atualizamos constantemente a nossa seleção para que encontre sempre as últimas promoções nas principais marcas, como <strong>TaylorMade, Callaway, Titleist, Cobra, Ping</strong> e muitas mais. Quer esteja à procura de um novo driver para ganhar distância, um conjunto de ferros para melhorar a sua precisão ou um saco ultraleve para o percurso, garantimos que as nossas ofertas o vão surpreender.</p><p><strong><h3>Renove o seu equipamento com a máxima qualidade e ao melhor preço:</h3></strong> Sabemos que o golfe é uma paixão que requer o melhor equipamento. Por isso, nesta página não só encontrará preços imbatíveis, mas também a tranquilidade de estar a comprar produtos de alta qualidade e das coleções mais recentes. A nossa equipa de especialistas revê cada oferta para garantir que cumpre os padrões de excelência que nos caracterizam.</p><p><strong><h3>Um local de consulta obrigatória para o golfista inteligente:</h3></strong>                                     Não perca a oportunidade de conseguir aquele produto que tanto deseja a um preço excecional. A nossa secção de <strong>ofertas de golfe</strong> é um recurso indispensável que recomendamos que guarde nos favoritos e consulte regularmente. Cada dia é uma nova oportunidade para encontrar a oferta perfeita que elevará o seu jogo ao próximo nível!</p><p><strong>Aproveite agora as nossas ofertas e equipe-se como um profissional; só na Álvarez!</strong></p>",
                        "fr" => "<p><strong><h3>Offres sur le matériel de golf : votre arrêt obligatoire pour vous équiper au meilleur prix !</h3></strong> Bienvenue dans la section Offres golf d'Álvarez, le site incontournable pour tous les amateurs de golf. Économies et qualité vont de pair pour que vous puissiez vous équiper avec le meilleur, sans sacrifier votre budget. Notre objectif : vous proposer <strong>les meilleures offres et des remises exclusives sur les clubs de golf, sacs, vêtements, chaussures, balles et accessoires</strong> des marques les plus prestigieuses du secteur.</p><p><strong><h3>Offres sur les clubs de golf et les équipements des meilleures marques :</h3></strong> Nous actualisons constamment notre sélection afin que vous trouviez toujours les dernières promotions sur les principales marques telles que <strong>TaylorMade, Callaway, Titleist, Cobra, Ping</strong> et bien d'autres. Que vous recherchiez un nouveau driver pour gagner en distance, un jeu de fers pour améliorer votre précision ou un sac ultrléger pour le parcours, nous vous garantissons que nos offres vous surprendront.</p><p><strong><h3>Renouvelez votre équipement avec la meilleure qualité et au meilleur prix :</h3></strong> Nous savons que le golf est une passion qui nécessite le meilleur équipement. C'est pourquoi, sur cette page, vous trouverez non seulement des prix imbattables, mais aussi la tranquillité d'esprit d'acheter des produits de haute qualité issus des dernières collections. Notre équipe d'experts examine chaque offre pour s'assurer qu'elle répond aux normes d'excellence qui nous caractérisent.</p><p><strong><h3>Un site incontournable pour le golfeur avisé :</h3></strong> Ne manquez pas l'occasion d'obtenir le produit que vous désirez tant à un prix exceptionnel. Notre section des <strong>offres de golf</strong> est une ressource indispensable que nous vous recommandons d'ajouter à vos favoris et de consulter régulièrement. Chaque jour est une nouvelle occasion de trouver l'offre parfaite qui vous permettra d'élever votre jeu au niveau supérieur !</p><p><strong>Profitez dès maintenant de nos offres et équipez-vous comme un professionnel ; uniquement chez Álvarez ¡</strong></p>",
                        "de" => "<p><strong><h3>Angebote für Golfausrüstung: Ihr Muss, um sich zum besten Preis auszustatten!</h3></strong> Willkommen im Bereich „Golfangebote” von Álvarez, dem Ort, den jeder Golfbegeisterte besuchen sollte. Sparsamkeit und Qualität gehen Hand in Hand, damit Sie sich mit dem Besten ausstatten können, ohne Ihr Budget zu strapazieren. Unser Ziel: Ihnen die <strong>besten Angebote und exklusiven Rabatte auf Golfschläger, Taschen, Kleidung, Schuhe, Bälle und Zubehör</strong> der renommiertesten Marken der Branche zu bieten.</p><p><strong><h3>Angebote für Golfschläger und Ausrüstung der besten Marken:</h3></strong> Wir aktualisieren ständig unser Sortiment, damit Sie immer die neuesten Angebote der wichtigsten Marken wie <strong>TaylorMade, Callaway, Titleist, Cobra, Ping</strong> und vielen anderen finden. Egal, ob Sie einen neuen Driver suchen, um mehr Weite zu erzielen, einen Satz Eisen, um Ihre Präzision zu verbessern, oder eine ultraleichte Tasche für den Platz, wir garantieren Ihnen, dass unsere Angebote Sie überraschen werden.</p><p><strong><h3>Erneuern Sie Ihre Ausrüstung mit höchster Qualität und zum besten Preis:</h3></strong> Wir wissen, dass Golf eine Leidenschaft ist, die die beste Ausrüstung erfordert. Deshalb finden Sie auf dieser Seite nicht nur unschlagbare Preise, sondern auch die Gewissheit, dass Sie hochwertige Produkte aus den neuesten Kollektionen kaufen. Unser Expertenteam überprüft jedes Angebot, um sicherzustellen, dass es den hohen Standards entspricht, die uns auszeichnen.</p><p><strong><h3>Ein Muss für den intelligenten Golfer:</h3></strong> Verpassen Sie nicht die Gelegenheit, das Produkt, das Sie sich so sehr wünschen, zu einem außergewöhnlichen Preis zu erwerben. Unser Bereich mit <strong>Golfangeboten</strong> ist eine unverzichtbare Ressource, die Sie zu Ihren Favoriten hinzufügen und regelmäßig besuchen sollten. Jeder Tag ist eine neue Gelegenheit, das perfekte Angebot zu finden, das Ihr Spiel auf die nächste Stufe hebt!</p><p><strong>Nutzen Sie jetzt unsere Angebote und rüsten Sie sich wie ein Profi aus; nur bei Álvarez!</strong></p>",
                        "it" => "<p><strong><h3>Offerte su articoli da golf: la tua tappa obbligatoria per attrezzarti al miglior prezzo!</h3></strong> Benvenuto nella sezione Offerte golf di Álvarez, il luogo che ogni appassionato di golf dovrebbe visitare. Risparmio e qualità vanno di pari passo per permetterti di equipaggiarti con il meglio, senza sacrificare il tuo budget. Il nostro obiettivo: offrirti <strong>le migliori offerte e sconti esclusivi su mazze da golf, sacche, abbigliamento, calzature, palline e accessori</strong> delle marche più prestigiose del settore.</p><p><strong><h3>Offerte su mazze da golf e attrezzature delle migliori marche:</h3></strong> Aggiorniamo costantemente la nostra selezione affinché tu possa sempre trovare le ultime promozioni delle principali marche come <strong>TaylorMade, Callaway, Titleist, Cobra, Ping</strong> e molte altre. Che tu stia cercando un nuovo driver per guadagnare distanza, un set di ferri per migliorare la tua precisione o una sacca ultraleggera per il percorso, ti garantiamo che le nostre offerte ti sorprenderanno.</p><p><strong><h3>Rinnova la tua attrezzatura con la massima qualità e al miglior prezzo:</h3></strong> Sappiamo che il golf è una passione che richiede la migliore attrezzatura. Per questo motivo, in questa pagina non troverai solo prezzi imbattibili, ma anche la tranquillità di acquistare prodotti di alta qualità e delle collezioni più recenti. Il nostro team di esperti controlla ogni offerta per garantire che soddisfi gli standard di eccellenza che ci contraddistinguono.</p><p><strong><h3>Un punto di riferimento obbligatorio per il golfista intelligente:</h3></strong> Non perdere l'occasione di acquistare quel prodotto che desideri tanto a un prezzo eccezionale. La nostra sezione dedicata alle <strong>offerte di golf</strong> è una risorsa indispensabile che ti consigliamo di aggiungere ai preferiti e di consultare regolarmente. Ogni giorno è una nuova opportunità per trovare l'offerta perfetta che porterà il tuo gioco al livello successivo!</p><p><strong>Approfitta subito delle nostre offerte e attrezzati come un professionista; solo da Álvarez!</strong></p>",
                        "en" => "<p><strong><h3>Golf equipment offers: Your must-visit destination to get kitted out at the best price!</h3></strong> Welcome to the Golf Deals section at Álvarez, the place every golf enthusiast should visit. Savings and quality go hand in hand so you can get the best gear without breaking the bank. Our goal: to offer you the <strong>best deals and exclusive discounts on golf clubs, bags, clothing, footwear, balls and accessories</strong> from the most prestigious brands in the industry.</p><p><strong><h3>Deals on golf clubs and equipment from the best brands:</h3></strong> We constantly update our selection so that you can always find the latest promotions on major brands such as <strong>TaylorMade, Callaway, Titleist, Cobra, Ping</strong> and many more. Whether you're looking for a new driver to gain distance, a set of irons to improve your accuracy or an ultrlightweight bag for the course, we guarantee that our deals will surprise you.</p><p><strong><h3>Renew your equipment with the highest quality and at the best price:</h3></strong> We know that golf is a passion that requires the best equipment. That's why on this page you'll not only find unbeatable prices, but also the peace of mind that comes with buying high-quality products from the latest collections. Our team of experts reviews each offer to ensure that it meets the standards of excellence that characterise us.</p><p><strong><h3>A must-visit for the smart golfer:</h3></strong> Don't miss out on the opportunity to get that product you want so much at an exceptional price. Our <strong>golf offers</strong> section is an indispensable resource that we recommend you bookmark and check regularly. Every day is a new opportunity to find the perfect offer that will take your game to the next level!</p><p><strong>Take advantage of our offers now and equip yourself like a pro; only at Álvarez!</strong></p>"
                    ],
                    "imagenes" => [
                        "es" => [
                            ["title" => "Ofertas palos de golf", "image" => "palos.webp", "url" => "/golf/palos_de_golf"],
                            ["title" => "Ofertas bolas de golf", "image" => "bolas.webp", "url" => "/golf/bolas_de_golf_y_accesorios"],
                            ["title" => "Ofertas bolsas de golf", "image" => "bolsas.webp", "url" => "/golf/bolsas_de_golf"],
                            ["title" => "Ofertas carros de golf", "image" => "carros.webp", "url" => "/golf/carros_de_golf"],
                            ["title" => "Ofertas ropa de golf", "image" => "ropa.webp", "url" => "/golf/ropa"],
                            ["title" => "Ofertas calzado de golf", "image" => "calzado.webp", "url" => "/golf/calzado_de_golf"],
                            ["title" => "0", "image" => "relleno-es.webp", "url" => ""],
                            ["title" => "Ofertas guantes de golf", "image" => "guante.webp", "url" => "/golf/guantes_de_golf"],
                            ["title" => "1", "image" => "relleno-es.webp", "url" => ""],
                        ],
                        "pt" => [
                            ["title" => "Ofertas tacos de golfe", "image" => "palos.webp", "url" => "/pt/golfe/tacos_de_golfe"],
                            ["title" => "Ofertas bolas de golfe", "image" => "bolas.webp", "url" => "/pt/golfe/bolas_de_golfe_e_acessorios"],
                            ["title" => "Ofertas sacos de golfe", "image" => "bolsas.webp", "url" => "/pt/golfe/sacos_de_golfe"],
                            ["title" => "Ofertas carros de golfe", "image" => "carros.webp", "url" => "/pt/golfe/carros_de_golfe"],
                            ["title" => "Ofertas roupa de golfe", "image" => "ropa.webp", "url" => "/pt/golfe/roupa"],
                            ["title" => "Ofertas calçado de golfe", "image" => "calzado.webp", "url" => "/pt/golfe/calcado_de_golfe"],
                            ["title" => "0", "image" => "relleno-pt.webp", "url" => ""],
                            ["title" => "Ofertas luvas de golfe", "image" => "guante.webp", "url" => "/pt/golfe/luvas_de_golfe"],
                            ["title" => "1", "image" => "relleno-pt.webp", "url" => ""],
                        ],
                        "fr" => [
                            ["title" => "Promotions clubs de golf", "image" => "palos.webp", "url" => "/fr/golf/clubs_de_golf"],
                            ["title" => "Promotions balles de golf", "image" => "bolas.webp", "url" => "/fr/golf/balles_de_golf_et_accessoires"],
                            ["title" => "Promotions sacs de golf", "image" => "bolsas.webp", "url" => "/fr/golf/sacs_de_golf"],
                            ["title" => "Promotions chariots golf", "image" => "carros.webp", "url" => "/fr/golf/chariots_de_golf"],
                            ["title" => "Promotions vêtements", "image" => "ropa.webp", "url" => "/fr/golf/vetements"],
                            ["title" => "Promotions chaussures", "image" => "calzado.webp", "url" => "/fr/golf/chaussures_de_golf"],
                            ["title" => "0", "image" => "relleno-fr.webp", "url" => ""],
                            ["title" => "Promotions gants de golf", "image" => "guante.webp", "url" => "/fr/golf/gants_de_golf"],
                            ["title" => "1", "image" => "relleno-fr.webp", "url" => ""],
                        ],
                        "de" => [
                            ["title" => "Angebote Golfschläger", "image" => "palos.webp", "url" => "/de/golf/golfschlaeger"],
                            ["title" => "Angebote Golfbälle", "image" => "bolas.webp", "url" => "/de/golf/golfballe_und_zubehor"],
                            ["title" => "Angebote Golftaschen", "image" => "bolsas.webp", "url" => "/de/golf/golfbags"],
                            ["title" => "Angebote Golfwagen", "image" => "carros.webp", "url" => "/de/golf/golftrolleys"],
                            ["title" => "Angebote Golfbekleidung", "image" => "ropa.webp", "url" => "/de/golf/kleidung"],
                            ["title" => "Angebote Golfschuhe", "image" => "calzado.webp", "url" => "/de/golf/golfschuhe"],
                            ["title" => "0", "image" => "relleno-de.webp", "url" => ""],
                            ["title" => "Angebote Golfhandschuhe", "image" => "guante.webp", "url" => "/de/golf/handschuhe"],
                            ["title" => "1", "image" => "relleno-de.webp", "url" => ""],
                        ],
                        "it" => [
                            ["title" => "Offerte mazze da golf", "image" => "palos.webp", "url" => "/it/golf/bastoni_da_golf"],
                            ["title" => "Offerte palline da golf", "image" => "bolas.webp", "url" => "/it/golf/palline_da_golf_accessori"],
                            ["title" => "Offerte sacche da golf", "image" => "bolsas.webp", "url" => "/it/golf/sacche_da_golf"],
                            ["title" => "Offerte carrelli da golf", "image" => "carros.webp", "url" => "/it/golf/carrelli_da_golf"],
                            ["title" => "Offerte abbigliamento", "image" => "ropa.webp", "url" => "/it/golf/abbigliamento_da_golf"],
                            ["title" => "Offerte calzature", "image" => "calzado.webp", "url" => "/it/golf/scarpe_da_golf"],
                            ["title" => "0", "image" => "relleno-it.webp", "url" => ""],
                            ["title" => "Offerte guanti da golf", "image" => "guante.webp", "url" => "/it/golf/guanti_da_golf"],
                            ["title" => "1", "image" => "relleno-it.webp", "url" => ""],
                        ],
                        "en" => [
                            ["title" => "Golf Club Offers", "image" => "palos.webp", "url" => "/en/golf/golfschlaeger"],
                            ["title" => "Golf Ball Offers", "image" => "bolas.webp", "url" => "/en/golf/balles_de_golf_et_accessoires"],
                            ["title" => "Golf Bag Offers", "image" => "bolsas.webp", "url" => "/en/golf/sacs_de_golf"],
                            ["title" => "Golf Trolley Offers", "image" => "carros.webp", "url" => "/en/golf/chariots_de_golf"],
                            ["title" => "Golf Clothing Offers", "image" => "ropa.webp", "url" => "/en/golf/vetements"],
                            ["title" => "Golf Shoes Offers", "image" => "calzado.webp", "url" => "/en/golf/chaussures_de_golf"],
                            ["title" => "0", "image" => "relleno-en.webp", "url" => ""],
                            ["title" => "Golf Gloves Offers", "image" => "guante.webp", "url" => "/en/golf/gants_de_golf"],
                            ["title" => "1", "image" => "relleno-en.webp", "url" => ""],
                        ],
                    ]
                ];
                break;

            case 4:
                $data = [
                    "deporte" => "caza",
                    "title" => [
                        "es" => "Oferta especial caza",
                        "pt" => "Oferta especial caça",
                        "fr" => "Offre spéciale chasse",
                        "de" => "Sonderangebot Jagd",
                        "it" => "Offerta speciale caccia",
                        "en" => "Special Hunting Offer",
                    ],
                    "h1" => [
                        "es" => "OFERTAS CAZA",
                        "pt" => "PROMOÇÕES CAÇA",
                        "fr" => "OFFRES DE CHASSE",
                        "de" => "ANGEBOTE JAGD",
                        "it" => "OFFERTE CACCIA",
                        "en" => "HUNTING OFFERS",
                    ],
                    "h3" => [
                        "es" => "OFERTAS CAZA",
                        "pt" => "PROMOÇÕES CAÇA",
                        "fr" => "OFFRES DE CHASSE",
                        "de" => "ANGEBOTE JAGD",
                        "it" => "OFFERTE CACCIA",
                        "en" => "HUNTING OFFERS",
                    ],
                    "texts" => [
                        "es" => "<p><strong><h3>Ofertas de caza: ¡Equípate con lo mejor al mejor precio!</h3></strong> Bienvenido a la sección de <strong>ofertas de caza</strong> de Álvarez, el rincón donde los cazadores expertos encuentran oportunidades únicas para renovar su equipo. Sabemos que la pasión por la caza requiere de los mejores productos, y nuestra misión es hacer que sean accesibles para ti. Por eso, hemos creado una selección exclusiva de las <strong>mejores ofertas y descuentos</strong> en rifles, visores, munición, cartuchos, ropa técnica, calzado y todo tipo de accesorios.</p><p><strong><h3>Marcas líderes y productos exclusivos a precios inmejorables</h3></strong> Actualizamos constantemente nuestra página para ofrecerte las promociones más frescas de las marcas más prestigiosas del sector. Aquí encontrarás descuentos en <strong>Swarovski, Zeiss, Blaser, Beretta, Browning, Chiruca</strong> y muchas otras. Además, nuestra sección de ofertas a menudo incluye productos exclusivos que no se encuentran en otras tiendas, lo que te da una ventaja única para conseguir ese equipo que has estado buscando a un precio excepcional.</p><p><strong><h3>Tu lugar de consulta obligatoria para la caza</h3></strong> No dejes que se te escape la oportunidad de conseguir el equipo que eleve tu experiencia de caza al siguiente nivel. Esta sección de <strong>ofertas de caza</strong> es un recurso indispensable que todo cazador debe tener en sus favoritos. Te invitamos a consultarla con regularidad, ya que las ofertas cambian y se actualizan con frecuencia.</p><p><strong>Aprovecha ahora nuestras ofertas limitadas y equípate con la máxima calidad; ¡Solo en Álvarez!</strong></p>",
                        "pt" => "<p><strong><h3>Ofertas de caça: Equipe-se com o melhor ao melhor preço!</h3></strong> Bem-vindo à secção de <strong>ofertas de caça</strong> da Álvarez, o local onde os caçadores experientes encontram oportunidades únicas para renovar o seu equipamento. Sabemos que a paixão pela caça requer os melhores produtos, e a nossa missão é torná-los acessíveis para si. Por isso, criámos uma seleção exclusiva das <strong>melhores ofertas e descontos</strong> em espingardas, miras, munições, cartuchos, roupa técnica, calçado e todo o tipo de acessórios.</p><p><strong><h3>Marcas líderes e produtos exclusivos a preços imbatíveis</h3></strong> Atualizamos constantemente a nossa página para lhe oferecer as promoções mais recentes das marcas mais prestigiadas do setor. Aqui encontrará descontos em <strong>Swarovski, Zeiss, Blaser, Beretta, Browning, Chiruca</strong> e muitas outras marcas. Além disso, a nossa secção de ofertas inclui frequentemente produtos exclusivos que não se encontram noutras lojas, o que lhe dá uma vantagem única para conseguir aquele equipamento que procurava a um preço excecional.</p><p><strong><h3>O seu local de consulta obrigatório para a caça</h3></strong> Não perca a oportunidade de adquirir o equipamento que elevará a sua experiência de caça ao próximo nível. Esta secção de <strong>ofertas de caça</strong> é um recurso indispensável que todo caçador deve ter nos seus favoritos. Convidamo-lo a consultá-la regularmente, pois as ofertas mudam e são atualizadas com frequência.</p><p><strong>Aproveite agora as nossas ofertas limitadas e equipe-se com a máxima qualidade. Só na Álvarez!</strong></p>",
                        "fr" => "<p><strong><h3>Offres chasse : équipez-vous du meilleur matériel au meilleur prix !</h3></strong> Bienvenue dans la section offres chasse d'Álvarez, le coin où les chasseurs experts trouvent des occasions uniques pour renouveler leur équipement. Nous savons que la passion pour la chasse exige les meilleurs produits, et notre mission est de vous les rendre accessibles. C'est pourquoi nous avons créé une sélection exclusive <strong>des meilleures offres et réductions</strong> sur les fusils, les lunettes de visée, les munitions, les cartouches, les vêtements techniques, les chaussures et toutes sortes d'accessoires.</p><p><strong><h3>Des marques leaders et des produits exclusifs à des prix imbattables</h3></strong> Nous mettons constamment à jour notre page pour vous proposer les dernières promotions des marques les plus prestigieuses du secteur. Vous trouverez ici des réductions sur <strong>Swarovski, Zeiss, Blaser, Beretta, Browning, Chiruca</strong> et bien d'autres. De plus, notre section « Offres » comprend souvent des produits exclusifs que vous ne trouverez pas dans d'autres magasins, ce qui vous donne un avantage unique pour obtenir l'équipement que vous recherchez à un prix exceptionnel.</p><p><strong><h3>Votre référence incontournable pour la chasse</h3></strong> Ne manquez pas l'occasion d'acquérir l'équipement qui vous permettra d'améliorer votre expérience de chasse. Cette section d'offres de chasse est une ressource indispensable que tout chasseur devrait avoir dans ses favoris. Nous vous invitons à la consulter régulièrement, car les offres changent et sont fréquemment mises à jour.</p><p><strong>Profitez dès maintenant de nos offres limitées et équipez-vous avec la meilleure qualité. Uniquement chez Álvarez !</strong></p>",
                        "de" => "<p><strong><h3>Jagdangebote: Rüsten Sie sich mit dem Besten zum besten Preis aus!</h3></strong> Willkommen im Bereich Jagdangebote von Álvarez, dem Ort, an dem erfahrene Jäger einzigartige Gelegenheiten finden, ihre Ausrüstung zu erneuern. Wir wissen, dass die Leidenschaft für die Jagd die besten Produkte erfordert, und unsere Mission ist es, diese für Sie zugänglich zu machen. Aus diesem Grund haben wir eine exklusive Auswahl <strong>der besten Angebote und Rabatte</strong> für Gewehre, Zielfernrohre, Munition, Patronen, Funktionsbekleidung, Schuhe und alle Arten von Zubehör zusammengestellt.</p><p><strong><h3>Führende Marken und exklusive Produkte zu unschlagbaren Preisen</h3></strong> Wir aktualisieren unsere Seite ständig, um Ihnen die neuesten Angebote der renommiertesten Marken der Branche zu präsentieren. Hier finden Sie Rabatte auf <strong>Swarovski, Zeiss, Blaser, Beretta, Browning, Chiruca</strong> und viele andere. Darüber hinaus umfasst unser Angebotsbereich oft exklusive Produkte, die Sie in anderen Geschäften nicht finden, was Ihnen einen einzigartigen Vorteil verschafft, um die Ausrüstung, nach der Sie gesucht haben, zu einem außergewöhnlichen Preis zu erhalten.</p><p><strong><h3>Ihre unverzichtbare Anlaufstelle für die Jagd</h3></strong> Verpassen Sie nicht die Gelegenheit, die Ausrüstung zu erwerben, die Ihr Jagderlebnis auf die nächste Stufe hebt. Dieser Bereich mit <strong>Jagdangeboten</strong> ist eine unverzichtbare Ressource, die jeder Jäger in seinen Favoriten haben sollte. Wir laden Sie ein, regelmäßig vorbeizuschauen, da die Angebote häufig wechseln und aktualisiert werden.</p><p><strong>Nutzen Sie jetzt unsere limitierten Angebote und statten Sie sich mit höchster Qualität aus. Nur bei Álvarez!</strong></p>",
                        "it" => "<p><strong><h3>Offerte per la caccia: equipaggiati con il meglio al miglior prezzo!</h3></strong> Benvenuto nella sezione delle <strong>offerte per la caccia</strong> di Álvarez, il luogo dove i cacciatori esperti trovano opportunità uniche per rinnovare la loro attrezzatura. Sappiamo che la passione per la caccia richiede i migliori prodotti e la nostra missione è renderli accessibili a te. Per questo motivo, abbiamo creato una <strong>selezione esclusiva delle migliori offerte e sconti</strong> su fucili, mirini, munizioni, cartucce, abbigliamento tecnico, calzature e accessori di ogni tipo.</p><p><strong><h3>Marchi leader e prodotti esclusivi a prezzi imbattibili</h3></strong> Aggiorniamo costantemente la nostra pagina per offrirti le promozioni più recenti dei marchi più prestigiosi del settore. Qui troverai sconti su <strong>Swarovski, Zeiss, Blaser, Beretta, Browning, Chiruca</strong> e molti altri. Inoltre, la nostra sezione delle offerte include spesso prodotti esclusivi che non si trovano in altri negozi, offrendoti un vantaggio unico per acquistare l'attrezzatura che stavi cercando a un prezzo eccezionale.</p><p><strong><h3>Il tuo punto di riferimento obbligatorio per la caccia</h3></strong> Non perdere l'occasione di acquistare l'attrezzatura che porterà la tua esperienza di caccia a un livello superiore. Questa sezione dedicata alle <strong>offerte per la caccia</strong> è una risorsa indispensabile che ogni cacciatore dovrebbe avere tra i propri preferiti. Ti invitiamo a consultarla regolarmente, poiché le offerte cambiano e vengono aggiornate frequentemente.</p><p><strong>Approfitta subito delle nostre offerte limitate e equipaggiati con la massima qualità. Solo da Álvarez!</strong></p>",
                        "en" => "<p><strong><h3>Hunting offers: Get the best gear at the best price!</h3></strong> Welcome to the <strong>hunting offers section</strong> at Álvarez, the place where expert hunters find unique opportunities to renew their equipment. We know that a passion for hunting requires the best products, and our mission is to make them accessible to you. That's why we've created an exclusive selection of <strong>the best deals and discounts</strong> on rifles, scopes, ammunition, cartridges, technical clothing, footwear and all kinds of accessories.</p><p><strong><h3>Leading brands and exclusive products at unbeatable prices</h3></strong> We constantly update our page to offer you the latest promotions from the most prestigious brands in the sector. Here you will find discounts on <strong>Swarovski, Zeiss, Blaser, Beretta, Browning, Chiruca</strong> and many others. In addition, our offers section often includes exclusive products not found in other stores, giving you a unique advantage in getting the equipment you have been looking for at an exceptional price.</p><p><strong><h3>Your go-to place for hunting</h3></strong> Don't miss out on the opportunity to get the equipment that will take your hunting experience to the next level. This <strong>hunting offers section</strong> is an indispensable resource that every hunter should have in their favourites. We invite you to check it regularly, as offers change and are updated frequently.</p><p><strong>Take advantage of our limited offers now and equip yourself with the highest quality. Only at Alvarez!</strong></p>"
                    ],
                    "imagenes" => [
                        "es" => [
                            ["title" => "Ofertas ESCOPETAS", "image" => "escopetas.webp", "url" => "/caza/escopetas"],
                            ["title" => "Ofertas RIFLES", "image" => "rifles.webp", "url" => "/caza/rifles"],
                            ["title" => "Ofertas armas balines", "image" => "balines.webp", "url" => "/caza/armas_de_balines"],
                            ["title" => "Ofertas térmicos", "image" => "termica.webp", "url" => "/caza/vision_termica_y_nocturna"],
                            ["title" => "Ofertas ARMEROS", "image" => "armeros.webp", "url" => "/caza/armeros_de_seguridad"],
                            ["title" => "Ofertas trípodes", "image" => "tripodes.webp", "url" => "/caza/tripodes_horquillas_y_bipodes"],
                            ["title" => "Ofertas ropa de caza", "image" => "ropa.webp", "url" => "/caza/ropa_y_complementos"],
                            ["title" => "Ofertas botas de caza", "image" => "botas.webp", "url" => "/caza/calzado"],
                            ["title" => "Ofertas para tu perro", "image" => "perros.webp", "url" => "/caza/productos_para_el_perro"],
                            ["title" => "Ofertas cuchillos", "image" => "cuchillos.webp", "url" => "/caza/cuchillos"],
                            ["title" => "Ofertas linternas", "image" => "linterna.webp", "url" => "/caza/linternas_y_focos"],
                            ["title" => "Ofertas competición", "image" => "tiro.webp", "url" => "/caza/competicion_y_tiro"],
                        ],
                        "pt" => [
                            ["title" => "Ofertas armas de chumbos", "image" => "balines.webp", "url" => "/pt/caca/armas_de_chumbos"],
                            ["title" => "Ofertas térmicos", "image" => "termica.webp", "url" => "/pt/caca/visao_termica_e_noturna"],
                            ["title" => "Ofertas tripés", "image" => "tripodes.webp", "url" => "/pt/caca/tripes_monopes_e_bipes"],
                            ["title" => "Ofertas roupa de caça", "image" => "ropa.webp", "url" => "/pt/caca/roupa_e_complementos"],
                            ["title" => "Ofertas calçado de caça", "image" => "botas.webp", "url" => "/pt/caca/calcado"],
                            ["title" => "Ofertas para o cão", "image" => "perros.webp", "url" => "/pt/caca/produtos_para_o_cao"],
                            ["title" => "Ofertas facas", "image" => "cuchillos.webp", "url" => "/pt/caca/facas"],
                            ["title" => "Ofertas lanternas", "image" => "linterna.webp", "url" => "/pt/caca/linternas_y_focos"],
                            ["title" => "Ofertas competição", "image" => "tiro.webp", "url" => "/pt/caca/competicao_e_tiro"],
                            ["title" => "2", "image" => "relleno-pt.webp", "url" => ""],
                        ],
                        "fr" => [
                            ["title" => "Promotions armes à air comprimé", "image" => "balines.webp", "url" => "/fr/chasse/armes_a_air"],
                            ["title" => "Promotions thermiques", "image" => "termica.webp", "url" => "/fr/chasse/vision_thermique_et_nocturne"],
                            ["title" => "Promotions trépieds", "image" => "tripodes.webp", "url" => "/fr/chasse/trepieds_de_chasse"],
                            ["title" => "Promotions vêtements", "image" => "ropa.webp", "url" => "/fr/chasse/vetements_et_accessoires"],
                            ["title" => "Promotions bottes", "image" => "botas.webp", "url" => "/fr/chasse/chaussures"],
                            ["title" => "Promotions pour chien", "image" => "perros.webp", "url" => "/fr/chasse/articles_pour_chiens"],
                            ["title" => "Promotions couteaux", "image" => "cuchillos.webp", "url" => "/fr/chasse/couteaux"],
                            ["title" => "Promotions lampes", "image" => "linterna.webp", "url" => "/fr/chasse/lampes"],
                            ["title" => "Promotions tir sportif", "image" => "tiro.webp", "url" => "/fr/chasse/tir_sportif"],
                            ["title" => "2", "image" => "relleno-fr.webp", "url" => ""],
                        ],
                        "de" => [
                            ["title" => "Angebote Druckluftwaffen", "image" => "balines.webp", "url" => "/de/jagd/luftdruckwaffen"],
                            ["title" => "Angebote Wärmebildgeräte", "image" => "termica.webp", "url" => "/de/jagd/waermebild_und_nachtsichtgeraete"],
                            ["title" => "Angebote Dreibeine", "image" => "tripodes.webp", "url" => "/de/jagd/jagd_stative"],
                            ["title" => "Angebote Bekleidung", "image" => "ropa.webp", "url" => "/de/jagd/bekleidung_und_accesoires"],
                            ["title" => "Angebote Stiefel", "image" => "botas.webp", "url" => "/de/jagd/schuhe"],
                            ["title" => "Angebote für Ihren Hund", "image" => "perros.webp", "url" => "/de/jagd/hundezubehoer_hundebedarf"],
                            ["title" => "Angebote Messer", "image" => "cuchillos.webp", "url" => "/de/jagd/messer"],
                            ["title" => "Angebote Taschenlampen", "image" => "linterna.webp", "url" => "/de/jagd/taschenlampen_und_handscheinwerfern"],
                            ["title" => "Angebote Sportschießen", "image" => "tiro.webp", "url" => "/de/jagd/wettbewerb_und_schiesen"],
                            ["title" => "2", "image" => "relleno-de.webp", "url" => ""],
                        ],
                        "it" => [
                            ["title" => "Offerte armi ad aria", "image" => "balines.webp", "url" => "/it/caccia/armi_ad_aria_compressa"],
                            ["title" => "Offerte termici", "image" => "termica.webp", "url" => "/it/caccia/visione_termica_e_notturna"],
                            ["title" => "Offerte treppiedi", "image" => "tripodes.webp", "url" => "/it/caccia/treppiedi_da_caccia"],
                            ["title" => "Offerte abbigliamento", "image" => "ropa.webp", "url" => "/it/caccia/abbigliamento_e_accessori"],
                            ["title" => "Offerte stivali", "image" => "botas.webp", "url" => "/it/caccia/calzature"],
                            ["title" => "Offerte per il tuo cane", "image" => "perros.webp", "url" => "/it/caccia/prodotti_per_cani"],
                            ["title" => "Offerte coltelli", "image" => "cuchillos.webp", "url" => "/it/caccia/coltelli"],
                            ["title" => "Offerte torce e fari", "image" => "linterna.webp", "url" => "/it/caccia/torce_e_fari"],
                            ["title" => "Offerte tiro sportivo", "image" => "tiro.webp", "url" => "/it/caccia/competizione_e_tiro"],
                            ["title" => "2", "image" => "relleno-it.webp", "url" => ""],
                        ],
                        "en" => [
                            ["title" => "Airgun Offers", "image" => "balines.webp", "url" => "/en/hunting/bb_guns"],
                            ["title" => "Thermal Vision Offers", "image" => "termica.webp", "url" => "/en/hunting/thermal_and_night_vision"],
                            ["title" => "Tripod Offers", "image" => "tripodes.webp", "url" => "/en/hunting/tripods_forks_and_bipods"],
                            ["title" => "Clothing Offers", "image" => "ropa.webp", "url" => "/en/hunting/clothes_and_complements"],
                            ["title" => "Boots Offers", "image" => "botas.webp", "url" => "/en/hunting/footwear"],
                            ["title" => "Dog Gear Offers", "image" => "perros.webp", "url" => "/en/hunting/dog_products"],
                            ["title" => "Knife Offers", "image" => "cuchillos.webp", "url" => "/en/hunting/knives"],
                            ["title" => "Flashlights Offers", "image" => "linterna.webp", "url" => "/en/hunting/flashlights_and_spotlights"],
                            ["title" => "Competition Offers", "image" => "tiro.webp", "url" => "/en/hunting/competition_and_shooting"],
                            ["title" => "2", "image" => "relleno-fr.webp", "url" => ""],
                        ],
                    ]
                ];
                break;

            case 5:
                $data = [
                    "deporte" => "pesca",
                    "title" => [
                        "es" => "Oferta especial pesca",
                        "pt" => "Oferta especial pesca",
                        "fr" => "Offre spéciale pêche",
                        "de" => "Spezialangebot Angelruten",
                        "it" => "Offerta speciale pesca",
                        "en" => "Special Fishing Offer",
                    ],
                    "h1" => [
                        "es" => "OFERTAS PESCA",
                        "pt" => "PROMOÇÕES PESCA",
                        "fr" => "OFFRES DE PÊCHE",
                        "de" => "ANGEBOTE ANGELN",
                        "it" => "OFFERTE PESCA",
                        "en" => "FISHING OFFERS",
                    ],
                    "h3" => [
                        "es" => "OFERTAS PESCA",
                        "pt" => "PROMOÇÕES PESCA",
                        "fr" => "OFFRES DE PÊCHE",
                        "de" => "ANGEBOTE ANGELN",
                        "it" => "OFFERTE PESCA",
                        "en" => "FISHING OFFERS",
                    ],
                    "texts" => [
                        "es" => "<p><strong><h3>Ofertas de pesca: Tu lugar de referencia para pescar al mejor precio</h3></strong> Bienvenido a la sección de <strong>ofertas de pesca</strong> de Álvarez, el punto de encuentro online para todo pescador. Sabemos que cada modalidad de pesca es diferente y requiere un equipo específico. Por eso, hemos reunido una cuidada selección de las <strong>mejores ofertas y descuentos</strong> en cañas, carretes, señuelos, líneas y todo tipo de accesorios para cualquier disciplina, ya sea <strong>pesca a mosca, spinning, surfcasting, carpfishing o pesca en alta mar.</strong></p><p><strong><h3>Productos de las mejores marcas y ofertas que se actualizan a diario</h3></strong> En esta página, la calidad no está reñida con el precio. Te garantizamos que encontrarás promociones exclusivas en las marcas más respetadas del sector, como <strong>Shimano, Daiwa, Hart, Rapala, Kali Kunnan, Penn, Mitchell</strong>… Nuestro equipo actualiza constantemente los productos para que siempre encuentres las últimas novedades y las mejores oportunidades del mercado.</p><p><strong><h3>Visita obligatoria para cualquier pescador</h3></strong> No dejes pasar la oportunidad de equiparte con el material que necesitas para tus jornadas de pesca a precios inigualables. Esta sección de <strong>ofertas de pesca</strong> es el recurso indispensable que debes tener en tus favoritos. Consulta a menudo y aprovecha antes de que se agoten.</p><p><strong>Aprovecha ahora nuestras ofertas limitadas para pescar como nunca ¡Solo en Álvarez!</strong></p>",
                        "pt" => "<p><strong><h3>Ofertas de pesca: O seu local de referência para pescar ao melhor preço</h3></strong> Bem-vindo à secção de <strong>ofertas de pesca</strong> da Álvarez, o ponto de encontro online para todos os pescadores. Sabemos que cada modalidade de pesca é diferente e requer um equipamento específico. Por isso, reunimos uma seleção cuidadosa das <strong>melhores ofertas e descontos</strong> em canas, carretos, iscas, linhas e todo o tipo de acessórios para qualquer disciplina, seja <strong>pesca com mosca, spinning, surfcasting, carpfishing ou pesca em alto mar.</strong></p><p><strong><h3>Produtos das melhores marcas e ofertas atualizadas diariamente</h3></strong> Nesta página, a qualidade não está em conflito com o preço. Garantimos que encontrará promoções exclusivas das marcas mais respeitadas do setor, como <strong>Shimano, Daiwa, Hart, Rapala, Kali Kunnan, Penn, Mitchell</strong>... A nossa equipa atualiza constantemente os produtos para que encontre sempre as últimas novidades e as melhores oportunidades do mercado.</p><p><strong><h3>Visita obrigatória para qualquer pescador</h3></strong> Não perca a oportunidade de se equipar com o material que precisa para as suas jornadas de pesca a preços imbatíveis. Esta secção de <strong>ofertas de pesca</strong> é o recurso indispensável que deve ter nos seus favoritos. Consulte-a frequentemente e aproveite antes que se esgotem.</p><p><strong>Aproveite agora as nossas ofertas limitadas. Só na Álvarez!</strong></p>",
                        "fr" => "<p><strong><h3>Offres de pêche : votre référence pour pêcher au meilleur prix</h3></strong> Bienvenue dans la section des <strong>offres de pêche</strong> d'Álvarez, le point de rencontre en ligne pour tous les pêcheurs. Nous savons que chaque type de pêche est différent et nécessite un équipement spécifique. C'est pourquoi nous avons réuni une sélection rigoureuse des <strong>meilleures offres et réductions</strong> sur les cannes, les moulinets, les leurres, les lignes et tous types d'accessoires pour toutes les disciplines, qu'il s'agisse de <strong>pêche à la mouche, de spinning, de surfcasting, de pêche à la carpe ou de pêche en haute mer.</strong></p><p><strong><h3>Des produits des meilleures marques et des offres mises à jour quotidiennement</h3></strong> Sur cette page, la qualité n'est pas incompatible avec le prix. Nous vous garantissons que vous trouverez des promotions exclusives sur les marques les plus respectées du secteur, telles que <strong>Shimano, Daiwa, Hart, Rapala, Kali Kunnan, Penn, Mitchell</strong>... Notre équipe met constamment à jour les produits afin que vous trouviez toujours les dernières nouveautés et les meilleures opportunités du marché.</p><p><strong><h3>Une visite incontournable pour tout pêcheur</h3></strong> Ne manquez pas l'occasion de vous équiper du matériel dont vous avez besoin pour vos journées de pêche à des prix imbattables. Cette section d'offres de pêche est une ressource indispensable à ajouter à vos favoris. Consultez-la régulièrement et profitez-en avant qu'il ne soit trop tard.</p><p><strong>Profitez dès maintenant de nos offres limitées. Uniquement chez Álvarez !</strong></p>",
                        "de" => "<p><strong><h3>Angelangebote: Ihre Anlaufstelle für Angelausrüstung zum besten Preis</h3></strong> Willkommen im Bereich Angelangebote von Álvarez, dem Online-Treffpunkt für alle Angler. Wir wissen, dass jede Angelart anders ist und eine spezielle Ausrüstung erfordert. Deshalb haben wir eine sorgfältige Auswahl der <strong>besten Angebote und Rabatte</strong> für Ruten, Rollen, Köder, Schnüre und alle Arten von Zubehör für jede Disziplin zusammengestellt, sei es <strong>Fliegenfischen, Spinnfischen, Brandungsangeln, Karpfenangeln oder Hochseefischen.</strong></p><p><strong><h3>Produkte der besten Marken und Angebote, die täglich aktualisiert werden</h3></strong> Auf dieser Seite steht Qualität nicht im Widerspruch zum Preis. Wir garantieren Ihnen, dass Sie exklusive Angebote der renommiertesten Marken der Branche finden, wie <strong>Shimano, Daiwa, Hart, Rapala, Kali Kunnan, Penn, Mitchell</strong>... Unser Team aktualisiert die Produkte ständig, damit Sie immer die neuesten Produkte und die besten Angebote auf dem Markt finden.</p><p><strong><h3>Ein Muss für jeden Angler</h3></strong> Verpassen Sie nicht die Gelegenheit, sich mit der Ausrüstung, die Sie für Ihre Angeltouren benötigen, zu unschlagbaren Preisen auszustatten. Dieser Bereich mit <strong>Angelangeboten</strong> ist eine unverzichtbare Ressource, die Sie zu Ihren Favoriten hinzufügen sollten. Schauen Sie regelmäßig vorbei und profitieren Sie davon, bevor die Angebote ausverkauft sind.</p><p><strong>Nutzen Sie jetzt unsere limitierten Angebote. Nur bei Álvarez!</strong></p>",
                        "it" => "<p><strong><h3>Offerte per la pesca: il tuo punto di riferimento per pescare al miglior prezzo</h3></strong> Benvenuto nella sezione delle <strong>offerte per la pesca</strong> di Álvarez, il punto d'incontro online per tutti i pescatori. Sappiamo che ogni tipo di pesca è diverso e richiede un'attrezzatura specifica. Per questo motivo, abbiamo raccolto un'accurata selezione delle <strong>migliori offerte e sconti</strong> su canne, mulinelli, esche, lenze e tutti i tipi di accessori per qualsiasi disciplina, che si tratti di <strong>pesca a mosca, spinning, surfcasting, carpfishing o pesca d'altura.</strong></p><p><strong><h3>Prodotti delle migliori marche e offerte aggiornate quotidianamente</h3></strong> In questa pagina, la qualità non è in contrasto con il prezzo. Ti garantiamo che troverai promozioni esclusive sulle marche più rispettate del settore, come <strong>Shimano, Daiwa, Hart, Rapala, Kali Kunnan, Penn, Mitchell</strong>... Il nostro team aggiorna costantemente i prodotti in modo che tu possa sempre trovare le ultime novità e le migliori opportunità sul mercato.</p><p><strong><h3>Una tappa obbligatoria per ogni pescatore</h3></strong> Non perdere l'occasione di attrezzarti con il materiale necessario per le tue giornate di pesca a prezzi imbattibili. Questa sezione di <strong>offerte di pesca</strong> è una risorsa indispensabile da aggiungere ai tuoi preferiti. Consultala spesso e approfittane prima che finiscano.</p><p><strong>Approfitta subito delle nostre offerte limitate. Solo da Álvarez!</strong></p>",
                        "en" => "<p><strong><h3>Fishing offers: Your go-to place for fishing at the best price</h3></strong> Welcome to the <strong>fishing offers section</strong> of Álvarez, the online meeting point for all anglers. We know that every type of fishing is different and requires specific equipment. That's why we've put together a carefully selected range of the <strong>best offers and discounts</strong> on rods, reels, lures, lines and all kinds of accessories for any discipline, whether it's <strong>fly fishing, spinning, surfcasting, carp fishing or deep-sea fishing.</strong></p><p><strong><h3>Products from the best brands and offers that are updated daily</h3></strong> On this page, quality is not at odds with price. We guarantee that you will find exclusive promotions on the most respected brands in the sector, such as <strong>Shimano, Daiwa, Hart, Rapala, Kali Kunnan, Penn, Mitchell</strong>... Our team constantly updates the products so that you can always find the latest innovations and the best opportunities on the market.</p><p><strong><h3>A must-visit for any fisherman</h3></strong> Don't miss the opportunity to equip yourself with the gear you need for your fishing trips at unbeatable prices. This <strong>fishing offers section</strong> is an indispensable resource that you should have in your favourites. Check it often and take advantage before they run out.</p><p><strong>Take advantage of our limited offers now. Only at Álvarez!</strong></p>"
                    ],
                    "imagenes" => [
                        "es" => [
                            ["title" => "Ofertas cañas", "image" => "canhas.webp", "url" => "/pesca/canas"],
                            ["title" => "Ofertas carretes", "image" => "carrete.webp", "url" => "/pesca/carretes"],
                            ["title" => "Ofertas hilos de pesca", "image" => "hilos.webp", "url" => "/pesca/hilos"],
                            ["title" => "Ofertas señuelos", "image" => "senhuelos.webp", "url" => "/pesca/peces_artificiales_y_senuelos_pesca"],
                            ["title" => "Ofertas patos", "image" => "pato.webp", "url" => "/pesca/patos_de_pesca"],
                            ["title" => "Ofertas botas/vadeadores", "image" => "botas.webp", "url" => "/pesca/botas_y_vadeadores"],
                            ["title" => "0", "image" => "relleno-es.webp", "url" => ""],
                            ["title" => "Ofertas ropa de pesca", "image" => "ropa.webp", "url" => "/pesca/ropa_y_complementos"],
                            ["title" => "1", "image" => "relleno-es.webp", "url" => ""],
                        ],
                        "pt" => [
                            ["title" => "Ofertas canas", "image" => "canhas.webp", "url" => "/pt/pesca/canas"],
                            ["title" => "Ofertas carretos", "image" => "carrete.webp", "url" => "/pt/pesca/carretos"],
                            ["title" => "Ofertas linhas", "image" => "hilos.webp", "url" => "/pt/pesca/linhas"],
                            ["title" => "Ofertas iscos", "image" => "senhuelos.webp", "url" => "/pt/pesca/iscos_e_peixes_artificiais"],
                            ["title" => "Ofertas patos", "image" => "pato.webp", "url" => "/pt/pesca/patos"],
                            ["title" => "Ofertas botas/vadeadores", "image" => "botas.webp", "url" => "/pt/pesca/botas_e_vadeadores"],
                            ["title" => "0", "image" => "relleno-pt.webp", "url" => ""],
                            ["title" => "Ofertas roupa de pesca", "image" => "ropa.webp", "url" => "/pt/pesca/roupa_e_complementos"],
                            ["title" => "1", "image" => "relleno-pt.webp", "url" => ""],
                        ],
                        "fr" => [
                            ["title" => "Promos cannes à pêche", "image" => "canhas.webp", "url" => "/fr/peche/cannes_a_peche"],
                            ["title" => "Promos moulinets", "image" => "carrete.webp", "url" => "/fr/peche/moulinets"],
                            ["title" => "Promos fils de pêche", "image" => "hilos.webp", "url" => "/fr/peche/fils_de_peche"],
                            ["title" => "Promos leurres de pêche", "image" => "senhuelos.webp", "url" => "/fr/peche/poissons_artificiels_et_leurres_de_peche"],
                            ["title" => "Promos float tubes", "image" => "pato.webp", "url" => "/fr/peche/float_tubes"],
                            ["title" => "Promos bottes et waders", "image" => "botas.webp", "url" => "/fr/peche/bottes_et_waders"],
                            ["title" => "0", "image" => "relleno-fr.webp", "url" => ""],
                            ["title" => "Promos vêtements de pêche", "image" => "ropa.webp", "url" => "/fr/peche/vetements_et_accessoires"],
                            ["title" => "1", "image" => "relleno-fr.webp", "url" => ""],
                        ],
                        "de" => [
                            ["title" => "Angelruten Angebote", "image" => "canhas.webp", "url" => "/de/angeln/angelruten"],
                            ["title" => "Angelrollen Angebote", "image" => "carrete.webp", "url" => "/de/angeln/angelrollen"],
                            ["title" => "Angelschnur Angebote", "image" => "hilos.webp", "url" => "/de/angeln/angelschnur"],
                            ["title" => "Kunstköder Angebote", "image" => "senhuelos.webp", "url" => "/de/angeln/kunstkoeder_und_angelkoeder"],
                            ["title" => "Belly Boot Angebote", "image" => "pato.webp", "url" => "/de/angeln/belly_boote"],
                            ["title" => "Stiefel Angebote", "image" => "botas.webp", "url" => "/de/angeln/wathosen"],
                            ["title" => "0", "image" => "relleno-de.webp", "url" => ""],
                            ["title" => "Angelbekleidung Angebote", "image" => "ropa.webp", "url" => "/de/angeln/angelbekleidung_und_accessoires"],
                            ["title" => "1", "image" => "relleno-de.webp", "url" => ""],
                        ],
                        "it" => [
                            ["title" => "Offerte canne da pesca", "image" => "canhas.webp", "url" => "/it/pesca/canne_da_pesca"],
                            ["title" => "Offerte mulinelli", "image" => "carrete.webp", "url" => "/it/pesca/mulinelli_da_pesca"],
                            ["title" => "Offerte fili da pesca", "image" => "hilos.webp", "url" => "/it/pesca/fili_da_pesca"],
                            ["title" => "Offerte esche da pesca", "image" => "senhuelos.webp", "url" => "/it/pesca/esche_e_artificiali_per_la_pesca"],
                            ["title" => "Offerte belly boat", "image" => "pato.webp", "url" => "/it/pesca/belly_boat_da_pesca_"],
                            ["title" => "Offerte stivali e wader", "image" => "botas.webp", "url" => "/it/pesca/waders_da_pesca"],
                            ["title" => "0", "image" => "relleno-it.webp", "url" => ""],
                            ["title" => "Offerte abbigliamento", "image" => "ropa.webp", "url" => "/it/pesca/abbigliamento_e_accessori"],
                            ["title" => "1", "image" => "relleno-it.webp", "url" => ""],
                        ],
                        "en" => [
                            ["title" => "Fishing Rod Offers", "image" => "canhas.webp", "url" => "/en/fishing/rods"],
                            ["title" => "Fishing Reel Offers", "image" => "carrete.webp", "url" => "/en/fishing/reels"],
                            ["title" => "Fishing Line Offers", "image" => "hilos.webp", "url" => "/en/fishing/fishing_lines"],
                            ["title" => "Fishing Lure Offers", "image" => "senhuelos.webp", "url" => "/en/fishing/artificial_fish_and_fishing_lures"],
                            ["title" => "Float Tube Offers", "image" => "pato.webp", "url" => "/en/fishing/float_tubes"],
                            ["title" => "Boots & Waders Offers", "image" => "botas.webp", "url" => "/en/fishing/boots_and_waders"],
                            ["title" => "0", "image" => "relleno-en.webp", "url" => ""],
                            ["title" => "Fishing Clothing Offers", "image" => "ropa.webp", "url" => "/en/fishing/clothes_and_complements"],
                            ["title" => "1", "image" => "relleno-en.webp", "url" => ""],
                        ],
                    ],
                ];
                break;

            case 6:
                $data = [
                    "deporte" => "hipica",
                    "title" => [
                        "es" => "Oferta especial hípica",
                        "pt" => "Oferta especial equitação",
                        "fr" => "Offre spéciale équitation",
                        "de" => "Spezialangebot Reitstiefel",
                        "it" => "Offerta speciale equi",
                        "en" => "Special Equestrian Offer",
                    ],
                    "h1" => [
                        "es" => "OFERTAS HIPICA",
                        "pt" => "PROMOÇÕES EQUITAÇÃO",
                        "fr" => "OFFRES D'ÉQUITATION",
                        "de" => "ANGEBOTE REITEN",
                        "it" => "OFFERTE EQUITAZIONE",
                        "en" => "HORSE RIDING OFFERS",
                    ],
                    "h3" => [
                        "es" => "OFERTAS HIPICA",
                        "pt" => "PROMOÇÕES EQUITAÇÃO",
                        "fr" => "OFFRES D'ÉQUITATION",
                        "de" => "ANGEBOTE REITEN",
                        "it" => "OFFERTE EQUITAZIONE",
                        "en" => "HORSE RIDING OFFERS",
                    ],
                    "texts" => [
                        "es" => "<p><strong><h3>Ofertas de Hípica: El mejor equipamiento para el jinete, al mejor precio</h3></strong> Bienvenido a la sección de <strong>ofertas de hípica</strong> de Álvarez, tu web para conseguir equipamiento ecuestre de alta calidad a precios inigualables. Entendemos la pasión por la equitación y la importancia de contar con el material adecuado, tanto para el jinete como para el caballo. Por eso, hemos reunido una selección exclusiva de <strong>ofertas y descuentos en sillas de montar, cascos, botas, ropa, mantas y accesorios.</strong></p><p><strong><h3>Las mejores marcas de equitación a precios de oferta</h3></strong> Nuestra página se actualiza constantemente para ofrecerte las promociones más recientes de las marcas más respetadas del sector. Encontrarás descuentos en productos de primeras marcas como <strong>Zaldi, Prestige, BR, Kingsland, Equi Theme, Tattini, Kask, Kep...</strong> entre muchas otras. Tanto si practicas doma clásica, salto o simplemente disfrutas de un paseo, aquí encontrarás el material perfecto para tu disciplina a un precio que te sorprenderá.</p><p><strong><h3>Un lugar de consulta obligada para los amantes del caballo</h3></strong> No dejes escapar la oportunidad de mejorar tu equipo con la seguridad y calidad que te ofrecen las marcas líderes. Esta sección de <strong>ofertas de equitación</strong> es un recurso indispensable para cualquier aficionado a montar a caballo. Te recomendamos que la consultes con regularidad, ya que nuestras ofertas son por tiempo limitado y se agotan rápidamente.</p><p><strong>Aprovecha ahora y equipa tu pasión; ¡Solo en Álvarez!</strong></p>",
                        "pt" => "<p><strong><h3>Ofertas de equitação: O melhor equipamento para o cavaleiro, ao melhor preço</h3></strong> Bem-vindo à secção de <strong>ofertas de equitação</strong> da Álvarez, o seu site para adquirir equipamento equestre de alta qualidade a preços imbatíveis. Compreendemos a paixão pela equitação e a importância de contar com o material adequado, tanto para o cavaleiro como para o cavalo. Por isso, reunimos uma seleção exclusiva de <strong>ofertas e descontos em selas, capacetes, botas, roupas, mantas e acessórios.</strong></p><p><strong><h3>As melhores marcas de equitação a preços promocionais</h3></strong> A nossa página é atualizada constantemente para lhe oferecer as promoções mais recentes das marcas mais respeitadas do setor. Encontrará descontos em produtos de marcas de primeira linha como <strong>Zaldi, Prestige, BR, Kingsland, Equi Theme, Tattini, Kask, Kep...</strong> entre muitas outras. Quer pratique adestramento clássico, salto ou simplesmente goste de passear, aqui encontrará o equipamento perfeito para a sua disciplina a um preço que o surpreenderá.</p><p><strong><h3>Um local de consulta obrigatória para os amantes de cavalos</h3></strong> Não perca a oportunidade de melhorar o seu equipamento com a segurança e qualidade que as marcas líderes oferecem. Esta secção de <strong>ofertas de equitação</strong> é um recurso indispensável para qualquer aficionado por equitação. Recomendamos que a consulte regularmente, pois as nossas ofertas são por tempo limitado e esgotam rapidamente.</p><p><strong>Aproveite agora e equipe a sua paixão. Só na Álvarez!</strong></p>",
                        "fr" => "<p><strong><h3>Offres équitation : le meilleur équipement pour le cavalier, au meilleur prix</h3></strong> Bienvenue dans la section des <strong>offres équitation</strong> d'Álvarez, votre site web pour trouver du matériel équestre de haute qualité à des prix imbattables. Nous comprenons la passion pour l'équitation et l'importance de disposer du matériel adéquat, tant pour le cavalier que pour le cheval. C'est pourquoi nous avons réuni une <strong>sélection exclusive d'offres et de réductions</strong> sur les selles, les casques, les bottes, les vêtements, les couvertures et les accessoires.</p><p><strong><h3>Les meilleures marques d'équitation à prix réduits</h3></strong> Notre page est constamment mise à jour pour vous proposer les dernières promotions des marques les plus respectées du secteur. Vous trouverez des réductions sur des produits de grandes marques telles que <strong>Zaldi, Prestige, BR, Kingsland, Equi Theme, Tattini, Kask, Kep...</strong> parmi beaucoup d'autres. Que vous pratiquiez le dressage classique, le saut d'obstacles ou que vous aimiez simplement vous promener à cheval, vous trouverez ici le matériel parfait pour votre discipline à un prix qui vous surprendra.</p><p><strong><h3>Un site incontournable pour les amateurs de chevaux</h3></strong> Ne manquez pas l'occasion d'améliorer votre équipement avec la sécurité et la qualité offertes par les marques leaders. Cette section <strong>d'offres équestres</strong> est une ressource indispensable pour tout amateur d'équitation. Nous vous recommandons de la consulter régulièrement, car nos offres sont limitées dans le temps et s'épuisent rapidement.</p><p><strong>Profitez-en dès maintenant et équipez-vous pour votre passion. Uniquement chez Álvarez !</strong></p>",
                        "de" => "<p><strong><h3>Angebote für Reitsport: Die beste Ausrüstung für Reiter zum besten Preis</h3></strong> Willkommen im Bereich <strong>Angebote für Reitsport</strong> von Álvarez, Ihrer Website für hochwertige Reitsportausrüstung zu unschlagbaren Preisen. Wir verstehen die Leidenschaft für den Reitsport und wissen, wie wichtig die richtige Ausrüstung sowohl für den Reiter als auch für das Pferd ist. Deshalb haben wir eine <strong>exklusive Auswahl an Angeboten und Rabatten für Sättel, Helme, Stiefel, Kleidung, Decken und Zubehör zusammengestellt.</strong></p><p><strong><h3>Die besten Reitsportmarken zu Sonderpreisen</h3></strong> Unsere Website wird ständig aktualisiert, um Ihnen die neuesten Angebote der renommiertesten Marken der Branche zu präsentieren. Sie finden Rabatte auf Produkte von Top-Marken wie <strong>Zaldi, Prestige, BR, Kingsland, Equi Theme, Tattini, Kask, Kep...</strong> und vielen anderen. Egal, ob Sie Dressur oder Springreiten betreiben oder einfach nur gerne ausreiten, hier finden Sie die perfekte Ausrüstung für Ihre Disziplin zu einem Preis, der Sie überraschen wird.</p><p><strong><h3>Ein Muss für Pferdeliebhaber</h3></strong> Verpassen Sie nicht die Gelegenheit, Ihre Ausrüstung mit der Sicherheit und Qualität der führenden Marken zu verbessern. Dieser Bereich mit Reitsportangeboten ist eine unverzichtbare Quelle für jeden Reitsportfan. Wir empfehlen Ihnen, regelmäßig vorbeizuschauen, da unsere <strong>Angebote</strong> zeitlich begrenzt sind und schnell ausverkauft sind.</p><p><strong>Nutzen Sie jetzt die Gelegenheit und statten Sie Ihre Leidenschaft aus. Nur bei Álvarez!</strong></p>",
                        "it" => "<p><strong><h3>Offerte equitazione: la migliore attrezzatura per il cavaliere, al miglior prezzo</h3></strong> Benvenuto nella sezione <strong>offerte equitazione</strong> di Álvarez, il tuo sito web per acquistare attrezzatura equestre di alta qualità a prezzi imbattibili. Comprendiamo la passione per l'equitazione e l'importanza di disporre dell'attrezzatura adeguata, sia per il cavaliere che per il cavallo. Per questo motivo, abbiamo raccolto una <strong>selezione esclusiva di offerte e sconti su selle, caschi, stivali, abbigliamento, coperte e accessori.</strong></p><p><strong><h3>Le migliori marche di equitazione a prezzi scontati</h3></strong> Il nostro sito viene aggiornato costantemente per offrirti le promozioni più recenti delle marche più rispettate del settore. Troverai sconti su prodotti di marchi leader come <strong>Zaldi, Prestige, BR, Kingsland, Equi Theme, Tattini, Kask, Kep...</strong> e molti altri. Che tu pratichi dressage, salto ostacoli o semplicemente ti piaccia fare una passeggiata, qui troverai l'attrezzatura perfetta per la tua disciplina a un prezzo che ti sorprenderà.</p><p><strong><h3>Un punto di riferimento obbligatorio per gli amanti dei cavalli</h3></strong> Non perdere l'occasione di migliorare la tua attrezzatura con la sicurezza e la qualità offerte dai marchi leader. Questa sezione di <strong>offerte per l'equitazione</strong> è una risorsa indispensabile per qualsiasi appassionato di equitazione. Ti consigliamo di consultarla regolarmente, poiché le nostre offerte sono a tempo limitato e vanno a ruba.</p><p><strong>Approfitta subito e equipaggia la tua passione. Solo da Álvarez!</strong></p>",
                        "en" => "<p><strong><h3>Equestrian Offers: The best equipment for riders, at the best prices</h3></strong> Welcome to the <strong>equestrian offers section</strong> of Álvarez, your website for high-quality equestrian equipment at unbeatable prices. We understand the passion for horse riding and the importance of having the right equipment, both for the rider and the horse. That's why we've put together an <strong>exclusive selection of offers and discounts on saddles, helmets, boots, clothing, blankets and accessories.</strong></p><p><strong><h3>The best equestrian brands at bargain prices</h3></strong> Our website is constantly updated to bring you the latest promotions from the most respected brands in the industry. You will find discounts on products from leading brands such as <strong>Zaldi, Prestige, BR, Kingsland, Equi Theme, Tattini, Kask, Kep...</strong> among many others. Whether you practise dressage, show jumping or simply enjoy a ride, here you will find the perfect equipment for your discipline at a price that will surprise you.</p><p><strong><h3>A must-visit for horse lovers</h3></strong> Don't miss the opportunity to upgrade your equipment with the safety and quality offered by leading brands. This section of <strong>equestrian offers</strong> is an indispensable resource for any horse riding enthusiast. We recommend that you check it regularly, as our offers are for a limited time only and sell out quickly.</p><p><strong>Take advantage now and equip your passion. Only at Álvarez!</strong></p>"
                    ],
                    "imagenes" => [
                        "es" => [
                            ["title" => "Ofertas sillas de montar", "image" => "sillas.webp", "url" => "/hipica/sillas_de_montar"],
                            ["title" => "Ofertas pantalones", "image" => "pantalones.webp", "url" => "/hipica/ropa_y_complementos-pantalones"],
                            ["title" => "Ofertas calzado hípico", "image" => "botas.webp", "url" => "/hipica/calzado_hipica"],
                            ["title" => "Ofertas cascos", "image" => "cascos.webp", "url" => "/hipica/cascos"],
                            ["title" => "Ofertas higiene y salud", "image" => "limpieza.webp", "url" => "/hipica/higiene_y_salud"],
                            ["title" => "Ofertas para el caballo", "image" => "todo-caballo.webp", "url" => "/hipica/equipo_del_caballo"],
                        ],
                        "pt" => [
                            ["title" => "Ofertas selas", "image" => "sillas.webp", "url" => "/pt/equitacao/selas_e_selins"],
                            ["title" => "Ofertas calças equitação", "image" => "pantalones.webp", "url" => "/pt/equitacao/roupa_e_complementos-calcas"],
                            ["title" => "Ofertas calçado equitação", "image" => "botas.webp", "url" => "/pt/equitacao/calcado_equitacao"],
                            ["title" => "Ofertas toques", "image" => "cascos.webp", "url" => "/pt/equitacao/toques_e_complementos"],
                            ["title" => "Ofertas higiene e saúde", "image" => "limpieza.webp", "url" => "/pt/equitacao/higiene_e_saude"],
                            ["title" => "Ofertas equipamento", "image" => "todo-caballo.webp", "url" => "/pt/equitacao/equipamento_do_cavalo"],
                        ],
                        "fr" => [
                            ["title" => "Promos selles", "image" => "sillas.webp", "url" => "/fr/equitation/selles"],
                            ["title" => "Promos pantalons", "image" => "pantalones.webp", "url" => "/fr/equitation/vetements_et_accessoires-pantalons"],
                            ["title" => "Promos chaussures", "image" => "botas.webp", "url" => "/fr/equitation/chaussures_d_equitation"],
                            ["title" => "Promos casques", "image" => "cascos.webp", "url" => "/fr/equitation/casques"],
                            ["title" => "Promos hygiène et santé", "image" => "limpieza.webp", "url" => "/fr/equitation/hygiene_et_sante"],
                            ["title" => "Offres équipement cheval", "image" => "todo-caballo.webp", "url" => "/fr/equitation/equipement_pour_le_cheval"],
                        ],
                        "de" => [
                            ["title" => "Angebote Sättel", "image" => "sillas.webp", "url" => "/de/reiten/sattel"],
                            ["title" => "Reithosen Angebote", "image" => "pantalones.webp", "url" => "/de/reiten/bekleidung_und_accesoires-reithosen"],
                            ["title" => "Reitstiefel Angebote", "image" => "botas.webp", "url" => "/de/reiten/reitschuhe"],
                            ["title" => "Helme Angebote", "image" => "cascos.webp", "url" => "/de/reiten/reithelme"],
                            ["title" => "Hygiene Angebote", "image" => "limpieza.webp", "url" => "/de/reiten/hygiene_und_gesundheit"],
                            ["title" => "Angebote Pferdezubehör", "image" => "todo-caballo.webp", "url" => "/de/reiten/pferdeausrustung"],
                        ],
                        "it" => [
                            ["title" => "Offerte selle", "image" => "sillas.webp", "url" => "/it/equitazione/selle"],
                            ["title" => "Offerte pantaloni", "image" => "pantalones.webp", "url" => "/it/equitazione/abbigliamento_e_accessori-pantaloni"],
                            ["title" => "Offerte calzature", "image" => "botas.webp", "url" => "/it/equitazione/calzature_per_equitazione"],
                            ["title" => "Offerte caschi", "image" => "cascos.webp", "url" => "/it/equitazione/caschi_da_equitazione"],
                            ["title" => "Offerte igiene e salute", "image" => "limpieza.webp", "url" => "/it/equitazione/salute_e_igiene"],
                            ["title" => "Offerte equipaggiamento", "image" => "todo-caballo.webp", "url" => "/it/equitazione/equipaggiamento_per_il_cavallo"],
                        ],
                        "en" => [
                            ["title" => "Saddle Offers", "image" => "sillas.webp", "url" => "/en/horse_riding/saddles"],
                            ["title" => "Riding Pants Offers", "image" => "pantalones.webp", "url" => "/en/horse_riding/clothes_and_complements-pants"],
                            ["title" => "Footwear Offers", "image" => "botas.webp", "url" => "/en/horse_riding/equestrian_footwear"],
                            ["title" => "Helmet Offers", "image" => "cascos.webp", "url" => "/en/horse_riding/helmets"],
                            ["title" => "Hygiene & Health Offers", "image" => "limpieza.webp", "url" => "/en/horse_riding/hygiene_and_health"],
                            ["title" => "Horse Gear Offers", "image" => "todo-caballo.webp", "url" => "/en/horse_riding/horse_equipment"],
                        ],
                    ],
                ];
                break;

            case 7:
                $data = [
                    "deporte" => "buceo",
                    "title" => [
                        "es" => "Oferta especial buceo",
                        "pt" => "Oferta especial mergulho",
                        "fr" => "Offre spéciale plongée",
                        "de" => "Spezialangebot tauchen",
                        "it" => "Offerta speciale SUB",
                        "en" => "Special Diving Offe",
                    ],
                    "h1" => [
                        "es" => "OFERTAS BUCEO",
                        "pt" => "PROMOÇÕES MERGULHO",
                        "fr" => "OFFRES DE PLONGÉE",
                        "de" => "ANGEBOTE TAUCHEN",
                        "it" => "OFFERTE SUBACQUEA",
                        "en" => "DIVING OFFERS",
                    ],
                    "h3" => [
                        "es" => "OFERTAS BUCEO",
                        "pt" => "PROMOÇÕES MERGULHO",
                        "fr" => "OFFRES DE PLONGÉE",
                        "de" => "ANGEBOTE TAUCHEN",
                        "it" => "OFFERTE SUBACQUEA",
                        "en" => "DIVING OFFERS",
                    ],
                    "texts" => [
                        "es" => "<p><strong><h3>Ofertas de buceo: El mejor equipo para disfrutar del mar al mejor precio</h3></strong> Bienvenido a la sección de <strong>ofertas de buceo</strong> de Álvarez, tu punto de referencia online para equiparte y explorar el mundo subacuático. Sabemos que la seguridad y el rendimiento son esenciales bajo el agua, y por eso, hemos seleccionado las mejores <strong>ofertas y descuentos</strong> en trajes de neopreno, reguladores, aletas, máscaras, ordenadores de buceo y todos los accesorios que necesitas para tus inmersiones.</p><p><strong><h3>Las mejores marcas de buceo y ofertas exclusivas</h3></strong> Actualizamos constantemente nuestra página para ofrecerte las promociones más atractivas de las marcas más respetadas del sector. Encontrarás descuentos en productos de primeras marcas como <strong>Mares, Cressi, Scubapro, Aqualung, Seac, Beuchat</strong>, entre otras. Tanto si eres un principiante como un buceador experimentado, aquí encontrarás el material perfecto que se adapte a tu nivel y modalidad, ya sea buceo recreativo, apnea o pesca submarina.</p><p><strong><h3>Visita obligada para cualquier aficionado al mundo submarino</h3></strong> No dejes pasar la oportunidad de conseguir ese equipo de alta calidad que tanto deseas a un precio excepcional. Esta sección de <strong>ofertas de buceo</strong> es un recurso indispensable para cualquier aficionado a disfrutar del mar. Te invitamos a consultarla con regularidad, ya que nuestras ofertas cambian y se agotan con rapidez.</p><p><strong>Equípate para tus inmersiones con la máxima calidad; ¡Solo en Álvarez!</strong></p>",
                        "pt" => "<p><strong><h3>Ofertas de mergulho: O melhor equipamento para desfrutar do mar ao melhor preço</h3></strong> Bem-vindo à secção de <strong>ofertas de mergulho</strong> da Álvarez, o seu ponto de referência online para se equipar e explorar o mundo subaquático. Sabemos que a segurança e o desempenho são essenciais debaixo de água e, por isso, selecionámos as melhores <strong>ofertas e descontos</strong> em fatos de mergulho, reguladores, barbatanas, máscaras, computadores de mergulho e todos os acessórios de que precisa para as suas imersões.</p><p><strong><h3>As melhores marcas de mergulho e ofertas exclusivas</h3></strong> Atualizamos constantemente a nossa página para lhe oferecer as promoções mais atraentes das marcas mais respeitadas do setor. Encontrará descontos em produtos de marcas de primeira linha como <strong>Mares, Cressi, Scubapro, Aqualung, Seac, Beuchat</strong>, entre outras. Quer seja um principiante ou um mergulhador experiente, aqui encontrará o material perfeito que se adapta ao seu nível e modalidade, seja mergulho recreativo, apneia ou pesca submarina.</p><p><strong><h3>Visita obrigatória para qualquer aficionado do mundo subaquático</h3></strong> Não perca a oportunidade de adquirir aquele equipamento de alta qualidade que tanto deseja a um preço excecional. Esta secção de <strong>ofertas de mergulho</strong> é um recurso indispensável para qualquer amante do mar. Convidamo-lo a consultá-la regularmente, pois as nossas ofertas mudam e esgotam rapidamente.</p><p><strong>Equipe-se para as suas imersões com a máxima qualidade; só na Álvarez!</strong></p>",
                        "fr" => "<p><strong><h3>Offres de plongée : le meilleur équipement pour profiter de la mer au meilleur prix</h3></strong> Bienvenue dans la section des <strong>offres de plongée</strong> d'Álvarez, votre référence en ligne pour vous équiper et explorer le monde sous-marin. Nous savons que la sécurité et la performance sont essentielles sous l'eau, c'est pourquoi nous avons sélectionné <strong>les meilleures offres et réductions</strong> sur les combinaisons, détendeurs, palmes, masques, ordinateurs de plongée et tous les accessoires dont vous avez besoin pour vos plongées.</p><p><strong><h3>Les meilleures marques de plongée et des offres exclusives</h3></strong> Nous mettons constamment à jour notre page pour vous proposer les promotions les plus intéressantes des marques les plus respectées du secteur. Vous trouverez des réductions sur des produits de grandes marques telles que <strong>Mares, Cressi, Scubapro, Aqualung, Seac, Beuchat</strong>, entre autres. Que vous soyez débutant ou plongeur expérimenté, vous trouverez ici le matériel parfait adapté à votre niveau et à votre discipline, qu'il s'agisse de plongée récréative, d'apnée ou de pêche sous-marine.</p><p><strong><h3>Un site incontournable pour tous les amateurs du monde sous-marin</h3></strong> Ne manquez pas l'occasion d'acquérir l'équipement de haute qualité que vous désirez tant à un prix exceptionnel. Cette section <strong>d'offres de plongée</strong> est une ressource indispensable pour tous les amateurs de la mer. Nous vous invitons à la consulter régulièrement, car nos offres changent et s'épuisent rapidement.</p><p><strong>Équipez-vous pour vos plongées avec la meilleure qualité ; uniquement chez Álvarez !</strong></p>",
                        "de" => "<p><strong><h3>Tauchangebote: Die beste Ausrüstung, um das Meer zum besten Preis zu genießen</h3></strong> Willkommen im <strong>Bereich Tauchangebote</strong> von Álvarez, Ihrer Online-Anlaufstelle, um sich auszurüsten und die Unterwasserwelt zu erkunden. Wir wissen, dass Sicherheit und Leistung unter Wasser unerlässlich sind, und deshalb haben wir die besten <strong>Angebote und Rabatte</strong> für Neoprenanzüge, Atemregler, Flossen, Masken, Tauchcomputer und sämtliches Zubehör ausgewählt, das Sie für Ihre Tauchgänge benötigen.</p><p><strong><h3>Die besten Tauchmarken und exklusive Angebote</h3></strong> Wir aktualisieren unsere Seite ständig, um Ihnen die attraktivsten Angebote der renommiertesten Marken der Branche zu präsentieren. Sie finden Rabatte auf Produkte von Top-Marken wie <strong>Mares, Cressi, Scubapro, Aqualung, Seac, Beuchat</strong> und anderen. Egal, ob Sie Anfänger oder erfahrener Taucher sind, hier finden Sie die perfekte Ausrüstung, die Ihrem Niveau und Ihrer Tauchart entspricht, sei es Sporttauchen, Apnoetauchen oder Unterwasserfischen.</p><p><strong><h3>Ein Muss für jeden Liebhaber der Unterwasserwelt</h3></strong> Verpassen Sie nicht die Gelegenheit, die hochwertige Ausrüstung, die Sie sich so sehr wünschen, zu einem außergewöhnlichen Preis zu erwerben. Dieser Bereich mit <strong>Tauchangeboten</strong> ist eine unverzichtbare Quelle für alle Liebhaber des Meeres. Wir laden Sie ein, regelmäßig vorbeizuschauen, da sich unsere Angebote ändern und schnell vergriffen sind.</p><p><strong>Rüsten Sie sich für Ihre Tauchgänge mit höchster Qualität aus; nur bei Álvarez!</strong></p>",
                        "it" => "<p><strong><h3>Offerte subacquee: la migliore attrezzatura per godersi il mare al miglior prezzo</h3></strong> Benvenuto nella sezione delle <strong>offerte per le immersioni</strong> di Álvarez, il tuo punto di riferimento online per attrezzarti ed esplorare il mondo sottomarino. Sappiamo che la sicurezza e le prestazioni sono fondamentali sott'acqua, per questo abbiamo selezionato <strong>le migliori offerte e sconti</strong> su mute, erogatori, pinne, maschere, computer da immersione e tutti gli accessori necessari per le tue immersioni.</p><p><strong><h3>Le migliori marche di immersioni e offerte esclusive</h3></strong> Aggiorniamo costantemente la nostra pagina per offrirti le promozioni più interessanti delle marche più rispettate del settore. Troverai sconti su prodotti di marchi leader come <strong>Mares, Cressi, Scubapro, Aqualung, Seac, Beuchat</strong> e molti altri. Che tu sia un principiante o un subacqueo esperto, qui troverai l'attrezzatura perfetta per il tuo livello e la tua disciplina, che si tratti di immersioni ricreative, apnea o pesca subacquea.</p><p><strong><h3>Una tappa obbligata per tutti gli appassionati del mondo sottomarino</h3></strong> Non perdere l'occasione di acquistare l'attrezzatura di alta qualità che desideri a un prezzo eccezionale. Questa sezione dedicata alle <strong>offerte per le immersioni</strong> è una risorsa indispensabile per tutti gli appassionati del mare. Ti invitiamo a consultarla regolarmente, poiché le nostre offerte cambiano e vanno a ruba.</p><p><strong>Equipaggiati per le tue immersioni con la massima qualità; solo da Álvarez!</strong></p>",
                        "en" => "<p><strong><h3>Diving offers: The best equipment to enjoy the sea at the best price</h3></strong> Welcome to <strong>the diving offers section</strong> of Álvarez, your online reference point for equipping yourself and exploring the underwater world. We know that safety and performance are essential underwater, which is why we have selected the best <strong>offers and discounts</strong> on wetsuits, regulators, fins, masks, dive computers and all the accessories you need for your dives.</p><p><strong><h3>The best diving brands and exclusive offers</h3></strong> We constantly update our website to offer you the most attractive promotions from the most respected brands in the industry. You will find discounts on products from leading brands such as <strong>Mares, Cressi, Scubapro, Aqualung, Seac, Beuchat</strong>, among others. Whether you are a beginner or an experienced diver, here you will find the perfect equipment to suit your level and type of diving, whether it be recreational diving, freediving or spearfishing.</p><p><strong><h3>A must-visit for any fan of the underwater world</h3></strong> Don't miss the opportunity to get that high-quality equipment you want so much at an exceptional price. This section of <strong>diving offers</strong> is an indispensable resource for any fan of the sea. We invite you to check it regularly, as our offers change and sell out quickly.</p><p><strong>Equip yourself for your dives with the highest quality; only at Álvarez!</strong></p>"
                    ],
                    "imagenes" => [
                        "es" => [
                            ["title" => "Ofertas trajes de buceo", "image" => "traje.webp", "url" => "/buceo/trajes_de_buceo"],
                            ["title" => "Ofertas jackets de buceo", "image" => "jacket.webp", "url" => "/buceo/chalecos_jackets"],
                            ["title" => "Ofertas ordenadores", "image" => "ordenador.webp", "url" => "/buceo/ordenadoresinterfaz"],
                            ["title" => "Ofertas reguladores", "image" => "regulador.webp", "url" => "/buceo/reguladores"],
                            ["title" => "Ofertas máscaras de buceo", "image" => "mascara.webp", "url" => "/buceo/mascaras_buceo"],
                            ["title" => "Ofertas aletas de buceo", "image" => "aletas.webp", "url" => "/buceo/aletas"],
                            ["title" => "0", "image" => "relleno-es.webp", "url" => ""],
                            ["title" => "Ofertas pesca submarina", "image" => "pesca-submarina.webp", "url" => "/buceo/pesca_submarina"],
                            ["title" => "1", "image" => "relleno-es.webp", "url" => ""],
                        ],
                        "pt" => [
                            ["title" => "Ofertas fatos de mergulho", "image" => "traje.webp", "url" => "/pt/mergulho/fatos_de_mergulho"],
                            ["title" => "Ofertas jackets mergulho", "image" => "jacket.webp", "url" => "/pt/mergulho/jackets"],
                            ["title" => "Ofertas ordenadores", "image" => "ordenador.webp", "url" => "/pt/mergulho/computadoresinterface"],
                            ["title" => "Ofertas reguladores", "image" => "regulador.webp", "url" => "/pt/mergulho/reguladores"],
                            ["title" => "Ofertas mascaras", "image" => "mascara.webp", "url" => "/pt/mergulho/mascaras_de_mergulho"],
                            ["title" => "Ofertas barbatanas", "image" => "aletas.webp", "url" => "/pt/mergulho/barbatanas"],
                            ["title" => "0", "image" => "relleno-pt.webp", "url" => ""],
                            ["title" => "Ofertas pesca submarina", "image" => "pesca-submarina.webp", "url" => "/pt/mergulho/pesca_submarina"],
                            ["title" => "1", "image" => "relleno-pt.webp", "url" => ""],
                        ],
                        "fr" => [
                            ["title" => "Offres combis de plongée", "image" => "traje.webp", "url" => "/fr/plongee/combinaisons_de_plongee"],
                            ["title" => "Offres jackets", "image" => "jacket.webp", "url" => "/fr/plongee/gilets_stabilisateurs"],
                            ["title" => "Offres ordi de plongée", "image" => "ordenador.webp", "url" => "/fr/plongee/ordinateurs"],
                            ["title" => "Offres détendeurs", "image" => "regulador.webp", "url" => "/fr/plongee/detendeurs"],
                            ["title" => "Offres masques de plongée", "image" => "mascara.webp", "url" => "/fr/plongee/masques_"],
                            ["title" => "Offres palmes de plongée", "image" => "aletas.webp", "url" => "/fr/plongee/palmes"],
                            ["title" => "0", "image" => "relleno-fr.webp", "url" => ""],
                            ["title" => "Offres chasse sous-marine", "image" => "pesca-submarina.webp", "url" => "/fr/plongee/chasse_sous_marine"],
                            ["title" => "1", "image" => "relleno-fr.webp", "url" => ""],
                        ],
                        "de" => [
                            ["title" => "Tauchanzug Angebote", "image" => "traje.webp", "url" => "/de/tauchen/tauchanzuge"],
                            ["title" => "Tauchjacken Angebote", "image" => "jacket.webp", "url" => "/de/tauchen/jackets"],
                            ["title" => "Tauchcomputer Angebote", "image" => "ordenador.webp", "url" => "/de/tauchen/tauchcomputer_und_interface"],
                            ["title" => "Tauchregler Angebote", "image" => "regulador.webp", "url" => "/de/tauchen/atemregler"],
                            ["title" => "Tauchmasken Angebote", "image" => "mascara.webp", "url" => "/de/tauchen/tauchermaske"],
                            ["title" => "Tauchflossen Angebote", "image" => "aletas.webp", "url" => "/de/tauchen/tauchflossen"],
                            ["title" => "0", "image" => "relleno-de.webp", "url" => ""],
                            ["title" => "Speerfischen Angebote", "image" => "pesca-submarina.webp", "url" => "/de/tauchen/speerfischen"],
                            ["title" => "1", "image" => "relleno-de.webp", "url" => ""],
                        ],
                        "it" => [
                            ["title" => "Offerte mute da sub", "image" => "traje.webp", "url" => "/it/subacquea/mute_da_immersione"],
                            ["title" => "Offerte jackets da sub", "image" => "jacket.webp", "url" => "/it/subacquea/giubbotti_jackets"],
                            ["title" => "Offerte computer sub", "image" => "ordenador.webp", "url" => "/it/subacquea/computer_interfaccia"],
                            ["title" => "Offerte regolatori da sub", "image" => "regulador.webp", "url" => "/it/subacquea/erogatori"],
                            ["title" => "Offerte maschere da sub", "image" => "mascara.webp", "url" => "/it/subacquea/maschere_subacquea"],
                            ["title" => "Offerte pinne da sub", "image" => "aletas.webp", "url" => "/it/subacquea/pinne"],
                            ["title" => "0", "image" => "relleno-it.webp", "url" => ""],
                            ["title" => "Offerte pesca subacquea", "image" => "pesca-submarina.webp", "url" => "/it/subacquea/pesca_subacquea"],
                            ["title" => "1", "image" => "relleno-it.webp", "url" => ""],
                        ],
                        "en" => [
                            ["title" => "Diving Suit Offers", "image" => "traje.webp", "url" => "/en/diving/diving_suits"],
                            ["title" => "Diving Jacket Offers", "image" => "jacket.webp", "url" => "/en/diving/jackets_vests"],
                            ["title" => "Dive Computer Offers", "image" => "ordenador.webp", "url" => "/en/diving/computers_interface"],
                            ["title" => "Regulator Offers", "image" => "regulador.webp", "url" => "/en/diving/regulators"],
                            ["title" => "Diving Mask Offers", "image" => "mascara.webp", "url" => "/en/diving/diving_masks"],
                            ["title" => "Diving Fins Offers", "image" => "aletas.webp", "url" => "/en/diving/fins"],
                            ["title" => "0", "image" => "relleno-en.webp", "url" => ""],
                            ["title" => "Spearfishing Offers", "image" => "pesca-submarina.webp", "url" => "/en/diving/underwater_fishing"],
                            ["title" => "1", "image" => "relleno-en.webp", "url" => ""],
                        ],
                    ],
                ];
                break;

            case 8:
                $data = [
                    "deporte" => "nautica",
                    "title" => [
                        "es" => "Oferta especial náutica",
                        "pt" => "Oferta especial náutica",
                        "fr" => "Offre spéciale nautique",
                        "de" => "Spezialangebot nautik",
                        "it" => "Offerta speciale nautica",
                        "en" => "Special Nautical Offer",
                    ],
                    "h1" => [
                        "es" => "OFERTAS náutica",
                        "pt" => "PROMOÇÕES náutica",
                        "fr" => "OFFRES DE nautique",
                        "de" => "ANGEBOTE nautik",
                        "it" => "OFFERTE nautica",
                        "en" => "Nautical Offers",
                    ],
                    "h3" => [
                        "es" => "OFERTAS náutica",
                        "pt" => "PROMOÇÕES náutica",
                        "fr" => "OFFRES DE nautique",
                        "de" => "ANGEBOTE nautik",
                        "it" => "OFFERTE nautica",
                        "en" => "Nautical Offers",
                    ],
                    "texts" => [
                        "es" => "<p><strong><h3>Ofertas de náutica: Equipa tu embarcación con lo mejor, al mejor precio</h3></strong> Bienvenido a la sección de <strong>ofertas de náutica</strong> de Álvarez, tu puerto seguro para conseguir equipamiento marítimo de alta calidad con descuentos exclusivos. Sabemos que el mar exige la máxima fiabilidad, y por eso, hemos reunido una selección inmejorable de <strong>ofertas y promociones</strong> en electrónica, seguridad, salvamento, amarre, fondeo y todo tipo de accesorios para tu barco. No importa si tienes una lancha, un velero, una neumática o una moto de agua: aquí encontrarás lo que necesitas para navegar con total seguridad y confort.</p><p><strong><h3>Productos de las mejores marcas y ofertas actualizadas</h3></strong> En esta página, la calidad no tiene por qué ser cara. Actualizamos constantemente nuestro catálogo para ofrecerte las promociones más atractivas de las marcas más respetadas del sector. Descubre descuentos en productos de <strong>Garmin, Raymarine, Lalizas, Slam, Plastimo, Igloo...</strong> entre otras. Nuestro compromiso es darte acceso a los equipos más avanzados y seguros del mercado a precios inigualables.</p><p><strong><h3>Visita obligada para cualquier amante del mar</h3></strong> No dejes pasar la oportunidad de equipar tu embarcación con el material que necesitas para tus travesías. Te recomendamos guardar esta sección de <strong>ofertas de náutica</strong> en tus favoritos y visitarla con regularidad. Nuestras promociones son por tiempo limitado y se agotan rápidamente.</p><p><strong>Aprovecha ahora y hazte a la mar con la confianza que mereces; ¡Solo en Álvarez!</strong></p>",
                        "pt" => "<p><strong><h3>Ofertas náuticas: Equipe o seu barco com o melhor, ao melhor preço</h3></strong> Bem-vindo à secção de <strong>ofertas náuticas</strong> da Álvarez, o seu porto seguro para adquirir equipamento marítimo de alta qualidade com descontos exclusivos. Sabemos que o mar exige a máxima fiabilidade e, por isso, reunimos <strong>uma seleção imbatível de ofertas e promoções</strong> em eletrónica, segurança, salvamento, amarração, ancoragem e todo o tipo de acessórios para o seu barco. Não importa se tem uma lancha, um veleiro, um bote pneumático ou uma mota de água: aqui encontrará o que precisa para navegar com total segurança e conforto.</p><p><strong><h3>Produtos das melhores marcas e ofertas atualizadas</h3></strong> Nesta página, a qualidade não tem de ser cara. Atualizamos constantemente o nosso catálogo para lhe oferecer as promoções mais atrativas das marcas mais respeitadas do setor. Descubra descontos em produtos da <strong>Garmin, Raymarine, Lalizas, Slam, Plastimo, Igloo...</strong> entre outros. O nosso compromisso é dar-lhe acesso aos equipamentos mais avançados e seguros do mercado a preços imbatíveis.</p><p><strong><h3>Visita obrigatória para qualquer amante do mar</h3></strong> Não perca a oportunidade de equipar a sua embarcação com o material de que precisa para as suas viagens. Recomendamos que guarde esta secção de <strong>ofertas náuticas</strong> nos seus favoritos e a visite regularmente. As nossas promoções são por tempo limitado e esgotam rapidamente.</p><p><strong>Aproveite agora e façse ao mar com a confiança que merece; só na Álvarez!</strong></p>",
                        "fr" => "<p><strong><h3>Offres nautiques : équipez votre bateau avec le meilleur matériel, au meilleur prix</h3></strong> Bienvenue dans la section des <strong>offres nautiques</strong> d'Álvarez, votre port d'attache pour obtenir du matériel maritime de haute qualité avec des remises exclusives. Nous savons que la mer exige une fiabilité maximale, c'est pourquoi nous avons réuni une <strong>sélection imbattable d'offres et de promotions</strong> dans les domaines de l'électronique, de la sécurité, du sauvetage, de l'amarrage, du mouillage et de tous types d'accessoires pour votre bateau. Que vous ayez un bateau à moteur, un voilier, un pneumatique ou un jet ski, vous trouverez ici tout ce dont vous avez besoin pour naviguer en toute sécurité et dans le plus grand confort.</p><p><strong><h3>Produits des meilleures marques et offres actualisées</h3></strong> Sur cette page, la qualité n'est pas nécessairement synonyme de prix élevé. Nous actualisons constamment notre catalogue afin de vous proposer les promotions les plus intéressantes des marques les plus respectées du secteur. Découvrez des réductions sur les produits <strong>Garmin, Raymarine, Lalizas, Slam, Plastimo, Igloo...</strong> entre autres. Notre engagement est de vous donner accès aux équipements les plus avancés et les plus sûrs du marché à des prix imbattables.</p><p><strong><h3>Un passage obligé pour tous les amoureux de la mer</h3></strong> Ne manquez pas l'occasion d'équiper votre bateau avec le matériel dont vous avez besoin pour vos traversées. Nous vous recommandons d'ajouter cette section <strong>d'offres nautiques</strong> à vos favoris et de la consulter régulièrement. Nos promotions sont limitées dans le temps et s'épuisent rapidement.</p><p><strong>Profitez-en dès maintenant et prenez la mer en toute confiance ; uniquement chez Álvarez !</strong></p>",
                        "de" => "<p><strong><h3>Angebote für den Wassersport: Statten Sie Ihr Boot mit dem Besten zum besten Preis aus</h3></strong> Willkommen im Bereich für <strong>Wassersportangebote</strong> von Álvarez, Ihrem sicheren Hafen für hochwertige Schiffsausrüstung mit exklusiven Rabatten. Wir wissen, dass das Meer höchste Zuverlässigkeit erfordert, und deshalb haben wir eine unschlagbare Auswahl an <strong>Angeboten und Aktionen</strong> in den Bereichen Elektronik, Sicherheit, Rettung, Festmachen, Ankern und allen Arten von Zubehör für Ihr Boot zusammengestellt. Egal, ob Sie ein Motorboot, ein Segelboot, ein Schlauchboot oder ein Jetboot haben: Hier finden Sie alles, was Sie brauchen, um sicher und komfortabel zu navigieren.</p><p><strong><h3>Produkte der besten Marken und aktuelle Angebote</h3></strong> Auf dieser Seite muss Qualität nicht teuer sein. Wir aktualisieren unseren Katalog ständig, um Ihnen die attraktivsten Sonderangebote der renommiertesten Marken der Branche anzubieten. Entdecken Sie Rabatte auf Produkte von <strong>Garmin, Raymarine, Lalizas, Slam, Plastimo, Igloo...</strong> und anderen. Wir haben es uns zur Aufgabe gemacht, Ihnen Zugang zu den fortschrittlichsten und sichersten Geräten auf dem Markt zu unschlagbaren Preisen zu bieten.</p><p><strong><h3>Ein Muss für jeden Liebhaber des Meeres</h3></strong> Verpassen Sie nicht die Gelegenheit, Ihr Boot mit der Ausrüstung auszustatten, die Sie für Ihre Reisen benötigen. Wir empfehlen Ihnen, diesen Bereich mit nautischen Angeboten in Ihren Favoriten zu speichern und regelmäßig zu besuchen. Unsere Sonderangebote sind zeitlich begrenzt und schnell vergriffen.</p><p><strong>Nutzen Sie jetzt die Gelegenheit und stechen Sie mit dem Vertrauen in See, das Sie verdienen; nur bei Álvarez!</strong></p>",
                        "it" => "<p><strong><h3>Offerte nautiche: equipaggia la tua imbarcazione con il meglio, al miglior prezzo</h3></strong> Benvenuto nella sezione delle <strong>offerte nautiche</strong> di Álvarez, il tuo porto sicuro per acquistare attrezzature nautiche di alta qualità con sconti esclusivi. Sappiamo che il mare richiede la massima affidabilità, per questo abbiamo raccolto una selezione imbattibile di offerte e promozioni su elettronica, sicurezza, salvataggio, ormeggio, ancoraggio e tutti i tipi di accessori per la tua barca. Non importa se hai un motoscafo, una barca a vela, un gommone o una moto d'acqua: qui troverai tutto ciò che ti serve per navigare in totale sicurezza e comfort.</p><p><strong><h3>Prodotti delle migliori marche e offerte aggiornate</h3></strong> In questa pagina, la qualità non deve necessariamente essere costosa. Aggiorniamo costantemente il nostro catalogo per offrirti le promozioni più interessanti delle marche più rispettate del settore. Scopri gli sconti sui prodotti <strong>Garmin, Raymarine, Lalizas, Slam, Plastimo, Igloo...</strong> tra gli altri. Il nostro impegno è quello di darti accesso alle attrezzature più avanzate e sicure sul mercato a prezzi imbattibili.</p><p><strong><h3>Una tappa obbligata per tutti gli amanti del mare</h3></strong> Non perdere l'occasione di equipaggiare la tua imbarcazione con il materiale necessario per le tue traversate. Ti consigliamo di salvare questa sezione di <strong>offerte nautiche</strong> nei tuoi preferiti e di visitarla regolarmente. Le nostre promozioni sono a tempo limitato e vanno a ruba.</p><p><strong>Approfitta subito e salpa con la sicurezza che meriti; solo da Álvarez!</strong></p>",
                        "en" => "<p><strong><h3>Nautical offers: Equip your boat with the best, at the best price</h3></strong> Welcome to the <strong>nautical offers</strong> section of Álvarez, your safe haven for high-quality marine equipment with exclusive discounts. We know that the sea demands maximum reliability, which is why we have put together an unbeatable selection of <strong>offers and promotions</strong> on electronics, safety, rescue, mooring, anchoring and all kinds of accessories for your boat. Whether you have a motorboat, a sailboat, a dinghy or a jet ski, here you will find everything you need to sail in complete safety and comfort.</p><p><strong><h3>Products from the best brands and updated offers</h3></strong> On this page, quality does not have to be expensive. We constantly update our catalogue to offer you the most attractive promotions from the most respected brands in the sector. Discover discounts on products from <strong>Garmin, Raymarine, Lalizas, Slam, Plastimo, Igloo...</strong> among others. Our commitment is to give you access to the most advanced and safest equipment on the market at unbeatable prices.</p><p><strong><h3>A must-visit for any sea lover</h3></strong> Don't miss the opportunity to equip your boat with the gear you need for your voyages. We recommend that you bookmark this section of <strong>nautical offers</strong> and visit it regularly. Our promotions are for a limited time only and sell out quickly.</p><p><strong>Take advantage now and set sail with the confidence you deserve; only at Álvarez!</strong></p>"
                    ],
                    "imagenes" => [
                        "es" => [
                            ["title" => "Ofertas chalecos salvavidas", "image" => "chalecos.webp", "url" => "/nautica/chalecos_salvavidas"],
                            ["title" => "Ofertas ropa náutica", "image" => "ropa.webp", "url" => "/nautica/ropa_nautica"],
                            ["title" => "Ofertas calzado náutico", "image" => "calzado.webp", "url" => "/nautica/calzado_nautico"],
                            ["title" => "Ofertas de fondeo", "image" => "fondeo.webp", "url" => "/nautica/fondeo"],
                            ["title" => "Ofertas equipos de cubierta", "image" => "equipo-cubierta.webp", "url" => "/nautica/equipo_de_cubierta"],
                            ["title" => "Ofertas para tu confort", "image" => "confort.webp", "url" => "/nautica/confort"],
                        ],
                        "pt" => [
                            ["title" => "Ofertas salva-vidas", "image" => "chalecos.webp", "url" => "/pt/vela/coletes_salvavidas"],
                            ["title" => "Ofertas roupas náuticas", "image" => "ropa.webp", "url" => "/pt/vela/roupas_nauticas"],
                            ["title" => "Ofertas calçado náutico", "image" => "calzado.webp", "url" => "/pt/vela/calcado_nautico"],
                            ["title" => "Ofertas ancoragem", "image" => "fondeo.webp", "url" => "/pt/vela/amarracao_e_ancoragem"],
                            ["title" => "Ofertas equipamento", "image" => "equipo-cubierta.webp", "url" => "/pt/vela/equipamento_de_conves"],
                            ["title" => "Ofertas conforto", "image" => "confort.webp", "url" => "/pt/vela/utilidades_e_conforto"],
                        ],
                        "fr" => [
                            ["title" => "Offres gilets de sauvetage", "image" => "chalecos.webp", "url" => "/fr/nautique/gilets_de_sauvetage"],
                            ["title" => "Offres vêtements", "image" => "ropa.webp", "url" => "/fr/nautique/vetements_marins"],
                            ["title" => "Offres chaussures", "image" => "calzado.webp", "url" => "/fr/nautique/chaussures_bateau"],
                            ["title" => "Offres mouillage", "image" => "fondeo.webp", "url" => "/fr/nautique/ancrage"],
                            ["title" => "Offres matériel de pont", "image" => "equipo-cubierta.webp", "url" => "/fr/nautique/equipements_pour_le_pont"],
                            ["title" => "Offres pour votre confort", "image" => "confort.webp", "url" => "/fr/nautique/confort"],
                        ],
                        "de" => [
                            ["title" => "Rettungswesten Angebote", "image" => "chalecos.webp", "url" => "/de/segeln/rettungswesten"],
                            ["title" => "Bekleidung Angebote", "image" => "ropa.webp", "url" => "/de/segeln/nautische_kleidung"],
                            ["title" => "Nautikschuhe Angebote", "image" => "calzado.webp", "url" => "/de/segeln/nautische_schuhe"],
                            ["title" => "Ankerangebote", "image" => "fondeo.webp", "url" => "/de/segeln/verankerung"],
                            ["title" => "Deck-Ausrüstung Angebote", "image" => "equipo-cubierta.webp", "url" => "/de/segeln/deckausrustung"],
                            ["title" => "Komfort-Angebote", "image" => "confort.webp", "url" => "/de/segeln/komfort"],
                        ],
                        "it" => [
                            ["title" => "Offerte salvagente", "image" => "chalecos.webp", "url" => "/it/vela/chalecos_salvavidas"],
                            ["title" => "Offerte abbigliamento", "image" => "ropa.webp", "url" => "/it/vela/ropa_nautica"],
                            ["title" => "Offerte calzature", "image" => "calzado.webp", "url" => "/it/vela/calzado_nautico"],
                            ["title" => "Offerte ancoraggio", "image" => "fondeo.webp", "url" => "/it/vela/fondeo"],
                            ["title" => "Offerte attrezzature coperta", "image" => "equipo-cubierta.webp", "url" => "/it/vela/equipo_de_cubierta"],
                            ["title" => "Offerte per il tuo comfort", "image" => "confort.webp", "url" => "/it/vela/confort"],
                        ],
                        "en" => [
                            ["title" => "Life Jacket Offers", "image" => "chalecos.webp", "url" => "/en/boating/lifevest"],
                            ["title" => "Nautical Clothing Offers", "image" => "ropa.webp", "url" => "/en/boating/nautical_clothing"],
                            ["title" => "Nautical Footwear Offers", "image" => "calzado.webp", "url" => "/en/boating/nautical_footwear"],
                            ["title" => "Mooring Equipment Offers", "image" => "fondeo.webp", "url" => "/en/boating/anchoring"],
                            ["title" => "Deck Equipment Offers", "image" => "equipo-cubierta.webp", "url" => "/en/boating/deck_equipment"],
                            ["title" => "Comfort Accessories Offers", "image" => "confort.webp", "url" => "/en/boating/comfort"],
                        ],
                    ],
                ];
                break;

            case 9:
                $data = [
                    "deporte" => "esqui",
                    "title" => [
                        "es" => "Oferta especial esquí",
                        "pt" => "Oferta especial esqui",
                        "fr" => "Offre spéciale ski",
                        "de" => "Spezialangebot ski",
                        "it" => "Offerta speciale sci",
                        "en" => "Special Ski Offer",
                    ],
                    "h1" => [
                        "es" => "OFERTAS esquí",
                        "pt" => "PROMOÇÕES esqui",
                        "fr" => "OFFRES DE ski",
                        "de" => "ANGEBOTE ski",
                        "it" => "OFFERTE sci",
                        "en" => "SKI OFFERS",
                    ],
                    "h3" => [
                        "es" => "OFERTAS esquí",
                        "pt" => "PROMOÇÕES esqui",
                        "fr" => "OFFRES DE ski",
                        "de" => "ANGEBOTE ski",
                        "it" => "OFFERTE sci",
                        "en" => "SKI OFFERS",
                    ],
                    "texts" => [
                        "es" => "<p><strong><h3>Ofertas de esquí: Equípate para la nieve con las mejores ofertas</h3></strong> Bienvenido a la sección de <strong>ofertas de esquí</strong> de Álvarez, tu parada obligatoria antes de lanzarte a las pistas. Sabemos que el equipamiento de calidad es fundamental para disfrutar al máximo de la nieve, y por eso, hemos reunido las <strong>mejores ofertas y descuentos</strong> en esquís, botas, fijaciones, cascos, gafas y ropa técnica. Tanto si eres un esquiador experimentado como si estás empezando, aquí encontrarás la oportunidad perfecta para renovar tu equipo sin sacrificar tu presupuesto.</p><p><strong><h3>Productos de las mejores marcas y promociones exclusivas</h3></strong> En esta página, la calidad y el ahorro van de la mano. Actualizamos constantemente nuestra selección para ofrecerte las promociones más atractivas de las marcas más prestigiosas del sector. Descubre descuentos en productos de <strong>Salomon, Atomic, Volkl, Nordica, +8000</strong>; entre otras. Nuestro objetivo es que tengas acceso a los equipos más avanzados y seguros del mercado a precios inigualables.</p><p><strong><h3>Una consulta obligada para todo aficionado a la nieve</h3></strong> No dejes pasar la oportunidad de conseguir ese equipo que mejorará tu rendimiento y seguridad en la montaña. Te recomendamos guardar esta sección de <strong>ofertas de esquí</strong> en tus favoritos y visitarla con regularidad, ya que muchas promociones son por tiempo limitado y se agotan rápidamente.</p><p><strong>Aprovecha ahora y equípate para la próxima temporada. ¡Solo en Álvarez!</strong></p>",
                        "pt" => "<p><strong><h3>Ofertas de esqui: Equipe-se para a neve com as melhores ofertas</h3></strong> Bem-vindo à secção de <strong>ofertas de esqui</strong> da Álvarez, a sua paragem obrigatória antes de se lançar nas pistas. Sabemos que um equipamento de qualidade é fundamental para desfrutar ao máximo da neve e, por isso, reunimos as <strong>melhores ofertas e descontos</strong> em esquis, botas, fixações, capacetes, óculos e roupa técnica. Quer seja um esquiador experiente ou esteja a começar, aqui encontrará a oportunidade perfeita para renovar o seu equipamento sem sacrificar o seu orçamento.</p><p><strong><h3>Produtos das melhores marcas e promoções exclusivas</h3></strong> Nesta página, qualidade e economia andam de mãos dadas. Atualizamos constantemente a nossa seleção para oferecer as promoções mais atraentes das marcas mais prestigiadas do setor. Descubra descontos em produtos da <strong>Salomon, Atomic, Volkl, Nordica, +8000</strong>, entre outras. O nosso objetivo é que tenha acesso aos equipamentos mais avançados e seguros do mercado a preços imbatíveis.</p><p><strong><h3>Uma consulta obrigatória para todos os amantes da neve</h3></strong> Não perca a oportunidade de adquirir aquele equipamento que irá melhorar o seu desempenho e segurança na montanha. Recomendamos que guarde esta secção de <strong>ofertas de esqui</strong> nos seus favoritos e a visite regularmente, pois muitas promoções são por tempo limitado e esgotam rapidamente.</p><p><strong>Aproveite agora e equipe-se para a próxima temporada. Só na Álvarez!</strong></p>",
                        "fr" => "<p><strong><h3>Offres ski : équipez-vous pour la neige avec les meilleures offres</h3></strong> Bienvenue dans la section <strong>offres ski</strong> d'Álvarez, votre étape incontournable avant de dévaler les pistes. Nous savons qu'un équipement de qualité est essentiel pour profiter pleinement de la neige. C'est pourquoi nous avons rassemblé les <strong>meilleures offres et réductions</strong> sur les skis, les chaussures, les fixations, les casques, les lunettes et les vêtements techniques. Que vous soyez un skieur expérimenté ou débutant, vous trouverez ici l'occasion idéale de renouveler votre équipement sans sacrifier votre budget.</p><p><strong><h3>Produits des meilleures marques et promotions exclusives</h3></strong> Sur cette page, qualité et économies vont de pair. Nous actualisons constamment notre sélection pour vous proposer les promotions les plus attractives des marques les plus prestigieuses du secteur. Découvrez des réductions sur les produits <strong>Salomon, Atomic, Volkl, Nordica, +8000</strong>, entre autres. Notre objectif est de vous donner accès aux équipements les plus avancés et les plus sûrs du marché à des prix imbattables.</p><p><strong><h3>Un incontournable pour tous les amateurs de neige</h3></strong> Ne manquez pas l'occasion d'acquérir l'équipement qui améliorera vos performances et votre sécurité en montagne. Nous vous recommandons d'ajouter cette section <strong>d'offres de ski</strong> à vos favoris et de la consulter régulièrement, car de nombreuses promotions sont limitées dans le temps et s'épuisent rapidement.</p><p><strong>Profitez-en dès maintenant et équipez-vous pour la saison prochaine. Uniquement chez Álvarez !</strong></p>",
                        "de" => "<p><strong><h3>Ski-Angebote: Rüsten Sie sich mit den besten Angeboten für den Schnee aus</h3></strong> Willkommen im Bereich <strong>Ski-Angebote</strong> von Álvarez, Ihrem obligatorischen Zwischenstopp, bevor Sie sich auf die Pisten begeben. Wir wissen, dass eine hochwertige Ausrüstung entscheidend ist, um den Schnee in vollen Zügen genießen zu können. Deshalb haben wir die <strong>besten Angebote und Rabatte</strong> für Skier, Skischuhe, Bindungen, Helme, Brillen und Funktionsbekleidung zusammengestellt. Egal, ob Sie ein erfahrener Skifahrer sind oder gerade erst anfangen, hier finden Sie die perfekte Gelegenheit, Ihre Ausrüstung zu erneuern, ohne Ihr Budget zu strapazieren.</p><p><strong><h3>Produkte der besten Marken und exklusive Sonderangebote</h3></strong> Auf dieser Seite gehen Qualität und Sparsamkeit Hand in Hand. Wir aktualisieren ständig unsere Auswahl, um Ihnen die attraktivsten Angebote der renommiertesten Marken der Branche zu bieten. Entdecken Sie Rabatte auf Produkte von <strong>Salomon, Atomic, Volkl, Nordica, +8000</strong> und anderen. Unser Ziel ist es, Ihnen Zugang zu den fortschrittlichsten und sichersten Ausrüstungen auf dem Markt zu unschlagbaren Preisen zu bieten.</p><p><strong><h3>Ein Muss für alle Schneeliebhaber</h3></strong> Verpassen Sie nicht die Gelegenheit, sich die Ausrüstung zu sichern, die Ihre Leistung und Sicherheit in den Bergen verbessert. Wir empfehlen Ihnen, diesen Bereich mit den <strong>Ski-Angeboten</strong> in Ihren Favoriten zu speichern und regelmäßig zu besuchen, da viele Angebote nur für begrenzte Zeit gültig sind und schnell ausverkauft sind.</p><p><strong>Nutzen Sie jetzt die Gelegenheit und rüsten Sie sich für die nächste Saison aus. Nur bei Álvarez!</strong></p>",
                        "it" => "<p><strong><h3>Offerte sci: attrezzati per la neve con le migliori offerte</h3></strong> Benvenuto nella sezione <strong>offerte sci</strong> di Álvarez, la tua tappa obbligatoria prima di lanciarti sulle piste. Sappiamo che un'attrezzatura di qualità è fondamentale per godersi al massimo la neve, ed è per questo che abbiamo raccolto le migliori offerte e sconti su sci, scarponi, attacchi, caschi, occhiali e abbigliamento tecnico. Che tu sia uno sciatore esperto o alle prime armi, qui troverai l'occasione perfetta per rinnovare la tua attrezzatura senza sacrificare il tuo budget.</p><p><strong><h3>Prodotti delle migliori marche e promozioni esclusive</h3></strong> In questa pagina, qualità e risparmio vanno di pari passo. Aggiorniamo costantemente la nostra selezione per offrirti le promozioni più interessanti delle marche più prestigiose del settore. Scopri gli sconti sui prodotti <strong>Salomon, Atomic, Volkl, Nordica, +8000</strong> e molti altri. Il nostro obiettivo è quello di offrirti l'attrezzatura più avanzata e sicura sul mercato a prezzi imbattibili.</p><p><strong><h3>Una tappa obbligata per tutti gli appassionati della neve</h3></strong> Non perdere l'occasione di acquistare l'attrezzatura che migliorerà le tue prestazioni e la tua sicurezza in montagna. Ti consigliamo di salvare questa sezione di <strong>offerte sci</strong> tra i tuoi preferiti e di visitarla regolarmente, poiché molte promozioni sono a tempo limitato e vanno a ruba.</p><p><strong>Approfitta subito e attrezzati per la prossima stagione. Solo da Álvarez!</strong></p>",
                        "en" => "<p><strong><h3>Ski deals: Get kitted out for the snow with the best deals</h3></strong> Welcome to the <strong>ski deals</strong> section at Álvarez, your must-visit stop before hitting the slopes. We know that quality equipment is essential to enjoy the snow to the fullest, which is why we have gathered the <strong>best deals and discounts</strong> on skis, boots, bindings, helmets, goggles and technical clothing. Whether you are an experienced skier or just starting out, here you will find the perfect opportunity to renew your equipment without breaking the bank.</p><p><strong><h3>Products from the best brands and exclusive promotions</h3></strong> On this page, quality and savings go hand in hand. We constantly update our selection to offer you the most attractive promotions from the most prestigious brands in the sector. Discover discounts on products from <strong>Salomon, Atomic, Volkl, Nordica, +8000</strong>, and more. Our goal is to give you access to the most advanced and safest equipment on the market at unbeatable prices.</p><p><strong><h3>A must-see for all snow enthusiasts</h3></strong> Don't miss out on the opportunity to get the equipment that will improve your performance and safety on the mountain. We recommend that you bookmark this <strong>ski offers</strong> section and visit it regularly, as many promotions are for a limited time only and sell out quickly.</p><p><strong>Take advantage now and get equipped for next season. Only at Álvarez!</strong></p>"
                    ],
                    "imagenes" => [
                        "es" => [
                            ["title" => "Ofertas de esquís + fijaciones", "image" => "esquis.webp", "url" => "/esqui/esquis_fijaciones"],
                            ["title" => "Ofertas botas de esquí", "image" => "botas.webp", "url" => "/esqui/botas_de_esqui"],
                            ["title" => "Ofertas cascos de esquí", "image" => "casco.webp", "url" => "/esqui/cascos_esqui"],
                            ["title" => "Ofertas bastones de esquí", "image" => "palos.webp", "url" => "/esqui/bastones_de_esqui"],
                            ["title" => "Ofertas gafas de esquí", "image" => "gafas.webp", "url" => "/esqui/gafas_y_mascaras_de_esqui"],
                            ["title" => "Ofertas ropa de esquí", "image" => "ropa.webp", "url" => "/esqui/ropa_hombre_esqui"],
                        ],
                        "pt" => [
                            ["title" => "Ofertas esquis + fixações", "image" => "esquis.webp", "url" => "/pt/esqui/esquis_fixacoes"],
                            ["title" => "Ofertas botas esqui", "image" => "botas.webp", "url" => "/pt/esqui/botas_de_esqui"],
                            ["title" => "Ofertas capacetes de esqui", "image" => "casco.webp", "url" => "/pt/esqui/capacetes_de_esqui"],
                            ["title" => "Ofertas bastões de esqui", "image" => "palos.webp", "url" => "/pt/esqui/bastoes_de_esqui"],
                            ["title" => "Ofertas máscaras de esqui", "image" => "gafas.webp", "url" => "/pt/esqui/mascaras_e_oculos_de_esqui"],
                            ["title" => "Ofertas roupa de esqui", "image" => "ropa.webp", "url" => "/pt/esqui/roupa_homem_esqui"],
                        ],
                        "fr" => [
                            ["title" => "Offres skis + fixations", "image" => "esquis.webp", "url" => "/fr/ski/skis_fixations"],
                            ["title" => "Offres chaussures de ski", "image" => "botas.webp", "url" => "/fr/ski/bottes_de_ski"],
                            ["title" => "Offres casques de ski", "image" => "casco.webp", "url" => "/fr/ski/casques_de_ski"],
                            ["title" => "Offres bâtons de ski", "image" => "palos.webp", "url" => "/fr/ski/batons_de_ski"],
                            ["title" => "Offres lunettes de ski", "image" => "gafas.webp", "url" => "/fr/ski/lunettes_et_masques_de_ski"],
                            ["title" => "Offres vêtements de ski", "image" => "ropa.webp", "url" => "/fr/ski/vetements_de_ski_pour_homme"],
                        ],
                        "de" => [
                            ["title" => "Ski + Bindungs Angebote", "image" => "esquis.webp", "url" => "/de/skifahren/ski_fixings"],
                            ["title" => "Skischuh Angebote", "image" => "botas.webp", "url" => "/de/skifahren/skischuhe"],
                            ["title" => "Skihelme Angebote", "image" => "casco.webp", "url" => "/de/skifahren/skihelme"],
                            ["title" => "Skistöcke Angebote", "image" => "palos.webp", "url" => "/de/skifahren/skistocke"],
                            ["title" => "Skibrillen Angebote", "image" => "gafas.webp", "url" => "/de/skifahren/skibrille_und_masken"],
                            ["title" => "Skibekleidung Angebote", "image" => "ropa.webp", "url" => "/de/skifahren/ski_herrenbekleidung"],
                        ],
                        "it" => [
                            ["title" => "Offerte sci + attacchi", "image" => "esquis.webp", "url" => "/it/sci/esquis_fijaciones"],
                            ["title" => "Offerte scarponi da sci", "image" => "botas.webp", "url" => "/it/sci/botas_de_esqui"],
                            ["title" => "Offerte caschi da sci", "image" => "casco.webp", "url" => "/it/sci/cascos_esqui"],
                            ["title" => "Offerte bastoni da sci", "image" => "palos.webp", "url" => "/it/sci/bastones_de_esqui"],
                            ["title" => "Offerte occhiali da sci", "image" => "gafas.webp", "url" => "/it/sci/gafas_y_mascaras_de_esqui"],
                            ["title" => "Offerte abbigliamento", "image" => "ropa.webp", "url" => "/it/sci/ropa_hombre_esqui"],
                        ],
                        "en" => [
                            ["title" => "Skis + Bindings Offers", "image" => "esquis.webp", "url" => "/en/skiing/skis_fixings"],
                            ["title" => "Ski Boots Offers", "image" => "botas.webp", "url" => "/en/skiing/ski_boots"],
                            ["title" => "Ski Helmets Offers", "image" => "casco.webp", "url" => "/en/skiing/ski_helmets"],
                            ["title" => "Ski Poles Offers", "image" => "palos.webp", "url" => "/en/skiing/ski_poles"],
                            ["title" => "Ski Goggles Offers", "image" => "gafas.webp", "url" => "/en/skiing/ski_goggles_and_masks"],
                            ["title" => "Ski Clothing Offers", "image" => "ropa.webp", "url" => "/en/skiing/ski_mens_clothing"],
                        ],
                    ]
                ];
                break;

            case 10:
                $data = [
                    "deporte" => "padel",
                    "title" => [
                        "es" => "Oferta especial pádel",
                        "pt" => "Oferta especial padel",
                        "fr" => "Offre spéciale padel",
                        "de" => "Spezialangebot padel",
                        "it" => "Offerta speciale padel",
                        "en" => "Special Paddle Offer",
                    ],
                    "h1" => [
                        "es" => "OFERTAS pádel",
                        "pt" => "PROMOÇÕES padel",
                        "fr" => "OFFRES DE padel",
                        "de" => "ANGEBOTE padel",
                        "it" => "OFFERTE padel",
                        "en" => "Padel OFFERS",
                    ],
                    "h3" => [
                        "es" => "OFERTAS pádel",
                        "pt" => "PROMOÇÕES padel",
                        "fr" => "OFFRES DE padel",
                        "de" => "ANGEBOTE padel",
                        "it" => "OFFERTE padel",
                        "en" => "Padel OFFERS",
                    ],
                    "texts" => [
                        "es" => "<p><strong><h3>Ofertas de pádel: Equípate con el mejor material, al mejor precio</h3></strong> Bienvenido a la sección de <strong>ofertas de pádel</strong> de Álvarez, tu destino online para conseguir el mejor material con descuentos exclusivos. Sabemos que el pádel es un deporte de precisión, potencia y agilidad, y contar con el equipo adecuado puede marcar la diferencia en cada partido. Por eso, hemos seleccionado las mejores ofertas y promociones en palas de pádel, zapatillas, ropa, pelotas y mochilas.</p><p><strong><h3>Las mejores marcas de pádel a precios de oferta</h3></strong> En esta página, la calidad y el ahorro van de la mano. Actualizamos constantemente nuestro catálogo para ofrecerte las promociones más atractivas de las marcas más respetadas del sector. Descubre descuentos en productos de <strong>Bullpadel, Adidas, Dunlop, Star Vie</strong>, entre otras. Nuestro objetivo es que tengas acceso a palas de última generación, zapatillas con la mejor amortiguación y toda la ropa técnica necesaria para rendir al máximo nivel.</p><p><strong><h3>Visita obligada para cualquier aficionado al pádel</h3></strong> No dejes pasar la oportunidad de conseguir ese equipo que mejorará tu juego y te hará sentir más cómodo en la pista. Te recomendamos guardar esta sección de <strong>ofertas de pádel</strong> en tus favoritos y visitarla con regularidad, ya que nuestras promociones son por tiempo limitado y las mejores oportunidades desaparecen rápido.</p><p><strong>Aprovecha ahora y equípate para tu próximo partido; ¡Solo en Álvarez!</strong></p>",
                        "pt" => "<p><strong><h3>Ofertas de padel: Equipe-se com o melhor material, ao melhor preço</h3></strong> Bem-vindo à secção de <strong>ofertas de padel</strong> da Álvarez, o seu destino online para obter o melhor material com descontos exclusivos. Sabemos que o padel é um desporto de precisão, potência e agilidade, e ter o equipamento adequado pode fazer a diferença em cada partida. Por isso, selecionamos as melhores ofertas e promoções em raquetes de padel, tênis, roupas, bolas e mochilas.</p><p><strong><h3>As melhores marcas de padel a preços promocionais</h3></strong> Nesta página, qualidade e economia andam de mãos dadas. Atualizamos constantemente o nosso catálogo para lhe oferecer as promoções mais atraentes das marcas mais respeitadas do setor. Descubra descontos em produtos da <strong>Bullpadel, Adidas, Dunlop, Star Vie</strong>, entre outras. O nosso objetivo é que tenha acesso a raquetes de última geração, ténis com o melhor amortecimento e todo o vestuário técnico necessário para ter o máximo desempenho.</p><p><strong><h3>Visita obrigatória para qualquer fã de padel</h3></strong> Não perca a oportunidade de adquirir aquele equipamento que irá melhorar o seu jogo e fazê-lo sentir-se mais confortável na pista. Recomendamos que guarde esta secção de <strong>ofertas de padel</strong> nos seus favoritos e a visite regularmente, pois as nossas promoções são por tempo limitado e as melhores oportunidades desaparecem rapidamente.</p><p><strong>Aproveite agora e equipe-se para o seu próximo jogo. Só na Álvarez!</strong></p>",
                        "fr" => "<p><strong><h3>Offres de padel : équipez-vous du meilleur matériel au meilleur prix</h3></strong> Bienvenue dans la section des <strong>offres de padel</strong> d'Álvarez, votre destination en ligne pour obtenir le meilleur matériel avec des réductions exclusives. Nous savons que le padel est un sport de précision, de puissance et d'agilité, et que disposer du matériel adéquat peut faire la différence dans chaque match. C'est pourquoi nous avons sélectionné les meilleures offres et promotions sur les raquettes, chaussures, vêtements, balles et sacs à dos de padel.</p><p><strong><h3>Les meilleures marques de padel à prix promotionnels</h3></strong> Sur cette page, qualité et économies vont de pair. Nous mettons constamment à jour notre catalogue afin de vous proposer les promotions les plus intéressantes des marques les plus respectées du secteur. Découvrez des réductions sur les produits <strong>Bullpadel, Adidas, Dunlop, Star Vie</strong>, entre autres. Notre objectif est de vous donner accès à des raquettes de dernière génération, des chaussures avec le meilleur amorti et tous les vêtements techniques nécessaires pour performer au plus haut niveau.</p><p><strong><h3>Une visite incontournable pour tout amateur de padel</h3></strong> Ne manquez pas l'occasion d'acquérir l'équipement qui améliorera votre jeu et vous permettra de vous sentir plus à l'aise sur le terrain. Nous vous recommandons d'ajouter cette section <strong>d'offres de padel</strong> à vos favoris et de la consulter régulièrement, car nos promotions sont limitées dans le temps et les meilleures occasions disparaissent rapidement.</p><p><strong>Profitez-en dès maintenant et équipez-vous pour votre prochain match. Uniquement chez Álvarez !</strong></p>",
                        "de" => "<p><strong><h3>Padel-Angebote: Rüsten Sie sich mit dem besten Material zum besten Preis aus</h3></strong> Willkommen im Bereich <strong></strong>Padel-Angebote</strong> von Álvarez, Ihrer Online-Adresse für das beste Material mit exklusiven Rabatten. Wir wissen, dass Padel ein Sport ist, bei dem es auf Präzision, Kraft und Beweglichkeit ankommt, und dass die richtige Ausrüstung in jedem Spiel den Unterschied ausmachen kann. Deshalb haben wir die besten Angebote und Aktionen für Padel-Schläger, Schuhe, Kleidung, Bälle und Rucksäcke ausgewählt.</p><p><strong><h3>Die besten Padel-Marken zu Sonderpreisen</h3></strong> Auf dieser Seite gehen Qualität und Sparsamkeit Hand in Hand. Wir aktualisieren unseren Katalog ständig, um Ihnen die attraktivsten Sonderangebote der renommiertesten Marken der Branche anzubieten. Entdecken Sie Rabatte auf Produkte von <strong>Bullpadel, Adidas, Dunlop, Star Vie</strong> und anderen. Unser Ziel ist es, Ihnen Zugang zu Schlägern der neuesten Generation, Schuhen mit der besten Dämpfung und der gesamten technischen Bekleidung zu verschaffen, die Sie benötigen, um Höchstleistungen zu erbringen.</p><p><strong><h3>Ein Muss für jeden Padel-Fan</h3></strong> Verpassen Sie nicht die Gelegenheit, sich die Ausrüstung zu sichern, die Ihr Spiel verbessert und Ihnen mehr Komfort auf dem Platz verschafft. Wir empfehlen Ihnen, diesen Bereich mit den Padel-Angeboten in Ihren Favoriten zu speichern und regelmäßig zu besuchen, da unsere Sonderangebote zeitlich begrenzt sind und die besten Gelegenheiten schnell vergriffen sind.</p><p><strong>Nutzen Sie jetzt die Gelegenheit und rüsten Sie sich für Ihr nächstes Spiel aus. Nur bei Álvarez!</strong></p>",
                        "it" => "<p><strong><h3>Offerte padel: equipaggiati con il miglior materiale al miglior prezzo</h3></strong> Benvenuto nella sezione delle <strong>offerte padel</strong> di Álvarez, il tuo negozio online di riferimento per acquistare il miglior materiale con sconti esclusivi. Sappiamo che il padel è uno sport di precisione, potenza e agilità, e avere l'attrezzatura giusta può fare la differenza in ogni partita. Per questo motivo, abbiamo selezionato le migliori offerte e promozioni su racchette da padel, scarpe, abbigliamento, palline e zaini.</p><p><strong><h3>Le migliori marche di padel a prezzi scontati</h3></strong> In questa pagina, qualità e risparmio vanno di pari passo. Aggiorniamo costantemente il nostro catalogo per offrirti le promozioni più interessanti dei marchi più rispettati del settore. Scopri gli sconti sui prodotti <strong>Bullpadel, Adidas, Dunlop, Star Vie</strong> e altri ancora. Il nostro obiettivo è quello di darti accesso a racchette di ultima generazione, scarpe con la migliore ammortizzazione e tutto l'abbigliamento tecnico necessario per dare il massimo.</p><p><strong><h3>Una tappa obbligata per ogni appassionato di padel</h3></strong> Non perdere l'occasione di acquistare l'attrezzatura che migliorerà il tuo gioco e ti farà sentire più a tuo agio in campo. Ti consigliamo di salvare questa sezione di <strong>offerte di padel nei tuoi preferiti e di visitarla regolarmente, poiché le nostre promozioni sono a tempo limitato e le migliori opportunità scompaiono rapidamente.</p><p><strong>Approfitta ora e attrezzati per la tua prossima partita. Solo da Álvarez!</strong></p>",
                        "en" => "<p><strong><h3>Padel offers: Get the best equipment at the best price</h3></strong> Welcome to the <strong>padel offers</strong> section at Álvarez, your online destination for the best equipment with exclusive discounts. We know that padel is a sport of precision, power and agility, and having the right equipment can make all the difference in every match. That's why we've selected the best offers and promotions on padel rackets, shoes, clothing, balls and backpacks.</p><p><strong><h3>The best padel brands at bargain prices</h3></strong> On this page, quality and savings go hand in hand. We constantly update our catalogue to offer you the most attractive promotions from the most respected brands in the sector. Discover discounts on products from <strong>Bullpadel, Adidas, Dunlop, Star Vie</strong>, among others. Our goal is to give you access to the latest generation of rackets, shoes with the best cushioning and all the technical clothing you need to perform at the highest level.</p><p><strong><h3>A must-visit for any padel fan</h3></strong> Don't miss out on the opportunity to get the equipment that will improve your game and make you feel more comfortable on the court. We recommend that you bookmark this <strong>padel offers</strong> section and visit it regularly, as our promotions are for a limited time only and the best opportunities disappear quickly.</p><p><strong>Take advantage now and get equipped for your next match. Only at Álvarez!</strong></p>"
                    ],
                    "imagenes" => [
                        "es" => [
                            ["title" => "Ofertas palas de pádel", "image" => "palas.webp", "url" => "/padel/palas_de_padel"],
                            ["title" => "Ofertas paleteros", "image" => "paletero.webp", "url" => "/padel/paleteros"],
                            ["title" => "Ofertas pelotas de pádel", "image" => "pelotas.webp", "url" => "/padel/pelotas"],
                            ["title" => "Ofertas ropa de pádel", "image" => "ropa.webp", "url" => "/padel/ropa"],
                            ["title" => "Ofertas zapatillas de pádel", "image" => "zapatillas.webp", "url" => "/padel/zapatillas_de_padel"],
                            ["title" => "1", "image" => "relleno-es.webp", "url" => ""],
                        ],
                        "pt" => [
                            ["title" => "Ofertas raquetes de padel", "image" => "palas.webp", "url" => "/pt/padel/raquetes_de_padel"],
                            ["title" => "Ofertas sacos de raquetes", "image" => "paletero.webp", "url" => "/pt/padel/sacos_de_raquetes"],
                            ["title" => "Ofertas bolas de padel", "image" => "pelotas.webp", "url" => "/pt/padel/bolas"],
                            ["title" => "Ofertas roupa de padel", "image" => "ropa.webp", "url" => "/pt/padel/roupa"],
                            ["title" => "Ofertas sapatilhas", "image" => "zapatillas.webp", "url" => "/pt/padel/sapatilhas_de_padel"],
                            ["title" => "1", "image" => "relleno-pt.webp", "url" => ""],
                        ],
                        "fr" => [
                            ["title" => "Offres raquettes de padel", "image" => "palas.webp", "url" => "/fr/padle/raquettes_de_padel"],
                            ["title" => "Offres sacs de padel", "image" => "paletero.webp", "url" => "/fr/padle/sacs_de_padel"],
                            ["title" => "Offres balles de padel", "image" => "pelotas.webp", "url" => "/fr/padle/balles"],
                            ["title" => "Offres vêtements de padel", "image" => "ropa.webp", "url" => "/fr/padle/vetements"],
                            ["title" => "Offres chaussures", "image" => "zapatillas.webp", "url" => "/fr/padle/chaussures_de_padel"],
                            ["title" => "1", "image" => "relleno-fr.webp", "url" => ""],
                        ],
                        "de" => [
                            ["title" => "Padelschläger Angebote", "image" => "palas.webp", "url" => "/de/padel/paddelschaufeln"],
                            ["title" => "Padel-Taschen Angebote", "image" => "paletero.webp", "url" => "/de/padel/paleteros"],
                            ["title" => "Padel-Ball Angebote", "image" => "pelotas.webp", "url" => "/de/padel/balle"],
                            ["title" => "Bekleidung Angebote", "image" => "ropa.webp", "url" => "/de/padel/kleidung"],
                            ["title" => "Padel-Schuh Angebote", "image" => "zapatillas.webp", "url" => "/de/padel/paddelschuhe"],
                            ["title" => "1", "image" => "relleno-de.webp", "url" => ""],
                        ],
                        "it" => [
                            ["title" => "Offerte racchette da padel", "image" => "palas.webp", "url" => "/it/padel/palas_de_padel"],
                            ["title" => "Offerte borse da padel", "image" => "paletero.webp", "url" => "/it/padel/paleteros"],
                            ["title" => "Offerte palline da padel", "image" => "pelotas.webp", "url" => "/it/padel/pelotas"],
                            ["title" => "Offerte abbigliamento", "image" => "ropa.webp", "url" => "/it/padel/ropa"],
                            ["title" => "Offerte scarpe da padel", "image" => "zapatillas.webp", "url" => "/it/padel/zapatillas_de_padel"],
                            ["title" => "1", "image" => "relleno-it.webp", "url" => ""],
                        ],
                        "en" => [
                            ["title" => "Paddle Racket Offers", "image" => "palas.webp", "url" => "/en/padel/paddle_shovels"],
                            ["title" => "Paddle Bag Offers", "image" => "paletero.webp", "url" => "/en/padel/paleteros"],
                            ["title" => "Paddle Ball Offers", "image" => "pelotas.webp", "url" => "/en/padel/balls"],
                            ["title" => "Paddle Clothing Offers", "image" => "ropa.webp", "url" => "/en/padel/clothing"],
                            ["title" => "Paddle Shoes Offers", "image" => "zapatillas.webp", "url" => "/en/padel/paddle_shoes"],
                            ["title" => "1", "image" => "relleno-en.webp", "url" => ""],
                        ],
                    ],
                ];
                break;
        }
        return $data;
    }


    public function getRelevantProducts($id_deporte)
    {
        $language_id = (int)$this->context->language->id;
        $iso_code = $this->context->language->iso_code;
        $products = [];

        $data = Db::getInstance()->executeS("SELECT aapd.id_product  FROM " . _DB_PREFIX_ . "alsernet_productos_destacados aapd WHERE aapd.id_category = " . $id_deporte . " ORDER BY aapd.`position`");

        foreach ($data as $prodid) {
            $product = new Product((int)$prodid['id_product']);
            $url = "/$iso_code/" . $product->id . '-' . $product->link_rewrite[$language_id];
            $images = $product->getImages($language_id);
            $image = $this->context->shop->getBaseURL() . $images[0]['id_image'] . '-home_default/' . $product->link_rewrite[$language_id] . '.jpg';
            $products[] = ["url" => $url, "image" => $image, "title" => $product->name[$language_id]];
        }

        if (isset($products[2])) {
            $products[2]['title'] = "0";
        }

        return $products;
    }
}