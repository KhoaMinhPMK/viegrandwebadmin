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

    // Add user buttons with enhanced feedback
    document.getElementById('addAdminBtn').addEventListener('click', (e) => {
        addButtonClickEffect(e.target);
        setTimeout(() => openAddModal('admin'), 150);
    });
    document.getElementById('addMainBtn').addEventListener('click', (e) => {
        addButtonClickEffect(e.target);
        setTimeout(() => openAddModal('main'), 150);
    });

    // Add modal buttons
    document.getElementById('closeAddModal').addEventListener('click', closeAddModal);
    document.getElementById('saveAddBtn').addEventListener('click', saveNewUser);
    document.getElementById('cancelAddBtn').addEventListener('click', closeAddModal);
    
    // Add form submit event handler to prevent default submission
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveNewUser();
    });

    // Role change handler for edit modal - dynamically enable/disable premium status field
    document.getElementById('editUserRole').addEventListener('change', function(e) {
        const selectedRole = e.target.value;
        const premiumStatusField = document.getElementById('editPremiumStatus');
        const premiumStatusNote = document.getElementById('premiumStatusNote');
        
        if (selectedRole === 'elderly') {
            // Disable premium status editing for elderly users
            premiumStatusField.disabled = true;
            premiumStatusField.title = 'Người cao tuổi không thể thay đổi trạng thái Premium';
            
            // Add visual indication that the field is disabled
            premiumStatusField.style.backgroundColor = '#f8f9fa';
            premiumStatusField.style.color = '#6c757d';
            
            // Show the informational note
            premiumStatusNote.style.display = 'block';
            
            // If currently premium, keep it; if regular, keep it regular
            // Don't change the current value when disabling
        } else {
            // Enable premium status editing for relative users
            premiumStatusField.disabled = false;
            premiumStatusField.title = '';
            
            // Reset visual styling
            premiumStatusField.style.backgroundColor = '';
            premiumStatusField.style.color = '';
            
            // Hide the informational note
            premiumStatusNote.style.display = 'none';
        }
    });

    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === viewModal) closeViewModal();
        if (e.target === editModal) closeEditModal();
        if (e.target === deleteModal) closeDeleteModal();
        if (e.target === document.getElementById('addUserModal')) closeAddModal();
        if (e.target === document.getElementById('premiumDetailsModal')) closePremiumModal();
    });
    
    // Event delegation for premium badge clicks
    document.addEventListener('click', function(e) {
        console.log('Document click detected on:', e.target);
        console.log('Target classes:', e.target.className);
        console.log('Parent elements:', e.target.parentElement);
        
        // Check if clicked element or its parent is a clickable premium badge
        const premiumBadge = e.target.closest('.premium-badge.clickable');
        if (premiumBadge) {
            console.log('Premium badge found:', premiumBadge);
            console.log('Premium badge classes:', premiumBadge.className);
            e.preventDefault();
            e.stopPropagation();
            
            const userId = premiumBadge.getAttribute('data-user-id');
            const startDate = premiumBadge.getAttribute('data-start-date');
            const endDate = premiumBadge.getAttribute('data-end-date');
            const premiumKey = premiumBadge.getAttribute('data-premium-key');
            const role = premiumBadge.getAttribute('data-role');
            
            console.log('Premium badge clicked with data:', { userId, startDate, endDate, premiumKey, role });
            
            if (userId) {
                showPremiumDetails(userId, startDate, endDate, premiumKey, role);
            } else {
                console.error('No user ID found in premium badge');
                alert('Lỗi: Không tìm thấy ID người dùng');
            }
        }
        
        // Additional fallback for direct clicks on premium text or icon
        if (e.target.closest('.premium-badge.active')) {
            const badge = e.target.closest('.premium-badge.active');
            console.log('Fallback premium badge click detected:', badge);
            
            if (badge.classList.contains('clickable')) {
                const userId = badge.getAttribute('data-user-id');
                const startDate = badge.getAttribute('data-start-date');
                const endDate = badge.getAttribute('data-end-date');
                const premiumKey = badge.getAttribute('data-premium-key');
                const role = badge.getAttribute('data-role');
                
                console.log('Fallback click with data:', { userId, startDate, endDate, premiumKey, role });
                
                if (userId) {
                    e.preventDefault();
                    e.stopPropagation();
                    showPremiumDetails(userId, startDate, endDate, premiumKey, role);
                }
            }
        }
    });
    
    // Premium modal event listeners
    document.getElementById('closePremiumModal').addEventListener('click', closePremiumModal);
    document.getElementById('closePremiumModalBtn').addEventListener('click', closePremiumModal);
    document.getElementById('editEndDateBtn').addEventListener('click', showEditEndDateForm);
    document.getElementById('saveEndDateBtn').addEventListener('click', saveNewEndDate);
    document.getElementById('cancelEndDateBtn').addEventListener('click', hideEditEndDateForm);
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

