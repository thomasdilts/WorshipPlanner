---
name: ops-saas-ux
description: >-
  UX rules for dense operational SaaS (admin panels, planners, CRM, scheduling).
  Use when building or reviewing Filament/Livewire app screens, tables, forms,
  dashboards, or volunteer/task workflows — not marketing landing pages.
---

# Ops SaaS UX

Apply these rules to **application** surfaces (Filament panel, logged-in planner). Do **not** apply marketing/landing hero rules from other skills to these screens.

## Modes

| Mode | Goal | Rules source |
|------|------|----------------|
| Marketing / public | Brand, conversion | Landing-page guidance |
| App shell | Get work done | **This skill** |

## Hard rules

1. **One primary job per screen.** Next Sunday’s service, my tasks, or this team — not all three.
2. **Density over whitespace.** Prefer tables, timelines, and compact toolbars over card grids and stat strips.
3. **Mobile = tasks first.** Accept/refuse, my schedule, notifications. Full admin CRUD can stay desktop-primary.
4. **Status color = state only.** Accepted / pending / refused / conflict. Never decorative rainbow.
5. **Empty states need one next action.** “No events yet — Create event”, not a blank table.
6. **Destructive actions:** confirm; prefer undo or soft-delete where the domain allows.
7. **Don’t bury the next work item** behind charts or dashboards. Charts are secondary.
8. **Filters beat navigation sprawl.** Few nav groups; deep lists with search/filter.
9. **Permissions in UI language.** “Can edit events”, never hardcoded role names like ChurchAdmin.
10. **No fake dashboard chrome.** Skip KPI tiles unless they answer a real ops question.

## Layout defaults

- Page title + primary action (top right)
- Secondary actions in a overflow/menu or row actions
- Bulk actions only when users already select rows for that job
- Prefer inline edit / slide-over for small fields; full page for complex flows

## Anti-patterns

- Card grid of every entity type on the home page
- Purple gradient “SaaS” chrome on ops screens
- Hiding the service order behind multiple clicks from login
- Greyed-out nav for disabled modules (omit them instead)

## When reviewing

Check: can a volunteer finish “accept my task” in ≤3 taps on phone? Can a planner reorder the service without leaving the event screen?
