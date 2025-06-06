<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

if(!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: detail.php");
    exit;
}

$question_id = intval($_GET["id"]);
require_once "../../config/database_game.php";

// 取得題目資料
$question = null;
$sql = "SELECT * FROM questions WHERE question_id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $question_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        if($result->num_rows == 1) {
            $question = $result->fetch_assoc();
        }
    }
    $stmt->close();
}
$conn->close();

if(!$question) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>找不到題目。</div></div>";
    exit;
}

// 處理答案提交
$feedback = "";
if($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_answer = "";
    if($question["question_type"] === "選擇題") {
        $user_answer = $_POST["choice"] ?? "";
    } else if($question["question_type"] === "排序題") {
        $user_answer = $_POST["order"] ?? "";
    } else {
        $user_answer = trim($_POST["answer"] ?? "");
    }
    // 這裡僅做簡單比對，實際可根據題型擴充
    if($user_answer === $question["answer"]) {
        $feedback = "<div class='alert alert-success mt-3'>答對了！</div>";
    } else {
        $feedback = "<div class='alert alert-danger mt-3'>答案不正確，請再試一次。</div>";
    }
}

// 定義你要傳給 OpenAI 的參數
$question_type = $question["question_type"];
$content = $question["content"];

// 讀取 .env 並取得 OPENAI_API_KEY_11
$dotenv_path = dirname(__DIR__, 2) . '/.env';
$env = parse_ini_file($dotenv_path, false, INI_SCANNER_RAW);
$OPENAI_API_KEY = isset($env['OPENAI_API_KEY_11']) ? trim($env['OPENAI_API_KEY_11'], "\"' ") : '';

// 組合 prompt
$prompt = "($question_type)$content";

// OpenAI API 請求
$system_prompt = <<<EOT
你是 Python 的教學助手，僅使用 Python 語法來解題，不使用其他程式語言。

請根據題目類型直接回答，**不要重複題目內容，也不要加任何說明**。

若為選擇題，請提供：
(A) ...
(B) ...
(C) ...
(D) ...
正解:X

若為排序題，請遵守以下規則：
- 正確邏輯順序為 1→2→3→4（程式碼執行順序）
- 請**將段落編號（1、2、3、4）打亂順序**後輸出，且**不能按照 1→2→3→4 順序呈現**
- 每段編號前面仍標示原本的編號，例如 `3:`、`1:`、`4:`、`2:`
- 若有註解（以 `#` 開頭），必須**放在對應程式碼的前一段**
- 註解本身算一段，程式碼算一段（例如：註解 1 段 + 程式碼 1 段 = 2 段）
- 總共仍然要輸出四段
- 每段獨立換行，不合併行

範例：
1: # 優先運算，計算乘法再加法
2: result = 5 + 3 * 2
3: # 結果將存於 result 變數
4: print(result)

只允許使用 Python 語法，禁止使用其他語言或額外說明文字。
EOT;

$payload = [
    "model" => "gpt-4o-mini",
    "messages" => [
        [
            "role" => "system",
            "content" => $system_prompt
        ],
        [
            "role" => "user",
            "content" => $prompt
        ]
    ]
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $OPENAI_API_KEY
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
curl_close($ch);

$output = "";
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['choices'][0]['message']['content'])) {
        $output = $data['choices'][0]['message']['content'];
    }
}

// 印出 OpenAI 回傳的結果
$options = explode("\n", trim($output));
$options = array_filter(array_map(function($v) {
    // 去除前後空白
    $v = trim($v);
    // 過濾空字串
    if ($v === '') return false;
    // 去除 (A)(B)(C)(D) 或 1: 2: 3: 4: 前綴
    return preg_replace('/^(\([A-D]\)|[1-4]:)\s*/u', '', $v);
}, $options));
// 重新索引
$options = array_values(array_filter($options, function($v) { return $v !== ''; }));

echo "<script>console.log(".json_encode($options).")</script>";
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($question["title"]); ?> - 題目解答</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sortable-list { list-style: none; padding: 0; }
        .sortable-list li { padding: 8px 12px; margin-bottom: 6px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; cursor: move; }
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="detail.php?id=<?php echo $question["chapter_id"]; ?>" class="btn btn-secondary mb-3">
        <i class="fas fa-arrow-left me-1"></i> 返回章節
    </a>
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><?php echo htmlspecialchars($question["title"]); ?></h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <span class="badge bg-primary"><?php echo htmlspecialchars($question["question_type"]); ?></span>
            </div>
            <div class="mb-4">
                <?php echo nl2br(htmlspecialchars($question["content"])); ?>
            </div>
            <form method="post" id="answerForm">
                <?php if($question["question_type"] === "選擇題" && count($options) > 0): ?>
                    <?php
                        $labels = ['A', 'B', 'C', 'D'];
                        $displayOptions = array_slice($options, 0, 4);
                    ?>
                    <?php foreach($displayOptions as $idx => $opt): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="choice" id="choice<?php echo $idx; ?>" value="<?php echo htmlspecialchars($opt); ?>" <?php if(isset($_POST["choice"]) && $_POST["choice"] === $opt) echo "checked"; ?> required>
                            <label class="form-check-label" for="choice<?php echo $idx; ?>">
                                <strong><?php echo $labels[$idx]; ?>.</strong> <?php echo htmlspecialchars($opt); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php elseif($question["question_type"] === "排序題" && count($options) > 0): ?>
                    <?php
                        $displayOptions = array_slice($options, 0, 4);
                        $order = $displayOptions;
                        if(isset($_POST["order"]) && $_POST["order"]) {
                            $order = explode('|', $_POST["order"]);
                        }
                    ?>
                    <ul class="sortable-list" id="sortableList">
                        <?php foreach($order as $item): ?>
                            <li class="mb-2 list-group-item"><?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <input type="hidden" name="order" id="orderInput" value="<?php echo isset($_POST["order"]) ? htmlspecialchars($_POST["order"]) : implode('|', $displayOptions); ?>">
                    <div class="form-text mb-3">請拖曳下列四個物件排序，排序完成後再提交。</div>
                <?php else: ?>
                    <div class="mb-3">
                        <label for="answer" class="form-label">請輸入您的答案：</label>
                        <textarea class="form-control" id="answer" name="answer" rows="4" required><?php echo isset($_POST["answer"]) ? htmlspecialchars($_POST["answer"]) : ""; ?></textarea>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-paper-plane me-1"></i> 提交答案
                </button>
            </form>
            <?php echo $feedback; ?>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if($question["question_type"] === "排序題" && count($options) > 0): ?>
<!-- 引入 SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // 使用 SortableJS 實現拖曳排序
    const sortable = new Sortable(document.getElementById('sortableList'), {
        animation: 150,
        onSort: function () {
            let items = document.querySelectorAll('#sortableList li');
            let order = [];
            items.forEach(li => order.push(li.textContent));
            document.getElementById('orderInput').value = order.join('|');
        }
    });
</script>
<?php endif; ?>
</body>
</html>
