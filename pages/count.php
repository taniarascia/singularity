<?php
/* QM Promisance - Turn-based strategy game
 * Copyright (C) QMT Productions
 *
 * $Id: count.php 1983 2014-10-01 15:18:43Z quietust $
 */

if (!defined('IN_GAME'))
	die("Access denied");

if (strlen(COUNTER_TEMPLATE) == 0)
	error_500(lang('ERROR_TITLE'), 'Registration counter is not configured to use an image!');
	
$num = $db->queryCell('SELECT COUNT(*) FROM '. EMPIRE_TABLE .' WHERE u_id != 0');
$num = str_pad($num, COUNTER_DIGITS, '0', STR_PAD_LEFT);

$filename = PROM_BASEDIR .'images/'. COUNTER_TEMPLATE;

$count = @getimagesize($filename) or error_500(lang('ERROR_TITLE'), 'Unable to read image for registration counter!');
$types = imagetypes();
switch ($count[2])
{
case IMAGETYPE_GIF:
	if ($types & IMG_GIF)
		$src = imagecreatefromgif($filename);
	else	error_500(lang('ERROR_TITLE'), 'Unable to load GIF image for registration counter!');
	break;
case IMAGETYPE_JPEG:
	if ($types & IMG_JPG)
		$src = imagecreatefromjpeg($filename);
	else	error_500(lang('ERROR_TITLE'), 'Unable to load JPEG image for registration counter!');
	break;
case IMAGETYPE_PNG:
	if ($types & IMG_PNG)
		$src = imagecreatefrompng($filename);
	else	error_500(lang('ERROR_TITLE'), 'Unable to open PNG image for registration counter!');
	break;
default:
	error_500(lang('ERROR_TITLE'), 'Unsupported image type for registration counter!');
	break;
}

$width = $count[0] / 10;
$height = $count[1];

$dest = imagecreatetruecolor($width * strlen($num), $height);

for ($i = 0; $i < strlen($num); $i++)
	imagecopy($dest, $src, $width * $i, 0, $width * $num[$i], 0, $width, $height);

imagedestroy($src);

header("Cache-Control: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("Content-type: ". image_type_to_mime_type($count[2]));

// no need to check supported types here - already done it above
switch ($count[2])
{
case IMAGETYPE_GIF:	imagegif($dest);	break;
case IMAGETYPE_JPEG:	imagejpeg($dest);	break;
case IMAGETYPE_PNG:	imagepng($dest);	break;
}

imagedestroy($dest);
?>
