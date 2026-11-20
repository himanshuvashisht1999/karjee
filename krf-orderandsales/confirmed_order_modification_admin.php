<?php
// This dedicated admin entry point enables Edit Order only after authorization.
// Do not expose this permission through a user-controlled GET parameter.
$confirmedOrderAdminEntry = true;
require __DIR__ . '/finalsaleorder_review_new.php';
