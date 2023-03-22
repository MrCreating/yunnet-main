<?php

/**
 * @var string $title Название страницы в шапке
 * @var User $user Текущий пользователь
 */


use unt\objects\User;

$user = \unt\objects\Context::get()->getCurrentUser();

$photo = $user->getCurrentPhoto();

$url = $photo ? $photo->getLink() : \unt\objects\Project::getDevDomain() . '/images/default.png';

$user_url = $user->getScreenName() === '' ? '/id' . $user->getId() : ('/' . $user->getScreenName());

?>

<div style="display: inline-flex; flex-direction: column; height: 100vh; width: 100%">
    <div>
        <nav class="light-blue lighten-1" style="position: relative; z-index: 100">
            <div class="nav-wrapper">
                <div class="row" style="height: 100%;">
                    <div style="width: 20%;" class="hide-on-med-and-down">
                        <div style="margin-left: 15px;">
                            <a class=" valign-wrapper unselectable" href="/">
                                <img class="circle" height="32" width="32" src="/favicon.ico">
                                <b style="margin-left: 15px;">
                                    yunNet.
                                </b>
                            </a>
                        </div>
                    </div>
                    <div class="col s6">
                        <div class="valign-wrapper">
                            <a onclick="unt.Sidenav.getInstance(document.getElementsByClassName('sidenav')[0]).open();" class="navigate_drawer_icon hide-on-large-only" style="cursor: pointer; margin-right: 15px;">
                                <i style="margin-bottom: -5px">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24">
                                        <path fill="white" d="M3 18v-2h18v2Zm0-5v-2h18v2Zm0-5V6h18v2Z"/>
                                    </svg>
                                </i>
                            </a>
                            <a class="navigate_back_button" onclick="history.back()" style="cursor: pointer; display: none; margin-right: 15px;">
                                <i style="margin-bottom: -5px">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                                        <path d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" fill="white"></path>
                                    </svg>
                                </i>
                            </a>
                            <div class="current-title unselectable" style="width: 100%; font-size: 90%;">
                                <?php echo $title; ?>
                            </div>
                            <div class="unselectable" style="width: 100%; font-size: 90%; padding: 0px; display: none;">
                                <a class="dropdown-trigger unselectable valign-wrapper" data-target="foldCOntextMenu" style="padding: 0px; cursor: pointer; width: 100%;">
                                    <div style="font-size: 90%;"></div>
                                    <i style="margin-right: 10px; margin-left: 10px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" style="fill: white; margin-top: 13px;"><path d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"></path>
                                        </svg>
                                    </i>
                                </a>
                                <ul class="dropdown-content" id="foldCOntextMenu" tabindex="0">
                                </ul>
                            </div>
                            <div class="current-additional unselectable" style="display: none;">
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1 1 auto;">
                        <div class="valign-wrapper right hide-on-med-and-down" style="flex: 1 1 auto;">
                            <a class="dropdown-trigger unselectable valign-wrapper" data-target="actionsId" style="cursor: pointer; width: 100%;">
                                <img class="circle" height="28" width="28" src="<?php echo $url; ?>">
                                <div style="margin-left: 15px; font-size: 90%; line-height: normal; text-align: center;">
                                    <?php echo htmlspecialchars($user->getFirstName() . ' ' . $user->getLastName()) ?>
                                </div>
                                <i style="margin-right: 10px; margin-left: 10px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" style="fill: white; margin-top: 13px;">
                                        <path d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"></path>
                                    </svg>
                                </i>
                            </a>
                            <ul class="dropdown-content" id="actionsId" tabindex="0">
                                <li tabindex="0">
                                    <a href="<?php echo $user_url; ?>">
                                        <div class="valign-wrapper">
                                            <img class="circle" width="28" src="<?php echo $url; ?>" style="margin-right: 15px;">
                                            <div>
                                                <?php echo htmlspecialchars($user->getFirstName() . ' ' . $user->getLastName()) ?>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="divider" tabindex="0"></li>
                                <?php if (!$user->isBanned()): ?>
                                    <li tabindex="0">
                                        <a href="/settings">
                                            <?php echo \unt\objects\Context::get()->getLanguage()->settings; ?>
                                        </a>
                                    </li>
                                    <li class="divider" tabindex="0"></li>
                                <?php endif; ?>
                                <li tabindex="0">
                                    <a onclick="unt.modules.profile.logout()">
                                        <?php echo \unt\objects\Context::get()->getLanguage()->log_out; ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <ul class="sidenav hide-on-large-only">
            <?php \unt\design\Template::get('drawer')->variables([
                'user' => $user,
                'photo' => $photo,
                'url' => $url,
                'user_url' => $user_url
            ])->show(); ?>
        </ul>
    </div>
    <div style="display: inline-flex;">
        <div style="flex: 0 0 20%; margin: 0 !important; padding: 0 !important; z-index: 0; border-radius: 0" class="card hide-on-med-and-down">
            <ul style="margin: 5px !important;">
                <?php \unt\design\Template::get('drawer')->variables([
                    'user' => $user,
                    'photo' => $photo,
                    'url' => $url,
                    'user_url' => $user_url
                ])->show(); ?>
            </ul>
        </div>
        <div style="overflow-y: auto; width: 100vw; height: calc(100vh - 50px)">
            <div class="window-content" style="width: 100%">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
</div>