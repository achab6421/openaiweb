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
        const playerAttackPower = parseInt(levelData.playerAttackPower) || 10;
        const damage = calculateDamage(playerAttackPower);
        battleState.monsterHp -= damage;
        console.log(`玩家攻擊！造成 ${damage} 點傷害，怪物剩餘血量: ${battleState.monsterHp}`);
        
        // 顯示攻擊效果
        showAttackEffect('monster', damage);
        
        // 更新UI
        try {
            updateBattleMessage(`你的答案正確！對怪物造成了 ${damage} 點傷害！`);
            updateMonsterHp();
        } catch (uiError) {
            console.warn('更新UI時發生錯誤:', uiError);
        }
        
        // 檢查怪物是否死亡
        if (battleState.monsterHp <= 0) {
            console.log('怪物被擊敗！');
            // 檢查是否有下一波
            if (battleState.wave < battleState.maxWaves) {
                nextWave();
            } else {
                // 最後一波結束，勝利
                endBattle(true);
            }
        } else {
            // 怪物還活著，換怪物回合
            battleState.isPlayerTurn = false;
            setTimeout(monsterAttack, 1500); // 延遲1.5秒後怪物攻擊
        }
    } catch (error) {
        console.error('玩家攻擊過程中發生錯誤:', error);
        // 嘗試恢復遊戲狀態
        battleState.isPlayerTurn = true;
    }
}

