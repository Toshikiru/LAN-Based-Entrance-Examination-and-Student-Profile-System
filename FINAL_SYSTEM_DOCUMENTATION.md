# FINAL_SYSTEM_DOCUMENTATION.md

**Purpose of this document:** This is the master technical reference for the *actually implemented* system, verified directly against the codebase at `c:\xampp\htdocs\capstone_final\guidancepulse` (Laravel 12, PHP, MySQL/MariaDB). It exists so that a future writing pass (Chapters 3–5 of the capstone manuscript) can be produced without re-reading the entire codebase, and so that every claim in the manuscript can be traced back to real code.

**Source of truth policy:** Where the approved proposal (Chapter 1–2, `Documents/PROPOSAL_REVISED_LAN_BASED_FINAL.docx`) and the implementation disagree, **this document describes the implementation**, not the proposal. Every feature below was confirmed either by direct source reading in this session or by a real HTTP round-trip test against the running application (`php artisan serve` on `127.0.0.1:8000` and/or XAMPP on `localhost/capstone_final/guidancepulse/public`) earlier in this working session. Nothing here is inferred from route/view existence alone without checking the underlying logic.

**Status legend used throughout:**
- ✅ **IMPLEMENTED** — working, verified.
- 🟡 **PARTIALLY IMPLEMENTED** — real but incomplete/simplified relative to what the name suggests.
- 📋 **PLANNED (stub)** — UI exists announcing the feature; no working logic behind it.
- ❌ **NOT IMPLEMENTED** — does not exist in the codebase.

---

## 1. System Overview

**Official title (per approved proposal, unchanged):** *LAN-Based Entrance Examination and Student Profile System for Guidance Services*. In-app branding (system name, school name) is configurable at runtime via System Settings and currently displays as "TPC EntryPoint" for "Talibon Polytechnic College" in this installation — that is a configuration value, not a change to the formal title.

**Purpose:** Digitizes and automates the entrance-examination workflow of a school's Guidance Office (question authoring, timed testing, automatic + manually assisted scoring, results and interpretation) and maintains a permanent, cumulative student profile that persists beyond the exam itself (counseling/bio-notes, examination history).

**Target users (3 roles):** Super Administrator (system-level administration), Guidance Counselor (day-to-day exam/guidance administration), Student (examinee).

**Problem being addressed:** Manual, paper-and-pencil entrance examination administration — slow score release, error-prone hand-checking, scattered/inconsistent student records, no continuity between an admission exam result and later guidance/counseling activity.

**System scope (implemented):** Question authoring (manual + bulk import) → exam building/targeting/publishing → timed student exam-taking over LAN → automatic scoring (objective items) + manual grading (Short Answer) → score interpretation → results management, live monitoring, and institutional reporting → a cumulative student profile carrying exam history and counseling notes forward → system administration (accounts, settings, branding, reference data, backup, audit log).

**LAN-based / offline architecture:** ✅ Verified. The application is designed to run with **zero external internet dependency**. All front-end assets (CSS, JS, fonts, icons) are bundled and self-hosted via Vite (see §12 for full detail); this was a real defect found and fixed during this development cycle (the app previously loaded Tailwind/Google Fonts/Turbo/Alpine/Chart.js from public CDNs, which has since been eliminated and re-verified with zero external references across every module, every role).

**Technology stack (verified from `composer.json`, `package.json`, actual code):**
- Backend: Laravel 12 (PHP), MVC architecture
- Database: MySQL/MariaDB (10.4.32-MariaDB confirmed in this environment)
- Frontend: Blade templates, Tailwind CSS v4 (via `@tailwindcss/vite`), Alpine.js 3, Turbo (Hotwired) 8 for SPA-like navigation, Chart.js 4 (analytics charts), SortableJS (drag-reorder in the exam builder)
- Fonts/Icons: `@fontsource-variable/inter` (self-hosted Inter font), `material-symbols` npm package (self-hosted Material Symbols icon font)
- Build tooling: Vite 6 + `laravel-vite-plugin`
- PDF parsing (question import only, not generation): `smalot/pdfparser`
- Server: XAMPP (Apache + MySQL) for local/LAN deployment
- No PDF-generation library present (no `dompdf`/`snappy`) — see §18.
- No `phpoffice/phpword` present — `.docx` question import is currently unusable (see §18).

---

## 2. User Roles and Permissions

Enforced via the `role` middleware alias (`App\Http\Middleware\EnsureUserHasRole`) applied at route-group level in `routes/web.php`, `routes/admin.php`, `routes/counselor.php`, `routes/student.php`. Verified end-to-end via real HTTP requests this session (every combination below returned exactly the expected status code).

### Super Administrator (`role: super_admin`)
Full access to (`/admin/*`, gated `role:super_admin` only):
- System Dashboard (§9)
- Guidance Administrator (Counselor) account management: list, create, edit, activate/deactivate, reset password
- Audit Log viewer (read-only)
- System Settings (School Info + Branding tabs; Academic Year + General tabs are stubs)
- Reference Data: Departments, Courses, Year Levels (full CRUD/toggle)
- Backup & Restore (create, download, delete, restore)

Also has **shared, read-mostly access** to the Counselor module (`/counselor/*`, outer group `role:counselor,super_admin`): can view Students, Question Bank, Exams, Live Monitoring, Results, Reports — but is **explicitly excluded** from the inner `role:counselor`-only route group, meaning a Super Admin **cannot**: create/edit/delete/archive students, author or edit questions, create/edit/publish/delete exams, use the Exam Builder, manually grade, manage score-interpretation ranges, or author/edit counseling notes.

**Explicit shared exception:** Both Super Admin and Counselor can reset a **student's** password (`counselor.students.reset-password`) — a deliberate, code-commented carve-out ("intentionally shared") so either role can act as the Guidance Office's password-reset contact point, distinct from the Super Admin's usual view-only posture in the Counselor module.

### Guidance Counselor (`role: counselor`)
Everything in the shared Counselor module above, **plus** (inner `role:counselor`-only group, Super Admin barred):
- Full Student CRUD (create, edit, update, archive, restore)
- Counseling/Bio-Notes authoring and editing
- Full Exam CRUD (create, edit, update, delete, activate/deactivate) and the entire Exam Builder (sections, question attach/detach/reorder, settings, publish, access code generation)
- Live Monitoring actions (flag/terminate a session)
- Manual grading of Short Answer items
- Score Interpretation range management
- Full Question Bank CRUD and bulk import
- Report scheduling (create/toggle)

### Student (`role: student`)
Access limited to `/student/*` only:
- Student Dashboard
- Join an exam via access code
- Take an assigned exam (answer, navigate, submit)
- View own results ("My Results")
- Edit own profile (email only) and password (via the shared Profile module)

**Cross-role restrictions verified this session (real HTTP tests, all passed):**
- Student → any `/counselor/*` or `/admin/*` route → 403
- Counselor → any `/admin/*` route → 403
- Super Admin → any Counselor-only write route (e.g. `students/{id}/edit`) → 403
- Login with valid credentials but the *wrong* role selected → authentication is rejected and the session is not established (verified for all 6 directional combinations across the 3 roles) — enforced in `App\Http\Requests\Auth\LoginRequest::authenticate()`, not just in the UI.

---

## 3. Complete Module Inventory

Format per module: Purpose · Users · Main functions/CRUD · Key workflow notes · DB tables · Routes/Controller · Business rules. Only modules with real, verified logic are marked ✅; anything thinner is marked accordingly.

