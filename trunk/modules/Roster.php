<?php
/*
* Roster.php - Handle member roster commands.
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
* File last changed at $LastChangedDate: 2008-05-22 06:43:51 +0100 (Thu, 22 May 2008) $
* Revision: $Id: Roster.php 1585 2008-05-22 05:43:51Z blueeagle $
*/

$roster_handler = new Roster_Handler($bot);

class Roster_Handler extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command("all", "member", "ADMIN");
		$this -> register_command("all", "guest", "ADMIN");
		$this -> register_command("all", "rosterupdate", "ADMIN");
		$this -> register_command("all", "buddylist", "ADMIN");

		$this -> register_event("cron", "1hour");

		$this -> help['description'] = 'Handles member roster commands.';
		$this -> help['command']['member'] = "Shows the members list.";
		$this -> help['command']['member add <name>'] = "Adds player <name> as a member to the bot.";
		$this -> help['command']['member del <name>'] = "Removes player <name> from the member list.";
		$this -> help['command']['guest'] = "Shows the guest list.";
		$this -> help['command']['guest add <name>'] = "Adds player <name> as a guest to the bot.";
		$this -> help['command']['guest del <name>'] = "Removes player <name> from the guest list.";
		$this -> help['command']['rosterupdate'] = "Forces the bot to run through a roster update.";
		$this -> help['command']['buddylist'] = "Displays a list of all of the bots buddies.";
		$this -> help['command']['buddylist clear'] = "Wipes all of the bots buddies from the bots buddylist.";

		$this -> bot -> core("settings") -> create("Roster", "Buddylistupdate", 10, "What difference is allowed between the bots buddylist and the number of members who should be on it before a roster update is forced?", "10;20;30;40;50");

