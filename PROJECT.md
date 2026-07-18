You are a senior Laravel software engineer. Build a complete but simple, secure, and maintainable electronic voting system for a school/student election.

The system should be suitable for a school election with about 2,000 students voting on a local school network or normal web hosting.

The application must be built as a single Laravel application, not a separate SPA.

MAIN STACK:

* Laravel latest stable version
* Livewire
* Blade
* Bootstrap
* MySQL
* Laravel Breeze or Laravel built-in authentication for admin/staff users
* Spatie Laravel Permission for roles and permissions
* Laravel Excel for Excel/CSV import and export
* DomPDF or another suitable Laravel PDF package for reports

PROJECT NAME:
School Electronic Voting System

CORE GOAL:
Build a secure school voting system where eligible students can log in, vote once per election, and where authorized administrators can manage elections, positions, candidates, voters, results, reports, and audit logs.

MOST IMPORTANT SECURITY RULE:
The system must record that a student has voted, but it must not expose or store a direct link between the voter and the candidate selected.

The anonymous vote table must not contain voter_id, student_id, user_id, email, name, or any direct voter identifier.

Use a secure anonymous voting approach:

* Store voters separately.
* Mark a voter as having voted after successful submission.
* Store vote choices in an anonymous votes table without voter identity.
* Use database transactions and row locking during vote submission.
* Do not log candidate choices together with voter identity.

APPLICATION STRUCTURE:
Use two login areas:

1. Admin/staff login

* Uses the normal Laravel users table.
* Used by Super Admin, Election Admin, ICT Officer, Electoral Officer, and Observer/Auditor.

2. Voter/student login

* Uses a separate voters table.
* Students log in with Student ID/Index Number and PIN/password.
* Voters should not use the normal users table.
* After a voter successfully votes, automatically log the voter out and return to the voter login page.

USER ROLES:

1. Super Admin

* Full access to all modules
* Manage users, roles, elections, positions, candidates, voters, results, reports, settings, and audit logs
* Can lock/unlock final results with a reason

2. Election Admin

* Manage elections
* Manage positions
* Manage candidates
* Manage voters
* Open, pause, close, and publish elections
* View results after election closure

3. ICT/Technical Officer

* Import voters and candidates
* Export data
* Assist with system setup and backups
* View turnout
* Cannot alter votes or manipulate results

4. Electoral Officer/Teacher

* Assist voters
* Verify whether a student has voted
* View turnout
* Cannot see live candidate results unless allowed by Super Admin
* Cannot edit votes

5. Observer/Auditor

* Read-only access
* View turnout, final results, and audit logs
* Cannot edit data

6. Voter/Student

* Log in with Student ID/Index Number and PIN/password
* Confirm personal details
* Vote once
* See voting completion confirmation
* Cannot vote again
* Cannot view results unless public result viewing is enabled after publication

PHASED DEVELOPMENT REQUIREMENT:
Build the system in phases. Do not attempt to complete all advanced features at once.

PHASE 1: MVP CORE SYSTEM
This phase is compulsory.

Build:

1. Laravel project setup
2. Authentication for admin/staff
3. Roles and permissions
4. Super Admin account
5. Admin dashboard
6. Election management
7. Position management
8. Candidate management with photos
9. Voter management
10. Manual voter creation
11. Excel/CSV voter import
12. Voter login
13. Voting interface
14. Vote review page
15. Secure anonymous vote submission
16. One-student-one-vote enforcement
17. Election closure
18. Basic result dashboard after election closure

PHASE 2: REPORTS AND EXPORTS
Build:

1. PDF result report
2. Excel result export
3. Voter turnout report
4. Voted/not voted report
5. Candidate list export
6. Voter list export

PHASE 3: AUDIT, SETTINGS, AND POLISH
Build:

1. Audit logs
2. Import history
3. System settings
4. Election result locking
5. Public result publication
6. Backup/export guidance screen
7. Better dashboard charts/cards
8. UI polish
9. Automated tests

DATABASE DESIGN:

Create migrations for the following tables:

1. users
   For admin/staff users only.

2. roles and permissions
   Use Spatie Laravel Permission tables.

3. elections
   Fields:

* id
* title
* description
* academic_year
* start_at
* end_at
* status: draft, scheduled, active, paused, closed, published, locked
* results_visible_to_public: boolean
* allow_internal_live_preview: boolean
* created_by
* closed_by, nullable
* closed_at, nullable
* locked_by, nullable
* locked_at, nullable
* lock_reason, nullable
* timestamps

4. positions
   Fields:

* id
* election_id
* name
* description
* max_choices, default 1
* display_order
* is_required: boolean
* allow_abstain: boolean
* is_active: boolean
* timestamps

5. candidates
   Fields:

* id
* election_id
* position_id
* candidate_name
* student_id, nullable
* class_name, nullable
* programme, nullable
* house, nullable
* gender, nullable
* photo_path, nullable
* manifesto, nullable
* ballot_number, nullable
* display_order
* status: active, inactive, disqualified
* timestamps

6. voters
   Fields:

