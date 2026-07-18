# School Electronic Voting System

A single Laravel application for school/student elections. Admin and staff users manage election setup, while students use a separate voter login with Student ID and PIN.

## Current Phase

Phase 1 MVP is functional, and the first Phase 2/3 pieces are in place:

- Admin authentication with Laravel Breeze
- Spatie roles and permissions seeded
- Super Admin staff user management
- Election, position, candidate, and voter management
- Candidate photo upload support
- CSV voter import
- CSV candidate import
- CSV import templates and failed-row correction downloads
- Separate voter login
- Ballot, review, and final submission flow
- Multi-choice positions where `max_choices` is greater than 1
- Anonymous vote storage
- One-student-one-vote enforcement
- Results dashboard after closure or internal preview, with turnout and winner/tie display
- PDF result report
- PDF turnout report
- CSV exports for results, voters, voted/not-voted status, and candidates
- Import history screen
- Audit log screen
- Audit records for admin logins, failed login attempts, result views, imports, exports, and staff user changes
- System settings screen
- School name/logo settings used in PDF reports
- Backup/local-network guidance screen
- Super Admin-only voted-status reset with required reason
- One-time generated voter PIN reset with audit logging
- Locked elections block related position, candidate, voter, import, and PIN/reset changes until explicitly unlocked
- Public result page after publication when enabled
- Automated tests covering voting, permissions, imports, exports, settings, public results, and locking rules
- Election readiness checklist before opening voting

## Security Rule

The system records that a student has voted in `voters.has_voted`, but candidate selections are stored separately in `anonymous_votes`.

`anonymous_votes` intentionally has no `voter_id`, `student_id`, `user_id`, name, email, or direct voter identifier. Do not add those columns.

## Stack

- Laravel 13
- Blade
- Livewire installed
- Bootstrap
- SQLite for local development, MySQL-ready through `.env`
- Spatie Laravel Permission
- DomPDF installed

Laravel Excel was requested in `PROJECT.md`, but `maatwebsite/excel` does not currently install against this PHP 8.5/Laravel 13 combination because its spreadsheet dependency caps PHP below 8.5. The app currently uses CSV import/export.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Open:

- Student voting: `http://127.0.0.1:8000/voter/login`
- Admin login: `http://127.0.0.1:8000/login`

## Default Accounts

Admin:

- Email: `admin@example.com`
- Password: `password`

Sample voters:

- Student ID `2026001`, PIN `1111`
- Student ID `2026002`, PIN `2222`
- Student ID `2026003`, PIN `3333`

Change default credentials before any real election.

## Basic Workflow

1. Log in as admin.
2. Create or edit an election.
3. Add positions.
4. Add candidates.
5. Add voters manually or import CSV.
6. Set election status to `active`.
7. Students log in at `/voter/login`, confirm details, vote, review, and submit.
8. Student is logged out after final submission.
9. Close the election and view results.
10. Publish results and enable public visibility if the school wants a public result page.
11. Lock final results after verification. Unlocking or changing locked election data requires deliberate admin action and audit history.

Use the election readiness screen before opening voting. It checks schedule, positions, active candidates, eligible voters, and the anonymous vote table guardrails.

## CSV Voter Import

Required columns:

```csv
student_id,full_name,class_name,programme,house,gender,pin
```

PINs are hashed before storage.

Voter PINs can also be reset from the voter edit screen. The generated PIN is shown once and then stored only as a hash.

## CSV Candidate Import

Required columns:

```csv
position,candidate_name,student_id,class_name,programme,house,gender,manifesto
```

The `position` value must match an existing position name in the selected election.

Import templates are available from the Import History screen. If rows fail validation, the import record links to a correction CSV containing the failed rows and a `failure_reason` column.

## Reports And Exports

From the results page, authorized users can download:

- Final results PDF
- Turnout PDF
- Results CSV
- Voter CSV
- Voted/not-voted status CSV
- Candidate CSV

Public result pages are available at `/results/{election}` only when:

- System setting `public_results_enabled` is enabled
- Election status is `published`
- Election has `results_visible_to_public` enabled

## Local Network Use

For a school LAN, run Laravel on a reachable host:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Find the server computer IP address and let voters open:

```text
http://SERVER-IP:8000/voter/login
```

Use a stable power source/UPS and back up the database before and after the election.

## Production Notes

- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Use strong admin passwords
- Use HTTPS where possible
- Restrict admin accounts
- Back up the database
- Protect `.env`
- Review audit logs
- Do not manually edit vote totals

## Verification

```bash
npm run build
php artisan test
```

For a full workflow rehearsal, run:

```bash
php artisan test --filter=EndToEndElectionRehearsalTest
```

That rehearsal covers admin setup, readiness, student voting, anonymous vote storage, closure, results, publication, public results, and CSV export.
