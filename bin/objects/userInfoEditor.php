<?php

require_once __DIR__ . '/infoEditor.php';

/**
 * User profile info editor
*/

class UserInfoEditor extends InfoEditor 
{
	private $firstName     = NULL;
	private $lastName      = NULL;
	private $screenName    = NULL;
	private $currentPhoto  = NULL;
	private $currentGender = NULL;

	public function __construct (User $user)
	{
		parent::__construct($user);

		$this->currentPhoto  = $user->getCurrentPhoto();
		$this->firstName     = $user->getFirstName();
		$this->lastName      = $user->getLastName();
		$this->screenName    = $user->getScreenName();
		$this->currentGender = $user->getGender();
	}

	public function setStatus (?string $newStatus = NULL): bool
	{
		if (!$this->getBoundEntity()->valid() || $this->getBoundEntity()->isBanned()) return false;

		if (!$newStatus || is_empty($newStatus))
		{
			return $this->currentConnection->prepare("UPDATE users.info SET status = NULL WHERE id = ? AND is_deleted = 0 AND is_banned = 0 LIMIT 1")->execute([$this->getBoundEntity()->getId()]);
		} else
		{
			if (strlen($newStatus) < 128)
			{
				return $this->currentConnection->prepare("UPDATE users.info SET status = ? WHERE id = ? AND is_deleted = 0 AND is_banned = 0 LIMIT 1;")->execute([trim($newStatus), $this->getBoundEntity()->getId()]);
			}
		}

		return false;
	}

	public function setPhoto (?Photo $newPhoto = NULL)
	{
		if (!$this->getBoundEntity()->valid() || $this->getBoundEntity()->isBanned()) return false;

		if (!$newPhoto)
		{
			$this->currentPhoto = NULL;
		} else
		{
			if ($newPhoto->valid())
				$this->currentPhoto = $newPhoto;
			else
				return false;
		}

		$res = $this->currentConnection->prepare("UPDATE users.info SET photo_path = " . ($this->currentPhoto ? "?" : "NULL") . " WHERE id = ? AND is_deleted = 0 AND is_banned = 0 LIMIT 1;");

		$result = $this->currentPhoto ? 
									 $res->execute([$this->currentPhoto->getQuery(), $this->getBoundEntity()->getId()])
								   :
								     $res->execute([$this->getBoundEntity()->getId()]);

		if ($result)
			return $this->currentPhoto ? $this->currentPhoto : true;
		else
			return false;
	}

	public function setFirstName (string $firstName): int
	{
		if (!$this->getBoundEntity()->valid() || $this->getBoundEntity()->isBanned()) return false;

		if (is_empty($firstName)) return -3;
		if (strlen($firstName) < 2 || strlen($firstName) > 32) return -2;
		if (preg_match("/[^a-zA-Zа-яА-ЯёЁ'-]/ui", $firstName)) return -1;

		$this->firstName = capitalize($firstName);

		return (int) $this->currentConnection->prepare("UPDATE users.info SET first_name = ? WHERE id = ? AND is_deleted = 0 AND is_banned = 0 LIMIT 1;")->execute([$this->firstName, $this->getBoundEntity()->getId()]);
	}

	public function setLastName (string $lastName): int
	{
		if (!$this->getBoundEntity()->valid() || $this->getBoundEntity()->isBanned()) return false;

		if (is_empty($lastName)) return -3;
		if (strlen($lastName) < 2 || strlen($lastName) > 32) return -2;
		if (preg_match("/[^a-zA-Zа-яА-ЯёЁ'-]/ui", $lastName)) return -1;

		$this->lastName = capitalize($lastName);

		return (int) $this->currentConnection->prepare("UPDATE users.info SET last_name = ? WHERE id = ? AND is_deleted = 0 AND is_banned = 0 LIMIT 1;")->execute([$this->lastName, $this->getBoundEntity()->getId()]);
	}

	public function setScreenName (?string $screenName = NULL): int
	{
		if (!$this->getBoundEntity()->valid() || $this->getBoundEntity()->isBanned()) return false;

		if (!$screenName)
		{
			$this->screenName = $screenName;
		} else
		{
			if (is_empty($screenName) || strlen($screenName) < 6 || strlen($screenName) > 64) return 0;

			if (!preg_match("/^[a-z]{1}[a-z_\d\s]*[a-z_\s\d]{1}$/i", $screenName)) return 0;

			if (Project::isLinkUsed($screenName)) return -1;

			$this->screenName = $screenName;
		}

		$res = $this->currentConnection->prepare("UPDATE users.info SET screen_name = " . ($this->screenName ? "?" : "NULL") . " WHERE id = ? AND is_deleted = 0 AND is_banned = 0 LIMIT 1;");

		return $this->screenName ? 
									$res->execute([$this->screenName, $this->getBoundEntity()->getId()])
								 :
								 	$res->execute([$this->getBoundEntity()->getId()]);
	}

	public function setGender (int $gender): bool
	{
		if (!$this->getBoundEntity()->valid() || $this->getBoundEntity()->isBanned()) return false;

		if ($gender !== 1 && $gender !== 2) return false;

		$this->currentGender = $gender;

		return $this->currentConnection->prepare("UPDATE users.info SET gender = ? WHERE id = ? LIMIT 1")->execute([$this->currentGender, $this->getBoundEntity()->getId()]);
	}
}

?>