# Helmes Take-Home Assignment

A sector-registration web app: users enter their name, pick one or more business sectors from a hierarchical list, and agree to terms. The submission is stored per browser session so users can return and edit their entry.

---

## Stack

| Layer | Technology |
|---|---|
| Database | PostgreSQL 16 (Docker) |
| Backend | Symfony 7.4 LTS + Doctrine ORM |
| Frontend | Angular 19 (standalone components, Reactive Forms) |

---

## Setup (from a clean clone)

### Prerequisites

- Docker & Docker Compose
- PHP 8.2+ with extensions: `pdo_pgsql`, `ctype`, `iconv`
- Composer
- Node.js 22+ and npm

### 1 — Start the database

```bash
docker compose up -d        # starts postgres:16 on port 5432
```

### 2 — Backend

```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php -S 127.0.0.1:8000 -t public/   # or: symfony server:start
```

The API is now at `http://localhost:8000/api`.

### 3 — Frontend

```bash
cd frontend
npm install
npm start                   # Angular dev server at http://localhost:4200
```

Open `http://localhost:4200` in your browser.

---

## API endpoints

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/sectors` | Full sector tree as nested JSON |
| `GET` | `/api/submissions/me` | Current session's submission (204 if none) |
| `POST` | `/api/submissions` | Create or update submission for the session |

### POST body

```json
{
  "name": "Jane Doe",
  "sectorIds": [1, 5],
  "agreeToTerms": true
}
```

### Validation errors (HTTP 422)

```json
{
  "errors": {
    "name": "Name is required.",
    "sectorIds": "Please select at least one sector.",
    "agreeToTerms": "You must agree to the terms."
  }
}
```

---

## Technology & design decisions

### Symfony 7.4 LTS (backend)

LTS means security patches through at least 2027, which matters for a production handover. Symfony's built-in Validator, Doctrine integration, and session handling let us write very little boilerplate while staying explicit — every step is traceable to a named Symfony component. No magic auto-wiring beyond what the framework documents.

### PostgreSQL (database)

Relational data with foreign keys, a join table, and transactional writes: Postgres is the right tool. The self-referencing tree is a standard adjacency-list pattern that Postgres handles natively. SQLite would work for local dev but does not enforce foreign keys by default and has weak concurrent-write semantics; MySQL/MariaDB is a valid alternative but adds no benefit here.

### Self-referencing adjacency list for sectors (`Sector.parent`)

Each `Sector` row optionally references its own table via `parent_id`. This is the simplest correct model for a tree of unknown depth without adding a tree-specific extension. The entire tree is loaded in one query and assembled in PHP memory (`SectorRepository::findAsTree`), so there is no N+1 problem. A closure table or nested sets would be faster for deep trees with frequent reads but add significant schema complexity for a tree that never changes at runtime.

### ManyToMany + join table (`submission_sector`)

A submission can reference multiple sectors, and a sector can appear in many submissions. ManyToMany is the correct cardinality. Doctrine auto-creates the join table with ON DELETE CASCADE so removing a submission or sector cleans up the join rows automatically. The alternative (a one-to-many `SubmissionSector` entity) would add a file and mapping without any functional benefit.

### Server-side validation with Symfony Validator

`Assert\Collection` validates the raw decoded JSON array directly, matching each field to a constraint. Returning a 422 Unprocessable Entity with a `{ errors: { field: message } }` map is the standard REST convention for validation failures and maps cleanly to Angular's reactive form error display.

### Client-side validation (Angular Reactive Forms)

Server validation is the source of truth (the client can be bypassed). Client validation mirrors it to give immediate feedback without a network round-trip. Reactive Forms keep validation logic in the component class (not the template), which makes it testable and explicit.

### Session-based editing (upsert, not append)

The user is identified by Symfony's session cookie. `GET /api/submissions/me` returns the single submission for the session; `POST /api/submissions` upserts it — updating the existing row rather than inserting a new one. This keeps one current state per session with no audit history needed. The trade-off (no history) is acceptable because the assignment says "allow the user to edit their data during the session", not "store every version".

### Read-model arrays, not raw entities

Controllers and repositories return plain PHP arrays (not Doctrine entity objects). This avoids Symfony's circular-reference problem when serialising entities with bidirectional associations (Sector ↔ children ↔ parent). It also makes the response shape explicit and independent of the entity model.

### CORS with `allow_credentials: true`

The Angular dev server runs on port 4200 and the Symfony server on port 8000. CORS preflight requests would block all API calls without the NelmioCorsBundle config. `allow_credentials: true` is required because the session ID travels as a cookie, and browsers suppress credentials on cross-origin requests unless both the server header and the client `withCredentials: true` option are set.
