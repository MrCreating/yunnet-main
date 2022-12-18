<?php

if ($action === 'auth')
{
    $login = $_POST['login'];
    $passw = $_POST['password'];

    if ($login === 'admin' && $passw === '123123') {
        $_SESSION['vd_user_id'] = 1;

        die(json_encode([
            'success' => 1
        ]));
    } else {
        die(json_encode([
            'error' => 1
        ]));
    }
}