<?php

class Data
{
	function __construct ($fields = [])
	{
		foreach ($fields as $index => $value)
		{
			if (is_array($value))
				$value = new Data($value);

			$this->{$index} = $value;
		}
	}
}

?>