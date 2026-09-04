<?php
date_default_timezone_set('Asia/Colombo');

set_time_limit(0);
ini_set('memory_limit', '512M');

// cPanel Source Database Credentials
$cpanelHost = 'localhost';
$cpanelDb   = 'bmsims_octomber_3'; // <--- Update with your cPanel DB name
$cpanelUser = 'bmsims_octomber_3'; // <--- Update with your cPanel DB username
$cpanelPass = '%tce4xK(g2iqd,8A'; // <--- Update with your cPanel DB password

$targetUrl = 'http://123.231.63.229/api_sync.php'; // <--- Local XAMPP Receiver URL
$syncToken = 'SecretSyncKey998877!';

$isCli = (php_sapi_name() === 'cli');

// Initialize Status Variables
$cpanelConnected = false;
$xamppReachable  = false;
$cpanelError     = '';
$xamppError      = '';
$tablesData      = [];
$syncResults     = null;
$httpCode        = 0;
$totalRows       = 0;
$rawBytes        = 0;
$compressedBytes = 0;

// ==========================================
// 1. TEST CPANEL DB CONNECTION
// ==========================================
try {
    $pdo = new PDO(
        "mysql:host={$cpanelHost};dbname={$cpanelDb};charset=utf8mb4",
        $cpanelUser,
        $cpanelPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    $pdo->exec("SET NAMES utf8mb4;");
    $cpanelConnected = true;
} catch (Exception $e) {
    $cpanelError = $e->getMessage();
}

// ==========================================
// 2. TEST LOCAL XAMPP SERVER REACHABILITY
// ==========================================
$chPing = curl_init($targetUrl);
curl_setopt_array($chPing, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY         => true,
    CURLOPT_TIMEOUT        => 5
]);
curl_exec($chPing);
$pingCode = curl_getinfo($chPing, CURLINFO_HTTP_CODE);
curl_close($chPing);

if ($pingCode === 200 || $pingCode === 403 || $pingCode === 400) {
    $xamppReachable = true;
} else {
    $xamppError = "Server returned HTTP {$pingCode} or timed out.";
}

// ==========================================
// 3. EXECUTE DATABASE EXPORT & SYNC
// ==========================================
if ($cpanelConnected && $xamppReachable) {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $payload = [];

        foreach ($tables as $table) {
            if ($table === 'sync_queue') continue;

            $schemaStmt  = $pdo->query("SHOW CREATE TABLE `$table`");
            $schemaRow   = $schemaStmt->fetch(PDO::FETCH_NUM);
            $tableSchema = $schemaRow[1] ?? '';

            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();

            $payload[$table] = [
                'schema' => $tableSchema,
                'rows'   => $rows
            ];
            $totalRows += count($rows);
        }

        $rawJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $rawBytes = strlen($rawJson);
        $compressedData = base64_encode(gzcompress($rawJson, 9));
        $compressedBytes = strlen($compressedData);

        // Send payload via cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $targetUrl,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_POSTFIELDS     => [
                'token' => $syncToken,
                'data'  => $compressedData
            ]
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$curlError && $httpCode === 200) {
            $syncResults = json_decode($response, true);
        } else {
            $xamppError = $curlError ? $curlError : "HTTP {$httpCode}: {$response}";
        }

    } catch (Exception $e) {
        $cpanelError = $e->getMessage();
    }
}

// If running in CLI / Cron, output plain text and exit
if ($isCli) {
    echo "[" . date('Y-m-d H:i:s') . "] cPanel DB: " . ($cpanelConnected ? "OK" : "FAIL") . " | XAMPP Server: " . ($xamppReachable ? "OK" : "FAIL") . "\n";
    if ($syncResults) {
        echo "[" . date('Y-m-d H:i:s') . "] SUCCESS (HTTP {$httpCode}): " . json_encode($syncResults) . "\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: cPanel Error: {$cpanelError} | XAMPP Error: {$xamppError}\n";
    }
    exit;
}

