# Users Module

Users Management module for Alsernet. Provides comprehensive user management including CRUD operations, user profiles, and user administration.

## Features

- **User Management** - Create, read, update, delete users
- **User Profiles** - Manage user information
- **Role Assignment** - Assign roles to users
- **Permissions** - Set user permissions and capabilities
- **User Administration** - Bulk operations and user management

## Routes

Manager Routes (`/manager/users/`):
- GET / - List all users
- GET /create - Create form
- POST /store - Store new user
- GET /{uid} - View user
- GET /{uid}/edit - Edit form
- POST /update - Update user
- GET /{uid}/destroy - Delete user

## Architecture

```
Modules/Users/
├── app/
│   ├── Http/
│   │   ├── Controllers/Managers/
│   │   │   └── UsersController.php
│   │   └── Requests/
│   ├── Providers/
│   │   └── UsersServiceProvider.php
│   └── Models/ (if User model moves here)
├── config/
│   └── users.php
├── routes/
│   └── managers.php
├── resources/views/managers/
└── README.md
```

## License

Proprietary - Alsernet
