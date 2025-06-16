<?php
// 取得 OpenAI API KEY
$dotenv_path = __DIR__ . '/.env';
$env = [];
if (file_exists($dotenv_path)) {
    $lines = file($dotenv_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value, "\"' ");
        }
    }
}
$OPENAI_API_KEY = isset($env['OPENAI_API_KEY']) ? $env['OPENAI_API_KEY'] : '';

// 新增 system prompt
$system_prompt = <<<EOT
你是一位 Python 教學任務設計助理，根據提供的關卡題目資訊以及使用者的提問，給予提示。
不要直接給題目答案
EOT;
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>AI 對話助理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: #181c20;
            color: #fff;
            font-family: 'Noto Sans TC', sans-serif;
        }
        .ai-fab {
            position: fixed;
            right: 32px;
            bottom: 32px;
            width: 64px;
            height: 64px;
            background: #3a8bfd;
            color: #fff;
            border-radius: 50%;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            cursor: pointer;
            z-index: 9999;
            transition: background 0.18s;
        }
        .ai-fab:hover {
            background: #2563eb;
        }
        .ai-chat-container {
            position: fixed;
            right: 32px;
            bottom: 110px;
            max-width: 520px;
            width: 98vw;
            min-width: 340px;
            margin: 0;
            background: #23272b;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 0;
            overflow: hidden;
            display: none;
            z-index: 9999;
        }
        .ai-chat-header {
            background: #222;
            padding: 18px 24px;
            font-size: 1.2rem;
            font-weight: bold;
            border-bottom: 1px solid #333;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
        }
        .ai-chat-header .ai-close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.3rem;
            cursor: pointer;
            margin-left: 8px;
        }
        .ai-chat-messages {
            height: 550px;
            overflow-y: auto;
            padding: 24px;
            background: #23272b;
        }
        .ai-chat-message {
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .ai-chat-message.user {
            flex-direction: row;
            justify-content: flex-end;
        }
        .ai-chat-message.user .bubble {
            background: #3a8bfd;
            color: #fff;
            align-self: flex-end;
            margin-left: 40px;
            margin-right: 0;
            /* 讓訊息靠右 */
            margin-left: auto;
            margin-right: 0;
        }
        .ai-chat-message.user {
            align-items: flex-end;
        }
        .ai-chat-message.ai .bubble {
            background: #444950;
            color: #fff;
            margin-right: auto;
        }
        .bubble {
            padding: 10px 16px;
            border-radius: 16px;
            max-width: 75%;
            word-break: break-word;
            font-size: 1rem;
        }
        .ai-chat-input-area {
            display: flex;
            border-top: 1px solid #333;
            background: #23272b;
            padding: 12px 14px;
        }
        .ai-chat-input-area input {
            flex: 1;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 1rem;
            background: #181c20;
            color: #fff;
            margin-right: 8px;
        }
        .ai-chat-input-area button {
            background: #3a8bfd;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .ai-chat-input-area button:hover {
            background: #2563eb;
        }
        .ai-suggested-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0 0 0;
        }
        .ai-suggested-questions button {
            background: #222;
            color: #3a8bfd;
            border: 1px solid #3a8bfd;
            border-radius: 16px;
            padding: 6px 16px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .ai-suggested-questions button:hover {
            background: #3a8bfd;
            color: #fff;
        }
        @media (max-width: 600px) {
            .ai-chat-container {
                right: 8px;
                bottom: 80px;
                max-width: 100vw;
                min-width: 0;
            }
            .ai-fab {
                right: 12px;
                bottom: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- 懸浮按鈕 -->
    <div class="ai-fab" id="aiFabBtn" title="AI 助理">
        <i class="fas fa-robot"></i>
    </div>
    <!-- 聊天視窗 -->
    <div class="ai-chat-container" id="aiChatBox">
        <div class="ai-chat-header">
            <span><i class="fas fa-robot"></i> AI 對話助理</span>
            <button class="ai-close-btn" id="aiCloseBtn" title="關閉"><i class="fas fa-times"></i></button>
        </div>
        <div class="ai-chat-messages" id="chatMessages">
            <div class="ai-chat-message ai">
                <div class="bubble">您好，我是您的全站 AI 助理，有任何問題都可以問我！</div>
            </div>
        </div>
        <div class="ai-chat-input-area">
            <input type="text" id="chatInput" placeholder="請輸入您的問題..." autocomplete="off" />
            <button id="sendBtn"><i class="fas fa-paper-plane"></i> 送出</button>
        </div>
    </div>
    <script>
        // 懸浮按鈕開關聊天視窗
        document.getElementById('aiFabBtn').onclick = function() {
            document.getElementById('aiChatBox').style.display = 'block';
            setTimeout(function() {
                document.getElementById('chatInput').focus();
            }, 200);
        };
        document.getElementById('aiCloseBtn').onclick = function() {
            document.getElementById('aiChatBox').style.display = 'none';
        };
        // 按下 ESC 關閉
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") {
                document.getElementById('aiChatBox').style.display = 'none';
            }
        });

        // 全域變數：記錄 maze 題目
        window._mazeQuestionText = '';

        // 提供全域函式給外部頁面呼叫
        window.aiReceiveMazeQuestion = function(questionText) {
            window._mazeQuestionText = questionText || '';
        };

        document.addEventListener('DOMContentLoaded', function() {
            var sendBtn = document.getElementById('sendBtn');
            var chatInput = document.getElementById('chatInput');
            var chatBox = document.getElementById('chatMessages');

            // 建立建議問題區塊
            function renderSuggestedQuestions(questions) {
                // 移除舊的
                var old = document.getElementById('aiSuggestedQuestions');
                if (old) old.remove();
                if (!questions || !questions.length) return;
                var wrap = document.createElement('div');
                wrap.className = 'ai-suggested-questions';
                wrap.id = 'aiSuggestedQuestions';
                questions.forEach(function(q) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = q;
                    btn.onclick = function() {
                        chatInput.value = q;
                        chatInput.focus();
                    };
                    wrap.appendChild(btn);
                });
                chatBox.appendChild(wrap);
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            function sendMessage() {
                var userMsg = chatInput.value.trim();
                if (!userMsg) return;
                // 顯示使用者訊息
                var userDiv = document.createElement('div');
                userDiv.className = 'ai-chat-message user';
                userDiv.innerHTML = '<div class="bubble">' + userMsg + '</div>';
                chatBox.appendChild(userDiv);
                chatBox.scrollTop = chatBox.scrollHeight;

                // 移除建議問題
                var old = document.getElementById('aiSuggestedQuestions');
                if (old) old.remove();

                // 準備送出內容
                var mazeQ = window._mazeQuestionText ? ("\n\n【本次挑戰題目】\n" + window._mazeQuestionText) : "";
                var fullMsg = userMsg + mazeQ;

                // 顯示 loading
                var aiDiv = document.createElement('div');
                aiDiv.className = 'ai-chat-message ai';
                aiDiv.innerHTML = '<div class="bubble"><i class="fas fa-spinner fa-spin"></i> AI 回覆中...</div>';
                chatBox.appendChild(aiDiv);
                chatBox.scrollTop = chatBox.scrollHeight;

                // 清空輸入框
                chatInput.value = '';

                // 第一次先請 AI 產生建議問題，再請 AI 正常回答
                fetch('/OPENAIWEB/ai_proxy.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        input: fullMsg,
                        system_prompt: <?php echo json_encode($system_prompt); ?>,
                        suggest_questions: true
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error('HTTP ' + response.status + ': ' + text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // 顯示建議問題
                    if (data && data.suggested_questions && Array.isArray(data.suggested_questions) && data.suggested_questions.length) {
                        renderSuggestedQuestions(data.suggested_questions);
                    }
                    // 再請 AI 正常回答（不帶 suggest_questions）
                    return fetch('/OPENAIWEB/ai_proxy.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            input: fullMsg,
                            system_prompt: <?php echo json_encode($system_prompt); ?>
                        })
                    });
                })
                .then(response => {
                    if (!response) return;
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error('HTTP ' + response.status + ': ' + text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data) return;
                    let reply = '';
                    if (data && Array.isArray(data.output)) {
                        if (data.output.length > 0 && data.output[0].content) {
                            if (typeof data.output[0].content === 'string') {
                                reply = data.output[0].content;
                            } else if (Array.isArray(data.output[0].content)) {
                                reply = data.output[0].content.map(function(c) {
                                    return typeof c === 'string' ? c : (c.text || '');
                                }).join('');
                            } else if (typeof data.output[0].content === 'object' && data.output[0].content.text) {
                                reply = data.output[0].content.text;
                            } else {
                                reply = JSON.stringify(data.output[0].content);
                            }
                        } else {
                            reply = '[object Object]';
                        }
                    } else if (data && typeof data.output === 'string') {
                        reply = data.output;
                    } else if (data && data.output) {
                        reply = String(data.output);
                    } else {
                        reply = 'AI 回覆失敗，請稍後再試。';
                    }
                    if (data && data.debug) {
                        reply += "<br><br><b>Debug:</b><br>" + JSON.stringify(data.debug, null, 2);
                    }
                    aiDiv.innerHTML = '<div class="bubble">' + reply.replace(/\n/g, '<br>') + '</div>';
                    chatBox.scrollTop = chatBox.scrollHeight;
                })
                .catch((err) => {
                    aiDiv.innerHTML = '<div class="bubble">AI 回覆失敗，請稍後再試。<br><span style="color:#f88;font-size:0.95em;">' + err.message + '</span></div>';
                    chatBox.scrollTop = chatBox.scrollHeight;
                    console.error('AI fetch error:', err);
                });
            }

            sendBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sendMessage();
            });
            chatInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>
