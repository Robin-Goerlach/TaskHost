# TaskHost API (PHP)

> PHP backend for a list-based task management application in the style of Wunderlist — at this stage **still without a mail queue**.

## Overview

This repository contains the backend of **TaskHost**, a task and list management system focused on a clear REST structure, understandable architecture, and a solid foundation for future extensions.

The state documented here deliberately describes a version **before the introduction of a mail queue**. Asynchronous email processing is therefore **not yet included** at this stage of development. If email features are already planned, they are either not implemented at all yet or only handled directly/synchronously in this version.

The project is designed to serve as a robust base for additional modules, such as user management, task lists, sharing, comments, reminders, or later background processes.

---

## Project Status

The current state focuses on the core business functionality of a task management system and on a clean backend structure.

**Important for this stage:**

- **no mail queue**
- **no asynchronous background processing for emails**
- focus on **clear API endpoints**, **business logic**, and **persistent data storage**
- architecture prepared in a way that keeps later extensions possible

---

## Project Goals

TaskHost is intended to serve as a technically clean backend for a modern task management application. The API is designed to reliably provide data and functionality to a web interface, a mobile frontend, or other clients.

The following goals are the main focus:

- clear separation between the HTTP layer, business logic, and data access
- understandable and maintainable PHP structure
- REST-oriented interfaces with JSON
- secure and extensible foundation for later versions
- a small, robust V1 instead of premature over-engineering

---

## Possible Scope of This Development Stage

Depending on the exact state of the code, this version typically includes the basics of a task management system, for example:

- user registration and/or login
- management of projects, lists, or task areas
- creating, editing, reading, and deleting tasks
- task status changes, e.g. open/completed
- assigning tasks to users or lists
- API responses in JSON format
- basic validation and error handling

If some of these points are not yet part of your current codebase, you can easily shorten this list.

---

## What Is Deliberately **Not** Included in This Version

This README explicitly describes the state **without a mail queue**. Therefore, this stage does not include in particular:

- asynchronous email processing
- queue workers
- retry mechanisms for email delivery
- dead-letter handling
- separate job/worker subsystem

These aspects can be added in a later architecture or release step.

---

## Technical Approach

The API is intended as a classic PHP service with a clear separation of responsibilities. Typically, this means:

- **HTTP layer** for request/response handling
- **Controllers** for routing-related orchestration
- **Services** for business logic
- **Repositories** or data access classes for persistence
- **Security components** for authentication and authorization
- **Infrastructure layer** for database access, configuration, and technical support services

This approach helps keep the application understandable and makes later refactoring, testing, and extensions easier.

---

## Technology Stack

The exact stack may vary slightly depending on the current state. The project is typically intended to use:

- **PHP 8.2+**
- **Composer**
- **MySQL or MariaDB**
- JSON-based REST communication
- routing through a central entry point
- optional `.env`-based configuration

If you are already using specific libraries, you can add them here, for example:

- `vlucas/phpdotenv`
- `firebase/php-jwt`
- `monolog/monolog`
- `phpunit/phpunit`

---

## Example Project Structure

The exact folder structure depends on the actual repository. For a clean PHP API, a structure like the following is a good fit:

```text
.
├── cli/
├── config/
├── public/
│   └── index.php
├── src/
│   ├── Http/
│   ├── Controller/
│   ├── Service/
│   ├── Repository/
│   ├── Security/
│   └── Infrastructure/
├── tests/
├── var/
├── vendor/
├── .env
├── .env.example
├── composer.json
└── README.md
```

If your current state is still simpler, that is not a problem. The README can still remain in this form because it clearly explains the intended direction.

---

## Installation

### 1. Clone the repository

```bash
git clone git@github.com:Robin-Goerlach/TaskHost.git
cd TaskHost
```

### 2. Install dependencies

```bash
composer install
```

### 3. Create configuration

If the project works with environment variables, a local configuration file can for example be created like this:

```bash
cp .env.example .env
```

Example of possible entries:

