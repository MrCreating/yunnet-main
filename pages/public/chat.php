<?php
$content = '';

$action = strtolower($context->request['action']);
$query  = strval($context->request['q']);
if (!is_empty($query))
{
	if (!function_exists('get_chat_by_query'))
		require __DIR__ . '/../../bin/functions/chats.php';

	$chat = get_chat_by_query($connection, $query, $context->user_id);
	if (!$chat)
		die(header("/messages"));

	$title   = htmlspecialchars($chat->title);
	$photo   = $chat->photo;
	$members = $chat->getMembers();
	if ($members['users']['user_'.$context->user_id])
	{
		die(header("Location: /messages?s=".$members['users']['user_'.$context->user_id]['local_id']));
	}

	if ($action === "join")
	{
		$owner_id_of_chat = 0;
		foreach ($members['users'] as $index => $user) {
			if ($user['flags']['level'] >= 9)
			{
				$owner_id_of_chat = intval($user['user_id']); break;
			}
		}

		$lid = $chat->addUser($owner_id_of_chat, $context->user_id, [
			'join_by_link' => true
		]);

		die(header("Location: /messages?s=".$lid));
	}

	if ($context->profile["user"]->getNavButtonState() && !$context->isMobile)
		$content .= '
		<ul class="collapsible">
			<li>
				<div class="collapsible-header" tabindex="0">
					<div class="valign-wrapper">
						<a href="/messages">
							<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"></path><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path></svg>
						</a>
						<div style="margin-left: 10px; margin-bottom: 5px">
							Приглашение в беседу
						</div>
					</div>
				</div>		
			</li>
		</ul>';

	$content .= '
		<div class="card">
			<div class="halign-wrapper full_section">
				<div class="center">
					<div style="text-align: -webkit-center;">
						<img class="circle" height="64" width="64" src="'.$photo.'">
					</div>
					<div id="chat_title">
						<h5><b>'.$title.'</b></h5>
					</div>
					<div id="chat_members">
						'.($members["count"]-1).' '.$context->Morphy($members["count"]-1, $context->lang['members'], $context->lang['id']).'
					</div>
					<br><br>
					<a href="/chat?action=join&q='.$context->request['q'].'" class="btn btn-large waves-effect waves-light">Присоединиться</a>
				</div>
			</div>
		</div>
		'.$script.'
	';
	$content = '';

	$default_html = set_page_title($default_html, 'yunNet. - '.$context->lang['chat']);
	$default_html = set_right_menu($default_html, EMPTY_RIGHT_MENU);
	$default_html = set_page_content($default_html, $context->isMobile ? $content : '<div class="col s6">'.$content.'</div>');

	die($default_html);
}

die(header("Location: /messages"));
?>