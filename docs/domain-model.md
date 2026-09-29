# QueueFlow Domain Model

## Phase 3 - Persistence & Domain Design

This document defines the initial database/domain model for QueueFlow.

## Core Relationships

Business
└── Branch
    ├── Service
    ├── Queue
    │   └── QueueEntry
    ├── StaffMembership
    └── Counter (optional)

User
└── QueueEntry

## Initial Entities

- Business
- Branch
- Service
- Queue
- QueueEntry
- User / Account
- StaffMembership
- Counter


## Database Tables 

## business

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| name | VARCHAR(150) | NOT NULL |
| description | TEXT | NULL |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- One Business can have many Branches.
- A Business must have at least one Branch in the application domain.

### branch

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| business_id | BIGINT | NOT NULL, FOREIGN KEY → business(id) |
| name | VARCHAR(150) | NOT NULL |
| address | TEXT | NOT NULL |
| latitude | DECIMAL(9,6) | NULL |
| longitude | DECIMAL(9,6) | NULL |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each Branch belongs to one Business.
- One Business can have many Branches.

### service 

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| branch_id | BIGINT | NOT NULL, FOREIGN KEY → branch(id) |
| name | VARCHAR(150) | NOT NULL |
| description | TEXT | NULL |
| duration_minutes | INTEGER | NOT NULL |
| active | BOOLEAN | NOT NULL, DEFAULT TRUE |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each Service belongs to one Branch.
- One Branch can offer many Services.

### queue

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| branch_id | BIGINT | NOT NULL, FOREIGN KEY -> branch(id) |
| service_id | BIGINT | NULL, FOREIGN KEY -> service(id) |
| name | VARCHAR(150) | NOT NULL |
| business_date | DATE | NOT NULL |
| ticket_prefix | VARCHAR(10) | NOT NULL |
| next_ticket_sequence | INTEGER | NOT NULL, DEFAULT 1 |
| status | VARCHAR(30) | NOT NULL |
| opened_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| closed_at | TIMESTAMP WITH TIME ZONE | NULL |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each Queue belongs to one Branch.
- A Queue may optionally belong to one Service.
- If `service_id` is NULL, the Queue is a shared branch queue.
- If `service_id` is not NULL, the Queue is service-specific.
- A Queue represents one operational queue for one business day.
- A new Queue is created for a new operating day.
- Queue status supports `OPEN`, `PAUSED`, and `CLOSED`.
- Closed Queues remain available as historical operational data.

### queue_entry

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| queue_id | BIGINT | NOT NULL, FOREIGN KEY -> queue(id) |
| service_id | BIGINT | NOT NULL, FOREIGN KEY -> service(id) |
| user_id | BIGINT | NULL, FOREIGN KEY -> user_account(id) |
| counter_id | BIGINT | NULL, FOREIGN KEY -> counter(id) |
| ticket_sequence | INTEGER | NOT NULL |
| guest_token_hash | VARCHAR(255) | NULL |
| status | VARCHAR(30) | NOT NULL |
| joined_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| called_at | TIMESTAMP WITH TIME ZONE | NULL |
| serving_at | TIMESTAMP WITH TIME ZONE | NULL |
| completed_at | TIMESTAMP WITH TIME ZONE | NULL |
| cancelled_at | TIMESTAMP WITH TIME ZONE | NULL |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Constraints

- `UNIQUE(queue_id, ticket_sequence)`

#### Relationships

- Each QueueEntry belongs to one Queue.
- Each QueueEntry records the Service requested by the customer.
- A Queue can contain many QueueEntries.
- `user_id` is nullable so customers can join as guests.
- `counter_id` is nullable because counters are optional.
- Guest ownership is handled through a secure guest-token mechanism.
- Guest name and phone are not required for basic queue participation.
- Display ticket numbers are derived from `Queue.ticket_prefix` and
  `QueueEntry.ticket_sequence`.
- Queue position is calculated from active entries rather than stored permanently.

### user_account 

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| email | VARCHAR(255) | NOT NULL, UNIQUE |
| password_hash | VARCHAR(255) | NOT NULL |
| first_name | VARCHAR(100) | NOT NULL |
| last_name | VARCHAR(100) | NOT NULL |
| phone | VARCHAR(30) | NULL |
| active | BOOLEAN | NOT NULL, DEFAULT TRUE |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- A User can have many QueueEntries.
- A User may have StaffMemberships if they work for a Business.

### staff_membership

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| user_id | BIGINT | NOT NULL, FOREIGN KEY → user_account(id) |
| business_id | BIGINT | NOT NULL, FOREIGN KEY → business(id) |
| branch_id | BIGINT | NULL, FOREIGN KEY -> branch(id) |
| role | VARCHAR(50) | NOT NULL |
| active | BOOLEAN | NOT NULL, DEFAULT TRUE |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each StaffMembership belongs to one User.
- Each StaffMembership belongs to one Business.
- A User can have StaffMemberships in multiple Businesses.
- A Business can have many StaffMemberships.
- A StaffMembership may optionally belong to one Branch
- If `branch_id` is NULL, the membership applies across the Business.
- If `branch_id` is not NULL, the membership is scoped to that Branch.

### counter

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| branch_id | BIGINT | NOT NULL, FOREIGN KEY → branch(id) |
| name | VARCHAR(100) | NOT NULL |
| active | BOOLEAN | NOT NULL, DEFAULT TRUE |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each Counter belongs to one Branch.
- One Branch can have many Counters.
- A Counter can be used by staff to serve QueueEntries.

## Deferred Domains

### Appointment 

Appointment persistence is deferred until the Appointment System phase.

The final Appointment schema will be designed when booking, rescheduling,
cancellation, check-in, availability, and related business rules are finalized.

### Notification

Notification persistence is deferred until the Notifications phase.

The final Notification schema will be designed when notification channels,
delivery state, preferences, queue alerts, and appointment alerts are finalized.

Both domains will be introduced through future Flyway migrations.