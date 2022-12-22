<?php

if ($action === 'auth')
{
    $login = $_POST['login'];
    $passw = $_POST['password'];

    $user = auth($login, $passw);
    if ($user)
    {
        $_SESSION['vd_user_id'] = $user['account_id'];
        $_SESSION['access_level'] = $user['access_level'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];

        die(json_encode([
            'success' => 1
        ]));
    }

    die(json_encode([
        'error' => 1
    ]));
}