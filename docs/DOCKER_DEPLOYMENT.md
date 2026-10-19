# QueueFlow — Docker Deployment Guide

## Overview

This setup runs the QueueFlow Spring Boot API and PostgreSQL 17 using Docker Compose.

Laravel is maintained separately by the frontend developer.
No files inside web/ are modified.

## Requirements

- Docker Desktop or Docker Engine
- Docker Compose v2
- Git

## Initial setup

1. Clone the QueueFlow repository.
2. Copy the root .env.example file to .env.
3. Generate a strong random PostgreSQL password.
4. Generate independent Base64-encoded 32-byte encryption keys.
5. Set WEB_ORIGIN to the Laravel frontend origin.
6. Never commit the real .env file.

## Start the application

    docker compose up -d --build

## Check container health

    docker compose ps

Both API and PostgreSQL should eventually report healthy.

## API health endpoint

    http://127.0.0.1:8080/api/v1/health

Expected JSON response:

    {"status":"UP","application":"QueueFlow API","version":"v1"}

## View logs

    docker compose logs --tail=100 api
    docker compose logs --tail=100 postgres

## Restart the API

    docker compose restart api

Allow several seconds for Spring Boot to initialize.

## Stop the application

    docker compose down

This preserves the PostgreSQL volume.

WARNING: Do not use docker compose down -v unless you
intentionally want to delete deployment database data.

## Persistent storage

PostgreSQL uses the Docker volume:

    queueflow-phase16_postgres_data

Flyway database migrations execute during API startup.

## Security

- The API runs as a non-root user.
- PostgreSQL has no published host port.
- The API is bound to 127.0.0.1 by default.
- Passwords and encryption keys are supplied through environment variables.
- Production deployment requires TLS, backups, and proper secrets management.
- Review the generated Spring Security development password warning.
- The API container root filesystem is currently writable.

## Laravel integration

The frontend developer is responsible for Laravel configuration,
deployment, and integration.

Any required changes inside web/ must be communicated to that developer.