function formatPrivateKey(privateKey) {
    if (!privateKey) {
        return '<span class="private-key-empty">No key</span>';
    }
    
    // Truncate long keys and add ellipsis
    const maxLength = 20;
    const displayKey = privateKey.length > maxLength ? 
        privateKey.substring(0, maxLength) + '...' : privateKey;
    
    return `
        <div class="private-key-container">
            <span class="private-key-text" title="${privateKey}">${displayKey}</span>
            <button class="copy-key-btn" onclick="copyPrivateKey('${privateKey}')" title="Copy private key">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    `;
}

function copyPrivateKey(key) {
    if (!key) {
        showNotification('No key to copy', 'error');
        return;
    }
    
    // Create a temporary textarea to copy the text
    const textarea = document.createElement('textarea');
    textarea.value = key;
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showNotification('Private key copied to clipboard!', 'success');
    } catch (err) {
        console.error('Error copying private key:', err);
        showNotification('Failed to copy private key', 'error');
    }
    
    document.body.removeChild(textarea);
}

function displayMainUsers(users) {
    const tbody = document.getElementById('mainUsersTableBody');
    tbody.innerHTML = '';

    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Format health info
        const healthInfoHtml = formatHealthInfoHtml(user.health_info);
        
        // Format private key with copy button
        const privateKeyHtml = formatPrivateKey(user.private_key);
        
        // Format premium status with clickable functionality for premium users
        const premiumStatus = user.premium_status ? 
            `<span class="premium-badge active clickable" data-user-id="${user.id}" data-start-date="${user.premium_start_date || ''}" data-end-date="${user.premium_end_date || ''}" data-premium-key="${user.premium_key || ''}" data-role="${user.role || ''}">
                <i class="fas fa-crown"></i> Premium
            </span>` :
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
                <span class="role-badge ${user.user_role}">${user.user_role_display || 'N/A'}</span>
            </td>
            <td>${privateKeyHtml}</td>
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
        
        // Add direct click handler to premium badge if it exists
        if (user.premium_status) {
            const premiumBadgeElement = row.querySelector('.premium-badge.clickable');
            if (premiumBadgeElement) {
                console.log('Adding direct click handler to premium badge for user:', user.id);
                premiumBadgeElement.addEventListener('click', function(e) {
                    console.log('Direct premium badge click for user:', user.id);
                    e.preventDefault();
                    e.stopPropagation();
                    showPremiumDetails(user.id, user.premium_start_date, user.premium_end_date, user.premium_key, user.role);
                });
                
                // Also add a visual indicator that it's clickable
                premiumBadgeElement.style.cursor = 'pointer';
                premiumBadgeElement.title = 'Nhấp để xem chi tiết Premium';
            }
        }
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
        document.getElementById('editRoleGroup').style.display = 'none';  // Hide user role field for admin
        
        document.getElementById('editRole').value = user.role || '';
        document.getElementById('editStatus').value = user.status || '';
    } else {
        document.getElementById('adminFields').style.display = 'none';
        document.getElementById('mainFields').style.display = 'block';
        document.getElementById('healthFields').style.display = 'block';
        document.getElementById('editRoleGroup').style.display = 'block';  // Show user role field for main database
        
        document.getElementById('editAge').value = user.age || '';
        document.getElementById('editGender').value = user.gender || '';
        document.getElementById('editBlood').value = user.blood || '';
        document.getElementById('editUserRole').value = user.user_role || 'relative';  // Set user role, default to 'relative'
        console.log('Setting role field value:', user.user_role || 'relative', 'from user data:', user.user_role);
        
        // Handle premium status based on user role
        const premiumStatusField = document.getElementById('editPremiumStatus');
        const premiumStatusNote = document.getElementById('premiumStatusNote');
        const userRole = user.user_role || 'relative';
        
        if (userRole === 'elderly') {
            // Disable premium status editing for elderly users
            premiumStatusField.disabled = true;
            premiumStatusField.value = user.premium_status ? '1' : '0';
            premiumStatusField.title = 'Người cao tuổi không thể thay đổi trạng thái Premium';
            
            // Add visual indication that the field is disabled
            premiumStatusField.style.backgroundColor = '#f8f9fa';
            premiumStatusField.style.color = '#6c757d';
            
            // Show the informational note
            premiumStatusNote.style.display = 'block';
        } else {
            // Enable premium status editing for relative users
            premiumStatusField.disabled = false;
            premiumStatusField.value = user.premium_status ? '1' : '0';
            premiumStatusField.title = '';
            
            // Reset visual styling
            premiumStatusField.style.backgroundColor = '';
            premiumStatusField.style.color = '';
            
            // Hide the informational note
            premiumStatusNote.style.display = 'none';
        }
        
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
    
    // Debug: Log all form data
    console.log('All form data from FormData:', data);
    console.log('Form data keys:', Object.keys(data));
    
    // Add user ID and handle field mapping based on database
    data.id = currentUserData.id;
    
    // Handle field mapping for main database (userName vs username)
    if (currentUserData.database === 'main' && data.username) {
        data.userName = data.username;
        delete data.username;
    }
    
    // Ensure role field is included for main database users
    if (currentUserData.database === 'main') {
        const roleField = document.getElementById('editUserRole');
        const roleGroup = document.getElementById('editRoleGroup');
        console.log('Role group visibility:', roleGroup ? roleGroup.style.display : 'group not found');
        console.log('Role field element:', roleField);
        console.log('Role field value:', roleField ? roleField.value : 'field not found');
        console.log('Role field in form data:', data.role);
        
        if (roleField && roleField.value) {
            data.role = roleField.value;
        } else if (roleField) {
            // If no value is selected, default to 'relative'
            data.role = 'relative';
        }
        console.log('Role being sent to API:', data.role);
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
        
        // Check if this was a premium upgrade or downgrade
        if (result.success && result.data) {
            if (result.data.premium_upgraded) {
                const startDate = result.data.premium_start_date;
                const endDate = result.data.premium_end_date;
                
                // Show premium upgrade notification
                const message = `🎉 Tài khoản đã được nâng cấp lên Premium!\n\n` +
                              `📅 Ngày bắt đầu: ${formatDate(startDate)}\n` +
                              `📅 Ngày kết thúc: ${formatDate(endDate)}\n\n` +
                              `Gói Premium có hiệu lực trong 30 ngày.`;
                
                alert(message);
            } else if (result.data.premium_downgraded) {
                // Show premium downgrade notification
                const message = `⬇️ Tài khoản đã được chuyển từ Premium về Regular.\n\n` +
                              `Các quyền lợi Premium đã bị hủy bỏ.`;
                
                alert(message);
            }
        }
        
        console.log('Edit operation completed:', result.success ? 'success' : 'failed');
        closeEditModal();
        
        // Always refresh the page after edit operation
        window.location.reload();
    } catch (error) {
        console.error('Error updating user:', error);
        closeEditModal();
        
        // Always refresh the page after edit operation
        window.location.reload();
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
        
        console.log('Delete operation completed:', result.success ? 'success' : 'failed');
        closeDeleteModal();
        
        // Always refresh the page after delete operation
        window.location.reload();
    } catch (error) {
        console.error('Error deleting user:', error);
        closeDeleteModal();
        
        // Always refresh the page after delete operation
        window.location.reload();
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

// Add User Modal Functions
function openAddModal(database) {
    console.log('Opening add modal for database:', database); // Debug log
    
    const addModal = document.getElementById('addUserModal');
    const addModalTitle = document.getElementById('addModalTitle');
    const addUserDatabase = document.getElementById('addUserDatabase');
    const addAdminFields = document.getElementById('addAdminFields');
    const addMainFields = document.getElementById('addMainFields');
    const addHealthFields = document.getElementById('addHealthFields');
    
    // Check if elements exist
    if (!addModal) {
        console.error('Add modal element not found');
        return;
    }
    
    // Clear form
    document.getElementById('addUserForm').reset();
    
    // Set database type
    addUserDatabase.value = database;
    
    // Update modal title and show appropriate fields
    if (database === 'admin') {
        addModalTitle.textContent = 'Thêm Admin mới';
        addAdminFields.style.display = 'flex';
        addMainFields.style.display = 'none';
        addHealthFields.style.display = 'none';
        document.getElementById('addRoleGroup').style.display = 'none';  // Hide role field for admin
        
        // Set required attributes for admin fields
        document.getElementById('addRole').required = true;
        document.getElementById('addStatus').required = true;
        document.getElementById('addFullName').required = true;  // Required for admin
        document.getElementById('addFullNameRequired').style.display = 'inline';  // Show required indicator
        document.getElementById('addFullName').placeholder = 'Nhập họ và tên đầy đủ';
        document.getElementById('addFullName').parentElement.style.display = 'block';  // Show full name field
        
        // Remove required attributes for main fields
        document.getElementById('addAge').required = false;
        document.getElementById('addGender').required = false;
    } else {
        addModalTitle.textContent = 'Thêm User mới';
        addAdminFields.style.display = 'none';
        addMainFields.style.display = 'flex';
        addHealthFields.style.display = 'flex';
        document.getElementById('addRoleGroup').style.display = 'block';  // Show role field for main database
        
        // Remove required attributes for admin fields
        document.getElementById('addRole').required = false;
        document.getElementById('addStatus').required = false;
        document.getElementById('addFullName').required = false;  // Not needed for main DB
        document.getElementById('addFullNameRequired').style.display = 'none';  // Hide required indicator
        document.getElementById('addFullName').parentElement.style.display = 'none';  // Hide full name field completely
        
        // Set appropriate attributes for main fields (none are required except basic info)
        document.getElementById('addAge').required = false;
        document.getElementById('addGender').required = false;
    }
    
    addModal.style.display = 'block';
    console.log('Add modal opened successfully'); // Debug log
}

function closeAddModal() {
    console.log('Closing add modal'); // Debug log
    const addModal = document.getElementById('addUserModal');
    if (addModal) {
        addModal.style.display = 'none';
        document.getElementById('addUserForm').reset();
        console.log('Add modal closed successfully'); // Debug log
    } else {
        console.error('Add modal element not found when trying to close');
    }
}

async function saveNewUser() {
    console.log('saveNewUser function called'); // Debug log
    
    const database = document.getElementById('addUserDatabase').value;
    const loadingIndicator = document.getElementById('addLoadingIndicator');
    const saveBtn = document.getElementById('saveAddBtn');
    
    console.log('Database type:', database); // Debug log
    
    // Add loading state to save button
    setButtonLoading(saveBtn, true);
    
    // Basic form validation
    const username = document.getElementById('addUsername').value.trim();
    const email = document.getElementById('addEmail').value.trim();
    const fullName = document.getElementById('addFullName').value.trim();
    const password = document.getElementById('addPassword').value;
    
    // For admin database: need username, email, full_name, password
    // For main database: need username, email, password (ignore full_name completely)
    if (database === 'admin') {
        if (!username || !email || !fullName || !password) {
            setButtonLoading(saveBtn, false);
            alert('Vui lòng điền đầy đủ các trường bắt buộc (Tên đăng nhập, Email, Họ và tên, Mật khẩu)');
            return;
        }
        
        const role = document.getElementById('addRole').value;
        const status = document.getElementById('addStatus').value;
        if (!role || !status) {
            setButtonLoading(saveBtn, false);
            alert('Vui lòng chọn Vai trò và Trạng thái cho Admin');
            return;
        }
        } else {
            // Main database validation - only need username, email, password (ignore full_name)
            if (!username || !email || !password) {
                setButtonLoading(saveBtn, false);
                alert('Vui lòng điền đầy đủ các trường bắt buộc (Tên đăng nhập, Email, Mật khẩu)');
                return;
            }
        }    // Show loading
    loadingIndicator.style.display = 'flex';
    saveBtn.disabled = true;
    
    try {
        // Collect form data
        const formData = new FormData();
        
        // Basic fields for both databases
        formData.append('username', username);
        formData.append('email', email);
        formData.append('full_name', fullName);
        formData.append('phone', document.getElementById('addPhone').value || '');
        formData.append('password', password);
        
        if (database === 'admin') {
            // Admin specific fields
            formData.append('role', document.getElementById('addRole').value);
            formData.append('status', document.getElementById('addStatus').value);
        } else {
            // Main database specific fields
            formData.append('role', document.getElementById('addRole').value || 'relative');  // Add role field
            formData.append('age', document.getElementById('addAge').value || null);
            formData.append('gender', document.getElementById('addGender').value || '');
            formData.append('blood', document.getElementById('addBlood').value || '');
            formData.append('premium_status', document.getElementById('addPremiumStatus').value || '0');
            
            // Health fields
            formData.append('height', document.getElementById('addHeight').value || null);
            formData.append('weight', document.getElementById('addWeight').value || null);
            formData.append('blood_pressure_systolic', document.getElementById('addSystolic').value || null);
            formData.append('blood_pressure_diastolic', document.getElementById('addDiastolic').value || null);
            formData.append('heart_rate', document.getElementById('addHeartRate').value || null);
        }
        
        // API URL
        const apiUrl = database === 'admin' 
            ? 'https://viegrand.site/viegrandwebadmin/php/add_user_admin.php'
            : 'https://viegrand.site/viegrandwebadmin/php/add_user_main.php';
        
        console.log('Sending request to:', apiUrl); // Debug log
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status); // Debug log
        console.log('Response headers:', response.headers); // Debug log
        
        // Get response text first to see what we're actually receiving
        const responseText = await response.text();
        console.log('Raw response:', responseText); // Debug log
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was:', responseText);
            setButtonLoading(saveBtn, false);
            alert('Lỗi: Server trả về phản hồi không hợp lệ. Chi tiết: ' + responseText.substring(0, 200));
            return;
        }
        
        console.log('API response:', result); // Debug log
        
        if (result.success) {
            console.log('User added successfully');
            setButtonSuccess(saveBtn);
            setTimeout(() => {
                closeAddModal();
                // Refresh the page
                window.location.reload();
            }, 1000);
        } else {
            console.error('Add failed:', result.message);
            setButtonLoading(saveBtn, false);
            alert('Lỗi: ' + result.message);
            // Still refresh the page even if there's an error
            setTimeout(() => window.location.reload(), 1000);
        }
        
    } catch (error) {
        console.error('Error adding user:', error);
        setButtonLoading(saveBtn, false);
        alert('Có lỗi xảy ra khi thêm người dùng: ' + error.message);
        // Refresh the page even if there's an error
        setTimeout(() => window.location.reload(), 1000);
    } finally {
        loadingIndicator.style.display = 'none';
    }
}

