<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>冒險者的世界</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: '微軟正黑體', sans-serif;
            background-color: #f8f9fa;
        }
        .location {
            position: relative;
            width: 60px;
            height: 60px;
            margin: 0 auto;
            cursor: pointer;
        }
        .location-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            color: #007bff;
        }
        .location.unlocked .location-icon {
            color: #28a745;
        }
        .world-info {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .stat-card {
            background-color: #f1f3f5;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        .stat-icon {
            font-size: 28px;
            color: #007bff;
            margin-right: 10px;
        }
        .hidden-treasures {
            margin-top: 20px;
        }
        .treasure-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .treasure-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        .treasure-locked {
            color: #dc3545;
        }
        .badge {
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="location <?php echo $completed_chapters >= 3 ? 'unlocked' : ''; ?>"
                             data-bs-toggle="tooltip" data-bs-placement="top" 
                             title="<?php echo $completed_chapters >= 3 ? '神秘森林 - 已解鎖' : '解鎖條件：完成 3 個章節'; ?>">
                            <div class="location-icon">
                                <i class="fas fa-tree"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="location <?php echo $completed_chapters >= 5 ? 'unlocked' : ''; ?>"
                             data-bs-toggle="tooltip" data-bs-placement="top" 
                             title="<?php echo $completed_chapters >= 5 ? '遺忘之城 - 已解鎖' : '解鎖條件：完成 5 個章節'; ?>">
                            <div class="location-icon">
                                <i class="fas fa-archway"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="location <?php echo $completed_chapters >= 7 ? 'unlocked' : ''; ?>"
                             data-bs-toggle="tooltip" data-bs-placement="top" 
                             title="<?php echo $completed_chapters >= 7 ? '模組山脈 - 已解鎖' : '解鎖條件：完成 7 個章節'; ?>">
                            <div class="location-icon">
                                <i class="fas fa-mountain"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="location <?php echo $completed_chapters >= 9 ? 'unlocked' : ''; ?>"
                             data-bs-toggle="tooltip" data-bs-placement="top" 
                             title="<?php echo $completed_chapters >= 9 ? '異常洞窟 - 已解鎖' : '解鎖條件：完成 9 個章節'; ?>">
                            <div class="location-icon">
                                <i class="fas fa-dungeon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="world-info">
                    <h3 class="mb-4"><i class="fas fa-map-marked-alt me-2"></i>世界狀態</h3>
                    
                    <div class="world-stats">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $completed_chapters; ?>/9</div>
                                    <div class="stat-label">已完成章節</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-map"></i>
                                    </div>
                                    <div class="stat-value"><?php echo ($completed_chapters >= 3) + ($completed_chapters >= 5) + ($completed_chapters >= 7) + ($completed_chapters >= 9); ?>/4</div>
                                    <div class="stat-label">已解鎖區域</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="hidden-treasures">
                        <h4 class="mb-3"><i class="fas fa-gem me-2"></i>隱藏寶藏</h4>
                        
                        <?php if(count($unlocked_stages) > 0): ?>
                            <?php foreach($unlocked_stages as $stage): ?>
                                <div class="treasure-item">
                                    <div class="treasure-icon <?php echo !isset($stage['is_unlocked']) || !$stage['is_unlocked'] ? 'treasure-locked' : ''; ?>">
                                        <i class="<?php echo !isset($stage['is_unlocked']) || !$stage['is_unlocked'] ? 'fas fa-lock' : 'fas fa-trophy'; ?>"></i>
                                    </div>
                                    <div class="treasure-info">
                                        <div class="treasure-name"><?php echo htmlspecialchars($stage['stage_name']); ?></div>
                                        <div class="treasure-effect">
                                            <?php
                                                if(isset($stage['is_unlocked']) && $stage['is_unlocked']) {
                                                    echo '獎勵：' . htmlspecialchars($stage['unlock_effect']);
                                                    if($stage['effect_applied']) {
                                                        echo ' <span class="badge bg-success">已獲得</span>';
                                                    }
                                                } else {
                                                    echo '繼續探索世界以解鎖這個寶藏';
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle me-2"></i>尚未發現任何隱藏寶藏！繼續完成關卡並探索世界。
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="hidden_quest.php" class="btn btn-success w-100">
                                <i class="fas fa-search me-2"></i>尋找隱藏任務
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 初始化工具提示
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>