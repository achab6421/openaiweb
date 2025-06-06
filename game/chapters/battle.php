<?php
// 初始化會話
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// 檢查是否有提供怪物 ID
if (!isset($_GET["monster_id"]) || empty($_GET["monster_id"])) {
    header("location: index.php");
    exit;
}

$monster_id = intval($_GET["monster_id"]);

// 引入數據庫連接文件
require_once "../../config/database_game.php";

// 載入 .env 取得 OpenAI API Key 與 Assistant ID
$dotenv_path = dirname(__DIR__, 2) . '/.env';
if (file_exists($dotenv_path)) {
    $env = parse_ini_file($dotenv_path, false, INI_SCANNER_RAW);
    $OPENAI_API_KEY = isset($env['OPENAI_API_KEY_43']) ? trim($env['OPENAI_API_KEY_43'], "\"' ") : '';
    $OPENAI_ASSISTANT_ID = isset($env['OPENAI_ASSISTANT_ID_43']) ? trim($env['OPENAI_ASSISTANT_ID_43'], "\"' ") : '';
} else {
    $OPENAI_API_KEY = '';
    $OPENAI_ASSISTANT_ID = '';
}

$user_id = $_SESSION["id"];

// 獲取怪物信息
$monster = null;
$sql = "SELECT * FROM monsters WHERE monster_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $monster_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $monster = $result->fetch_assoc();
        } else {
            header("location: index.php");
            exit;
        }
    }
    $stmt->close();
}

// 取得怪物名稱、章節ID、難度（只用 monsters 表）
$monster_name = '';
$chapter_id = 0;
$difficulty = '';
if ($monster) {
    echo "<pre>";
    // var_dump($monster);
    echo "</pre>";

    $monster_name = $monster['monster_name'] ?? '';
    if (isset($monster['chapter_id'])) {
        $chapter_id = intval($monster['chapter_id']);
    } elseif (isset($monster['stage_id'])) {
        $chapter_id = intval($monster['stage_id']);
    }
    $difficulty = $monster['difficulty'] ?? '初階';
} else {
    echo "<span style='color:red;'>❌ 未抓到怪物資料，請確認 monster_id 是否正確</span>";
    exit;
}

// 模擬戰鬥邏輯
$player_hp = 100;
$monster_hp = $monster["max_hp"];
$battle_log = [];
$player_health_log = [];
$monster_health_log = [];
$base_attack_damage = 20;
$player_position = ['x' => 2, 'y' => 4];
$monster_position = ['x' => 2, 'y' => 0];
$battle_message = '';
$damage_to_player = 0;
$damage_to_monster = 0;

// OpenAI API 取得題目（使用怪物資訊）
function get_openai_question($api_key, $assistant_id, $difficulty, $monster_name) {
    $api_url = 'https://api.openai.com/v1/chat/completions';

    $prompt = <<<EOD
你是一位專業的 Python 出題助教，根據怪物難易度（初階 / 中階 / 高階）設計一題實作題。
怪物名稱：「{$monster_name}」請作為題目背景的設定。
難易度：{$difficulty}

請使用繁體中文，並回傳以下格式：
題目：<請設計背景與說明>
答案：<請提供標準 Python 程式碼>
EOD;

    $data = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "system", "content" => "你是程式設計助理。"],
            ["role" => "user", "content" => $prompt]
        ],
        "max_tokens" => 512,
        "temperature" => 0.7
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key"
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $question = "題目取得失敗";
    $answer = "";

    if (empty($api_key)) {
        $question = "題目取得失敗（API KEY 未設定）";
        return [$question, $answer];
    }

    if ($result && $http_code === 200) {
        $json = json_decode($result, true);
        if (isset($json['choices'][0]['message']['content'])) {
            $content = $json['choices'][0]['message']['content'];
            if (preg_match('/題目[:：](.+)\n答案[:：](.+)/us', $content, $matches)) {
                $question = trim($matches[1]);
                $answer = trim($matches[2]);
            } else {
                $question = trim($content);
            }
        } else {
            $question = "題目取得失敗（API 回傳格式錯誤）: <pre>" . htmlspecialchars($result) . "</pre>";
        }
    } else {
        $question = "題目取得失敗（HTTP $http_code ）: " . htmlspecialchars($curl_error) . "<br>API回傳: <pre>" . htmlspecialchars($result) . "</pre>";
    }
    return [$question, $answer];
}

