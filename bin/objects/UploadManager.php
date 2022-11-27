<?php

/**
 * File uploader checker and upload session creator
*/

class UploadManager 
{
	private bool $alreadyUploaded = false;

	public function __construct (string $query)
	{

	}

	public function isUploaded (): bool
	{
		return $this->alreadyUploaded;
	}
}

?>