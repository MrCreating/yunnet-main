<?php

$_SESSION = [];

session_destroy();

die(header('Location: /'));
?>