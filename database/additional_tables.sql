-- 創建關卡教學內容表
CREATE TABLE IF NOT EXISTS `level_tutorials` (
  `tutorial_id` INT AUTO_INCREMENT PRIMARY KEY,
  `level_id` INT NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `content` TEXT NOT NULL,
  `code_example` TEXT,
  `order_index` INT NOT NULL DEFAULT 1,
  FOREIGN KEY (`level_id`) REFERENCES `levels`(`level_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入示例教學內容
INSERT INTO `level_tutorials` (`level_id`, `title`, `content`, `code_example`, `order_index`) VALUES
-- 第一關：變數教學
(1, '什麼是變數？', '變數是用來儲存資料的容器，在Python中，你可以透過簡單的賦值語句來創建變數。', 'name = "Python怪物村"\nplayer_level = 1\nis_game_on = True', 1),
(1, '變數命名規則', '變數名稱只能包含字母、數字和底線，且不能以數字開頭。變數名稱區分大小寫。', 'valid_name = "OK"\n# 1st_name = "錯誤：不能以數字開頭"', 2),
(1, '變數的使用', '定義變數後，你可以在程式中使用它們進行各種操作。', 'health = 100\nhealth = health - 10\nprint("剩餘血量:", health)', 3),

-- 第二關：字串教學
(2, '字串基礎', '字串是Python中用於表示文字的資料類型，可以用單引號或雙引號來定義。', 'monster_name = "字串哥布林"\ndescription = \'這是一個危險的怪物\'', 1),
(2, '字串操作', '你可以使用+運算符來連接字串，使用*運算符來重複字串。', 'greeting = "你好" + "，冒險者！"\ncheer = "加油！" * 3\nprint(greeting)\nprint(cheer)', 2);
