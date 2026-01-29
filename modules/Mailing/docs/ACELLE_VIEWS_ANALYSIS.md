# Acelle Views Architecture Analysis

**Analysis Date:** 2026-01-29
**Source Path:** `/Users/functionbytes/Function/Coding/acelle/resources/views/`
**Total Blade Templates:** 930 files

---

## Executive Summary

Acelle Mail uses a comprehensive Blade-based view architecture with 930+ templates organized into 60+ directories. The system employs a modular component approach with reusable partials, multiple layout variations supporting themes, and specialized views for mailing operations (campaigns, lists, subscribers, templates, automation).

---

## 1. Directory Structure Overview

### 1.1 Top-Level Organization

```
resources/views/
├── layouts/              # Layout templates (core, popup, automation)
├── campaigns/            # Campaign management views (73 files)
├── lists/                # Mailing list management (26 files)
├── subscribers/          # Subscriber management (18 files)
├── templates/            # Email template management (16 files)
├── automation2/          # Marketing automation workflows (46 files)
├── segments/             # Subscriber segmentation (14 files)
├── helpers/              # Reusable form helpers (39 files)
├── elements/             # Reusable UI elements
├── admin/                # Admin panel views (40+ subdirs)
├── auth/                 # Authentication views
├── sending_servers/      # Mail server configuration (12 files)
├── sending_domains/      # Domain management (9 files)
├── senders/              # Sender identity management (12 files)
├── blacklists/           # Email blacklist management
├── builder/              # Email template builder
├── forms/                # Form builder and management
├── errors/               # Error pages
└── [Additional modules]  # Store, Site, Invoices, Plans, etc.
```

---

## 2. Layout System Architecture

### 2.1 Core Layouts

Located in `layouts/core/`:

#### **Primary Layouts**

| Layout File | Purpose | Used For |
|-------------|---------|----------|
| `frontend.blade.php` | Customer/User interface | Campaigns, Lists, Subscribers, Templates |
| `backend.blade.php` | Admin interface | System settings, User management |
| `frontend_dark.blade.php` | Dark mode frontend | Customer UI (dark theme) |
| `backend_dark.blade.php` | Dark mode backend | Admin UI (dark theme) |
| `frontend_public.blade.php` | Public pages | Landing pages, Forms |
| `login.blade.php` | Authentication | Login screen |
| `login_slider.blade.php` | Auth with slider | Modern login UI |
| `register.blade.php` | Registration | User signup |
| `page.blade.php` | Generic page | Static content |
| `empty.blade.php` | Blank layout | Minimal UI contexts |
| `none.blade.php` | No layout | Raw output |

#### **Layout Structure**

**Frontend Layout (`frontend.blade.php`):**

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.core._head')
    @include('layouts.core._script_vars')
    @yield('head')

    <!-- Theme Detection -->
    @if (Auth::user()->customer->theme_mode == 'auto')
        // Auto dark mode detection
    @endif

    <!-- Theme CSS -->
    <link rel="stylesheet" href="theme/{color_scheme}.css">
</head>
<body class="theme-{color} {menu_layout}bar mode-{light|dark}">
    <!-- Navigation Menu -->
    @include('layouts.core._menu_frontend_saas')

    <!-- Middle Bar -->
    @include('layouts.core._middle_bar')

    <main class="container page-container px-3">
        @include('layouts.core._headbar_frontend')
        @yield('page_header')
        @include('layouts.core._errors')
        @yield('content')
        @include('layouts.core._footer')
    </main>

    @include('layouts.core._admin_area')
    @include('layouts.core._notify')
    @include('layouts.core._flash')
</body>
</html>
```

**Backend Layout (`backend.blade.php`):**

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.core._head')
    @include('layouts.core._favicon')
    @include('layouts.core._script_vars')
    @yield('head')

    <!-- Theme CSS -->
    <link rel="stylesheet" href="theme/{admin_color_scheme}.css">
</head>
<body class="theme-{color} {menu_layout}bar mode-{light|dark}">
    @include('layouts.core._menu_backend')
    @include('layouts.core._middle_bar')

    <main class="container page-container px-3">
        @include('layouts.core._headbar_backend')
        @yield('page_header')
        @include('layouts.core._errors')
        @yield('content')
        @include('layouts.core._footer')
    </main>

    @include('layouts.core._notify')
    @include('layouts.core._loginas_area')
    @include('layouts.core._flash')
</body>
</html>
```

### 2.2 Layout Partials

Located in `layouts/core/`:

| Partial | Purpose |
|---------|---------|
| `_head.blade.php` | HTML head (meta, title) |
| `_includes.blade.php` | CSS/JS includes (Bootstrap, jQuery, Select2, Tooltipster) |
| `_script_vars.blade.php` | JavaScript global variables |
| `_favicon.blade.php` | Favicon configuration |
| `_menu_backend.blade.php` | Admin sidebar menu |
| `_menu_frontend_saas.blade.php` | Customer sidebar menu (SaaS mode) |
| `_menu_frontend_single.blade.php` | Customer sidebar menu (single-tenant) |
| `_menu_frontend_store.blade.php` | E-commerce store menu |
| `_menu_frontend_cartpaye.blade.php` | Cart/Payment menu |
| `_menu_dark_backend.blade.php` | Dark mode backend menu |
| `_menu_dark_frontend.blade.php` | Dark mode frontend menu |
| `_headbar_backend.blade.php` | Admin top bar |
| `_headbar_frontend.blade.php` | Customer top bar |
| `_middle_bar.blade.php` | Middle navigation bar |
| `_topbar_frontend.blade.php` | Frontend top bar (store) |
| `_footer.blade.php` | Page footer |
| `_errors.blade.php` | Error message display |
| `_flash.blade.php` | Flash message display |
| `_notify.blade.php` | Notification system |
| `_notify_backend.blade.php` | Backend notifications |
| `_notify_frontend.blade.php` | Frontend notifications |
| `_admin_area.blade.php` | Admin quick access panel |
| `_loginas_area.blade.php` | "Login as" functionality |
| `_theme_mode_control.blade.php` | Theme switcher |
| `_theme_color_control.blade.php` | Color scheme picker |
| `_dark_mode_switch.blade.php` | Dark mode toggle |
| `_top_notifications.blade.php` | Top notification bar |
| `_top_activity_log.blade.php` | Activity log dropdown |

### 2.3 Popup Layouts

Located in `layouts/popup/`:

| Layout | Size | Use Case |
|--------|------|----------|
| `small.blade.php` | ~400px | Confirmations, alerts |
| `medium.blade.php` | ~600px | Forms, quick edits |
| `large.blade.php` | ~800px | Complex forms |
| `full.blade.php` | ~95% screen | Full-featured modals |

### 2.4 Automation Layouts

Located in `layouts/automation/`:

| Layout | Purpose |
|--------|---------|
| `main.blade.php` | Automation workflow builder main layout |
| `frontend.blade.php` | Automation customer interface |

---

## 3. Core Assets & Dependencies

From `layouts/core/_includes.blade.php`:

### 3.1 CSS Frameworks & Libraries

```html
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css?family=Poppins:400,400i,600,600i,700,700i,800,800i" rel="stylesheet">

<!-- Bootstrap 5.x -->
<link rel="stylesheet" href="core/bootstrap/css/bootstrap.min.css">

<!-- Select2 -->
<link rel="stylesheet" href="core/select2/css/select2.min.css">

<!-- Tooltipster -->
<link rel="stylesheet" href="core/tooltipster/css/tooltipster.bundle.min.css">

<!-- Google Material Icons -->
<link href="core/css/google-font-icon.css" rel="stylesheet">

<!-- Custom Theme Styles -->
<link rel="stylesheet" href="core/css/dark.css">
<link rel="stylesheet" href="core/css/menu.css">
<link rel="stylesheet" href="core/css/app.css">
<link rel="stylesheet" href="core/css/responsive.css">
<link rel="stylesheet" href="core/css/autofill.css">

<!-- Custom User CSS -->
<link rel="stylesheet" href="custom.css">
```