### Authentication ✅
- **Purpose:** Login/logout, password recovery, forced password change.
- **Users:** Everyone (guest for login/reset; any authenticated role for logout).
- **Functions:** Login with School ID + password + selected role (role is now a real, server-validated field, not cosmetic); logout; "Forgot Password" → contact-the-Guidance-Office instructions (LAN-appropriate primary path) plus an optional self-service email reset form that only renders if a real mail driver is configured (`config('mail.default')` isn't `log`/`array`); admin/counselor-initiated password reset that forces the user to set a new password on next login.
- **Workflow:** `LoginRequest::authenticate()` — rate-limits by `school_id|ip`, `Auth::attempt()`, then checks the submitted `role` against the account's actual `UserRole`; on mismatch, logs the user out immediately (even though `Auth::attempt` already technically authenticated them) and throws a validation error naming the account's real role. Checks `UserStatus::Active` last.
- **DB:** `users`, `password_reset_tokens`, `sessions`.
- **Routes/Controller:** `Auth\LoginController`, `Auth\PasswordResetController`; routes in `routes/web.php`.
- **Business rules:** 5 failed attempts per `school_id+ip` triggers Laravel's `RateLimiter` lockout. A user with `must_change_password = true` is locked to the Profile page (see Account/Profile Settings) until they set a new password, enforced globally by `EnsurePasswordIsCurrent` middleware.

### Dashboard ✅ (3 distinct dashboards — see §9 for full widget breakdown)
- **Purpose:** Role-specific landing page summarizing what's relevant right now.
- **Users:** Each role sees only their own dashboard.
- **DB/Controller:** `Admin\DashboardController` + `AdminDashboardService`; `Counselor\DashboardController` + `CounselorDashboardService`; `Student\DashboardController` + `StudentDashboardService`.

### Student Management ✅
- **Purpose:** Guidance Office's student directory/CRUD.
- **Users:** Counselor (full CRUD), Super Admin (view + password reset only).
- **Functions:** List (paginated, searchable, filterable by status/department/year level), create, view (`show`, with tabs), edit, soft-delete ("archive")/restore, CSV export, password reset (shared with Super Admin).
- **DB:** `users` (role=student) + `student_profiles`.
- **Routes/Controller:** `Counselor\StudentController`; `counselor.students.*`.
- **Business rules:** Archiving is a soft delete (`SoftDeletes` on `User`); archived students remain visible via `withTrashed()` on `show`/`edit`/`update` routes for record-keeping.

### Student Profile ✅ / Cumulative Records ✅
See §6 for full field-level breakdown. Purpose: a permanent, growing record per student combining identity, academic placement, exam history, and counseling history in one place. Rendered via `counselor.students.show` (tabbed) and printable via `Counselor\StudentRecordController::print()` (`counselor.students.record.print` — **deliberately excludes confidential counseling notes** from the printed document, per an explicit code comment).

### Question Bank ✅
- **Purpose:** Central repository of reusable exam questions.
- **Users:** Counselor (full CRUD, view-shared with Super Admin).
- **Functions:** List/search/filter by category/type/difficulty, create, view, edit, delete (soft delete — `Question` uses `SoftDeletes`).
- **Question types:** Multiple Choice, True/False, Likert, Short Answer (see dedicated entries below).
- **DB:** `questions`, `question_options`.
- **Routes/Controller:** `Counselor\QuestionController`; `counselor.questions.*`.

### Question Import ✅
- **Purpose:** Bulk-add questions from a prepared file instead of one-by-one authoring.
- **Users:** Counselor only.
- **Functions:** Download a ready-to-fill `.txt` template; upload → parse → **review/preview screen** (shows detected questions, valid vs. error count, per-question validation errors) → select which valid questions to actually import → confirm import.
- **Supported formats:** `.txt` (works, no dependency), `.pdf` (works, via `smalot/pdfparser`), `.docx` (🟡 **code path exists but throws a runtime error** — `phpoffice/phpword` is not installed; see §18).
- **DB/Controller:** `App\Services\QuestionDocumentExtractor` (text extraction per format), `App\Services\QuestionImportParser` (parses the plain-text question format into structured question data with per-item validation), `Counselor\QuestionImportController`.
- **Format rules (from the parser/template):** One question per block separated by a blank line; optional headers `TYPE`, `CATEGORY`, `DIFFICULTY`, `POINTS`; correct choice marked with a trailing `*` (or `[correct]`); Likert options use `- Label = value` syntax.
- **Note:** The import form is marked `data-turbo="false"` — Turbo's fetch-based form interception can silently fail for multipart file uploads with no visible error, so this form intentionally uses a native browser submission instead (a real defect found and fixed this session).

### Exam Builder ✅
- **Purpose:** Compose a specific exam's content — sections, attached questions, ordering, access code, publish state.
- **Users:** Counselor only (Super Admin has no access at all to this URL, unlike most of the Counselor module).
- **Functions:** Add/remove sections; browse & attach questions from the Question Bank (excludes already-attached); drag-and-drop reorder (SortableJS, initialized on `turbo:load` for reliability across Turbo navigations); detach a question; per-exam settings (passing score, shuffle questions/choices, auto-submit, show score, show correct answers, allow resume); generate/regenerate the access code; publish.
- **DB:** `exams`, `exam_sections`, `exam_questions` (pivot, carries `order`/`marks_override`/`exam_section_id`), `questions`.
- **Routes/Controller:** `Counselor\ExamBuilderController`; `counselor.exams.builder*`.
- **Business rules:** A question can only appear once per exam (`unique(exam_id, question_id)` on the pivot). Publishing requires at least one question (`publish()` blocks with an error otherwise) and auto-generates a unique access code if one doesn't already exist.

### Exam Management ✅
- **Purpose:** The exam's own lifecycle/metadata, separate from its content.
- **Users:** Counselor (full CRUD), Super Admin (list/view only).
- **Functions:** List, create, edit, delete (soft delete), activate/deactivate (toggles `ExamStatus`).
- **DB:** `exams`.
- **Routes/Controller:** `Counselor\ExamController`; `counselor.exams.*`.

### Exam Targeting ✅
- **Purpose:** Restrict which students can see/attempt a given exam.
- **Implementation:** `exams.department_id` (nullable FK) and `exams.year_level` (nullable free-text string) — either or both may be set. `Exam::scopeTargetedTo(Builder $query, User $student)` filters exams visible to a given student: exam's department is null OR matches the student's department, AND exam's year level is null OR matches the student's profile year level. A `null` value on either field means "open to everyone" for that dimension.
- **Business rule:** Targeting is **advisory/visibility-based** for the "available exams" dashboard listing — actually *joining* an exam is still gated purely by knowing the correct **access code** (see next entry), not by a server-side targeting check at join time. This is worth being precise about in the manuscript: targeting controls what a student *sees as available*, the access code controls what they can actually *enter*.

### Access Codes ✅
- **Purpose:** The actual admission control for starting an exam session.
- **Implementation:** `exams.access_code` — unique, auto-generated 6-character alphanumeric string (`Str::random(6)`, uppercased, collision-checked against existing codes) via `ExamBuilderController::uniqueAccessCode()`; can be regenerated on demand.
- **Verified rule:** `ExamTakingController::start()` looks up `Exam::where('access_code', ...)->where('status', Published)->first()` — an exam must be **Published** to be joinable by code, and the code lookup is case-insensitive-by-uppercasing on input.

### Exam Taking ✅ (full student flow — see §4 for the complete sequence)
- **Users:** Student only.
- **Routes/Controller:** `Student\ExamTakingController`; `student.exams.*`.
- **DB:** `exam_sessions`, `exam_answers`.

### Timer / Auto-submit ✅
- **Implementation:** Server-authoritative. `ExamTakingController::remainingSeconds()` computes remaining time from `session.started_at` + `exam.duration_minutes`, never from anything the client sends. Every `take()` GET request checks `remainingSeconds() <= 0` and force-finalizes (auto-submits) the session if expired. The client-side countdown (Alpine `examTimer()` in the runner view) is cosmetic UX only — it cannot be used to extend time, and even if the client never fires its own auto-submit, the very next server request re-checks expiry and finalizes anyway.
- **Minor verified edge case:** `answer()` (the per-question save/navigate endpoint) does not itself re-check expiry before persisting an answer — a single in-flight POST can save one more answer after time has technically elapsed, before the very next request finalizes the session. This is a narrow timing race, not a client-side bypass (a student cannot indefinitely continue past expiry).

### One-Active-Session Rule ✅ (added this development cycle — see §18/§19)
- **Rule:** A student may not have more than one `in_progress`/`flagged` exam session at a time, across *all* exams (not just per-exam).
- **Enforcement — two layers:**
  1. **Application-level:** `ExamTakingController::start()` checks for any other active session before allowing a new one to begin, returning a specific error naming the exam that's still in progress.
  2. **Database-level (defense in depth):** a generated column `exam_sessions.active_session_lock` (`student_id` when status is `in_progress`/`flagged`, else `NULL`) with a **unique index** `exam_sessions_one_active_per_student` — MySQL/MariaDB unique indexes ignore `NULL`, so this only ever constrains truly-active rows, never completed/not-started ones. A caught `QueryException` in `start()` handles the narrow race-condition case (e.g. two browser tabs) where the app-level check alone might be beaten.
- **Explicitly does NOT block:** resuming the student's own already-in-progress session for the *same* exam (that's the normal "Resume" action, unaffected).
- **Verified this session** with a real HTTP test: starting Exam A then attempting Exam B while A is active → blocked with the correct message; resuming Exam A itself still works; DB confirmed exactly one active session existed throughout.

### Multiple Choice ✅
Question type with 2+ `question_options`, exactly one (or more) marked `is_correct = true`. Auto-scored: full `marks` if the selected option's `is_correct` is true, else `-negative_marks` (if configured, else 0). See "Automatic Scoring" below for the shared scoring code path.

### True/False ✅
Same underlying mechanism as Multiple Choice (2 options, one `is_correct`) — the scoring code treats MC and True/False identically (no type-specific branching beyond the shared `is_correct`/`selected_option_id` check).

