# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install && npm install

# First-time setup (migrate, seed, link storage)
composer run setup

# Development server (PHP + Vite concurrently)
composer run dev

# Asset build (production)
npm run build

# Run all tests
composer run test
# or: php artisan test

# Run a single test file
php artisan test tests/Feature/FollowUpItemTest.php

# Lint (Laravel Pint)
./vendor/bin/pint

# Database refresh with seed data
php artisan migrate:fresh --seed
```

## Architecture Overview

**DRCP** is a Laravel 12 document/case follow-up tracking system for organizational workflow coordination.

### Core Domain

The central entity is `FollowUpItem` — a trackable work item (letter, call, meeting, task, etc.) that moves through a lifecycle:

```
new → assigned → in_progress → waiting_external_response
                                      ↓
                           at_director ↔ returned_for_action
                                      ↓
                              completed / closed
```

Items have a `current_owner_id` (the user responsible right now) and a `section_id`. Every state change is logged to `item_histories` for a full audit trail.

### Roles & Access Control

Three roles, enforced in controllers and Blade views:

| Role | Access |
|------|--------|
| `secretary` | Full CRUD, route items to/from director, see all items |
| `head_of_section` | See/update items owned by themselves or their section members |
| `director` | View items in `at_director` status routed through secretary |

Role checks are done with `auth()->user()->role` comparisons — there is no Laravel Gate/Policy layer yet.

### Key Files

- [app/Models/FollowUpItem.php](app/Models/FollowUpItem.php) — core model with status constants, `reference_code` auto-generation (DRCP-YYYY-####), relationships
- [app/Http/Controllers/FollowUpItemController.php](app/Http/Controllers/FollowUpItemController.php) — CRUD + transfer + document upload logic; the largest controller
- [app/Http/Controllers/DashboardController.php](app/Http/Controllers/DashboardController.php) — role-filtered KPI aggregation
- [app/Models/ItemHistory.php](app/Models/ItemHistory.php) — audit trail; written on every item lifecycle event
- [app/Models/ItemDocument.php](app/Models/ItemDocument.php) — versioned file attachments (stored at `storage/app/items/{item_id}/`)
- [app/Events/ItemTransferred.php](app/Events/ItemTransferred.php) + [app/Listeners/SendTransferNotification.php](app/Listeners/SendTransferNotification.php) — event/listener pair for transfer notifications

### Database

SQLite by default (`database/database.sqlite`). Tests run against SQLite `:memory:` (see `phpunit.xml`).

Core tables: `users`, `sections`, `follow_up_items`, `item_histories`, `item_documents`.
Laravel infra tables: `sessions`, `cache`, `jobs` (all database-backed by default).

### Frontend

Blade templates + Tailwind CSS 4.0, compiled via Vite. No JS framework — vanilla JS for interactive elements. Layout base: [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php).

File uploads are validated server-side: max 10 MB, allowed MIME types: PDF, Word, Excel, images.