### 3.2 JavaScript Libraries

```html
<!-- jQuery 3.6.4 -->
<script src="core/js/jquery-3.6.4.min.js"></script>

<!-- Bootstrap 5.x JS -->
<script src="core/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Select2 -->
<script src="core/select2/js/select2.min.js"></script>

<!-- jQuery Validation -->
<script src="core/validate/jquery.validate.min.js"></script>
<script src="core/js/validate.js"></script>

<!-- jQuery Numeric -->
<script src="core/numeric/jquery.numeric.min.js"></script>

<!-- Tooltipster -->
<script src="core/tooltipster/js/tooltipster.bundle.min.js"></script>

<!-- Custom JavaScript Modules -->
<script src="core/js/functions.js"></script>
<script src="core/js/link.js"></script>
<script src="core/js/box.js"></script>
<script src="core/js/popup.js"></script>
<script src="core/js/sidebar.js"></script>
<script src="core/js/list.js"></script>
<script src="core/js/anotify.js"></script>
<script src="core/js/dialog.js"></script>
<script src="core/js/iframe_modal.js"></script>
<script src="core/js/search.js"></script>
<script src="core/js/image_popup.js"></script>
<script src="core/js/autofill.js"></script>
<script src="core/js/app.js"></script>
```

### 3.3 Chart Library

For analytics views (campaign overview):

```html
<!-- ECharts (Apache ECharts) -->
<script src="core/echarts/echarts.min.js"></script>
<script src="core/echarts/dark.js"></script>
```

---

## 4. Theme System

### 4.1 Available Color Schemes

Located in `public/core/css/theme/`:

| Theme File | Color Scheme |
|------------|--------------|
| `default.css` | Default blue |
| `blue.css` | Blue |
| `pink.css` | Pink |
| `green.css` | Green |
| `brown.css` | Brown |
| `grey.css` | Grey |
| `white.css` | White/Light |
| `store.css` | E-commerce store theme |

### 4.2 Theme Modes

- **Light Mode:** Standard theme
- **Dark Mode:** Dark theme with adjusted colors
- **Auto Mode:** System-based detection (prefers-color-scheme)

### 4.3 Menu Layouts

- **Left Bar:** Sidebar on the left (default)
- **Top Bar:** Horizontal top menu
- **Collapsed State:** Minimized sidebar (icons only)

### 4.4 Theme Configuration

**User-level theme settings:**
- Color scheme selection
- Dark/Light/Auto mode
- Menu layout preference
- Sidebar collapsed state

**Dynamic Theme Loading:**

```php
// In layout files
<link rel="stylesheet" href="{{ AppUrl::asset('core/css/theme/'.Auth::user()->customer->getColorScheme().'.css') }}">

<body class="theme-{{ Auth::user()->customer->getColorScheme() }}
             {{ Auth::user()->customer->getMenuLayout() }}bar
             mode-{{ getThemeMode(Auth::user()->customer->theme_mode) }}">
```

---

## 5. Reusable Components (Helpers)

### 5.1 Form Control Helpers

Located in `helpers/`:

| Helper | Purpose | Usage |
|--------|---------|-------|
| `_text.blade.php` | Text input | Text fields |
| `_email.blade.php` | Email input | Email fields with validation |
| `_number.blade.php` | Number input | Numeric fields |
| `_password.blade.php` | Password input | Secure password fields |
| `_select.blade.php` | Dropdown select | Standard select boxes |
| `_select_ajax.blade.php` | AJAX select | Dynamic options via AJAX |
| `_select_tag.blade.php` | Tag select | Tag/multi-select with chips |
| `_checkbox2.blade.php` | Checkbox | Single checkbox |
| `_checkbox3.blade.php` | Checkbox v3 | Alternative checkbox style |
| `_checkboxes.blade.php` | Multiple checkboxes | Checkbox group |
| `_mc_checkbox.blade.php` | Multi-column checkbox | Grid layout checkboxes |
| `_date.blade.php` | Date picker | Date selection |
| `_time.blade.php` | Time picker | Time selection |
| `_datetime.blade.php` | DateTime picker | Combined date/time |
| `_captcha.blade.php` | CAPTCHA | reCAPTCHA integration |
| `_autofill.blade.php` | Autocomplete | Autofill suggestions |
| `switch.blade.php` | Toggle switch | On/off switch |

### 5.2 Form Helper Patterns

Located in `helpers/form_control/`:

```blade
<!-- Text Input Helper -->
@include('helpers._text', [
    'label' => 'Full Name',
    'name' => 'full_name',
    'value' => old('full_name', $user->full_name),
    'help_class' => 'custom',
    'rules' => ['required', 'min:3']
])

<!-- Select Helper -->
@include('helpers._select', [
    'label' => 'Country',
    'name' => 'country',
    'options' => $countries,
    'include_blank' => 'Select Country',
    'value' => old('country', $user->country_id)
])

<!-- Date Picker Helper -->
@include('helpers._date', [
    'label' => 'Birth Date',
    'name' => 'birth_date',
    'value' => old('birth_date', $user->birth_date)
])
```

### 5.3 UI Elements

Located in `elements/`:

| Element | Purpose |
|---------|---------|
| `_per_page_select.blade.php` | Pagination size selector |
| `_cron_jobs.blade.php` | Cron job status display |
| `_notification.blade.php` | Notification badge |
| `_tags.blade.php` | Tag list display |

### 5.4 RSS & Builder Widgets

Located in `helpers/rss/` and `helpers/_builder_rss_widgets.blade.php`:

- RSS feed integration for email templates
- Dynamic content widgets for email builder

---

## 6. Critical Mailing Views

### 6.1 Campaign Management (`campaigns/`)

**73 view files** for complete campaign lifecycle.

#### **Main Views**

| View | Purpose | Key Features |
|------|---------|--------------|
| `index.blade.php` | Campaign list | Bulk actions, filtering, sorting |
| `select_type.blade.php` | Campaign type selection | Regular, A/B test, recurring |
| `overview.blade.php` | Campaign dashboard | Analytics, charts, metrics |
| `subscribers.blade.php` | Campaign subscribers | Recipient list |
| `tracking_log.blade.php` | Click tracking | Link click tracking |
| `open_log_list.blade.php` | Open tracking | Email open tracking |
| `unsubscribe_log.blade.php` | Unsubscribe log | Unsubscribe tracking |
| `webhooksList.blade.php` | Webhook integration | External webhook management |

#### **Template & Content**

| View | Purpose |
|------|---------|
| `template_build.blade.php` | Email template editor |
| `plain.blade.php` | Plain text version |
| `previewAsList.blade.php` | Preview with subscriber data |

#### **Analytics Partials**

| Partial | Metric |
|---------|--------|
| `_chart.blade.php` | Main analytics chart |
| `_24h_chart.blade.php` | 24-hour activity |
| `_open_click_rate.blade.php` | Open/click rates |
| `_count_boxes.blade.php` | Metric counters |
| `_top_link.blade.php` | Most clicked links |
| `_most_click_country.blade.php` | Click heatmap by country |
| `_most_open_country.blade.php` | Open heatmap by country |
| `_most_open_location.blade.php` | Geographic opens |
| `open_map.blade.php` | Interactive map view |

