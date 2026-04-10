# Launch Freeze Log

Purpose: Track every feature hidden or removed for launch hardening, starting now.

This log is append-only. Do not delete entries. When a feature is brought back, update the same entry and set status to `restored`.

## Rules

- Add or update one entry in this file in the same change that hides/removes a feature.
- Keep references precise so restore work does not require deep git history digging.
- Use one entry per feature-level decision. If multiple files are part of one decision, keep them in one entry.

## Entry Template

Copy this section for each launch-freeze item:

```md
## [LF-YYYYMMDD-XX] Feature Name

- Status: hidden | removed | restored
- Date: YYYY-MM-DD
- Restored date: YYYY-MM-DD (only when restored)
- Owner: name/team
- Why deferred: short launch-first reason
- User impact: what users no longer see or can do
- Code references:
  - /path/or/route/
  - /another/path/file.php
- Re-enable steps:
  1. Step to restore route/UI/API behavior
  2. Step to restore related validation/permissions
  3. Step to re-test affected flow
- Dependencies: blockers or `none`
- Target restore phase: e.g. post-launch week 1
- Change refs:
  - Commit/PR/issue link or id
```

## Entries

<!-- Add new entries below this line, newest first. -->

## [LF-20260409-01] Social login buttons (Google/Facebook) on auth screens

- Status: hidden
- Date: 2026-04-09
- Restored date: N/A
- Owner: launch hardening team
- Why deferred: reduce go-live risk by temporarily removing third-party OAuth entry points from primary auth screens.
- User impact: users can no longer start sign-in/sign-up with Google or Facebook from login/register pages; email/password auth remains available.
- Code references:
  - /login/
  - /register/
  - login/index.php
  - register/index.php
- Re-enable steps:
  1. Restore Google/Facebook button blocks in `login/index.php` and `register/index.php`.
  2. Restore the social/email divider text blocks in both files.
  3. Validate `/auth/google/` and `/auth/facebook/` callback flows end-to-end in staging before production release.
- Dependencies: valid Google/Facebook OAuth app settings and callback URLs in the target environment.
- Target restore phase: post-launch phase 1
- Change refs:
  - local working tree change on 2026-04-09
