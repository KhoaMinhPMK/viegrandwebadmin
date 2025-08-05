document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let currentLimit = 10;
    
    initializePage();
    
    function initializePage() {
        setDefaultUser();
        initializeDateTime();
        initializeNewsTicker();
        setupLogoutHandler();
        initializeEmptyTable();
    }
    
    function setDefaultUser() {
        const defaultUser = {
            username: 'admin',
            full_name: 'Administrator',
            role: 'admin'
        };
        
        updateUserDisplay(defaultUser);
    }
    
    function updateUserDisplay(userData) {
        const userAvatar = document.getElementById('userAvatar');
        const userName = document.getElementById('userName');
        const userRole = document.getElementById('userRole');
        
        userAvatar.textContent = generateUserAvatar(userData.full_name || userData.username);
        userName.textContent = userData.full_name || userData.username;
        userRole.textContent = userData.role ? userData.role.charAt(0).toUpperCase() + userData.role.slice(1) : 'User';
    }
    
    function generateUserAvatar(name) {
        if (!name) return 'U';
        
        const words = name.trim().split(' ');
        if (words.length >= 2) {
            return (words[0][0] + words[words.length - 1][0]).toUpperCase();
        }
        return name.charAt(0).toUpperCase();
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
        const timeElement = document.getElementById('currentTime');
        const dateElement = document.getElementById('currentDate');
        
        function updateTime() {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('vi-VN');
        }
        
        function updateDate() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: '2-digit', 
                day: '2-digit' 
            };
            dateElement.textContent = now.toLocaleDateString('vi-VN', options);
        }
        
        updateTime();
        updateDate();
        
        setInterval(updateTime, 1000);
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
        
        let currentIndex = 0;
        
        function updateTicker() {
            ticker.innerHTML = newsItems[currentIndex];
            currentIndex = (currentIndex + 1) % newsItems.length;
        }
        
        updateTicker();
        setInterval(updateTicker, 5000);
    }
    
    function setupLogoutHandler() {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                showNotification('Đang đăng xuất...', 'info');
                
                // Chuyển hướng về trang đăng nhập sau 1 giây
                setTimeout(() => {
                    window.location.href = '../login/';
                }, 1000);
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
    
    function handleResponsive() {
        const isMobile = window.innerWidth <= 768;
        // Add responsive handling if needed
    }
    
    function initializeEmptyTable() {
        // Show empty table with placeholder message
        const tbody = document.getElementById('usersTableBody');
        const table = document.getElementById('usersTable');
        const loadingSpinner = document.getElementById('loadingSpinner');
        
        if (loadingSpinner) {
            loadingSpinner.style.display = 'none';
        }
        
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-database" style="font-size: 3rem; color: #ddd; margin-bottom: 10px;"></i>
                        <h3 style="margin: 10px 0; color: #999;">Database Connection Required</h3>
                        <p style="color: #999; margin: 0;">Database connection will be implemented later.</p>
                    </td>
                </tr>
            `;
        }
        
        if (table) {
            table.style.display = 'table';
        }
        
        // Hide pagination
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
        
        // Update header
        const header = document.querySelector('.section-header h2');
        if (header) {
            header.innerHTML = `<i class="fas fa-users"></i> Quản lý người dùng <span style="font-size: 0.7rem; background: #ffc107; color: #333; padding: 2px 8px; border-radius: 10px; margin-left: 10px;">Database Disconnected</span>`;
        }
        
        // Setup refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                showNotification('Database connection will be implemented later.', 'info');
            });
        }
    }
    
    // Initialize responsive handling
    window.addEventListener('resize', handleResponsive);
    handleResponsive();
});
