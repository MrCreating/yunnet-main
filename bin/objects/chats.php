<?php

/**
 * Tis file contains Chat and Permissions classes
 * for chats management
*/

require_once __DIR__ . '/../functions/messages.php';
require_once __DIR__ . '/../functions/users.php';
require_once __DIR__ . '/../functions/messages.php';

// class which describes the MULTIPLE chat
class Chat
{
	public $isValid = false;
	public $title   = "";
	public $photo   = NULL;

	private $uid = 0;

	private $permissions = '';
	private $link        = '';

	private $members = NULL;
	private $kickedShown = false;

	private $utils = [
		'connection' => NULL
	];

	function __construct ($connection, $uid)
	{
		if ($uid > 0)
			return $this->isValid = false;

		$this->photo = Project::getDevDomain() . "/images/default.png";

		// now we gettings link to the photo,
		// title and members list
		$res = $connection->prepare("SELECT title, photo, permissions, link FROM messages.members_engine_1 WHERE uid = :uid;");
		$res->bindParam(":uid", $uid, PDO::PARAM_INT);
		$res->execute();

		$data = $res->fetch(PDO::FETCH_ASSOC);
		if ($data)
		{
			$this->isValid     = true;
			$this->title       = $data["title"];
			$this->permissions = $data["permissions"];
			$this->link        = Project::getDefaultDomain() . '/chats?c='.$data['link'];
			if ($data["photo"] && $data["photo"] !== "")
				$this->photo = Project::getAttachmentsDomain()."/".$data["photo"];

			$this->utils["connection"] = $connection;
			$this->uid                 = intval($uid);
		}

		return true;
	}

	// get chat link
	function getLink ()
	{
		return $this->link;
	}

	// update chat link
	function updateLink ()
	{
		$new_link_query = str_shuffle('asdaAJKDSADLKN/asdklasdlqwek/djghkuwefldlkwASDLKJWQD');

		$res = $this->utils['connection']->prepare("UPDATE messages.members_engine_1 SET link = :link WHERE uid = :uid;");
		$res->bindParam(":link", $new_link_query, PDO::PARAM_STR);
		$res->bindParam(":uid",  $this->uid,      PDO::PARAM_INT);

		// ok!
		if ($res->execute()) return DEFAULT_URL.'/chats?c='.$new_link_query;

		// another error
		return false;
	}

	// get members wh not leaved and not kicked
	function getMembers ($show_kicked = false)
	{
		if (!$show_kicked)
			if ($this->members !== null) return $this->members;
		else
			if ($this->kickedMembers !== null) return $this->kickedMembers;

		$connection = $this->utils["connection"];
		if (!$connection || !$this->isValid)
			return false;

		$kicked = $show_kicked ? "" : " AND is_kicked = 0";

		$res = $connection->prepare("SELECT DISTINCT user_id, is_muted, is_leaved, invited_by, is_kicked, permissions_level, lid FROM messages.members_chat_list WHERE uid = ?".$kicked." ORDER BY permissions_level DESC;");
		$res->execute([$this->uid]);

		$result = [
			'count' => 0,
			'users' => []
		];

		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		if (!$data)
			return false;

		foreach ($data as $index => $user_info) {
			$result["count"]++;

			$result["users"]["user_".$user_info["user_id"]] = [
				'user_id'    => intval($user_info["user_id"]),
				'local_id'   => intval($user_info["lid"]),
				'invited_by' => intval($user_info["invited_by"]),
				'flags'   => [
					'is_muted'  => intval($user_info["permissions_level"]) === 9 ? false : intval($user_info["is_muted"]),
					'is_leaved' => intval($user_info["is_leaved"]),
					'is_kicked' => intval($user_info["permissions_level"]) === 9 ? false : intval($user_info["is_kicked"]),
					'level'     => intval($user_info["permissions_level"])
				]
			];
		}

		if (!$show_kicked)
			$this->members = $result;
		else
			$this->kickedMembers = $result;

		return $result;
	}

