<?php

use unt\design\Icon;

?>

<div class="main-interface-window" style="display: none">
</div>
<div class="main-information-window" style="display: none">
    <nav class="hide-on-large-only" style="background: white !important;">
        <div class="actions valign-wrapper">
            <a href="/" style="cursor: pointer">
                <i style="margin-top: 2px;margin-left: 10px">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                        <path d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" fill="black"></path>
                    </svg>
                </i>
            </a>
        </div>
    </nav>
    <div class="hide-on-med-and-down" style="display: inline-flex">
        <div style="flex: 0 0 15%; margin: 0 !important; padding: 0 !important; z-index: 0" class="card hide-on-med-and-down">
            <ul class="collection" style="margin: 5px !important;">
                <li class="collection-item waves-effect" style="width: 100%">
                    <a href="/" style="color: black; line-height: 2px" class="valign-wrapper">
                        <div>
                            <?php Icon::get('back')->setHeight(24)->setWidth(24)->show(); ?>
                        </div>
                        <div style="margin-left: 15px;">
                            <?php echo 'Настройки'; ?>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
        <div style="overflow-y: auto; width: 100vw;">
            <div class="window-content" style="width: 100%">
                <ul class="collection">

                </ul>
            </div>
        </div>
    </div>
</div>