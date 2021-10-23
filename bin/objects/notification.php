<?php

require_once __DIR__ . '/../event_manager.php';

/**
 * Notification class
*/

class Notification extends EventEmitter
{
	private $currentConnection = NULL;

	private $id           = NULL;
	private $ownerId      = NULL;
	private $creationTime = NULL;

	private $type        = NULL;

	private $additionalData = NULL;

	private $isValid              = NULL;
	private $isNotificationRead   = NULL;
	private $isNotificationHidden = NULL;

	public function __construct (int $ownerId, int $notificationId)
	{
		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		$this->currentConnection = $connection;

		$res = $connection->prepare("SELECT type, local_id, data, is_read, is_hidden, owner_id FROM users.notes WHERE local_id = :local_id AND owner_id = :owner_id LIMIT 1;");
		$res->bindParam(":owner_id", $ownerId,        PDO::PARAM_INT);
		$res->bindParam(":local_id", $notificationId, PDO::PARAM_INT);
		
		if ($res->execute())
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
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
					$this->creationTime   = intval($getAdditionalData['time']);
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
		return strval($this->type);
	}

	public function getId (): int
	{
		return intval($this->id);
	}

	public function getCreationTime (): int
	{
		return intval($this->creationTime);
	}

	public function getOwnerId (): int
	{
		return intval($this->ownerId);
	}

	public function valid (): bool
	{
		return boolval($this->isValid);
	}

	public function isRead (): bool
	{
		return boolval($this->isNotificationRead);
	}

	public function isHidden (): bool
	{
		return boolval($this->isNotificationHidden);
	}

	public function read (): bool
	{
		if ($this->isRead()) return false;

		if ($this->currentConnection->prepare("UPDATE users.notes SET is_read = 1 WHERE local_id = ? AND owner_id = ? LIMIT 1")->execute([$this->getId(), $this->getOwnerId()]))
		{
			$this->isNotificationRead = true;
			$this->isNotificationHidden = true;
			
			$this->sendEvent([$this->getOwnerId()], [0], [
				'event' => 'notification_read',
				'data'  => $this->toArray()
			]);

			return true;
		}

		return false;
	}

	public function hide (): bool
	{
		if ($this->isRead() || $this->isHidden()) return false;

		if ($this->currentConnection->prepare("UPDATE users.notes SET is_hidden = 1 WHERE local_id = ? AND owner_id = ? LIMIT 1")->execute([$this->getId(), $this->getOwnerId()]))
		{
			$this->isNotificationHidden = true;

			$this->sendEvent([$this->getOwnerId()], [0], [
				'event' => 'notification_hide',
				'data'  => $this->toArray()
			]);

			return true;
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
	public static function create (Entity $entityForCreation, string $type, ?array $additionalData = NULL): ?Notification
	{
		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		return NULL;
	}

	public static function getList ($offset = 0, $count = 30): array
	{
		$offset = ($offset < 0 || $offset > 30) ? 0 : $offset;
		$count  = ($count < 0 || $count > 1000) ? 30 : $count;

		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		$result = [];

		$res = $connection->prepare("SELECT DISTINCT local_id FROM users.notes WHERE owner_id = ? AND is_read = 0 LIMIT ".intval($offset).",".intval($count).";");
		if ($res->execute([intval($_SESSION['user_id'])]))
		{
			$local_ids  = $res->fetchAll(PDO::FETCH_ASSOC);
			foreach ($local_ids as $index => $id)
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
		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		$res = $connection->prepare("SELECT COUNT(DISTINCT local_id) FROM users.notes WHERE owner_id = ? AND is_read = 0");
		if ($res->execute(intval($_SESSION['user_id'])))
		{
			$result = $res->fetch(PDO::FETCH_ASSOC);
			if ($result['COUNT(DISTINCT local_id'])
			{
				return intval($result['COUNT(DISTINCT local_id']);
			}
		}

		return 0;
	}
}

?>