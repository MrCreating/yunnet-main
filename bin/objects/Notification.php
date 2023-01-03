<?php

namespace unt\objects;

use unt\platform\EventManager;

/**
 * Notification class
*/

class Notification extends BaseObject
{
    /////////////////////////////
    /////////////////////////////

	private int $id;
	private int $ownerId;
	private int $creationTime;

	private string $type;

	private array $additionalData;

	private bool $isValid = false;
	private bool $isNotificationRead;
	private bool $isNotificationHidden;

	public function __construct (int $ownerId, int $notificationId)
	{
        parent::__construct();

		$res = $this->currentConnection->prepare("SELECT type, local_id, data, is_read, is_hidden, owner_id FROM users.notes WHERE local_id = ? AND owner_id = ? LIMIT 1");
		if ($res->execute([$ownerId, $notificationId]))
		{
			$data = $res->fetch(\PDO::FETCH_ASSOC);
			if ($data)
			{
				$this->isValid = true;

				$this->id      = intval($data['local_id']);
				$this->type    = strval($data['type']);
				$this->ownerId = intval($data['owner_id']);

				$this->isNotificationRead   = boolval(intval($data['is_read']));
				$this->isNotificationHidden = boolval(intval($data['is_hidden']));
				
				$additionalData = json_decode($data['data'], true);
				if ($additionalData)
				{
					$this->additionalData = $additionalData;
					$this->creationTime   = intval($additionalData['time']);
				}
			}
		}
	}

	public function getAdditionalData (): ?array
	{
		return $this->additionalData;
	}

	public function getType (): string
	{
		return $this->type;
	}

	public function getId (): int
	{
		return $this->id;
	}

	public function getCreationTime (): int
	{
		return $this->creationTime;
	}

	public function getOwnerId (): int
	{
		return $this->ownerId;
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

	public function isRead (): bool
	{
		return $this->isNotificationRead;
	}

	public function isHidden (): bool
	{
		return $this->isNotificationHidden;
	}

	public function read (): bool
	{
		if ($this->isRead()) return false;

		if ($this->currentConnection->prepare("UPDATE users.notes SET is_read = 1 WHERE local_id = ? AND owner_id = ? LIMIT 1")->execute([$this->getId(), $this->getOwnerId()]))
		{
			$this->isNotificationRead = true;
			$this->isNotificationHidden = true;

			return EventManager::event([$this->getOwnerId()], [
                'event' => 'notification_read',
                'data'  => $this->toArray()
            ]);
		}

		return false;
	}

	public function hide (): bool
	{
		if ($this->isRead() || $this->isHidden()) return false;

		if ($this->currentConnection->prepare("UPDATE users.notes SET is_hidden = 1 WHERE local_id = ? AND owner_id = ? LIMIT 1")->execute([$this->getId(), $this->getOwnerId()]))
		{
			$this->isNotificationHidden = true;

			return EventManager::event([$this->getOwnerId()], [
                'event' => 'notification_hide',
                'data'  => $this->toArray()
            ]);
		}

		return false;
	}

	public function toArray (): array
	{
		$result = [
			'id'   => $this->getId(),
			'type' => $this->getType()
		];

		if ($this->getAdditionalData())
			$result['data'] = $this->getAdditionalData();

		if ($this->isRead())
			$result['is_read'] = true;
		if ($this->isHidden())
			$result['is_hidden'] = true;

		return $result;
	}

	//////////////////////////////////////
	public static function create (int $to_id, string $type, ?array $additionalData = NULL): ?Notification
	{
		$entity = User::findById($to_id);

		if (!$entity) return NULL;

		$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT DISTINCT local_id FROM users.notes WHERE owner_id = ? ORDER BY local_id DESC LIMIT 1");

		if ($res->execute([$to_id]))
		{
			$new_local_id = intval($res->fetch(\PDO::FETCH_ASSOC)["local_id"]) + 1;

			$res = \unt\platform\DataBaseManager::getConnection()->prepare("INSERT INTO users.notes (owner_id, local_id, type, data, is_read) VALUES (?, ?, ?, ?, 0)");

			if ($res->execute([$to_id, $new_local_id, $type, ($additionalData ? json_encode($additionalData) : json_encode([]))]))
			{
				$result = new Notification($to_id, $new_local_id);
				if ($result->valid())
				{
					if ($entity->getSettings()->getSettingsGroup(Settings::PUSH_GROUP)->isNotificationsEnabled())
					{
                        EventManager::event([$to_id], [
                            'event'        => 'new_notification',
                            'notification' => $result->toArray()
                        ]);
					}
				}

				return $result;
			}
		}

		return NULL;
	}

	public static function getList (int $offset = 0, int $count = 30): array
	{
		$offset = ($offset < 0 || $offset > 30) ? 0 : $offset;
		$count  = ($count < 0 || $count > 1000) ? 30 : $count;

		$result = [];

		$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT DISTINCT local_id FROM users.notes WHERE owner_id = ? AND is_read = 0 LIMIT ".intval($offset).",".intval($count).";");
		if ($res->execute([intval($_SESSION['user_id'])]))
		{
			$local_ids  = $res->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($local_ids as $id)
			{
				$notification = new Notification(intval($_SESSION['user_id']), intval($id['local_id']));
				if ($notification->valid())
				{
					$result[] = $notification;
				}
			}
		}

		return array_reverse($result);
	}

	public static function getUnreadCount (): int
	{
		$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT COUNT(DISTINCT local_id) FROM users.notes WHERE owner_id = ? AND is_read = 0");
		if ($res->execute(intval($_SESSION['user_id'])))
		{
			$result = $res->fetch(\PDO::FETCH_ASSOC);
			if ($result['COUNT(DISTINCT local_id'])
			{
				return intval($result['COUNT(DISTINCT local_id']);
			}
		}

		return 0;
	}
}

?>