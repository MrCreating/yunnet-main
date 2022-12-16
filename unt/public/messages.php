<?php


use unt\objects\Context;
use unt\objects\Request;

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);

	switch ($action) {
		case 'get_chat_info_by_link':
			die(json_encode(array('error' => 3)));
		break;
		
		default:
		break;
	}

	if (!Context::get()->allowToUseUnt()) die(json_encode(array('error' => 1)));

	if (isset(Request::get()->data['peer_id']) || isset(Request::get()->data['chat_id']))
	{
        // TODO: восстановление с нуля сообщений здесь
        die(json_encode(array('error' => 1)));
	} else
	{
		switch ($action) {
            case 'chat_create':
            case 'get_chats':
                die(json_encode([]));
                break;

            default:
			break;
		}
	}

	die(json_encode(array('error' => 1)));
}

?>