#### **Campaign Index Structure**

```blade
@extends('layouts.core.frontend', ['menu' => 'campaign'])

@section('title', trans('messages.campaigns'))

@section('page_header')
    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item">
                <a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a>
            </li>
        </ul>
        <h1>
            <span class="material-symbols-rounded">format_list_bulleted</span>
            {{ trans('messages.campaigns') }}
        </h1>
    </div>
@endsection

@section('content')
    <div id="CampaignsIndexContainer" class="listing-form">
        <!-- Filter Controls -->
        <div class="d-flex top-list-controls">
            <div class="me-auto">
                <div class="filter-box">
                    <!-- Checkbox for select all -->
                    <input type="checkbox" name="page_checked" class="check_all">

                    <!-- Bulk Actions Dropdown -->
                    <div class="dropdown list_actions">
                        <button class="btn btn-secondary dropdown-toggle">
                            {{ trans('messages.actions') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="action dropdown-item" href="#">Restart</a></li>
                            <li><a class="action dropdown-item" href="#">Pause</a></li>
                            <li><a class="action dropdown-item" href="#">Delete</a></li>
                        </ul>
                    </div>

                    <!-- Sort Controls -->
                    <select name="sort_order">
                        <option value="created_at">Created At</option>
                        <option value="name">Name</option>
                    </select>

                    <!-- Status Filter -->
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="sending">Sending</option>
                        <option value="sent">Sent</option>
                    </select>

                    <!-- Search -->
                    <input type="text" name="keyword" class="form-control search"
                           placeholder="Search...">
                </div>
            </div>

            <!-- Create Button -->
            <div class="text-end">
                <a href="{{ action('CampaignController@selectType') }}"
                   class="btn btn-secondary">
                    <span class="material-symbols-rounded">add</span>
                    {{ trans('messages.create_campaign') }}
                </a>
            </div>
        </div>

        <!-- List Content (AJAX loaded) -->
        <div id="CampaignsIndexContent" class="pml-table-container"></div>
    </div>

    <script>
        var CampaignsIndex = {
            getList: function() {
                return makeList({
                    url: '{{ action('CampaignController@listing') }}',
                    container: $('#CampaignsIndexContainer'),
                    content: $('#CampaignsIndexContent')
                });
            }
        };

        $(document).ready(function() {
            CampaignsIndex.getList().load();
        });
    </script>
@endsection
```

#### **Campaign Overview Structure**

```blade
@extends('layouts.core.frontend', ['menu' => 'campaign'])

@section('title', $campaign->name)

@section('head')
    <script src="{{ AppUrl::asset('core/echarts/echarts.min.js') }}"></script>
    <script src="{{ AppUrl::asset('core/echarts/dark.js') }}"></script>
@endsection

@section('page_header')
    @include("campaigns._header")
@endsection

@section('content')
    @include("campaigns._menu", ['menu' => 'overview'])
    @include("campaigns._info")

    <br />

    @include("campaigns._chart")
    @include("campaigns._open_click_rate")
    @include("campaigns._count_boxes")

    <br />

    @include("campaigns._24h_chart")

    <br />

    @include("campaigns._top_link")

    <br />

    @include("campaigns._most_click_country")

    <br />

    @include("campaigns._most_open_country")

    <br />

    @include("campaigns._most_open_location")
@endsection
```

### 6.2 Mailing Lists (`lists/`)

**26 view files** for list management.

#### **Main Views**

| View | Purpose |
|------|---------|
| `index.blade.php` | List of mailing lists |
| `create.blade.php` | Create new list |
| `edit.blade.php` | Edit list settings |
| `overview.blade.php` | List dashboard |
| `email_verification.blade.php` | Email verification settings |
| `email_verification_progress.blade.php` | Verification progress |
| `embedded_form.blade.php` | Subscribe form generator |
| `embedded_form_frame.blade.php` | Form iframe embed |
| `copy.blade.php` | Duplicate list |
| `selectList.blade.php` | List picker modal |

#### **Partials**

| Partial | Purpose |
|---------|---------|
| `_form.blade.php` | List settings form |
| `_menu.blade.php` | List sub-navigation |
| `_stat.blade.php` | List statistics |
| `_header.blade.php` | List page header |
| `_quick_view.blade.php` | Quick stats panel |
| `_list.blade.php` | List item row |
| `_growth_chart.blade.php` | Subscriber growth chart |
| `_embedded_form_content.blade.php` | Form HTML content |
| `_modals_export.blade.php` | Export modal |

#### **List Index Structure**

```blade
@extends('layouts.core.frontend', ['menu' => 'list'])

@section('title', trans('messages.my_lists'))

@section('content')
    <div class="listing-form" id="ListsIndexContainer">
        <div class="d-flex top-list-controls">
            <div class="me-auto">
                <div class="filter-box">
                    <!-- Select All Checkbox -->
                    <input type="checkbox" name="page_checked" class="check_all">

                    <!-- Actions Dropdown -->
                    <div class="dropdown list_actions">
                        <button class="btn btn-secondary dropdown-toggle">
                            {{ trans('messages.actions') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item"
                                   link-confirm-url="{{ action('MailListController@deleteConfirm') }}"
                                   href="{{ action('MailListController@delete') }}">
                                    <span class="material-symbols-rounded">delete_outline</span>
                                    {{ trans('messages.delete') }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Sort -->
                    <select name="sort_order">
                        <option value="created_at">Created At</option>
                        <option value="name">Name</option>
                    </select>

                    <!-- Search -->
                    <input type="text" name="keyword" class="form-control search">
                </div>
            </div>

            <!-- Create Button -->
            <div class="text-end">
                <a href="{{ action("MailListController@create") }}"
                   class="btn btn-secondary">
                    <span class="material-symbols-rounded">add</span>
                    {{ trans('messages.create_list') }}
                </a>
            </div>
        </div>

        <div id="ListsIndexContent"></div>
    </div>

    <script>
        var ListsIndex = {
            list: null,
            getList: function() {
                if (this.list == null) {
                    this.list = makeList({
                        url: '{{ action('MailListController@listing') }}',
                        container: $('#ListsIndexContainer'),
                        content: $('#ListsIndexContent')
                    });
                }
                return this.list;
            }
        };

        $(document).ready(function() {
            ListsIndex.getList().load();
        });
    </script>
@endsection
```

### 6.3 Subscribers (`subscribers/`)

**18 view files** for subscriber management.

#### **Main Views**

| View | Purpose |
|------|---------|
| `index.blade.php` | Subscriber list with advanced filtering |
| `create.blade.php` | Add subscriber |
| `edit.blade.php` | Edit subscriber |
| `import.blade.php` | Import subscribers (legacy) |
| `import2.blade.php` | Import subscribers v2 |
| `export.blade.php` | Export subscribers |
| `bulkDelete.blade.php` | Bulk delete interface |
| `bulkDeleteConfirm.blade.php` | Delete confirmation |
| `assignValues.blade.php` | Bulk assign field values |
| `updateTags.blade.php` | Bulk update tags |
| `copy_move_form.blade.php` | Copy/move subscribers |
| `noList.blade.php` | No list selected state |

#### **Import Wizard (import2/)**

| View | Step |
|------|------|
| `upload.blade.php` | Step 1: File upload |
| `mapping.blade.php` | Step 2: Field mapping |
| `progress.blade.php` | Step 3: Import progress |
| `progressContent.blade.php` | Progress bar content |
| `_sidebar.blade.php` | Wizard sidebar |

