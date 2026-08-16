<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/dbconnect.php';

$testStudents = [
    ['student01', 'Aina Farhana', 'Information Systems', 'Year 4', 'female', 'Web Developer', [
        'PHP' => 4, 'MySQL' => 4, 'JavaScript' => 4, 'Bootstrap 5' => 3, 'Git' => 3, 'API Integration' => 4, 'Project Planning' => 3,
        'Team Coordination' => 4, 'Event Planning' => 4, 'Public Speaking' => 4, 'Customer Support' => 3,
    ], 'Validation case with all Web Developer requirements met; also strong leadership evidence.'],
    ['student02', 'Brandon Lee', 'Information Systems', 'Year 3', 'male', 'Web Developer', [
        'PHP' => 0, 'MySQL' => 0, 'JavaScript' => 0, 'Bootstrap 5' => 0, 'Git' => 0, 'API Integration' => 0, 'Project Planning' => 0,
    ], 'Validation case with no current Web Developer benchmark requirements met.'],
    ['student03', 'Chong Mei Xin', 'Software Engineering', 'Year 4', 'female', 'Web Developer', [
        'PHP' => 3, 'MySQL' => 3, 'JavaScript' => 3, 'Bootstrap 5' => 2, 'Git' => 2, 'API Integration' => 3, 'Project Planning' => 2,
    ], 'Validation case where benchmark skills are close to the required level, so roadmap items are prioritised as Partial.'],
    ['student04', 'Danish Hakim', 'Computer Science', 'Year 2', 'male', 'Web Developer', [
        'PHP' => 2, 'MySQL' => 4, 'JavaScript' => 5, 'Bootstrap 5' => 1, 'Git' => 3, 'API Integration' => 0, 'Project Planning' => 3,
    ], 'Validation case with mixed Have, Partial, and Missing Web Developer skill statuses.'],
    ['student05', 'Elena Tan', 'Information Systems', 'Year 4', 'female', 'Student Club President', [
        'Team Coordination' => 4, 'Event Planning' => 4, 'Public Speaking' => 4, 'Project Planning' => 3, 'Customer Support' => 3,
    ], 'Validation case with all Student Club President requirements met.'],
    ['student06', 'Farhan Zulkifli', 'Information Systems', 'Year 2', 'male', 'Student Club President', [
        'Team Coordination' => 0, 'Event Planning' => 0, 'Public Speaking' => 0, 'Project Planning' => 0, 'Customer Support' => 0,
    ], 'Validation case with no current Student Club President benchmark requirements met.'],
    ['student07', 'Grace Wong', 'Software Engineering', 'Year 3', 'female', 'Student Club President', [
        'Team Coordination' => 3, 'Event Planning' => 2, 'Public Speaking' => 4, 'Project Planning' => 2, 'Customer Support' => 1,
    ], 'Validation case with mixed leadership strengths and gaps.'],
    ['student08', 'Harith Azman', 'Computer Science', 'Year 4', 'male', 'Data Analyst', [
        'MySQL' => 4, 'Python' => 3, 'Statistics' => 2, 'Data Visualization' => 4, 'Research Writing' => 2,
    ], 'Validation case for Data Analyst readiness and analytics.'],
    ['student09', 'Intan Maisarah', 'Information Systems', 'Year 1', 'female', 'IT Support', [
        'Troubleshooting' => 4, 'Network Basics' => 3, 'Customer Support' => 4, 'MySQL' => 2, 'Git' => 1,
    ], 'Validation case for IT Support readiness.'],
    ['student10', 'Jason Ng', 'Software Engineering', 'Year 2', 'male', 'Software Engineer', [
        'JavaScript' => 4, 'Git' => 4, 'API Integration' => 2, 'Problem Solving' => 4, 'Cloud Fundamentals' => 2, 'Agile Methodology' => 2,
    ], 'Validation case for Software Engineer readiness with several improvement items.'],
    ['student11', 'Kavitha Raj', 'Computer Science', 'Year 3', 'female', 'Cybersecurity Analyst', [
        'Cybersecurity Basics' => 3, 'Network Basics' => 4, 'Troubleshooting' => 2, 'Problem Solving' => 3, 'Research Writing' => 2,
    ], 'Validation case for Cybersecurity Analyst gaps and analytics.'],
    ['student12', 'Luqman Hakim', 'Information Systems', 'Year 4', 'male', 'Project Manager', [
        'Project Planning' => 4, 'Agile Methodology' => 3, 'Team Coordination' => 3, 'Public Speaking' => 2, 'Requirements Analysis' => 3,
    ], 'Validation case for leadership Project Manager readiness.'],
    ['student13', 'Maya Sofea', 'Software Engineering', 'Year 1', 'female', 'UI/UX Designer', [
        'UI/UX Design' => 4, 'Public Speaking' => 2, 'Requirements Analysis' => 3, 'Bootstrap 5' => 2, 'Research Writing' => 2,
    ], 'Validation case for UI/UX Designer cross-category gap results.'],
    ['student14', 'Nabil Irfan', 'Computer Science', 'Year 2', 'male', 'Database Administrator', [
        'MySQL' => 3, 'Database Administration' => 2, 'Troubleshooting' => 3, 'Cybersecurity Basics' => 2, 'Cloud Fundamentals' => 1,
    ], 'Validation case for lower Database Administrator readiness.'],
    ['student15', 'Olivia Chan', 'Information Systems', 'Year 3', 'female', 'Business Analyst', [
        'Requirements Analysis' => 4, 'Data Visualization' => 3, 'Public Speaking' => 3, 'Project Planning' => 3, 'Research Writing' => 3,
    ], 'Validation case for high Business Analyst readiness.'],
];

