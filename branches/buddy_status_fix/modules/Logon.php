<?php
/*
 * Logon.php - Announces logon/logoff events in guildchat
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
* File last changed at $LastChangedDate:2007-12-02 20:57:46 -0800 (Sun, 02 Dec 2007) $
* Revision: $Id:Logon.php 1152 2007-12-03 04:57:46Z ebag333 $
 */

$Logon = new Logon($bot);

/*
The Class itself...
*/
class Logon extends BaseActiveModule
{
	var $last_log;
	var $start;

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("logon", "true") . "
				(id INT NOT NULL PRIMARY KEY,
				message VARCHAR(255))");

		$this -> last_log = array();

		$this -> start = time() + 3600;

		$this -> help['description'] = 'Announces logon logoff events in guildchat.';
		$this -> help['command']['logon <message>']="Sets a custom logon message to be displayed when you log on.";
		$this -> help['command']['logon']="Deletes your custom logon message.";

		$this -> register_command("all", "logon", "MEMBER");
		$this -> register_event("buddy");
		$this -> register_event("connect");

		$this -> bot -> core("colors") -> define_scheme("logon", "logon_spam", "darkaqua");
		$this -> bot -> core("colors") -> define_scheme("logon", "level", "lightteal");
		$this -> bot -> core("colors") -> define_scheme("logon", "ailevel", "lightgreen");
		$this -> bot -> core("colors") -> define_scheme("logon", "organization", "darkaqua");
		$this -> bot -> core("colors") -> define_scheme("logon", "logoff_spam", "yellow");

		$this -> bot -> core("settings") -> create("Logon", "Enable", TRUE, "Should logon spam be enabled at all?");
		$this -> bot -> core("settings") -> create("Relay", "Logon", FALSE, "Should logon spam be relayed to the linked org bots?");
		$this -> bot -> core("settings") -> create("Relay", "LogonInPgroup", TRUE, "Should logons be shown in the private group of the bot too?");
		$this -> bot -> core("settings") -> create("Relay", "OrgLogon", FALSE, "Should prefixing the org channel shortcut to the logon information be used when relaying logons?");
	}



	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^logon (.+)/i", $msg, $info))
		{
			return $this -> set_msg($name, $info[1]);
		}
		elseif (preg_match("/^logon$/i", $msg, $info))
		{
			return $this -> set_msg($name, '');
		}
		return false;
	}



	function buddy($name, $msg)
	{
		if ($msg == 1 || $msg == 0)
		{
			if (($this -> start < time()) && ($this -> bot -> core("settings") -> get("Logon", "Enable")))
			{
				if ($this -> bot -> core("notify") -> check($name))
				{
					$id = $this -> bot -> core("chat") -> get_uid($name);
					if ($msg == 1)
					{
						if ($this -> last_log["on"][$name] < (time() - 5))
						{
							$result = $this -> bot -> core("whois") -> lookup($name);

							$res = "\"" . $name . "\"";
							if (!empty($result["firstname"]))
							{
								$res = $result["firstname"] . " " . $res;
							}
							if (!empty($result["lastname"]))
							{
								$res .= " " . $result["lastname"];
							}

							$res .= " (Lvl ##logon_level##" . $result["level"] . "##end## ";
							$res .= $result["class"];
	/*
							if ($result["org"] != '')
							{
								$res .= ", ##logon_organization##" . $result["rank"] . " of " . $result["org"] . "##end##";
							}
	*/
							$res .= ") Logged On";


							if ($this -> bot -> core("settings") -> get("Whois", "Details") == TRUE)
							{
								if ($this -> bot -> core("settings") -> get("Whois", "ShowMain") == TRUE)
								{
									$main = $this -> bot -> core("alts") -> main($name);
									if (strcasecmp($main, $name) != 0)
									{
										$res .= " :: Alt of $main";
									}
								}
								$res .= " :: " . $this -> bot -> core("whois") -> whois_details($name, $result);
							}
							else if ($this -> bot -> core("settings") -> get("Whois", "Alts") == TRUE)
							{
								$alts = $this -> bot -> core("alts") -> show_alt($name);
								if ($alts['alts'])
								{
									$res .= " :: " . $alts['list'];
								}
							}

							$result = $this -> bot -> db -> select("SELECT message FROM #___logon WHERE id = " . $id);
							if (!empty($result))
							{
								$res .= "  ::  " . stripslashes($result[0][0]);
							}

							$this -> show_logon("##logon_logon_spam##" . $res . "##end##");
							$this -> last_log["on"][$name] = time();
						}
					}
					else
					{
						if ($this -> last_log["off"][$name] < (time() - 5))
						{
							$this -> show_logon("##logon_logoff_spam##" . $name . " logged off##end##");
							$this -> last_log["off"][$name] = time();
						}
					}
				}
			}
		}
	}


	function show_logon($txt)
	{
		$this -> bot -> send_gc($txt);

		if ($this -> bot -> core("settings") -> get("Relay", "Logoninpgroup"))
		{
			$this -> bot -> send_pgroup($txt);
		}

		if ($this -> bot -> core("settings") -> get('Relay', 'Logon')
		&& $this -> bot -> core("settings") -> get('Relay', 'Status'))
		{
			$pre = "";
			if ($this -> bot -> core("settings") -> get("Relay", "Orglogon"))
			{
				$pre = "##relay_channel##[" . $this -> bot -> core("settings") -> get("Relay", "Gcname") . "]##end## ";
			}
			$this -> bot -> core("relay") -> relay_to_bot($pre . $txt);
		}
	}


	function connect()
	{
		$this -> start = time() + 3 * $this -> bot -> crondelay;
	}



	function set_msg($name, $message)
	{
		$id = $this -> bot -> core("chat") -> get_uid($name);
		$message = mysql_real_escape_string($message);
		$this -> bot -> db -> query("REPLACE INTO #___logon (id, message) VALUES ('" . $id . "', '" . $message . "')");
		return "Thank you " . $name . ". You logon message has been set.";
	}
  }
?>
