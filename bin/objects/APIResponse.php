<?php

namespace unt\objects;

use Exception;

/**
 * API response class
*/

class APIResponse extends BaseObject
{
	private array $response;

    /**
     * @throws Exception
     */
    private function __clone ()
	{
		throw new Exception('Not allowed to clone this object');
	}

	public function __construct (array $response = [])
	{
        parent::__construct();

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
