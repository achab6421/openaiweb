<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once "../../config/database_game.php";

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
$ai_content = "";
// 讀取 .env 並取得 OPENAI_API_KEY_11
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
$OPENAI_API_KEY = isset($env['OPENAI_API_KEY_11']) ? $env['OPENAI_API_KEY_11'] : '';

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

正確答案：（A、B、C、D 其中之一，請**僅標示選項字母**）

✅【內容設計規範】
題目難度請依據 \$levels[$level]["required"] 的數值大小設計（越大越難），並根據 \$levels[$level]["desc"] 的描述設計主題。

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
    curl_close($ch);
    $response = json_decode($result, true);

    if (isset($response["choices"][0]["message"]["content"])) {
        $ai_content = nl2br(htmlspecialchars($response["choices"][0]["message"]["content"]));
    } else {
        $ai_content = "<p>無法取得題目，請稍後再試。</p>";
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
    echo "<script>console.log(" . json_encode($response["choices"][0]["message"]["content"]) . ");</script>";
    echo "<script>console.log(" . json_encode($clean_text) . ");</script>";
    echo "<script>console.log(" . json_encode($question_text) . ");</script>";
    echo "<script>console.log(" . json_encode($options_arr) . ");</script>";
    echo "<script>console.log(" . json_encode($answer) . ");</script>";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('../assets/images/maze-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }

        .maze-level-detail {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 15px;
            margin: 40px auto;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .maze-level-title {
            font-size: 2rem;
            font-weight: bold;
            color: #D81B60;
            margin-bottom: 20px;
        }

        .maze-level-desc {
            font-size: 1.15rem;
            margin-bottom: 30px;
        }

        .btn-back {
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="maze-level-detail text-center">
            <div class="maze-level-title">
                <i class="fas fa-dungeon me-2"></i><?php echo htmlspecialchars($level_info['name']); ?>
            </div>
            <div class="maze-level-desc">
                <?php echo htmlspecialchars($level_info['desc']); ?>
            </div>
            <div class="mb-4">
                <span class="badge bg-primary fs-6">需要等級 <?php echo $level_info['required']; ?></span>
                <span class="badge bg-success fs-6">你的等級 <?php echo $current_level; ?></span>
            </div>
            <!-- 顯示題目內容 -->
            <div class="card mt-4 text-start">
                <div class="card-header bg-warning text-dark fw-bold">
                    關卡題目
                </div>
                <div class="card-body">
                    <?php
                    // 顯示題目描述
                    echo nl2br(htmlspecialchars($question_text));
                    ?>
                </div>
                <?php if (!empty($options_arr)): ?>
                    <div class="card-header bg-warning text-dark fw-bold">
                        選項
                    </div>
                    <div class="card-body">
                        <form class="mt-2">
                            <div class="row">
                                <?php foreach ($options_arr as $label => $code): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="code_option" id="option<?php echo $label; ?>" value="<?php echo $label; ?>">
                                            <label class="form-check-label w-100" for="option<?php echo $label; ?>">
                                                <span class="fw-bold"><?php echo $label; ?>.</span>
                                                <pre class="mb-0 text-start" style="background:#f8f9fa;border-radius:4px;padding:8px;"><?php echo htmlspecialchars(preg_replace("/\n{2,}/", "\n", $code)); ?></pre>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-back">
                <i class="fas fa-arrow-left mt-2"></i> 返回迷宮選單
            </a>
            <button type="button" class="btn btn-success ms-2" id="submitAnswerBtn">
                <i class="fas fa-paper-plane"></i> 提交
            </button>
            <!-- end 顯示題目內容 -->
        </div>
    </div>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('submitAnswerBtn').addEventListener('click', function() {
            // 取得選中的選項
            var checked = document.querySelector('input[name="code_option"]:checked');
            if (!checked) {
                Swal.fire({
                    icon: 'warning',
                    title: '請先選擇一個選項',
                    confirmButtonText: '確定'
                });
                return;
            }
            var userAns = checked.value;
            var correctAns = <?php echo json_encode($answer); ?>;
            if (userAns === correctAns) {
                // 答對了，呼叫 award.php
                fetch('award.php', {
                    method: 'POST',
                    credentials: 'same-origin', // 確保帶上 cookie
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: ''
                })
                .then(response => {
                    if (!response.ok) throw new Error('award.php failed');
                    return response.json();
                })
                .then(data => {
                    Swal.fire({
                        icon: 'success',
                        title: '答對了！',
                        html: '攻擊力 + ' + data.attack_power + '<br>防禦力 + ' + data.defense_power,
                        confirmButtonText: '返回選單'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'warning',
                        title: '答對了，但獎勵未發放',
                        text: '請稍後再試或聯絡管理員',
                        confirmButtonText: '返回選單'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
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