// 題目 session 控制（只用怪物難度與名稱）
if (!isset($_SESSION['current_question']) || !isset($_SESSION['current_answer'])) {
    list($q, $a) = get_openai_question($OPENAI_API_KEY, $OPENAI_ASSISTANT_ID, $difficulty, $monster_name);
    $_SESSION['current_question'] = $q;
    $_SESSION['current_answer'] = $a;
}
$current_question = $_SESSION['current_question'];
$correct_answer = $_SESSION['current_answer'];

// 處理玩家回答問題
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    $player_answer = trim($_POST['answer']);
    if ($player_answer !== $correct_answer) {
        if ($monster_position['y'] < $player_position['y']) {
            $monster_position['y']++;
        }
        if ($monster_position['y'] === $player_position['y']) {
            $damage_to_player = $monster["attack_power"];
            $player_hp -= $damage_to_player;
            $player_hp = max(0, $player_hp);
            $battle_message = "怪物攻擊你，造成 {$damage_to_player} 點傷害！";
        } else {
            $battle_message = "答錯了！怪物靠近了你。";
        }
    } else {
        $damage_to_monster = $base_attack_damage;
        $monster_hp -= $damage_to_monster;
        $monster_hp = max(0, $monster_hp);
        $battle_message = "你攻擊怪物，造成 {$damage_to_monster} 點傷害！";

        list($q, $a) = get_openai_question($OPENAI_API_KEY, $OPENAI_ASSISTANT_ID, $difficulty, $monster_name);
        $_SESSION['current_question'] = $q;
        $_SESSION['current_answer'] = $a;
        $current_question = $q;
        $correct_answer = $a;
    }
}

