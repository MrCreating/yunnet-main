<?php

$credentials = explode('?', substr($_SERVER['REQUEST_URI'], 1))[0];

$poll = (new AttachmentsParser())->getObject($credentials);
if (!$poll)
	die(json_encode(array('error' => 1)));

$owner = Entity::findById($poll->getOwnerId());
if ($owner->inBlacklist())
	die(json_encode(array('error' => 1)));

if (isset(Request::get()->data['action']))
{
	$action = strtolower(Request::get()->data['action']);
	if ($action === 'add_vote')
	{
		$variant_ids = array_filter(array_map(function ($item) {
			return intval($item);
		}, explode(',', Request::get()->data['answer_id'])), function ($item) {
			return $item >= 1 && $item <= 10;
		});

		if (count($variant_ids) <= 0)
			die(json_encode(array('error' => 1)));

		foreach ($variant_ids as $id)
		{
			if (!$poll->vote($id))
				die(json_encode(array('error' => 1)));
		}

		die(json_encode(array('stats' => $poll->getStats())));
	}
	if ($action === 'get_voted')
	{
		$variant_id = intval(Request::get()->data['variant_id']);
		if ($variant_id <= 0 || $variant_id > 10)
			die(json_encode(array('error' => 1)));

		if ($poll->isVoted())
			die(json_encode(array('users' => array_map(function ($item) {
				return $item->toArray('*');
			}, $poll->getVoters($variant_id)))));

		die(json_encode(array('error' => 1)));
	}
}

die(json_encode(array('error' => 1)));
?>