$staffUsers = [
    ['staff01', 'Nurul Staff One'],
    ['staff02', 'Raj Staff Two'],
    ['staff03', 'Mei Staff Three'],
    ['staff04', 'Adam Staff Four'],
    ['staff05', 'Priya Staff Five'],
];

$lecturerUsers = [
    ['lecturer01', 'Dr. Aiman Lecturer'],
    ['lecturer02', 'Dr. Sara Lecturer'],
    ['lecturer03', 'Dr. Victor Lecturer'],
];

$categories = [
    ['Technical', 'Skill Category', 'bi-code-square'],
    ['Leadership', 'Skill Category', 'bi-people-fill'],
    ['Interpersonal', 'Skill Category', 'bi-chat-dots-fill'],
    ['Academic', 'Skill Category', 'bi-journal-text'],
    ['Organisational', 'Skill Category', 'bi-folder-check'],
];

$skills = [
    ['Technical', 'PHP', 'Server-side scripting for backend logic.', 3],
    ['Technical', 'MySQL', 'Structured querying and relational database design.', 3],
    ['Technical', 'JavaScript', 'Interactive client-side application behaviour.', 3],
    ['Technical', 'Bootstrap 5', 'Responsive layout and UI components.', 2],
    ['Technical', 'Git', 'Version control and collaboration workflow.', 2],
    ['Technical', 'API Integration', 'Connect systems through web APIs.', 4],
    ['Technical', 'Python', 'Automation, scripting, and data-focused programming.', 3],
    ['Technical', 'Data Visualization', 'Turn raw data into clear charts and decision-ready dashboards.', 3],
    ['Technical', 'Network Basics', 'Understand IP addressing, connectivity, and common network tools.', 2],
    ['Technical', 'Troubleshooting', 'Diagnose technical issues with structured investigation steps.', 3],
    ['Technical', 'Cybersecurity Basics', 'Protect systems with foundational security controls and risk awareness.', 3],
    ['Technical', 'Cloud Fundamentals', 'Understand hosted infrastructure, services, deployment, and cloud cost basics.', 3],
    ['Technical', 'Database Administration', 'Maintain relational database performance, backups, access, and reliability.', 4],
    ['Technical', 'UI/UX Design', 'Design user flows, wireframes, and usable interfaces based on user needs.', 3],
    ['Leadership', 'Team Coordination', 'Organise people, tasks, and follow-up across shared goals.', 3],
    ['Leadership', 'Event Planning', 'Plan student or work events with timelines, budgets, and logistics.', 2],
    ['Interpersonal', 'Public Speaking', 'Present ideas clearly to classmates, teams, and stakeholders.', 2],
    ['Interpersonal', 'Customer Support', 'Communicate calmly and resolve user problems professionally.', 2],
    ['Interpersonal', 'Requirements Analysis', 'Gather, clarify, and document stakeholder needs for a solution.', 3],
    ['Academic', 'Research Writing', 'Structure evidence, citations, and analysis into clear reports.', 3],
    ['Academic', 'Statistics', 'Use descriptive statistics and basic inference to interpret data.', 3],
    ['Academic', 'Problem Solving', 'Break complex problems into clear, testable solution steps.', 4],
    ['Organisational', 'Project Planning', 'Break work into milestones, owners, timelines, and risks.', 3],
    ['Organisational', 'Agile Methodology', 'Plan and deliver iterative work with sprints, ceremonies, and feedback.', 3],
];

