document.addEventListener('DOMContentLoaded', function() {
    // 關卡項目的點擊處理
    setupQuestItemClicks();
    
    // 為統計條設置動畫效果
    animateStatBars();
    
    // 怪物圖片懸停效果
    setupMonsterImageEffects();
});

/**
 * 設置關卡項目的點擊處理
 */
function setupQuestItemClicks() {
    const questItems = document.querySelectorAll('.quest-item-content');
    
    questItems.forEach(item => {
        item.addEventListener('click', function() {
            const questItem = this.closest('.quest-item');
            const levelId = questItem.dataset.levelId;
            const chapterId = new URLSearchParams(window.location.search).get('id');
            
            if (!questItem.classList.contains('locked')) {
                window.location.href = `?id=${chapterId}&level_id=${levelId}`;
            }
        });
        
        item.addEventListener('mouseenter', function() {
            const questItem = this.closest('.quest-item');
            if (!questItem.classList.contains('locked') && !questItem.classList.contains('selected')) {
                questItem.style.transform = 'translateX(-5px)';
                questItem.style.boxShadow = '0 0 10px rgba(255, 64, 0, 0.3)';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            const questItem = this.closest('.quest-item');
            if (!questItem.classList.contains('selected')) {
                questItem.style.transform = '';
                questItem.style.boxShadow = '';
            }
        });
    });
}

/**
 * 為統計條設置動畫效果
 */
function animateStatBars() {
    const statFills = document.querySelectorAll('.stat-fill');
    
    statFills.forEach(fill => {
        const originalWidth = fill.style.width;
        fill.style.width = '0';
        
        // 觸發重繪
        void fill.offsetWidth;
        
        // 設置過渡動畫
        fill.style.transition = 'width 1s ease-out';
        fill.style.width = originalWidth;
    });
}

/**
 * 怪物圖片懸停效果
 */
function setupMonsterImageEffects() {
    const monsterImage = document.querySelector('.monster-image');
    
    if (monsterImage) {
        monsterImage.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        monsterImage.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    }
}

/**
 * 添加頁面載入動畫效果
 */
window.addEventListener('load', function() {
    const monsterPanel = document.querySelector('.monster-info-panel');
    const questPanel = document.querySelector('.quest-list-panel');
    
    if (monsterPanel && questPanel) {
        // 先設置透明
        monsterPanel.style.opacity = '0';
        questPanel.style.opacity = '0';
        monsterPanel.style.transform = 'translateX(-20px)';
        questPanel.style.transform = 'translateX(20px)';
        
        // 設置過渡
        monsterPanel.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        questPanel.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        // 觸發重繪
        void monsterPanel.offsetWidth;
        void questPanel.offsetWidth;
        
        // 顯示元素
        setTimeout(() => {
            monsterPanel.style.opacity = '1';
            monsterPanel.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            questPanel.style.opacity = '1';
            questPanel.style.transform = 'translateX(0)';
        }, 300);
    }
});
