<?php
/*
* Bot.php - The actual core functions for the bot
*
* BeBot - An Anarchy Online Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stens�s, ShadowRealm Creations and the BeBot development team.
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
* File last changed at $LastChangedDate: 2008-06-17 22:33:51 +0100 (Tue, 17 Jun 2008) $
* Revision: $Id: Bot.php 1664 2008-06-17 21:33:51Z temar $
*/

/*
This is where the basic magic happens
Some functions you might need:

connect():
Connects the bot with AO Chatserver

disconnect():
Disconnects the bot from AO Chatserver

reconnect():
Disconnects and then connects the bot from AO Chatserver

log($first, $second, $msg):
Writes to console/log file.

make_blob($title, $content):
Makes a text blob.
- Returns blob.

make_chatcommand($link, $title):
Creates a clickable chatcommand link
- Returns string

make_item($lowid, $highid, $ql, $name)
Makes an item reference.
- Returns reference blob.

send_tell($to, $msg):
Sends a tell to character.

send_pgroup($msg):
Sends a msg to the privategroup.

send_gc($msg):
Sends a msg to the guildchat.

send_help($to):
Sends /tell <botname> <pre>help.

send_permission_denied($to, $command, $type)
If $type is missing or 0 error is returned to the calling function, else it
sends a permission denied error to the apropriate location based on $type for $command.

get_site($url, $strip_headers, $server_timeout, $read_timeout):
Retrives the content of a site
- Returns array:
$array["error"] - true if error was encountered, false if not.
$array["errordesc"] - Error description if error was encountered.
$array["content"] - String containing content of $url

int_to_string($int)
Used to convert an overflowed (unsigned) integer to a string with the correct positive unsigned integer value
If the passed integer is not negative, the integer is merely passed back in string form with no modifications.
- Returns a string.

string_to_int($string)
Used to convert an unsigned interger in string form to an overflowed (negative) integere
If the passed string is not an integer large enough to overflow, the string is merely passed back in integer form with no modifications.
- Returns an integer.
*/

define("SAME", 1);
define("TELL", 2);
define("GC", 4);
define("PG", 8);
define("RELAY", 16);
define("IRC", 32);
define("ALL", 255);

class Bot
{
	var $lasttell;
	var $banmsgout;
	var $dimension;
	var $botversion;
	var $botversionname;
	var $other_bots;
	var $aoc;
	var $irc;
	var $db;
	var $commpre;
	var $crondelay;
	var $telldelay;
	var $maxsize;
	var $reconnecttime;
	var $guildbot;
	var $guildid;
	var $guild;
	var $log;
	var $log_path;
	var $log_timestamp;
	var $use_proxy_server;
	var $proxy_server_address;
	var $starttime;
	var $commands;

	private $module_links;
	private $cron_times;
	private $cron_job_timer;
	private $cron_job_active;
	private $cron_actived;
	private $cron;
	private $startup_time;
	public $buddy_status = array();
	public $glob;
	public $botname;

	/*
	Constructor:
	Prepares bot.
	*/
	function __construct($uname, $pwd, $botname, $dim, $botversion, $botversionname, $other_bots, &$aoc, &$irc, &$db, $commprefix, $crondelay, $telldelay, $maxsize, $recontime, $guildbot, $guildid, $guild, $log, $log_path, $log_timestamp, $use_proxy_server, $proxy_server_address, $proxy_server_port)
	{
		$this -> username = $uname;
		$this -> password = $pwd;
		$this -> botname = $botname;
		$this -> dimension = $dim;
		$this -> botversion = $botversion;
		$this -> botversionname = $botversionname;
		$this -> other_bots = $other_bots;
		$this -> aoc = &$aoc;
		$this -> irc = &$irc;
		$this -> db = &$db;
		$this -> commands = array();
		$this -> commpre = $commprefix;
		$this -> cron = array();
		$this -> crondelay = $crondelay;
		$this -> telldelay = $telldelay;
		$this -> maxsize = $maxsize;
		$this -> reconnecttime = $recontime;
		$this -> guildbot = $guildbot;
		$this -> guildid = $guildid;
		$this -> guild = $guild;
		$this -> log = $log;
		$this -> log_path = $log_path;
		$this -> log_timestamp = $log_timestamp;
		$this -> banmsgout = array();
		$this -> use_proxy_server = $use_proxy_server;
		$this -> proxy_server_address = explode(",", $proxy_server_address);
		$this -> starttime = time();

		$this -> module_links = array();

		$this -> cron_times = array();
		$this -> cron_job_activate = array();
		$this -> cron_job_timer = array();
		$this -> cron_activated = false;

		$this -> glob = array();
	}