$roles = [
    ['Web Developer', 'Career', 'Build modern web applications using PHP, databases, JavaScript, UI frameworks, version control, and APIs.'],
    ['Data Analyst', 'Career', 'Analyse datasets, prepare reports, and communicate evidence-based insights.'],
    ['IT Support', 'Career', 'Provide technical support, troubleshooting, network basics, and user-facing service.'],
    ['Software Engineer', 'Career', 'Design, build, test, and maintain reliable software systems.'],
    ['Cybersecurity Analyst', 'Career', 'Monitor risks, protect systems, and respond to security incidents.'],
    ['UI/UX Designer', 'Career', 'Design useful, accessible, and polished digital product experiences.'],
    ['Database Administrator', 'Career', 'Maintain secure, reliable, and high-performing database systems.'],
    ['Business Analyst', 'Career', 'Bridge stakeholder needs, process improvements, and technical delivery.'],
    ['Student Club President', 'Lead', 'Lead student organisations through coordination, communication, events, and service.'],
    ['Project Manager', 'Lead', 'Coordinate project scope, timelines, risks, stakeholders, and delivery.'],
    ['Team Leader', 'Lead', 'Guide team communication, ownership, planning, and problem solving.'],
];

$benchmarks = [
    'Web Developer' => [['PHP', 4, 'Critical'], ['MySQL', 4, 'Critical'], ['JavaScript', 4, 'Critical'], ['Bootstrap 5', 3, 'Important'], ['Git', 3, 'Important'], ['API Integration', 4, 'Important'], ['Project Planning', 3, 'Optional']],
    'Data Analyst' => [['MySQL', 4, 'Critical'], ['Python', 4, 'Critical'], ['Statistics', 4, 'Critical'], ['Data Visualization', 4, 'Important'], ['Research Writing', 3, 'Important']],
    'IT Support' => [['Troubleshooting', 4, 'Critical'], ['Network Basics', 4, 'Critical'], ['Customer Support', 3, 'Important'], ['MySQL', 2, 'Optional'], ['Git', 2, 'Optional']],
    'Software Engineer' => [['JavaScript', 4, 'Critical'], ['Git', 4, 'Critical'], ['API Integration', 4, 'Important'], ['Problem Solving', 4, 'Critical'], ['Cloud Fundamentals', 3, 'Important'], ['Agile Methodology', 3, 'Important']],
    'Cybersecurity Analyst' => [['Cybersecurity Basics', 4, 'Critical'], ['Network Basics', 4, 'Critical'], ['Troubleshooting', 4, 'Important'], ['Problem Solving', 4, 'Important'], ['Research Writing', 3, 'Optional']],
    'UI/UX Designer' => [['UI/UX Design', 4, 'Critical'], ['Public Speaking', 3, 'Important'], ['Requirements Analysis', 4, 'Critical'], ['Bootstrap 5', 3, 'Important'], ['Research Writing', 3, 'Optional']],
    'Database Administrator' => [['MySQL', 4, 'Critical'], ['Database Administration', 4, 'Critical'], ['Troubleshooting', 4, 'Important'], ['Cybersecurity Basics', 3, 'Important'], ['Cloud Fundamentals', 3, 'Optional']],
    'Business Analyst' => [['Requirements Analysis', 4, 'Critical'], ['Data Visualization', 3, 'Important'], ['Public Speaking', 3, 'Important'], ['Project Planning', 3, 'Important'], ['Research Writing', 4, 'Critical']],
    'Student Club President' => [['Team Coordination', 4, 'Critical'], ['Event Planning', 4, 'Critical'], ['Public Speaking', 4, 'Important'], ['Project Planning', 3, 'Important'], ['Customer Support', 3, 'Optional']],
    'Project Manager' => [['Project Planning', 4, 'Critical'], ['Agile Methodology', 4, 'Critical'], ['Team Coordination', 4, 'Critical'], ['Public Speaking', 3, 'Important'], ['Requirements Analysis', 3, 'Important']],
    'Team Leader' => [['Team Coordination', 4, 'Critical'], ['Project Planning', 4, 'Important'], ['Public Speaking', 3, 'Important'], ['Agile Methodology', 3, 'Important'], ['Problem Solving', 4, 'Critical']],
];

