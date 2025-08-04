document.addEventListener('DOMContentLoaded', function() {
    // Xóa tất cả dữ liệu đăng nhập cũ khi load trang
    localStorage.clear();
    sessionStorage.clear();
    
    const loginForm = document.getElementById('loginForm');
    const API_BASE_URL = 'https://viegrand.site/viegrandwebadmin/php/';
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Validation cơ bản
        if (!username || !password) {
            showMessage('<i class="fas fa-exclamation-triangle"></i> Vui lòng nhập đầy đủ thông tin đăng nhập!', 'error');
            return;
        }
        
        if (username.length < 3) {
            showMessage('<i class="fas fa-exclamation-triangle"></i> Tên đăng nhập phải có ít nhất 3 ký tự!', 'error');
            return;
        }
        
        if (password.length < 3) {
            showMessage('<i class="fas fa-exclamation-triangle"></i> Mật khẩu phải có ít nhất 3 ký tự!', 'error');
            return;
        }
        
        // Disable button và hiển thị loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading-spinner"></span> Đang đăng nhập...';
        
        try {
            console.log('Attempting login with API:', API_BASE_URL + 'login.php');
            
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
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('API Response:', result);
            
            if (result.success) {
                // Không lưu thông tin đăng nhập để bắt buộc đăng nhập lại mỗi lần
                // localStorage.setItem('viegrand_user', JSON.stringify(result.data));
                // localStorage.setItem('viegrand_token', result.data.session_token);
                
                showMessage('<i class="fas fa-check-circle"></i> Đăng nhập thành công!', 'success');
                
                // Chuyển hướng sau 1.5 giây
                setTimeout(() => {
                    window.location.href = '../home/';
                }, 1500);
            } else {
                // Hiển thị thông báo lỗi cụ thể từ server
                let errorMessage = result.message || 'Đăng nhập thất bại!';
                
                // Thêm icon cho các loại lỗi khác nhau
                if (errorMessage.includes('mật khẩu')) {
                    errorMessage = '<i class="fas fa-lock"></i> ' + errorMessage;
                } else if (errorMessage.includes('tài khoản') || errorMessage.includes('khóa')) {
                    errorMessage = '<i class="fas fa-ban"></i> ' + errorMessage;
                } else if (errorMessage.includes('không đúng')) {
                    errorMessage = '<i class="fas fa-times-circle"></i> ' + errorMessage;
                } else {
                    errorMessage = '<i class="fas fa-exclamation-triangle"></i> ' + errorMessage;
                }
                
                showMessage(errorMessage, 'error');
                
                // Thêm hiệu ứng shake cho form khi sai thông tin
                const loginContainer = document.querySelector('.login-container');
                loginContainer.classList.add('shake');
                setTimeout(() => {
                    loginContainer.classList.remove('shake');
                }, 600);
                
                // Focus vào input username để nhập lại
                document.getElementById('username').focus();
                
                // Xóa mật khẩu để bảo mật
                document.getElementById('password').value = '';
            }
        } catch (error) {
            console.error('Login error:', error);
            
            // Xử lý các loại lỗi khác nhau
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                showMessage('Không thể kết nối đến server. Vui lòng kiểm tra kết nối internet!', 'error');
            } else if (error.message.includes('HTTP error')) {
                showMessage('Server trả về lỗi. Vui lòng thử lại sau!', 'error');
            } else {
                showMessage('Có lỗi xảy ra khi kết nối đến server!', 'error');
            }
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
    
    // Test API Button
    const testApiBtn = document.getElementById('testApiBtn');
    testApiBtn.addEventListener('click', async function() {
        const originalText = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<span class="loading-spinner"></span> Testing...';
        
        try {
            console.log('Testing API connection to:', API_BASE_URL + 'login.php');
            
            const response = await fetch(API_BASE_URL + 'login.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            console.log('Test response status:', response.status);
            
            if (response.ok) {
                const result = await response.json();
                console.log('Test response data:', result);
                showMessage(`API kết nối thành công! Version: ${result.version || 'N/A'}`, 'success');
            } else {
                showMessage(`API trả về lỗi: ${response.status}`, 'error');
            }
        } catch (error) {
            console.error('API test error:', error);
            showMessage('Không thể kết nối đến API!', 'error');
        } finally {
            this.disabled = false;
            this.innerHTML = originalText;
        }
    });
});