	// mute or unmute some user
	function changeWriteAccess ($owner_id, $to_id, $mute_state, $params = [])
	{
		if ($owner_id === $to_id)
			return false;

		if (!$params['actioner_id'])
			$params['actioner_id'] = $to_id;

		$mute_value = intval($mute_state) === 1 ? 0 : 1;
		$event_name = intval($mute_state) === 1 ? "unmute_user" : "mute_user";

		send_service_message($this->utils["connection"], $this->uid, $owner_id, $event_name, $params);
		return $this->utils["connection"]->prepare("UPDATE messages.members_chat_list SET is_muted = ? WHERE user_id = ? AND uid = ?;")->execute([
				intval($mute_value), intval($to_id), intval($this->uid)
			]);
	}

	// get permissions system of the chat
	function getPermissions ()
	{
		if (!$this->isValid)
			return false;

		return new Permissions($this->utils["connection"], $this->uid, $this->permissions);
	}

	// adds $add_id to chat by $user_id
	function addUser ($user_id, $add_id, $params = [])
	{
		$permissions = $this->getPermissions();
		$members     = $this->getMembers(true);

		$connection = $this->utils["connection"];
		if (!$connection || !$this->isValid)
			return false;

		$me = $members["users"]["user_".$user_id];
		if ((!$me || $me["flags"]["is_kicked"]) && !$params['join_by_link'])
			return false;

		if (!$params['actioner_id'])
			$params['actioner_id'] = $add_id;

		if ($user_id === $add_id)
		{
			if (!$me["flags"]["is_leaved"])
				return false;

			$connection->prepare("UPDATE messages.members_chat_list SET is_leaved = 0 WHERE user_id = ? AND uid = ? LIMIT 1;")->execute([intval($user_id), intval($this->uid)]);
			$connection->prepare("UPDATE messages.members_chat_list SET return_time = ? WHERE user_id = ? AND uid = ? LIMIT 1;")->execute([time(), intval($user_id), intval($this->uid)]);

			return send_service_message($connection, $this->uid, $user_id, "returned_to_chat", $params);
		} else
		{
			if ($me["flags"]["is_leaved"] && !$params['join_by_link'])
				return false;

			if ($members["users"]["user_".$add_id])
			{
				if ($params['join_by_link']) return false;

				$connection->prepare("UPDATE messages.members_chat_list SET is_leaved = 0 WHERE user_id = ? AND uid = ? LIMIT 1;")->execute([intval($add_id), intval($this->uid)]);
				$connection->prepare("UPDATE messages.members_chat_list SET is_kicked = 0 WHERE user_id = ? AND uid = ? LIMIT 1;")->execute([intval($add_id), intval($this->uid)]);
				$connection->prepare("UPDATE messages.members_chat_list SET invited_by = ? WHERE user_id = ? AND uid = ? LIMIT 1;")->execute([intval($user_id), intval($add_id), intval($this->uid)]);
				$connection->prepare("UPDATE messages.members_chat_list SET return_time = ? WHERE user_id = ? AND uid = ? LIMIT 1;")->execute([time(), intval($add_id), intval($this->uid)]);
			} else
			{
				$res = $connection->prepare("SELECT lid FROM messages.members_chat_list WHERE user_id = ? ORDER BY lid LIMIT 1;");
				$res->execute([intval($add_id)]);
				$lid = intval($res->fetch(PDO::FETCH_ASSOC)["lid"])-1;
				if ($lid === 0)
					$lid = $lid - 1;

				if ($params["join_by_link"])
				{
					$params = [
						'chat_id' => $lid,
						'is_bot'  => false,
						'join_by_link' => true
					];
				}

				$connection->prepare('INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id,  invited_by, return_time, leaved_time, last_time, is_kicked, is_leaved) VALUES (?, ?, ?, 0, ?, ?, ?, ?, 0, 0);')->execute([$add_id, $lid, $this->uid, $user_id, time(), 1, time()]);
			}

			send_service_message($connection, $this->uid, (!$params['join_by_link'] ? $user_id : $add_id), (!$params['join_by_link'] ? "invited_user" : "join_by_link"), $params);

			if ($params["join_by_link"]) return $lid;
			return true;
		}
	}

