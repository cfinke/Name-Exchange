<?php

include "config.php";

function stripslashes_deep($value) {
	$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
	return $value;
}

if (get_magic_quotes_gpc()) {
	$_POST = array_map('stripslashes_deep', $_POST);
	$_GET = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

$meta = array("title" => "Name Exchange");

if (isset($_POST["names"])) {
	view_confirm($_POST["names"]);
}
else if (isset($_POST["households"])) {
	view_complete(unserialize(base64_decode($_POST["households"])));
}
else {
	view_main($_POST["initial_names"]);
}

function view_main($names, $error = null) {
	global $meta;
	
	include "templates/main.php";
}

function view_confirm($names) {
	global $meta;
	
	$households = array();
	
	$name_lines = explode("\n", trim($names));
	$name_lines[] = "";
	
	$household = array();
	
	$flat_list = array();
	
	foreach ($name_lines as $name_line) {
		$name_line = trim($name_line);
		
		if (!$name_line) {
			if ($household) {
				$households[] = $household;
			}
			
			$household = array();
		}
		else {
			$name_words = explode(" ", $name_line);
			
			$name = "";
			$email_address = "";
			
			foreach ($name_words as $name_word) {
				if (strpos($name_word, "@") !== false) {
					$email_address = str_replace(array('"', "'", "<"), "", $name_word);
				}
				else {
					$name .= " " . $name_word;
				}
			}
			
			$name = trim($name);
			
			if (!$name && $email_address) {
				$name = $email_address;
			}
			
			$household[] = array("name" => $name, "email" => $email_address);
		}
	}
	
	if ($duplicate = has_duplicates($households)) {
		$error = "You have a duplicate entry: " . htmlspecialchars($duplicate["name"]) . " (" . htmlspecialchars($duplicate["email"]) . ")";
		
		return view_main($names, $error);
	}
	
	if (count($households) > 1) {
		if (!is_valid_group_swap($households)) {
			$error = "Invalid group swap - there aren't enough people in all of the groups for this to work. You may want to consider eliminating the separate groups and doing one big exchange.";
			
			return view_main($names, $error);
		}
	}
	
	include "templates/confirm.php";
}

function view_complete($_households) {
	global $meta;
	
	$households = array();
	
	$idx = 0;
	
	if (has_duplicates($_households)) {
		echo "You have a duplicate entry: " . $member["name"] . " " . $member["email"];
		die;
	}
		
	$flat_list = array();
	
	foreach ($_households as $_household) {
		$keyed_household = array();
		
		foreach ($_household as $member) {
			$member_key = $member["name"] . $member["email"];
			
			$keyed_household[$member_key] = $member;
			$flat_list[$member_key] = $member;
		}
		
		$households["House" . $idx++] = $keyed_household;
	}
	
	function standard_swap($participants) {
		while (true) {
			$remaining_participants = $participants;
		
			$matches = array();
		
			foreach ($participants as $p_key => $p) {
				$match = array_rand($remaining_participants);
				
				if ($p_key == $match) {
					continue 2;
				}
				
				$matches[] = array($p_key, $match);
				unset($remaining_participants[$match]);
			}
			
			return $matches;
		}
	}
	
	function group_swap($groups) {
		$original_groups = $groups;
		
		if (!is_valid_group_swap($groups)) {
			return false;
		}
		
		while (true) {
			$matches = array();
			
			$groups = $original_groups;
			
			$remaining_members = shuffle_assoc($groups);
			
			foreach ($groups as $group_key => $members) {
				foreach ($members as $member_key => $member) {
					$swap_group_key = array_rand($remaining_members);
				
					if ($swap_group_key == $group_key) {
						continue 3;
					}
				
					$swap_member_key = array_rand($remaining_members[$swap_group_key]);
				
					$matches[] = array($member_key, $swap_member_key);
					
					unset($remaining_members[$swap_group_key][$swap_member_key]);
					
					if (count($remaining_members[$swap_group_key]) == 0) {
						unset($remaining_members[$swap_group_key]);
					}
				}
			}
			
			return $matches;
		}
	}
	
	if (count($households) == 1) {
		$matches = standard_swap($households["House0"]);
	}
	else {
		$matches = group_swap($households);
		
		if (!$matches) {
			echo "Invalid group swap - there aren't enough people in all of the groups for this to work.";
			die;
		}
	}

	$emails = array();

	$subject = "Name Exchange";

	foreach ($matches as $matchup) {
		$giver = $flat_list[$matchup[0]];
		$givee = $flat_list[$matchup[1]];

		$body = "<p>This email is for " . $giver["name"] . ".</p>";
		$body .= "<p>You have drawn ".$givee["name"]." in the name exchange.</p>";
		$body .= "<p>This email was sent automatically, and nobody else knows whose name you have.</p>";
		
		email($giver["email"], $subject, $body);
	}
	
	include "templates/complete.php";
}

function email($to, $subject, $body, $headers = array()) {
	require_once "pear/Mail.php";
	
	$params = array();
	
	if (MAIL_HOST) {
		$params["host"] = MAIL_HOST;
	}
	
	if (MAIL_USER) {
		$params["username"] = MAIL_USER;
		
		if (MAIL_PASSWORD) {
			$params["password"] = MAIL_PASSWORD;
		}
		
		$params["auth"] = true;
	}
	
	if (!isset($headers["From"])) {
		$headers["From"] = FROM_EMAIL;
	}
	
	if (!isset($headers["Reply-To"])) {
		$headers["Reply-To"] = $headers["From"];
	}
	
	$headers["To"] = $to;
	$headers["MIME-Version"] = "1.0";
	$headers["Content-Type"] = "text/html; charset=utf8";
	$headers["Subject"] = $subject;
	
	$email_object = &Mail::factory(MAIL_DRIVER, $params);
	$r = $email_object->send($to, $headers, $body);
	
	return $r;
}

function has_duplicates($groups) {
	$flat_list = array();
	
	foreach ($groups as $group) {
		foreach ($group as $member) {
			$member_key = $member["name"] . $member["email"];
			
			if (isset($flat_list[$member_key])) {
				return $member;
			}
			
			$flat_list[$member_key] = true;
		}
	}
	
	return false;
}

function is_valid_group_swap($groups) {
	$total_count = 0;
	
	foreach ($groups as $members) {
		$total_count += count($members);
	}
	
	foreach ($groups as $members) {
		// If there aren't as many people in the rest of the swap groups as there are in this group,
		// the swap won't work.
		
		if ($total_count - count($members) < count($members)) {
			return false;
		}
	}
	
	return true;
}

function shuffle_assoc($list) {
	if (!is_array($list)) return $list;

	$keys = array_keys($list);
	
	shuffle($keys);
	
	$random = array();
	
	foreach ($keys as $key) {
		$random[$key] = $list[$key];
	}

	return $random;
}

?>