* id
* election_id
* student_id
* full_name
* class_name
* programme
* house, nullable
* gender, nullable
* pin_hash or password_hash
* is_eligible: boolean
* has_voted: boolean
* voted_at, nullable
* last_login_at, nullable
* timestamps

Important voter constraint:

* election_id + student_id must be unique.

7. anonymous_votes
   Fields:

* id
* election_id
* position_id
* candidate_id, nullable
* anonymous_ballot_code
* is_abstain: boolean
* created_at

Important:

* This table must not contain voter_id, student_id, user_id, name, or any direct voter identifier.

8. imports
   Fields:

* id
* election_id
* import_type: voters, candidates, positions
* filename
* total_rows
* successful_rows
* failed_rows
* imported_by
* created_at

9. audit_logs
   Fields:

* id
* user_id, nullable
* election_id, nullable
* role, nullable
* action
* description
* ip_address
* user_agent
* severity: info, warning, critical
* created_at

Important:

* Do not store voter candidate choices in audit logs.
* Do not store “student X voted for candidate Y.”
* It is acceptable to log “student with ID X completed voting” only if no candidate choices are included.

10. system_settings
    Fields:

* id
* key
* value
* timestamps

CORE MODULES:

1. Admin Authentication
   Build admin/staff authentication using Laravel Breeze or Laravel’s built-in authentication.
   Create role-based redirects after login.
   Protect admin routes using middleware and permissions.

2. Role and Permission Management
   Use Spatie Laravel Permission.
   Create roles:

* Super Admin
* Election Admin
* ICT Officer
* Electoral Officer
* Observer/Auditor

Create permissions such as:

* manage elections
* manage positions
* manage candidates
* manage voters
* import voters
* import candidates
* view turnout
* view results
* publish results
* lock results
* view audit logs
* manage settings
* export reports

3. Admin Dashboard
   Dashboard should show:

* Total voters
* Total candidates
* Total positions
* Total votes cast
* Voter turnout percentage
* Current election status
* Recent actions
* Quick links to voters, candidates, positions, and results

4. Election Management
   Admins should be able to:

* Create election
* Edit election
* Set start and end time
* Set status
* Activate election
* Pause election
* Close election
* Publish results
* Lock final results

Rules:

* Draft elections cannot be voted in.
* Scheduled elections cannot be voted in before start time.
* Active elections can be voted in.
* Paused elections cannot be voted in.
* Closed elections cannot accept votes.
* Published elections show final results if result visibility is enabled.
* Locked elections cannot be edited unless Super Admin unlocks with a reason.
* An election with votes should not be deleted.

5. Position Management
   Admins should be able to:

* Add position
* Edit position
* Set display order
* Set whether position is required
* Set whether abstain is allowed
* Set maximum choices, default 1
* Activate/deactivate position

Rules:

* Candidates are grouped under positions.
* Voter can select only the allowed number of candidates per position.
* If a required position has no selection and abstain is not allowed, voting should not submit.

6. Candidate Management
   Admins should be able to:

* Add candidate
* Upload candidate photo
* Edit candidate
* Assign candidate to position
* Set ballot number or display order
* Mark candidate as active, inactive, or disqualified
* Import candidates from Excel/CSV
* Export candidate list

Rules:

* Do not delete candidates after votes exist.
* Mark candidates inactive/disqualified instead.
* Only active candidates should appear on the ballot.
* Candidate must belong to the correct election and position.

7. Voter Management
   Admins should be able to:

* Add voter manually
* Import voters from Excel/CSV
* Generate voter PINs
* Reset voter PIN
* Search voters
* Filter voters by class, programme, house, voted/not voted, eligible/ineligible
* Export voter list
* View voter voting status

Rules:

* Student ID must be unique per election.
* PIN/password must be hashed.
* Admins should not see plain PINs after generation unless printing a one-time slip immediately.
* A voter can be marked ineligible before voting.
* A voter who has voted cannot be reset casually.
* Any reset of voted status, if implemented at all, must be Super Admin-only and require a reason.

8. Voter Login
   Create a separate voter login page.

Voter login fields:

* Student ID/Index Number
* PIN/password
* Election selection if more than one active election exists

After login:

* Verify election status.
* Verify voter eligibility.
* Verify voter has not voted.
* Show voter identity confirmation:

  * Full name
  * Student ID
  * Class
  * Programme
* Provide “Proceed to Vote” button.

If voter has already voted:

* Show a message that voting has already been completed.
* Do not show ballot.

9. Voting Interface
   Build a simple and clear voting page.

For each position:

* Show position name
* Show candidate cards
* Each candidate card should include:

  * Photo
  * Name
  * Class/programme
  * Optional manifesto
  * Select button/radio button

For required positions:

* Voter must select a valid candidate unless abstain is allowed.

For optional positions:

* Voter may skip.

Before final submission:

* Show review page with all selected candidates.
* Warn that vote submission is final.
* Provide “Submit Final Vote” button.

After submission:

* Save vote anonymously.
* Mark voter as voted.
* Show success page.
* Automatically log voter out.
* Return to voter login page.

10. Vote Submission Logic
    Create a VoteCastingService.

The service must:

