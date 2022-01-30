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
		$this->currentConnection = DataBaseManager::getConnection();
		if ($ound_app && !$bound_app->valid()) return;

		$this->bound_app = $bound_app;

		$res = $this->currentConnection->cache('Token_' . $id . '_' . $bound_app ? $bound_app->getId() : 0)->prepare("SELECT id, token, permissions, owner_id FROM apps.tokens WHERE id = :id AND app_id = :app_id AND is_deleted = 0 LIMIT 1;");

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

	public function getToken (): string
	{
		return $this->value;
	}

	public function delete (): bool
	{
		return $this->currentConnection->uncache('Token_' . $this->getId() . '_' . $this->bound_app ? $this->bound_app->getId() : 0)->prepare("UPDATE apps.tokens SET is_deleted = 1 WHERE id = ? LIMIT 1")->execute([$this->getId()]);
	}

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

	public function getId(): int
	{
		return $this->id;
	}

	public function apply (): bool
	{
		$res = $this->currentConnection->uncache('Token_' . $this->getId() . '_' . $bound_app->getId())->prepare('UPDATE apps.tokens SET permissions = :new_permissions WHERE id = :id LIMIT 1');

		$res->bindParam(":new_permissions", implode(',', $this->getPermissions()), PDO::PARAM_STR);
		$res->bindParam(":id",              $this->getId(),                        PDO::PARAM_INT);

		return $res->execute();
	}

	public function auth (): ?Entity
	{
		if (!$this->valid()) return NULL;

		$entity = Entity::findById($this->getOwnerId());

		if (!$entity->valid()) return NULL;

		session_write_close();

		$_SESSION = [];
		$_SESSION['user_id']    = $this->getOwnerId();
		$_SESSION['session_id'] = $this->getToken();

		return $entity;
	}

	public function toArray (): array
	{
		return [
			'id'          => $this->getId(),
			'owner_id'    => $this->getOwnerId(),
			'token'       => $this->getToken(),
			'permissions' => $this->getPermissions()
		];
	}

	public function valid (): bool
	{
		return boolval($this->isValid);
	}
}

?>