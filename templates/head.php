<?php

use unt\design\Template;
use unt\objects\Context;
use unt\objects\Project;

?>

<!DOCTYPE html>
<html lang="<?php echo \unt\objects\Context::get()->getLanguage()->id; ?>" class="unselectable">
    <head>
        <title>yunNet.</title>
        <link rel="shortcut icon" href="<?php echo Project::getDefaultDomain(); ?>/favicon.ico"/>
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo Project::getDefaultDomain(); ?>/favicon/apple-touch-icon.png">
        <link rel="icon" type="image/png" href="<?php echo Project::getDefaultDomain(); ?>/favicon/favicon-16x16.png" sizes="16x16">
        <link rel="icon" type="image/png" href="<?php echo Project::getDefaultDomain(); ?>/favicon/favicon-32x32.png" sizes="32x32">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0"/>
        <meta name="theme-color" content="#42A5F5">
        <meta name="description" content="yunNet. - is a social network whose purpose is to return the old principles of social networks, while following the new technologies">
        <link href="/manifest.json" rel="manifest">
        <link href="/favicon/apple-touch-icon.png" sizes="180x180" rel="apple-touch-icon">
        <link href="<?php echo Project::getDevDomain(); ?>/css/unt-platform-design.css" type="text/css" rel="stylesheet" media="screen,projection"/>
        <link href="<?php echo Project::getDevDomain(); ?>/css/unt-default-theme.css" type="text/css" rel="stylesheet" media="screen,projection"/>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-platform-design.js" type="text/javascript"></script>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-platform-components.js" type="text/javascript"></script>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-platform-account.js" type="text/javascript"></script>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-platform-internal.js" type="text/javascript"></script>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-module-profile.js" type="text/javascript"></script>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-module-news.js" type="text/javascript"></script>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-module-auth.js" type="text/javascript"></script>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-module-register.js" type="text/javascript"></script>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-module-restore.js" type="text/javascript"></script>
        <script src="<?php echo Project::getDevDomain(); ?>/js/unt-module-settings.js" type="text/javascript"></script>
    </head>
    <body style="display: flex">
        <?php if (empty($_POST)): ?>
            <div class="unt-loader" style="background: var(--unt-loader-color, white); !important;">
                <div class="item-center item-halign-wrapper">
                    <img src="/favicon.ico" class="circle" width="164" height="164">

                    <div class="loading-spinner"></div>

                    <div class="retry-button" style="padding-top: 100px; display: none">
                        <a class="retry-button-item btn-floating btn-large waves-effect waves-light">
                            <?php \unt\design\Icon::get('refresh')->show(); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!Context::get()->isLogged() && $is_default): ?>
            <?php
                if ($module === 'register')
                    Template::get('register')->show();
                elseif ($module === 'restore')
                    Template::get('restore')->show();
                else
                    Template::get('auth')->show();
            ?>
        <?php endif; ?>

        <?php if (Context::get()->isLogged() && $is_default): ?>
            <?php
                $forbidden_modules = ['auth', 'register', 'restore', 'head'];
                if (in_array($module, $forbidden_modules))
                    die(header('Location: /'));

                $template = Template::get($module);
                if (!$template)
                    Template::get('not_found')->show();
                else
                    $template->show();
            ?>
        <?php endif; ?>

        <?php if (!$is_default): ?>
            <?php Template::get('profile')->show(); ?>
        <?php endif; ?>

        <?php if (!Project::isProduction()): ?>
            <div class="card waves-effect waves-light" style="z-index: 1010; padding: 15px; position: fixed !important; bottom: 20px; left: 25px;">
                <b>Режим разработки.</b>
            </div>
        <?php endif; ?>
    </body>
</html>