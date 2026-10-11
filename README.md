# Witty Inventions – Service Report Generator

Laravel 13 + Inertia/React app that produces the **Witty Inventions Service Report** (.docx) for every service.

## Interfaces

| | URL | Who | What they do |
|---|---|---|---|
| **Admin** | `/admin` | Admin users | Review/approve/return reports, download .docx, manage service types, task list, platforms, worker roster and users |
| **Service coordinator** | `/coordinator/reports` | Coordinators | Create, edit, duplicate and submit their own reports; preview and download .docx |

Report workflow: `draft → submitted → approved` (or `returned` with a note, which unlocks editing again).

## Service types
Sunday Service, Tuesday Service, 7DG and 12DG are seeded. Admins add more under **Service types**.
Each type has its own default summary/conclusion text, and tasks can be global or limited to one service type.

## Setup
```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build          # or `npm run dev` while developing
php artisan serve
```
Seeded demo logins (change/delete before going live): `admin@witty.test` / `coordinator@witty.test`, password `password`.

## Document generation
`app/Services/ReportDocxBuilder.php` builds the Word file with PHPWord, mirroring the original layout
(A4, Arial, purple `#5A2D82` headings/table headers, header and footer, 7 numbered sections).
Branding strings live in `config/report.php`; the logo is `resources/logo.png`.

## Tests
`php artisan test`
