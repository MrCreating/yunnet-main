<?php

$action = isset($_POST['action']) ? strtolower($_POST['action']) : NULL;

if ($action) {
    die(require __DIR__ . '/flex.php');
}

if (strtolower(REQUESTED_PAGE) === '/logout')
{
    die(require __DIR__ . '/logout.php');
}
if (strtolower(REQUESTED_PAGE) === '/events')
{
    die(require __DIR__ . '/events.php');
}
if (strtolower(REQUESTED_PAGE) === '/schedule')
{
    die(require __DIR__ . '/schedule.php');
}
if (strtolower(REQUESTED_PAGE) === '/sheet')
{
    die(require __DIR__ . '/sheet.php');
}

if ($_SESSION['vd_user_id']) {
    die(require __DIR__ . '/lk.php');
} else {
    die(require __DIR__ . '/reg.php');
}

?>