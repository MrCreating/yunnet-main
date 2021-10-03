<?php

require_once __DIR__ . '/attachment.php';

/**
 * Poll class
*/

class Poll extends Attachment
{
	private $owner_id   = 0;
	private $poll_id    = 0;
	private $access_key = 0;

	private $can_revote       = false;
	private $can_multi_select = false;
	private $is_anonymous     = false;
	private $poll_title       = '';

	private $creation_time    = 0;
	private $end_time         = 0;

	private $currentConnection = false;

	private $currentVariants   = [];

	function __construct ($owner_id, $poll_id, $access_key)
	{
		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		$this->currentConnection = $connection;

		$res = $connection->prepare("SELECT id, owner_id, access_key, title, isAnonymous, canRevote, canMultiSelect, endTime, creationTime FROM polls.info WHERE id = :id AND owner_id = :owner_id AND access_key = :access_key LIMIT 1;");

		$p_owner_id   = intval($owner_id);
		$p_poll_id    = intval($poll_id);
		$p_access_key = strval($access_key);

		$res->bindParam(":id",         $p_poll_id,    PDO::PARAM_INT);
		$res->bindParam(":owner_id",   $p_owner_id,   PDO::PARAM_INT);
		$res->bindParam(":access_key", $p_access_key, PDO::PARAM_STR);

		if ($res->execute())
		{
			$poll_data = $res->fetch(PDO::FETCH_ASSOC);
			if ($poll_data)
			{
				$this->isValid = true;

				$this->poll_id    = intval($poll_data['id']);
				$this->owner_id   = intval($poll_data['owner_id']);
				$this->access_key = strval($poll_data['access_key']);
				$this->poll_title = strval($poll_data['title']);

				$this->can_revote       = boolval(intval($poll_data['canRevote']));
				$this->can_multi_select = boolval(intval($poll_data['canMultiSelect']));
				$this->is_anonymous     = boolval(intval($poll_data['isAnonymous']));

				$this->creation_time = intval($poll_data['creationTime']);
				$this->end_time      = intval($poll_data['endTime']);

				$this->getAnswers();
			}
		}
	}

	public function getType (): string
	{
		return "poll";
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
	}

	public function getId (): int
	{
		return $this->poll_id;
	}

	public function getAccessKey (): string
	{
		return $this->access_key;
	}

	public function getTitle (): string
	{
		return $this->poll_title;
	}

	public function canRevote (): bool
	{
		return boolval($this->can_revote);
	}

	public function canMultiSelect (): bool
	{
		return boolval($this->can_multi_select);
	}

	public function isAnonymous (): bool
	{
		return boolval($this->is_anonymous);
	}

	public function getEndTime (): int
	{
		return intval($this->end_time);
	}

	public function getCreationTime (): int
	{
		return intval($this->creation_time);
	}

	public function toArray (): array
	{
		return [
			'type' => 'poll',
			'poll' => [
				'id'         => $this->getId(),
				'owner_id'   => $this->getOwnerId(),
				'access_key' => $this->getAccessKey(),
				'data'       => [
					'title'            => $this->getTitle(),
					'can_revote'       => $this->canRevote(),
					'can_multi_select' => $this->canMultiSelect(),
					'is_anonymous'     => $this->isAnonymous(),
					'end_time'         => $this->getEndTime()
				],
				'creation_time' => $this->getCreationTime(),
				'variants_list' => $this->getAnswers()
			]
		];
	}

	public function getAnswers (): array
	{
		if (!$this->isValid) return false;

		$connection = $_SERVER['dbConnection'];

		if (count($this->currentVariants) === 0)
		{
			$res = $connection->prepare("SELECT poll_id, title, var_id FROM polls.variants WHERE poll_id = ? LIMIT 10;");
			
			if ($res->execute([$this->poll_id]))
			{
				$answers_list = $res->fetchAll(PDO::FETCH_ASSOC);
				foreach ($answers_list as $index => $answer)
				{
					$this->currentVariants[] = [
						'id'   => intval($answer['var_id']),
						'text' => strval($answer['title'])
					];
				}
			}
		}

		return $this->currentVariants;
	}

	public function addAnswer ($answer_text): bool
	{
		if (!$this->isValid) return false;

		$connection = $_SERVER['dbConnection'];

		$answer_id = count($this->currentVariants) + 1;
		$poll_id   = $this->getId();
		if ($answer_id > 10)
			return false;

		$res = $connection->prepare("INSERT INTO polls.variants (poll_id, title, var_id) VALUES (:poll_id, :title, :var_id);");

		$res->bindParam(":poll_id", $poll_id,     PDO::PARAM_INT);
		$res->bindParam(":title",   $answer_text, PDO::PARAM_STR);
		$res->bindParam(":var_id",  $answer_id,   PDO::PARAM_INT);

		if ($res->execute())
		{
			$this->currentVariants[] = [
				'id'    => $answer_id,
				'title' => $answer_text
			];

			return true;
		}

		return false;
	}

	public function editAnswer ($answer_id): bool
	{
		return false;
	}

	public function removeAnswer ($answer_id): bool
	{
		return false;
	}

	public function vote ($user_id, $answer_id): bool
	{
		return false;
	}

	public function getCredentials (): string
	{
		return $this->getType() . $this->getOwnerId() . '_' . $this->getId() . '_' . $this->getAccessKey();
	}
}

?>