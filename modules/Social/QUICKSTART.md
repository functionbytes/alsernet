# 🚀 Social Module - Quick Start Guide

¡Bienvenido al módulo Social! Esta guía te ayudará a empezar en 5 minutos.

## ✅ Estado Actual

El módulo Social está **100% configurado y listo para usar** con:

- ✅ **Datos de demostración cargados**
  - 4 cuentas sociales (Facebook, Instagram, Twitter, LinkedIn)
  - 3 campañas activas
  - 5 posts de ejemplo (publicado, programado, borrador, fallido)
  - 3 templates reutilizables
  - 3 grupos de hashtags

- ✅ **Permisos configurados**
  - 40+ permisos granulares
  - 5 roles predefinidos

- ✅ **Funcionalidades disponibles**
  - Publicación multi-canal
  - Generación de contenido con IA
  - Traducción a 11 idiomas
  - Análitics y reportes
  - Video processing y watermarks

## 🎯 Acceso Rápido

### 1. Acceder al Módulo

```
URL: http://tu-dominio/admin/social
```

### 2. Explorar los Datos Demo

**Ver todas las publicaciones:**
```
http://tu-dominio/admin/social/publishing
```

**Ver calendario de publicaciones:**
```
http://tu-dominio/admin/social/publishing/calendar
```

**Ver campañas:**
```
http://tu-dominio/admin/social/campaigns
```

**Ver analytics:**
```
http://tu-dominio/admin/social/analytics
```

## 📝 Crear tu Primera Publicación

### Opción 1: Desde la UI

1. Ve a `/admin/social/publishing/create`
2. Selecciona una cuenta social
3. Escribe tu contenido
4. (Opcional) Usa los botones de IA:
   - **Generar** - Crear contenido desde cero
   - **Hashtags** - Sugerir hashtags relevantes
   - **Mejorar** - Optimizar contenido existente
   - **Variaciones** - Crear múltiples versiones
5. Programa o publica

### Opción 2: Desde Código

```php
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Enums\PostStatus;

// Obtener cuenta social
$account = SocialAccount::where('network', 'facebook')->first();

// Crear post
$post = Post::create([
    'account_id' => auth()->user()->account_id,
    'social_account_id' => $account->id,
    'content' => '¡Hola mundo desde el módulo Social! 🎉',
    'status' => PostStatus::DRAFT,
    'created_by' => auth()->id(),
]);

// Programar para mañana a las 10 AM
$post->update([
    'scheduled_at' => now()->addDay()->setTime(10, 0),
    'status' => PostStatus::SCHEDULED,
]);
```

## 🤖 Usar IA para Generar Contenido

### Desde la UI
1. Clic en botón "🤖 Generar con IA"
2. Ingresa el tema
3. Selecciona tono (profesional, casual, amigable, formal, humorístico)
4. ¡Listo! El contenido se genera automáticamente

### Desde Código

```php
use Modules\Social\Services\AIContentGenerator;

$generator = app(AIContentGenerator::class);

// Generar contenido
$content = $generator->generateContent(
    topic: 'Lanzamiento de nuevo producto',
    tone: 'professional',
    maxLength: 280
);

// Sugerir hashtags
$hashtags = $generator->suggestHashtags($content);

// Mejorar contenido
$better = $generator->improveContent($content, 'casual');

// Crear variaciones para A/B testing
$variations = $generator->generateVariations($content, 5);
```

## 🌍 Traducir Contenido

```php
use Modules\Social\Services\TranslationService;

$translator = app(TranslationService::class);

// Idiomas disponibles
$languages = $translator->getAvailableLanguages();
// ['es', 'en', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh-CN', 'ar']

// Traducir post
$translations = $translator->translatePost($post, ['en', 'fr', 'de']);

// O usa Job para background processing
use Modules\Social\Jobs\TranslatePostJob;
TranslatePostJob::dispatch($post, ['en', 'fr']);
```

## 📊 Exportar Reportes

### Excel
```
GET /admin/social/export/posts/excel
GET /admin/social/export/posts/excel?status=published&date_from=2025-01-01
```

### PDF
```
GET /admin/social/export/posts/pdf
GET /admin/social/export/analytics/pdf
```

## ⚙️ Configuración Opcional

### 1. Configurar API de OpenAI (para IA)

Edita tu archivo `.env`:

```env
OPENAI_API_KEY=sk-tu-api-key-aqui
OPENAI_ORGANIZATION=org-tu-organizacion
```

### 2. Configurar Google Translate (para traducción)

```env
GOOGLE_TRANSLATE_API_KEY=tu-api-key-google
```

### 3. Configurar Redes Sociales

Para publicar realmente en las redes sociales, configura:

```env
# Facebook/Instagram
FACEBOOK_APP_ID=tu-app-id
FACEBOOK_APP_SECRET=tu-app-secret

# Twitter/X
TWITTER_API_KEY=tu-api-key
TWITTER_API_SECRET=tu-api-secret
TWITTER_ACCESS_TOKEN=tu-access-token
TWITTER_ACCESS_SECRET=tu-access-secret

# LinkedIn
LINKEDIN_CLIENT_ID=tu-client-id
LINKEDIN_CLIENT_SECRET=tu-client-secret
```

