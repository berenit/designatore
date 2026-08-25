# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

- `composer run dev` — Laravel server + queue listener + Vite dev server concurrently (primary local dev entrypoint).
- `php artisan test` / `php artisan test --compact --filter=test_name` — run tests (Pest, SQLite in-memory DB per `phpunit.xml`).
- `vendor/bin/pint --dirty --format agent` — fix PHP formatting on changed files only; run this after any PHP edit.
- `php artisan make:test --pest {Name}` — create a Pest feature test (omit `Feature/` prefix).
- `npm run dev` / `npm run build` — Vite dev server / production asset build.
- The app itself is always served by Laravel Herd at the project's `.test` domain — never run `php artisan serve` yourself to "start" the app locally.

## Architecture

**Important:** `RugbyMatch` uses `protected $table = 'matches'` (not the default `rugby_matches`). Route model binding for `rugby-matches` resource creates route parameter `{rugby_match}` — controller methods must use `RugbyMatch $rugbyMatch` (not `$match`) for binding to work, but pass `$match = $rugbyMatch` to views to keep view variable names consistent.

### Key behaviours to preserve

**Email notification flow:** `DesignationController::store()` fires `DesignationNotificationMail` immediately after saving. The mailable builds two `URL::signedRoute()` links (confirm/decline) and passes them to the `emails.designation-notification` Markdown template. `DesignationResponseController::respond()` validates the signature and updates `designation.status`.

**Date filtering in reports:** `ReportController::getDesignations()` uses a `join('matches', ...)` + `whereDate()` (not `whereHas`) because SQLite string comparison of datetime vs date is unreliable with `where()`. Always use `whereDate()` for date-only comparisons against `date_time` columns.

**Designations index is match-centric:** `DesignationController::index()` queries `RugbyMatch` (not `Designation`) for the selected week and eager-loads `designation.referee`. This shows all matches including those with no referee assigned yet.

**Team conflict check:** `RugbyMatchController` uses `bookedTeamDates()` (UNION of home + away per date) to pass a JSON map to Alpine.js for client-side team filtering, and `checkTeamConflicts()` for server-side validation. Both must stay in sync.

**Match form — league-first flow:** The create form uses Alpine.js (`matchForm()` component in the view) to filter team dropdowns by `league_division` and exclude teams already booked on the selected date. The `bookedDates` map is passed from the controller as JSON.

### Flash messages
Views read `session('success')` and `session('error')` (not `session('status')`), defined in `layouts/app.blade.php`. Controllers should use `->with('success', ...)`.

### Fillable attributes
Models use PHP 8 attribute syntax `#[Fillable([...])]` instead of the `$fillable` property array.

### PDF margins
DomPDF ignores `@page` margin shorthand and `setOption('margin-*')`. Margins are applied via a `.wrap` div with explicit `padding-*` in pixels inside `reports/pdf.blade.php`.

### Multi-team events (Concentramenti/Tornei)
A `RugbyMatch` with `competition_type` of `concentramento` or `torneo` involves 3+ teams (identified by a descriptive `name` instead of home/away) rather than the usual two-team home/away shape. Forms, conflict checks, and role-uniqueness rules (see below) all branch on this distinction — check `competition_type` before assuming a match has `home_team`/`away_team`.

### Designation role uniqueness
For ordinary two-team matches each role (Arbitro, Assistente 1, Assistente 2, Osservatore, 4° uomo, 5° uomo, Tutor) may be assigned only once per match. For Concentramenti/Tornei the same role may repeat across multiple referees. Validation in `DesignationController` must respect this distinction.

### Mail-triggered side effects
Beyond the initial designation email, referee-facing mail is fired from several places and must stay wired up together: `SendDesignationRemindersCommand` (hourly, resends `DesignationNotificationMail` to unanswered designations after 24h and repeats every 24h), `DesignationDeclinedMail` (to the assigning designatore on decline), `DesignationRemovedMail` (designation deleted), and `MatchCancelledMail`/`MatchUpdatedMail` (match cancelled/deleted, sent to every referee with an active designation on it). In Docker this reminder cron requires the `scheduler` compose service; locally it needs a running `schedule:work`/cron.

### Gmail API mailer
Besides standard SMTP, `MAIL_MAILER=gmail` routes outgoing mail through Gmail API via a custom Symfony Mailer transport (`app/Mail/Transport/GmailApiTransport.php`, registered in `AppServiceProvider`). Auth is one-time OAuth2 via `php artisan gmail:authorize` (`GmailAuthorizeCommand`), which needs the classic Authorization Code flow (device flow doesn't cover Gmail scopes) and stores a `GMAIL_REFRESH_TOKEN`.