// 關閉資料庫連線
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- 加入 CodeMirror 樣式 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.14/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.14/theme/material-darker.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Segoe UI', 'Microsoft JhengHei', Arial, sans-serif;
            /* 保持原本的背景或移除背景圖 */
            background: #f9fbe7;
        }
        body {
            height: 100vh;
        }
        .container-split {
            display: flex;
            height: 100vh;
            width: 100vw;
        }
        .left {
            flex: 1;
            /* 移除原本的背景色 */
            background: transparent;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 12px rgba(76,175,80,0.08);
        }
        .left-top {
            flex: 2;
            padding: 18px 18px 10px 18px;
            border-bottom: 1px solid #bdbdbd;
            display: flex;
            flex-direction: column;
            background: #f9fbe7;
            padding-bottom: 0;
        }
        .left-top label {
            font-weight: bold;
            font-size: 1.1rem;
            color: #388e3c;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        .left-top form {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .code-input {
            display: none; /* 隱藏原生 textarea */
        }
        .CodeMirror {
            height: 100% !important;
            min-height: 400px;
            font-size: 1.08rem;
            border-radius: 8px;
            border: 1.5px solid #aed581;
            background: #212121;
            color: #fff;
            box-shadow: 0 2px 8px rgba(76,175,80,0.04);
        }
        .CodeMirror-gutters {
            background: #23272e;
            border-right: 1.5px solid #333;
            color: #bdbdbd;
        }
        .CodeMirror-linenumber {
            color: #bdbdbd;
        }
        .CodeMirror-focused {
            outline: 2px solid #43a047;
        }
        .left-top .btn {
            padding: 7px 18px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .btn-secondary {
            background: #c8e6c9;
            color: #388e3c;
            border: 1px solid #a5d6a7;
        }
        .btn-secondary:hover {
            background: #a5d6a7;
            color: #fff;
        }
        .btn-primary {
            background: #43a047;
            color: #fff;
            border: 1px solid #388e3c;
        }
        .btn-primary:hover {
            background: #388e3c;
            color: #fffde7;
        }
        .left-bottom {
            flex: 1;
            padding: 18px;
            overflow: auto;
            /* 移除原本的背景色 */
            background: transparent;
        }
        .left-bottom > div:first-child {
            font-weight: bold;
            color: #558b2f;
            font-size: 1.08rem;
            letter-spacing: 1px;
        }
        #output {
            margin-top: 10px;
            background: #fff;
            min-height: 48px;
            border: 1.5px solid #aed581;
            border-radius: 8px;
            padding: 12px;
            font-size: 1.08rem;
            color: #333;
            box-shadow: 0 2px 8px rgba(76,175,80,0.04);
        }
        .right {
            flex: 4;
            background: url('../assets/images/backgrounds/battle_background.png') center center / cover no-repeat;
            height: 100%;
            display: flex;
            flex-direction: column;
            border-radius: 24px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            margin: 16px;
            overflow: hidden;
        }
        /* 移除 .game-top 的背景色與漸層 */
        .game-top {
            flex: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 32px 40px 16px 40px;
            border-bottom: 1px solid #eee;
            height: 0;
            min-height: 0;
            position:relative;
            background: none !important;
        }
        .character-full {
            width: 160px;
            height: 240px;
            border-radius: 0; /* 移除圓角 */
            box-shadow: none; /* 秘除陰影 */
            background: none; /* 秘除背景 */
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-right: 32px;
            font-size: 6rem;
        }
        .monster-full {
            width: 160px;
            height: 240px;
            border-radius: 0; /* 移除圓角 */
            box-shadow: none; /* 秘除陰影 */
            background: none; /* 移除背景 */
            display: flex;
            align-items: flex-end;
            justify-content: center;
            overflow: hidden;
            margin-left: 32px;
            font-size: 6rem;
        }
        .game-bottom {
            flex: 1;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            background: #f9f9f9;
            height: 0;
            min-height: 0;
            justify-content: space-between;
            border-radius: 0 0 24px 24px;
        }
        .player-bottom-avatar, .player-bottom-monster-avatar {
            width: 60px;
            height: 60px;
            border-radius: 0; /* 移除圓角 */
            background: none; /* 移除背景 */
            box-shadow: none; /* 秘除陰影 */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            overflow: hidden;
        }
        .player-bottom-monster-avatar {
            background: none; /* 移除怪物頭像背景 */
        }
        .player-bottom-info-block {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin: 0 18px;
            min-width: 90px;
        }
        .player-bottom-monster-info-block {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin: 0 18px;
            min-width: 90px;
        }
        .player-bottom-stat {
            font-size: 1rem;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }
        .stat-label {
            color: #888;
            margin-right: 4px;
        }
        .character-full img,
        .monster-full img {
            background: transparent !important;
            z-index: 1;
            position: relative;
        }
        /* 選單美化 */
        #menuBtn {
            box-shadow: 0 2px 8px rgba(76,175,80,0.08);
        }
        #dropdownMenu a {
            transition: background 0.2s, color 0.2s;
        }
        #dropdownMenu a:hover {
            background: #e8f5e9;
            color: #388e3c;
        }
        /* 響應式 */
        @media (max-width: 900px) {
            .container-split { flex-direction: column; }
            .right, .left { margin: 0; border-radius: 0; }
            .game-top, .game-bottom { padding: 12px; }
        }
    </style>
