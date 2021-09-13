<?php
require __DIR__ . '/../../../bin/context.php';
require __DIR__ . '/../../page_templates.php';

$context = new Context();
$connection = $context->getConnection();

if (REQUESTED_PAGE === '/flex')
{
	die(require __DIR__ . '/flex.php');
}

die(default_page_template($context->isMobile(), $context->getLanguage()->id, $context->getCurrentUser()));

?>