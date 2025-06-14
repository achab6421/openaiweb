-- 副本資料表 dungeons

CREATE TABLE dungeons (
  id INT AUTO_INCREMENT PRIMARY KEY,                         -- 副本唯一 ID
  name VARCHAR(100) NOT NULL COMMENT '副本名稱',             -- 顯示用的副本名稱
  description TEXT COMMENT '副本描述',                      -- 描述副本故事或背景
  difficulty ENUM('簡單', '普通', '困難', '地獄') COMMENT '難度等級',  -- 可選定義好的難度分類
  levels INT DEFAULT 1 COMMENT '副本包含的關卡數',            -- 可擴展為多層副本
  theme_summary TEXT COMMENT '主題摘要'                     -- 本副本學習的 Python 主題
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO dungeons (name, description, difficulty, levels, theme_summary) VALUES
('初識之門', '從變數與輸入開始你的冒險旅程，進入魔塔的第一道門。', '簡單', 1, 'input(), print(), 變數設定'),
('資料迷宮', '在充滿岔路與機關的迷宮中，學習流程控制脫困。', '普通', 1, 'if, while, for'),
('數據結界', '整理混亂的資料結構，破解封印的結界大門。', '普通', 1, 'list, dict, set'),
('錯誤異界', '深入錯誤與例外的空間，修正錯誤程式碼並生還。', '困難', 1, 'try/except, debug'),
('AI 魔神終局之戰', '與 AI 魔神展開最終對決，運用所學戰勝程式黑暗。', '地獄', 1, 'function, algorithm, 綜合挑戰');


-- 隊伍資料表
CREATE TABLE IF NOT EXISTS teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    invite_code VARCHAR(16) UNIQUE NOT NULL COMMENT '隊伍邀請碼',
    max_members INT DEFAULT 4 COMMENT '隊伍上限人數',
    is_public BOOLEAN DEFAULT FALSE COMMENT '是否公開',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 隊伍成員資料表（修正版，建立 user_id 的外鍵）
CREATE TABLE IF NOT EXISTS team_members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '使用者ID',
    team_id INT NOT NULL COMMENT '隊伍ID',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES players(player_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_member (user_id, team_id) COMMENT '防止同一使用者重複加入同一隊伍'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE teams ADD COLUMN room_name VARCHAR(255) NOT NULL DEFAULT '' COMMENT '房間名稱';

ALTER TABLE teams ADD COLUMN room_password VARCHAR(255) DEFAULT NULL;


CREATE TABLE player_dungeon_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  player_id INT NOT NULL,
  dungeon_id INT NOT NULL,
  status ENUM('未開始', '進行中', '已完成') DEFAULT '未開始',
  progress INT DEFAULT 0,  -- 當前通過的關卡數（若支援多層）
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE,
  FOREIGN KEY (dungeon_id) REFERENCES dungeons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE teams ADD COLUMN dungeon_id INT;
