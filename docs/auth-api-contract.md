\# QueueFlow Authentication API Contract



Status: Draft — Phase 6



\## Ownership



Spring Boot owns:

\- Authentication

\- Password hashing

\- User accounts

\- Staff memberships

\- Authorization

\- Authentication credentials/tokens



Laravel owns:

\- Login/register UI

\- Laravel web session

\- Calling the Spring authentication API

\- Protected staff web routes

\- Role-aware presentation

\- Handling 401 and 403 responses



Public customer functionality remains unauthenticated.



\---



\## Endpoints



\### Register



POST /api/v1/auth/register



Request and response:

TBD by Spring implementation.



\### Login



POST /api/v1/auth/login



Request and response:

TBD by Spring implementation.



\### Logout



POST /api/v1/auth/logout



Authentication requirements and response:

TBD by Spring implementation.



\### Current User



GET /api/v1/auth/me



Expected to provide:

\- User/account ID

\- Email

\- Display name if supported

\- Business memberships

\- Role for each membership



Exact response:

TBD by Spring implementation.



\---



\## Authentication Transport



TBD.



Laravel and Spring must agree on:

\- What Spring returns after successful login

\- Where Laravel stores authentication state

\- How Laravel authenticates later API requests

\- Expiration behavior

\- Logout/invalidation behavior



Long-lived credentials must not be stored in browser localStorage.



\---



\## Roles



Roles are business-scoped through STAFF\_MEMBERSHIP:



\- STAFF

\- MANAGER

\- OWNER



A user does not have one global business role.



Example:



User

\- Business A → OWNER

\- Business B → STAFF



Spring is responsible for enforcing authorization.



Laravel may use membership roles for presentation, but hiding UI elements is not authorization.



\---



\## HTTP Status Contract



\- 200 — Successful request/login/logout

\- 201 — Account created

\- 400 — Validation or malformed request

\- 401 — Missing/invalid authentication or invalid credentials

\- 403 — Authenticated but not authorized

\- 404 — Resource not found

\- 409 — Account/email conflict

\- 5xx — Server error



Exact error JSON should follow the existing QueueFlow API error format.



\---



\## Public Laravel Routes



These remain public:



\- /

\- /ticket

\- /queue-board



Future guest customer discovery/join flows must also remain public.



\## Protected Laravel Area



\- /staff/\*

