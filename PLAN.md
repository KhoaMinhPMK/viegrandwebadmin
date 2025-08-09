## Kế hoạch triển khai hệ thống quản trị, phân quyền, giao việc, theo dõi tiến độ (VieGrand)

### 1) Mục tiêu
- Xây dựng hệ thống quản trị có phân quyền theo vai trò (RBAC).
- Admin có toàn quyền; nhân viên chỉ có quyền theo vai trò được cấp.
- Có luồng giao việc đến địa chỉ của khách, kỹ thuật viên nhận – cập nhật – hoàn thành kèm ảnh chứng minh.
- Có trang theo dõi tiến trình cho khách hàng (giống ứng dụng vận chuyển/tracking), cập nhật thời gian thực.

### 2) Phân quyền (RBAC)
- Vai trò đề xuất:
  - super_admin: toàn quyền hệ thống, cấu hình, quản trị bảo mật.
  - admin: quản lý người dùng, vai trò, giao việc, xem báo cáo.
  - dispatcher (điều phối): tạo/assign công việc cho kỹ thuật viên, cập nhật lịch.
  - technician (kỹ thuật viên): xem việc được giao, cập nhật trạng thái, upload ảnh/bút ký, hoàn thành.
  - auditor (kiểm duyệt): nghiệm thu, duyệt hoàn thành.
  - customer_viewer: chỉ xem trang tracking công việc thông qua link/tokens.

- Nhóm quyền chi tiết (permissions – gợi ý):
  - user.read, user.create, user.update, user.delete
  - role.read, role.create, role.update, role.delete
  - permission.assign (gán quyền/role cho user)
  - task.read, task.create, task.update, task.assign, task.cancel
  - task.photo.upload, task.photo.delete
  - task.materials.update (vật tư sử dụng)
  - task.approve (nghiệm thu)
  - tracking.generate (tạo link theo dõi)
  - report.view

### 3) Quy trình nghiệp vụ
1. Đăng nhập: tài khoản thuộc DB admin, xác thực -> sinh session token.
2. Quản trị phân quyền: tạo vai trò, ánh xạ role-permission, gán role cho user.
3. Tạo khách hàng/địa điểm (nếu chưa có trong DB chính) -> chuẩn hóa địa chỉ, tọa độ (tùy chọn).
4. Tạo công việc (task): thông tin khách, địa chỉ, khung thời gian, yêu cầu, vật tư dự kiến.
5. Phân công (assign): dispatcher/ admin gán kỹ thuật viên; gửi thông báo.
6. Kỹ thuật viên nhận việc: xem chi tiết, check-in, cập nhật trạng thái: scheduled -> en_route -> on_site -> in_progress -> completed (hoặc failed/canceled).
7. Tải ảnh minh chứng: trước/sau lắp đặt, biên bản, chữ ký (nếu có).
8. Nghiệm thu (auditor/admin): duyệt hoàn thành hoặc yêu cầu bổ sung.
9. Tạo link tracking cho khách: khách xem tiến trình realtime/polling, vị trí (tùy chọn), ảnh trước/sau.
10. Báo cáo: thống kê công việc theo kỹ thuật viên/thời gian/trạng thái.

### 4) Kiến trúc kỹ thuật (đề xuất dựa trên nền PHP hiện có)
- Backend PHP (thư mục `php/`):
  - Tách endpoints theo module: auth, users, roles, permissions, tasks, tracking, uploads.
  - Chuẩn hóa response JSON; dùng PDO với prepared statements (đã có trong `php/config.php`).
  - File upload: lưu `uploads/` (tạo mới), validate MIME, kích thước, đổi tên file an toàn; lưu metadata trong DB.
  - Bảo vệ API bằng session token + kiểm tra quyền theo RBAC.
  - Realtime: giai đoạn 1 dùng polling 5–10s; giai đoạn 2 nâng cấp SSE/WebSocket nếu cần.

- Databases:
  - `viegrand_admin` (quản trị + phân quyền + task workflow).
  - `viegrand` (DB chính hiện hữu – có bảng khách hàng nếu đã có; nếu chưa, tạo bổ sung).

### 5) Lược đồ CSDL (tối thiểu cần thêm ở DB admin)

