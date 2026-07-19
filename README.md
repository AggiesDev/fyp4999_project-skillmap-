# Skill Map

Skill Map is a local-development full-stack university FYP web application for skill gap analysis, learning roadmaps, and admin analytics.

## Tech Stack

- PHP 8+ with MySQLi
- MySQL
- HTML5, vanilla JavaScript, Bootstrap 5.3
- Chart.js

## Folder Layout

- `/admin` admin dashboards and management pages
- `/users` student-facing pages
- `/config/dbconnect.php` MySQLi connection helper
- `/includes` shared authentication and utility helpers
- `/assets/css` custom styling
- `/assets/js` shared UI and Chart.js logic
- `schema.sql` database schema and seed data

## Run on XAMPP

1. Copy the project folder into your XAMPP `htdocs` directory.
2. Start Apache and MySQL from the XAMPP control panel.
3. Open phpMyAdmin and import `schema.sql` into a database named `skill_map_system`.
4. If needed, confirm `config/dbconnect.php` uses `root` with no password for localhost.
5. Visit `http://localhost/fyp_skillmapsystem/login.php`.

## Demo Accounts

- Admin: `admin@gmail.com` / `admin@123`
- Student: `student@gmail.com` / `student@123` (`demostudent`)
- Lecturer: `lecturer@gmail.com` / `lecturer@123` (`demolecturer`)

## Notes

- The UI now reads from MySQL for the main student/admin data views.
- If the database is offline, protected pages will still render an empty fallback state.
# fyp4999_project-skillmap-
