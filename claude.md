# Alsernet - Claude Code Configuration

## Context7 Integration - Complete Stack Documentation

Always use context7 when working with ANY of these technologies:

### 🔙 Backend Framework & Core
- **Laravel 12** - PHP framework, routing, Eloquent ORM, migrations, service providers
- **Laravel Sanctum** - API authentication, token management
- **Laravel Horizon** - Queue management UI and monitoring
- **Laravel Reverb** - WebSocket server for real-time features
- **Laravel Pulse** - Application performance monitoring
- **Laravel Telescope** - Debugging and profiling tool
- **Twig** - Template engine for custom rendering

### 🗄️ Databases & Caching
- **PostgreSQL** - Primary relational database (via Laravel docs)
- **MongoDB** - NoSQL alternative (1.6M+ tokens, 5,143 snippets)
- **MariaDB** - SQL optimization and administration
- **Redis** - Caching, sessions, queues, pub/sub patterns

### 🔐 Authentication & Authorization
- **JWT Auth (tymon/jwt-auth)** - JWT token authentication for APIs
- **Laravel Permission (Spatie)** - Role-based access control, permissions management

### 📊 Data Management & Export
- **Maatwebsite/Excel** - Excel import/export functionality
- **League CSV** - CSV file handling and parsing
- **Laravel Activity Log (Spatie)** - User action auditing and history tracking
- **Laravel Backup (Spatie)** - Automated backup configuration and management
- **Laravel MediaLibrary (Spatie)** - File upload and media management

### 🎨 Frontend & UI
- **TailwindCSS 4.0** - Utility-first CSS framework
- **Bootstrap 5.3** - Responsive components, grid system, utilities
- **DevExpress jQuery** - Advanced widgets, data grids, charts
- **Vue 3 / Inertia** - Component development, state management, routing
- **Vite** - Modern JavaScript build tool and bundler
- **Axios** - HTTP client for JavaScript API requests

### 📄 PDF & Document Generation
- **DomPDF (barryvdh/laravel-dompdf)** - HTML to PDF conversion
- **FPDF / FPDI / TCPDF** - Advanced PDF generation and manipulation
- **HTML2Text** - Converting HTML to plain text

### 🖼️ Image & Barcode Processing
- **Intervention/Image** - Image manipulation and processing
- **Barcode Generators** - QR codes, barcodes, code generation
  - bacon/bacon-qr-code
  - simplesoftwareio/simple-qrcode
  - picqer/php-barcode-generator
  - milon/barcode

### 🌐 Real-time & Communication
- **Pusher** - Push notifications and real-time messaging
- **BotMan** - Chatbot framework and web driver
- **Email Utilities** - Advanced email handling
- **Laravel IMAP** - Email reading via IMAP protocol
- **DeepL Translator** - Machine translation API integration

### 🛠️ Utilities & Tools
- **Guzzle HTTP** - HTTP client for API requests
- **Doctrine DBAL** - Database abstraction layer
- **Laravel Query Builder (Spatie)** - Advanced query building
- **Laravel Rate Limited Job Middleware (Spatie)** - Job rate limiting
- **GeoIP** - IP-based geolocation services
- **Email Validator** - Email address validation
- **HTML Purifier** - HTML sanitization

### 🌐 E-Commerce & Related
- **PrestaShop 1.6** - E-commerce module patterns
- **SSL Certificate Manager (Spatie)** - SSL certificate management

### 📱 Development Tools
- **Laravel Pint** - Code style formatter
- **Laravel Sail** - Docker development environment
- **Laravel Tinker** - Interactive REPL
- **IDE Helper (barryvdh)** - IDE autocompletion

This means you should automatically use the Context7 MCP tools to:
1. Resolve library IDs for ANY technology mentioned
2. Fetch official documentation and code examples in real-time
3. Get version-specific best practices and APIs
4. Access real code snippets from official repositories
5. Find integration patterns between different technologies
6. Keep up with the latest versions and deprecations

## Project Context

- **Framework**: Laravel 11+
- **Type**: E-commerce/Admin System (Alsernet)
- **Primary Database**: PostgreSQL (with MongoDB/MariaDB optional support)
- **Caching**: Redis for sessions, queues, caching
- **Frontend**: Bootstrap 5.3 + Vue 3 + Inertia (planned)
- **UI Components**: DevExpress jQuery widgets
- **Admin Panel**: PrestaShop-inspired (some concepts)
- **Key Features**:
  - Role-based access control (RBAC)
  - Supervisor integration for background jobs
  - Multi-tenant support (partial)
  - RESTful API with caching
  - Real-time notifications via Redis

## Development Workflow with Context7

When you need help, simply mention what you're building. Context7 automatically fetches current documentation:

**Backend Examples:**
- "Build a Laravel queue job with Horizon for email notifications using DeepL translation"
- "Create a JWT authentication endpoint with Laravel Sanctum for API access"
- "Set up Laravel Activity Log to audit all user permission changes"
- "Design a database backup strategy using Laravel Backup for PostgreSQL"
- "Implement real-time notifications with Pusher and Laravel Reverb"

