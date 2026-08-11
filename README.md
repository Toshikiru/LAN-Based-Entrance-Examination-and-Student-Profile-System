# TPC EntryPoint

A LAN-based Entrance Examination and Student Profile Management System built for **Talibon Polytechnic College's Guidance Office**, developed as a capstone project on **Laravel 12**.

The system runs entirely on the campus local area network (no internet dependency for core functionality) and serves three role-specific portals from a single codebase: **Super Administrator**, **Guidance Counselor**, and **Student**.

## Features

**Super Administrator**
- Guidance Counselor account management (create, edit, activate/deactivate, reset password)
- Institutional reference data — departments, courses, year levels
- System settings and branding (school info, logo, favicon)
- Full database backup, download, and restore
- Audit log of every account and data-changing action

**Guidance Counselor**
- Question bank authoring (Multiple Choice, True/False, Likert Scale, Short Answer) with bulk import from TXT/DOCX/PDF
- Exam Builder — sections, drag-to-reorder questions, search/filter question picker, per-exam settings and score interpretation ranges
- Live session monitoring with real-time progress, flagging, and termination
- Manual grading queue for short-answer responses
- Student profile and counseling-note CRUD
- Results, analytics, and printable/CSV reports

**Student**
- Join an assigned examination with an access code
- Timed exam runner with question navigation, flagging, and auto-save
- Automatic scoring (with manual grading for short-answer items) and personal results history

**Shared**
- Role-based access control enforced at both the route and request-authorization layers
- Global header search scoped per role
- Light/dark theme support across every portal
- Public landing page with a working "Sign In" flow

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.2+), standard MVC
- **Database:** MySQL/MariaDB (production/LAN deployment via XAMPP), SQLite supported for local development
- **Frontend:** Blade components, Tailwind CSS v4, Alpine.js, Hotwired Turbo, Chart.js, SortableJS — all bundled and self-hosted via Vite (no CDN dependencies, by design, for LAN-only deployment)
- **Design system:** Material 3–inspired token system (see `Stitch_UIDesign/guidancepulse/DESIGN.md`)

## Getting Started

```bash
# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database (SQLite works out of the box; see .env for MySQL/MariaDB config)
php artisan migrate --seed

# Build frontend assets
npm run build
# or, during development:
npm run dev

# Serve
php artisan serve
```

Visit `/` for the public landing page, or `/login` directly. Seeded demo accounts for each role are created by `database/seeders/UserSeeder.php`.

## License

Built on the [Laravel](https://laravel.com) framework, open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
