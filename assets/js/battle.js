// Python 怪物村戰鬼和程式編輯功能

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
    // initTutorialTabs();
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

            // --- 將題目內容傳給 AI 助理 ---
            if (window.aiReceiveMazeQuestion) {
                window.aiReceiveMazeQuestion(data.problem);
            }
            // --- end ---
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
    // 取得主編輯器內容
    let code = editor.getValue();
    // 取得多行輸入框內容
    const inputValue = document.getElementById('editor-input') ? document.getElementById('editor-input').value : '';
    const outputDisplay = document.getElementById('output-display');

    // 將多行輸入框內容依行分割，模擬多次 input()
    const inputLines = inputValue.split(/\r?\n/);
    let inputIndex = 0;
    // 用唯一字串暫時替換 input()
    const inputPlaceholder = '__AI_INPUT_PLACEHOLDER__';
    code = code.replace(/input\s*\(\s*\)/g, inputPlaceholder);

    // 依序替換每個 input() 為對應行
    code = code.replace(new RegExp(inputPlaceholder, 'g'), function() {
        // 若超過輸入行數則給空字串
        return JSON.stringify(inputLines[inputIndex++] ?? '');
    });

    if (!code.trim()) {
        outputDisplay.innerHTML = '請先編寫程式碼';
        outputDisplay.classList.add('error');
        return;
    }

    // 顯示測試中...
    outputDisplay.innerHTML = '<div class="loading">測試運行中...</div>';
    outputDisplay.classList.remove('error');

    // 添加測試調試日誌
    console.log('測試代碼請求開始，代碼長度:', code.length, '輸入框內容:', inputValue);

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
        levelId: levelData.levelId || 0,
        input: inputValue // 仍保留原始輸入框內容
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
    
    const code = editor.getValue();
    const outputDisplay = document.getElementById('output-display');
    
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
    .then(response => response.json())
    .then(data => {
        // 恢復提交按鈕
        submitButton.disabled = false;
        submitButton.textContent = '提交答案';
        
        console.log('評估結果:', data);
        
        if (data.success) {
            // 處理評估結果
            const isCorrect = data.isCorrect;
            const evaluationText = data.evaluation;
            
            // 顯示回饋 - 將評估結果格式化後顯示
            outputDisplay.innerHTML = formatEvaluationResult(evaluationText, isCorrect);
            
            // 根據結果執行戰鬥邏輯
            if (isCorrect) {
                outputDisplay.classList.remove('error');
                // 答案正確，進行玩家攻擊
                playerAttack();
                // 注意：玩家攻擊函數內部會處理後續的重新生成題目邏輯
            } else {
                outputDisplay.classList.add('error');
                // 答案錯誤，進行怪物攻擊
                monsterAttack();
            }
        } else {
            outputDisplay.innerHTML = `驗證失敗: ${data.message}`;
            outputDisplay.classList.add('error');
        }
    })
    .catch(error => {
        // 恢復提交按鈕
        submitButton.disabled = false;
        submitButton.textContent = '提交答案';
        
        console.error('Error:', error);
        outputDisplay.innerHTML = '驗證期間發生錯誤，請稍後再試。';
        outputDisplay.classList.add('error');
    });
}

