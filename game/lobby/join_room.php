<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0;
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";

$db = new Database();
$pdo = $db->getConnection();

// 只要網址有 code 參數就直接查詢房間，不需再輸入房間代碼
$invite_code = isset($_GET['code']) ? trim($_GET['code']) : '';
$room = null;
$members = [];
$msg = "";
$step = 1;

// 查詢房間
if ($invite_code) {
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE invite_code = ?");
    $stmt->execute([$invite_code]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($room) {
        // 查詢成員
        $stmt = $pdo->prepare("SELECT tm.user_id, p.username FROM team_members tm JOIN players p ON tm.user_id = p.player_id WHERE tm.team_id = ?");
        $stmt->execute([$room['team_id']]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 檢查是否已加入
        $already_in = false;
        foreach ($members as $m) {
            if ($m['user_id'] == $user_id) $already_in = true;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_password'])) {
            // 處理密碼送出
            $input_pwd = trim($_POST['room_password']);
            if ($already_in) {
                header("Location: room.php?code=" . urlencode($invite_code));
                exit;
            } elseif (count($members) >= $room['max_members']) {
                $msg = "房間已滿";
            } elseif ($input_pwd !== $room['room_password']) {
                $msg = "密碼錯誤";
            } else {
                // 密碼正確，加入房間
                $stmt = $pdo->prepare("INSERT INTO team_members (user_id, team_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $room['team_id']]);
                header("Location: room.php?code=" . urlencode($invite_code));
                exit;
            }
            $step = 2;
        } elseif ($room['is_public']) {
            // 公開房間直接加入
            if (!$already_in && count($members) < $room['max_members']) {
                $stmt = $pdo->prepare("INSERT INTO team_members (user_id, team_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $room['team_id']]);
            }
            header("Location: room.php?code=" . urlencode($invite_code));
            exit;
        } else {
            // 私人房間顯示密碼輸入
            $step = 2;
        }
    } else {
        $msg = "房間不存在或已被刪除";
        $step = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>加入房間</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #111;
            color: #fff;
            min-height: 100vh;
            margin: 0;
            font-family: 'Noto Sans TC', 'Microsoft JhengHei', Arial, sans-serif;
        }
        .join-room-box {
            background: #232526;
            border-radius: 18px;
            padding: 38px 38px 28px 38px;
            min-width: 350px;
            max-width: 420px;
            box-shadow: 0 8px 32px #0007;
            margin: 40px auto;
        }
        .join-room-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 28px;
            color: #fff;
        }
        .form-label {
            color: #fff;
            font-weight: 500;
        }
        .form-control {
            background: #363738;
            color: #fff;
            border: none;
            margin-bottom: 18px;
        }
        .form-control:focus {
            background: #363738;
            color: #fff;
            border: 1.5px solid #4a90e2;
            box-shadow: none;
        }
        .btn-cancel {
            background: #444;
            color: #fff;
            border-radius: 8px;
            margin-right: 10px;
            min-width: 80px;
        }
        .btn-join {
            background: #2257e7;
            color: #fff;
            border-radius: 8px;
            min-width: 80px;
        }
        .btn-join:active, .btn-cancel:active {
            filter: brightness(0.95);
        }
        .alert {
            margin-top: 10px;
        }
        @media (max-width: 600px) {
            .join-room-box {
                padding: 18px 8px 18px 8px;
                min-width: unset;
                max-width: 98vw;
            }
            .join-room-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
    <div class="join-room-box w-100">
        <div class="join-room-title text-center mb-4">加入房間</div>
        <?php if (!$room): ?>
            <div class="alert alert-danger text-center">房間不存在或已被刪除</div>
            <div class="text-center"><a href="index.php" class="btn btn-secondary mt-3">返回大廳</a></div>
        <?php elseif ($room['is_public']): ?>
            <!-- 公開房間不顯示表單，直接導向 -->
        <?php else: ?>
            <form method="post">
                <div class="mb-3">
                    <label for="roomPassword" class="form-label">房間密碼</label>
                    <input type="text" class="form-control" id="roomPassword" name="room_password" placeholder="請輸入房間密碼" required>
                </div>
                <?php if ($msg): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php" class="btn btn-cancel">取消</a>
                    <button type="submit" class="btn btn-join">加入</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
        <?php if ($step === 1): ?>
            <form method="post">
                <div class="mb-3">
                    <label for="roomCode" class="form-label">房間代碼</label>
                    <input type="text" class="form-control" id="roomCode" name="room_code" placeholder="請輸入房間代碼" required>
                </div>
                <?php if ($msg): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php" class="btn btn-cancel">取消</a>
                    <button type="submit" class="btn btn-join">下一步</button>
                </div>
            </form>
        <?php elseif ($step === 2): ?>
            <form method="post">
                <input type="hidden" name="invite_code" value="<?php echo htmlspecialchars($room['invite_code']); ?>">
                <div class="mb-3">
                    <label for="roomPassword" class="form-label">房間密碼</label>
                    <input type="text" class="form-control" id="roomPassword" name="room_password" placeholder="請輸入房間密碼" required>
                </div>
                <?php if ($msg): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php" class="btn btn-cancel">取消</a>
                    <button type="submit" class="btn btn-join">加入</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
