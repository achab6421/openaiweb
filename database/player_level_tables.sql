-- 創建玩家等級經驗表
CREATE TABLE IF NOT EXISTS `player_level_experience` (
  `level` INT PRIMARY KEY,
  `required_exp` INT NOT NULL,  -- 到達該等級所需總經驗
  `level_attribute_bonus` FLOAT NOT NULL DEFAULT 1.05,  -- 升級時能力提升比例
  `description` VARCHAR(100)  -- 等級描述
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入玩家等級經驗數據（1-50級）
INSERT INTO `player_level_experience` (`level`, `required_exp`, `level_attribute_bonus`, `description`) VALUES
(1, 0, 1.00, '初學者'),
(2, 100, 1.05, '見習程式員'),
(3, 250, 1.05, '初級程式員'),
(4, 450, 1.05, '業餘程式員'),
(5, 700, 1.08, '進階程式員'),
(6, 1000, 1.08, '熟練程式員'),
(7, 1400, 1.08, '專業程式員'),
(8, 1900, 1.08, '資深程式員'),
(9, 2500, 1.10, '專家程式員'),
(10, 3200, 1.10, '程式大師'),
(11, 4000, 1.08, '程式魔法師'),
(12, 4900, 1.08, '程式奇才'),
(13, 5900, 1.08, '程式巫師'),
(14, 7000, 1.08, '程式煉金師'),
(15, 8200, 1.10, '程式賢者'),
(16, 9500, 1.08, '代碼工匠'),
(17, 10900, 1.08, '代碼建築師'),
(18, 12400, 1.08, '演算法專家'),
(19, 14000, 1.08, '演算法大師'),
(20, 15700, 1.12, '代碼傳奇'),
(21, 17500, 1.08, '程式領主'),
(22, 19400, 1.08, '代碼領主'),
(23, 21400, 1.08, '程式霸主'),
(24, 23500, 1.08, '代碼霸主'),
(25, 25700, 1.10, '程式王者'),
(26, 28000, 1.08, '代碼王者'),
(27, 30400, 1.08, '程式宗師'),
(28, 32900, 1.08, '代碼宗師'),
(29, 35500, 1.08, '編程皇帝'),
(30, 38200, 1.15, '程式之神'),
(31, 41000, 1.08, '神級程式員'),
(32, 44000, 1.08, '神級碼農'),
(33, 47200, 1.08, '程式傀儡師'),
(34, 50600, 1.08, '程式支配者'),
(35, 54200, 1.10, '程式創造者'),
(36, 58000, 1.08, '程式元素師'),
(37, 62000, 1.08, '代碼咒術師'),
(38, 66200, 1.08, '代碼審判官'),
(39, 70600, 1.08, '代碼執行者'),
(40, 75200, 1.12, '代碼之王'),
(41, 80000, 1.08, '程式帝王'),
(42, 85000, 1.08, '代碼帝王'),
(43, 90200, 1.08, '程式帝尊'),
(44, 95600, 1.08, '代碼帝尊'),
(45, 101200, 1.10, '程式聖主'),
(46, 107000, 1.08, '代碼聖主'),
(47, 113000, 1.08, '程式天尊'),
(48, 119200, 1.08, '代碼天尊'),
(49, 125600, 1.08, '程式主宰'),
(50, 132300, 1.15, '終極程式師');

-- 添加 experience 列到 players 表（如果尚未添加）
SET @experienceColumnExists = 0;
SELECT COUNT(*) INTO @experienceColumnExists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'players' AND COLUMN_NAME = 'experience';

SET @alterStmt = IF(@experienceColumnExists = 0, 
                    'ALTER TABLE players ADD COLUMN experience INT NOT NULL DEFAULT 0;', 
                    'SELECT "experience column already exists" AS message;');

PREPARE stmt FROM @alterStmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
