<?php

require_once __DIR__ . '/conversation.php';

/**
 * Chat networks - this is a goup of conversations
 * with multi-admin and global rules.
*/

class ConversationNetwork
{
	protected $id       = NULL;
	protected $title    = NULL;
	protected $owner_id = NULL;

	protected $isValid = false;

	public function __consruct (int $id)
	{}

	public function getId (): int
	{
		return $this->id;
	}

	public function getTitle (): string
	{
		return $this->title;
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

	public function toArray (): array
	{
		return [];
	}

	/////////////////////////

	/**
	 * Conversation network initializer
	 * array with Conversatiom obecs
	*/
	public static function create (array $chats_list): ?ConversationNetwork 
	{}

	public static function findById (int $id): ?ConversationNetwork
	{
		$network = new ConversationNetwork($id);

		if ($network->valid())
			return $network;

		return NULL;
	}
}

?>