<?php
    $lang = \unt\objects\Context::get()->getLanguage();

    http_response_code(404);
?>

<div class="main-interface-window" style="display: none">
    <div class="not_found" style="display: none"></div>
</div>
<div class="main-information-window" style="display: none">
    <div class="item-center item-halign-wrapper">
        <img src="/favicon.ico" height="96" width="96" class="circle">
        <div style="padding-bottom: 100px">
            <div class="card center" style="margin-top: 25px; padding: 20px">
                <b><?php echo $lang->page_not_found; ?></b>
                <div style="padding-top: 10px">
                    <?php echo $lang->page_not_found_subtext; ?>
                </div>
                <div style="padding-top: 20px">
                    <a class="btn btn-flat" href="/"><?php echo $lang->go_back;?></a>
                </div>
            </div>
        </div>
    </div>
</div>