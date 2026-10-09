# QueueFlow

QueueFlow is a learning-first virtual queue and appointment platform. The web
experience will be rendered by Laravel and Blade, while business rules and
persistence will live behind a Spring Boot REST API backed by PostgreSQL.

## Status

**Phase 0 — Foundation & Architecture**

No application features have been implemented yet. The current phase defines
the product boundary, architecture, development workflow, and local toolchain
before framework code is introduced.

## Planned architecture

```text
Browser
   |
   v
Laravel + Blade + Tailwind (web/)
   |
   | HTTP / JSON
   v
Java + Spring Boot REST API (api/)
   |
   v
Spring Data JPA / Hibernate
   |
   v
PostgreSQL
```

Laravel is the browser-facing web application. Spring Boot is the system of
record for QueueFlow business data and rules. See
[`docs/architecture.md`](docs/architecture.md) for the boundaries and request
flow.

## Repository layout

```text
queueflow/
|-- api/       # Spring Boot application (introduced in Phase 2)
|-- docs/      # Product and engineering documentation
|-- web/       # Laravel application (introduced in Phase 1)
|-- .editorconfig
|-- .gitignore
`-- README.md
```

## Phase 0 checklist

- [x] Define the initial product scope and roles
- [x] Document application boundaries and data ownership
- [x] Define the repository and Git workflow
- [x] Check the required local tools
- [x] Install Java 21
- [x] Install PHP and Composer
- [x] Install PostgreSQL (including `psql`)
- [x] Verify Node.js, npm, and Git

The captured environment result and suggested verification commands are in
[`docs/environment.md`](docs/environment.md).

## Documentation

- [Product requirements](docs/requirements.md)
- [Architecture](docs/architecture.md)
- [Development and Git workflow](docs/development.md)
- [Environment check](docs/environment.md)
- [Delivery roadmap](docs/roadmap.md)

## Next milestone

Review and commit the Phase 0 foundation, then begin Phase 1 with a deliberately
small, manually created Laravel exercise. The first Laravel work should teach
Composer, routes, controllers, Blade, forms, validation, sessions, environment
variables, and Tailwind before QueueFlow features are added.

## QR Credential Encryption

QueueFlow uses AES-256-GCM to encrypt QR credentials and return
the same credential when a customer refreshes their ticket.

Required environment variable:
QR_CREDENTIAL_ENCRYPTION_KEY

The value must be a Base64-encoded, cryptographically random 32-byte key.

Important:
- Generate the key once per environment.
- Store the key securely and reuse it after application restarts.
- Never commit the actual encryption key to Git.
- Keep the QR encryption key separate from the guest token encryption key.
- Changing or losing the key makes existing encrypted QR credentials unreadable.
- Legacy QR credentials without encrypted values are replaced on reissuance.
