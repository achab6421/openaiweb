<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once "config/database.php";
$db = (new Database())->getConnection();

// 取得所有章節
$chapters = [];
$stmt = $db->query("SELECT chapter_id, chapter_name FROM chapters WHERE is_hidden=0 ORDER BY chapter_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $chapters[$row['chapter_id']] = $row['chapter_name'];
}

// 取得每章節總關卡數
$level_counts = [];
$stmt = $db->query("SELECT chapter_id, COUNT(*) AS cnt FROM levels GROUP BY chapter_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $level_counts[$row['chapter_id']] = $row['cnt'];
}

// 取得玩家每章節已完成關卡數
$user_id = $_SESSION['user_id'];
$completed_counts = [];
$sql = "SELECT l.chapter_id, COUNT(DISTINCT plr.level_id) AS completed
        FROM player_level_records plr
        JOIN levels l ON plr.level_id = l.level_id
        WHERE plr.player_id = ? AND plr.success_count > 0
        GROUP BY l.chapter_id";
$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $completed_counts[$row['chapter_id']] = $row['completed'];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['username']) ?> - Python 程式迷宮</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/chapter.css">
    <link rel="stylesheet" href="assets/css/quest.css">
    <link rel="stylesheet" href="assets/css/problem-display.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .maze-levels {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: flex-start;
        }

        .maze-level {
            background: #111;
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.18);
            margin-bottom: 0;
            min-width: 220px;
            max-width: 260px;
            flex: 1 1 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: box-shadow 0.2s, background 0.2s;
            border: 2px solid #222;
        }

        .maze-level:hover:not(.locked) {
            box-shadow: 0 8px 24px rgba(60, 60, 60, 0.18);
            background: #222;
            border: 2px solid #444;
        }

        .maze-level.locked {
            background: #222;
            color: #888;
            cursor: not-allowed;
            border: 2px dashed #444;
        }

        .maze-level .maze-pattern {
            width: 48px;
            height: 48px;
            background: #222;
            border-radius: 50%;
            margin-bottom: 8px;
        }

        .maze-level .level-lock {
            color: #e67e22;
            font-size: 1.5rem;
            margin-bottom: 4px;
        }

        .maze-level .level-required {
            color: #e67e22;
            font-size: 0.98rem;
            margin-bottom: 8px;
        }

        .maze-level .level-number {
            font-size: 2.2rem;
            font-weight: bold;
            margin: 10px 0 4px 0;
            letter-spacing: 2px;
            background: #222;
            border-radius: 6px;
            padding: 2px 18px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            color: #fff;
        }

        .maze-level .level-label {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0;
            letter-spacing: 1px;
        }

        @media (max-width: 900px) {
            .maze-levels {
                flex-direction: column;
                gap: 16px;
            }

            .maze-level {
                min-width: 0;
                max-width: 100%;
                width: 100%;
            }
        }

        /* 新增 quest-list-panel 邊距 */
        .maze-info {
            margin-top: 20px;
            margin-left: 20px;
        }

        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            display: none;
        }

        .modal-edit-profile {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #23272b;
            color: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.18);
            padding: 32px 32px 24px 32px;
            z-index: 9999;
            min-width: 320px;
            max-width: 90vw;
            display: none;
        }

        .modal-edit-profile h2 {
            margin-top: 0;
            color: #3a8bfd;
        }

        .modal-edit-profile label {
            display: block;
            margin-top: 16px;
            margin-bottom: 6px;
            font-size: 1rem;
        }

        .modal-edit-profile input {
            width: 100%;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #333;
            background: #181c20;
            color: #fff;
            font-size: 1rem;
        }

        .modal-edit-profile .modal-actions {
            margin-top: 24px;
            display: flex;
            gap: 16px;
            justify-content: flex-end;
        }

        .modal-edit-profile .btn {
            padding: 8px 18px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            cursor: pointer;
        }

        .modal-edit-profile .btn-primary {
            background: #3a8bfd;
            color: #fff;
        }

        .modal-edit-profile .btn-secondary {
            background: #444;
            color: #fff;
        }

        .modal-edit-profile .error-message {
            color: #ff8888;
            margin-top: 8px;
            font-size: 0.98rem;
        }

        .modal-edit-profile .success-message {
            color: #4caf50;
            margin-top: 8px;
            font-size: 0.98rem;
        }
    </style>
</head>

