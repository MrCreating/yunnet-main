<?php

namespace unt\platform;

class Data extends \unt\objects\BaseObject
{
	function __construct (array $fields = [])
	{
        parent::__construct();

		foreach ($fields as $index => $value)
		{
			if (is_array($value))
				$value = new Data($value);

			$this->{$index} = $value;
		}

		//$this->toArray();
	}

	public function toArray (): array
	{
        unset($this->currentConnection);

		return get_object_vars($this);
	}
}

?>