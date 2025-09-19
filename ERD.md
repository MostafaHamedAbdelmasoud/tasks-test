# Entity Relationship Diagram (ERD)

## Database Schema Documentation

### Tables Overview

The Task Management System uses the following database tables:

1. **users** - Stores user information
2. **tasks** - Stores task information
3. **task_dependencies** - Manages task dependencies (many-to-many relationship)
4. **personal_access_tokens** - Laravel Sanctum tokens for API authentication
5. **roles** - Spatie Permission: Stores user roles
6. **permissions** - Spatie Permission: Stores system permissions
7. **model_has_roles** - Spatie Permission: Links users to roles
8. **model_has_permissions** - Spatie Permission: Links users to direct permissions
9. **role_has_permissions** - Spatie Permission: Links roles to permissions

---

## Table Structures

### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Fields:**
- `id`: Primary key
- `name`: User's full name
- `email`: Unique email address for login
- `email_verified_at`: Timestamp of email verification
- `password`: Hashed password
- `remember_token`: For "remember me" functionality
- `created_at`: Record creation timestamp
- `updated_at`: Record last update timestamp

**Note:** User roles are now managed through Spatie Laravel Permission package tables.

### Tasks Table
```sql
CREATE TABLE tasks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('pending', 'completed', 'canceled') DEFAULT 'pending' NOT NULL,
    assigned_to BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    due_date DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);
```

**Fields:**
- `id`: Primary key
- `title`: Task title
- `description`: Optional task description
- `status`: Task status (pending, completed, canceled)
- `assigned_to`: Foreign key to users table (nullable)
- `created_by`: Foreign key to users table (task creator)
- `due_date`: Optional due date
- `created_at`: Record creation timestamp
- `updated_at`: Record last update timestamp

### Task Dependencies Table
```sql
CREATE TABLE task_dependencies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    depends_on_task_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (depends_on_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dependency (task_id, depends_on_task_id)
);
```

**Fields:**
- `id`: Primary key
- `task_id`: The task that has dependencies
- `depends_on_task_id`: The task that must be completed first
- `created_at`: Record creation timestamp
- `updated_at`: Record last update timestamp

### Spatie Permission Tables

The system uses **Spatie Laravel Permission** package which adds the following tables:

- **roles**: Stores role definitions (manager, user)
- **permissions**: Stores permission definitions (create tasks, view tasks, etc.)
- **model_has_roles**: Links users to their roles
- **model_has_permissions**: Links users to direct permissions (optional)
- **role_has_permissions**: Links roles to their permissions

---

## Relationships

### User Model Relationships
- **One-to-Many**: User → Tasks (as assigned user)
- **One-to-Many**: User → Tasks (as creator)
- **Many-to-Many**: User → Roles (via Spatie Permission)
- **Many-to-Many**: User → Permissions (via Spatie Permission)

### Task Model Relationships
- **Many-to-One**: Task → User (assigned_to)
- **Many-to-One**: Task → User (created_by)
- **Many-to-Many**: Task → Task (dependencies via task_dependencies table)

### Permission System Relationships
- **Many-to-Many**: Role → Permission
- **Many-to-Many**: User → Role
- **Many-to-Many**: User → Permission (direct assignment)

---

## Visual ERD

```
┌─────────────────┐       ┌─────────────────┐
│      USERS      │       │      TASKS      │
├─────────────────┤       ├─────────────────┤
│ • id (PK)       │       │ • id (PK)       │
│ • name          │◄──────┤ • title         │
│ • email         │       │ • description   │
│ • password      │       │ • status        │
│ • created_at    │       │ • assigned_to (FK)│
│ • updated_at    │       │ • created_by (FK)│
└─────────────────┘       │ • due_date      │
         │                │ • created_at    │
         │                │ • updated_at    │
         │                └─────────────────┘
         │                          │
         │                          │ Many-to-Many
         │                          │ (Self-referencing)
         │                          ▼
         │                ┌─────────────────────┐
         │                │ TASK_DEPENDENCIES   │
         │                ├─────────────────────┤
         │                │ • id (PK)           │
         │                │ • task_id (FK)      │
         │                │ • depends_on_task_id│
         │                │ • created_at        │
         │                │ • updated_at        │
         │                └─────────────────────┘
         │
         │ Many-to-Many (via Spatie Permission)
         ▼
┌─────────────────┐       ┌─────────────────┐
│      ROLES      │       │   PERMISSIONS   │
├─────────────────┤       ├─────────────────┤
│ • id (PK)       │◄─────►│ • id (PK)       │
│ • name          │       │ • name          │
│ • guard_name    │       │ • guard_name    │
│ • created_at    │       │ • created_at    │
│ • updated_at    │       │ • updated_at    │
└─────────────────┘       └─────────────────┘
```

---

## Business Rules

1. **Permission-Based Access Control:**
   - **Manager Role Permissions:**
     - `view tasks` - Can view all tasks
     - `create tasks` - Can create new tasks
     - `update tasks` - Can update any task
     - `delete tasks` - Can delete tasks

   - **User Role Permissions:**
     - `view own tasks` - Can only view assigned tasks
     - `update own task status` - Can update status of assigned tasks

2. **Task Dependencies:**
   - A task cannot be completed until all its dependencies are completed
   - Circular dependencies are prevented by application logic

3. **Task Assignment:**
   - Tasks can be assigned to users by managers
   - Users can only see tasks assigned to them
   - Managers can see all tasks

4. **Task Status:**
   - Default status is "pending"
   - Valid statuses: pending, completed, canceled
   - Status transitions are controlled by business logic

5. **Role Assignment:**
   - Users are assigned roles through Spatie Permission system
   - Roles define sets of permissions
   - Fine-grained permission control is available

---

## Indexes

```sql
-- Users table indexes
CREATE INDEX idx_users_email ON users(email);

-- Tasks table indexes
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_created_by ON tasks(created_by);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);

-- Task dependencies indexes
CREATE INDEX idx_task_dependencies_task_id ON task_dependencies(task_id);
CREATE INDEX idx_task_dependencies_depends_on ON task_dependencies(depends_on_task_id);

-- Spatie Permission package creates its own indexes automatically
-- for roles, permissions, and relationship tables
```