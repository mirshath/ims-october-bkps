<?php
phpinfo();





echo 'PHP Version: ' . PHP_VERSION . '<br>';
echo 'MySQLi: ' . (extension_loaded('mysqli') ? 'Enabled' : 'Not Enabled') . '<br>';
echo 'PDO MySQL: ' . (extension_loaded('pdo_mysql') ? 'Enabled' : 'Not Enabled');

echo '--------------------------------------------------------------------<br>';



echo "<h2>IMS Server Environment</h2>";

echo "PHP Version: " . PHP_VERSION . "<br>";
echo "PHP SAPI: " . PHP_SAPI . "<br><br>";

$extensions = [
    'mysqli',
    'pdo',
    'pdo_mysql',
    'openssl',
    'curl',
    'mbstring',
    'fileinfo',
    'gd',
    'zip',
    'xml',
    'json'
];

echo "<h3>PHP Extensions</h3>";

foreach ($extensions as $extension) {
    echo $extension . ": ";

    if (extension_loaded($extension)) {
        echo "<strong style='color:green'>Enabled</strong>";
    } else {
        echo "<strong style='color:red'>Missing</strong>";
    }

    echo "<br>";
}

echo "<h3>Composer</h3>";

$composerPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];

$composerFound = false;

foreach ($composerPaths as $path) {
    if (file_exists($path)) {
        $composerFound = true;
        echo "Composer autoload: <strong style='color:green'>Found</strong><br>";
        echo "Path: " . htmlspecialchars($path) . "<br>";
        break;
    }
}

if (!$composerFound) {
    echo "Composer autoload: <strong style='color:red'>Not found</strong><br>";
}

echo "<h3>PHPMailer</h3>";

$phpmailerFound = false;

foreach ($composerPaths as $path) {
    if (file_exists($path)) {
        require_once $path;

        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $phpmailerFound = true;
            echo "PHPMailer: <strong style='color:green'>Installed</strong><br>";
        }

        break;
    }
}

if (!$phpmailerFound) {
    echo "PHPMailer: <strong style='color:red'>Not detected</strong><br>";
}

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {

    echo "PHPMailer: Installed<br>";

    $reflection = new ReflectionClass('PHPMailer\PHPMailer\PHPMailer');
    $file = $reflection->getFileName();

    // Find Composer's installed.json
    $installedFile = __DIR__ . '/vendor/composer/installed.php';

    if (file_exists($installedFile)) {

        $installed = require $installedFile;

        if (isset($installed['versions']['phpmailer/phpmailer']['pretty_version'])) {
            echo "PHPMailer Version: " .
                 $installed['versions']['phpmailer/phpmailer']['pretty_version'];
        } else {
            echo "PHPMailer Version: Unable to detect";
        }

    } else {
        echo "Composer installed.php not found";
    }

} else {
    echo "PHPMailer: Not Installed";
}




/*
|--------------------------------------------------------------------------
| Get PHPMailer Version
|--------------------------------------------------------------------------
*/

$installed = require __DIR__ . '/vendor/composer/installed.php';

if (isset($installed['versions']['phpmailer/phpmailer'])) {

    $phpmailerVersion = $installed['versions']['phpmailer/phpmailer']['pretty_version'];

    echo "<strong>PHPMailer Version:</strong> " . $phpmailerVersion . "<br><br>";

} else {

    echo "<strong>PHPMailer:</strong> Not Installed<br><br>";

    exit;
}

/*
|--------------------------------------------------------------------------
| PHPMailer Compatibility
|--------------------------------------------------------------------------
*/

$phpmailerSupportedVersions = [
    '8.1',
    '8.2',
    '8.3',
    '8.4'
];

echo "<h3>PHP & PHPMailer Compatibility</h3>";

echo "<table border='1' cellpadding='8' cellspacing='0'>";

echo "<tr>";
echo "<th>PHP Version</th>";
echo "<th>PHPMailer " . htmlspecialchars($phpmailerVersion) . "</th>";
echo "</tr>";

foreach ($phpmailerSupportedVersions as $phpVersion) {

    echo "<tr>";

    echo "<td>PHP " . $phpVersion . "</td>";

    /*
     * PHPMailer 6.10.0 supports these PHP versions
     */
    echo "<td style='color: green; font-weight: bold;'>✅ Supported</td>";

    echo "</tr>";
}

echo "</table>";




