<?php
$context = Context::get();
$connection = $context->getConnection();

require_once __DIR__ . '/../../page_templates.php';

if (REQUESTED_PAGE === '/login')
{
	die(require_once __DIR__ . '/login.php');
}
if (REQUESTED_PAGE === '/flex')
{
	die(require_once __DIR__ . '/flex.php');
}
if (REQUESTED_PAGE === '/settings')
{
	die(require_once __DIR__ . '/settings.php');
}

die(default_page_template(Context::get()->isMobile(), Context::get()->getLanguage()->id, Context::get()->getCurrentUser()));

?>