<?php
// 確保包含了必要的頭部信息和導航
// ...existing code...
?>

<div class="challenge-content">
    <!-- 題目顯示區域 -->
    <div id="problem-display" class="problem-container">
        <!-- 題目內容將在這裡動態載入 -->
    </div>
    
    <!-- 代碼編輯器區域 -->
    <div class="code-editor-section">
        <!-- ...existing code... -->
    </div>
</div>

<!-- 引入格式化工具 -->
<link rel="stylesheet" href="../css/problem-display.css">
<script src="../js/problem-formatter.js"></script>

<script>
// 載入題目
function loadProblem(levelId) {
    // ...existing code...
    
    fetch('../api/generate-problem.php', {
        // ...existing request code...
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 使用格式化工具處理問題文本
            const problemHtml = ProblemFormatter.formatProblem(data.problem);
            document.getElementById('problem-display').innerHTML = problemHtml;
        } else {
            document.getElementById('problem-display').innerHTML = 
                `<div class="error-message">載入題目失敗: ${data.message}</div>`;
        }
    })
    .catch(error => {
        // ...existing error handling...
    });
}

// ...existing code...
</script>