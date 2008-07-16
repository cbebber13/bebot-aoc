<?php
/*
* User interface to add, list and remove timers.
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
* File last changed at $LastChangedDate$
* Revision: $Id: TimerUI.php 1593 2008-05-22 15:24:42Z alreadythere $
*/

$timergui = new TimerGUI($bot);

/*
The Class itself...
*/
class TimerGUI extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command("all", "timer", "GUEST");
		$this -> register_command("all", "rtimer", "GUEST");
		$this -> register_alias('timer', 'timers');
		$this -> register_command("all", "remtimer", "GUEST");

		$this -> help['description'] = 'Setting and removing of timers..';
		$this -> help['command']['timer']="Lists all current timer for the bot and offers support to delete them.";
		$this -> help['command']['timer [class] #[mshd] title']="Adds a timer for # minutes (m), seconds (s), hours (h) or days (d). If no time unit is added it's # seconds. [class] is an optional parameter defining which timer class to use.";
		$this -> help['command']['timer [class] #[:##[:##[:##]]] title']="Adds a timer using the format days:hours:minutes:seconds, with the lowest time unit always being seconds (so 1:20 means 1min 20secs, 1:03:05 means 1hour 3mins 5secs). On every : there have to follow exactly two numbers. You don't have to enter all numbers. [class] is an optional parameter defining which timer class to use.";
		$this -> help['command']['rtimer [class] <dur>[mshd] <repeat>[mshd] title']="Adds a repeating timer for <dur> minutes (m), seconds (s), hours (h) or days (d). If no time unit is added it's <dur> seconds. <rep> is the time between repetitions of the timer, the same rules as for <dur> apply. [class] is an optional parameter defining which timer class to use.";
		$this -> help['command']['rtimer [class] <dur>[:##[:##[:##]]] <repeat>[:##[:##[:##]]] title']="Adds a timer using the format days:hours:minutes:seconds, with the lowest time unit always being seconds (so 1:20 means 1min 20secs, 1:03:05 means 1hour 3mins 5secs). On every : there have to follow exactly two numbers. You don't have to enter all numbers. <rep> is the time between repetitions of the timer, the same rules as for <dur> apply. [class] is an optional parameter defining which timer class to use.";
	}

	function command_handler($name, $msg, $channel)
	{
		$command = explode(" ", $msg, 2);
		Switch(strtolower($command[0]))
		{
			case 'timer':
				if (preg_match("/^timer ([a-z]+ )?([1-9][0-9]*[mshd]?) (.*)/i", $msg, $info))
				{
					return $this -> add_timer($name, $info[2], $info[3], $info[1], 0, $channel);
				}
				elseif (preg_match("/^timer ([a-z]+ )?([0-9]+(:[0-9][0-9]){0,3}) (.*)/i", $msg, $info))
				{
					return $this -> add_timer($name, $info[2], $info[4], $info[1], 0, $channel);
				}
				elseif (preg_match("/^timer$/i", $msg))
				{
					return $this -> show_timer($name, $channel);
				}
				else
				{
					return "Correct Format: ##highlight##<pre>timer [class] #[mshd] title##end## or ##highlight##<pre>timer [class] #[:##[:##[:##]]] title##end## [class] is an optional parameter";
				}
			case 'rtimer':
				if (preg_match("/^rtimer ([a-z]+ )?([1-9][0-9]*[mshd]?) ([1-9][0-9]*[mshd]?) (.*)/i", $msg, $info))
				{
					return $this -> add_timer($name, $info[2], $info[4], $info[1], $info[3], $channel);
				}
				elseif (preg_match("/^rtimer ([a-z]+ )?([0-9]+(:[0-9][0-9]){0,3}) ([0-9]+(:[0-9][0-9]){0,3}) (.*)/i", $msg, $info))
				{
					return $this -> add_timer($name, $info[2], $info[6], $info[1], $info[4], $channel);
				}
				else
				{
					return "Correct Format: ##highlight##<pre>rtimer [class] <dur>[mshd] <repeat>[mshd] title##end## or ##highlight##<pre>rtimer [class] <dur>[:##[:##[:##]]] <repeat>[:##[:##[:##]]] title##end## [class] is an optional parameter";
				}
			case 'remtimer':
				return $this -> rem_timer($name, $command[1]);
		}
	}

	function add_timer($owner, $timestr, $name, $class, $repeatstr, $channel)
	{
		$duration = $this -> bot -> core("time") -> parse_time($timestr);
		$repeat = $this -> bot -> core("time") -> parse_time($repeatstr);
		if ($repeat != 0 && $repeat < $this -> bot -> core("settings") -> get("Timer", "MinRepeatInterval"))
		{
			return "The repeat interval must be at least##highlight## " . $this -> bot -> core("settings") -> get("Timer", "MinRepeatInterval") . "##end## seconds!";
		}

		$this -> bot -> core("timer") -> add_timer(false, $owner, $duration, $name, $channel, $repeat, $class);

		$msg = "Timer ##highlight##" . $name . " ##end##with ##highlight##" . $this -> bot -> core("time") -> format_seconds($duration) . " ##end##runtime started!";
		if ($repeat > 0)
		{
			 $msg .= " The timer has a repeat interval of##highlight## " . $this -> bot -> core("time") -> format_seconds($repeat) . " ##end##";
		}
		return $msg;
	}

	function show_timer($name, $channel)
	{
		$channelstr = "channel = '" . $channel . "'";
		if ($this -> bot -> core("settings") -> get("timer", "global"))
		{
			$channelstr = "channel = 'global'";
		}
		elseif ($this -> bot -> core("settings") -> get("timer", "guestchannel") == "both" && ($channel == "pgmsg" || $channel == "gc"))
		{
			$channelstr = "(channel = 'both' OR channel = '" . $channel . "')";
		}

		$namestr = "";
		if ($channel == "tell")
		{
			$namestr = " AND owner = '" . $name . "'";
		}

		$timers = $this -> bot -> db -> select("SELECT * FROM #___timer WHERE " . $channelstr . $namestr . " ORDER BY endtime ASC", MYSQL_ASSOC);
		if (empty($timers))
		{
			return "No timers defined!";
		}

		$thistime = time();
		$listing = "";
		foreach ($timers as $timer)
		{
			$listing .= "\n##blob_text##Timer ##end##" . $timer['name'] . " ##blob_text##has ##end##";
			$listing .= $this -> bot -> core("time") -> format_seconds($timer['endtime'] - $thistime);
			$listing .= " ##blob_text##remaining";
			if ($timer['repeatinterval'] > 0)
			{
				$listing .= " and is repeated every ##end##" . $this -> bot -> core("time") -> format_seconds($timer['repeatinterval']) . "##blob_text##";
			}
			$listing .= ". Owner:##end## " . $timer['owner'] . " ";
			$listing .= $this -> bot -> core("tools") -> chatcmd("remtimer " . $timer['id'], "[DELETE]");
		}

		return $this -> bot -> core("tools") -> make_blob("Current timers", "##blob_title##Timers for <botname>:##end##\n" . $listing);
	}

	function rem_timer($name, $id)
	{
		$ret = $this -> bot -> core("timer") -> del_timer($name, $id, false);
		if ($ret['error'])
		{
			return $ret['errordesc'];
		}
		return $ret['content'];
	}
}
?>
