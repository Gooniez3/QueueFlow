# Architecture

## Context

```text
Customer / Staff / Admin
           |
           | HTTPS
           v
  Laravel web application
  - routes and controllers
  - Blade and Tailwind UI
  - browser session / UI state
           |
           | REST over HTTP, JSON
           v
  Spring Boot application
  - API controllers and DTOs
  - authentication and authorization
  - application/domain services
  - repositories and transactions
           |
           | JDBC via JPA / Hibernate
           v
        PostgreSQL
```

## Component responsibilities

### Laravel (`web/`)

- Owns browser routing, page composition, presentation, and form UX.
- Calls the Spring API through Laravel's HTTP client.
- Converts upstream failures into useful loading, validation, and error states.
- Holds no authoritative QueueFlow business data.
- Never connects directly to the QueueFlow PostgreSQL schema.

### Spring Boot (`api/`)

- Exposes the versioned REST API and, later, real-time events.
- Authenticates callers and enforces role and tenant boundaries.
- Owns queue, appointment, and configuration business rules.
- Defines transaction boundaries and coordinates repositories.
- Returns stable DTOs rather than exposing persistence entities.

### PostgreSQL

- Is the durable system of record for application data.
- Enforces relational integrity, uniqueness, and important constraints.
- Is accessed by the Spring application only.

## Backend layering

```text
HTTP request
    |
    v
Controller  -- HTTP concerns, request validation, response status
    |
    v
DTO         -- explicit external contract
    |
    v
Service     -- use cases, authorization context, transactions, rules
    |
    v
Repository  -- persistence access
    |
    v
PostgreSQL
```

Controllers should remain thin. Queue ordering and state transitions belong in
services, while database constraints protect invariants under concurrency.

## Initial domain map

```text
Business 1 ----- * Branch 1 ----- * Service
   |                 |                 |
   |                 |                 * Queue 1 ----- * QueueEntry
   |                 |                                  |
   |                 * StaffAssignment                  * Customer (User)
   |                                                    |
   * User (Admin)        Appointment * -----------------+
```

This is a conceptual map, not the final ERD. Phase 3 will define keys,
cardinality, lifecycle ownership, audit fields, indexes, and constraints before
entities and migrations are written.

## Request flow example

When a customer joins a queue:

1. The browser submits a Laravel form with CSRF protection.
2. Laravel validates presentation-level input and sends an authenticated JSON
   request to the Spring API.
3. The API validates the DTO and authorization context.
4. A queue service transaction checks queue availability and duplicates,
   allocates the next ticket deterministically, and persists the entry.
5. The API returns a response DTO and appropriate HTTP status.
6. Laravel renders the customer's ticket and handles any API error explicitly.

## API conventions to establish in Phase 4

- Base path: `/api/v1` for public application endpoints.
- JSON request and response bodies.
- Resource-oriented URLs and standard HTTP methods/status codes.
- A consistent problem-details error shape.
- Pagination and filtering for unbounded collections.
- ISO 8601 timestamps with an explicit timezone; persist instants in UTC.
- Idempotency or equivalent concurrency protection where duplicate mutations
  would be harmful.

## Security boundaries

- Browser traffic terminates at Laravel; backend credentials are never exposed
  to Blade or JavaScript.
- The API independently authorizes every protected action; it does not trust a
  role asserted only by Laravel.
- Every business-scoped query includes an enforced tenant/business boundary.
- Secrets are supplied through environment or deployment secret storage.
- Cross-origin access is denied by default and narrowly configured if a direct
  browser-to-API flow is introduced later.

## Evolution

Real-time updates, notification delivery, and analytics should begin inside the
Spring application unless measured complexity justifies a separate service or
message broker. This keeps version 1 operable while preserving clear seams for
future extraction.
