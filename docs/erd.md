# QueueFlow ERD

## Phase 3 - Persistence & Domain Design

This ERD represents the proposed QueueFlow persistence model for Phase 3.

It must be reviewed by both developers before the complete Flyway migration
and JPA entity implementation is created.

```mermaid
erDiagram

    BUSINESS ||--|{ BRANCH : has
    BUSINESS ||--o{ STAFF_MEMBERSHIP : employs

    BRANCH ||--o{ SERVICE : offers
    BRANCH ||--o{ QUEUE : has
    BRANCH ||--o{ COUNTER : has
    BRANCH ||--o{ APPOINTMENT : hosts

    SERVICE ||--o{ QUEUE : configures
    SERVICE ||--o{ APPOINTMENT : booked_for

    QUEUE ||--o{ QUEUE_ENTRY : contains

    USER_ACCOUNT ||--o{ QUEUE_ENTRY : joins
    USER_ACCOUNT ||--o{ STAFF_MEMBERSHIP : has
    USER_ACCOUNT ||--o{ APPOINTMENT : books
    USER_ACCOUNT ||--o{ NOTIFICATION : receives

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
        BOOLEAN active
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    QUEUE_ENTRY {
        BIGINT id PK
        BIGINT queue_id FK
        BIGINT user_id FK
        VARCHAR ticket_number
        VARCHAR guest_name
        VARCHAR guest_phone
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

    APPOINTMENT {
        BIGINT id PK
        BIGINT user_id FK
        BIGINT service_id FK
        BIGINT branch_id FK
        TIMESTAMPTZ scheduled_at
        VARCHAR status
        TEXT notes
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    NOTIFICATION {
        BIGINT id PK
        BIGINT user_id FK
        VARCHAR type
        VARCHAR title
        TEXT message
        BOOLEAN read
        TIMESTAMPTZ created_at
    }
```

## Domain Rules

- A business contains one or more branches.
- Services belong to individual branches.
- A queue always belongs to a branch.
- A queue may optionally belong to a service, allowing both shared and
  service-specific queues.
- Customers can participate in queues without creating an account.
- `QueueEntry.user_id` is therefore nullable for guest entries.
- Guest queue entries use a secure guest token mechanism so a guest can
  access or cancel only their own ticket.
- Queue position is calculated from active queue entries rather than stored
  permanently.
- Queue entry states include `WAITING`, `CALLED`, `SERVING`, `COMPLETED`,
  `CANCELLED`, and `SKIPPED`.
- Ticket numbers are sequential within their queue/session and must be
  generated safely under concurrent requests.
- Counters are optional and must not be required for queue operation.
- Staff permissions are represented through business memberships and roles.
- Notification participation is optional.

## Review Status

**Status:** Draft - awaiting developer review.

The ERD must be reviewed before the complete PostgreSQL schema, Flyway
migrations, JPA entities, repositories, and persistence tests are implemented.