-- Python 怪物村：AI 助教教你寫程式打怪獸！ 資料庫結構

-- 創建資料庫
CREATE DATABASE IF NOT EXISTS python_monster_village CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用資料庫
USE python_monster_village;

-- 創建使用者表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    current_level INT DEFAULT 1,
    experience_points INT DEFAULT 0,
    attack_power INT DEFAULT 10,
    defense_power INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 章節資料表
CREATE TABLE IF NOT EXISTS chapters (
    chapter_id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_name VARCHAR(100) NOT NULL,
    difficulty VARCHAR(10) NOT NULL COMMENT '初階 / 中階 / 高階',
    summary TEXT NOT NULL COMMENT '該章節的學習目標或介紹',
    num_stages INT NOT NULL DEFAULT 1 COMMENT '該章節包含幾個關卡（不含隱藏）',
    is_unlocked BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'True：已開放、False：鎖定中',
    is_hidden BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'True：這是個隱藏章節',
    unlock_hint TEXT COMMENT '需要先完成前一個章節才可以解鎖',
    parent_chapter_id INT NULL COMMENT '若這是隱藏章節，這欄可指向主章節',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_chapter_id) REFERENCES chapters(chapter_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 題目資料表
CREATE TABLE IF NOT EXISTS questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT NOT NULL,
    title VARCHAR(255) NOT NULL COMMENT '題目的名稱或主旨',
    content TEXT NOT NULL COMMENT '題目的敘述文字',
    correct_answer TEXT NOT NULL COMMENT '用於評分與比對',
    difficulty VARCHAR(10) NOT NULL COMMENT '初階 / 中階 / 高階',
    question_type VARCHAR(20) NOT NULL COMMENT '選擇題、填空題',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chapter_id) REFERENCES chapters(chapter_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 玩家答題紀錄表
CREATE TABLE IF NOT EXISTS player_answer_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '時間戳記',
    player_answer TEXT NOT NULL COMMENT '玩家實際輸入的答案',
    is_correct BOOLEAN NOT NULL COMMENT '是否答對',
    hint_used_count INT DEFAULT 0 COMMENT '與 AI 助教互動次數',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 怪物資料表
CREATE TABLE IF NOT EXISTS monsters (
    monster_id INT AUTO_INCREMENT PRIMARY KEY,
    monster_name VARCHAR(100) NOT NULL COMMENT '怪物名稱（如：語法哥布林、函式龍）',
    max_hp INT NOT NULL COMMENT '最大血量',
    attack_power INT NOT NULL COMMENT '攻擊力',
    difficulty VARCHAR(10) NOT NULL COMMENT '難度等級（初階 / 中階 / 高階）',
    stage_id INT NOT NULL COMMENT '所在關卡 id（關聯關卡資料表）',
    try_count INT DEFAULT 0 COMMENT '玩家總挑戰次數（可累積統計）',
    image_path VARCHAR(255) NULL COMMENT '怪物圖示路徑，支援前端顯示',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stage_id) REFERENCES chapters(chapter_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 隱藏關卡資料表
CREATE TABLE IF NOT EXISTS hidden_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT NOT NULL COMMENT '對應章節（關聯普通章節）',
    stage_name VARCHAR(100) NOT NULL COMMENT '隱藏關卡名稱（ex: 深網追蹤）',
    trigger_hint TEXT NOT NULL COMMENT '顯示給玩家的提示文字（如：某網站有密碼）',
    expected_answer TEXT NOT NULL COMMENT '玩家要提交的正確答案（可為關鍵字、數值、網址等）',
    unlock_effect TEXT NOT NULL COMMENT '解鎖後給予的效果描述（ex: 攻擊力+20）',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT '是否啟用此隱藏關卡（開發中可暫時關閉）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chapter_id) REFERENCES chapters(chapter_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 玩家隱藏關卡解鎖表
CREATE TABLE IF NOT EXISTS player_hidden_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL COMMENT '關聯玩家資料表',
    hidden_stage_id INT NOT NULL COMMENT '關聯隱藏關卡資料表',
    is_unlocked BOOLEAN NOT NULL DEFAULT FALSE COMMENT '玩家是否已解鎖',
    unlocked_at TIMESTAMP NULL COMMENT '解鎖時間',
    effect_applied BOOLEAN NOT NULL DEFAULT FALSE COMMENT '是否已套用效果（防止重複套用）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hidden_stage_id) REFERENCES hidden_stages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 玩家進度表
CREATE TABLE IF NOT EXISTS player_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    chapter_id INT NOT NULL,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    completion_date TIMESTAMP NULL,
    stars_earned INT DEFAULT 0 COMMENT '評分（1-3顆星）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES chapters(chapter_id) ON DELETE CASCADE,
    UNIQUE KEY player_chapter (player_id, chapter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入一些初始資料

-- 插入測試使用者
INSERT INTO users (username, password, current_level) VALUES 
('test', '$2y$10$4RB7a.FXOzRx7AJgFL4IyuvTm3W4Aop1ov6H20TALYd9dAgtwKJte', 1),
('admin', '$2y$10$4RB7a.FXOzRx7AJgFL4IyuvTm3W4Aop1ov6H20TALYd9dAgtwKJte', 5);
-- 密碼均為 "password"

-- 插入章節資料
INSERT INTO chapters (chapter_name, difficulty, summary, num_stages, is_unlocked, is_hidden, unlock_hint) VALUES
('第1章 Python基礎入門', '初階', '學習Python的基本語法、變數和運算符', 3, TRUE, FALSE, NULL),
('第2章 條件判斷', '初階', '學習if-else條件語句和邏輯運算', 3, FALSE, FALSE, '需完成第1章所有關卡'),
('第3章 循環結構', '初階', '掌握for和while循環的使用方式', 3, FALSE, FALSE, '需完成第2章所有關卡'),
('第4章 函數基礎', '中階', '學習定義和使用函數', 3, FALSE, FALSE, '需完成第3章所有關卡'),
('第5章 列表與元組', '中階', '掌握Python中的序列型資料結構', 3, FALSE, FALSE, '需完成第4章所有關卡'),
('第6章 字典與集合', '中階', '學習使用key-value資料結構', 3, FALSE, FALSE, '需完成第5章所有關卡'),
('第7章 文件操作', '高階', '學習讀寫文件和處理不同格式的數據', 3, FALSE, FALSE, '需完成第6章所有關卡'),
('第8章 錯誤處理', '高階', '學習使用try-except處理程序中的異常', 3, FALSE, FALSE, '需完成第7章所有關卡'),
('第9章 模組與套件', '高階', '學習導入和使用Python模組與第三方庫', 3, FALSE, FALSE, '需完成第8章所有關卡');

-- 插入一些隱藏章節
INSERT INTO chapters (chapter_name, difficulty, summary, num_stages, is_unlocked, is_hidden, unlock_hint, parent_chapter_id) VALUES
('隱藏關卡：Python之謎', '中階', '解開Python中的一些奇特用法', 1, FALSE, TRUE, '在第3章發現特殊線索', 3),
('隱藏關卡：爬蟲秘傳', '高階', '學習簡單的網頁爬蟲技術', 1, FALSE, TRUE, '在第7章使用特定的文件操作', 7);

-- 插入一些基礎題目
INSERT INTO questions (chapter_id, title, content, correct_answer, difficulty, question_type) VALUES
(1, '變數宣告', '如何宣告一個名為 name 且值為 "Python" 的變數？', 'name = "Python"', '初階', '填空題'),
(1, '整數運算', '計算 5 + 3 * 2 的結果是多少？', '11', '初階', '填空題'),
(1, '字串連接', '如何連接兩個字串 "Hello" 和 "World"？', '"Hello" + "World"', '初階', '填空題'),
(2, '條件判斷', '編寫一個if語句檢查變數x是否大於10', 'if x > 10:', '初階', '填空題'),
(2, '多重條件', '如何檢查變數x是否在1到10之間（包含1和10）？', 'if 1 <= x <= 10:', '初階', '填空題');

-- 插入一些怪物資料
INSERT INTO monsters (monster_name, max_hp, attack_power, difficulty, stage_id, image_path) VALUES
('語法哥布林', 50, 5, '初階', 1, 'assets/images/monsters/syntax_goblin.png'),
('變數史萊姆', 40, 3, '初階', 1, 'assets/images/monsters/variable_slime.png'),
('條件判斷蝙蝠', 60, 7, '初階', 2, 'assets/images/monsters/if_bat.png'),
('循環幽靈', 80, 10, '中階', 3, 'assets/images/monsters/loop_ghost.png'),
('函式骷髏', 100, 15, '中階', 4, 'assets/images/monsters/function_skeleton.png'),
('列表巨獸', 120, 20, '中階', 5, 'assets/images/monsters/list_beast.png'),
('字典魔法師', 150, 25, '高階', 6, 'assets/images/monsters/dict_wizard.png'),
('檔案巨龍', 200, 30, '高階', 7, 'assets/images/monsters/file_dragon.png'),
('異常惡魔', 220, 35, '高階', 8, 'assets/images/monsters/exception_demon.png'),
('模組獵人', 250, 40, '高階', 9, 'assets/images/monsters/module_hunter.png');

-- 插入隱藏關卡
INSERT INTO hidden_stages (chapter_id, stage_name, trigger_hint, expected_answer, unlock_effect, is_active) VALUES
(3, '深網追蹤', '尋找隱藏在第3章中的秘密註解', 'python_secret_1337', '攻擊力+15', TRUE),
(7, '爬蟲密技', '使用程式從特定網站取得密鑰', 'crawler_master_key', '防禦力+20', TRUE);

-- 將第一章設為已解鎖狀態
UPDATE chapters SET is_unlocked = TRUE WHERE chapter_id = 1;
