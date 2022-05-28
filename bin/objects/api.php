<?php

require_once __DIR__ . '/token.php';
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/apiResponse.php';
require_once __DIR__ . '/../exceptions/apiException.php';

/**
 * API tools class
*/

class API
{
	private $currentConnection = NULL;
	private $accessKey         = NULL;
	private $boundApp          = NULL;
	private $isValid           = NULL;
	private $owner             = NULL;

	public function __construct ()
	{
		$this->currentConnection = DataBaseManager::getConnection();

		$this->isValid = false;

		$access_key = self::getRequestValue("key");

		$res = $this->currentConnection->cache('API_' . $access_key)->prepare("SELECT app_id, id FROM apps.tokens WHERE token = ? AND is_deleted = 0 LIMIT 1");
		if ($res->execute([$access_key]))
		{
			$data = $res->fetch(PDO::FETCH_ASSOC);
			if ($data)
			{
				$app = new App(intval($data['app_id']));
				if ($app->valid())
				{
					$token = new Token($app, intval($data['id']));
					if ($token->valid() && $token->getToken() === $access_key)
					{
						$entity = $token->auth();
						if (!$entity) return;
						
						$this->accessKey = $token;
						$this->boundApp  = $app;
						$this->owner     = $entity;
						$this->isValid   = true;

                        session_write_close();
                        $_SESSION = [];
                        $_SESSION['user_id'] = $this->owner->getId();
                        $_SESSION['session_id'] = $this->accessKey->getToken();
					}
				}
			}
		}
	}

	public function sendError (int $code, string $description): void
	{
		$error = new APIException($description, $code);

		$error->send($this);
	}

	public function valid (): bool
	{
        return $this->isValid;
	}

	public function getOwner (): ?Entity
	{
		return $this->owner;
	}

	public function getRequestedMethod (): string
	{
		return explode('/', explode('?', $_SERVER['REQUEST_URI'])[0])[1];
	}

	public function getRequestParams (): array
	{
		$excludes = ['key', 'v', 'auth'];

		$result = [];

		foreach (Request::get()->data as $index => $value) {
			if (in_array(strtolower($index), $excludes)) continue;

			$result[$index] = $value;
		}

		return $result;
	}

	public function callMethod (AbstractAPIMethod $method, callable $callback): API
	{
        if (!$method->isPublicMethod() && !$this->valid())
		{
			$callback(null, new APIException('Authentication failed: invalid session', -1));

			return $this;
		}

		if (!preg_match("/[^a-zA-Z.]/ui", $method))
		{
            $permissions = $method->isPublicMethod() ? [] : $this->getToken()->getPermissions();
            if (!$method->isPublicMethod() && !in_array(intval($method->getPermissionsGroup()), $permissions) && $method->getPermissionsGroup() !== 0)
            {
                $callback(null, new APIException('Authentication failed: this access key does not required permissions level: ' . $method->getPermissionsGroup(), -15));
                return $this;
            }

            if ($method->error) {
                $callback(null, $method->error);
                return $this;
            }

            try {
                $callback($method->run(), null);
            } catch (Exception $e)
            {
                $callback(null, $e);
            }

            $callback(null, new APIException('Internal server error', -10));
            return $this;
		} else 
		{
			$callback(null, new APIException('Internal server error', -10));
		}

		return $this;
	}

	public function getToken (): ?Token
	{
		return $this->accessKey;
	}

	public function setContentType (string $contentType): API
	{
		header("Content-Type: " . $contentType);

		return $this;
	}

	////////////////////////
	public static function getRequestValue (string $value)
	{
		if (isset(Request::get()->data[$value])) return Request::get()->data[$value];

		return $_SERVER['REQUEST_METHOD'] === 'GET' ? Request::get()->data[$value] : Request::get()->data[$value];
	}

	public static function get (): API
	{
		return isset($_SERVER['api']) && ($_SERVER['api'] instanceof API) ? $_SERVER['api'] : (function () {
			$_SERVER['api'] = new API();

			return $_SERVER['api'];
		})();
	}
}

?>