**Data Processing Examples:**
- "Import an Excel file using Maatwebsite/Excel and validate data with Laravel Permission roles"
- "Generate a PDF invoice with DomPDF including product images via MediaLibrary"
- "Create a chatbot using BotMan that reads emails via IMAP and responds with translations"
- "Export user activity logs to Excel with Laravel Activity Log data"

**Frontend Examples:**
- "Create a responsive TailwindCSS dashboard component using Vite and Axios to fetch data"
- "Build a DevExpress jQuery data grid that updates in real-time with Pusher"
- "Design a Vue 3 modal with file upload using MediaLibrary via Axios"
- "Write a Bootstrap 5.3 form with image processing using Intervention/Image"

**API & Integration Examples:**
- "Build a Guzzle HTTP client to fetch GeoIP data and cache results in Redis"
- "Create a rate-limited API endpoint using Laravel Rate Limited Job Middleware"
- "Generate QR codes for orders and store them in MediaLibrary with barcode generation"
- "Validate emails and sanitize HTML using Email Validator and HTML Purifier"

Context7 will automatically fetch current documentation for all mentioned libraries.

## 🎨 Bootstrap Modernize Template - Design Standard

**Important Rule:** Whenever you receive requests for UI/UX adjustments, design new pages, or create frontend components, you MUST:

1. **Always reference** the Modernize template documentation in `docs/frontend/`
2. **Base designs on** existing Modernize components and patterns
3. **Never create custom CSS** when Bootstrap/Modernize alternatives exist
4. **Maintain consistency** across colors, spacing, typography, and iconography

### Modernize Documentation Files

Located in `docs/frontend/`:
- **README.md** - Quick start guide (read this first)
- **modernize-complete-index.md** - All demo URLs and components
- **modernize-overview.md** - General features and structure
- **components.md** - Detailed component reference with code
- **layouts.md** - Page layout patterns ready to copy
- **design-rules.md** - Visual standards and consistency rules

### Workflow for Design Requests

When user asks: *"Design a product listing page"*

```
1. Consult modernize-complete-index.md
   → Find similar component (eco-product-list.html)

2. Open demo URL in browser
   → Inspect with F12 DevTools
   → Copy HTML structure

3. Reference components.md
   → Find relevant components (tables, buttons, badges)

4. Check design-rules.md
   → Verify colors, spacing, typography

5. Build using Bootstrap classes only
   → No custom CSS unless absolutely required

6. Validate with responsive breakpoints
   → Test mobile, tablet, desktop
```

### Quick Color/Icon Reference

**Colors:** Primary `#90bb13`, Success `#13C672`, Danger `#FA896B`, Warning `#FEC90F`
**Icons:** ALWAYS use Font Awesome 6 - `fas fa-{icon-name}` or `far fa-{icon-name}`
**NEVER use Tabler Icons:** Do NOT use `ti ti-*` classes - this project exclusively uses Font Awesome
**Spacing:** Bootstrap scale `mb-2`, `p-3`, `gap-2`

### Typography and Capitalization Rules

**Section Titles (h6 with icons):**
- ALWAYS capitalize only the first word, rest in lowercase
- Format: `<h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fa fa-icon me-2"></i>Primera palabra en mayúscula</h6>`
- ✅ Correct: `<i class="fa fa-info-circle me-2"></i>Información básica`
- ✅ Correct: `<i class="fa fa-tags me-2"></i>Tipos de venta`
- ❌ Wrong: `<i class="fa fa-info-circle me-2"></i>Información Básica`
- ❌ Wrong: `<i class="fa fa-tags me-2"></i>Tipos De Venta`

**Exception:** Proper nouns and acronyms maintain their original capitalization
- Example: `<i class="fa fa-database me-2"></i>Configuración de PostgreSQL`

### Icon Usage Rules

**CRITICAL: When NOT to use icons:**
- ❌ **Card headers with simple text**: Avoid icons in basic section titles
  - Wrong: `<h6 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i>Historial de cambios</h6>`
  - ✅ Correct: `<h6 class="mb-0 fw-bold">Historial de cambios</h6>`
- ❌ **Plain informational headers**: Don't add decorative icons just because you can
- ❌ **Every single heading**: Icons should be meaningful, not mandatory

**When icons ARE appropriate:**
- ✅ **Actionable buttons**: Icons that indicate an action (`<i class="fas fa-save"></i> Guardar`)
- ✅ **Status indicators**: Icons with semantic meaning (`<i class="fas fa-check-circle text-success"></i>`)
- ✅ **Navigation items**: Icons help identify menu options
- ✅ **Form field labels**: When the icon adds context (email, password, etc.)
- ✅ **Sidebar info cards**: Icons categorizing different data types with circular backgrounds

**General principle:**
- Icons should **add meaning or context**, not just decoration
- When in doubt, leave it out - clean text is better than cluttered icons
- Review existing similar components before adding icons

