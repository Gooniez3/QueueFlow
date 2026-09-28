# Delivery roadmap

The project proceeds in learning-sized phases. A phase is complete only when
its milestone works and the author can explain the important design choices.

| Phase | Name | Milestone |
| ---: | --- | --- |
| 0 | Foundation & Architecture | Requirements, boundaries, workflow, and toolchain are ready. |
| 1 | Laravel Fundamentals | Basic pages can be built with routes, controllers, Blade, forms, validation, sessions, environment configuration, and Tailwind. |
| 2 | Spring Boot Fundamentals | A small Spring API, including `GET /api/health`, works locally. |
| 3 | Database & Domain Design | An reviewed ERD becomes migrations and JPA persistence against PostgreSQL. |
| 4 | REST API Foundation | Versioned CRUD APIs use DTOs, services, validation, exception handling, pagination, and filtering. |
| 5 | Laravel–Java Integration | Data persisted by Spring is rendered through Laravel with clear loading and error behavior. |
| 6 | Authentication & Authorization | Customer, staff, and admin access is authenticated and role-scoped. |
| 7 | Businesses, Branches & Services | Admins configure branches, services, hours, duration, and staff assignments. |
| 8 | Queue Engine | Transaction-safe joining, ordering, ticketing, wait estimates, and queue state transitions work. |
| 9 | Customer Experience | Customers browse services, join/leave queues, monitor status, and see history. |
| 10 | Staff Dashboard | Authorized staff call, recall, skip, serve, and complete tickets. |
| 11 | Real-Time System | Customer and staff views receive queue changes without manual refresh. |
| 12 | Appointment System | Customers safely book, reschedule, cancel, and check in appointments. |
| 13 | Notifications | Useful in-app and email queue/appointment alerts are delivered. |
| 14 | Analytics Dashboard | Admins see volume, wait, service, completion, cancellation, and peak-time metrics. |
| 15 | Testing, Security & Reliability | Both stacks have meaningful automated coverage and production-minded safeguards. |
| 16 | Docker & Deployment | Web, API, and PostgreSQL run reproducibly in containers and are deployed. |
| 17 | CI/CD & Portfolio Release | CI builds/tests the system and the documented, demonstrated release is tagged `v1.0.0`. |

Additional phases should be introduced only when implementation reveals a
genuine product or engineering need.
