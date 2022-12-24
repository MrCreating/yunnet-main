<?php

function get_group_info (int $id): ?array
{
    $res = db()->prepare('SELECT id, title FROM dekanat.groups WHERE id = ?;');

    if ($res->execute([$id]))
    {
        return $res->fetch(PDO::FETCH_ASSOC);
    }

    return null;
}

function get_group_students (int $id): array
{
    if ($_SESSION['access_level'] >= 2)
    {
        $res = db()->prepare('
            SELECT 
                g.title AS group_name,
                gc.student_id AS student_id,
                s.first_name AS first_name,
                s.last_name AS last_name
            FROM 
                dekanat.group_containing AS gc
            JOIN dekanat.`groups` AS g 
                ON g.id = gc.group_id
            JOIN dekanat.students AS s 
                ON gc.student_id = s.id
            WHERE gc.group_id = ?
            ORDER BY s.first_name ASC;
        ');

        if ($res->execute([$id]))
        {
            return $res->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return [];
}

function create_group (string $name): bool
{
    $connection = db();
    $connection->setAttribute(PDO::ERRMODE_EXCEPTION, 1);

    try {
        $res = $connection->prepare('INSERT INTO dekanat.`groups` (education_form_id, title) VALUES (1, ?);');
        if ($res->execute([$name]))
        {
            return true;
        }
    } catch (Exception $e) {
        return false;
    }

    return false;
}

function delete_group (int $id): bool
{
    $connection = db();
    $connection->setAttribute(PDO::ERRMODE_EXCEPTION, 1);

    try {
        $res = $connection->prepare('DELETE FROM dekanat.`groups` WHERE id = ? LIMIT 1');
        if ($res->execute([$id]))
        {
            return true;
        }
    } catch (Exception $e) {
        return false;
    }

    return false;
}

?>