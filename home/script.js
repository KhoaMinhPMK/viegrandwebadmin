document.addEventListener('DOMContentLoaded', function() {
    // Cấu hình - Force sử dụng API production
    const API_BASE_URL = 'https://viegrand.site/viegrandwebadmin/php/';
    
    console.log('API Base URL:', API_BASE_URL);
    
    // Biến global
    let currentPage = 1;
    let currentLimit = 10;
    let currentSearchQuery = '';
    let currentDatabase = 'admin'; // Mặc định dùng admin database
    
    // Khởi tạo trang
    initializePage();
    
    function initializePage() {
        // Kiểm tra đăng nhập (tạm thời skip vì không lưu token)
        // loadUserInfo();
        
        // Set user mặc định
        setDefaultUser();
        
        // Khởi tạo đồng hồ thời gian thực
        initializeDateTime();
        
        // Khởi tạo news ticker
        initializeNewsTicker();
        
        // Xử lý sự kiện đăng xuất
        setupLogoutHandler();
        
        // Khởi tạo quản lý users
        initializeUsersManagement();
        
        // Khởi tạo database selector
        initializeDatabaseSelector();
        
        // Load danh sách users
        loadUsers();
    }
    
    function setDefaultUser() {
        // Thông tin user mặc định (mockup)
        const defaultUser = {
            username: 'admin',
            full_name: 'Administrator',
            role: 'admin'
        };
        
        updateUserDisplay(defaultUser);
    }
    
    function updateUserDisplay(user) {
        // Cập nhật avatar (viết tắt tên)
        const avatarElement = document.getElementById('userAvatar');
        const avatar = generateAvatar(user.full_name || user.username);
        avatarElement.textContent = avatar;
        
        // Cập nhật tên người dùng
        const userNameElement = document.getElementById('userName');
        userNameElement.textContent = user.full_name || user.username;
        
        // Cập nhật role
        const userRoleElement = document.getElementById('userRole');
        const roleDisplay = getRoleDisplay(user.role);
        userRoleElement.textContent = roleDisplay;
    }
    
    function generateAvatar(name) {
        if (!name) return 'U';
        
        // Tách từ và lấy chữ cái đầu
        const words = name.trim().split(' ');
        if (words.length >= 2) {
            return (words[0][0] + words[words.length - 1][0]).toUpperCase();
        } else {
            return words[0].substring(0, 2).toUpperCase();
        }
    }
    
    function getRoleDisplay(role) {
        const roles = {
            'admin': 'Quản trị viên',
            'manager': 'Quản lý',
            'user': 'Người dùng'
        };
        return roles[role] || 'Người dùng';
    }
    
    function initializeDateTime() {
        function updateDateTime() {
            const now = new Date();
            
            // Cập nhật thời gian
            const timeString = now.toLocaleTimeString('vi-VN', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
            
            // Cập nhật ngày tháng
            const dateString = now.toLocaleDateString('vi-VN', {
                weekday: 'long',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
            document.getElementById('currentDate').textContent = dateString;
        }
        
        // Cập nhật ngay lập tức
        updateDateTime();
        
        // Cập nhật mỗi giây
        setInterval(updateDateTime, 1000);
    }
    
    function initializeNewsTicker() {
        const ticker = document.getElementById('tickerContent');
        const newsItems = [
            '<i class="fas fa-party-horn"></i> Chào mừng bạn đến với VieGrand Admin!',
            '<i class="fas fa-megaphone"></i> Hệ thống đang hoạt động bình thường.',
            '<i class="fas fa-rocket"></i> Phiên bản mới v1.0.0 đã được cập nhật.',
            '<i class="fas fa-briefcase"></i> Kiểm tra báo cáo hàng ngày trong mục Dashboard.',
            '<i class="fas fa-tools"></i> Bảo trì hệ thống định kỳ vào 2:00 AM mỗi ngày.',
            '<i class="fas fa-shield-alt"></i> Tính năng bảo mật mới đã được kích hoạt.',
            '<i class="fas fa-database"></i> Dữ liệu được sao lưu tự động hàng ngày.',
            '<i class="fas fa-bolt"></i> Hiệu suất hệ thống đã được tối ưu hóa.',
            '<i class="fas fa-bell"></i> Nhận thông báo realtime cho tất cả hoạt động.',
            '<i class="fas fa-star"></i> Cảm ơn bạn đã sử dụng VieGrand!'
        ];
        
        // Random thứ tự tin tức
        const shuffledNews = newsItems.sort(() => Math.random() - 0.5);
        ticker.innerHTML = shuffledNews.join(' • ');
        
        // Cập nhật tin tức mỗi 30 giây
        setInterval(() => {
            const reshuffled = newsItems.sort(() => Math.random() - 0.5);
            ticker.innerHTML = reshuffled.join(' • ');
        }, 30000);
    }
    
    function setupLogoutHandler() {
        const logoutBtn = document.getElementById('logoutBtn');
        
        logoutBtn.addEventListener('click', async function() {
            // Hiển thị confirm dialog
            if (!confirm('Bạn có chắc chắn muốn đăng xuất không?')) {
                return;
            }
            
            // Disable button
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Đang đăng xuất...</span>';
            
            try {
                // Gọi API logout (nếu có token)
                const token = localStorage.getItem('viegrand_token');
                if (token) {
                    await fetch(API_BASE_URL + 'login.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'logout',
                            session_token: token
                        })
                    });
                }
            } catch (error) {
                console.error('Logout API error:', error);
            } finally {
                // Xóa dữ liệu local
                localStorage.clear();
                sessionStorage.clear();
                
                // Hiển thị thông báo
                showNotification('Đã đăng xuất thành công!', 'success');
                
                // Chuyển hướng về trang đăng nhập sau 1 giây
                setTimeout(() => {
                    window.location.href = '../login/';
                }, 1000);
            }
        });
    }
    
    // Khởi tạo database selector
    function initializeDatabaseSelector() {
        const databaseSelector = document.getElementById('databaseSelector');
        if (databaseSelector) {
            databaseSelector.addEventListener('change', function() {
                currentDatabase = this.value;
                currentPage = 1; // Reset về trang đầu
                loadUsers(); // Reload dữ liệu
                
                // Cập nhật thông báo
                const dbName = this.value === 'admin' ? 'Admin Database (Login Web)' : 'Main Database (VieGrand Chính)';
                showNotification(`Đã chuyển sang ${dbName}`, 'info');
            });
        }
    }
    
    function showNotification(message, type = 'info') {
        // Tạo notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        `;
        
        // Style cho notification
        let bgColor = '#28a745';
        let iconClass = 'fa-check-circle';
        
        switch(type) {
            case 'error':
                bgColor = '#dc3545';
                iconClass = 'fa-exclamation-circle';
                break;
            case 'warning':
                bgColor = '#ffc107';
                iconClass = 'fa-exclamation-triangle';
                break;
            case 'info':
                bgColor = '#17a2b8';
                iconClass = 'fa-info-circle';
                break;
            default:
                bgColor = '#28a745';
                iconClass = 'fa-check-circle';
        }
        
        notification.innerHTML = `
            <i class="fas ${iconClass}"></i>
            <span>${message}</span>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(135deg, ${bgColor} 0%, ${bgColor}dd 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            animation: slideInRight 0.3s ease-out;
            max-width: 400px;
        `;
        
        // Thêm animation CSS nếu chưa có
        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Thêm vào DOM
        document.body.appendChild(notification);
        
        // Tự động xóa sau 3 giây
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Xử lý responsive cho ticker
    function handleResponsive() {
        const ticker = document.querySelector('.ticker-content');
        if (window.innerWidth <= 768) {
            ticker.style.animationDuration = '20s';
        } else {
            ticker.style.animationDuration = '30s';
        }
    }
    
    window.addEventListener('resize', handleResponsive);
    handleResponsive();
    
    // =====================================
    // USERS MANAGEMENT FUNCTIONS
    // =====================================
    
    function initializeUsersManagement() {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const refreshBtn = document.getElementById('refreshBtn');
        
        // Search on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        // Search button click
        searchBtn.addEventListener('click', performSearch);
        
        // Refresh button click
        refreshBtn.addEventListener('click', function() {
            currentSearchQuery = '';
            searchInput.value = '';
            currentPage = 1;
            loadUsers();
        });
        
        function performSearch() {
            const query = searchInput.value.trim();
            if (query.length >= 2 || query.length === 0) {
                currentSearchQuery = query;
                currentPage = 1;
                loadUsers();
            } else {
                showNotification('Vui lòng nhập ít nhất 2 ký tự để tìm kiếm', 'warning');
            }
        }
    }
    
    async function loadUsers() {
        try {
            showLoading(true);
            
            let url = `${API_BASE_URL}users.php?action=list&db=${currentDatabase}&page=${currentPage}&limit=${currentLimit}`;
            
            if (currentSearchQuery) {
                url = `${API_BASE_URL}users.php?action=search&db=${currentDatabase}&q=${encodeURIComponent(currentSearchQuery)}&page=${currentPage}&limit=${currentLimit}`;
            }
            
            console.log('Loading users from:', url);
            
            const response = await fetch(url);
            const result = await response.json();
            
            console.log('Users API response:', result);
            
            if (result.success) {
                displayUsers(result.data.users);
                displayPagination(result.data.pagination);
                updateDatabaseInfo(result.data);
                showNoData(false);
            } else {
                showNotification(result.message || 'Không thể tải danh sách người dùng', 'error');
                showNoData(true);
            }
        } catch (error) {
            console.error('Error loading users:', error);
            showNotification('Có lỗi xảy ra khi tải danh sách người dùng', 'error');
            showNoData(true);
        } finally {
            showLoading(false);
        }
    }
    
    // Function để cập nhật thông tin database hiện tại
    function updateDatabaseInfo(data) {
        const header = document.querySelector('.section-header h2');
        if (header && data.database && data.table) {
            const dbInfo = currentDatabase === 'admin' ? 
                '<span style="font-size: 0.7rem; background: #28a745; color: white; padding: 2px 8px; border-radius: 10px; margin-left: 10px;">Admin DB</span>' :
                '<span style="font-size: 0.7rem; background: #17a2b8; color: white; padding: 2px 8px; border-radius: 10px; margin-left: 10px;">Main DB</span>';
            
            header.innerHTML = `<i class="fas fa-users"></i> Quản lý người dùng ${dbInfo}`;
        }
    }
    
    function displayUsers(users) {
        const tbody = document.getElementById('usersTableBody');
        const table = document.getElementById('usersTable');
        
        if (!users || users.length === 0) {
            showNoData(true);
            table.style.display = 'none';
            return;
        }
        
        tbody.innerHTML = users.map(user => `
            <tr data-user-id="${user.id}">
                <td>
                    <div class="user-avatar-cell">${user.avatar}</div>
                </td>
                <td>
                    <div class="user-info">
                        <div class="user-name clickable" onclick="showUserDetail(${user.id}, '${user.database_source || currentDatabase}')" 
                             title="Click để xem chi tiết">
                            ${user.full_name || 'Chưa cập nhật'}
                        </div>
                        <div class="user-username">@${user.username}</div>
                        ${user.database_source ? `<div style="font-size: 0.7rem; color: #666; margin-top: 2px;">
                            <i class="fas fa-database"></i> ${user.database_source === 'admin' ? 'Admin DB' : 'Main DB'}
                        </div>` : ''}
                    </div>
                </td>
                <td>${user.email || 'Chưa có'}</td>
                <td>
                    <span class="role-badge role-${user.role}">${user.role_display}</span>
                </td>
                <td>
                    <span class="status-badge status-${user.status}">${user.status_display}</span>
                </td>
                <td>${user.created_at_formatted}</td>
                <td>${user.last_login_formatted}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn btn-view" onclick="showUserDetail(${user.id}, '${user.database_source || currentDatabase}')" title="Xem chi tiết">
                            <i class="fas fa-eye"></i> <span>Xem</span>
                        </button>
                        <button class="action-btn btn-edit" onclick="editUser(${user.id}, '${user.database_source || currentDatabase}')" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i> <span>Sửa</span>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteUser(${user.id}, '${user.database_source || currentDatabase}')" title="Xóa">
                            <i class="fas fa-trash"></i> <span>Xóa</span>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        
        table.style.display = 'table';
        showNoData(false);
    }
    
    function displayPagination(pagination) {
        const container = document.getElementById('paginationContainer');
        
        if (!pagination || pagination.total_pages <= 1) {
            container.style.display = 'none';
            return;
        }
        
        let paginationHtml = '';
        
        // Previous button
        paginationHtml += `
            <button ${pagination.current_page <= 1 ? 'disabled' : ''} 
                    onclick="changePage(${pagination.current_page - 1})">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button onclick="changePage(1)">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span>...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <button ${i === pagination.current_page ? 'class="active"' : ''} 
                        onclick="changePage(${i})">${i}</button>
            `;
        }
        
        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                paginationHtml += `<span>...</span>`;
            }
            paginationHtml += `<button onclick="changePage(${pagination.total_pages})">${pagination.total_pages}</button>`;
        }
        
        // Next button
        paginationHtml += `
            <button ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''} 
                    onclick="changePage(${pagination.current_page + 1})">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        // Pagination info
        paginationHtml += `
            <div class="pagination-info">
                Trang ${pagination.current_page} / ${pagination.total_pages} 
                (${pagination.total_users} người dùng)
            </div>
        `;
        
        container.innerHTML = paginationHtml;
        container.style.display = 'flex';
    }
    
    function showLoading(show) {
        const spinner = document.getElementById('loadingSpinner');
        const table = document.getElementById('usersTable');
        const pagination = document.getElementById('paginationContainer');
        
        if (show) {
            spinner.style.display = 'flex';
            table.style.display = 'none';
            pagination.style.display = 'none';
        } else {
            spinner.style.display = 'none';
        }
    }
    
    function updateDatabaseInfo(data) {
        // Cập nhật thông tin database trong header
        const sectionHeader = document.querySelector('.section-header h2');
        if (sectionHeader && data) {
            const dbInfo = data.database === 'admin' ? 'Admin DB' : 'Main DB';
            const tableName = data.table || 'unknown';
            sectionHeader.innerHTML = `
                <i class="fas fa-users"></i> Quản lý người dùng 
                <small style="font-size: 0.7em; color: #666; font-weight: normal;">
                    (${dbInfo}: ${tableName})
                </small>
            `;
        }
    }
    
    function showNoData(show) {
        const noDataMsg = document.getElementById('noDataMessage');
        const table = document.getElementById('usersTable');
        const pagination = document.getElementById('paginationContainer');
        
        if (show) {
            noDataMsg.style.display = 'block';
            table.style.display = 'none';
            pagination.style.display = 'none';
        } else {
            noDataMsg.style.display = 'none';
        }
    }
    
    // Global functions for user actions
    window.changePage = function(page) {
        currentPage = page;
        loadUsers();
    };
    
    window.viewUser = function(userId, dbSource = null) {
        const database = dbSource || currentDatabase;
        const dbName = database === 'admin' ? 'Admin Database' : 'Main Database';
        showNotification(`Xem thông tin chi tiết user ID: ${userId} từ ${dbName}`, 'info');
        // TODO: Implement view user modal with database parameter
    };
    
    window.editUser = function(userId, dbSource = null) {
        const database = dbSource || currentDatabase;
        const dbName = database === 'admin' ? 'Admin Database' : 'Main Database';
        showNotification(`Chỉnh sửa user ID: ${userId} từ ${dbName}`, 'info');
        // TODO: Implement edit user modal with database parameter
    };
    
    window.deleteUser = function(userId, dbSource = null) {
        const database = dbSource || currentDatabase;
        const dbName = database === 'admin' ? 'Admin Database' : 'Main Database';
        if (confirm(`Bạn có chắc chắn muốn xóa người dùng này từ ${dbName}?`)) {
            showNotification(`Xóa user ID: ${userId} từ ${dbName}`, 'warning');
            // TODO: Implement delete user API call with database parameter
        }
    };

    // Modal Functions
    function initializeModal() {
        const modal = document.getElementById('userDetailModal');
        const closeBtn = document.querySelector('.close');
        const closeModalBtn = document.getElementById('closeModalBtn');
        
        // Close modal events
        closeBtn.onclick = closeModal;
        closeModalBtn.onclick = closeModal;
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        };
        
        // ESC key to close modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'block') {
                closeModal();
            }
        });
    }
    
    function openModal() {
        const modal = document.getElementById('userDetailModal');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    }
    
    function closeModal() {
        const modal = document.getElementById('userDetailModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scroll
    }
    
    function showUserDetail(userId, dbSource = null) {
        const database = dbSource || currentDatabase;
        
        // Show loading in modal
        openModal();
        
        // Find user data from current loaded users or fetch from API
        const tableRows = document.querySelectorAll('#usersTableBody tr');
        let userData = null;
        
        tableRows.forEach(row => {
            const userIdCell = row.querySelector('[data-user-id]');
            if (userIdCell && userIdCell.getAttribute('data-user-id') == userId) {
                userData = extractUserDataFromRow(row);
            }
        });
        
        if (userData) {
            populateModal(userData, database);
        } else {
            // Fetch user detail from API if not found in current table
            fetchUserDetail(userId, database);
        }
    }
    
    function extractUserDataFromRow(row) {
        const cells = row.querySelectorAll('td');
        return {
            id: row.querySelector('[data-user-id]').getAttribute('data-user-id'),
            avatar: cells[0].textContent.trim(),
            username: cells[1].querySelector('.user-username')?.textContent.trim() || '',
            full_name: cells[1].querySelector('.user-name')?.textContent.trim() || '',
            email: cells[2].textContent.trim(),
            role: cells[3].querySelector('.role-badge')?.textContent.trim() || '',
            status: cells[4].querySelector('.status-badge')?.textContent.trim() || '',
            created_at: cells[5].textContent.trim(),
            last_login: cells[6].textContent.trim(),
            phone: 'N/A' // Will be fetched from API if needed
        };
    }
    
    async function fetchUserDetail(userId, database) {
        try {
            console.log(`Fetching user detail: ID=${userId}, DB=${database}`);
            const response = await fetch(`${API_BASE_URL}users.php?action=get&id=${userId}&db=${database}`);
            
            console.log('Response status:', response.status);
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('Server trả về dữ liệu không hợp lệ');
            }
            
            console.log('Parsed result:', result);
            
            if (result.success) {
                populateModal(result.data, database);
            } else {
                showNotification(result.message || 'Không thể tải thông tin chi tiết người dùng', 'error');
                closeModal();
            }
        } catch (error) {
            console.error('Error fetching user detail:', error);
            showNotification(`Có lỗi xảy ra khi tải thông tin người dùng: ${error.message}`, 'error');
            closeModal();
        }
    }
    
    function populateModal(userData, database) {
        // Update avatar
        const avatar = userData.full_name ? userData.full_name.charAt(0).toUpperCase() : 
                     userData.username ? userData.username.charAt(0).toUpperCase() : 'U';
        document.getElementById('modalUserAvatar').textContent = avatar;
        
        // Update user info
        document.getElementById('modalUserId').textContent = userData.id || '-';
        document.getElementById('modalUserFullName').textContent = userData.full_name || userData.username || '-';
        document.getElementById('modalUserUsername').textContent = userData.username || '-';
        document.getElementById('modalUserEmail').textContent = userData.email || '-';
        document.getElementById('modalUserPhone').textContent = userData.phone || '-';
        document.getElementById('modalUserCreated').textContent = formatDate(userData.created_at) || '-';
        document.getElementById('modalUserLastLogin').textContent = formatDate(userData.last_login) || 'Chưa đăng nhập';
        
        // Update role badge
        const roleElement = document.getElementById('modalUserRole');
        roleElement.textContent = userData.role || '-';
        roleElement.className = `role-badge role-${userData.role}`;
        
        // Update status badge
        const statusElement = document.getElementById('modalUserStatus');
        statusElement.textContent = userData.status || '-';
        statusElement.className = `status-badge status-${userData.status}`;
        
        // Show/hide premium info for main database
        const premiumRow = document.getElementById('modalPremiumRow');
        if (database === 'main' && userData.premium_status !== undefined) {
            premiumRow.style.display = 'flex';
            document.getElementById('modalUserPremium').textContent = userData.premium_status ? 'Premium' : 'Thường';
        } else {
            premiumRow.style.display = 'none';
        }
        
        // Update edit button
        const editBtn = document.getElementById('editUserBtn');
        editBtn.onclick = () => editUser(userData.id, database);
    }
    
    function formatDate(dateString) {
        if (!dateString || dateString === '0000-00-00 00:00:00') return null;
        
        try {
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return dateString;
        }
    }
    
    // Initialize modal when page loads
    initializeModal();
    
    // Make showUserDetail globally available
    window.showUserDetail = showUserDetail;
});
