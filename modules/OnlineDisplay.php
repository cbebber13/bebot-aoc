<?php
/*
* Online.php - Online plugin to display online users
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
* File last changed at $LastChangedDate: 2008-05-12 00:15:50 +0100 (Mon, 12 May 2008) $
* Revision: $Id: OnlineDisplay.php 1554 2008-05-11 23:15:50Z temar $
*/

$onlinedisplay = new OnlineDisplay($bot);

/*
The Class itself...
*/
class OnlineDisplay extends BaseActiveModule
{
	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command("all", "online", "GUEST");
		$this -> register_command("all", "sm", "GUEST");

		// Register for logon notifies
		$this -> register_event("logon_notify");
		$this -> register_event("pgjoin");

		$this -> help['description'] = 'Shows who is online.';
		$this -> help['command']['online'] = 'Shows who is online in org or chatgroup.';
		$this -> help['command']['online <prof>'] = "Shows all characters of classes <prof> online in org or chatgroup.";
		$this -> help['command']['sm'] = "Lists all characters online sorted alphabetical by name.";

		$this -> bot -> core("settings") -> create("Online", "Mode", "Basic", "Which mode should be used in the online display?", "Basic;Fancy");
		if ($this -> bot -> guildbot)
		{
			$altmode = true;
			$charinfo = "rank";
			if ($this -> bot -> core("settings") -> get("Online", "Otherbots") != "")
			{
				$guildtext = "members online in Alliance.";
			}
			else
			{
				$guildtext = "members online in Guild.";
			}

		}
		else
		{
			$altmode = false;
			$charinfo = "org";
			$guildtext = "members online";
		}
		$this -> bot -> core("settings") -> create("Online", "ShowAlts", $altmode, "Whould mains and alts be shown in the online display?");
		$this -> bot -> core("settings") -> create("Online", "CharInfo", $charinfo, "Which information should be shown besides level and alien level?", "none;rank;org;both");
		$this -> bot -> core("settings") -> create("Online", "UseShortcuts", FALSE, "Should the shortcut database be used to transform the info shown about characters?");
		$this -> bot -> core("settings") -> create("Online", "ShowAccessLevel", FALSE, "Should the access level of each player be displayed?");
		$this -> bot -> core("settings") -> create("Online", "GuildText", $guildtext, "What title should be displayed when online buddies are listed?");
		$this -> bot -> core("settings") -> create("Online", "GroupText", "characters in privategroup", "What title should be displayed when online characters in the private group are listed?");
		$this -> bot -> core("settings") -> create("Online", "SortBy", "nickname", "Should the characters of each class be sorted by nickname or level?", "nickname;level");
		$this -> bot -> core("settings") -> create("Online", "LogonSpam", FALSE, "Should buddies that log on be spammed with the current online list?");
		$this -> bot -> core("settings") -> create("Online", "PgjoinSpam", FALSE, "Should users who join private group get spammed with current online list?");

