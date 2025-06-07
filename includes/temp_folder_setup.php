<?php
/**
 * 設置臨時檔案存放結構
 */

// 確保必要目錄存在
function ensureFoldersExist() {
    // 臨時檔案目錄
    $tempDir = dirname(__DIR__) . '/temp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    // 執行緒目錄
    $threadsDir = $tempDir . '/threads';
    if (!file_exists($threadsDir)) {
        mkdir($threadsDir, 0777, true);
    }
    
    // 爬蟲挑戰目錄
    $crawlerDir = $tempDir . '/crawler';
    if (!file_exists($crawlerDir)) {
        mkdir($crawlerDir, 0777, true);
    }
    
    // 確保目錄可寫
    chmod($tempDir, 0777);
    chmod($threadsDir, 0777);
    chmod($crawlerDir, 0777);
}

// 執行目錄檢查和創建
ensureFoldersExist();
?>
