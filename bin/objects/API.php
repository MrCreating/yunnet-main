<?php

namespace unt\objects;

use PDO;
use unt\exceptions\APIException;

/**
 * API tools class
*/

class API extends BaseObject
{
	private ?Token $accessKey  = NULL;
	private ?App $boundApp     = NULL;
	private bool $isValid      = false;
	private ?Entity $owner     = NULL;

	public function __construct ()
	{
        parent::__construct();

		parse_str(explode('?', $_SERVER['REQUEST_URI'])[1], $_REQUEST);
		$_REQUEST = array_merge($_REQUEST, array_merge($_GET, $_POST));

		$this->isValid = false;

		$access_key = self::getRequestValue("key");

		$res = $this->currentConnection->prepare("SELECT app_id, id FROM apps.tokens WHERE token = ? AND is_deleted = 0 LIMIT 1");
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
						$entity = Entity::findById($token->getOwnerId());
						if (!$entity || !$entity->valid()) return;
						
						$this->accessKey = $token;
						$this->boundApp  = $app;
						$this->owner     = $entity;
						$this->isValid   = true;

						session_write_close();

						$_SESSION = [];
						$_SESSION['user_id']    = $this->accessKey->getOwnerId();
						$_SESSION['session_id'] = $token->getToken();
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

	public function getOwner (): Entity
	{
		return $this->owner;
	}

    public function getApp (): ?App
    {
        return $this->boundApp;
    }

	public function getRequestedMethod (): string
	{
		return explode('/', explode('?', $_SERVER['REQUEST_URI'])[0])[1];
	}

	public function getRequestParams (): array
	{
		$excludes = ['key', 'v', 'auth'];

		$result = [];

		foreach ($_REQUEST as $index => $value) {
			if (in_array(strtolower($index), $excludes)) continue;

			$result[$index] = $value;
		}

		return $result;
	}

	public function callMethod (string $method, array $params, callable $callback): API
	{
		if (!$this->valid())
		{
			$callback(null, new APIException('Authentication failed: invalid session', -1));

			return $this;
		}

		if (!preg_match("/[^a-zA-Z.]/ui", $method))
		{
			$method_data = explode('.', $method, 2);
			if (count($method_data) < 2)
			{
				$callback(null, new APIException('Method format is incorrect', -5));

				return $this;
			}
			
			$method_group = strtolower($method_data[0]);
			$method_name  = strtolower($method_data[1]);

			$method_path = __DIR__ . '/../../api/methods/' . $method_group . '/' . $method_name . '.php';
			if (file_exists($method_path))
			{
				try 
				{
					require_once $method_path;

					if (!isset($method_permissions_group) || !isset($method_params) || !function_exists('call') || !is_array($method_params))
					{
						$callback(null, new APIException('Method is invalid', 0));
						return $this;
					}

					$permissions = $this->getToken()->getPermissions();
					if (!in_array(intval($method_permissions_group), $permissions) && $method_permissions_group !== 0)
					{
						$callback(null, new APIException('Authentication failed: this access key does not required permissions level: ' . $method_permissions_group, -15));
						return $this;
					}

					$params = $this->getRequestParams();
					$resulted_params = [];

					foreach ($method_params as $name => $param)
					{
						if (boolval(intval($param['required'])) && !isset($params[$name]))
						{
							$callback(null, new APIException('Some parameters was missing: ' . $name . ' is required', -4));
							return $this;
						}

						if (!isset($params[$name])) continue;

						switch ($param['type']) {
							case 'integer':
								$resulted_params[$name] = intval($params[$name]);
							break;

                            case 'boolean':
								$resulted_params[$name] = boolval(intval($params[$name]));
							break;

							case 'json':
								$param_result = json_decode($params[$name]);
								if (!$param_result)
								{
									$callback(null, new APIException('Some parameters was invalid: ' . $name . ' has invalid JSON syntax', -4));
									return $this;
								}

								$resulted_params[$name] = json_decode($params[$name]);
							break;
							
							default:
								$resulted_params[$name] = strval($params[$name]);
							break;
						}
					}

					$method_result = call($this, $resulted_params);
					if ($method_result instanceof APIResponse)
					{
						$callback($method_result, null);
						return $this;
					}
					elseif ($method_result instanceof APIException)
					{
						$callback(null, $method_result);
						return $this;
					}

					$callback(null, new APIException('Internal server error', -10));
					return $this;
				} catch (Exception $e)
				{
					$callback(null, new APIException('Internal server error', -10));
					return $this;
				}
			} else
			{
				$callback(null, new APIException('Method not found', 0));
			}
		} else 
		{
			$callback(null, new APIException('Internal server error', -10));
		}

		return $this;
	}

	public function getToken (): Token
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
		if (isset($_REQUEST[$value])) return $_REQUEST[$value];

		return $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET[$value] : $_POST[$value];
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