```sql
-- DB: viegrand_admin
-- Users (nếu chưa có trường role tách rời)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE,
  email VARCHAR(190) UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(190),
  phone VARCHAR(50),
  status ENUM('active','inactive','suspended') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE,
  description VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) UNIQUE,
  description VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE,
  customer_id INT NULL,
  customer_name VARCHAR(190),
  customer_phone VARCHAR(50),
  address TEXT,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  window_start DATETIME NULL,
  window_end DATETIME NULL,
  note TEXT,
  status ENUM('draft','scheduled','en_route','on_site','in_progress','completed','failed','canceled') DEFAULT 'scheduled',
  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS task_assignments (
  task_id INT NOT NULL,
  technician_id INT NOT NULL,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (task_id, technician_id),
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS task_status_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  status VARCHAR(50) NOT NULL,
  changed_by INT,
  changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  note TEXT,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS task_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  type ENUM('before','during','after','signature') DEFAULT 'after',
  file_path VARCHAR(255) NOT NULL,
  uploaded_by INT,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS materials_used (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  material_name VARCHAR(190) NOT NULL,
  quantity DECIMAL(10,2) DEFAULT 1,
  unit VARCHAR(50) DEFAULT 'pcs',
  note TEXT,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tracking_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  token VARCHAR(64) UNIQUE,
  expires_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);
```

> DB `viegrand` (chính): tận dụng bảng khách hàng hiện có; nếu thiếu, bổ sung `customers(id, name, phone, address, ... )` hoặc ánh xạ qua `customer_id`.

### 6) API dự kiến (PHP)
- Auth: `POST /php/auth.php?action=login|logout` (session + token), `GET /php/auth.php?action=me`
- Users: `GET /php/users.php?action=list|get|search`, `POST /php/users.php`, `PUT /php/users.php`, `DELETE /php/users.php`
- Roles/Permissions: `GET /php/roles.php`, `POST /php/roles.php`, `PUT /php/roles.php`, `DELETE /php/roles.php`, `POST /php/roles.php?action=assign`
- Tasks: `GET /php/tasks.php?action=list|get`, `POST /php/tasks.php` (create), `PUT /php/tasks.php` (update/status), `POST /php/tasks.php?action=assign`, `POST /php/tasks.php?action=cancel`
- Uploads: `POST /php/uploads.php?action=task_photo&task_id=...` (multipart/form-data)
- Tracking: `GET /php/tracking.php?token=...` (public read-only), `POST /php/tracking.php?action=generate&task_id=...` (admin/dispatcher)
- Reports: `GET /php/reports.php?...`

Lưu ý: mọi endpoint (trừ tracking public) phải kiểm tra session token và RBAC.

### 7) Giao diện/Trang (Web)
- Trang Admin (desktop):
  - Dashboard: số việc theo trạng thái, hôm nay/tuần/tháng.
  - Quản lý người dùng: CRUD, gán vai trò.
  - Vai trò & Quyền: CRUD roles, gán permissions cho roles.
  - Giao việc: form tạo task, chọn khách/địa chỉ, gán kỹ thuật viên, cửa sổ thời gian.
  - Danh sách công việc: lọc theo trạng thái, người phụ trách, ngày; xem chi tiết task; nhật ký; ảnh; vật tư; nghiệm thu.
  - Báo cáo.

- Giao diện Kỹ thuật viên (mobile web responsive):
  - Danh sách việc được giao; chi tiết.
  - Nút trạng thái: Đang di chuyển -> Đến nơi -> Đang thực hiện -> Hoàn thành.
  - Upload ảnh trước/sau; ghi chú; vật tư; chữ ký.

- Trang Theo dõi cho khách (public tracking):
  - Truy cập qua link có `token`.
  - Hiển thị tiến trình, thời gian ước tính, ảnh trước/sau khi hoàn thành.
  - Polling 10s hoặc SSE để cập nhật.

### 8) Bảo mật & Tuân thủ
- Hash mật khẩu `password_hash`, kiểm soát login attempts, lockout.
- RBAC kiểm tra ở middleware cho mỗi endpoint.
- Upload: kiểm MIME, giới hạn dung lượng, đổi tên file, cấm thực thi, lưu ngoài webroot nếu có thể; tạo thumbnail (tùy chọn).
- CSRF (cho form), XSS encode output, CORS có whitelist theo domain.
- Audit log: ghi lại hoạt động quan trọng (assign, đổi trạng thái, xóa ảnh, tạo link tracking...).

