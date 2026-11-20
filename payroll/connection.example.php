<?php
/* TEMPLATE: copy to connection.php in this folder and fill in real credentials. connection.php is git-ignored. */
/* ---------- HOST BASED CONFIG ---------- */
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

if (in_array($host, ["localhost", "127.0.0.1", "192.168.1.6"])) {
    $config = [
        "servername" => '127.0.0.1',
        "username"   => "root",
        "password"   => "" /* XAMPP default: empty */,
        "db_master"  => "krf_accounting",
        "db_accounting" => "krf_accounting",
        "db_hrm"     => "hrmplus",
        "db_auth"    => "dbauthorization_karjee",
        "db_krf"     => "karjeefarm",
        "dbname_krf" => "root",
        "dbname_hrm" => "root",
        "dbname_auth"=> "root",
    ];

} else if (in_array($host, ["192.168.1.65", "192.168.1.215"])) {
    $config = [
        "servername" => '127.0.0.1',
        "username"   => "root",
        "password"   => "YOUR_DB_PASSWORD",
        "db_master"  => "krf_accounting",
        "db_accounting" => "krf_accounting",
        "db_hrm"     => "hrmplus",
        "db_auth"    => "dbauthorization",
        "db_krf"     => "karjeefarm",
        "dbname_krf" => "root",
        "dbname_hrm" => "root",
        "dbname_auth"=> "root",
    ];

} else {
    $config = [
        "servername" => "127.0.0.1",
        "username"   => "YOUR_DB_USER",
        "password"   => "YOUR_DB_PASSWORD",
        "db_master"  => "krf_accounting",
        "db_accounting"  => "krf_accounting",
        "db_hrm"     => "hrmplus",
        "db_auth"    => "dbauthorization",
        "db_krf"    => "karjeefarm",
        "dbname_hrm"     => "hrm",
        "dbname_auth"     => "dbauth",
        "dbname_krf" => "krf_main",
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

    @mysqli_query($conn, "SET SESSION innodb_lock_wait_timeout = 50");
    @mysqli_query($conn, "SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
    @mysqli_query($conn, "SET SESSION group_concat_max_len = 65535");
    @mysqli_query($conn, "SET SESSION time_zone = '+05:30'");

    return $conn;
}

/* ---------- CREATE CONNECTIONS ---------- */
if (!isset($mconn) || !($mconn instanceof mysqli) || !@mysqli_ping($mconn)) {
    $mconn = createConnection($config['servername'], $config['dbname_krf'], $config['password'], $config['db_accounting']);
}

$conn = $mconn; // Same DB reuse

if (!isset($hrmconn) || !($hrmconn instanceof mysqli) || !@mysqli_ping($hrmconn)) {
    $hrmconn = createConnection($config['servername'], $config['dbname_hrm'], $config['password'], $config['db_hrm']);
}

if (!isset($authconn) || !($authconn instanceof mysqli) || !@mysqli_ping($authconn)) {
    $authconn = createConnection($config['servername'], $config['dbname_auth'], $config['password'], $config['db_auth']);
}

if (!isset($krfconn) || !($krfconn instanceof mysqli) || !@mysqli_ping($krfconn)) {
    $krfconn = createConnection($config['servername'], $config['dbname_krf'], $config['password'], $config['db_krf']);
}

function safeClose(&$db)
{
    if (!is_object($db)) {
        return;
    }

    try {
        @$db->ping(); // check if connection is alive
        @$db->close();
    } catch (Throwable $e) {
        // already closed, ignore
    }

    $db = null;
}

function closeallconection() {
    global $conn, $mconn, $krfconn, $hrmconn, $authconn;
    safeClose($conn);
    safeClose($mconn);
    safeClose($krfconn);
    safeClose($hrmconn);
    safeClose($authconn);
}
?>