$resources = [
    ['PHP', 'PHP Backend Fundamentals', 'W3Schools', 'https://www.w3schools.com/php/', 6, 1],
    ['MySQL', 'SQL and Database Practice', 'W3Schools', 'https://www.w3schools.com/mysql/', 5, 1],
    ['JavaScript', 'JavaScript Guide', 'MDN', 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide', 8, 1],
    ['Bootstrap 5', 'Bootstrap Layout and Components', 'Bootstrap Docs', 'https://getbootstrap.com/docs/5.3/getting-started/introduction/', 4, 1],
    ['Git', 'Git Branching and Collaboration', 'Atlassian', 'https://www.atlassian.com/git/tutorials', 3, 1],
    ['API Integration', 'Build and Consume REST APIs', 'freeCodeCamp', 'https://www.freecodecamp.org/news/tag/api/', 7, 1],
    ['Python', 'Python for Everybody', 'Coursera', 'https://www.coursera.org/specializations/python', 12, 0],
    ['Data Visualization', 'Data Visualization Fundamentals', 'Tableau', 'https://www.tableau.com/learn/training', 5, 1],
    ['Statistics', 'Introductory Statistics', 'Khan Academy', 'https://www.khanacademy.org/math/statistics-probability', 10, 1],
    ['Team Coordination', 'Team Leadership Basics', 'LinkedIn Learning', 'https://www.linkedin.com/learning/', 4, 0],
    ['Event Planning', 'Event Planning Checklist', 'Coursera', 'https://www.coursera.org/', 4, 0],
    ['Public Speaking', 'Public Speaking Skills', 'Toastmasters', 'https://www.toastmasters.org/resources/public-speaking-tips', 3, 1],
    ['Project Planning', 'Project Planning Foundations', 'Coursera', 'https://www.coursera.org/', 6, 0],
    ['Agile Methodology', 'Agile Project Management', 'Atlassian', 'https://www.atlassian.com/agile', 5, 1],
    ['Cybersecurity Basics', 'Cybersecurity Fundamentals', 'Cisco Skills for All', 'https://skillsforall.com/', 8, 1],
    ['Cloud Fundamentals', 'AWS Cloud Practitioner Essentials', 'AWS Skill Builder', 'https://skillbuilder.aws/', 6, 1],
    ['Database Administration', 'Database Administration Basics', 'Oracle Dev Gym', 'https://devgym.oracle.com/', 7, 1],
    ['UI/UX Design', 'UX Design Basics', 'Google UX Design', 'https://www.coursera.org/professional-certificates/google-ux-design', 12, 0],
    ['Requirements Analysis', 'Requirements Gathering', 'LinkedIn Learning', 'https://www.linkedin.com/learning/', 5, 0],
    ['Problem Solving', 'Computational Problem Solving', 'Khan Academy', 'https://www.khanacademy.org/computing', 5, 1],
];

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
        if (strlen($letters) >= 3) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'SM';
}