</head>
<body>
    <div class="container-split">
        <div class="left">
            <div class="left-top">
                <label for="code" style="font-weight:bold;">程式碼輸入區</label>
                <form method="post" id="answerForm">
                    <textarea id="code" class="code-input" name="answer" placeholder="請在此輸入答案..."></textarea>
                    <div style="margin-top:8px; display:flex; gap:8px;">
                        <button type="button" id="testBtn" class="btn btn-secondary">測試執行</button>
                        <button type="submit" class="btn btn-primary">提交答案</button>
                    </div>
                </form>
            </div>
            <div class="left-bottom">
                <div style="font-weight:bold;">測試結果</div>
                <div id="output" style="margin-top:8px; background:#fff; min-height:40px; border:1.5px solid #aed581; border-radius:8px; padding:12px;">
                    <?php if (!empty($battle_message)) echo htmlspecialchars($battle_message); ?>
                </div>
                <div id="run-result" style="margin-top:12px; background:#f5fbe7; min-height:32px; border:1.5px solid #aed581; border-radius:8px; padding:10px; color:#222; font-size:1.08rem;"></div>
            </div>
        </div>
        <div class="right">
            <div class="game-top" style="background: linear-gradient(45deg, #4CAF50, #2E7D32); border-bottom: none; position:relative;">
                <!-- 右上角選單 -->
                <div style="position:absolute; top:18px; right:32px; z-index:20;">
                    <div style="position:relative;">
                        <button id="menuBtn" style="background:#fff; border-radius:12px; border:2px solid #ccc; padding:10px 28px; font-weight:bold; font-size:1.3rem; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                            ☰ 選單
                        </button>
                        <div id="dropdownMenu" style="display:none; position:absolute; right:0; top:54px; background:#fff; border:2px solid #ccc; border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,0.13); min-width:150px; z-index:99;">
                            <a href="detail.php" style="display:block; padding:16px 24px; color:#333; text-decoration:none; font-size:1.15rem;">返回首頁</a>
                            <a href="../logout.php" style="display:block; padding:16px 24px; color:#c62828; text-decoration:none; font-size:1.15rem;">登出</a>
                        </div>
                    </div>
                </div>
                <!-- 中間上方：目前關卡、章節 -->
                <div style="position:absolute; top:0; left:0; width:100%; text-align:center; z-index:10;">
                    <div style="font-size:2.6rem; font-weight:900; color:#fff; letter-spacing:2px; text-shadow:0 2px 12px #333;">
                        <?php echo "關卡 {$monster['monster_name']}"; ?>
                    </div>
                </div>
                <!-- 左：人物全身圖 -->
                <div class="character-full" style="display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:90px;line-height:1;">🧑‍💻</span>
                </div>
                <!-- 右：怪物全身圖 -->
                <div class="monster-full">
                    <img src="../<?php echo $monster["image_path"]; ?>" alt="Monster" style="max-width:100%;max-height:100%;">
                </div>
            </div>
            <div class="game-bottom">
                <!-- 左：腳色頭像 -->
                <div class="player-bottom-avatar" style="background: #e8f5e9; border:2px solid #4CAF50;display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:2.2rem;">🧑‍💻</span>
                </div>
                <!-- 腳色資訊 -->
                <div class="player-bottom-info-block" style="color:#2E7D32;">
                    <div class="player-bottom-stat"><span class="stat-label" style="color:#388e3c;">血量</span> <span><?php echo $player_hp; ?></span></div>
                    <div class="player-bottom-stat"><span class="stat-label" style="color:#388e3c;">攻擊力</span> <span><?php echo $base_attack_damage; ?></span></div>
                    <div class="player-bottom-stat"><span class="stat-label" style="color:#388e3c;">次數</span> <span>3</span></div>
                </div>
                <!-- 傷害訊息框 -->
                <div style="min-width:90px; margin:0 12px; text-align:center;">
                    <?php if ($damage_to_player > 0): ?>
                        <div style="display:inline-block; background:#fff3e0; color:#e65100; border-radius:16px; padding:6px 18px; font-weight:bold; box-shadow:0 1px 4px rgba(255,152,0,0.08); font-size:1.1rem;">
                            -<?php echo $damage_to_player; ?> HP
                        </div>
                    <?php elseif ($damage_to_monster > 0): ?>
                        <div style="display:inline-block; background:#e3f2fd; color:#1976d2; border-radius:16px; padding:6px 18px; font-weight:bold; box-shadow:0 1px 4px rgba(33,150,243,0.08); font-size:1.1rem;">
                            -<?php echo $damage_to_monster; ?> HP (怪物)
                        </div>
                    <?php else: ?>
                        <div style="display:inline-block; background:#fffde7; color:#fbc02d; border-radius:16px; padding:6px 18px; font-weight:bold; box-shadow:0 1px 4px rgba(255,235,59,0.08); font-size:1.1rem;">
                            等待行動...
                        </div>
                    <?php endif; ?>
                </div>
                <!-- 怪物資訊 -->
                <div class="player-bottom-monster-info-block" style="color:#c62828;">
                    <div class="player-bottom-stat"><span class="stat-label" style="color:#c62828;">怪物血量</span> <span><?php echo $monster_hp; ?></span></div>
                    <div class="player-bottom-stat"><span class="stat-label" style="color:#c62828;">怪物訊息</span> <span>
                        <?php
                            if ($monster_hp <= 0) echo "怪物被擊敗！";
                            elseif ($damage_to_monster > 0) echo "受到攻擊！";
                            elseif ($damage_to_player > 0) echo "準備攻擊！";
                            else {
                                echo "等待中...<br>";
                                // 顯示題目
                                echo "<div style='color:#333; font-size:1rem; margin-top:8px; text-align:left; white-space:pre-line;'>";
                                echo nl2br(htmlspecialchars($current_question));
                                echo "</div>";
                            }
                        ?>
                    </span></div>
                </div>
                <!-- 右：怪物頭像 -->
                <div class="player-bottom-monster-avatar" style="background: #ffebee; border:2px solid #c62828;">
                    <img src="../<?php echo $monster["image_path"]; ?>" alt="Monster" style="width:100%;height:100%;">
                </div>
            </div>
        </div>
    </div>
    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.14/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.14/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.14/addon/edit/closebrackets.min.js"></script>
    <script>
        // 初始化 CodeMirror 編輯器
        var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
            mode: "python",
            theme: "material-darker",
            lineNumbers: true,
            indentUnit: 4,
            lineWrapping: true,
            autoCloseBrackets: true,
            tabSize: 4,
        });

        // 提交答案時將內容同步到 textarea
        document.getElementById('answerForm').onsubmit = function() {
            document.getElementById('code').value = editor.getValue();
        };

        // 測試執行按鈕功能
        document.getElementById('testBtn').onclick = function() {
            var code = editor.getValue();
            var output = document.getElementById('output');
            var runResult = document.getElementById('run-result');
            if (code.trim() === "") {
                output.innerHTML = "<span style='color:#c00;'>請輸入內容再測試！</span>";
                runResult.innerHTML = "";
            } else {
                output.innerHTML = "（模擬）測試執行結果：";
                // 模擬執行 Python 程式碼，只處理 print() 輸出
                // 這裡僅前端模擬，實際應用請用後端執行
                let lines = code.split('\n');
                let result = [];
                try {
                    for (let i = 0; i < lines.length; i++) {
                        let line = lines[i].trim();
                        // 偵測 print
                        let printMatch = line.match(/^print\((.+)\)$/);
                        if (printMatch) {
                            // 只處理單純變數或數字
                            let val = printMatch[1].trim();
                            if (/^\d+$/.test(val)) {
                                result.push(val);
                            } else if (/^["'].*["']$/.test(val)) {
                                result.push(val.replace(/^["']|["']$/g, ""));
                            } else if (val === "i") {
                                // 嘗試找 for 迴圈
                                let forMatch = lines[i-1] && lines[i-1].match(/^for\s+(\w+)\s+in\s+range\((\d+),\s*(\d+)\):/);
                                if (forMatch) {
                                    let varName = forMatch[1];
                                    let start = parseInt(forMatch[2]);
                                    let end = parseInt(forMatch[3]);
                                    for (let j = start; j < end; j++) {
                                        result.push(j);
                                    }
                                } else {
                                    result.push(val);
                                }
                            } else {
                                result.push(val);
                            }
                        }
                    }
                } catch(e) {
                    result = ["[模擬器錯誤]"];
                }
                runResult.innerHTML = "<b>輸出結果：</b><br>" + (result.length ? result.join('<br>') : "(無輸出)");
            }
        };

        // 選單下拉功能（修正版，阻止冒泡並可多次點擊）
        document.getElementById('menuBtn').onclick = function(e) {
            e.stopPropagation();
            var menu = document.getElementById('dropdownMenu');
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        };
        document.getElementById('dropdownMenu').onclick = function(e) {
            e.stopPropagation();
        };
        document.body.onclick = function() {
            var menu = document.getElementById('dropdownMenu');
            if(menu) menu.style.display = 'none';
        };
    </script>
</body>
</html>