	// removes $remover_id from chat by $user_id
	function removeUser ($user_id, $remover_id, $params = [])
	{
		$permissions = $this->getPermissions();
		$members     = $this->getMembers();

		$connection = $this->utils["connection"];
		if (!$connection || !$this->isValid)
			return false;

		$me = $members["users"]["user_".$user_id];
		if (!$me || $me["flags"]["is_kicked"])
			return false;

		if (!$params['actioner_id'])
			$params['actioner_id'] = $remover_id;

		if ($user_id === $remover_id)
		{
			if ($me["flags"]["is_leaved"])
				return false;

			send_service_message($connection, $this->uid, $user_id, "leaved_chat", $params);
			$connection->prepare("UPDATE messages.members_chat_list SET is_leaved = 1 WHERE user_id = ? AND uid = ?;")->execute([intval($user_id), intval($this->uid)]);
			$connection->prepare("UPDATE messages.members_chat_list SET leaved_time = ? WHERE user_id = ? AND uid = ?;")->execute([time(), intval($user_id), intval($this->uid)]);
		
			return true;
		} else
		{
			if ($me["flags"]["is_leaved"] || $me['flags']['is_kicked']) return false;

			$user_to_kick = $members["users"]["user_".$remover_id];
			if ($user_to_kick)
			{
				if (!$user_to_kick || $user_to_kick['flags']['is_kicked']) return false;
				if ($me['flags']['level'] <= $user_to_kick['flags']['level']) return false;

				send_service_message($connection, $this->uid, $user_id, "kicked_user", $params);
				$connection->prepare("UPDATE messages.members_chat_list SET is_leaved = 1 WHERE permissions_level != 9 AND user_id = ? AND uid = ?")->execute([intval($remover_id), intval($this->uid)]);
				$connection->prepare("UPDATE messages.members_chat_list SET is_kicked = 1 WHERE permissions_level != 9 AND user_id = ? AND uid = ?")->execute([intval($remover_id), intval($this->uid)]);
				$connection->prepare("UPDATE messages.members_chat_list SET leaved_time = ? WHERE permissions_level != 9 AND user_id = ? AND uid = ?")->execute([time(), intval($remover_id), intval($this->uid)]);
			} else
			{
				// this user not found in chat!
				return false;
			}

			return true;
		}
	}

	// sets new title of current chat
	function setTitle ($owner_id, $title, $params = [])
	{
		if (is_empty($title) || strlen($title) > 64) return false;

		send_service_message($this->utils["connection"], $this->uid, $owner_id, "change_title", $params);
		$decoded_title = htmlspecialchars_decode($title);

		$res = $this->utils["connection"]->prepare("UPDATE messages.members_engine_1 SET title = :new_title WHERE uid = :uid;");
		$res->bindParam(":uid",         $this->uid,              PDO::PARAM_INT);
		$res->bindParam(":new_title",   $decoded_title, PDO::PARAM_STR);

		return $res->execute();
	}

	// updates chat photo or deletes it.
	function updatePhoto ($photo = null, $owner_id, $params)
	{
		if (!$photo)
		{
			send_service_message($this->utils["connection"], $this->uid, $owner_id, "deleted_photo", $params);

			return $this->utils["connection"]->prepare("UPDATE messages.members_engine_1 SET photo = '' WHERE uid = ?;")->execute([$this->uid]);
		} else
		{
			$query = $photo->getQuery();
			if (!$query) return false;

			send_service_message($this->utils["connection"], $this->uid, $owner_id, "updated_photo", $params);
			$res = $this->utils["connection"]->prepare("UPDATE messages.members_engine_1 SET photo = :photo WHERE uid = :uid;");

			$res->bindParam(":uid",   $this->uid, PDO::PARAM_INT);
			$res->bindParam(":photo", $query,     PDO::PARAM_STR);

			return $res->execute();
		}
	}

	// set new $user_id access level
	function setUserLevel ($user_id, $new_level = 0)
	{
		return $this->utils["connection"]->prepare("UPDATE messages.members_chat_list SET permissions_level = ? WHERE user_id = ? AND uid = ?;")->execute([intval($new_level), intval($user_id), intval($this->uid)]);
	}
}

class Permissions
{	
	private $uid         = 0;
	private $permissions = [];
	private $utils       = [
		'connection' => NULL
	];

	// setting up permissions system
	function __construct ($connection, $uid, $permissions)
	{
		$this->utils["connection"] = $connection;
		$this->uid                 = intval($uid);
		$this->permissions         = unserialize($permissions);
	}

	// get permission value;
	function getValue ($value)
	{
		if (!isset($this->permissions[$value]))
			return false;

		return intval($this->permissions[$value]);
	}

