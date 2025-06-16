<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once "../../config/database.php";

$user_id = $_SESSION["user_id"];
$current_level = $_SESSION["level"];

// 關卡資料
$levels = [
    1 => [
        "name" => "基礎迷宮",
        "required" => 2,
        "desc" => "請設計一道適合 Python 新手的題目，內容聚焦在基本語法與邏輯，例如變數、輸入輸出、條件判斷或迴圈等，題目簡單易懂。"
    ],
    2 => [
        "name" => "函數迷宮",
        "required" => 4,
        "desc" => "請設計一道 Python 題目，重點在函數的定義與使用，以及流程控制（如 if、for、while），適合具備初階基礎的學習者。"
    ],
    3 => [
        "name" => "數據迷宮",
        "required" => 6,
        "desc" => "請設計一道與資料結構（如 list、dict）與數據運算有關的 Python 題目，強調資料處理與邏輯思考能力。"
    ],
    4 => [
        "name" => "檔案迷宮",
        "required" => 8,
        "desc" => "請設計一道需要操作檔案（開啟、讀寫）與處理例外狀況的 Python 題目，幫助使用者進一步理解實務應用。"
    ],
    5 => [
        "name" => "進階迷宮",
        "required" => 10,
        "desc" => "請設計一道 Python 綜合題目，結合基本語法、函數、資料結構、檔案操作與例外處理，適合具備中階能力的學習者挑戰整合應用。"
    ],
];

// ---------- AI 題目生成區塊 ----------
// 只在 AJAX 請求時才產生題目
$dotenv_path = dirname(__DIR__, 2) . '/.env';
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

$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] === '1';
if ($is_ajax) {
    if (empty($OPENAI_API_KEY)) {
        echo json_encode([
            'error' => '未設定 OPENAI_API_KEY，請聯絡管理員。'
        ]);
        exit;
    }
    $level = isset($_GET['level']) ? intval($_GET['level']) : 0;
    if (!in_array($level, [1, 2, 3, 4, 5])) {
        echo json_encode(['error' => '關卡參數錯誤']);
        exit;
    }
    $system_prompt = <<<EOT
你是一位 Python 教學任務設計助理，根據使用者提供的關卡資訊 \$levels 與當前關卡 \$level，自動產生一題符合難度的 Python 單選題。請嚴格遵守以下輸出格式與規則產出題目。

✅【格式要求】
請強制輸出以下格式內容，不得增減或改動格式順序與欄位名稱：
題目描述：
（請以繁體中文撰寫題目說明，說明使用者任務與輸入/輸出格式。）

範例輸入：
（清楚列出測資輸入）

範例輸出：
（對應的正確輸出）

選項:
只產生一個選項，為正確程式碼拆成多行，每行加上行號，縮排用\t顯示，並將行號順序打亂後排列（從1開始，禁止出現123456等情況），例如：

3.\tprint("這是奇數")
1.num = int(input("請輸入一個整數："))
5.\tprint("這是偶數")
4.else:
6.print("結束")
2.if num % 2 != 0:

✅【內容設計規範】
題目難度請依據 \$levels[\$level["required"] 的數值大小設計（越大越難），並根據 \$levels[\$level]["desc"] 的描述設計主題。

題目語言：全程使用繁體中文敘述。

範例輸入/輸出：

使用簡單清楚的數據。

與題目一致，無多餘說明文字。

一個範例即可。

選項設計：

僅產生一個正確程式碼的打亂版本作為選項。

行號從1開始依序編號，且行數與程式碼行數相同。

不得使用 markdown、HTML 等格式標記在選項中。
EOT;

    $user_prompt = "levels = " . var_export($levels, true) . "\nlevel = $level";
    $data = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "system", "content" => $system_prompt],
            ["role" => "user", "content" => $user_prompt]
        ],
        "temperature" => 0.7,
    ];
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_errno) {
        echo json_encode([
            'error' => 'curl 錯誤: ' . $curl_error
        ]);
        exit;
    }

    $response = json_decode($result, true);

    if (isset($response['error'])) {
        echo json_encode([
            'error' => 'OpenAI API 錯誤: ' . $response['error']['message'],
            'raw' => $result
        ]);
        exit;
    }

    if (isset($response["choices"][0]["message"]["content"])) {
        $ai_content = $response["choices"][0]["message"]["content"];
    } else {
        echo json_encode([
            'error' => '無法取得題目內容，請稍後再試。',
            'raw' => $result
        ]);
        exit;
    }

    // 處理內容
    $clean_text = str_replace(["\r\n", "\r"], "\n", $ai_content);
    $clean_text = preg_replace("/\n{2,}/", "\n", $clean_text);
    // 用 explode("選項:", $clean_text) 切分
    $question_text = '';
    $options = '';
    $parts = explode("選項:", $clean_text, 2);
    if (count($parts) === 2) {
        $question_text = trim($parts[0]);
        $options = trim($parts[1]);
    } else {
        $question_text = trim($clean_text);
        $options = '';
    }
    // 處理題目描述格式
    $question_text = preg_replace('/\s*\n*(範例輸入：)/u', "\n$1", $question_text);
    $question_text = preg_replace('/\s*\n*(範例輸出：)/u', "\n$1", $question_text);
    $question_text = preg_replace("/\n{3,}/", "\n\n", $question_text);
    $question_text = trim($question_text);
    $lines = explode("\n", $question_text);
    $lines = array_map('rtrim', $lines);
    $lines = array_map('ltrim', $lines);
    $question_text = implode("\n", array_filter($lines, function ($l) {
        return $l !== '';
    }));

    // 將 $options 轉成陣列，key 為選項數字，value 為保留縮排的程式碼
    $options_arr = [];
    $lines = explode("\n", $options);
    foreach ($lines as $line) {
        if (preg_match('/^(\d+)[\.:：](.*)$/', $line, $matches)) {
            $key = $matches[1];
            $code = $matches[2];
            $options_arr[$key] = $code;
        }
    }
    echo json_encode([
        'question_text' => $question_text,
        'options_arr' => $options_arr
    ]);
    exit;
}
// ---------- END AI 題目生成區塊 ----------

