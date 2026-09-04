<?php

echo "<h2>Server Time Information</h2>";

echo "PHP Timezone: " . date_default_timezone_get() . "<br>";
echo "Server Time: " . date('Y-m-d H:i:s T') . "<br>";
echo "UTC Time: " . gmdate('Y-m-d H:i:s T') . "<br>";

echo "<br>";

echo "Sri Lanka Time: " . 
    (new DateTime('now', new DateTimeZone('Asia/Colombo')))
    ->format('Y-m-d H:i:s T') . "<br>";