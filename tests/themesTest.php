<?php
require __DIR__ . '/../bin/base_functions.php';

if (!class_exists('Theme'))
	require __DIR__ . '/../bin/objects/theme.php';

$theme = new Theme(1, 1);

die(var_dump($theme->getCSSCode()));

?>