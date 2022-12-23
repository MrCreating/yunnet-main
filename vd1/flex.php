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

if ($action === 'create_group')
{
    if ($_SESSION['access_level'] >= 3)
    {
        if (empty(\unt\objects\Request::get()->data['group_name']))
            die(json_encode([
                'error' => 1
            ]));

        $result = create_group(\unt\objects\Request::get()->data['group_name']);
        if (!$result)
            die(json_encode([
                'error' => 1
            ]));

        die(json_encode([
            'success' => 1
        ]));
    }

    die(json_encode([
        'error' => 1
    ]));
}

if ($action === 'delete_group')
{
    if ($_SESSION['access_level'] >= 3)
    {
        if (intval(\unt\objects\Request::get()->data['group_id']) <= 0)
            die(json_encode([
                'error' => 1
            ]));

        $result = delete_group(\unt\objects\Request::get()->data['group_id']);
        if (!$result)
            die(json_encode([
                'error' => 1
            ]));

        die(json_encode([
            'success' => 1
        ]));
    }

    die(json_encode([
        'error' => 1
    ]));
}