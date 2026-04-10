# Features and registry

For **behavior-level detail** (home page sections, header/footer, routes, API), see [FEATURE_DETAILS.md](FEATURE_DETAILS.md) and validate with [FEATURE_VALIDATION_CHECKLIST.md](FEATURE_VALIDATION_CHECKLIST.md).

## Feature list (current + planned)

- API v1 authentication (JWT cookie) + role enforcement
- Subscriber register/login/logout
- Categories/tags admin CRUD
- Category request queue (approve/reject)
- Anonymous business submission queue (approve/reject)
- User profiles (`userprofiles` table)
- Social login: `user_auth_providers` (schema; API wiring pending)
- Public business infinite scroll + listings API endpoint
- Inappropriate business flagging + CP moderation
- Edit-only SEO metadata in business edit flow
- Error log operations (single delete + retention prune)

## Registry (status)

| ID | Status / notes |
|----|----------------|
| `api_v1_core_auth` | JWT cookie auth + roles (subscriber / super_admin / co_admin) |
| `api_v1_categories_tags` | Categories/tags CRUD |
| `api_v1_category_requests` | Request queue + approve/reject |
| `api_v1_anon_business_submissions` | Anonymous submission queue + approve/reject |
| `user_auth_providers` | DB schema added; API login wiring pending |
| `public_business_listings_scroll` | Added `GET /api/v1/public/businesses` + infinite scroll UI |
| `business_flagging_workflow` | Public flag submission + CP flag moderation endpoints |
| `business_edit_seo_fields` | SEO fields available in edit flow and persisted via business service |
| `cp_error_log_ops` | Error log now supports row delete + retention prune |

## Adding a new feature

1. Add a row to the registry table above.
2. Create `docs/feature_docs/<feature>.md` (spec).
3. Update [CHANGELOG.md](CHANGELOG.md) for user-visible or behavior changes.
