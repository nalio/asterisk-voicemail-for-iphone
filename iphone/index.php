<?php
/*
	Asterisk Voicemail for iPhone
	Copyright 2008 Christopher P Carey (http://chriscarey.com)
	This program is distributed under the terms of the GNU General Public License
	
	This file is part of Asterisk Voicemail for iPhone.

    Asterisk Voicemail for iPhone is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Asterisk Voicemail for iPhone is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Asterisk Voicemail for iPhone.
	If not, see <http://www.gnu.org/licenses/>.
*/

	require_once("i_db.php");
	require_once("i_settings.php");
	require_once("i_functions.php");
	require_once($g_smarty_root.'Smarty.class.php');
	
	// Local Variables
	$debug = false;
	$action = "";   
	$mailbox = "";
	$password = "";
	$login_message = "";
	$login_success = false;
	$default_prefix = "801";

	$p_mailbox = "";
	$p_password = "";
	if (isset($_POST['mailbox'])) $p_mailbox = $_POST['mailbox'];
	if (isset($_POST['password'])) $p_password = $_POST['password'];
	
	// Add default_prefix if they provide 7 digits
	if (strlen($p_mailbox) == 7) $p_mailbox = $default_prefix.$p_mailbox;
	
	$smarty = new Smarty();
	$smarty->template_dir = $g_smarty_root.'templates';
	$smarty->compile_dir = $g_smarty_root.'templates_c';
	$smarty->cache_dir = $g_smarty_root.'cache';
	$smarty->config_dir = $g_smarty_root.'configs';
	
	// Check for Login
	$smarty->assign('mailbox', $p_mailbox);
	
	if (strlen($p_mailbox) > 0) {
		
		if ($g_use_database) {
		
			// Authenticate with Database
			$my_db = new DB();
			$my_db->construct($g_db_host, $g_db_name, $g_db_user, $g_db_pass);
			$my_db->connect();
			$my_db->select();
			
			$sql = "SELECT uniqueid,password,fullname,email FROM voicemail WHERE mailbox='$p_mailbox';";
			if ($debug) echo("SQL: $sql<br />\n");
			
			$result = $my_db->query($sql);
			if ($result) {
				if (mysql_num_rows($result) > 0) {
					$row = mysql_fetch_array($result);
					$r_password = $row['password'];
					$r_fullname = $row['fullname'];
					$r_email = $row['email'];
					
					if ($p_password == $r_password) {
						$login_success = true;
					} else {
						$login_success = false;
						$smarty->assign('mailbox_error', 'Password Incorrect');
					}
				} else {
					$login_success = false;
					$smarty->assign('mailbox_error', 'Mailbox Incorrect');
				}
			} else {
				$login_success = false;
				$smarty->assign('mailbox_error', 'Mailbox Incorrect');
			}
			
			// Disconnect
			$my_db->disconnect();
			
		} else {
		
			// Authenticate with voicemail.conf
			if (doVoicemailConfAuthentication($p_mailbox, $p_password)) {
				$login_success = true;
			} else {
				$login_success = false;
				$smarty->assign('mailbox_error', 'Login Incorrect');
			}			
		}
	}
	
	session_start();
	
	// If we have a cookie, do our thing
	if (isset($_COOKIE['mailbox'])) {
				
		// Save Session from Cookie
		$_SESSION['mailbox'] = $_COOKIE['mailbox'];
		$_SESSION['fullname'] = $_COOKIE['fullname'];
				
		// Main Menu
		header("Location: main.php");
	}
	
	// If we have successful login, do our thing
	if ($login_success == true) {
	
		// Save Cookie
		setcookie("mailbox", $p_mailbox, time()+3600*24*14);
		setcookie("fullname", $r_fullname, time()+3600*24*14);
		
		// Save Session
		$_SESSION['mailbox'] = $p_mailbox;
		$_SESSION['fullname'] = $r_fullname;
				
		// Main Menu
		header("Location: main.php");
	}
	
	// If not logging in, show the template
	$smarty->display($g_smarty_template_folder.'login.tpl');
	
?>