function fetchId(PDO $pdo, string $table, string $name): int
{
    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE name = :name LIMIT 1");
    $stmt->execute(['name' => $name]);
    $id = (int) ($stmt->fetchColumn() ?: 0);
    if ($id <= 0) {
        throw new RuntimeException("Missing {$table} row: {$name}");
    }

    return $id;
}

function upsertUser(PDO $pdo, string $username, string $name, string $role, string $programme, string $year, string $gender): int
{
    $email = $username . '@gmail.com';
    $icon = in_array($role, ['admin', 'lecturer', 'staff'], true)
        ? 'profileicons/icons8-administrator-male-100.png'
        : ($gender === 'female' ? 'profileicons/icons8-add-user-female-skin-type-7-100.png' : 'profileicons/icons8-add-user-male-100.png');

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, username, email, password_hash, role, programme, year_level, avatar_initials, gender, profile_icon, status)
         VALUES (:name, :username, :email, :password_hash, :role, :programme, :year_level, :avatar_initials, :gender, :profile_icon, "Active")
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            email = VALUES(email),
            password_hash = VALUES(password_hash),
            role = VALUES(role),
            programme = VALUES(programme),
            year_level = VALUES(year_level),
            avatar_initials = VALUES(avatar_initials),
            gender = VALUES(gender),
            profile_icon = VALUES(profile_icon),
            status = "Active"'
    );
    $stmt->execute([
        'name' => $name,
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($username . '@123', PASSWORD_BCRYPT),
        'role' => $role,
        'programme' => $programme,
        'year_level' => $year,
        'avatar_initials' => initials($name),
        'gender' => $gender,
        'profile_icon' => $icon,
    ]);

    $idStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $idStmt->execute(['username' => $username]);
    return (int) $idStmt->fetchColumn();
}

