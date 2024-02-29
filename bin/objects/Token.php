<?php

namespace unt\objects;

/**
 * Access key (token) class
*/

class Token extends BaseObject
{
	protected int $id;
	protected ?App $bound_app = NULL;

	protected int $owner_id;

	private string $value;
	private array $permissions;

	private bool $isValid = false;

	function __construct (?App $bound_app, int $id)
	{
        parent::__construct();

		if ($bound_app && !$bound_app->valid()) return;

		$this->bound_app = $bound_app;

		$res = $this->currentConnection->prepare("SELECT id, token, permissions, owner_id FROM apps.tokens WHERE id = ? AND app_id = ? AND is_deleted = 0 LIMIT 1");

		if ($res->execute([$id, ($bound_app ? $bound_app->getId() : 0)]))
		{
			$data = $res->fetch(\PDO::FETCH_ASSOC);

			if ($data)
			{
				$this->isValid = true;

				$this->value       = strval($data['token']);
				$this->owner_id    = intval($data['owner_id']);
				$this->id          = intval($data['id']);
				$this->permissions = [];

				$permissions = explode(',', $data['permissions']);
				foreach ($permissions as $permission_id)
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
		return $this->currentConnection->prepare("UPDATE apps.tokens SET is_deleted = 1 WHERE id = ? LIMIT 1")->execute([$this->getId()]);
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
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
		$res = $this->currentConnection->prepare('UPDATE apps.tokens SET permissions = ? WHERE id = ? LIMIT 1');

		return $res->execute([implode(',', $this->getPermissions()), $this->getId()]);
	}

	public function auth (): ?Entity
	{
		if (!$this->valid()) return NULL;

		$entity = $this->getOwnerId() > 0 ? User::findById($this->getOwnerId()) : Bot::findById($this->getOwnerId());

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
        return $this->isValid;
	}
}

?>
