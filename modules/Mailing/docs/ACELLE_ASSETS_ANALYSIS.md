# Acelle Assets Analysis Report

**Analysis Date:** January 29, 2026
**Source Directories:**
- `/Users/functionbytes/Function/Coding/acelle/public/`
- `/Users/functionbytes/Function/Coding/acelle/resources/assets/`

---

## Executive Summary

Acelle is a legacy Laravel email marketing application with a comprehensive frontend stack built on **Bootstrap 5.2.3**, **jQuery 3.6.4**, and a custom email builder. The application uses **Laravel Mix** for asset compilation and features a rich ecosystem of third-party libraries for editing, file management, and data visualization.

---

## 1. Build System & Compilation

### Laravel Mix Configuration
**File:** `/webpack.mix.js`

```javascript
mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', []);
```

**Package Manager:** npm
**Build Tool:** Laravel Mix v6.0.6
**Commands:**
- `npm run dev` - Development build
- `npm run watch` - Watch mode
- `npm run production` - Production build with minification

### Source Structure

```
resources/assets/
├── js/
│   ├── app.js              # Vue.js initialization (legacy)
│   ├── bootstrap.js        # Core dependencies loader
│   └── components/         # Vue components (minimal usage)
└── sass/
    ├── _variables.scss     # SCSS variables
    └── app.scss            # Main SCSS entry (mostly commented out)
```

**Note:** The SASS compilation is minimal - most styles are pre-compiled in `/public/core/css/`.

---

## 2. Core JavaScript Libraries

### 2.1 Primary Framework

| Library | Version | Location | Purpose |
|---------|---------|----------|---------|
| **jQuery** | 3.6.4 | `/public/core/js/jquery-3.6.4.min.js` | DOM manipulation, AJAX |
| **jQuery Migrate** | 3.4.1 | `/public/core/js/jquery-migrate-3.4.1.min.js` | Backward compatibility |
| **Bootstrap** | 5.2.3 | `/public/core/bootstrap/js/bootstrap.bundle.min.js` | UI framework |

### 2.2 NPM Dependencies (from package.json)

```json
{
  "devDependencies": {
    "axios": "^0.21",
    "laravel-mix": "^6.0.6",
    "lodash": "^4.17.19",
    "postcss": "^8.1.14"
  }
}
```

**Bootstrap Configuration (resources/assets/js/bootstrap.js):**
- Loads Lodash for utility functions
- Configures Axios for HTTP requests with CSRF token handling
- Sets up Laravel Echo (commented out - WebSocket support available)

### 2.3 Vue.js Integration (Legacy/Minimal)

**File:** `resources/assets/js/app.js`

```javascript
window.Vue = require('vue');
Vue.component('example', require('./components/Example.vue'));
const app = new Vue({ el: '#app' });
```

**Status:** Present but minimally utilized. The application primarily uses jQuery.

---

## 3. CSS Framework & Styling

### 3.1 Bootstrap 5.2.3

**Location:** `/public/core/bootstrap/css/bootstrap.min.css`
**Version:** 5.2.3 (verified from source)
**Bundle:** Includes full Bootstrap CSS grid, components, and utilities

### 3.2 Custom Stylesheets

**Main Application Styles:** `/public/core/css/`

| File | Size | Purpose |
|------|------|---------|
| `app.css` | 174KB | Main application stylesheet |
| `dark.css` | 34KB | Dark mode theme |
| `automation.css` | 26KB | Automation workflow styles |
| `menu.css` | 14KB | Navigation and sidebar |
| `laza.css` | 13KB | Custom UI components |
| `form_popup_content.css` | 12KB | Modal form styles |
| `builder-custom.css` | 2.5KB | Email builder customizations |
| `responsive.css` | 698B | Mobile responsive adjustments |

### 3.3 Icon System

**Font Awesome 5.10.1** (Email Builder Only)
**Location:** `/public/builder/iframe/fontawesome-free/`
**Classes:** `.fa`, `.fas`, `.far`, `.fab`, `.fal`, `.fad`

**Custom Icon Font:** `/public/core/font/` (Google Material Icons style)
**CSS Class Prefix:** `.icon-*` (custom icon implementation)

**Important:** The main application uses a **custom icon font**, NOT Font Awesome globally.

---

## 4. Email Builder Infrastructure

### 4.1 Builder Core

**Location:** `/public/builder/`
**Main Files:**
- `builder.js` (2.0MB) - Email template builder engine
- `builder.css` (1.5MB) - Builder-specific styles

