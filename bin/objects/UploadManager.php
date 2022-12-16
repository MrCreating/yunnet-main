<?php

namespace unt\objects;

/**
 * File uploader checker and upload session creator
*/

class UploadManager extends BaseObject
{
	private bool $alreadyUploaded = false;

	public function __construct (string $query)
	{
        parent::__construct();
	}

	public function isUploaded (): bool
	{
		return $this->alreadyUploaded;
	}
}

?>