### Demo URLs Always Available

- Main: https://bootstrapdemos.adminmart.com/modernize/dist/main/index.html
- Complete: https://demos.adminmart.com/premium/bootstrap/modernize-bootstrap/landingpage/index.html
- Use F12 DevTools to inspect and learn from any demo page

---

## 📁 Documentation Organization Rules

### Directory Structure for Context7 Indexing

All project documentation must be organized in the `docs/` folder to ensure Context7 properly indexes it:

```
docs/
├── backend/          → Laravel code, API, services, permissions
├── frontend/         → Vue components, Inertia, styling, build
├── database/         → PostgreSQL schema, Redis, backups
├── devops/           → Deployment, Docker, environment setup
├── api/              → Endpoint specifications, authentication, errors
└── guides/           → Setup, workflows, testing, troubleshooting
```

### ✅ Files Context7 WILL Index

- ✅ All `.md` (Markdown) files inside `docs/` folder
- ✅ Code examples within markdown files
- ✅ API specifications and documentation
- ✅ Architecture and design decisions

### ❌ Files Context7 WILL NOT Index (Excluded)

These file types are automatically excluded by `context7.json`:

| Type | Example | Reason |
|------|---------|--------|
| Shell Scripts | `.sh` | Operational, not documentation |
| Config Files | `.conf`, `.json` (config) | Binary/structured, not docs |
| Text Files | `.txt` | Plain text should be converted to `.md` |
| Log Files | `.log` | Runtime data, not documentation |
| Lock Files | `composer.lock`, `package-lock.json` | Dependencies tracking |
| Source Code | `.php`, `.js` | Code lives in app/, not docs/ |

### 📝 Documentation Format Rules

- **Write in Markdown (.md)** - All documentation must be in Markdown format
- **One topic per file** - Keep files focused and navigable
- **Include code examples** - Real examples from the codebase
- **Reference official docs** - Link to context7 indexed libraries when applicable
- **Internal links relative** - Use `./path-to-file.md` for references within docs/

### 🔄 How Documentation Gets Updated

1. Modify `.md` files in `docs/` folder
2. Commit changes to git: `git add docs/ && git commit -m "docs: ..."`
3. Context7 automatically re-indexes your changes
4. Next Claude Code conversation includes updated documentation

### 💡 Example Documentation Request

```
"According to our API documentation, what are the required headers for JWT authentication?"
"Show me the database schema for the products table from our documentation"
"What does our setup guide say about installing Redis?"
```

Claude will automatically pull from your indexed documentation in `docs/`.

## Development Guidelines

- Always prefer official documentation (via context7) over generic solutions
- Reference project documentation first (docs/ folder via context7)
- Use Laravel conventions for backend, Blade for templates
- Bootstrap 5.3 for responsive design, DevExpress for complex UI
- Keep database queries optimized with proper indexing
- Use Redis for caching critical data
- Document API endpoints with their permission requirements in `docs/api/`
- Follow PrestaShop module patterns where applicable for extensibility
- Store all documentation in `docs/` in Markdown format

## 🚨 CRITICAL ICON LIBRARY RULE 🚨

**MANDATORY:** This project uses **Font Awesome 6** exclusively for all icons.

### ✅ CORRECT - Use Font Awesome 6:
```html
<i class="fas fa-upload"></i>
<i class="far fa-folder"></i>
<i class="fab fa-github"></i>
```

### ❌ FORBIDDEN - Never Use Tabler Icons:
```html
<!-- NEVER DO THIS -->
<i class="ti ti-upload"></i>
<i class="ti ti-folder"></i>
```

**This rule applies to:**
- All HTML/Blade templates
- All Vue components
- All JavaScript-generated HTML
- All documentation examples
- All agent-generated code
- All prompts and suggestions

**Violation of this rule breaks the UI** as Tabler Icons are not loaded in this project.

**When suggesting icons:** Always use Font Awesome notation: `fas fa-{name}`, `far fa-{name}`, or `fab fa-{name}`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.4
- laravel/framework (LARAVEL) - v12
- laravel/horizon (HORIZON) - v5
- laravel/mcp (MCP) - v0
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/telescope (TELESCOPE) - v5
- livewire/livewire (LIVEWIRE) - v3
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- laravel-echo (ECHO) - v1
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== mcp/core rules ===

## Laravel MCP

- MCP (Model Context Protocol) is very new. You must use the `search-docs` tool to get documentation for how to write and test Laravel MCP servers, tools, resources, and prompts effectively.
- MCP servers need to be registered with a route or handle in `routes/ai.php`. Typically, they will be registered using `Mcp::web()` to register a HTTP streaming MCP server.
- Servers are very testable - use the `search-docs` tool to find testing instructions.
- Do not run `mcp:start`. This command hangs waiting for JSON RPC MCP requests.
- Some MCP clients use Node, which has its own certificate store. If a user tries to connect to their web MCP server locally using https://, it could fail due to this reason. They will need to switch to http:// during local development.


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
