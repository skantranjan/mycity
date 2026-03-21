# Planning (PRD and tasks)

## Feature PRD template

### Problem

### Goals (measurable)

### Non-goals

### Users impacted

### UX / API changes

### Data model changes

### Security considerations

### Test plan

### Rollout / migration plan

---

## How to plan a feature

1. Draft a short PRD using the template above.
2. Identify: endpoints (`api/v1/index.php`), schema (`api/v1/migrations/001_create_core_tables.sql`), UI pages/forms.
3. Task checklist: docs → schema → API → frontend → tests (or manual test cases).

## Technical task checklist

- Update docs first (feature doc + API notes if needed).
- Update `api/v1/migrations/001_create_core_tables.sql` for schema changes.
- Update `api/v1/index.php` and `api/v1/lib/*`.
- Update frontend forms/payloads as needed.
- Validate: `php -l` on changed PHP files; Cursor lints if available.
