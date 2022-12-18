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
    if ($_SESSION['access_level'] >= 3)
        die(require __DIR__ . '/events.php');
}
if (strtolower(REQUESTED_PAGE) === '/schedule')
{
    if ($_SESSION['access_level'] >= 1)
        die(require __DIR__ . '/schedule.php');
}
if (strtolower(REQUESTED_PAGE) === '/sheet')
{
    if ($_SESSION['access_level'] >= 2)
        die(require __DIR__ . '/sheet.php');
}

if ($_SESSION['vd_user_id']) {
    die(require __DIR__ . '/lk.php');
} else {
    die(require __DIR__ . '/reg.php');
}

?>