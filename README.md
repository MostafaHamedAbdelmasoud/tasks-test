# Task Management System API

A RESTful API for managing tasks with role-based access control built with **Laravel 11**, **PHP 8.2**, **MySQL**, and **Docker**.

## Features

- **Role-based Authentication**: Manager and User roles with different permissions
- **Task Management**: Create, read, update, delete tasks
- **Task Dependencies**: Tasks can depend on other tasks
- **Status Management**: Track task progress (pending, completed, canceled)
- **Filtering**: Filter tasks by status, assigned user, and due date range
- **API Authentication**: Stateless authentication using Laravel Sanctum
- **Modern PHP 8.2**: Latest PHP features with strict typing and return types
- **Laravel 11**: Latest Laravel features with anonymous migration classes

## Requirements

- **PHP 8.2+**
- **Composer**
- **Docker & Docker Compose**
- **Laravel Sail**

## Installation & Setup

### 1. Clone the Repository
```bash
git clone <repository-url>
cd task-management-api
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

**Important**: The `.env` file is pre-configured with:
- **Port 8080**: Application runs on `http://localhost:8080`
- **MySQL Port 3307**: Database port forwarded to 3307 to avoid conflicts
- **Laravel Sail**: Docker containerization with all dependencies

### 4. Start Docker Environment (Laravel Sail)
```bash
# Make sure you have PHP 8.2+ installed locally for initial setup
composer install

# Start Laravel Sail containers
./vendor/bin/sail up -d
```

### 5. Run Database Migrations and Seeders
```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

This will:
- Drop all existing tables
- Run all migrations to create the database schema
- Seed the database with sample users and tasks using factories

### 6. Access the Application
The API will be available at: `http://localhost:8080`

> **Note**: The application runs on port 8080 as configured in the `.env` file. MySQL runs on port 3307 to avoid conflicts with local MySQL installations.

## API Endpoints

### Authentication
- `POST /api/login` - User login
- `GET /api/me` - Get authenticated user profile
- `POST /api/logout` - User logout

### Tasks
- `GET /api/tasks` - List all tasks (with filtering)
- `POST /api/tasks` - Create a new task (Managers only)
- `GET /api/tasks/{id}` - Get task details
- `PUT /api/tasks/{id}` - Update task (Managers only)
- `PATCH /api/tasks/{id}/status` - Update task status
- `DELETE /api/tasks/{id}` - Delete task (Managers only)

### Query Parameters for Task Listing
- `status` - Filter by task status (pending, completed, canceled)
- `assigned_to` - Filter by assigned user ID (Managers only)
- `due_date_from` - Filter by due date range start
- `due_date_to` - Filter by due date range end
- `per_page` - Number of results per page (default: 15)

## Authentication

The API uses Laravel Sanctum for stateless API authentication. Include the Bearer token in the Authorization header:

```
Authorization: Bearer {your-token}
```

## Roles & Permissions System

The application uses **Spatie Laravel Permission** package for advanced role and permission management.

### Permission-Based Access Control

**Manager Role Permissions:**
- `view tasks` - Can view all tasks in the system
- `create tasks` - Can create new tasks
- `update tasks` - Can update any task details
- `delete tasks` - Can delete tasks

**User Role Permissions:**
- `view own tasks` - Can only view tasks assigned to them
- `update own task status` - Can update status of their assigned tasks

### Benefits of Spatie Permission System
- **Database-driven**: Roles and permissions stored in database
- **Granular control**: Fine-grained permission management
- **Scalable**: Easy to add new roles and permissions
- **Caching**: Built-in permission caching for performance
- **Industry standard**: Most popular Laravel permission package

## Sample Users (Seeded)

### Managers
- Email: `manager1@example.com`, Password: `password123`
- Email: `manager2@example.com`, Password: `password123`

### Users
- Email: `john@example.com`, Password: `password123`
- Email: `jane@example.com`, Password: `password123`
- Email: `bob@example.com`, Password: `password123`

## Testing the API

### Using Postman
Import the Postman collection from `Task_Management_API.postman_collection.json`

### Using cURL Examples

#### Login
```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"manager1@example.com","password":"password123"}'
```

