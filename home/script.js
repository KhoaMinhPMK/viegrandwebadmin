// Global variables
let currentDatabase = 'admin';
let adminCurrentPage = 1;
let mainCurrentPage = 1;
let adminCurrentLimit = 10;
let mainCurrentLimit = 10;
let currentUserData = null;
let adminUsersData = []; // Store admin users data
let mainUsersData = []; // Store main users data

// API URLs
const ADMIN_API_URL = 'https://viegrand.site/viegrandwebadmin/php/get_users_viegrand_admin.php';
const MAIN_API_URL = 'https://viegrand.site/viegrandwebadmin/php/get_users_viegrand.php';
const UPDATE_ADMIN_API = 'https://viegrand.site/viegrandwebadmin/php/update_user_admin.php';
const DELETE_ADMIN_API = 'https://viegrand.site/viegrandwebadmin/php/delete_user_admin.php';
const UPDATE_MAIN_API = 'https://viegrand.site/viegrandwebadmin/php/update_user_main.php';
const DELETE_MAIN_API = 'https://viegrand.site/viegrandwebadmin/php/delete_user_main.php';

// DOM elements
const tabButtons = document.querySelectorAll('.tab-btn');
const databaseSections = document.querySelectorAll('.database-section');
const refreshAdminBtn = document.getElementById('refreshAdminBtn');
const refreshMainBtn = document.getElementById('refreshMainBtn');

