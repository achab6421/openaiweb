// 身份驗證相關JS功能

// DOM元素載入完成後執行
document.addEventListener('DOMContentLoaded', function() {
    // 獲取元素
    const enterButton = document.getElementById('enter-village');
    const authModal = document.getElementById('auth-modal');
    const closeButton = document.querySelector('.close-button');
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const loginError = document.getElementById('login-error');
    const registerError = document.getElementById('register-error');

    // 點擊進入村莊按鈕打開模態框
    enterButton.addEventListener('click', function() {
        authModal.style.display = 'block';
    });

    // 點擊關閉按鈕關閉模態框
    closeButton.addEventListener('click', function() {
        authModal.style.display = 'none';
    });

    // 點擊模態框外部區域關閉
    window.addEventListener('click', function(event) {
        if (event.target === authModal) {
            authModal.style.display = 'none';
        }
    });

    // 標籤頁切換功能
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // 移除所有tab的active類
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // 添加當前點擊tab的active類
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab') + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });

    // 處理登入表單提交
    loginForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const account = document.getElementById('login-account').value;
        const password = document.getElementById('login-password').value;
        
        // 清除之前的錯誤信息
        loginError.textContent = '';
        
        // 發送登入請求
        fetch('api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                account: account,
                password: password
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                loginError.textContent = data.message || '登入失敗，請檢查帳號和密碼。';
            }
        })
        .catch(error => {
            loginError.textContent = '發生錯誤，請稍後再試。';
            console.error('Error:', error);
        });
    });

    // 處理註冊表單提交
    registerForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const username = document.getElementById('register-username').value;
        const account = document.getElementById('register-account').value;
        const password = document.getElementById('register-password').value;
        const confirmPassword = document.getElementById('register-confirm').value;
        
        // 清除之前的錯誤信息
        registerError.textContent = '';
        
        // 驗證密碼
        if (password !== confirmPassword) {
            registerError.textContent = '兩次輸入的密碼不一致';
            return;
        }
        
        // 發送註冊請求
        fetch('api/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username,
                account: account,
                password: password
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('註冊成功！請登入。');
                // 切換到登入頁
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                document.querySelector('[data-tab="login"]').classList.add('active');
                document.getElementById('login-tab').classList.add('active');
                // 清空表單
                registerForm.reset();
            } else {
                registerError.textContent = data.message || '註冊失敗，請稍後再試。';
            }
        })
        .catch(error => {
            registerError.textContent = '發生錯誤，請稍後再試。';
            console.error('Error:', error);
        });
    });
});
