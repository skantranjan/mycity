---
name: ui-ux-site-review
description: Acts as a senior UI/UX engineer to audit the My City Info site end-to-end, summarize findings, list gaps as actionable TODOs, and propose a phased fix plan. Use when the user requests a UX review, design audit, UI consistency check, accessibility pass, or holistic site improvement before implementation.
---

# Senior UI/UX site review (My City Info)

## Role and mindset

- Operate as a **senior UI/UX engineer**: balance user goals, business constraints, accessibility, and engineering feasibility.
- Prefer **evidence** over opinion: cite specific pages, components, CSS/JS files, or screenshots when possible.
- Default stack context: PHP pages under segment `index.php`, `views/`, `assets/css/`, `assets/js/`, API-backed flows. Follow [.cursor/rules/php-routing-structure.mdc](../../rules/php-routing-structure.mdc) for URL and include patterns.

## When to run a live pass

If browser automation or a local/staging URL is available, **exercise primary journeys** (home, search/listings, business detail, login/register, subscriber/cp surfaces relevant to the task) and note breakpoints (mobile/tablet/desktop). If only code is available, infer issues from markup, CSS, and JS and label inferred items clearly.

## Review dimensions (cover each; skip only if out of scope)

Use a short pass per dimension; group findings under the same dimension in the written review.

1. **Information architecture & content** — wayfinding, labels, hierarchy, empty/error states, trust signals.
2. **Core user flows** — discover → listing → business detail → contact/action; auth and post-login tasks.
3. **Visual design** — typography scale, spacing rhythm, color contrast, imagery, consistency across areas (public vs cp vs subscriber).
4. **Components & patterns** — buttons, forms, cards, nav/footer, modals; duplication or drift between pages.
5. **Responsive behavior** — overflow, tap targets, readable line length, tables/lists on small screens.
6. **Accessibility** — keyboard order/focus, semantics (headings, landmarks), form labels, contrast (WCAG 2.2 AA as target), motion preferences where applicable.
7. **Performance perception** — layout shift, heavy images, blocking scripts (flag only; deep perf is optional unless asked).
8. **Polish** — microcopy, loading states, success feedback, edge cases (no results, offline-ish errors).

## Required deliverables (output order)

Produce all four sections below in one response (or as the user requests: e.g. paste into `docs/`). Use **complete sentences**; avoid vague bullets (“improve UX”) without a concrete observation.

### 1. Executive summary

- **Audience**: product/engineering.
- **Length**: 5–10 sentences: overall quality, top risks, top opportunities.

### 2. Detailed review

For each **dimension** above:

- **What works well** (brief).
- **Issues**: observation → **where** (route, file path, or component) → **user impact** → **severity**: `Critical` | `High` | `Medium` | `Low`.

### 3. Gap TODO list

- Markdown checklist grouped by dimension or by surface (e.g. public vs cp).
- Each item: **actionable** title + one line of acceptance (“done when …”).
- Prefer stable IDs for tracking: `UX-001`, `UX-002`, …

Example item:

```markdown
- [ ] **UX-014** [Accessibility] [High] Add visible focus styles for primary nav links on `views/...` — Done when keyboard users can see focus on all top-level nav items in light and dark contexts.
```

### 4. Remediation plan

- **Phases**: e.g. Phase 0 quick wins (copy/contrast/focus), Phase 1 layout/responsive, Phase 2 component system alignment, Phase 3 flows/polish.
- For each phase: **goal**, **approximate scope** (files/areas), **dependencies**, **verification** (manual steps or “re-run accessibility spot check on routes X, Y”).
- Call out items that need **design decision** vs **pure implementation**.

## Project alignment

- Before large change lists, skim [project_brain/FEATURES.md](../../../project_brain/FEATURES.md) and [project_brain/LAUNCH_FREEZE_LOG.md](../../../project_brain/LAUNCH_FREEZE_LOG.md) so recommendations respect launch constraints and existing scope.
- Do not propose rewriting routing structure unless the review explicitly includes IA/URL strategy; prefer fixes inside existing patterns.

## Anti-patterns

- Do not produce a generic design essay with no file or page anchors.
- Do not conflate **visual taste** with **usability**; tie recommendations to users or tasks.
- Do not duplicate the entire TODO list inside the plan; the plan should **reference** IDs and sequencing.

## Verification before calling the review “complete”

- Every **Critical** or **High** item appears in the gap TODO list with an ID.
- The remediation plan maps **every Critical/High** ID to a phase or explicitly defers it with rationale.
