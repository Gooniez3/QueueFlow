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
- Appointment
- Notification

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
| branch_id | BIGINT | NOT NULL, FOREIGN KEY → branch(id) |
| service_id | BIGINT | NULL, FOREIGN KEY → service(id) |
| name | VARCHAR(150) | NOT NULL |
| active | BOOLEAN | NOT NULL, DEFAULT TRUE |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each Queue belongs to one Branch.
- A Queue may optionally belong to one Service.
- One Branch can have many Queues.
- One Service can have its own Queue.
- If service_id is NULL, the Queue is a shared branch-level queue.

### queue_entry

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| queue_id | BIGINT | NOT NULL, FOREIGN KEY → queue(id) |
| user_id | BIGINT | NULL, FOREIGN KEY → user_account(id) |
| ticket_number | VARCHAR(50) | NOT NULL |
| guest_name | VARCHAR(150) | NULL |
| guest_phone | VARCHAR(30) | NULL |
| status | VARCHAR(30) | NOT NULL |
| joined_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| called_at | TIMESTAMP WITH TIME ZONE | NULL |
| serving_at | TIMESTAMP WITH TIME ZONE | NULL |
| completed_at | TIMESTAMP WITH TIME ZONE | NULL |
| cancelled_at | TIMESTAMP WITH TIME ZONE | NULL |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each QueueEntry belongs to one Queue.
- A Queue can have many QueueEntries.
- A QueueEntry may belong to a registered User.
- If user_id is NULL, the entry represents a guest.

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
- A User can have many Appointments.
- A User can receive many Notifications.
- A User may have StaffMemberships if they work for a Business.

### staff_membership

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| user_id | BIGINT | NOT NULL, FOREIGN KEY → user_account(id) |
| business_id | BIGINT | NOT NULL, FOREIGN KEY → business(id) |
| role | VARCHAR(50) | NOT NULL |
| active | BOOLEAN | NOT NULL, DEFAULT TRUE |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each StaffMembership belongs to one User.
- Each StaffMembership belongs to one Business.
- A User can have StaffMemberships in multiple Businesses.
- A Business can have many StaffMemberships.

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

### appointment

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| user_id | BIGINT | NOT NULL, FOREIGN KEY → user_account(id) |
| service_id | BIGINT | NOT NULL, FOREIGN KEY → service(id) |
| branch_id | BIGINT | NOT NULL, FOREIGN KEY → branch(id) |
| scheduled_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| status | VARCHAR(30) | NOT NULL |
| notes | TEXT | NULL |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |
| updated_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each Appointment belongs to one User.
- Each Appointment belongs to one Service.
- Each Appointment belongs to one Branch.
- A User can have many Appointments.
- A Service can have many Appointments.
- A Branch can have many Appointments.

### notification

| Column | PostgreSQL Type | Constraints |
|---|---|---|
| id | BIGSERIAL | PRIMARY KEY |
| user_id | BIGINT | NOT NULL, FOREIGN KEY → user_account(id) |
| type | VARCHAR(50) | NOT NULL |
| title | VARCHAR(150) | NOT NULL |
| message | TEXT | NOT NULL |
| read | BOOLEAN | NOT NULL, DEFAULT FALSE |
| created_at | TIMESTAMP WITH TIME ZONE | NOT NULL |

#### Relationships

- Each Notification belongs to one User.
- A User can receive many Notifications.