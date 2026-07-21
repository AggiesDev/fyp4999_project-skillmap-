<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/dbconnect.php';

function skillmap_profile_icon_options(): array
{
    return [
        'male' => [
            'profileicons/icons8-add-user-male-100.png',
            'profileicons/icons8-administrator-male-100.png',
            'profileicons/icons8-android-user-100.png',
            'profileicons/icons8-person-100.png',
            'profileicons/icons8-checked-user-male-100.png',
        ],
        'female' => [
            'profileicons/icons8-add-user-female-skin-type-7-100.png',
            'profileicons/icons8-profile-100-2.png',
            'profileicons/icons8-profile-100-3.png',
            'profileicons/icons8-profile-100-5.gif',
            'profileicons/icons8-apple-user-100.png',
        ],
        'neutral' => [
            'profileicons/icons8-profile-100.png',
            'profileicons/icons8-test-account-100.png',
            'profileicons/icons8-person-100-2.png',
            'profileicons/icons8-add-user-100.png',
            'profileicons/icons8-checked-user-100.png',
        ],
    ];
}

function skillmap_normalize_gender(string $gender): string
{
    $gender = strtolower(trim($gender));
    return in_array($gender, ['male', 'female'], true) ? $gender : 'male';
}

function skillmap_default_profile_icon(string $gender = 'male', string $role = 'student'): string
{
    $gender = skillmap_normalize_gender($gender);
    $role = strtolower(trim($role));

    if (in_array($role, ['admin', 'staff', 'lecturer'], true) && $gender === 'male') {
        return 'profileicons/icons8-administrator-male-100.png';
    }

    return $gender === 'female'
        ? 'profileicons/icons8-add-user-female-skin-type-7-100.png'
        : 'profileicons/icons8-add-user-male-100.png';
}

function skillmap_sanitize_profile_icon(string $icon, string $gender = 'male', string $role = 'student'): string
{
    $icon = trim($icon);
    $allowed = [];
    foreach (skillmap_profile_icon_options() as $group) {
        $allowed = array_merge($allowed, $group);
    }

    return in_array($icon, $allowed, true) ? $icon : skillmap_default_profile_icon($gender, $role);
}

function auth_session_user(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'username' => (string) ($user['username'] ?? $user['email']),
        'role' => (string) $user['role'],
        'programme' => (string) $user['programme'],
        'year' => (string) $user['year_level'],
        'initials' => (string) $user['avatar_initials'],
        'gender' => (string) ($user['gender'] ?? 'male'),
        'profile_icon' => (string) ($user['profile_icon'] ?? skillmap_default_profile_icon((string) ($user['gender'] ?? 'male'), (string) $user['role'])),
        'status' => (string) $user['status'],
    ];
}

function set_authenticated_user(array $user): array
{
    $sessionUser = auth_session_user($user);

    $_SESSION['user_id'] = $sessionUser['id'];
    $_SESSION['role'] = $sessionUser['role'];
    $_SESSION['name'] = $sessionUser['name'];
    $_SESSION['avatar_initials'] = $sessionUser['initials'];
    $_SESSION['profile_icon'] = $sessionUser['profile_icon'];
    $_SESSION['user'] = $sessionUser;

    return $sessionUser;
}

function current_user(): ?array
{
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, name, username, email, role, programme, year_level, avatar_initials, gender, profile_icon, status FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ? set_authenticated_user($user) : null;
}

function login(string $emailOrUsername, string $password): ?array
{
    global $pdo;

    $loginKey = trim($emailOrUsername);
    if ($loginKey === '' || $password === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, name, username, email, password_hash, role, programme, year_level, avatar_initials, gender, profile_icon, status
         FROM users
         WHERE email = :email_login OR username = :username_login
         LIMIT 1'
    );
    $stmt->execute([
        'email_login' => $loginKey,
        'username_login' => $loginKey,
    ]);
    $user = $stmt->fetch();

    if (!$user || (string) $user['status'] !== 'Active') {
        return null;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        return null;
    }

    return set_authenticated_user($user);
}

