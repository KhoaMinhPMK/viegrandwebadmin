-- Schema for RBAC and Task workflow (viegrand_admin)

-- Roles
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE,
  description VARCHAR(255) NULL
);

-- Permissions
CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) UNIQUE,
  description VARCHAR(255) NULL
);

-- Role-Permissions
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- User-Roles
CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Tasks
CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE NULL,
  customer_id INT NULL,
  customer_name VARCHAR(190) NOT NULL,
  customer_phone VARCHAR(50) NULL,
  address TEXT NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  window_start DATETIME NULL,
  window_end DATETIME NULL,
  note TEXT NULL,
  status ENUM('draft','scheduled','en_route','on_site','in_progress','completed','failed','canceled') DEFAULT 'scheduled',
  created_by INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  INDEX(status),
  INDEX(created_at)
);

-- Task Assignments
CREATE TABLE IF NOT EXISTS task_assignments (
  task_id INT NOT NULL,
  technician_id INT NOT NULL,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (task_id, technician_id),
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Task Status Logs
CREATE TABLE IF NOT EXISTS task_status_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  status VARCHAR(50) NOT NULL,
  changed_by INT NULL,
  changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  note TEXT NULL,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  INDEX(task_id),
  INDEX(changed_at)
);

-- Task Photos
CREATE TABLE IF NOT EXISTS task_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  type ENUM('before','during','after','signature') DEFAULT 'after',
  file_path VARCHAR(255) NOT NULL,
  uploaded_by INT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Materials Used
CREATE TABLE IF NOT EXISTS materials_used (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  material_name VARCHAR(190) NOT NULL,
  quantity DECIMAL(10,2) DEFAULT 1,
  unit VARCHAR(50) DEFAULT 'pcs',
  note TEXT NULL,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Tracking Links
CREATE TABLE IF NOT EXISTS tracking_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  token VARCHAR(64) UNIQUE NOT NULL,
  expires_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  INDEX(expires_at)
);

-- Seed minimal permissions and roles (optional)
INSERT IGNORE INTO permissions(name, description) VALUES
('user.read','Xem người dùng'),('user.create','Tạo người dùng'),('user.update','Sửa người dùng'),('user.delete','Xóa người dùng'),
('role.read','Xem vai trò'),('role.create','Tạo vai trò'),('role.update','Sửa vai trò'),('role.delete','Xóa vai trò'),
('permission.assign','Gán quyền/role cho user'),
('task.read','Xem công việc'),('task.create','Tạo công việc'),('task.update','Sửa công việc'),('task.assign','Giao việc'),('task.cancel','Hủy việc'),
('task.photo.upload','Tải ảnh công việc'),('task.photo.delete','Xóa ảnh công việc'),
('tracking.generate','Tạo link tracking'),('report.view','Xem báo cáo');

INSERT IGNORE INTO roles(name, description) VALUES
('super_admin','Toàn quyền hệ thống'),
('admin','Quản trị'),
('dispatcher','Điều phối'),
('technician','Kỹ thuật viên'),
('auditor','Kiểm duyệt'),
('customer_viewer','Khách xem tracking');

-- Map full permissions to super_admin
INSERT IGNORE INTO role_permissions(role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'super_admin';


