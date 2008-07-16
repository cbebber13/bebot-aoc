<?php
/*
* Main.php - Main loop and parser
*
* BeBot - An Anarchy Online Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stensås, ShadowRealm Creations and the BeBot development team.
*
* Developed by:
* - Alreadythere (RK2)
* - Blondengy (RK1)
* - Blueeagl3 (RK1)
* - Glarawyn (RK1)
* - Khalem (RK1)
* - Naturalistic (RK1)
* - Temar (RK1)
*
* See Credits file for all aknowledgements.
*
*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; version 2 of the License only.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
*  USA
*
* File last changed at $LastChangedDate: 2008-05-29 14:06:27 +0100 (Thu, 29 May 2008) $
* Revision: $Id: Main.php 1641 2008-05-29 13:06:27Z alreadythere $
*/

$bot_version = "0.5.3.Hyborian";
$php_version = phpversion();

/*
Detect if we are being run from a shell or if someone is stupid enough to try and run from a web browser.
*/
if ((!empty($_SERVER[HTTP_HOST])) || (!empty($_SERVER[HTTP_USER_AGENT])))
{
	die("BeBot does not support being run from a web server and it is inherently dangerous to do so!\nFor your own good and for the safety of your account information, please do not attempt to run BeBot from a web server!");
}

echo "
===================================================\n
    _/_/_/              _/_/_/                _/   \n
   _/    _/    _/_/    _/    _/    _/_/    _/_/_/_/\n
  _/_/_/    _/_/_/_/  _/_/_/    _/    _/    _/     \n
 _/    _/  _/        _/    _/  _/    _/    _/      \n
_/_/_/      _/_/_/  _/_/_/      _/_/        _/_/   \n
          An Age of Conan Chat Automaton           \n
             v.$bot_version - PHP $php_version     \n
===================================================\n
";

usleep(1500000);

// The minimum required PHP version to run.
if((float)phpversion() < 5.2)
{
	die("BeBot requires PHP version 5.2.0 or later to work.\n");
}

/*
OS detection, borrowed from Angelsbot.
*/
$os = getenv("OSTYPE");
if (empty($os))
{
	$os = getenv("OS");
}

if (preg_match("/^windows/i", $os))
{
	$os_windows = true;
}

/*
Load extentions we need
*/

if (!extension_loaded("sockets"))
{
	if ($os_windows)
	{
		if (!dl("php_sockets.dll"))
		{
			die("Loading php_sockets.dll failed. Sockets extention required to run this bot");
		}
	}
	else
	{
		die("Sockets extention required to run this bot");
	}

}

if (!extension_loaded("mysql"))
{
	if ($os_windows)
	{
		if (!dl("php_mysql.dll"))
		{
			die("Loading php_mysql.dll failed. MySQL extention required to run this bot");
		}
	}
	else
	{
		die("MySQL support required to run this bot");
	}

}


/*
If an argument is given, use that to create the config file name.
*/
if ($argc > 1)
{
	$conffile = ucfirst(strtolower($argv[1])) . ".Bot.conf";
}
else
{
	$conffile = "Bot.conf";
}

/*
Load up the required files.
Bot.conf: The bot configuration.
MySQL.conf: The MySQL configuration.
MySQL.php: Used to communicate with the MySQL database
AOChat.php: Interface to communicate with AO chat servers
Bot.php: The actual bot itself.
*/
require_once "./conf/" . $conffile;
require_once "./Sources/MySQL.php";
require_once "./Sources/AOChat.php";
require_once "./Sources/Bot.php";

/*
Prepare the global variables we will need.
*/
global $aoc;
global $db;
global $bot;

/*
Make sure the log directory exists
*/
$logpath = $log_path . "/" . strtolower($bot_name) . "@RK" . $dimension;
if (!file_exists($logpath))
{
	mkdir($logpath);
}

$db = new MySQL(ucfirst(strtolower($bot_name)));
echo "Creating MySQL class!\n";
$aoc = new AOChat("callback");
echo "Creating AOChat class!\n";
$bot = new Bot($ao_username, $ao_password, $bot_name, $dimension, $bot_version, $bot_version_name, $other_bots, $aoc, $irc, $db, $command_prefix, $cron_delay, $tell_delay, $max_blobsize, $reconnect_time, $guildbot, $guild_id, $guild, $log, $logpath, $log_timestamp, $use_proxy_server, $proxy_server_address, $proxy_server_port);
echo "Creating main Bot class!\n";

/*
Make sure we no longer keep username and password in memory.
*/
unset ($ao_password);
unset ($ao_username);


// Load all main functions of the bot:
$bot -> log("MAIN", "DIR", "Loading main functions of the bot");
$folder = dir("./main/");
$mainclass = array();
while ($mod = $folder->read())
{
	if (!is_dir($mod) && preg_match("/^[01-9][01-9]_[A-Za-z_01-9\.]+\.php$/i", $mod))
	{
		$mainclass[] = $mod;
	}
}

if (is_array($mainclass))
{
	sort($mainclass);

	foreach ($mainclass AS $name)
	{
		require_once "main/" . $name;
		$bot -> log("MAIN", "LOAD", $name);
	}
}
unset($mainclass);
echo "\n";

// create new ConfigMagik-Object
$path = "conf/" . ucfirst(strtolower($bot -> botname)) . ".Modules.ini";
$bot -> ini = new ConfigMagik(&$bot, $path, true, true);

// change path or name of config-file
$bot -> core("ini") -> PATH = $path;

// Load up core-modules
$bot -> log("CORE", "DIR", "Loading core-modules");
$folder = dir("./core/");
while ($mod = $folder->read())
{
	$value = $bot -> core("ini") -> get($mod,"Core");

	if (!is_dir($mod) && !preg_match("/^_/", $mod) && preg_match("/\.php$/i", $mod)
	&& $value != "FALSE")
	{
		require_once "core/" . $mod;
		$bot -> log("CORE", "LOAD", $mod);
	}
}
echo "\n";

