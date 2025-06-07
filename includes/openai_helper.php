<?php
/**
 * OpenAI API 整合助手
 * 負責處理與OpenAI API通訊的功能
 */

require_once dirname(__DIR__) . '/config/settings.php';

// 載入環境變數
function loadEnvVariables() {
    $env_file = dirname(__DIR__) . '/game/.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '//') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim(trim($value), '"\'');
                if (!empty($key)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }
}

loadEnvVariables();

/**
 * 創建假網頁與隱藏答案
 * 
 * @param string $theme 主題
 * @param string $difficulty 難度
 * @return array 生成的網頁內容和隱藏答案
 */
function generateFakeWebpage($theme, $difficulty) {
    if (!USE_OPENAI_API) {
        // 如果不使用API，返回預設頁面
        return generateDefaultWebpage($theme, $difficulty);
    }
    
    // 隨機生成一個答案
    $answer = 'ans_' . substr(md5(rand()), 0, 6);
    
    // 建立HTML模板
    switch($difficulty) {
        case '高階':
            $html = generateAdvancedWebpage($theme, $answer);
            break;
        case '中階':
            $html = generateIntermediateWebpage($theme, $answer);
            break;
        default:
            $html = generateBeginnerWebpage($theme, $answer);
    }
    
    return [
        'html' => $html,
        'answer' => $answer
    ];
}

/**
 * 生成初階難度的假網頁
 */
