# Skill Map System

Skill Map is a PHP and MySQL final-year project web application for student skill assessment, skill gap analysis, learning roadmaps, notifications, and admin management.

The system supports students, lecturers, staff, and admins. Students can manage their skill profile and view progress insights, while admins and authorized users can manage users, skills, roles, permissions, reviews, analytics, and notifications.

## Features

- Student skill self-assessment and profile management
- Target role benchmarks and skill gap analysis
- Learning roadmap and progress tracking
- Admin analytics dashboard
- User management with role-aware create/edit forms
- Registration validation for duplicate email, duplicate username, password strength, and password confirmation
- Role-aware signup fields:
  - Students use programme and year level
  - Lecturers, staff, and admin-style users use department and `N/A` year level
- Notification inbox with popup message reading
- Admin notification sending by role or specific user
- Message history for sent notifications
- Student, lecturer, and staff feedback messages to admin
- Role permission matrix
- User-specific permission overrides

## Tech Stack

- PHP 8+
- MySQL / MariaDB
- MySQLi and PDO database access
- Bootstrap 5.3
- Bootstrap Icons
- Vanilla JavaScript
- Chart.js

## Project Structure

- `admin/` - admin dashboard, users, permissions, notifications, reviews, skills, and benchmarks
- `users/` - student-facing dashboard, profile, assessment, notifications, roadmap, and reports
- `includes/` - shared authentication, authorization, database bootstrap, and helper functions
- `config/dbconnect.php` - database connection settings
- `assets/css/style.css` - custom UI styling
- `assets/js/app.js` - shared frontend interactions
- `schema.sql` - database schema and seed data
- `DEPLOY_INFINITYFREE.md` - InfinityFree deployment notes

## Local Setup With XAMPP

1. Place this folder inside XAMPP `htdocs`.
2. Start Apache and MySQL from the XAMPP control panel.
3. Create a MySQL database named `skill_map_system`.
4. Import `schema.sql` into that database using phpMyAdmin.
5. Confirm `config/dbconnect.php` matches your local MySQL credentials.
6. Open `http://localhost/fyp_skillmapsystem/login.php`.

## Demo Accounts

- Admin: `admin@gmail.com` / `admin@123`
- Student: `student@gmail.com` / `student@123`
- Lecturer: `lecturer@gmail.com` / `lecturer@123`
- Lecturer: `demostaff@gmail.com` / `staff@123`

Some seeded accounts also have usernames, such as `demostudent` and `demolecturer`, which can be used instead of email on the login form.

## Registration Rules

New account creation checks:

- Email must be valid and unique
- Username must be unique
- Username must be 3-40 characters using letters, numbers, dots, underscores, or hyphens
- Password must be at least 8 characters and include letters and numbers
- Password confirmation must match
- Other programme or role requests require admin approval

Student accounts require programme and year level. Lecturer, staff, and admin-created non-student accounts use a department value and store year level as `N/A`.

## Permissions

Permissions are managed in `admin/permissions.php`.

There are two permission layers:

- Role permissions: permissions granted to an access role such as admin, lecturer, staff, or student
- User permission overrides: extra permissions granted directly to one selected user

The permission checker first looks for direct user permissions, then falls back to role permissions. Admin users always have full access.

## Notifications

Notifications support:

- Sending to a selected role
- Sending to one specific user
- User feedback messages to admin
- Admin inbox and sent message history
- Popup reading view for full messages
- Read/unread tracking

Specific-user notifications are delivered only to the selected user. Role notifications are delivered only to users with the selected role.

## Development Notes

- The database bootstrap in `includes/functions.php` creates missing support tables when the app loads.
- Keep `schema.sql` updated when adding new tables or seed data.
- Avoid committing local machine files such as `.DS_Store`.
- For InfinityFree deployment, follow `DEPLOY_INFINITYFREE.md`.

## License

Developed by AggiesDEV for the Skill Map FYP project.
