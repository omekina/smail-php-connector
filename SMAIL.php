<?php

namespace SMAIL;


const SMAIL_CONFIG_FILE = "/var/www/smail.conf";


function remove_control_characters(string $text): string {
	return preg_replace("/[\x00-\x1F\x7F]/", "", $text) ?? "";
}


function send(string $from, array $to, string $subject, string $message): bool {
	if (count($to) === 0 || strlen($subject) === 0 || strlen($message) === 0) {
		return false;
	}
  
	$from = remove_control_characters($from);
	$recipients = "";
	foreach ($to as $recipient) {
		$recipients .= remove_control_characters($recipient) . "\n";
	}
	$subject = remove_control_characters($subject);
	$message = "\\" . remove_control_characters($message);
	$formatted_stdin = "==SUBJECT\n$subject\n==FROM\n$from\n==TO\n$recipients==BODY\n$message";
	$command = "smail --config=" . SMAIL_CONFIG_FILE . " send";
	
	$process = proc_open($command, [
		0 => ["pipe", "r"],
		1 => ["pipe", "w"],
		2 => ["pipe", "w"]
	], $pipes);
	if (!is_resource($process)) {
		return false;
	}
	fwrite($pipes[0], $formatted_stdin);
	fclose($pipes[0]);

	$stdout = stream_get_contents($pipes[1]); // I suggest logging the stdout
	fclose($pipes[1]);

	fclose($pipes[2]);

	$return_value = proc_close($process);
	return $return_value === 0;
}
