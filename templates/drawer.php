<?php

/**
 * @var User $user Текущий пользователь
 * @var string $url Ссылка на фото юзера
 * @var string $user_url Ссылка на профиль юзера
 * @var Photo|null $photo Фото юзера
 */

use unt\design\Icon;
use unt\objects\Context;
use unt\objects\Photo;
use unt\objects\Settings;
use unt\objects\ThemingSettingsGroup;
use unt\objects\User;

/**
 * @var ThemingSettingsGroup $theming_settings
 */
$theming_settings = $user->getSettings()->getSettingsGroup(Settings::THEMING_GROUP);

$menu_items = $theming_settings->getMenuItemIds();

?>

<li class="hide-on-large-only">
    <div class="user-view">
        <div class="background"></div>
        <a href="<?php echo $user_url; ?>">
            <img alt="" class="circle" src="<?php echo $url; ?>">
        </a>
        <a href="<?php echo $user_url; ?>">
            <span class="name" style="color: var(--unt-sidenav-username-color, white)">
                <?php echo htmlspecialchars($user->getFirstName() . ' ' . $user->getLastName()) ?>
            </span>
        </a>
        <a href="<?php echo $user_url; ?>">
            <span class="email" style="color: var(--unt-sidenav-status-color, white)">
                <?php echo htmlspecialchars($user->getStatus()); ?>
            </span>
        </a>
    </div>
</li>

<?php foreach ($menu_items as $menu_item): ?>
    <?php if ($menu_item['shown']): ?>
        <li class="hide-on-large-only">
            <a style="color: black; width: 100%" class="waves-effect" href="<?php echo $menu_item['url']; ?>">
                <i style="padding-top: 5px;">
                    <?php Icon::get($menu_item['icon'])->setHeight(24)->setWidth(24)->show(false); ?>
                </i>
                <div style="margin-left: 15px;">
                    <?php echo Context::get()->getLanguage()->{$menu_item['lang_name']}; ?>
                </div>
            </a>
        </li>
    <?php endif; ?>
<?php endforeach; ?>

<div class="collection" style="padding: 0 !important; margin: 0 !important;">
    <?php foreach ($menu_items as $menu_item): ?>
        <?php if ($menu_item['shown']): ?>
            <li class="hide-on-med-and-down collection-item waves-effect" style="width: 100%">
                <a style="color: black; line-height: 2px" href="<?php echo $menu_item['url']; ?>" class="valign-wrapper">
                    <div>
                        <?php Icon::get($menu_item['icon'])->setHeight(24)->setWidth(24)->show(); ?>
                    </div>
                    <div style="margin-left: 15px;">
                        <?php echo Context::get()->getLanguage()->{$menu_item['lang_name']}; ?>
                    </div>
                </a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</div>