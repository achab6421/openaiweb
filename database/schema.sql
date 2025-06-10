-- Python 怪物村：AI 助教教你寫程式打怪獸！
-- 資料庫建立指令

-- 玩家資料表
CREATE TABLE IF NOT EXISTS `players` (
  `player_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `account` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL, -- 儲存雜湊後的密碼
  `completed_levels` JSON DEFAULT ('[]'), -- 已完成關卡
  `completed_learning_levels` JSON DEFAULT ('[]'), -- 已完成學習關卡
  `attack_power` INT NOT NULL DEFAULT 10,
  `base_hp` INT NOT NULL DEFAULT 100,
  `level` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 章節資料表
CREATE TABLE IF NOT EXISTS `chapters` (
  `chapter_id` INT AUTO_INCREMENT PRIMARY KEY,
  `chapter_name` VARCHAR(100) NOT NULL,
  `difficulty` INT NOT NULL DEFAULT 1, -- 1-10 難度等級
  `summary` TEXT NOT NULL, -- 章節大綱
  `level_count` INT NOT NULL DEFAULT 0, -- 章節關卡數量
  `is_open` BOOLEAN NOT NULL DEFAULT FALSE, -- 是否開放
  `is_hidden` BOOLEAN NOT NULL DEFAULT FALSE, -- 是否為隱藏章節
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 怪物資料表
CREATE TABLE IF NOT EXISTS `monsters` (
  `monster_id` INT AUTO_INCREMENT PRIMARY KEY,
  `hp` INT NOT NULL,
  `difficulty` INT NOT NULL DEFAULT 1, -- 1-10 難度等級
  `attack_power` INT NOT NULL,
  `exp_reward` INT NOT NULL, -- 經驗值獎勵
  `teaching_point` TEXT NOT NULL, -- 教學重點
  `is_boss` BOOLEAN NOT NULL DEFAULT FALSE, -- 是否為BOSS級怪物
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 關卡資料表
CREATE TABLE IF NOT EXISTS `levels` (
  `level_id` INT AUTO_INCREMENT PRIMARY KEY,
  `chapter_id` INT NOT NULL,
  `monster_id` INT NOT NULL,
  `prerequisite_level_id` INT NULL, -- 前置條件關卡ID，NULL表示沒有前置條件
  `wave_count` INT NOT NULL DEFAULT 1, -- 關卡波數
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`chapter_id`) REFERENCES `chapters`(`chapter_id`) ON DELETE CASCADE,
  FOREIGN KEY (`monster_id`) REFERENCES `monsters`(`monster_id`) ON DELETE CASCADE,
  FOREIGN KEY (`prerequisite_level_id`) REFERENCES `levels`(`level_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 玩家關卡紀錄表
CREATE TABLE IF NOT EXISTS `player_level_records` (
  `record_id` INT AUTO_INCREMENT PRIMARY KEY,
  `player_id` INT NOT NULL,
  `level_id` INT NOT NULL,
  `attempt_count` INT NOT NULL DEFAULT 0, -- 嘗試次數
  `success_count` INT NOT NULL DEFAULT 0, -- 討伐成功數
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `player_level_unique` (`player_id`, `level_id`),
  FOREIGN KEY (`player_id`) REFERENCES `players`(`player_id`) ON DELETE CASCADE,
  FOREIGN KEY (`level_id`) REFERENCES `levels`(`level_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 玩家章節紀錄表
CREATE TABLE IF NOT EXISTS `player_chapter_records` (
  `record_id` INT AUTO_INCREMENT PRIMARY KEY,
  `player_id` INT NOT NULL,
  `chapter_id` INT NOT NULL,
  `is_completed` BOOLEAN NOT NULL DEFAULT FALSE, -- 是否完成
  `is_unlocked` BOOLEAN NOT NULL DEFAULT FALSE, -- 是否解鎖
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `player_chapter_unique` (`player_id`, `chapter_id`),
  FOREIGN KEY (`player_id`) REFERENCES `players`(`player_id`) ON DELETE CASCADE,
  FOREIGN KEY (`chapter_id`) REFERENCES `chapters`(`chapter_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 隱藏關卡資料表
CREATE TABLE IF NOT EXISTS `hidden_levels` (
  `hidden_level_id` INT AUTO_INCREMENT PRIMARY KEY,
  `answer` TEXT NOT NULL, -- 解鎖答案
  `effect_description` TEXT NOT NULL, -- 獲得效果描述
  `is_open` BOOLEAN NOT NULL DEFAULT FALSE, -- 是否開放
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 玩家額外效果表
CREATE TABLE IF NOT EXISTS `player_special_effects` (
  `effect_id` INT AUTO_INCREMENT PRIMARY KEY,
  `hidden_level_id` INT NOT NULL,
  `player_id` INT NOT NULL,
  `is_unlocked` BOOLEAN NOT NULL DEFAULT TRUE, -- 已解鎖
  `unlocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `player_hidden_level_unique` (`player_id`, `hidden_level_id`),
  FOREIGN KEY (`hidden_level_id`) REFERENCES `hidden_levels`(`hidden_level_id`) ON DELETE CASCADE,
  FOREIGN KEY (`player_id`) REFERENCES `players`(`player_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 觸發器：設定新玩家預設解鎖第一個章節
DELIMITER //
CREATE TRIGGER after_player_insert
AFTER INSERT ON `players`
FOR EACH ROW
BEGIN
    -- 插入所有章節的紀錄，但僅第一章默認解鎖
    INSERT INTO `player_chapter_records` (`player_id`, `chapter_id`, `is_unlocked`)
    SELECT NEW.player_id, chapter_id, IF(chapter_id = 1, TRUE, FALSE)
    FROM `chapters`;
END //
DELIMITER ;

-- 插入初始第一章數據
INSERT INTO `chapters` (`chapter_id`, `chapter_name`, `difficulty`, `summary`, `level_count`, `is_open`, `is_hidden`)
VALUES (1, 'Python入門：變數與基本資料型態', 1, 'Python基本語法介紹，學習變數宣告和使用基本資料型態', 5, TRUE, FALSE);
