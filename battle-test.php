<?php
// 戰鬥機制測試頁面
session_start();

// 確保用戶已登入，並使用有效的數字ID
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = 1; // 使用數字ID而非文本
    $_SESSION['username'] = '測試玩家';
    $_SESSION['level'] = 1;
    $_SESSION['attack_power'] = 10;
    $_SESSION['base_hp'] = 100;
}

// 確保用戶ID是數字
if (!is_numeric($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

// 設置頁面標題
$pageTitle = "戰鬥系統測試";

// 模擬關卡數據
$levelData = [
    'levelId' => 1,
    'chapterId' => 1,
    'monsterHp' => 100,
    'monsterAttack' => 15,
    'playerHp' => $_SESSION['base_hp'],
    'playerAttack' => $_SESSION['attack_power'],
    'waveCount' => 2,
    'expReward' => 50,
    'isBoss' => false,
    'teachingPoint' => '測試教學點'
];

// 確保測試用戶存在於數據庫
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // 檢查測試用戶是否存在
    $checkUserQuery = "SELECT * FROM players WHERE player_id = ?";
    $checkUserStmt = $db->prepare($checkUserQuery);
    $checkUserStmt->execute([$_SESSION['user_id']]);
    
    if ($checkUserStmt->rowCount() == 0) {
        // 創建測試用戶
        $createUserQuery = "INSERT INTO players (player_id, username, account, password, attack_power, base_hp, level) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
        $createUserStmt = $db->prepare($createUserQuery);
        $createUserStmt->execute([
            $_SESSION['user_id'],
            $_SESSION['username'],
            'test_account',
            password_hash('test_password', PASSWORD_DEFAULT),
            $_SESSION['attack_power'],
            $_SESSION['base_hp'],
            $_SESSION['level']
        ]);
        
        // 添加經驗值列 (如果不存在)
        try {
            $checkColumnQuery = "SHOW COLUMNS FROM players LIKE 'experience'";
            $checkColumnStmt = $db->query($checkColumnQuery);
            $experienceColumnExists = ($checkColumnStmt->rowCount() > 0);
            
            if (!$experienceColumnExists) {
                $addColumnQuery = "ALTER TABLE players ADD COLUMN experience INT NOT NULL DEFAULT 0";
                $db->exec($addColumnQuery);
            }
        } catch (PDOException $e) {
            // 忽略錯誤，繼續執行
        }
        
        echo "<script>console.log('已創建測試用戶')</script>";
    }
} catch (PDOException $e) {
    // 記錄錯誤但繼續執行
    echo "<script>console.error('檢查/創建測試用戶時出錯: " . addslashes($e->getMessage()) . "')</script>";
}
?>

<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .panel {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .battle-scene {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .monster-area, .player-area {
            flex: 1;
            padding: 20px;
            text-align: center;
        }
        .monster-sprite, .player-sprite {
            width: 150px;
            height: 150px;
            background-color: #eee;
            margin: 0 auto 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #666;
        }
        .hp-bar {
            height: 20px;
            background-color: #e0e0e0;
            border-radius: 10px;
            margin-top: 10px;
            overflow: hidden;
            position: relative;
        }
        .hp-fill {
            height: 100%;
            background-color: #4CAF50;
            width: 100%;
            transition: width 0.3s;
        }
        .monster-hp .hp-fill {
            background-color: #F44336;
        }
        .battle-log {
            background-color: #333;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            min-height: 100px;
            max-height: 200px;
            overflow-y: auto;
        }
        .battle-log-entry {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #555;
        }
        .battle-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .button {
            padding: 12px 24px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #0b7dda;
        }
        .button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .button.attack {
            background-color: #4CAF50;
        }
        .button.danger {
            background-color: #F44336;
        }
        .debug-panel {
            margin-top: 30px;
            padding: 15px;
            background-color: #f0f8ff;
            border: 1px solid #b3e0ff;
            border-radius: 8px;
        }
        .debug-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .debug-button {
            padding: 8px 15px;
            font-size: 14px;
        }
        .debug-log {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 15px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="panel">
            <h1>戰鬥系統測試</h1>
            <p>本頁面用於測試戰鬥邏輯和數據庫更新功能</p>
            
            <div class="battle-scene">
                <div class="monster-area">
                    <h2>怪物</h2>
                    <div class="monster-sprite">👾</div>
                    <div class="monster-stats">
                        HP: <span id="monster-hp">100</span>/<span id="monster-max-hp">100</span>
                    </div>
                    <div class="hp-bar monster-hp">
                        <div class="hp-fill" id="monster-hp-bar"></div>
                    </div>
                </div>
                
                <div class="player-area">
                    <h2>玩家: <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                    <div class="player-sprite">🧙</div>
                    <div class="player-stats">
                        <div>等級: <span id="player-level"><?php echo $_SESSION['level']; ?></span></div>
                        <div>HP: <span id="player-hp"><?php echo $levelData['playerHp']; ?></span>/<span id="player-max-hp"><?php echo $levelData['playerHp']; ?></span></div>
                        <div>攻擊力: <span id="player-atk"><?php echo $levelData['playerAttack']; ?></span></div>
                    </div>
                    <div class="hp-bar player-hp">
                        <div class="hp-fill" id="player-hp-bar"></div>
                    </div>
                </div>
            </div>
            
            <div class="battle-log" id="battle-log">
                <div class="battle-log-entry">戰鬥開始！</div>
            </div>
            
            <div class="battle-controls">
                <button id="attack-btn" class="button attack">玩家攻擊</button>
                <button id="monster-attack-btn" class="button danger">怪物攻擊</button>
                <button id="reset-btn" class="button">重設戰鬥</button>
            </div>
            
            <div class="debug-panel">
                <h3>測試功能</h3>
                <div class="debug-controls">
                    <button id="complete-level-btn" class="button debug-button">模擬關卡完成</button>
                    <button id="win-battle-btn" class="button debug-button attack">模擬戰鬥勝利</button>
                    <button id="test-level-up-btn" class="button debug-button">測試升級功能</button>
                    <button id="check-db-btn" class="button debug-button">檢查數據庫記錄</button>
                    <button id="fix-db-btn" class="button debug-button danger">修復數據庫</button>
                </div>
                
                <h3>自定義測試</h3>
                <div>
                    <label for="level-id">關卡ID:</label>
                    <input type="number" id="level-id" min="1" value="1" style="width:60px">
                    
                    <label for="chapter-id" style="margin-left:10px">章節ID:</label>
                    <input type="number" id="chapter-id" min="1" value="1" style="width:60px">
                    
                    <label for="exp-reward" style="margin-left:10px">經驗值獎勵:</label>
                    <input type="number" id="exp-reward" min="0" value="50" style="width:60px">
                    
                    <button id="custom-test-btn" class="button debug-button" style="margin-left:10px">執行自定義測試</button>
                </div>
                
                <h3>調試日誌</h3>
                <div class="debug-log" id="debug-log"></div>
            </div>
        </div>
    </div>
    
    <script>
        // 戰鬥狀態
        let battleState = {
            playerHp: <?php echo $levelData['playerHp']; ?>,
            monsterHp: <?php echo $levelData['monsterHp']; ?>,
            isPlayerTurn: true,
            isBattleOver: false,
            wave: 1,
            maxWaves: <?php echo $levelData['waveCount']; ?>,
            hasWon: false
        };
        
        // 等級數據
        const levelData = <?php echo json_encode($levelData); ?>;
        
        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            updateHealthBars();
            
            // 事件監聽
            document.getElementById('attack-btn').addEventListener('click', playerAttack);
            document.getElementById('monster-attack-btn').addEventListener('click', monsterAttack);
            document.getElementById('reset-btn').addEventListener('click', resetBattle);
            
            // 調試按鈕
            document.getElementById('complete-level-btn').addEventListener('click', testCompleteLevel);
            document.getElementById('win-battle-btn').addEventListener('click', simulateBattleWin);
            document.getElementById('test-level-up-btn').addEventListener('click', testLevelUp);
            document.getElementById('check-db-btn').addEventListener('click', checkDatabaseRecords);
            document.getElementById('fix-db-btn').addEventListener('click', function() {
                window.location.href = 'tools/fix-database.php';
            });
            document.getElementById('custom-test-btn').addEventListener('click', runCustomTest);
        });
        
        // 玩家攻擊
        function playerAttack() {
            if (battleState.isBattleOver) return;
            
            // 計算傷害
            const damage = calculateDamage(levelData.playerAttack);
            battleState.monsterHp -= damage;
            
            // 記錄日誌
            logBattle(`玩家攻擊！造成 ${damage} 點傷害！`);
            logDebug(`玩家攻擊，怪物剩餘HP: ${battleState.monsterHp}`);
            
            // 更新UI
            updateHealthBars();
            
            // 檢查怪物是否死亡
            if (battleState.monsterHp <= 0) {
                endBattle(true);
            }
        }
        
        // 怪物攻擊
        function monsterAttack() {
            if (battleState.isBattleOver) return;
            
            // 計算傷害
            const damage = calculateDamage(levelData.monsterAttack);
            battleState.playerHp -= damage;
            
            // 記錄日誌
            logBattle(`怪物攻擊！造成 ${damage} 點傷害！`);
            logDebug(`怪物攻擊，玩家剩餘HP: ${battleState.playerHp}`);
            
            // 更新UI
            updateHealthBars();
            
            // 檢查玩家是否死亡
            if (battleState.playerHp <= 0) {
                endBattle(false);
            }
        }
        
        // 計算傷害
        function calculateDamage(attackPower) {
            const baseDamage = parseInt(attackPower);
            const minDamage = Math.floor(baseDamage * 0.8);
            const maxDamage = Math.floor(baseDamage * 1.2);
            return Math.floor(Math.random() * (maxDamage - minDamage + 1)) + minDamage;
        }
        
        // 結束戰鬥
        function endBattle(isVictory) {
            battleState.isBattleOver = true;
            battleState.hasWon = isVictory;
            
            if (isVictory) {
                logBattle('戰鬥勝利！怪物被擊敗了！');
            } else {
                logBattle('戰鬥失敗！你被怪物擊敗了...');
            }
        }
        
        // 重設戰鬥
        function resetBattle() {
            battleState = {
                playerHp: levelData.playerHp,
                monsterHp: levelData.monsterHp,
                isPlayerTurn: true,
                isBattleOver: false,
                wave: 1,
                maxWaves: levelData.waveCount,
                hasWon: false
            };
            
            // 更新UI
            updateHealthBars();
            logBattle('戰鬥已重置！');
            logDebug('戰鬥狀態已重置');
            
            document.getElementById('battle-log').innerHTML = 
                '<div class="battle-log-entry">戰鬥開始！</div>';
        }
        
        // 更新血量條
        function updateHealthBars() {
            // 玩家HP
            document.getElementById('player-hp').textContent = Math.max(0, battleState.playerHp);
            const playerHpPercent = Math.max(0, (battleState.playerHp / levelData.playerHp) * 100);
            document.getElementById('player-hp-bar').style.width = `${playerHpPercent}%`;
            
            // 怪物HP
            document.getElementById('monster-hp').textContent = Math.max(0, battleState.monsterHp);
            const monsterHpPercent = Math.max(0, (battleState.monsterHp / levelData.monsterHp) * 100);
            document.getElementById('monster-hp-bar').style.width = `${monsterHpPercent}%`;
        }
        
        // 記錄戰鬥日誌
        function logBattle(message) {
            const battleLog = document.getElementById('battle-log');
            const entry = document.createElement('div');
            entry.className = 'battle-log-entry';
            entry.textContent = message;
            battleLog.appendChild(entry);
            battleLog.scrollTop = battleLog.scrollHeight;
        }
        
        // 記錄調試日誌
        function logDebug(message) {
            const debugLog = document.getElementById('debug-log');
            const time = new Date().toLocaleTimeString();
            debugLog.innerHTML += `[${time}] ${message}\n`;
            debugLog.scrollTop = debugLog.scrollHeight;
        }
        
        // 測試關卡完成
        function testCompleteLevel() {
            const levelId = document.getElementById('level-id').value || 1;
            const chapterId = document.getElementById('chapter-id').value || 1;
            
            logDebug(`測試關卡完成: 關卡ID=${levelId}, 章節ID=${chapterId}`);
            
            fetch('api/complete-level.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    levelId: levelId,
                    chapterId: chapterId
                })
            })
            .then(response => response.json())
            .then(data => {
                logDebug(`API回應: ${JSON.stringify(data)}`);
                
                if (data.success) {
                    logBattle(`關卡 ${levelId} 完成記錄成功！`);
                    
                    if (data.expReward) {
                        logBattle(`獲得 ${data.expReward} 經驗值！`);
                    }
                    
                    if (data.levelUp) {
                        logBattle(`升級了！現在是等級 ${data.newLevel}！`);
                        document.getElementById('player-level').textContent = data.newLevel;
                    }
                } else {
                    logBattle(`關卡完成記錄失敗: ${data.message}`);
                }
            })
            .catch(error => {
                logDebug(`API錯誤: ${error.message}`);
                logBattle(`發生錯誤: ${error.message}`);
            });
        }
        
        // 模擬戰鬥勝利
        function simulateBattleWin() {
            battleState.monsterHp = 0;
            updateHealthBars();
            endBattle(true);
            logBattle('模擬戰鬥勝利！');
            logDebug('模擬戰鬥勝利完成');
        }
        
        // 測試升級功能
        function testLevelUp() {
            const expInput = prompt('輸入要給予的經驗值 (建議: 100+):', '100');
            if (!expInput) return;
            
            const exp = parseInt(expInput);
            
            logDebug(`測試升級功能: 增加 ${exp} 經驗值`);
            
            fetch('api/test-level-up.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    expAmount: exp
                })
            })
            .then(response => response.json())
            .then(data => {
                logDebug(`API回應: ${JSON.stringify(data)}`);
                
                if (data.success) {
                    logBattle(`獲得 ${exp} 經驗值！`);
                    
                    if (data.levelUp) {
                        logBattle(`升級了！現在是等級 ${data.newLevel}！`);
                        document.getElementById('player-level').textContent = data.newLevel;
                    }
                } else {
                    logBattle(`測試失敗: ${data.message}`);
                }
            })
            .catch(error => {
                logDebug(`API錯誤: ${error.message}`);
                logBattle(`發生錯誤: ${error.message}`);
            });
        }
        
        // 檢查數據庫記錄
        function checkDatabaseRecords() {
            logDebug('檢查數據庫記錄...');
            
            fetch('api/check-records.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                logDebug(`API回應: ${JSON.stringify(data)}`);
                
                if (data.success) {
                    logBattle('數據庫記錄檢查完成');
                    
                    // 顯示玩家信息
                    if (data.playerInfo) {
                        logBattle(`玩家等級: ${data.playerInfo.level}, 經驗值: ${data.playerInfo.experience || 0}`);
                        logBattle(`已完成關卡數: ${data.completedLevelsCount}`);
                    }
                } else {
                    logBattle(`檢查失敗: ${data.message}`);
                }
            })
            .catch(error => {
                logDebug(`API錯誤: ${error.message}`);
                logBattle(`發生錯誤: ${error.message}`);
            });
        }
        
        // 運行自定義測試
        function runCustomTest() {
            const levelId = document.getElementById('level-id').value || 1;
            const chapterId = document.getElementById('chapter-id').value || 1;
            const expReward = document.getElementById('exp-reward').value || 50;
            
            logDebug(`自定義測試: 關卡=${levelId}, 章節=${chapterId}, 經驗值=${expReward}`);
            
            fetch('api/complete-level.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    levelId: levelId,
                    chapterId: chapterId,
                    expReward: expReward
                })
            })
            .then(response => response.text())
            .then(text => {
                logDebug(`原始API回應: ${text}`);
                
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        logBattle(`關卡 ${levelId} 完成！獲得 ${expReward} 經驗值！`);
                        
                        if (data.levelUp) {
                            logBattle(`升級了！現在是等級 ${data.newLevel}！`);
                        }
                    } else {
                        logBattle(`測試失敗: ${data.message}`);
                    }
                } catch (e) {
                    logDebug(`JSON解析錯誤: ${e.message}`);
                    logBattle(`回應格式錯誤，請查看調試日誌`);
                }
            })
            .catch(error => {
                logDebug(`API錯誤: ${error.message}`);
                logBattle(`發生錯誤: ${error.message}`);
            });
        }
    </script>
</body>
</html>
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
