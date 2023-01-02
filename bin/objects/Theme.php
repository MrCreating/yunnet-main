<?php

namespace unt\objects;

use Sabberworm\CSS\Parsing\SourceException;
use unt\platform\Cache;
use unt\platform\DataBaseManager;
use unt\platform\EventEmitter;

/**
 * Theme class
*/

class Theme extends Attachment
{
    ///////////////////////////////////////
    const ATTACHMENT_TYPE = 'theme';
    ///////////////////////////////////////

	private int $owner_id;
	private int $id;

	private string $title;
	private string $description;

	private bool $defaultTheme;
	private bool $privateTheme;

	private string $JSCode;
	private string $CSSCode;
    private string $JSONCode;

	private EventEmitter $eventManager;
	private Cache $currentCache;

	function __construct (int $owner_id, int $id)
	{
        parent::__construct();

		$res = $this->currentConnection->prepare("SELECT id, owner_id, title, description, path_to_css, path_to_js, path_to_api, js_code, css_code, api_code, is_hidden, is_default FROM users.themes WHERE owner_id = ? AND id = ? AND (is_deleted = 0 OR is_default = 1) LIMIT 1;");

		if ($res->execute([$owner_id, $id]))
		{
			$data = $res->fetch(\PDO::FETCH_ASSOC);
			if ($data)
			{
				$this->id           = intval($data['id']);
				$this->owner_id     = intval($data['owner_id']);
				$this->title        = strval($data['title']);
				$this->description  = strval($data['description']);
				$this->defaultTheme = boolval(intval($data['is_default']));
				$this->privateTheme = boolval(intval($data['is_hidden']));

				$this->isValid = true;

				$this->eventManager = new EventEmitter();
				$this->currentCache = new Cache('themes');

				$this->CSSCode  = $data['css_code'] ?: ' ';
				$this->JSCode   = $data['js_code'] ?: ' ';
				$this->JSONCode = $data['api_code'] ?: ' ';
			}
		}
	}

	public function setAsCurrent (): bool
	{
		if ((!$this->valid() || ($this->isPrivate() && $this->getOwnerId() !== intval($_SESSION['user_id']))) && !$this->isDefault()) return false;

		// setting up theme
		$res = $this->currentConnection->prepare("UPDATE users.info SET settings_theming_current_theme = ? WHERE id = ? LIMIT 1;");
		if ($res->execute([$this->getCredentials(), intval($_SESSION['user_id'])]))
		{
			return $this->eventManager->sendEvent([intval($_SESSION['user_id'])], [0], [
				'event' => 'interface_event',
				'data'  => [
					'action' => 'theme_changed',
					'theme'  => $this->toArray()
				]
			]);
		}

		return false;
	}

	public function delete (): bool
	{
		if (!$this->valid() || $this->isDefault()) return false;

		$this->isValid = false;

		Theme::reset();

		return $this->currentConnection->prepare('UPDATE users.themes SET is_deleted = 1 WHERE user_id = ? AND id = ? AND is_deleted = 0 AND is_default = 0 LIMIT 1;')
						  			   ->execute([intval($_SESSION['user_id']), $this->getId()]);
	}

	public function getType (): string
	{
		return self::ATTACHMENT_TYPE;
	}

	public function getCredentials (): string
	{
		return $this->getType() . $this->getOwnerId() . '_' . $this->getId();
	}

	public function getId (): int
	{
		return $this->id;
	}

	public function getOwnerId (): int
	{
		return $this->owner_id;
	}

	public function getTitle (): string
	{
		return $this->title;
	}

	public function getDescription (): string
	{
		return $this->description;
	}

	public function setTitle (string $title): Theme
	{
		$this->title = $title;

		return $this;
	}

	public function setDescription (string $description): Theme
	{
		$this->description = $description;

		return $this;
	}

	public function isPrivate (): bool
	{
		return $this->privateTheme;
	}

	public function setPrivate (bool $private): Theme
	{
		$this->privateTheme = $private;

		return $this;
	}

