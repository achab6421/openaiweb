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

選項:
只產生一個選項，為正確程式碼拆成多行，每行加上行號，並將行號順序打亂後排列（從1開始），例如：

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
    $clean_text = str_replace(["\r\n", "\r"], "\n", $clean_text);
    $clean_text = preg_replace("/\n{2,}/", "\n", $clean_text); // 所有連續空行只保留1行
    echo "<script>console.log(" . json_encode($clean_text) . ");</script>";

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

    // 進一步處理選項，每個選項格式為「數字.內容」
    $options_arr = [];
    if (!empty($options)) {
        if (preg_match_all('/(\d+)[\.:：]\s*(.+)/u', $options, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $num = $match[1];
                $code = trim($match[2]);
                $options_arr[$num] = $code;
            }
        }
    }

    echo "<script>console.log(" . json_encode($question_text) . ");</script>";
    echo "<script>console.log(" . json_encode($options) . ");</script>";

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

    echo "<script>console.log(" . json_encode($options) . ");</script>";

    // 將 $options 轉成陣列，key 為選項數字，value 為程式碼內容（保留縮排與多行，並將每行前的空格轉為\t）
    $options_arr = [];
    // 處理為陣列格式，key 為選項數字，value 為保留縮排的程式碼
    $lines = explode("\n", $options);

    foreach ($lines as $line) {
        // 使用 regex 抓取選項號與內容
        if (preg_match('/^(\d+)\.(.*)$/', $line, $matches)) {
            $key = $matches[1];
            $code = $matches[2];

            // 將每一行前方空白統一轉成 \t
            $code = preg_replace_callback('/^( +)/', function ($m) {
                $spaceCount = strlen($m[1]);
                return str_repeat("\t", intval($spaceCount / 4)); // 每四個空白視為一個 tab
            }, $code);

            $options_arr[$key] = $code;
        }
    }

    echo "<script>console.log(" . json_encode($options_arr) . ");</script>";
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
                        <form class="mt-2" id="sortableForm">
                            <ul id="sortable-options" class="list-group">
                                <?php foreach ($options_arr as $label => $code): ?>
                                    <li class="list-group-item d-flex align-items-start" data-id="<?php echo $label; ?>">
                                        <pre class="mb-0 text-start flex-fill" style="background:#f8f9fa;border-radius:4px;padding:8px;"><?php
                                                                                                                                            // 將\t轉成4個&nbsp;以呈現縮排
                                                                                                                                            $display_code = preg_replace_callback('/\t/', function () {
                                                                                                                                                return str_repeat('&nbsp;', 4);
                                                                                                                                            }, $code);
                                                                                                                                            // 也將每行開頭的空白轉成&nbsp;（保險處理）
                                                                                                                                            $display_code = preg_replace_callback('/^([ ]+)/m', function ($m) {
                                                                                                                                                return str_repeat('&nbsp;', strlen($m[1]));
                                                                                                                                            }, $display_code);
                                                                                                                                            echo $display_code;
                                                                                                                                            ?></pre>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-back">
                <i class="fas fa-arrow-left mt-2"></i> 返回迷宮選單
            </a>
            <button type="button" class="btn btn-warning ms-2" id="resetBtn">
                <i class="fas fa-undo"></i> 重製題目
            </button>
            <button type="button" class="btn btn-success ms-2" id="submitAnswerBtn">
                <i class="fas fa-paper-plane"></i> 提交
            </button>
            <!-- end 顯示題目內容 -->
        </div>
    </div>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // 啟用 SortableJS
        var sortable = new Sortable(document.getElementById('sortable-options'), {
            animation: 150
        });

        // 重製題目按鈕
        document.getElementById('resetBtn').addEventListener('click', function() {
            window.location.reload();
        });

        document.getElementById('submitAnswerBtn').addEventListener('click', function() {
            // 取得目前排序
            var items = document.querySelectorAll('#sortable-options li');
            var userOrder = [];
            items.forEach(function(li) {
                userOrder.push(parseInt(li.getAttribute('data-id')));
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
                // 答對了，呼叫 award.php
                fetch('award.php', {
                    method: 'POST',
                    credentials: 'same-origin', // 確保帶上 cookie
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
                    // 403 已處理，其他錯誤才進這裡
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