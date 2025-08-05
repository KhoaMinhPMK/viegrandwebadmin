// Global variables
let currentDatabase = 'admin';
let adminCurrentPage = 1;
let mainCurrentPage = 1;
let adminCurrentLimit = 10;
let mainCurrentLimit = 10;

// API URLs
const ADMIN_API_URL = 'https://viegrand.site/viegrandwebadmin/php/get_users_viegrand_admin.php';
const MAIN_API_URL = 'https://viegrand.site/viegrandwebadmin/php/get_users_viegrand.php';

// DOM elements
const tabButtons = document.querySelectorAll('.tab-btn');
const databaseSections = document.querySelectorAll('.database-section');
const refreshAdminBtn = document.getElementById('refreshAdminBtn');
const refreshMainBtn = document.getElementById('refreshMainBtn');

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
    setupEventListeners();
    loadAdminUsers();
});

function initializePage() {
    updateDateTime();
    setInterval(updateDateTime, 1000);
    loadUserInfo();
}

function setupEventListeners() {
    // Tab switching
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const database = button.getAttribute('data-database');
            switchDatabase(database);
        });
    });

    // Refresh buttons
    refreshAdminBtn.addEventListener('click', loadAdminUsers);
    refreshMainBtn.addEventListener('click', loadMainUsers);

    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', logout);
}

function switchDatabase(database) {
    // Update active tab
    tabButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-database') === database) {
            btn.classList.add('active');
        }
    });

    // Update active section
    databaseSections.forEach(section => {
        section.classList.remove('active');
    });

    if (database === 'admin') {
        document.getElementById('adminDatabaseSection').classList.add('active');
        if (!document.getElementById('adminUsersTable').hasAttribute('data-loaded')) {
            loadAdminUsers();
        }
    } else {
        document.getElementById('mainDatabaseSection').classList.add('active');
        if (!document.getElementById('mainUsersTable').hasAttribute('data-loaded')) {
            loadMainUsers();
        }
    }

    currentDatabase = database;
}

// Admin Database Functions
async function loadAdminUsers() {
    try {
        showAdminLoading(true);
        
        const url = `${ADMIN_API_URL}?page=${adminCurrentPage}&limit=${adminCurrentLimit}`;
        console.log('Fetching admin users from:', url);
        
        const response = await fetch(url);
        const result = await response.json();
        
        console.log('Admin API Response:', result);
        
        if (result.success) {
            displayAdminUsers(result.data.users);
            displayAdminPagination(result.data.pagination);
            showAdminNoData(false);
            document.getElementById('adminUsersTable').setAttribute('data-loaded', 'true');
        } else {
            console.error('Admin API Error:', result.message);
            showNotification(result.message || 'Không thể tải danh sách Admin', 'error');
            showAdminNoData(true);
        }
    } catch (error) {
        console.error('Error loading admin users:', error);
        showNotification('Có lỗi xảy ra khi tải danh sách Admin', 'error');
        showAdminNoData(true);
    } finally {
        showAdminLoading(false);
    }
}

