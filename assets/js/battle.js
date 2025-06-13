// Python 怪物村戰鬥和程式編輯功能

// 全局變量
let editor;
let currentThreadId = null;
let currentProblem = '';
let battleState = {
    playerHp: levelData.playerHp,
    monsterHp: levelData.monsterHp,
    isPlayerTurn: true,
    isBattleOver: false,
    wave: 1,
    maxWaves: levelData.waveCount,
    hasWon: false
};

// 頁面加載完成後初始化
document.addEventListener('DOMContentLoaded', function() {
    // 初始化程式碼編輯器
    initCodeEditor();
    
    // 獲取問題內容
    loadProblem();
    
    // 設置按鈕事件
    document.getElementById('test-code').addEventListener('click', testCode);
    document.getElementById('submit-code').addEventListener('click', submitCode);
    document.getElementById('retry-button').addEventListener('click', resetBattle);
    
    // 初始化教學標籤頁切換功能
    initTutorialTabs();
});

// 初始化程式碼編輯器
function initCodeEditor() {
    // 使用 CodeMirror 創建程式碼編輯器
    editor = CodeMirror.fromTextArea(document.getElementById('code-editor'), {
        mode: 'python',
        theme: 'dracula',
        lineNumbers: true,
        indentUnit: 4,
        matchBrackets: true,
        autoCloseBrackets: true,
        lineWrapping: true
    });
    
    // 設置預設程式碼
    editor.setValue('# 請在這裡編寫您的Python程式碼\n\n');
}

// 獲取問題內容
function loadProblem() {
    const outputDisplay = document.getElementById('problem-description');
    outputDisplay.innerHTML = '<div class="loading">正在載入題目...</div>';
    
    // 向後端API發送請求
    fetch('api/generate-problem.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            levelId: levelData.levelId,
            chapterId: levelData.chapterId,
            teachingPoint: levelData.teachingPoint
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 保存問題和線程ID
            currentProblem = data.problem;
            currentThreadId = data.threadId;
            
            // 顯示問題描述
            outputDisplay.innerHTML = renderMarkdown(data.problem);
            
            // 檢查問題中是否有程式碼框架，如果有，更新編輯器
            const codeFramework = extractCodeFramework(data.problem);
            if (codeFramework) {
                editor.setValue(codeFramework);
            }
            
            // 顯示戰鬥消息
            updateBattleMessage('題目已載入，請編寫程式碼來擊敗怪物！');
        } else {
            outputDisplay.innerHTML = `<div class="error">載入題目失敗: ${data.message}</div>`;
            updateBattleMessage('載入題目失敗，請重新整理頁面。');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        outputDisplay.innerHTML = '<div class="error">載入題目時發生錯誤，請稍後再試。</div>';
    });
}

// 從問題中提取程式碼框架
function extractCodeFramework(problem) {
    const regex = /```python\n([\s\S]*?)```/g;
    const matches = [...problem.matchAll(regex)];
    
    // 返回最後一個Python程式碼塊，通常是框架
    if (matches.length > 0) {
        return matches[matches.length - 1][1];
    }
    
    return null;
}

// 測試程式碼
function testCode() {
    const code = editor.getValue();
    const outputDisplay = document.getElementById('output-display');
    
    if (!code.trim()) {
        outputDisplay.innerHTML = '請先編寫程式碼';
        outputDisplay.classList.add('error');
        return;
    }
    
    // 顯示測試中...
    outputDisplay.innerHTML = '<div class="loading">測試運行中...</div>';
    outputDisplay.classList.remove('error');
    
    // 添加測試調試日誌
    console.log('測試代碼請求開始，代碼長度:', code.length);
    
    // 使用XMLHttpRequest而不是fetch，因為可能有瀏覽器兼容性問題
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/test-code.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.responseType = 'json';
    
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            console.log('測試代碼請求成功:', xhr.response);
            const data = xhr.response;
            
            if (data && data.success === true) {
                let resultHTML = '';
                
                // 顯示輸出
                if (data.output && data.output.trim()) {
                    resultHTML += '<h4>程式輸出:</h4>';
                    resultHTML += `<pre class="output">${formatOutput(data.output)}</pre>`;
                } else {
                    resultHTML += '<p>程式沒有輸出</p>';
                }
                
                // 如果有警告/錯誤但執行成功，也顯示
                if (data.errors && data.errors.trim() && !data.isError) {
                    resultHTML += '<h4>警告/注意:</h4>';
                    resultHTML += `<pre class="warning">${formatOutput(data.errors)}</pre>`;
                }
                
                // 顯示執行時間
                if (data.executionTime) {
                    resultHTML += `<div class="execution-info">執行時間: ${data.executionTime} 毫秒</div>`;
                }
                
                outputDisplay.innerHTML = resultHTML;
                outputDisplay.classList.remove('error');
            } else {
                outputDisplay.innerHTML = `<div class="error-title">執行錯誤</div>`;
                
                if (data && data.message) {
                    outputDisplay.innerHTML += `<div>${data.message}</div>`;
                }
                
                if (data && data.errors && data.errors.trim()) {
                    outputDisplay.innerHTML += `<pre class="error-details">${formatOutput(data.errors)}</pre>`;
                }
                
                outputDisplay.classList.add('error');
            }
        } else {
            console.error('測試代碼請求失敗:', xhr.status, xhr.statusText);
            outputDisplay.innerHTML = `測試運行時發生錯誤: HTTP ${xhr.status} ${xhr.statusText}`;
            outputDisplay.classList.add('error');
        }
    };
    
    xhr.onerror = function() {
        console.error('測試代碼網絡錯誤');
        outputDisplay.innerHTML = '網絡錯誤，無法連接到伺服器';
        outputDisplay.classList.add('error');
    };
    
    const data = JSON.stringify({
        code: code,
        levelId: levelData.levelId || 0
    });
    
    console.log('發送請求數據...');
    xhr.send(data);
}

