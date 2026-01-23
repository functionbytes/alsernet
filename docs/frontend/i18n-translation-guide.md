# i18n Translation Guide - LiveChat Widget

## Overview

The AlserNet LiveChat widget now supports multi-language translations with automatic language detection. Languages are detected from the `data-lang` attribute on the body or html element, with fallback to browser language or default Spanish (es).

## Supported Languages

- **Spanish** (es) - Default
- **English** (en)
- **Portuguese** (pt)
- **French** (fr)
- **German** (de)
- **Italian** (it)

## How It Works

### 1. Language Detection

The widget automatically detects the language in this order:

1. **`data-lang` attribute on `<body>`** - Highest priority
2. **`data-lang` attribute on `<html>`** - Second priority
3. **`lang` attribute on `<html>`** - Third priority (e.g., `lang="en-US"` → extracts "en")
4. **Browser language** - Uses navigator.language (e.g., en-US → en)
5. **Default to Spanish** (es) - Fallback

### 2. Setting Language on Your Website

Add the `data-lang` attribute to your HTML body tag:

```html
<!-- Spanish -->
<body data-lang="es">
  ...
</body>

<!-- English -->
<body data-lang="en">
  ...
</body>

<!-- Portuguese -->
<body data-lang="pt">
  ...
</body>

<!-- French -->
<body data-lang="fr">
  ...
</body>

<!-- German -->
<body data-lang="de">
  ...
</body>

<!-- Italian -->
<body data-lang="it">
  ...
</body>
```

### 3. Using Translations in React Components

Import and use the `useTranslation` hook:

```tsx
import { useTranslation } from '../i18n/useLanguage';

export function MyComponent() {
    const t = useTranslation();

    return (
        <div>
            <h1>{t('home.greeting')}</h1>
            <p>{t('home.greeting_message')}</p>
        </div>
    );
}
```

### 4. Translation Keys Structure

All translations are organized by feature (home, help, article, chat, etc.):

```typescript
{
    home: {
        greeting: 'Message text',
        greeting_message: 'Another message',
        send_message: 'Button text',
        // ...
    },
    help: {
        title: 'Help Center',
        search_placeholder: 'Search...',
        // ...
    },
    // ...
}
```

### 5. Variable Replacement

Translations support variable placeholders using the `:variable` syntax:

```typescript
// In translations.ts
queue_message: 'You are number :number in the queue.'

// In component
const message = t('chat.queue_message', { number: '5' });
// Result: "You are number 5 in the queue."
```

## Translation Files

### Backend (Laravel)

Located in `resources/lang/`:

- **Spanish**: `resources/lang/es/helpdesk.php`
- **English**: `resources/lang/en/helpdesk.php`
- Other languages can be added following the same pattern

These are used for admin panel translations with `__('helpdesk.livechat.fields.xxx')`.

### Frontend (React)

Located in `resources/js/helpdesk/widget/i18n/`:

- **`translations.ts`** - All widget UI translations (6 languages)
- **`useLanguage.ts`** - Language detection and translation hook

## Adding New Translations

### 1. Add to Frontend Translations (`translations.ts`)

```typescript
export const translations = {
    es: {
        new_feature: {
            title: 'Título en español',
            description: 'Descripción',
        },
        // ... other languages
    },
    en: {
        new_feature: {
            title: 'Title in English',
            description: 'Description',
        },
        // ... other languages
    },
    // ... add for each language
};
```

### 2. Use in Component

```tsx
import { useTranslation } from '../i18n/useLanguage';

export function NewFeature() {
    const t = useTranslation();

    return (
        <div>
            <h2>{t('new_feature.title')}</h2>
            <p>{t('new_feature.description')}</p>
        </div>
    );
}
```

### 3. Add to Backend (if needed for admin panel)

In `resources/lang/es/helpdesk.php`:

```php
'new_feature' => [
    'title' => 'Título en español',
    'description' => 'Descripción',
],
```

Then use in Blade:

```blade
<h2>{{ __('helpdesk.new_feature.title') }}</h2>
```

## API Endpoints

The settings API at `/lc/api/settings` returns configuration including which features are enabled:

```json
{
    "enable_send_message": true,
    "enable_create_ticket": true,
    "enable_search_help": true,
    "primary_color": "#90bb13",
    "secondary_color": "#ffffff",
    "header_title": "Chat de Soporte"
}
```

The widget automatically detects the language and displays translated UI.

## Multilingual Admin Panel

The LiveChat settings admin panel in `/manager/helpdesk/settings/livechat` uses Laravel's translation system:

```blade
<label>{{ __('helpdesk.livechat.fields.enable_send_message') }}</label>
<small>{{ __('helpdesk.livechat.fields.enable_send_message_help') }}</small>
```

All settings labels and help text are translatable.

## Current Translation Coverage

### Frontend Widget UI
- ✅ Home screen (greeting, card titles, descriptions)
- ✅ Help center (search, navigation, feedback)
- ✅ Article detail (feedback buttons, CTA)
- ✅ Chat screen (welcome message, input placeholder)
- ✅ Footer (powered by text)

### Admin Panel
- ✅ Settings page titles and descriptions
- ✅ Feature toggle labels (send message, create ticket, search help)
- ✅ Color picker labels and help text
- ✅ All accordion section titles
- ✅ Form field labels and descriptions
- ✅ Tab titles (Widget, Timeouts, Installation, Security)

## Testing Language Detection

### Browser Console Test

```javascript
// Check detected language
import { getDetectedLanguage } from 'i18n/useLanguage';
console.log(getDetectedLanguage()); // Should log current language

// Change body language
document.body.setAttribute('data-lang', 'pt');
// Widget should update to Portuguese
```

### Manual Testing

1. **Set language via data-lang attribute**:
   ```html
   <body data-lang="pt">
   ```

2. **Set language via lang attribute**:
   ```html
   <html lang="en-US">
   ```

3. **Test browser language**:
   - Change your browser's language setting
   - Widget should detect it automatically

## Implementation Notes

`★ Insight ─────────────────────────────────────`
**Language Detection Pattern**: The widget uses a mutation observer to detect when the `data-lang` attribute changes, allowing dynamic language switching without page reload. This enables users to change languages by updating the attribute via JavaScript.

**Translation File Organization**: Frontend and backend translations are kept separate - frontend uses TypeScript object files for bundling efficiency, while backend uses PHP files for Laravel's translation system.

**Fallback Strategy**: Each translation has multiple fallbacks (data-lang → lang attribute → browser language → Spanish), ensuring the widget always displays in a readable language.
`─────────────────────────────────────────────────`