	/*
	Connects the bot to AO's chat server
	*/
	function connect()
	{
		// Make sure all cronjobs are locked, we don't want to run any cronjob before we are logged in!
		$this -> cron_activated = false;

		// Get dimension server
		switch($this -> dimension)
		{
			// EU Servers
			case "Ahriman":
				$server = "proddm06.ams.ageofconan.com";
				$port = 7021;
				break;
			case "Bori":
				$server = "proddm02.ams.ageofconan.com";
				$port = 7004;
				break;
			case "Crom":
				$server = "proddm01.ams.ageofconan.com";
				$port = 7001;
				break;
			case "Dagon":
				$server = "proddm01.ams.ageofconan.com";
				$port = 7002;
				break;
			case "Ymir":
				$server = "proddm02.ams.ageofconan.com";
				$port = 7003;
				break;
			case "Hyrkania":
				$server = "proddm06.ams.ageofconan.com";
				$port = 7022;
				break;
			case "Battlescar":
				$server = "proddm06.ams.ageofconan.com";
				$port = 7023;
				break;
			case "Fury":
				$server = "proddm02.ams.ageofconan.com";
				$port = 7005;
				break;
			case "Soulstorm":
				$server = "proddm06.ams.ageofconan.com";
				$port = 7024;
				break;
			case "Wildsoul":
				$server = "proddm02.ams.ageofconan.com";
				$port = 7006;
				break;
			case "Aquilonia":
				$server = "proddm03.ams.ageofconan.com";
				$port = 7008;
				break;
			case "Twilight":
				$server = "proddm03.ams.ageofconan.com";
				$port = 7007;
				break;
			case "Corinthia":
				$server = "proddm07.ams.ageofconan.com";
				$port = 7034;
				break;

			// Spanish Servers
			case "Zingara":
				$server = "proddm05.ams.ageofconan.com";
				$port = 7016;
				break;
			case "Indomitus":
				$server = "proddm07.ams.ageofconan.com";
				$port = 7036;
				break;

			// French Servers
			case "Ishtar":
				$server = "proddm04.ams.ageofconan.com";
				$port = 7013;
				break;
			case "Ferox":
				$server = "proddm05.ams.ageofconan.com";
				$port = 7014;
				break;
			case "Stygia":
				$server = "proddm05.ams.ageofconan.com";
				$port = 7015;
				break;
			case "Strix":
				$server = "proddm04.ams.ageofconan.com";
				$port = 7014;
				break;

			// German Servers
			case "Asura":
				$server = "proddm03.ams.ageofconan.com";
				$port = 7010;
				break;
			case "Ibis":
				$server = "proddm05.ams.ageofconan.com";
				$port = 7018;
				break;
			case "Mitra":
				$server = "proddm03.ams.ageofconan.com";
				$port = 7009;
				break;
			case "Aries":
				$server = "proddm04.ams.ageofconan.com";
				$port = 7011;
				break;
			case "Titus":
				$server = "proddm05.ams.ageofconan.com";
				$port = 7019;
				break;
			case "Asgard":
				$server = "proddm04.ams.ageofconan.com";
				$port = 7012;
				break;

			// US servers (missing: Bardisattva, Damballah, Hanuman, Scorge, Stormrage, Hyperborea)
			case "Ajujo":
				$server = "208.82.194.10";
				$port = 7017;
				break;
			case "Dagoth":
				$server = "208.82.194.5";
				$port = 7002;
				break;
			case "Derketo":
				$server = "208.82.194.6";
				$port = 7005;
				break;
			case "Gwahlur":
				$server = "208.82.194.7";
				$port = 7008;
				break;
			case "Omm":
				$server = "208.82.194.6";
				$port = 7004;
				break;
			case "Set":
				$server = "208.82.194.5";
				$port = 7001;
				break;
			case "Thog":
				$server = "208.82.194.7";
				$port = 7006;
				break;
			case "Wiccana":
				$server = "208.82.194.7";
				$port = 7007;
				break;
			case "Zug":
				$server = "208.82.194.6";
				$port = 7003;
				break;
			case "Bloodspire":
				$server = "208.82.194.8";
				$port = 7012;
				break;
			case "Bluesteel":
				$server = "208.82.194.12";
				$port = 7022;
				break;
			case "Deathwhisper":
				$server = "208.82.194.8";
				$port = 7011;
				break;
			case "Doomsayer":
				$server = "208.82.194.9";
				$port = 7013;
				break;
			case "Shadowblade":
				$server = "208.82.194.12";
				$port = 7023;
				break;
			case "Tyranny":
				$server = "208.82.194.7";
				$port = 7009;
				break;
			case "Bane":
				$server = "208.82.194.8";
				$port = 7010;
				break;
			case "Cimmeria":
				$server = "208.82.194.10";
				$port = 7015;
				break;

			// Test Live server
			case "Testlive":
				$server = "213.244.186.68";
				$port = 7001;
				break;

			default:
				die("Unknown dimension " . $this -> dimension);
		}

		// Open connection
		$this -> log("LOGIN", "STATUS", "Connecting");
		$this -> aoc -> connect($server, $port);

		// Authenticate
		$this -> log("LOGIN", "STATUS", "Authenticating");
		$this -> aoc -> authenticate($this -> username, $this -> password);

		// Login the bot character
		$this -> log("LOGIN", "STATUS", "Logging in");
		$this -> aoc -> login(ucfirst(strtolower($this -> botname)));

		/*
		We're logged in. Make sure we no longer keep username and password in memory.
		*/
		unset($this -> username);
		unset($this -> password);

		// Create the CORE settings, settings module is initialized here
		$this -> core("settings") -> create("Core", "RequireCommandPrefixInTells", FALSE, "Is the command prefix (in this bot <pre>) required for commands in tells?");
		$this -> core("settings") -> create("Core", "LogGCOutput", TRUE, "Should the bots own output be logged when sending messages to organization chat?");
		$this -> core("settings") -> create("Core", "LogPGOutput", TRUE, "Should the bots own output be logged when sending messages to private groups?");
		$this -> core("settings") -> create("Core", "SimilarCheck", FALSE, "Should the bot try to match a similar written command if an exact match is not found? This is not recommended if you dont use a prefix!");
		$this -> core("settings") -> create("Core", "SimilarMinimum", 75, "What is the minimum percentage of similarity that has to be reached to consider two commands similar?", "75;80;85;90;95");
		$this -> core("settings") -> create("Core", "CommandErrorTell", FALSE, "Should the bot output an Access Level Error if a user tries to use a command in tells he doesn't have access to?");
		$this -> core("settings") -> create("Core", "CommandErrorPgMsg", FALSE, "Should the bot output an Access Level Error if a user tries to use a command in the private group he doesn't have access to?");
		$this -> core("settings") -> create("Core", "CommandErrorGc", FALSE, "Should the bot output an Access Level Error if a user tries to use a command in guild chat he doesn't have access to?");
		$this -> core("settings") -> create("Core", "CommandErrorExtPgMsg", FALSE, "Should the bot output an Access Level Error if a user tries to use a command in an external private group he doesn't have access to?");
		$this -> core("settings") -> create("Core", "CommandDisabledError", FALSE, "Should the bot output a Disabled Error if they try to use a command that is Disabled?");
		$this -> core("settings") -> create("Core", "DisableGC", FALSE, "Should the Bot output into and reactions to commands in the guildchat be disabled?");
		$this -> core("settings") -> create("Core", "DisablePGMSG", FALSE, "Should the Bot output into and reactions to commands in it's own private group be disabled?");
		$this -> core("settings") -> create("Core", "ColorizeTells", TRUE, "Should tells going out be colorized on default? Notice: Modules can set a nocolor flag before sending out tells.");
		$this -> core("settings") -> create("Core", "ColorizeGC", TRUE, "Should output to guild chat be colorized using the current theme?");
		$this -> core("settings") -> create("Core", "ColorizePGMSG", TRUE, "Should output to private group be colorized using the current theme?");

		// Tell modules that the bot is connected
		if (!empty($this -> commands["connect"]))
		{
			$keys = array_keys($this -> commands["connect"]);
			foreach ($keys as $key)
			$this -> commands["connect"][$key] -> connect();
		}

		$this -> startup_time = time() + $this -> crondelay;
		// Set the time of the first cronjobs
		foreach ($this -> cron_times AS $timestr => $value)
		{
			$this -> cron_job_timer[$timestr] = $this -> startup_time;
		}

		// and unlock all cronjobs again:
		$this -> cron_activated = true;
	}



