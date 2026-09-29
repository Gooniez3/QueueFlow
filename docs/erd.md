# QueueFlow ERD

## Phase 3 - Persistence & Domain Design

This ERD represents the revised QueueFlow persistence model for Phase 3.

A Queue represents one actual operational queue for a business day. Historical
closed queues remain available for operational history and future analytics.

This ERD must be reviewed by both developers before the complete Flyway
migration and JPA entity implementation is created.

```mermaid
erDiagram

    BUSINESS ||--|{ BRANCH : has
    BUSINESS ||--o{ STAFF_MEMBERSHIP : employs

    BRANCH ||--o{ SERVICE : offers
    BRANCH ||--o{ QUEUE : operates
    BRANCH ||--o{ COUNTER : has
    BRANCH o|--o{ STAFF_MEMBERSHIP : scopes

    SERVICE o|--o{ QUEUE : configures
    SERVICE ||--o{ QUEUE_ENTRY : requested_for

    QUEUE ||--o{ QUEUE_ENTRY : contains

    USER_ACCOUNT o|--o{ QUEUE_ENTRY : joins
    USER_ACCOUNT ||--o{ STAFF_MEMBERSHIP : has

    COUNTER o|--o{ QUEUE_ENTRY : serves_at

    BUSINESS {
        BIGINT id PK
        VARCHAR name
        TEXT description
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    BRANCH {
        BIGINT id PK
        BIGINT business_id FK
        VARCHAR name
        TEXT address
        DECIMAL latitude
        DECIMAL longitude
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    SERVICE {
        BIGINT id PK
        BIGINT branch_id FK
        VARCHAR name
        TEXT description
        INTEGER duration_minutes
        BOOLEAN active
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    QUEUE {
        BIGINT id PK
        BIGINT branch_id FK
        BIGINT service_id FK
        VARCHAR name
        DATE business_date
        VARCHAR ticket_prefix
        INTEGER next_ticket_sequence
        VARCHAR status
        TIMESTAMPTZ opened_at
        TIMESTAMPTZ closed_at
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    QUEUE_ENTRY {
        BIGINT id PK
        BIGINT queue_id FK
        BIGINT service_id FK
        BIGINT user_id FK
        BIGINT counter_id FK
        INTEGER ticket_sequence
        VARCHAR guest_token_hash
        VARCHAR status
        TIMESTAMPTZ joined_at
        TIMESTAMPTZ called_at
        TIMESTAMPTZ serving_at
        TIMESTAMPTZ completed_at
        TIMESTAMPTZ cancelled_at
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    USER_ACCOUNT {
        BIGINT id PK
        VARCHAR email
        VARCHAR password_hash
        VARCHAR first_name
        VARCHAR last_name
        VARCHAR phone
        BOOLEAN active
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    STAFF_MEMBERSHIP {
        BIGINT id PK
        BIGINT user_id FK
        BIGINT business_id FK
        BIGINT branch_id FK
        VARCHAR role
        BOOLEAN active
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    COUNTER {
        BIGINT id PK
        BIGINT branch_id FK
        VARCHAR name
        BOOLEAN active
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }
```

## Domain Rules

### Daily operational queues

- A `Queue` represents one actual operating queue for a business day.
- A new Queue is created for a new operating day.
- `business_date` identifies the Queue's operating day.
- Queue status supports `OPEN`, `PAUSED`, and `CLOSED`.
- Closed queues stop being live/current but remain available as historical
  operational data.
- Queue records are not automatically deleted after 24 hours.

### Shared and service-specific queues

- Every Queue belongs to a Branch.
- `Queue.service_id` is nullable.
- A non-null `service_id` represents a service-specific Queue.
- A null `service_id` represents a shared branch Queue.
- `QueueEntry.service_id` records the actual Service requested by the customer,
  including when the customer participates in a shared Queue.

### Ticket numbering

- Each Queue has a `ticket_prefix`, such as `A`.
- Each QueueEntry stores a numeric `ticket_sequence`.
- A displayed ticket such as `A023` is derived from the Queue prefix and entry
  sequence.
- Ticket sequences restart for each new daily Queue.
- `UNIQUE(queue_id, ticket_sequence)` prevents duplicate ticket numbers within
  the same Queue.
- `next_ticket_sequence` must be updated safely under concurrent join requests.
- Cancelled or skipped ticket sequences are not reused.

### Queue entry lifecycle

QueueEntry status supports:

- `WAITING`
- `CALLED`
- `SERVING`
- `COMPLETED`
- `CANCELLED`
- `SKIPPED`

Queue position is calculated from active QueueEntries and ordering rather than
stored as a permanent position value.

### Guest participation

- `QueueEntry.user_id` is nullable.
- Customers do not need an account for basic queue participation.
- Guest ownership uses a secure guest-token mechanism.
- The database stores the secure token representation rather than relying on
  guest identity information.
- Guest name and phone are not required for the basic queue flow.

### Counters

- Counters are optional.
- `QueueEntry.counter_id` is nullable.
- A Queue and the Queue Board must operate without a Counter.
- Businesses using physical counters may associate an entry with a Counter
  during service.

### Staff membership

- StaffMembership associates a User with a Business.
- `StaffMembership.branch_id` is nullable.
- A null `branch_id` represents business-wide membership.
- A non-null `branch_id` represents branch-scoped membership.
- Roles can distinguish `STAFF`, `MANAGER`, and `OWNER`.

### Deferred domains

Appointment and Notification schemas are intentionally deferred.

They are planned for later roadmap phases and will be introduced through future
Flyway migrations once their domain requirements are finalized.

## Database Constraints

The persistence implementation should enforce at minimum:

- Foreign-key integrity between related entities.
- `UNIQUE(queue_id, ticket_sequence)` for QueueEntry.
- Required Queue operational fields such as `business_date`, `ticket_prefix`,
  `next_ticket_sequence`, `status`, and `opened_at`.
- Nullable relationships where the product explicitly supports optional
  behavior, including Queue.service_id, QueueEntry.user_id,
  QueueEntry.counter_id, and StaffMembership.branch_id.

Additional indexes and constraints will be finalized during persistence
implementation after ERD approval.

## Review Status

**Status:** Revised draft - awaiting developer re-review.

The complete PostgreSQL schema, Flyway migrations, JPA entities, repositories,
and persistence tests will be implemented after this revised ERD is approved.