function createAnalysis(PDO $pdo, int $userId, int $roleId, array $benchmarkRows, array $ratings, DateTimeImmutable $createdAt, string $note): float
{
    $scoreEarned = 0;
    $scoreRequired = 0;
    $results = [];
    $summary = ['Have' => 0, 'Partial' => 0, 'Missing' => 0];
    $missing = [];

    foreach ($benchmarkRows as $row) {
        $yourRating = (int) ($ratings[$row['skill_name']] ?? 0);
        $requiredRating = (int) $row['required_rating'];
        $gap = max($requiredRating - $yourRating, 0);
        $status = $yourRating >= $requiredRating ? 'Have' : ($gap <= 1 ? 'Partial' : 'Missing');
        $scoreEarned += min($yourRating, $requiredRating);
        $scoreRequired += $requiredRating;
        $summary[$status]++;
        if ($status === 'Missing') {
            $missing[] = $row['skill_name'];
        }
        $results[] = [$row['skill_id'], $status, $yourRating, $requiredRating, $gap];
    }

    $matchScore = $scoreRequired > 0 ? round(($scoreEarned / $scoreRequired) * 100, 2) : 0.0;
    $summaryText = sprintf(
        'Profile analysis: %.2f%% match. Have %d, Partial %d, Missing %d. %s',
        $matchScore,
        $summary['Have'],
        $summary['Partial'],
        $summary['Missing'],
        $note
    );
    if ($missing !== []) {
        $summaryText .= ' Focus on: ' . implode(', ', array_slice($missing, 0, 3)) . '.';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO analyses (user_id, target_role_id, match_score, ai_summary, created_at)
         VALUES (:user_id, :target_role_id, :match_score, :ai_summary, :created_at)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'target_role_id' => $roleId,
        'match_score' => $matchScore,
        'ai_summary' => $summaryText,
        'created_at' => $createdAt->format('Y-m-d H:i:s'),
    ]);
    $analysisId = (int) $pdo->lastInsertId();

    $resultStmt = $pdo->prepare(
        'INSERT INTO analysis_results (analysis_id, skill_id, status, your_rating, required_rating, gap_value)
         VALUES (:analysis_id, :skill_id, :status, :your_rating, :required_rating, :gap_value)'
    );
    foreach ($results as [$skillId, $status, $yourRating, $requiredRating, $gap]) {
        $resultStmt->execute([
            'analysis_id' => $analysisId,
            'skill_id' => $skillId,
            'status' => $status,
            'your_rating' => $yourRating,
            'required_rating' => $requiredRating,
            'gap_value' => $gap,
        ]);
    }

    return $matchScore;
}

$pdo->beginTransaction();