**Components:**
- Drag-and-drop email template editor
- WYSIWYG content editing
- Template library system
- Multi-language support

### 4.2 Builder Dependencies

| Library | Version | Purpose |
|---------|---------|---------|
| **TinyMCE** | 5.x | Rich text editor for content blocks |
| **Fabric.js** | 1.6.7 | Canvas-based image editing |
| **TUI Image Editor** | 3.9.0 | Advanced image manipulation |
| **TUI Color Picker** | 2.2.0 | Color selection interface |
| **Font Awesome** | 5.10.1 | Icons within email templates |

**TinyMCE Plugins Loaded:**
- imagetools, code, searchreplace, lists, textcolor
- template, contextmenu, quickbars, codesample
- toc, visualblocks, textpattern, directionality

---

## 5. File Management System

### 5.1 FileManager2

**Location:** `/public/filemanager2/`
**Purpose:** Media library, file uploads, image editing

**Key Libraries:**

| Library | Purpose |
|---------|---------|
| jQuery File Upload | AJAX file uploads with progress bars |
| jQuery UI 1.12.1 | Drag-and-drop, sortable lists |
| jPlayer 2.9.2 | Audio/video preview |
| TUI Image Editor | Image cropping and filters |
| FileSaver.js 1.3.3 | Client-side file downloads |

**Features:**
- Multi-file upload with drag-and-drop
- Image editing (crop, rotate, filters)
- Audio/video playback
- File browser with thumbnails
- RTL (Right-to-Left) language support

---

## 6. UI Components & Widgets

### 6.1 Form Controls

| Component | Library | Location |
|-----------|---------|----------|
| **Select2** | 4.x | `/public/core/select2/` |
| **Date Picker** | Pickadate.js | `/public/core/datetime/` |
| **Range Slider** | Custom | `/public/core/rangeslider/` |
| **Numeric Input** | Custom | `/public/core/numeric/` |
| **Inline Editing** | Bootstrap Editable 3 | `/public/core/bootstrap3-editable/` |
| **Dropzone** | Dropzone.js | `/public/core/dropzone/` |
| **Emoji Picker** | EmojiOne Area | `/public/core/emojionearea/` |

**Select2 Configuration (from functions.js):**
```javascript
container.find('.select').select2({
    dropdownAutoWidth: true,
    minimumResultsForSearch: 30,
    escapeMarkup: function(markup) {
        return markup;
    }
});
```

### 6.2 Code Editors

| Editor | Purpose | Location |
|--------|---------|----------|
| **ACE Editor** | Code editing (HTML, CSS, JS) | `/public/core/ace/` |
| **TinyMCE** | WYSIWYG HTML editing | `/public/core/tinymce/` |
| **Prism.js** | Syntax highlighting | `/public/core/prismjs/` |

**ACE Editor Modes:** 99+ syntax highlighters available

### 6.3 Visualization

| Library | Purpose | Size |
|---------|---------|------|
| **ECharts** | Charts and graphs | 986KB (minified) |
| **ECharts Dark Theme** | Dark mode charts | 3.8KB |

**Location:** `/public/core/echarts/`

### 6.4 UX Enhancements

| Library | Purpose | Location |
|---------|---------|----------|
| **Tooltipster** | Advanced tooltips | `/public/core/tooltipster/` |
| **LightSlider** | Image carousels | `/public/core/lightslider/` |
| **Custom UX Library** | Acelle-specific interactions | `/public/core/ux/` |
| **jQuery Validation** | Form validation | `/public/core/validate/` |

---

## 7. Core JavaScript Modules

**Location:** `/public/core/js/`

### 7.1 Main Application Scripts

| File | Size | Purpose |
|------|------|---------|
| `functions.js` | 29KB | Core utility functions, component initialization |
| `automation.js` | 39KB | Email automation workflow engine |
| `search.js` | 43KB | Advanced search functionality |
| `dialog.js` | 8.6KB | Modal dialogs and confirmations |
| `editor.js` | 7KB | WYSIWYG editor integration |
| `list.js` | 6.5KB | List management (subscribers, campaigns) |
| `popup.js` | 6.6KB | Popup window handling |
| `autofill.js` | 9.9KB | Form auto-fill functionality |
| `validate.js` | 5KB | Custom validation rules |
| `sidebar.js` | 3KB | Sidebar navigation logic |

### 7.2 Initialization Pattern (from functions.js)

