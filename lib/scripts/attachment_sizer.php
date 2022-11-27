<?php

$db = mysqli_connect("127.0.0.1", "root", "iA22021981_");

$all_attachments = mysqli_fetch_all(mysqli_query($db, "SELECT path FROM attachments.d_1;"));
foreach ($all_attachments as $key => $value) {
	$path = __DIR__.'/'.$value[0];

	$data = getimagesize($path);

	$width = $data[0];
	$height = $data[1];

	mysqli_query($db, "UPDATE attachments.d_1 SET width = ".$width." WHERE path = '".$value[0]."';");
	mysqli_query($db, "UPDATE attachments.d_1 SET height = ".$height." WHERE path = '".$value[0]."';");
}
?>