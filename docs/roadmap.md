# Delivery roadmap

The project proceeds in learning-sized phases. A phase is complete only when
its milestone works and the author can explain the important design choices.

| Phase | Name | Milestone |
| ---: | --- | --- |
| 0 | Foundation & Architecture | Requirements, system boundaries, Git workflow, repository structure, and toolchain are ready. |
| 1 | Laravel Fundamentals | Basic pages can be built with routes, controllers, Blade, forms, validation, sessions, environment configuration, and Tailwind. |
| 2 | Spring Boot Fundamentals | A small Spring API with versioned configuration, service separation, health endpoint, and integration testing works locally. |
| 3 | Database & Domain Design | Product/domain rules are agreed, the final ERD is reviewed by both developers, and the approved model becomes Flyway migrations and JPA persistence against PostgreSQL. |
| 4 | REST API Foundation | Versioned REST APIs use DTOs, services, validation, exception handling, pagination, and filtering. |
| 5 | Laravel–Java Integration | Laravel consumes the Spring REST/JSON API and renders Spring-owned business data with clear loading and error behavior. |
| 6 | Authentication & Authorization | Staff/admin access is authenticated and role-scoped. Customer accounts remain optional for the basic queue experience. |
| 7 | Businesses, Branches & Services | Businesses configure branches, branch-specific services, operating information, staff assignments, queues, and optional counters. |
| 8 | Queue Engine | Shared and service-specific queues support transaction-safe joining, ordering, concurrency-safe ticket numbering, calculated positions, wait estimates, and queue state transitions. |
| 9 | Customer Experience | Without requiring login, customers can search/browse, optionally discover nearby services, use QR/direct links, join as guests, receive tickets, monitor live queue status, and leave their own queue entry. |
| 10 | Staff Dashboard & Queue Board | Authorized staff can call, recall, skip, start service, and complete tickets while the public/live Queue Board displays current queue state. |
| 11 | Real-Time System | WebSocket updates push queue state changes to customer ticket screens, the Queue Board, and staff views without manual refresh. |
| 12 | Appointment System | Customers can book, reschedule, cancel, and check in appointments using the finalized appointment model. |
| 13 | Notifications | Customers can optionally receive useful queue and appointment notifications without notification permission being required to join a queue. |
| 14 | Analytics & BI | Admins can analyze queue volume, waiting time, service performance, completion/cancellation rates, and peak periods using operational data collected by QueueFlow. |
| 15 | Testing, Security & Reliability | Both stacks have meaningful automated coverage, guest-ticket access is protected, concurrency-sensitive operations are tested, and production-minded safeguards are applied. |
| 16 | Docker & Deployment | Laravel, Spring Boot, and PostgreSQL run reproducibly in development containers and can be moved to an appropriate production environment. |
| 17 | CI/CD & Portfolio Release | CI builds and tests the system automatically and the documented, demonstrated release is tagged `v1.0.0`. |

Additional phases should be introduced only when implementation reveals a
genuine product or engineering need.