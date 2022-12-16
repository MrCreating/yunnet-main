<?php

namespace unt\objects;

use Sabberworm\CSS\Parsing\SourceException;
use unt\platform\Cache;
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

	////////////////
	private string $cssPath;
	private string $jsPath;
	private string $apiPath;

	function __construct (int $owner_id, int $id)
	{
        parent::__construct();

		$res = $this->currentConnection->prepare("SELECT id, owner_id, title, description, path_to_css, path_to_js, path_to_api, is_hidden, is_default FROM users.themes WHERE owner_id = ? AND id = ? AND (is_deleted = 0 OR is_default = 1) LIMIT 1;");

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

				$this->cssPath = __DIR__ . "/../../themes/themes" . $data["path_to_css"];
				$this->jsPath  = __DIR__ . "/../../themes/themes" . $data["path_to_js"];

				$this->isValid = true;

				$this->eventManager = new EventEmitter();
				$this->currentCache = new Cache('themes');

				$CSSCode = $this->currentCache->getItem($this->getCredentials() . '/css');
				if (!$CSSCode)
				{
					$CSSCode = file_get_contents($this->cssPath);

					if ($CSSCode)
						$this->currentCache->putItem($this->getCredentials() . '/css', $CSSCode);
				}

				$JSCode = $this->currentCache->getItem($this->getCredentials() . '/js');
				if (!$JSCode)
				{
					$JSCode = file_get_contents($this->jsPath);
					if ($JSCode)
						$this->currentCache->putItem($this->getCredentials() . '/js', $JSCode);
				}

				/*$JSONCode = $this->currentCache->getItem($this->getCredentials() . '/json');
				if (!$JSONCode)
				{
					$JSONCode = file_get_contents(__DIR__ . "/../../attachments/themes" . $data["path_to_api"]);
					if ($JSONCode)
						$this->currentCache->putItem($this->getCredentials() . '/json', $JSONCode);
				}*/

				$this->CSSCode  = $CSSCode;
				$this->JSCode   = $JSCode;
				$this->JSONCode = '';
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

		return $this->currentConnection->prepare('UPDATE users.themes SET is_deleted = 1 WHERE user_id = ? AND is_deleted = 0 AND is_default = 0 LIMIT 1;')
						  			   ->execute([intval($_SESSION['user_id'])]);
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
				$result = intval(file_put_contents($this->cssPath, $code));

				if ($result) {
					$this->currentCache->putItem($this->getCredentials() . '/css', $code);
					return true;
				}
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
				$res = intval(file_put_contents($this->jsPath, $code));
				if ($res) {
					$this->currentCache->putItem($this->getCredentials() . '/js', $code);
					return true;
				}
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
		$theme_id     = $this->getId();
		$theme_title  = $this->getTitle();
		$theme_descr  = $this->getDescription();
		$theme_owner  = $this->getOwnerId();
		$private_mode = intval($this->isPrivate());

		if ($theme_owner !== intval($_SESSION['user_id'])) return false;

		// checking new title
		if (\unt\functions\is_empty($theme_title) || strlen($theme_title) > 32) return false;

		// checking new descrption
		if (\unt\functions\is_empty($theme_descr) || strlen($theme_descr) > 512) return false;

		$res_title = $this->currentConnection->prepare("UPDATE users.themes SET title = :new_title WHERE id = :theme_id AND owner_id = :owner_id LIMIT 1;");
		$res_title->bindParam(":new_title", $theme_title, \PDO::PARAM_STR);
		$res_title->bindParam(":theme_id",  $theme_id,    \PDO::PARAM_INT);
		$res_title->bindParam(":owner_id",  $theme_owner, \PDO::PARAM_INT);
		
		if (!$res_title->execute()) return false;

		$res_descr = $this->currentConnection->prepare("UPDATE users.themes SET description = :new_desc WHERE id = :theme_id AND owner_id = :owner_id LIMIT 1;");
		$res_descr->bindParam(":new_desc", $theme_descr, \PDO::PARAM_STR);
		$res_descr->bindParam(":theme_id", $theme_id,    \PDO::PARAM_INT);
		$res_descr->bindParam(":owner_id", $theme_owner, \PDO::PARAM_INT);

		if (!$res_descr->execute()) return false;

		$res_private = $this->currentConnection->prepare("UPDATE users.themes SET is_hidden = :is_hidden WHERE id = :theme_id AND owner_id = :owner_id AND is_default != 1 LIMIT 1;");
		$res_private->bindParam(":is_hidden", $private_mode, \PDO::PARAM_INT);
		$res_private->bindParam(":theme_id",  $theme_id,     \PDO::PARAM_INT);
		$res_private->bindParam(":owner_id",  $theme_owner,  \PDO::PARAM_INT);
		
		if (!$res_private->execute()) return false;

		return true;
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
		if (\unt\functions\is_empty($title) || strlen($title) > 32) return NULL;
		if (\unt\functions\is_empty($description) || strlen($description) > 128) return NULL;

		// creating new user folder if not created.
		if (!file_exists(__DIR__ . '/../../attachments/themes/' . $owner_id)) 
			if (!mkdir(__DIR__ . '/../../attachments/themes/' . $owner_id))
				return NULL;

		// inserting new theme data.
		$res = \unt\platform\DataBaseManager::getConnection()->prepare("INSERT INTO users.themes (user_id, owner_id, title, description, is_hidden) VALUES (
			:user_id, :owner_id, :title, :description, :is_hidden
		);");

		$res->bindParam(":user_id",     $owner_id,    \PDO::PARAM_INT);
		$res->bindParam(":owner_id",    $owner_id,    \PDO::PARAM_INT);
		$res->bindParam(":title",       $title,       \PDO::PARAM_STR);
		$res->bindParam(":description", $description, \PDO::PARAM_STR);
		$res->bindParam(":is_hidden",   $is_private,  \PDO::PARAM_INT);

		if ($res->execute())
		{
			$res = \unt\platform\DataBaseManager::getConnection()->prepare("SELECT LAST_INSERT_ID();");
			
			if ($res->execute())
			{
				/**
				 * All theme code stores in the files. Create it.
				*/
				$new_theme_id = intval($res->fetch(\PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

				if (!file_exists(__DIR__ . '/../../attachments/themes/' . $owner_id . "/" . $new_theme_id))
					if (!mkdir(__DIR__ . '/../../attachments/themes/' . $owner_id . "/" . $new_theme_id))
						return NULL;

				// CSS code.
				file_put_contents(__DIR__ . '/../../attachments/themes/' . $owner_id . "/" . $new_theme_id . "/theme.css", "/**
 * Welcome to your theme code! 
 * It is an a CSS code. 
 * You can edit it as you want! 
*/

/* variables can be put here */
:root {
	/* Unread messages color */
    --unreaded-messages-color: initial;
    
    /* Body color and 90% text color */
    --unt-background-color: initial;
    --unt-background-light-color: initial;
    --unt-text-color: initial;
    
    /* Custom user image and interface opacity */
    --unt-user-background: initial;
    --unt-opacity: initial;
    /* Sidenav user info div coloring */
    --unt-sidenav-background: initial;
    
    /* Collections color (right menu, left menu on PC, etc) */
    --unt-collection-background-color: initial;
    --unt-section-item-background-color: initial;
    --unt-collection-text-color: initial;
    
    /* Currently selected items */
    --unt-collection-active-background: initial;
    
    /* In modal buttons text color (flat buttons) */
    --unt-flat-btn-text-color: initial;
    
    /* SVG Icons fill color */
    --svg-fill-color: initial;
    
    /* Subtext (chats members count, online, etc) text color */
    --unt-subtext-color: initial;
    
    /* Right and left active items */
    --unt-active-item-color: initial;
    --unt-active-borded-color: initial;
    
    /* Hovered collection and collapsible colors */
    --hovered-collection-items-color: initial;
    --hovered-collapsible-items-color: initial;
    
    /* Default collapsible colors */
    --unt-collapsible-header-color: initial;
    --unt-collapsible-body-color: initial;
    --unt-collapsible-items-color: initial;
    --unt-collapsible-text-color: initial;
    
    /* Navigation panel color */
    --unt-navigation-panel-color: initial;
    
    /* Cards color */
    --unt-background-card-color: initial;
    
    /* Links (href) color */
    --unt-links-color: initial;
    
    /* Text input colors */
    --unt-input-text-color: initial;
    
    /* Unliked icons color */
    --unt-unlike-color: initial;
    
    /* Modals background colors */
    --unt-modals-background-color: initial;
    
    /* Dropdown background and hovered colors */
    --unt-dropdown-background-color: initial;
    --unt-dropdown-hovered-color: initial;
    
    /* Divider background color */
    --unt-divider-background-color: initial;
    
    /* Messages from me and from another colors */
    --from-me-messages-background: initial;
    --from-another-messages-background: initial;
    --from-me-messages-text-color: initial;
    --from-another-messages-text-color: initial;
    
    /* Notifications background color */
    --unt-notifications-background-color: initial;
    
    /* Counters color (messages, friends, etc) */
    --unt-counters-color: initial;
}
");

				// JS code.
				file_put_contents(__DIR__ . '/../../attachments/themes/' . $owner_id . "/" . $new_theme_id . "/theme.js", "/**
 * Welcome to your theme code! 
 * It is an a JS code. 
 * You can edit it as you want! 
*/

console.log(`[OK] Theme is working! Fine :)`)
");
				// update that info in DB
				\unt\platform\DataBaseManager::getConnection()->prepare('UPDATE users.themes SET path_to_css = "/'.intval($owner_id).'/'.intval($new_theme_id).'/theme.css" WHERE id = ?;')->execute([intval($new_theme_id)]);
				\unt\platform\DataBaseManager::getConnection()->prepare('UPDATE users.themes SET path_to_js = "/'.intval($owner_id).'/'.intval($new_theme_id).'/theme.js" WHERE id = ?;')->execute([intval($new_theme_id)]);

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

		// gettings themes for user_id and that not deleted
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
}

?>