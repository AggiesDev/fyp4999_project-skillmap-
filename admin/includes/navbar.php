<?php
// Shared admin navbar for analytics and management pages.

$activePage = $activePage ?? '';
$user = skillmap_current_user() ?? ['name' => 'Admin', 'initials' => 'LMY'];
$userRole = $user['role'] ?? 'admin';
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-sm-navy admin-nav px-3 px-lg-4">
  <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/fyp_skillmapsystem/admin/analytics.php">
    <span class="skillmap-brand-mark" style="width:40px;height:40px;border-radius:12px;font-size:.95rem"><i class="bi bi-map-fill"></i></span>
    <span>Skill Map</span>
    <span class="badge rounded-pill text-bg-secondary">Admin</span>
  </a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="adminNavbar">
    <ul class="navbar-nav mx-auto gap-1 my-3 my-lg-0">
      <?php if ($userRole === 'admin'): ?>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'analytics' ? 'active' : '' ?>" href="/fyp_skillmapsystem/admin/analytics.php">Analytics</a></li>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'categories' ? 'active' : '' ?>" href="/fyp_skillmapsystem/admin/categories.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'skill_library' ? 'active' : '' ?>" href="/fyp_skillmapsystem/admin/skill_library.php">Skill Library</a></li>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'benchmarks' ? 'active' : '' ?>" href="/fyp_skillmapsystem/admin/benchmarks.php">Benchmarks</a></li>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>" href="/fyp_skillmapsystem/admin/users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'reviews' ? 'active' : '' ?>" href="/fyp_skillmapsystem/admin/reviews.php">Student Reviews</a></li>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'permissions' ? 'active' : '' ?>" href="/fyp_skillmapsystem/admin/permissions.php">Permissions</a></li>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link <?= $activePage === 'reviews' ? 'active' : '' ?>" href="/fyp_skillmapsystem/admin/reviews.php">Student Reviews</a></li>
      <?php endif; ?>
    </ul>
    <div class="d-flex align-items-center gap-3 text-white">
      <a class="nav-link" href="/fyp_skillmapsystem/logout.php">Logout</a>
      <span class="avatar-circle bg-purple bg-opacity-25"><?= htmlspecialchars($user['initials'] ?? 'LMY', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>
</nav>
