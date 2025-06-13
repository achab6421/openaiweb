<?php
// 用於測試Python中文輸出的簡單腳本
session_start();

// 啟用測試模式，跳過登入檢查
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 'test';
$_SESSION['username'] = 'tester';

?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python中文輸出測試</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        pre { background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; white-space: pre-wrap; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Python中文輸出測試</h1>
    
    <div>
        <textarea id="codeInput" style="width: 100%; height: 150px;">print("Hello, World!")
print("你好，世界！")
print("這是中文測試")
print("繁體中文測試：測試，測試，測試")
print("簡體中文測試：测试，测试，测试")</textarea>
        <br>
        <button id="runCode">執行程式碼</button>
    </div>
    
    <h2>執行結果：</h2>
    <div id="output">
        <pre>(結果將顯示在這裡)</pre>
    </div>
    
    <script>
        document.getElementById('runCode').addEventListener('click', function() {
            const code = document.getElementById('codeInput').value;
            const outputDiv = document.getElementById('output');
            
            outputDiv.innerHTML = '<pre>執行中...</pre>';
            
            fetch('api/test-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ code: code })
            })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                if (data.success) {
                    let html = '<pre class="success">';
                    html += data.output || '(無輸出)';
                    html += '</pre>';
                    
                    if (data.errors) {
                        html += '<h3>警告/錯誤：</h3><pre class="error">' + data.errors + '</pre>';
                    }
                    
                    html += '<p>執行時間: ' + data.executionTime + ' 毫秒</p>';
                    html += '<p>Python路徑: ' + data.pythonPath + '</p>';
                    
                    outputDiv.innerHTML = html;
                } else {
                    outputDiv.innerHTML = '<pre class="error">錯誤: ' + (data.message || '未知錯誤') + '</pre>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                outputDiv.innerHTML = '<pre class="error">請求錯誤: ' + error.message + '</pre>';
            });
        });
    </script>
</body>
</html>