try {
    foreach ($categories as $category) {
        $stmt = $pdo->prepare(
            'INSERT INTO skill_categories (name, type, icon)
             VALUES (:name, :type, :icon)
             ON DUPLICATE KEY UPDATE type = VALUES(type), icon = VALUES(icon)'
        );
        $stmt->execute(['name' => $category[0], 'type' => $category[1], 'icon' => $category[2]]);
    }

    foreach ($skills as $skill) {
        $stmt = $pdo->prepare(
            'INSERT INTO skills (category_id, name, description, difficulty, status)
             VALUES (:category_id, :name, :description, :difficulty, "Active")
             ON DUPLICATE KEY UPDATE
                category_id = VALUES(category_id),
                description = VALUES(description),
                difficulty = VALUES(difficulty),
                status = "Active"'
        );
        $stmt->execute([
            'category_id' => fetchId($pdo, 'skill_categories', $skill[0]),
            'name' => $skill[1],
            'description' => $skill[2],
            'difficulty' => $skill[3],
        ]);
    }

    foreach ($roles as $role) {
        $stmt = $pdo->prepare(
            'INSERT INTO career_roles (name, type, description)
             VALUES (:name, :type, :description)
             ON DUPLICATE KEY UPDATE type = VALUES(type), description = VALUES(description)'
        );
        $stmt->execute(['name' => $role[0], 'type' => $role[1], 'description' => $role[2]]);
    }

    $managedRoleIds = [];
    foreach (array_keys($benchmarks) as $roleName) {
        $managedRoleIds[] = fetchId($pdo, 'career_roles', $roleName);
    }
    $rolePlaceholders = implode(',', array_fill(0, count($managedRoleIds), '?'));
    $pdo->prepare("DELETE FROM role_skill_benchmarks WHERE role_id IN ({$rolePlaceholders})")->execute($managedRoleIds);

    $benchmarkStmt = $pdo->prepare(
        'INSERT INTO role_skill_benchmarks (role_id, skill_id, required_rating, priority)
         VALUES (:role_id, :skill_id, :required_rating, :priority)'
    );
    foreach ($benchmarks as $roleName => $rows) {
        $roleId = fetchId($pdo, 'career_roles', $roleName);
        foreach ($rows as $row) {
            $skillId = fetchId($pdo, 'skills', $row[0]);
            $benchmarkStmt->execute([
                'role_id' => $roleId,
                'skill_id' => $skillId,
                'required_rating' => $row[1],
                'priority' => $row[2],
            ]);
        }
    }

    $managedResourceSkillIds = [];
    foreach ($resources as $resource) {
        $managedResourceSkillIds[] = fetchId($pdo, 'skills', $resource[0]);
    }
    $managedResourceSkillIds = array_values(array_unique($managedResourceSkillIds));
    $skillPlaceholders = implode(',', array_fill(0, count($managedResourceSkillIds), '?'));
    $pdo->prepare("DELETE FROM learning_resources WHERE skill_id IN ({$skillPlaceholders})")->execute($managedResourceSkillIds);

    $resourceStmt = $pdo->prepare(
        'INSERT INTO learning_resources (skill_id, title, platform, url, duration_hours, is_free)
         VALUES (:skill_id, :title, :platform, :url, :duration_hours, :is_free)'
    );
    foreach ($resources as $resource) {
        $resourceStmt->execute([
            'skill_id' => fetchId($pdo, 'skills', $resource[0]),
            'title' => $resource[1],
            'platform' => $resource[2],
            'url' => $resource[3],
            'duration_hours' => $resource[4],
            'is_free' => $resource[5],
        ]);
    }

    $studentIds = [];
    foreach ($testStudents as $student) {
        $studentIds[$student[0]] = upsertUser($pdo, $student[0], $student[1], 'student', $student[2], $student[3], $student[4]);
    }
    foreach ($staffUsers as $staff) {
        upsertUser($pdo, $staff[0], $staff[1], 'staff', 'Faculty Administration', 'Staff', 'male');
    }
    foreach ($lecturerUsers as $lecturer) {
        upsertUser($pdo, $lecturer[0], $lecturer[1], 'lecturer', 'Information Systems', 'Staff', 'male');
    }

    $testUserIds = array_values($studentIds);
    $placeholders = implode(',', array_fill(0, count($testUserIds), '?'));
    foreach (['user_skill_ratings', 'user_roadmap_progress', 'user_credentials', 'user_badges', 'learning_streaks'] as $table) {
        $pdo->prepare("DELETE FROM {$table} WHERE user_id IN ({$placeholders})")->execute($testUserIds);
    }
    $pdo->prepare("DELETE FROM analyses WHERE user_id IN ({$placeholders})")->execute($testUserIds);

    $ratingStmt = $pdo->prepare(
        'INSERT INTO user_skill_ratings (user_id, skill_id, rating, notes, updated_at)
         VALUES (:user_id, :skill_id, :rating, :notes, NOW())'
    );
    $credentialStmt = $pdo->prepare(
        'INSERT INTO user_credentials (user_id, entry_type, title, issuer, notes, earned_at)
         VALUES (:user_id, :entry_type, :title, :issuer, :notes, :earned_at)'
    );
    $streakStmt = $pdo->prepare(
        'INSERT INTO learning_streaks (user_id, current_streak, best_streak, last_activity)
         VALUES (:user_id, :current_streak, :best_streak, :last_activity)'
    );
    $roadmapStmt = $pdo->prepare(
        'INSERT INTO user_roadmap_progress (user_id, skill_id, status, progress_pct, started_at, completed_at)
         VALUES (:user_id, :skill_id, :status, :progress_pct, :started_at, :completed_at)'
    );

    $today = new DateTimeImmutable('today');
    $expected = [];
    foreach ($testStudents as $index => $student) {
        [$username, , , , , $targetRole, $ratings, $note] = $student;
        $userId = $studentIds[$username];

        foreach ($ratings as $skillName => $rating) {
            $ratingStmt->execute([
                'user_id' => $userId,
                'skill_id' => fetchId($pdo, 'skills', $skillName),
                'rating' => $rating,
                'notes' => 'Self-assessment evidence for ' . $skillName,
            ]);
        }

        $credentialStmt->execute([
            'user_id' => $userId,
            'entry_type' => 'Skill',
            'title' => $targetRole . ' evidence',
            'issuer' => 'UTM SkillMap',
            'notes' => $note,
            'earned_at' => $today->modify('-' . (10 + $index) . ' days')->format('Y-m-d'),
        ]);

        $streakStmt->execute([
            'user_id' => $userId,
            'current_streak' => 1 + ($index % 9),
            'best_streak' => 4 + ($index % 12),
            'last_activity' => $today->modify('-' . ($index % 5) . ' days')->format('Y-m-d'),
        ]);

        $roleId = fetchId($pdo, 'career_roles', $targetRole);
        $benchmarkRowsStmt = $pdo->prepare(
            'SELECT rb.skill_id, rb.required_rating, s.name AS skill_name
             FROM role_skill_benchmarks rb
             INNER JOIN skills s ON s.id = rb.skill_id
             WHERE rb.role_id = :role_id
             ORDER BY rb.id'
        );
        $benchmarkRowsStmt->execute(['role_id' => $roleId]);
        $benchmarkRows = $benchmarkRowsStmt->fetchAll();

        $createdAt = $today->modify('-' . (45 - ($index * 3)) . ' days')->setTime(9 + ($index % 8), 15);
        $score = createAnalysis($pdo, $userId, $roleId, $benchmarkRows, $ratings, $createdAt, $note);
        $expected[] = [$username, $targetRole, $score];

        foreach ($benchmarkRows as $row) {
            $rating = (int) ($ratings[$row['skill_name']] ?? 0);
            $required = (int) $row['required_rating'];
            if ($rating < $required) {
                $gap = $required - $rating;
                $roadmapStmt->execute([
                    'user_id' => $userId,
                    'skill_id' => $row['skill_id'],
                    'status' => $gap <= 1 ? 'Partial' : 'Missing',
                    'progress_pct' => $gap <= 1 ? 50 : 0,
                    'started_at' => $gap <= 1 ? $today->modify('-7 days')->format('Y-m-d') : null,
                    'completed_at' => null,
                ]);
            }
        }
    }

    $seedNotificationTitles = [
        'FYP2 validation cohort ready',
        'UAT reminder',
        'Analytics validation data',
        'Demo cohort ready',
        'Analytics validation data',
    ];
    $notificationPlaceholders = implode(',', array_fill(0, count($seedNotificationTitles), '?'));
    $pdo->prepare("DELETE FROM notifications WHERE title IN ({$notificationPlaceholders})")->execute($seedNotificationTitles);

    $notificationStmt = $pdo->prepare(
        'INSERT INTO notifications (sender_role, recipient_role, notification_type, title, body)
         VALUES (:sender_role, :recipient_role, :notification_type, :title, :body)'
    );
    foreach ([
        ['admin', 'student', 'info', 'Validation cohort ready', 'Use student01 to student15 for career and leadership skill-gap reviews.'],
        ['lecturer', 'student', 'reminder', 'Validation session reminder', 'Please complete the role-specific UAT scenario and SUS questionnaire after the validation session.'],
        ['staff', 'lecturer', 'message', 'Analytics validation data', 'The FYP2 validation cohort contains known match percentages for dashboard verification.'],
    ] as $notification) {
        $notificationStmt->execute([
            'sender_role' => $notification[0],
            'recipient_role' => $notification[1],
            'notification_type' => $notification[2],
            'title' => $notification[3],
            'body' => $notification[4],
        ]);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, 'FYP2 data seed failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

echo 'FYP2 data seed completed.' . PHP_EOL;
echo 'Accounts created: 15 students, 5 staff, 3 lecturers.' . PHP_EOL;
echo 'Password rule: username@123, for example student01@123.' . PHP_EOL;
echo 'Expected latest analysis scores:' . PHP_EOL;
foreach ($expected as [$username, $role, $score]) {
    echo sprintf('- %s / %s / %.2f%%', $username, $role, $score) . PHP_EOL;
}
