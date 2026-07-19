CREATE DATABASE IF NOT EXISTS skill_map_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skill_map_system;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  username VARCHAR(120) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(80) NOT NULL DEFAULT 'student',
  programme VARCHAR(150) NOT NULL,
  year_level VARCHAR(50) NOT NULL,
  avatar_initials VARCHAR(8) NOT NULL,
  gender ENUM('male','female') NOT NULL DEFAULT 'male',
  profile_icon VARCHAR(190) NOT NULL DEFAULT 'profileicons/icons8-add-user-male-100.png',
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  type ENUM('Career','Lead') NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  description TEXT NULL
);

CREATE TABLE IF NOT EXISTS access_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS access_role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_access_role_permissions_role FOREIGN KEY (role_id) REFERENCES access_roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_access_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_user_id INT NULL,
  sender_role ENUM('admin', 'lecturer', 'staff') NULL,
  recipient_user_id INT NULL,
  recipient_role ENUM('admin', 'student', 'lecturer', 'staff', 'all') NOT NULL DEFAULT 'all',
  notification_type ENUM('message', 'info', 'alert', 'reminder') NOT NULL DEFAULT 'message',
  title VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_notifications_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_credentials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  entry_type ENUM('Skill', 'Certification') NOT NULL,
  title VARCHAR(190) NOT NULL,
  issuer VARCHAR(190) NULL,
  notes TEXT NULL,
  earned_at DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_credentials_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS skill_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  type ENUM('Skill Category','Target Role') NOT NULL DEFAULT 'Skill Category',
  icon VARCHAR(60) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS skills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(150) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  difficulty TINYINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_skills_category FOREIGN KEY (category_id) REFERENCES skill_categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS career_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  type ENUM('Career','Lead') NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS role_skill_benchmarks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  skill_id INT NOT NULL,
  required_rating TINYINT UNSIGNED NOT NULL,
  priority ENUM('Critical','Important','Optional') NOT NULL DEFAULT 'Important',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_role_skill_benchmarks_role FOREIGN KEY (role_id) REFERENCES career_roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_skill_benchmarks_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_skill_ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  skill_id INT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_skill (user_id, skill_id),
  CONSTRAINT fk_user_skill_ratings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_skill_ratings_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS analyses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  target_role_id INT NOT NULL,
  match_score DECIMAL(5,2) NOT NULL,
  ai_summary TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_analyses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_analyses_role FOREIGN KEY (target_role_id) REFERENCES career_roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS analysis_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  analysis_id INT NOT NULL,
  skill_id INT NOT NULL,
  status ENUM('Have','Partial','Missing') NOT NULL,
  your_rating TINYINT UNSIGNED NOT NULL,
  required_rating TINYINT UNSIGNED NOT NULL,
  gap_value TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_analysis_results_analysis FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
  CONSTRAINT fk_analysis_results_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS learning_resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  skill_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  platform VARCHAR(120) NOT NULL,
  url VARCHAR(500) NOT NULL,
  duration_hours DECIMAL(5,2) NOT NULL DEFAULT 0,
  is_free TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_learning_resources_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_roadmap_progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  skill_id INT NOT NULL,
  status ENUM('Missing','Partial','Completed') NOT NULL,
  progress_pct TINYINT UNSIGNED NOT NULL DEFAULT 0,
  started_at DATE NULL,
  completed_at DATE NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_roadmap_skill (user_id, skill_id),
  CONSTRAINT fk_roadmap_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_roadmap_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  tier ENUM('bronze','silver','gold') NOT NULL,
  description TEXT NULL,
  icon VARCHAR(60) NULL
);

CREATE TABLE IF NOT EXISTS user_badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  earned_at DATE NOT NULL,
  CONSTRAINT fk_user_badges_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_badges_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS learning_streaks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  current_streak INT NOT NULL DEFAULT 0,
  best_streak INT NOT NULL DEFAULT 0,
  last_activity DATE NULL,
  CONSTRAINT fk_learning_streaks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO permissions (name, description) VALUES
('view_admin_dashboard', 'View the main admin dashboard'),
('manage_users', 'Create, update, and deactivate user accounts'),
('manage_roles', 'Maintain target roles and skill benchmarks'),
('manage_skills', 'Maintain the skill library and categories'),
('review_student_skills', 'Review and edit student skill ratings'),
('manage_permissions', 'Manage access for system roles'),
('send_notifications', 'Send notifications and messages to users')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO access_roles (name, description) VALUES
('admin', 'Full system access'),
('lecturer', 'Review and support student skills'),
('staff', 'Operational support access'),
('student', 'Personal skill tracking access')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO access_role_permissions (role_id, permission_id, enabled)
SELECT ar.id, p.id, 1
FROM access_roles ar
JOIN permissions p
WHERE ar.name = 'admin'
ON DUPLICATE KEY UPDATE enabled = VALUES(enabled);

INSERT INTO access_role_permissions (role_id, permission_id, enabled)
SELECT ar.id, p.id, 1
FROM access_roles ar
JOIN permissions p
WHERE ar.name IN ('lecturer', 'staff') AND p.name IN ('view_admin_dashboard', 'review_student_skills', 'send_notifications')
ON DUPLICATE KEY UPDATE enabled = VALUES(enabled);