function generateBeginnerWebpage($theme, $answer) {
    $html = '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($theme) . ' 學習資源</title>
    <meta name="description" content="學習Python ' . htmlspecialchars($theme) . '的重要資源">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        header { background: #3498db; color: white; padding: 20px; text-align: center; }
        nav { background: #f8f9fa; padding: 10px; }
        nav ul { list-style-type: none; padding: 0; display: flex; justify-content: center; }
        nav ul li { margin: 0 15px; }
        nav ul li a { text-decoration: none; color: #333; }
        .container { max-width: 1100px; margin: 20px auto; }
        .info-box { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        footer { background: #f8f9fa; padding: 20px; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <header>
        <h1>' . htmlspecialchars($theme) . '</h1>
        <p>Python程式教學資源</p>
    </header>
    
    <nav>
        <ul>
            <li><a href="#intro">介紹</a></li>
            <li><a href="#resources">學習資源</a></li>
            <li><a href="#examples">範例</a></li>
            <li><a href="#contact">聯絡我們</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <section id="intro">
            <h2>介紹</h2>
            <p>Python是一種易學且功能強大的程式語言。' . htmlspecialchars($theme) . '是Python中非常重要的一部分。</p>
            <div class="info-box">
                <h3>為什麼學習' . htmlspecialchars($theme) . '？</h3>
                <p>掌握' . htmlspecialchars($theme) . '可以幫助你更有效地處理資料和解決問題。</p>
                <!-- 答案：' . $answer . ' -->
            </div>
        </section>
        
        <section id="resources">
            <h2>學習資源</h2>
            <ul>
                <li><a href="#">Python官方文檔</a></li>
                <li><a href="#">線上教學課程</a></li>
                <li><a href="#">推薦書籍</a></li>
            </ul>
        </section>
        
        <section id="examples">
            <h2>範例程式碼</h2>
            <pre><code>
# 這是一個簡單的Python範例
def hello_world():
    print("Hello, World!")
    
hello_world()
            </code></pre>
        </section>
        
        <section id="contact">
            <h2>聯絡我們</h2>
            <p>如有任何問題，請發送郵件至：<a href="mailto:example@python.org">example@python.org</a></p>
        </section>
    </div>
    
    <footer>
        <p>&copy; 2023 Python教學資源網站</p>
    </footer>
</body>
</html>';

    return $html;
}

/**
 * 生成中階難度的假網頁
 */
function generateIntermediateWebpage($theme, $answer) {
    $encodedAnswer = base64_encode($answer);
    
    $html = '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($theme) . ' - 進階學習</title>
    <meta name="description" content="深入學習Python ' . htmlspecialchars($theme) . '的進階資源" data-secret="' . $encodedAnswer . '">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; line-height: 1.6; color: #333; }
        header { background: linear-gradient(45deg, #2c3e50, #3498db); color: white; padding: 30px 20px; text-align: center; }
        nav { background: #f8f9fa; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        nav ul { list-style-type: none; padding: 0; margin: 0; display: flex; justify-content: center; }
        nav ul li { margin: 0; }
        nav ul li a { display: block; padding: 15px 25px; text-decoration: none; color: #333; transition: all 0.3s; }
        nav ul li a:hover { background: #e9ecef; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .code-block { background: #f8f9fa; border-left: 4px solid #3498db; padding: 15px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 12px; text-align: left; }
        th { background: #f8f9fa; }
        footer { background: #2c3e50; color: white; padding: 20px; text-align: center; }
    </style>
</head>
<body>
    <header>
        <h1>' . htmlspecialchars($theme) . ' - 進階學習指南</h1>
        <p>掌握Python進階技能，成為資料處理專家</p>
    </header>
    
    <nav>
        <ul>
            <li><a href="#concepts">核心概念</a></li>
            <li><a href="#techniques">進階技巧</a></li>
            <li><a href="#examples">實例分析</a></li>
            <li><a href="#resources">推薦資源</a></li>
            <li><a href="#faq">常見問題</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <section id="concepts">
            <h2>核心概念</h2>
            <div class="card">
                <h3>理解' . htmlspecialchars($theme) . '的基本原理</h3>
                <p>在深入學習進階技巧之前，確保你完全理解了這些核心概念。</p>
                <ul>
                    <li>基本數據結構與操作</li>
                    <li>效能考量與最佳實踐</li>
                    <li>常見錯誤與解決方案</li>
                </ul>
            </div>
        </section>
        
        <section id="techniques">
            <h2>進階技巧</h2>
            <div class="card">
                <h3>優化程式效能</h3>
                <p>學習如何撰寫高效的Python程式，提升處理效率。</p>
                <div class="code-block">
<pre>
# 高效率程式碼範例
import time

def optimized_function(data):
    start_time = time.time()
    # 實際處理邏輯
    result = process_data(data)
    end_time = time.time()
    
    print(f"處理時間: {end_time - start_time:.4f} 秒")
    return result
</pre>
                </div>
            </div>
        </section>
        
        <section id="examples">
            <h2>實例分析</h2>
            <div class="card">
                <h3>實際案例研究</h3>
                <p>透過實際案例學習如何應用' . htmlspecialchars($theme) . '解決問題。</p>
                <table>
                    <tr>
                        <th>案例</th>
                        <th>應用領域</th>
                        <th>關鍵技術</th>
                        <th>成效</th>
                    </tr>
                    <tr>
                        <td>資料分析系統</td>
                        <td>金融科技</td>
                        <td>數據清理、統計分析</td>
                        <td>提升效率30%</td>
                    </tr>
                    <tr>
                        <td>自動化報表</td>
                        <td>企業管理</td>
                        <td>資料彙整、視覺化</td>
                        <td>節省90%人工時間</td>
                    </tr>
                    <!-- 隱藏資訊: ' . $answer . ' -->
                </table>
            </div>
        </section>
        
        <section id="resources">
            <h2>推薦資源</h2>
            <div class="card">
                <h3>進階學習材料</h3>
                <ul>
                    <li>《Python進階程式設計》</li>
                    <li>《資料科學實戰》</li>
                    <li>線上課程：<a href="#">Python專家系列</a></li>
                    <li>社群討論：<a href="#">Python進階論壇</a></li>
                </ul>
            </div>
        </section>
        
        <section id="faq">
            <h2>常見問題</h2>
            <div class="card">
                <h3>FAQ</h3>
                <dl>
                    <dt>如何解決記憶體溢出問題？</dt>
                    <dd>使用生成器、分批處理資料或最佳化資料結構可以有效減少記憶體使用。</dd>
                    
                    <dt>如何提高程式處理速度？</dt>
                    <dd>使用向量化操作、並行處理或編譯型擴展如Cython可以大幅提升效能。</dd>
                </dl>
            </div>
        </section>
    </div>
    
    <footer>
        <div>
            <p>&copy; 2023 Python進階學習平台 | <a href="#" style="color:white">隱私政策</a> | <a href="#" style="color:white">使用條款</a></p>
        </div>
    </footer>
    
    <!-- 網站統計追蹤 -->
    <script>
        // 分析使用者行為
        console.log("頁面已載入完成");
        /* 
           進階學習者可能會查看這裡
           運用你的知識解讀這段訊息：
           ' . $encodedAnswer . '
        */
    </script>
</body>
</html>';

    return $html;
}

/**
 * 生成高階難度的假網頁
 */
function generateAdvancedWebpage($theme, $answer) {
    // 多層次加密答案
    $reversedAnswer = strrev($answer);
    $encodedAnswer = base64_encode($reversedAnswer);
    $parts = str_split($encodedAnswer, 2);
    $shuffledParts = array_reverse($parts);
    $finalEncoded = implode('', $shuffledParts);
    
    $html = '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($theme) . ' - 專家指南</title>
    <meta name="description" content="Python ' . htmlspecialchars($theme) . ' 專家級學習資源">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="Python, ' . htmlspecialchars($theme) . ', 程式設計, 專家技巧">
    <meta name="author" content="Python Expert Team">
    <meta name="robots" content="index, follow">
    <meta name="revisit-after" content="7 days">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <!-- 安全挑戰：解密這段資料 key="' . $finalEncoded . '" -->
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --text: #333;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background-color: #f5f5f5;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        header .pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: url("data:image/svg+xml,%3Csvg width=\'100\' height=\'100\' viewBox=\'0 0 100 100\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z\' fill=\'%23ffffff\' fill-opacity=\'1\' fill-rule=\'evenodd\'/%3E%3C/svg%3E");
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 0;
        }
        
        nav {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        nav ul li a {
            display: block;
            padding: 1rem 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }
        
        nav ul li a::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background-color: var(--secondary);
            transition: width 0.3s;
        }
        
        nav ul li a:hover::after {
            width: 80%;
        }
        
        section {
            margin-bottom: 3rem;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        h2 {
            font-size: 2rem;
            border-bottom: 2px solid var(--light-bg);
            padding-bottom: 0.5rem;
            margin-top: 2rem;
        }
        
        p {
            margin-bottom: 1rem;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .code-block {
            background-color: var(--dark-bg);
            color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 5px;
            overflow-x: auto;
            font-family: "Courier New", Courier, monospace;
            margin: 1rem 0;
            position: relative;
        }
        
        .code-block::before {
            content: "Python Code";
            position: absolute;
            top: 0;
            right: 0;
            background-color: var(--accent);
            color: white;
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            border-bottom-left-radius: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th {
            background-color: var(--light-bg);
            padding: 0.8rem;
            text-align: left;
        }
        
        td {
            padding: 0.8rem;
        }
        
        .tip-box {
            background-color: rgba(52, 152, 219, 0.1);
            border-left: 4px solid var(--secondary);
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 5px 5px 0;
        }
        
        .warning-box {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--accent);
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 5px 5px 0;
        }
        
        footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin: 1rem 0;
        }
        
        .footer-links a {
            color: #ddd;
            margin: 0 1rem;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .card-header {
                font-size: 1rem;
            }
        }
        
        .hidden-content {
            display: none;
        }
    </style>
</head>

<body>
    <header>
        <div class="pattern"></div>
        <div class="container">
            <h1>' . htmlspecialchars($theme) . ' - 專家指南</h1>
            <p>掌握Python最強大的技術，成為真正的專家</p>
        </div>
    </header>
    
    <nav>
        <ul>
            <li><a href="#introduction">介紹</a></li>
            <li><a href="#advanced-concepts">進階概念</a></li>
            <li><a href="#performance">效能優化</a></li>
            <li><a href="#case-studies">案例分析</a></li>
            <li><a href="#challenges">挑戰</a></li>
            <li><a href="#resources">資源</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <section id="introduction">
            <h2>介紹</h2>
            <p>歡迎來到Python ' . htmlspecialchars($theme) . ' 專家指南。這個資源專為有經驗的Python開發者設計，幫助你掌握進階技術。</p>
            
            <div class="tip-box">
                <h4>為什麼需要專家級知識？</h4>
                <p>隨著項目規模和複雜度增加，基本技能往往不足以解決高階問題。專家級技能讓你能夠設計出高效、可擴展且強健的解決方案。</p>
            </div>
        </section>
        
        <section id="advanced-concepts">
            <h2>進階概念</h2>
            
            <div class="card">
                <div class="card-header">記憶體管理與最佳化</div>
                <div class="card-body">
                    <p>了解Python的記憶體管理機制，以及如何最佳化大型應用程式的記憶體使用。</p>
                    
                    <div class="code-block">
<pre>
import gc
import tracemalloc

# 啟用追蹤
tracemalloc.start()

# 程式碼區塊
def memory_intensive_function():
    # 建立大型資料結構
    large_dict = {i: object() for i in range(1000000)}
    return process_data(large_dict)

result = memory_intensive_function()

# 收集垃圾
gc.collect()

# 獲取當前和峰值記憶體使用
current, peak = tracemalloc.get_traced_memory()
print(f"當前記憶體使用: {current / 10**6:.1f} MB")
print(f"峰值記憶體使用: {peak / 10**6:.1f} MB")
tracemalloc.stop()
</pre>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">進階設計模式</div>
                <div class="card-body">
                    <p>學習如何在Python中實現複雜的設計模式，以創建更可維護和可擴展的代碼。</p>
                    
                    <table>
                        <tr>
                            <th>設計模式</th>
                            <th>適用場景</th>
                            <th>優點</th>
                            <th>實現難度</th>
                        </tr>
                        <tr>
                            <td>單例模式</td>
                            <td>資源管理、配置</td>
                            <td>控制資源存取、全局點存取</td>
                            <td>低</td>
                        </tr>
                        <tr>
                            <td>工廠模式</td>
                            <td>物件創建邏輯複雜</td>
                            <td>封裝創建邏輯、靈活性</td>
                            <td>中</td>
                        </tr>
                        <tr>
                            <td>觀察者模式</td>
                            <td>事件處理、UI</td>
                            <td>低耦合、事件驅動</td>
                            <td>中</td>
                        </tr>
                        <tr>
                            <td>裝飾者模式</td>
                            <td>動態添加功能</td>
                            <td>不修改原始碼、靈活擴展</td>
                            <td>中高</td>
                        </tr>
                    </table>
                </div>
            </div>
        </section>
        
        <section id="performance">
            <h2>效能優化</h2>
            
            <div class="card">
                <div class="card-header">多執行緒與並行處理</div>
                <div class="card-body">
                    <p>學習如何使用Python的並行處理功能來加速應用程式。</p>
                    
                    <div class="code-block">
<pre>
import concurrent.futures
import time

def cpu_intensive_task(n):
    # 模擬CPU密集型計算
    result = 0
    for i in range(10**7):
        result += i * n
    return result

def main():
    numbers = [1, 2, 3, 4, 5, 6, 7, 8]
    
    # 計時單執行緒執行
    start = time.time()
    results = [cpu_intensive_task(n) for n in numbers]
    end = time.time()
    print(f"單執行緒時間: {end - start:.2f} 秒")
    
    # 計時多執行緒執行
    start = time.time()
    with concurrent.futures.ProcessPoolExecutor() as executor:
        results = list(executor.map(cpu_intensive_task, numbers))
    end = time.time()
    print(f"多執行緒時間: {end - start:.2f} 秒")

if __name__ == "__main__":
    main()
</pre>
                    </div>
                    
                    <div class="warning-box">
                        <h4>注意 GIL 限制</h4>
                        <p>Python的全局解釋器鎖(GIL)會限制多執行緒在CPU密集型任務中的效能。對於這類任務，考慮使用多處理(multiprocessing)而非多執行緒。</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">C擴展與Cython</div>
                <div class="card-body">
                    <p>學習如何通過C擴展和Cython加速Python代碼的執行。</p>
                </div>
            </div>
        </section>
        
        <section id="case-studies">
            <h2>案例分析</h2>
            
            <div class="card">
                <div class="card-header">企業級應用案例</div>
                <div class="card-body">
                    <p>分析大型企業如何使用Python ' . htmlspecialchars($theme) . ' 解決複雜問題。</p>
                </div>
            </div>
        </section>
        
        <section id="challenges">
            <h2>專家挑戰</h2>
            
            <div class="card">
                <div class="card-header">加密解密挑戰</div>
                <div class="card-body">
                    <p>本頁面包含一個特殊的加密信息，你能找到並解密它嗎？提示：查看頁面源代碼中的meta標籤。</p>
                </div>
            </div>
        </section>
        
        <section id="resources">
            <h2>專家資源</h2>
            
            <div class="card">
                <div class="card-header">推薦閱讀與工具</div>
                <div class="card-body">
                    <p>精選的高級學習資源，幫助你更深入地掌握Python。</p>
                    <ul>
                        <li><a href="#">Python高效能程式設計</a></li>
                        <li><a href="#">Python源碼剖析</a></li>
                        <li><a href="#">Python進階數據結構與算法</a></li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
    
    <footer>
        <div class="container">
            <p>Python專家學習平台 &copy; 2023</p>
            <div class="footer-links">
                <a href="#">關於我們</a>
                <a href="#">聯絡我們</a>
                <a href="#">隱私政策</a>
                <a href="#">使用條款</a>
            </div>
            <p><small>本網站內容僅供學習參考，非官方Python文檔。</small></p>
        </div>
    </footer>
    
    <!-- 隱藏內容 -->
    <div class="hidden-content" aria-hidden="true">
        <!-- 這裡隱藏了一個秘密訊息 -->
        <!-- ' . $answer . ' -->
    </div>
    
    <script>
        // 初始化頁面功能
        document.addEventListener("DOMContentLoaded", function() {
            console.log("頁面已載入");
            // 在控制台中留下提示
            console.log("%c專家挑戰：尋找隱藏在這個頁面中的秘密代碼", "color: red; font-size: 16px; font-weight: bold;");
            console.log("%c提示：加密金鑰在metadata中", "color: blue;");
            
            // 隱藏的Base64資料，解密後得到答案
            let encodedData = "' . $finalEncoded . '";
            // 你知道該怎麼解密它嗎？
        });
    </script>
</body>
</html>';

    return $html;
}

/**
 * 生成預設頁面
 */
function generateDefaultWebpage($theme, $difficulty) {
    $answer = 'ans_' . substr(md5(rand()), 0, 6);
    
    $html = '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($theme) . ' 學習資源</title>
</head>
<body>
    <h1>' . htmlspecialchars($theme) . ' 教學</h1>
    <div>
        <!-- ' . $answer . ' -->
        <p>這是一個測試網頁，請練習尋找隱藏在HTML中的答案。</p>
    </div>
</body>
</html>';
    
    return [
        'html' => $html,
        'answer' => $answer
    ];
}

/**
 * 生成AI提示
 */
function generateHint($html, $answer, $difficulty) {
    $hints = [];
    
    // 基本提示
    $hints[] = "尋找包含 'ans_' 開頭的字串，這通常是隱藏答案的格式。";
    
    // 根據難度提供更多提示
    switch($difficulty) {
        case '初階':
            $hints[] = "檢查HTML的註解部分，答案可能藏在<!-- -->標籤內。";
            $hints[] = "使用簡單的字串搜索功能就能找到答案，例如 html.find('ans_')。";
            break;
        
        case '中階':
            $hints[] = "有時答案會被編碼，例如使用Base64，或藏在頁面的metadata中。";
            $hints[] = "檢查script標籤中的JavaScript代碼，或特殊的隱藏元素。";
            break;
        
        case '高階':
            $hints[] = "答案可能經過多層加密或編碼，需要綜合運用多種解碼技術。";
            $hints[] = "探索HTML屬性、CSS偽元素或JavaScript中的變數，答案可能被分散儲存。";
            break;
    }
    
    $hintText = "以下是幫助你找到答案的提示：\n\n";
    foreach($hints as $index => $hint) {
        $hintText .= ($index + 1) . ". " . $hint . "\n";
    }
    
    return $hintText;
}
?>
