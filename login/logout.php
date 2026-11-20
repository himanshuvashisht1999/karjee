<?php
// Start the session
session_start();
// Unset all session variables
$_SESSION = array();
// Destroy the session
session_destroy();
// Optional: Clear the session cookie (if used)
$rrs=array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
$rrs=array('status'=>'Success', 'message'=>'You are logout successfully');
// Redirect to login or homepage
// header("Location: /index.php"); // Replace with your login page URL
// exit;
echo json_encode($rrs);
?>