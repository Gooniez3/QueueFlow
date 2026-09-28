# Development environment

## Required tools

| Tool | QueueFlow target | Verified 2026-09-28 | Status |
| --- | --- | --- | --- |
| Java | JDK 21 LTS | Temurin 21.0.12.1 | Ready |
| PHP | 8.4 | 8.4.26 NTS, VS 2022 x64 | Ready |
| Composer | 2.x | 2.10.3 | Ready |
| PostgreSQL | 17 or 18 | 18.6 client/server binaries | Ready for tooling; cluster deferred |
| Node.js | Existing installation | 24.19.0 | Ready |
| npm | Compatible with Node.js | 11.17.0 | Ready |
| Git | Current stable | 2.49.0.windows.1 | Ready |

PHP, Composer, Temurin, and PostgreSQL are installed under
`C:\Users\sawlw\.queueflow-toolchain`. Their executable directories were added
to the user `PATH`, and user `JAVA_HOME` points to the Temurin 21 installation.
Cursor must be restarted for existing integrated terminals to inherit those
environment changes.

The PostgreSQL distribution includes the server and command-line tools, but a
database cluster has intentionally not been initialized. Cluster location,
credentials, port, and service behavior will be chosen when persistent database
work begins; no development password has been invented or stored during Phase 0.

## Verification commands (PowerShell)

```powershell
java -version
php --version
composer --version
psql --version
node --version
npm --version
git --version
```

For Java, also confirm that `javac -version` reports 21 and `JAVA_HOME` points
to the same JDK. PostgreSQL readiness in Phase 3 will include initializing and
connecting to a local development database, not only finding the client.

## Installation guidance

- Keep Java on the QueueFlow 21 LTS toolchain even if another JDK is installed.
- Keep PHP extensions required by Laravel and Composer enabled in `php.ini`.
- Store future PostgreSQL credentials outside source control.
- Use a Node version manager only if the selected Laravel/Vite toolchain later
  reveals a concrete incompatibility with Node 24.

Exact framework and database versions will be pinned when the applications are
created, so future builds remain reproducible.
