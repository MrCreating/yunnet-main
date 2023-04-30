<?php

use unt\objects\Request;

if (isset(Request::get()->data["action"]))
{
    $action = strval(Request::get()->data['action']);
}

?>