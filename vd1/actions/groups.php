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
        ');

        if ($res->execute([$id]))
        {
            return $res->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return [];
}

?>