// Load up all custom core-modules if the directory exists
if (is_dir("./custom/core"))
{
	$bot -> log("CORE-CUSTOM", "DIR", "Loading additional core-modules in directory custom/core/");
	$folder = dir("./custom/core/");
	while ($mod = $folder->read())
	{
		$value = $bot -> core("ini") -> get($mod,"Custom_Core");

		if (!is_dir($mod) && !preg_match("/^_/", $mod) && preg_match("/\.php$/i", $mod)
		&& $value != "FALSE")
		{
			require_once "custom/core/" . $mod;
			$bot -> log("CORE-CUSTOM", "LOAD", $mod);
		}
	}
	echo "\n";
}

// Load up the core modules in the $core_directories config entry
$core_dirs = explode(",", $core_directories);
foreach ($core_dirs as $core_dir)
{
	$core_dir = trim($core_dir);
	// Only load anything if it really is a directory
	if (is_dir($core_dir))
	{
		$bot -> log("CORE-ADD", "DIR", "Loading additional core-modules in directory " . $core_dir);
		$sec_name = str_replace("/", "_", $core_dir);

		$folder = dir($core_dir . "/");
		while ($mod = $folder->read())
		{
			$value = $bot -> core("ini") -> get($mod, $sec_name);

			if (!is_dir($mod) && !preg_match("/^_/", $mod) && preg_match("/\.php$/i", $mod)
			&& $value != "FALSE")
			{
				require_once $core_dir . "/" . $mod;
				$bot -> log("CORE-ADD", "LOAD", $mod);
			}
		}
		echo "\n";
	}
}

// Load up all modules
$bot -> log("MOD", "DIR", "Loading modules");
$folder = dir("./modules/");
while ($mod = $folder->read())
{
	$value = $bot -> core("ini") -> get($mod,"Modules");

	if (!is_dir($mod) && !preg_match("/^_/", $mod) && preg_match("/\.php$/i", $mod)
	&& $value != "FALSE")
	{
		require_once "modules/" . $mod;
		$bot -> log("MOD", "LOAD", $mod);
	}
}
echo "\n";

// Load up all custom modules if the directoy exists
if (is_dir("./custom/modules"))
{
	$bot -> log("MOD-CUSTOM", "DIR", "Loading additional modules in directory custom/modules/");
	$folder = dir("./custom/modules/");
	while ($mod = $folder->read())
	{
		$value = $bot -> core("ini") -> get($mod,"Custom_Modules");

		if (!is_dir($mod) && !preg_match("/^_/", $mod) && preg_match("/\.php$/i", $mod)
		&& $value != "FALSE")
		{
			require_once "custom/modules/" . $mod;
			$bot -> log("MOD-CUSTOM", "LOAD", $mod);
		}
	}
	echo "\n";
}

// Load up the modules in the $module_directories config entry
$mod_dirs = explode(",", $module_directories);
foreach ($mod_dirs as $mod_dir)
{
	$mod_dir = trim($mod_dir);
	// Only load anything if it really is a directory
	if (is_dir($mod_dir))
	{
		$bot -> log("MOD-ADD", "DIR", "Loading additional modules in directory " . $mod_dir);
		$sec_name = str_replace("/", "_", $mod_dir);

		$folder = dir($mod_dir . "/");
		while ($mod = $folder->read())
		{
			$value = $bot -> core("ini") -> get($mod, $sec_name);

			if (!is_dir($mod) && !preg_match("/^_/", $mod) && preg_match("/\.php$/i", $mod)
			&& $value != "FALSE")
			{
				require_once $mod_dir . "/" . $mod;
				$bot -> log("MOD-ADD", "LOAD", $mod);
			}
		}
	}
	echo "\n";
}



// Start up the bot.
$bot -> connect();

/*
Listen for incoming events...
*/
while(true)
{
	if ($aoc -> wait_for_packet() == "disconnected")
	$bot -> reconnect();

	$bot -> cron();
}


/*
Parse incomeing events...
*/
function callback($type, $args)
{
	global $bot;

	$bot -> cron();

	switch ($type)
	{
		case 5:
			$bot -> log("LOGIN", "RESULT", "OK");
			break;
		case 6:
			$bot -> log("LOGIN", "RESULT", "Error");
			break;
		case 20:
			// Silently ignore for now (AOCP_CLIENT_NAME)
			break;
		case AOCP_MSG_PRIVATE:
			// Event is a tell
			$bot -> inc_tell($args);
			break;
		case AOCP_BUDDY_ADD:
			// Event is a buddy logging on/off
			$bot -> inc_buddy($args);
			break;
		case AOCP_PRIVGRP_CLIJOIN:
			// Event is someone joining the privgroup
			$bot -> inc_pgjoin($args);
			break;
		case AOCP_PRIVGRP_CLIPART:
			// Event is someone leaveing the privgroup
			$bot -> inc_pgleave($args);
			break;
		case AOCP_PRIVGRP_MESSAGE:
			// Event is a privgroup message
			$bot -> inc_pgmsg($args);
			break;
		case AOCP_GROUP_MESSAGE:
			// Event is a group message (guildchat, towers etc)
			$bot -> inc_gmsg($args);
			break;
		case AOCP_PRIVGRP_INVITE:
			// Event is a privgroup invite
			$bot -> inc_pginvite($args);
			break;
		case AOCP_GROUP_ANNOUNCE:
			$bot -> inc_gannounce($args);
			break;
		default:
			// $bot -> log ("MAIN", "TYPE", "Uhandeled packet type $type");

	}
}
?>
