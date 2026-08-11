# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

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
