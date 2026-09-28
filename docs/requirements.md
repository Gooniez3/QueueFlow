# QueueFlow product requirements

## Product goal

QueueFlow helps service businesses manage walk-in queues and future
appointments while giving customers a clear, low-friction view of their wait.
The first release should prove the complete Laravel-to-Spring-to-PostgreSQL
path and be understandable enough to explain in a technical interview.

## Users and responsibilities

### Customer

- Browse businesses, branches, and available services.
- Join one active queue for a selected service and receive a unique ticket.
- See queue position, estimated wait, and live status.
- Leave a queue before service is completed.
- Book, reschedule, or cancel an appointment.
- View their own queue and appointment history.

### Staff

- View queues for branches and services to which they are assigned.
- Call or recall the next customer.
- Start service, complete service, skip a ticket, or cancel an invalid entry.
- See operational information needed to serve customers without accessing
  business administration settings.

### Admin

- Manage their business, branches, services, hours, and staff assignments.
- Configure service duration and queue availability.
- View operational analytics for the business they administer.
- Perform staff actions when authorized for the relevant branch.

## Core rules for version 1

- A queue belongs to one service at one branch and has a defined lifecycle.
- A customer cannot hold duplicate active entries in the same queue.
- Ticket numbers are unique within an agreed scope that will be finalized with
  the data model (at minimum, within an active queue).
- Queue order is deterministic; concurrent joins must not produce duplicate
  positions or ticket numbers.
- Only authorized staff can change queue-entry state.
- State transitions follow the documented queue lifecycle; arbitrary status
  changes are rejected.
- Customers can only read and change their own entries and appointments.
- Admin access is restricted to the admin's business.
- Appointment slots cannot overlap beyond the configured capacity.
- The Spring Boot API owns business validation and persistence. Laravel must
  not bypass it by connecting directly to the application database.

## Version 1 capabilities

- Role-based customer, staff, and admin accounts.
- Business, branch, service, operating-hours, and staff configuration.
- Walk-in queue creation and operation.
- Customer queue status and history.
- Real-time queue updates.
- Appointment scheduling integrated with queue operations.
- In-app and email notifications.
- Branch and service operational analytics.
- Automated tests, Docker-based local deployment, and CI/CD checks.

## Out of scope for version 1

- Paid SMS infrastructure.
- Payments, invoicing, or point-of-sale features.
- Native iOS or Android applications.
- Complex multi-region or offline-first operation.
- Machine-learning wait-time prediction.
- Marketplace discovery, reviews, or public business ranking.

## Quality attributes

- **Security:** least-privilege authorization, hashed passwords, secret-free
  source control, input validation, and safe browser defaults.
- **Correctness:** transactional queue operations and explicit state rules.
- **Reliability:** useful API errors, structured logging, health checks, and
  recovery-friendly deployments.
- **Performance:** indexed queue lookups and paginated collection endpoints;
  concrete targets will be established when representative load exists.
- **Accessibility:** semantic HTML, keyboard operation, visible focus, and
  readable status messaging.
- **Testability:** domain logic belongs in services rather than controllers or
  Blade templates.
- **Observability:** request correlation and meaningful operational metrics are
  introduced before production deployment.

## Initial acceptance scenarios

1. An admin configures a branch and a service and opens its queue.
2. A customer joins that queue once and receives a ticket and wait estimate.
3. A duplicate join is rejected without creating another active entry.
4. Assigned staff call the next waiting ticket and complete its service.
5. The customer's screen reflects those state changes without a refresh.
6. A customer books an available appointment and cannot double-book a slot.
7. An admin can view daily volume, wait time, and completion metrics.

## Open decisions

These choices are intentionally deferred until their implementation phase:

- Authentication token/session design across Laravel and Spring Boot.
- The exact queue-to-appointment check-in policy.
- WebSocket transport and event payload contract.
- Email provider and deployment platform.
- Ticket numbering scope and daily reset behavior.