// 將輸出格式化為HTML，處理中文字符
function formatOutput(output) {
    if (!output) return '無輸出';
    
    // 確保輸出是字符串
    if (typeof output !== 'string') {
        output = String(output);
    }
    
    // HTML編碼以防止XSS攻擊，同時保留換行符
    return output
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/\n/g, '<br>');
}

// 提交程式碼答案
function submitCode() {
    if (battleState.isBattleOver) {
        updateBattleMessage('戰鬥已結束，請點擊重試按鈕重新開始。');
        return;
    }
    
    const outputDisplay = document.getElementById('output-display');
    const code = editor.getValue();
    
    if (!code.trim()) {
        outputDisplay.innerHTML = '請先編寫程式碼';
        outputDisplay.classList.add('error');
        return;
    }
    
    // 禁用提交按鈕，防止重複提交
    const submitButton = document.getElementById('submit-code');
    submitButton.disabled = true;
    submitButton.textContent = '檢查中...';
    
    // 顯示檢查中
    outputDisplay.innerHTML = '檢查答案中...';
    outputDisplay.classList.remove('error');
    updateBattleMessage('正在檢查您的答案...');
    
    // 添加除錯資訊
    console.log('提交答案:', {
        levelId: levelData.levelId,
        threadId: currentThreadId,
        codePreview: code.substring(0, 50) + (code.length > 50 ? '...' : '')
    });
    
    // 發送代碼到後端進行評估
    fetch('api/evaluate-answer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            levelId: levelData.levelId,
            userCode: code,
            threadId: currentThreadId,
            problemStatement: currentProblem
        })
    })
    .then(response => {
        console.log('評估回應狀態:', response.status);
        
        if (!response.ok) {
            throw new Error('網絡回應不正常: ' + response.status);
        }
        
        // 檢查回應編碼
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error(`回應格式不正確: ${contentType}`);
        }
        
        return response.json();
    })
    .then(data => {
        // 恢復提交按鈕
        submitButton.disabled = false;
        submitButton.textContent = '提交答案';
        
        console.log('評估回應數據:', data);
        
        if (data.success) {
            // 處理評估結果
            const isCorrect = data.isCorrect;
            const evaluationText = data.evaluation || '';
            
            console.log('答案是否正確:', isCorrect);
            
            // 顯示回饋 - 將評估結果格式化後顯示
            outputDisplay.innerHTML = formatEvaluationResult(evaluationText, isCorrect);
            
            // 根據結果執行戰鬥邏輯
            if (isCorrect) {
                outputDisplay.classList.remove('error');
                try {
                    playerAttack();  // 執行玩家攻擊
                    console.log('玩家攻擊成功執行');
                } catch(e) {
                    console.error('執行玩家攻擊時發生錯誤:', e);
                    updateBattleMessage('回應處理過程中發生錯誤，但答案是正確的！');
                }
            } else {
                outputDisplay.classList.add('error');
                try {
                    monsterAttack();  // 執行怪物攻擊
                    console.log('怪物攻擊成功執行');
                } catch(e) {
                    console.error('執行怪物攻擊時發生錯誤:', e);
                    updateBattleMessage('回應處理過程中發生錯誤，請檢查您的答案並再試一次。');
                }
            }
        } else {
            outputDisplay.innerHTML = `驗證失敗: ${data.message || '未知錯誤'}`;
            outputDisplay.classList.add('error');
            updateBattleMessage('無法驗證您的答案，請稍後再試。');
        }
    })
    .catch(error => {
        // 恢復提交按鈕
        submitButton.disabled = false;
        submitButton.textContent = '提交答案';
        
        console.error('提交答案錯誤:', error);
        outputDisplay.innerHTML = '驗證期間發生錯誤，請稍後再試。<br>詳細錯誤: ' + error.message;
        outputDisplay.classList.add('error');
        updateBattleMessage('發生錯誤，請稍後再試。');
    });
}

