# 📖 Guía de Documentación del Proyecto

## 🎯 Objetivo

Mantener una documentación centralizada, consistente y fácil de mantener en la carpeta `/docs`.

## 📂 Estructura Documentada

```
docs/
├── README.md                    # Índice principal
├── DOCUMENTATION_GUIDE.md       # Este archivo
├── TEMPLATE.md                  # Plantilla para nuevos documentos
│
├── 1-ARQUITECTURA/
│   ├── ARCHITECTURE.md          # Descripción general
│   ├── LAYERS.md               # Capas del proyecto
│   └── INTEGRATIONS.md         # Integraciones (Laravel + PrestaShop)
│
├── 2-SETUP/
│   ├── INSTALLATION.md         # Instalación inicial
│   ├── ENVIRONMENT.md          # Configuración de .env
│   ├── DATABASE.md             # Migraciones y seeders
│   └── REQUIREMENTS.md         # Requisitos del proyecto
│
├── 3-DEVELOPMENT/
│   ├── PROJECT_STRUCTURE.md    # Estructura de carpetas
│   ├── CODING_STANDARDS.md     # Estándares de código
│   ├── GIT_WORKFLOW.md         # Workflow de Git
│   └── TESTING.md              # Testing y QA
│
├── 4-APIs/
│   ├── REST_API.md             # Especificación REST
│   ├── AUTHENTICATION.md       # Auth (JWT, Sanctum)
│   ├── WEBHOOKS.md             # Webhooks PrestaShop
│   └── RATE_LIMITING.md        # Rate limiting
│
├── 5-INFRASTRUCTURE/
│   ├── DOCKER.md               # Docker setup
│   ├── NGINX.md                # Configuración Nginx
│   ├── DATABASE.md             # PostgreSQL setup
│   ├── ELASTICSEARCH.md        # Elasticsearch setup
│   ├── REDIS.md                # Redis setup
│   └── MONITORING.md           # Health checks y monitoreo
│
├── 6-DEPLOYMENT/
│   ├── PRODUCTION.md           # Deploy a producción
│   ├── CI_CD.md                # Pipelines CI/CD
│   ├── BACKUP.md               # Estrategia de backups
│   └── SECURITY.md             # Consideraciones de seguridad
│
├── 7-PRESTASHOP/
│   ├── INTEGRATION.md          # Integración PrestaShop + Laravel
│   ├── WEBHOOKS.md             # Webhooks PrestaShop
│   ├── MODULES.md              # Módulos custom
│   └── DATA_SYNC.md            # Sincronización de datos
│
└── 8-TROUBLESHOOTING/
    ├── COMMON_ISSUES.md        # Problemas comunes
    ├── DEBUG_GUIDE.md          # Guía de debugging
    └── PERFORMANCE.md          # Optimización de performance
```

## ✅ Reglas de Documentación

### Formato

- **Extensión**: `.md` (Markdown)
- **Encoding**: UTF-8
- **Line endings**: LF (Unix)
- **Indentación**: 2 espacios
- **Máximo de líneas**: 120 caracteres

### Estructura de Documento

```markdown
# Título H1

**Versión**: 1.0.0
**Última actualización**: YYYY-MM-DD
**Autor**: Nombre

## 📋 Tabla de Contenidos

## 📖 Sección Principal

### Subsección

### Ejemplo de código

\`\`\`language
code
\`\`\`

## 🐛 Troubleshooting

## 📚 Referencias
```

### Convenciones de Nombre

- Usar UPPERCASE con guiones: `FILENAME.md`
- Nombre descriptivo: `API_DOCUMENTATION.md`
- No usar espacios, usar guiones: `MY-DOCUMENT.md` ✅ / `MY DOCUMENT.md` ❌

## 🎨 Formato Markdown Recomendado

### Títulos

```markdown
# H1 - Página principal
## H2 - Secciones principales
### H3 - Subsecciones
#### H4 - Detalles
```

### Énfasis

```markdown
**Negrita** para conceptos importantes
*Cursiva* para términos técnicos
`código inline` para variables/comandos
```

### Listas

```markdown
- Item 1
- Item 2
  - Sub-item 2.1
  - Sub-item 2.2

1. Paso 1
2. Paso 2
3. Paso 3
```

### Código

````markdown
```language
code block
```

```php
// PHP code
```

```bash
# Bash command
```
````

### Bloques de Nota

```markdown
> **Nota**: Información importante
> **Advertencia**: Algo a tener en cuenta
> **Consejo**: Buena práctica
```

### Enlaces

```markdown
[Texto del link](https://url.com)
[Link interno](./FILENAME.md)
[Link a sección](#sección)
```

## 🔄 Flujo de Documentación

### Crear Nuevo Documento

1. Copiar `TEMPLATE.md`
2. Renombrar a `MY-DOCUMENT.md`
3. Llenar contenido
4. Agregar a índice principal (`README.md`)
5. Git add y commit

### Actualizar Documento

1. Editar archivo `.md`
2. Actualizar fecha "Última actualización"
3. Actualizar versión si es cambio mayor
4. Commit con mensaje descriptivo

### Revisión de Documentación

- Mantener consistencia con otros documentos
- Revisar links (internos y externos)
- Verificar ejemplos de código
- Actualizar cuando hay cambios en el código

## 🛠️ Herramientas Útiles

### EditorConfig

- Automáticamente formatea archivos según `.editorconfig`
- Instalar extensión en tu editor

### Markdown Preview

- VS Code: "Markdown Preview Enhanced"
- GitHub: Preview en repositorio

### Validación de Links

```bash
# Verificar links rotos
find docs -name "*.md" -exec grep -o '\[.*\](.*)\' {} \;
```

## 📚 Checklist para Documentación

- [ ] Archivo guardado en carpeta `docs/`
- [ ] Nombre en UPPERCASE con guiones
- [ ] UTF-8 encoding
- [ ] LF line endings
- [ ] Contiene título H1
- [ ] Contiene tabla de contenidos
- [ ] Contiene secciones claras
- [ ] Ejemplos de código funcionan
- [ ] Links están actualizados
- [ ] Añadido a `README.md`
- [ ] Commit con mensaje descriptivo

## 🚀 Próximos Pasos

1. Crear estructura de carpetas
2. Documentar cada sección
3. Revisar consistencia
4. Publicar en GitHub Pages (opcional)

## 📞 Contacto

Para preguntas o sugerencias sobre documentación, contactar al equipo de desarrollo.

---

**Estado**: Activo
**Versión**: 1.0.0
**Última actualización**: 2025-11-26