function logout() {
    if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        localStorage.removeItem('userInfo');
        window.location.href = '../login/';
    }
}

// Enhanced UI helper functions for buttons
function addButtonClickEffect(button) {
    button.style.transform = 'scale(0.95)';
    setTimeout(() => {
        button.style.transform = '';
    }, 150);
}

function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.classList.add('loading');
        button.disabled = true;
        const icon = button.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-spinner';
        }
        const span = button.querySelector('span');
        if (span) {
            span.textContent = 'Đang xử lý...';
        }
    } else {
        button.classList.remove('loading');
        button.disabled = false;
        // Reset button content based on button ID
        if (button.id === 'saveAddBtn') {
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-save';
            }
            const span = button.querySelector('span');
            if (span) {
                span.textContent = 'Lưu';
            }
        }
    }
}

function setButtonSuccess(button) {
    button.classList.remove('loading');
    button.classList.add('success');
    button.disabled = true;
    const icon = button.querySelector('i');
    if (icon) {
        icon.className = 'fas fa-check';
    }
    const span = button.querySelector('span');
    if (span) {
        span.textContent = 'Thành công!';
    }
}

// Premium Details Modal Functions
let currentPremiumUserId = null;

function showPremiumDetails(userId, startDate, endDate, premiumKey = null, userRole = null) {
    console.log('Opening premium details for user:', userId);
    console.log('Start date:', startDate);
    console.log('End date:', endDate);
    console.log('Premium key:', premiumKey);
    console.log('User role:', userRole);
    
    // Validate inputs
    if (!userId) {
        console.error('No user ID provided');
        alert('Lỗi: Không tìm thấy thông tin người dùng');
        return;
    }
    
    // Store current user ID for editing
    currentPremiumUserId = userId;
    
    const modal = document.getElementById('premiumDetailsModal');
    if (!modal) {
        console.error('Premium details modal not found');
        alert('Lỗi: Không tìm thấy modal thông tin Premium');
        return;
    }
    
    // Show/hide elderly management section based on user role
    const elderlySection = document.getElementById('elderlyManagementSection');
    if (elderlySection) {
        if (userRole === 'relative') {
            elderlySection.style.display = 'flex';
        } else {
            elderlySection.style.display = 'none';
        }
    }
    
    // Reset edit form visibility
    hideEditEndDateForm();
    
    // Display premium key
    const premiumKeyElement = document.getElementById('premiumKey');
    if (premiumKeyElement) {
        premiumKeyElement.textContent = premiumKey || 'Chưa có thông tin';
        premiumKeyElement.title = premiumKey ? 'Click để sao chép Premium Key' : '';
        
        // Add click-to-copy functionality for premium key
        if (premiumKey) {
            premiumKeyElement.style.cursor = 'pointer';
            premiumKeyElement.onclick = function() {
                navigator.clipboard.writeText(premiumKey).then(function() {
                    // Show temporary feedback
                    const originalText = premiumKeyElement.textContent;
                    premiumKeyElement.textContent = 'Đã sao chép!';
                    premiumKeyElement.style.backgroundColor = '#d4edda';
                    premiumKeyElement.style.color = '#155724';
                    
                    setTimeout(function() {
                        premiumKeyElement.textContent = originalText;
                        premiumKeyElement.style.backgroundColor = '';
                        premiumKeyElement.style.color = '';
                    }, 1500);
                }).catch(function(err) {
                    console.error('Could not copy premium key: ', err);
                    alert('Không thể sao chép Premium Key');
                });
            };
        } else {
            premiumKeyElement.style.cursor = 'default';
            premiumKeyElement.onclick = null;
        }
    }
    
    // Format and display dates
    const startDateElement = document.getElementById('premiumStartDate');
    const endDateElement = document.getElementById('premiumEndDate');
    
    if (!startDateElement || !endDateElement) {
        console.error('Premium date elements not found');
        alert('Lỗi: Không tìm thấy các phần tử hiển thị ngày tháng');
        return;
    }
    
    // Clean and validate dates
    const cleanStartDate = startDate && startDate !== 'null' && startDate !== 'undefined' && startDate.trim() !== '' ? startDate.trim() : null;
    const cleanEndDate = endDate && endDate !== 'null' && endDate !== 'undefined' && endDate.trim() !== '' ? endDate.trim() : null;
    
    // Format dates or show "Chưa có thông tin"
    startDateElement.textContent = cleanStartDate ? formatDate(cleanStartDate) : 'Chưa có thông tin';
    endDateElement.textContent = cleanEndDate ? formatDate(cleanEndDate) : 'Chưa có thông tin';
    
    // Store original end date for editing
    const dateInput = document.getElementById('newEndDate');
    if (dateInput && cleanEndDate) {
        try {
            // Convert to local datetime format for input
            const date = new Date(cleanEndDate);
            if (!isNaN(date.getTime())) {
                const localDateTime = new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
                dateInput.value = localDateTime;
            }
        } catch (error) {
            console.error('Error setting date input:', error);
        }
    }
    
    // Calculate time remaining and status
    updatePremiumStatus(cleanEndDate);
    
    modal.style.display = 'block';
}
    
    

