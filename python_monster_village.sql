-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-06-16 07:38:17
-- 伺服器版本： 8.3.0
-- PHP 版本： 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `python_monster_village`
--

-- --------------------------------------------------------

--
-- 資料表結構 `chapters`
--

CREATE TABLE `chapters` (
  `chapter_id` int NOT NULL,
  `chapter_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `difficulty` int NOT NULL DEFAULT '1',
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `level_count` int NOT NULL DEFAULT '0',
  `is_open` tinyint(1) NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `chapters`
--

INSERT INTO `chapters` (`chapter_id`, `chapter_name`, `difficulty`, `summary`, `level_count`, `is_open`, `is_hidden`, `created_at`, `updated_at`) VALUES
(1, 'Python入門：變數與基本資料型態', 1, 'Python基本語法介紹，學習變數宣告和使用基本資料型態', 5, 1, 0, '2025-06-10 19:21:14', '2025-06-10 19:21:14'),
(2, 'Python列表與元組', 2, '學習Python中的列表(List)和元組(Tuple)資料結構，掌握資料集合操作', 5, 1, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(3, 'Python字典與集合', 3, '探索Python中的字典(Dict)和集合(Set)，理解鍵值對和唯一值的概念', 5, 1, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(4, 'Python流程控制', 4, '學習條件判斷、迴圈與控制流程，讓程式能依據不同情況做出不同反應', 5, 1, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(5, 'Python函數與模組', 5, '設計可重用的程式碼片段，理解函數的概念與模組的組織方式', 5, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(6, '隱藏的知識：Python速度優化', 7, '進階Python性能優化技巧與演算法效率', 3, 0, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23');

-- --------------------------------------------------------

--
-- 資料表結構 `dungeons`
--

CREATE TABLE `dungeons` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '副本名稱',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '副本描述',
  `difficulty` enum('簡單','普通','困難','地獄') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '難度等級',
  `levels` int DEFAULT '1' COMMENT '副本包含的關卡數',
  `theme_summary` text COLLATE utf8mb4_unicode_ci COMMENT '主題摘要'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `dungeons`
--

INSERT INTO `dungeons` (`id`, `name`, `description`, `difficulty`, `levels`, `theme_summary`) VALUES
(1, '初識之門', '從變數與輸入開始你的冒險旅程，進入魔塔的第一道門。', '簡單', 1, 'input(), print(), 變數設定'),
(2, '資料迷宮', '在充滿岔路與機關的迷宮中，學習流程控制脫困。', '普通', 1, 'if, while, for'),
(3, '數據結界', '整理混亂的資料結構，破解封印的結界大門。', '普通', 1, 'list, dict, set'),
(4, '錯誤異界', '深入錯誤與例外的空間，修正錯誤程式碼並生還。', '困難', 1, 'try/except, debug'),
(5, 'AI 魔神終局之戰', '與 AI 魔神展開最終對決，運用所學戰勝程式黑暗。', '地獄', 1, 'function, algorithm, 綜合挑戰');

-- --------------------------------------------------------

--
-- 資料表結構 `hidden_levels`
--

CREATE TABLE `hidden_levels` (
  `hidden_level_id` int NOT NULL,
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `effect_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `hidden_levels`
--

INSERT INTO `hidden_levels` (`hidden_level_id`, `answer`, `effect_description`, `is_open`, `created_at`, `updated_at`) VALUES
(1, 'python', '增加攻擊力10點', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(2, 'monster', '增加基礎血量50點', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(3, 'dictionary', '增加攻擊力15點', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(4, 'loop', '增加基礎血量75點', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(5, 'function', '同時增加攻擊力10點和血量50點', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(6, 'pythonmaster', '全屬性增強20%', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23');

-- --------------------------------------------------------

--
-- 資料表結構 `levels`
--

CREATE TABLE `levels` (
  `level_id` int NOT NULL,
  `chapter_id` int NOT NULL,
  `monster_id` int NOT NULL,
  `prerequisite_level_id` int DEFAULT NULL,
  `wave_count` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dungeon_id` int DEFAULT NULL COMMENT '所屬副本ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `levels`
--

INSERT INTO `levels` (`level_id`, `chapter_id`, `monster_id`, `prerequisite_level_id`, `wave_count`, `created_at`, `updated_at`, `dungeon_id`) VALUES
(1, 1, 1, NULL, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(2, 1, 2, 1, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(3, 1, 3, 2, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(4, 1, 4, 3, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(5, 1, 5, 4, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(6, 2, 6, 5, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(7, 2, 7, 6, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(8, 2, 8, 7, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(9, 2, 9, 8, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(10, 2, 10, 9, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(11, 3, 11, 10, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(12, 3, 12, 11, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(13, 3, 13, 12, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(14, 3, 14, 13, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(15, 3, 15, 14, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(16, 4, 16, 15, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(17, 4, 17, 16, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(18, 4, 18, 17, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(19, 4, 19, 18, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(20, 4, 20, 19, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(21, 5, 21, 20, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(22, 5, 22, 21, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(23, 5, 23, 22, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(24, 5, 24, 23, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(25, 5, 25, 24, 4, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(26, 6, 26, NULL, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(27, 6, 27, 26, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL),
(28, 6, 28, 27, 4, '2025-06-10 19:21:23', '2025-06-10 19:21:23', NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `level_tutorials`
--

CREATE TABLE `level_tutorials` (
  `tutorial_id` int NOT NULL,
  `level_id` int NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_example` text COLLATE utf8mb4_unicode_ci,
  `order_index` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `level_tutorials`
--

INSERT INTO `level_tutorials` (`tutorial_id`, `level_id`, `title`, `content`, `code_example`, `order_index`) VALUES
(1, 1, '什麼是變數？', '變數是用來儲存資料的容器，在Python中，你可以透過簡單的賦值語句來創建變數。', 'name = \"Python怪物村\"\nplayer_level = 1\nis_game_on = True', 1),
(2, 1, '變數命名規則', '變數名稱只能包含字母、數字和底線，且不能以數字開頭。變數名稱區分大小寫。', 'valid_name = \"OK\"\n# 1st_name = \"錯誤：不能以數字開頭\"', 2),
(3, 1, '變數的使用', '定義變數後，你可以在程式中使用它們進行各種操作。', 'health = 100\nhealth = health - 10\nprint(\"剩餘血量:\", health)', 3),
(4, 2, '字串基礎', '字串是Python中用於表示文字的資料類型，可以用單引號或雙引號來定義。', 'monster_name = \"字串哥布林\"\ndescription = \'這是一個危險的怪物\'', 1),
(5, 2, '字串操作', '你可以使用+運算符來連接字串，使用*運算符來重複字串。', 'greeting = \"你好\" + \"，冒險者！\"\ncheer = \"加油！\" * 3\nprint(greeting)\nprint(cheer)', 2);

-- --------------------------------------------------------

--
-- 資料表結構 `monsters`
--

CREATE TABLE `monsters` (
  `monster_id` int NOT NULL,
  `hp` int NOT NULL,
  `difficulty` int NOT NULL DEFAULT '1',
  `attack_power` int NOT NULL,
  `exp_reward` int NOT NULL,
  `teaching_point` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_boss` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `monsters`
--

INSERT INTO `monsters` (`monster_id`, `hp`, `difficulty`, `attack_power`, `exp_reward`, `teaching_point`, `is_boss`, `created_at`, `updated_at`) VALUES
(1, 50, 1, 5, 10, 'Python變數宣告與使用', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(2, 100, 2, 8, 20, 'Python字串操作', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(3, 150, 3, 12, 30, 'Python數字與運算', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(4, 200, 4, 15, 40, 'Python布林與條件判斷', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(5, 500, 5, 25, 100, 'Python資料型態綜合運用', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(6, 120, 2, 10, 25, 'Python列表的創建與基本操作', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(7, 150, 2, 12, 30, 'Python列表的切片與遍歷', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(8, 180, 3, 15, 35, 'Python列表的常用方法', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(9, 200, 3, 18, 40, 'Python元組的特性與使用', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(10, 600, 4, 30, 120, 'Python列表與元組的綜合應用', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(11, 220, 3, 20, 45, 'Python字典的創建與基本操作', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(12, 250, 4, 22, 50, 'Python字典的常用方法', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(13, 280, 4, 25, 55, 'Python集合的特性與操作', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(14, 310, 5, 28, 60, 'Python集合的常用方法', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(15, 750, 6, 40, 150, 'Python字典與集合的綜合應用', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(16, 350, 4, 30, 65, 'Python條件判斷(if-else)', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(17, 380, 5, 33, 70, 'Python for迴圈', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(18, 410, 5, 36, 75, 'Python while迴圈', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(19, 450, 6, 39, 80, 'Python流程控制進階技巧', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(20, 900, 7, 50, 180, 'Python流程控制綜合應用', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(21, 500, 6, 42, 85, 'Python函數定義與呼叫', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(22, 550, 6, 45, 90, 'Python函數參數', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(23, 600, 7, 48, 95, 'Python模組與導入', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(24, 650, 7, 51, 100, 'Python函數進階特性', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(25, 1200, 8, 65, 200, 'Python函數與模組綜合挑戰', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(26, 800, 8, 60, 120, 'Python程式優化基礎', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(27, 1000, 9, 70, 150, 'Python高級演算法', 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(28, 1500, 10, 90, 300, '終極Python效能調校大師', 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23');

-- --------------------------------------------------------

--
-- 資料表結構 `players`
--

CREATE TABLE `players` (
  `player_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `completed_levels` json DEFAULT (_utf8mb4'[]'),
  `completed_learning_levels` json DEFAULT (_utf8mb4'[]'),
  `attack_power` int NOT NULL DEFAULT '10',
  `base_hp` int NOT NULL DEFAULT '100',
  `level` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `players`
--

INSERT INTO `players` (`player_id`, `username`, `account`, `password`, `completed_levels`, `completed_learning_levels`, `attack_power`, `base_hp`, `level`, `created_at`, `updated_at`) VALUES
(1, '怪獸獵人', 'hunter', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '[]', '[]', 15, 120, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(2, 'Python新手', 'newbie', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '[]', '[]', 10, 100, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(3, '程式大師', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '[]', '[]', 25, 200, 8, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(4, 'snow', '41241443', '$2y$10$E6hu6vxGkjHkguJw8qVtFuRcNsKeJweM18ULdU1ck3qbk7/1Q.UU2', '[]', '[]', 10, 100, 1, '2025-06-10 19:22:10', '2025-06-10 19:22:10'),
(5, '恩', '41241411', '$2y$10$yZ7HkVqA39zTPofWAhd1NewWDoCkYNf7V20TiiGLNSadDOpGtPpau', '[]', '[]', 10, 100, 1, '2025-06-10 20:01:10', '2025-06-10 20:01:10'),
(6, '邱子晏', 'snow', '$2y$10$001fYtBMCCQR4jbsDaxOROBEq.tHmZwoasKTpfrKDOztfyESSOZG2', '[]', '[]', 10, 100, 1, '2025-06-11 14:56:34', '2025-06-11 14:56:34'),
(7, '綺', '11', '$2y$10$KcMtzGi1SACQLLgpM.I/cOcsd0wEJFrqbaiEEpb8IjkVLxriYIwJ2', '[]', '[]', 10, 100, 10, '2025-06-12 13:15:37', '2025-06-15 00:49:20'),
(8, '邱子晏qq', 'qe', '$2y$10$tQ8tV1SXjf2GOybCL7d7F.LjPPtB5fIgBucRnjnn04cq4DyfRmHde', '[]', '[]', 10, 100, 1, '2025-06-13 09:21:46', '2025-06-13 09:21:46'),
(9, 'qeqe', 'qqq', '$2y$10$KsTb5Nd96sAWrtIc077KTuj9.JWisvk2dDwX.V1eAYogUorlEwriO', '[]', '[]', 10, 100, 1, '2025-06-13 09:22:04', '2025-06-13 09:22:04'),
(10, '123123', '123123', '$2y$10$n722HoMnwTCyLRpr4LNdze9OqV.7Dd0rS4fjkIYy71.XVLv9D4Qjy', '[]', '[]', 10, 100, 1, '2025-06-15 10:10:13', '2025-06-15 10:10:13'),
(11, '我是你爹', 'admin', '$2y$10$o6lWMOlWGNkHGVU3ynwFSeNKAFio0LA/dVkBfzAfqUZ8gRRKZn70m', '[]', '[]', 10, 100, 1, '2025-06-15 14:41:06', '2025-06-15 14:41:06'),
(12, 'Eva', 'Eva', '$2y$10$6jXHIbrNmQZsEZBK6Ps16O5JRlgNLihky4tPc4XV9BT2Ss2t1lre6', '[]', '[]', 10, 100, 1, '2025-06-15 14:45:17', '2025-06-15 14:45:17');

--
-- 觸發器 `players`
--
DELIMITER $$
CREATE TRIGGER `after_player_insert` AFTER INSERT ON `players` FOR EACH ROW BEGIN
    -- 插入所有章節的紀錄，但僅第一章默認解鎖
    INSERT INTO `player_chapter_records` (`player_id`, `chapter_id`, `is_unlocked`)
    SELECT NEW.player_id, chapter_id, IF(chapter_id = 1, TRUE, FALSE)
    FROM `chapters`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- 資料表結構 `player_chapter_records`
--

CREATE TABLE `player_chapter_records` (
  `record_id` int NOT NULL,
  `player_id` int NOT NULL,
  `chapter_id` int NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `is_unlocked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `player_chapter_records`
--

INSERT INTO `player_chapter_records` (`record_id`, `player_id`, `chapter_id`, `is_completed`, `is_unlocked`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 0, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(2, 1, 2, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(3, 1, 3, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(4, 1, 4, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(5, 1, 5, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(6, 1, 6, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(8, 2, 1, 0, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(9, 2, 2, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(10, 2, 3, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(11, 2, 4, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(12, 2, 5, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(13, 2, 6, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(15, 3, 1, 0, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(16, 3, 2, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(17, 3, 3, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(18, 3, 4, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(19, 3, 5, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(20, 3, 6, 0, 0, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(22, 4, 1, 0, 1, '2025-06-10 19:22:10', '2025-06-10 19:22:10'),
(23, 4, 2, 0, 0, '2025-06-10 19:22:10', '2025-06-10 19:22:10'),
(24, 4, 3, 0, 0, '2025-06-10 19:22:10', '2025-06-10 19:22:10'),
(25, 4, 4, 0, 0, '2025-06-10 19:22:10', '2025-06-10 19:22:10'),
(26, 4, 5, 0, 0, '2025-06-10 19:22:10', '2025-06-10 19:22:10'),
(27, 4, 6, 0, 0, '2025-06-10 19:22:10', '2025-06-10 19:22:10'),
(29, 5, 1, 0, 1, '2025-06-10 20:01:10', '2025-06-10 20:01:10'),
(30, 5, 2, 0, 0, '2025-06-10 20:01:10', '2025-06-10 20:01:10'),
(31, 5, 3, 0, 0, '2025-06-10 20:01:10', '2025-06-10 20:01:10'),
(32, 5, 4, 0, 0, '2025-06-10 20:01:10', '2025-06-10 20:01:10'),
(33, 5, 5, 0, 0, '2025-06-10 20:01:10', '2025-06-10 20:01:10'),
(34, 5, 6, 0, 0, '2025-06-10 20:01:10', '2025-06-10 20:01:10'),
(36, 6, 1, 0, 1, '2025-06-11 14:56:34', '2025-06-11 14:56:34'),
(37, 6, 2, 0, 0, '2025-06-11 14:56:34', '2025-06-11 14:56:34'),
(38, 6, 3, 0, 0, '2025-06-11 14:56:34', '2025-06-11 14:56:34'),
(39, 6, 4, 0, 0, '2025-06-11 14:56:34', '2025-06-11 14:56:34'),
(40, 6, 5, 0, 0, '2025-06-11 14:56:34', '2025-06-11 14:56:34'),
(41, 6, 6, 0, 0, '2025-06-11 14:56:34', '2025-06-11 14:56:34'),
(43, 7, 1, 0, 1, '2025-06-12 13:15:37', '2025-06-12 13:15:37'),
(44, 7, 2, 0, 0, '2025-06-12 13:15:37', '2025-06-12 13:15:37'),
(45, 7, 3, 0, 0, '2025-06-12 13:15:37', '2025-06-12 13:15:37'),
(46, 7, 4, 0, 0, '2025-06-12 13:15:37', '2025-06-12 13:15:37'),
(47, 7, 5, 0, 0, '2025-06-12 13:15:37', '2025-06-12 13:15:37'),
(48, 7, 6, 0, 0, '2025-06-12 13:15:37', '2025-06-12 13:15:37'),
(49, 8, 1, 0, 1, '2025-06-13 09:21:46', '2025-06-13 09:21:46'),
(50, 8, 2, 0, 0, '2025-06-13 09:21:46', '2025-06-13 09:21:46'),
(51, 8, 3, 0, 0, '2025-06-13 09:21:46', '2025-06-13 09:21:46'),
(52, 8, 4, 0, 0, '2025-06-13 09:21:46', '2025-06-13 09:21:46'),
(53, 8, 5, 0, 0, '2025-06-13 09:21:46', '2025-06-13 09:21:46'),
(54, 8, 6, 0, 0, '2025-06-13 09:21:46', '2025-06-13 09:21:46'),
(56, 9, 1, 0, 1, '2025-06-13 09:22:04', '2025-06-13 09:22:04'),
(57, 9, 2, 0, 0, '2025-06-13 09:22:04', '2025-06-13 09:22:04'),
(58, 9, 3, 0, 0, '2025-06-13 09:22:04', '2025-06-13 09:22:04'),
(59, 9, 4, 0, 0, '2025-06-13 09:22:04', '2025-06-13 09:22:04'),
(60, 9, 5, 0, 0, '2025-06-13 09:22:04', '2025-06-13 09:22:04'),
(61, 9, 6, 0, 0, '2025-06-13 09:22:04', '2025-06-13 09:22:04'),
(63, 10, 1, 0, 1, '2025-06-15 10:10:13', '2025-06-15 10:10:13'),
(64, 10, 2, 0, 0, '2025-06-15 10:10:13', '2025-06-15 10:10:13'),
(65, 10, 3, 0, 0, '2025-06-15 10:10:13', '2025-06-15 10:10:13'),
(66, 10, 4, 0, 0, '2025-06-15 10:10:13', '2025-06-15 10:10:13'),
(67, 10, 5, 0, 0, '2025-06-15 10:10:13', '2025-06-15 10:10:13'),
(68, 10, 6, 0, 0, '2025-06-15 10:10:13', '2025-06-15 10:10:13'),
(70, 11, 1, 0, 1, '2025-06-15 14:41:06', '2025-06-15 14:41:06'),
(71, 11, 2, 0, 0, '2025-06-15 14:41:06', '2025-06-15 14:41:06'),
(72, 11, 3, 0, 0, '2025-06-15 14:41:06', '2025-06-15 14:41:06'),
(73, 11, 4, 0, 0, '2025-06-15 14:41:06', '2025-06-15 14:41:06'),
(74, 11, 5, 0, 0, '2025-06-15 14:41:06', '2025-06-15 14:41:06'),
(75, 11, 6, 0, 0, '2025-06-15 14:41:06', '2025-06-15 14:41:06'),
(77, 12, 1, 0, 1, '2025-06-15 14:45:17', '2025-06-15 14:45:17'),
(78, 12, 2, 0, 0, '2025-06-15 14:45:17', '2025-06-15 14:45:17'),
(79, 12, 3, 0, 0, '2025-06-15 14:45:17', '2025-06-15 14:45:17'),
(80, 12, 4, 0, 0, '2025-06-15 14:45:17', '2025-06-15 14:45:17'),
(81, 12, 5, 0, 0, '2025-06-15 14:45:17', '2025-06-15 14:45:17'),
(82, 12, 6, 0, 0, '2025-06-15 14:45:17', '2025-06-15 14:45:17');

-- --------------------------------------------------------

--
-- 資料表結構 `player_dungeon_records`
--

CREATE TABLE `player_dungeon_records` (
  `id` int NOT NULL,
  `player_id` int NOT NULL,
  `dungeon_id` int NOT NULL,
  `status` enum('未開始','進行中','已完成') DEFAULT '未開始',
  `progress` int DEFAULT '0',
  `started_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `player_level_records`
--

CREATE TABLE `player_level_records` (
  `record_id` int NOT NULL,
  `player_id` int NOT NULL,
  `level_id` int NOT NULL,
  `attempt_count` int NOT NULL DEFAULT '0',
  `success_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `player_level_records`
--

INSERT INTO `player_level_records` (`record_id`, `player_id`, `level_id`, `attempt_count`, `success_count`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(2, 1, 2, 3, 2, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(3, 1, 3, 5, 3, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(4, 1, 4, 2, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(5, 2, 1, 4, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(6, 3, 1, 1, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(7, 3, 2, 1, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(8, 3, 3, 1, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(9, 3, 4, 1, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(10, 3, 5, 1, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(11, 3, 6, 1, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(12, 3, 7, 1, 1, '2025-06-10 19:21:23', '2025-06-10 19:21:23'),
(13, 4, 1, 0, 0, '2025-06-10 19:22:20', '2025-06-10 19:22:20'),
(14, 6, 1, 0, 0, '2025-06-11 14:58:00', '2025-06-11 14:58:00'),
(15, 9, 1, 0, 0, '2025-06-13 11:07:25', '2025-06-13 11:07:25'),
(16, 7, 1, 0, 0, '2025-06-14 11:28:00', '2025-06-14 11:28:00'),
(17, 12, 1, 0, 0, '2025-06-15 15:11:53', '2025-06-15 15:11:53'),
(18, 11, 1, 0, 0, '2025-06-15 15:16:17', '2025-06-15 15:16:17');

-- --------------------------------------------------------

--
-- 資料表結構 `player_special_effects`
--

CREATE TABLE `player_special_effects` (
  `effect_id` int NOT NULL,
  `hidden_level_id` int NOT NULL,
  `player_id` int NOT NULL,
  `is_unlocked` tinyint(1) NOT NULL DEFAULT '1',
  `unlocked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `player_special_effects`
--

INSERT INTO `player_special_effects` (`effect_id`, `hidden_level_id`, `player_id`, `is_unlocked`, `unlocked_at`) VALUES
(1, 1, 1, 1, '2025-06-10 19:21:23'),
(2, 2, 3, 1, '2025-06-10 19:21:23'),
(3, 3, 3, 1, '2025-06-10 19:21:23');

-- --------------------------------------------------------

--
-- 資料表結構 `teams`
--

CREATE TABLE `teams` (
  `team_id` int NOT NULL,
  `invite_code` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '隊伍邀請碼',
  `max_members` int DEFAULT '4' COMMENT '隊伍上限人數',
  `is_public` tinyint(1) DEFAULT '0' COMMENT '是否公開',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `room_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '房間名稱',
  `room_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dungeon_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `teams`
--

INSERT INTO `teams` (`team_id`, `invite_code`, `max_members`, `is_public`, `created_at`, `updated_at`, `room_name`, `room_password`, `dungeon_id`) VALUES
(68, 'HM8E6ZUL', 4, 0, '2025-06-15 11:59:09', '2025-06-15 11:59:09', 'C', '123', 1),
(69, 'SW6Y5EVD', 4, 1, '2025-06-15 11:59:16', '2025-06-15 11:59:16', 'W', NULL, 1),
(71, 'NTE2U956', 4, 0, '2025-06-15 12:45:06', '2025-06-15 12:45:06', 'ad', '22', 1),
(72, 'SEM7K9ZL', 4, 1, '2025-06-15 13:35:25', '2025-06-15 13:35:25', '123', NULL, 1);

-- --------------------------------------------------------

--
-- 資料表結構 `team_members`
--

CREATE TABLE `team_members` (
  `member_id` int NOT NULL,
  `user_id` int NOT NULL COMMENT '使用者ID',
  `team_id` int NOT NULL COMMENT '隊伍ID',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `team_members`
--

INSERT INTO `team_members` (`member_id`, `user_id`, `team_id`, `joined_at`) VALUES
(53, 10, 68, '2025-06-15 11:59:09'),
(54, 10, 69, '2025-06-15 11:59:16'),
(56, 7, 68, '2025-06-15 12:44:03'),
(59, 10, 71, '2025-06-15 12:45:06'),
(61, 10, 72, '2025-06-15 13:35:25'),
(74, 7, 72, '2025-06-16 05:18:14');

-- --------------------------------------------------------

--
-- 資料表結構 `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int NOT NULL,
  `user_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level_id` int NOT NULL,
  `completed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `level_id`, `completed_at`) VALUES
(1, '11', 1, '2025-06-15 23:26:56');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `chapters`
--
ALTER TABLE `chapters`
  ADD PRIMARY KEY (`chapter_id`);

--
-- 資料表索引 `dungeons`
--
ALTER TABLE `dungeons`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `hidden_levels`
--
ALTER TABLE `hidden_levels`
  ADD PRIMARY KEY (`hidden_level_id`);

--
-- 資料表索引 `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`level_id`),
  ADD KEY `chapter_id` (`chapter_id`),
  ADD KEY `monster_id` (`monster_id`),
  ADD KEY `prerequisite_level_id` (`prerequisite_level_id`),
  ADD KEY `idx_dungeon_id` (`dungeon_id`);

--
-- 資料表索引 `level_tutorials`
--
ALTER TABLE `level_tutorials`
  ADD PRIMARY KEY (`tutorial_id`),
  ADD KEY `level_id` (`level_id`);

--
-- 資料表索引 `monsters`
--
ALTER TABLE `monsters`
  ADD PRIMARY KEY (`monster_id`);

--
-- 資料表索引 `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`player_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `account` (`account`);

--
-- 資料表索引 `player_chapter_records`
--
ALTER TABLE `player_chapter_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `player_chapter_unique` (`player_id`,`chapter_id`),
  ADD KEY `chapter_id` (`chapter_id`);

--
-- 資料表索引 `player_dungeon_records`
--
ALTER TABLE `player_dungeon_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `dungeon_id` (`dungeon_id`);

--
-- 資料表索引 `player_level_records`
--
ALTER TABLE `player_level_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `player_level_unique` (`player_id`,`level_id`),
  ADD KEY `level_id` (`level_id`);

--
-- 資料表索引 `player_special_effects`
--
ALTER TABLE `player_special_effects`
  ADD PRIMARY KEY (`effect_id`),
  ADD UNIQUE KEY `player_hidden_level_unique` (`player_id`,`hidden_level_id`),
  ADD KEY `hidden_level_id` (`hidden_level_id`);

--
-- 資料表索引 `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`team_id`),
  ADD UNIQUE KEY `invite_code` (`invite_code`),
  ADD KEY `idx_team_invite_code` (`invite_code`),
  ADD KEY `idx_team_public` (`is_public`),
  ADD KEY `fk_teams_dungeon` (`dungeon_id`);

--
-- 資料表索引 `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `unique_team_member` (`user_id`,`team_id`) COMMENT '防止同一使用者重複加入同一隊伍',
  ADD KEY `team_id` (`team_id`);

--
-- 資料表索引 `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_level_idx` (`user_id`,`level_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `chapters`
--
ALTER TABLE `chapters`
  MODIFY `chapter_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `dungeons`
--
ALTER TABLE `dungeons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hidden_levels`
--
ALTER TABLE `hidden_levels`
  MODIFY `hidden_level_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `levels`
--
ALTER TABLE `levels`
  MODIFY `level_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `level_tutorials`
--
ALTER TABLE `level_tutorials`
  MODIFY `tutorial_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `monsters`
--
ALTER TABLE `monsters`
  MODIFY `monster_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `players`
--
ALTER TABLE `players`
  MODIFY `player_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `player_chapter_records`
--
ALTER TABLE `player_chapter_records`
  MODIFY `record_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `player_dungeon_records`
--
ALTER TABLE `player_dungeon_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `player_level_records`
--
ALTER TABLE `player_level_records`
  MODIFY `record_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `player_special_effects`
--
ALTER TABLE `player_special_effects`
  MODIFY `effect_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `team_members`
--
ALTER TABLE `team_members`
  MODIFY `member_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `levels`
--
ALTER TABLE `levels`
  ADD CONSTRAINT `levels_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`chapter_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `levels_ibfk_2` FOREIGN KEY (`monster_id`) REFERENCES `monsters` (`monster_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `levels_ibfk_3` FOREIGN KEY (`prerequisite_level_id`) REFERENCES `levels` (`level_id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `level_tutorials`
--
ALTER TABLE `level_tutorials`
  ADD CONSTRAINT `level_tutorials_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `player_chapter_records`
--
ALTER TABLE `player_chapter_records`
  ADD CONSTRAINT `player_chapter_records_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_chapter_records_ibfk_2` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`chapter_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `player_dungeon_records`
--
ALTER TABLE `player_dungeon_records`
  ADD CONSTRAINT `player_dungeon_records_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_dungeon_records_ibfk_2` FOREIGN KEY (`dungeon_id`) REFERENCES `dungeons` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `player_level_records`
--
ALTER TABLE `player_level_records`
  ADD CONSTRAINT `player_level_records_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_level_records_ibfk_2` FOREIGN KEY (`level_id`) REFERENCES `levels` (`level_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `player_special_effects`
--
ALTER TABLE `player_special_effects`
  ADD CONSTRAINT `player_special_effects_ibfk_1` FOREIGN KEY (`hidden_level_id`) REFERENCES `hidden_levels` (`hidden_level_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_special_effects_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_teams_dungeon` FOREIGN KEY (`dungeon_id`) REFERENCES `dungeons` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