// Modal elements
const viewModal = document.getElementById('viewUserModal');
const editModal = document.getElementById('editUserModal');
const deleteModal = document.getElementById('deleteConfirmModal');

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

    // Modal close buttons
    document.getElementById('closeViewModal').addEventListener('click', closeViewModal);
    document.getElementById('closeEditModal').addEventListener('click', closeEditModal);
    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);

    // View modal buttons
    document.getElementById('editFromViewBtn').addEventListener('click', editFromView);
    document.getElementById('deleteFromViewBtn').addEventListener('click', deleteFromView);
    document.getElementById('closeViewBtn').addEventListener('click', closeViewModal);

    // Edit modal buttons
    document.getElementById('saveChangesBtn').addEventListener('click', saveChanges);
    document.getElementById('cancelEditBtn').addEventListener('click', closeEditModal);
    document.getElementById('previewChangesBtn').addEventListener('click', previewChanges);

    // Delete modal buttons
    document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);
    document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);

    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === viewModal) closeViewModal();
        if (e.target === editModal) closeEditModal();
        if (e.target === deleteModal) closeDeleteModal();
    });
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
            adminUsersData = result.data.users; // Store the data
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
                    <button class="action-btn delete-btn" onclick="deleteAdminUser(${user.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
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
            mainUsersData = result.data.users; // Store the data
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
                    <button class="action-btn delete-btn" onclick="deleteMainUser(${user.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
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
        return '<div class="health-tag">Chưa có thông tin sức khỏe</div>';
    }

    let html = '';
    Object.entries(healthInfo).forEach(([key, value]) => {
        let className = 'health-tag';
        let displayKey = key;
        
        // Customize display based on key type
        if (key.includes('premium') || key.includes('blood_type')) {
            className += ' premium';
        } else if (key.includes('hypertension') || key.includes('heart') || key.includes('stroke')) {
            className += ' condition';
        } else if (key.includes('height') || key.includes('weight') || key.includes('bmi') || key.includes('pressure')) {
            className += ' measurement';
        }
        
        // Format display key
        displayKey = displayKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        // Format the value
        let displayValue = value;
        if (key.includes('height')) displayValue += ' cm';
        else if (key.includes('weight')) displayValue += ' kg';
        else if (key.includes('pressure')) displayValue += ' mmHg';
        else if (key.includes('heart_rate')) displayValue += ' BPM';
        
        html += `<div class="${className}">
            <strong>${displayKey}:</strong> ${displayValue}
        </div>`;
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

// View Functions
function viewAdminUser(userId) {
    // Find user data from stored admin users
    const user = adminUsersData.find(u => u.id === userId);
    if (user) {
        showViewModal(user, 'admin');
    } else {
        showNotification('Không tìm thấy thông tin người dùng', 'error');
    }
}

function viewMainUser(userId) {
    // Find user data from stored main users
    const user = mainUsersData.find(u => u.id === userId);
    if (user) {
        showViewModal(user, 'main');
    } else {
        showNotification('Không tìm thấy thông tin người dùng', 'error');
    }
}

function showViewModal(user, database) {
    currentUserData = { ...user, database };
    
    // Set basic user info
    document.getElementById('viewUserAvatar').textContent = user.avatar;
    document.getElementById('viewUserDisplayName').textContent = user.full_name || user.username;
    document.getElementById('viewUserDatabaseType').textContent = database === 'admin' ? 'Admin Database' : 'Main Database';
    
    // Set detail values
    document.getElementById('viewUserId').textContent = user.id;
    document.getElementById('viewUserFullName').textContent = user.full_name || 'N/A';
    document.getElementById('viewUserUsername').textContent = user.username;
    document.getElementById('viewUserEmail').textContent = user.email || 'N/A';
    document.getElementById('viewUserPhone').textContent = user.phone || 'N/A';
    document.getElementById('viewUserCreated').textContent = user.created_at_formatted;
    
    // Set badges
    const roleElement = document.getElementById('viewUserRole');
    roleElement.textContent = user.role_display;
    roleElement.className = `role-badge ${user.role}`;
    
    const statusElement = document.getElementById('viewUserStatus');
    statusElement.textContent = user.status_display;
    statusElement.className = `status-badge ${user.status}`;
    
    // Show/hide database-specific sections
    if (database === 'admin') {
        // Admin database specific
        document.getElementById('viewUserLastLoginRow').style.display = 'flex';
        document.getElementById('viewUserLastLogin').textContent = user.last_login_formatted || 'Chưa đăng nhập';
        document.getElementById('viewUserUpdatedRow').style.display = user.updated_at_formatted ? 'flex' : 'none';
        document.getElementById('viewUserUpdated').textContent = user.updated_at_formatted || 'N/A';
        
        // Hide main database sections
        document.getElementById('viewUserMainDataSection').style.display = 'none';
        document.getElementById('viewUserHealthSection').style.display = 'none';
        document.getElementById('viewUserPremiumBadge').style.display = 'none';
    } else {
        // Main database specific
        document.getElementById('viewUserLastLoginRow').style.display = 'none';
        document.getElementById('viewUserUpdatedRow').style.display = 'none';
        
        // Show main database sections
        document.getElementById('viewUserMainDataSection').style.display = 'block';
        document.getElementById('viewUserHealthSection').style.display = 'block';
        
        // Set main database specific data
        document.getElementById('viewUserAge').textContent = user.age || 'N/A';
        document.getElementById('viewUserGender').textContent = getGenderDisplay(user.gender) || 'N/A';
        document.getElementById('viewUserBlood').textContent = user.blood || 'N/A';
        document.getElementById('viewUserPremium').textContent = user.premium_status ? 'Premium' : 'Regular';
        
        // Show premium badge if applicable
        if (user.premium_status) {
            document.getElementById('viewUserPremiumBadge').style.display = 'flex';
        } else {
            document.getElementById('viewUserPremiumBadge').style.display = 'none';
        }
        
        // Set health information
        document.getElementById('viewUserHealth').innerHTML = formatHealthInfoHtml(user.health_info);
    }
    
    viewModal.style.display = 'block';
}

function getGenderDisplay(gender) {
    switch (gender) {
        case 'male': return 'Nam';
        case 'female': return 'Nữ';
        case 'other': return 'Khác';
        default: return gender;
    }
}

// Edit Functions
function editAdminUser(userId) {
    const user = adminUsersData.find(u => u.id === userId);
    if (user) {
        showEditModal(user, 'admin');
    } else {
        showNotification('Không tìm thấy thông tin người dùng', 'error');
    }
}

function editMainUser(userId) {
    const user = mainUsersData.find(u => u.id === userId);
    if (user) {
        showEditModal(user, 'main');
    } else {
        showNotification('Không tìm thấy thông tin người dùng', 'error');
    }
}

function editFromView() {
    if (currentUserData) {
        showEditModal(currentUserData, currentUserData.database);
        closeViewModal();
    }
}

function showEditModal(user, database) {
    currentUserData = { ...user, database };
    
    // Set form fields
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUserDatabase').value = database;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editEmail').value = user.email || '';
    document.getElementById('editFullName').value = user.full_name;
    document.getElementById('editPhone').value = user.phone || '';
    
    // Set current info
    document.getElementById('currentUserId').textContent = user.id;
    document.getElementById('currentDatabase').textContent = database === 'admin' ? 'Admin Database' : 'Main Database';
    document.getElementById('currentCreated').textContent = user.created_at_formatted;
    document.getElementById('currentUpdated').textContent = user.updated_at_formatted || 'N/A';
    
    // Show/hide database-specific fields
    if (database === 'admin') {
        document.getElementById('adminFields').style.display = 'block';
        document.getElementById('mainFields').style.display = 'none';
        document.getElementById('healthFields').style.display = 'none';
        
        document.getElementById('editRole').value = user.role || '';
        document.getElementById('editStatus').value = user.status || '';
    } else {
        document.getElementById('adminFields').style.display = 'none';
        document.getElementById('mainFields').style.display = 'block';
        document.getElementById('healthFields').style.display = 'block';
        
        document.getElementById('editAge').value = user.age || '';
        document.getElementById('editGender').value = user.gender || '';
        document.getElementById('editBlood').value = user.blood || '';
        document.getElementById('editPremiumStatus').value = user.premium_status ? '1' : '0';
        document.getElementById('editHeight').value = user.height || '';
        document.getElementById('editWeight').value = user.weight || '';
        document.getElementById('editSystolic').value = user.blood_pressure_systolic || '';
        document.getElementById('editDiastolic').value = user.blood_pressure_diastolic || '';
        document.getElementById('editHeartRate').value = user.heart_rate || '';
    }
    
    editModal.style.display = 'block';
}

// Delete Functions
function deleteAdminUser(userId) {
    const user = adminUsersData.find(u => u.id === userId);
    if (user) {
        showDeleteModal(user, 'admin');
    } else {
        showNotification('Không tìm thấy thông tin người dùng', 'error');
    }
}

function deleteMainUser(userId) {
    const user = mainUsersData.find(u => u.id === userId);
    if (user) {
        showDeleteModal(user, 'main');
    } else {
        showNotification('Không tìm thấy thông tin người dùng', 'error');
    }
}

function deleteFromView() {
    if (currentUserData) {
        showDeleteModal(currentUserData, currentUserData.database);
        closeViewModal();
    }
}

function showDeleteModal(user, database) {
    currentUserData = { ...user, database };
    
    document.getElementById('deleteUserAvatar').textContent = user.avatar;
    document.getElementById('deleteUserName').textContent = user.full_name;
    document.getElementById('deleteUserEmail').textContent = user.email || 'N/A';
    document.getElementById('deleteUserDatabase').textContent = database === 'admin' ? 'Admin Database' : 'Main Database';
    
    deleteModal.style.display = 'block';
}

// Save Functions
async function saveChanges() {
    if (!currentUserData) return;
    
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Add user ID and handle field mapping based on database
    data.id = currentUserData.id;
    
    // Handle field mapping for main database (userName vs username)
    if (currentUserData.database === 'main' && data.username) {
        data.userName = data.username;
        delete data.username;
    }
    
    try {
        document.getElementById('editLoadingIndicator').style.display = 'flex';
        document.getElementById('saveChangesBtn').disabled = true;
        
        const apiUrl = currentUserData.database === 'admin' ? UPDATE_ADMIN_API : UPDATE_MAIN_API;
        const method = 'PUT';
        
        console.log('Sending data to API:', { apiUrl, data });
        
        const response = await fetch(apiUrl, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        console.log('API Response:', result);
        
        if (result.success) {
            showNotification('Cập nhật người dùng thành công!', 'success');
            closeEditModal();
            
            // Update the local data with new information
            if (currentUserData.database === 'admin') {
                // Update admin users array
                const userIndex = adminUsersData.findIndex(u => u.id === currentUserData.id);
                if (userIndex !== -1) {
                    // Merge updated data with existing user data
                    adminUsersData[userIndex] = {
                        ...adminUsersData[userIndex],
                        ...data,
                        updated_at_formatted: new Date().toLocaleString('vi-VN')
                    };
                }
                loadAdminUsers(); // Refresh admin table
            } else {
                // Update main users array
                const userIndex = mainUsersData.findIndex(u => u.id === currentUserData.id);
                if (userIndex !== -1) {
                    // Merge updated data with existing user data
                    mainUsersData[userIndex] = {
                        ...mainUsersData[userIndex],
                        ...data,
                        updated_at_formatted: new Date().toLocaleString('vi-VN')
                    };
                }
                loadMainUsers(); // Refresh main table
            }
        } else {
            showNotification(result.message || 'Có lỗi xảy ra khi cập nhật', 'error');
            console.error('Update failed:', result);
        }
    } catch (error) {
        console.error('Error updating user:', error);
        showNotification('Có lỗi kết nối. Vui lòng thử lại sau.', 'error');
    } finally {
        document.getElementById('editLoadingIndicator').style.display = 'none';
        document.getElementById('saveChangesBtn').disabled = false;
    }
}

async function confirmDelete() {
    if (!currentUserData) return;
    
    try {
        document.getElementById('deleteLoadingIndicator').style.display = 'flex';
        document.getElementById('confirmDeleteBtn').disabled = true;
        
        const apiUrl = currentUserData.database === 'admin' ? 
            `${DELETE_ADMIN_API}?id=${currentUserData.id}` : 
            `${DELETE_MAIN_API}?id=${currentUserData.id}`;
        
        console.log('Deleting user:', { apiUrl, userId: currentUserData.id, database: currentUserData.database });
        
        const response = await fetch(apiUrl, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        console.log('Delete API Response:', result);
        
        if (result.success) {
            showNotification('Xóa người dùng thành công!', 'success');
            closeDeleteModal();
            
            // Remove from local data arrays
            if (currentUserData.database === 'admin') {
                adminUsersData = adminUsersData.filter(u => u.id !== currentUserData.id);
                loadAdminUsers(); // Refresh admin table
            } else {
                mainUsersData = mainUsersData.filter(u => u.id !== currentUserData.id);
                loadMainUsers(); // Refresh main table
            }
        } else {
            showNotification(result.message || 'Có lỗi xảy ra khi xóa', 'error');
            console.error('Delete failed:', result);
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showNotification('Có lỗi kết nối. Vui lòng thử lại sau.', 'error');
    } finally {
        document.getElementById('deleteLoadingIndicator').style.display = 'none';
        document.getElementById('confirmDeleteBtn').disabled = false;
    }
}

// Preview Functions
function previewChanges() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const changes = [];
    Object.entries(data).forEach(([key, value]) => {
        if (value && key !== 'id' && key !== 'database') {
            const oldValue = currentUserData[key] || 'N/A';
            if (value !== oldValue) {
                changes.push({
                    field: key,
                    oldValue: oldValue,
                    newValue: value
                });
            }
        }
    });
    
    if (changes.length === 0) {
        showNotification('Không có thay đổi nào', 'info');
        return;
    }
    
    const changesList = document.getElementById('changesList');
    changesList.innerHTML = '';
    
    changes.forEach(change => {
        const changeItem = document.createElement('div');
        changeItem.className = 'change-item';
        changeItem.innerHTML = `
            <span class="change-field">${change.field}:</span>
            <span class="change-value">${change.oldValue} → ${change.newValue}</span>
        `;
        changesList.appendChild(changeItem);
    });
    
    document.getElementById('changesPreview').style.display = 'block';
}

// Modal Functions
function closeViewModal() {
    viewModal.style.display = 'none';
    currentUserData = null;
}

function closeEditModal() {
    editModal.style.display = 'none';
    document.getElementById('changesPreview').style.display = 'none';
    currentUserData = null;
}

function closeDeleteModal() {
    deleteModal.style.display = 'none';
    currentUserData = null;
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