function closePremiumModal() {
    const modal = document.getElementById('premiumDetailsModal');
    modal.style.display = 'none';
    hideEditEndDateForm();
    currentPremiumUserId = null;
}

function updatePremiumStatus(endDate) {
    const timeRemainingElement = document.getElementById('premiumTimeRemaining');
    const statusElement = document.getElementById('premiumStatusText');
    
    if (!timeRemainingElement || !statusElement) {
        console.error('Premium status elements not found');
        return;
    }
    
    if (endDate && endDate !== 'null' && endDate !== 'undefined' && endDate.trim() !== '') {
        try {
            const now = new Date();
            const end = new Date(endDate);
            
            if (isNaN(end.getTime())) {
                throw new Error('Invalid date format');
            }
            
            const timeDiff = end.getTime() - now.getTime();
            
            if (timeDiff > 0) {
                const days = Math.ceil(timeDiff / (1000 * 3600 * 24));
                timeRemainingElement.textContent = `${days} ngày`;
                statusElement.textContent = 'Đang hoạt động';
                statusElement.className = 'status-active';
            } else {
                timeRemainingElement.textContent = 'Đã hết hạn';
                statusElement.textContent = 'Đã hết hạn';
                statusElement.className = 'status-expired';
            }
        } catch (error) {
            console.error('Error parsing end date:', error);
            timeRemainingElement.textContent = 'Lỗi định dạng ngày';
            statusElement.textContent = 'Không xác định';
            statusElement.className = '';
        }
    } else {
        timeRemainingElement.textContent = 'Chưa có thông tin';
        statusElement.textContent = 'Không xác định';
        statusElement.className = '';
    }
}