#### **Partials**

| Partial | Purpose |
|---------|---------|
| `_form.blade.php` | Subscriber form |
| `_list.blade.php` | Subscriber list item |
| `_summary.blade.php` | Subscriber summary |

#### **Subscriber Index Features**

**Advanced Filtering:**
- Status filter (subscribed, unsubscribed, unconfirmed, spam-reported, blacklisted)
- Email verification status filter
- Custom field columns (dynamic)
- Full-text search

**Bulk Actions:**
- Subscribe
- Unsubscribe
- Resend confirmation email
- Copy to another list
- Move to another list
- Assign values
- Delete
- Bulk delete

**Dynamic Columns:**
```blade
<div class="btn-group">
    <button class="btn btn-default dropdown-toggle">
        {{ trans('messages.columns') }}
    </button>
    <ul class="dropdown-menu">
        @foreach ($list->getFields as $field)
            @if ($field->tag != "EMAIL")
                <li>
                    <label>
                        <input type="checkbox" name="columns[]" value="{{ $field->uid }}">
                        <span>{{ $field->label }}</span>
                    </label>
                </li>
            @endif
        @endforeach
        <li>
            <label>
                <input checked type="checkbox" name="columns[]" value="created_at">
                <span>{{ trans('messages.created_at') }}</span>
            </label>
        </li>
        <li>
            <label>
                <input checked type="checkbox" name="columns[]" value="updated_at">
                <span>{{ trans('messages.updated_at') }}</span>
            </label>
        </li>
    </ul>
</div>
```

### 6.4 Email Templates (`templates/`)

**16 view files** for template management.

#### **Main Views**

| View | Purpose |
|------|---------|
| `index.blade.php` | Template gallery (grid/list view) |
| `edit.blade.php` | Edit template |
| `preview.blade.php` | Template preview |
| `upload.blade.php` | Upload HTML template |
| `copy.blade.php` | Duplicate template |
| `changeName.blade.php` | Rename template |
| `updateThumb.blade.php` | Update thumbnail |
| `updateThumbUrl.blade.php` | Set thumbnail URL |
| `categories.blade.php` | Template categories |
| `chat.blade.php` | AI chat for template creation |

#### **Builder Views (`builder/`)**

| View | Purpose |
|------|---------|
| `create.blade.php` | Create from scratch |
| `edit.blade.php` | Visual email builder |
| `content.blade.php` | Builder content area |
| `templates.blade.php` | Pre-built templates |

#### **Partials**

| Partial | Purpose |
|---------|---------|
| `_form.blade.php` | Template settings form |
| `_list_list.blade.php` | List view template item |
| `_list_grid.blade.php` | Grid view template item |

#### **Template Index Structure**

```blade
@extends('layouts.core.frontend', ['menu' => 'template'])

@section('content')
    <div id="TemplatesIndexContainer" class="view-{{ request()->view }}">
        <!-- View Toggle (Grid/List) -->
        <div class="view-toggle">
            <a href="?view=grid" class="btn btn-default">
                <span class="material-symbols-rounded">grid_view</span>
            </a>
            <a href="?view=list" class="btn btn-default">
                <span class="material-symbols-rounded">reorder</span>
            </a>
        </div>

        <!-- Template Source Toggle -->
        <div class="pt-5">
            <a href="?from=mine&view=list"
               class="btn btn-light {{ request()->from != 'gallery' ? 'focus' : '' }}">
                <span class="material-symbols-rounded">portrait</span>
                {{ trans('messages.my_templates') }}
            </a>

            <a href="?from=gallery&view=grid"
               class="btn btn-light {{ request()->from == 'gallery' ? 'focus' : '' }}">
                <span class="material-symbols-rounded">collections</span>
                {{ trans('messages.base_template_gallery') }}
            </a>
        </div>

        <!-- Filters -->
        <div class="filter-box">
            <input type="hidden" name="view" value="{{ request()->view }}">

            @if (request()->view == 'list')
                <!-- Bulk Actions (List View Only) -->
                <div class="dropdown list_actions">
                    <button class="btn btn-secondary dropdown-toggle">
                        {{ trans('messages.actions') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item"
                               link-confirm="{{ trans('messages.delete_templates_confirm') }}"
                               href="{{ action('TemplateController@delete') }}">
                                <span class="material-symbols-rounded">delete_outline</span>
                                {{ trans('messages.delete') }}
                            </a>
                        </li>
                    </ul>
                </div>
            @endif

            <!-- Sort -->
            <select name="sort_order">
                <option value="created_at">Created At</option>
                <option value="name">Name</option>
            </select>

            <!-- Category Filter (Gallery Only) -->
            @if (request()->from != 'mine')
                <select name="category_uid">
                    <option value="">All Categories</option>
                    @foreach (\Acelle\Model\TemplateCategory::all() as $category)
                        <option value="{{ $category->uid }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            @endif

            <!-- Search -->
            <input type="text" name="keyword" class="form-control search">
        </div>

        <!-- Action Buttons -->
        <div class="text-end">
            <a href="{{ action('TemplateController@uploadTemplate') }}"
               class="btn btn-light">
                <span class="material-symbols-rounded">file_upload</span>
                {{ trans('messages.upload') }}
            </a>
            <a href="{{ action('TemplateController@builderCreate') }}"
               class="btn btn-secondary">
                <span class="material-symbols-rounded">add</span>
                {{ trans('messages.create') }}
            </a>
        </div>

        <div id="TemplatesIndexContent" class="pml-table-container"></div>
    </div>

    <script>
        var TemplatesIndex = {
            list: null,
            getList: function() {
                if (this.list == null) {
                    this.list = makeList({
                        url: '{{ action('TemplateController@listing') }}',
                        container: $('#TemplatesIndexContainer'),
                        content: $('#TemplatesIndexContent')
                    });
                }
                return this.list;
            }
        };

        $(document).ready(function() {
            TemplatesIndex.getList().load();
        });
    </script>
@endsection
```

### 6.5 Marketing Automation (`automation2/`)

**46 view files** for automation workflows.

#### **Main Views**

| View | Purpose |
|------|---------|
| `index.blade.php` | Automation list |
| `triggerSelectPupop.blade.php` | Trigger selection modal |

#### **Workflow Components**

**Triggers (`trigger/`):**
- `welcome-new-subscriber.blade.php` - Welcome series
- `say-happy-birthday.blade.php` - Birthday emails
- `say-goodbye-subscriber.blade.php` - Goodbye series
- `monthly-recurring.blade.php` - Recurring campaigns
- `woo-abandoned-cart.blade.php` - Cart abandonment
- Icons for each trigger type

**Conditions (`condition/`):**
- `_wait.blade.php` - Wait/delay condition
- `_click.blade.php` - Link click condition
- `_wait_select.blade.php` - Wait duration selector

**Actions (`action/`):**
- Email sending actions

**Operations (`operation/`):**
- `copy_contact.blade.php` - Copy to list
- `tag_contact.blade.php` - Add/remove tags
- `update_contact.blade.php` - Update fields

**Other Components:**
- `email/` - Email configuration
- `cart/` - Shopping cart integration
- `contacts/` - Contact management
- `subscribers/` - Subscriber actions
- `timeline/` - Workflow timeline
- `wizard/` - Setup wizard

#### **Partials**

| Partial | Purpose |
|---------|---------|
| `_tabs.blade.php` | Automation tabs |
| `_tabs_timeline.blade.php` | Timeline tabs |
| `_back.blade.php` | Back navigation |

### 6.6 Segments (`segments/`)