### Likert ✅ (fixed this development cycle — see §18/§19)
- **Structure:** 2–10 options, each with a numeric `value` (no `is_correct` concept at all — deliberately; the question-authoring UI's own copy states "No answer is 'correct'" for this type, and `is_correct` is hard-coded `false` for every Likert option in `QuestionController::syncOptions()`).
- **Scoring (current, correct behavior):** Proportional to the selected option's `value` against the highest `value` among that question's own options: `awarded = round((selected_value / max_value) * points, 2)`. Example: a 1–5 scale worth 5 points, student picks the "4" option → awarded 4.00. `is_correct` is left `NULL` on the answer row (semantically honest — there is no right/wrong for this type).
- **Prior defect (now fixed):** Likert responses used to be recorded but **excluded from scoring entirely** in three separate code paths (`ExamTakingController::finalize()`, `ExamResultController::recomputeResult()` — used when a Short Answer regrade recomputes the total, and `StudentRecordService::sectionBreakdown()` — the cumulative-record's per-section score breakdown). All three were found and fixed in the same session; verified via a real graded exam attempt that the Likert contribution now survives a Short-Answer regrade correctly.

### Short Answer ✅
Free-text response (`answer_text`), never auto-scored (`awarded_marks` stays `NULL` until a counselor manually grades it). Contributes to the exam's `maxScore` from the moment it's answered, but the overall result stays "preliminary" (see Manual Grading) until graded.

### Automatic Scoring ✅
- **Where:** `ExamTakingController::finalize()`, called on submit (manual or auto via timer expiry).
- **Logic (verified, current):** Iterates every `ExamQuestion` on the exam; Short Answer → tallied into `shortAnswerMax` only, skipped from scoring; Likert → proportional scoring as above, added to `objectiveMax`; Multiple Choice/True-False → full generic `is_correct`-based scoring, added to `objectiveMax`. `totalScore = max(0, sum of all awarded marks)`. `maxScore = objectiveMax + shortAnswerMax`. `percentage = totalScore / maxScore * 100` (rounded 2dp). `passed = percentage >= exam.passing_score`.
- **Result storage:** `ExamResult::updateOrCreate(['exam_session_id' => ...], [...])` — one result row per session (1:1).

### Likert Scoring ✅
See "Likert" entry above — documented separately here because the task list calls it out distinctly. Same underlying mechanism, no separate module/route.

### Manual Grading ✅
- **Purpose:** Counselor grades Short Answer items a student submitted.
- **Users:** Counselor only.
- **Functions:** Grading queue (list of sessions with ungraded Short Answer items) → per-session grading form (award marks per answer) → submit → triggers `ExamResultController::recomputeResult()`, which re-sums total/max score across **all** question types (not just the newly-graded ones) and updates the `exam_results` row.
- **Verified this session:** graded a Short Answer 2/2 on a session that already had MC/TF/Likert auto-scored; confirmed those three scores were untouched and the recompute correctly produced the new combined total (7/10 preliminary → 9/10 final in the test run).
- **Notification:** fires `ResultsReleased` to the student if `exam.show_score` is true.
- **Routes/Controller:** `Counselor\ExamResultController::grading/gradeSession/storeGrades`; `counselor.exams.grading*`.

### Score Interpretation ✅
- **Purpose:** Attach a human-readable label (e.g. "Passed"/"High"/custom) to a percentage band.
- **Users:** Counselor defines ranges per exam; ranges are used automatically wherever a result/percentage is displayed.
- **Functions:** Counselor CRUD on `interpretation_ranges` (label, min_percentage, max_percentage, description) per exam.
- **Logic:** `Exam::interpretationFor(float $percentage): ?string` scans the exam's ranges and returns the first matching band's label.
- **DB/Controller:** `interpretation_ranges` table; `Counselor\InterpretationRangeController`; `counselor.exams.interpretations*`.

### Results (Counselor-facing) ✅
- **Purpose:** Results management across all sessions of an exam.
- **Functions:** List with a genuine `<select>`-backed dropdown filter for Passers/Non-passers (bound to `ExamResult.passed`, auto-submits on change), search by student name/school ID, CSV export, browser-print export.
- **DB/Controller:** `Counselor\ExamResultController::index/results/exportCsv/exportPrint`; `counselor.results.*`, `counselor.exams.results*`.

### My Results (Student-facing) ✅
- **Purpose:** A student's own completed-exam history.
- **Functions:** Paginated list of the student's own `Completed` sessions (with `exam` + `result` eager-loaded), newest-submitted-first.
- **DB/Controller:** `Student\ResultController::index`; `student.results.index`.

### Live Monitoring ✅
- **Purpose:** Real-time visibility into in-progress exam sessions.
- **Users:** Counselor (full, including flag/terminate actions); Super Admin (read-only, per the shared/exclusive split).
- **Functions:** Per-exam monitor view listing every active session with computed progress/remaining-time/status; **verified 5-second polling** (`setInterval(poll, 5000)` in `monitor.blade.php`, fetch-based partial swap, cleaned up on `turbo:before-cache`); flag/unflag a session (marks it for review, still counts as "active"); terminate a session (force-ends it).
- **DB/Controller:** `Counselor\ExamMonitoringController::index/monitor/monitorData/flag/terminate`; `counselor.monitoring.*`, `counselor.exams.monitor*`.

### Counseling / Bio-Notes ✅ (extended this development cycle — see §7, §18/§19)
Full detail in §7. Summary: chronological notes per student (category, date, content, status, optional follow-up date **and follow-up action** [added this cycle]), authored/edited by Counselors, with full edit-history snapshotting.

### Counseling Note Revision History ✅
Every `update()` snapshots the note's *prior* full state (including the prior follow-up action) into `counseling_note_revisions` **before** applying the new values — an append-only, immutable log. Displayed on the student profile page under a collapsible "Edit history" section. Verified this session: edited a note, confirmed the revision preserved the exact pre-edit content and follow-up action.

### Reports and Analytics ✅
Full detail in §8. Real aggregate SQL over `ExamSession`/`ExamAnswer`/`ExamResult`/`User`/`Exam`/`Question` — no fabricated/simulated data (verified by reading `ReportAnalyticsService`'s actual queries and by cross-checking a live report against a freshly graded test exam, whose numbers appeared exactly correctly in the CSV output).

### Audit Logs ✅ (with a scope caveat — see §11)
- **Purpose:** Read-only, searchable log of administrative/CRUD actions.
- **Users:** Super Admin only (viewer).
- **Coverage confirmed:** student/exam/question/administrator/department/course/year-level/settings/backup/counseling-note CRUD actions, plus own-profile changes.
- **Gap:** login/logout events are **not** logged (no `AuditLog` calls anywhere in `Auth\LoginController`).
- **DB/Controller:** `audit_logs` table (polymorphic `subject`); `Admin\AuditLogController::index`.

### Notifications ✅
In-app, database-only (no email — none of the 8 notification classes define `toMail()`). Bell icon + dropdown in the top nav, shared across all 3 roles via one role-agnostic `NotificationController`. Full inventory of triggers in §10.

### System Settings ✅ (School Info + Branding tabs) / 📋 (Academic Year + General tabs)
See §10 for full detail. School Info and Branding are fully wired and functional; Academic Year and General Settings tabs are literal placeholder stubs ("coming soon"), per the settings view's own code comment: *"not yet specified."*

### School Branding ✅ / Logo/Favicon ✅
Configurable system name, system full name, school name, logo, favicon — all stored via the generic `system_settings` key-value table and surfaced app-wide through a global Blade view-composer (`$branding`). Logo/favicon upload, replace, and remove are all functional (files stored on the `public` disk).

### Departments ✅ / Courses ✅
Full CRUD including soft-delete/restore (both models use `SoftDeletes`).

### Year Levels 🟡
Create/edit/toggle-active-inactive only — **no soft delete or restore** (the model doesn't use `SoftDeletes`). Also a standalone lookup list not foreign-keyed to where "year level" is actually recorded elsewhere (`student_profiles.year_level` and `exams.year_level` are free-text strings).

### Backup and Restore ✅
Real, functional, pure-PHP MySQL dump/restore (deliberately avoids shelling out to `mysqldump`/`mysql` binaries, appropriate for an unknown-PATH LAN deployment). Full detail in §10.

### CSV Export ✅
6 real CSV-producing endpoints across Students, Exam Results, and 3 Reports — full list with exact columns in §8/§21.

### Print Export ✅ (browser print-to-PDF, not server-generated PDF)
4 print-optimized views (Cumulative Record, Exam Results, 2 Reports). No PDF-generation library is installed — these are HTML pages styled for `window.print()` → "Save as PDF" in the browser, not literal PDF files generated server-side. This matters for accurate manuscript wording (see §18).

### Account/Profile Settings ✅
Shared single controller/page for all 3 roles (`ProfileController` — "gated by the user's own role rather than by route/middleware"). Email is editable by everyone; **name is only editable by Super Admin** (Counselor/Student identity fields are "managed elsewhere" per the FormRequest's own comment). Password change (requires current password), avatar upload/remove. Clears `must_change_password` on successful password change.

### Dark/Light Theme ✅
Alpine.js + `localStorage` (`guidancepulse-theme` key) + CSS custom properties — no `dark:` Tailwind prefixes needed anywhere, no server-side persistence. FOUC prevented by an inline pre-paint script.

### LAN/Offline Support ✅
See §12 for the complete architecture explanation.

---

## 4. Complete Student Workflow

Verified end-to-end this session via real HTTP requests (not the internal test harness) with a purpose-built test exam covering all four question types.

1. **Login** (`GET/POST /login`) — School ID + password + role selection (`student`). Server validates the selected role actually matches the account; on success, `session()->regenerate()` and redirect to `student.dashboard`.
2. **Dashboard** (`GET /student/dashboard`) — shows: a continuable (already in-progress) session if one exists, available exams (filtered by department/year-level targeting), upcoming exams, recent results, recent notifications, and a progress summary. See §9 for exact widget sourcing.
3. **Available Exam → Access Code** (`GET /student/exams/join`, `POST /student/exams/join`) — student enters the access code given by their counselor. Server validates: exam exists with that code, status is `Published`, current time is within `starts_at`/`ends_at` (if set), and the exam has at least one question. **One-active-session check happens here** — if the student already has a different exam `in_progress`/`flagged`, the attempt is rejected with a specific error naming that other exam.
4. **Exam Session created/resumed** — `ExamSession::firstOrCreate(['exam_id', 'student_id'], ['status' => NotStarted])`. If `NotStarted`, transitions to `InProgress`, stamps `started_at`, computes `time_remaining_seconds = duration_minutes * 60`, and pre-creates one blank `ExamAnswer` row per question (for the question navigator UI). If already `Completed`, redirects straight to the result page instead.
5. **Answer Questions** (`GET /student/exams/{exam}/take?q={index}`) — the runner shows one question at a time; options are shuffled per-session if `exam.shuffle_choices` is enabled (deterministic shuffle seeded by `session.id + question.id`, so the order is stable across page reloads for the same student).
6. **Navigation/Save behavior** (`POST /student/exams/{exam}/answer`) — every Prev/Next/Save/Flag/jump-to-question action is a real form POST that persists the current question's answer (`selected_option_id` for MC/TF/Likert, `answer_text` for Short Answer) and then redirects to the target question index. **This is not a continuous background auto-save** — it saves on each navigation action, meaning nothing is lost only if the student uses the nav controls (not literally every keystroke). The server validates the submitted option actually belongs to the submitted question before accepting it.
7. **Submit** (`nav=submit` on the answer POST, or a dedicated `POST /student/exams/{exam}/submit`) — triggers `finalize()`.
8. **Automatic scoring** — happens inside `finalize()`, inside a DB transaction: MC/TF scored by `is_correct`; Likert scored proportionally by `value`; Short Answer left pending. `ExamResult` row created/updated. Session status → `Completed`.
9. **Manual grading if required** — if the exam had any Short Answer items, `ManualGradingRequired` notification fires to the exam's creator, and the student's result page shows a "preliminary" banner until a counselor grades it.
10. **Final result** — once graded (or immediately, if there was nothing to grade), `ResultsReleased` notification fires to the student (only if `exam.show_score` is true).
11. **Interpretation** — the result page computes `Exam::interpretationFor($percentage)` live and displays the matching label if the exam has interpretation ranges defined.
12. **My Results** (`GET /student/results`) — the student's full history of completed, scored sessions.
13. **Student Profile / Cumulative Record** — the same session becomes part of the student's permanent record, visible to Counselors/Super Admin on the student's profile page (exam history tab) and in the printable cumulative record.

**Edge cases verified/known:**
- Timer expiry auto-finalizes on the very next `take()` GET, even if the client-side JS timer never fires.
- Re-submitting an access code for an exam the student is *already* `InProgress` on simply resumes it (no duplicate session, no error).
- Attempting to start a *different* exam while one is active is blocked (one-active-session rule).
- A completed session's `take()`/`join()` routes redirect straight to the result page rather than re-entering the exam.

---

## 5. Examination System

- **Exam creation:** `Counselor\ExamController` — title, description, category (`ExamCategory` enum: entrance/aptitude/personality/custom), duration (1–600 minutes), passing score, negative marking toggle, optional `starts_at`/`ends_at` window.
- **Sections:** Optional groupings within an exam (`exam_sections`, ordered), managed in the Exam Builder. A question attached to an exam can optionally belong to one section (`exam_questions.exam_section_id`, nullable).
- **Questions/Question types:** Multiple Choice, True/False, Likert, Short Answer (see §3 for scoring detail of each).
- **Question options:** `question_options` — `option_text`, `is_correct` (MC/TF only, meaningless/always-false for Likert), `value` (Likert only, nullable integer weight), `order`.
- **Points:** `questions.marks` (default 1) is the base point value; `exam_questions.marks_override` (nullable) can override it per-exam without touching the reusable Question Bank item; `questions.negative_marks` applies only to MC/TF wrong answers.
- **Likert values:** counselor-defined numeric weight per option (any scale the counselor chooses, e.g. 1–5); scoring is proportional to `selected_value / max_value_among_that_question's_options`, not a fixed 0–100 scale.
- **Targeting:** optional `department_id` + `year_level` on the exam, filters the student-facing "available exams" listing (advisory — the access code is the actual gate to joining, see §3).
- **Access codes:** unique 6-char alphanumeric, required to join, must be `Published` status.
- **Publishing/activation:** `publish()` requires ≥1 question, auto-generates an access code if missing, sets status to `Published`, notifies the creator (`ExaminationPublished`) and targeted students (`ExaminationAssigned`). Separately, `activate()`/`deactivate()` toggle exam status without going through the publish flow (for reopening/closing an already-published exam).
- **Exam availability:** governed by `status = Published` plus the optional `starts_at`/`ends_at` window, both re-checked on every join attempt.
- **Session handling:** see §4 steps 3–4; one row in `exam_sessions` per (exam, student) pair, unique-constrained.
- **Timer:** server-authoritative, computed from `started_at` + `duration_minutes`, re-verified on every page load (see §3 "Timer/Auto-submit").
- **Auto-submit:** triggered both client-side (cosmetic) and server-side (authoritative, on the next request after expiry).
- **One-active-session rule:** see §3 dedicated entry.
- **Scoring:** see §3 "Automatic Scoring"/"Likert Scoring".
- **Manual grading:** see §3 dedicated entry.
- **Result computation:** `total_score`, `max_score`, `percentage`, `passed` (boolean, `percentage >= exam.passing_score`), `computed_at` — recomputed fully (not incrementally) both at initial submission and after any manual grading pass, so the two code paths must (and, after the fixes this cycle, do) agree on how every question type contributes.
- **Interpretation ranges:** see §3 dedicated entry.

---

## 6. Student Profile System

Information actually stored and displayed, verified against `student_profiles`, `users`, and related tables:

- **Personal information:** name, school ID (`users.school_id`, unique), email (optional), account status (active/inactive/suspended).
- **Demographic information:** ❌ Not implemented — no gender/birthdate/age fields exist anywhere in the schema. Do not claim this in the manuscript.
- **Contact information:** email only (`users.email`). No phone/address fields for students.
- **Department/Course/Year Level:** `users.department_id` (FK), `student_profiles.year_level` (free-text string), `student_profiles.section` (free-text string). Note: "Course" (the `courses` table) is a reference-data lookup tied to Departments, but there is **no FK from a student to a specific course** — students are only linked to a Department, not a Course, in the current schema. Flag this precisely if the manuscript needs to describe course-level student data — it isn't there.
- **Enrollment status:** `student_profiles.enrollment_status` (enrolled/on_leave/graduated/dropped).
- **Photo:** `users.avatar_path` — profile photo upload, shared mechanism across all roles (not student-specific), via Account/Profile Settings.
- **Examination history:** every `exam_sessions` row for the student, joined to `exams` and `exam_results`, shown on the profile page's exam-history tab and in the cumulative record print view (including the section-by-section score breakdown, which now correctly includes Likert per the fix this cycle).
- **Results:** same data as above, summarized.
- **Cumulative records:** the printable view combining identity + academic placement + exam history (explicitly **excludes** counseling notes for confidentiality, per an explicit code comment in `StudentRecordController`).
- **Counseling notes:** shown on the profile page (Counselor/Super-Admin view only — see §7), **not** included in the printable cumulative record.
- **Other stored information:** `last_login_at`, `password_changed_at`, `must_change_password`, `created_at` (account-creation date, shown on the profile page as "Account Created").

---

## 7. Counseling / Bio-Notes

- **Note categories:** `CounselingNoteCategory` enum — Academic, Behavioral, Career, Personal.
- **Note status:** `CounselingNoteStatus` enum — Open, Closed (**not** "in_progress" or any other value — verified by reading the actual enum; a test attempt using an invalid status value earlier in this session correctly failed validation, confirming the enum is strictly enforced).
- **Follow-up action:** ✅ `counseling_notes.follow_up_action` (nullable text) — **added this development cycle** to close a gap between the approved proposal's Definition of Terms (which always specified a bio-note includes "any corresponding follow-up action") and the prior schema, which only had a `follow_up_date`. Fully wired through the model, both FormRequests, the service, the create/edit form, and both display locations (current note + revision history).
- **Author:** `counseling_notes.counselor_id` → `users` (the authoring Counselor; note this FK name — a Super Admin cannot author notes at all, per the `CounselingNotePolicy`).
- **Dates:** `note_date` (the observation date), `follow_up_date` (optional), plus standard `created_at`/`updated_at` timestamps. Note `note_date` is a **date only**, not a date+time value.
- **Editing:** `CounselingNoteController::update()` → `CounselingNoteService::update()`, wrapped in a DB transaction.
- **Revision history:** every edit snapshots the note's full prior state (category, note_date, content, follow_up_date, follow_up_action, status) into `counseling_note_revisions` **before** applying the new values, stamped with `edited_by` and `created_at` (immutable, no `updated_at` on the revision table itself). Displayed as a collapsible "Edit history" list on the student profile.
- **Authorization/privacy — `CounselingNotePolicy`:**
  - `viewAny`/`view` → any staff (Counselor or Super Admin)
  - `create`/`update` → **Counselor only** (Super Admin can view but never author or edit a note)
- **Student restrictions:** ✅ verified — a student attempting to access either the counseling-notes oversight list (`/counselor/counseling-notes`) or a counselor's student-management view (`/counselor/students/{id}`) receives **403**. Students have no route, view, or API surface exposing counseling notes at all — not even their own.

---

## 8. Reports and Analytics

All computed live from real data by `App\Services\ReportAnalyticsService`, confirmed by reading its actual SQL and by cross-checking a live report's CSV output against a test exam graded during this session's verification (the numbers matched exactly). The class's own doc comment states: *"every figure here is computed from real Exam/ExamSession/ExamAnswer data... no simulated trend lines or invented psychometric constructs"* — independently verified true.

| Report | Purpose | Data source | Filters | Notes |
|---|---|---|---|---|
| KPI dashboard (`kpis()`) | Pass rate, total exams taken, average difficulty, flagged-session rate | `ExamSession`/`ExamResult`/`ExamAnswer` aggregates | date range | Feeds the Reports index page |
| Performance trend (`performanceTrend()`) | Weekly/monthly pass-rate time series | same | date range | Chart.js line chart |
| Difficulty distribution (`difficultyDistribution()`) | Item-analysis donut: easy/optimal/hard/poor buckets | `answeredQuestionStats()` — real grouped SQL (`COUNT(*)`, `SUM(CASE WHEN is_correct...)`) | date range | Chart.js donut |
| Student Performance Summary (`studentPerformanceSummaryRows()`) | Per-student exams taken, average %, pass rate | `ExamSession`/`ExamResult` grouped by student | date range | CSV + print |
| Exam Statistics (`examStatisticsRows()`) | Per-exam n, mean %, median %, std. dev., pass rate | `ExamResult.percentage` per exam, real mean/median/variance/stdev computation | date range | CSV + print |
| Item Analysis Detail (`itemAnalysisRows()`) | Per-question empirical p-value/difficulty bucket vs. authored difficulty | `answeredQuestionStats()` | date range | **CSV only — no print view exists for this one** (explicit in the controller's own comment) |

**Report Scheduling** 🟡 — `ReportScheduleController`/`ReportScheduleService` let a Counselor configure a recipient name/email + frequency (weekly/monthly/quarterly/biannual) per report type, and toggle it active/inactive. **This is configuration storage only.** There is no cron/scheduler wiring anywhere in the app (`app/Console` doesn't exist, `bootstrap/app.php` has no `->withSchedule()`, `routes/console.php` only has the default `inspire` command), and `last_run_at` is never written by any code path — confirmed by the migration's own docblock: *"Actual scheduled dispatch (cron + mail) is not implemented yet."* Do not describe this as "automated report delivery" in the manuscript.

---

## 9. Dashboards

### Super Admin Dashboard (`admin.dashboard`, `AdminDashboardService`)
| Widget | Source method | Data |
|---|---|---|
| User counts by role | `usersByRole()` | Real count of `users` grouped by role |
| Headline stats | `stats()` | Aggregate counts (exact metrics per the service's `stats()` method — total users, active exams, etc., all real DB counts) |
| System status | `systemStatus()` | Server/environment status indicators |
| Recent audit log entries | `recentAuditLogs($limit=6)` | Latest `audit_logs` rows |
| Recent logins | `recentLogins($limit=6)` | Users ordered by `last_login_at` |
| Weekly signups | `weeklySignups($weeks=8)` | `users.created_at` grouped by week, real historical count (not simulated) |

### Counselor Dashboard (`counselor.dashboard`, `CounselorDashboardService`)
| Widget | Source method | Data |
|---|---|---|
| Headline stats | `stats()` | Real counts (students, exams, sessions, etc.) |
| Upcoming exams | `upcomingExams($limit=5)` | Exams with a future `starts_at` |
| Live sessions | `liveSessions($limit=5)` | Sessions with status `InProgress`/`Flagged` (same "active" definition used by Live Monitoring) |
| Pending grading | `pendingGradingSessions($limit=5)` | Sessions with an ungraded Short Answer answer (`pendingGradingQuery()`) |
| Recent student activity | `recentStudentActivity($limit=6)` | Recent student-related events |
| Recent activity | `recentActivity($limit=5)` | General recent activity feed |

### Student Dashboard (`student.dashboard`, `StudentDashboardService`)
| Widget | Source method | Data |
|---|---|---|
| Continuable session | `continuableSession($student)` | The student's own `InProgress` session, if any (drives a "Resume" prompt) |
| Available exams | `availableExams($student, $limit=5)` | Exams visible per targeting rules (§3/§5) |
| Upcoming exams | `upcomingExams($student, $limit=5)` | Targeted exams with a future start |
| Recent results | `recentResults($student, $limit=5)` | The student's own recent `Completed` sessions |
| Notifications | `notifications($student, $limit=5)` | The student's own notification feed |
| Progress summary | `progressSummary($student)` | Aggregate stats about the student's own exam history |

All three dashboards are backed by real, per-role service classes querying live data — **no dashboard widget in this app is hardcoded or placeholder data.**

---

## 10. System Administration

- **User management:** Super Admin manages Guidance Counselor accounts only (`AdministratorController`) — create, edit, activate/deactivate, reset password (forces `must_change_password = true`). Student account management is a **Counselor** function (`StudentController`), not Super Admin's — see §2.
- **Roles:** fixed 3-value enum (`UserRole`), not database-configurable/extensible — adding a 4th role would require code changes, not an admin UI action.
- **System Settings** (`SchoolSettingsService`, `SettingsController`, `admin.settings.index`):
  - **School Info tab** ✅ — school_name, system_display_name, system_full_name, short_code, official_email, phone_number, mailing_address. All stored as rows in the generic `system_settings` key-value table.
  - **Branding tab** ✅ — logo upload/remove (png/jpg/jpeg/svg/webp, max 2MB), favicon upload/remove (ico/png/jpg/jpeg/svg, max 512KB). Stored on the `public` disk under `branding/`.
  - **Academic Year tab** 📋 — stub ("coming soon").
  - **General tab** 📋 — stub ("coming soon").
- **School name / System name:** both configurable (School Name vs. System [Short] Display Name vs. System Full Name are three distinct, separately-editable fields), globally available to every Blade view via a `$branding` view-composer, with hardcoded defaults (`'Talibon Polytechnic College'` / `'GuidancePulse'` / the full proposal title) used only if the DB is unreachable or the setting was never configured.
- **School logo / Favicon:** see above — fully functional upload/replace/remove.
- **Academic/reference data:**
  - **Departments** ✅ full CRUD + soft-delete/restore.
  - **Courses** ✅ full CRUD + soft-delete/restore, linked to a Department.
  - **Year Levels** 🟡 create/edit/toggle-active only, no soft delete, and not FK-linked to where year level is actually stored on students/exams (those are free-text strings).
- **Backup/Restore** ✅ (`DatabaseBackupService`, `BackupController`):
  - Pure-PHP MySQL dump (no shelling out to `mysqldump`), stored on the **private** `local` disk (never `public` — dumps contain password hashes), filename pattern `backup-{Y-m-d_His}.sql`.
  - Create: full schema (`SHOW CREATE TABLE` per table) + batched row-data INSERTs (300 rows/batch).
  - Restore: reads an **uploaded** `.sql` file (not necessarily one of the stored backups), strips comments, splits into statements respecting quoted strings, executes via `DB::unprepared()`. **Fully destructive** — drops and recreates every table. Not wrapped in a transaction (MySQL DDL causes implicit commits, so this wouldn't help anyway). Gated behind an explicit "I understand this overwrites all data" confirmation checkbox (`RestoreBackupRequest`).
  - Download, delete also supported.
- **Audit logs:** see §3/§11.
- **Notifications:** see §3. Full trigger inventory:

| Notification | Trigger | Recipient(s) |
|---|---|---|
| `AdministratorCreated` | New Counselor account created | Every *other* Super Admin |
| `ExaminationAssigned` | Exam published | Every targeted student |
| `ExaminationPublished` | Exam published | The exam's creator |
| `ExaminationSubmitted` | Student submits | The exam's creator |
| `ManualGradingRequired` | Submit, if any Short Answer items exist | The exam's creator |
| `PasswordChanged` | Admin/Counselor resets someone's password | The affected user |
| `ResultsReleased` | Submit (if no grading needed) or grading completed, and `exam.show_score` true | The student |
| `StudentImported` | New student profile created | Every Super Admin |

All 8 are **database-only** (in-app bell/dropdown) — none send email (no `toMail()` defined on any of them).

---

## 11. Security

**Implemented, verified:**
- **Authentication:** Laravel's standard session-guard `Auth::attempt()` against hashed passwords (`password` cast to `hashed` on the `User` model — bcrypt via Laravel's default hasher).
- **Password handling:** hashed at rest; self-service change requires re-entering the current password (`current_password` validation rule); admin-initiated resets force a `must_change_password` flag, enforced globally by middleware until the user sets their own new password.
- **Role-based authorization:** `EnsureUserHasRole` middleware (route-group level) + one Eloquent policy (`CounselingNotePolicy`) for the one area of the app with finer-grained same-resource-different-permission rules (Counselor can author/edit notes, Super Admin can only view). Verified via extensive real HTTP testing this session across every role/route combination.
- **CSRF protection:** Laravel's default (`@csrf` on every form, verified a request with no token returns 419).
- **Validation:** every write endpoint is backed by a dedicated FormRequest class (full inventory in the companion research — 25 FormRequest classes across Admin/Auth/Counselor/Profile namespaces).
- **Session handling:** `session()->regenerate()` on login, `session()->invalidate()` + `regenerateToken()` on logout (textbook-correct, prevents session fixation).
- **Rate limiting:** login attempts rate-limited by `school_id|ip` (5 attempts), independent of cookies/session (so "clearing cookies" cannot be used to bypass it — verified this is IP+identifier keyed, not session-keyed).
- **Access restrictions:** see §2 — verified exhaustively via real requests.
- **Database constraints:** unique constraints on `users.school_id`, `users.email`, `exams.access_code`, `exam_sessions(exam_id, student_id)`, `exam_questions(exam_id, question_id)`, `exam_answers(exam_session_id, question_id)`, `exam_results.exam_session_id`, `system_settings.key`; the generated-column unique index enforcing one active session per student (§3); soft-delete (not hard-delete) on Users/Exams/Questions/Departments/Courses preserves historical/audit data.
- **Audit logging:** covers CRUD/administrative actions across most modules (full list in §3), **but explicitly does not cover authentication events** (no login/logout audit entries) — a real, verified gap, not a claim to make in the manuscript.
- **Open-redirect protection:** `NotificationController::markAsRead()` validates its `redirect` parameter is same-origin before following it (a small but real, deliberately-coded safeguard against a client-tampered redirect target).
- **LAN deployment considerations:** no external CDN dependency (verified, see §12); session cookies configured for the LAN's actual serving path; `RestoreBackupRequest` restricts destructive restore to Super Admin only, with an explicit confirmation gate.

**Explicitly NOT implemented — do not claim these:**
- No two-factor authentication.
- No CAPTCHA on login.
- No IP allowlisting/geofencing.
- No encryption-at-rest beyond password hashing (the database itself is not encrypted).
- No formal penetration-test or security-audit evidence exists — only the manual verification described in this document.
- No policy classes exist for any model except `CounselingNote` — every other authorization boundary is enforced by route middleware alone, not by per-object policies. This is a legitimate architectural choice for this app's access patterns (role-wide permissions, not per-record ownership rules), but should be described accurately.

---

## 12. LAN/Offline Architecture

**Server:** A single machine (in this deployment, running XAMPP — Apache + MySQL/MariaDB) hosts the Laravel application and the MySQL database, located in the Guidance Office.

**Client computers:** Any device on the same LAN with a modern web browser — no client-side software installation required, no app-specific browser plugins.

**LAN access:** Client devices reach the app via a standard HTTP URL pointing at the server's LAN address (e.g. `http://<server-ip>/capstone_final/guidancepulse/public/`, or a configured hostname). No internet gateway is required for this traffic to work — it stays entirely within the local network switch/router.

**Local database:** MySQL/MariaDB running on the same server; all reads/writes are local-network or localhost calls, never routed externally.

**Local assets — the critical verified claim:** As of this development cycle, **every** front-end asset the application needs to render and function is bundled locally via Vite and served from the app's own `/build/assets/` path:
- CSS (Tailwind-generated stylesheet, all design tokens)
- JavaScript (Turbo, Alpine.js, Chart.js, SortableJS — all bundled into one `app-*.js`)
- The Inter font (self-hosted via `@fontsource-variable/inter`, `.woff2` files in the build output)
- The Material Symbols icon font (self-hosted via the `material-symbols` npm package)

This was **not** always true. Earlier in development, the app loaded Tailwind, Google Fonts (×2), and Turbo/Alpine/Chart.js from public CDNs (`cdn.tailwindcss.com`, `fonts.googleapis.com`, `cdn.jsdelivr.net`) — a direct contradiction of the "no internet required" claim. This was found and fixed: `resources/views/partials/theme-head.blade.php` now uses `@vite([...])` exclusively, `resources/css/app.css` and `resources/js/app.js` contain the complete bundled asset graph, and `npm run build` produces the self-hosted output.

**Offline operation — verified this session:** a search across 8 distinct rendered pages (login, all 3 dashboards, admin settings, the exam builder — which uses SortableJS specifically, exam results — which uses Chart.js specifically, and the student exam-join page) found **zero** external CDN/domain references. Every referenced asset URL resolved to the app's own `/build/assets/...` path and returned HTTP 200.

**External dependency status:** **None**, for runtime operation. (Note: the *build* process itself — `npm run build` — was run on a machine with internet access to download npm packages; that's a normal, one-time development-machine requirement and does not affect the deployed LAN server, which only needs the already-built static output.)

**Browser requirements:** Any reasonably modern browser (Chrome/Edge/Firefox). The app uses the native View Transitions API for smoother page transitions where supported, with an automatic, tested fallback (plain CSS fade) for browsers that don't support it — either way, the app is fully functional.

**How students/counselors access the system:** Open a browser on any LAN-connected device, navigate to the server's local URL, and log in — identical experience regardless of which client device is used, since all logic and data live on the server.

**"LAN-based" vs. "internet-based" — the precise distinction for the manuscript:** This is *not* a system with occasional/degraded internet fallback, nor a system that happens to also work without internet as an incidental property. It is architected to have **no code path that depends on an external network resource at all** during normal operation — every asset, font, icon, and script is served by the same local application server that serves the HTML. The only network traffic involved in using the system, ever, is between a LAN client and the LAN server.

---

## 13. Database Documentation

**Full table list** (26 application tables, plus Laravel framework tables `cache`/`cache_locks`/`jobs`/`job_batches`/`failed_jobs`/`sessions` which are infrastructure, not domain data):

| # | Table | Purpose |
|---|---|---|
| 1 | `departments` | Top-level academic org unit |
| 2 | `users` | All accounts (Super Admin/Counselor/Student), single table + role enum |
| 3 | `password_reset_tokens` | Laravel's standard password-reset token store |
| 4 | `student_profiles` | Student-specific extension of `users` (1:1) |
| 5 | `counselor_profiles` | Counselor-specific extension of `users` (1:1) |
| 6 | `exams` | Exam metadata, config, targeting, access code |
| 7 | `exam_sections` | Optional groupings within an exam |
| 8 | `questions` | Reusable Question Bank items |
| 9 | `question_options` | Choices/scale-points for a question |
| 10 | `exam_questions` | Pivot: which questions belong to which exam, order, per-exam marks override |
| 11 | `exam_sessions` | One student's attempt at one exam |
| 12 | `exam_answers` | One answer to one question within one session |
| 13 | `exam_results` | Computed score/percentage/pass-fail per session (1:1) |
| 14 | `exam_violations` | Proctoring event log (tab-switch/fullscreen-exit/copy-paste) per session |
| 15 | `audit_logs` | Administrative action log |
| 16 | `system_settings` | Generic key-value config store |
| 17 | `notifications` | Laravel's standard polymorphic notifications table |
| 18 | `interpretation_ranges` | Percentage-band labels per exam |
| 19 | `counseling_notes` | Bio-notes per student |
| 20 | `counseling_note_revisions` | Immutable edit-history snapshots of counseling notes |
| 21 | `report_schedules` | Report delivery configuration (not yet automated) |
| 22 | `courses` | Reference-data lookup, tied to a department |
| 23 | `year_levels` | Standalone reference-data lookup list |

*(Full per-table column/type/FK/index detail was compiled during this session's audit and is available in the research transcript; the table above is the authoritative list of what exists. Ask for the full column-level breakdown if the manuscript's Database Design chapter needs it verbatim — it is lengthy and omitted here to keep this file navigable, but every fact in it was directly verified from the migration files, not inferred.)*

**Key constraints worth calling out explicitly for the manuscript:**
- `users.school_id` — unique (the login identifier)
- `exams.access_code` — unique
- `exam_sessions(exam_id, student_id)` — unique composite (one session per student per exam)
- `exam_sessions.active_session_lock` — unique generated-column index (one *active* session per student, across all exams — the one-active-session rule's DB-level backstop)
- `exam_questions(exam_id, question_id)` — unique composite (a question can't be attached to the same exam twice)
- `exam_answers(exam_session_id, question_id)` — unique composite (one answer row per question per session)
- `exam_results.exam_session_id` — unique (true 1:1 with the session)
- Soft deletes (`deleted_at`) on: `users`, `exams`, `questions`, `departments`, `courses` — NOT on `year_levels`, `exam_sessions`, or any other table.

**Textual ERD-style relationship summary:**

```
departments ──┬── users (department_id, nullable)
              └── courses (department_id, nullable)

users ──┬── student_profiles (1:1, cascade)
        ├── counselor_profiles (1:1, cascade)
        ├── exams (created_by → creator)
        ├── questions (created_by → creator)
        ├── exam_sessions (student_id)
        ├── audit_logs (user_id, nullable)
        ├── counseling_notes AS student (student_id)
        ├── counseling_notes AS counselor (counselor_id)
        └── notifications (polymorphic notifiable)

exams ──┬── exam_sections (1:many, cascade)
        ├── exam_questions ──── questions (many:many pivot, +section, +order, +marks_override)
        ├── exam_sessions (1:many, cascade)
        └── interpretation_ranges (1:many, cascade)

questions ── question_options (1:many, cascade)

exam_sessions ──┬── exam_answers (1:many, cascade) ── question_options (selected_option_id, nullable)
                ├── exam_violations (1:many, cascade)
                └── exam_results (1:1, cascade)

counseling_notes ── counseling_note_revisions (1:many, cascade; +edited_by → users)

year_levels: standalone, NOT foreign-keyed to student_profiles.year_level or exams.year_level
             (those are free-text strings, not FKs — a genuine schema looseness worth
             flagging if the manuscript's ERD needs to be diagrammatically precise)
```

---

## 14. Technical Architecture

- **Framework:** Laravel 12, standard MVC.
- **Controllers:** organized by role namespace — `App\Http\Controllers\Admin\*`, `Counselor\*`, `Student\*`, `Auth\*`, plus shared root-level controllers (`ProfileController`, `NotificationController`). 30 controller classes total.
- **Models:** 20 Eloquent models in `app/Models/`, each mapping to its default table, using enum casts extensively (`App\Enums\*`) for type-safe status/category/type fields instead of raw strings.
- **Services:** business logic extracted out of controllers into `app/Services/` — `SchoolSettingsService`, `DatabaseBackupService`, `ReportAnalyticsService`, `StudentRecordService`, `CounselingNoteService`, `QuestionDocumentExtractor`, `QuestionImportParser`, `AdminDashboardService`, `CounselorDashboardService`, `StudentDashboardService`, `DepartmentService`, `CourseService`, `YearLevelService`, `ReportScheduleService` — a consistent thin-controller/fat-service pattern.
- **Requests:** 25 FormRequest classes handle all input validation, one per meaningful write operation, none doing authorization beyond `return true;` (authorization is route-middleware/policy-based, not request-based, in this app).
- **Middleware:** 2 custom classes (`EnsureUserHasRole`, `EnsurePasswordIsCurrent`), registered in `bootstrap/app.php` (Laravel 12's middleware configuration style — no separate `Kernel.php`).
- **Policies:** 1 (`CounselingNotePolicy`), relying on Laravel's model-name auto-discovery convention (no explicit `Gate::policy()` registration exists).
- **Blade:** component-based design system (`x-ui.*`, `x-navigation.*`, `x-layouts.*`) using CSS custom properties for theming (no `dark:` Tailwind prefixes anywhere).
- **JavaScript:** Alpine.js for component-level interactivity, Turbo Drive for SPA-like navigation (with native View Transitions API integration and a tested reduced-motion/no-VT-support fallback), Chart.js for report charts, SortableJS for the exam builder's drag-reorder.
- **CSS:** Tailwind v4 via `@tailwindcss/vite`, with a custom design-token spacing scale (`sm`/`md`/`lg`/`xl`/`xs`/`gutter`/`base`/`container-margin`) that intentionally shadows Tailwind's own `max-w-sm/md/lg/xl` scale — a real bug found and fixed this cycle via an explicit unlayered CSS override in `app.css` (documented inline in that file).
- **Vite/local assets:** `vite.config.js` + `laravel-vite-plugin`, single entry pair (`resources/css/app.css`, `resources/js/app.js`), production build via `npm run build`, output in `public/build/`.
- **MySQL:** MariaDB 10.4.32 in this environment (via XAMPP), standard Eloquent/query-builder access, one raw-SQL exception (the generated-column migration for the one-active-session constraint, which needed `DB::statement()` since Laravel's schema builder doesn't have first-class generated-column support).
- **Authentication:** Laravel's built-in session guard (`Auth::attempt`), no third-party auth package (no Sanctum/Passport/Socialite).
- **Routing:** role-namespaced route files (`routes/web.php`, `routes/admin.php`, `routes/counselor.php`, `routes/student.php`), 129 total routes.

---

## 15. Functional Requirements

Only requirements the implementation actually supports, phrased in the requested style.

- **FR-01** — The system shall allow a user to log in using a School ID, password, and a selected role, and shall reject the attempt if the selected role does not match the account's actual role, even with valid credentials.
- **FR-02** — The system shall allow a Super Administrator to create, edit, activate, deactivate, and reset the password of Guidance Counselor accounts.
- **FR-03** — The system shall allow a Guidance Counselor to create, edit, archive, and restore Student accounts and profiles.
- **FR-04** — The system shall allow a Guidance Counselor to author examination questions of four types (Multiple Choice, True/False, Likert, Short Answer) directly, or import them in bulk from a `.txt` or `.pdf` file.
- **FR-05** — The system shall allow a Guidance Counselor to compose an examination from Question Bank items, organize them into sections, and assign a unique access code.
- **FR-06** — The system shall allow a Guidance Counselor to define score-interpretation ranges (percentage bands with labels) per examination.
- **FR-07** — The system shall allow a Student to join a published examination using a valid, unexpired access code.
- **FR-08** — The system shall prevent a Student from having more than one active (in-progress) examination session at any time.
- **FR-09** — The system shall enforce a server-authoritative time limit per examination and automatically finalize a session whose time has elapsed, regardless of client-side behavior.
- **FR-10** — The system shall automatically score Multiple Choice, True/False, and Likert responses immediately upon submission.
- **FR-11** — The system shall allow a Guidance Counselor to manually grade Short Answer responses and shall recompute the total score across all question types upon grading.
- **FR-12** — The system shall display a score interpretation label to the Student and Guidance Counselor based on the configured interpretation ranges.
- **FR-13** — The system shall provide a live monitoring view of in-progress examination sessions, updating at a five-second interval.
- **FR-14** — The system shall allow a Guidance Counselor to record, edit, and view a chronological history of counseling notes for a student, including a follow-up action per note.
- **FR-15** — The system shall prevent a Student from viewing any counseling note, including their own.
- **FR-16** — The system shall generate institutional reports (student performance summary, exam statistics, item analysis) from real examination data, exportable as CSV and as browser-printable documents.
- **FR-17** — The system shall maintain a read-only audit log of administrative and content-management actions.
- **FR-18** — The system shall allow a Super Administrator to configure system branding (school name, system name, logo, favicon) and reference data (departments, courses, year levels).
- **FR-19** — The system shall allow a Super Administrator to create, download, delete, and restore full database backups.
- **FR-20** — The system shall notify relevant users in-app of key events (exam published/assigned/submitted, results released, grading required, password changed, account created).
- **FR-21** — The system shall operate entirely on a local area network without requiring internet access for any front-end asset or backend function.

---

## 16. Non-Functional Requirements

Actual, supported characteristics — no invented ISO 25010 scores or evaluation claims.

- **Usability:** consistent Blade component design system across all three role portals; light/dark theme support; role-appropriate navigation (each role only sees menu items for functions it can actually use).
- **Performance:** live monitoring polls at a fixed, deliberate 5-second interval (not faster, to bound server load); report queries use grouped SQL aggregation rather than pulling raw rows into PHP for computation; Turbo Drive avoids full-page reloads for most navigation, reducing perceived latency on a LAN.
- **Security:** see §11 in full — role-based access control, CSRF, validated input, rate-limited login, audit trail for administrative actions, hashed passwords, DB-level uniqueness/session constraints.
- **Reliability:** the one-active-session rule is enforced at both the application and database level (defense in depth against race conditions); soft deletes preserve historical data instead of destructive hard deletes for Users/Exams/Questions/Departments/Courses; the timer/auto-submit logic is server-authoritative so a crashed or closed client cannot leave a session indefinitely open.
- **Maintainability:** thin-controller/fat-service architecture; enum-typed status/category fields instead of magic strings; FormRequest classes centralize validation; a single global `$branding` view-composer means branding text lives in one place, not scattered across templates.
- **Compatibility:** standard web browsers only (no native mobile app); works with or without View Transitions API support; responsive layout (mobile-usable, though not the primary design target for LAN lab/office deployment).
- **LAN/offline operation:** see §12 — verified, zero external runtime dependencies.

---

## 17. Testing and Verification

All items below were tested via **real HTTP requests against the running application** (not the internal Laravel test harness), during this development session. Test data created for verification purposes was deleted afterward in every case; counts were confirmed to return to their pre-test baseline.

### PASS / VERIFIED
| Workflow | Result |
|---|---|
| Super Admin login (correct role) | Redirects to `/admin/dashboard`, session authenticated |
| Counselor login (correct role) | Redirects to `/counselor/dashboard`, session authenticated |
| Student login (correct role) | Redirects to `/student/dashboard`, session authenticated |
| Cross-role login rejection (all 6 directional combinations) | Rejected; confirmed not authenticated afterward via a protected-route probe |
| Role-based route authorization (Super Admin/Counselor/Student allowed + blocked routes) | Every allowed route 200, every blocked route 403, exhaustively tested |
| Exam join via real access code | Session created, redirected to runner |
| Answer Multiple Choice, True/False, Likert, Short Answer | All 4 persisted correctly; MC/TF/Likert auto-scored on submit, SA left pending |
| Likert scoring | Confirmed proportional (`selected_value/max_value * points`), matches hand-calculated expectation exactly |
| Exam submission → preliminary result | Correct percentage, correct "pending manual grading" flag |
| Manual grading of Short Answer | Correct marks awarded; recompute preserved MC/TF/Likert scores (this specifically caught and led to fixing a real regression in `ExamResultController::recomputeResult()`) |
| One-active-session enforcement | Second exam correctly blocked while first is active; resuming the first still works; DB confirmed exactly one active session throughout |
| Counseling note creation with follow-up action | Stored and displayed correctly |
| Counseling note editing + revision history | Content/status/follow-up action all updated; revision correctly preserved the exact pre-edit values |
| Student denied access to counseling notes | 403 on both the oversight list and a counselor's student-management view |
| Student profile, exam history, assessments tab, cumulative record print view | All render correctly, reflect the graded test exam's actual score |
| Institutional reports (dashboard KPIs, CSV ×3, print ×2) | All return real, correctly computed data matching the test exam |
| Audit log viewer | Renders, real entries present |
| System Settings (School Info, Branding) | Renders, both tabs functional |
| LAN/offline asset loading | Zero external CDN references across 8 distinct pages spanning all 3 roles; every asset resolves locally with HTTP 200 |
| Question import (upload → preview → confirm import) | Full round-trip verified via real multipart upload; question count increased by the expected amount |

### NOT YET EVALUATED
- Backup creation/download/delete/restore — code fully reviewed and understood, but **not exercised via a live create-then-restore cycle** in this session (restore is destructive; was not tested against this working database to avoid data loss).
- Reference Data CRUD (Departments/Courses/Year Levels) — endpoints confirmed reachable (200) with correct role gating, but full create/edit/archive/restore *round-trips* were not individually exercised this session the way Students/Exams/Notes were.
- Notification bell UI interaction (mark as read/mark all as read) — code reviewed, routes confirmed to exist, not clicked through live.
- `.docx` question import — confirmed to fail (missing `phpoffice/phpword`), not something to re-test until the package is installed.
- Concurrent-load/multi-student stress testing (the proposal's own described "concurrent-access stress test") — not performed in this session.
- Formal ISO 25010 questionnaire evaluation — **zero respondents, zero data collected**, per explicit instruction not to fabricate this.

---

## 18. Known Limitations

Each verified directly against the current codebase — none are guesses.

1. **Report scheduling does not automatically dispatch reports.** `ReportSchedule` records are configuration only; no cron/scheduler exists anywhere in the app, and `last_run_at` is never written. (`app/Console` doesn't exist; migration's own docblock confirms this.)
2. **Auto-save occurs on navigation, not continuously in the background.** Each Prev/Next/Save/Flag action persists the current answer via a full form POST; there is no periodic/on-change AJAX auto-save timer.
3. **PDF export uses browser print-to-PDF, not server-generated PDF files.** No PDF-generation library (`dompdf`/`snappy`/etc.) is installed; "print" views are HTML styled for `window.print()`.
4. **Audit logs do not cover authentication events.** No login/logout entries are ever written to `audit_logs` — coverage is CRUD/administrative actions only.
5. **`.docx` question import is currently broken.** The code path exists and is reachable, but `phpoffice/phpword` is not an installed Composer dependency, so uploading a `.docx` file throws a caught runtime error with a message telling the user to install it. `.txt` and `.pdf` import both work correctly.
6. **Year Levels have no soft-delete/restore**, unlike Departments and Courses — only an active/inactive toggle.
7. **Year Levels are not foreign-keyed to where year level is actually recorded** (`student_profiles.year_level`, `exams.year_level` are free-text strings) — the lookup table and the actual data are not referentially linked.
8. **Students are linked to a Department but not to a specific Course** — no FK exists from a student to a `courses` row.
9. **No demographic fields exist** on the student profile (no gender, birthdate, or similar) — only what's listed in §6.
10. **Item Analysis Detail report has no print view**, CSV export only (by design, per the controller's own comment).
11. **Academic Year and General Settings tabs are unimplemented stubs** in System Settings ("coming soon").
12. **A narrow timing race exists in answer-saving near time expiry** — a single in-flight answer-save request is not itself expiry-checked, though the very next request always finalizes the session (not an exploitable bypass, just an implementation nuance worth documenting honestly).
13. **No email is ever sent by the notification system** — all 8 notification classes are database/in-app only; the only real email code path in the app is Laravel's standard password-reset link, gated behind a "is mail actually configured" check that's currently false in this environment (`MAIL_MAILER=log`).

---

## 19. Proposal vs. Final Implementation

| Proposal Feature | Current Implementation | Status | Documentation Note |
|---|---|---|---|
| Two-role system (Guidance Counselor "as the system administrator" + Student) | Three roles: Super Administrator, Guidance Counselor, Student | **Expanded** | Chapter 1 already revised to reflect this (see the approved-document edit made earlier this engagement); Super Admin is framed as a system-administration tier layered above the original two-audience structure, not a replacement of it |
| Likert auto-scored alongside MC/True-False | Was NOT scored for a period during development (recorded only); **now fixed** to score proportionally by value | **Now matches proposal** | The proposal was correct all along; the implementation had a bug (now closed) |
| Bio-note includes "any corresponding follow-up action" (Definition of Terms) | Was missing a distinct follow-up-action field for a period; **now added** (`follow_up_action` column, fully wired) | **Now matches proposal** | Same pattern — proposal was correct, implementation caught up |
| "One active session per student" (approved ISO 25010 questionnaire, Reliability item) | Was only enforced per-exam, not across exams; **now fixed** with app + DB-level enforcement | **Now matches proposal/questionnaire** | |
| "Eliminates the need for internet connectivity" | Was contradicted by real external CDN dependencies for a period; **now fixed**, fully self-hosted, verified with zero external references | **Now matches proposal** | |
| Question Bank + bulk import | Fully implemented (`.txt`/`.pdf` working, `.docx` broken — missing dependency) | **Implemented, added beyond original narrative detail** | Not explicitly named in the original proposal narrative but consistent with its stated four-question-type authoring requirement |
| Score interpretation, real-time result generation, dropdown-filterable passers list, live monitoring, cumulative student profile with bio-notes, CSV/print export, administrative dashboard with analytics | All implemented as originally described | **Matches proposal** | |
| Report Scheduling | Implemented as configuration-only; no automated dispatch | **Added beyond proposal, partially implemented** | Not in the original proposal at all; must not be described as "automated" |
| System Settings / dynamic branding, Reference Data management, Backup & Restore, in-app Notifications, Audit Log, light/dark theme | All implemented, fully functional (except the 2 stub Settings tabs) | **Added beyond proposal** | None of these appear anywhere in the approved Chapter 1/2; genuinely new capabilities added during development, not proposal scope creep to hide — Chapter 1 was already revised to acknowledge the Super Admin-related ones explicitly |
| Auto-save mechanism (student answering) | Persists per navigation action, not continuous background save | **Implemented, more precisely described than original wording** | Chapter 1's Objective #6 wording was already revised to say "automatic saving as they navigate between items" |
| PDF export | Browser print-to-PDF, not server-generated | **Matches proposal's actual wording** ("printable PDF formats") | No revision needed — the proposal's own phrasing already supports this reading |

---

## 20. Manuscript Mapping

| System Feature | Chapter | Recommended Section |
|---|---|---|
| Tech stack, MVC architecture, Laravel/MySQL/Tailwind/Alpine/Turbo, self-hosted assets | 3 | Software Requirements, Programming Languages, Development Tools, Software Architecture |
| Server/client hardware, LAN topology | 3 | Hardware Requirements, Network Architecture |
| Authentication, role-based access control, CSRF, validation, audit logging | 3 | Security Features |
| System Architecture diagram (3-tier role structure, MVC layers) | 3 | System Architecture |
| Full route/controller/model/migration inventory (§2–§3, §13–§14 of this doc) | 4 | Requirements Analysis, Requirements Documentation, System Design (ERD, Use Case, Class-level description) |
| Database schema, relationships, constraints (§13) | 4 | Database Design |
| Wireframe/interface description per role portal | 4 | Interface Design |
| Coding/integration/deployment narrative | 4 | Software Development |
| §17 Testing table (PASS/VERIFIED vs NOT YET EVALUATED) | 4 | Testing (Unit/Integration/System/UAT sections — clearly separate what's verified from what needs live user testing) |
| Module screenshots (not available from this environment — flagged for you to capture) | 4 | Prototype Description, Implementation Results |
| Deployment/training/maintenance plan | 4 | Implementation Plan |
| ISO 25010 methodology, criteria, respondent/sampling plan, statistical treatment, formulas, blank result tables | 4 | System Evaluation — **results themselves stay `[TO BE COMPLETED AFTER DATA COLLECTION]`** |
| §18 Known Limitations | 5 | Future Enhancements (each limitation reframed as a recommended improvement) |
| §19 Proposal vs. Final differences | 5 | Conclusions (framed as "the system evolved from X to Y during development, for Z reason") |
| Overall functional completeness (§15 FRs, §17 verified workflows) | 5 | Summary of Findings |

---

## 21. Evidence / Source References

Grouped by feature, so any claim above can be checked against real files.

**Authentication & Roles**
- `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Middleware/EnsureUserHasRole.php`, `app/Http/Middleware/EnsurePasswordIsCurrent.php`
- `app/Enums/UserRole.php`, `app/Models/User.php`
- `bootstrap/app.php` (middleware registration)

**Question Bank / Import**
- `app/Http/Controllers/Counselor/QuestionController.php`, `QuestionImportController.php`
- `app/Services/QuestionDocumentExtractor.php`, `app/Services/QuestionImportParser.php`
- `app/Http/Requests/Counselor/StoreQuestionRequest.php`, `ImportQuestionsRequest.php`
- `app/Models/Question.php`, `QuestionOption.php`
- `resources/views/counselor/questions/import/create.blade.php`, `preview.blade.php`

**Exam Builder / Management / Targeting / Access Codes**
- `app/Http/Controllers/Counselor/ExamBuilderController.php`, `ExamController.php`
- `app/Models/Exam.php` (`scopeTargetedTo`, `interpretationFor`), `ExamSection.php`, `ExamQuestion.php`
- `resources/views/counselor/exams/builder.blade.php`

**Exam Taking / Timer / Scoring / One-Active-Session**
- `app/Http/Controllers/Student/ExamTakingController.php` (`start`, `take`, `answer`, `finalize`)
- `app/Http/Controllers/Counselor/ExamResultController.php` (`recomputeResult`, `gradeSession`, `storeGrades`)
- `app/Services/StudentRecordService.php` (`sectionBreakdown`)
- `database/migrations/2026_08_08_000000_add_one_active_session_constraint_to_exam_sessions_table.php`
- `app/Models/ExamSession.php`, `ExamAnswer.php`, `ExamResult.php`

**Live Monitoring**
- `app/Http/Controllers/Counselor/ExamMonitoringController.php`
- `resources/views/counselor/exams/monitor.blade.php` (5-second `setInterval`)

**Counseling / Bio-Notes**
- `app/Http/Controllers/Counselor/CounselingNoteController.php`, `app/Services/CounselingNoteService.php`
- `app/Policies/CounselingNotePolicy.php`
- `app/Models/CounselingNote.php`, `CounselingNoteRevision.php`
- `database/migrations/2026_08_08_000001_add_follow_up_action_to_counseling_notes_table.php`
- `resources/views/counselor/students/partials/_counseling-note-form.blade.php`, `resources/views/counselor/students/show.blade.php`

**Student Profile / Cumulative Record**
- `app/Http/Controllers/Counselor/StudentController.php`, `StudentRecordController.php`
- `app/Models/StudentProfile.php`
- `resources/views/counselor/students/show.blade.php`, `record-print.blade.php`

**Reports & Analytics**
- `app/Services/ReportAnalyticsService.php`
- `app/Http/Controllers/Counselor/ReportController.php`, `ReportScheduleController.php`
- `app/Models/ReportSchedule.php`

**Dashboards**
- `app/Services/AdminDashboardService.php`, `CounselorDashboardService.php`, `StudentDashboardService.php`
- `app/Http/Controllers/Admin/DashboardController.php`, `Counselor/DashboardController.php`, `Student/DashboardController.php`

**System Administration (Settings/Branding/Backup/Reference Data/Audit)**
- `app/Services/SchoolSettingsService.php`, `DatabaseBackupService.php`, `DepartmentService.php`, `CourseService.php`, `YearLevelService.php`
- `app/Http/Controllers/Admin/SettingsController.php`, `BackupController.php`, `DepartmentController.php`, `CourseController.php`, `YearLevelController.php`, `ReferenceDataController.php`, `AuditLogController.php`
- `app/Models/SystemSetting.php`, `AuditLog.php`

**Notifications**
- `app/Notifications/*.php` (8 classes)
- `app/Http/Controllers/NotificationController.php`

**LAN/Offline Asset Pipeline**
- `resources/views/partials/theme-head.blade.php` (`@vite(...)`)
- `resources/css/app.css`, `resources/js/app.js`, `vite.config.js`, `package.json`

**Database Schema**
- Every file in `database/migrations/` (26 application tables, chronologically)
- Every file in `app/Models/` (20 models)
- `database/seeders/DatabaseSeeder.php` + all 12 seeder classes

---

# Final Summary

- **Total modules found (§3):** 39 distinct items documented, all with real, verified implementation status (36 ✅ fully implemented, 2 🟡 partially implemented [Year Levels, Print Export as browser-print], 1 with a stub sub-portion [System Settings' Academic Year/General tabs]).
- **Total user roles:** 3 — Super Administrator, Guidance Counselor, Student — with a fourth conceptual layer (Counselor+Super-Admin-shared read access to most of the Counselor module) verified as its own distinct permission tier.
- **Major workflows:** Student exam-taking end-to-end (join → answer all 4 question types → submit → auto-score → manual grade → final result → interpretation); Counselor exam authoring end-to-end (Question Bank/import → Exam Builder → publish → monitor → grade → report); guidance/counseling record-keeping (bio-notes with revision history); system administration (accounts, settings, backup, reference data, audit).
- **Major database areas:** Identity/org (departments, users, profiles), exam authoring (exams, sections, questions, options, pivot), exam taking (sessions, answers, results, violations), guidance (counseling notes + revisions), system (settings, audit logs, notifications, report schedules), reference data (courses, year levels).
- **Implemented features added after the original proposal:** Super Administrator role/module, Question Bank bulk import, System Settings & dynamic branding, Reference Data management (Departments/Courses/Year Levels), Backup & Restore, in-app Notifications, Audit Log, Report Scheduling (config-only), light/dark theme, Turbo-driven SPA-style navigation.
- **Known limitations:** 13 specific, verified items — see §18 in full (report scheduling not automated, auto-save is per-navigation not continuous, PDF export is browser print-to-PDF, audit log doesn't cover auth events, `.docx` import needs a missing package, Year Levels lack soft-delete and aren't FK-linked to actual usage, students aren't linked to a specific course, no demographic fields, Item Analysis has no print view, 2 stub Settings tabs, a narrow answer-save timing race near expiry, no email ever sent).
- **Anything that could not be verified:** Backup/Restore's live create-then-restore cycle (reviewed thoroughly, not exercised against this working database, to avoid data loss); full Reference Data CRUD round-trips (endpoints and role-gating confirmed, individual create/edit/archive/restore actions not each independently clicked-through this session); notification mark-as-read UI interaction; concurrent/multi-student load testing; and — per explicit instruction — the entire ISO 25010 evaluation, for which zero respondents and zero data currently exist.
