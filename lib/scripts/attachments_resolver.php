<?php

error_reporting(0);
echo "[+] Including API..." . PHP_EOL;

require __DIR__ . '/bin/base_functions.php';
require __DIR__ . '/bin/functions/uploads.php';

$working_user_id = '51';
$attachments_dir = __DIR__ . '/attachments/d-1/' . $working_user_id . '/images/';

echo "[...] Opening attachments folder..." . PHP_EOL;
$handler = opendir($attachments_dir);

while (($entry = readdir($handler)))
{
	if (!$entry)
	{
		echo '[!] Unable to read the file...' . PHP_EOL;

		continue;
	}

	if ($entry === '.' || $entry === '..') continue;
	$attachmentPath = $attachments_dir . $entry;

	$img = new Imagick($attachmentPath);

	$expLevel = getExpessionLevel($img);
	echo '[...] Working on image with expression level ' . $expLevel . '...' . PHP_EOL;

	$img->setCompression(Imagick::COMPRESSION_JPEG);
	$icc_profile = $img->getImageProfiles('icc', true);

	$img->stripImage();
	$img->resizeImage(intval($img->getImageWidth() / $expLevel), intval($img->getImageHeight() / $expLevel), Imagick::FILTER_LANCZOS, 1);
	$img->setCompressionQuality(20);

	if(!empty($profiles)) 
	{
		$img->profileImage('icc', $icc_profile['icc']);
	}

	if (!$img->writeImage($attachmentPath)) echo '[!] Unable to write image!' . PHP_EOL;
}

echo '[+] All done.' . PHP_EOL;
?>https://vk.com/sticker/1-13612-128