<?php
/*
* Say.php - Makes the bot say things. :-)
*  Good example of using access control for security.
*  Example of using StringFilter functions.
*
* Say module by Andrew Zbikowski <andyzib@gmail.com> (AKA Glarawyn, RK1)
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
* Revision: $Id: Say.php 1554 2008-05-11 23:15:50Z temar $
*/

$say = new Say($bot);

/*
The Class itself...
*/
class Say extends BaseActiveModule
{ // Start Class
	var $whosaidthat;

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> whosaidthat = array();

		// Setup Access Control
		$this -> register_command("all", "say", "ADMIN");
		$this -> register_command("all", "whosaidthat", "MEMBER");

		$this -> help['description'] = 'Makes the bot say things.';
		$this -> help['command']['say something'] = "Makes that bot say 'something'";
		$this -> help['command']['whosaidthat'] = "Find out who made the bot say that.";
	}

	/*
	This function handles all the inputs and returns FALSE if the
	handler should not send output, otherwise returns a string
	sutible for output via send_tell, send_pgroup, and send_gc.
	*/
	function command_handler($name, $msg, $source)
	{ // Start function handler()
		$filters = "rot13|chef|eleet|fudd|pirate|nofont";
		if (preg_match("/^say (".$filters.") (.+)$/i", $msg, $info))
			return $this -> saythis($name, $info[2], $info[1]);
		else if (preg_match("/^say (.+)$/i", $msg, $info))
			return $this -> saythis($name, $info[1]);
		else if (preg_match("/^whosaidthat$/i", $msg))
			return $this -> whosaidthat();
		else
			$this -> bot -> send_help($name);
	} // End function handler()

	/*
	Determine output channel.
	If user is guest, output to pgroup.
	If user is a member:
		Output to pgroup if they are in pgroup.
		Output to gc if they are not in pgroup.
	*/
	function saythis($name, $message, $filter="off")
	{
		$filter = strtolower($filter);
		$this -> whosaidthat['time'] = time();
		$this -> whosaidthat['name'] = $name;
		$this -> whosaidthat['what'] = $message;
		// Determine output channel(s).
		$access = $this -> bot -> core("security") -> get_access_level($name);
		$sql = "SELECT status_gc, status_pg FROM #___online WHERE nickname = '".$name."' AND botname = '".$this -> bot -> botname."'";
		$result = $this -> bot -> db -> select($sql, MYSQL_ASSOC);
		//	if user isn't online, don't do anything...cause it's some sort of but that it got this far. =)
		// | nickname     | botname   | status_gc | status_gc_changetime | status_pg | status_pg_changetime |
		if (empty($result))
		{
			return FALSE;
		}
		if ($access >= GUEST && $result[0]['status_pg'] == 1 && $result[0]['status_gc'] == 0)
		{
			$output = "pgroup";
		}
		else if ($access >= MEMBER && $result[0]['status_pg'] == 1 && $result[0]['status_gc'] == 1)
		{
			$output = "both";
		}
		else if ($access >= MEMBER && $result[0]['status_pg'] == 0 && $result[0]['status_gc'] == 1)
		{
			$output = "gc";
		}
		else
		{
			$output = "tell";
		}

		// Run through the requested text filter.
		if ($filter <> "off")
		{
			$message = $this -> bot -> core("stringfilter") -> funmode($message, $filter);
		}

		$this -> bot -> send_output($name, $message, $output);
		return FALSE;
	}

	function whosaidthat()
	{
		if (empty($this -> whosaidthat))
		{
			$output = "Nobody has used the say command since I logged in.";
		}
		else
		{
			$output = $this -> whosaidthat['name'];
			$output .= ' made me say "';
			$output .= $this -> whosaidthat['what'];
			$output .= '" ';
			$output .= time()-$this -> whosaidthat['time'];
			$output .= ' seconds ago.';
		}
		return $output;
	}

} // End of Class
?>