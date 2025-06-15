<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://tcgrw8vb-8080.asse.devtunnels.ms/index.php");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 忽略憑證錯誤（開發階段用）

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo '錯誤：' . curl_error($ch);
} else {
    echo $response;
}
curl_close($ch);
?>