// 		$infos = $this -> bot -> db -> select("SELECT * FROM #___professions ORDER BY profession ASC");
// 		foreach ($infos as $info)
// 		{
// 			$this -> prof[$info[1]] = $info[0];
// 		}

	}

	/*
	Unified message handler
	*/
	function command_handler($source, $msg, $type)
	{
		$return = false;

		/*
		This should really be moved to the bot core.. but until i get the time to modify every single module... :\
		*/
		$vars = explode(' ', strtolower($msg));

		$command = $vars[0];

		switch($command)
		{
			case 'member':
				switch($vars[1])
				{
					case 'del':
					case 'rem':
						$return = $this -> bot -> core("user") -> del ($source, $vars[2], 0, 0);
						if ($return["error"] == true)
						{
							return $return["errordesc"];
						}
						else
						{
							return $return["content"];
						}
						break;
					case 'add':
						$return = $this -> bot -> core("user") -> add ($source, $vars[2], 0, MEMBER, 0);
						if ($return["error"] == true)
						{
							return $return["errordesc"];
						}
						else
						{
							return $return["content"];
						}
						break;
					case 'list':
						return $this -> memberslist();
						break;
					default:
						return $this -> memberscount();
						break;
				}
				break;
			case 'guest':
				switch($vars[1])
				{
					case 'del':
					case 'rem':
						$return = $this -> bot -> core("user") -> del ($source, $vars[2], 0, 0);
						if ($return["error"] == true)
						{
							return $return["errordesc"];
						}
						else
						{
							$this->bot->core('notify')->del($vars[2]);
							return $return["content"];
						}
						break;
					case 'list':
					default:
						return $this -> guest_list();
						break;
					case 'add':
						if ($this -> bot -> core("security") -> check_access($vars[2], "MEMBER"))
						{
							return "##highlight##" . $vars[2] . " ##end##already has at least MEMBER access to the bot!";
						}

						$return = $this -> bot -> core("user") -> add ($source, $vars[2], 0, GUEST, 0);
						if ($return["error"] == true)
						{
							return $return["errordesc"];
						}
						else
						{
							$this -> bot -> core('notify') -> add ($source, $vars[2]);
							return $return["content"];
						}
						break;
				}
				break;
			case 'rosterupdate':
				$force = true;
				if ($this -> bot -> guildbot)
				{
					$this -> output($source, "Starting roster update.");
					$this -> bot -> core("roster_core") -> update_guild($force);
					return FALSE;
				}
				else
				{
					$this -> output($source, "Starting roster update.");
					$this -> bot -> core("roster_core") -> update_raid($force);
					return FALSE;
				}
				break;
			case 'buddylist':
				if ($vars[1] == 'clear')
				{
					return $this -> clear_buddies();
				}
				else
				{
					return $this -> list_buddies();
				}
				break;
			default:
				return "Broken plugin, recieved unhandled command: $command";
				break;
		}
	}

	/*
	Makes a list of current Guests
	*/
	function guest_list()
	{
		$inside = "##blob_title##:::: <botname>'s Guest List ::::##end##\n\n";
		$count = 0;
		$result = $this -> bot -> db -> select("SELECT id, nickname, added_at, added_by FROM #___users WHERE user_level = " . GUEST . " ORDER BY nickname ASC");
		if (!empty($result))
		{
			foreach ($result as $val)
			{
				if (!empty($val[1]))
				{
					$count++;
					$inside .= "##blob_text##&#8226; " . $val[1] . "##end## ".$this -> bot -> core("tools") -> chatcmd("whois " . $val[1], "[Whois]")." ".$this -> bot -> core("tools") -> chatcmd("guest del " . $val[1], "[Remove]")."\n";
					$inside .= "##blob_title##Added:##end## ##blob_text##" . gmdate($this -> bot -> core("settings") -> get("Time", "FormatString"), $val[2]) . " GMT##end## :: ##blob_title##By:##end####blob_text## ". stripslashes($val[3]) ."##end##\n\n";
				}
			}
		}
		return $count . " guests in <botname> :: " . $this -> bot -> core("tools") -> make_blob("click to view", $inside);
	}

	function memberslist()
	{
		$blob = "";
		$count = 0;
		$result = $this -> bot -> db -> select("SELECT nickname, last_seen FROM #___users WHERE user_level = " . MEMBER . " ORDER BY nickname ASC");
		if (!empty($result))
		{
			$inside = "##blob_title##:::: <botname>'s Member List ::::##end##\n\n";
			foreach ($result as $val)
			{
				$count++;
				$inside .= "##blob_text##&#8226; " . $val[0];
				if ($val[1] > 0)
				{
					$inside .= ", last seen at " . gmdate($this -> bot -> core("settings") -> get("Time", "FormatString"), $val[1]);
				}
				else
				{
					$inside .= ", never seen online";
				}
				$inside .= "##end## ".$this -> bot -> core("tools") -> chatcmd("whois " . $val[0], "[Whois]")."\n";
			}
			$blob = " :: " . $this -> bot -> core("tools") -> make_blob("click to view", $inside);
		}
		return $count . " members in <botname>" . $blob;
	}

	function memberscount()
	{
		$blob = "";
		$total = 0;

		$buddies = count($this -> bot -> aoc -> buddies);
		//Get a list of professions
		$profession_list = "'".$this->bot->core('professions')->get_professions("', '")."'";
		$counts = $this -> bot -> db -> select("SELECT t2.class, COUNT(DISTINCT t1.nickname)
				FROM #___users AS t1 LEFT JOIN #___whois AS t2 ON t1.nickname = t2.nickname
				 WHERE user_level = " . MEMBER . " AND t2.class IN ($profession_list) GROUP BY class");

		foreach ($this -> bot->core('professions')->get_profession_array() as $prof)
			$count[$prof] = 0;
		if (!(empty($counts)))
		{
			foreach ($counts as $profcount)
			{
				$count[$profcount[0]] += $profcount[1];
				$total += $profcount[1];
			}
		}

		$inside = "##blob_title##:::: <botname>'s Member Count ::::##end##\n";
		$inside .= "\n##blob_text##Buddy List Count: ##blob_title##".$buddies."##end##\n";
		foreach ($count as $key => $value)
			$inside .= "\n&#8226; ".$key." = ##blob_title##".$value."##end##";
		$blob = " :: " . $this -> bot -> core("tools") -> make_blob("click to view", $inside);

		return $total . " members in <botname>" . $blob;
	}

	function list_buddies()
	{
		$buddies = $this -> bot -> aoc -> buddies;
		$count = 0;

		if (empty($buddies))
		{
			return "No buddies in <botname>'s buddylist!";
		}

		foreach ($buddies as $id => $value)
		{
			$buddy[$id] = $this -> bot -> core("chat") -> get_uname($id);
			$count++;
		}

		asort($buddy);

		foreach ($buddy as $id => $value)
		{
			$msg .= $value . " (ID: " . $id . ")\n";
		}

		return $count . " buddies in <botname>'s buddylist :: " . $this -> bot -> core("tools") -> make_blob("click to view", $msg);
	}

	function clear_buddies()
	{
		$buddies = $this -> bot -> aoc -> buddies;
		$count = 0;

		foreach ($buddies as $id => $value)
		{
			$this -> bot -> core("chat") -> buddy_remove($id);
			$count++;
		}

		return "Removed " . $count . " buddies from <botname>'s buddylist.";
	}

	/*
	This gets called on cron
	*/
	function cron()
	{
/*
		$buddies = $this -> bot -> aoc -> buddies;
		$buddy_count = count($buddies);

		$notify_db = $this -> bot -> db -> select("SELECT count(notify) FROM #___users WHERE notify = 1");

		$notify_count = $notify_db[0][0];

		if ($notify_count - $buddy_count >= $this -> bot -> core("settings") -> get("Roster", "Buddylistupdate") || $buddy_count - $notify_count >= $this -> bot -> core("settings") -> get("Roster", "Buddylistupdate"))
		{
				$force = true;

				if ($this -> bot -> guildbot)
				{
					$this -> bot -> core("roster_core") -> update_guild($force);
				}
				else
				{
					$this -> bot -> core("roster_core") -> update_raid($force);
				}
		}
*/
	}
}
?>
