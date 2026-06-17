# Defense Notes

Per-file explanation of non-trivial decisions, written to be re-stated in a technical interview.

---

## Backend

### `backend/config/packages/nelmio_cors.yaml`

Configures the NelmioCorsBundle to add `Access-Control-Allow-*` headers to every `/api/` response. `allow_origin: ['http://localhost:4200']` is the Angular dev server. `allow_credentials: true` is mandatory because the browser only sends cookies on cross-origin requests when both sides opt in: the server via this header and the client via `withCredentials: true`. Without credentials the Symfony session cookie would not arrive on the second request, breaking the "remember your submission" feature.

### `backend/src/Repository/SectorRepository.php` — `findAsTree()`

Runs one DQL query selecting `id`, `name`, and `IDENTITY(s.parent)` (the raw FK integer, bypassing lazy-loading). All rows land in `$byId`, keyed by `id`. A second pass assigns each row as a child under its parent using PHP references (`&$byId[$id]`), so each node appears exactly once in memory. Only root nodes (where `parentId === null`) are added to the returned array; children are already nested inside them. Returning plain arrays (not entities) means `JsonResponse` serialises without touching Doctrine's collection proxies, avoiding circular-reference exceptions.

### `backend/src/Controller/SectorController.php`

A single `GET /api/sectors` action delegates entirely to the repository and returns the result as JSON. There is no logic here by design: the controller's job is routing + HTTP, the repository's job is data access. Thin controllers are easier to test and easier to read.

### `backend/src/Repository/SubmissionRepository.php` — `findOneBySessionId()`

A trivial `findOneBy` wrapper. Named explicitly so call sites read like prose ("find one submission by session ID") rather than bare `findOneBy(['sessionId' => ...])` calls sprinkled in controller code. No custom query is needed because Doctrine generates an efficient `WHERE session_id = ?` internally.

### `backend/src/Controller/SubmissionController.php`

**`save()` (POST /api/submissions)**

1. Decodes the JSON body into a plain array — no form type or DTO class needed for a single endpoint.
2. `Assert\Collection` validates the array structure in one call, producing a list of `ConstraintViolation` objects. The loop converts them to a `{ field: message }` map and returns HTTP 422.
3. `$sectors->findBy(['id' => $data['sectorIds']])` loads all requested sectors in a single `WHERE id IN (...)` query. Comparing the count against the unique input IDs catches any IDs that do not exist in the database.
4. The session is explicitly started (`$session->start()`) before calling `$session->get()` to guarantee the session is initialized and has a stable ID. Symfony starts sessions lazily; calling `getId()` before `start()` returns an empty string.
5. `$session->get('submissionId')` retrieves the submission PK stored by a prior save. If found, the existing row is loaded and updated (upsert); otherwise a new `Submission` is created. Symfony's `AbstractSessionListener` only sends `Set-Cookie` when the session is non-empty, so storing the submission ID in the session is load-bearing for the cookie flow — not just an optimization.
6. Sectors are cleared by calling `->toArray()` to snapshot the collection before the loop, then calling `removeSector()` on each snapshot element. Modifying a Doctrine `ArrayCollection` during live iteration can skip elements; `toArray()` avoids that. Doctrine tracks the removals and issues the correct `DELETE` + `INSERT` on the join table when flushed.

**`me()` (GET /api/submissions/me)**

Starts the session, reads `submissionId` from it, and does a PK lookup. Returns 204 No Content if neither exists. 204 is the correct "resource not yet created" status; the Angular service maps it to `null` via `observe: 'response'` and `r.body`.

**`toArray()`**

A private method that converts a `Submission` entity to a plain array read-model. Centralising it means both `save()` and future endpoints always return the same shape. The `map(fn($s) => $s->getId())` call avoids exposing the full Sector entity (and its children relation) in the submission response.

---

## Frontend

### `frontend/src/app/sector.ts`

Defines three interfaces: `Sector` (the tree node shape from the API), `FlatSector` (a flattened node that adds `depth` for rendering), and `Submission` (the form payload shape). Keeping interfaces in one file avoids scattered type definitions for such a small domain.

### `frontend/src/app/sector.service.ts`

All three HTTP calls are in one service because the app has exactly one feature (the form) and splitting further would add files without benefit. `withCredentials: true` on every request ensures the browser attaches the session cookie cross-origin. `getSubmission()` uses `observe: 'response'` and maps `r.body`, which is `null` for a 204 No Content response, allowing the component to distinguish "found" from "not found" without error handling.

### `frontend/src/app/app.config.ts`

`provideHttpClient(withFetch())` registers Angular's `HttpClient` with the Fetch API backend (instead of the legacy XHR backend). `withFetch()` is the Angular 17+ recommended default; it gives better streaming support and aligns with modern browser APIs. No interceptors are needed — credentials are set per-request in the service.

### `frontend/src/app/app.component.ts`

**Form definition**

Three controls: `name` (Validators.required), `sectorIds` (custom `atLeastOne` validator), `agreeToTerms` (Validators.requiredTrue). `atLeastOne` is a two-line function that checks `Array.isArray(c.value) && c.value.length > 0`. It is not abstracted further because it is used in exactly one place. `Validators.minLength(1)` would also work but returns a `minlength` error key instead of `required`, making the template condition less readable.

**`ngOnInit`**

Loads the sector tree (to render the select) and the existing submission (to prefill the form) in parallel on startup. `patchValue(sub)` sets only the fields present in the object, leaving the rest unchanged — the right choice here because the server returns exactly the three form fields.

**`save()`**

`markAllAsTouched()` triggers validation display if the user submits without touching any field. On a 422 response the server's error map is assigned to `serverErrors`, which the template renders next to each field. On any other non-2xx error a generic message is shown.

**`flatten()`**

Recursive `flatMap` over the nested tree. Each call appends the current node (with its depth) followed by all descendants at `depth + 1`. The result is an ordered flat list that matches the visual tree order — parents before children. The template iterates this list to render `<option>` elements.

### `frontend/src/app/app.component.html`

**Sector `<select>`**

`[value]="s.id"` binds the JavaScript number (not the DOM string attribute), so Angular's `SelectMultipleControlValueAccessor` stores `number[]` in the form control. `[style.paddingLeft]="s.depth * 1.5 + 'rem'"` derives indentation from depth — no `&nbsp;` characters. CSS padding on `<option>` works in Firefox and macOS Chrome/Safari. On Windows, Chrome/Edge render the dropdown via the OS widget and ignore CSS; this is a known browser limitation. The alternative (`<optgroup>` for every parent) cannot nest beyond two levels (HTML prohibits nested `<optgroup>`) and would make root categories non-selectable, which contradicts the original data model where root sectors are valid choices.

**Validation display**

Each field shows its client-side error when the control is `invalid && touched` (touched = user interacted with it). Server errors from a 422 response are shown below client errors; in practice only one set is visible at a time because the client validator mirrors the server rules.

---

## `frontend/index.html` (static artifact)

This file is a corrected version of the original provided `index.html`, kept as a documentation artifact. The live form runs inside the Angular app (`frontend/src/index.html`). The comment block at the top of the file lists all ten deficiencies found in the original.

---

## Database dump (`database/dump.sql`)

Generated with `pg_dump` from the running container after fixtures were loaded. Contains the full schema (tables, indexes, constraints, sequences) and all 79 seeded sector rows. The submission table is empty at dump time because no user data existed yet. This file lets a reviewer inspect the exact schema without running the migration.
