<?php

/**
 * DB CONNECTION TEST
*/

require __DIR__ . '/../bin/database.php';

$db = new DataBaseConnection();
echo "Created DB instance!\n";

$paramsList = [
	new DBRequestParam(":user_id", 1, PDO::PARAM_INT)
];

$params = new DataBaseParams($paramsList);
echo "Params defined!\n";

$result = $db->execute("SELECT first_name FROM users.info WHERE id = :user_id LIMIT 1;", $params);

var_dump($result);
// RESULT: PASS

?>