	/*
	Reconnect the bot.
	*/
	function reconnect()
	{
		$this -> cron_activated = false;
		$this -> disconnect();
		$this -> log("CONN", "ERROR", "Bot has disconnected. Reconnecting in " . $this -> reconnecttime . " seconds.");
		sleep($this -> reconnecttime);
		die("The bot is restarting.\n");
	}



	/*
	Dissconnect the bot
	*/
	function disconnect()
	{
		$this -> aoc -> disconnect();

		if (!empty($this -> commands["disconnect"]))
		{
			$keys = array_keys($this -> commands["disconnect"]);
			foreach ($keys as $key)
			$this -> commands["disconnect"][$key] -> disconnect();
		}
	}



	function replace_string_tags($msg)
	{
		$msg = str_replace("<botname>", $this -> botname, $msg);
		$msg = str_replace("<guildname>", $this -> guildname, $msg);
		$msg = str_replace("<pre>", str_replace("\\", "", $this -> commpre), $msg);

		return $msg;
	}
	/*
	sends a tell asking user to use "help"
	*/
	function send_help($to, $command=FALSE)
	{
		if ($command == FALSE)
		{
			$this -> send_tell($to, "/tell <botname> <pre>help");
		}
		else
		{
			$this -> send_tell($to, $this -> core("help") -> show_help($to, $command));
		}
	}


	/*
	sends a message over IRC if it's enabled and connected
	*/
	function send_irc($prefix, $name, $msg)
	{
		if (isset($this -> irc) && $this -> core("settings") -> exists("irc", "connected"))
		{
			if ($this -> core("settings") -> get("Irc", "Connected"))
			{
				$this -> core("irc") -> send_irc($prefix, $name, $msg);
			}
		}
	}

	/*
	Notifies someone that they are banned, but only once.
	*/
	function send_ban($to, $msg=FALSE)
	{
		if (!isset($this -> banmsgout[$to]))
		{
			$this -> banmsgout[$to] = time();
			if ($msg === FALSE)
			{
				$this -> send_tell($to, "You are banned from <botname>.");
			}
			else
			{
				$this -> send_tell($to, $msg);
			}
		}
		else
		{
			return FALSE;
		}
	}

	/*
	Sends a permission denied error to user for the given command.
	*/
	function send_permission_denied($to, $command, $type=0)
	{
		$string = "You do not have permission to access $command";
		if ($type = 0)
		{
			return $string;
		}
		else
		{
			$this -> send_output($to, $string, $type);
		}
	}



	/*
	send a tell. Set $low to 1 on tells that are likely to cause spam.
	*/
	function send_tell($to, $msg, $low=0, $color=true, $sizecheck=TRUE)
	{
		// parse all color tags:
		$msg = $this -> core("colors") -> parse($msg);

		$send = true;
		if($sizecheck)
		{
			if (preg_match("/<a href=\"(.+)\">/isU", $msg, $info))
			{
				if (strlen($info[1]) > $this -> maxsize)
				{
					$this -> cut_size($msg, "tell", $to, $low);
					$send = false;
				}
			}
		}

		if ($send)
		{
			$msg = $this -> replace_string_tags($msg);

			if ($color && $this -> core("settings") -> get("Core", "ColorizeTells"))
			{
				$msg = $this -> core("colors") -> colorize("normal", $msg);
			}

			if ($this -> core("chat_queue") -> check_queue())
			{
				$this -> log("TELL", "OUT", "-> " . $this -> core("chat") -> get_uname($to) . ": " . $msg);
				$msg = utf8_encode($msg);
				$this -> aoc -> send_tell($to, $msg);
			}
			else
			$this -> core("chat_queue") -> into_queue($to, $msg, "tell", $low);
		}
	}



	/*
	send a message to privategroup
	*/
	function send_pgroup($msg, $group = NULL, $checksize = TRUE)
	{
		if ($group == NULL)
			$group = $this -> botname;

		if ($group == $this -> botname && $this -> core("settings") -> get("Core", "DisablePGMSG"))
		{
			return FALSE;
		}

		// parse all color tags:
		$msg = $this -> core("colors") -> parse($msg);

		$gid = $this -> core("chat") -> get_uid($group);

		$send = true;
		if($checksize)
		{
			if (preg_match("/<a href=\"(.+)\">/isU", $msg, $info))
				if (strlen($info[1]) > $this -> maxsize)
				{
					$this -> cut_size($msg, "pgroup", $group);
					$send = false;
				}
		}

		if ($send)
		{
			$msg = $this -> replace_string_tags($msg);

			$msg = utf8_encode($msg);

			if (strtolower($group) == strtolower($this -> botname))
			{
				if ($this -> core("settings") -> get("Core", "ColorizePGMSG"))
				{
					$msg = $this -> core("colors") -> colorize("normal", $msg);
				}
				$this -> aoc -> send_privgroup($gid,$msg);
			}
			else
				$this -> aoc -> send_privgroup($gid,$msg);
		}
	}


	/*
	* Send a message to guild channel
	*/

	function send_gc($msg, $low=0, $checksize = TRUE)
	{
		if($this -> core("settings") -> get("Core", "DisableGC"))
		{
			Return FALSE;
		}

		// parse all color tags:
		$msg = $this -> core("colors") -> parse($msg);

		$send = true;
		if($checksize)
		{
			if (preg_match("/<a href=\"(.+)\">/isU", $msg, $info))
				if (strlen($info[1]) > $this -> maxsize)
				{
					$this -> cut_size($msg, "gc", "", $low);
					$send = false;
				}
		}

		if ($send)
		{
			$msg = $this -> replace_string_tags($msg);

			if ($this -> core("settings") -> get("Core", "ColorizeGC"))
			{
				$msg = $this -> core("colors") -> colorize("normal", $msg);
			}

			if ($this -> core("chat_queue") -> check_queue())
			{
				$msg = utf8_encode($msg);
				$this -> aoc -> send_group($this -> guildname, $msg);
			}
			else
				$this -> core("chat_queue") -> into_queue($this -> guildname, $msg, "gc", $low);
		}
	}