function displayAdminUsers(users) {
    const tbody = document.getElementById('adminUsersTableBody');
    tbody.innerHTML = '';

    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="user-avatar" title="${user.full_name}">
                    ${user.avatar}
                </div>
            </td>
            <td>
                <div class="user-info">
                    <div class="user-name">${user.full_name}</div>
                    <div class="user-username">@${user.username}</div>
                </div>
            </td>
            <td>${user.email || 'N/A'}</td>
            <td>
                <span class="role-badge ${user.role}">${user.role_display}</span>
            </td>
            <td>
                <span class="status-badge ${user.status}">${user.status_display}</span>
            </td>
            <td>${user.created_at_formatted}</td>
            <td>${user.last_login_formatted}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn view-btn" onclick="viewAdminUser(${user.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn edit-btn" onclick="editAdminUser(${user.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    document.getElementById('adminUsersTable').style.display = 'table';
}

function displayAdminPagination(pagination) {
    const container = document.getElementById('adminPaginationContainer');
    if (pagination.total_pages <= 1) {
        container.style.display = 'none';
        return;
    }

    container.innerHTML = `
        <div class="pagination-info">
            Trang ${pagination.current_page} / ${pagination.total_pages} 
            (${pagination.total_users} người dùng)
        </div>
        <div class="pagination-controls">
            <button class="pagination-btn" onclick="changeAdminPage(${pagination.current_page - 1})" 
                    ${pagination.current_page <= 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i> Trước
            </button>
            <span class="page-info">${pagination.current_page} / ${pagination.total_pages}</span>
            <button class="pagination-btn" onclick="changeAdminPage(${pagination.current_page + 1})" 
                    ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}>
                Sau <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    `;
    container.style.display = 'flex';
}

function changeAdminPage(page) {
    if (page < 1) return;
    adminCurrentPage = page;
    loadAdminUsers();
}

// Main Database Functions
async function loadMainUsers() {
    try {
        showMainLoading(true);
        
        const url = `${MAIN_API_URL}?page=${mainCurrentPage}&limit=${mainCurrentLimit}`;
        console.log('Fetching main users from:', url);
        
        const response = await fetch(url);
        const result = await response.json();
        
        console.log('Main API Response:', result);
        
        if (result.success) {
            displayMainUsers(result.data.users);
            displayMainPagination(result.data.pagination);
            showMainNoData(false);
            document.getElementById('mainUsersTable').setAttribute('data-loaded', 'true');
        } else {
            console.error('Main API Error:', result.message);
            showNotification(result.message || 'Không thể tải danh sách Main', 'error');
            showMainNoData(true);
        }
    } catch (error) {
        console.error('Error loading main users:', error);
        showNotification('Có lỗi xảy ra khi tải danh sách Main', 'error');
        showMainNoData(true);
    } finally {
        showMainLoading(false);
    }
}

function displayMainUsers(users) {
    const tbody = document.getElementById('mainUsersTableBody');
    tbody.innerHTML = '';

    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Format health info
        const healthInfoHtml = formatHealthInfoHtml(user.health_info);
        
        // Format premium status
        const premiumStatus = user.premium_status ? 
            '<span class="premium-badge active"><i class="fas fa-crown"></i> Premium</span>' :
            '<span class="premium-badge inactive"><i class="fas fa-user"></i> Regular</span>';

        row.innerHTML = `
            <td>
                <div class="user-avatar" title="${user.full_name}">
                    ${user.avatar}
                </div>
            </td>
            <td>
                <div class="user-info">
                    <div class="user-name">${user.full_name}</div>
                    <div class="user-username">@${user.username}</div>
                </div>
            </td>
            <td>${user.email || 'N/A'}</td>
            <td>
                <span class="role-badge ${user.role}">${user.role_display}</span>
            </td>
            <td>
                <span class="status-badge ${user.status}">${user.status_display}</span>
            </td>
            <td>${premiumStatus}</td>
            <td>
                <div class="health-info">
                    ${healthInfoHtml}
                </div>
            </td>
            <td>${user.created_at_formatted}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn view-btn" onclick="viewMainUser(${user.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn edit-btn" onclick="editMainUser(${user.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    document.getElementById('mainUsersTable').style.display = 'table';
}

function formatHealthInfoHtml(healthInfo) {
    if (!healthInfo || Object.keys(healthInfo).length === 0) {
        return '<span class="health-tag">Chưa có thông tin</span>';
    }

    let html = '';
    Object.entries(healthInfo).forEach(([key, value]) => {
        let className = 'health-tag';
        if (key.includes('premium') || key.includes('blood')) className += ' premium';
        else if (key.includes('hypertension') || key.includes('heart') || key.includes('stroke')) className += ' condition';
        else if (key.includes('height') || key.includes('weight') || key.includes('bmi') || key.includes('pressure')) className += ' measurement';
        
        html += `<span class="${className}">${value}</span>`;
    });
    
    return html;
}

function displayMainPagination(pagination) {
    const container = document.getElementById('mainPaginationContainer');
    if (pagination.total_pages <= 1) {
        container.style.display = 'none';
        return;
    }

    container.innerHTML = `
        <div class="pagination-info">
            Trang ${pagination.current_page} / ${pagination.total_pages} 
            (${pagination.total_users} người dùng)
        </div>
        <div class="pagination-controls">
            <button class="pagination-btn" onclick="changeMainPage(${pagination.current_page - 1})" 
                    ${pagination.current_page <= 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i> Trước
            </button>
            <span class="page-info">${pagination.current_page} / ${pagination.total_pages}</span>
            <button class="pagination-btn" onclick="changeMainPage(${pagination.current_page + 1})" 
                    ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}>
                Sau <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    `;
    container.style.display = 'flex';
}

function changeMainPage(page) {
    if (page < 1) return;
    mainCurrentPage = page;
    loadMainUsers();
}

// Loading and No Data Functions
function showAdminLoading(show) {
    const spinner = document.getElementById('adminLoadingSpinner');
    const table = document.getElementById('adminUsersTable');
    const pagination = document.getElementById('adminPaginationContainer');
    
    if (show) {
        spinner.style.display = 'flex';
        table.style.display = 'none';
        pagination.style.display = 'none';
    } else {
        spinner.style.display = 'none';
    }
}

function showMainLoading(show) {
    const spinner = document.getElementById('mainLoadingSpinner');
    const table = document.getElementById('mainUsersTable');
    const pagination = document.getElementById('mainPaginationContainer');
    
    if (show) {
        spinner.style.display = 'flex';
        table.style.display = 'none';
        pagination.style.display = 'none';
    } else {
        spinner.style.display = 'none';
    }
}

function showAdminNoData(show) {
    const noData = document.getElementById('adminNoDataMessage');
    const table = document.getElementById('adminUsersTable');
    const pagination = document.getElementById('adminPaginationContainer');
    
    if (show) {
        noData.style.display = 'flex';
        table.style.display = 'none';
        pagination.style.display = 'none';
    } else {
        noData.style.display = 'none';
    }
}

function showMainNoData(show) {
    const noData = document.getElementById('mainNoDataMessage');
    const table = document.getElementById('mainUsersTable');
    const pagination = document.getElementById('mainPaginationContainer');
    
    if (show) {
        noData.style.display = 'flex';
        table.style.display = 'none';
        pagination.style.display = 'none';
    } else {
        noData.style.display = 'none';
    }
}

// User action functions (placeholder for now)
function viewAdminUser(userId) {
    showNotification('Chức năng xem chi tiết Admin sẽ được phát triển sau', 'info');
}

function editAdminUser(userId) {
    showNotification('Chức năng chỉnh sửa Admin sẽ được phát triển sau', 'info');
}

function viewMainUser(userId) {
    showNotification('Chức năng xem chi tiết Main sẽ được phát triển sau', 'info');
}

function editMainUser(userId) {
    showNotification('Chức năng chỉnh sửa Main sẽ được phát triển sau', 'info');
}

// Utility functions
function updateDateTime() {
    const now = new Date();
    const timeElement = document.getElementById('currentTime');
    const dateElement = document.getElementById('currentDate');
    
    const timeString = now.toLocaleTimeString('vi-VN');
    const dateString = now.toLocaleDateString('vi-VN', {
        weekday: 'long',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
    
    timeElement.textContent = timeString;
    dateElement.textContent = dateString;
}

function loadUserInfo() {
    // Load user info from localStorage or session
    const userInfo = JSON.parse(localStorage.getItem('userInfo')) || {
        name: 'Administrator',
        role: 'Admin',
        avatar: 'AD'
    };
    
    document.getElementById('userName').textContent = userInfo.name;
    document.getElementById('userRole').textContent = userInfo.role;
    document.getElementById('userAvatar').textContent = userInfo.avatar;
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function logout() {
    if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        localStorage.removeItem('userInfo');
        window.location.href = '../login/';
    }
}
