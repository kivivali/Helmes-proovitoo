# Helmes Take-Home Assignment

Sector-registration form: enter name → pick sectors → agree to terms → save. Stored per browser session, editable.

```
backend/     Symfony 7.4 + Doctrine — JSON API at :8000
frontend/    Angular 19 — UI at :4200
database/    pg_dump of schema + sectors
backend/compose.yaml PostgreSQL 16
```

## Prerequisites

- Docker + Docker Compose
- PHP 8.2
- Composer
- Node.js 22+

## Run it (clean clone → working app)

```bash
# 1. database (pulls the postgres image automatically on first run)
cd backend
docker compose up -d

# 2. backend (in a new terminal)
cd backend
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php -S 127.0.0.1:8000 -t public/

# 3. frontend (in a new terminal)
cd frontend
npm install
npm start
```


### In the browser

1. Open <http://localhost:4200>.
2. Submit empty form -> client-side errors appear under each field, Save does nothing.
3. Fill name, pick 1+ sectors, tick the checkbox -> click Save -> green "Your data has been saved." appears.
4. Reload the page -> the form is pre-filled with what you saved.
5. Change the name, click Save again -> success message reappears. The row is updated, not duplicated.
6. Open the page in a different browser (or Incognito) -> form is empty. Sessions are isolated.

### In the database

PostgreSQL runs in the `helmes-db` container; data is persisted to the `helmes-pgdata` Docker volume.
