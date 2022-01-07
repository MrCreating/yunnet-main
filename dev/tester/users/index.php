<?php

require_once __DIR__ . '/../../../pages/page_templates.php';

if (isset(Request::get()->data['action']))
{

}

die(default_page_template(Context::get()->isMobile(), Context::get()->getLanguage()->id, Context::get()->getCurrentUser()));
?>