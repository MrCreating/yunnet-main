<div class="main-interface-window" style="display: none">
    <?php $interface = \unt\design\Template::get('interface')->begin(); ?>
    <?php $interface->end()->variables(['title' => \unt\objects\Context::get()->getLanguage()->news])->show(); ?>
</div>
<div class="main-information-window" style="display: none">
</div>