// 格式化評估結果為HTML
function formatEvaluationResult(evaluation, isCorrect) {
    // 創建基本標題
    let formattedResult = `<div class="evaluation-header ${isCorrect ? 'correct' : 'incorrect'}">
        <h3>${isCorrect ? '恭喜，答案正確！' : '答案不正確'}</h3>
    </div>`;
    
    // 如果沒有評估內容，添加默認訊息
    if (!evaluation) {
        return formattedResult + `<p>${isCorrect ? '你成功解決了這個問題！' : '請檢查你的代碼並再試一次。'}</p>`;
    }
    
    // 將評估內容格式化
    const formattedEvaluation = evaluation
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

// 計算傷害值 (含隨機波動)
function calculateDamage(attackPower) {
    // 傷害值在攻擊力的80%~120%之間浮動
    const minDamage = Math.floor(attackPower * 0.8);
    const maxDamage = Math.floor(attackPower * 1.2);
    return Math.floor(Math.random() * (maxDamage - minDamage + 1)) + minDamage;
}

// 玩家攻擊
function playerAttack() {
    try {
        if (battleState.isBattleOver) {
            console.log('戰鬥已結束，無法進行玩家攻擊');
            return;
        }
        
        // 計算傷害 - 使用玩家攻擊力
        const playerAttack = parseInt(levelData.playerAttack) || 10;
        const damage = calculateDamage(playerAttack);
        battleState.monsterHp -= damage;
        console.log(`玩家攻擊！造成 ${damage} 點傷害，怪物剩餘血量: ${battleState.monsterHp}`);
        
        // 顯示攻擊效果
        showAttackEffect('monster', damage);
        
        // 更新UI
        updateMonsterHp();
        updateBattleMessage(`你的答案正確！對怪物造成了 ${damage} 點傷害！`);
        
        // 檢查怪物是否死亡
        if (battleState.monsterHp <= 0) {
            // 檢查是否有下一波
            if (battleState.wave < battleState.maxWaves) {
                nextWave();
            } else {
                // 最後一波結束，勝利
                endBattle(true);
            }
        } else {
            // ===== 關鍵修改：答對後不是輪到怪物，而是重新生成題目 =====
            // 將 battleState.isPlayerTurn = false; 和 setTimeout(monsterAttack, 1500); 移除
            
            // 答對後重新生成題目
            setTimeout(() => {
                regenerateProblem();
            }, 1500); // 延遲1.5秒後重新生成題目
        }
    } catch (error) {
        console.error('玩家攻擊過程中發生錯誤:', error);
        // 嘗試恢復遊戲狀態
        battleState.isPlayerTurn = true;
    }
}

// 新增：重新生成題目的函數
function regenerateProblem() {
    // 顯示載入提示
    const outputDisplay = document.getElementById('output-display');
    if (outputDisplay) {
        outputDisplay.innerHTML = '<div class="loading">正在生成新的挑戰...</div>';
        outputDisplay.classList.remove('error');
    }
    
    updateBattleMessage('怪物防禦了你的攻擊！正在生成新的挑戰...');
    
    // 調用 loadProblem 函數重新生成題目
    loadProblem();
}

// 怪物攻擊
function monsterAttack() {
    try {
        if (battleState.isBattleOver) {
            console.log('戰鬥已結束，無法進行怪物攻擊');
            return;
        }
        
        // 使用正確的怪物攻擊力參數
        const monsterAttack = parseInt(levelData.monsterAttack) || 5;
        const damage = calculateDamage(monsterAttack);
        battleState.playerHp -= damage;
        console.log(`怪物攻擊！造成 ${damage} 點傷害，玩家剩餘血量: ${battleState.playerHp}`);
        
        // 顯示攻擊效果
        showAttackEffect('player', damage);
        
        // 更新UI
        updatePlayerHp();
        updateBattleMessage(`怪物攻擊了你，造成 ${damage} 點傷害！`);
        
        // 檢查玩家是否死亡
        if (battleState.playerHp <= 0) {
            console.log('玩家被擊敗！');
            endBattle(false); // 失敗
        } else {
            // 玩家還活著，換玩家回合
            battleState.isPlayerTurn = true;
        }
    } catch (error) {
        console.error('怪物攻擊過程中發生錯誤:', error);
        // 嘗試恢復遊戲狀態
        battleState.isPlayerTurn = true;
    }
}

// 更新戰鬥消息
function updateBattleMessage(message) {
    // 獲取戰鬥消息元素
    const battleMessageElement = document.getElementById('battle-message') || 
        document.getElementById('battle-log') || 
        document.getElementById('battle-message-content');
    
    // 如果元素存在，則更新內容
    if (battleMessageElement) {
        battleMessageElement.textContent = message;
        // 添加動畫效果 (如果需要)
        battleMessageElement.classList.remove('message-animation');
        void battleMessageElement.offsetWidth; // 強制重繪
        battleMessageElement.classList.add('message-animation');
    } else {
        // 如果找不到消息元素，記錄警告
        console.warn('找不到戰鬥消息顯示元素，無法更新消息:', message);
    }
}

// 重置戰鬥狀態
function resetBattle() {
    // 重置戰鬥狀態
    battleState = {
        playerHp: parseInt(levelData.playerHp) || 100,
        monsterHp: parseInt(levelData.monsterHp) || 100,
        isPlayerTurn: true,
        isBattleOver: false,
        wave: 1,
        maxWaves: parseInt(levelData.waveCount) || 1,
        hasWon: false
    };
    
    console.log('重置戰鬥狀態:', battleState);
    
    // 更新生命值顯示
    updatePlayerHp();
    updateMonsterHp();
    
    // 隱藏結果彈窗 (如果存在)
    const resultModal = document.getElementById('result-modal');
    if (resultModal) {
        resultModal.style.display = 'none';
    }
    
    // 啟用提交按鈕
    const submitButton = document.getElementById('submit-code');
    if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = '提交答案';
    }
    
    // 隱藏重試按鈕
    const retryButton = document.getElementById('retry-button');
    if (retryButton) {
        retryButton.style.display = 'none';
    }
    
    // 更新戰鬥消息
    updateBattleMessage('戰鬥已重置，請編寫程式碼以擊敗怪物！');
    
    // 清空輸出區
    const outputDisplay = document.getElementById('output-display');
    if (outputDisplay) {
        outputDisplay.innerHTML = '';
        outputDisplay.classList.remove('error');
    }
}