function register(string $name, string $username, string $email, string $password, string $programme, string $yearLevel, string $role = 'student', string $status = 'Active', array $approvalRequests = [], string $gender = 'male', string $profileIcon = ''): ?array
{
    global $pdo;

    $name = trim($name);
    $username = trim($username);
    $email = trim($email);
    $programme = trim($programme);
    $yearLevel = trim($yearLevel);

    $role = in_array($role, ['student', 'lecturer'], true) ? $role : 'student';
    $status = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
    $gender = skillmap_normalize_gender($gender);
    $profileIcon = skillmap_sanitize_profile_icon($profileIcon, $gender, $role);

    if ($name === '' || $email === '' || $password === '' || $programme === '' || $yearLevel === '') {
        return null;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $username = $username !== '' ? $username : $email;

    $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
    $duplicate->execute(['email' => $email, 'username' => $username]);
    if ($duplicate->fetch()) {
        return null;
    }

    $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'SM', 0, 2));

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO users (name, username, email, password_hash, role, programme, year_level, avatar_initials, gender, profile_icon, status)
             VALUES (:name, :username, :email, :password_hash, :role, :programme, :year_level, :avatar_initials, :gender, :profile_icon, :status)'
        );
        $stmt->execute([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'programme' => $programme,
            'year_level' => $yearLevel,
            'avatar_initials' => $initials,
            'gender' => $gender,
            'profile_icon' => $profileIcon,
            'status' => $status,
        ]);

        $userId = (int) $pdo->lastInsertId();
        $requestStmt = $pdo->prepare(
            'INSERT INTO account_approval_requests (user_id, request_type, requested_value)
             VALUES (:user_id, :request_type, :requested_value)'
        );
        foreach (['programme', 'role'] as $requestType) {
            $requestedValue = trim((string) ($approvalRequests[$requestType] ?? ''));
            if ($requestedValue !== '') {
                $requestStmt->execute([
                    'user_id' => $userId,
                    'request_type' => $requestType,
                    'requested_value' => $requestedValue,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return null;
    }

    $user = [
        'id' => $userId,
        'name' => $name,
        'username' => $username,
        'email' => $email,
        'role' => $role,
        'programme' => $programme,
        'year_level' => $yearLevel,
        'avatar_initials' => $initials,
        'gender' => $gender,
        'profile_icon' => $profileIcon,
        'status' => $status,
    ];

    return $status === 'Active' ? set_authenticated_user($user) : $user;
}

function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /fyp_skillmapsystem/login.php');
        exit;
    }
}

function require_role(array $roles): void
{
    require_login();
    $user = current_user();

    if (!$user || !in_array((string) $user['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function has_permission(string $permissionName): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    if (($user['role'] ?? '') === 'admin') {
        return true;
    }

    global $pdo;
    $userPermissionStmt = $pdo->prepare(
        'SELECT 1
         FROM user_permissions up
         INNER JOIN permissions p ON p.id = up.permission_id
         WHERE up.user_id = :user_id AND up.enabled = 1 AND p.name = :permission
         LIMIT 1'
    );
    $userPermissionStmt->execute([
        'user_id' => (int) $user['id'],
        'permission' => $permissionName,
    ]);
    if ($userPermissionStmt->fetchColumn()) {
        return true;
    }

    $stmt = $pdo->prepare(
        'SELECT 1
         FROM access_roles ar
         INNER JOIN access_role_permissions arp ON arp.role_id = ar.id AND arp.enabled = 1
         INNER JOIN permissions p ON p.id = arp.permission_id
         WHERE ar.name = :role AND p.name = :permission
         LIMIT 1'
    );
    $stmt->execute([
        'role' => (string) $user['role'],
        'permission' => $permissionName,
    ]);

    return (bool) $stmt->fetchColumn();
}
