<?php
$context = Context::get();
$connection = $context->getConnection();

require_once __DIR__ . '/../../page_templates.php';

if (REQUESTED_PAGE === '/flex')
{
	die(require_once __DIR__ . '/flex.php');
}

die(default_page_template(Context::get()->isMobile(), Context::get()->getLanguage()->id, Context::get()->getCurrentUser()));

?>