function showEditEndDateForm() {
    document.getElementById('editEndDateForm').style.display = 'block';
    document.getElementById('editEndDateBtn').style.display = 'none';
}

function hideEditEndDateForm() {
    document.getElementById('editEndDateForm').style.display = 'none';
    document.getElementById('editEndDateBtn').style.display = 'inline-block';
}

async function saveNewEndDate() {
    if (!currentPremiumUserId) {
        alert('Lỗi: Không tìm thấy thông tin người dùng');
        return;
    }
    
    const newEndDate = document.getElementById('newEndDate').value;
    if (!newEndDate) {
        alert('Vui lòng chọn ngày kết thúc mới');
        return;
    }
    
    const saveBtn = document.getElementById('saveEndDateBtn');
    const editForm = document.getElementById('editEndDateForm');
    
    // Show loading state
    editForm.classList.add('loading-date-update');
    saveBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('userId', currentPremiumUserId);
        formData.append('newEndDate', newEndDate);
        
        const response = await fetch('https://viegrand.site/viegrandwebadmin/php/update_premium_date.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update the display with new date
            document.getElementById('premiumEndDate').textContent = formatDate(result.data.premium_end_date);
            
            // Update status and time remaining
            updatePremiumStatus(result.data.premium_end_date);
            
            // Hide edit form
            hideEditEndDateForm();
            
            // Show success message
            alert('Cập nhật ngày kết thúc Premium thành công!');
            
        } else {
            alert('Lỗi: ' + result.message);
        }
        
    } catch (error) {
        console.error('Error updating premium end date:', error);
        alert('Có lỗi xảy ra khi cập nhật ngày kết thúc: ' + error.message);
    } finally {
        // Remove loading state
        editForm.classList.remove('loading-date-update');
        saveBtn.disabled = false;
    }
}

