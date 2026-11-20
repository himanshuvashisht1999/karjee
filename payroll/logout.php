<?php
session_start();
// Unset all session variables
$_SESSION = [];
// Destroy the session completely
session_unset();
session_destroy();
// Optional: Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
// Redirect to root index.php
header("Location: ../index.php");
exit;
?>