		$this -> bot -> core("colors") -> define_scheme("online", "title", "blob_title");
		$this -> bot -> core("colors") -> define_scheme("online", "class", "seagreen");
		$this -> bot -> core("colors") -> define_scheme("online", "characters", "blob_text");
		$this -> bot -> core("colors") -> define_scheme("online", "afk", "white");
	}

	function notify($user, $startup = false)
	{
		if (!$startup && $this -> bot -> core("settings") -> get("Online", "Logonspam"))
		{
			$this -> bot -> send_tell($user, $this -> online_msg("", $this -> bot -> core("settings") -> get("Online", "Channel")));
		}
	}

	function pgjoin($user)
	{
		if ($this -> bot -> core("settings") -> get("Online", "PgjoinSpam"))
			$this -> bot -> send_tell($user, $this -> online_msg("", $this -> bot -> core("settings") -> get("Online", "Channel")));
	}

	function command_handler($name, $msg, $origin)
	{
		return $this -> handler($msg, $this -> bot -> core("settings") -> get("Online", "Channel"));
	}

	function handler($msg, $what)
	{
		if (preg_match("/^online$/i", $msg))
			return $this -> online_msg("all", $what);
		else if (preg_match("/^online (.+)$/i", $msg, $info))
			return $this -> online_msg($info[1], $what);
		else if (preg_match("/^sm$/i", $msg))
			return $this -> sm_msg($what);
	}


	/*
	Makes the message.
	*/
	function online_msg($param, $what)
	{
		if ($param == "all")
		$param = "";

		// If any search parameter is added try to get the profession name
		$profstring = "";
		if ($param != "")
		{
			if(($profname = $this -> bot -> core("professions") -> full_name($param)) instanceof BotError) return $profname;
			$profstring = " AND t2.class = '" . $profname . "' ";
		}

		$guild = $this -> online_list("gc", $profstring);
		$pgroup = $this -> online_list("pg", $profstring);

		$online = "";
		$msg = "";

		if (($what == "both") || ($what == "guild"))
		{
			$online .= $this -> bot -> core("colors") -> colorize("online_title", "::: " . $guild[0] . " " . $this -> bot -> core("settings") -> get("Online", "Guildtext") . " :::") . "\n" . $guild[1];
			$online .= "\n" . $this -> bot -> core("colors") -> colorize("lightbeige", "--------------------------------------------------------------\n");
			$msg .= $this -> bot -> core("colors") -> colorize("highlight", $guild[0]) . " " . $this -> bot -> core("settings") -> get("Online", "Guildtext") . " ";
		}
/*
		if (($what == "both") || ($what == "pgroup"))
		{
			$online .= $this -> bot -> core("colors") -> colorize("online_title", "::: " . $pgroup[0] . " " . $this -> bot -> core("settings") -> get("Online", "GroupText") . " :::") . "\n" . $pgroup[1];
			$msg .= $this -> bot -> core("colors") -> colorize("highlight",  $pgroup[0]) . " " . $this -> bot -> core("settings") -> get("Online", "GroupText");
		}
*/

		$msg .= ":: " . $this -> bot -> core("tools") -> make_blob("click to view", $online);

		return $msg;
	}



	/*
	make the list of online players
	*/
	function online_list($channel, $like)
	{
		$botstring = $this -> bot -> core("online") -> otherbots();

		if (strtolower($this -> bot -> core("settings") -> get("Online", "Sortby")) == "level")
		{
			$sortstring = " ORDER BY class ASC, level DESC, t1.nickname ASC";
		}
		else
		{
			$sortstring = " ORDER BY class ASC, t1.nickname ASC";
		}

		$online = $this -> bot -> db -> select("SELECT t1.nickname, level, org_rank, org_name, class FROM "
		. "#___online AS t1 LEFT JOIN #___whois AS t2 ON t1.nickname = t2.nickname WHERE status_" . $channel . "=1 AND "
		. $botstring . $like . $sortstring);

		if (strtolower($this -> bot -> core("settings") -> get("Online", "Mode")) == "fancy")
		{
			$profgfx["Barbarian"] = "16308";
			$profgfx["Guardian"] = "84203";
			$profgfx["Conqueror"] = "16252";
			$profgfx["Priest of Mitra"] = "16237";
			$profgfx["Tempest of Set"] = "84197";
			$profgfx["Bear Shaman"] = "39290";
			$profgfx["Dark Templar"] = "16300";
			$profgfx["Assassin"] = "16186";
			$profgfx["Ranger"] = "117993";
			$profgfx["Doctor"] = "44235";
			$profgfx["Necromancer"] = "100998";
			$profgfx["Herald of Xotli"] = "16341";
			$profgfx["Demonologist"] = "16196";
		}
		$prof_based = "";
		$online_list = "";
		$online_num = 0;

		if (!empty($online))
		{
			$currentprof = "";
			foreach ($online as $player)
			{
				if ($currentprof != $player[4])
				{
					$currentprof = $player[4];
					if (strtolower($this -> bot -> core("settings") -> get("Online", "Mode")) == "fancy")
					{
						$online_list .= "\n<img src=tdb://id:GFX_GUI_FRIENDLIST_SPLITTER>\n";
						$online_list .= "<img src=rdb://" . $profgfx[$player[4]] . ">";
					}
					else
					{
						$online_list .= "\n";
					}
					$online_list .= $this -> bot -> core("colors") -> colorize("online_class", $player[4]) . "\n";
					if (strtolower($this -> bot -> core("settings") -> get("Online", "Mode")) == "fancy")
					{
						$online_list .= "<img src=tdb://id:GFX_GUI_FRIENDLIST_SPLITTER>\n";
					}
				}

				$admin = "";
				$online_num++;
				$main = $this -> bot -> core("alts") -> main($player[0]);
				$alts = $this -> bot -> core("alts") -> get_alts($main);

				if ($this -> bot -> core("settings") -> get("Online", "Showaccesslevel") && $this -> bot -> core("security") -> check_access($player[0], "LEADER"))
				{
					$level = $this -> bot -> core("security") -> get_access_name($this -> bot -> core("security") -> get_access_level($player[0]));
					$admin = " :: " . $this -> bot -> core("colors") -> colorize("online_title", ucfirst(strtolower($level))) . " ";
				}

				if (empty($alts) || !$this -> bot -> core("settings") -> get("Online", "Showalts"))
				{
					$alts = "";
				}
				else if ($main == $this -> bot -> core("chat") -> get_uname($player[0]))
				{
					$alts = ":: ".$this -> bot -> core("tools") -> chatcmd("whois " . $player[0], "Details")." ::";
				}
				else
				{
					$alts = ":: ".$this -> bot -> core("tools") -> chatcmd("whois " . $player[0], $main . "'s Alt")." ";
				}

				$charinfo = "";
				if ($this -> bot -> core("settings") -> get("Online", "Useshortcuts"))
				{
					$player[2] = $this -> bot -> core("shortcuts") -> get_short($player[2]);
					$player[3] = $this -> bot -> core("shortcuts") -> get_short(stripslashes($player[3]));
				}
				else
				{
					$player[3] = stripslashes($player[3]);
				}
/*
				if (strtolower($this -> bot -> core("settings") -> get("Online", "Charinfo")) == "both")
				{
					if ($player[3] != '')
					$charinfo = "(" . $player[2] . ", " . $player[3] . ") ";
				}
				elseif (strtolower($this -> bot -> core("settings") -> get("Online", "Charinfo")) == "rank")
				{
					if ($player[2] != '')
					$charinfo = "(" . $player[2] . ") ";
				}
				elseif (strtolower($this -> bot -> core("settings") -> get("Online", "Charinfo")) == "org")
				{
					if ($player[3] != '')
					$charinfo = "(" . $player[3] . ") ";
				}
*/
				$online_list .= $this -> bot -> core("colors") -> colorize("online_characters", " - Lvl " . $player[1] . " " . $player[0] . " " . $charinfo . $admin . $alts);
				if($this -> bot -> commands["tell"]["afk"] -> afk[$player[0]])
				{
					$online_list .= ":: " . $this -> bot -> core("colors") -> colorize("online_afk", "( AFK )") . "\n";
				}
				else
				{
					$online_list .= "\n";
				}
			}
		}

		return array($online_num, $online_list);
	}

	/*
	Makes the message.
	*/
	function sm_msg($what)
	{
		if ($param == "all")
			$param = "";

		// If any search parameter is added try to get the profession name
		$profstring = "";
		if ($param != "")
		{
			if(($profname = $this -> bot -> core("professions") -> full_name($param)) instanceof BotError) return $profname;
			$profstring = " AND t2.class = '" . $profname . "' ";
		}

		$countonline = $this -> bot -> db -> select("SELECT count(DISTINCT t1.nickname) FROM " . $this -> bot -> core("online") -> full_tablename() . " WHERE level >= 1");

		$count1 = $this -> bot -> db -> select("SELECT count(DISTINCT t1.nickname) FROM " . $this -> bot -> core("online") -> full_tablename() . " WHERE level < 100");
		$count2 = $this -> bot -> db -> select("SELECT count(DISTINCT t1.nickname) FROM " . $this -> bot -> core("online") -> full_tablename() . " WHERE level < 190 AND level > 99");
		$count3 = $this -> bot -> db -> select("SELECT count(DISTINCT t1.nickname) FROM " . $this -> bot -> core("online") -> full_tablename() . " WHERE level < 205 AND level > 189");
		$count4 = $this -> bot -> db -> select("SELECT count(DISTINCT t1.nickname) FROM " . $this -> bot -> core("online") -> full_tablename() . " WHERE level < 220 AND level > 204");
		$count5 = $this -> bot -> db -> select("SELECT count(DISTINCT t1.nickname) FROM " . $this -> bot -> core("online") -> full_tablename() . " WHERE level = 220");

		$online = $this -> bot -> db -> select("SELECT DISTINCT(t1.nickname), level, class, org_name FROM "
		. $this -> bot -> core("online") -> full_tablename() . " WHERE level >= 1"
		. " ORDER BY t1.nickname ASC, class ASC, level DESC DESC");


		$count = 0;
		$msg = $this -> bot -> core("colors") -> colorize("highlight", "Chatlist\n\n");
		$msg .= $this -> bot -> core("colors") -> colorize("online_characters", "Players (1-99): ") . $count1[0][0] . "\n";
		$msg .= $this -> bot -> core("colors") -> colorize("online_characters", "Players (100-189): ") . $count2[0][0] . "\n";
		$msg .= $this -> bot -> core("colors") -> colorize("online_characters", "Players (190-204): ") . $count3[0][0] . "\n";
		$msg .= $this -> bot -> core("colors") -> colorize("online_characters", "Players (205-219): ") . $count4[0][0] . "\n";
		$msg .= $this -> bot -> core("colors") -> colorize("online_characters", "Players (220): ") . $count5[0][0] . "\n\n";


		if (!empty($online))
		{
			foreach ($online as $player)
			{
				$msg .= $this -> bot -> core("colors") -> colorize("online_title",$player[0] . "\n");
				$msg .= "    " .  $player[1] . "/" . $player[2] . " " . $player[3] . "\n";
				$count++;
			}
		}

		if(empty($countonline[0][0]))
			$countonline[0][0] = 0;

		return $countonline[0][0]  . " Members Online :: " . $this -> bot -> core("tools") -> make_blob("click to view", $msg);
	}
}
?>
