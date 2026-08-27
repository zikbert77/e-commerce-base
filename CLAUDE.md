# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Project Overview

Symfony 7.3 e-commerce application with PostgreSQL.

The system includes:
- multilingual product catalog
- user authentication and email verification
- hierarchical categories
- domain-based multi-store / multi-tenant support
- per-store themes

## Documentation

Documentation is located under `docs/`.

See [docs/README.md](docs/README.md) for the documentation index.

### Documentation loading

Do not read the entire `docs/` directory.

Before starting a task:

1. Identify which project area or domain is affected.
2. Check `docs/README.md`.
3. Read only documentation relevant to the task.
4. Inspect related source code.
5. If documentation conflicts with code, treat the code as the current source of truth and report the discrepancy.

Avoid loading unrelated documentation into context.

### Documentation maintenance

After implementing a feature or changing project behavior:

1. Update the relevant documentation under `docs/`.
2. Do not modify unrelated documentation.
3. If no relevant documentation exists, create an appropriate file.
4. Update `docs/README.md` when adding a new documentation area.

## Global Development Rules

- Use PHP 8.2+ attributes for Doctrine ORM mapping.
- Use camelCase for PHP properties and snake_case for database columns.
- Use repositories for custom database queries.
- Follow existing project patterns before introducing new abstractions.
- Prefer modifying existing services over creating duplicate functionality.
- Do not introduce new dependencies unless necessary.
- Generate Doctrine migrations for schema changes.

## Architecture

For architecture details, see:
[docs/architecture.md](docs/architecture.md)

## Environment

- `.env` — committed defaults
- `.env.local` — local overrides, not committed
- `.env.dev` — development defaults
- `.env.test` — test defaults
- Database configuration uses `DATABASE_URL`
