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
    const outputDisplay = document.getElementById('output-display');
    const code = editor.getValue();
    
    if (!code.trim()) {
        outputDisplay.innerHTML = '請先編寫程式碼';
        outputDisplay.classList.add('error');
        return;
    }
    
    // 顯示載入中
    outputDisplay.innerHTML = '執行中...';
    outputDisplay.classList.remove('error');
    
    // 發送代碼到後端進行測試
    fetch('api/test-code.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            code: code
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 顯示執行結果
            outputDisplay.innerHTML = data.output || '程式執行完成，沒有輸出結果。';
            if (data.isError) {
                outputDisplay.classList.add('error');
                updateBattleMessage('程式執行失敗，請修正錯誤後再試。');
            } else {
                outputDisplay.classList.remove('error');
                updateBattleMessage('程式執行成功！');
            }
        } else {
            outputDisplay.innerHTML = `執行失敗: ${data.message}`;
            outputDisplay.classList.add('error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        outputDisplay.innerHTML = '執行期間發生錯誤，請稍後再試。';
        outputDisplay.classList.add('error');
    });
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
    
    // 發送代碼到後端進行驗證
    fetch('api/validate-solution.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            code: code,
            levelId: levelData.levelId,
            problem: currentProblem,
            threadId: currentThreadId
        })
    })
    .then(response => response.json())
    .then(data => {
        // 恢復提交按鈕
        submitButton.disabled = false;
        submitButton.textContent = '提交答案';
        
        if (data.success) {
            // 處理驗證結果
            const result = data.result;
            
            // 顯示回饋
            outputDisplay.innerHTML = `
                <strong>${result.isCorrect ? '恭喜，答案正確！' : '答案不正確'}</strong>
                <p>${result.feedback}</p>
                <p>${result.explanation}</p>
            `;
            
            // 根據結果執行戰鬥邏輯
            if (result.isCorrect) {
                outputDisplay.classList.remove('error');
                playerAttack();
            } else {
                outputDisplay.classList.add('error');
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

// 玩家攻擊
function playerAttack() {
    if (battleState.isBattleOver) return;
    
    // 計算傷害
    const damage = Math.floor(levelData.playerAttack * (0.8 + Math.random() * 0.4));
    
    // 怪物受到傷害
    battleState.monsterHp -= damage;
    
    // 確保生命值不會小於0
    if (battleState.monsterHp < 0) battleState.monsterHp = 0;
    
    // 更新怪物生命條
    updateMonsterHp();
    
    // 顯示攻擊效果
    showAttackEffect('monster1', damage);
    
    // 顯示戰鬥消息
    updateBattleMessage(`您的代碼擊中怪物，造成 ${damage} 點傷害！`);
    
    // 檢查戰鬥是否結束
    setTimeout(() => {
        if (battleState.monsterHp <= 0) {
            // 如果還有下一波
            if (battleState.wave < battleState.maxWaves) {
                battleState.wave++;
                battleState.monsterHp = levelData.monsterHp;
                updateMonsterHp();
                updateBattleMessage(`第 ${battleState.wave}/${battleState.maxWaves} 波怪物出現了！`);
            } else {
                // 戰鬥勝利
                battleState.isBattleOver = true;
                battleState.hasWon = true;
                updateBattleMessage('戰鬥勝利！您成功完成了這個關卡！');
                showResultModal(true);
            }
        } else {
            // 輪到怪物攻擊
            setTimeout(monsterAttack, 1000);
        }
    }, 1000);
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
    html = html.replace(/