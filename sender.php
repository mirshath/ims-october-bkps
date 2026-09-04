<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

// cPanel DB Config
$cpanelHost = 'localhost';
$cpanelDb   = 'bmsims_octomber_3';
$cpanelUser = 'bmsims_octomber_3';
$cpanelPass = '%tce4xK(g2iqd,8A';

// Local Server Web Receiver URL
$receiverUrl = 'http://123.231.63.229/receiver.php';
$secretKey   = '';

try {
    $cpanelPdo = new PDO(
        "mysql:host={$cpanelHost};dbname={$cpanelDb};charset=utf8mb4",
        $cpanelUser,
        $cpanelPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $tables = $cpanelPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $exportData = [];

    foreach ($tables as $table) {
        if ($table === 'sync_queue') continue;
        $rows = $cpanelPdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $exportData[$table] = $rows;
    }

    // Send data to local server via HTTP POST (Port 80)
    $ch = curl_init($receiverUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'key'  => $secretKey,
        'data' => json_encode($exportData)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "Response from Local Server: " . $response;
    } else {
        echo "HTTP Error {$httpCode}: " . $response;
    }

} catch (Exception $e) {
    echo "cPanel Error: " . $e->getMessage();
}
?>