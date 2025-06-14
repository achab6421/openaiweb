<?php
// 評估答案診斷頁面
session_start();

// 測試用 - 確保用戶已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = '999';
    $_SESSION['username'] = 'debugger';
}
?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>評估答案除錯工具</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .editor-container {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        #code-editor {
            width: 100%;
            height: 300px;
            font-family: monospace;
            font-size: 14px;
        }
        .panel {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            background-color: #fff;
        }
        .panel-header {
            background-color: #f0f0f0;
            padding: 10px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        .panel-body {
            padding: 15px;
            overflow-x: auto;
        }
        .log-panel {
            height: 200px;
            overflow-y: auto;
            font-family: monospace;
            background-color: #f8f8f8;
            padding: 10px;
            border: 1px solid #ddd;
            white-space: pre-wrap;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            font-size: 14px;
        }
        button:hover {
            background-color: #45a049;
        }
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .control-panel {
            display: flex;
            margin-bottom: 15px;
        }
        .test-input {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        #thread-id {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 2s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/codemirror.min.css">
</head>
<body>
    <div class="container">
        <h1>評估答案除錯工具</h1>
        
        <div class="control-panel">
            <button id="create-thread-btn">1. 創建對話</button>
            <input type="text" id="thread-id" placeholder="對話ID" readonly>
        </div>

        <div class="panel">
            <div class="panel-header">問題描述</div>
            <div class="panel-body">
                <textarea id="problem-statement" class="test-input" rows="5" placeholder="輸入問題描述...">
在一個神奇的冒險世界裡，變數史萊姆擁有不同的屬性，包括名字、生命值和魔法值。你的任務是創建一個程式，讓使用者能夠輸入這些屬性，並列印出變數史萊姆的資訊。

請撰寫一個Python程式，要求使用者依序輸入變數史萊姆的名字、生命值及魔法值，然後以友好的格式顯示這些資訊。

輸入/輸出說明：

輸入：
1. 一個字串，表示變數史萊姆的名字
2. 一個整數，表示變數史萊姆的生命值
3. 一個整數，表示變數史萊姆的魔法值

輸出：
程式應輸出以下格式的字串：
"變數史萊姆的名字是 {name}，生命值為 {hp}，魔法值為 {mp}！"</textarea>
            </div>
        </div>

        <div class="editor-container">
            <div id="code-editor"></div>
        </div>
        
        <div style="margin-bottom: 15px;">
            <button id="test-code-btn">2. 測試程式碼</button>
            <button id="submit-btn" disabled>3. 提交答案</button>
            <button id="load-example-btn">載入範例答案</button>
            <button id="load-wrong-btn">載入錯誤答案</button>
            <div id="loading" style="display:none;"><span class="loader"></span> 處理中...</div>
        </div>
        
        <div class="panel">
            <div class="panel-header">執行結果</div>
            <div class="panel-body" id="output-panel">
                <div id="output-display">尚未執行程式碼</div>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-header">日誌與診斷</div>
            <div class="panel-body">
                <div id="log-panel" class="log-panel"></div>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-header">API 回應原始數據</div>
            <div class="panel-body">
                <pre id="raw-response">尚未取得回應</pre>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.62.0/mode/python/python.min.js"></script>
    <script>
        // 初始化程式碼編輯器
        const editor = CodeMirror(document.getElementById("code-editor"), {
            lineNumbers: true,
            mode: "python",
            theme: "default",
            indentUnit: 4,
            smartIndent: true
        });
        
        // 設置預設範例
        editor.setValue('# 請使用者輸入變數史萊姆的資訊\n# 在這裡編寫您的程式碼');
        
        // 變數和元素參照
        const logPanel = document.getElementById('log-panel');
        const outputDisplay = document.getElementById('output-display');
        const rawResponse = document.getElementById('raw-response');
        const createThreadBtn = document.getElementById('create-thread-btn');
        const submitBtn = document.getElementById('submit-btn');
        const testCodeBtn = document.getElementById('test-code-btn');
        const threadIdInput = document.getElementById('thread-id');
        const loadExampleBtn = document.getElementById('load-example-btn');
        const loadWrongBtn = document.getElementById('load-wrong-btn');
        const loadingIndicator = document.getElementById('loading');
        const problemStatement = document.getElementById('problem-statement');
        
        let currentThreadId = '';
        
        // 添加日誌訊息
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = type;
            logEntry.textContent = `[${timestamp}] ${message}`;
            logPanel.appendChild(logEntry);
            logPanel.scrollTop = logPanel.scrollHeight; // 自動滾動到底部
        }
        
        // 顯示載入中狀態
        function setLoading(isLoading) {
            loadingIndicator.style.display = isLoading ? 'inline-block' : 'none';
            createThreadBtn.disabled = isLoading;
            submitBtn.disabled = isLoading || !currentThreadId;
            testCodeBtn.disabled = isLoading;
        }
        
        // 創建新對話
        createThreadBtn.addEventListener('click', function() {
            setLoading(true);
            log('正在創建新對話...');
            
            fetch('api/create-thread.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                log(`收到回應，狀態碼: ${response.status}`);
                return response.json();
            })
            .then(data => {
                log(`創建對話成功，對話ID: ${data.threadId}`, 'success');
                currentThreadId = data.threadId;
                threadIdInput.value = currentThreadId;
                submitBtn.disabled = false;
                
                rawResponse.textContent = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                log(`創建對話失敗: ${error.message}`, 'error');
                rawResponse.textContent = `錯誤: ${error.message}`;
            })
            .finally(() => {
                setLoading(false);
            });
        });
        
        // 測試程式碼
        testCodeBtn.addEventListener('click', function() {
            const code = editor.getValue();
            
            if (!code.trim()) {
                log('請先輸入程式碼', 'error');
                return;
            }
            
            setLoading(true);
            log('正在測試程式碼...');
            outputDisplay.innerHTML = '<div style="padding: 10px;">執行中...</div>';
            
            fetch('api/test-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    code: code,
                    input: '小綠\n100\n50'  // 預設測試輸入
                })
            })
            .then(response => {
                log(`收到回應，狀態碼: ${response.status}`);
                return response.json();
            })
            .then(data => {
                log(`測試完成，成功: ${data.success}`, data.success ? 'success' : 'error');
                rawResponse.textContent = JSON.stringify(data, null, 2);
                
                let html = '';
                if (data.success) {
                    html = `<pre class="success">${data.output || '(無輸出)'}</pre>`;
                    if (data.errors) {
                        html += `<h4>警告/錯誤:</h4><pre class="warning">${data.errors}</pre>`;
                    }
                } else {
                    html = `<pre class="error">錯誤: ${data.message || '未知錯誤'}</pre>`;
                }
                
                outputDisplay.innerHTML = html;
            })
            .catch(error => {
                log(`測試失敗: ${error.message}`, 'error');
                outputDisplay.innerHTML = `<pre class="error">錯誤: ${error.message}</pre>`;
                rawResponse.textContent = `錯誤: ${error.message}`;
            })
            .finally(() => {
                setLoading(false);
            });
        });
        
        // 提交答案
        submitBtn.addEventListener('click', function() {
            const code = editor.getValue();
            
            if (!currentThreadId) {
                log('請先創建對話', 'error');
                return;
            }
            
            if (!code.trim()) {
                log('請先輸入程式碼', 'error');
                return;
            }
            
            setLoading(true);
            log('正在提交答案進行評估...');
            outputDisplay.innerHTML = '<div style="padding: 10px;">評估中...</div>';
            
            fetch('api/evaluate-answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    levelId: 1, // 測試用級別 ID
                    userCode: code,
                    threadId: currentThreadId,
                    problemStatement: problemStatement.value
                })
            })
            .then(response => {
                log(`收到評估回應，狀態碼: ${response.status}`);
                const contentType = response.headers.get('content-type');
                log(`回應內容類型: ${contentType}`);
                
                return response.text().then(text => {
                    try {
                        // 嘗試解析為 JSON
                        return { json: JSON.parse(text), text: text };
                    } catch (e) {
                        // 如果不是有效 JSON，返回原始文本
                        log(`JSON 解析錯誤: ${e.message}`, 'error');
                        return { json: null, text: text };
                    }
                });
            })
            .then(({ json, text }) => {
                rawResponse.textContent = text;
                
                if (json) {
                    if (json.success) {
                        log(`評估完成，答案${json.isCorrect ? '正確' : '不正確'}`, json.isCorrect ? 'success' : 'warning');
                        
                        let html = `<div class="${json.isCorrect ? 'success' : 'error'}">`;
                        html += `<h3>${json.isCorrect ? '答案正確' : '答案不正確'}</h3>`;
                        html += `<pre>${json.evaluation || '(無評估詳情)'}</pre>`;
                        html += '</div>';
                        
                        outputDisplay.innerHTML = html;
                    } else {
                        log(`評估失敗: ${json.message}`, 'error');
                        outputDisplay.innerHTML = `<pre class="error">評估失敗: ${json.message}</pre>`;
                    }
                } else {
                    log('收到非 JSON 回應', 'error');
                    outputDisplay.innerHTML = `<pre class="error">收到非 JSON 回應：\n${text.substring(0, 500)}${text.length > 500 ? '...' : ''}</pre>`;
                }
            })
            .catch(error => {
                log(`評估過程發生錯誤: ${error.message}`, 'error');
                outputDisplay.innerHTML = `<pre class="error">錯誤: ${error.message}</pre>`;
            })
            .finally(() => {
                setLoading(false);
            });
        });
        
        // 載入範例答案
        loadExampleBtn.addEventListener('click', function() {
            editor.setValue(`# 請使用者輸入變數史萊姆的資訊
name = input("請輸入變數史萊姆的名字：")
hp = int(input("請輸入變數史萊姆的生命值："))
mp = int(input("請輸入變數史萊姆的魔法值："))

# 顯示變數史萊姆的資訊
print(f"變數史萊姆的名字是 {name}，生命值為 {hp}，魔法值為 {mp}！")`);
            log('已載入範例答案');
        });
        
        // 載入錯誤答案
        loadWrongBtn.addEventListener('click', function() {
            editor.setValue(`# 這是一個有錯誤的答案
name = input("請輸入變數史萊姆的名字：")
hp = input("請輸入變數史萊姆的生命值：")  # 錯誤：沒有轉換為整數
mp = input("請輸入變數史萊姆的魔法值：")  # 錯誤：沒有轉換為整數

print("變數史萊姆的名稱是 " + name + "，HP為 " + hp + "，MP為 " + mp)`);
            log('已載入錯誤答案範例');
        });
        
        // 初始日誌
        log('除錯頁面已載入，請點擊"創建對話"開始測試', 'info');
    </script>
</body>
</html>
