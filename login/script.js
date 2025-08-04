document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const API_BASE_URL = 'https://viegrand.site/viegrandwebadmin/php/';
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Validation cơ bản
        if (!username || !password) {
            showMessage('Vui lòng nhập đầy đủ thông tin!', 'error');
            return;
        }
        
        // Disable button và hiển thị loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading-spinner"></span> Đang đăng nhập...';
        
        try {
            const response = await fetch(API_BASE_URL + 'login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'login',
                    username: username,
                    password: password
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Lưu thông tin đăng nhập
                localStorage.setItem('viegrand_user', JSON.stringify(result.data));
                localStorage.setItem('viegrand_token', result.data.session_token);
                
                showMessage('Đăng nhập thành công!', 'success');
                
                // Chuyển hướng sau 1 giây
                setTimeout(() => {
                    window.location.href = '../home/';
                }, 1000);
            } else {
                showMessage(result.message || 'Đăng nhập thất bại!', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            showMessage('Có lỗi xảy ra khi kết nối đến server!', 'error');
        } finally {
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Đăng nhập';
        }
    });
    
    // Hàm hiển thị thông báo
    function showMessage(message, type) {
        // Xóa thông báo cũ nếu có
        const existingMessage = document.querySelector('.message-box');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Tạo thông báo mới
        const messageBox = document.createElement('div');
        messageBox.className = `message-box ${type}`;
        messageBox.innerHTML = `
            <span>${message}</span>
            <button class="close-btn" onclick="this.parentElement.remove()">×</button>
        `;
        
        // Thêm vào form
        loginForm.insertBefore(messageBox, loginForm.firstChild);
        
        // Tự động ẩn sau 5 giây
        setTimeout(() => {
            if (messageBox.parentElement) {
                messageBox.remove();
            }
        }, 5000);
    }
    
    // Thêm hiệu ứng cho các input
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Xóa thông báo lỗi khi người dùng bắt đầu nhập
        input.addEventListener('input', function() {
            const messageBox = document.querySelector('.message-box.error');
            if (messageBox) {
                messageBox.remove();
            }
        });
    });
    
    // Kiểm tra nếu đã đăng nhập
    const existingToken = localStorage.getItem('viegrand_token');
    if (existingToken) {
        // Có thể thêm logic kiểm tra token còn hợp lệ không
        showMessage('Bạn đã đăng nhập rồi. Đang chuyển hướng...', 'info');
        setTimeout(() => {
            window.location.href = '../home/';
        }, 2000);
    }
});