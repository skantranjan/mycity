# How we work (AI and developers)

## Read before coding

1. [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) — ground truth
2. [ARCHITECTURE.md](ARCHITECTURE.md) — stack, flow, folders
3. [CODING_AND_SECURITY.md](CODING_AND_SECURITY.md) — rules and review checklist

If something is missing, add a stub under `docs/` or update the canonical files above first.

## Task workflow

### Phase A: Plan

1. Summarize goal and constraints (1–5 sentences).
2. Identify impact: SQL (`api/v1/migrations/001_create_core_tables.sql`), API (`api/v1/index.php`, `api/v1/lib/*`), frontend PHP pages.
3. Update docs: `docs/feature_docs/<feature>.md` (stub ok); adjust [ARCHITECTURE.md](ARCHITECTURE.md) or [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) if behavior changes.

### Phase B: Implement

- Small, reviewable changes.
- Keep auth/role logic consistent.
- SQL must be valid MySQL (FK order, etc.).
- If a feature is hidden/removed for launch, update `project_brain/LAUNCH_FREEZE_LOG.md` in the same change.

### Phase C: Validate

- `php -l` on changed PHP files.
- Add tests if the repo has a runner; else document manual steps in `docs/testing_strategy/`.
- No security regressions: validation, no secrets in logs, cookie/JWT usage correct.

### Phase D: Review and changelog

- Use the checklist in [CODING_AND_SECURITY.md](CODING_AND_SECURITY.md).
- User-visible or behavior changes: add a line to [CHANGELOG.md](CHANGELOG.md).

## Master prompts (copy-paste)

### Feature development

You are implementing a new feature in My City Info.

- Follow [ARCHITECTURE.md](ARCHITECTURE.md).
- Update `api/v1/migrations/001_create_core_tables.sql` when the DB changes.
- Update `api/v1/index.php` and `api/v1/lib/*` as needed.
- Preserve security and role checks.
- Add or update `docs/feature_docs/<feature>.md`.
- Provide a test plan and a [CHANGELOG.md](CHANGELOG.md) entry if relevant.

### Bug fix

1. Identify failing behavior and where it occurs.
2. Check docs; update if expected behavior was undocumented.
3. Inspect `api/v1/index.php`, `api/v1/lib/*`, `api/v1/migrations/001_create_core_tables.sql`.
4. Minimal fix; document why it works; [CHANGELOG.md](CHANGELOG.md) if user-facing.
5. Test plan (manual and/or automated).

### Regenerate project documentation

Refresh canonical brain docs from code:

- Read `api/v1/index.php`, `api/v1/lib/*`, `api/v1/migrations/001_create_core_tables.sql`.
- Update [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md), [ARCHITECTURE.md](ARCHITECTURE.md), [FEATURES.md](FEATURES.md), [ROADMAP_AND_ISSUES.md](ROADMAP_AND_ISSUES.md), [CODING_AND_SECURITY.md](CODING_AND_SECURITY.md) as needed.