**14 view files** for subscriber segmentation.

#### **Main Views**

| View | Purpose |
|------|---------|
| `index.blade.php` | Segment list |
| `create.blade.php` | Create segment |
| `edit.blade.php` | Edit segment |
| `subscribers.blade.php` | Segment subscribers |
| `noList.blade.php` | No list selected |

#### **Condition Types (`conditions/`)**

| View | Condition Type |
|------|----------------|
| `date.blade.php` | Date-based conditions |
| `datetime.blade.php` | DateTime conditions |

#### **Operators (`operator/`)**

| View | Operator Type |
|------|---------------|
| `created_date.blade.php` | Created date operators |

#### **Partials**

| Partial | Purpose |
|---------|---------|
| `_form.blade.php` | Segment form |
| `_list.blade.php` | Segment list item |
| `_select_box.blade.php` | Segment selector |
| `_condition_value_control.blade.php` | Condition value input |
| `_sample_condition.blade.php` | Example condition |

---

## 7. Email Builder System

### 7.1 Builder Architecture

Located in `builder/`:

#### **Core Builder Files**

| File | Purpose |
|------|---------|
| `js/widgets.blade.php` | Widget definitions |
| `js/RssElement.blade.php` | RSS feed element |
| `js/RssWidget.blade.php` | RSS widget control |
| `js/RssControl.blade.php` | RSS configuration |
| `js/RssItem.blade.php` | RSS item template |
| `js/ProductElement.blade.php` | Product display element |
| `js/ProductWidget.blade.php` | Product widget |
| `js/ProductControl.blade.php` | Product configuration |
| `js/ProductListElement.blade.php` | Product list element |
| `js/ProductListWidget.blade.php` | Product list widget |
| `js/ProductListControl.blade.php` | Product list config |
| `js/ProductImgElement.blade.php` | Product image element |
| `js/AbandonedCartElement.blade.php` | Cart recovery element |
| `js/AbandonedCartWidget.blade.php` | Cart widget |

#### **Pre-built Themes (`themes/`)**

| Theme | Style |
|-------|-------|
| `kids.blade.php` | Kids/playful theme |
| `yoga.blade.php` | Wellness/yoga theme |

### 7.2 Builder Features

**Drag-and-Drop Elements:**
- Text blocks
- Images
- Buttons
- Dividers
- Social icons
- Products (e-commerce)
- RSS feeds
- Custom HTML

**Dynamic Content:**
- Merge tags (subscriber fields)
- Product catalogs
- RSS feed integration
- Abandoned cart items

**Responsive Design:**
- Mobile preview
- Desktop preview
- Tablet preview

**Template Library:**
- Pre-built templates
- Custom user templates
- Gallery templates

---

## 8. Form Builder System

Located in `forms/`:

| View | Purpose |
|------|---------|
| `index.blade.php` | Form list |
| `create.blade.php` | Form builder |
| `builder.blade.php` | Visual form builder |
| `build.blade.php` | Form construction |
| `list.blade.php` | Form submissions |
| `templates.blade.php` | Form templates |
| `connect.blade.php` | Form integration |
| `content.blade.php` | Form content |

### 8.1 Frontend Forms (`forms/frontend/`)

Public-facing subscription forms with customizable fields and styling.

---

## 9. Admin Panel Views

Located in `admin/`:

### 9.1 Admin Sections

| Directory | Purpose | File Count |
|-----------|---------|------------|
| `customers/` | Customer management | Multiple |
| `subscriptions/` | Subscription management | Multiple |
| `plans/` | Plan management | Multiple |
| `invoices/` | Invoice management | Multiple |
| `sending_servers/` | Mail server config | Multiple |
| `templates/` | Template management | Multiple |
| `settings/` | System settings | 20+ files |
| `languages/` | Language/locale management | Multiple |
| `currencies/` | Currency settings | Multiple |
| `payments/` | Payment gateways | Multiple |
| `plugins/` | Plugin management | Multiple |
| `blacklists/` | Email blacklists | Multiple |
| `bounce_handlers/` | Bounce handling | Multiple |
| `feedback_loop_handlers/` | FBL handlers | Multiple |
| `email_verification_servers/` | Verification services | Multiple |
| `tracking_logs/` | Tracking analytics | Multiple |
| `open_logs/` | Open tracking | Multiple |
| `click_logs/` | Click tracking | Multiple |
| `bounce_logs/` | Bounce logs | Multiple |
| `feedback_logs/` | Feedback logs | Multiple |
| `unsubscribe_logs/` | Unsubscribe logs | Multiple |
| `admins/` | Admin users | Multiple |
| `admins2/` | Admin users v2 | Multiple |
| `admin_groups/` | Admin groups | Multiple |
| `admin_groups2/` | Admin groups v2 | Multiple |
| `sub_accounts/` | Sub-accounts | Multiple |
| `taxes/` | Tax management | Multiple |
| `notifications/` | Notification settings | Multiple |
| `form_templates/` | Form template management | Multiple |
| `search/` | Global search | Multiple |
| `geoip/` | GeoIP configuration | Multiple |

### 9.2 Admin Settings Views

Located in `admin/settings/`:

| View | Purpose |
|------|---------|
| `general.blade.php` | General settings |
| `_general.blade.php` | General partial |
| `_mailer.blade.php` | Mail settings |
| `_sending.blade.php` | Sending settings |
| `_advanced.blade.php` | Advanced settings |
| `_urls.blade.php` | URL configuration |
| `_tabs.blade.php` | Settings tabs |
| `cronjob.blade.php` | Cron job setup |
| `license.blade.php` | License management |
| `logs.blade.php` | System logs |
| `advanced.blade.php` | Advanced options |

**General Settings Subdirectory (`general/`):**
- Brand settings
- Site information
- Frontend configuration

---

## 10. Additional Module Views

### 10.1 Sending Infrastructure

#### **Sending Servers (`sending_servers/`)**

**12 view files** for mail server configuration:

- Server list
- Add/edit servers
- Server types (SMTP, SendGrid, Amazon SES, Mailgun, SparkPost, etc.)
- Test connection
- Sending limits
- Bounce/feedback handlers

**Forms (`form/`):**
- `_aws_region_host.blade.php` - AWS region selector
- `_sending_limit.blade.php` - Rate limit configuration

#### **Sending Domains (`sending_domains/`)**

**9 view files** for domain management:

- Domain list
- Add/edit domains
- DNS verification
- SPF/DKIM setup

#### **Senders (`senders/`)**

**12 view files** for sender identities:

- Sender list
- Add/edit senders
- Email verification
- Sender reputation

### 10.2 Verification & Validation

#### **Email Verification Servers (`email_verification_servers/`)**

**6 view files** for email validation:

- Verification service list
- Add/edit services
- API configuration
- Test verification

#### **Blacklists (`blacklists/`)**

**5 view files** for email blacklist management:

- Blacklist entries
- Add/remove emails
- Import blacklist
- Export blacklist

### 10.3 User Management

#### **Customers (`customers/`)**

**3 view files** for customer management:

- Customer directory
- Customer profiles
- Customer actions

#### **Users (`users/`)**

**6 view files** for user accounts:

- User list
- User profile
- User settings

### 10.4 Subscription & Billing

#### **Plans (`plans/`)**

**6 view files** for subscription plans:

- Plan list
- Create/edit plans
- Plan features
- Pricing configuration

**Public Plan Views (`publicView/`):**
- Public plan selection page

#### **Invoices (`invoices/`)**

**5 view files** for invoicing:

- Invoice list
- Invoice details
- Payment status
- Invoice templates

