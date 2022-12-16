<?php

namespace unt\functions\wall;

/**
 * functions with user's wall.
*/

use PDO;
use unt\objects\Bot;
use unt\objects\Context;
use unt\objects\Post;
use unt\objects\User;
use unt\parsers\AttachmentsParser;
use unt\platform\DataBaseManager;

/**
 * Checks the posts write access
 * @return true if you can or false on error
 *
 * @deprecated
 * Parameters:
 * @param $user_id - current user id
 * @param $check_id - who check to permission?
*/
function can_write_posts ($connection, $user_id, $check_id): bool
{
	if (!Context::get()->isLogged()) return false;
	
	// current user always can write to itself
	if (intval($user_id) === intval($check_id)) return true;

	$object = intval($check_id) > 0 ? new User(intval($check_id)) : new Bot(intval($check_id)*-1);

	// only exists
	if (!$object->valid()) return false;

	$can_write_posts = $object->getSettings()->getSettingsGroup(\unt\objects\Settings::PRIVACY_GROUP)->getGroupValue('can_write_on_wall');

	// all users can write
	if ($can_write_posts === 0) return true;

	/**
	 * Here we will to check user friendship.
	*/

	// checking if only friends level set.
	if ($object->getType() === User::ENTITY_TYPE && $can_write_posts === 1 && $object->isFriends()) return true;

	// only owners can write on bot's wall
	if ($object->getType() === "bot" && $can_write_posts === 2 && intval($user_id) === $object->getOwnerId()) return true;

	// another errors is a false for safety
	return false;
}

/**
 * Creates an a post
 * @return post array if ok or false if error
 *
 * Parameters:
 * @param $owner_id - new owner id of the post
 * @param $wall_id - wall id for new post
 * @param $text (max 128000 symbols)
 * @param $attachments - attachments for the post
*/
function create_post ($connection, $owner_id, $wall_id, $text = '', $attachments = '', $event = '')
{
	if (!\unt\functions\is_empty($event))
	{
		$allowed_events = ['updated_photo'];

		if (!in_array($event, $allowed_events)) $event = '';
	}

	$attachments_string = [];
	$objects = (new AttachmentsParser())->getObjects($attachments);
	foreach ($objects as $index => $attachment) 
	{
		$attachments_string[] = $attachment->getCredentials();
	}

	// empty post is not allowed
	if (\unt\functions\is_empty($text) && count($attachments_string) <= 0) return false;

	// too long text is not allowed
	if (strlen($text) > 128000) return false;

	// checking the user existance.
	if (!($wall_id > 0 ? User::findById($wall_id) : Bot::findById($wall_id)) == NULL) return false;

	// attachments
	$attachments = implode(',', $attachments_string);

	/**
	 * Now getting the local id and increment it.
	*/
	$res = DataBaseManager::getConnection()->prepare("SELECT COUNT(DISTINCT local_id) FROM wall.posts WHERE to_id = ?;");
	
	if ($res->execute([intval($wall_id)]))
	{
		$time         = time();
		$new_local_id = intval($res->fetch(PDO::FETCH_ASSOC)['COUNT(DISTINCT local_id)']) + 1;
		
		// creating new post.
		$res = DataBaseManager::getConnection()->prepare("INSERT INTO wall.posts (owner_id, local_id, text, time, to_id, attachments, event) VALUES (:owner_id, :local_id, :text, :time, :to_id, :attachments, :event);");

		// binding post data.
		$res->bindParam(":owner_id",    $owner_id,     PDO::PARAM_INT);
		$res->bindParam(":local_id",    $new_local_id, PDO::PARAM_INT);
		$res->bindParam(":text",        $text,         PDO::PARAM_STR);
		$res->bindParam(":time",        $time,         PDO::PARAM_INT);
		$res->bindParam(":to_id",       $wall_id,      PDO::PARAM_INT);
		$res->bindParam(":attachments", $attachments,  PDO::PARAM_STR);
		$res->bindParam(":event",       $event,        PDO::PARAM_STR);
		if ($res->execute())
		{
			return $new_local_id;
		}
	}

	// another errors
	return false;
}

/**
 * Edits the post
 * @return true if ok.
 *
 * Parameters:
 * @param $user_id - who edits the post.
 * @param $wall_id - where post locates?
 * @param $post_id - local id of the post on the wall
 * @param $text - text of the post
 * @param $attachments - attachments for new post
*/
function update_post_data ($connection, $user_id, $wall_id, $post_id, $text = '', $attachments = ''): bool
{
	$attachments_list = [];
	$objects = (new AttachmentsParser())->getObjects($attachments);
	foreach ($objects as $index => $attachment) 
	{
		$attachments_list[] = $attachment;
	}

	// empty post is not allowed
	if (\unt\functions\is_empty($text) && count($attachments_list) <= 0) return false;

	// too long text is not allowed
	if (strlen($text) > 128000) return false;

	// now getting post.
	$post = Post::findById($wall_id, $post_id);

	// non-existing posts is not allowed
	if (!$post) return false;

	/**
	 * We can edit only own posts on any wall
	*/
	if ($post->getOwnerId() !== intval($user_id)) return false;

	return $post->setText($text)->setAttachmentsList($attachments_list)->apply();
}

?>