```env
APP_ENV=dev
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=taskhost
DB_USER=taskhost
DB_PASSWORD=change-me

JWT_SECRET=please-change-this-secret
```

> The exact variable names should be adapted to your actual code.

### 4. Set up the database

Create a database for the project and import the existing schema or SQL files, if they are already included in the repository.

Typically, this will create tables for users, tasks, lists, or projects.

### 5. Configure the web server

Set up the web server so that requests are forwarded to the API’s central entry point. Depending on the project structure, this is usually `public/index.php` or `index.php` in the project root directory.

---

## Local Development

For local development, a clear separation between configuration, routing, business logic, and database access is recommended.

Also helpful:

- meaningful error responses in development mode
- structured logs
- reproducible SQL scripts
- simple test data for local development

If you have already defined Composer scripts, you can later add a block like this here:

```bash
composer test
composer analyse
composer cs
```

---

## Core API Principles

The API is designed for clean JSON-based communication.

### Typical characteristics

- requests and responses in JSON format
- meaningful HTTP status codes
- validation of incoming payloads
- understandable error messages
- clean separation between business and technical errors

### Example JSON response

```json
{
  "status": "success",
  "data": {
    "id": 42,
    "title": "Create API README",
    "isDone": false
  }
}
```

### Example error response

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "title": "The title field is required."
  }
}
```

---

## Example Endpoints

The exact routes depend on the current state of your implementation. For a Wunderlist-like API, endpoints like these typically make sense:

```text
GET    /health
POST   /api/v1/auth/register
POST   /api/v1/auth/login
GET    /api/v1/users/me

GET    /api/v1/lists
POST   /api/v1/lists
GET    /api/v1/lists/{id}
PATCH  /api/v1/lists/{id}
DELETE /api/v1/lists/{id}

GET    /api/v1/tasks
POST   /api/v1/tasks
GET    /api/v1/tasks/{id}
PATCH  /api/v1/tasks/{id}
DELETE /api/v1/tasks/{id}
```

If your current code uses different terms, such as `projects`, `task-lists`, or `todos`, you should adjust the route names accordingly.

---

## Example Request

```http
POST /api/v1/tasks
Content-Type: application/json
Authorization: Bearer <token>

{
  "title": "Write README for GitHub",
  "description": "Document the version without a mail queue",
  "isDone": false
}
```

---

## Security

Even at an early development stage, the API should not only work, but also be cleanly secured. This includes in particular:

- server-side validation of all inputs
- protection against SQL injection through parameterized queries
- proper password handling with secure hashing algorithms
- controlled handling of error messages
- separation between the public API and internal technical details
- cautious handling of debug output in production environments

If authentication is already available, you can additionally document here whether sessions, tokens, or JWT are used.

---

## Tests

Automated tests help evolve the API reliably and make later refactoring safer.

Especially useful are:

- unit tests for services
- tests for validation logic
- repository-level or database-related tests
- API tests for key endpoints

If PHPUnit is already integrated, you can make this section more specific later.

---

## Roadmap

Possible next steps for later versions:

- integrate or expand email sending
- add a **mail queue** for asynchronous processing
- introduce worker processes
- expand reminders and notifications
- add comments, labels, and priorities
- add sharing or team functionality
- add OpenAPI/Swagger documentation
- increase test coverage
- expand logging and monitoring

---

## Notes on Further Development

For this project, incremental development is the sensible approach: first a small, robust foundation, then targeted extensions.

Especially features such as a mail queue, workers, background jobs, or extensive notifications should only be added once the core architecture is stable and the API business logic works cleanly.

This keeps the project understandable, testable, and maintainable in the long term.

---

## License

Insert the actual license used by the project here, for example:

```text
MIT License
```

or

```text
GNU Affero General Public License v3.0
```

---

## Summary

TaskHost API is intended as a PHP backend for a modern, list-based task management application. This README deliberately describes an early to mid-stage development state in which the core business functionality is the main focus and **a mail queue is not yet part of the system**.

This keeps the current state understandable, traceable, and well suited as a basis for further development stages.
