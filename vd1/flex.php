<?php

if ($action === 'auth')
{
    $login = $_POST['login'];
    $passw = $_POST['password'];

    if (($login === 'admin' || $login === 'd.andreeva@stankin.ru') && $passw === '123123') {
        $_SESSION['vd_user_id'] = 1;
        $_SESSION['access_level'] = 3;
        $_SESSION['first_name'] = 'Дарья';
        $_SESSION['last_name'] = 'Андреева';
        $_SESSION['email'] = 'd.andreeva@stankin.ru';

        die(json_encode([
            'success' => 1
        ]));
    }

    if (($login === 'teacher' || $login === 'n.bychkova@stankin.ru') && $passw === 'qwerty') {
        $_SESSION['vd_user_id'] = 2;
        $_SESSION['access_level'] = 2;
        $_SESSION['first_name'] = 'Наталья';
        $_SESSION['last_name'] = 'Бычкова';
        $_SESSION['email'] = 'n.bychkova@stankin.ru';

        die(json_encode([
            'success' => 1
        ]));
    }

    if (($login === 'student' || $login === 'a.tenev@yunnet.ru') && $passw === '228228') {
        $_SESSION['vd_user_id'] = 3;
        $_SESSION['access_level'] = 1;
        $_SESSION['first_name'] = 'Андрей';
        $_SESSION['last_name'] = 'Тенёв';
        $_SESSION['email'] = 'a.tenev@yunnet.ru';

        die(json_encode([
            'success' => 1
        ]));
    }

    die(json_encode([
        'error' => 1
    ]));
}