<body>
    <div class="main-container quest-page">
        <!-- 側邊欄 -->
        <div class="sidebar">
            <div class="player-info">
                <img src="assets/images/hunter-avatar.png" alt="獵人頭像" class="player-avatar">
                <h3><?= htmlspecialchars($_SESSION['username']) ?></h3>
                <div class="player-stats">
                    <div class="stat">
                        <span>等級</span>
                        <strong><?= $_SESSION['level'] ?></strong>
                    </div>
                    <div class="stat">
                        <span>攻擊力</span>
                        <strong><?= $_SESSION['attack_power'] ?></strong>
                    </div>
                    <div class="stat">
                        <span>血量</span>
                        <strong><?= $_SESSION['base_hp'] ?></strong>
                    </div>
                </div>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="dashboard.php">主頁</a></li>
                    <li><a href="profile.php">獵人檔案</a></li>
                    <li><a href="achievements.php">成就系統</a></li>
                    <li><a href="game/maze/index.php">秘密任務</a></li>
                    <li><a href="game/lobby/index.php">多人副本</a></li>
                    <li><a href="api/logout.php">登出</a></li>
                </ul>
            </nav>
        </div>
        <div class="main-content">
            <div class="quest-header">
                <h1>獵人檔案</h1>
                <p>查看您的獵人資訊和章節進度</p>
            </div>
            <div class="profile-content" style="display: flex; gap: 32px; align-items: flex-start; justify-content: center; height: 100vh; overflow: hidden;">
                <!-- 左側：使用者基本資訊 -->
                <div class="profile-card" style="background:#23272b;border-radius:14px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:32px 32px 24px 32px;width:50%;overflow:hidden;">
                    <div style="text-align:center;">
                        <img src="assets/images/hunter-avatar.png" alt="頭像" style="width:120px;height:120px;border-radius:50%;border:4px solid #3a8bfd;margin-bottom:18px;background:#222;object-fit:cover;">
                        <div style="font-size:2.2rem;font-weight:bold;margin-bottom:8px;color:#3a8bfd;text-align:center;">
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </div>
                    </div>
                    <div style="margin:18px 0 0 0;display:flex;gap:16px;justify-content:center;">
                        <div style="background:#222;border-radius:8px;padding:12px 18px;text-align:center;min-width:70px;">
                            <div style="font-size:1rem;color:#aaa;">等級</div>
                            <div style="font-size:1.5rem;font-weight:bold;color:#fff;margin-top:4px;"><?= $_SESSION['level'] ?></div>
                        </div>
                        <div style="background:#222;border-radius:8px;padding:12px 18px;text-align:center;min-width:70px;">
                            <div style="font-size:1rem;color:#aaa;">攻擊力</div>
                            <div style="font-size:1.5rem;font-weight:bold;color:#fff;margin-top:4px;"><?= $_SESSION['attack_power'] ?></div>
                        </div>
                        <div style="background:#222;border-radius:8px;padding:12px 18px;text-align:center;min-width:70px;">
                            <div style="font-size:1rem;color:#aaa;">血量</div>
                            <div style="font-size:1.5rem;font-weight:bold;color:#fff;margin-top:4px;"><?= $_SESSION['base_hp'] ?></div>
                        </div>
                    </div>
                    <div style="margin-top:32px;text-align:center;">
                        <button id="editProfileBtn" type="button" style="display:inline-block;background:#3a8bfd;color:#fff;border-radius:8px;padding:10px 28px;font-size:1.1rem;text-decoration:none;margin:0 8px;transition:background 0.15s;border:none;cursor:pointer;">
                            <i class="fas fa-user-edit"></i> 編輯帳號
                        </button>
                    </div>
                </div>
                <!-- 右側：章節進度 -->
                <div class="profile-card" style="background:#23272b;border-radius:14px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:32px 32px 24px 32px;width:50%;">
                    <h3 style="color:#3a8bfd;text-align:center;margin-bottom:18px;">章節進度</h3>
                    <table style="width:100%;color:#fff;background:#23272b;border-radius:8px;">
                        <thead>
                            <tr style="color:#aaa;">
                                <th style="padding:8px 0;">章節</th>
                                <th style="padding:8px 0;">完成/總數</th>
                                <th style="padding:8px 0;">進度</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chapters as $cid => $cname): 
                                $done = isset($completed_counts[$cid]) ? $completed_counts[$cid] : 0;
                                $total = isset($level_counts[$cid]) ? $level_counts[$cid] : 0;
                                $percent = ($total > 0) ? round($done / $total * 100) : 0;
                            ?>
                            <tr>
                                <td style="padding:6px 0;"><?= htmlspecialchars($cname) ?></td>
                                <td style="padding:6px 0;"><?= $done ?> / <?= $total ?></td>
                                <td style="padding:6px 0;">
                                    <div style="background:#333;border-radius:6px;width:100px;height:10px;display:inline-block;vertical-align:middle;overflow:hidden;">
                                        <div style="background:linear-gradient(90deg,#4fc3f7,#2196f3);height:100%;width:<?= $percent ?>%;"></div>
                                    </div>
                                    <span style="font-size:12px;color:#aaa;margin-left:8px;"><?= $percent ?>%</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- 編輯帳號 Modal -->
    <div class="modal-backdrop" id="editProfileBackdrop"></div>
    <div class="modal-edit-profile" id="editProfileModal">
        <h2>編輯帳號</h2>
        <form id="editProfileForm" autocomplete="off">
            <label for="edit-username">名稱</label>
            <input type="text" id="edit-username" name="username" required maxlength="32" value="<?= htmlspecialchars($_SESSION['username']) ?>">
            <label for="edit-password">新密碼（留空則不更改）</label>
            <input type="password" id="edit-password" name="password" maxlength="64" autocomplete="new-password" placeholder="請輸入新密碼">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="editProfileCancelBtn">取消</button>
                <button type="submit" class="btn btn-primary">儲存</button>
            </div>
            <div class="error-message" id="editProfileError" style="display:none;"></div>
            <div class="success-message" id="editProfileSuccess" style="display:none;"></div>
        </form>
    </div>

    <script src="assets/js/quest.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function randomMaze(level, el) {
            // 若有 locked class，則不執行並提示
            if (el.classList.contains('locked')) {
                let msg = el.querySelector('.level-required')?.innerText || '此迷宮尚未解鎖！';
                alert(msg); // 增加 alert 提示
                return false; // 明確回傳 false
            }

            var pages = [
                'maze_level_choose.php?level=' + level,
                'maze_level_Sorting.php?level=' + level
            ];
            var idx = Math.floor(Math.random() * pages.length);
            window.location.href = pages[idx];

            return true; // 執行成功時回傳 true
        }

        // 編輯帳號 Modal 控制
        const editProfileBtn = document.getElementById('editProfileBtn');
        const editProfileModal = document.getElementById('editProfileModal');
        const editProfileBackdrop = document.getElementById('editProfileBackdrop');
        const editProfileCancelBtn = document.getElementById('editProfileCancelBtn');
        const editProfileForm = document.getElementById('editProfileForm');
        const editProfileError = document.getElementById('editProfileError');
        const editProfileSuccess = document.getElementById('editProfileSuccess');

        // 開啟 modal
        editProfileBtn.onclick = function() {
            editProfileModal.style.display = 'block';
            editProfileBackdrop.style.display = 'block';
            editProfileError.style.display = 'none';
            editProfileSuccess.style.display = 'none';
            editProfileForm.username.value = <?= json_encode($_SESSION['username']) ?>;
            editProfileForm.password.value = '';
        };

        // 關閉 modal
        function closeEditProfileModal() {
            editProfileModal.style.display = 'none';
            editProfileBackdrop.style.display = 'none';
        }

        editProfileCancelBtn.onclick = closeEditProfileModal;
        editProfileBackdrop.onclick = closeEditProfileModal;

        // ESC 關閉
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape" && editProfileModal.style.display === 'block') {
                closeEditProfileModal();
            }
        });

        // 表單送出
        editProfileForm.onsubmit = function(e) {
            e.preventDefault();
            editProfileError.style.display = 'none';
            editProfileSuccess.style.display = 'none';
            const username = editProfileForm.username.value.trim();
            const password = editProfileForm.password.value;
            if (!username) {
                editProfileError.textContent = '名稱不能為空';
                editProfileError.style.display = 'block';
                return;
            }
            fetch('edit_profile_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({username, password})
            })
            .then(res => {
                // 強制用 text() 解析，避免瀏覽器自動解析 JSON 造成 SweetAlert2 衝突
                return res.text();
            })
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    editProfileError.textContent = '伺服器回傳格式錯誤';
                    editProfileError.style.display = 'block';
                    editProfileSuccess.style.display = 'none';
                    return;
                }
                if (data.success) {
                    // SweetAlert2 成功提示
                    Swal.fire({
                        icon: 'success',
                        title: '修改成功！',
                        showConfirmButton: false,
                        timer: 1200
                    });

                    setTimeout(() => {
                        closeEditProfileModal();
                        window.location.reload();
                    }, 1200);
                } else {
                    editProfileError.textContent = data.message || '修改失敗';
                    editProfileError.style.display = 'block';
                    editProfileSuccess.style.display = 'none';
                }
            })
            .catch(err => {
                editProfileError.textContent = '伺服器錯誤，請稍後再試。';
                editProfileError.style.display = 'block';
                editProfileSuccess.style.display = 'none';
            });
        };
    </script>
</body>

</html>