**Partials:**
- `_template_items.blade.php` - Invoice line items

#### **Subscription (`subscription/`)**

**15 view files** for subscription management:

- Subscription status
- Plan selection
- Upgrade/downgrade
- Billing history
- Payment methods

### 10.5 E-Commerce Integration

#### **Store (`store/`)**

**9 view files** for e-commerce:

- Store dashboard
- Product integration
- Cart tracking
- Order management

**Subdirectories:**
- `products/` - Product management
- `orders/` - Order tracking
- `categories/` - Category management
- `attributes/` - Product attributes
- `media/` - Media management
- `helpers/` - Store helper functions
- `funnels/` - Sales funnels

#### **Products (`products/`)**

**7 view files** for product management:

- Product list
- Add/edit products
- Product sync

**Partials:**
- `_list_list.blade.php` - List view
- `_list_grid.blade.php` - Grid view

#### **Site (`site/`)**

**10 view files** for website integration:

- Site settings
- Product pages
- Category pages
- Customer portal
- Order tracking

**Subdirectories:**
- `products/` - Product pages
- `categories/` - Category pages
- `customers/` - Customer portal
- `orders/` - Order management
- `menus/` - Menu builder
- `sources/` - Data sources
- `templates/` - Site templates
- `settings/` - Site configuration

### 10.6 Websites & Landing Pages

#### **Websites (`websites/`)**

**7 view files** for website builder:

- Website list
- Create/edit websites
- Landing page builder
- Domain management

### 10.7 Content Management

#### **Pages (`pages/`)**

**9 view files** for static pages:

- Page list
- Create/edit pages
- Page templates
- Layout builder

### 10.8 Tracking & Analytics

#### **Tracking Domains (`tracking_domains/`)**

**9 view files** for link tracking:

- Domain list
- Add/edit domains
- DNS setup
- Tracking configuration

### 10.9 Search System

Located in `search/`:

**10 view files** for global search functionality across all resources.

---

## 11. Authentication & Account Views

### 11.1 Authentication (`auth/`)

**7 view files**:

| View | Purpose |
|------|---------|
| `login.blade.php` | Login page |
| `register.blade.php` | Registration page |
| `verify.blade.php` | Email verification |
| `emails/password.blade.php` | Password reset email |
| `passwords/email.blade.php` | Reset request |
| `passwords/reset.blade.php` | Reset password form |

### 11.2 Account Management (`account/`)

**17 view files**:

- Profile settings
- Contact information
- API access
- Notifications
- Security settings
- Theme preferences
- Timezone settings
- Password change
- Two-factor authentication
- API keys
- Webhooks
- Developer settings

---

## 12. Error & Special Pages

### 12.1 Error Pages (`errors/`)

**6 view files**:

| View | HTTP Code |
|------|-----------|
| `403.blade.php` | Forbidden |
| `404.blade.php` | Not Found |
| `500.blade.php` | Server Error |
| `503.blade.php` | Maintenance |
| `general.blade.php` | Generic error |
| `layout.blade.php` | Error layout |

### 12.2 Special State Pages

Located in root `resources/views/`:

| View | Purpose |
|------|---------|
| `notAuthorized.blade.php` | Authorization error |
| `notActivated.blade.php` | Account not activated |
| `isDisabled.blade.php` | Feature disabled |
| `noPrimaryPayment.blade.php` | No payment method |
| `noMoreItem.blade.php` | Quota exceeded |
| `notice.blade.php` | General notice |
| `offline.blade.php` | System offline |
| `termsOfService.blade.php` | Terms of service |
| `somethingWentWrong.blade.php` | Generic error |
| `welcome.blade.php` | Welcome page |
| `home.blade.php` | Home page |
| `demo.blade.php` | Demo mode |
| `demoLogin.blade.php` | Demo login |

### 12.3 Dashboard

**Root Dashboard View:**

| View | Purpose |
|------|---------|
| `dashboard.blade.php` | Main dashboard |
| `_dashboard_campaigns.blade.php` | Campaign widget |
| `_dashboard_list_growth.blade.php` | List growth widget |

---

## 13. Installation & Documentation

### 13.1 Installation Wizard (`install/`)

**9 view files**:

- System requirements check
- Database configuration
- Admin account setup
- License activation
- Initial settings
- Completion

**Partials:**
- `_steps.blade.php` - Installation steps

### 13.2 Documentation (`docs/`)

**3 view files**:

- API documentation (`api/`)
- Developer guides
- Integration docs

---

## 14. Notification System

### 14.1 Notification Views (`notifications/`)

**3 view files**:

- Notification center
- Notification preferences
- Notification history

### 14.2 Quicktip System (`quicktip/`)

**3 view files**:

- Contextual help tips
- Tutorial overlays

---

## 15. Common Patterns & Best Practices

### 15.1 View Structure Pattern

**Standard Index View Pattern:**

```blade
@extends('layouts.core.frontend', ['menu' => 'section_name'])

@section('title', trans('messages.title'))

@section('page_header')
    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item">
                <a href="{{ action("HomeController@index") }}">
                    {{ trans('messages.home') }}
                </a>
            </li>
        </ul>
        <h1>
            <span class="material-symbols-rounded">icon_name</span>
            {{ trans('messages.title') }}
        </h1>
    </div>
@endsection

@section('content')
    <div id="SectionIndexContainer" class="listing-form">
        <!-- Filter Controls -->
        <div class="d-flex top-list-controls">
            <div class="me-auto">
                <div class="filter-box">
                    <!-- Filters and actions -->
                </div>
            </div>
            <div class="text-end">
                <!-- Action buttons -->
            </div>
        </div>

        <!-- Content Area (AJAX loaded) -->
        <div id="SectionIndexContent" class="pml-table-container"></div>
    </div>

    <script>
        var SectionIndex = {
            list: null,
            getList: function() {
                if (this.list == null) {
                    this.list = makeList({
                        url: '{{ action('SectionController@listing') }}',
                        container: $('#SectionIndexContainer'),
                        content: $('#SectionIndexContent')
                    });
                }
                return this.list;
            }
        };

        $(document).ready(function() {
            SectionIndex.getList().load();
        });
    </script>
@endsection
```

### 15.2 AJAX List Loading Pattern

**JavaScript List Object:**

```javascript
var makeList = function(options) {
    var list = {
        url: options.url,
        container: options.container,
        content: options.content,

        // Load list data
        load: function() {
            var data = this.container.serialize();

            $.ajax({
                url: this.url,
                type: 'GET',
                data: data,
                success: function(response) {
                    list.content.html(response);
                }
            });
        },

        // Get form data
        data: function() {
            return this.container.serializeArray();
        }
    };

    // Auto-reload on filter change
    list.container.find('select, input[type=text]').on('change keyup', function() {
        list.load();
    });

    return list;
};
```

### 15.3 Popup Pattern

**Popup Usage:**

```javascript
var popup = new Popup({
    url: '{{ action('Controller@method') }}',
    data: {
        uid: '{{ $item->uid }}'
    }
});

popup.load();

// On submit
popup.on('submit', function() {
    // Reload list or perform action
    SectionIndex.getList().load();
});
```

### 15.4 Bulk Actions Pattern

**Bulk Action Implementation:**

