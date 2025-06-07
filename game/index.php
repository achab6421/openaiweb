<?php
// 初始化會話
session_start();

// 如果用戶已經登入，直接導向主選單頁面
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: main.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python 怪物村：AI 助教教你寫程式打怪獸！</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
            background-color: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .splash-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            background: url('assets/images/splash-bg.jpg') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
        }
        
        .splash-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
        }
        
        .splash-content {
            position: relative;
            z-index: 10;
            color: white;
            padding: 20px;
            width: 90%;
            max-width: 800px;
        }
        
        .game-logo {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #f8f9fa;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        
        .game-logo .highlight {
            color: #ffc107;
        }
        
        .game-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
            opacity: 0.9;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        
        .start-btn {
            font-size: 1.2rem;
            padding: 12px 50px;
            border-radius: 50px;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            border: none;
            color: white;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .start-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
            background: linear-gradient(45deg, #5d4ee2, #9782ff);
        }
        
        .start-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.3s ease;
        }
        
        .start-btn:hover::before {
            left: 100%;
        }
        
        .feature-items {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        
        .feature-item {
            margin: 0 15px;
            padding: 15px 20px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
        }
        
        .feature-icon {
            font-size: 2rem;
            color: #ffc107;
            margin-bottom: 10px;
        }
        
        .feature-description {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .floating-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }
        
        @keyframes float {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 1;
            }
            100% {
                transform: translateY(-10vh) translateX(50px);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="splash-container">
        <div class="splash-overlay"></div>
        
        <div class="floating-particles" id="particles"></div>
        
        <div class="splash-content">
            <h1 class="game-logo">
                <i class="fas fa-dragon me-2"></i> 
                <span class="highlight">Python</span> 怪物村
            </h1>
            <h2 class="game-subtitle">AI 助教教你寫程式打怪獸！</h2>
            
            <div class="feature-items">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <div class="feature-description">學習Python編程</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-spider"></i>
                    </div>
                    <div class="feature-description">爬蟲技術挑戰</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-dragon"></i>
                    </div>
                    <div class="feature-description">打敗程式怪獸</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="feature-description">AI助教指導</div>
                </div>
            </div>
            
            <a href="login.php" class="btn start-btn">
                <i class="fas fa-play me-2"></i> 開始冒險
            </a>
        </div>
    </div>
    
    <script>
        // 創建浮動粒子效果
        const particlesContainer = document.getElementById('particles');
        const particlesCount = 30;
        
        for (let i = 0; i < particlesCount; i++) {
            createParticle();
        }
        
        function createParticle() {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            
            // 隨機大小
            const size = Math.random() * 5 + 2;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            
            // 隨機位置
            const posX = Math.random() * 100;
            const delay = Math.random() * 15;
            const duration = Math.random() * 10 + 10;
            
            particle.style.left = `${posX}%`;
            particle.style.bottom = `-${size}px`;
            particle.style.opacity = Math.random() * 0.7 + 0.3;
            
            // 隨機動畫
            particle.style.animation = `float ${duration}s infinite linear`;
            particle.style.animationDelay = `${delay}s`;
            
            particlesContainer.appendChild(particle);
        }
    </script>
</body>
</html>
