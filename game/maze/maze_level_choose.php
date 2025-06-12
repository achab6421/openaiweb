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

$ai_content = "";
// 讀取 .env 並取得 OPENAI_API_KEY
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
    // 檢查 API KEY 是否設定
    if (empty($OPENAI_API_KEY)) {
        echo json_encode([
            'error' => '未設定 OPENAI_API_KEY，請聯絡管理員。'
        ]);
        exit;
    }

    $correct_option = ['A', 'B', 'C', 'D'][array_rand(['A', 'B', 'C', 'D'])];

    if (in_array($level = (isset($_GET['level']) ? intval($_GET['level']) : 0), [1, 2, 3, 4, 5])) {
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

選項：

A:
（Python 程式碼，正確或錯誤選項）

B:
（Python 程式碼，正確或錯誤選項）

C:
（Python 程式碼，正確或錯誤選項）

D:
（Python 程式碼，正確或錯誤選項）

正確答案：{\$correct_option}

✅【內容設計規範】
題目難度請依據 \$levels[\$level]["required"] 的數值大小設計（越大越難），並根據 \$levels[\$level]["desc"] 的描述設計主題。

題目語言：全程使用繁體中文敘述。

範例輸入/輸出：

使用簡單清楚的數據。

與題目一致，無多餘說明文字。

一個範例即可。

選項設計：

四個選項皆需為可執行的 Python 程式碼。

僅一個正確，其餘三個為合理的錯誤選項（例如：off-by-one 錯誤、範圍錯、少行、錯誤變數名、語法錯誤等）。

所有選項的程式碼皆需有語法正確縮排，程式碼段中不得使用 markdown、HTML 等標記。

正確答案：

只需標示 A、B、C 或 D，不得包含程式碼內容或多餘文字。

正解選項需生在rand(A,B,C,D)
EOT;

        $user_prompt = "levels = " . var_export($levels, true) . "\nlevel = $level";
        $data = [
            "model" => "gpt-4o-mini",
            "messages" => [
                ["role" => "system", "content" => $system_prompt],
                ["role" => "user", "content" => $user_prompt]
            ],
            "temperature" => 1,
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

        // 若 OpenAI 回傳錯誤
        if (isset($response['error'])) {
            echo json_encode([
                'error' => 'OpenAI API 錯誤: ' . $response['error']['message'],
                'raw' => $result
            ]);
            exit;
        }

        if (isset($response["choices"][0]["message"]["content"])) {
            $ai_content = nl2br(htmlspecialchars($response["choices"][0]["message"]["content"]));
        } else {
            echo json_encode([
                'error' => '無法取得題目內容，請稍後再試。',
                'raw' => $result
            ]);
            exit;
        }

        // 先把 <br /> 全部轉成換行符號方便處理
        $clean_text = str_replace('<br />', "\n", $ai_content);
        $clean_text = html_entity_decode($clean_text, ENT_QUOTES, 'UTF-8');
        $clean_text = preg_replace("/\n{2,}/", "\n", $clean_text); // 所有連續空行只保留1行

        // 拆分成題目描述、選項、正確答案三個部分
        $question_text = '';
        $options = '';
        $answer = '';
        if (preg_match('/^(.*?範例輸出：.*?)(選項：.*?)(正確答案：.*)$/s', $clean_text, $parts)) {
            // $parts[1]: 題目描述 + 範例輸入 + 範例輸出
            // $parts[2]: 選項
            // $parts[3]: 正確答案
            $question_text = trim($parts[1]);
            $options = trim($parts[2]);
            $answer = trim($parts[3]);
        }

        // 處理題目描述格式
        // 只保留「題目描述：」到「範例輸出：」的區塊，並去除多餘空行
        $question_text = preg_replace('/\s*\n*(範例輸入：)/u', "\n$1", $question_text);
        $question_text = preg_replace('/\s*\n*(範例輸出：)/u', "\n$1", $question_text);
        $question_text = preg_replace("/\n{3,}/", "\n\n", $question_text);
        $question_text = trim($question_text);

        // 每行前後去空白，並去除多餘空行
        $lines = explode("\n", $question_text);
        $lines = array_map('rtrim', $lines);
        $lines = array_map('ltrim', $lines);
        $question_text = implode("\n", array_filter($lines, function ($l) {
            return $l !== '';
        }));

        // 抓出 A-D 選項（將 $options 轉成陣列，key 為選項字母，value 為程式碼內容）
        $options_arr = [];
        if (!empty($options) && is_string($options)) {
            if (preg_match_all('/([A-D]):\s*([\s\S]*?)(?=(?:[A-D]:|$))/u', $options, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $opt) {
                    $label = $opt[1];
                    $code = trim($opt[2]);
                    $options_arr[$label] = $code;
                }
            }
        }
        $answer = preg_replace('/^正確答案：\s*/u', '', $answer);
        $answer = trim($answer);
        echo json_encode([
            'question_text' => $question_text,
            'options_arr' => $options_arr,
            'answer' => $answer
        ]);
        exit;
    }
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
</head>

<body>
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
                <div class="problem-container" style="height: 200px;">
                    <h2>挑戰題目</h2>
                    <div id="question-area" class="problem-description">
                        <div id="loading-msg" class="loading">題目生成中...</div>
                    </div>
                </div>
                <div id="options-area"></div>
            </div>
            <div class="battle-section">
                <div class="battle-message">
                    <div class="message-content" id="battle-message-content">
                        請選擇正確的 Python 程式碼答案！
                    </div>
                </div>
                <div class="battle-tutorial" style="height: 30%;">
                    <h3 class="tutorial-title"><i class="fas fa-book"></i> 關卡說明</h3>
                    <div class="tutorial-content">
                        <div class="maze-level-desc">
                            <?php echo htmlspecialchars($level_info['desc']); ?>
                        </div>
                        <div class="mb-4">
                            <span class="badge bg-primary fs-6">需要等級 <?php echo $level_info['required']; ?></span>
                            <span class="badge bg-success fs-6">你的等級 <?php echo $current_level; ?></span>
                        </div>
                    </div>
                </div>
                <div class="battle-actions mt-3">
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
        // 類似 level.php，頁面載入後用 AJAX 載入題目
        $(function() {
            // 取得目前網址參數
            let url = window.location.pathname + window.location.search + (window.location.search ? '&' : '?') + 'ajax=1';
            $.get(url)
                .done(function(data) {
                    // 若回傳為字串，嘗試轉為物件
                    if (typeof data === 'string') {
                        try { data = JSON.parse(data); } catch(e) {}
                    }
                    console.log('AJAX 回傳資料:', data);
                    if (data.error) {
                        $('#question-area').html('<div class="text-danger">題目載入失敗：' + data.error + '</div>');
                        $('#options-area').html('');
                        $('#submitAnswerBtn').hide();
                        if (data.raw) {
                            console.log('OpenAI raw:', data.raw);
                        }
                        return;
                    }
                    // 題目
                    $('#question-area').html(
                        '<div>' + (data.question_text ? data.question_text.replace(/\n/g, '<br>') : '題目載入失敗') + '</div>'
                    );
                    // 選項
                    if (data.options_arr && Object.keys(data.options_arr).length > 0) {
                        let html = '<div class="card-header bg-warning text-dark fw-bold">選項</div>';
                        html += '<div class="card-body"><form class="mt-2"><div class="row">';
                        for (const label in data.options_arr) {
                            html += `<div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="code_option" id="option${label}" value="${label}">
                                    <label class="form-check-label w-100" for="option${label}">
                                        <span class="fw-bold">${label}.</span>
                                        <pre class="mb-0 text-start" style="background:#f8f9fa;border-radius:4px;padding:8px;">${data.options_arr[label]}</pre>
                                    </label>
                                </div>
                            </div>`;
                        }
                        html += '</div></form></div>';
                        $('#options-area').html(html);
                        $('#submitAnswerBtn').show();
                        window.correctAns = data.answer;
                        $('#loading-msg').remove();
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

        // 提交按鈕事件
        $('#submitAnswerBtn').on('click', function() {
            var checked = $('input[name="code_option"]:checked');
            if (!checked.length) {
                Swal.fire({
                    icon: 'warning',
                    title: '請先選擇一個選項',
                    confirmButtonText: '確定'
                });
                return;
            }
            var userAns = checked.val();
            var correctAns = window.correctAns;
            if (userAns === correctAns) {
                fetch('award.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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