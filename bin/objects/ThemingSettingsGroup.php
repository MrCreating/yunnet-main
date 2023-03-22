<?php

namespace unt\objects;

use unt\parsers\AttachmentsParser;

/**
 * Class for Theming settings
*/

class ThemingSettingsGroup extends SettingsGroup
{
	private ?Theme $currentTheme;

	private bool  $JSAllowed;
	private bool  $newDesignUsed;
	private array $menuItemIds;

	public function __construct (Entity $user, array $params = [])
	{
		parent::__construct($user, Settings::THEMING_GROUP, $params);

		$this->newDesignUsed = $params['new_design'];
		$this->JSAllowed     = $params['js_allowed'];
		$this->currentTheme  = (new AttachmentsParser())->getObject($params['theme']);

        $this->setMenuItemIds($params['menu_items']);
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
		if ($this->currentConnection->prepare("UPDATE users.info SET settings_theming_js_allowed = ? WHERE id = ? LIMIT 1;")->execute([intval($jsAllowed), intval($_SESSION['user_id'])]))
		{
			$this->JSAllowed = $jsAllowed;
		}

		return $this;
	}

	public function getMenuItemIds (): array
    {
        $menu_indexes = [
            [
                'lang_name' => 'news',
                'url' => '/',
                'shown' => true,
                'disabled' => false,
                'icon' => 'news'
            ],
            [
                'lang_name' => 'notifications',
                'url' => '/notifications',
                'shown' => true,
                'disabled' => false,
                'icon' => 'notifications'
            ],
            [
                'lang_name' => 'friends',
                'url' => '/friends',
                'shown' => true,
                'disabled' => false,
                'icon' => 'friends'
            ],
            [
                'lang_name' => 'messages',
                'url' => '/messages',
                'shown' => true,
                'disabled' => true,
                'icon' => 'messages'
            ],
            [
                'lang_name' => 'groups',
                'url' => '/groups',
                'shown' => true,
                'disabled' => false,
                'icon' => 'groups'
            ],
            [
                'lang_name' => 'faves',
                'url' => '/faves',
                'shown' => true,
                'disabled' => false,
                'icon' => 'faves'
            ],
            [
                'lang_name' => 'audios',
                'url' => '/audios',
                'shown' => true,
                'disabled' => false,
                'icon' => 'audios'
            ],
            [
                'lang_name' => 'settings',
                'url' => '/settings',
                'shown' => Context::get()->isMobile(),
                'disabled' => false,
                'icon' => 'settings'
            ]
        ];

        $result = [];

        foreach ($this->menuItemIds as $menu_item_id)
        {
            $result[] = $menu_indexes[$menu_item_id - 1];
        }

		return $result;
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
				$item = $menu_id;

			if (!in_array($item, $item_ids))
				$item_ids[] = $item;
		}

		if (count($menuItemIds) != count($default_item_ids))
		{
			foreach ($default_item_ids as $menu_id) {
				if (!in_array($menu_id, $item_ids))
					$item_ids[] = $menu_id;
			}
		}

		if ($this->currentConnection->prepare("UPDATE users.info SET settings_theming_menu_items = ? WHERE id = ? LIMIT 1;")->execute([implode(',', $item_ids), intval($_SESSION['user_id'])]))
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
		if ($this->currentConnection->prepare("UPDATE users.info SET settings_theming_new_design = ? WHERE id = ? LIMIT 1;")->execute([intval($use), intval($_SESSION['user_id'])]))
		{
			$this->newDesignUsed = $use;
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