```javascript
function initJs(container) {
    // Tooltip initialization
    container.find('.xtooltip:not([title=""])')
        .tooltipster({ theme: 'tooltipster-light' });

    // Select2 initialization
    container.find('.select').select2({
        dropdownAutoWidth: true,
        minimumResultsForSearch: 30
    });

    // Date picker initialization
    container.find('.pickadate-control').pickadate({
        format: 'yyyy-mm-dd',
        selectMonths: true,
        selectYears: 60
    });
}
```

**Pattern:** Dynamic component initialization on container load (supports AJAX content).

---

## 8. Asset Organization

### 8.1 Public Directory Structure

```
public/
├── core/                    # Core libraries and assets
│   ├── bootstrap/           # Bootstrap 5.2.3
│   ├── css/                 # Application stylesheets (25+ files)
│   ├── js/                  # Core JavaScript (22 files)
│   ├── font/                # Custom web fonts (76 files)
│   ├── select2/             # Select2 dropdown library
│   ├── tinymce/             # TinyMCE editor
│   ├── echarts/             # Data visualization
│   ├── ace/                 # Code editor
│   ├── datetime/            # Date/time pickers
│   ├── dropzone/            # File upload widget
│   ├── emojionearea/        # Emoji picker
│   ├── tooltipster/         # Tooltip library
│   └── [15+ other components]
├── builder/                 # Email template builder
│   ├── builder.js           # Builder engine (2MB)
│   ├── builder.css          # Builder styles (1.5MB)
│   ├── iframe/              # Isolated builder environment
│   │   ├── tinymce/         # Editor for content blocks
│   │   ├── fontawesome-free/ # FA 5.10.1 for templates
│   │   └── events.js        # Builder event handlers
│   ├── templates/           # Pre-built email templates
│   ├── language/            # i18n translations
│   └── [fonts, images]
├── filemanager2/            # Media library
│   ├── css/                 # File manager styles
│   ├── js/                  # Upload & editing libraries
│   │   ├── jquery-ui-1.12.1/
│   │   ├── jPlayer-2.9.2/
│   │   ├── tui.image-editor-3.9.0/
│   │   ├── fabric.js-1.6.7/
│   │   └── jquery.fileupload*.js
│   └── [plugins, themes]
├── images/                  # Application images (87 files)
├── favicon/                 # Favicon variants (27 files)
├── svg/                     # Error page illustrations
├── themes/                  # Custom themes (kids, yoga)
├── api/                     # API documentation assets
├── vendor/                  # Third-party packages
└── [index.php, .htaccess, robots.txt]
```

### 8.2 Critical Assets Inventory

**Essential for Application:**
1. `/public/core/js/jquery-3.6.4.min.js` - Core dependency
2. `/public/core/bootstrap/` - UI framework
3. `/public/core/css/app.css` - Main application styles
4. `/public/core/js/functions.js` - Application initialization
5. `/public/builder/builder.js` - Email editor (if using email features)

**Total Asset Size:** ~15-20MB (uncompressed)

---

## 9. Browser Compatibility & Loading Strategy

### 9.1 AJAX Content Loading

**Pattern:** Components are re-initialized when AJAX loads new content

```javascript
// After AJAX load
$.ajax({
    success: function(response) {
        var container = $('#content-area').html(response);
        initJs(container); // Re-initialize plugins
    }
});
```

### 9.2 Lazy Loading

**Not Implemented:** All JavaScript is loaded upfront. Potential optimization opportunity.

### 9.3 Browser Requirements

- **Minimum:** ES5 support (IE11+)
- **Recommended:** Modern browsers (Chrome 90+, Firefox 88+, Safari 14+)
- **Mobile:** iOS Safari 12+, Chrome Android 90+

**jQuery Migrate:** Used for backward compatibility with older jQuery plugins.

---

## 10. Internationalization (i18n)

### 10.1 Builder Translations

**Location:** `/public/builder/language/`
**Format:** JSON language files
**Languages Supported:** Multiple (directory contains translation files)

### 10.2 Application Locale

**Google Fonts:** Open Sans loaded with multiple character sets
**Font File:** `/public/core/css/google-open-sans.css` (19KB)
**RTL Support:** Available in filemanager2 (`rtl-style.css`)

---

## 11. Performance Considerations

### 11.1 Current State

**Strengths:**
- Minified core libraries (jQuery, Bootstrap)
- Pre-compiled CSS (no runtime SASS compilation)
- Separate builder context (iframe isolation)

**Weaknesses:**
- No code splitting (all JS loaded upfront)
- Large builder.js (2MB) even if not used
- Multiple HTTP requests for individual components
- No service worker or caching strategy

### 11.2 Optimization Opportunities