$compressionRatio = $rawBytes > 0 ? round((1 - ($compressedBytes / $rawBytes)) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Sync Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-arrows-rotate text-primary me-2"></i>cPanel to XAMPP Sync</h2>
            <p class="text-muted small mb-0">Automated MySQL Data Mirroring Endpoint</p>
        </div>
        <button onclick="window.location.reload();" class="btn btn-primary btn-sm fw-bold">
            <i class="fa-solid fa-rotate me-1"></i> Run Manual Sync Now
        </button>
    </div>

    <!-- Server Connection Status Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase small fw-bold d-block">Source Server</span>
                        <h5 class="fw-bold mb-0">cPanel Database</h5>
                        <small class="text-secondary"><?= htmlspecialchars($cpanelUser . '@' . $cpanelHost) ?></small>
                    </div>
                    <?php if ($cpanelConnected): ?>
                        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fs-6 rounded-pill">
                            <i class="fa-solid fa-circle-check me-1"></i> Connected
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 fs-6 rounded-pill">
                            <i class="fa-solid fa-circle-xmark me-1"></i> Disconnected
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase small fw-bold d-block">Target Endpoint</span>
                        <h5 class="fw-bold mb-0">Local XAMPP Server</h5>
                        <small class="text-secondary"><?= htmlspecialchars($targetUrl) ?></small>
                    </div>
                    <?php if ($xamppReachable): ?>
                        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fs-6 rounded-pill">
                            <i class="fa-solid fa-wifi me-1"></i> Online (Port 80)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 fs-6 rounded-pill">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Offline / Blocked
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Alerts -->
    <?php if ($cpanelError): ?>
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-4 me-3"></i>
            <div><strong>cPanel Error:</strong> <?= htmlspecialchars($cpanelError) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($xamppError): ?>
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-4 me-3"></i>
            <div><strong>XAMPP Receiver Error:</strong> <?= htmlspecialchars($xamppError) ?></div>
        </div>
    <?php endif; ?>

    <!-- Compression & Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <h3 class="fw-bold text-primary mb-0"><?= count($payload) ?></h3>
                <span class="text-muted small">Tables Exported</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <h3 class="fw-bold text-success mb-0"><?= number_format($totalRows) ?></h3>
                <span class="text-muted small">Total Rows Sent</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <h3 class="fw-bold text-info mb-0"><?= number_format($compressedBytes / 1024, 1) ?> KB</h3>
                <span class="text-muted small">Payload Size (Gzipped)</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <h3 class="fw-bold text-warning mb-0">-<?= $compressionRatio ?>%</h3>
                <span class="text-muted small">Bandwidth Saved</span>
            </div>
        </div>
    </div>

    <!-- Data Info Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Synchronized Database Tables</h5>
            <span class="badge bg-secondary"><?= date('Y-m-d H:i:s') ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>#</th>
                            <th>Table Name</th>
                            <th>Source Rows (cPanel)</th>
                            <th>Synced Rows (XAMPP)</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $syncedTables = $syncResults['tables'] ?? [];
                        foreach ($payload as $tableName => $info):
                            $sourceRowCount = count($info['rows']);
                            $syncedRowCount = $syncedTables[$tableName] ?? null;
                            $isSuccess = ($syncedRowCount !== null && $syncedRowCount === $sourceRowCount);
                        ?>
                        <tr>
                            <td class="text-muted small"><?= $i++ ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($tableName) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= number_format($sourceRowCount) ?> rows</span></td>
                            <td>
                                <?php if ($syncedRowCount !== null): ?>
                                    <span class="badge bg-light text-primary border"><?= number_format($syncedRowCount) ?> rows</span>
                                <?php else: ?>
                                    <span class="text-muted small">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($isSuccess): ?>
                                    <span class="badge bg-success-subtle text-success border border-success"><i class="fa-solid fa-check me-1"></i> Synced</span>
                                <?php elseif ($syncedRowCount !== null): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i> Partial</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger"><i class="fa-solid fa-xmark me-1"></i> Failed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payload)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No tables retrieved. Check database credentials or server connection status.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>


<!-- Auto Page Refresh Script (Sri Lanka Time Schedule) -->
<!--<script>-->
<!--(function() {-->
    // Scheduled daily refresh times in 24-hour format: [Hour, Minute]
<!--    const schedule = [-->
        [8, 0],   // 08:00 AM
        [10, 0],   // 09:00 AM
        [12, 0],  // 12:00 PM
        [14, 0],  // 02:00 PM
        [16, 55],  // 03:00 PM
        [18, 0],  // 04:00 PM
        [20, 0]   // 08:00 PM
<!--    ];-->

<!--    function getMsUntilNextRefresh() {-->
        // Get current time specifically in Sri Lanka (Asia/Colombo) timezone
<!--        const now = new Date();-->
<!--        const slTimeString = now.toLocaleString("en-US", { timeZone: "Asia/Colombo" });-->
<!--        const slDate = new Date(slTimeString);-->

<!--        const currentHour = slDate.getHours();-->
<!--        const currentMinute = slDate.getMinutes();-->
<!--        const currentSecond = slDate.getSeconds();-->
<!--        const currentMs = slDate.getMilliseconds();-->

        // Convert current Sri Lanka time into total milliseconds from midnight
<!--        const nowMsOfDay = ((currentHour * 3600) + (currentMinute * 60) + currentSecond) * 1000 + currentMs;-->

        // Find the next target time today
<!--        for (const [hour, minute] of schedule) {-->
<!--            const targetMsOfDay = ((hour * 3600) + (minute * 60)) * 1000;-->
<!--            if (targetMsOfDay > nowMsOfDay) {-->
<!--                return targetMsOfDay - nowMsOfDay;-->
<!--            }-->
<!--        }-->

        // If all scheduled times today have passed, target 8:00 AM tomorrow
<!--        const msUntilMidnight = (24 * 3600 * 1000) - nowMsOfDay;-->
<!--        const firstTargetTomorrowMs = ((schedule[0][0] * 3600) + (schedule[0][1] * 60)) * 1000;-->
        
<!--        return msUntilMidnight + firstTargetTomorrowMs;-->
<!--    }-->

<!--    const delayMs = getMsUntilNextRefresh();-->

    // Log the remaining time to browser console for verification
<!--    const minutesLeft = (delayMs / (1000 * 60)).toFixed(1);-->
<!--    console.log(`[SL Refresh Script] Next refresh scheduled in ${minutesLeft} minutes.`);-->

    // Set auto-refresh timer
<!--    setTimeout(function() {-->
<!--        window.location.reload();-->
<!--    }, delayMs);-->
<!--})();-->
<!--</script>-->
</body>
</html>