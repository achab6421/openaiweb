<?php
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['code'])) {
    header('Location: room_list.php');
    exit;
}

$invite_code = trim($_GET['code']);

include_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();

// 房間與副本
$stmt = $db->prepare("SELECT t.*, d.name AS dungeon_name FROM teams t LEFT JOIN dungeons d ON t.dungeon_id = d.id WHERE t.invite_code = ?");
$stmt->execute([$invite_code]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    echo '<div class="container py-5 text-center text-light"><h2>房間不存在或已被刪除</h2><a href="room_list.php" class="btn btn-secondary mt-3">返回房間列表</a></div>';
    exit;
}

// 副本關卡與怪物
$stmt = $db->prepare("SELECT l.*, m.hp, m.attack_power, m.is_boss, m.exp_reward, m.monster_id 
    FROM levels l 
    JOIN monsters m ON l.monster_id = m.monster_id 
    WHERE l.dungeon_id = ? 
    ORDER BY l.level_id ASC LIMIT 1");
$stmt->execute([$room['dungeon_id']]);
$level = $stmt->fetch(PDO::FETCH_ASSOC);

$monster_max_hp = $level['hp'] ?? 100;
$monster_attack = $level['attack_power'] ?? 10;
$monster_id = $level['monster_id'] ?? 1;
$is_boss = $level['is_boss'] ?? 0;
$exp_reward = $level['exp_reward'] ?? 10;
$monsterImage = "../../assets/images/monsters/monster-{$monster_id}.png";
if (!file_exists($monsterImage)) {
    $monsterImage = $is_boss
        ? "../../assets/images/monsters/default-boss.png"
        : "../../assets/images/monsters/default-monster.png";
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>多人戰鬥 - <?= htmlspecialchars($room['dungeon_name'] ?? '副本') ?></title>

    <!-- 基本樣式 -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/battle_multi.css">

    <!-- 字體與圖示 -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- CodeMirror 樣式 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/theme/dracula.css">
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>
    <script>
        // 讓 team_chat.js 能取得房間代碼與用戶名
        window.TEAM_CHAT_CODE = "<?= htmlspecialchars($room['invite_code']) ?>";
        window.TEAM_CHAT_USER = "<?= htmlspecialchars($_SESSION['username']) ?>";
    </script>
    <script src="../../assets/js/team_chat.js"></script>
</head>
<body>
    <div class="multi-battle-container">
        <!-- 大標題：副本：名稱（靠左） -->
        <div style="width:100%;padding:28px 0 0 38px;text-align:left;">
            <span style="font-size:2rem;font-weight:bold;color:#ffe4b5;letter-spacing:2px;">
                副本：<?= htmlspecialchars($room['dungeon_name'] ?? '副本') ?>
            </span>
        </div>
        <!-- 上方：副本名稱 + 返回副本大廳 -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 32px 0 32px;">
            <div>
                <a href="room.php?code=<?= urlencode($room['invite_code']) ?>"
                   class="btn"
                   style="
                        font-size:1.08rem;
                        padding:7px 22px;
                        border-radius:10px;
                        margin-right:18px;
                        background: linear-gradient(90deg,#232526 60%,#181210 100%);
                        color:#ffe4b5;
                        border: 2px solid #ffb84d;
                        font-weight:bold;
                        letter-spacing:1px;
                        box-shadow:0 2px 8px #0006;
                        transition:background 0.2s,border 0.2s;
                        
                   "
                   onmouseover="this.style.background='#2d2113';this.style.color='#fffbe6';this.style.borderColor='#ffe4b5';"
                   onmouseout="this.style.background='linear-gradient(90deg,#232526 60%,#181210 100%)';this.style.color='#ffe4b5';this.style.borderColor='#ffb84d';"
                >
                    <i class="fas fa-arrow-left" style="margin-right:7px;"></i>返回房間
                </a>
            </div>
            <!-- 玩家狀態欄 -->
            <div style="display:flex;align-items:center;gap:18px;">
                <span style="color:#ffe4b5;font-size:1.1rem;font-weight:bold;">
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <div style="background:#181210;padding:8px 18px;border-radius:8px;color:#ffe4b5;font-size:1rem;display:flex;gap:18px;align-items:center;border:1.5px solid #534b42;">
                    <span>LV. <?= intval($_SESSION['level'] ?? 1) ?></span>
                    <span>ATK: <?= intval($_SESSION['attack_power'] ?? 10) ?></span>
                    <span>HP: <?= intval($_SESSION['base_hp'] ?? 100) ?></span>
                </div>
            </div>
        </div>
        <!-- 主內容 -->
        <div class="multi-battle-content">
            <!-- 左側程式區 -->
            <div class="multi-code-section">
                <div class="multi-problem-container">
                    <h2>挑戰題目</h2>
                    <div id="problem-description" class="multi-problem-description">
                        <div class="multi-loading">正在載入題目...</div>
                    </div>
                </div>

                <div class="multi-code-editor-container" style="display:flex;flex-direction:column;height:100%;">
                    <div style="color:#ffb84d;font-size:1.05rem;font-weight:bold;margin-bottom:8px;">
                        PYTHON 程式碼
                    </div>
                    <textarea id="code-editor"># 請輸入你的程式碼</textarea>

                    <!-- 操作區塊（移除紅色邊框，改為一般區塊） -->
                    <div style="margin-top:16px; background:#181210; border-radius:8px; padding:18px 12px 12px 12px; display:flex; flex-direction:column; gap:12px;">
                        <div style="display: flex; gap: 0;">
                            <div style="flex:1.2; display:flex; flex-direction:column;">
                                <textarea id="editor-input" class="multi-input-textarea" placeholder="請輸入你的答案..." style="width:100%;height:100%;min-height:60px;max-height:100px;padding:10px 14px;border-radius:8px 0 0 8px;border:1.5px solid #534b42;border-right:none;background:#181c20;color:#fff;font-size:1rem;resize:vertical;"></textarea>
                            </div>
                            <div style="flex:2; display:flex; flex-direction:column;">
                                <div class="multi-output-container" style="height:100%; min-height:60px; max-height:100px; border-radius: 0 8px 8px 0; border:1.5px solid #534b42; border-left:none; background: #181210; box-sizing: border-box; overflow: hidden;">
                                    <div style="padding: 8px 16px; color: #ffe4b5; font-weight: bold; background: none; border-bottom: 1px solid #534b42; font-size: 1rem;">
                                        執行結果
                                    </div>
                                    <div id="output-display" class="multi-output-display" style="min-height:28px; max-height:calc(100% - 36px); background: none; border: none; border-radius: 0;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="multi-editor-buttons" style="display:flex; margin-top:0;">
                            <button id="test-code" class="multi-test-button" style="background:#2c3e50;color:#fff;">測試代碼</button>
                            <button id="submit-code" class="multi-submit-button" style="background:#27ae60;color:#fff;">提交答案</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 右側怪物區 -->
            <div class="multi-battle-section">
                <div class="multi-monsters-area">
                    <div class="multi-monster-unit" id="monster1">
                        <div class="multi-monster-name"><?= $is_boss ? 'BOSS' : '怪物' ?></div>
                        <div class="multi-monster-sprite">
                            <img src="<?= $monsterImage ?>" alt="怪物" class="multi-monster-image">
                            <div class="multi-monster-effects"></div>
                        </div>
                        <div class="multi-monster-hp">
                            <div class="multi-hp-text">HP <span class="current-hp"><?= $monster_max_hp ?></span>/<?= $monster_max_hp ?></div>
                            <div class="multi-hp-bar">
                                <div class="multi-hp-fill" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="multi-battle-message">
                    <div class="multi-message-content" id="battle-message-content">
                        戰鬥開始！請編寫程式碼以擊敗怪物！
                    </div>
                </div>

                <!-- 新增：組隊聊天室 -->
                <div id="team-chat-container" style="margin-top:24px;background:rgba(30,20,10,0.93);border-radius:12px;padding:12px 10px 8px 10px;box-shadow:0 2px 12px #0006;">
                    <div style="font-weight:bold;color:#ffb84d;font-size:1.08rem;margin-bottom:8px;">
                        組隊聊天室
                    </div>
                    <div id="team-chat-messages" style="height:180px;overflow-y:auto;background:#181210;border-radius:8px;padding:10px 8px 10px 12px;margin-bottom:8px;border:1px solid #534b42;"></div>
                    <form id="team-chat-form" style="display:flex;gap:8px;">
                        <input type="text" id="team-chat-input" placeholder="輸入訊息..." autocomplete="off" style="flex:1;border-radius:6px;padding:6px 12px;border:1px solid #534b42;background:#232526;color:#fff;">
                        <button type="submit" style="background:#8b0000;color:#fff;border:none;border-radius:6px;padding:6px 18px;font-weight:bold;">送出</button>
                    </form>
                    <?php include 'get_team_chat.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CodeMirror Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/python/python.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/addon/edit/matchbrackets.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/addon/edit/closebrackets.js"></script>

    <!-- 初始化 CodeMirror -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const editor = CodeMirror.fromTextArea(document.getElementById("code-editor"), {
            lineNumbers: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            mode: "python",
            theme: "dracula",
            indentUnit: 4,
            tabSize: 4
        });

        document.getElementById('test-code').addEventListener('click', function () {
            const code = editor.getValue();
            console.log('[測試] 代碼內容：', code);
            // 加入你要傳送 code 的 ajax
        });

        document.getElementById('submit-code').addEventListener('click', function () {
            const code = editor.getValue();
            console.log('[提交] 代碼內容：', code);
            // 加入你要傳送 code 的 ajax
        });
    });
    </script>

    <!-- 其他 JS -->
    <script src="../../assets/js/battle_multi.js"></script>
    <script>
    // Firebase 設定
    const firebaseConfig = {
        // TODO: 請填入你的 Firebase 設定
        apiKey: "YOUR_API_KEY",
        authDomain: "YOUR_AUTH_DOMAIN",
        databaseURL: "YOUR_DATABASE_URL",
        projectId: "YOUR_PROJECT_ID",
        storageBucket: "YOUR_STORAGE_BUCKET",
        messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
        appId: "YOUR_APP_ID"
    };
    firebase.initializeApp(firebaseConfig);

    // 取得房間唯一代碼作為聊天室房間
    const teamCode = "<?= htmlspecialchars($room['invite_code']) ?>";
    const chatRef = firebase.database().ref('team_chats/' + teamCode);

    // 顯示訊息
    chatRef.limitToLast(50).on('child_added', function(snapshot) {
        const msg = snapshot.val();
        const msgDiv = document.createElement('div');
        msgDiv.innerHTML = `<span style="color:#ffe4b5;font-weight:bold;">${msg.user}：</span><span style="color:#fff;">${msg.text}</span><span style="color:#888;font-size:0.9em;margin-left:8px;">${msg.time}</span>`;
        document.getElementById('team-chat-messages').appendChild(msgDiv);
        document.getElementById('team-chat-messages').scrollTop = document.getElementById('team-chat-messages').scrollHeight;
    });

    // 發送訊息
    document.getElementById('team-chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('team-chat-input');
        const text = input.value.trim();
        if (!text) return;
        const now = new Date();
        const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        chatRef.push({
            user: "<?= htmlspecialchars($_SESSION['username']) ?>",
            text: text,
            time: time
        });
        input.value = '';
    });

  
    </script>
</body>
</html>
