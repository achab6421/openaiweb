<?php
// 初始化會話
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// 引入 OpenAI API 客戶端
require_once "../../config/openai_client.php";

$chapter_id = isset($_GET["chapter_id"]) ? intval($_GET["chapter_id"]) : 0;

// 生成 AI 題目
$ai_question = "撰寫一個函數來計算給定數字的階乘。";

// 處理玩家提交的答案
$feedback = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $player_answer = $_POST["answer"];

    // 使用 OpenAI API 批改答案
    $response = $openai->complete([
        'model' => 'text-davinci-003',
        'prompt' => "檢查以下 Python 程式碼是否正確計算階乘:\n\n" . $player_answer,
        'max_tokens' => 100,
    ]);

    $feedback = $response['choices'][0]['text'];
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 生成實作題 - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">AI 生成實作題</h1>
        <div class="card mt-4">
            <div class="card-body">
                <p><strong>題目：</strong><?php echo htmlspecialchars($ai_question); ?></p>
                <form method="post">
                    <div class="mb-3">
                        <label for="answer" class="form-label">請輸入您的答案 (Python 程式碼)：</label>
                        <textarea id="answer" name="answer" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">提交答案</button>
                </form>
                <?php if (!empty($feedback)): ?>
                    <div class="alert alert-info mt-4">
                        <strong>批改結果：</strong>
                        <p><?php echo nl2br(htmlspecialchars($feedback)); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="detail.php?id=<?php echo $chapter_id; ?>" class="btn btn-secondary">返回章節頁面</a>
        </div>
    </div>
</body>
</html>
