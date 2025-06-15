// 跟章節頁一樣的紅色貝茲曲線連接副本卡片

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(drawDungeonPaths, 100); // 延遲繪製，確保 DOM 位置正確
    window.addEventListener('resize', function () {
        clearDungeonPaths();
        setTimeout(drawDungeonPaths, 100);
    });
});

function clearDungeonPaths() {
    const svg = document.getElementById('dungeonPathSvg');
    if (svg) svg.innerHTML = '';
}

function adjustDungeonSvgViewbox() {
    const grid = document.getElementById('dungeonsGrid');
    const svg = document.getElementById('dungeonPathSvg');
    if (grid && svg) {
        const rect = grid.getBoundingClientRect();
        svg.setAttribute('viewBox', `0 0 ${rect.width} ${rect.height}`);
        svg.setAttribute('width', rect.width);
        svg.setAttribute('height', rect.height);
    }
}

function drawDungeonPaths() {
    const grid = document.getElementById('dungeonsGrid');
    const svg = document.getElementById('dungeonPathSvg');
    if (!grid || !svg) return;
    svg.innerHTML = '';
    adjustDungeonSvgViewbox();

    // 取得每排的卡片
    const row0 = document.querySelectorAll('.dungeon-row[data-row="0"] .dungeon-card');
    const row1 = document.querySelectorAll('.dungeon-row[data-row="1"] .dungeon-card');
    const row2 = document.querySelectorAll('.dungeon-row[data-row="2"] .dungeon-card');

    // 取得相對於 grid 的中心點
    function getCenter(card) {
        const rect = card.getBoundingClientRect();
        const gridRect = grid.getBoundingClientRect();
        return {
            x: rect.left - gridRect.left + rect.width / 2,
            y: rect.top - gridRect.top + rect.height / 2
        };
    }

    // row0右 -> row2
    if (row0.length === 2 && row2.length === 1) {
        const right0 = row0[1];
        const center2 = row2[0];
        const p1 = getCenter(right0);
        const p2 = getCenter(center2);
        drawBezierPath(svg, p1, p2);
    }
    // row1左 -> row2
    if (row1.length === 1 && row2.length === 1) {
        const left1 = row1[0];
        const center2 = row2[0];
        const p1 = getCenter(left1);
        const p2 = getCenter(center2);
        drawBezierPath(svg, p1, p2);
    }
    // row1右 -> row2
    if (row1.length === 2 && row2.length === 1) {
        const right1 = row1[1];
        const center2 = row2[0];
        const p1 = getCenter(right1);
        const p2 = getCenter(center2);
        drawBezierPath(svg, p1, p2);
    }
}

function drawBezierPath(svg, start, end) {
    // 跟章節一樣的彎曲貝茲曲線
    const offsetY = (end.y - start.y) * 0.2;
    const offsetX = (end.x - start.x) * 0.2;
    const midX = (start.x + end.x) / 2;
    const midY = (start.y + end.y) / 2;
    const d = `M ${start.x} ${start.y} Q ${midX + offsetX} ${midY - offsetY}, ${end.x} ${end.y}`;
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', d);
    path.setAttribute('stroke', '#ff4000');
    path.setAttribute('stroke-width', '4');
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('class', 'dungeon-path-curve');
    svg.appendChild(path);

    // 動畫
    const length = path.getTotalLength();
    path.style.strokeDasharray = length;
    path.style.strokeDashoffset = length;
    path.style.animation = `dash 1.2s ease-in-out forwards`;
    if (!document.getElementById('dungeon-path-anim-style')) {
        const style = document.createElement('style');
        style.id = 'dungeon-path-anim-style';
        style.textContent = `
            @keyframes dash {
                to { stroke-dashoffset: 0; }
            }
        `;
        document.head.appendChild(style);
    }
}
    