<?php

/**
 * Polls module file.
*/

if (!class_exists('Poll'))
	require __DIR__ . '/../objects/poll.php';

/**
 * Creates a poll
 * @return Poll object or false if error
 * 
 * Parameters:
 * $poll_title: str - Poll title.
 * $is_anonymous: bool - Anonymous poll or not (can see voted users or not)
 * $multi_selection: bool - cab multiple select or not
 * $can_revote: bool - can revote or not
 * $end_time: int - ending UNIX time or 0 if unlimited time.
 * $variants_list: array - array with variants list (associative arrays)
*/
function create_poll ($connection, $poll_title, $owner_id, $variants_list, $is_anonymous = false, $multi_selection = false, $can_revote = true, $end_time = 0)
{

	if (is_empty($poll_title)) return false;
	if (strlen($title) > 64) return false;

	if (count($variants_list) > 10 || count($variants_list) < 1) return false;

	$res = $connection->prepare("
		INSERT INTO 
			polls.info (owner_id, access_key, title, isAnonymous, endTIme, canMultiSelect, canRevote, creationTime) 
		VALUES (:owner_id, :access_key, :title, :isAnonymous, :endTime, :canMultiSelect, :canRevote, :creationTime);
	");

	$new_access_key = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 10);

	$p_owner_id        = intval($owner_id);
	$p_poll_title      = strval($poll_title);
	$p_is_anonymous    = intval(boolval($is_anonymous));
	$p_end_time        = intval($end_time);
	$p_multi_selection = intval(boolval($multi_selection));
	$p_can_revote      = intval(boolval($can_revote));
	$p_creation_time   = intval(time());

	$res->bindParam(":owner_id",       $p_owner_id,        PDO::PARAM_INT);
	$res->bindParam(":access_key",     $new_access_key,    PDO::PARAM_STR);
	$res->bindParam(":title",          $p_poll_title,      PDO::PARAM_STR);
	$res->bindParam(":isAnonymous",    $p_is_anonymous,    PDO::PARAM_INT);
	$res->bindParam(":endTime",        $p_end_time,        PDO::PARAM_INT);
	$res->bindParam(":canMultiSelect", $p_multi_selection, PDO::PARAM_INT);
	$res->bindParam(":canRevote",      $p_can_revote,      PDO::PARAM_INT);
	$res->bindParam(":creationTime",   $p_creation_time,   PDO::PARAM_INT);

	if ($res->execute())
	{
		$res = $connection->prepare("SELECT LAST_INSERT_ID();");
		
		if ($res->execute())
		{
			$created_poll_id = intval($res->fetch(PDO::FETCH_ASSOC)["LAST_INSERT_ID()"]);

			$poll = new Poll($p_owner_id, $created_poll_id, $new_access_key);
			foreach ($variants_list as $index => $variantTitle) {
				$poll->addAnswer($variantTitle);
			}

			return $poll;
		}
	}

	return false;
}

?>