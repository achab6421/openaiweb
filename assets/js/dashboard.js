// 儀表板功能

document.addEventListener('DOMContentLoaded', function() {
    // 繪製章節之間的連接路徑
    drawChapterPaths();
    
    // 添加章節卡片懸停效果
    setupChapterHoverEffects();
    
    // 動態調整SVG視圖大小
    adjustSvgViewbox();
    
    // 視窗調整大小時重新繪製路徑
    window.addEventListener('resize', function() {
        clearSvgPaths();
        drawChapterPaths();
        adjustSvgViewbox();
    });
});

/**
 * 清除SVG中的所有路徑
 */
function clearSvgPaths() {
    const svg = document.querySelector('.chapters-grid svg');
    while (svg.firstChild) {
        svg.removeChild(svg.firstChild);
    }
}

/**
 * 調整SVG視圖大小以匹配容器
 */
function adjustSvgViewbox() {
    const chaptersGrid = document.querySelector('.chapters-grid');
    const svg = chaptersGrid.querySelector('svg');
    
    if (chaptersGrid && svg) {
        const rect = chaptersGrid.getBoundingClientRect();
        svg.setAttribute('viewBox', `0 0 ${rect.width} ${rect.height}`);
        svg.setAttribute('width', rect.width);
        svg.setAttribute('height', rect.height);
    }
}

/**
 * 繪製章節之間的連接路徑
 */
function drawChapterPaths() {
    const chaptersGrid = document.querySelector('.chapters-grid');
    const svg = chaptersGrid.querySelector('svg');
    const chapterCards = document.querySelectorAll('.chapter-card');
    
    if (chapterCards.length < 2) return;
    
    const pathData = [];
    
    // 獲取每個章節卡片的位置
    const cardPositions = [];
    chapterCards.forEach(card => {
        const rect = card.getBoundingClientRect();
        const gridRect = chaptersGrid.getBoundingClientRect();
        
        // 計算相對於 chaptersGrid 的位置
        cardPositions.push({
            x: rect.left - gridRect.left + rect.width / 2,
            y: rect.top - gridRect.top + rect.height / 2,
            isUnlocked: card.classList.contains('unlocked')
        });
    });
    
    // 連接連續章節卡片
    for (let i = 0; i < cardPositions.length - 1; i++) {
        const current = cardPositions[i];
        const next = cardPositions[i + 1];
        
        // 創建路徑
        drawPath(svg, current, next, current.isUnlocked);
        
        // 如果是奇數行，還需要連接到下一行的起點
        if (i % 2 === 0 && i + 2 < cardPositions.length) {
            const nextRow = cardPositions[i + 2];
            if (current.isUnlocked && next.isUnlocked) {
                drawCornerPath(svg, next, nextRow, next.isUnlocked);
            }
        }
    }
    
    // 添加動畫
    animatePaths();
}

/**
 * 繪製兩點間的直接路徑
 */
function drawPath(svg, start, end, isUnlocked) {
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    
    // 計算控制點，使路徑有些彎曲
    const midX = (start.x + end.x) / 2;
    const midY = (start.y + end.y) / 2;
    const offsetX = (end.x - start.x) * 0.2;
    const offsetY = (end.y - start.y) * 0.2;
    
    // 彎曲的路徑
    const d = `M ${start.x} ${start.y} Q ${midX + offsetX} ${midY - offsetY}, ${end.x} ${end.y}`;
    
    // 設置路徑屬性
    path.setAttribute('d', d);
    path.setAttribute('class', 'path-line');
    
    // 如果未解鎖，使用灰色
    if (!isUnlocked) {
        path.setAttribute('stroke', '#555');
        path.setAttribute('stroke-opacity', '0.3');
    }
    
    // 添加到SVG
    svg.appendChild(path);
    
    return path;
}

/**
 * 繪製轉角路徑，用於連接行與行
 */
function drawCornerPath(svg, start, end, isUnlocked) {
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    
    // 計算控制點，創建S形彎曲
    const controlX1 = start.x;
    const controlY1 = (start.y + end.y) / 2;
    const controlX2 = end.x;
    const controlY2 = (start.y + end.y) / 2;
    
    // S形彎曲路徑
    const d = `M ${start.x} ${start.y} C ${controlX1} ${controlY1}, ${controlX2} ${controlY2}, ${end.x} ${end.y}`;
    
    // 設置路徑屬性
    path.setAttribute('d', d);
    path.setAttribute('class', 'path-line');
    
    // 如果未解鎖，使用灰色
    if (!isUnlocked) {
        path.setAttribute('stroke', '#555');
        path.setAttribute('stroke-opacity', '0.3');
    }
    
    // 添加到SVG
    svg.appendChild(path);
    
    return path;
}

/**
 * 為所有路徑添加動畫
 */
function animatePaths() {
    const paths = document.querySelectorAll('.path-line');
    
    paths.forEach((path, index) => {
        const length = path.getTotalLength();
        
        // 設置初始狀態
        path.style.strokeDasharray = length;
        path.style.strokeDashoffset = length;
        
        // 設置延遲動畫，每條路徑有不同的延遲
        path.style.animation = `dash 2s ease-in-out ${index * 0.3}s forwards`;
    });
    
    // 添加動畫關鍵幀
    if (!document.getElementById('path-animation')) {
        const style = document.createElement('style');
        style.id = 'path-animation';
        style.textContent = `
            @keyframes dash {
                to {
                    stroke-dashoffset: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * 設置章節卡片懸停效果
 */
function setupChapterHoverEffects() {
    const chapterCards = document.querySelectorAll('.chapter-card.unlocked');
    
    chapterCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            // 添加浮動效果
            this.style.transform = 'translateY(-8px) rotateX(5deg)';
            this.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.7)';
            
            // 高亮進度條
            const progressBar = this.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.boxShadow = '0 0 10px var(--highlight)';
                progressBar.style.filter = 'brightness(1.2)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            // 恢復正常狀態
            this.style.transform = '';
            this.style.boxShadow = '';
            
            // 恢復進度條
            const progressBar = this.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.boxShadow = '';
                progressBar.style.filter = '';
            }
        });
    });
}