	function send_output($source, $msg, $type)
	{
		// Parse color tags now to be sure they don't get changed by output filters
		$msg = $this -> core("colors") -> parse($msg);

		// Output filter
		if ($this -> core("settings") -> exists('Filter', 'Enabled'))
		{
			if ($this -> core("settings") -> get('Filter', 'Enabled'))
			{
				$msg = $this -> core("stringfilter") -> output_filter($msg);
			}
		}

		if (!is_numeric($type))
		{
			$type = strtolower($type);
		}
		switch($type)
		{
			case '0':
			case '1':
			case 'tell':
				$this -> send_tell($source, $msg);
				break;
			case '2':
			case 'pgroup':
			case 'pgmsg':
				$this -> send_pgroup($msg);
				break;
			case '3':
			case 'gc':
				$this -> send_gc($msg);
				break;
			case '4':
			case 'both':
				$this -> send_gc($msg);
				$this -> send_pgroup($msg);
				break;
			default:
				$this -> log("OUTPUT", "ERROR", "Broken plugin, type: $type is unknown to me; source: $source, message: $msg");
		}
	}



	/*
	 * This function tries to find a similar written command based compared to $cmd, based on
	 * all available commands in $channel. The percentage of match and the closest matching command
	 * are returned in an array.
	 */
	function find_similar_command($channel, $cmd)
	{
		$use = array(0);
		$percentage = 0;

		if(isset($this -> commands["tell"][$cmd]) ||
			isset($this -> commands["gc"][$cmd]) ||
			isset($this -> commands["pgmsg"][$cmd]) ||
			isset($this -> commands["extpgmsg"][$cmd]))
		{
			return $use;
		}

		$perc = $this -> core("settings") -> get("Core", "SimilarMinimum");
		foreach($this -> commands[$channel] as $compare_cmd => $value)
		{
			similar_text($cmd, $compare_cmd, $percentage);
			if ($percentage >= $perc
			&& $percentage > $use[0])
			{
				$use = array($percentage, $compare_cmd);
			}
		}
		return $use;
	}



	/*
	 * This function checks if $user got access to $command (with possible subcommands based on $msg)
	 * in $channel. If the check is positive the command is executed and TRUE returned, otherwise FALSE.
	 * $pgname is used to identify which external private group issued the command if $channel = extpgmsg.
	 */
	function check_access_and_execute($user, $command, $msg, $channel, $pgname)
	{
		if ($this -> core("access_control") -> check_rights($user, $command, $msg, $channel))
		{
			if ($channel == "extpgmsg")
			{
				$this -> commands[$channel][$command] -> $channel($pgname, $user, $msg);
			}
			else
			{
				$this -> commands[$channel][$command] -> $channel($user, $msg);
			}
			return true;
		}
		return false;
	}



	/*
	 * This function check if $msg contains a command in the channel.
	 * If $msg contains a command it checks for access rights based on the $user, command and $channel.
	 * If $user may access the command $msg is handed over to the parser of the responsible module.
	 * This function returns true if the $msg has been handled, and false otherwise.
	 * $pgname is used to identify external private groups.
	 */
	function handle_command_input($user, $msg, $channel, $pgname = NULL)
	{
		$match = false;
		$this -> command_error_text = false;

		if (!empty($this -> commands[$channel]))
		{
			if ($this -> core("security") -> is_banned($user))
			{
				$this -> send_ban($user);
				return true;
			}

			$stripped_prefix = str_replace("\\", "", $this -> commpre);

			// Add missing command prefix in tells if the settings allow for it:
			if ($channel == "tell" && !$this -> core("settings") -> get("Core", "RequireCommandPrefixInTells") && $this -> commpre != ""
			&& $msg[0] != $stripped_prefix)
			{
				$msg = $stripped_prefix . $msg;
			}

			// Only if first character is the command prefix is any check for a command needed,
			// or if no command prefix is used at all:
			if ($this -> commpre == "" || $msg[0] == $stripped_prefix)
			{
				// Strip command prefix if it is set - we already checked that the input started with it:
				if ($this -> commpre != "")
				{
					$msg = substr($msg, 1);
				}

				// Check if Command is an Alias of another Command
				$msg = $this -> core("command_alias") -> replace($msg);

				$cmd = explode(" ", $msg, 3);
				$cmd[0] = strtolower($cmd[0]);

				$msg = implode(" ", $cmd);

				if (isset($this -> commands[$channel][$cmd[0]]))
				{
					$match = TRUE;

					if ($this -> check_access_and_execute($user, $cmd[0], $msg, $channel, $pgname))
					{
						return true;
					}
				}
				elseif($this -> core("settings") -> get("Core", "SimilarCheck"))
				{
					$use = $this -> find_similar_command($channel, $cmd[0]);
					if($use[0] > 0)
					{
						$cmd[0] = $use[1];
						$msg = explode(" ", $msg, 2);
						$msg[0] = $use[1];
						$msg = implode(" ", $msg);
						if(isset($this -> commands[$channel][$use[1]]))
						{
							$match = TRUE;

							if ($this -> check_access_and_execute($user, $use[1], $msg, $channel, $pgname))
							{
								return true;
							}
						}
					}
				}
				if ($this -> core("settings") -> get("Core", "CommandError" . $channel) && $match)
				{
					$minlevel = $this -> core("access_control") -> get_min_rights($cmd[0], $msg, $channel);
					if ($minlevel == OWNER + 1)
					{
						$minstr = "DISABLED";
					}
					else
					{
						$minstr = $this -> core("security") -> get_access_name($minlevel);
					}
					$req = array("Command", $msg, $minstr);
					if ($req[2] == "DISABLED")
					{
						if($this -> core("settings") -> get("Core", "CommandDisabledError"))
						{
							$this -> command_error_text = "You're not authorized to use this ".$req[0].": ##highlight##".$req[1]."##end##, it is Currently ##highlight##DISABLED##end##";
						}
					}
					else
					{
						$this -> command_error_text = "You're not authorized to use this ".$req[0].": ##highlight##".$req[1]."##end##, Your Access Level is required to be at least ##highlight##".$req[2]."##end##";
					}
				}
			}

			return false;
		}
	}

