<?php
/*
* Shutdown.php - Shuts bot down and restarts it.
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
* Revision: $Id: Shutdown.php 1554 2008-05-11 23:15:50Z temar $
*/

$sdrs = new Shutdown($bot);

/*
The Class itself...
*/
class Shutdown extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
	
		$this -> register_command("tell", "shutdown", "SUPERADMIN");	
		$this -> register_command("tell", "restart", "SUPERADMIN");

		$this -> help['description'] = 'Handles bot shut down and restart..';
		$this -> help['command']['shutdown']="Shuts down the bot.";
		$this -> help['command']['restart'] = "Restarts the bot.";
		$this -> help['notes'] = "If the bot is started in debug mode input _might_ be required in the console for the bot to restart.";
	}



	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^shutdown$/i", $msg))
		{
			$this -> stop($name, "has been shutdown.");
		}
		else if (preg_match("/^restart$/i", $msg))
		{
			$this -> stop($name, "is restarting.");
		}
		return false;
	}

	function stop($name, $text)
	{
		$this -> bot -> send_irc("", "", "The bot " . $text);
		$this -> bot -> send_gc("The bot " . $text);
		$this -> bot -> send_pgroup("The bot " . $text);
		$this -> bot -> send_tell($name, "The bot " . $text);

		$this -> crontime = array (time() + 2, "The bot " . $text);
		$this -> register_event("cron", "1sec");
	}

	function cron()
	{
		if ($this -> crontime[0] <= time())
		{
			$this -> bot -> disconnect();
			die("". $this -> crontime[1] . "\n");
		}
	}
}
?>