// 怪物攻擊
function monsterAttack() {
    try {
        if (battleState.isBattleOver) {
            console.log('戰鬥已結束，無法進行怪物攻擊');
            return;
        }
        
        // 計算傷害 - 使用怪物攻擊力
        const monsterAttackPower = parseInt(levelData.monsterAttackPower) || 5;
        const damage = calculateDamage(monsterAttackPower);
        battleState.playerHp -= damage;
        console.log(`怪物攻擊！造成 ${damage} 點傷害，玩家剩餘血量: ${battleState.playerHp}`);
        
        // 顯示攻擊效果
        showAttackEffect('player', damage);
        
        // 更新UI
        try {
            updateBattleMessage(`怪物攻擊了你，造成 ${damage} 點傷害！`);
            updatePlayerHp();
        } catch (uiError) {
            console.warn('更新UI時發生錯誤:', uiError);
        }
        
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

// 更新玩家生命值顯示
function updatePlayerHp() {
    const playerHpElement = document.getElementById('player-hp') || 
        document.getElementById('player-hp-text');
    
    if (!playerHpElement) {
        console.warn('找不到玩家生命值顯示元素');
        return;
    }
    
    const playerMaxHp = parseInt(levelData.playerHp) || 100;
    const currentHp = Math.max(0, battleState.playerHp);
    
    playerHpElement.textContent = `${currentHp}/${playerMaxHp}`;
    
    // 更新生命值百分比
    const percent = (currentHp / playerMaxHp) * 100;
    const playerHpBar = document.getElementById('player-hp-bar');
    if (playerHpBar) {
        playerHpBar.style.width = `${percent}%`;
        
        // 根據血量百分比更改顏色
        if (percent <= 20) {
            playerHpBar.style.backgroundColor = '#ff4444'; // 紅色
        } else if (percent <= 50) {
            playerHpBar.style.backgroundColor = '#ffaa33'; // 橙色
        } else {
            playerHpBar.style.backgroundColor = '#44cc44'; // 綠色
        }
    }
}

// 添加 monsterHp 更新函數的完整定義
function updateMonsterHp() {
    const monsterHpElement = document.getElementById('monster-hp') || 
        document.getElementById('monster-hp-text');
    
    if (!monsterHpElement) {
        console.warn('找不到怪物生命值顯示元素');
        return;
    }
    
    const monsterMaxHp = parseInt(levelData.monsterHp) || 100;
    const currentHp = Math.max(0, battleState.monsterHp);
    
    monsterHpElement.textContent = `${currentHp}/${monsterMaxHp}`;
    
    // 更新生命值百分比
    const percent = (currentHp / monsterMaxHp) * 100;
    const monsterHpBar = document.getElementById('monster-hp-bar');
    if (monsterHpBar) {
        monsterHpBar.style.width = `${percent}%`;
        
        // 根據血量百分比更改顏色
        if (percent <= 20) {
            monsterHpBar.style.backgroundColor = '#ff4444'; // 紅色
        } else if (percent <= 50) {
            monsterHpBar.style.backgroundColor = '#ffaa33'; // 橙色
        } else {
            monsterHpBar.style.backgroundColor = '#cc4444'; // 暗紅色
        }
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

// 結束戰鬥
function endBattle(isVictory) {
    battleState.isBattleOver = true;
    battleState.hasWon = isVictory;
    
    if (isVictory) {
        updateBattleMessage('恭喜！你擊敗了所有怪物，完成了這個關卡！');
        
        // 顯示勝利效果
        showVictoryEffect();
        
        // 記錄關卡完成並解鎖下一關
        recordLevelCompletion();
        
        // 顯示結果彈窗
        showResultModal(true);
    } else {
        updateBattleMessage('你被怪物擊敗了！');
        
        // 顯示失敗效果
        showDefeatEffect();
        
        // 顯示結果彈窗
        showResultModal(false);
    }
}

// 顯示勝利效果
function showVictoryEffect() {
    const victoryEffect = document.createElement('div');
    victoryEffect.className = 'victory-effect';
    victoryEffect.innerHTML = '<span>Victory!</span>';
    document.getElementById('battle-container').appendChild(victoryEffect);
    
    // 播放勝利音效
    playSound('victory');
    
    // 3秒後移除效果
    setTimeout(() => {
        victoryEffect.remove();
    }, 3000);
}

// 顯示失敗效果
function showDefeatEffect() {
    const defeatEffect = document.createElement('div');
    defeatEffect.className = 'defeat-effect';
    defeatEffect.innerHTML = '<span>Defeat!</span>';
    document.getElementById('battle-container').appendChild(defeatEffect);
    
    // 播放失敗音效
    playSound('defeat');
    
    // 3秒後移除效果
    setTimeout(() => {
        defeatEffect.remove();
    }, 3000);
}

// 播放音效
function playSound(type) {
    // 如果存在音效系統則使用，否則只記錄到控制台
    console.log(`播放音效: ${type}`);
    // 未來可以實現實際的音效播放功能
}

// 修改 showAttackEffect 函數來適應不同的 HTML 結構
function showAttackEffect(targetId, damage) {
    console.log(`顯示攻擊效果，目標: ${targetId}，傷害: ${damage}`);
    
    // 尋找適合的容器元素
    let battleContainer = document.getElementById('battle-container');
    
    // 如果找不到 battle-container，尋找其他可能的元素
    if (!battleContainer) {
        const possibleContainers = [
            document.querySelector('.battle-area'),
            document.querySelector('.battle-container'),
            document.querySelector('.game-container'),
            document.querySelector('.container'),
            document.getElementById('main-content'),
            document.body // 最後的備用選項
        ];
        
        // 使用找到的第一個非空元素
        for (const container of possibleContainers) {
            if (container) {
                battleContainer = container;
                console.log('使用備用容器:', container.tagName, container.className || container.id);
                break;
            }
        }
    }
    
    // 創建效果容器
    let effectsContainer = document.getElementById('effects-container');
    if (!effectsContainer) {
        effectsContainer = document.createElement('div');
        effectsContainer.id = 'effects-container';
        effectsContainer.style.position = 'fixed'; // 改為 fixed 以確保顯示
        effectsContainer.style.top = '0';
        effectsContainer.style.left = '0';
        effectsContainer.style.width = '100%';
        effectsContainer.style.height = '100%';
        effectsContainer.style.pointerEvents = 'none'; // 讓點擊事件穿透
        effectsContainer.style.zIndex = '9999'; // 確保在頂層
        
        // 添加到找到的容器或直接添加到 body
        if (battleContainer) {
            battleContainer.appendChild(effectsContainer);
        } else {
            console.warn('找不到合適的容器，將效果直接添加到 body');
            document.body.appendChild(effectsContainer);
        }
    }
    
    // 創建攻擊效果元素
    const attackEffect = document.createElement('div');
    attackEffect.className = 'attack-effect';
    attackEffect.style.position = 'absolute';
    attackEffect.style.width = '50px';
    attackEffect.style.height = '50px';
    attackEffect.style.backgroundColor = targetId === 'monster' ? 'red' : 'blue';
    attackEffect.style.borderRadius = '50%';
    attackEffect.style.opacity = '0.7';
    attackEffect.style.left = `${Math.random() * 80 + 10}%`;
    attackEffect.style.top = `${Math.random() * 80 + 10}%`;
    effectsContainer.appendChild(attackEffect);
    
    // 創建傷害文字
    const damageText = document.createElement('div');
    damageText.className = 'damage-text';
    damageText.textContent = `-${damage}`;
    damageText.style.position = 'absolute';
    damageText.style.color = targetId === 'monster' ? 'red' : 'orange';
    damageText.style.fontWeight = 'bold';
    damageText.style.fontSize = '24px';
    damageText.style.textShadow = '1px 1px 2px black';
    damageText.style.left = `${Math.random() * 60 + 20}%`;
    damageText.style.top = `${Math.random() * 60 + 20}%`;
    effectsContainer.appendChild(damageText);
    
    // 添加動畫效果
    attackEffect.animate([
        { transform: 'scale(0.5)', opacity: 0.8 },
        { transform: 'scale(1.2)', opacity: 1 },
        { transform: 'scale(1)', opacity: 0 }
    ], {
        duration: 800,
        easing: 'ease-out'
    });
    
    damageText.animate([
        { transform: 'translateY(0)', opacity: 1 },
        { transform: 'translateY(-50px)', opacity: 0 }
    ], {
        duration: 1000,
        easing: 'ease-out'
    });
    
    // 動畫結束後移除元素
    setTimeout(() => {
        try {
            if (attackEffect.parentNode) attackEffect.parentNode.removeChild(attackEffect);
            if (damageText.parentNode) damageText.parentNode.removeChild(damageText);
        } catch (e) {
            console.warn('移除效果元素時發生錯誤:', e);
        }
    }, 1000);
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