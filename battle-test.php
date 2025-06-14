<?php
// 模擬會話狀態
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 'test_user';
$_SESSION['username'] = '測試玩家';
$_SESSION['level'] = 5;
?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>戰鬥系統測試</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .battle-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .battle-scene {
            display: flex;
            flex-direction: column;
            min-height: 400px;
            position: relative;
        }
        .monsters-area {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }
        .monster-unit {
            text-align: center;
            width: 200px;
            padding: 10px;
            margin: 0 20px;
        }
        .monster-name {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .monster-sprite {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
            background-color: #f5f5f5;
            border-radius: 5px;
            overflow: hidden;
        }
        .monster-image {
            max-width: 100%;
            max-height: 100%;
        }
        .monster-hp {
            margin-top: 10px;
        }
        .hp-text {
            margin-bottom: 5px;
        }
        .hp-bar {
            height: 15px;
            background-color: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .hp-fill {
            height: 100%;
            background-color: #cc4444;
            width: 100%;
            transition: width 0.3s ease;
        }
        .characters-area {
            display: flex;
            justify-content: center;
            padding: 20px 0;
            border-top: 1px solid #eee;
        }
        .character-unit {
            display: flex;
            align-items: center;
            width: 300px;
        }
        .character-sprite {
            width: 100px;
            height: 100px;
            background-color: #f5f5f5;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
        }
        .character-image {
            max-width: 100%;
            max-height: 100%;
        }
        .character-info {
            flex: 1;
        }
        .character-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .character-stats {
            font-size: 14px;
        }
        .stat {
            margin: 5px 0;
        }
        .battle-message {
            background-color: #333;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
            margin-top: 20px;
        }
        .button-area {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .test-panel {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .test-controls {
            margin-bottom: 20px;
        }
        .test-controls h2 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .test-button {
            background-color: #2196F3;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .test-button.danger {
            background-color: #F44336;
        }
        .debug-log {
            max-height: 300px;
            overflow-y: auto;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .debug-entry {
            margin-bottom: 5px;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 5px;
        }
        .debug-time {
            color: #666;
            margin-right: 10px;
        }
        .debug-info { color: #2196F3; }
        .debug-warn { color: #FF9800; }
        .debug-error { color: #F44336; }
    </style>
</head>
<body>
    <div class="container">
        <h1>戰鬥系統測試頁面</h1>
        
        <div class="battle-container">
            <div class="battle-scene">
                <div class="monsters-area">
                    <div class="monster-unit" id="monster1">
                        <div class="monster-name">測試怪物</div>
                        <div class="monster-sprite">
                            <img src="assets/images/monsters/default-monster.png" alt="怪物" class="monster-image" onerror="this.src='https://via.placeholder.com/150?text=Monster'">
                            <div class="monster-effects"></div>
                        </div>
                        <div class="monster-hp">
                            <div class="hp-text">HP <span class="current-hp">100</span>/100</div>
                            <div class="hp-bar">
                                <div class="hp-fill" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="characters-area">
                    <div class="character-unit">
                        <div class="character-sprite">
                            <img src="assets/images/characters/character-1.png" alt="角色" class="character-image" onerror="this.src='https://via.placeholder.com/100?text=Player'">
                        </div>
                        <div class="character-info">
                            <div class="character-name">測試玩家</div>
                            <div class="character-stats">
                                <div class="stat">HP <span class="hp-value">100</span></div>
                                <div class="stat">LV <span class="level-value">5</span></div>
                                <div class="stat">ATK <span class="atk-value">20</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="battle-message">
                <div class="message-content" id="battle-message-content">
                    戰鬥測試頁面已載入，請使用下方控制項測試戰鬥系統。
                </div>
            </div>
            
            <div class="button-area">
                <button id="submit-code">提交答案</button>
                <button id="retry-button" style="display:none;">重試</button>
            </div>
        </div>
        
        <div class="test-panel">
            <div class="test-controls">
                <h2>測試控制項</h2>
                <button class="test-button" id="test-correct">測試答案正確</button>
                <button class="test-button danger" id="test-incorrect">測試答案錯誤</button>
                <button class="test-button" id="test-victory">測試勝利效果</button>
                <button class="test-button danger" id="test-defeat">測試失敗效果</button>
                <button class="test-button" id="test-next-wave">測試下一波</button>
                <button class="test-button" id="test-reset">重置測試</button>
            </div>
            
            <h2>測試日誌</h2>
            <div class="debug-log" id="debug-log"></div>
            
            <h2>戰鬥狀態</h2>
            <pre id="battle-state-display"></pre>
        </div>
    </div>
    
    <!-- 測試用的簡化戰鬥系統 -->
    <script>
        // 關卡數據模擬
        const levelData = {
            levelId: 1,
            chapterId: 1,
            teachingPoint: "測試教學點",
            monsterHp: 100,
            monsterAttack: 15,
            playerHp: 100,
            playerAttack: 20,
            waveCount: 2,
            expReward: 50,
            isBoss: false
        };
        
        // 戰鬥狀態
        let battleState = {
            playerHp: levelData.playerHp,
            monsterHp: levelData.monsterHp,
            isPlayerTurn: true,
            isBattleOver: false,
            wave: 1,
            maxWaves: levelData.waveCount,
            hasWon: false
        };
        
        // 日誌函數
        function log(message, type = 'info') {
            const logElement = document.getElementById('debug-log');
            const time = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = `debug-entry debug-${type}`;
            entry.innerHTML = `<span class="debug-time">[${time}]</span> ${message}`;
            logElement.prepend(entry);
            
            // 同時在控制台輸出
            if (type === 'info') console.log(message);
            else if (type === 'warn') console.warn(message);
            else if (type === 'error') console.error(message);
        }
        
        // 更新戰鬥狀態顯示
        function updateBattleStateDisplay() {
            document.getElementById('battle-state-display').textContent = JSON.stringify(battleState, null, 2);
        }
        
        // 更新戰鬥消息
        function updateBattleMessage(message) {
            const messageElement = document.getElementById('battle-message-content');
            if (messageElement) {
                messageElement.textContent = message;
                log(`更新戰鬥消息: ${message}`);
            } else {
                log('找不到戰鬥消息元素', 'warn');
            }
        }
        
        // 更新玩家HP顯示
        function updatePlayerHp() {
            const hpElement = document.querySelector('.character-stats .hp-value');
            if (hpElement) {
                hpElement.textContent = battleState.playerHp;
                log(`更新玩家HP: ${battleState.playerHp}`);
            } else {
                log('找不到玩家HP元素', 'warn');
            }
        }
        
        // 更新怪物HP顯示
        function updateMonsterHp() {
            const hpElement = document.querySelector('.monster-hp .current-hp');
            if (hpElement) {
                hpElement.textContent = battleState.monsterHp;
                log(`更新怪物HP: ${battleState.monsterHp}`);
            } else {
                log('找不到怪物HP元素', 'warn');
            }
            
            const hpBar = document.querySelector('.monster-hp .hp-fill');
            if (hpBar) {
                const percent = Math.max(0, battleState.monsterHp / levelData.monsterHp * 100);
                hpBar.style.width = `${percent}%`;
                
                // 根據血量調整顏色
                if (percent <= 20) {
                    hpBar.style.backgroundColor = '#ff4444';
                } else if (percent <= 50) {
                    hpBar.style.backgroundColor = '#ffaa33';
                } else {
                    hpBar.style.backgroundColor = '#cc4444';
                }
            }
        }
        
        // 計算傷害
        function calculateDamage(attackPower) {
            const minDamage = Math.floor(attackPower * 0.8);
            const maxDamage = Math.floor(attackPower * 1.2);
            const damage = Math.floor(Math.random() * (maxDamage - minDamage + 1)) + minDamage;
            log(`計算傷害: 攻擊力 ${attackPower}，結果 ${damage}`);
            return damage;
        }
        
        // 顯示攻擊效果
        function showAttackEffect(target, damage) {
            log(`顯示攻擊效果: 目標 ${target}，傷害 ${damage}`);
            
            try {
                // 創建特效元素
                const effectsContainer = document.querySelector(target === 'monster' ? '.monster-effects' : '.character-unit');
                
                if (!effectsContainer) {
                    log('找不到效果容器元素', 'warn');
                    return;
                }
                
                const damageText = document.createElement('div');
                damageText.textContent = `-${damage}`;
                damageText.style.position = 'absolute';
                damageText.style.color = 'red';
                damageText.style.fontWeight = 'bold';
                damageText.style.fontSize = '24px';
                damageText.style.top = '50%';
                damageText.style.left = '50%';
                damageText.style.transform = 'translate(-50%, -50%)';
                damageText.style.textShadow = '1px 1px 2px black';
                damageText.style.zIndex = '100';
                
                // 添加到容器
                effectsContainer.appendChild(damageText);
                
                // 動畫效果
                let opacity = 1;
                let posY = 0;
                const interval = setInterval(() => {
                    opacity -= 0.05;
                    posY -= 2;
                    damageText.style.opacity = opacity;
                    damageText.style.transform = `translate(-50%, calc(-50% + ${posY}px))`;
                    
                    if (opacity <= 0) {
                        clearInterval(interval);
                        damageText.remove();
                    }
                }, 50);
            } catch (error) {
                log(`顯示攻擊效果時出錯: ${error.message}`, 'error');
            }
        }
        
        // 玩家攻擊
        function playerAttack() {
            try {
                if (battleState.isBattleOver) {
                    log('戰鬥已結束，無法攻擊', 'warn');
                    return;
                }
                
                const damage = calculateDamage(levelData.playerAttack);
                battleState.monsterHp -= damage;
                
                showAttackEffect('monster', damage);
                updateBattleMessage(`玩家攻擊！對怪物造成 ${damage} 點傷害！`);
                updateMonsterHp();
                updateBattleStateDisplay();
                
                // 檢查怪物是否死亡
                if (battleState.monsterHp <= 0) {
                    log(`怪物被擊敗! 當前波數: ${battleState.wave}/${battleState.maxWaves}`);
                    
                    if (battleState.wave < battleState.maxWaves) {
                        nextWave();
                    } else {
                        endBattle(true);
                    }
                } else {
                    // 怪物回合
                    battleState.isPlayerTurn = false;
                    setTimeout(monsterAttack, 1500);
                }
            } catch (error) {
                log(`玩家攻擊時出錯: ${error.message}`, 'error');
                console.error(error);
            }
        }
        
        // 怪物攻擊
        function monsterAttack() {
            try {
                if (battleState.isBattleOver) {
                    log('戰鬥已結束，無法攻擊', 'warn');
                    return;
                }
                
                const damage = calculateDamage(levelData.monsterAttack);
                battleState.playerHp -= damage;
                
                showAttackEffect('player', damage);
                updateBattleMessage(`怪物攻擊！對玩家造成 ${damage} 點傷害！`);
                updatePlayerHp();
                updateBattleStateDisplay();
                
                // 檢查玩家是否死亡
                if (battleState.playerHp <= 0) {
                    endBattle(false);
                } else {
                    // 玩家回合
                    battleState.isPlayerTurn = true;
                }
            } catch (error) {
                log(`怪物攻擊時出錯: ${error.message}`, 'error');
                console.error(error);
            }
        }
        
        // 下一波
        function nextWave() {
            try {
                battleState.wave++;
                battleState.monsterHp = levelData.monsterHp; // 重置怪物血量
                
                log(`進入下一波: ${battleState.wave}/${battleState.maxWaves}`);
                updateBattleMessage(`第 ${battleState.wave} 波戰鬥開始！`);
                updateMonsterHp();
                updateBattleStateDisplay();
                
                // 顯示波數效果
                showWaveIndicator();
                
                // 重置回合
                battleState.isPlayerTurn = true;
            } catch (error) {
                log(`切換到下一波時出錯: ${error.message}`, 'error');
                console.error(error);
            }
        }
        
        // 顯示波數指示器
        function showWaveIndicator() {
            try {
                const waveText = document.createElement('div');
                waveText.textContent = `第 ${battleState.wave} 波`;
                waveText.style.position = 'fixed';
                waveText.style.top = '50%';
                waveText.style.left = '50%';
                waveText.style.transform = 'translate(-50%, -50%)';
                waveText.style.backgroundColor = 'rgba(0,0,0,0.7)';
                waveText.style.color = 'white';
                waveText.style.padding = '20px 40px';
                waveText.style.borderRadius = '10px';
                waveText.style.fontSize = '24px';
                waveText.style.zIndex = '1000';
                
                document.body.appendChild(waveText);
                
                setTimeout(() => {
                    waveText.remove();
                }, 2000);
            } catch (error) {
                log(`顯示波數指示器時出錯: ${error.message}`, 'error');
            }
        }
        
        // 結束戰鬥
        function endBattle(isVictory) {
            try {
                battleState.isBattleOver = true;
                battleState.hasWon = isVictory;
                updateBattleStateDisplay();
                
                log(`戰鬥結束: ${isVictory ? '勝利' : '失敗'}`);
                
                if (isVictory) {
                    updateBattleMessage('恭喜！你擊敗了所有怪物！');
                    showVictoryEffect();
                } else {
                    updateBattleMessage('你被怪物擊敗了！');
                    showDefeatEffect();
                }
                
                // 顯示重試按鈕
                const retryButton = document.getElementById('retry-button');
                if (retryButton) {
                    retryButton.style.display = 'inline-block';
                }
                
                // 禁用提交按鈕
                const submitButton = document.getElementById('submit-code');
                if (submitButton) {
                    submitButton.disabled = true;
                }
            } catch (error) {
                log(`結束戰鬥時出錯: ${error.message}`, 'error');
                console.error(error);
            }
        }
        
        // 顯示勝利效果
        function showVictoryEffect() {
            try {
                log('顯示勝利效果');
                
                const victoryEffect = document.createElement('div');
                victoryEffect.style.position = 'fixed';
                victoryEffect.style.top = '0';
                victoryEffect.style.left = '0';
                victoryEffect.style.width = '100%';
                victoryEffect.style.height = '100%';
                victoryEffect.style.backgroundColor = 'rgba(0,150,0,0.3)';
                victoryEffect.style.display = 'flex';
                victoryEffect.style.justifyContent = 'center';
                victoryEffect.style.alignItems = 'center';
                victoryEffect.style.zIndex = '9999';
                
                const victoryText = document.createElement('div');
                victoryText.textContent = 'Victory!';
                victoryText.style.fontSize = '72px';
                victoryText.style.color = '#ffcc00';
                victoryText.style.textShadow = '2px 2px 4px #000';
                victoryText.style.fontWeight = 'bold';
                
                victoryEffect.appendChild(victoryText);
                document.body.appendChild(victoryEffect);
                
                setTimeout(() => {
                    victoryEffect.remove();
                }, 3000);
            } catch (error) {
                log(`顯示勝利效果時出錯: ${error.message}`, 'error');
                console.error(error);
            }
        }
        
        // 顯示失敗效果
        function showDefeatEffect() {
            try {
                log('顯示失敗效果');
                
                const defeatEffect = document.createElement('div');
                defeatEffect.style.position = 'fixed';
                defeatEffect.style.top = '0';
                defeatEffect.style.left = '0';
                defeatEffect.style.width = '100%';
                defeatEffect.style.height = '100%';
                defeatEffect.style.backgroundColor = 'rgba(150,0,0,0.3)';
                defeatEffect.style.display = 'flex';
                defeatEffect.style.justifyContent = 'center';
                defeatEffect.style.alignItems = 'center';
                defeatEffect.style.zIndex = '9999';
                
                const defeatText = document.createElement('div');
                defeatText.textContent = 'Defeat!';
                defeatText.style.fontSize = '72px';
                defeatText.style.color = '#ff3333';
                defeatText.style.textShadow = '2px 2px 4px #000';
                defeatText.style.fontWeight = 'bold';
                
                defeatEffect.appendChild(defeatText);
                document.body.appendChild(defeatEffect);
                
                setTimeout(() => {
                    defeatEffect.remove();
                }, 3000);
            } catch (error) {
                log(`顯示失敗效果時出錯: ${error.message}`, 'error');
                console.error(error);
            }
        }
        
        // 重置戰鬥
        function resetBattle() {
            log('重置戰鬥');
            
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
            
            // 更新界面
            updatePlayerHp();
            updateMonsterHp();
            updateBattleMessage('戰鬥已重置，準備開始！');
            
            // 重置按鈕
            const submitButton = document.getElementById('submit-code');
            if (submitButton) {
                submitButton.disabled = false;
            }
            
            const retryButton = document.getElementById('retry-button');
            if (retryButton) {
                retryButton.style.display = 'none';
            }
            
            updateBattleStateDisplay();
        }
        
        // 添加事件監聽器
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化
            log('頁面載入完成，初始化戰鬥測試系統');
            updateBattleStateDisplay();
            
            // 綁定測試按鈕
            document.getElementById('submit-code').addEventListener('click', function() {
                log('點擊提交按鈕 - 模擬答案正確');
                playerAttack();
            });
            
            document.getElementById('retry-button').addEventListener('click', resetBattle);
            
            // 測試控制按鈕
            document.getElementById('test-correct').addEventListener('click', function() {
                log('測試: 答案正確');
                playerAttack();
            });
            
            document.getElementById('test-incorrect').addEventListener('click', function() {
                log('測試: 答案錯誤');
                monsterAttack();
            });
            
            document.getElementById('test-victory').addEventListener('click', function() {
                log('測試: 勝利效果');
                showVictoryEffect();
            });
            
            document.getElementById('test-defeat').addEventListener('click', function() {
                log('測試: 失敗效果');
                showDefeatEffect();
            });
            
            document.getElementById('test-next-wave').addEventListener('click', function() {
                log('測試: 下一波');
                nextWave();
            });
            
            document.getElementById('test-reset').addEventListener('click', resetBattle);
        });
    </script>
</body>
</html>