	/*
	 * This function handles input after a successless try to find a command in it.
	 * If some modules has registered a chat handover for $channel it will hand it over here.
	 * It checks $found first, if $found = true it doesn't do anything.
	 * $group is used by external private groups and to listen to specific chat channels outside the bot.
	 * Returns true if some module accessing this chat returns true, false otherwise.
	 */
	function hand_to_chat($found, $user, $msg, $channel, $group = NULL)
	{
		if ($found)
		{
			return true;
		}
		if ($channel == "gmsg")
		{
			if ($group == $this -> guildname)
			{
				$group = "org";
			}
			$registered = $this -> commands[$channel][$group];
		}
		else
		{
			$registered = $this -> commands[$channel];
		}
		if (!empty($registered))
		{
			$keys = array_keys($registered);
			foreach ($keys as $key)
			{
				if ($channel == "extprivgroup")
				{
					$found = $found | $this -> commands[$channel][$key] -> $channel($group, $user, $msg);
				}
				else if ($channel == "gmsg")
				{
					$found = $found | $this -> commands[$channel][$group][$key] -> $channel($user, $group, $msg);
				}
				else
				{
					$found = $found | $this -> commands[$channel][$key] -> $channel($user, $msg);
				}
			}
		}
		return $found;
	}

	/*
	Incoming Tell
	*/
	function inc_tell($args)
	{
		if (4 == ($this -> buddy_status[$args[0]] | 4) &&!preg_match("/Away from keyboard./i", $args[1]) && !preg_match("/.tell (.+)help/i",$args[1]) && !preg_match("/I only listen to members of this bot/i",$args[1] ))
		{
			$user = $this -> core("chat") -> get_uname($args[0]);
			$found = false;

			$args[1] = utf8_decode($args[1]);

			// Ignore bot chat, no need to handle it's own output as input again
			if (strtolower($this -> botname) == strtolower($user))
			{
				// Danger will robinson. We just sendt a tell to ourselves!!!!!!!!!
				$this -> log("CORE", "INC_TELL", "Danger will robinson. Received tell from myself: $args[1]");
				return;
			}

			$this -> log("TELL", "INC", $user . ": " . $args[1]);

			if (!isset($this -> other_bots[$user]))
			{
				$found = $this -> handle_command_input($user, $args[1], "tell");

				$found = $this -> hand_to_chat($found, $user, $args[1], "tells");

				if ($this -> command_error_text)
				{
					$this -> send_tell($args[0], $this -> command_error_text);
				}
				elseif (!$found && $this -> core("security") -> check_access($user, "GUEST"))
				{
					$this -> send_help($args[0]);
				}
				else if (!$found)
				{
					if ($this -> guild_bot)
					{
						$this -> send_tell($args[0], "I only listen to members of " . $this -> guildname . ".");
					}
					else
					{
						$this -> send_tell($args[0], "I only listen to members of this bot.");
					}
				}
				unset($this -> command_error_text);
			}
		}
	}



	/*
	Buddy logging on/off
	*/
	function inc_buddy($args)
	{
		$user = $this -> core("chat") -> get_uname($args[0]);
		$mem = $this -> core("notify") -> check($user);

		// Get the users current state
		$old_who = $this -> core("Whois") -> lookup($user);

		if(array_key_exists($user, $this -> buddy_status))
			$old_buddy_status = $this -> buddy_status[$user];
		else
			$old_buddy_status = 0;

		$who = array();
		$who["id"] = $args[0];
		$who["nickname"] = $user;
		$who["online"] = $args[1];
		$who["level"] = $args[2];
		$who["location"] = $args[3];
		$class_name = $this -> core("Whois") -> class_name[$args[4]];
		$who["class"] = $class_name;
		$lookup = $this -> db -> select("SELECT * FROM craftingclass WHERE name = '" . $user . "'", MYSQL_ASSOC);
		if (!empty($lookup))
		{
			$who["craft1"] = $lookup[0]['class1'];
			$who["craft2"] = $lookup[0]['class2'];
		}
		$this -> core("Whois") -> update($who);

		if($old_who["error"])
		{
			$old_who["level"] = 0;
			$old_who["location"] = 0;
		}

		// status change flags:
		// 1 = online
		// 2 = LFG
		// 4 = AFK
		if(0 == $who["online"])
			$buddy_status = 0;
		else if(1 == $who["online"])
			$buddy_status = 1;
		else if(2 == $who["online"])
			$buddy_status = $old_buddy_status | 2;
		else if(3 == $who["online"])
			$buddy_status = $old_buddy_status | 4;

		$this -> buddy_status[$user] = $buddy_status;

		$changed = $buddy_status ^ $old_buddy_status;

		$current_statuses = array();

		/* Player Statuses
		0 = logged off
		1 = logged on
		2 = went LFG
		3 = went AFK
		4 = stopped LFG
		5 = no longer AFK
		6 = changed location
		7 = changed level
		*/

		// Deal with overriding status changes
		if(1 == ($changed & 1))
		{
			if(1 == ($old_buddy_status & 1))
			{
				// User just went offline
				$current_statuses[] = 0;
			}
			else
			{
				// User just came online
				$current_statuses[] = 1;
			}
		}
		if(2 == ($changed & 2))
		{
			if(2 == ($old_buddy_status & 2))
			{
				// User just returned from LFG
				$current_statuses[] = 4;
			}
			else
			{
				// User just went LFG
				$current_statuses[] = 2;
			}
		}

		if(4 == ($changed & 4))
		{
			if(4 == ($old_buddy_status & 4))
			{
				// User just returned from AFK
				$current_statuses[] = 5;
			}
			else
			{
				// User just went AFK
				$current_statuses[] = 3;
			}
		}

		// Deal with events we don't have to remember
		if($old_who["level"] != $who["level"] && $old_who["level"] != 0)
		{
			// User has changed level
			$current_statuses[] = 7;
		}
		if($old_who["location"] != $who["location"] && $old_who["location"] != 0 && $who["online"] != 0 && !in_array(0, $current_statuses))
		{
			// User has changed location
			$current_statuses[] = 6;
		}

		// Make sure we only cache members and guests to prevent any issues with !is and anything else that might do buddy actions on non members.
		if ($mem)
		{
			if(in_array(1, $current_statuses))
			{
				// User just came online
				// Enter the user into the online buddy list
				$this -> glob["online"][$user] = $user;
			}
			else if(in_array(0, $current_statuses))
			{
				// User just went offline
				unset($this -> glob["online"][$user]);
			}
			$end = " (" . $this -> core("security") -> get_access_name($this -> core("security") -> get_access_level($user)) . ")";
		}
		else
		{
			$end = " (not on notify)";
			// Using aoc -> buddy_remove() here is an exception, all the checks in chat -> buddy_remove() aren't needed!
			$this -> aoc -> buddy_remove($user);
		}


		foreach($current_statuses as $status)
		{
			$this -> log("BUDDY", "LOG", $user . " changed status [" . $status . "]" . $end);

			if (!empty($this -> commands["buddy"]))
			{
				$keys = array_keys($this -> commands["buddy"]);
				foreach ($keys as $key)
				{
					$this -> commands["buddy"][$key] -> buddy($user, $status, $args[2], $args[3], $args[4]);
				}
			}
		}
	}



