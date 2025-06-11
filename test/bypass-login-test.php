<?php
// 這個工具用於測試API會話驗證問題
session_start();

// 設置或取消登入狀態
if (isset($_GET['login'])) {
    $_SESSION['logged_in'] = true;
    $message = '已成功模擬登入狀態';
} elseif (isset($_GET['logout'])) {
    unset($_SESSION['logged_in']);
    $message = '已取消登入狀態';
} else {
    $message = '使用 ?login 或 ?logout 參數來設置會話狀態';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API 會話測試工具</title>
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
        
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        
        #testResult {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            display: none;
        }
    </style>
</head>
<body>
    <h1>API 會話測試工具</h1>
    
    <?php if (isset($message)): ?>
    <div class="message <?php echo isset($_GET['login']) ? 'success' : 'info'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>當前會話狀態</h2>
        <p><strong>登入狀態:</strong> 
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <span style="color: green;">已登入 ✅</span>
            <?php else: ?>
                <span style="color: red;">未登入 ❌</span>
            <?php endif; ?>
        </p>
        
        <p>
            <a href="?login" class="btn">模擬登入</a>
            <a href="?logout" class="btn" style="background-color: #dc3545;">取消登入</a>
        </p>
    </div>
    
    <div class="card">
        <h2>API 測試</h2>
        <p>測試 API 是否正確驗證會話狀態</p>
        
        <button id="testApi" class="btn">測試 API</button>
        <div id="testResult"></div>
    </div>
    
    <div class="card">
        <h2>解決方案</h2>
        <p>如果 API 返回 <code>User not logged in</code> 錯誤，請執行以下步驟:</p>
        <ol>
            <li>點擊上方的「模擬登入」按鈕</li>
            <li>返回 <a href="python-test-cases.php">Python 測試用例</a> 頁面</li>
            <li>重新執行測試</li>
        </ol>
        
        <p>如果問題仍然存在，可以暫時修改 <code>test-code.php</code> 檔案，略過會話驗證:</p>
        <pre>
// 註釋以下會話驗證代碼
/*
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
    ]);
    exit;
}
*/

// 臨時設置登入狀態
$_SESSION['logged_in'] = true;
        </pre>
    </div>
    
    <script>
        document.getElementById('testApi').addEventListener('click', function() {
            const resultDiv = document.getElementById('testResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<p>測試中...</p>';
            
            // 測試 test-code.php API
            fetch('../api/test-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    code: 'print("Hello, World!")'
                }),
                credentials: 'same-origin'
            })
            .then(response => {
                // 檢查是否為JSON響應
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        return {
                            isJson: true,
                            data: data,
                            status: response.status
                        };
                    });
                } else {
                    // 如果不是JSON
                    return response.text().then(text => {
                        return {
                            isJson: false,
                            text: text,
                            status: response.status
                        };
                    });
                }
            })
            .then(result => {
                if (result.isJson) {
                    if (result.data.success) {
                        resultDiv.innerHTML = `
                            <h3 style="color:green">成功! ✅</h3>
                            <p>API 接受了請求，會話驗證通過。</p>
                            <pre>${JSON.stringify(result.data, null, 2)}</pre>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <h3 style="color:orange">API 返回錯誤 ⚠️</h3>
                            <p>錯誤訊息: ${result.data.message}</p>
                            <pre>${JSON.stringify(result.data, null, 2)}</pre>
                        `;
                    }
                } else {
                    resultDiv.innerHTML = `
                        <h3 style="color:red">非 JSON 響應 ❌</h3>
                        <p>收到 HTTP ${result.status} 響應</p>
                        <pre style="max-height:300px;overflow:auto">${result.text}</pre>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <h3 style="color:red">請求錯誤 ❌</h3>
                    <p>${error.message}</p>
                `;
            });
        });
    </script>
</body>
</html>