$level = isset($_GET['level']) ? intval($_GET['level']) : 0;

if (!isset($levels[$level])) {
    header("location: index.php");
    exit;
}

$level_info = $levels[$level];

// 檢查權限
if ($current_level < $level_info['required']) {
    header("location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($level_info['name']); ?> - 迷宮尋寶</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- level.php 樣式 -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/battle.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <!-- 引入 jQuery (AJAX 套件) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>

<body>
    <?php include '../../ai.php'; ?>

    <div class="battle-container">
        <div class="battle-header">
            <div class="header-info">
                <a href="index.php" class="back-button"><i class="fas fa-arrow-left"></i> 返回迷宮選單</a>
                <h1><?php echo htmlspecialchars($level_info['name']); ?></h1>
            </div>
            <div class="player-stats">
                <div class="player-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div class="stats-row">
                    <div class="stat-item">LV. <?php echo $_SESSION['level']; ?></div>
                    <div class="stat-item">ATK: <?php echo $_SESSION['attack_power']; ?></div>
                    <div class="stat-item">HP: <span id="player-hp"><?php echo $_SESSION['base_hp']; ?></span>/<?php echo $_SESSION['base_hp']; ?></div>
                </div>
            </div>
        </div>

        <div class="battle-content">
            <div class="code-section">
                <div class="problem-container1">
                    <h2>挑戰題目</h2>
                    <div id="question-area" class="problem-description">
                        <div id="loading-msg" class="loading">題目生成中...</div>
                    </div>
                </div>
            </div>
            <div class="battle-section">
                <div id="options-area"></div>

                <div class="battle-actions mt-3">
                    <button type="button" class="btn btn-warning ms-2" id="resetBtn">
                        <i class="fas fa-undo"></i> 重製題目
                    </button>
                    <button type="button" class="btn btn-success ms-2" id="submitAnswerBtn" style="display:none;">
                        <i class="fas fa-paper-plane"></i> 提交
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // 類似 choose 頁面，AJAX 載入題目
        $(function() {
            let url = window.location.pathname + window.location.search + (window.location.search ? '&' : '?') + 'ajax=1';
            $.get(url)
                .done(function(data) {
                    if (typeof data === 'string') {
                        try {
                            data = JSON.parse(data);
                        } catch (e) {}
                    }
                    console.log('AJAX 回傳資料:', data);
                    if (data.error) {
                        $('#question-area').html('<div class="text-danger">題目載入失敗：' + data.error + '</div>');
                        $('#options-area').html('');
                        $('#submitAnswerBtn').hide();
                        return;
                    }
                    $('#question-area').html(
                        '<div>' + (data.question_text ? data.question_text.replace(/\n/g, '<br>') : '題目載入失敗') + '</div>'
                    );
                    // --- 將題目內容傳給 AI 助理 ---
                    if (window.aiReceiveMazeQuestion) {
                        window.aiReceiveMazeQuestion(data.question_text);
                    }
                    // --- end ---
                    if (data.options_arr && Object.keys(data.options_arr).length > 0) {
                        let optionsEntries = Object.entries(data.options_arr);
                        optionsEntries.sort(() => Math.random() - 0.5); // 簡單亂序

                        let html = '<div class="card-header bg-warning text-dark fw-bold">選項</div>';
                        html += '<div class="card-body"><form class="mt-2" id="sortableForm"><ul id="sortable-options" class="list-group">';
                        for (const [label, code] of optionsEntries) {
                            let code = data.options_arr[label]
                                .replace(/\t/g, '&nbsp;&nbsp;&nbsp;&nbsp;')
                                .replace(/^([ ]+)/gm, function(m) {
                                    return m.replace(/ /g, '&nbsp;');
                                });
                            html += `<li class="list-group-item d-flex align-items-start" data-id="${label}">
                                <pre class="mb-0 text-start flex-fill" style="background: #222; color: #fff; border-radius: 8px; padding: 16px;">${code}</pre>
                            </li>`;
                        }
                        html += '</ul></form></div>';
                        $('#options-area').html(html);
                        $('#submitAnswerBtn').show();
                        $('#loading-msg').remove();
                        // 啟用 SortableJS
                        new Sortable(document.getElementById('sortable-options'), {
                            animation: 150
                        });
                    } else {
                        $('#options-area').html('');
                        $('#submitAnswerBtn').hide();
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    $('#question-area').html('<div class="text-danger">AJAX 請求失敗：' + textStatus + ' ' + errorThrown + '</div>');
                    $('#options-area').html('');
                    $('#submitAnswerBtn').hide();
                    console.log('AJAX 錯誤:', jqXHR, textStatus, errorThrown);
                });
        });

        // 重製題目按鈕
        $('#resetBtn').on('click', function() {
            window.location.reload();
        });

        // 提交按鈕事件
        $('#submitAnswerBtn').on('click', function() {
            var items = $('#sortable-options li');
            var userOrder = [];
            items.each(function() {
                userOrder.push(parseInt($(this).attr('data-id')));
            });
            if (userOrder.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '請先排序所有程式碼行',
                    confirmButtonText: '確定'
                });
                return;
            }
            // 檢查是否為連續遞增數字（如 1,2,3,...,N）
            var isContinuous = true;
            for (var i = 0; i < userOrder.length; i++) {
                if (userOrder[i] !== i + 1) {
                    isContinuous = false;
                    break;
                }
            }
            if (isContinuous) {
                fetch('award.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: ''
                    })
                    .then(response => {
                        response.clone().text().then(function(txt) {
                            console.log('award.php raw response:', txt);
                        });
                        if (response.status === 403) {
                            Swal.fire({
                                icon: 'error',
                                title: '權限錯誤 (403)',
                                text: '請確認您已登入，或重新登入後再試。',
                                confirmButtonText: '返回選單'
                            }).then(() => {
                                window.location.href = 'index.php';
                            });
                            throw new Error('award.php failed, status=403');
                        }
                        if (!response.ok) throw new Error('award.php failed, status=' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('award.php JSON:', data);
                        Swal.fire({
                            icon: 'success',
                            title: '答對了！',
                            html: '攻擊力 + ' + data.attack_power + '<br>血量 + ' + data.base_hp,
                            confirmButtonText: '返回選單'
                        }).then(() => {
                            window.location.href = 'index.php';
                        });
                    })
                    .catch((err) => {
                        if (err.message.indexOf('status=403') === -1) {
                            Swal.fire({
                                icon: 'warning',
                                title: '答對了，但獎勵未發放',
                                text: '請稍後再試或聯絡管理員\n' + err,
                                confirmButtonText: '返回選單'
                            }).then(() => {
                                window.location.href = 'index.php';
                            });
                        }
                    });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '答錯了！',
                    text: '請再試一次',
                    confirmButtonText: '確定'
                });
            }
        });
    </script>
</body>

</html>