INSERT INTO access_role_permissions (role_id, permission_id, enabled)
SELECT ar.id, p.id, 1
FROM access_roles ar
JOIN permissions p
WHERE ar.name = 'student' AND p.name = 'view_admin_dashboard'
ON DUPLICATE KEY UPDATE enabled = VALUES(enabled);

INSERT INTO roles (name, type, description) VALUES
('Web Developer', 'Career', 'Build modern web applications using PHP and JavaScript'),
('Data Analyst', 'Career', 'Analyse data and communicate insights'),
('IT Support', 'Career', 'Support users with hardware and software issues'),
('Student Club President', 'Lead', 'Coordinate and lead student organisation activities')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO skill_categories (name, type, icon) VALUES
('Technical', 'Skill Category', 'bi-code-square'),
('Leadership', 'Skill Category', 'bi-people-fill'),
('Interpersonal', 'Skill Category', 'bi-chat-dots-fill'),
('Academic', 'Skill Category', 'bi-journal-text'),
('Organisational', 'Skill Category', 'bi-folder-check'),
('Web Developer', 'Target Role', 'bi-code-slash'),
('Data Analyst', 'Target Role', 'bi-bar-chart-line'),
('IT Support', 'Target Role', 'bi-pc-display')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO skills (category_id, name, description, difficulty, status) VALUES
(1, 'PHP', 'Server-side scripting for backend logic.', 3, 'Active'),
(1, 'MySQL', 'Structured querying and relational database design.', 3, 'Active'),
(1, 'JavaScript', 'Interactive client-side application behaviour.', 3, 'Active'),
(1, 'Bootstrap 5', 'Responsive layout and UI components.', 2, 'Active'),
(1, 'Git', 'Version control and collaboration workflow.', 2, 'Active'),
(1, 'API Integration', 'Connect systems through web APIs.', 4, 'Active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO badges (name, tier, description, icon) VALUES
('Bronze Explorer', 'bronze', 'Completed first gap analysis', 'bi-award'),
('Skill Builder', 'silver', 'Improved three core skills', 'bi-stars'),
('Roadmap Runner', 'gold', 'Completed a full learning roadmap', 'bi-trophy')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (name, username, email, password_hash, role, programme, year_level, avatar_initials, gender, profile_icon, status) VALUES
('Admin User', 'admin@gmail.com', 'admin@gmail.com', '$2y$10$wD08lmaueYT.1/QHAl63q.99WXPomKTsVyIYGKrs0bozsj.N1ALV2', 'admin', 'FDSIT', 'Staff', 'AU', 'male', 'profileicons/icons8-administrator-male-100.png', 'Active'),
('Demo Student', 'demostudent', 'student@gmail.com', '$2y$10$WAhc1B6/Ul7XRbNcXYpuD.HTMcKZqDKnCuaBqC/X7WHsHi/hYBCOy', 'student', 'Information Systems', 'Year 4', 'DS', 'male', 'profileicons/icons8-add-user-male-100.png', 'Active'),
('Demo Lecturer', 'demolecturer', 'lecturer@gmail.com', '$2y$10$SSn8Vmrxf7EQVjdxcedMG.o.hl/ehe52ODEBmviThS1y0GA57hJhG', 'lecturer', 'Information Systems', 'Staff', 'DL', 'male', 'profileicons/icons8-administrator-male-100.png', 'Active'),
('Demo Staff', 'demostaff', 'staff@gmail.com', '$2y$10$SSn8Vmrxf7EQVjdxcedMG.o.hl/ehe52ODEBmviThS1y0GA57hJhG', 'staff', 'Information Systems', 'Staff', 'ST', 'male', 'profileicons/icons8-administrator-male-100.png', 'Active')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO notifications (sender_role, recipient_role, notification_type, title, body) VALUES
('admin', 'student', 'info', 'Welcome to Skill Map', 'Start by updating your profile and completing your first analysis.'),
('lecturer', 'student', 'reminder', 'Profile review reminder', 'Please review and rate any new skills you have learned this week.'),
('staff', 'lecturer', 'message', 'Department update', 'Please check the latest student notification schedule and review queue.')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO user_credentials (user_id, entry_type, title, issuer, notes, earned_at) VALUES
(2, 'Certification', 'AWS Cloud Practitioner', 'Amazon Web Services', 'Completed cloud fundamentals and core services.', '2026-06-20'),
(2, 'Skill', 'Public Speaking', 'University Club', 'Led a student showcase presentation.', '2026-05-10')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO career_roles (name, type, description) VALUES
('Web Developer', 'Career', 'Build modern web applications'),
('Data Analyst', 'Career', 'Analyse and present data insights'),
('IT Support', 'Career', 'Provide technical support'),
('Student Club President', 'Lead', 'Lead student organisations')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO learning_resources (skill_id, title, platform, url, duration_hours, is_free) VALUES
(6, 'Build a REST API with PHP', 'Coursera', 'https://example.com', 6, 1),
(6, 'Deploy to Apache and cPanel', 'YouTube', 'https://example.com', 3, 1),
(4, 'Responsive UI build-up', 'Udemy', 'https://example.com', 5, 0),
(3, 'Fetch and async patterns', 'MDN', 'https://example.com', 4, 1)
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO learning_streaks (user_id, current_streak, best_streak, last_activity) VALUES
(2, 12, 18, '2026-07-05')
ON DUPLICATE KEY UPDATE current_streak = VALUES(current_streak);