function formatDate(dateString) {
    if (!dateString || dateString === 'null') return 'Chưa có thông tin';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return 'Ngày không hợp lệ';
    }
}

// ========== ELDERLY MANAGEMENT FUNCTIONALITY ==========

function showAddElderlyForm() {
    const addForm = document.getElementById('addElderlyForm');
    const addButton = document.getElementById('addElderlyButton');
    
    if (addForm && addButton) {
        addForm.style.display = 'block';
        addButton.style.display = 'none';
        
        // Focus on input field
        const keyInput = document.getElementById('elderlyPrivateKey');
        if (keyInput) {
            keyInput.focus();
        }
    }
}

function hideAddElderlyForm() {
    const addForm = document.getElementById('addElderlyForm');
    const addButton = document.getElementById('addElderlyButton');
    
    if (addForm && addButton) {
        addForm.style.display = 'none';
        addButton.style.display = 'block';
        
        // Clear input
        const keyInput = document.getElementById('elderlyPrivateKey');
        if (keyInput) {
            keyInput.value = '';
        }
    }
}

async function addElderlyUser() {
    const keyInput = document.getElementById('elderlyPrivateKey');
    if (!keyInput) {
        alert('Lỗi: Không tìm thấy trường nhập private key');
        return;
    }
    
    const privateKey = keyInput.value.trim();
    if (!privateKey) {
        alert('Vui lòng nhập private key của người cao tuổi');
        keyInput.focus();
        return;
    }
    
    if (!currentPremiumUserId) {
        alert('Lỗi: Không tìm thấy thông tin người dùng hiện tại');
        return;
    }
    
    try {
        // Show loading state
        const addButton = document.querySelector('#addElderlyForm .btn-primary');
        if (addButton) {
            addButton.disabled = true;
            addButton.textContent = 'Đang thêm...';
        }
        
        const response = await fetch('../php/add_elderly_to_premium.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                relative_user_id: currentPremiumUserId,
                elderly_private_key: privateKey
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Thêm người cao tuổi thành công!');
            hideAddElderlyForm();
            
            // Refresh elderly list
            await loadElderlyList();
        } else {
            alert('Lỗi: ' + result.message);
        }
        
    } catch (error) {
        console.error('Error adding elderly user:', error);
        alert('Có lỗi xảy ra khi thêm người cao tuổi: ' + error.message);
    } finally {
        // Reset button state
        const addButton = document.querySelector('#addElderlyForm .btn-primary');
        if (addButton) {
            addButton.disabled = false;
            addButton.textContent = 'Thêm';
        }
    }
}