```blade
<!-- Select All Checkbox -->
<input type="checkbox" name="page_checked" class="styled check_all">

<!-- Bulk Actions Dropdown -->
<div class="dropdown list_actions" style="display: none">
    <button class="btn btn-secondary dropdown-toggle">
        {{ trans('messages.actions') }} <span class="number"></span>
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item action"
               link-method="POST"
               link-confirm="{{ trans('messages.confirm_action') }}"
               href="{{ action('Controller@bulkAction') }}">
                <span class="material-symbols-rounded">icon</span>
                {{ trans('messages.action') }}
            </a>
        </li>
    </ul>
</div>

<!-- Item Checkboxes -->
<input type="checkbox" name="uids[]" value="{{ $item->uid }}" class="styled node">

<script>
// Show/hide bulk actions based on selection
$(document).on('change', '.node, .check_all', function() {
    var checked = $('.node:checked').length;

    if (checked > 0) {
        $('.list_actions').show();
        $('.list_actions .number').text('(' + checked + ')');
    } else {
        $('.list_actions').hide();
    }
});
</script>
```

### 15.5 Form Validation Pattern

**Client-side Validation:**

```blade
<form action="{{ action('Controller@store') }}"
      method="POST"
      class="ajax-upload-form form-validate-jquery">
    @csrf

    @include('helpers._text', [
        'label' => trans('messages.name'),
        'name' => 'name',
        'value' => old('name', $item->name),
        'rules' => ['required', 'min:3']
    ])

    <button type="submit" class="btn btn-secondary">
        {{ trans('messages.save') }}
    </button>
</form>

<script>
$(function() {
    $('.form-validate-jquery').validate({
        rules: {
            name: {
                required: true,
                minlength: 3
            }
        }
    });
});
</script>
```

### 15.6 Icon System

**Material Symbols Rounded:**

Acelle uses Google Material Symbols (Rounded variant) throughout:

```blade
<!-- Icon Usage -->
<span class="material-symbols-rounded">icon_name</span>

<!-- Common Icons -->
format_list_bulleted - Lists
add - Create
delete_outline - Delete
edit - Edit
search - Search
people - Subscribers
mail - Email/Campaigns
settings - Settings
dashboard - Dashboard
insights - Analytics
folder - Folders/Lists
content_copy - Copy
save - Save
close - Close
check - Success/Done
error - Error/Warning
info - Information
```

### 15.7 Translation Pattern

**All user-facing strings use Laravel translations:**

```blade
{{ trans('messages.key') }}
{{ trans('messages.key_with_params', ['name' => $name]) }}

{!! trans('messages.key_with_html', ['link' => $link]) !!}
```

### 15.8 Authorization Pattern

**Policy-based Authorization:**

```blade
@if (Auth::user()->customer->can('create', new Acelle\Model\Campaign()))
    <a href="{{ action('CampaignController@create') }}" class="btn btn-secondary">
        {{ trans('messages.create_campaign') }}
    </a>
@endif

@if (Auth::user()->customer->can('update', $campaign))
    <!-- Edit button -->
@endif
```

---

## 16. JavaScript Architecture

### 16.1 Core JavaScript Modules

Located in `public/core/js/`:

| Module | Purpose |
|--------|---------|
| `app.js` | Main application logic |
| `functions.js` | Utility functions |
| `list.js` | AJAX list management |
| `popup.js` | Modal/popup management |
| `dialog.js` | Confirmation dialogs |
| `box.js` | Box/container utilities |
| `link.js` | Link action handling |
| `sidebar.js` | Sidebar interactions |
| `search.js` | Search functionality |
| `anotify.js` | Notification system |
| `iframe_modal.js` | Iframe modal handling |
| `image_popup.js` | Image lightbox |
| `validate.js` | Form validation |
| `autofill.js` | Autofill/autocomplete |

### 16.2 Global JavaScript Variables

From `layouts/core/_script_vars.blade.php`:

```javascript
// Laravel routes and URLs
var APP_URL = '{{ config('app.url') }}';
var CSRF_TOKEN = '{{ csrf_token() }}';

// User context
var USER_ID = '{{ Auth::user()->id }}';
var CUSTOMER_ID = '{{ Auth::user()->customer->id }}';

// Theme settings
var THEME_MODE = '{{ Auth::user()->customer->theme_mode }}';
var COLOR_SCHEME = '{{ Auth::user()->customer->getColorScheme() }}';
var ECHARTS_THEME = '{{ Auth::user()->customer->theme_mode == 'dark' ? 'dark' : null }}';

// Localization
var LOCALE = '{{ Auth::user()->customer->language->code }}';
var TIMEZONE = '{{ Auth::user()->customer->timezone }}';

// Feature flags
var SAAS_MODE = {{ config('app.saas') ? 'true' : 'false' }};
var DEMO_MODE = {{ config('app.demo') ? 'true' : 'false' }};
```

---

## 17. Recommendations for Mailing Module Migration

### 17.1 Critical Views to Replicate

**High Priority:**

1. **Campaign Management:**
   - `campaigns/index.blade.php` - Campaign list with filters
   - `campaigns/overview.blade.php` - Analytics dashboard
   - `campaigns/template_build.blade.php` - Email editor
   - `campaigns/_chart.blade.php` - Analytics charts
   - `campaigns/_24h_chart.blade.php` - Recent activity
   - `campaigns/_top_link.blade.php` - Link tracking

2. **List Management:**
   - `lists/index.blade.php` - List directory
   - `lists/overview.blade.php` - List dashboard
   - `lists/embedded_form.blade.php` - Subscribe forms
   - `lists/_growth_chart.blade.php` - Growth analytics

3. **Subscriber Management:**
   - `subscribers/index.blade.php` - Subscriber list with advanced filters
   - `subscribers/import2/*` - Import wizard
   - `subscribers/export.blade.php` - Export functionality
   - `subscribers/copy_move_form.blade.php` - Bulk operations

4. **Template System:**
   - `templates/index.blade.php` - Template gallery
   - `templates/builder/edit.blade.php` - Visual builder
   - Email builder JavaScript components

5. **Automation:**
   - `automation2/index.blade.php` - Workflow list
   - Trigger, condition, and action components

### 17.2 Reusable Components to Extract

**Form Helpers:**
- All helper components in `helpers/` directory
- Select2 integration patterns
- Date/time picker implementations
- Tag selection components

**UI Elements:**
- List filtering system
- Bulk action dropdowns
- AJAX pagination
- Sort controls
- Search boxes

**JavaScript Modules:**
- `list.js` - AJAX list management
- `popup.js` - Modal system
- Form validation setup
- Chart integration (ECharts)

### 17.3 Layout Integration Strategy

**Adapt Acelle Patterns to Bootstrap Modernize:**

1. **Layout Structure:**
   - Map Acelle's `frontend.blade.php` to Modernize's main layout
   - Preserve sidebar/topbar structure
   - Maintain breadcrumb patterns

2. **Component Mapping:**
   - Acelle's filter boxes → Modernize card filters
   - Material icons → Font Awesome 6 (per project rules)
   - Acelle buttons → Modernize button styles
   - Acelle tables → Modernize DataTables

3. **Theme System:**
   - Adapt color scheme system to Modernize themes
   - Integrate dark mode support
   - Maintain menu layout preferences

4. **JavaScript Architecture:**
   - Keep AJAX list loading pattern
   - Adapt popup system to Bootstrap 5 modals
   - Integrate validation with Modernize patterns

### 17.4 Critical Features to Maintain

1. **AJAX List Loading:**
   - Server-side pagination
   - Real-time filtering
   - Sorting without page reload

2. **Bulk Actions:**
   - Select all/none
   - Batch operations
   - Confirmation dialogs

3. **Dynamic Columns:**
   - User-selectable columns
   - Field-based filtering

4. **Embedded Forms:**
   - Customizable subscribe forms
   - iframe/JavaScript embed options

