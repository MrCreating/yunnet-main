<?php

error_reporting(0);
ini_set('display_errors', 0);

echo "Scanning the Project folder..." . PHP_EOL;

function get_directory_info (string $path)
{
	$result = [];

	echo "Open the Project folder...";
	if ($handle = opendir($path))
	{
		echo " ok!" . PHP_EOL;

		while (false !== ($entry = readdir($handle))) 
		{
	        if (in_array($entry, [
	        	'.', '..', 'proc', 'sys', 'dev', '.git'
	        ])) continue;

	        $filePath = str_replace('//', '/', $path . '/' . $entry);

	        echo "Scanning path: " . $filePath . "...";

	        if (is_dir($filePath))
	        {
	        	echo " directory!" . PHP_EOL;
	        	$subdir_result = get_directory_info($filePath);
	        	$result = array_merge($result, $subdir_result);
	        } else 
	        {
	        	if (file_exists($filePath)) {
	        		$result[] = $filePath;
	        		echo " ok!" . PHP_EOL;
	        	} else
	        	{
	        		echo " fail!" . PHP_EOL;
	        		echo "Directory virtual. Exit from them... ok!" . PHP_EOL;
	        		break;
	        	}
	        }
	    }
	} else {
		echo " fail." . PHP_EOL;

		echo "Failed to open the directory with Project. Continue." . PHP_EOL;
	}

	return $result;
}

$files = get_directory_info('/');
echo "Project folder scanned." . PHP_EOL;
echo "Files: " . count($files) . PHP_EOL;

$results = [];

foreach ($files as $path)
{
	echo "Reading the path: " . $path . "...";

	$content = file_get_contents($path);

	if ($content !== false)
	{
		echo " ok!" . PHP_EOL;

		$figuresIn = 0;
		$figuresOut = 0;

		$rows = explode(PHP_EOL, $content);

		$rowsCount = count($rows);
		$results[] = $rowsCount;

		echo "Rows count: " . $rowsCount . "... ok!" . PHP_EOL;
	} else
	{
		echo " fail!" . PHP_EOL;
	}
}

echo "Files counted." . PHP_EOL;

$done = 0;

echo "Counting: ";
foreach ($results as $i => $value) 
{
	if ($i === 0)
	{
		echo $value;
	} elseif ($i === (count($results) - 1))
	{
		echo $value . " = ";
	} else
	{
		echo " + " . $value;
	}

	$done += $value;

	usleep(5000);
}

echo $done . "!" . PHP_EOL;

echo "All project files counted." . PHP_EOL;
echo "Files count: " . count($files) . PHP_EOL;
echo "Rows in files count: " . $done . PHP_EOL;
echo "Finishing the scan...";

sleep(2);

$needed = rand(rand(-100000, 100000), rand(-100000, 100000));
$symbol = rand(rand(-100000, 100000), rand(-100000, 100000));

while ($symbol !== $needed)
{
	$needed = rand(rand(-100000, 100000), rand(-100000, 100000));
	$symbol = rand(rand(-100000, 100000), rand(-100000, 100000));
}

echo " ok!" . PHP_EOL;
?>