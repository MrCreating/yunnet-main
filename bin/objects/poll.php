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
	private $voted_count      = 0;

	private $currentConnection = false;

	private $currentVariants   = [];

	function __construct ($owner_id, $poll_id, $access_key)
	{
		$this->currentConnection = DataBaseManager::getConnection();

		$res = $this->currentConnection->prepare("SELECT polls.info.id, owner_id, access_key, title, isAnonymous, canRevote, canMultiSelect, endTime, creationTime, COUNT(DISTINCT user_id) AS voted FROM polls.info JOIN polls.users ON polls.info.id = polls.users.poll_id WHERE polls.info.id = :id AND owner_id = :owner_id AND access_key = :access_key AND is_cancelled = 0 LIMIT 1");

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

				$this->poll_id     = intval($poll_data['id']);
				$this->owner_id    = intval($poll_data['owner_id']);
				$this->access_key  = strval($poll_data['access_key']);
				$this->poll_title  = strval($poll_data['title']);
				$this->voted_count = intval($poll_data['voted']);

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

	public function getVotedCount (): int
	{
		return intval($this->voted_count);
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
					'end_time'         => $this->getEndTime(),
					'voted'            => $this->getVotedCount()
				],
				'creation_time' => $this->getCreationTime(),
				'variants_list' => $this->getAnswers(),
				'voted_by_me'   => count($this->getSelectedAnswers()) > 0
			]
		];
	}

	public function getAnswers (): array
	{
		if (!$this->valid()) return false;

		$variants_selected = $this->getSelectedAnswers();

		if (count($variants_selected) > 0)
		{
			$stats = $this->getStats();
		}

		if (count($this->currentVariants) === 0)
		{
			$res = $this->currentConnection->prepare("SELECT title, var_id FROM polls.variants WHERE poll_id = ? LIMIT 10");
			
			if ($res->execute([$this->poll_id]))
			{
				$answers_list = $res->fetchAll(PDO::FETCH_ASSOC);
				foreach ($answers_list as $index => $answer)
				{
					$var_info = [
						'id'       => intval($answer['var_id']),
						'text'     => strval($answer['title']),
						'selected' => in_array(intval($answer['var_id']), $variants_selected)
					];

					if ($stats)
						$var_info['count'] = intval($stats[intval($answer['var_id'])]['count']);

					$this->currentVariants[] = $var_info;
				}
			}
		}

		return $this->currentVariants;
	}

	public function addAnswer ($answer_text): bool
	{
		if (!$this->valid()) return false;

		$answer_id = count($this->currentVariants) + 1;
		$poll_id   = $this->getId();
		if ($answer_id > 10)
			return false;

		$res = $this->currentConnection->prepare("INSERT INTO polls.variants (poll_id, title, var_id) VALUES (:poll_id, :title, :var_id);");

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

	public function getSelectedAnswers (): array
	{
		if ($this->variants_selected)
			return $this->variants_selected;

		$res = $this->currentConnection->prepare("SELECT DISTINCT var_id FROM polls.users WHERE poll_id = ? AND user_id = ? AND is_cancelled = 0 LIMIT 10");
		if ($res->execute([$this->getId(), intval($_SESSION['user_id'])]))
		{
			$data = $res->fetchAll(PDO::FETCH_ASSOC);

			$result = [];

			foreach ($data as $index => $var_info) {
				$result[] = intval($var_info['var_id']);
			}

			$this->variants_selected = $result;

			return $result;
		}

		return [];
	}

	public function getStats (): ?array
	{
		$res = $this->currentConnection->prepare('SELECT polls.users.var_id AS id, polls.variants.title, COUNT(DISTINCT polls.users.user_id) AS voted FROM polls.users JOIN polls.variants ON polls.users.var_id = polls.variants.var_id WHERE is_cancelled = 0 AND polls.variants.poll_id = ? AND polls.users.poll_id = ? GROUP BY polls.users.var_id, polls.variants.title');

		if ($res->execute([$this->getId(), $this->getId()]))
		{
			$data = $res->fetchAll(PDO::FETCH_ASSOC);

			$result = [];
			foreach ($data as $stat) 
			{
				$result[intval($stat['id'])] = [
					'id'    => intval($stat['id']),
					'title' => strval($stat['title']),
					'count' => intval($stat['voted'])
				];
			}

			return $result;
		}

		return NULL;
	}

	public function vote ($answer_id): bool
	{
		$vars = $this->getAnswers();
		foreach ($vars as $variant) 
		{
			if ($variant['id'] === intval($answer_id))
			{
				if ($variant['selected']) return true;

				if (!$this->canMultiSelect() && count($this->getSelectedAnswers()) >= 1) return false;

				$res = $this->currentConnection->prepare("SELECT is_cancelled FROM polls.users WHERE poll_id = ? AND user_id = ? AND var_id = ? LIMIT 1");
				if ($res->execute([$this->getId(), intval($_SESSION['user_id']), intval($answer_id)]))
				{
					$is_cancelled = boolval(intval($res->fetch(PDO::FETCH_ASSOC)['is_cancelled']));
					if ($is_cancelled)
					{
						return $this->currentConnection->prepare("UPDATE polls.users SET is_cancelled = 0 WHERE poll_id = ? AND user_id = ? AND var_id = ? LIMIT 1")->execute([$this->getId(), intval($_SESSION['user_id']), intval($answer_id)]);
					} else
					{
						return $this->currentConnection->prepare("INSERT INTO polls.users (poll_id, var_id, user_id, is_cancelled) VALUES (?, ?, ?, 0)")->execute([$this->getId(), intval($answer_id), intval($_SESSION['user_id'])]);
					}
				}
			}
		}

		return false;
	}

	public function getCredentials (): string
	{
		return $this->getType() . $this->getOwnerId() . '_' . $this->getId() . '_' . $this->getAccessKey();
	}

	//////////////////////////////////////////
	public static function create (string $poll_title, array $variants_list, int $end_time = 0, bool $is_anonymous = false, bool $multi_selection = false, bool $can_revote = true): ?Poll
	{
		if (is_empty($poll_title)) return NULL;
		if (strlen($title) > 64)   return NULL;

		$owner_id = intval($_SESSION['user_id']);

		if (count($variants_list) > 10 || count($variants_list) < 1) return NULL;

		$res = DataBaseManager::getConnection()->prepare("
			INSERT INTO 
				polls.info (owner_id, access_key, title, isAnonymous, endTIme, canMultiSelect, canRevote, creationTime) 
			VALUES (:owner_id, :access_key, :title, :isAnonymous, :endTime, :canMultiSelect, :canRevote, :creationTime);
		");

		$new_access_key = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 10);

		$p_owner_id        = intval($owner_id);
		$p_poll_title      = strval($poll_title);
		$p_is_anonymous    = intval(boolval($is_anonymous));
		$p_end_time        = intval($end_time);
		$p_multi_selection = intval(boolval($multi_selection));
		$p_can_revote      = intval(boolval($can_revote));
		$p_creation_time   = intval(time());

		$res->bindParam(":owner_id",       $p_owner_id,        PDO::PARAM_INT);
		$res->bindParam(":access_key",     $new_access_key,    PDO::PARAM_STR);
		$res->bindParam(":title",          $p_poll_title,      PDO::PARAM_STR);
		$res->bindParam(":isAnonymous",    $p_is_anonymous,    PDO::PARAM_INT);
		$res->bindParam(":endTime",        $p_end_time,        PDO::PARAM_INT);
		$res->bindParam(":canMultiSelect", $p_multi_selection, PDO::PARAM_INT);
		$res->bindParam(":canRevote",      $p_can_revote,      PDO::PARAM_INT);
		$res->bindParam(":creationTime",   $p_creation_time,   PDO::PARAM_INT);

		if ($res->execute())
		{
			$res = DataBaseManager::getConnection()->prepare("SELECT LAST_INSERT_ID();");
			
			if ($res->execute())
			{
				$created_poll_id = intval($res->fetch(PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

				$poll = new Poll($p_owner_id, $created_poll_id, $new_access_key);
				foreach ($variants_list as $index => $variantTitle) 
				{
					$poll->addAnswer($variantTitle);
				}

				return $poll;
			}
		}

		return NULL;
	}
}

?>