	/*
	Someone joined privategroup
	*/
	function inc_pgjoin($args)
	{
		$pgname = $this -> core("chat") -> get_uname($args[0]);

		if (empty($pgname) || $pgname == "")
		$pgname = $this -> botname;

		$user = $this -> core("chat") -> get_uname($args[1]);

		if (strtolower($pgname) == strtolower($this -> botname))
		{
			$this -> log("PGRP", "JOIN", $user . " joined privategroup.");
			if (!empty($this -> commands["pgjoin"]))
			{
				$keys = array_keys($this -> commands["pgjoin"]);
				foreach ($keys as $key)
				$this -> commands["pgjoin"][$key] -> pgjoin($user);
			}
		}
		else
		{
			$this -> log("PGRP", "JOIN", $user . " joined the exterior privategroup of " . $pgname . ".");
			if (!empty($this -> commands["extpgjoin"]))
			{
				$keys = array_keys($this -> commands["extpgjoin"]);
				foreach ($keys as $key)
				$this -> commands["extpgjoin"][$key] -> extpgjoin($pgname, $user);
			}
		}
	}



	/*
	Someone left privategroup
	*/
	function inc_pgleave($args)
	{
		$pgname = $this -> core("chat") -> get_uname($args[0]);

		if (empty($pgname) || $pgname == "")
		$pgname = $this -> botname;

		$user = $this -> core("chat") -> get_uname($args[1]);

		if (strtolower($pgname) == strtolower($this -> botname))
		{
			$this -> log("PGRP", "LEAVE", $user . " left privategroup.");
			if (!empty($this -> commands["pgleave"]))
			{
				$keys = array_keys($this -> commands["pgleave"]);
				foreach ($keys as $key)
				$this -> commands["pgleave"][$key] -> pgleave($user);
			}
		}
		else
		{
			$this -> log("PGRP", "LEAVE", $user . " left the exterior privategroup " . $pgname . ".");
			if (!empty($this -> commands["extpgleave"]))
			{
				$keys = array_keys($this -> commands["extpgleave"]);
				foreach ($keys as $key)
				$this -> commands["extpgleave"][$key] -> extpgleave($pgname, $user);
			}
		}
	}



	/*
	Message in privategroup
	*/
	function inc_pgmsg($args)
	{
		$pgname = $this -> core("chat") -> get_uname($args[0]);
		$user = $this -> core("chat") -> get_uname($args[1]);
		$found = false;

		if (empty($pgname) || $pgname == "")
		$pgname = $this -> botname;

		if ($pgname == $this -> botname && $this -> core("settings") -> get("Core", "DisablePGMSG"))
		{
			return FALSE;
		}

		$args[2] = utf8_decode($args[2]);

		// Ignore bot chat, no need to handle it's own output as input again
		if (strtolower($this -> botname) == strtolower($user))
		{
			if ($this -> core("settings") -> get("Core", "LogPGOutput"))
			{
				$this -> log("PGRP", "MSG", "[" . $this -> core("chat") -> get_uname($args[0]) . "] " .
				$user . ": " . $args[2]);
			}
			return;
		}
		else
		{
			$this -> log("PGRP", "MSG", "[" . $this -> core("chat") -> get_uname($args[0]) . "] " .
			$user . ": " . $args[2]);
		}

		if (!isset($this -> other_bots[$user]))
		{
			if (strtolower($pgname) == strtolower($this -> botname))
			{
				$found = $this -> handle_command_input($user, $args[2], "pgmsg");
				$found = $this -> hand_to_chat($found, $user, $args[2], "privgroup");
			}
			else
			{
				$found = $this -> handle_command_input($user, $args[2], "extpgmsg", $pgname);
				$found = $this -> hand_to_chat($found, $user, $args[2], "extprivgroup", $pgname);
			}
			if($this -> command_error_text)
			{
				$this -> send_pgroup($this -> command_error_text, $pgname);
			}
			unset($this -> command_error_text);
		}
	}

	/*
	Incoming group announce
	*/
	function inc_gannounce($args)
	{
		if ($args[2] == 32772)
		{
			$this -> guildname = $args[1];
			$this -> log("CORE", "INC_GANNOUNCE", "Detected org name as: $args[1]");
		}
	}

	/*
	* Incoming private group invite
	*/
	function inc_pginvite($args)
	{
		$group = $this -> core("chat") -> get_uname($args[0]);

		if (!empty($this -> commands["pginvite"]))
		{
			$keys = array_keys($this -> commands["pginvite"]);
			foreach ($keys as $key)
			$this -> commands["pginvite"][$key] -> pginvite($group);
		}
	}


