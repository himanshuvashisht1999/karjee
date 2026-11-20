<div id="output" style="width:100%;min-height:790px;"></div>
<?php
$filetodisplay = "";
if($_GET){
	 $filetodisplay = $_GET["f"];
}
else if($_POST){
	$filetodisplay = $_POST["f"];
}
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://';
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$uriParts = explode('?', $uri);
$path = $uriParts[0];
$currentURL = dirname($protocol . $host . $path);
if($filetodisplay){
	 if(!str_starts_with($filetodisplay,$currentURL)){
		$filetodisplay = $currentURL."/".$filetodisplay;
	} 
?>
<!-- <script type="text/javascript"  src="<?php echo $currentURL; ?>/js/tesseract.min.js"></script> -->
<script type="text/javascript" src="<?php echo $currentURL; ?>/js/jszip.min.js"></script>
<script type="text/javascript" src="<?php echo $currentURL; ?>/js/docx-preview.js"></script>
<script type="text/javascript" src="<?php echo $currentURL; ?>/js/xlsx.full.min.js"></script>
<script type="text/javascript">
if (typeof window._babelPolyfill === 'undefined') {
    document.write('<script type="text/javascript" src="<?php echo $currentURL; ?>/js/polyfill.js"><\/script>');
}
</script>
<script type="text/javascript" src="<?php echo $currentURL; ?>/js/exceljs.min.js"></script>
<script type="text/javascript"  src="<?php echo $currentURL; ?>/js/preview.js"></script>

<script>preview('<?php echo $filetodisplay; ?>','output');</script>
<?php
}
else{
?>
<script>document.getElementById('output').innerHTML = "Nothing To Preview";</script>
<?php
}
?>