* Accept voter and ballot choices
* Confirm election is active
* Confirm current time is within election time
* Confirm voter is eligible
* Confirm voter has not voted
* Lock voter row using database row lock
* Validate all selected positions
* Validate all selected candidates
* Confirm selected candidates belong to correct election and position
* Confirm max choices per position
* Store anonymous votes without voter identity
* Mark voter as voted
* Set voted_at timestamp
* Write safe audit log
* Commit transaction
* Roll back transaction if any step fails

Use database transaction for the full vote submission process.

The anonymous vote insert must not contain:

* voter_id
* student_id
* user_id
* voter name
* IP address linked to candidate choice

11. Results Module
    Results should be based on anonymous_votes.

Admins should be able to:

* View results after election is closed
* View result per position
* View total votes per candidate
* View percentage per candidate
* View ranking
* View winner
* View ties
* View abstentions where applicable
* View total registered voters
* View total votes cast
* View turnout percentage

Rules:

* Results should not be visible to voters during active voting.
* Results should not be public until election is closed and published.
* If two or more candidates have the highest votes, mark the position as “Tie.”
* Do not automatically break ties.
* Do not allow manual editing of vote totals.

12. Reports
    In Phase 2, generate:

* Final result PDF
* Result by position PDF
* Voter turnout PDF
* Voted/not voted report
* Candidate list
* Voter register
* Excel exports

PDF result report should include:

* School name
* School logo if configured
* Election name
* Academic year
* Date/time of election
* Total registered voters
* Total votes cast
* Turnout percentage
* Position-by-position results
* Winner or tie note
* Generated by
* Generated date/time
* Signature lines for electoral officers

13. Audit Logs
    In Phase 3, log:

* Admin login
* Failed login attempts
* Election created/updated/opened/paused/closed/published/locked
* Voter imported
* Candidate imported
* Voter PIN reset
* Candidate updated
* Report exported
* Results viewed
* Vote completed
* Failed vote attempt

Do not log candidate choices tied to voter identity.

14. Excel/CSV Import
    Support import for:

* Voters
* Candidates

Requirements:

* Provide sample templates
* Validate required columns
* Show preview before saving
* Detect duplicates
* Show failed rows and reasons
* Save import history
* Allow export of failed rows for correction

Voter import columns:

* student_id
* full_name
* class_name
* programme
* house
* gender
* pin

Candidate import columns:

* position
* candidate_name
* student_id
* class_name
* programme
* house
* gender
* manifesto

15. UI Requirements
    Use Bootstrap.

Design style:

* Simple
* Professional
* School-friendly
* Responsive
* Clear navigation
* Large buttons on voter screens
* Candidate photos clearly visible
* Minimal distractions during voting

Admin layout:

* Sidebar navigation
* Top navbar
* Dashboard cards
* Searchable tables
* Filters
* Confirmation modals
* Success/error alerts

Voter layout:

* Clean login page
* Identity confirmation screen
* Ballot page
* Review page
* Success page
* Auto logout after voting

16. Testing
    Write automated tests for:

* Admin can create election
* Admin can add position
* Admin can add candidate
* Admin can add/import voter
* Voter can log in
* Ineligible voter cannot vote
* Voter cannot vote before election starts
* Voter cannot vote after election closes
* Voter can vote once
* Voter cannot vote twice
* Anonymous vote does not store voter_id/student_id/user_id
* Results calculate correctly
* Tie is detected correctly
* Results hidden before closure
* Permissions work correctly

17. Seeders
    Create seeders for:

* Roles
* Permissions
* Super Admin
* Sample election
* Sample positions
* Sample candidates
* Sample voters

Default Super Admin:

* Email: [admin@example.com](mailto:admin@example.com)
* Password: password

Add a clear warning in README that default credentials must be changed before real use.

18. Local Network Deployment
    Document how to run the system on a local school network:

* Install PHP, Composer, MySQL
* Configure .env
* Run migrations and seeders
* Start Laravel server or configure Apache/Nginx
* Find local server IP address
* Allow other computers on the same network to access the system
* Use a stable power source/UPS during voting
* Back up the database before and after election

19. Production Security
    Add documentation for:

* APP_ENV=production
* APP_DEBUG=false
* Strong admin passwords
* HTTPS where possible
* Database backup
* Restricted admin accounts
* Secure file upload
* Correct storage permissions
* Protect .env file
* Regular audit review

20. README Documentation
    Create README.md with:

* Project overview
* Features
* Stack
* Installation steps
* Database setup
* Admin login setup
* How to create an election
* How to add positions
* How to add candidates
* How to import voters
* How students vote
* How to close election
* How to view results
* How to export reports
* Local network deployment guide
* Security notes
* Troubleshooting guide

21. Final Review Before Completion
    Before declaring the project complete, verify:

* A voter can vote only once.
* Votes are anonymous.
* Admins cannot see who voted for whom.
* Results are calculated from anonymous votes.
* Results are hidden until election closure/publication.
* Voter PINs/passwords are hashed.
* Database transactions protect vote submission.
* Candidate choices are validated.
* Permissions are enforced.
* Reports work.
* The system can run on a local school network.
* README is clear for a beginner/intermediate Laravel user.
