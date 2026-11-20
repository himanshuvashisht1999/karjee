<?php
/**
 * QZ Tray secure message signing script for live web hosts.
 *
 * This script signs QZ websocket messages using your private-key.pem,
 * completely suppressing the "Untrusted Website" popup on clients' computers.
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$req = $_POST['request'] ?? '';
$private_key_path = __DIR__ . "/private-key.pem";

if (empty($req)) {
    http_response_code(400);
    die("No request data provided");
}

if (!file_exists($private_key_path)) {
    // Return early if developer has not uploaded private key yet
    http_response_code(404);
    die("Private key not found. Please upload private-key.pem to the krf-orderandsales/ directory.");
}

$private_key = file_get_contents($private_key_path);
$key = openssl_get_privatekey($private_key);

if (!$key) {
    http_response_code(500);
    die("Invalid private key format.");
}

$signature = "";
if (openssl_sign($req, $signature, $key, OPENSSL_ALGO_SHA512)) {
    echo base64_encode($signature);
} else {
    http_response_code(500);
    die("Unable to generate signature.");
}
?>
