<?php

function get_subjects_list (): array
{
    if ($_SESSION['access_level'] >= 2) {
        $res = null;

        if ($_SESSION['access_level'] === 2)
            $res = db()->prepare('
                SELECT 
                    gs.subject_id AS id,
                    title
                FROM dekanat.subjects AS s
                JOIN dekanat.going_subjects AS gs ON s.id = gs.subject_id
                WHERE professor_id = 1;
            ');
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
        $res = db()->prepare('SELECT id, title FROM dekanat.groups;');

        if ($res && $res->execute()) {
            return $res->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return [];
}

function get_sheet_groups_list ($subject_id): array
{
    return [
        [
            'id' => 26,
            'title' => 'ИДБ-20-10'
        ]
    ];
}

?>