// 更新玩家生命值顯示 - 適應當前頁面結構
function updatePlayerHp() {
    console.log('更新玩家HP:', battleState.playerHp);
    
    // 根據 level.php 的結構精確定位元素
    const playerHpElement = document.querySelector('.character-stats .hp-value');
    if (playerHpElement) {
        playerHpElement.textContent = battleState.playerHp;
        console.log('成功更新玩家HP顯示元素');
    } else {
        console.warn('找不到玩家HP顯示元素 (.character-stats .hp-value)');
    }
    
    // 可選：如果有玩家血條，也可以更新
    const playerHpBar = document.querySelector('.character-stats .hp-bar');
    if (playerHpBar) {
        const playerMaxHp = parseInt(levelData.playerHp) || 100;
        const currentHp = Math.max(0, battleState.playerHp);
        const percent = (currentHp / playerMaxHp) * 100;
        
        playerHpBar.style.width = `${percent}%`;
        console.log('成功更新玩家HP條');
    }
}

// 更新怪物生命值顯示 - 適應特定HTML結構
function updateMonsterHp() {
    console.log('更新怪物HP:', battleState.monsterHp);
    
    // 根據 level.php 的結構精確定位元素
    const monsterHpElement = document.querySelector('.monster-hp .current-hp');
    if (monsterHpElement) {
        monsterHpElement.textContent = battleState.monsterHp;
        console.log('成功更新怪物HP顯示元素');
    } else {
        console.warn('找不到怪物HP顯示元素 (.monster-hp .current-hp)');
    }
    
    // 更新怪物血條
    const monsterHpBar = document.querySelector('.monster-hp .hp-bar .hp-fill');
    if (monsterHpBar) {
        const monsterMaxHp = parseInt(levelData.monsterHp) || 100;
        const currentHp = Math.max(0, battleState.monsterHp);
        const percent = (currentHp / monsterMaxHp) * 100;
        
        monsterHpBar.style.width = `${percent}%`;
        console.log('成功更新怪物HP條:', `${percent}%`);
        
        // 根據血量百分比更改顏色
        if (percent <= 20) {
            monsterHpBar.style.backgroundColor = '#ff4444'; // 紅色
        } else if (percent <= 50) {
            monsterHpBar.style.backgroundColor = '#ffaa33'; // 橙色
        }
    } else {
        console.warn('找不到怪物HP條元素 (.monster-hp .hp-bar .hp-fill)');
    }
}

// 進入下一波怪物
function nextWave() {
    battleState.wave++;
    battleState.monsterHp = parseInt(levelData.monsterHp); // 重置怪物生命值
    
    // 更新UI
    updateBattleMessage(`第 ${battleState.wave}/${battleState.maxWaves} 波！新的怪物出現了！`);
    updateMonsterHp();
    
    // 播放下一波動畫
    showNextWaveEffect();
    
    // 重置回合
    battleState.isPlayerTurn = true;
}

// 顯示下一波效果
function showNextWaveEffect() {
    const waveIndicator = document.createElement('div');
    waveIndicator.className = 'wave-indicator';
    waveIndicator.innerHTML = `<span>第 ${battleState.wave} 波</span>`;
    document.getElementById('battle-container').appendChild(waveIndicator);
    
    // 2秒後移除效果
    setTimeout(() => {
        waveIndicator.remove();
    }, 2000);
}

// 修復 showVictoryEffect 函數，增加錯誤處理
function showVictoryEffect() {
    try {
        console.log('顯示勝利效果');
        
        // 尋找適合的容器元素
        let battleContainer = document.getElementById('battle-container');
        
        // 如果找不到特定的容器，尋找替代元素
        if (!battleContainer) {
            const possibleContainers = [
                document.querySelector('.battle-container'),
                document.querySelector('.battle-content'),
                document.querySelector('.battle-scene'),
                document.querySelector('.battle-section'),
                document.body // 最後的備用選項
            ];
            
            // 使用找到的第一個非空元素
            for (const container of possibleContainers) {
                if (container) {
                    battleContainer = container;
                    console.log('使用替代容器:', container.tagName, container.className || container.id);
                    break;
                }
            }
        }
        
        if (!battleContainer) {
            console.warn('找不到可用容器來顯示勝利效果，將直接使用body');
            battleContainer = document.body;
        }
        
        // 創建勝利效果元素
        const victoryEffect = document.createElement('div');
        victoryEffect.className = 'victory-effect';
        victoryEffect.style.position = 'fixed';
        victoryEffect.style.top = '0';
        victoryEffect.style.left = '0';
        victoryEffect.style.width = '100%';
        victoryEffect.style.height = '100%';
        victoryEffect.style.backgroundColor = 'rgba(0, 150, 0, 0.3)';
        victoryEffect.style.display = 'flex';
        victoryEffect.style.justifyContent = 'center';
        victoryEffect.style.alignItems = 'center';
        victoryEffect.style.zIndex = '9999';
        
        const victoryText = document.createElement('span');
        victoryText.textContent = 'Victory!';
        victoryText.style.fontSize = '72px';
        victoryText.style.color = '#ffcc00';
        victoryText.style.textShadow = '2px 2px 4px #000';
        victoryText.style.fontWeight = 'bold';
        
        // 安全地添加元素
        victoryEffect.appendChild(victoryText);
        battleContainer.appendChild(victoryEffect);
        
        // 播放勝利音效（如果有）
        try {
            playSound('victory');
        } catch (soundError) {
            console.log('無法播放勝利音效');
        }
        
        // 3秒後移除效果
        setTimeout(() => {
            try {
                if (victoryEffect.parentNode) {
                    victoryEffect.parentNode.removeChild(victoryEffect);
                }
            } catch (removeError) {
                console.warn('移除勝利效果時出錯', removeError);
            }
        }, 3000);
    } catch (error) {
        console.error('顯示勝利效果時出錯:', error);
        // 即使出錯，也不影響遊戲流程
    }
}

