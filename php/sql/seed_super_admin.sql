-- Seed admin user and assign super_admin role

-- 1) Create admin user if not exists (password = 'password')
INSERT IGNORE INTO users (username, password, email, full_name, phone, role, status)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@viegrand.local', 'Administrator', NULL, 'admin', 'active');

-- 2) Assign super_admin role to admin
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.username = 'admin' AND r.name = 'super_admin';


