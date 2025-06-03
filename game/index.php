<?php
// 初始化會話
session_start();
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
            background: url('assets/images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .game-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
        }
        
        .game-title {
            font-size: 3.5rem;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.7);
            margin-bottom: 2rem;
            animation: float 4s ease-in-out infinite;
        }
        
        .start-button {
            padding: 15px 60px;
            font-size: 1.5rem;
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }
        
        .start-button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.5);
            background: linear-gradient(45deg, #2E7D32, #1B5E20);
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .version {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .dragons {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 200px;
            opacity: 0.8;
            pointer-events: none;
        }
        
        .dragon {
            position: absolute;
            bottom: 0;
            width: 150px;
            height: 150px;
            background-image: url('assets/images/dragon.png');
            background-size: contain;
            background-repeat: no-repeat;
            animation: dragon-move 25s linear infinite;
        }
        
        .dragon:nth-child(2) {
            left: 20%;
            animation-delay: 5s;
            animation-duration: 30s;
            transform: scaleX(-1);
        }
        
        .dragon:nth-child(3) {
            left: 70%;
            animation-delay: 10s;
            animation-duration: 35s;
        }
        
        @keyframes dragon-move {
            0% {
                transform: translateX(-200px);
            }
            100% {
                transform: translateX(calc(100vw + 200px));
            }
        }
        
        .clouds {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            overflow: hidden;
            pointer-events: none;
            z-index: -1;
        }
        
        .cloud {
            position: absolute;
            background-image: url('assets/images/cloud.png');
            background-size: contain;
            background-repeat: no-repeat;
            opacity: 0.7;
            animation: cloud-move 60s linear infinite;
        }
        
        .cloud:nth-child(1) {
            top: 10%;
            left: -10%;
            width: 300px;
            height: 150px;
            animation-duration: 70s;
        }
        
        .cloud:nth-child(2) {
            top: 30%;
            left: -15%;
            width: 200px;
            height: 100px;
            animation-delay: 10s;
            animation-duration: 50s;
        }
        
        .cloud:nth-child(3) {
            top: 25%;
            left: -5%;
            width: 250px;
            height: 125px;
            animation-delay: 25s;
            animation-duration: 60s;
        }
        
        @keyframes cloud-move {
            0% {
                transform: translateX(-10%);
            }
            100% {
                transform: translateX(110%);
            }
        }
    </style>
</head>
<body>
    <div class="clouds">
        <div class="cloud"></div>
        <div class="cloud"></div>
        <div class="cloud"></div>
    </div>
    
    <div class="game-container">
        <h1 class="game-title">Python 怪物村<br><span class="fs-4">AI 助教教你寫程式打怪獸！</span></h1>
        <button class="start-button" onclick="location.href='login.php'">
            開始冒險 <i class="fas fa-dragon ms-2"></i>
        </button>
    </div>
    
    <div class="dragons">
        <div class="dragon"></div>
        <div class="dragon"></div>
        <div class="dragon"></div>
    </div>
    
    <div class="version">版本: 1.0.0</div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
