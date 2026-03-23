# TaskHost API

> PHP REST API for task, list, and project management inspired by the simplicity of Task Host.
> JavaScript frontend 

![TaskHost interface with task details.png](docu/images/taskhost.png)

TaskHost API is the backend foundation of **TaskHost**, a productivity application for managing tasks, lists, and related workflows in a clean and structured way. The project is designed as a maintainable PHP service with a clear separation between HTTP handling, business logic, security, and persistence.

This README is intended as the **main GitHub entry page** for the repository. More detailed language-specific documentation can live in the `docs/` directory.

## Documentation

- German documentation: `docs/README.DE.md`
- English documentation: `docs/README.EN.md`

## Project Overview

TaskHost API aims to provide a solid backend for a modern task management application. The focus is on a clean REST-oriented architecture, understandable code structure, and a robust foundation for future extensions.

The current documented code state is the version **0.01.00**. That means the project currently focuses on the application core and not yet on asynchronous mail processing or worker-based background jobs.

## Core Goals

The project is built around a few clear goals:

- provide a reliable backend for task and list management
- keep the architecture understandable and maintainable
- separate transport, domain, and persistence concerns clearly
- make future extensions possible without overengineering the first versions
- prefer a small and robust implementation over a bloated early feature set

## Current Scope

At this stage, the project is centered on the application core. Depending on the exact branch or implementation progress, the codebase is intended to cover the following areas:

- user-related API functionality such as registration, login, or profile access
- creation and management of lists, projects, or task containers
- creation, reading, updating, and deletion of tasks
- task state changes such as open, done, or archived
- JSON-based request and response handling
- validation and structured error handling
- a backend structure prepared for future growth

## Key Features

### REST-oriented API design

The service is intended to expose a clean JSON API with predictable routes, meaningful HTTP status codes, and clearly structured request and response payloads.

### Clean backend architecture

TaskHost API follows the idea of separating responsibilities cleanly. A typical structure includes controllers, services, repositories, security components, and infrastructure code.

### Extensible foundation

The project is designed so that future additions such as notifications, collaboration features, labels, reminders, queue-based jobs, or more advanced security features can be added without having to redesign the entire backend.

### Focus on maintainability

The codebase is meant to stay understandable over time. Clear naming, consistent structure, and limited early complexity are part of the intended design philosophy.

## Not Included in This Code State

This documented repository state is explicitly the one **without a mail queue**. In particular, the following items are not part of this stage:

- asynchronous mail processing
- dedicated queue workers
- retry handling for mail delivery
- dead-letter processing
- background job subsystems for email workflows

These features can be introduced in a later development phase.

## Technology Direction

The exact implementation details may evolve, but the project is generally aligned with the following stack and concepts:

- PHP 8.2+
- Composer
- MySQL or MariaDB
- JSON over HTTP
- central API entry point
- environment-based configuration
- modular backend structure for controllers, services, repositories, and security

## Suggested Project Structure

```text
.
├── docs/
│   ├── README.DE.md
│   └── README.EN.md
├── public/
│   └── index.php
├── src/
│   ├── Controller/
│   ├── Service/
│   ├── Repository/
│   ├── Security/
│   ├── Http/
│   └── Infrastructure/
├── tests/
├── composer.json
├── LICENSE
└── README.md
```

## Getting Started

### Clone the repository

```bash
git clone git@github.com:Robin-Goerlach/TaskHost.git
cd TaskHost
```

### Install dependencies

```bash
composer install
```

### Configure the application

If the project uses environment-based configuration, create a local configuration file:

```bash
cp .env.example .env
```

Then adjust the application and database settings to your local environment.

### Run the project

The exact startup method depends on your current project structure and local web server setup. In a typical PHP setup, requests are routed through the main entry point such as `public/index.php`.

## API Philosophy

TaskHost API is intended to provide:

- JSON requests and responses
- meaningful HTTP status codes
- server-side validation
- clear separation between technical and business errors
- a secure basis for authentication and persistence logic

## Roadmap Direction

Possible future expansion areas include:

- mail handling and queue integration
- reminders and notifications
- collaboration and sharing features
- comments, labels, and priorities
- improved testing coverage
- OpenAPI or Swagger documentation
- better logging and monitoring

## License

This repository uses the **Apache License 2.0**. See the `LICENSE` file for details.

## Status Note

This README intentionally describes a **core-focused backend state**. It is meant to present the project clearly on GitHub while the more detailed German and English documentation can be maintained separately in `docs/`.
