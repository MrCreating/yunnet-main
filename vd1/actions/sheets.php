<?php

function get_subjects_list (): array
{
    if ($_SESSION['access_level'] >= 2) {
        $res = null;

        if ($_SESSION['access_level'] === 2)
            $res = db()->prepare('SELECT id, title FROM dekanat.subjects WHERE professor_id = ?');
        if ($_SESSION['access_level'] === 3)
            $res = db()->prepare('SELECT id, title FROM dekanat.subjects;');

        if ($res && $res->execute($_SESSION['access_level'] === 2 ? [$_SESSION['vd_user_id']] : [])) {
            return $res->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return [];
}

function get_subject_info (int $id): ?array
{
    $res = db()->prepare('SELECT id, title FROM dekanat.subjects WHERE id = ?;');

    if ($res->execute([$id]))
    {
        return $res->fetch(PDO::FETCH_ASSOC);
    }

    return null;
}

function get_groups_list ()
{
    if ($_SESSION['access_level'] >= 3) {
        $res = db()->prepare('SELECT id, title FROM dekanat.groups_list;');

        if ($res && $res->execute()) {
            return $res->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return [];
}

?>