<?php

function auth (string $login, string $password): ?array
{
    $req = db()->prepare('SELECT id, account_id, access_level FROM dekanat.student_accounts WHERE login = ? AND `password` = ? LIMIT 1');
    if ($req->execute([$login, $password]))
    {
        $res = $req->fetch(PDO::FETCH_ASSOC);

        $access_level = (int) $res['access_level'];
        $account_id   = (int) $res['account_id'];

        return get_user($account_id, $access_level);
    }

    return null;
}

function get_user (int $id, int $access_level): ?array
{
    $req = null;

    // студент
    if ($access_level === 1)
        $req = db()->prepare('SELECT first_name, last_name FROM dekanat.students WHERE id = ? LIMIT 1;');

    // преподаватель
    if ($access_level === 2)
        $req = db()->prepare('SELECT first_name, last_name FROM dekanat.professors WHERE id = ? LIMIT 1;');

    // работник деканата
    if ($access_level === 3)
        $req = db()->prepare('SELECT first_name, last_name FROM dekanat.employees WHERE id = ? LIMIT 1;');

    if ($req !== NULL && $req->execute([$id]))
    {
        $data = $req->fetch(PDO::FETCH_ASSOC);

        return array_merge($data, [
            'account_id' => $id,
            'access_level' => $access_level
        ]);
    }

    return NULL;
}