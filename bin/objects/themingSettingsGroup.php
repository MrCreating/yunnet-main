<?php

require_once __DIR__ . '/settingsGroup.php';
require_once __DIR__ . '/../parsers/attachments.php';

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

	public function __construct ($connection, array $params = [])
	{
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
			$item = $menu_ids[$index];
			if (!$item)
				$item = intval($menu_id);

			if (!in_array($item, $item_ids))
				$item_ids[] = $item;
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

	public function setCurrentTheme (?Theme $newTheme = NULL): ThemingSettingsGroup
	{
		return $this;
	}

	public function isJSAllowed (): bool
	{
		return boolval($this->JSAllowed);
	}

	public function setJSAllowance (bool $jsAllowed): ThemingSettingsGroup
	{
		return $this;
	}

	public function getMenuItemIds (): array
	{
		return $this->menuItemIds;
	}

	public function setMenuItemIds (array $menuItemIds): bool
	{
		return true;
	}

	public function isNewDesignUsed (): bool
	{
		return boolval($this->newDesignUsed);
	}

	public function useNewDesign (bool $use): ThemingSettingsGroup
	{
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