// 類似地修復 showDefeatEffect 函數，增加錯誤處理
function showDefeatEffect() {
    try {
        console.log('顯示失敗效果');
        
        // 尋找適合的容器元素
        let battleContainer = document.getElementById('battle-container');
        
        // 如果找不到特定的容器，尋找替代元素
        if (!battleContainer) {
            const possibleContainers = [
                document.querySelector('.battle-container'),
                document.querySelector('.battle-content'),
                document.querySelector('.battle-scene'),
                document.querySelector('.battle-section'),
                document.body // 最後的備用選項
            ];
            
            // 使用找到的第一個非空元素
            for (const container of possibleContainers) {
                if (container) {
                    battleContainer = container;
                    console.log('使用替代容器:', container.tagName, container.className || container.id);
                    break;
                }
            }
        }
        
        if (!battleContainer) {
            console.warn('找不到可用容器來顯示失敗效果，將直接使用body');
            battleContainer = document.body;
        }
        
        // 創建失敗效果元素
        const defeatEffect = document.createElement('div');
        defeatEffect.className = 'defeat-effect';
        defeatEffect.style.position = 'fixed';
        defeatEffect.style.top = '0';
        defeatEffect.style.left = '0';
        defeatEffect.style.width = '100%';
        defeatEffect.style.height = '100%';
        defeatEffect.style.backgroundColor = 'rgba(150, 0, 0, 0.3)';
        defeatEffect.style.display = 'flex';
        defeatEffect.style.justifyContent = 'center';
        defeatEffect.style.alignItems = 'center';
        defeatEffect.style.zIndex = '9999';
        
        const defeatText = document.createElement('span');
        defeatText.textContent = 'Defeat!';
        defeatText.style.fontSize = '72px';
        defeatText.style.color = '#ff3333';
        defeatText.style.textShadow = '2px 2px 4px #000';
        defeatText.style.fontWeight = 'bold';
        
        // 安全地添加元素
        defeatEffect.appendChild(defeatText);
        battleContainer.appendChild(defeatEffect);
        
        // 播放失敗音效
        try {
            playSound('defeat');
        } catch (soundError) {
            console.log('無法播放失敗音效');
        }
        
        // 3秒後移除效果
        setTimeout(() => {
            try {
                if (defeatEffect.parentNode) {
                    defeatEffect.parentNode.removeChild(defeatEffect);
                }
            } catch (removeError) {
                console.warn('移除失敗效果時出錯', removeError);
            }
        }, 3000);
    } catch (error) {
        console.error('顯示失敗效果時出錯:', error);
        // 即使出錯，也不影響遊戲流程
    }
}

// 修改 endBattle 函數，顯示更美觀的狩獵成功畫面
function endBattle(isVictory) {
    try {
        console.log(`戰鬥結束，結果: ${isVictory ? '勝利' : '失敗'}`);
        
        battleState.isBattleOver = true;
        battleState.hasWon = isVictory;
        
        // 更新戰鬥訊息
        updateBattleMessage(isVictory 
            ? '恭喜！你擊敗了所有怪物，完成了這個關卡！' 
            : '你被怪物擊敗了！');
        
        // 顯示效果
        if (isVictory) {
            // 顯示狩獵成功畫面
            showHuntSuccessScreen();
            
            // 記錄關卡完成並解鎖下一關
            try {
                recordLevelCompletion();
            } catch (recordError) {
                console.warn('記錄關卡完成時出錯:', recordError);
            }
        } else {
            // 顯示失敗效果 (有錯誤處理)
            showDefeatEffect();
        }
        
        // 禁用提交按鈕
        const submitButton = document.getElementById('submit-code');
        if (submitButton) {
            submitButton.disabled = true;
        }
    } catch (error) {
        console.error('結束戰鬥過程中發生錯誤:', error);
    }
}