	public function isDefault (): bool
	{
		return $this->defaultTheme;
	}

	public function hasCSSCode (): bool
	{
		return !($this->CSSCode == NULL);
	}

	public function hasJSCode (): bool
	{
		return !($this->JSCode == NULL);
	}

	public function hasJSONCode (): bool
	{
		return !($this->JSONCode == NULL);
	}

	public function createUTH (): UTHTheme
	{
		return (new UTHTheme($this));
	}

	public function getCSSCode (): ?string
	{
		return $this->CSSCode;
	}

	public function getJSCode (): ?string
	{
		return $this->JSCode;
	}

	public function getJSONCode (): ?string
	{
		return $this->JSONCode;
	}

	public function setJSONCode (): bool
	{
		return false;
	}

	public function setCSSCode (string $code)
	{
		if ($code === $this->getCSSCode())
			return true;

		try {
			$css = new \Sabberworm\CSS\Parser($code, \Sabberworm\CSS\Settings::create()->beStrict());
			$res = $css->parse();

			if ($res)
			{
				return DataBaseManager::getConnection()
                    ->prepare('UPDATE users.themes SET css_code = ? WHERE id = ? AND owner_id = ? LIMIT 1;')->execute([
                        $code, $this->getId(), $this->getOwnerId()
                    ]);
			}
		} catch (\Sabberworm\CSS\Parsing\UnexpectedTokenException|SourceException $e) {
			return $e->getMessage();
		}

        return false;
	}

	public function setJSCode (string $code)
	{
		if ($code === $this->getJSCode())
			return false;

		try {
			$result = \Peast\Peast::latest($code, [])->parse();
			if ($result)
			{
				return DataBaseManager::getConnection()
                    ->prepare('UPDATE users.themes SET js_code = ? WHERE id = ? AND owner_id = ? LIMIT 1;')->execute([
                        $code, $this->getId(), $this->getOwnerId()
                    ]);
			}
		} catch (\Peast\Syntax\Exception $e) {
			$message = $e->getMessage();
			$line    = $e->getPosition()->getLine();
			$column  = $e->getPosition()->getColumn();
			$index   = $e->getPosition()->getIndex();

            return "SyntaxError: ".$message.
            " <br>at line: ".$line.", column: ".$column.
            " <br>at index: ".$index;
		}

		return false;
	}

