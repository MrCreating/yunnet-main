<?php

/**
 * Access key (token) class
*/

class Token
{
	protected $id        = NULL;
	protected $bound_app = NULL;

	protected $owner_id  = NULL;

	private $value       = NULL;
	private $permissions = NULL;

	private $isValid = false;

	private $currentConnection = NULL;

	function __construct (?App $bound_app, int $id)
	{
		$connection = $_SERVER['dbConnection'];
		if (!$connection)
			$connection = get_database_connection();

		$this->currentConnection = $connection;
		if (!$bound_app->valid()) return;
		$this->bound_app = $bound_app;

		$res = $connection->prepare("SELECT id, token, permissions, owner_id FROM apps.tokens WHERE id = :id AND app_id = :app_id AND is_deleted = 0 LIMIT 1;");

		$token_id = intval($id);
		$app_id   = $bound_app ? intval($bound_app->getId()) : 0;

		$res->bindParam(":id",     $token_id, PDO::PARAM_INT);
		$res->bindParam(":app_id", $app_id,   PDO::PARAM_INT);

		if ($res->execute())
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
			if ($data)
			{
				$this->isValid = true;

				$this->value       = strval($data['token']);
				$this->owner_id    = intval($data['owner_id']);
				$this->id          = intval($data['id']);
				$this->permissions = [];

				$permissions = explode(',', $data['permissions']);
				foreach ($permissions as $index => $permission_id)
				{
					$permission = intval($permission_id);

					if ($permission < 1 || $permission > 4) continue;
					$this->permissions[] = $permission;	
				}
			}
		}
	}

	public function delete (): bool
	{}

	public function getOwnerId (): int
	{
		return intval($this->owner_id);
	}

	public function getPermissions (): array
	{
		return $this->permissions;
	}

	public function setPermissions (array $permissions): Token
	{
		$result = [];

		foreach ($permissions as $index => $permission_id) {
			if ($index >= 4) break;

			$permission = intval($permission_id);
			if ($permission < 1 || $permission > 4) continue;

			$result[] = $permission;
		}

		$this->permissions = $result;

		return $this;
	}

	public function apply (): bool
	{}

	public function toArray (): array
	{}

	public function valid (): bool
	{
		return boolval($this->isValid);
	}
}

?>