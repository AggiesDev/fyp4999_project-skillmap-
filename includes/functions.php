<?php
// Shared helper functions for Skill Map.

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/dbconnect.php';
require_once __DIR__ . '/auth.php';

function skillmap_has_username_column(): bool
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    global $conn;
    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'username'");
    $cached = $result instanceof mysqli_result && mysqli_num_rows($result) > 0;

    if ($result instanceof mysqli_result) {
        mysqli_free_result($result);
    }

    return $cached;
}

function skillmap_user_column_exists(string $column): bool
{
    global $conn;

    $column = preg_replace('/[^A-Za-z0-9_]/', '', $column);
    if ($column === '') {
        return false;
    }

    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE '" . mysqli_real_escape_string($conn, $column) . "'");
    $exists = $result instanceof mysqli_result && mysqli_num_rows($result) > 0;

    if ($result instanceof mysqli_result) {
        mysqli_free_result($result);
    }

    return $exists;
}

function skillmap_bootstrap_database(): void
{
    global $conn;

    if (!($conn instanceof mysqli)) {
        return;
    }

    $usersTable = mysqli_query($conn, 'SHOW TABLES LIKE "users"');
    if (!($usersTable instanceof mysqli_result) || mysqli_num_rows($usersTable) === 0) {
        if ($usersTable instanceof mysqli_result) {
            mysqli_free_result($usersTable);
        }
        return;
    }
    mysqli_free_result($usersTable);

    if (!skillmap_has_username_column()) {
        @mysqli_query($conn, 'ALTER TABLE users ADD COLUMN username VARCHAR(120) NOT NULL UNIQUE AFTER name');
        @mysqli_query($conn, 'UPDATE users SET username = email WHERE username IS NULL OR username = ""');
    }

    skillmap_db_query('CREATE TABLE IF NOT EXISTS access_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL UNIQUE,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');

    skillmap_db_query('CREATE TABLE IF NOT EXISTS access_role_permissions (
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        enabled TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (role_id, permission_id),
        CONSTRAINT fk_access_role_permissions_role FOREIGN KEY (role_id) REFERENCES access_roles(id) ON DELETE CASCADE,
        CONSTRAINT fk_access_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    )');

    skillmap_db_query('CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_user_id INT NULL,
        sender_role ENUM("admin", "lecturer", "staff") NULL,
        recipient_user_id INT NULL,
        recipient_role ENUM("admin", "student", "lecturer", "staff", "all") NOT NULL DEFAULT "all",
        notification_type ENUM("message", "info", "alert", "reminder") NOT NULL DEFAULT "message",
        title VARCHAR(190) NOT NULL,
        body TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        read_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_notifications_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_notifications_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    skillmap_db_query('CREATE TABLE IF NOT EXISTS user_credentials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        entry_type ENUM("Skill", "Certification") NOT NULL,
        title VARCHAR(190) NOT NULL,
        issuer VARCHAR(190) NULL,
        notes TEXT NULL,
        earned_at DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_user_credentials_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    skillmap_db_query('CREATE TABLE IF NOT EXISTS account_approval_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        request_type ENUM("programme", "role") NOT NULL,
        requested_value VARCHAR(190) NOT NULL,
        status ENUM("Pending", "Approved", "Rejected") NOT NULL DEFAULT "Pending",
        reviewed_by INT NULL,
        reviewed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_account_approval_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_account_approval_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    )');

    @mysqli_query($conn, 'ALTER TABLE users MODIFY COLUMN role VARCHAR(80) NOT NULL DEFAULT "student"');
    if (!skillmap_user_column_exists('gender')) {
        mysqli_query($conn, 'ALTER TABLE users ADD COLUMN gender ENUM("male", "female") NOT NULL DEFAULT "male" AFTER avatar_initials');
    }
    if (!skillmap_user_column_exists('profile_icon')) {
        mysqli_query($conn, 'ALTER TABLE users ADD COLUMN profile_icon VARCHAR(190) NOT NULL DEFAULT "profileicons/icons8-add-user-male-100.png" AFTER gender');
    }
    @mysqli_query($conn, 'UPDATE users SET gender = "male" WHERE gender IS NULL OR gender = ""');
    @mysqli_query($conn, 'UPDATE users SET profile_icon = CASE WHEN role IN ("admin", "staff", "lecturer") THEN "profileicons/icons8-administrator-male-100.png" ELSE "profileicons/icons8-add-user-male-100.png" END WHERE profile_icon IS NULL OR profile_icon = ""');
    @mysqli_query($conn, 'UPDATE users SET profile_icon = "profileicons/icons8-administrator-male-100.png" WHERE role IN ("admin", "staff", "lecturer") AND profile_icon = "profileicons/icons8-add-user-male-100.png"');

    skillmap_db_query('INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)', 'ss', ['view_admin_dashboard', 'View the main admin dashboard']);
    skillmap_db_query('INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)', 'ss', ['manage_users', 'Create, update, and deactivate user accounts']);
    skillmap_db_query('INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)', 'ss', ['manage_roles', 'Maintain target roles and skill benchmarks']);
    skillmap_db_query('INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)', 'ss', ['manage_skills', 'Maintain the skill library and categories']);
    skillmap_db_query('INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)', 'ss', ['review_student_skills', 'Review and edit student skill ratings']);
    skillmap_db_query('INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)', 'ss', ['manage_permissions', 'Manage access for system roles']);
    skillmap_db_query('INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)', 'ss', ['send_notifications', 'Send notifications and messages to users']);

    skillmap_db_query('INSERT IGNORE INTO access_roles (name, description) VALUES (?, ?)', 'ss', ['admin', 'Full system access']);
    skillmap_db_query('INSERT IGNORE INTO access_roles (name, description) VALUES (?, ?)', 'ss', ['lecturer', 'Review and support student skills']);
    skillmap_db_query('INSERT IGNORE INTO access_roles (name, description) VALUES (?, ?)', 'ss', ['staff', 'Operational support access']);
    skillmap_db_query('INSERT IGNORE INTO access_roles (name, description) VALUES (?, ?)', 'ss', ['student', 'Personal skill tracking access']);

    $fullAccessPermissions = ['view_admin_dashboard', 'manage_users', 'manage_roles', 'manage_skills', 'review_student_skills', 'manage_permissions', 'send_notifications'];
    foreach ($fullAccessPermissions as $permissionName) {
        skillmap_db_query('INSERT IGNORE INTO access_role_permissions (role_id, permission_id, enabled) VALUES ((SELECT id FROM access_roles WHERE name = ? LIMIT 1), (SELECT id FROM permissions WHERE name = ? LIMIT 1), 1)', 'ss', ['admin', $permissionName]);
    }

    foreach (['view_admin_dashboard', 'review_student_skills'] as $permissionName) {
        skillmap_db_query('INSERT IGNORE INTO access_role_permissions (role_id, permission_id, enabled) VALUES ((SELECT id FROM access_roles WHERE name = ? LIMIT 1), (SELECT id FROM permissions WHERE name = ? LIMIT 1), 1)', 'ss', ['lecturer', $permissionName]);
        skillmap_db_query('INSERT IGNORE INTO access_role_permissions (role_id, permission_id, enabled) VALUES ((SELECT id FROM access_roles WHERE name = ? LIMIT 1), (SELECT id FROM permissions WHERE name = ? LIMIT 1), 1)', 'ss', ['staff', $permissionName]);
    }

    skillmap_db_query('INSERT IGNORE INTO access_role_permissions (role_id, permission_id, enabled) VALUES ((SELECT id FROM access_roles WHERE name = ? LIMIT 1), (SELECT id FROM permissions WHERE name = ? LIMIT 1), 1)', 'ss', ['lecturer', 'send_notifications']);
    skillmap_db_query('INSERT IGNORE INTO access_role_permissions (role_id, permission_id, enabled) VALUES ((SELECT id FROM access_roles WHERE name = ? LIMIT 1), (SELECT id FROM permissions WHERE name = ? LIMIT 1), 1)', 'ss', ['staff', 'send_notifications']);

    $userCountResult = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM users');
    $userCount = 0;
    if ($userCountResult instanceof mysqli_result) {
        $row = mysqli_fetch_assoc($userCountResult);
        $userCount = (int) ($row['total'] ?? 0);
        mysqli_free_result($userCountResult);
    }

    if ($userCount === 0) {
        $passwords = [
            'admin' => '$2y$10$wD08lmaueYT.1/QHAl63q.99WXPomKTsVyIYGKrs0bozsj.N1ALV2',
            'student' => '$2y$10$WAhc1B6/Ul7XRbNcXYpuD.HTMcKZqDKnCuaBqC/X7WHsHi/hYBCOy',
            'lecturer' => '$2y$10$SSn8Vmrxf7EQVjdxcedMG.o.hl/ehe52ODEBmviThS1y0GA57hJhG',
        ];

        skillmap_db_query(
            'INSERT INTO users (name, username, email, password_hash, role, programme, year_level, avatar_initials, gender, profile_icon, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Active") ON DUPLICATE KEY UPDATE email = VALUES(email)',
            'ssssssssss',
            ['Admin User', 'admin@gmail.com', 'admin@gmail.com', $passwords['admin'], 'admin', 'FDSIT', 'Staff', 'AU', 'male', skillmap_default_profile_icon('male', 'admin')]
        );
        skillmap_db_query(
            'INSERT INTO users (name, username, email, password_hash, role, programme, year_level, avatar_initials, gender, profile_icon, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Active") ON DUPLICATE KEY UPDATE email = VALUES(email)',
            'ssssssssss',
            ['Demo Student', 'demostudent', 'student@gmail.com', $passwords['student'], 'student', 'Information Systems', 'Year 4', 'DS', 'male', skillmap_default_profile_icon('male', 'student')]
        );
        skillmap_db_query(
            'INSERT INTO users (name, username, email, password_hash, role, programme, year_level, avatar_initials, gender, profile_icon, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Active") ON DUPLICATE KEY UPDATE email = VALUES(email)',
            'ssssssssss',
            ['Demo Lecturer', 'demolecturer', 'lecturer@gmail.com', $passwords['lecturer'], 'lecturer', 'Information Systems', 'Staff', 'DL', 'male', skillmap_default_profile_icon('male', 'lecturer')]
        );
        skillmap_db_query(
            'INSERT INTO users (name, username, email, password_hash, role, programme, year_level, avatar_initials, gender, profile_icon, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Active") ON DUPLICATE KEY UPDATE email = VALUES(email)',
            'ssssssssss',
            ['Demo Staff', 'demostaff', 'staff@gmail.com', $passwords['lecturer'], 'staff', 'Information Systems', 'Staff', 'ST', 'male', skillmap_default_profile_icon('male', 'staff')]
        );

        skillmap_db_query('INSERT INTO notifications (sender_role, recipient_role, notification_type, title, body) VALUES (?, ?, ?, ?, ?)', 'sssss', ['admin', 'student', 'info', 'Welcome to Skill Map', 'Start by updating your profile and completing your first analysis.']);
        skillmap_db_query('INSERT INTO notifications (sender_role, recipient_role, notification_type, title, body) VALUES (?, ?, ?, ?, ?)', 'sssss', ['lecturer', 'student', 'reminder', 'Profile review reminder', 'Please review and rate any new skills you have learned this week.']);
        skillmap_db_query('INSERT INTO notifications (sender_role, recipient_role, notification_type, title, body) VALUES (?, ?, ?, ?, ?)', 'sssss', ['staff', 'lecturer', 'message', 'Department update', 'Please check the latest student notification schedule and review queue.']);

        skillmap_db_query('INSERT IGNORE INTO roles (name, type, description) VALUES (?, ?, ?)', 'sss', ['Web Developer', 'Career', 'Build modern web applications']);
        skillmap_db_query('INSERT IGNORE INTO roles (name, type, description) VALUES (?, ?, ?)', 'sss', ['Data Analyst', 'Career', 'Analyse data and communicate insights']);
        skillmap_db_query('INSERT IGNORE INTO roles (name, type, description) VALUES (?, ?, ?)', 'sss', ['IT Support', 'Career', 'Support users with hardware and software issues']);
        skillmap_db_query('INSERT IGNORE INTO roles (name, type, description) VALUES (?, ?, ?)', 'sss', ['Student Club President', 'Lead', 'Coordinate and lead student organisation activities']);

        skillmap_db_query('INSERT IGNORE INTO skill_categories (name, type, icon) VALUES (?, ?, ?)', 'sss', ['Technical', 'Skill Category', 'bi-code-square']);
        skillmap_db_query('INSERT IGNORE INTO skill_categories (name, type, icon) VALUES (?, ?, ?)', 'sss', ['Leadership', 'Skill Category', 'bi-people-fill']);
        skillmap_db_query('INSERT IGNORE INTO skill_categories (name, type, icon) VALUES (?, ?, ?)', 'sss', ['Interpersonal', 'Skill Category', 'bi-chat-dots-fill']);
        skillmap_db_query('INSERT IGNORE INTO skill_categories (name, type, icon) VALUES (?, ?, ?)', 'sss', ['Academic', 'Skill Category', 'bi-journal-text']);
        skillmap_db_query('INSERT IGNORE INTO skill_categories (name, type, icon) VALUES (?, ?, ?)', 'sss', ['Organisational', 'Skill Category', 'bi-folder-check']);

        skillmap_db_query('INSERT IGNORE INTO skills (category_id, name, description, difficulty, status) VALUES ((SELECT id FROM skill_categories WHERE name = ? LIMIT 1), ?, ?, ?, ?)', 'sssis', ['Technical', 'PHP', 'Server-side scripting for backend logic.', 3, 'Active']);
        skillmap_db_query('INSERT IGNORE INTO skills (category_id, name, description, difficulty, status) VALUES ((SELECT id FROM skill_categories WHERE name = ? LIMIT 1), ?, ?, ?, ?)', 'sssis', ['Technical', 'MySQL', 'Structured querying and relational database design.', 3, 'Active']);
        skillmap_db_query('INSERT IGNORE INTO skills (category_id, name, description, difficulty, status) VALUES ((SELECT id FROM skill_categories WHERE name = ? LIMIT 1), ?, ?, ?, ?)', 'sssis', ['Technical', 'JavaScript', 'Interactive client-side application behaviour.', 3, 'Active']);
        skillmap_db_query('INSERT IGNORE INTO skills (category_id, name, description, difficulty, status) VALUES ((SELECT id FROM skill_categories WHERE name = ? LIMIT 1), ?, ?, ?, ?)', 'sssis', ['Technical', 'Bootstrap 5', 'Responsive layout and UI components.', 2, 'Active']);
        skillmap_db_query('INSERT IGNORE INTO skills (category_id, name, description, difficulty, status) VALUES ((SELECT id FROM skill_categories WHERE name = ? LIMIT 1), ?, ?, ?, ?)', 'sssis', ['Technical', 'Git', 'Version control and collaboration workflow.', 2, 'Active']);
        skillmap_db_query('INSERT IGNORE INTO skills (category_id, name, description, difficulty, status) VALUES ((SELECT id FROM skill_categories WHERE name = ? LIMIT 1), ?, ?, ?, ?)', 'sssis', ['Technical', 'API Integration', 'Connect systems through web APIs.', 4, 'Active']);

        skillmap_db_query('INSERT IGNORE INTO badges (name, tier, description, icon) VALUES (?, ?, ?, ?)', 'ssss', ['Bronze Explorer', 'bronze', 'Completed first gap analysis', 'bi-award']);
        skillmap_db_query('INSERT IGNORE INTO badges (name, tier, description, icon) VALUES (?, ?, ?, ?)', 'ssss', ['Skill Builder', 'silver', 'Improved three core skills', 'bi-stars']);
        skillmap_db_query('INSERT IGNORE INTO badges (name, tier, description, icon) VALUES (?, ?, ?, ?)', 'ssss', ['Roadmap Runner', 'gold', 'Completed a full learning roadmap', 'bi-trophy']);

        skillmap_db_query('INSERT IGNORE INTO career_roles (name, type, description) VALUES (?, ?, ?)', 'sss', ['Web Developer', 'Career', 'Build modern web applications']);
        skillmap_db_query('INSERT IGNORE INTO career_roles (name, type, description) VALUES (?, ?, ?)', 'sss', ['Data Analyst', 'Career', 'Analyse and present data insights']);
        skillmap_db_query('INSERT IGNORE INTO career_roles (name, type, description) VALUES (?, ?, ?)', 'sss', ['IT Support', 'Career', 'Provide technical support']);
        skillmap_db_query('INSERT IGNORE INTO career_roles (name, type, description) VALUES (?, ?, ?)', 'sss', ['Student Club President', 'Lead', 'Lead student organisations']);
    }

    @mysqli_query($conn, 'UPDATE career_roles SET name = "Computer Scientist", description = COALESCE(NULLIF(description, ""), "Apply computing theory, software design, and data methods") WHERE name = "Computer Scient"');

    $referenceCategories = [
        ['Technical', 'Skill Category', 'bi-code-square'],
        ['Leadership', 'Skill Category', 'bi-people-fill'],
        ['Interpersonal', 'Skill Category', 'bi-chat-dots-fill'],
        ['Academic', 'Skill Category', 'bi-journal-text'],
        ['Organisational', 'Skill Category', 'bi-folder-check'],
        ['Web Developer', 'Target Role', 'bi-code-slash'],
        ['Data Analyst', 'Target Role', 'bi-bar-chart-line'],
        ['IT Support', 'Target Role', 'bi-pc-display'],
        ['Student Club President', 'Target Role', 'bi-megaphone'],
        ['Computer Scientist', 'Target Role', 'bi-cpu'],
        ['Software Engineer', 'Target Role', 'bi-window-stack'],
        ['Cybersecurity Analyst', 'Target Role', 'bi-shield-lock'],
        ['UI/UX Designer', 'Target Role', 'bi-palette'],
        ['Database Administrator', 'Target Role', 'bi-database-check'],
        ['Business Analyst', 'Target Role', 'bi-clipboard-data'],
        ['Project Manager', 'Target Role', 'bi-kanban'],
        ['Research Assistant', 'Target Role', 'bi-journal-richtext'],
        ['Team Leader', 'Target Role', 'bi-person-workspace'],
    ];
    foreach ($referenceCategories as $category) {
        skillmap_db_query('INSERT IGNORE INTO skill_categories (name, type, icon) VALUES (?, ?, ?)', 'sss', $category);
    }

    $referenceSkills = [
        ['Technical', 'PHP', 'Server-side scripting for backend logic.', 3, 'Active'],
        ['Technical', 'MySQL', 'Structured querying and relational database design.', 3, 'Active'],
        ['Technical', 'JavaScript', 'Interactive client-side application behaviour.', 3, 'Active'],
        ['Technical', 'Bootstrap 5', 'Responsive layout and UI components.', 2, 'Active'],
        ['Technical', 'Git', 'Version control and collaboration workflow.', 2, 'Active'],
        ['Technical', 'API Integration', 'Connect systems through web APIs.', 4, 'Active'],
        ['Technical', 'Python', 'Automation, scripting, and data-focused programming.', 3, 'Active'],
        ['Technical', 'Data Visualization', 'Turn raw data into clear charts and decision-ready dashboards.', 3, 'Active'],
        ['Technical', 'Network Basics', 'Understand IP addressing, connectivity, and common network tools.', 2, 'Active'],
        ['Technical', 'Troubleshooting', 'Diagnose technical issues with structured investigation steps.', 3, 'Active'],
        ['Leadership', 'Team Coordination', 'Organise people, tasks, and follow-up across shared goals.', 3, 'Active'],
        ['Leadership', 'Event Planning', 'Plan student or work events with timelines, budgets, and logistics.', 2, 'Active'],
        ['Interpersonal', 'Public Speaking', 'Present ideas clearly to classmates, teams, and stakeholders.', 2, 'Active'],
        ['Interpersonal', 'Customer Support', 'Communicate calmly and resolve user problems professionally.', 2, 'Active'],
        ['Academic', 'Research Writing', 'Structure evidence, citations, and analysis into clear reports.', 3, 'Active'],
        ['Academic', 'Statistics', 'Use descriptive statistics and basic inference to interpret data.', 3, 'Active'],
        ['Organisational', 'Project Planning', 'Break work into milestones, owners, timelines, and risks.', 3, 'Active'],
        ['Technical', 'Cybersecurity Basics', 'Protect systems with foundational security controls and risk awareness.', 3, 'Active'],
        ['Technical', 'Cloud Fundamentals', 'Understand hosted infrastructure, services, deployment, and cloud cost basics.', 3, 'Active'],
        ['Technical', 'Database Administration', 'Maintain relational database performance, backups, access, and reliability.', 4, 'Active'],
        ['Technical', 'UI/UX Design', 'Design user flows, wireframes, and usable interfaces based on user needs.', 3, 'Active'],
        ['Interpersonal', 'Requirements Analysis', 'Gather, clarify, and document stakeholder needs for a solution.', 3, 'Active'],
        ['Organisational', 'Agile Methodology', 'Plan and deliver iterative work with sprints, ceremonies, and feedback.', 3, 'Active'],
        ['Academic', 'Problem Solving', 'Break complex problems into clear, testable solution steps.', 4, 'Active'],
    ];
    foreach ($referenceSkills as $skill) {
        skillmap_db_query(
            'INSERT IGNORE INTO skills (category_id, name, description, difficulty, status)
             VALUES ((SELECT id FROM skill_categories WHERE name = ? LIMIT 1), ?, ?, ?, ?)',
            'sssis',
            $skill
        );
    }

    $referenceRoles = [
        ['Web Developer', 'Career', 'Build modern web applications'],
        ['Data Analyst', 'Career', 'Analyse and present data insights'],
        ['IT Support', 'Career', 'Provide technical support'],
        ['Computer Scientist', 'Career', 'Apply computing theory, software design, and data methods'],
        ['Software Engineer', 'Career', 'Design, build, test, and maintain reliable software systems'],
        ['Cybersecurity Analyst', 'Career', 'Monitor risks, protect systems, and respond to security incidents'],
        ['UI/UX Designer', 'Career', 'Design useful, accessible, and polished digital product experiences'],
        ['Database Administrator', 'Career', 'Maintain secure, reliable, and high-performing database systems'],
        ['Business Analyst', 'Career', 'Bridge stakeholder needs, process improvements, and technical delivery'],
        ['Research Assistant', 'Career', 'Support academic or industry research with data, writing, and analysis'],
        ['Project Manager', 'Lead', 'Coordinate scope, timelines, risks, and team delivery'],
        ['Team Leader', 'Lead', 'Guide a team through communication, ownership, and delivery priorities'],
        ['Student Club President', 'Lead', 'Lead student organisations'],
    ];
    foreach ($referenceRoles as $role) {
        skillmap_db_query('INSERT IGNORE INTO career_roles (name, type, description) VALUES (?, ?, ?)', 'sss', $role);
    }

    $referenceBenchmarks = [
        ['Web Developer', 'PHP', 4, 'Critical'],
        ['Web Developer', 'MySQL', 4, 'Critical'],
        ['Web Developer', 'JavaScript', 4, 'Critical'],
        ['Web Developer', 'Bootstrap 5', 3, 'Important'],
        ['Web Developer', 'Git', 3, 'Important'],
        ['Web Developer', 'API Integration', 4, 'Important'],
        ['Web Developer', 'Project Planning', 3, 'Optional'],
        ['Data Analyst', 'MySQL', 4, 'Critical'],
        ['Data Analyst', 'Python', 4, 'Critical'],
        ['Data Analyst', 'Statistics', 4, 'Critical'],
        ['Data Analyst', 'Data Visualization', 4, 'Important'],
        ['Data Analyst', 'Research Writing', 3, 'Important'],
        ['IT Support', 'Troubleshooting', 4, 'Critical'],
        ['IT Support', 'Network Basics', 4, 'Critical'],
        ['IT Support', 'Customer Support', 3, 'Important'],
        ['IT Support', 'MySQL', 2, 'Optional'],
        ['IT Support', 'Git', 2, 'Optional'],
        ['Computer Scientist', 'Python', 4, 'Critical'],
        ['Computer Scientist', 'Git', 3, 'Important'],
        ['Computer Scientist', 'MySQL', 3, 'Important'],
        ['Computer Scientist', 'Statistics', 3, 'Important'],
        ['Computer Scientist', 'Research Writing', 4, 'Important'],
        ['Student Club President', 'Team Coordination', 4, 'Critical'],
        ['Student Club President', 'Event Planning', 4, 'Critical'],
        ['Student Club President', 'Public Speaking', 4, 'Important'],
        ['Student Club President', 'Project Planning', 3, 'Important'],
        ['Student Club President', 'Customer Support', 3, 'Optional'],
        ['Software Engineer', 'JavaScript', 4, 'Critical'],
        ['Software Engineer', 'Git', 4, 'Critical'],
        ['Software Engineer', 'API Integration', 4, 'Important'],
        ['Software Engineer', 'Problem Solving', 4, 'Critical'],
        ['Software Engineer', 'Cloud Fundamentals', 3, 'Important'],
        ['Software Engineer', 'Agile Methodology', 3, 'Important'],
        ['Cybersecurity Analyst', 'Cybersecurity Basics', 4, 'Critical'],
        ['Cybersecurity Analyst', 'Network Basics', 4, 'Critical'],
        ['Cybersecurity Analyst', 'Troubleshooting', 4, 'Important'],
        ['Cybersecurity Analyst', 'Problem Solving', 4, 'Important'],
        ['Cybersecurity Analyst', 'Research Writing', 3, 'Optional'],
        ['UI/UX Designer', 'UI/UX Design', 4, 'Critical'],
        ['UI/UX Designer', 'Public Speaking', 3, 'Important'],
        ['UI/UX Designer', 'Requirements Analysis', 4, 'Critical'],
        ['UI/UX Designer', 'Bootstrap 5', 3, 'Important'],
        ['UI/UX Designer', 'Research Writing', 3, 'Optional'],
        ['Database Administrator', 'MySQL', 4, 'Critical'],
        ['Database Administrator', 'Database Administration', 4, 'Critical'],
        ['Database Administrator', 'Troubleshooting', 4, 'Important'],
        ['Database Administrator', 'Cybersecurity Basics', 3, 'Important'],
        ['Database Administrator', 'Cloud Fundamentals', 3, 'Optional'],
        ['Business Analyst', 'Requirements Analysis', 4, 'Critical'],
        ['Business Analyst', 'Data Visualization', 3, 'Important'],
        ['Business Analyst', 'Public Speaking', 3, 'Important'],
        ['Business Analyst', 'Project Planning', 3, 'Important'],
        ['Business Analyst', 'Research Writing', 4, 'Critical'],
        ['Project Manager', 'Project Planning', 4, 'Critical'],
        ['Project Manager', 'Agile Methodology', 4, 'Critical'],
        ['Project Manager', 'Team Coordination', 4, 'Critical'],
        ['Project Manager', 'Public Speaking', 3, 'Important'],
        ['Project Manager', 'Requirements Analysis', 3, 'Important'],
        ['Research Assistant', 'Research Writing', 4, 'Critical'],
        ['Research Assistant', 'Statistics', 4, 'Critical'],
        ['Research Assistant', 'Data Visualization', 3, 'Important'],
        ['Research Assistant', 'Python', 3, 'Important'],
        ['Research Assistant', 'Problem Solving', 4, 'Important'],
        ['Team Leader', 'Team Coordination', 4, 'Critical'],
        ['Team Leader', 'Project Planning', 4, 'Important'],
        ['Team Leader', 'Public Speaking', 3, 'Important'],
        ['Team Leader', 'Agile Methodology', 3, 'Important'],
        ['Team Leader', 'Problem Solving', 4, 'Critical'],
    ];
    foreach ($referenceBenchmarks as $benchmark) {
        skillmap_db_query(
            'INSERT INTO role_skill_benchmarks (role_id, skill_id, required_rating, priority)
             SELECT cr.id, s.id, ?, ?
             FROM career_roles cr
             INNER JOIN skills s ON s.name = ?
             WHERE cr.name = ?
               AND NOT EXISTS (
                   SELECT 1
                   FROM role_skill_benchmarks rb
                   WHERE rb.role_id = cr.id AND rb.skill_id = s.id
               )
             LIMIT 1',
            'isss',
            [(int) $benchmark[2], (string) $benchmark[3], (string) $benchmark[1], (string) $benchmark[0]]
        );
    }
}

/**
 * Execute a prepared statement and return the result or boolean success.
 * Returns mysqli_result on SELECT queries, true on successful non-SELECT, or false on failure.
 *
 * @param string $sql
 * @param string $types
 * @param array $params
 * @return mysqli_result|bool
 */
function skillmap_db_query(string $sql, string $types = '', array $params = [])
{
    global $conn;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    if ($types !== '' && $params !== []) {
        $bindParams = [$types];
        foreach ($params as $index => $value) {
            $bindParams[$index + 1] = &$params[$index];
        }
        if (!call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $bindParams))) {
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result instanceof mysqli_result) {
        mysqli_stmt_close($stmt);
        return $result;
    }

    mysqli_stmt_close($stmt);
    return true;
}

function skillmap_fetch_one(string $sql, string $types = '', array $params = []): ?array
{
    $result = skillmap_db_query($sql, $types, $params);
    if ($result instanceof mysqli_result) {
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return $row ?: null;
    }

    return null;
}

function skillmap_fetch_all(string $sql, string $types = '', array $params = []): array
{
    $result = skillmap_db_query($sql, $types, $params);
    if ($result instanceof mysqli_result) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);
        return $rows;
    }

    return [];
}