// 改進評估結果格式化函數
function formatEvaluationResult(evaluation, isCorrect) {
    if (!evaluation || evaluation.trim() === '') {
        return `<div class="evaluation-header ${isCorrect ? 'correct' : 'incorrect'}">
            <h3>${isCorrect ? '恭喜，答案正確！' : '答案不正確'}</h3>
            <p>${isCorrect ? '您成功解決了這個問題！' : '請檢查您的答案並再試一次。'}</p>
        </div>`;
    }
    
    // 先創建基本標題
    let formattedResult = `<div class="evaluation-header ${isCorrect ? 'correct' : 'incorrect'}">
        <h3>${isCorrect ? '恭喜，答案正確！' : '答案不正確'}</h3>
    </div>`;
    
    // 將評估內容格式化
    let formattedEvaluation = evaluation
        .replace(/\n/g, '<br>')
        .replace(/```python([\s\S]*?)```/g, '<pre class="code-block"><code>$1</code></pre>')
        .replace(/評估結果: (正確|不正確)/g, '<strong class="evaluation-result $1">評估結果: $1</strong>')
        .replace(/評估結果：(正確|不正確)/g, '<strong class="evaluation-result $1">評估結果：$1</strong>')
        .replace(/詳細分析:/g, '<strong class="evaluation-section">詳細分析:</strong>')
        .replace(/詳細分析：/g, '<strong class="evaluation-section">詳細分析：</strong>')
        .replace(/改進建議:/g, '<strong class="evaluation-section">改進建議:</strong>')
        .replace(/改進建議：/g, '<strong class="evaluation-section">改進建議：</strong>');
    
    return formattedResult + '<div class="evaluation-content">' + formattedEvaluation + '</div>';
}

// 玩家攻擊函數
function playerAttack() {
    // 防止重複觸發
    if (!battleState.isPlayerTurn || battleState.isBattleOver) {
        console.warn('非玩家回合或戰鬥已結束');
        return;
    }
    
    try {
        console.log('玩家攻擊開始，目前狀態:', JSON.stringify(battleState));
        
        // 計算傷害
        const damage = calculateDamage(levelData.playerAttackPower || 10);
        battleState.monsterHp -= damage;
        
        // 更新UI
        updateBattleMessage(`你的答案正確！對怪物造成了 ${damage} 點傷害！`);
        updateHealthBars();
        
        // 檢查怪物是否被擊敗
        if (battleState.monsterHp <= 0) {
            // 檢查是否還有下一波
            if (battleState.wave < battleState.maxWaves) {
                nextWave();
            } else {
                endBattle(true); // 玩家勝利
            }
        } else {
            // 切換回合
            battleState.isPlayerTurn = false;
            setTimeout(monsterAttack, 1500); // 1.5秒後怪物反擊
        }
        
        console.log('玩家攻擊結束，更新後狀態:', JSON.stringify(battleState));
    } catch (e) {
        console.error('玩家攻擊函數出錯:', e);
    }
}

// 怪物攻擊
function monsterAttack() {
    if (battleState.isBattleOver) return;
    
    // 計算傷害
    const damage = Math.floor(levelData.monsterAttack * (0.8 + Math.random() * 0.4));
    
    // 玩家受到傷害
    battleState.playerHp -= damage;
    
    // 確保生命值不會小於0
    if (battleState.playerHp < 0) battleState.playerHp = 0;
    
    // 更新玩家生命值
    updatePlayerHp();
    
    // 顯示受傷效果
    // 目前簡單顯示傷害文本
    document.querySelector('.character-unit').classList.add('damaged');
    setTimeout(() => {
        document.querySelector('.character-unit').classList.remove('damaged');
    }, 500);
    
    // 顯示戰鬥消息
    updateBattleMessage(`怪物攻擊了您，造成 ${damage} 點傷害！`);
    
    // 檢查戰鬥是否結束
    setTimeout(() => {
        if (battleState.playerHp <= 0) {
            // 戰鬥失敗
            battleState.isBattleOver = true;
            battleState.hasWon = false;
            updateBattleMessage('戰鬥失敗！您被怪物擊敗了...');
            showResultModal(false);
        }
    }, 1000);
}