	/*
	* Incoming group message (Guildchat, towers etc)
	*/
	function inc_gmsg($args)
	{
		$found = false;

		$group = $this -> core("chat") -> lookup_group($args[0]);

		if (!$group)
		{
			$group = $this -> core("chat") -> get_gname($args[0]);
		}

		$args[2] = utf8_decode($args[2]);

		if (isset($this -> commands["gmsg"][$group]) || ($group == $this -> guildname))
		{
			$msg = "[" . $group . "] ";
			if ($args[1] != 0)
			{
				$msg .= $this -> core("chat") -> get_uname($args[1]) . ": ";
			}
			$msg .= $args[2];
		}
		else
		{
			// If we dont have a hook active for the group, and its not guildchat... BAIL now before wasting cycles
			return FALSE;
		}

		if ($group == $this -> guildname && $this -> core("settings") -> get("Core", "DisableGC"))
		{
			Return FALSE;
		}

		if ($args[1] == 0)
		{
			$user = "0";
		}
		else
		{
			$user = $this -> core("chat") -> get_uname($args[1]);
		}
		// Ignore bot chat, no need to handle it's own output as input again
		if (strtolower($this -> botname) == strtolower($user))
		{
			if ($this -> core("settings") -> get("Core", "LogGCOutput"))
			{
				$this -> log("GROUP", "MSG", $msg);
			}
			return;
		}
		else
		{
			$this -> log("GROUP", "MSG", $msg);
		}

		if (!isset($this -> other_bots[$user]))
		{
			if ($group == $this -> guildname)
			{
				$found = $this -> handle_command_input($user, $args[2], "gc");

				if($this -> command_error_text)
				{
					$this -> send_gc($this -> command_error_text);
				}
				unset($this -> command_error_text);
			}

			$found = $this -> hand_to_chat($found, $user, $args[2], "gmsg", $group);
		}
	}




	/*
	Does all the checks and work for a specific cron time
	*/
	function cronjob($time, $duration)
	{
		if (($this -> cron_job_timer[$duration] < $time) && ($this -> cron_job_active[$duration] == false))
		{
			if (!empty($this -> cron[$duration]))
			{
				$this -> cron_job_active[$duration] = true;
				$crons = array_keys($this -> cron[$duration]);
				for ($i = 0; $i < count($crons); $i++)
				{
					$this -> cron[$duration][$crons[$i]] -> cron($duration);
				}
			}
			$this -> cron_job_active[$duration] = false;
			$this -> cron_job_timer[$duration] = time() + $duration;
		}
	}



	/*
	CronJobs of the bot
	*/
	function cron()
	{
		if (!$this -> cron_activated)
		{
			return;
		}
		$time = time();

		// Check timers:
		$this -> core("timer") -> check_timers();

		if (empty($this -> cron))
		{
			return;
		}

		foreach ($this -> cron_times AS $interval)
		{
			$this -> cronjob($time, $interval);
		}
	}



	/*
	Writes events to the console and log if logging is turned on.
	*/
	function log($first, $second, $msg, $write_to_db = false)
	{
		//Remove font tags
		$msg = preg_replace("/<font(.+)>/U", "", $msg);
		$msg = preg_replace("/<\/font>/U", "", $msg);
		//Remove color tags
		$msg = preg_replace("/##end##/U", "]", $msg);
		$msg = preg_replace("/##(.+)##/U", "[", $msg);
		//Change links to the text [link]...[/link]
		$msg = preg_replace("/<a href=\"(.+)\">/sU", "[link]", $msg);
		$msg = preg_replace("/<\/a>/U", "[/link]", $msg);

		if ($this -> log_timestamp == 'date')
			$timestamp = "[" . gmdate("Y-m-d") . "]\t";
		elseif ($this -> log_timestamp == 'time')
			$timestamp = "[" . gmdate("H:i:s") . "]\t";
		elseif ($this -> log_timestamp == 'none')
			$timestamp = "";
		else
			$timestamp = "[" . gmdate("Y-m-d H:i:s") . "]\t";


		$line = $timestamp . "[" . $first . "]\t[" . $second . "]\t" . $msg . "\n";
		echo $this -> botname . " " . $line;


		// We have a possible security related event.
		// Log to the security log and notify guildchat/pgroup.
		if (preg_match("/^security$/i", $second))
		{
			if ($this -> guildbot)
			{
				$this -> send_gc ($line);
			}
			else
			{
				$this -> send_pgroup ($line);
			}
			$log = fopen($this -> log_path . "/security.txt", "a");
			fputs($log, $line);
			fclose($log);
		}

		if (($this -> log == "all") || (($this -> log == "chat") && (($first == "GROUP") || ($first == "TELL") || ($first == "PGRP"))))
		{
			$log = fopen($this -> log_path . "/" . gmdate("Y-m-d") . ".txt", "a");
			fputs($log, $line);
			fclose($log);
		}

		if ($write_to_db)
		{
			$logmsg = substr($msg, 0, 500);
			$this -> db -> query("INSERT INTO #___log_message (message, first, second, timestamp) VALUES ('" . mysql_real_escape_string($logmsg) . "','" . $first . "','" . $second . "','" . time() . "')");
		}
	}


	/*
	Cut msg into Size Small enough to Send
	*/
	function cut_size($msg, $type, $to="", $pri=0)
	{
		preg_match("/^(.*)<a href=\"(.+)\">(.*)$/isU", $msg, $info);
		$info[2] = str_replace("<br>","\n",$info[2]);
		$content = explode("\n", $info[2]);
		$page = 0;
		$result[$page] = "";
		foreach($content as $line)
		{
			if ((strlen($result[$page]) + strlen($line) + 12) < $this -> maxsize)
			$result[$page] .= $line . "\n";
			else
			{
				$page++;
				$result[$page] .= $line . "\n";
			}
		}

		$between = "";
		for ($i = 0; $i <= $page; $i++)
		{
			if ($i != 0) $between = "text://";
			$msg = $info[1] . "<a href=\"" . $between . $result[$i] . "\">" . $info[3] .
			" <font color=#ffffff>(page ".($i+1)." of ".($page+1).")</font>";

			if ($type == "tell")
			$this -> send_tell($to, $msg, $pri, TRUE, FALSE);
			else if ($type == "pgroup")
			$this -> send_pgroup($msg, $to, FALSE);
			else if ($type == "gc")
			$this -> send_gc($msg, $pri, FALSE);
		}
	}