#### Create Task
```bash
curl -X POST http://localhost:8080/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your-token}" \
  -d '{
    "title": "New Task",
    "description": "Task description",
    "assigned_to": 3,
    "due_date": "2025-12-31",
    "dependencies": [1, 2]
  }'
```

#### Get Tasks
```bash
curl -X GET "http://localhost:8080/api/tasks?status=pending" \
  -H "Authorization: Bearer {your-token}"
```

## Running Tests

```bash
./vendor/bin/sail test
```

The test suite includes:
- **Authentication Tests**: Login, logout, profile access
- **Task Management Tests**: CRUD operations, permissions, dependencies
- **Authorization Tests**: Role-based access control
- **Validation Tests**: Input validation and error handling

Current test status: **14 out of 17 tests passing**

## Database Schema

See `ERD.md` for detailed database schema and relationships.

## Development Commands

### Laravel Sail Commands
```bash
# Start containers
./vendor/bin/sail up -d

# Stop containers
./vendor/bin/sail down

# Run artisan commands
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed

# Access container shell
./vendor/bin/sail shell

# Run tests
./vendor/bin/sail test
```

### Database Operations
```bash
# Fresh migration with seeding
./vendor/bin/sail artisan migrate:fresh --seed

# Create new migration
./vendor/bin/sail artisan make:migration create_example_table

# Create new seeder
./vendor/bin/sail artisan make:seeder ExampleSeeder
```

## Project Structure

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php (with PHP 8.2 return types)
│   │   │   └── TaskController.php (with PHP 8.2 return types)
│   │   ├── Requests/
│   │   │   ├── CreateTaskRequest.php (Laravel 11 validation)
│   │   │   ├── UpdateTaskRequest.php (Laravel 11 validation)
│   │   │   └── UpdateTaskStatusRequest.php (Laravel 11 validation)
│   │   └── Traits/
│   │       └── ApiResponseTrait.php (Consistent API responses)
│   ├── Models/
│   │   ├── Task.php (Laravel 11 casts() method)
│   │   └── User.php (Laravel 11 casts() method)
│   └── Policies/
│       └── TaskPolicy.php (Role-based authorization)
├── database/
│   ├── migrations/ (Anonymous migration classes - Laravel 11)
│   │   ├── add_role_to_users_table.php
│   │   ├── create_tasks_table.php
│   │   ├── create_task_dependencies_table.php
│   │   ├── create_cache_table.php
│   │   ├── create_sessions_table.php
│   │   └── create_jobs_table.php
│   └── seeders/
│       ├── UserSeeder.php
│       ├── TaskSeeder.php
│       └── DatabaseSeeder.php
├── routes/
│   └── api.php (Laravel 11 routing)
├── tests/
│   └── Feature/
│       └── AuthTest.php
├── docker-compose.yml (Laravel Sail)
├── Task_Management_API.postman_collection.json
└── ERD.md
```

## Modern Laravel 11 & PHP 8.2 Features Used

### Laravel 11 Features
- **Anonymous Migration Classes**: Using `return new class extends Migration`
- **Simplified Configuration**: Streamlined config structure
- **Enhanced Casts**: Using `casts()` method instead of `$casts` property
- **Latest Sanctum v4**: Enhanced API authentication
- **Improved Validation**: Advanced Form Request features

### PHP 8.2 Features
- **Strict Return Types**: All methods have explicit return types (`:void`, `:bool`, `:array`, `:JsonResponse`)
- **Constructor Property Promotion**: Used in newer classes
- **Named Arguments**: Used throughout the codebase
- **Strong Typing**: Full type declarations on all class properties and methods
- **Enhanced Error Handling**: Better exception handling with types

### Permission Management Features
- **Spatie Laravel Permission v6.21**: Professional role & permission management
- **Database-driven Roles**: Flexible role assignment and management
- **Permission Caching**: Automatic caching for optimal performance
- **Policy Integration**: Seamless integration with Laravel authorization policies
- **Migration Support**: Easy migration from simple role-based to permission-based system

## API Response Format

All API responses follow a consistent format:

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    // Validation errors (if applicable)
  }
}
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Write tests for new functionality
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).