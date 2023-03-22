<?php

use unt\objects\Bot;
use unt\objects\User;

/**
 * @var User|Bot $entity
 */
$entity = \unt\objects\Entity::findByScreenName(substr(REQUESTED_PAGE, 1));
?>

<?php if ($entity): ?>
    <div class="main-interface-window" style="display: none">
        <div id="current_user_id" style="display: none" data-id="<?php echo $entity instanceof User ? $entity->getId() : ($entity->getId() * -1); ?>"></div>
        <?php $interface = \unt\design\Template::get('interface')->begin(); ?>
            <div style="margin: 0 0 0 1px;">
                <div style="padding: 32px; margin: 0;border-radius: 0" class="card">
                    <div class="valign-wrapper">
                        <img class="circle" height="96" width="96" src="<?php echo ($entity->getCurrentPhoto() !== NULL ? $entity->getCurrentPhoto()->getLink() : (\unt\objects\Project::getDevDomain() . '/images/default.png')); ?>">
                        <div style="margin-left: 20px" class="item-halign-wrapper">
                            <div style="font-size: 20px">
                                <?php echo ($entity instanceof Bot ? $entity->getName() : ($entity->getFirstName() . ' ' . $entity->getLastName())); ?>
                            </div>
                            <div>
                                <small><?php echo \unt\parsers\StringHelper::online($entity instanceof User ? $entity->getOnline() : NULL, $entity instanceof User ? $entity->getGender() : NULL); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($entity->isBlocked()): ?>
                    <div class="card blocked" style="padding: 20px"><?php echo \unt\objects\Context::get()->getLanguage()->blocked; ?></div>
                <?php endif; ?>
                <div class="card" id="error_message" style="padding: 20px; display: none"><?php echo \unt\objects\Context::get()->getLanguage()->load_error; ?></div>

                <div class="profile-actions">
                    <div class="profile-content">
                        <div class="posts_list" style="display: none">
                        </div>
                        <div class="loader center">
                        </div>
                    </div>
                    <div class="actions hide-on-med-and-down"></div>
                </div>
            </div>
        <?php $interface->end()->variables(['title' => $entity instanceof Bot ? $entity->getName() : ($entity->getFirstName() . ' ' . $entity->getLastName())])->show(); ?>
    </div>
    <div class="main-information-window" style="display: none">
    </div>
<?php else: ?>
    <?php \unt\design\Template::get('not_found')->show(); ?>
<?php endif; ?>
