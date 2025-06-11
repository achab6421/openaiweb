<?php
// OpenAI API 設定檔
class OpenAIConfig {
    // OpenAI API 相關配置
    private $apiKey;
    private $problemGeneratorAssistantId;
    private $solutionValidatorAssistantId;
    
    // 獲取API金鑰
    public function getApiKey() {
        return $this->apiKey;
    }
    
    // 獲取題目生成助手ID
    public function getProblemGeneratorAssistantId() {
        return $this->problemGeneratorAssistantId;
    }
    
    // 獲取解答驗證助手ID
    public function getSolutionValidatorAssistantId() {
        return $this->solutionValidatorAssistantId;
    }
    
    // 設置API金鑰
    public function setApiKey($key) {
        $this->apiKey = $key;
    }
    
    // 設置題目生成助手ID
    public function setProblemGeneratorAssistantId($id) {
        $this->problemGeneratorAssistantId = $id;
    }
    
    // 設置解答驗證助手ID
    public function setSolutionValidatorAssistantId($id) {
        $this->solutionValidatorAssistantId = $id;
    }
    
    // 讀取環境變數中的配置信息
    public function loadFromEnvironment() {
        // 如果有.env文件，可以從中讀取配置
        if (file_exists(__DIR__ . '/../.env')) {
            $this->loadEnvFile(__DIR__ . '/../.env');
        }
        
        // 從環境變數獲取配置
        $this->apiKey = getenv('OPENAI_API_KEY') ?: $this->apiKey;
        $this->problemGeneratorAssistantId = getenv('PROBLEM_GENERATOR_ASSISTANT_ID') ?: 
                                            getenv('Questiongeneration') ?: 
                                            getenv('QUESTIONGENERATION') ?: $this->problemGeneratorAssistantId;
        
        $this->solutionValidatorAssistantId = getenv('SOLUTION_VALIDATOR_ASSISTANT_ID') ?: 
                                             getenv('Codeevaluation') ?: 
                                             getenv('CODEEVALUATION') ?: $this->solutionValidatorAssistantId;
    }
    
    // 讀取.env文件
    private function loadEnvFile($path) {
        if (!file_exists($path)) {
            return false;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // 忽略註釋行
            if (strpos(trim($line), '#') === 0 || strpos(trim($line), '//') === 0) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // 從引號中提取值
            if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
        
        return true;
    }
}
?>
