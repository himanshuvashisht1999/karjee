<?php
ini_set('display_errors', 1);
error_reporting(E_ALL &~ E_STRICT);
 include '../imagetopdf.php';
// $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
       // . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
       // . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
       // . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
if (isset($_FILES['file'])) {
//move_uploaded_file($_FILES['file']['tmp_name'], "myuploadedimage.jpeg")
	$imagepath = $_FILES['file']['tmp_name'];
	$outputpdfpath = "myimagefiletopdf.pdf";
	convert_image_to_pdf($imagepath,$outputpdfpath);
	echo $outputpdfpath;
}
//$outputpdfpath = "example_imagetopdf3.pdf";
//convert_imagestring_to_pdf($data,$outputpdfpath);
echo '<h2>Upload form</h2>
<form method="post" enctype="multipart/form-data">
Input Image: <input type="file" name="file"  />&nbsp;&nbsp;<input type="submit" value="Parse" />
</form>';
?>