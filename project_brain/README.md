# Project brain — My City Info

**Start here.** This folder is the single place to understand the product, architecture, rules, and how we work.

## Canonical docs (read these)

| File | What it is |
|------|------------|
| [**REQUIREMENTS_AND_FEATURES.md**](REQUIREMENTS_AND_FEATURES.md) | **Single source of truth:** product requirements, features, acceptance criteria, how we build (with links to technical docs). Update this as scope evolves. |
| [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md) | Ground truth: purpose, users, DB/API pointers |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Stack, layers, data flow, folders, integrations |
| [CODING_AND_SECURITY.md](CODING_AND_SECURITY.md) | Coding rules, security, pre-merge checklist |
| [FEATURES.md](FEATURES.md) | Feature list + registry |
| [FEATURE_DETAILS.md](FEATURE_DETAILS.md) | Human-readable spec: what each area does and expected behavior |
| [FEATURE_VALIDATION_CHECKLIST.md](FEATURE_VALIDATION_CHECKLIST.md) | Checklist to validate implementation vs expected behavior |
| [ROADMAP_AND_ISSUES.md](ROADMAP_AND_ISSUES.md) | Decisions, known issues, roadmap |
| [CHANGELOG.md](CHANGELOG.md) | What changed |
| [SETUP_AND_DEPLOY.md](SETUP_AND_DEPLOY.md) | Env, DB import, run, deploy |
| [API_AUTHENTICATION.md](API_AUTHENTICATION.md) | Auth API: subscriber + CP login, `/auth/me`, curl examples |
| [DEV_TEST_ACCOUNTS.md](DEV_TEST_ACCOUNTS.md) | Local dev logins + optional DB seed (not for production) |
| [HOW_WE_WORK.md](HOW_WE_WORK.md) | Workflow for AI/devs + master prompts |
| [planning.md](planning.md) | PRD template + planning checklists |
| [lessons_and_patterns.md](lessons_and_patterns.md) | Recurring bugs and lessons |

## Deeper docs (grow as needed)

- [docs/README.md](docs/README.md) — API, data models, UI flows, testing strategy

## Database source of truth

`api/v1/migrations/001_create_core_tables.sql`
