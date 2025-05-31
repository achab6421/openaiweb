-- 創建數據庫
CREATE DATABASE IF NOT EXISTS python_teaching CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用數據庫
USE python_teaching;

-- 創建使用者表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    current_level INT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入一些測試用戶
INSERT INTO users (username, password, current_level) VALUES 
('test', '$2y$10$4RB7a.FXOzRx7AJgFL4IyuvTm3W4Aop1ov6H20TALYd9dAgtwKJte', 1),
('admin', '$2y$10$4RB7a.FXOzRx7AJgFL4IyuvTm3W4Aop1ov6H20TALYd9dAgtwKJte', 5);
-- 上面兩個用戶的密碼都是 "password"
