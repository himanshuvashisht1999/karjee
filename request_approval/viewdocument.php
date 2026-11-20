<?php
require '../connection.php';
require('../functions/function_master.php');
$blobrefid = $_REQUEST['blobid'];
$blobtblname = $_REQUEST['blobtblname'];
$blfileinfo = getblobfileinfo($conn,$blobrefid,$blobtblname);
foreach ($blfileinfo as $blbkey) {
		$ext = $blbkey['filetype'];
		$filesize = $blbkey['filesize'];
		$mimetype = Getmimecode($ext);
		header("Content-Type: {$mimetype}\n");
		header("Content-Disposition: inline; filename=\"attachment\"\n");
		header("Content-Length: {$filesize}\n");
		echo $blbkey['documentstring'];
}