### 9) Lộ trình triển khai (To-Do chi tiết)

#### P0 – Nền tảng bắt buộc
- [ ] DB – Tạo bảng RBAC: `roles`, `permissions`, `role_permissions`, `user_roles` (admin DB)
- [ ] DB – Tạo bảng công việc: `tasks`, `task_assignments`, `task_status_logs`, `task_photos`, `materials_used`, `tracking_links`
- [ ] API – Auth: đăng nhập/đăng xuất, `me`
- [ ] API – Users: list/get/update (giữ nguyên email là định danh)
- [ ] API – Roles/Permissions: CRUD + gán role cho user, gán permission cho role
- [ ] API – Tasks: tạo, list, get, cập nhật trạng thái, phân công
- [ ] API – Upload ảnh công việc (multipart), lưu metadata
- [ ] API – Tracking public (read-only qua token)
- [ ] UI – Trang Quản trị Người dùng (nâng cấp hiện có: gán role/status)
- [ ] UI – Trang Vai trò & Quyền
- [ ] UI – Trang Tạo/Giao việc + Danh sách công việc + Chi tiết công việc
- [ ] UI – Trang Theo dõi cho khách (public)

#### P1 – Hoàn thiện trải nghiệm
- [ ] Nhật ký trạng thái chi tiết + timeline UI
- [ ] Form vật tư sử dụng trong task
- [ ] Nghiệm thu/duyệt (auditor)
- [ ] Thông báo in-app (toast) + email cơ bản khi assign
- [ ] Bộ lọc/báo cáo cơ bản theo ngày/kỹ thuật viên/trạng thái

#### P2 – Nâng cao
- [ ] Realtime nâng cấp (SSE/WebSocket) cho tracking/board
- [ ] Tối ưu upload: nén ảnh, thumbnail, retry, batch upload
- [ ] Định vị (GPS) tự nguyện của kỹ thuật viên khi on_route/on_site
- [ ] Xuất báo cáo PDF/Excel
- [ ] Phân ca/lịch làm việc

### 10) Phân rã công việc (kèm file/code dự kiến)
- [ ] Backend cấu trúc thư mục `php/api/`
  - [ ] `php/api/auth.php`
  - [ ] `php/api/users.php`
  - [ ] `php/api/roles.php`
  - [ ] `php/api/tasks.php`
  - [ ] `php/api/uploads.php`
  - [ ] `php/api/tracking.php`

- [ ] Uploads
  - [ ] Tạo thư mục `uploads/` + `.htaccess` chặn thực thi (nếu dùng Apache)
  - [ ] Validate file + rename an toàn + trả về URL công khai

- [ ] Frontend (web)
  - [ ] `home/` bổ sung menu: Users, Roles, Tasks, Reports
  - [ ] Trang Roles & Permissions (CRUD, gán quyền)
  - [ ] Trang Tasks: list/filter; create/edit; detail (ảnh, timeline, vật tư, nút nghiệm thu)
  - [ ] Trang Technician (responsive): list công việc; detail; cập nhật trạng thái; upload ảnh; ký tên
  - [ ] Trang Tracking public: `tracking/index.html?token=...` (read-only, polling)

### 11) Tiêu chí hoàn thành giai đoạn 1
- Admin tạo role/permission, gán cho nhân sự.
- Dispatcher tạo task, gán kỹ thuật viên.
- Kỹ thuật viên cập nhật các mốc trạng thái và upload ảnh.
- Khách xem trang tracking bằng link token.
- Báo cáo cơ bản hiển thị tổng số công việc theo trạng thái/ngày.

### 12) Ghi chú triển khai
- Tận dụng `php/config.php` hiện có cho 2 DB; bổ sung helper kiểm tra quyền theo token/session.
- Chuẩn hóa response `{ success, data, message }` ở mọi API.
- Giữ backward-compatible với các trang đang có, triển khai dần theo P0 -> P1 -> P2.

