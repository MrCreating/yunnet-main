<?php

require_once __DIR__ . '/settingsGroup.php';

/**
 * Class for Theming settings
*/

class ThemingSettingsGroup extends SettingsGroup
{
	protected $currentConnection = NULL;

	private $currentTheme;

	private bool  $JSAllowed;
	private bool  $newDesignUsed;
	private array $menuItemIds;

	public function __construct (Entity $user, DataBaseManager $connection, array $params = [])
	{
		$this->currentEntity     = $user;

		$this->type              = "theming";
		$this->currentConnection = $connection;

		$this->newDesignUsed = $params['new_design'];
		$this->JSAllowed     = $params['js_allowed'];
		$this->currentTheme  = (new AttachmentsParser())->getObject($params['theme']);

		$default_item_ids = [
			1, 2, 3, 4, 5, 6, 7, 8
		];

		$item_ids = array();
		
		$menu_ids = $params["menu_items"];
		foreach ($default_item_ids as $index => $menu_id)
		{
			$item = intval($menu_ids[$index]);
			if (!$item)
				$item = intval($menu_id);

			if (!in_array(intval($item), $item_ids))
				$item_ids[] = intval($item);
		}

		if (count($menu_ids) != count($default_item_ids))
		{
			foreach ($default_item_ids as $index => $menu_id) {
				if (!in_array($menu_id, $item_ids))
					$item_ids[] = $menu_id;
			}
		}

		$this->menuItemIds = $item_ids;
	}

	public function getCurrentTheme (): ?Theme
	{
		return $this->currentTheme;
	}

	public function isJSAllowed (): bool
	{
		return boolval($this->JSAllowed);
	}

	public function setJSAllowance (bool $jsAllowed): ThemingSettingsGroup
	{
		if ($this->currentConnection->uncache('User_' . $this->currentEntity->getId())->prepare("UPDATE users.info SET settings_theming_js_allowed = ? WHERE id = ? LIMIT 1;")->execute([intval(boolval($jsAllowed)), intval($_SESSION['user_id'])]))
		{
			$this->JSAllowed = boolval($jsAllowed);
		}

		return $this;
	}

	public function getMenuItemIds (): array
	{
		return $this->menuItemIds;
	}

	public function setMenuItemIds (array $menuItemIds): bool
	{
		$default_item_ids = [
			1, 2, 3, 4, 5, 6, 7, 8
		];

		// in items ids must only have unique items from 1 to 6.
		$item_ids = array();
		foreach ($default_item_ids as $index => $menu_id)
		{
			$item = intval($menuItemIds[$index]);
			if (!$item)
				$item = intval($menu_id);

			if (!in_array($item, $item_ids))
				$item_ids[] = $item;
		}

		if (count($menuItemIds) != count($default_item_ids))
		{
			foreach ($default_item_ids as $index => $menu_id) {
				if (!in_array($menu_id, $item_ids))
					$item_ids[] = $menu_id;
			}
		}

		if ($this->currentConnection->uncache('User_' . $this->currentEntity->getId())->prepare("UPDATE users.info SET settings_theming_menu_items = ? WHERE id = ? LIMIT 1;")->execute([implode(',', $item_ids), intval($_SESSION['user_id'])]))
		{
			$this->menuItemIds = $item_ids;

			return true;
		}

		return false;
	}

	public function isNewDesignUsed (): bool
	{
		return boolval($this->newDesignUsed);
	}

	public function useNewDesign (bool $use): ThemingSettingsGroup
	{
		if ($this->currentConnection->uncache('User_' . $this->currentEntity->getId())->prepare("UPDATE users.info SET settings_theming_new_design = ? WHERE id = ? LIMIT 1;")->execute([intval(boolval($use)), intval($_SESSION['user_id'])]))
		{
			$this->newDesignUsed = boolval($use);
		}

		return $this;
	}

	public function toArray (): array
	{
		return [
			'current_theme' => $this->getCurrentTheme() !== NULL ? $this->getCurrentTheme()->getCredentials() : NULL,
			'menu_items'    => $this->getMenuItemIds(),
			'js_allowed'    => intval($this->isJSAllowed()),
			'new_design'    => intval($this->isNewDesignUsed())
		];
	}
}
?>