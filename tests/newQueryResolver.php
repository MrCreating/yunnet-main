<?php

require __DIR__ . '/../bin/objects/attachment.php';

$parser = new AttachmentsParser();

die(var_dump($parser->resolveFromQuery("WbB5tK1xSeizliWWkKWyWYCxfi62HC/CfxIUwFJlKtfNsvpFml28BRdzl0GkosYAUtNCgTkS1gbAMuxXBUU=")));
?>