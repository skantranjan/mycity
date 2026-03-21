# MyCityInfo — Requirements & features (single source of truth)

| Field | Value |
|--------|--------|
| **Purpose** | One document for **what** we build, **why**, **who** it’s for, **acceptance criteria**, and **how** we implement it (with pointers to technical specs). |
| **Audience** | Product, engineering, AI assistants, future contributors. |
| **Maintenance** | Update this file when scope, priorities, or major behaviors change. Link out for deep technical detail (architecture, API contracts, SQL). |
| **Related** | [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md), [ARCHITECTURE.md](ARCHITECTURE.md), [FEATURES.md](FEATURES.md), [FEATURE_DETAILS.md](FEATURE_DETAILS.md), [API_AUTHENTICATION.md](API_AUTHENTICATION.md), [SETUP_AND_DEPLOY.md](SETUP_AND_DEPLOY.md) |

**Source:** Derived from `requirement-document-initial.txt` (original PRD) and aligned with the live codebase. The original file remains as historical input; **this file is the maintained requirements reference.**

---

## Table of contents

1. [Product overview](#1-product-overview)  
2. [How we build (engineering model)](#2-how-we-build-engineering-model)  
3. [User types & permissions](#3-user-types--permissions)  
4. [Core domain: business listings](#4-core-domain-business-listings)  
5. [Business submission (wizard)](#5-business-submission-wizard)  
6. [Search & discovery](#6-search--discovery)  
7. [Business detail page & enquiries](#7-business-detail-page--enquiries)  
8. [Lead management](#8-lead-management)  
9. [Claim & dispute](#9-claim--dispute)  
10. [Authentication & accounts](#10-authentication--accounts)  
11. [Subscriber dashboard](#11-subscriber-dashboard)  
12. [Admin / control panel](#12-admin--control-panel)  
13. [Monetization (logic-ready)](#13-monetization-logic-ready)  
14. [Notifications](#14-notifications)  
15. [System rules (global)](#15-system-rules-global)  
16. [Security & performance](#16-security--performance)  
17. [Backlog & phases](#17-backlog--phases)  
18. [User stories index](#18-user-stories-index)  
19. [Release / QA checklist](#19-release--qa-checklist)  
20. [Document changelog](#20-document-changelog)

---

## 1. Product overview

### 1.1 Vision

**MyCityInfo** is a **business listing & lead-generation platform** where:

- Businesses can present a rich **mini profile** (listing) on the web.
- End users can **discover** businesses quickly.
- Listed businesses receive **qualified leads** (enquiries) when rules allow.

### 1.2 Success criteria (product)

| Metric | Target |
|--------|--------|
| Time to list a business | **&lt; 5 minutes** (happy path) |
| Time to find a relevant business | **&lt; 10 seconds** (search/discovery) |
| Lead form completion success | **&gt; 95%** (no spurious failures) |

### 1.3 Non-goals (unless backlog promotes them)

- Full custom website builder outside the listing template.
- Generic social network; focus stays on **local business discovery + leads**.

---

## 2. How we build (engineering model)

This section is the bridge between **requirements** and **implementation**. Update it when stack or conventions change.

### 2.1 Stack & boundaries

| Layer | Choice | Notes |
|-------|--------|--------|
| Web UI | PHP templates + shared layout | Public site, subscriber area, control panel (`/cp/`). |
| API | JSON **REST**, versioned **`/api/v1`** | JWT (`mci_api_token` cookie + `Authorization: Bearer`). |
| Data | **MySQL** | Schema source: `api/v1/migrations/` (see `001_create_core_tables.sql` and follow-ons). |
| Auth | Email/password + roles; social providers in schema | OAuth wiring tracked in feature registry; see [API_AUTHENTICATION.md](API_AUTHENTICATION.md). |

### 2.2 How features should be documented

1. **Requirements** live here (behavior, roles, acceptance).  
2. **Technical contract** lives in `FEATURE_DETAILS.md`, `docs/`, or API notes.  
3. **Implementation status** is tracked in [FEATURES.md](FEATURES.md) registry + [CHANGELOG.md](CHANGELOG.md).  
4. Breaking or security-sensitive changes: update [CODING_AND_SECURITY.md](CODING_AND_SECURITY.md) as needed.

### 2.3 Key technical references

| Topic | Where |
|--------|--------|
| Folders, layers, data flow | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Env, DB, run/deploy | [SETUP_AND_DEPLOY.md](SETUP_AND_DEPLOY.md) |
| Auth endpoints & web login parity | [API_AUTHENTICATION.md](API_AUTHENTICATION.md) |
| Dev-only test accounts | [DEV_TEST_ACCOUNTS.md](DEV_TEST_ACCOUNTS.md) |

---

## 3. User types & permissions

### 3.1 Guest

| Capability | Restriction |
|------------|-------------|
| Browse public pages | — |
| Search / discover businesses | — |
| Submit a business listing (per product rules) | Cannot manage listings or see leads without appropriate account/state |
| Claim business | Requires login (and verification where required) |

### 3.2 Subscriber (logged-in end user / business owner)

| Capability | Notes |
|------------|--------|
| Create / manage own listing(s) | Per lifecycle rules |
| View leads for **owned** businesses | May require **email/phone verification** (product rule) |
| Claim business | Flow + admin approval |
| Raise dispute | Admin resolution |

**Validation rule (product):** Before **viewing leads** or **claiming**, user must satisfy **phone/email verification** requirements (exact gates implemented per milestone).

### 3.3 Admin roles

| Role | Permissions |
|------|-------------|
| **Super admin** | Full control panel access |
| **Co-admin** | Same as super admin **except** cannot modify/delete **super admin** accounts |

---

## 4. Core domain: business listings

### 4.1 Functional data (required vs optional)

**Required (conceptual):**

- Business name (min length, e.g. 3 characters)
- Category
- City
- Phone (valid format)

**Optional (conceptual):**

- Email, website, description, services, products, gallery, FAQs

*Exact columns and validation are defined in DB + API; keep migrations and API docs in sync.*

### 4.2 Lifecycle

```text
Draft → Submitted → Pending → Approved → Live
                         → Rejected
```

### 4.3 Rules

- Only **Approved** listings are **publicly visible** in discovery.
- **Rejected** listings: editable by owner where product allows; **not** public.

### 4.4 Acceptance (QA)

- Submit → status **Pending** (unless draft-only path).
- Approved → appears in search/listing surfaces.
- Rejected → not public.

---

## 5. Business submission (wizard)

### 5.1 Eight-step wizard (target UX)

| Step | Focus |
|------|--------|
| 1 | Basic info (name, category, subcategory) |
| 2 | Contact + location (phone, email, address, city, map pin) |
| 3 | Services (multiple) |
| 4 | Products (optional) |
| 5 | Business hours (validate close &gt; open) |
| 6 | Gallery (types, size limits configurable) |
| 7 | FAQs |
| 8 | Review / preview |

### 5.2 Behavior

- **Guest:** cannot save draft (per original PRD); **logged-in:** can save draft and continue later.

### 5.3 Acceptance

- Block progression when required fields invalid.
- Draft persistence works for logged-in users.
- Submit transitions workflow state correctly.

---

## 6. Search & discovery

### 6.1 Search scope (match)

- Business name, description, services, products, category (and related fields as implemented).

### 6.2 Filters (target)

| Filter | UI |
|--------|-----|
| Category | Dropdown |
| Location | Dropdown |
| Verified | Toggle |
| Claimed | Toggle |
| Services | Multi-select |

**Rule:** Filters combine with **AND** logic. **URL should reflect** filter state (shareable, back button friendly).

### 6.3 Sorting

- **Popular** (default), **Latest**, **Distance** (when location available).

### 6.4 Acceptance

- Keyword search returns relevant rows.
- Filters narrow results correctly.
- Sort order changes as expected.

---

## 7. Business detail page & enquiries

### 7.1 Page sections (target)

Header (name, rating), About, Services, Products, Hours, Gallery, FAQs, Reviews, Map, sidebar (contact + enquiry).

### 7.2 Enquiry gate (business logic)

```text
IF business is claimed AND within lead limit (when monetization active)
→ allow enquiry
ELSE → disable enquiry form
```

### 7.3 Acceptance

- Unclaimed → enquiry disabled (or hidden), per rules.
- Claimed + allowed → enquiry succeeds; lead persisted.
- Lead visible in owner/admin flows.

---

## 8. Lead management

### 8.1 Lead fields

- Name *, Phone *, Message *, Email optional (exact schema in DB).

### 8.2 Lifecycle

```text
New → Contacted → Converted → Closed
```

### 8.3 Visibility

- Leads visible to **business owner** and **admin** (not public).

### 8.4 Acceptance

- New lead appears in subscriber dashboard for owned business.
- Status updates persist.
- Notifications triggered when channel exists (email / in-app).

---

## 9. Claim & dispute

### 9.1 Claim

```text
User submits claim → Pending → Admin approves → Ownership assigned
```

### 9.2 Dispute

```text
User raises dispute → Admin review → Resolve
```

### 9.3 Rules

- Multiple claims may exist; **admin** decides.
- Disputes notify relevant parties when notifications exist.

---

## 10. Authentication & accounts

### 10.1 Methods (target)

- **Email + password**
- **Social** (e.g. Google) — schema/support as per [FEATURES.md](FEATURES.md) registry

### 10.2 Verification

- Email verification
- Phone OTP

### 10.3 Account management (subscriber & control panel)

- Change password, forgot/reset password, profile (user + profile + auth providers).
- **Implementation detail:** see [API_AUTHENTICATION.md](API_AUTHENTICATION.md) and env flags in `.env.example` for dev-only behaviors.

### 10.4 Acceptance

- Registration/login flows succeed with clear errors.
- Unverified users restricted where product requires verification.
- Password hashing and JWT practices per [CODING_AND_SECURITY.md](CODING_AND_SECURITY.md).

---

## 11. Subscriber dashboard

### 11.1 Metrics (target)

- Total businesses
- Total leads
- Monthly leads

### 11.2 Actions

- Add business
- View/manage leads

### 11.3 Acceptance

- Metrics match underlying data.
- Navigation to key flows works.

---

## 12. Admin / control panel

### 12.1 Listings

- Approve / reject (inline or detail)
- Edit listing when allowed

### 12.2 Users

- Change role (within permission rules)
- Activate / deactivate

### 12.3 Taxonomy

- **Categories:** hierarchical (e.g. 2 levels)
- **Tags:** flat structure

### 12.4 Acceptance

- Actions reflect immediately in UI and API.
- Co-admin cannot alter super admin.

---

## 13. Monetization (logic-ready)

### 13.1 Current (default)

- **Unlimited leads** (until business rules change).

### 13.2 Future (example from PRD)

After a defined date (e.g. April 2030 in original doc):

- **Free:** capped leads/month (e.g. 10)
- **Premium:** unlimited

### 13.3 Acceptance (when enabled)

- Lead limit enforced by plan/date.
- Monthly reset behaves correctly.

---

## 14. Notifications

| Type | Examples |
|------|-----------|
| Transactional | Lead received, claim update, listing approval |
| Channels | Email, in-app |

*Requirements for providers (SMTP, queues) belong in architecture/setup docs when implemented.*

---

## 15. System rules (global)

- Only **approved** listings are publicly discoverable.
- Only **claimed** listings receive enquiries (subject to lead limits when active).
- **Ownership** required for subscriber management actions.
- **Role-based** access on all sensitive APIs and CP pages.

---

## 16. Security & performance

### 16.1 Security (requirements)

- Input validation on all boundaries (forms + API).
- Rate limiting where exposed publicly.
- Role-based API protection.
- Secure password storage (modern password hashing).

### 16.2 Performance (requirements)

- Indexed DB queries for hot paths.
- Lazy loading / pagination on list endpoints and heavy UI.

*Concrete patterns: [ARCHITECTURE.md](ARCHITECTURE.md), [CODING_AND_SECURITY.md](CODING_AND_SECURITY.md).*

---

## 17. Backlog & phases

### 17.1 Phase 2 — high priority (from PRD)

- Map view in search
- Reviews moderation
- Premium plans
- Lead limit enforcement UI

### 17.2 Medium priority

- Auto-save drafts (wizard)
- Bulk admin actions
- Analytics dashboard

### 17.3 Low priority

- AI tag suggestions
- Recommendation engine
- Ads system

*Priorities may shift — update this table when decisions change.*

---

## 18. User stories index

Detailed acceptance criteria from the initial PRD are summarized below. Keep **US IDs** stable when referring in issues or tests.

| ID | Epic | Summary |
|----|------|---------|
| US-01 | Listing | Guest submits listing → pending; not public until approved |
| US-02 | Listing | Logged-in user saves draft, edits, owns listing |
| US-03 | Search | Keyword + location search; URL reflects state |
| US-04 | Search | Filters (AND), reset, dynamic results |
| US-05 | Detail | Full sections, map, verified badge |
| US-06 | Enquiry | Gated by claimed + limits; lead stored; notify |
| US-07 | Leads | Owner sees leads for owned businesses only |
| US-08 | Leads | Status workflow New → … → Closed |
| US-09 | Claim | Login, pending claim, admin approval → ownership |
| US-10 | Dispute | Stored, tracked, notifications |
| US-11 | Auth | Register email/social; verification |
| US-12 | Auth | Login; errors; redirect |
| US-13 | Dashboard | Metrics accurate |
| US-14 | Admin | Approve/reject; reject reason |
| US-15 | Admin | User management; co-admin restrictions |

**Full narrative acceptance criteria** are preserved in `requirement-document-initial.txt` (US-01–US-15 sections). When narrative and this table diverge, **update this document** and note the change in [§20](#20-document-changelog).

---

## 19. Release / QA checklist

Before considering a milestone “done”:

- [ ] End-to-end flows for the milestone work (guest + subscriber + admin as applicable).
- [ ] Role restrictions verified (including co-admin vs super admin).
- [ ] No unauthorized API access for protected resources.
- [ ] Forms validated client- and server-side where applicable.
- [ ] Notifications for implemented channels.
- [ ] Mobile-responsive UI for touched pages.

---

## 20. Document changelog

| Date | Change |
|------|--------|
| *(add row when this file changes materially)* | Created consolidated **REQUIREMENTS_AND_FEATURES.md** from `requirement-document-initial.txt`, structured for ongoing updates and cross-linked to technical docs. |

---

### How to enhance this document

1. **Small behavior change:** Edit the relevant section + one line in §20.  
2. **New feature:** Add to §4–§12 (or new subsection), register in [FEATURES.md](FEATURES.md), note in [CHANGELOG.md](CHANGELOG.md).  
3. **Priority shift:** Update §17 and §20.  
4. **Conflict with code:** Treat this file as **intent**; either update code or update requirements — never leave both contradictory without a tracked decision in [ROADMAP_AND_ISSUES.md](ROADMAP_AND_ISSUES.md).
