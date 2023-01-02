<?php

namespace unt\objects;

/**
 * Class for Security settings
*/

class SecuritySettingsGroup extends SettingsGroup
{
	public function __construct (Entity $user, array $params = [])
	{
        parent::__construct($user, Settings::SECURITY_GROUP, $params);
	}

	public function setPassword (string $newPassword): bool
	{
		$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

		$result = $this->currentConnection->prepare("UPDATE users.info SET password = ? WHERE id = ? LIMIT 1")->execute([
			$passwordHash,
			$this->entity->getId()
		]);

		if ($result)
		{
			if ($this->currentConnection->prepare("UPDATE apps.tokens SET is_deleted = 1 WHERE user_id = ?")->execute([$this->entity->getId()]))
			{
				$sessions = Session::getList();
				foreach ($sessions as $session) {
					$session->end();
				}
			}
		}

		return $result;
	}

	public function isPasswordCorrect (string $password): bool
	{
		if (is_empty($password) || strlen($password) < 6 || strlen($password) > 64) return false;

		$res = $this->currentConnection->prepare("SELECT password FROM users.info WHERE id = ? LIMIT 1");
		if ($res->execute([$this->entity->getId()]))
		{
			$passwordHash = $res->fetch(\PDO::FETCH_ASSOC)['password'];
			if (!$passwordHash)
				return false;

			return password_verify($password, $passwordHash);
		}

		return false;
	}

	public function toArray (): array
	{
		return [];
	}
}
?>