1. **Code Splitting:** Load builder.js only on email editing pages
2. **CDN:** Serve jQuery, Bootstrap from CDN with local fallback
3. **Image Optimization:** Compress images in `/public/images/`
4. **HTTP/2:** Enable for parallel asset loading
5. **Lazy Loading:** Defer non-critical components (ECharts, ACE Editor)
6. **Bundle Consolidation:** Combine core.js modules into single file

### 11.3 Asset Loading Order

**Current Pattern (typical page):**
```html
<!-- Core dependencies -->
<script src="/core/js/jquery-3.6.4.min.js"></script>
<script src="/core/js/jquery-migrate-3.4.1.min.js"></script>
<script src="/core/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Component libraries -->
<script src="/core/select2/js/select2.min.js"></script>
<script src="/core/datetime/pickadate.js"></script>
<script src="/core/echarts/echarts.min.js"></script>

<!-- Application scripts -->
<script src="/core/js/functions.js"></script>
<script src="/core/js/automation.js"></script>
<script src="/core/js/search.js"></script>
```

---

## 12. Migration Considerations (Mailing Module)

### 12.1 Assets to Preserve

**Critical for Email Features:**
- Email builder (`/public/builder/`) - Core functionality
- TinyMCE editor (`/public/core/tinymce/`) - Content editing
- File manager (`/public/filemanager2/`) - Media library
- Automation engine (`/public/core/js/automation.js`) - Workflow logic

**Reusable Components:**
- Select2 dropdowns - Already used in main system
- Date pickers - Modernize equivalent available
- Form validation - Can migrate to Laravel validation
- Tooltips - Bootstrap 5.3 native tooltips preferred

### 12.2 Replace vs. Keep

| Component | Action | Reason |
|-----------|--------|--------|
| **Bootstrap 5.2.3** | ✅ Keep/Upgrade to 5.3 | Already using Bootstrap 5.3 in main system |
| **jQuery 3.6.4** | ⚠️ Evaluate | Main system uses Vue 3, but builder relies heavily on jQuery |
| **Email Builder** | ✅ Keep | Core functionality, no equivalent in main system |
| **Font Awesome 5.10.1** | ⚠️ Replace with FA 6 | Main system uses Font Awesome 6 |
| **Custom Icon Font** | ❌ Replace | Migrate to Font Awesome 6 icons |
| **Vue.js (legacy)** | ❌ Remove | Minimal usage, main system has Vue 3 + Inertia |
| **Laravel Mix** | ⚠️ Migrate to Vite | Main system uses Vite |
| **ECharts** | ✅ Keep | Good for analytics dashboards |
| **ACE Editor** | ✅ Keep | Useful for template editing |

### 12.3 Asset Migration Strategy

**Phase 1: Isolation**
1. Copy `/public/builder/` to `/modules/Mailing/public/builder/`
2. Copy required `/public/core/` components to module
3. Create module-specific asset manifest

**Phase 2: Modernization**
1. Upgrade Bootstrap 5.2.3 → 5.3 (align with main system)
2. Replace Font Awesome 5.10.1 → 6.x (consistency)
3. Migrate Laravel Mix → Vite (main build system)
4. Convert custom icons to Font Awesome 6 equivalents

**Phase 3: Optimization**
1. Bundle builder assets separately (code splitting)
2. Implement lazy loading for editor components
3. Use main system's Axios instance (remove duplicate)
4. Share common components (Select2, validation) with main system

---

## 13. Third-Party Library Licenses

**Review Required:** Before migration, verify licenses for:

| Library | License | Commercial Use |
|---------|---------|----------------|
| jQuery 3.6.4 | MIT | ✅ Allowed |
| Bootstrap 5.2.3 | MIT | ✅ Allowed |
| TinyMCE | LGPL 2.1 / Commercial | ⚠️ Verify usage |
| Font Awesome 5.10.1 | CC BY 4.0 (icons), SIL OFL 1.1 (fonts), MIT (code) | ✅ Allowed |
| Fabric.js | MIT | ✅ Allowed |
| ECharts | Apache 2.0 | ✅ Allowed |
| Select2 | MIT | ✅ Allowed |
| ACE Editor | BSD | ✅ Allowed |

**Note:** TinyMCE may require commercial license for production use. Verify current Acelle license.

---

## 14. Security Considerations

### 14.1 Outdated Dependencies

**Vulnerabilities to Address:**

| Library | Current | Latest | Risk |
|---------|---------|--------|------|
| Axios | 0.21.x | 1.6.x | 🔴 High (known CVEs) |
| jQuery | 3.6.4 | 3.7.1 | 🟡 Medium |
| Bootstrap | 5.2.3 | 5.3.x | 🟢 Low |
| Laravel Mix | 6.0.6 | 6.0.49 | 🟡 Medium |

