<?php

/**
 * API response class
*/

class APIResponse
{
	private array $response = [];

	private function __clone ()
	{
		throw new Exception('Not allowed to clone this object');
	}

	public function __construct (array $response = [])
	{
		$this->response = $response;
	}

	public function getResponse (): array
	{
		return $this->response;
	}

	public function toJSON (): string
	{
		return json_encode($this->getResponse());
	}

	public function send (): void
	{
		die($this->toJSON());
	}
}

?>