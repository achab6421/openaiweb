<?php
// 網站基本設定
define('SITE_NAME', 'Python 怪物村：AI 助教教你寫程式打怪獸！');
define('SITE_VERSION', '1.0.0');

// API設定
define('USE_OPENAI_API', true);

// 爬蟲挑戰設定
define('CRAWLER_LOCATIONS', [
    1 => [
        'name' => '數據森林', 
        'theme' => 'Python數據分析與資料處理', 
        'difficulty' => '初階', 
        'required_level' => 1
    ],
    2 => [
        'name' => '字典城市', 
        'theme' => 'Python字典與JSON處理', 
        'difficulty' => '中階', 
        'required_level' => 3
    ],
    3 => [
        'name' => '模組山脈', 
        'theme' => 'Python模組與套件', 
        'difficulty' => '中階', 
        'required_level' => 6
    ],
    4 => [
        'name' => '異常洞窟', 
        'theme' => 'Python異常處理與除錯', 
        'difficulty' => '高階', 
        'required_level' => 8
    ]
]);

// 目錄設定
define('TEMP_DIR', dirname(__DIR__) . '/temp');
define('ASSETS_DIR', dirname(__DIR__) . '/assets');
?>