5. **Email Builder:**
   - Drag-and-drop interface
   - Dynamic content blocks
   - Responsive preview

### 17.5 Migration Priority Matrix

| Component | Priority | Complexity | Dependencies |
|-----------|----------|------------|--------------|
| Campaign List | High | Medium | List.js, Filters |
| Campaign Analytics | High | High | ECharts, AJAX |
| List Management | High | Low | Standard CRUD |
| Subscriber List | High | High | Dynamic columns, Bulk actions |
| Import Wizard | High | Medium | Multi-step form |
| Template Gallery | High | Medium | Grid/List views |
| Email Builder | Critical | Very High | Builder.js, Widgets |
| Automation Workflow | Medium | Very High | Visual flow builder |
| Segments | Medium | Medium | Condition builder |
| Forms | Medium | Low | Form builder |

---

## 18. File Naming Conventions

### 18.1 View File Patterns

**Main Views:**
- `index.blade.php` - List/index view
- `create.blade.php` - Create form
- `edit.blade.php` - Edit form
- `show.blade.php` - Detail view
- `overview.blade.php` - Dashboard/overview

**Partials (prefix with `_`):**
- `_form.blade.php` - Reusable form
- `_list.blade.php` - List item template
- `_menu.blade.php` - Navigation menu
- `_header.blade.php` - Section header
- `_tabs.blade.php` - Tab navigation

**Modals/Popups:**
- `{action}Popup.blade.php` - Popup view
- `{action}Confirm.blade.php` - Confirmation dialog

**Specialized:**
- `{feature}Log.blade.php` - Log views
- `{feature}List.blade.php` - Alternative list view
- `delete_confirm.blade.php` - Delete confirmation

### 18.2 Directory Naming

- **Lowercase with underscores:** `sending_servers`, `email_verification_servers`
- **Version suffixes:** `automation2`, `import2`, `admins2`
- **Nested resources:** `campaigns/template/`, `subscribers/import2/`

---

## 19. Integration Points

### 19.1 Third-Party Services

**Email Sending:**
- SMTP servers
- Amazon SES
- SendGrid
- Mailgun
- SparkPost
- Postal
- Elastic Email

**Email Verification:**
- NeverBounce
- ZeroBounce
- EmailListVerify
- QuickEmailVerification

**E-Commerce:**
- WooCommerce
- Shopify
- PrestaShop
- Custom cart integrations

**Analytics:**
- ECharts for visualization
- Google Analytics integration
- Custom event tracking

### 19.2 API Integration

**Webhook Views:**
- `campaigns/webhooksList.blade.php` - Webhook management
- Webhook configuration in admin settings

**API Documentation:**
- `docs/api/` - API reference views

---

## 20. Advanced Features Documented in Views

### 20.1 A/B Testing

Campaign type selection includes A/B testing options.

### 20.2 Recurring Campaigns

Automation triggers include monthly recurring campaigns.

### 20.3 Cart Abandonment

- `automation2/cart/` - Cart tracking
- `builder/js/AbandonedCartElement.blade.php` - Cart recovery emails

### 20.4 RSS-to-Email

- `builder/js/RssElement.blade.php` - RSS feed integration
- `helpers/rss/` - RSS template helpers

### 20.5 Multi-List Management

- List cloning: `lists/copy.blade.php`
- Subscriber copy/move: `subscribers/copy_move_form.blade.php`

### 20.6 Email Verification

- List-level verification: `lists/email_verification.blade.php`
- Progress tracking: `lists/email_verification_progress.blade.php`
- Subscriber verification status filter

### 20.7 Segmentation

- Dynamic segments with conditions
- Operator-based filtering
- Date/datetime conditions

### 20.8 Embedded Forms

- Customizable subscribe forms
- CAPTCHA support: `lists/embedded_form_captcha.blade.php`
- Error handling: `lists/embedded_form_errors.blade.php`
- Frame embedding: `lists/embedded_form_frame.blade.php`

---

## 21. Performance Optimizations

### 21.1 AJAX List Loading

- Pagination on server-side
- Lazy loading of content
- Filter caching

### 21.2 Asset Loading

- CDN for Google Fonts
- Minified CSS/JS
- Conditional asset loading (ECharts only on analytics pages)

### 21.3 Responsive Design

- Mobile-first approach
- Adaptive layouts
- Touch-friendly interfaces

---

## 22. Security Patterns

### 22.1 CSRF Protection

All forms include CSRF tokens:

```blade
@csrf
```

### 22.2 XSS Prevention

Blade escaping by default:

```blade
{{ $variable }} <!-- Escaped -->
{!! $htmlVariable !!} <!-- Unescaped, use with caution -->
```

### 22.3 Authorization Checks

Policy-based access control in all views:

```blade
@if (Auth::user()->customer->can('action', $model))
    <!-- Protected action -->
@endif
```

### 22.4 Link Method Security

Link actions with confirmation:

```blade
<a href="{{ action('Controller@delete') }}"
   link-method="POST"
   link-confirm="{{ trans('messages.confirm_delete') }}"
   class="dropdown-item">
    Delete
</a>
```

---

## 23. Accessibility Features

### 23.1 ARIA Labels

Buttons and interactive elements include ARIA labels:

```blade
<button aria-label="{{ trans('messages.action') }}">
    <span class="material-symbols-rounded">icon</span>
</button>
```

### 23.2 Keyboard Navigation

- Focus states on interactive elements
- Tab navigation support
- Escape key to close modals

### 23.3 Screen Reader Support

- Semantic HTML structure
- Descriptive labels
- Status announcements

---

## 24. Conclusion & Summary

### 24.1 Key Takeaways

1. **Comprehensive System:** 930+ view files covering complete email marketing platform
2. **Modular Architecture:** Reusable components, partials, and helpers
3. **Theme System:** 8 color schemes, light/dark modes, menu layouts
4. **AJAX-Driven:** Dynamic list loading, filtering, and bulk actions
5. **Bootstrap 5:** Modern responsive framework with custom extensions
6. **Material Icons:** Consistent icon system throughout
7. **Policy-Based Auth:** Granular permission checks in views
8. **Localization:** All text translatable via Laravel's trans() function

### 24.2 Critical Paths for Mailing Module

**Must-Have Views:**
1. Campaign management (list, create, edit, analytics)
2. List management (list, create, edit, subscribers)
3. Subscriber management (list, import, export, bulk actions)
4. Template management (gallery, builder, editor)
5. Email builder components

**Nice-to-Have Views:**
1. Automation workflows
2. Segments
3. Forms
4. Advanced analytics
5. A/B testing

**Can Be Deferred:**
1. Store integration
2. Site builder
3. Multiple admin panels
4. Plugin system

### 24.3 Migration Checklist

- [ ] Extract reusable form helpers
- [ ] Adapt layout system to Modernize
- [ ] Port AJAX list loading pattern
- [ ] Integrate ECharts for analytics
- [ ] Implement bulk action system
- [ ] Build email builder interface
- [ ] Create import/export wizards
- [ ] Set up theme/color system
- [ ] Implement dynamic column selection
- [ ] Add embedded form generator
- [ ] Build automation workflow UI
- [ ] Create segment condition builder

---

## Document Metadata

**Created:** 2026-01-29
**Author:** Claude Code Assistant
**Version:** 1.0
**Last Updated:** 2026-01-29
**Related Documents:**
- `ACELLE_DATABASE_SCHEMA.md`
- `ACELLE_MODELS_RELATIONSHIPS.md`
- `MAILING_INFRASTRUCTURE_VERIFICATION_REPORT.md`

---

**End of Analysis**
