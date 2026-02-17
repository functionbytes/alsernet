# Widget Module

A comprehensive widget management system for Laravel modular applications.

## Overview

The Widget module provides a flexible and extensible framework for creating, managing, and rendering widgets throughout your application. It supports widget groups, caching, and dynamic configuration.

## Features

- **Abstract Widget Base Class**: Extend `AbstractWidget` to create custom widgets
- **Widget Factory**: Automatically discover and register widgets
- **Widget Groups**: Organize widgets into logical groups
- **Repository Pattern**: Clean data access with caching support
- **Facade Support**: Easy-to-use facades for widget management
- **Blade Directives**: Custom directives for rendering widgets in views
- **Event System**: Hook into widget rendering lifecycle
- **Database Storage**: Persist widget configurations and positions

## Installation

The module is automatically registered when present in the `modules` directory.

Run migrations:

```bash
php artisan migrate
```

## Usage

### Creating a Widget

Create a widget by extending `AbstractWidget`:

```php
<?php

namespace Modules\YourModule\app\Widgets;

use Modules\Widget\app\AbstractWidget;

class MyCustomWidget extends AbstractWidget
{
    public static function group(): string
    {
        return 'sidebar';
    }

    public function title(): string
    {
        return 'My Custom Widget';
    }

    protected function data(): ?array
    {
        return [
            'items' => ['Item 1', 'Item 2', 'Item 3'],
        ];
    }
}
```

### Registering Widgets

Register widgets using the facade:

```php
use Modules\Widget\app\Facades\Widget;

Widget::register(MyCustomWidget::class);
```

Auto-discover widgets in a directory:

```php
Widget::discover(
    module_path('YourModule', 'app/Widgets'),
    'Modules\YourModule\app\Widgets'
);
```

### Rendering Widgets

In Blade templates:

```blade
@widget('Modules\YourModule\app\Widgets\MyCustomWidget', ['config' => 'value'])
```

Using the facade:

```php
use Modules\Widget\app\Facades\Widget;

echo Widget::render(MyCustomWidget::class, ['config' => 'value']);
```

### Widget Groups

Register widgets in groups:

```php
use Modules\Widget\app\Facades\WidgetGroup;

WidgetGroup::registerWidget('sidebar', MyCustomWidget::class, [
    'cache' => true,
]);
```

Render all widgets in a group:

```blade
@widgetGroup('sidebar')
```

### Repository Usage

Use the repository for database operations:

```php
use Modules\Widget\app\Repositories\Interfaces\WidgetInterface;

class MyController
{
    protected $widgetRepository;

    public function __construct(WidgetInterface $widgetRepository)
    {
        $this->widgetRepository = $widgetRepository;
    }

    public function index()
    {
        $widgets = $this->widgetRepository->getBySidebar('main-sidebar');
        return view('widgets.index', compact('widgets'));
    }
}
```

## Blade Directives

- `@widget($class, $config)` - Render a single widget
- `@widgetGroup($groupName)` - Render all widgets in a group
- `@renderWidget($instance)` - Render a widget instance
- `@widgetSettings($class, $config)` - Render widget backend settings

## Configuration

Publish configuration:

```bash
php artisan vendor:publish --tag=widget-config
```

Configuration options in `config/widget.php`:

```php
return [
    'cache' => [
        'enabled' => true,
        'lifetime' => 3600,
    ],
    'groups' => [
        'sidebar' => [
            'title' => 'Sidebar Widgets',
            'description' => 'Widgets for sidebar areas',
        ],
    ],
];
```

## Events

- `RenderingWidgetSettings`: Fired when rendering widget backend settings

## Permissions

The module defines the following permissions:

- `widget.view` - View widgets
- `widget.create` - Create new widgets
- `widget.edit` - Edit existing widgets
- `widget.delete` - Delete widgets
- `widget.manage` - Full widget management access

## Database Schema

The module creates a `widgets` table with the following structure:

- `id` - Primary key
- `widget_id` - Widget class identifier
- `sidebar_id` - Sidebar location identifier
- `theme` - Theme name
- `position` - Display order
- `data` - JSON configuration data
- `status` - Active/inactive status
- `timestamps` - Created/updated timestamps

## API Reference

### AbstractWidget Methods

- `group()` - Define widget group (abstract)
- `title()` - Get widget title
- `run()` - Render widget frontend
- `backend()` - Render widget backend settings
- `getConfig($key, $default)` - Get configuration value
- `setConfig($key, $value)` - Set configuration value
- `setData($key, $value)` - Set template data

### WidgetFactory Methods

- `register($class)` - Register a widget
- `make($class, $config)` - Create widget instance
- `render($class, $config)` - Render widget
- `discover($path, $namespace)` - Auto-discover widgets

### WidgetGroupCollection Methods

- `group($name, $title, $description)` - Get/create group
- `registerWidget($group, $class, $config)` - Add widget to group
- `getGroup($name)` - Get specific group
- `getGroups()` - Get all groups
- `getAllWidgets()` - Get all registered widgets

## License

This module is part of the InoQua Lab application.
