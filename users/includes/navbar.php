<?php
// Shared student navbar with navigation, notifications, and user avatar.

$activePage = $activePage ?? '';
$user = skillmap_current_user() ?? ['name' => 'Chong Pei', 'initials' => 'CP'];
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-sm-primary px-3 px-lg-4 shadow-sm">
  <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/fyp_skillmapsystem/users/dashboard.php">
    <span class="skillmap-brand-mark" style="width:40px;height:40px;border-radius:12px;font-size:.95rem"><i class="bi bi-map-fill"></i></span>
    <span>Skill Map</span>
  </a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNavbar"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="studentNavbar">
    <ul class="navbar-nav mx-auto gap-1 my-3 my-lg-0 align-items-lg-center">
      <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="/fyp_skillmapsystem/users/dashboard.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $activePage === 'profile' ? 'active' : '' ?>" href="/fyp_skillmapsystem/users/profile.php">My Profile</a></li>
      <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $activePage === 'analyse' ? 'active' : '' ?>" href="/fyp_skillmapsystem/users/analyse.php">Analyse</a></li>
      <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $activePage === 'progress' ? 'active' : '' ?>" href="/fyp_skillmapsystem/users/progress.php">Progress</a></li>
    </ul>
    <div class="d-flex align-items-center gap-3 text-white">
      <a class="nav-link d-flex align-items-center gap-1" href="/fyp_skillmapsystem/users/progress.php"><i class="bi bi-bell"></i></a>
      <a class="nav-link d-flex align-items-center gap-1" href="/fyp_skillmapsystem/logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
      <span class="avatar-circle"><?= htmlspecialchars($user['initials'] ?? 'CP', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>
</nav>
