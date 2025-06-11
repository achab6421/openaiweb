/**
 * 題目格式化工具
 * 專注於格式化挑戰題目部分
 */
class ProblemFormatter {
    /**
     * 將純文本題目轉換為HTML
     * @param {string} problemText 原始問題文本
     * @return {string} 格式化的HTML
     */
    static formatProblem(problemText) {
        if (!problemText) return '';
        
        // 直接將文本作為挑戰題目內容處理
        let html = `
            <div class="challenge-title">挑戰題目</div>
            <div class="challenge-content">
                ${this.formatContent(problemText)}
            </div>
        `;
        
        return html;
    }
    
    /**
     * 格式化題目內容
     * @param {string} content 題目內容
     * @return {string} 格式化的HTML
     */
    static formatContent(content) {
        // 檢測和格式化代碼區塊
        content = this.formatCodeBlocks(content);
        
        // 格式化段落
        content = content.split('\n\n').map(paragraph => {
            // 忽略空段落
            if (!paragraph.trim()) return '';
            
            // 檢查是否為代碼區塊（已處理過）
            if (paragraph.includes('<pre class="code-block">')) {
                return paragraph;
            }
            
            // 檢查是否為列表（項目符號或數字列表）
            if (paragraph.match(/^[-•*]\s/m) || paragraph.match(/^\d+\.\s/m)) {
                return this.formatList(paragraph);
            }
            
            // 一般段落
            return `<p>${paragraph.replace(/\n/g, '<br>')}</p>`;
        }).join('');
        
        return content;
    }
    
    /**
     * 格式化代碼區塊
     * @param {string} content 原始內容
     * @return {string} 處理後的內容
     */
    static formatCodeBlocks(content) {
        // 檢測 Python 代碼區塊，可能有 ```python 或單純的 ``` 標記
        const codeBlockRegex = /```(?:python)?([\s\S]*?)```/g;
        
        return content.replace(codeBlockRegex, function(match, code) {
            return `<pre class="code-block"><code>${ProblemFormatter.escapeHtml(code.trim())}</code></pre>`;
        });
    }
    
    /**
     * 格式化列表
     * @param {string} paragraph 列表段落
     * @return {string} 格式化的HTML列表
     */
    static formatList(paragraph) {
        // 檢查是數字列表還是項目符號列表
        const isNumberedList = paragraph.match(/^\d+\.\s/m);
        const listType = isNumberedList ? 'ol' : 'ul';
        
        // 分割為列表項
        const items = paragraph.split('\n').map(line => {
            line = line.trim();
            if (!line) return '';
            
            // 移除列表標記
            if (isNumberedList) {
                line = line.replace(/^\d+\.\s/, '');
            } else {
                line = line.replace(/^[-•*]\s/, '');
            }
            
            return `<li>${line}</li>`;
        }).filter(item => item !== '').join('');
        
        return `<${listType} class="challenge-list">${items}</${listType}>`;
    }
    
    /**
     * HTML 轉義
     * @param {string} text 原始文本
     * @return {string} 轉義後的文本
     */
    static escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}