	// Registers a new reference to a module, used to access the new module by other modules.
	public function register_module(&$ref, $name)
	{
		if (isset($this -> module_links[strtolower($name)]))
		{
			$this -> log('CORE', 'ERROR', "Module '$name' does not exist or is not loaded.");
			return;
		}
		$this -> module_links[strtolower($name)] = &$ref;
	}

	// Unregisters a module link.
	public function unregister_module($name)
	{
		$this -> module_links[strtolower($name)] = NULL;
		unset($this -> module_links[strtolower($name)]);
	}

	// Returns the reference to the module registered under $name. Returns NULL if link is not registered.
	public function core($name)
	{
		if (isset($this -> module_links[strtolower($name)]))
		{
			return $this -> module_links[strtolower($name)];
		}
		$dummy = new BasePassiveModule(&$this, $name);
		$this -> log('CORE', 'ERROR', "Module '$name' does not exist or is not loaded.");
		return $dummy;
	}

	/*
	 * Interface to register and unregister commands
	 */
	public function register_command($channel, $command, &$module)
	{
		$channel = strtolower($channel);
		$command = strtolower($command);
		$allchannels = array("gc", "tell", "pgmsg");
		if ($channel == "all")
		{
			foreach ($allchannels AS $cnl)
			{
				$this -> commands[$cnl][$command] = &$module;
			}
		}
		else
		{
			$this -> commands[$channel][$command] = &$module;
		}
	}

	public function unregister_command($channel, $command)
	{
		$channel = strtolower($channel);
		$command = strtolower($command);
		$allchannels = array("gc", "tell", "pgmsg");
		if ($channel == "all")
		{
			foreach ($allchannels AS $cnl)
			{
				$this -> commands[$cnl][$command] = NULL;
				unset($this -> commands[$cnl][$command]);
			}
		}
		else
		{
			$this -> commands[$channel][$command] = NULL;
			unset($this -> commands[$channel][$command]);
		}
	}

	public function exists_command($channel, $command)
	{
		$channel = strtolower($channel);
		$command = strtolower($command);
		$exists = false;
		$allchannels = array("gc", "tell", "pgmsg");

		if ($channel == "all")
		{
			foreach ($allchannels AS $cnl)
			{
				$exists = $exists & isset($this -> commands[$cnl][$command]);
			}
		}
		else
		{
			$exists = isset($this -> commands[$channel][$command]);
		}

		return $exists;
	}

	public function get_all_commands()
	{
		Return $commands;
	}

	public function get_command_handler($channel, $command)
	{
		$channel = strtolower($channel);
		$command = strtolower($command);
		$handler = "";
		$allchannels = array("gc", "tell", "pgmsg");

		if ($channel == "all")
		{
			$handlers = array();
			foreach ($allchannels AS $cnl)
			{
				$handlers[] = get_class($this -> commands[$cnl][$command]);
			}
			$handler = implode(", ", $handles);
		}
		else
		{
			$handler = get_class($this -> commands[$channel][$command]);
		}

		return $handler;
	}

	/*
	 * Interface to register and unregister commands
	 */
	public function register_event($event, $target, &$module)
	{
		$event = strtolower($event);

		$events = array('connect', 'disconnect', 'pgjoin', 'pgleave', 'buddy', 'privgroup', 'gmsg', 'cron', 'timer', 'logon_notify', 'pginvite', 'extpgjoin', 'extpgleave', 'tells', 'extprivgroup');
		if(in_array($event, $events))
		{
			if($event == 'gmsg')
			{
				if ($target)
				{
					$this -> commands[$event][$target][get_class($module)] = &$module;
					return false;
				}
				else
				{
					return "No channel specified for gmsg. Not registering.";
				}
			}
			elseif($event == 'cron')
			{
				$time = strtotime($target, 0);

				if($time > 0)
				{
					if (!isset($this -> cron_job_active[$time]))
					{
			 			$this -> cron_job_active[$time] = false;
					}
					if (!isset($this -> cron_job_timer[$time]))
					{
						$this -> cron_job_timer[$time] = max(time(), $this -> startup_time);
					}
					$this -> cron_times[$time] = $time;
					$this -> cron[$time][get_class($module)] = &$module;
					return false;
				}
				else
				{
					return "Cron time '$target' is invalid. Not registering.";
				}
			}
			elseif ($event == 'timer')
			{
				if ($target)
				{
					$this -> core("timer") -> register_callback($target, &$module);
					return false;
				}
				else
				{
					return "No name for the timer callback given! Not registering.";
				}
			}
			elseif ($event == 'logon_notify')
			{
				$this -> core("logon_notifies") -> register(&$module);
				return false;
			}
			else
			{
				$this -> commands[$event][get_class($module)] = &$module;
				return false;
			}
		}
		else
		{
			return "Event '$event' is invalid. Not registering.";
		}
		return false;
	}

	public function unregister_event($event, $target, &$module)
	{
		$event = strtolower($event);

		$events = array('connect', 'disconnect', 'pgjoin', 'pgleave', 'buddy', 'privgroup', 'gmsg', 'cron', 'timer', 'logon_notify', 'pginvite', 'extpgjoin', 'extpgleave', 'tells', 'extprivgroup');
		if(in_array($event, $events))
		{
			if($event == 'gmsg')
			{
				if (isset($this -> commands[$event][$target][get_class($module)]))
				{
					$this -> commands[$event][$target][get_class($module)] = NULL;
					unset($this -> commands[$event][$target][get_class($module)]);
					return false;
				}
				else
				{
					return "GMSG $target is not registered or invalid!";
				}
			}
			elseif($event == 'cron')
			{
				$time = strtotime($target, 0);
				if(isset($this -> cron[$time][get_class($module)]))
				{
					$this -> cron[$time][get_class($module)] = NULL;
					unset($this -> cron[$time][get_class($module)]);
					return false;
				}
				else
				{
					return "Cron time '$target' is not registered or invalid!";
				}
			}
			elseif ($event == 'timer')
			{
				return $this -> core("timer") -> unregister_callback($target, &$module);
			}
			elseif ($event == 'logon_notify')
			{
				$this -> core("logon_notifies") -> unregister(&$module);
				return false;
			}
			else
			{
				$this -> commands[$event][get_class($module)] = NULL;
				unset($this -> commands[$event][get_class($module)]);
				return false;
			}
		}
		else
		{
			return "Event '$event' is invalid. Not registering.";
		}
		return false;
	}
}
?>
