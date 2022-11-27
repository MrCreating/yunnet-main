<?php

require_once __DIR__ . '/SettingsGroup.php';

/**
 * Class for Security settings
*/

class SecuritySettingsGroup extends SettingsGroup
{
	protected $currentConnection = NULL;

	public function __construct (Entity $user, DataBaseManager $connection, array $params = [])
	{
		$this->currentEntity     = $user;
		$this->currentConnection = $connection;
	}

	public function setPassword (string $newPassword): bool
	{
		$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

		$result = $this->currentConnection->uncache('User_' . $this->currentEntity->getId())->prepare("UPDATE users.info SET password = ? WHERE id = ? LIMIT 1")->execute([
			$passwordHash,
			$this->currentEntity->getId()
		]);

		if ($result)
		{
			if ($this->currentConnection->uncache('User_' . $this->currentEntity->getId())->prepare("UPDATE apps.tokens SET is_deleted = 1 WHERE user_id = ?")->execute([$this->currentEntity->getId()]))
			{
				$sessions = Session::getList();
				foreach ($sessions as $index => $session) {
					$session->end();
				}
			}
		}

		return $result;
	}

	public function isPasswordCorrect (string $password): bool
	{
		if (unt\functions\is_empty($password) || strlen($password) < 6 || strlen($password) > 64) return false;

		$res = $this->currentConnection->prepare("SELECT password FROM users.info WHERE id = ? LIMIT 1");
		if ($res->execute([$this->currentEntity->getId()]))
		{
			$passwordHash = $res->fetch(PDO::FETCH_ASSOC)['password'];
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