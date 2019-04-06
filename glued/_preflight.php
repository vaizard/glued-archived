<?php

///////////////////////////////////////////////
// PREFLIGHT TESTS USED BY THE INSTALLER //////
///////////////////////////////////////////////


//
// glued/settings.php exists?
//

if (!file_exists( __DIR__ . '/settings.php')) { die("[ERROR] application configuration problem."); }
require __DIR__ . '/../vendor/autoload.php';


//
// database connection configured and alive?
//

$config = require __DIR__ . '/settings.php';
$link = mysqli_connect($config['settings']['db']['host'], $config['settings']['db']['username'], $config['settings']['db']['password'], $config['settings']['db']['database']);
if (!$link) {
    echo "[ERROR] Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    die();
}
echo "[PASS] MySQL connection OK to " . mysqli_get_host_info($link) . PHP_EOL;
mysqli_close($link);


?>