// 更新怪物生命值顯示
function updateMonsterHp() {
    const monster = document.getElementById('monster1');
    const hpText = monster.querySelector('.current-hp');
    const hpFill = monster.querySelector('.hp-fill');
    
    hpText.textContent = Math.max(0, battleState.monsterHp);
    
    // 更新血條百分比
    const hpPercent = Math.max(0, (battleState.monsterHp / levelData.monsterHp) * 100);
    hpFill.style.width = `${hpPercent}%`;
    
    // 血條顏色變化
    if (hpPercent < 20) {
        hpFill.style.backgroundColor = '#e74c3c';
    } else if (hpPercent < 50) {
        hpFill.style.backgroundColor = '#f39c12';
    } else {
        hpFill.style.backgroundColor = '#2ecc71';
    }
}

// 更新玩家生命值顯示
function updatePlayerHp() {
    const playerHpElement = document.getElementById('player-hp');
    const characterHpElement = document.querySelector('.hp-value');
    
    playerHpElement.textContent = Math.max(0, battleState.playerHp);
    characterHpElement.textContent = Math.max(0, battleState.playerHp);
}

// 顯示攻擊效果
function showAttackEffect(targetId, damage) {
    const target = document.getElementById(targetId);
    const effectsContainer = target.querySelector('.monster-effects');
    
    // 創建攻擊效果元素
    const attackEffect = document.createElement('div');
    attackEffect.className = 'attack-effect';
    effectsContainer.appendChild(attackEffect);
    
    // 創建傷害文字
    const damageText = document.createElement('div');
    damageText.className = 'damage-text';
    damageText.textContent = `-${damage}`;
    damageText.style.left = `${Math.random() * 60 + 20}%`;
    damageText.style.top = `${Math.random() * 60 + 20}%`;
    effectsContainer.appendChild(damageText);
    
    // 動畫結束後移除元素
    setTimeout(() => {
        attackEffect.remove();
        damageText.remove();
    }, 1000);
}

// 更新戰鬥消息
function updateBattleMessage(message) {
    document.getElementById('battle-message-content').textContent = message;
}

// 顯示結果彈窗
function showResultModal(isVictory) {
    const modal = document.getElementById('result-modal');
    const title = document.getElementById('result-title');
    const message = document.getElementById('result-message');
    const expGain = document.getElementById('exp-gain');
    
    if (isVictory) {
        title.textContent = '戰鬥勝利！';
        message.innerHTML = `
            <p>恭喜您成功完成了這個關卡！</p>
            <p>您的程式碼成功地解決了問題，並擊敗了怪物！</p>
        `;
        expGain.textContent = levelData.expReward;
    } else {
        title.textContent = '戰鬥失敗';
        message.innerHTML = `
            <p>很遺憾，您在這次挑戰中失敗了。</p>
            <p>請修改您的程式碼，然後再次嘗試。</p>
        `;
        expGain.textContent = '0';
    }
    
    modal.style.display = 'block';
}

// 重置戰鬥狀態
function resetBattle() {
    // 重置戰鬥狀態
    battleState = {
        playerHp: levelData.playerHp,
        monsterHp: levelData.monsterHp,
        isPlayerTurn: true,
        isBattleOver: false,
        wave: 1,
        maxWaves: levelData.waveCount,
        hasWon: false
    };
    
    // 更新生命值顯示
    updatePlayerHp();
    updateMonsterHp();
    
    // 隱藏結果彈窗
    document.getElementById('result-modal').style.display = 'none';
    
    // 更新戰鬥消息
    updateBattleMessage('戰鬥已重置，請編寫程式碼以擊敗怪物！');
    
    // 清空輸出區
    document.getElementById('output-display').innerHTML = '';
}

// 初始化教學標籤頁
function initTutorialTabs() {
    const tabs = document.querySelectorAll('.tutorial-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // 移除所有標籤頁的活動狀態
            tabs.forEach(t => t.classList.remove('active'));
            
            // 移除所有面板的活動狀態
            document.querySelectorAll('.tutorial-panel').forEach(p => p.classList.remove('active'));
            
            // 激活當前標籤頁和對應的面板
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

// 將Markdown轉換為HTML
function renderMarkdown(markdown) {
    // 簡單的Markdown轉HTML處理
    // 標題
    let html = markdown.replace(/^## (.*$)/gm, '<h2>$1</h2>')
                       .replace(/^### (.*$)/gm, '<h3>$1</h3>')
                       .replace(/^#### (.*$)/gm, '<h4>$1</h4>');
    
    // 程式碼塊
    html = html.replace(/```python\n([\s\S]*?)```/g, '<pre><code class="language-python">$1</code></pre>');
    html = html.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
    
    // 粗體和斜體
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    
    // 列表
    html = html.replace(/^\s*[\-\*]\s+(.*$)/gm, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>\n)+/g, '<ul>$&</ul>');
    
    // 段落
    html = html.replace(/^(?!<[hou])(.+)$/gm, '<p>$1</p>');
    
    return html;
}