<?php

namespace unt\objects;

/**
 * Poll class
*/

class Poll extends Attachment
{
    ///////////////////////////////////////
    const ATTACHMENT_TYPE = 'poll';
    ///////////////////////////////////////

	private int $owner_id;
	private int $poll_id;
	private string $access_key;

	private bool $can_re_vote      = false;
	private bool $can_multi_select = false;
	private bool $is_anonymous     = false;

	private string $poll_title;

	private int $creation_time;
	private int $end_time;
	private int $voted_count = 0;

	private array $currentVariants = [];

	function __construct (int $owner_id, int $poll_id, string $access_key)
	{
        parent::__construct();

		$res = $this->currentConnection->prepare("SELECT polls.info.id, owner_id, access_key, title, isAnonymous, canRevote, canMultiSelect, endTime, creationTime, COUNT(DISTINCT user_id) AS voted FROM polls.info JOIN polls.users ON polls.info.id = polls.users.poll_id WHERE polls.info.id = ? AND owner_id = ? AND access_key = ? AND is_cancelled = 0 LIMIT 1");

		if ($res->execute([$poll_id, $owner_id, $access_key]))
		{
			$poll_data = $res->fetch(\PDO::FETCH_ASSOC);
			if ($poll_data)
			{
				$this->isValid = true;

				$this->poll_id     = intval($poll_data['id']);
				$this->owner_id    = intval($poll_data['owner_id']);
				$this->access_key  = strval($poll_data['access_key']);
				$this->poll_title  = strval($poll_data['title']);
				$this->voted_count = intval($poll_data['voted']);

				$this->can_re_vote       = boolval(intval($poll_data['canRevote']));
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
		return self::ATTACHMENT_TYPE;
	}

	public function getVotedCount (): int
	{
		return $this->voted_count;
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

	public function canReVote (): bool
	{
		return $this->can_re_vote;
	}

	public function canMultiSelect (): bool
	{
		return $this->can_multi_select;
	}

	public function isAnonymous (): bool
	{
		return $this->is_anonymous;
	}

	public function getEndTime (): int
	{
		return $this->end_time;
	}

	public function getCreationTime (): int
	{
		return $this->creation_time;
	}

	public function isVoted (): bool
	{
		return count($this->getSelectedAnswers()) > 0;
	}

	public function getVoters (int $variant_id, int $offset = 0, int $count = 30): array
	{
		$res = $this->currentConnection->prepare("SELECT DISTINCT user_id FROM polls.users WHERE poll_id = ? AND var_id = ? AND is_cancelled = 0 LIMIT ".$offset.",".$count);
		if ($res->execute([$this->getId(), $variant_id]))
		{
			$data = $res->fetchAll(\PDO::FETCH_ASSOC);

			$result = [];
			foreach ($data as $info) 
			{
				$user_id = intval($info['user_id']);

				$entity = User::findById($user_id);

				if ($entity)
					$result[] = $entity;
			}

			return $result;
		}

		return [];
	}

	public function toArray (): array
	{
		return [
			'type' => self::ATTACHMENT_TYPE,
			'poll' => [
				'id'         => $this->getId(),
				'owner_id'   => $this->getOwnerId(),
				'access_key' => $this->getAccessKey(),
				'data'       => [
					'title'            => $this->getTitle(),
					'can_revote'       => $this->canReVote(),
					'can_multi_select' => $this->canMultiSelect(),
					'is_anonymous'     => $this->isAnonymous(),
					'end_time'         => $this->getEndTime(),
					'voted'            => $this->getVotedCount()
				],
				'creation_time' => $this->getCreationTime(),
				'variants_list' => $this->getAnswers(),
				'voted_by_me'   => $this->isVoted()
			]
		];
	}

	public function getAnswers (): array
	{
		if (!$this->valid()) return [];

		$variants_selected = $this->getSelectedAnswers();

        $stats = NULL;
		if (count($variants_selected) > 0)
		{
			$stats = $this->getStats();
		}

		if (count($this->currentVariants) === 0)
		{
			$res = $this->currentConnection->prepare("SELECT title, var_id FROM polls.variants WHERE poll_id = ? LIMIT 10");
			
			if ($res->execute([$this->poll_id]))
			{
				$answers_list = $res->fetchAll(\PDO::FETCH_ASSOC);
				foreach ($answers_list as $answer)
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

	public function addAnswer (string $answer_text): bool
	{
		if (!$this->valid()) return false;

		$answer_id = count($this->currentVariants) + 1;
		$poll_id   = $this->getId();
		if ($answer_id > 10)
			return false;

		$res = $this->currentConnection->prepare("INSERT INTO polls.variants (poll_id, title, var_id) VALUES (?, ?, ?);");

		if ($res->execute([$poll_id, $answer_text, $answer_id]))
		{
			$this->currentVariants[] = [
				'id'    => $answer_id,
				'title' => $answer_text
			];

			return true;
		}

		return false;
	}

	public function editAnswer (int $answer_id): bool
	{
		return false;
	}

	public function removeAnswer (int $answer_id): bool
	{
		return false;
	}

	public function getSelectedAnswers (): array
	{
		if (isset($this->variants_selected) && is_array($this->variants_selected))
			return $this->variants_selected;

		$res = $this->currentConnection->prepare("SELECT DISTINCT var_id FROM polls.users WHERE poll_id = ? AND user_id = ? AND is_cancelled = 0 LIMIT 10");
		if ($res->execute([$this->getId(), intval($_SESSION['user_id'])]))
		{
			$data = $res->fetchAll(\PDO::FETCH_ASSOC);

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
			$data = $res->fetchAll(\PDO::FETCH_ASSOC);

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

	public function vote (int $answer_id): bool
	{
		$vars = $this->getAnswers();
		foreach ($vars as $variant) 
		{
			if ($variant['id'] === $answer_id)
			{
				if ($variant['selected']) return true;

				if (!$this->canMultiSelect() && count($this->getSelectedAnswers()) >= 1) return false;

				$res = $this->currentConnection->prepare("SELECT is_cancelled FROM polls.users WHERE poll_id = ? AND user_id = ? AND var_id = ? LIMIT 1");
				if ($res->execute([$this->getId(), intval($_SESSION['user_id']), intval($answer_id)]))
				{
					$is_cancelled = boolval(intval($res->fetch(\PDO::FETCH_ASSOC)['is_cancelled']));
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
		if (strlen($poll_title) > 64) return NULL;

		$owner_id = intval($_SESSION['user_id']);

		if (count($variants_list) > 10 || count($variants_list) < 1) return NULL;

		$res = \unt\platform\DataBaseManager::getConnection()->prepare("
			INSERT INTO 
				polls.info (
				            owner_id, 
				            access_key, 
				            title, 
				            isAnonymous, 
				            endTIme, 
				            canMultiSelect, 
				            canRevote, 
				            creationTime
				            ) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?);
		");

		$new_access_key = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 10);

		if ($res->execute([$owner_id, $new_access_key, $poll_title, intval($is_anonymous), $end_time, intval($multi_selection), intval($can_revote), time()]))
		{
			$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT LAST_INSERT_ID();");
			
			if ($res->execute())
			{
				$created_poll_id = intval($res->fetch(\PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

				$poll = new Poll($owner_id, $created_poll_id, $new_access_key);
				foreach ($variants_list as $variantTitle)
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