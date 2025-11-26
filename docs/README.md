# 📚 Documentación del Proyecto

Este directorio contiene toda la documentación del proyecto en formato Markdown.

## 📁 Estructura de Carpetas

```
docs/
├── README.md                    # Este archivo
├── ARCHITECTURE.md              # Arquitectura del proyecto
├── SETUP.md                     # Guía de configuración inicial
├── API.md                       # Documentación de APIs
├── DEPLOYMENT.md                # Guía de deployment
├── PRESTASHOP.md               # Integración con PrestaShop
├── DATABASE.md                 # Esquema y migraciones
└── GUIDES/
    ├── ELASTICSEARCH.md         # Configuración de Elasticsearch
    ├── DOCKER.md               # Uso de Docker
    ├── NGINX.md                # Configuración de Nginx
    └── DEVELOPMENT.md          # Guía de desarrollo
```

## 🎯 Convenciones

- **Formato**: Todos los archivos deben estar en Markdown (`.md`)
- **Encoding**: UTF-8
- **Indentación**: 2 espacios
- **Línea máxima**: 120 caracteres (recomendado)
- **Final de línea**: LF (Unix)

## ✅ Reglas Automáticas

El proyecto está configurado con `.editorconfig` para aplicar automáticamente:

```editorconfig
[docs/*.md]
charset = utf-8
end_of_line = lf
indent_size = 2
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = false
```

## 📝 Cómo Crear Documentación

1. Crea un nuevo archivo en `docs/` con extensión `.md`
2. Tu editor respetará automáticamente las convenciones
3. Usa Markdown estándar para formateo
4. Agrégalo a Git con `git add docs/miarchivo.md`

## 🔗 Referencias

- [Markdown Guide](https://www.markdownguide.org/)
- [EditorConfig](https://editorconfig.org/)
- [Laravel Documentation](https://laravel.com/docs)
- [PrestaShop Documentation](https://devdocs.prestashop-project.org/)

## 🚀 Próximos Pasos

1. Crear `ARCHITECTURE.md` - Descripción de la arquitectura
2. Crear `SETUP.md` - Instrucciones de instalación
3. Crear `API.md` - Especificación de APIs
4. Crear `DEPLOYMENT.md` - Guía de deployment
5. Documentar integraciones PrestaShop + Laravel

---

**Última actualización**: 2025-11-26
**Versión**: 1.0.0
**Mantener**: Equipo de desarrollo
