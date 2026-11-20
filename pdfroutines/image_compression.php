<?php
ini_set('memory_limit', '512M');

 function compressImage($source_url, $quality)
 {
    try
	{
		if(file_exists($source_url))
		{
			$exploding = explode(".",$source_url);
			$ext = end($exploding);
			$destination_url = str_replace(".".$ext,strtolower("_compress.".$ext),$source_url);

			
			switch(strtolower($ext)){
			case "png":
				$image = imagecreatefrompng($source_url);
				break;
			case "jpeg":
			case "jpg":
				$image = imagecreatefromjpeg($source_url);
				break;
			case "gif":
				$image = imagecreatefromgif($source_url);
				break;
			default:
				$image = imagecreatefromjpeg($source_url);
				break;
			}
			
			imagefilter($image, IMG_FILTER_GRAYSCALE);
			imagejpeg($image, $destination_url, $quality);
			imagedestroy($image );
			$sz = intval(filesize($destination_url)/1000/1000);
			if($sz>=1)
			{
				if($quality>=20)
				{
					unlink($destination_url);
					$quality = $quality-10;
					compressImage($source_url, $quality);
				}					
			}
			return $destination_url;
		}
		else
		{
			return false;
		}
	}
	catch(Exception $e) 
	{
  		return false;
	} 

}

function compressImageString($base64String, $quality)
 {
    try
	{
		begin:
		$data = base64_decode($base64String);
		$image = imagecreatefromstring($data);
		imagefilter($image, IMG_FILTER_GRAYSCALE);
		ob_start();
		imagejpeg($image, NULL, $quality);
		imagedestroy($image);
		$sz = ob_get_length()/1000/1000;
		$contents = ob_get_contents();
		ob_end_clean();
		if(intval($sz)>=1) 
		{
			if($quality>=20)
			{
				$quality = $quality-10;
				goto begin;
			}					
		}
		$base64 = base64_encode($contents);
		return $base64;
	}
	catch(Exception $e) 
	{
  		return $e;
	} 

}
?>