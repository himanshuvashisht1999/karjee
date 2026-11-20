<?php
/* TEMPLATE: copy to connection.php in this folder and fill in real credentials. connection.php is git-ignored. */
/* ---------- HOST BASED CONFIG ---------- */
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

if (in_array($host, ["localhost"])) {
    $config = [
        "servername" => '127.0.0.1',
        "username"   => "root",
        "password"   => "" /* XAMPP default: empty */,
        "db_master"  => "krf_accounting",
        "db_hrm"     => "hrmplus",
        "db_auth"    => "dbauthorization_karjee",
        "db_krf"    => "karjeefarm",
    ];

}else if (in_array($host, ["192.168.1.65","192.168.1.215"])) {
    $config = [
        "servername" => '192.168.1.65',
        "username"   => "root",
        "password"   => "YOUR_DB_PASSWORD",
        "db_master"  => "krf_accounting",
        "db_hrm"     => "hrmplus",
        "db_auth"    => "dbauthorization",
        "db_krf"    => "karjeefarm",
    ];

} else {
    $config = [
        "servername" => "127.0.0.1",
        "username"   => "YOUR_DB_USER",
        "password"   => "YOUR_DB_PASSWORD",
        "db_master"  => "YOUR_HOSTING_PREFIX_ask_company_master",
        "db_hrm"     => "hrmplus",
        "db_auth"    => "dbauthorization",
        "db_krf"    => "karjeefarm",
    ];
}


/* ---------- COMMON CONNECTION FUNCTION ---------- */

function createConnection($server, $user, $pass, $dbname)
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @mysqli_connect($server, $user, $pass, $dbname);
    if (!$conn) {
        $conn = @mysqli_connect('localhost', $user, $pass, $dbname);
    }

    if (!$conn) {
        die("Database Connection Failed : " . mysqli_connect_error());
    }

    mysqli_query($conn, "SET SESSION innodb_lock_wait_timeout = 50");
    mysqli_query($conn, "SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
    mysqli_query($conn, "SET SESSION group_concat_max_len = 65535");
    mysqli_query($conn, "SET SESSION time_zone = '+05:30'");

    return $conn;
}

/* ---------- CREATE CONNECTIONS ---------- */
$mconn     = createConnection($config['servername'], $config['username'], $config['password'], $config['db_master']);

$conn      = $mconn; // Same DB reuse

$hrmconn   = createConnection($config['servername'], $config['username'], $config['password'], $config['db_hrm']);

$authconn  = createConnection($config['servername'], $config['username'], $config['password'], $config['db_auth']);

$krfconn  = createConnection($config['servername'], $config['username'], $config['password'], $config['db_krf']);
?>