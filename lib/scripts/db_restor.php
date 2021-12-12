<?php

$db = new PDO("mysql:host=mysql", "root", "root");

$path = __DIR__ . '/../../local/config/mysql/dump';

$files = array_diff(scandir($path), array('.', '..'));

foreach ($files as $index => $database) {
	$sql = file_get_contents($path . '/' . $database);
	if ($sql)
	{
		echo "[+] Working with DB: " . $database  . '...' . PHP_EOL;

		try
		{
			$db->prepare("CREATE DATABASE " . $database)->execute();
		} catch (PDOException $e)
		{
			echo "[!] DB already exists. Continue..." . PHP_EOL;
		}

		$templine = '';
		
		$lines = file($path . '/' . $database);
		foreach ($lines as $i => $line)
		{
			if (substr($line, 0, 2) == '--' || $line == '') continue;

			$templine .= $line;
			if (substr(trim($line), -1, 1) == ';')
			{
				$r = $db->prepare($templine);
				if ($r->execute())
				{
					echo "[OK] File " . $database . ", line " . $i . " executed. Continue..." . PHP_EOL;
				}
				
    			$templine = '';
			}
		}
	}
}

?>