// 顯示狩獵成功畫面
function showHuntSuccessScreen() {
    try {
        // 創建遮罩背景
        const overlay = document.createElement('div');
        overlay.className = 'hunt-success-overlay';
        
        // 創建成功畫面容器
        const successScreen = document.createElement('div');
        successScreen.className = 'hunt-success-container';
        
        // 獲取怪物名稱（如果有）或使用默認名稱
        const monsterName = levelData.monsterName || '野生怪物';
        
        // 添加標題和內容
        successScreen.innerHTML = `
            <div class="hunt-success-header">
                <div class="hunt-header-decoration">
                    <span class="hunt-decoration-symbol">⚔️</span>
                    <span class="hunt-decoration-line"></span>
                    <span class="hunt-decoration-symbol">🏹</span>
                </div>
                <h2>狩獵成功</h2>
                <div class="hunt-header-decoration">
                    <span class="hunt-decoration-line"></span>
                </div>
            </div>
            <div class="hunt-success-content">
                <div class="hunt-monster-defeated">
                    <div class="monster-trophy">🏆</div>
                    <div class="defeat-text">你成功擊倒了 ${monsterName}！</div>
                </div>
                
                <div class="hunt-rewards-title">戰利品</div>
                <div class="hunt-rewards">
                    <div class="reward-item exp-reward">
                        <span class="reward-icon">✨</span>
                        <span class="reward-label">經驗值</span>
                        <span class="reward-value">+${levelData.expReward || 50}</span>
                    </div>
                    <div class="reward-item monster-part">
                        <span class="reward-icon">🦴</span>
                        <span class="reward-label">怪物碎片</span>
                        <span class="reward-value">+${levelData.isBoss ? 3 : 1}</span>
                    </div>
                </div>
                
                <div id="level-up-section"></div>
                
                <div class="hunter-notes">
                    <div class="note-title">探險筆記</div>
                    <div class="note-content">
                        <p>完成了程式設計挑戰，將代碼的力量應用到狩獵中！</p>
                        <p>狩獵等級越高，能夠挑戰更強大的怪物。</p>
                    </div>
                </div>
            </div>
            <div class="hunt-success-buttons">
                <button class="hunt-success-btn return-btn">
                    <span class="btn-icon">🗺️</span> 返回狩獵地圖
                </button>
                <button class="hunt-success-btn retry-btn">
                    <span class="btn-icon">🔄</span> 再次狩獵
                </button>
            </div>
        `;
        
        // 添加到頁面
        overlay.appendChild(successScreen);
        document.body.appendChild(overlay);
        
        // 淡入動畫效果
        setTimeout(() => {
            overlay.style.opacity = '1';
            successScreen.style.transform = 'translateY(0)';
        }, 10);
        
        // 添加按鈕事件
        const returnBtn = successScreen.querySelector('.return-btn');
        if (returnBtn) {
            returnBtn.addEventListener('click', () => {
                // 返回章節頁面
                window.location.href = `chapter.php?id=${levelData.chapterId || 1}`;
            });
        }
        
        const retryBtn = successScreen.querySelector('.retry-btn');
        if (retryBtn) {
            retryBtn.addEventListener('click', () => {
                // 移除成功畫面
                overlay.style.opacity = '0';
                successScreen.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    if (overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                    // 重新載入頁面以重新開始
                    window.location.reload();
                }, 300);
            });
        }
        
        // 播放勝利音效（如果有）
        try {
            playSound('victory');
        } catch (soundError) {
            console.log('無法播放勝利音效');
        }
        
    } catch (error) {
        console.error('顯示狩獵成功畫面時出錯:', error);
        // 如果出錯，回退到簡單的勝利效果
        showVictoryEffect();
    }
}

// 記錄關卡完成並處理經驗值和升級
function recordLevelCompletion() {
    fetch('api/complete-level.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            levelId: levelData.levelId,
            chapterId: levelData.chapterId || 1
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('關卡完成記錄結果:', data);
        
        if (data.success) {
            // 如果解鎖了新關卡，顯示解鎖訊息
            if (data.unlockedLevels && data.unlockedLevels.length > 0) {
                showUnlockNotification(data.unlockedLevels);
            }
            
            // 如果完成了整個章節，顯示章節完成訊息
            if (data.completedChapter) {
                showChapterCompleteNotification(data.completedChapter);
            }
            
            // 顯示獲得經驗值
            if (data.expReward) {
                showExpReward(data.expReward);
            }
            
            // 如果升級了，顯示升級訊息
            if (data.levelUp) {
                showLevelUpNotification(data.newLevel, data.newLevelTitle);
                
                // 在狩獵成功畫面中添加升級信息
                const levelUpSection = document.getElementById('level-up-section');
                if (levelUpSection) {
                    levelUpSection.innerHTML = `
                        <div class="level-up-info">
                            <div class="level-up-title">等級提升！</div>
                            <div class="level-up-details">
                                <div class="new-level">
                                    <span class="level-label">新等級</span>
                                    <span class="level-value">${data.newLevel}</span>
                                </div>
                                <div class="level-title">
                                    <span class="title-label">稱號</span>
                                    <span class="title-value">${data.newLevelTitle || `等級 ${data.newLevel}`}</span>
                                </div>
                            </div>
                        </div>
                        <div class="exp-progress">
                            <div class="exp-bar-container">
                                <div class="exp-label">經驗值</div>
                                <div class="exp-bar-wrapper">
                                    <div class="exp-bar" style="width: ${(data.currentExp/data.expToNextLevel*100).toFixed(1)}%"></div>
                                </div>
                                <div class="exp-values">${data.currentExp} / ${data.expToNextLevel}</div>
                            </div>
                        </div>
                    `;
                }
            } else {
                // 沒有升級，但仍顯示經驗進度
                const levelUpSection = document.getElementById('level-up-section');
                if (levelUpSection) {
                    const expPercentage = (data.currentExp/data.expToNextLevel*100).toFixed(1);
                    levelUpSection.innerHTML = `
                        <div class="exp-progress">
                            <div class="exp-bar-container">
                                <div class="exp-label">經驗值進度</div>
                                <div class="exp-bar-wrapper">
                                    <div class="exp-bar" style="width: ${expPercentage}%"></div>
                                </div>
                                <div class="exp-values">${data.currentExp} / ${data.expToNextLevel} (${expPercentage}%)</div>
                            </div>
                        </div>
                    `;
                }
            }
        }
    })
    .catch(error => {
        console.error('記錄關卡完成時發生錯誤:', error);
    });
}