### 4. Iniciar Queue Worker (para procesamiento en background)

```bash
php artisan queue:work
```

Esto procesará:
- Traducciones automáticas
- Procesamiento de videos
- Aplicación de marcas de agua
- Generación de contenido con IA
- Publicación programada

## 🎨 Personalizar

### Cambiar Colors de Campañas

```php
use Modules\Social\Models\Campaign;

$campaign = Campaign::find(1);
$campaign->update(['color' => '#FF5733']);
```

### Crear Plantillas Personalizadas

```php
use Modules\Social\Models\Template;

Template::create([
    'account_id' => auth()->user()->account_id,
    'name' => 'Promoción Semanal',
    'content' => "🔥 ¡Oferta de la Semana! 🔥\n\n{{descripcion}}\n\n{{hashtags}}",
    'category' => 'Promociones',
]);
```

### Grupos de Hashtags

```php
use Modules\Social\Models\HashtagGroup;

HashtagGroup::create([
    'account_id' => auth()->user()->account_id,
    'name' => 'Tecnología',
    'hashtags' => json_encode([
        '#Tech', '#Innovation', '#AI', '#MachineLearning', '#Cloud'
    ]),
    'category' => 'Technology',
]);
```

## 🔍 Búsqueda

El módulo incluye búsqueda full-text con Laravel Scout:

```php
use Modules\Social\Models\Post;

// Buscar posts
$results = Post::search('Laravel framework')->get();

// Buscar con filtros
$results = Post::search('marketing')
    ->where('status', 'published')
    ->get();
```

Para indexar todos los posts existentes:

```bash
php artisan scout:import "Modules\Social\Models\Post"
```

## 📈 Analytics

### Ver Métricas

```php
use Modules\Social\Models\Post;

$post = Post::find(1);

// Métricas individuales
echo $post->likes_count;      // Likes
echo $post->comments_count;   // Comentarios
echo $post->shares_count;     // Compartidos

// Engagement total
echo $post->getTotalEngagement();

// Top performing posts
$topPosts = Post::where('status', 'published')
    ->orderByRaw('(likes_count + comments_count + shares_count) DESC')
    ->take(10)
    ->get();
```

## 🎯 A/B Testing

```php
use Modules\Social\Models\AbTest;
use Modules\Social\Models\Post;

// Crear variante A
$postA = Post::create([...]);

// Crear variante B
$postB = Post::create([...]);

// Iniciar A/B test
$test = AbTest::create([
    'account_id' => auth()->user()->account_id,
    'variant_a_id' => $postA->id,
    'variant_b_id' => $postB->id,
    'duration_days' => 7,
    'status' => 'running',
]);

// Después de 7 días, determinar ganador
$winner = $test->determineWinner(); // Retorna el post con mejor engagement
```

## ⚡ Tips Pro

### 1. Usar Templates para Velocidad
En lugar de escribir desde cero, usa templates:
```
/admin/social/templates → Aplicar template → Personalizar → Publicar
```

### 2. Programación en Lote
Crea múltiples posts y prográmalos para toda la semana usando el calendario.

### 3. Reutilizar Hashtags
Crea grupos de hashtags por categoría y aplícalos con un clic.

### 4. Importación Masiva
Usa el bulk importer para cargar múltiples posts desde Excel:
```
/admin/social/bulk-import/create
```

### 5. QR Codes para Campañas
Genera QR codes trackeable para campañas offline:

```php
use Modules\Social\Services\QRCodeService;

$qr = app(QRCodeService::class)->generateForCampaign($campaign);
echo $qr->qr_image_url; // URL de la imagen del QR
echo $qr->scans_count;  // Número de escaneos
```

## 🆘 Ayuda

### Problemas Comunes

**"No puedo ver el módulo Social"**
- Verifica que tengas el permiso `view-posts`
- Asegúrate de estar autenticado

**"Los posts programados no se publican"**
- Inicia el queue worker: `php artisan queue:work`

**"La IA no funciona"**
- Verifica que `OPENAI_API_KEY` esté configurado en `.env`
- Revisa los logs: `php artisan tail`

**"Error al traducir"**
- Verifica `GOOGLE_TRANSLATE_API_KEY` en `.env`
- Confirma que la API está habilitada en Google Cloud Console

### Logs

Ver logs en tiempo real:
```bash
php artisan tail
```

Ver logs de trabajos en cola:
```bash
php artisan queue:failed
php artisan queue:retry {id}
```

## 📚 Más Información

- **README completo**: `Modules/Social/README.md`
- **Documentación de tests**: `tests/Feature/Modules/Social/`
- **Configuración**: `config/social.php`

## 🎉 ¡Listo!

Ya estás listo para usar el módulo Social. Explora, experimenta y disfruta de todas las funcionalidades enterprise-grade disponibles.

**¿Necesitas ayuda?** Revisa el README.md o contacta al equipo de soporte.

---

Made with ❤️ by the Channels Team
