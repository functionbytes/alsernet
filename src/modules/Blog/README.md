# Blog

> Modulo de blog con posts, categorias y tags

## Proposito

Gestiona publicaciones de blog con soporte multilingue via DeepL, versionado de contenido, moderacion de comentarios y publicacion controlada. Permite a autores redactar, editores revisar y administradores publicar entradas con categorias, tags y traducciones automaticas.

## Componentes principales

- **Modelos**:
  - `BlogPost` — publicacion principal con slug, estado, autor, categoria y contenido
  - `BlogCategory` / `BlogCategoryTranslation` — categorias con soporte de traduccion
  - `BlogTag` / `BlogTagTranslation` — etiquetas con soporte de traduccion
  - `BlogPostTranslation` — traducciones por locale de cada post
  - `BlogPostComment` — comentarios con moderacion
  - `BlogPostVersion` — historial de versiones del contenido (permite restaurar)
  - `BlogGlossaryTerm` — terminos del glosario usados en traduccion automatica

- **Rutas principales**:
  - `GET /panel/blog/posts` — listado de posts (panel admin)
  - `GET /panel/blog/posts/create` — formulario de creacion
  - `POST /panel/blog/posts/{post}/publish` — publicar post
  - `POST /panel/blog/posts/{post}/translate` — traducir con DeepL
  - `GET /panel/blog/posts/{post}/versions` — historial de versiones
  - `GET /panel/blog/translations` — dashboard de traducciones
  - `GET /panel/blog/categories` — gestion de categorias
  - `GET /panel/blog/tags` — gestion de etiquetas
  - `GET /blog` / `GET /blog/{slug}` — vistas publicas del blog

- **Servicios**:
  - `BlogPostService` — creacion, actualizacion, publicacion y duplicado de posts
  - `BlogTranslationService` — integracion con DeepL para traduccion automatica
  - `BlogTranslationExportService` — exportacion/importacion de traducciones
  - `CommentService` — moderacion de comentarios

## Permisos (Spatie)

| Permiso | Descripcion |
|---------|-------------|
| `blog.post.view` | Ver publicaciones propias |
| `blog.post.view-all` | Ver todas las publicaciones |
| `blog.post.create` | Crear nuevas publicaciones |
| `blog.post.update` | Editar publicaciones |
| `blog.post.delete` | Eliminar publicaciones |
| `blog.post.publish` | Publicar y despublicar entradas |
| `blog.category.view` | Ver categorias |
| `blog.category.create` | Crear categorias |
| `blog.category.update` | Editar categorias |
| `blog.category.delete` | Eliminar categorias |
| `blog.tag.view` | Ver etiquetas |
| `blog.tag.create` | Crear etiquetas |
| `blog.tag.update` | Editar etiquetas |
| `blog.tag.delete` | Eliminar etiquetas |
| `blog.comment.moderate` | Moderar comentarios |
| `blog.settings` | Gestionar configuracion del modulo |

**Roles predefinidos**: `blog-admin`, `blog-editor`, `blog-author`

## Dependencias

- **Requeridos**: `Modules\Core\Models\Setting`, `Modules\Theme\Services\NavService`
- **Opcionales**: modulo DeepL / Locales para traduccion automatica de posts

## Comandos Artisan

```bash
php artisan module:seed Blog --class=BlogPermissionsSeeder
```
