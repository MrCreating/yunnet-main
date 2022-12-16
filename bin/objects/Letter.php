<?php

namespace unt\objects;

class Letter extends BaseObject
{
	private string $subject = '';
	private string $text    = '';

	public function __construct (string $subject = '', string $text = '')
	{
        parent::__construct();

		$this->setSubject($subject)->setText($text);
	}

	public function setSubject (string $subject): Letter
	{
		$this->subject = trim($subject);

		return $this;
	}

	public function setText (string $text): Letter
	{
		$this->text = trim($text);

		return $this;
	}

	public function getSubject (): string
	{
		return $this->subject;
	}

	public function getText (): string
	{
		return $this->text;
	}

	public function send (string $email): bool
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

		return mail($email, '=?UTF-8?B?' . base64_encode($this->getSubject()) . '?=', $this->getText(), array(
	    	"MIME-Version: 1.0",
    		"Content-Type: text/html;charset=utf-8"
		));
	}

	public function sendTo (array $emails): bool
	{
		foreach ($emails as $email) 
		{
			if (!$this->send($email)) return false;
		}

		return true;
	}
}

?>