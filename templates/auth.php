<?php
$lang = \unt\objects\Context::get()->getLanguage();
?>

<div class="main-interface-window" style="display: none">
</div>
<div class="main-information-window" style="display: none">
    <div class="item-center item-halign-wrapper">
        <img src="/favicon.ico" height="96" width="96" class="circle">
        <div style="padding-bottom: 100px">
            <div class="card" style="margin-top: 25px; padding: 20px; min-width: 400px; max-width: 600px">
                <div class="center login-message" style="padding: 10px;">
                    <?php echo $lang->welcome . ' ' . $lang->login_warning; ?>
                </div>
                <form action="/login" method="post" id="auth_form">
                    <?php \unt\design\InputFirld::create($lang->email, 'email')->setId('login')->show(); ?>
                    <?php \unt\design\InputFirld::create($lang->password, 'password')->setId('password')->show(); ?>
                    <div class="center actions-list">
                        <a href="/register">
                            <?php echo $lang->register; ?>
                        </a>
                        <br>
                        <a href="/restore">
                            <?php echo $lang->forgot_password; ?>
                        </a>
                    </div>
                    <div class="center" style="padding-top: 20px">
                        <button type="submit" id="auth_button" class="btn btn-large waves-light waves-effect">
                            <div><?php echo $lang->log_in; ?><div>
                        </button>
                        <div id="auth_loader" style="display: none">
                            <?php \unt\design\LoadingSpinner::create()->setWidth(45)->setHeight(45)->show(); ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
