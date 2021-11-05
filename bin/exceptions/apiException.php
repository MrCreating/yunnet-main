<?php

/**
 * Unt engine 8 exception.
*/

class APIException extends Exception
{	
	public function __constuct (string $message = '', $code = 0, Throwable $prev = NULL)
	{
		$this->code    = $code;
		$this->message = $message;

		parent::__constuct($message, $code, $prev);
	}

	public function setErrorCode (int $code): APIException
	{
		$this->errorCode = $code;

		return $this;
	}

	public function setErrorMessage (string $message): APIException
	{
		$this->message = $message;

		return $this;
	}

	public function toArray (?API $api = NULL): array
	{
		$result = [
			'error' => [
				'error_code' => $this->getCode(),
				'error_message' => $this->getMessage()
			],
			'params' => []
		];

		if ($api)
		{
			$apiParams = $api->getRequestParams();
			foreach ($apiParams as $key => $value) 
			{

				$result['params'][] = [
					'key'   => $key,
					'value' => $value
				];
			}
		}

		return $result;
	}

	public function send (?API $api = NULL): void
	{
		die(json_encode($this->toArray($api)));
	}
}

?>