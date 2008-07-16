<?php
/*
* Roster.php - Handle updating the members list
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
* File last changed at $LastChangedDate: 2008-05-23 09:19:26 +0100 (Fri, 23 May 2008) $
* Revision: $Id: 10_Roster.php 1613 2008-05-23 08:19:26Z alreadythere $
*/

$roster_core = new Roster_Core($bot);

class Roster_Core extends BasePassiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("users", "true") . "
					(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					char_id INT NOT NULL UNIQUE,
					nickname VARCHAR(25) UNIQUE,
					password VARCHAR(64),
					password_salt VARCHAR(5),
					last_seen INT(11) DEFAULT '0',
					last_raid INT(11) DEFAULT '0',
					added_by VARCHAR(25),
					added_at INT(11) DEFAULT '0',
					deleted_by VARCHAR(25),
					deleted_at INT(11),
					banned_by VARCHAR(25),
					banned_at INT(11),
					banned_for VARCHAR(100),
					banned_until INT(11) DEFAULT '0',
					notify INT(1) DEFAULT '0',
					user_level INT(1) DEFAULT '0',
					updated_at INT(11) DEFAULT '0',
					INDEX user_level (user_level),
					INDEX banned_until (banned_until),
					INDEX notify (notify))");

		$this -> register_module("roster_core");
		if ($bot -> guildbot)
		{
			$this -> register_event("gmsg", "Org Msg");
		}

		$this -> register_event("cron", "24hour");
		$this -> register_event("connect");

		$this -> bot -> core("settings") -> create("Members", "LastRosterUpdate", 1, "Last time we completed an ", NULL, TRUE, 2);
		$this -> bot -> core("settings") -> create('Members', 'Roster', 'XML', 'What should we use to look up organization information? (Fallback means that if XML fails the cache will be used)', 'XML;WhoisCache;Fallback');
		$this -> bot -> core("settings") -> create("Members", "Update", TRUE, "Should the roster be updated automaticly?");
		$this -> startup = TRUE;
	}

	function update_user_table()
	{
		if($this -> bot -> core("settings") -> exists("members", "schemaversion"))
		{
			$this -> bot -> db -> set_version("users",
			$this -> bot -> core("settings") -> get("members", "schemaversion"));
			$this -> bot -> core("settings") -> del("members", "schemaversion");
		}
		switch ($this -> bot -> db -> get_version("users"))
		{
			case 1:
				$this -> bot -> db -> update_table("users", array("banned_for", "banned_until"), "add",
				"ALTER TABLE #___users ADD banned_for VARCHAR(100) AFTER banned_at, ADD banned_until INT(11) DEFAULT '0' AFTER banned_for");
				$this -> bot -> db -> set_version("users", 2);
			case 2:
				$this -> bot -> db -> update_table("users", array("user_level", "banned_until", "notify"), "alter",
				"ALTER TABLE #___users ADD INDEX (user_level), ADD INDEX (banned_until), ADD INDEX (notify)");
				$this -> bot -> db -> set_version("users", 3);
			case 3:
				$this -> bot -> db -> update_table('users', array('recieve_announce', 'recieve_invite', 'admin_level'), 'drop',
				"ALTER TABLE #___users DROP recieve_announce, DROP recieve_invite, DROP admin_level");
				$this -> bot -> db -> set_version("users", 4);
			case 4:
				if($this -> bot -> core('prefs') -> exists('AutoInv', 'recieve_auto_invite'))
				{
					$invited_users = $this -> bot -> db -> select('SELECT char_id FROM #___users WHERE auto_invite=1', MYSQL_ASSOC);
					if(!empty($invited_users))
					{
						foreach($invited_users as $invited_user)
						{
							$this -> bot -> core('prefs') -> change($invited_user['char_id'], 'AutoInv', 'recieve_auto_invite', 'On');
						}
					}
					$this -> bot -> db -> update_table('users', array('auto_invite'), 'drop',
					"ALTER TABLE #___users DROP auto_invite");
					$this -> bot -> db -> set_version("users", 5);
				}
				else
				{
					// We have to delay any further updates until we can correctly update the autoinvite fields!
					return;
				}
			case 5:
				// update pref default and remove old settings, useing user scheme since it used to be in user table
				if($this -> bot -> core("settings") -> exists("members", "Receiveannounce"))
				{
					if($this -> bot -> core("settings") -> get("members", "Receiveannounce"))
						$set = "On";
					else
						$set = "Off";
					$this -> bot -> core("prefs") -> change_default("Roster Module", "MassMsg", "recieve_message", $set);
					$this -> bot -> core("settings") -> del("members", "Receiveannounce");
				}
				if($this -> bot -> core("settings") -> exists("members", "Receiveinvite"))
				{
					if($this -> bot -> core("settings") -> get("members", "Receiveinvite"))
						$set = "On";
					else
						$set = "Off";
					$this -> bot -> core("prefs") -> change_default("Roster Module", "MassMsg", "recieve_invite", $set);
					$this -> bot -> core("settings") -> del("members", "Receiveinvite");
				}
			case 6:
			default:
		}
		$this -> bot -> db -> set_version("users", 6);
	}

	function connect()
	{
		$this -> update_user_table();
	}

	function gmsg($name, $group, $msg)
	{
		if ($name == "0")
		{
			if (preg_match("/(.+) kicked (.+) from the organization./i", $msg, $info))
			{
				$person = $info[2];
				$source = $info[1];
				$id = $this -> bot -> core("chat") -> get_uid($person);
				$this -> del ("Org Message", $id, $person, "from Org Message, kicked by $source");
				$this -> bot -> send_gc("##highlight##$person ##end##has been kicked from the org by##highlight## $source##end##");
				$this -> bot -> send_irc($this -> bot -> core("settings") -> get("Irc", "Ircguildprefix"), "",
				"$person has been Kicked from Org by $source");
			}
			else if (preg_match("/(.+) has left the organization./i", $msg, $info))
			{
				$person = $info[1];
				$id = $this -> bot -> core("chat") -> get_uid($person);
				$this -> del ("Org Message", $id, $person, "from Org Message");
				$this -> bot -> send_gc("##highlight##$person ##end##has left the org");
				$this -> bot -> send_irc($this -> bot -> core("settings") -> get("Irc", "Ircguildprefix"), "",
				"$person has Left the Org");
			}
			else if (preg_match("/(.+) has joined the organization./i", $msg, $info))
			{
				$person = $info[1];
				$id = $this -> bot -> core("chat") -> get_uid($person);
				$this -> add ("Org Message", $id, $person, "from Org Message");
				$this -> bot -> send_gc("Welcome##highlight## $person##end##!!!");
				$this -> bot -> send_irc($this -> bot -> core("settings") -> get("Irc", "Ircguildprefix"), "",
				"$person has Joined the Org ");
			}
		}
	}

	/*
	This gets called on cron
	*/
	function cron()
	{
		if (!$this -> bot -> core("settings") -> get("members", "Update"))
		{
			Return;
		}
		if ($this -> bot -> guildbot)
		{
			$this -> update_guild();
		}
		else
		{
			$this -> update_raid();
		}
	}

	function update_guild($force = false)
	{
		if ($this -> startup && !$force)
		{
			$msg = "Bot is online ::: ";
			$this -> startup = FALSE;
			return;
		}
		$this -> lastrun = $this -> bot -> core("settings") -> get("members", "LastRosterUpdate");
		if (($this -> lastrun + (60 * 60 * 6)) >= time() && $force == false)
		{
			$this -> bot -> log("ROSTER", "UPDATE", "Roster update ran less than 6 hours ago, skipping!");
			//$this -> bot -> send_gc("##normal##".$msg."Roster update not scheduled ::: System ready##end##");
		}
		else
		{
			$this -> bot -> log("ROSTER", "UPDATE", "Starting roster update");
			//$this -> bot -> send_gc("##normal##".$msg."Roster update starting ::: System busy##end##");

			$buddies = $this -> bot -> aoc -> buddies;
			$this -> added = 0;
			$this -> removed = 0;
			$this -> rerolled = 0;

			$db_members = $this -> bot -> db -> select("SELECT char_id, nickname, user_level, updated_at FROM #___users");
			if (!empty($db_members))
			{
				foreach ($db_members as $db_member)
				{
					$db_members[$db_member[1]] = $db_member;
					$this -> bot -> core("chat") -> buddy_add($db_member[1]);
					unset($buddies[$db_member[1]]);
				}
			}

			/*
			Run through our notifylist to make sure they are on the buddylist - and stay there
			*/
			$guests = $this -> bot -> db -> select("SELECT char_id, nickname FROM #___users WHERE notify = '1'");
			if(!empty($guests))
			{
				foreach($guests as $guest)
				{
					/*
					Make sure the user is on the buddylist.
					*/
					$this -> bot -> core("chat") -> buddy_add($guest[1]);

					/*
					Make sure we don't delete the guest in the final step
					*/
					unset($buddies[$guest[1]]);
				}
			}

			$this -> bot -> core("notify") -> update_cache();
		}
	}

	function update_raid($force = false)
	{
		if ($this -> startup && !$force)
		{
			$msg = "Bot is online ::: ";
			$this -> startup = FALSE;
		}
		$this -> lastrun = $this -> bot -> core("settings") -> get("members", "LastRosterUpdate");
		if (($this -> lastrun + (60 * 60 * 6)) >= time() && $force == false)
		{
			$this -> bot -> log("ROSTER", "UPDATE", "Roster update ran less than 6 hours ago, skipping!");
			//$this -> bot -> send_pgroup("##normal##".$msg."Roster update not scheduled ::: System ready##end##");
		}
		else
		{
			$this -> bot -> log("ROSTER", "UPDATE", "Starting roster update");
			//$this -> bot -> send_pgroup("##normal##".$msg."Roster update starting ::: System busy##end##");

			$buddies = $this -> bot -> aoc -> buddies;
			$num = 0;
			$this -> removed = 0;
			$this -> rerolled = 0;

			$members = $this -> bot -> db -> select("SELECT char_id, nickname, user_level, notify, updated_at FROM #___users");
			if (!empty($members))
			{
				foreach ($members as $member)
				{
					$id = $this -> bot -> core("chat") -> get_uid($member[1]);

					/*
					Catch deleted characters.
					*/
					if (!$id)
					{
						if ((($member[4] + 172800) <= time()) && ($member[4] != 0))
						{
							$this -> erase("Roster", $member[0], $member[1], "as the character appears to have been deleted.");
							$this -> removed++;
						}
						else
						{
							$this -> bot -> log("ROSTER", "INFO", $member[1] . " is in members table but Apears to have been Deleted, skipping removal for now");
							$this -> bot -> db -> query("UPDATE #___users SET updated_at = " . time() . " WHERE char_id = '" . $member[0] . "'");
						}
					}

					/*
					Catch rerolled characters.
					*/
					else if ($id != $member[0])
					{
						if($id == "0" || $id == "-1" || $id == "" || $id == NULL || empty($id) || strlen($id) < 5)
						{
							$this -> bot -> log("ROSTER", "ID", "Get ID Failed for $name (ID: ".$id.")");
						}
						else
						{
							$this -> erase("Roster", $member[0], $member[1], "as the character appears to have been rerolled. Old: $member[0] New: $id");
							$this -> rerolled++;
						}
					}

					/*
					If not we just run through the paces and make sure everything is in order.
					*/
					else
					{
						if($member[4] != 0)
						{
							$this -> bot -> db -> query("UPDATE #___users SET updated_at = 0 WHERE char_id = " . $id);
						}
						/*
						Make sure we have an entry in the whois cache for the character.
						*/
						$this -> bot -> core("whois") -> lookup($member[1]);

						if ($member[3] == 1)
						{
							/*
							Make sure all on characters on notify list are in buddy list
							*/
							$this -> bot -> core("chat") -> buddy_add($id);

							/*
							Make sure we don't remove the characters on notify list from buddy list later
							*/
							unset($buddies[$member[0]]);
						}
					}
				}
			}

			$this -> bot -> log("CRON", "ROSTER", "Done updating roster. Removed " . $this -> removed . " members of which " . $this -> rerolled . " was rerolled characters.", true);

			$this -> bot -> log("CRON", "ROSTER", "Cleaning buddylist.");
			/*
			cycle through anything still on our buddylist
			*/
			foreach ($buddies as $id => $value)
			{
				$this -> bot -> core("chat") -> buddy_remove($id);
				$num++;
			}
			$this -> bot -> core("settings") -> save("members", "LastRosterUpdate", time());
			$this -> bot -> log("CRON", "ROSTER", "Cleaning buddylist done. $num buddies removed.");
			$this -> bot -> send_pgroup("##normal##Roster update completed ::: System ready##end##");
		}
	}

	function add($source, $id, $name, $reason)
	{
		$this -> bot -> log("ROSTER", "ADD", "Adding $name $reason");
		$result = $this -> bot -> core("user") -> add($source, $name, $id, 2, 1);
		if ($result["error"])
		{
			$this -> bot -> log("ROSTER", "ERROR", $result["errordesc"]);
		}
		else
		{
			$this -> added++;
		}

		$this -> bot -> core("chat") -> buddy_add($id);

	}

	function del($source, $id, $name, $reason)
	{
		$this -> bot -> log("ROSTER", "DEL", "Deleting $name $reason");
		$result = $this -> bot -> core("user") -> del($source, $name, $id, 1);
		if ($result["error"])
		{
			$this -> bot -> log("ROSTER", "ERROR", $result["errordesc"]);
		}
		else
		{
			$this -> removed++;
		}

		$this -> bot -> core("chat") -> buddy_remove($id);
	}

	function erase($source, $id, $nickname, $reason)
	{
		$this -> bot -> log("ROSTER", "ERASE", "Erasing $nickname $reason");
		$result = $this -> bot -> core("user") -> erase($source, $nickname, 1, $id);
		if ($result["error"])
		{
			$this -> bot -> log("ROSTER", "ERROR", $result["errordesc"]);
		}

		$this -> bot -> core("chat") -> buddy_remove($id);
	}

	function parse_org($dim, $id)
	{
		if ($this -> bot -> core("settings") -> get("Members", "Roster") == "XML" || $this -> bot -> core("settings") -> get("Members", "Roster") == "Fallback")
		{
			// Get the guild roster
			$xml_roster = $this -> bot -> core("tools") -> get_site("http://people.anarchy-online.com/org/stats/d/$dim/name/$id/basicstats.xml");
			$faction = $this -> bot -> core("tools") -> xmlparse($xml_roster["content"], "side");
			$orgname = $this -> bot -> core("tools") -> xmlparse($xml_roster["content"], "name");
			$xml_roster = explode("<member>", $xml_roster["content"]);
			unset($xml_roster[0]); //Get rid of the header as it's not a member.

			if (!empty($org))
			{
				// Build array of members
				foreach($xml_roster as $xml_member)
				{
					$member['nickname'] = $this -> bot -> core("tools") -> xmlparse($xml_member, "nickname");
					$member["firstname"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "firstname");
					$member["lastname"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "lastname");
					$member["level"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "level");
					$member["gender"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "gender");
					$member["breed"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "breed");
					$member["faction"] = $faction;
					$member["profession"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "profession");
					$member["at"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "defender_rank");
					$member["at_id"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "defender_rank_id");
					$member["org_id"] = $id;
					$member["org"] = $orgname;
					$member["rank_id"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "rank");
					$member["rank"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "rank_name");
					$member["pictureurl"] = $this -> bot -> core("tools") -> xmlparse($xml_member, "photo_url");
					$member["id"] = $this -> bot -> core("chat") -> get_uid($member["nickname"]); // Hey, don't get the character ID before you have a nickname!
					if ($member['nickname'] !== ucfirst(strtolower($this -> bot -> botname)))
					{
						$members[]=$member;
					}
				}
			}
		}

		if (($this -> bot -> core("settings") -> get("Members", "Roster") == "WhoisCache" || $this -> bot -> core("settings") -> get("Members", "Roster") == "Fallback") && (empty($members) || $members == 0))
		{
			$db_members = $this -> bot -> db -> select("SELECT id, nickname, firstname,lastname,level,gender,breed,faction,profession,defender_rank,defender_rank_id,org_id,org_name,org_rank,org_rank_id,pictureurl FROM #___whois WHERE org_id = '" . $id . "'");

			if (!empty($db_members))
			{
				foreach ($db_members as $key => $db_member)
				{
					$members[$key]["nickname"] = $db_member[1];
					$members[$key]["firstname"] = $db_member[2];
					$members[$key]["lastname"] = $db_member[3];
					$members[$key]["level"] = $db_member[4];
					$members[$key]["gender"] = $db_member[5];
					$members[$key]["breed"] = $db_member[6];
					$members[$key]["faction"] = $db_member[7];
					$members[$key]["profession"] = $db_member[8];
					$members[$key]["at"] = $db_member[9];
					$members[$key]["at_id"] = $db_member[10];
					$members[$key]["org_id"] = $db_member[11];
					$members[$key]["org"] = $db_member[12];
					$members[$key]["rank"] = $db_member[13];
					$members[$key]["rank_id"] = $db_member[14];
					$members[$key]["pictureurl"] = $db_member[15];
					$members[$key]["id"] = $db_member[0];

					if ($members[$key]["nickname"] == ucfirst(strtolower($this -> bot -> botname)))
						unset($members[$key]);
				}
			}
		}
		return $members;
	}

}
?>