**Action Required:**
1. Update Axios immediately (security patches)
2. Review jQuery for XSS vulnerabilities
3. Audit file upload components (Dropzone, FileManager)

### 14.2 CSRF Protection

**Implementation:** Axios configured with Laravel CSRF token

```javascript
// From bootstrap.js
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}
```

**Status:** ✅ Properly implemented

---

## 15. Documentation & Resources

### 15.1 Library Documentation

**Email Builder:**
- No official documentation found in codebase
- Proprietary Acelle builder (custom implementation)
- Reverse engineering required for deep integration

**TinyMCE:**
- Official docs: https://www.tiny.cloud/docs/
- Version: 5.x (check for EOL status)

**Fabric.js:**
- Official docs: http://fabricjs.com/docs/
- Version: 1.6.7 (very old, consider updating)

### 15.2 Missing Documentation

**Critical Gaps:**
1. Email builder API reference
2. Custom icon font mapping (icon-* classes)
3. Automation engine workflow specification
4. File manager integration guide

---

## 16. Testing Recommendations

### 16.1 Asset Load Testing

**Before Migration:**
```bash
# Test all assets load correctly
npm run production

# Check bundle sizes
ls -lh public/js/
ls -lh public/css/

# Verify no 404s
curl -I http://localhost/core/js/jquery-3.6.4.min.js
curl -I http://localhost/builder/builder.js
```

### 16.2 Compatibility Testing

**Email Builder:**
1. Create new email template
2. Test all editor features (text, images, buttons)
3. Send test email, verify rendering
4. Test on mobile devices

**File Manager:**
1. Upload image, video, document
2. Edit image (crop, filters)
3. Delete file
4. Verify permissions

---

## 17. Conclusion & Next Steps

### 17.1 Summary

Acelle's frontend is a **mature jQuery-based application** with a comprehensive email builder. The asset structure is well-organized but shows signs of age:

**Strengths:**
- Modular component structure
- Comprehensive email editing capabilities
- Well-tested file management system
- Dark mode support

**Weaknesses:**
- Legacy build system (Laravel Mix vs. Vite)
- Outdated dependencies (security risk)
- No code splitting (performance)
- Inconsistent icon system (custom vs. Font Awesome)

### 17.2 Recommended Actions

**Immediate (Before Migration):**
1. ✅ Update Axios to latest version (security)
2. ✅ Audit email builder for XSS vulnerabilities
3. ✅ Document custom icon mappings
4. ✅ Create asset migration checklist

**Short-term (During Migration):**
1. Isolate builder assets in Mailing module
2. Replace custom icons with Font Awesome 6
3. Migrate build system to Vite
4. Implement code splitting for builder

**Long-term (Post-Migration):**
1. Evaluate replacing jQuery with Alpine.js (consistency)
2. Migrate to modern email builder (if available)
3. Implement asset CDN strategy
4. Add automated frontend tests (Cypress/Playwright)

---

## 18. Asset Migration Checklist

```markdown
### Required Files for Mailing Module

- [ ] /public/builder/ (entire directory)
- [ ] /public/filemanager2/ (entire directory)
- [ ] /public/core/tinymce/ (rich text editor)
- [ ] /public/core/select2/ (dropdowns)
- [ ] /public/core/datetime/ (date pickers)
- [ ] /public/core/echarts/ (charts)
- [ ] /public/core/js/automation.js (workflow engine)
- [ ] /public/core/js/functions.js (initialization)
- [ ] /public/core/css/automation.css (workflow styles)
- [ ] /public/core/bootstrap/ (if version differs from main)
- [ ] /public/images/ (email-related assets only)

### Files to Replace/Modernize

- [ ] Font Awesome 5.10.1 → 6.x
- [ ] Custom icon font → Font Awesome 6
- [ ] Axios 0.21 → 1.6+
- [ ] jQuery 3.6.4 → 3.7.1 (or evaluate removal)
- [ ] Laravel Mix → Vite config

### Optional/Evaluate

- [ ] /public/core/ace/ (code editor - needed?)
- [ ] /public/core/emojionearea/ (emoji picker - needed?)
- [ ] /public/themes/ (custom themes - relevant?)
- [ ] Vue.js resources (minimal usage, remove?)
```

---

**Report Prepared By:** Claude Code Analysis Agent
**Next Review:** After initial migration testing
**Contact:** Refer to project documentation for technical questions
