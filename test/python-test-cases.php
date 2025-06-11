<?php
// 初始化會話
session_start();
// 設置臨時登入狀態以便測試
$_SESSION['logged_in'] = true;

// Python 程式碼測試用例
$test_cases = [
    [
        'name' => '基本輸出測試',
        'code' => "print('Hello, World!')\nprint('This is a test')",
        'input' => '',
        'expected_output' => "Hello, World!\nThis is a test"
    ],
    [
        'name' => '輸入處理測試',
        'code' => "name = input('Please enter your name: ')\nprint(f'Hello, {name}!')",
        'input' => 'Python Tester',
        'expected_output' => "Hello, Python Tester!"
    ],
    [
        'name' => '數學計算測試',
        'code' => "a = int(input('Enter first number: '))\nb = int(input('Enter second number: '))\nprint(f'Sum: {a+b}')\nprint(f'Product: {a*b}')",
        'input' => "5\n7",
        'expected_output' => "Sum: 12\nProduct: 35"
    ],
    [
        'name' => '錯誤處理測試',
        'code' => "try:\n    x = 10 / 0\nexcept ZeroDivisionError:\n    print('Cannot divide by zero!')",
        'input' => '',
        'expected_output' => "Cannot divide by zero!"
    ],
    [
        'name' => '超時測試',
        'code' => "import time\nwhile True:\n    print('This will timeout')\n    time.sleep(1)",
        'input' => '',
        'expected_output' => '程式執行超時'
    ],
    [
        'name' => '語法錯誤測試',
        'code' => "if True\n    print('This has a syntax error')",
        'input' => '',
        'expected_output' => 'SyntaxError'
    ]
];

// 使用PHP渲染HTML時可以迭代這些測試用例
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python 測試用例</title>
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1, h2 {
            color: #2c3e50;
        }
        
        .test-case {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        
        .test-name {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .code-block {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
        }
        
        .input-block {
            background-color: #f0f7ff;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
        }
        
        .expected-output {
            background-color: #f0fff0;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
        }
        
        .test-button {
            background-color: #3498db;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .test-button:hover {
            background-color: #2980b9;
        }
        
        /* 添加結果顯示區域樣式 */
        .test-result {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        
        .output-display {
            background-color: #f0fff0;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            margin-bottom: 10px;
        }
        
        .error-display {
            background-color: #fff0f0;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            margin-bottom: 10px;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <h1>Python 測試用例</h1>
    
    <div class="status-panel" style="margin-bottom: 20px; padding: 10px; background-color: #f8f8f8; border-left: 4px solid #3498db;">
        <h3 style="margin-top: 0;">測試環境狀態</h3>
        <p><strong>會話狀態:</strong> <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? '<span style="color: green;">已登入 ✅</span>' : '<span style="color: red;">未登入 ❌</span>'; ?></p>
        <p><small>注意: 此頁面已自動設置測試用的登入狀態</small></p>
    </div>
    
    <?php foreach ($test_cases as $index => $test): ?>
    <div class="test-case">
        <div class="test-name"><?= htmlspecialchars($test['name']) ?></div>
        
        <div>
            <h3>程式碼:</h3>
            <div class="code-block"><?= htmlspecialchars($test['code']) ?></div>
        </div>
        
        <?php if (!empty($test['input'])): ?>
        <div>
            <h3>輸入:</h3>
            <div class="input-block"><?= htmlspecialchars($test['input']) ?></div>
        </div>
        <?php endif; ?>
        
        <div>
            <h3>預期輸出:</h3>
            <div class="expected-output"><?= htmlspecialchars($test['expected_output']) ?></div>
        </div>
        
        <button class="test-button" data-index="<?= $index ?>">執行此測試</button>
        <div class="test-result" id="result-<?= $index ?>"></div>
    </div>
    <?php endforeach; ?>
    
    <script>
        document.querySelectorAll('.test-button').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                const testCase = <?= json_encode($test_cases) ?>[index];
                const resultDiv = document.getElementById(`result-${index}`);
                
                resultDiv.innerHTML = '<p>正在執行測試...</p>';
                
                fetch('../api/test-code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        code: testCase.code,
                        input: testCase.input || ''
                    }),
                    credentials: 'same-origin'
                })
                .then(response => {
                    // 檢查響應是否為有效JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json().catch(error => {
                            throw new Error('無效的JSON響應：' + error.message);
                        });
                    } else {
                        // 如果不是JSON，則先獲取文本
                        return response.text().then(text => {
                            resultDiv.innerHTML = `<p style="color:red">非JSON響應：</p><pre style="background-color:#fff0f0;padding:10px;overflow:auto;max-height:300px;">${text}</pre>`;
                            throw new Error('服務器返回非JSON響應');
                        });
                    }
                })
                .then(data => {
                    if (data.success) {
                        let resultHTML = '<h3>測試結果:</h3>';
                        
                        if (data.output) {
                            resultHTML += `<div class="output-display">輸出:\n${data.output}</div>`;
                        } else {
                            resultHTML += `<div class="output-display">輸出: (沒有輸出)</div>`;
                        }
                        
                        if (data.errors) {
                            resultHTML += `<div class="error-display">錯誤:\n${data.errors}</div>`;
                        }
                        
                        // 比較預期輸出與實際輸出
                        const expectedOutput = testCase.expected_output || '';
                        const actualOutput = data.output || '';
                        
                        if (actualOutput.trim() === expectedOutput.trim()) {
                            resultHTML += `<p style="color:green;font-weight:bold;">✅ 測試通過！輸出符合預期</p>`;
                        } else {
                            resultHTML += `<p style="color:orange;font-weight:bold;">⚠️ 輸出與預期不符</p>`;
                        }
                        
                        if (data.executionTime) {
                            resultHTML += `<div style="margin-top:10px;color:#666;">執行時間: ${data.executionTime} 毫秒</div>`;
                        }
                        
                        resultDiv.innerHTML = resultHTML;
                    } else {
                        resultDiv.innerHTML = `<p style="color:red">執行失敗: ${data.message || '未知錯誤'}</p>`;
                    }
                })
                .catch(error => {
                    if (!resultDiv.innerHTML.includes('非JSON響應')) {
                        resultDiv.innerHTML = `<p style="color:red">請求錯誤: ${error.message}</p>`;
                    }
                });
            });
        });
    </script>
</body>
</html>
