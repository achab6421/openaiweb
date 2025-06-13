document.addEventListener('DOMContentLoaded', function() {
    const codeSubmitBtn = document.getElementById('submit-code');
    const codeEditor = document.getElementById('code-editor');
    const evaluationResultElement = document.getElementById('evaluation-result');
    const loadingIndicator = document.getElementById('loading-indicator');
    
    if (codeSubmitBtn) {
        codeSubmitBtn.addEventListener('click', submitCode);
    }
    
    function submitCode() {
        // 獲取必要的數據
        const levelId = codeSubmitBtn.dataset.levelId;
        const threadId = codeSubmitBtn.dataset.threadId;
        const problemStatement = document.getElementById('problem-statement').textContent;
        const userCode = codeEditor.value;
        
        if (!userCode.trim()) {
            showEvaluationResult('請先編寫程式碼再提交', false);
            return;
        }
        
        // 顯示載入指示器
        if (loadingIndicator) loadingIndicator.style.display = 'block';
        if (evaluationResultElement) evaluationResultElement.style.display = 'none';
        
        // 禁用提交按鈕避免重複提交
        codeSubmitBtn.disabled = true;
        
        // 發送至後端API
        fetch('/api/evaluate-answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                levelId: levelId,
                userCode: userCode,
                threadId: threadId,
                problemStatement: problemStatement
            })
        })
        .then(response => response.json())
        .then(data => {
            // 隱藏載入指示器
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            
            if (data.success) {
                // 顯示評估結果
                showEvaluationResult(data.evaluation, data.isCorrect);
                
                // 如果答案正確，可以進行額外處理（例如顯示通關提示）
                if (data.isCorrect) {
                    showLevelCompletedMessage();
                }
            } else {
                showEvaluationResult('評估過程中發生錯誤: ' + data.message, false);
            }
        })
        .catch(error => {
            console.error('Error submitting code:', error);
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            showEvaluationResult('提交代碼時發生錯誤，請稍後再試。', false);
        })
        .finally(() => {
            // 重新啟用提交按鈕
            codeSubmitBtn.disabled = false;
        });
    }
    
    function showEvaluationResult(message, isCorrect) {
        if (!evaluationResultElement) return;
        
        evaluationResultElement.innerHTML = formatEvaluationResult(message);
        evaluationResultElement.className = isCorrect ? 'evaluation-success' : 'evaluation-feedback';
        evaluationResultElement.style.display = 'block';
        evaluationResultElement.scrollIntoView({ behavior: 'smooth' });
    }
    
    function formatEvaluationResult(message) {
        // 將評估結果格式化為HTML
        return message
            .replace(/\n/g, '<br>')
            .replace(/```python([\s\S]*?)```/g, '<pre class="code-block"><code>$1</code></pre>');
    }
    
    function showLevelCompletedMessage() {
        // 顯示關卡完成的訊息和下一步選項
        const completionMessage = document.createElement('div');
        completionMessage.className = 'level-completed-message';
        completionMessage.innerHTML = `
            <h3>🎉 恭喜您完成這個關卡！</h3>
            <div class="next-actions">
                <button id="next-level-btn" class="btn btn-primary">進入下一關</button>
                <button id="back-to-map-btn" class="btn btn-secondary">返回地圖</button>
            </div>
        `;
        
        document.querySelector('.challenge-container').appendChild(completionMessage);
        
        // 添加按鈕事件
        document.getElementById('next-level-btn').addEventListener('click', goToNextLevel);
        document.getElementById('back-to-map-btn').addEventListener('click', goToMap);
    }
    
    function goToNextLevel() {
        // 從當前URL或按鈕數據中獲取下一關的ID
        const currentLevelId = parseInt(codeSubmitBtn.dataset.levelId);
        window.location.href = `/level.php?id=${currentLevelId + 1}`;
    }
    
    function goToMap() {
        window.location.href = '/map.php';
    }
});
