<?php
if (!function_exists('emit_event'))
	require __DIR__ . "/../emitters.php";

/**
 * Tools for notifications management
 * create, read, delete, etc.
*/

/**
 * Class who has some params:
 * @param: type - type of notification (login, comment, friend_request, etc.)
 * @param: connection - connection with database object 
*/
class Notification
{
	public $state     = 0;
	public $is_hidden = 0;
	public $is_read   = 0;
	public $type      = null;
	public $isValid   = false;

	private $ownerId = 0;
	private $noteId  = 0;
	private $isSent  = 0;
	private $destId  = 0;
	private $data    = [];
	private $utils   = [];

	function __construct ($connection, $lid, $owner_id)
	{
		$this->utils["connection"] = $connection;
		$this->ownerId             = $owner_id;

		$res = $connection->prepare("SELECT id, type, local_id, data, is_read, is_hidden FROM users.notes WHERE local_id = :lid AND owner_id = :owner_id LIMIT 1;");
		$res->bindParam(":owner_id", $this->ownerId, PDO::PARAM_INT);
		$res->bindParam(":lid",      $lid,           PDO::PARAM_INT);
		$res->execute();

		$data = $res->fetch(PDO::FETCH_ASSOC);
		if ($data["id"])
		{
			$this->state     = 1;
			$this->noteId    = intval($data["local_id"]);
			$this->data      = json_decode($data["data"], true);
			$this->type      = strval($data["type"]);
			$this->is_read   = intval($data["is_read"]);
			$this->is_hidden = boolval(intval($data['is_hidden']));
			$this->isValid   = true;
		}
	}

	// get data array of notification
	function getData ()
	{
		return $this->data;
	}

	// get current notification's id
	function getId ()
	{
		if (!$this->noteId)
			return false;

		return $this->noteId;
	}

	// reads (removes) the selected notification
	function read ()
	{
		emit_event([$this->ownerId], [0], [
			'event' => 'notification_read',
			'data'  => $this->toArray()
		]);

		return $this->utils["connection"]->prepare("UPDATE users.notes SET is_read = 1 WHERE local_id = ? AND owner_id = ?;")->execute([intval($this->noteId), intval($this->ownerId)]);
	}

	// hides the notification (counter down)
	function hide ()
	{
		emit_event([$this->ownerId], [0], [
			'event' => 'notification_hide',
			'data'  => $this->toArray()
		]);

		return $this->utils["connection"]->prepare("UPDATE users.notes SET is_hidden = 1 WHERE local_id = ? AND owner_id = ?;")->execute([intval($this->noteId), intval($this->ownerId)]);
	}

	// convert class to array (for json responses)
	function toArray ()
	{
		$result = [
			'id'   => intval($this->noteId),
			'type' => strtolower($this->type),
			'data' => $this->data
		];

		if ($this->is_hidden)
			$result['is_hidden'] = true;

		return $result;
	}
}

/**
 * function for notification creation
 * returns Notificaions class if notifications created
 * or false if error will happen
*/
function create_notification ($connection, $to_id, $type, $data)
{
	$res = $connection->prepare("SELECT DISTINCT local_id FROM users.notes WHERE owner_id = ? ORDER BY local_id DESC LIMIT 1;");
	$res->execute([intval($to_id)]);

	// getting local_id of new notifications
	$new_local_id = intval($res->fetch(PDO::FETCH_ASSOC)["local_id"])+1;

	$res = $connection->prepare("INSERT INTO users.notes (owner_id, local_id, type, data, is_read) VALUES (:owner_id, :local_id, :type, :data, 0);");

	$encoded_data = json_encode($data);

	$res->bindParam(":owner_id", $to_id,        PDO::PARAM_INT);
	$res->bindParam(":local_id", $new_local_id, PDO::PARAM_INT);
	$res->bindParam(":type",     $type,         PDO::PARAM_STR);
	$res->bindParam(":data",     $encoded_data, PDO::PARAM_STR);

	if ($res->execute())
		$result = new Notification($connection, $new_local_id, $to_id);

	if ($result)
	{
		$user = new User($to_id);

		// emit event if it allow settings.
		if ($user->getSettings()->getSettingsGroup('push')->isNotificationsEnabled())
		{
			emit_event([$to_id], [0], [
				'event'        => 'new_notification',
				'notification' => [
					'id'   => $new_local_id,
					'type' => $type,
					'data' => $data
				]
			]);
		}
	}

	// return Notification class
	return $result;
}

/**
 * function who gets all notifications of selected $user_id
*/
function get_notifications ($connection, $user_id, $offset = 0, $count = 20)
{
	if ($count < 0 || $count > 100) return [];
	if ($offset < 0) return false;

	$res = $connection->prepare("SELECT DISTINCT local_id FROM users.notes WHERE owner_id = ? AND is_read = 0 LIMIT ".intval($offset).",".intval($count).";");
	$res->execute([intval($user_id)]);

	$local_ids  = $res->fetchAll(PDO::FETCH_ASSOC);
	$notes_list = [];
	foreach ($local_ids as $index => $id)
	{
		$notes_list[] = new Notification($connection, intval($id["local_id"]), intval($user_id));
	}

	return array_reverse($notes_list);
}

?>