	// set value of permissions
	function setValue ($key, $new_value = 0)
	{
		if (!isset($this->permissions[$key]))
			return false;

		if (intval($new_value) < 0 || intval($new_value) > 9)
			return false;

		if (!isset($this->permissions[$key]))
			return false;

		$this->permissions[$key] = intval($new_value);

		$res = $this->utils["connection"]->prepare("UPDATE messages.members_engine_1 SET permissions = :permissions WHERE uid = :uid;");

		$saved_permissions = serialize($this->permissions);

		$res->bindParam(":uid",         $this->uid,         PDO::PARAM_INT);
		$res->bindParam(":permissions", $saved_permissions, PDO::PARAM_STR);
		
		return $res->execute();
	}

	// get all perms
	function getAll ()
	{
		return $this->permissions;
	}
}

// creates the new multi chat
// returns -1 if the users count error.
// returns -2 if title error.
// returns false if unknown error
function create_chat ($connection, $creator_id, $title, $users_list, $customPermissions = null, $chatPhoto = null)
{
	$users = [];
	$title = trim($title);

	if (is_empty($title) || count($title) > 64)
		return ['error'=>-2];

	if (count($users_list) > 1000)
		return ['error'=>-1];

	foreach ($users_list as $index => $user_id) {
		if (!in_array(intval($user_id), $users) && $user_id !== $creator_id)
			$users[] = intval($user_id);
	}

	if (count($users) < 2 || count($users) > 500)
		return ['error'=>-1];

	$new_users = [$creator_id];

	if ($creator_id > 0)
	{
		foreach ($users as $index => $user_id) {
			if (can_invite_to_chat($connection, $creator_id, new User($user_id))) $new_users[] = intval($user_id);
		}
	}

	$uid = get_last_uid(false);
	if (!$uid)
		return false;

	$permissions = [
		'can_change_title'  => 4,
		'can_change_photo'  => 4,
		'can_kick'          => 7,
		'can_invite'        => 7,
		'can_invite_bots'   => 8,
		'can_mute'          => 5,
		'can_pin_message'   => 4,
		'delete_messages_2' => 7,
		'can_change_levels' => 9,
		'can_link_join'     => 0
	];

	foreach ($permissions as $permissionName => $value) {
		if (isset($customPermissions[$permissionName]))
		{
			if (intval($customPermissions[$permissionName]) >= 0 && intval($customPermissions[$permissionName]) <= 9) $permissions[$permissionName] = intval($customPermissions[$permissionName]);
		}
	}

	$res = $connection->prepare('INSERT INTO messages.members_engine_1 (uid, title, permissions, photo) VALUES (:uid, :title, :perms, :photo);');

	$photo_src = '';
	if ($chatPhoto)
	{
		if ($chatPhoto->valid())
		{
			$photo_src = $chatPhoto->getQuery();
		}
	}

	$saved_permissions = serialize($permissions);
	$res->bindParam(":uid",   $uid,               PDO::PARAM_INT);
	$res->bindParam(":title", $title,             PDO::PARAM_STR);
	$res->bindParam(":perms", $saved_permissions, PDO::PARAM_STR);
	$res->bindParam(":photo", $photo_src,         PDO::PARAM_STR);

	$res->execute();

	$my_lid = 0;
	foreach ($new_users as $index => $user_id) {
		$res = $connection->prepare("SELECT lid FROM messages.members_chat_list WHERE user_id = ? ORDER BY lid LIMIT 1;");
		$res->execute([intval($user_id)]);

		$permissions_level = $creator_id === $user_id ? 9 : 0;
		$lid = intval($res->fetch(PDO::FETCH_ASSOC)["lid"])-1;
		if ($user_id === $creator_id && $my_lid === 0)
			$my_lid = $lid;

		$connection->prepare('INSERT INTO messages.members_chat_list (user_id, lid, uid, cleared_message_id, invited_by, permissions_level, last_time) VALUES (?, ?, ?, 0, ?, ?, ?);')->execute([$user_id, $lid, $uid, $creator_id, $permissions_level, time()]);
	}

	send_service_message($connection, $uid, $creator_id, "chat_create", [
		'chat_id'   => $my_lid,
		'is_bot'    => false,
		'new_title' => $title
	]);

	return $my_lid;
}

?>