async function removeElderlyUser(elderlyPrivateKey) {
    if (!confirm('Bạn có chắc chắn muốn xóa người cao tuổi này khỏi gói Premium?')) {
        return;
    }
    
    if (!currentPremiumUserId) {
        alert('Lỗi: Không tìm thấy thông tin người dùng hiện tại');
        return;
    }
    
    try {
        const response = await fetch('../php/remove_elderly_from_premium.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                relative_user_id: currentPremiumUserId,
                elderly_private_key: elderlyPrivateKey
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Đã xóa người cao tuổi khỏi gói Premium');
            
            // Refresh elderly list
            await loadElderlyList();
        } else {
            alert('Lỗi: ' + result.message);
        }
        
    } catch (error) {
        console.error('Error removing elderly user:', error);
        alert('Có lỗi xảy ra khi xóa người cao tuổi: ' + error.message);
    }
}

async function loadElderlyList() {
    if (!currentPremiumUserId) {
        console.error('No current premium user ID');
        return;
    }
    
    try {
        const response = await fetch(`../php/get_elderly_in_premium.php?user_id=${currentPremiumUserId}`);
        const result = await response.json();
        
        const elderlyList = document.getElementById('elderlyList');
        if (!elderlyList) {
            console.error('Elderly list container not found');
            return;
        }
        
        if (result.success && result.data && result.data.length > 0) {
            // Display elderly users
            elderlyList.innerHTML = result.data.map(elderly => `
                <div class="elderly-item">
                    <div class="elderly-info">
                        <div class="elderly-private-key">${elderly.private_key}</div>
                        <div class="elderly-user-info">
                            ${elderly.full_name || 'Chưa có tên'} 
                            ${elderly.phone ? `• ${elderly.phone}` : ''}
                        </div>
                    </div>
                    <button class="remove-elderly-btn" onclick="removeElderlyUser('${elderly.private_key}')" 
                            title="Xóa người cao tuổi">
                        ×
                    </button>
                </div>
            `).join('');
        } else {
            // Show empty state
            elderlyList.innerHTML = `
                <div class="elderly-empty-state">
                    Chưa có người cao tuổi nào trong gói Premium này
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Error loading elderly list:', error);
        const elderlyList = document.getElementById('elderlyList');
        if (elderlyList) {
            elderlyList.innerHTML = `
                <div class="elderly-empty-state">
                    Lỗi khi tải danh sách người cao tuổi
                </div>
            `;
        }
    }
}

// Initialize elderly list when premium details modal is opened
document.addEventListener('DOMContentLoaded', function() {
    // Override the showPremiumDetails function to include elderly list loading
    const originalShowPremiumDetails = window.showPremiumDetails;
    window.showPremiumDetails = function(userId, startDate, endDate, premiumKey = null, userRole = null) {
        // Call the original function
        originalShowPremiumDetails(userId, startDate, endDate, premiumKey, userRole);
        
        // Load elderly list after modal is shown (only for relative users)
        if (userRole === 'relative') {
            setTimeout(() => {
                loadElderlyList();
            }, 100);
        }
    };
});