function skillmap_current_user(): ?array
{
    return current_user();
}

function skillmap_is_logged_in(): bool
{
    return skillmap_current_user() !== null;
}

function skillmap_require_auth(array $roles = []): void
{
    if ($roles === []) {
        require_login();
        return;
    }

    require_role($roles);
}

function skillmap_current_profile(string $email): ?array
{
    $columns = skillmap_has_username_column() ? 'id, name, email, username, role, programme, year_level, avatar_initials, gender, profile_icon, status' : 'id, name, email, role, programme, year_level, avatar_initials, gender, profile_icon, status';
    $user = skillmap_fetch_one('SELECT ' . $columns . ' FROM users WHERE email = ? LIMIT 1', 's', [$email]);
    return $user ?: null;
}

function skillmap_login(string $loginKey, string $password): ?array
{
    return login($loginKey, $password);
}

function skillmap_register(array $payload): ?array
{
    $name = trim((string) ($payload['name'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');
    $programmeChoice = trim((string) ($payload['programme'] ?? ''));
    $programmeOther = trim((string) ($payload['programme_other'] ?? ''));
    $year = trim((string) ($payload['year'] ?? ''));
    $roleChoice = trim((string) ($payload['role'] ?? 'student'));
    $roleOther = trim((string) ($payload['role_other'] ?? ''));
    $username = trim((string) ($payload['username'] ?? ''));
    $gender = skillmap_normalize_gender((string) ($payload['gender'] ?? 'male'));
    $profileIcon = skillmap_sanitize_profile_icon((string) ($payload['profile_icon'] ?? ''), $gender, $roleChoice);

    $programme = $programmeChoice === '__other' ? $programmeOther : $programmeChoice;
    $requestedRole = $roleChoice === '__other' ? $roleOther : $roleChoice;
    $systemRole = in_array($roleChoice, ['student', 'lecturer'], true) ? $roleChoice : 'student';
    $needsApproval = $programmeChoice === '__other' || $roleChoice === '__other';

    if ($programme === '') {
        $programme = 'Information Systems';
    }
    if ($year === '') {
        $year = 'Year 1';
    }

    return register($name, $username, $email, $password, $programme, $year, $systemRole, $needsApproval ? 'Inactive' : 'Active', [
        'programme' => $programmeChoice === '__other' ? $programme : '',
        'role' => $roleChoice === '__other' ? $requestedRole : '',
    ], $gender, $profileIcon);
}

function skillmap_registration_options(): array
{
    $programmes = array_column(skillmap_fetch_all('SELECT DISTINCT programme AS value FROM users WHERE programme <> "" AND status = "Active" AND role IN ("student", "lecturer") ORDER BY programme'), 'value');
    $years = array_column(skillmap_fetch_all('SELECT DISTINCT year_level AS value FROM users WHERE year_level LIKE "Year %" AND status = "Active" AND role IN ("student", "lecturer") ORDER BY year_level'), 'value');

    if ($programmes === []) {
        $programmes = ['Information Systems', 'Software Engineering', 'Computer Science'];
    }

    $years = array_values(array_unique(array_merge(['Year 1', 'Year 2', 'Year 3', 'Year 4'], $years)));

    $roles = array_column(skillmap_fetch_all('SELECT name AS value FROM access_roles WHERE name IN ("student", "lecturer") ORDER BY FIELD(name, "student", "lecturer")'), 'value');
    if ($roles === []) {
        $roles = ['student', 'lecturer'];
    }

    return [
        'programmes' => $programmes,
        'years' => $years,
        'roles' => $roles,
    ];
}

function skillmap_pending_account_requests(): array
{
    return skillmap_fetch_all(
        'SELECT ar.id, ar.user_id, ar.request_type, ar.requested_value, ar.status, ar.created_at,
                u.name, u.email, u.username, u.role, u.programme, u.year_level, u.status AS user_status
         FROM account_approval_requests ar
         INNER JOIN users u ON u.id = ar.user_id
         WHERE ar.status = "Pending"
         ORDER BY ar.created_at DESC'
    );
}

function skillmap_asset(string $path): string
{
    return '/fyp_skillmapsystem/' . ltrim($path, '/');
}

function skillmap_current_page(string $page): string
{
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}

function skillmap_star_rating(int $rating, int $max = 5): string
{
    $html = '<div class="skillmap-stars" data-rating="' . $rating . '">';
    for ($i = 1; $i <= $max; $i++) {
        $html .= '<button type="button" class="btn btn-link p-0 border-0 star-btn" data-value="' . $i . '" aria-label="Rate ' . $i . ' out of ' . $max . '">';
        $html .= $i <= $rating ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-warning"></i>';
        $html .= '</button>';
    }
    $html .= '</div>';

    return $html;
}

function skillmap_rating_badge(int $rating, int $max = 5): string
{
    return '<span class="badge rounded-pill text-bg-light border">' . $rating . '/' . $max . '</span>';
}

function skillmap_status_badge(string $status): string
{
    $map = [
        'Have' => 'bg-success-subtle text-success border border-success-subtle',
        'Active' => 'bg-success-subtle text-success border border-success-subtle',
        'Good Match' => 'bg-success-subtle text-success border border-success-subtle',
        'Partial' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'Important' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'Missing' => 'bg-danger-subtle text-danger border border-danger-subtle',
        'Critical' => 'bg-danger-subtle text-danger border border-danger-subtle',
        'Inactive' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        'Career' => 'bg-primary-subtle text-primary border border-primary-subtle',
        'Lead' => 'bg-purple-subtle text-purple border border-purple-subtle',
    ];

    $class = $map[$status] ?? 'bg-light text-dark border';
    return '<span class="badge rounded-pill ' . $class . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
}

function skillmap_allowed_notification_targets(string $role): array
{
    if (skillmap_user_can('manage_permissions') || skillmap_user_can('manage_users')) {
        return ['all', 'student', 'lecturer', 'staff'];
    }

    if (!skillmap_user_can('send_notifications')) {
        return [];
    }

    switch ($role) {
        case 'admin':
            return ['all', 'student', 'lecturer', 'staff'];
        case 'staff':
            return ['student', 'lecturer'];
        case 'lecturer':
            return ['student'];
        default:
            return [];
    }
}

function skillmap_fetch_notification_items(int $userId, string $role, int $limit = 10): array
{
    $items = skillmap_fetch_all(
        'SELECT n.id, n.notification_type, n.title, n.body, n.is_read, n.read_at, n.created_at,
                COALESCE(sender.name, n.sender_role, "System") AS sender_name,
                COALESCE(sender.avatar_initials, UPPER(LEFT(COALESCE(sender.name, n.sender_role, "SY"), 2))) AS sender_initials,
                CASE WHEN n.recipient_user_id IS NOT NULL THEN "Direct"
                     ELSE UPPER(n.recipient_role) END AS audience
         FROM notifications n
         LEFT JOIN users sender ON sender.id = n.sender_user_id
         WHERE n.recipient_user_id = ?
            OR (n.recipient_user_id IS NULL AND n.recipient_role IN (?, "all"))
         ORDER BY n.created_at DESC
         LIMIT ' . (int) $limit,
        'is',
        [$userId, $role]
    );

    if ($items === []) {
        return [];
    }

    return $items;
}

function skillmap_fetch_sent_notification_items(int $senderUserId, int $limit = 20): array
{
    if ($senderUserId <= 0) {
        return [];
    }

    return skillmap_fetch_all(
        'SELECT n.id, n.notification_type, n.title, n.body, n.created_at,
                CASE WHEN n.recipient_user_id IS NOT NULL THEN COALESCE(recipient.name, "Selected user")
                     ELSE UPPER(n.recipient_role) END AS audience,
                COALESCE(recipient.role, n.recipient_role) AS audience_role
         FROM notifications n
         LEFT JOIN users recipient ON recipient.id = n.recipient_user_id
         WHERE n.sender_user_id = ?
         ORDER BY n.created_at DESC
         LIMIT ' . (int) $limit,
        'i',
        [$senderUserId]
    );
}

function skillmap_notification_unread_count(int $userId, string $role): int
{
    $row = skillmap_fetch_one(
        'SELECT COUNT(*) AS unread_count
         FROM notifications n
         WHERE n.is_read = 0
           AND (n.recipient_user_id = ? OR (n.recipient_user_id IS NULL AND n.recipient_role IN (?, "all")))',
        'is',
        [$userId, $role]
    );

    return (int) ($row['unread_count'] ?? 0);
}

function skillmap_mark_notification_read(int $notificationId, int $userId, string $role): bool
{
    $updated = skillmap_db_query(
        'UPDATE notifications
         SET is_read = 1, read_at = NOW()
         WHERE id = ? AND (recipient_user_id = ? OR (recipient_user_id IS NULL AND recipient_role IN (?, "all")))',
        'iis',
        [$notificationId, $userId, $role]
    );

    return $updated === true;
}

function skillmap_create_notification(array $payload): bool
{
    $senderUserId = isset($payload['sender_user_id']) ? (int) $payload['sender_user_id'] : null;
    $senderRole = trim((string) ($payload['sender_role'] ?? ''));
    $recipientRole = trim((string) ($payload['recipient_role'] ?? ''));
    $recipientUserId = isset($payload['recipient_user_id']) && $payload['recipient_user_id'] !== '' ? (int) $payload['recipient_user_id'] : null;
    $type = trim((string) ($payload['notification_type'] ?? 'message'));
    $title = trim((string) ($payload['title'] ?? ''));
    $body = trim((string) ($payload['body'] ?? ''));

    if ($title === '' || $body === '') {
        return false;
    }

    if ($recipientUserId !== null) {
        $recipientRole = in_array($recipientRole, ['admin', 'student', 'lecturer', 'staff'], true) ? $recipientRole : '';
        if ($recipientRole === '') {
            $recipient = skillmap_fetch_one('SELECT role FROM users WHERE id = ? LIMIT 1', 'i', [$recipientUserId]);
            $recipientRole = (string) ($recipient['role'] ?? '');
        }

        if (!in_array($recipientRole, ['admin', 'student', 'lecturer', 'staff'], true)) {
            return false;
        }

        $senderIdValue = $senderUserId > 0 ? $senderUserId : 0;
        $senderRoleValue = in_array($senderRole, ['admin', 'lecturer', 'staff'], true) ? $senderRole : '';
        return skillmap_db_query(
            'INSERT INTO notifications (sender_user_id, sender_role, recipient_user_id, recipient_role, notification_type, title, body) VALUES (NULLIF(?, 0), NULLIF(?, ""), ?, ?, ?, ?, ?)',
            'isissss',
            [$senderIdValue, $senderRoleValue, $recipientUserId, $recipientRole, $type, $title, $body]
        ) === true;
    }

    if ($recipientRole === '') {
        return false;
    }

    $recipientRole = in_array($recipientRole, ['admin', 'student', 'lecturer', 'staff', 'all'], true) ? $recipientRole : 'all';

    $senderIdValue = $senderUserId > 0 ? $senderUserId : 0;
    $senderRoleValue = in_array($senderRole, ['admin', 'lecturer', 'staff'], true) ? $senderRole : '';

    $insert = skillmap_db_query(
        'INSERT INTO notifications (sender_user_id, sender_role, recipient_role, notification_type, title, body) VALUES (NULLIF(?, 0), NULLIF(?, ""), ?, ?, ?, ?)',
        'isssss',
        [$senderIdValue, $senderRoleValue, $recipientRole, $type, $title, $body]
    );

    return $insert === true;
}

function skillmap_fetch_user_credentials(int $userId): array
{
    return skillmap_fetch_all('SELECT id, entry_type, title, issuer, notes, DATE_FORMAT(earned_at, "%e %b %Y") AS earned_at FROM user_credentials WHERE user_id = ? ORDER BY created_at DESC', 'i', [$userId]);
}

function skillmap_save_user_credential(int $userId, array $payload): bool
{
    if ($userId <= 0) {
        return false;
    }

    $entryType = (string) ($payload['entry_type'] ?? 'Skill');
    $entryType = in_array($entryType, ['Skill', 'Certification'], true) ? $entryType : 'Skill';
    $title = trim((string) ($payload['title'] ?? ''));
    $issuer = trim((string) ($payload['issuer'] ?? ''));
    $notes = trim((string) ($payload['notes'] ?? ''));
    $earnedAt = trim((string) ($payload['earned_at'] ?? ''));

    if ($title === '') {
        return false;
    }

    $insert = skillmap_db_query(
        'INSERT INTO user_credentials (user_id, entry_type, title, issuer, notes, earned_at) VALUES (?, ?, ?, ?, ?, NULLIF(?, ""))',
        'isssss',
        [$userId, $entryType, $title, $issuer !== '' ? $issuer : null, $notes !== '' ? $notes : null, $earnedAt]
    );

    return $insert === true;
}

function skillmap_access_roles(): array
{
    $roles = skillmap_fetch_all('SELECT id, name, description FROM access_roles ORDER BY id ASC');
    if ($roles !== []) {
        return $roles;
    }

    return [
        ['id' => 1, 'name' => 'admin', 'description' => 'Full system access'],
        ['id' => 2, 'name' => 'lecturer', 'description' => 'Review and support student skills'],
        ['id' => 3, 'name' => 'staff', 'description' => 'Operational support access'],
        ['id' => 4, 'name' => 'student', 'description' => 'Personal skill tracking access'],
    ];
}

function skillmap_access_permissions(): array
{
    $permissions = skillmap_fetch_all('SELECT id, name, description FROM permissions ORDER BY id ASC');
    if ($permissions !== []) {
        return $permissions;
    }

    return [
        ['id' => 1, 'name' => 'view_admin_dashboard', 'description' => 'View the main admin dashboard'],
        ['id' => 2, 'name' => 'manage_users', 'description' => 'Create, update, and deactivate user accounts'],
        ['id' => 3, 'name' => 'manage_roles', 'description' => 'Maintain target roles and skill benchmarks'],
        ['id' => 4, 'name' => 'manage_skills', 'description' => 'Maintain the skill library and categories'],
        ['id' => 5, 'name' => 'review_student_skills', 'description' => 'Review and edit student skill ratings'],
        ['id' => 6, 'name' => 'manage_permissions', 'description' => 'Manage access for system roles'],
    ];
}

function skillmap_user_can(string $permissionName): bool
{
    return has_permission($permissionName);
}

function skillmap_require_permission(string $permissionName): void
{
    if (!skillmap_user_can($permissionName)) {
        header('Location: /fyp_skillmapsystem/login.php');
        exit;
    }
}

function skillmap_default_destination(array $user): string
{
    if (($user['role'] ?? '') === 'admin' || skillmap_user_can('view_admin_dashboard')) {
        return '/fyp_skillmapsystem/admin/analytics.php';
    }

    if (skillmap_user_can('review_student_skills')) {
        return '/fyp_skillmapsystem/admin/reviews.php';
    }

    if (skillmap_user_can('manage_users')) {
        return '/fyp_skillmapsystem/admin/users.php';
    }

    if (skillmap_user_can('manage_skills')) {
        return '/fyp_skillmapsystem/admin/skill_library.php';
    }

    if (skillmap_user_can('manage_roles')) {
        return '/fyp_skillmapsystem/admin/benchmarks.php';
    }

    if (skillmap_user_can('send_notifications')) {
        return '/fyp_skillmapsystem/admin/notifications.php';
    }

    return '/fyp_skillmapsystem/users/dashboard.php';
}

function skillmap_percent_bar(int $value, string $color = 'primary'): string
{
    return '<div class="progress" role="progressbar" aria-valuenow="' . $value . '" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-' . $color . '" style="width: ' . $value . '%"></div></div>';
}

function skillmap_progress_ring(int $percent, string $label = ''): string
{
    $labelHtml = $label !== '' ? '<div class="small text-muted mt-2">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>' : '';
    return '<div class="skillmap-ring" style="--value:' . $percent . '"><div class="skillmap-ring-inner"><strong>' . $percent . '%</strong>' . $labelHtml . '</div></div>';
}

function skillmap_badge_tier_class(string $tier): string
{
    switch ($tier) {
        case 'bronze':
            return 'badge-bronze';
        case 'silver':
            return 'badge-silver';
        case 'gold':
            return 'badge-gold';
        default:
            return 'badge-secondary';
    }
}

function skillmap_icon(string $icon): string
{
    return '<i class="bi ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i>';
}

function skillmap_student_data(): array
{
    $user = skillmap_current_user();
    $email = $user['email'] ?? 'student@gmail.com';

    $student = skillmap_fetch_one(
        'SELECT u.id, u.name, u.email, u.role, u.programme, u.year_level, u.avatar_initials,
                COALESCE((SELECT AVG(match_score) FROM analyses a WHERE a.user_id = u.id), 0) AS avg_match,
                COALESCE((SELECT COUNT(*) FROM analyses a WHERE a.user_id = u.id), 0) AS analyses_done,
                COALESCE((SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.id), 0) AS badges_earned,
                COALESCE((SELECT COUNT(*) FROM user_skill_ratings r WHERE r.user_id = u.id), 0) AS skills_assessed,
                COALESCE((SELECT COUNT(*) FROM skills s), 0) AS skills_total,
                COALESCE((SELECT current_streak FROM learning_streaks ls WHERE ls.user_id = u.id), 0) AS streak_days
         FROM users u
         WHERE u.email = ?
         LIMIT 1',
        's',
        [$email]
    );

    if (!$student) {
        return [
            'name' => 'Student',
            'programme' => 'Information Systems',
            'year' => 'Year 4',
            'institution' => 'UTM',
            'match_score' => 0,
            'skills_assessed' => 0,
            'skills_total' => 0,
            'badges_earned' => 0,
            'analyses_done' => 0,
            'last_analysis' => '-',
            'recent_analyses' => [],
            'achievement_badges' => [],
            'ai_insight' => 'Add a first analysis to generate recommendations.',
            'profile_completion' => 0,
            'profile_categories' => [],
            'profile_skills' => [],
            'analyse_roles' => [],
            'leadership_roles' => [],
            'gap_analysis' => ['role' => 'Web Developer', 'match' => 0, 'status' => 'Good Match', 'summary' => ['have' => 0, 'partial' => 0, 'missing' => 0], 'verdict' => '', 'focus' => '', 'eta' => '', 'skills' => []],
            'roadmap' => ['role' => 'Web Developer', 'match' => 0, 'complete' => 0, 'summary' => ['missing' => 0, 'partial' => 0, 'completed' => 0], 'hours' => 0, 'days' => 0, 'missing' => [], 'partial' => [], 'completed' => []],
            'progress' => ['delta_month' => 0, 'skills_improved' => [], 'streak' => 0, 'badges' => [], 'history' => []],
            'report' => ['generated' => date('j M Y'), 'pages' => 0, 'share_link' => '', 'match' => 0, 'top_missing' => []],
        ];
    }

    $analysisRows = skillmap_fetch_all(
        'SELECT cr.name AS role_name, a.match_score
         FROM analyses a
         INNER JOIN career_roles cr ON cr.id = a.target_role_id
         WHERE a.user_id = ?
         ORDER BY a.created_at DESC
         LIMIT 3',
        'i',
        [(int) $student['id']]
    );

    if ($analysisRows === []) {
        $analysisRows = [
            ['role_name' => 'Web Developer', 'match_score' => 82],
        ];
    }

    $recentAnalyses = array_map(function ($row) {
        return ['role' => $row['role_name'], 'score' => (int) round((float) $row['match_score'])];
    }, $analysisRows);

    $lastAnalysis = skillmap_fetch_one('SELECT DATE_FORMAT(created_at, "%e %b %Y") AS created_at FROM analyses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1', 'i', [(int) $student['id']]);

    $badges = skillmap_fetch_all(
        'SELECT b.name, b.tier, DATE_FORMAT(ub.earned_at, "%e %b %Y") AS earned_at
         FROM user_badges ub
         INNER JOIN badges b ON b.id = ub.badge_id
         WHERE ub.user_id = ?
         ORDER BY ub.earned_at DESC',
        'i',
        [(int) $student['id']]
    );
    if ($badges === []) {
        $badges = [
            ['name' => 'Bronze Explorer', 'tier' => 'bronze', 'earned_at' => '10 May 2026'],
        ];
    }

    $skillCount = (int) ($student['skills_total'] ?? 0);
    $ratedCount = (int) ($student['skills_assessed'] ?? 0);
    $completion = $skillCount > 0 ? (int) round(($ratedCount / $skillCount) * 100) : 0;

    $categories = skillmap_fetch_all('SELECT name FROM skill_categories WHERE type = "Skill Category" ORDER BY id ASC');
    $profileCategories = [];
    foreach ($categories as $index => $category) {
        $profileCategories[] = ['name' => $category['name'], 'done' => $index < 3];
    }

    $skills = skillmap_fetch_all('SELECT s.id AS skill_id, c.name AS category, s.name, s.description, COALESCE(r.rating, 0) AS score FROM skills s INNER JOIN skill_categories c ON c.id = s.category_id LEFT JOIN user_skill_ratings r ON r.skill_id = s.id AND r.user_id = ? ORDER BY c.id, s.name', 'i', [(int) $student['id']]);
    $profileSkills = [];
    foreach ($skills as $skill) {
        $profileSkills[$skill['category']][] = ['id' => (int) $skill['skill_id'], 'name' => $skill['name'], 'description' => $skill['description'], 'score' => (int) $skill['score']];
    }

    $analyseRoles = skillmap_fetch_all('SELECT name, icon, mapped_count AS mapped FROM (SELECT cr.name, COALESCE(sc.icon, "bi-code-slash") AS icon, 12 AS mapped_count FROM career_roles cr LEFT JOIN skill_categories sc ON sc.name = cr.name WHERE cr.type = "Career" ORDER BY cr.id LIMIT 5) AS t');
    if ($analyseRoles === []) {
        $analyseRoles = [
            ['name' => 'Web Developer', 'icon' => 'bi-code-slash', 'mapped' => 18],
        ];
    }

    $gapSkills = skillmap_fetch_all(
        'SELECT s.name AS skill, c.name AS category, COALESCE(r.rating, 0) AS rating, COALESCE(rb.required_rating, 0) AS required_rating,
                CASE WHEN COALESCE(r.rating, 0) >= COALESCE(rb.required_rating, 0) THEN "Have"
                     WHEN COALESCE(r.rating, 0) = 0 THEN "Missing"
                     ELSE "Partial" END AS status,
                GREATEST(COALESCE(rb.required_rating, 0) - COALESCE(r.rating, 0), 0) AS gap
         FROM skills s
         INNER JOIN skill_categories c ON c.id = s.category_id
         LEFT JOIN user_skill_ratings r ON r.skill_id = s.id AND r.user_id = ?
         LEFT JOIN role_skill_benchmarks rb ON rb.skill_id = s.id
         LEFT JOIN career_roles cr ON cr.id = rb.role_id AND cr.name = ?
         ORDER BY FIELD(status, "Have", "Partial", "Missing"), s.name',
        'is',
        [(int) $student['id'], 'Web Developer']
    );
    if ($gapSkills === []) {
        $gapSkills = [
            ['skill' => 'PHP', 'category' => 'Technical', 'rating' => 4, 'required_rating' => 4, 'status' => 'Have', 'gap' => 0],
            ['skill' => 'API Integration', 'category' => 'Technical', 'rating' => 2, 'required_rating' => 4, 'status' => 'Missing', 'gap' => 2],
        ];
    }

    $roadmapMissing = skillmap_fetch_all('SELECT s.name AS skill, lr.platform, lr.duration_hours, lr.is_free, lr.title AS resource, COALESCE(urp.progress_pct, 0) AS progress FROM learning_resources lr INNER JOIN skills s ON s.id = lr.skill_id LEFT JOIN user_roadmap_progress urp ON urp.skill_id = s.id AND urp.user_id = ? WHERE COALESCE(urp.status, "Missing") = "Missing" ORDER BY lr.duration_hours ASC', 'i', [(int) $student['id']]);
    if ($roadmapMissing === []) {
        $roadmapMissing = [
            ['skill' => 'API Integration', 'platform' => 'Coursera', 'duration_hours' => 6, 'is_free' => 1, 'resource' => 'Build a REST API with PHP', 'progress' => 0],
        ];
    }

    $roadmapPartial = skillmap_fetch_all('SELECT s.name AS skill, lr.platform, lr.duration_hours, lr.is_free, lr.title AS resource, COALESCE(urp.progress_pct, 0) AS progress FROM learning_resources lr INNER JOIN skills s ON s.id = lr.skill_id LEFT JOIN user_roadmap_progress urp ON urp.skill_id = s.id AND urp.user_id = ? WHERE COALESCE(urp.status, "Partial") = "Partial" ORDER BY lr.duration_hours ASC', 'i', [(int) $student['id']]);
    if ($roadmapPartial === []) {
        $roadmapPartial = [
            ['skill' => 'Bootstrap 5', 'platform' => 'Udemy', 'duration_hours' => 5, 'is_free' => 0, 'resource' => 'Responsive UI build-up', 'progress' => 60],
        ];
    }

    $roadmapCompleted = skillmap_fetch_all('SELECT s.name AS skill, lr.platform, lr.duration_hours, lr.title AS resource, COALESCE(urp.progress_pct, 100) AS progress FROM learning_resources lr INNER JOIN skills s ON s.id = lr.skill_id LEFT JOIN user_roadmap_progress urp ON urp.skill_id = s.id AND urp.user_id = ? WHERE COALESCE(urp.status, "Completed") = "Completed" ORDER BY lr.duration_hours ASC', 'i', [(int) $student['id']]);
    if ($roadmapCompleted === []) {
        $roadmapCompleted = [
            ['skill' => 'PHP', 'platform' => 'Codecademy', 'duration_hours' => 8, 'resource' => 'Server-side fundamentals', 'progress' => 100],
        ];
    }

    $historyRows = skillmap_fetch_all('SELECT DATE_FORMAT(a.created_at, "%e %b %Y") AS created_at, cr.name AS role_name, ROUND(a.match_score) AS match_score FROM analyses a INNER JOIN career_roles cr ON cr.id = a.target_role_id WHERE a.user_id = ? ORDER BY a.created_at ASC LIMIT 10', 'i', [(int) $student['id']]);
    if ($historyRows === []) {
        $historyRows = [
            ['created_at' => date('j M Y'), 'role_name' => 'Web Developer', 'match_score' => 82],
        ];
    }

    $notificationItems = skillmap_fetch_notification_items((int) $student['id'], (string) $student['role'], 5);
    $credentials = skillmap_fetch_user_credentials((int) $student['id']);
    $unreadNotifications = skillmap_notification_unread_count((int) $student['id'], (string) $student['role']);

    $profile = [
        'name' => $student['name'],
        'programme' => $student['programme'],
        'year' => $student['year_level'],
        'institution' => 'UTM',
        'match_score' => (int) round((float) ($student['avg_match'] ?? 0)),
        'skills_assessed' => $ratedCount,
        'skills_total' => $skillCount,
        'badges_earned' => (int) ($student['badges_earned'] ?? 0),
        'analyses_done' => (int) ($student['analyses_done'] ?? 0),
        'last_analysis' => $lastAnalysis['created_at'] ?? '-',
        'recent_analyses' => $recentAnalyses,
        'achievement_badges' => array_map(function ($badge) { return ['name' => $badge['name'], 'date' => $badge['earned_at'], 'tier' => $badge['tier']]; }, $badges),
        'ai_insight' => 'Focus on API integration and deployment basics to raise your match score faster for web and product roles.',
        'profile_completion' => $completion,
        'profile_categories' => $profileCategories,
        'profile_skills' => $profileSkills,
        'credentials' => $credentials,
        'notifications' => $notificationItems,
        'unread_notifications' => $unreadNotifications,
        'analyse_roles' => $analyseRoles,
        'leadership_roles' => [
            ['name' => 'Student Club President', 'icon' => 'bi-people-fill', 'tags' => ['Leadership', 'Public Speaking', 'Event Planning'], 'mapped' => 11],
            ['name' => 'Academic Project Manager', 'icon' => 'bi-kanban-fill', 'tags' => ['Planning', 'Coordination', 'Reporting'], 'mapped' => 10],
            ['name' => 'Peer Mentor Lead', 'icon' => 'bi-mortarboard-fill', 'tags' => ['Mentoring', 'Empathy', 'Coaching'], 'mapped' => 9],
        ],
        'gap_analysis' => [
            'role' => 'Web Developer',
            'match' => (int) round((float) ($student['avg_match'] ?? 0)),
            'status' => 'Good Match',
            'summary' => ['have' => 8, 'partial' => 4, 'missing' => 2],
            'verdict' => 'You are close to job-ready for this target role.',
            'focus' => 'Strengthen API Integration, responsive layout testing, and deployment workflow.',
            'eta' => '4 to 6 weeks',
            'skills' => $gapSkills,
        ],
        'roadmap' => [
            'role' => 'Web Developer',
            'match' => (int) round((float) ($student['avg_match'] ?? 0)),
            'complete' => 64,
            'summary' => ['missing' => count($roadmapMissing), 'partial' => count($roadmapPartial), 'completed' => count($roadmapCompleted)],
            'hours' => 42,
            'days' => 18,
            'missing' => $roadmapMissing,
            'partial' => $roadmapPartial,
            'completed' => $roadmapCompleted,
        ],
        'progress' => [
            'delta_month' => 9,
            'skills_improved' => [
                ['name' => 'JavaScript', 'delta' => '+1'],
                ['name' => 'Bootstrap 5', 'delta' => '+1'],
                ['name' => 'API Integration', 'delta' => '+2'],
            ],
            'streak' => (int) ($student['streak_days'] ?? 0),
            'badges' => array_map(function ($badge) { return ['name' => $badge['name'], 'progress' => 100, 'unlocked' => true]; }, $badges),
            'history' => array_map(function ($row) { return ['date' => $row['created_at'], 'role' => $row['role_name'], 'score' => (int) $row['match_score'], 'change' => '+5']; }, $historyRows),
        ],
        'report' => [
            'generated' => date('j M Y'),
            'pages' => 8,
            'share_link' => 'https://skillmap.local/share/' . $student['avatar_initials'] . '-' . $student['id'],
            'match' => (int) round((float) ($student['avg_match'] ?? 0)),
            'top_missing' => [
                ['skill' => 'API Integration', 'resource' => 'Build a REST API with PHP', 'platform' => 'Coursera'],
                ['skill' => 'Deployment Basics', 'resource' => 'Deploy to Apache and cPanel', 'platform' => 'YouTube'],
                ['skill' => 'Test Automation', 'resource' => 'Introduction to testing workflows', 'platform' => 'freeCodeCamp'],
            ],
        ],
    ];

    return $profile;
}

function skillmap_admin_data(): array
{
    $totalStudents = skillmap_fetch_one('SELECT COUNT(*) AS total_students FROM users WHERE role IN ("student", "lecturer", "staff")');
    $newStudents = skillmap_fetch_one('SELECT COUNT(*) AS new_students FROM users WHERE created_at >= CURDATE() - INTERVAL 30 DAY');
    $avgMatch = skillmap_fetch_one('SELECT COALESCE(ROUND(AVG(match_score)), 0) AS avg_match FROM analyses');
    $popularRole = skillmap_fetch_one('SELECT cr.name AS role_name, COUNT(*) AS total FROM analyses a INNER JOIN career_roles cr ON cr.id = a.target_role_id GROUP BY cr.id ORDER BY total DESC LIMIT 1');
    $missingSkill = skillmap_fetch_one('SELECT s.name AS skill_name, COUNT(*) AS total FROM analysis_results ar INNER JOIN skills s ON s.id = ar.skill_id WHERE ar.status = "Missing" GROUP BY s.id ORDER BY total DESC LIMIT 1');

    $missingSkills = skillmap_fetch_all('SELECT s.name, ROUND(100 * COUNT(*) / NULLIF((SELECT COUNT(*) FROM analyses), 0), 0) AS pct FROM analysis_results ar INNER JOIN skills s ON s.id = ar.skill_id WHERE ar.status = "Missing" GROUP BY s.id ORDER BY pct DESC LIMIT 8');
    $categories = skillmap_fetch_all('SELECT sc.name, sc.type, COUNT(sk.id) AS subcategories, DATE_FORMAT(sc.updated_at, "%e %b %Y") AS updated, sc.icon FROM skill_categories sc LEFT JOIN skills sk ON sk.category_id = sc.id GROUP BY sc.id ORDER BY sc.id ASC');
    $skills = skillmap_fetch_all('SELECT s.name, c.name AS category, s.description, s.difficulty, s.status FROM skills s INNER JOIN skill_categories c ON c.id = s.category_id ORDER BY s.id ASC');
    $users = skillmap_fetch_all('SELECT u.name, u.email, u.programme, u.year_level AS year, COALESCE(COUNT(a.id), 0) AS analyses, COALESCE(ROUND(AVG(a.match_score)), 0) AS best_match, COALESCE((SELECT cr.name FROM analyses a2 INNER JOIN career_roles cr ON cr.id = a2.target_role_id WHERE a2.user_id = u.id ORDER BY a2.match_score DESC LIMIT 1), "-") AS top_role, COALESCE((SELECT current_streak FROM learning_streaks ls WHERE ls.user_id = u.id), 0) AS streak, DATE_FORMAT(u.updated_at, "%e %b %Y") AS last_active, u.status FROM users u LEFT JOIN analyses a ON a.user_id = u.id WHERE u.role IN ("student", "lecturer", "staff") GROUP BY u.id ORDER BY u.created_at DESC');
    $roles = skillmap_fetch_all('SELECT cr.name, cr.type, COUNT(rsb.id) AS skills, COALESCE(ROUND(AVG(rsb.required_rating), 1), 0) AS avg_required, SUM(CASE WHEN rsb.priority = "Critical" THEN 1 ELSE 0 END) AS critical, DATE_FORMAT(MAX(rsb.updated_at), "%e %b %Y") AS updated FROM career_roles cr LEFT JOIN role_skill_benchmarks rsb ON rsb.role_id = cr.id GROUP BY cr.id ORDER BY cr.id ASC');
    $selectedSkills = skillmap_fetch_all('SELECT s.name, c.name AS category, rb.required_rating AS required, rb.priority FROM role_skill_benchmarks rb INNER JOIN skills s ON s.id = rb.skill_id INNER JOIN skill_categories c ON c.id = s.category_id INNER JOIN career_roles cr ON cr.id = rb.role_id WHERE cr.name = "Web Developer" ORDER BY rb.required_rating DESC');

    return [
        'analytics' => [
            'month' => date('F Y'),
            'total_students' => (int) ($totalStudents['total_students'] ?? 0),
            'new_students' => (int) ($newStudents['new_students'] ?? 0),
            'avg_match' => (int) ($avgMatch['avg_match'] ?? 0),
            'avg_change' => '+6%',
            'popular_role' => $popularRole['role_name'] ?? 'Web Developer',
            'missing_skill' => $missingSkill['skill_name'] ?? 'API Integration',
            'missing_skill_pct' => 41,
            'missing_skills' => $missingSkills,
        ],
        'categories' => $categories,
        'skills' => $skills,
        'benchmarks' => [
            'roles' => $roles,
            'selected' => 'Web Developer',
            'selected_skills' => $selectedSkills,
        ],
        'users' => $users,
    ];
}

function skillmap_save_profile(int $userId, array $ratings): bool
{
    if ($userId <= 0 || $ratings === []) {
        return false;
    }

    foreach ($ratings as $skillId => $rating) {
        $skillId = (int) $skillId;
        $rating = max(1, min(5, (int) $rating));

        $existing = skillmap_fetch_one('SELECT id FROM user_skill_ratings WHERE user_id = ? AND skill_id = ? LIMIT 1', 'ii', [$userId, $skillId]);
        if ($existing) {
            $updated = skillmap_db_query('UPDATE user_skill_ratings SET rating = ?, updated_at = NOW() WHERE user_id = ? AND skill_id = ?', 'iii', [$rating, $userId, $skillId]);
            if ($updated !== true) {
                return false;
            }
            continue;
        }

        $inserted = skillmap_db_query('INSERT INTO user_skill_ratings (user_id, skill_id, rating) VALUES (?, ?, ?)', 'iii', [$userId, $skillId, $rating]);
        if ($inserted !== true) {
            return false;
        }
    }

    return true;
}

function skillmap_data(): array
{
    return [
        'student' => skillmap_student_data(),
        'admin' => skillmap_admin_data(),
    ];
}

skillmap_bootstrap_database();