	public function apply (): bool
	{
		$theme_id          = $this->getId();
		$theme_title       = $this->getTitle();
		$theme_description = $this->getDescription();
		$theme_owner       = $this->getOwnerId();
		$private_mode      = intval($this->isPrivate());

		if ($theme_owner !== intval($_SESSION['user_id'])) return false;

		// checking new title
		if (is_empty($theme_title) || strlen($theme_title) > 32) return false;

		// checking new description
		if (is_empty($theme_description) || strlen($theme_description) > 512) return false;

		$res = $this->currentConnection->getClient()->prepare("
            UPDATE 
                users.themes 
            SET 
                title = :new_title, 
                `description` = :new_desc, 
                is_hidden = :is_hidden 
            WHERE 
                id = :theme_id 
              AND 
                owner_id = :owner_id 
            LIMIT 1;");

        $res->bindParam(":new_title", $theme_title,       \PDO::PARAM_STR);
        $res->bindParam(":new_desc",  $theme_description, \PDO::PARAM_STR);
        $res->bindParam(":is_hidden", $private_mode,      \PDO::PARAM_INT);

        $res->bindParam(":theme_id",  $theme_id,    \PDO::PARAM_INT);
        $res->bindParam(":owner_id",  $theme_owner, \PDO::PARAM_INT);

        return $res->execute();
	}

	public function toArray (): array
	{
		return [
			'owner_id' => $this->getOwnerId(),
			'id'       => $this->getId(),
			'data'     => [
				'title'       => $this->getTitle(),
				'description' => $this->getDescription(),
				'url'         => Project::getThemesDomain() . '/' . $this->getCredentials()
			],
			'settings' => [
				'is_private' => intval($this->isPrivate()),
				'is_default' => intval($this->isDefault())
			],
			'params'   => [
				'has_js'  => intval($this->hasJSCode()),
				'has_css' => intval($this->hasCSSCode()),
				'has_api' => intval($this->hasJSONCode())
			]
		];
	}

	////////////////////////////////
    private static function getDefaultCode ($type): string
    {
        $code = (new Cache('themes'))->getItem('default_' . $type . '_code');
        if (!$code)
        {
            $code = file_get_contents(__DIR__ . '/../languages/themes/default_' . $type . '_code');

            if ($code)
                (new Cache('themes'))->putItem('default_' . $type . '_code', $code);
        }

        return (string) $code;
    }

	public static function reset (): bool
	{
		if (\unt\platform\DataBaseManager::getConnection()->prepare("UPDATE users.info SET settings_theming_current_theme = NULL WHERE id = ? LIMIT 1")->execute([intval($_SESSION['user_id'])]))
		{
			return (new EventEmitter())->sendEvent([intval($_SESSION['user_id'])], [0], [
				'event' => 'interface_event',
				'data'  => [
					'action' => 'theme_changed'
				]
			]);
		}

		return false;
	}

	public static function create (string $title, string $description, bool $is_private): ?Theme
	{
		$owner_id   = intval($_SESSION['user_id']);
		$is_private = intval($is_private);

		// checking title and description for validity
		if (is_empty($title) || strlen($title) > 32) return NULL;
		if (is_empty($description) || strlen($description) > 128) return NULL;

		// inserting new theme data.
		$res = \unt\platform\DataBaseManager::getConnection()->prepare("INSERT INTO users.themes (user_id, owner_id, title, description, is_hidden, js_code, css_code, api_code) VALUES (
			:user_id, :owner_id, :title, :description, :is_hidden, :js_code, :css_code, :api_code
		);");

        $js_code = self::getDefaultCode('js');
        $css_code = self::getDefaultCode('css');
        $api_code = '';

		$res->bindParam(":user_id",     $owner_id,    \PDO::PARAM_INT);
		$res->bindParam(":owner_id",    $owner_id,    \PDO::PARAM_INT);
		$res->bindParam(":title",       $title,       \PDO::PARAM_STR);
		$res->bindParam(":description", $description, \PDO::PARAM_STR);
		$res->bindParam(":is_hidden",   $is_private,  \PDO::PARAM_INT);
        $res->bindParam(':js_code',     $js_code,     \PDO::PARAM_STR);
        $res->bindParam(":css_code",    $css_code,    \PDO::PARAM_STR);
        $res->bindParam(":api_code",    $api_code,    \PDO::PARAM_STR);

		if ($res->execute())
		{
			$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT LAST_INSERT_ID();");
			
			if ($res->execute())
			{
				$new_theme_id = intval($res->fetch(\PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

				$result = new Theme($owner_id, $new_theme_id);
				if ($result->valid())
					return $result;
			}
		}

		return NULL;
	}

	public static function getList (int $count = 30, int $offset = 0): array
	{
		if ($count > 100) $count = 100;
		if ($offset < 0) $offset = 0;

		$result = [];

		// get themes for user_id and that not deleted
		$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT DISTINCT id, owner_id FROM users.themes WHERE (user_id = ? OR is_default = 1) AND (is_deleted = 0 OR is_default = 1) LIMIT ".intval($offset).",".intval($count).";");

		if ($res->execute([intval($_SESSION['user_id'])]))
		{
			$data = $res->fetchAll(\PDO::FETCH_ASSOC);
			if ($data)
			{
				foreach ($data as $theme_data)
				{
					$theme = new Theme(intval($theme_data['owner_id']), intval($theme_data['id']));

					if ($theme->valid())
						$result[] = $theme;
				}
			}
		}

		return $result;
	}

    public static function findById (int $owner_id, int $theme_id): ?Theme
    {
        $theme = new static($owner_id, $theme_id);

        return $theme->valid() ? $theme : NULL;
    }
}

?>