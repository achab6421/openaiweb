-- 初始資料填充腳本

-- 插入示範怪物
INSERT INTO `monsters` (`monster_id`, `hp`, `difficulty`, `attack_power`, `exp_reward`, `teaching_point`, `is_boss`) VALUES
(1, 50, 1, 5, 10, 'Python變數宣告與使用', FALSE),
(2, 100, 2, 8, 20, 'Python字串操作', FALSE),
(3, 150, 3, 12, 30, 'Python數字與運算', FALSE),
(4, 200, 4, 15, 40, 'Python布林與條件判斷', FALSE),
(5, 500, 5, 25, 100, 'Python資料型態綜合運用', TRUE);

-- 插入初始關卡
INSERT INTO `levels` (`level_id`, `chapter_id`, `monster_id`, `prerequisite_level_id`, `wave_count`) VALUES
(1, 1, 1, NULL, 1),
(2, 1, 2, 1, 1),
(3, 1, 3, 2, 2),
(4, 1, 4, 3, 2),
(5, 1, 5, 4, 3);

-- 插入隱藏關卡
INSERT INTO `hidden_levels` (`hidden_level_id`, `answer`, `effect_description`, `is_open`) VALUES
(1, 'python', '增加攻擊力10點', TRUE),
(2, 'monster', '增加基礎血量50點', TRUE);

-- 新增更多的章節
INSERT INTO `chapters` (`chapter_id`, `chapter_name`, `difficulty`, `summary`, `level_count`, `is_open`, `is_hidden`) VALUES
(2, 'Python列表與元組', 2, '學習Python中的列表(List)和元組(Tuple)資料結構，掌握資料集合操作', 5, TRUE, FALSE),
(3, 'Python字典與集合', 3, '探索Python中的字典(Dict)和集合(Set)，理解鍵值對和唯一值的概念', 5, TRUE, FALSE),
(4, 'Python流程控制', 4, '學習條件判斷、迴圈與控制流程，讓程式能依據不同情況做出不同反應', 5, TRUE, FALSE),
(5, 'Python函數與模組', 5, '設計可重用的程式碼片段，理解函數的概念與模組的組織方式', 5, FALSE, FALSE),
(6, '隱藏的知識：Python速度優化', 7, '進階Python性能優化技巧與演算法效率', 3, FALSE, TRUE);

-- 新增更多怪物
INSERT INTO `monsters` (`monster_id`, `hp`, `difficulty`, `attack_power`, `exp_reward`, `teaching_point`, `is_boss`) VALUES
-- 第二章怪物
(6, 120, 2, 10, 25, 'Python列表的創建與基本操作', FALSE),
(7, 150, 2, 12, 30, 'Python列表的切片與遍歷', FALSE),
(8, 180, 3, 15, 35, 'Python列表的常用方法', FALSE),
(9, 200, 3, 18, 40, 'Python元組的特性與使用', FALSE),
(10, 600, 4, 30, 120, 'Python列表與元組的綜合應用', TRUE),

-- 第三章怪物
(11, 220, 3, 20, 45, 'Python字典的創建與基本操作', FALSE),
(12, 250, 4, 22, 50, 'Python字典的常用方法', FALSE),
(13, 280, 4, 25, 55, 'Python集合的特性與操作', FALSE),
(14, 310, 5, 28, 60, 'Python集合的常用方法', FALSE),
(15, 750, 6, 40, 150, 'Python字典與集合的綜合應用', TRUE),

-- 第四章怪物
(16, 350, 4, 30, 65, 'Python條件判斷(if-else)', FALSE),
(17, 380, 5, 33, 70, 'Python for迴圈', FALSE),
(18, 410, 5, 36, 75, 'Python while迴圈', FALSE),
(19, 450, 6, 39, 80, 'Python流程控制進階技巧', FALSE),
(20, 900, 7, 50, 180, 'Python流程控制綜合應用', TRUE),

-- 第五章怪物
(21, 500, 6, 42, 85, 'Python函數定義與呼叫', FALSE),
(22, 550, 6, 45, 90, 'Python函數參數', FALSE),
(23, 600, 7, 48, 95, 'Python模組與導入', FALSE),
(24, 650, 7, 51, 100, 'Python函數進階特性', FALSE),
(25, 1200, 8, 65, 200, 'Python函數與模組綜合挑戰', TRUE),

-- 隱藏章節怪物
(26, 800, 8, 60, 120, 'Python程式優化基礎', FALSE),
(27, 1000, 9, 70, 150, 'Python高級演算法', FALSE),
(28, 1500, 10, 90, 300, '終極Python效能調校大師', TRUE);

-- 新增更多關卡
-- 第二章關卡
INSERT INTO `levels` (`level_id`, `chapter_id`, `monster_id`, `prerequisite_level_id`, `wave_count`) VALUES
(6, 2, 6, 5, 1),
(7, 2, 7, 6, 1),
(8, 2, 8, 7, 2),
(9, 2, 9, 8, 2),
(10, 2, 10, 9, 3),

-- 第三章關卡
(11, 3, 11, 10, 1),
(12, 3, 12, 11, 2),
(13, 3, 13, 12, 2),
(14, 3, 14, 13, 2),
(15, 3, 15, 14, 3),

-- 第四章關卡
(16, 4, 16, 15, 2),
(17, 4, 17, 16, 2),
(18, 4, 18, 17, 2),
(19, 4, 19, 18, 3),
(20, 4, 20, 19, 3),

-- 第五章關卡
(21, 5, 21, 20, 2),
(22, 5, 22, 21, 2),
(23, 5, 23, 22, 3),
(24, 5, 24, 23, 3),
(25, 5, 25, 24, 4),

-- 隱藏章節關卡
(26, 6, 26, NULL, 2),
(27, 6, 27, 26, 3),
(28, 6, 28, 27, 4);

-- 新增更多隱藏關卡
INSERT INTO `hidden_levels` (`hidden_level_id`, `answer`, `effect_description`, `is_open`) VALUES
(3, 'dictionary', '增加攻擊力15點', TRUE),
(4, 'loop', '增加基礎血量75點', TRUE),
(5, 'function', '同時增加攻擊力10點和血量50點', TRUE),
(6, 'pythonmaster', '全屬性增強20%', FALSE);

-- 插入示範玩家
INSERT INTO `players` (`player_id`, `username`, `account`, `password`, `attack_power`, `base_hp`, `level`) VALUES
(1, '怪獸獵人', 'hunter', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 15, 120, 3),
(2, 'Python新手', 'newbie', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10, 100, 1),
(3, '程式大師', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 25, 200, 8);

-- 插入玩家關卡紀錄
INSERT INTO `player_level_records` (`player_id`, `level_id`, `attempt_count`, `success_count`) VALUES
(1, 1, 2, 2),
(1, 2, 3, 2),
(1, 3, 5, 3),
(1, 4, 2, 1),
(2, 1, 4, 1),
(3, 1, 1, 1),
(3, 2, 1, 1),
(3, 3, 1, 1),
(3, 4, 1, 1),
(3, 5, 1, 1),
(3, 6, 1, 1),
(3, 7, 1, 1);

-- 插入玩家特殊效果
INSERT INTO `player_special_effects` (`hidden_level_id`, `player_id`) VALUES
(1, 1),
(2, 3),
(3, 3);
