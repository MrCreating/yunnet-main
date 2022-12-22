<?php

function get_group_info (int $id): ?array
{
    $res = db()->prepare('SELECT id, title FROM dekanat.groups_list WHERE id = ?;');

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
                gl.title AS group_name,
                g.student_id AS student_id,
                s.first_name AS first_name,
                s.last_name AS last_name 
            FROM 
                dekanat.`groups` AS g
            JOIN
                dekanat.students AS s ON g.student_id = s.id
            JOIN
                dekanat.groups_list AS gl ON g.group_id = gl.id
            WHERE 
                group_id = ?;
        ');

        if ($res->execute([$id]))
        {
            return $res->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return [];
}

?>