// 顯示升級通知
function showLevelUpNotification(newLevel, levelTitle) {
    try {
        // 建立升級通知元素
        const levelUpEffect = document.createElement('div');
        levelUpEffect.className = 'level-up-effect';
        levelUpEffect.style.position = 'fixed';
        levelUpEffect.style.top = '0';
        levelUpEffect.style.left = '0';
        levelUpEffect.style.width = '100%';
        levelUpEffect.style.height = '100%';
        levelUpEffect.style.backgroundColor = 'rgba(255, 215, 0, 0.3)';
        levelUpEffect.style.display = 'flex';
        levelUpEffect.style.justifyContent = 'center';
        levelUpEffect.style.alignItems = 'center';
        levelUpEffect.style.zIndex = '9999';
        levelUpEffect.style.animation = 'fadeIn 0.5s ease-out';
        
        const levelUpContent = document.createElement('div');
        levelUpContent.style.textAlign = 'center';
        levelUpContent.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        levelUpContent.style.padding = '30px';
        levelUpContent.style.borderRadius = '15px';
        levelUpContent.style.animation = 'scaleIn 0.5s ease-out';
        
        const levelUpTitle = document.createElement('div');
        levelUpTitle.textContent = '升級！';
        levelUpTitle.style.fontSize = '48px';
        levelUpTitle.style.color = '#ffd700';
        levelUpTitle.style.fontWeight = 'bold';
        levelUpTitle.style.marginBottom = '10px';
        levelUpTitle.style.textShadow = '2px 2px 4px rgba(0,0,0,0.5)';
        
        const levelUpInfo = document.createElement('div');
        levelUpInfo.textContent = `你現在是等級 ${newLevel}：${levelTitle || ''}`;
        levelUpInfo.style.color = 'white';
        levelUpInfo.style.fontSize = '24px';
        
        const levelUpBenefits = document.createElement('div');
        levelUpBenefits.innerHTML = '攻擊力和生命值提高了！';
        levelUpBenefits.style.color = '#77dd77';
        levelUpBenefits.style.fontSize = '18px';
        levelUpBenefits.style.marginTop = '15px';
        
        // 組合元素
        levelUpContent.appendChild(levelUpTitle);
        levelUpContent.appendChild(levelUpInfo);
        levelUpContent.appendChild(levelUpBenefits);
        levelUpEffect.appendChild(levelUpContent);
        document.body.appendChild(levelUpEffect);
        
        // 播放升級音效
        try {
            playSound('levelUp');
        } catch (e) {
            console.log('播放升級音效失敗');
        }
        
        // 4秒後移除
        setTimeout(() => {
            levelUpEffect.style.animation = 'fadeOut 0.5s ease-out';
            setTimeout(() => levelUpEffect.remove(), 500);
        }, 4000);
    } catch (error) {
        console.error('顯示升級通知時發生錯誤:', error);
    }
}

