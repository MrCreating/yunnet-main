<?php

use unt\objects\Context;
use unt\objects\Group;
use unt\objects\Request;

if (isset(Request::get()->data["action"]))
{
    if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

    $action = strtolower(Request::get()->data["action"]);

    if ($action === 'create_group') {
        $title       = trim(strval(Request::get()->data['title']));
        $description = trim(strval(Request::get()->data['description']));
        $type        = intval(Request::get()->data['type']);

        $group = Group::create($title, $description, $type);

        if ($group) {
            die(json_encode($group->toArray()));
        }
    }
    if ($action === 'join_group') {
        $group_id = intval(Request::get()->data['group_id']);

        $group = Group::findById($group_id);
        if ($group) {
            die(json_encode(array('success' => $group->addMember($_SESSION['user_id']))));
        }
    }
    if ($action === 'leave_group') {
        $group_id = intval(Request::get()->data['group_id']);

        $group = Group::findById($group_id);
        if ($group) {
            die(json_encode(array('success' => $group->removeMember($_SESSION['user_id']))));
        }
    }
    if ($action === 'get_list') {
        $offset = intval(Request::get()->data['offset']);
        $count = intval(Request::get()->data['count']);

        die(json_encode(array_map(function ($group) {
            return $group->toArray();
        }, Group::getList($offset, $count))));
    }

    die(json_encode(array('error' => 1)));
}

?>