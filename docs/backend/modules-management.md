# Module Management System Documentation

## Overview

The Modules Management Module is a comprehensive system for administering all modules in the Alsernet application. It provides web-based and command-line interfaces for managing module lifecycle: installation, enabling, disabling, and uninstallation.

## Key Features

### Web Interface
- **Module Listing**: Display all installed modules with status indicators
- **Module Details**: View comprehensive information about each module
- **Module Installation**: Upload and install new modules via ZIP files
- **Module Control**: Enable/disable/uninstall modules with confirmation dialogs
- **Status Dashboard**: Quick overview of enabled vs disabled modules
- **Protected Modules**: Core modules (Role, Modules) cannot be modified

### Command Line Interface
- `php artisan modules:status` - Display all modules and their statuses
- `php artisan module:toggle {name}` - Toggle module state
- `php artisan module:toggle {name} --action=enable` - Enable a module
- `php artisan module:toggle {name} --action=disable` - Disable a module

## Routes and Endpoints

```
GET    /modules                           List all modules
GET    /modules/{moduleAlias}             View module details
POST   /modules/{moduleAlias}/enable      Enable a module
POST   /modules/{moduleAlias}/disable     Disable a module
GET    /modules/upload/form               Show installation form
POST   /modules/install                   Install module from ZIP
POST   /modules/{moduleAlias}/uninstall   Uninstall a module
```

All routes require authentication (`auth`, `verified` middleware).

## Module Structure

When installing a new module, the ZIP file should contain:

```
ModuleName/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   ├── Providers/
│   │   └── ModuleNameServiceProvider.php
│   └── Console/
│       └── Commands/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   └── assets/
├── routes/
│   ├── web.php
│   └── api.php
├── config/
│   └── config.php
├── module.json (REQUIRED)
├── composer.json (optional)
└── README.md (optional)
```

### Required module.json

```json
{
    "name": "ModuleName",
    "alias": "modulealias",
    "description": "Module description",
    "version": "1.0.0",
    "priority": 0,
    "providers": [
        "Modules\\ModuleName\\Providers\\ModuleNameServiceProvider"
    ],
    "files": []
}
```

## Controller Methods

### ModulesController

#### index()
- **Purpose**: Display list of all modules
- **Response**: Blade view with modules grouped by status
- **Data Passed**:
  - `modules`: Array of all modules with details
  - `enabledModules`: Filtered array of enabled modules
  - `disabledModules`: Filtered array of disabled modules
  - `totalModules`: Count of all modules
  - `enabledCount`: Count of enabled modules
  - `disabledCount`: Count of disabled modules

#### show(string $moduleAlias)
- **Purpose**: Display detailed information about a specific module
- **Parameters**: Module alias/identifier
- **Returns**: Redirect if not found, otherwise module details view
- **Module Data**:
  - Basic info (name, alias, description, version)
  - Status (enabled/disabled)
  - Technical details (path, namespace)
  - Providers and aliases

#### enable(string $moduleAlias)
- **Purpose**: Enable a disabled module
- **Parameters**: Module alias
- **Security**: Prevents enabling core modules
- **Response**: Redirect with success/error message

#### disable(string $moduleAlias)
- **Purpose**: Disable an enabled module
- **Parameters**: Module alias
- **Security**: Prevents disabling core modules (Role, Modules)
- **Response**: Redirect with success/error message

#### uploadForm()
- **Purpose**: Display module installation form
- **Response**: Installation form view with instructions

#### install(Request $request)
- **Purpose**: Install a new module from uploaded ZIP
- **Validation**:
  - `module_file`: required, file, must be ZIP
- **Process**:
  1. Validate ZIP file
  2. Extract to temporary directory
  3. Verify module.json exists
  4. Check for existing module
  5. Move to Modules directory
  6. Clean up temporary files
- **Security**: Validates ZIP content and module structure

#### uninstall(string $moduleAlias)
- **Purpose**: Completely remove a module
- **Parameters**: Module alias
- **Security**:
  - Prevents uninstalling core modules
  - Auto-disables module before deletion
- **Warning**: This is irreversible

## Artisan Commands

### ModulesStatusCommand

Display a formatted table of all modules with their status.

```bash
php artisan modules:status
```

Output includes:
- Module name
- Module alias
- Status (Enabled/Disabled)
- Priority
- Version

### ToggleModuleCommand

Change the state of a module.

```bash
# Toggle between enabled/disabled
php artisan module:toggle ModuleName

# Specifically enable
php artisan module:toggle ModuleName --action=enable

# Specifically disable
php artisan module:toggle ModuleName --action=disable
```

## Protected Modules

The following modules cannot be modified:
- `Role` - Permission and role management
- `Modules` - Module administration system

Attempting to disable or uninstall these modules will result in an error message.

## Error Handling

The system provides clear error messages for:
- Module not found
- Invalid ZIP file format
- Missing module.json
- Module already exists
- Protected module attempt
- File system errors

All errors are logged and displayed to the user.

## Security Considerations

1. **File Upload Validation**: Only ZIP files are accepted
2. **Module Validation**: Checks for required module.json
3. **Path Traversal Prevention**: Validates extracted directory structure
4. **Core Module Protection**: Essential modules cannot be disabled/uninstalled
5. **Authentication Required**: All operations require login
6. **Permission Integration**: Ready for granular permission checks

## Future Enhancements

Potential improvements:
- Module dependency checking
- Automatic backup before uninstallation
- Module version comparison
- Migration running on installation
- Granular permission-based access control
- Audit logging of module changes
- Module update functionality
- Module marketplace integration