// 添加 CSS 樣式
function addCssStyles() {
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        @keyframes fadeInUp {
            from { 
                opacity: 0;
                transform: translate(-50%, 20px);
            }
            to { 
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }
        
        @keyframes scaleIn {
            from { 
                transform: scale(0.8);
                opacity: 0;
            }
            to { 
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .exp-notice {
            animation-fill-mode: both;
        }
        
        /* 新增狩獵成功畫面的樣式 */
        .hunt-success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.5s ease;
            z-index: 10000;
        }
        
        .hunt-success-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            transform: translateY(20px);
            transition: transform 0.5s ease;
        }
        
        .hunt-success-header h2 {
            margin: 0;
            font-size: 28px;
            color: #4caf50;
        }
        
        .hunt-success-animation {
            font-size: 48px;
            margin: 10px 0;
        }
        
        .hunt-success-content {
            margin: 20px 0;
        }
        
        .reward-item {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 10px 0;
        }
        
        .reward-icon {
            font-size: 24px;
            margin-right: 8px;
        }
        
        .hunt-success-buttons {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        
        .hunt-success-btn {
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .hunt-success-btn:hover {
            background: #45a049;
        }
        
        /* 新增狩獵成功畫面的裝飾樣式 */
        .hunt-header-decoration {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .hunt-decoration-symbol {
            font-size: 28px;
            color: #4caf50;
            margin: 0 5px;
        }
        
        .hunt-decoration-line {
            flex-grow: 1;
            height: 2px;
            background: linear-gradient(to right, transparent, #4caf50, transparent);
            margin: 0 5px;
        }
        
        .hunt-monster-defeated {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-bottom: 20px;
        }
        
        .monster-trophy {
            font-size: 48px;
            color: #ffd700;
            margin-bottom: 10px;
        }
        
        .defeat-text {
            font-size: 20px;
            color: #333;
            font-weight: bold;
        }
        
        .hunt-rewards-title {
            font-size: 22px;
            color: #4caf50;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .hunt-rewards {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .reward-item {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 5px 0;
            width: 100%;
        }
        
        .reward-label {
            font-size: 18px;
            color: #333;
            margin-right: 5px;
        }
        
        .reward-value {
            font-size: 18px;
            color: #4caf50;
            font-weight: bold;
        }
        
        .hunter-notes {
            margin-top: 15px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: left;
        }
        
        .note-title {
            font-size: 18px;
            color: #333;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .note-content {
            font-size: 16px;
            color: #666;
            line-height: 1.4;
        }
    `;
    document.head.appendChild(styleElement);
}

// 將Markdown轉換為HTML
function renderMarkdown(markdown) {
    if (!markdown) return '';
    
    let html = markdown;
    
    // 處理標題
    html = html.replace(/^### (.*$)/gm, '<h3>$1</h3>');
    html = html.replace(/^## (.*$)/gm, '<h2>$1</h2>');
    html = html.replace(/^# (.*$)/gm, '<h1>$1</h1>');
    
    // 處理程式碼區塊
    html = html.replace(/```python\n([\s\S]*?)```/g, '<pre class="code-block"><code class="language-python">$1</code></pre>');
    html = html.replace(/```([\s\S]*?)```/g, '<pre class="code-block"><code>$1</code></pre>');
    
    // 處理行內程式碼
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
    
    // 處理粗體和斜體
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
    
    // 處理列表
    html = html.replace(/^\s*[\-\*] (.*$)/gm, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>\n)+/g, '<ul>$&</ul>');
    
    // 處理段落 (非標題、列表或程式碼區塊的行)
    html = html.replace(/^(?!<h|<ul|<pre|<li)(.+)$/gm, '<p>$1</p>');
    
    // 處理換行
    html = html.replace(/\n\n/g, '<br><br>');
    
    return html;
}

// 在頁面載入完成後添加 CSS 樣式
document.addEventListener('DOMContentLoaded', function() {
    addCssStyles();
    // ...existing code...
});

// 顯示攻擊效果
function showAttackEffect(targetId, damage) {
    try {
        console.log(`顯示攻擊效果：目標=${targetId}，傷害=${damage}`);
        
        // 尋找適合的容器元素
        let battleContainer = document.querySelector('.battle-scene') || 
                             document.querySelector('.battle-container') || 
                             document.getElementById('battle-container');
                             
        if (!battleContainer) {
            console.warn('找不到戰鬥場景容器，直接使用body');
            battleContainer = document.body;
        }
        
        // 決定效果的位置和方向
        const isMonsterTarget = targetId === 'monster';
        const targetElement = isMonsterTarget ? 
                            document.querySelector('.monster-sprite') || document.querySelector('.monster-unit') : 
                            document.querySelector('.character-sprite') || document.querySelector('.character-unit');
        
        // 如果找不到目標元素，就在容器中心顯示效果
        let targetRect = battleContainer.getBoundingClientRect();
        if (targetElement) {
            targetRect = targetElement.getBoundingClientRect();
        }
        
        // 創建攻擊特效元素
        const effectElement = document.createElement('div');
        effectElement.className = 'attack-effect';
        effectElement.style.position = 'absolute';
        effectElement.style.zIndex = '100';
        effectElement.style.pointerEvents = 'none';
        
        // 設置特效初始位置
        const battleRect = battleContainer.getBoundingClientRect();
        effectElement.style.left = `${targetRect.left - battleRect.left + targetRect.width/2 - 25}px`;
        effectElement.style.top = `${targetRect.top - battleRect.top + targetRect.height/2 - 25}px`;
        
        // 設置特效樣式
        effectElement.style.width = '50px';
        effectElement.style.height = '50px';
        effectElement.style.backgroundSize = 'contain';
        effectElement.style.backgroundPosition = 'center';
        effectElement.style.backgroundRepeat = 'no-repeat';
        
        // 根據目標選擇不同的效果
        if (isMonsterTarget) {
            effectElement.style.backgroundImage = 'url("assets/images/sword-effect.png")';
            effectElement.style.backgroundImage = 'none'; // 備用：如果圖片不存在
            effectElement.innerHTML = '⚔️'; // 使用表情符號作為備用
            effectElement.style.fontSize = '40px';
            effectElement.style.display = 'flex';
            effectElement.style.justifyContent = 'center';
            effectElement.style.alignItems = 'center';
        } else {
            effectElement.style.backgroundImage = 'url("assets/images/claw-effect.png")';
            effectElement.style.backgroundImage = 'none'; // 備用：如果圖片不存在
            effectElement.innerHTML = '👹'; // 使用表情符號作為備用
            effectElement.style.fontSize = '40px';
            effectElement.style.display = 'flex';
            effectElement.style.justifyContent = 'center';
            effectElement.style.alignItems = 'center';
        }
        
        // 添加到戰鬥容器
        battleContainer.appendChild(effectElement);
        
        // 創建傷害數字元素
        const damageElement = document.createElement('div');
        damageElement.className = 'damage-number';
        damageElement.textContent = `-${damage}`;
        damageElement.style.position = 'absolute';
        damageElement.style.zIndex = '101';
        damageElement.style.color = 'red';
        damageElement.style.fontWeight = 'bold';
        damageElement.style.fontSize = '24px';
        damageElement.style.textShadow = '1px 1px 2px black';
        damageElement.style.pointerEvents = 'none';
        
        // 設置傷害數字的初始位置（稍微偏移，以免與效果重疊）
        damageElement.style.left = `${targetRect.left - battleRect.left + targetRect.width/2}px`;
        damageElement.style.top = `${targetRect.top - battleRect.top + targetRect.height/3}px`;
        
        // 添加到戰鬥容器
        battleContainer.appendChild(damageElement);
        
        // 創建動畫效果
        const attackAnimation = effectElement.animate([
            { transform: 'scale(0.5) rotate(-20deg)', opacity: 0 },
            { transform: 'scale(1.2) rotate(0deg)', opacity: 1 },
            { transform: 'scale(1) rotate(10deg)', opacity: 0 }
        ], {
            duration: 600,
            easing: 'ease-out'
        });
        
        const damageAnimation = damageElement.animate([
            { transform: 'translateY(0)', opacity: 1 },
            { transform: 'translateY(-50px)', opacity: 0 }
        ], {
            duration: 1000,
            easing: 'ease-out'
        });
        
        // 動畫完成後移除元素
        attackAnimation.onfinish = () => {
            if (effectElement.parentNode) {
                effectElement.parentNode.removeChild(effectElement);
            }
        };
        
        damageAnimation.onfinish = () => {
            if (damageElement.parentNode) {
                damageElement.parentNode.removeChild(damageElement);
            }
        };
        
        // 播放攻擊音效 (如果存在)
        try {
            const audioType = isMonsterTarget ? 'playerAttack' : 'monsterAttack';
            playSound(audioType);
        } catch (e) {
            console.log('播放攻擊音效失敗');
        }
        
    } catch (error) {
        console.error('顯示攻擊效果時出錯:', error);
        // 錯誤處理：即使特效失敗，戰鬥邏輯依然繼續
    }
}

// 播放音效 (如果不存在則靜默失敗)
function playSound(soundType) {
    // 這是一個簡單的音效播放函數，可以根據需要實現
    console.log(`播放音效：${soundType}`);
    
    // 檢查是否有音效系統
    if (window.Audio) {
        try {
            let soundFile;
            switch (soundType) {
                case 'playerAttack':
                    soundFile = 'assets/sounds/player-attack.mp3';
                    break;
                case 'monsterAttack':
                    soundFile = 'assets/sounds/monster-attack.mp3';
                    break;
                case 'victory':
                    soundFile = 'assets/sounds/victory.mp3';
                    break;
                case 'defeat':
                    soundFile = 'assets/sounds/defeat.mp3';
                    break;
                case 'levelUp':
                    soundFile = 'assets/sounds/level-up.mp3';
                    break;
                default:
                    soundFile = null;
            }
            
            if (soundFile) {
                const audio = new Audio(soundFile);
                audio.volume = 0.5; // 設置音量
                audio.play().catch(e => {
                    console.log('播放音效失敗：可能需要用戶交互才能播放音效');
                });
            }
        } catch (e) {
            console.log('音效系統錯誤:', e);
        }
    }
}

// 添加 CSS 樣式
function addAttackEffectStyles() {
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        .attack-effect {
            position: absolute;
            z-index: 100;
        }
        
        .damage-number {
            position: absolute;
            z-index: 101;
            color: red;
            font-weight: bold;
            font-size: 24px;
            text-shadow: 1px 1px 2px black;
        }
        
        @keyframes attackFadeIn {
            0% { opacity: 0; transform: scale(0.5) rotate(-20deg); }
            50% { opacity: 1; transform: scale(1.2) rotate(0deg); }
            100% { opacity: 0; transform: scale(1) rotate(10deg); }
        }
        
        @keyframes damageFloat {
            0% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-50px); }
        }
    `;
    document.head.appendChild(styleElement);
}

// 在頁面加載完成後添加樣式
document.addEventListener('DOMContentLoaded', function() {
    // 添加攻擊效果的樣式
    addAttackEffectStyles();
    
    // ...existing code...
});