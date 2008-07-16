<?php
/*
* Roll.php - Rolls a number and flips a coin.
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
* Revision: $Id: Roll.php 1554 2008-05-11 23:15:50Z temar $
*/

$roll = new Roll($bot);

/*
The Class itself...
*/
class Roll extends BaseActiveModule
{
	var $bot;
	var $verify;
	var $lastroll;



	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> verify = array();

		$this -> register_command('all', 'roll', 'GUEST');
		$this -> register_command('all', 'flip', 'GUEST');
		$this -> register_command('tell', 'verify', 'ANONYMOUS');

		$this -> help['description'] = 'Throws a dice and shows the result.';
		$this -> help['command']['roll <limit>']="Rolls a number between 1 and <limit> and shows the result.";
		$this -> help['command']['flip'] = "Flips a coin and shows the result.";
		$this -> help['command']['verify <num>'] = "Shows the result of roll <num>";

		$this -> bot -> core("settings") -> create("Roll", "RollTime", 30, "How many seconds must someone wait before they can roll again?", "5;10;20;30;45;60;120;300;600");
	}



	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^roll ([0-9]{1,4})$/i", $msg, $info))
		$this -> bot -> send_output($name, $this -> do_roll($name, $info[1]), $origin);
		else if (preg_match("/^flip$/i", $msg, $info))
		$this -> bot -> send_output($name, $this -> do_flip($name), $origin);
		else if (preg_match("/^verify ([0-9]+)$/i", $msg, $info))
		$this -> bot -> send_output($name, $this -> verify($info[1]), $origin);
		else
		$this -> bot -> send_help($name);
	}

	/*
	Verifys result
	*/
	function verify($num)
	{
		if ($num >= count($this -> verify))
		return "I only have " . count($this -> verify) . " rolls with verification.";
		else
		return "<font color=#ffff00>" . (time() - $this -> verify[$num]["time"]) .
		"</font> seconds ago " . $this -> verify[$num]["msg"];
	}



	/*
	Starts the roll
	*/
	function do_roll($name, $num)
	{
		if ($num == 0)
		$num = 1;
		if (!isset($this -> lastroll[$name]) || ($this -> lastroll[$name] < time() - $this -> bot -> core("settings") -> get("Roll", "RollTime")))
		{
			$rand_num = rand(1, $num);
			$ver_num = count($this -> verify);
			$this -> lastroll[$name] = time();
			$this -> verify[$ver_num]["time"] = time();
			$this -> verify[$ver_num]["msg"] = "I told <font color=#ffff00>" . $name . "</font> from 1 to " .
			$num . " I rolled <font color=#ffff00>" . $rand_num . "</font>.";
			return "From 1 to " . $num . " I rolled <font color=#ffff00>" . $rand_num .
			"</font>, to verify do /tell <botname> <pre>verify " . $ver_num;
		}
		else
		return "You may only roll once every " . $this -> bot -> core("settings") -> get("Roll", "RollTime") . " seconds.";
	}



	/*
	Starts the flip
	*/
	function do_flip($name)
	{
		if (!isset($this -> lastroll[$name]) || ($this -> lastroll[$name] < time() - $this -> bot -> core("settings") -> get("Roll", "RollTime")))
		{
			$rand_num = ((rand(1, 2) == 1) ? "heads" : "tails");
			$ver_num = count($this -> verify);
			$this -> lastroll[$name] = time();
			$this -> verify[$ver_num]["time"] = time();
			$this -> verify[$ver_num]["msg"] = "I told <font color=#ffff00>" . $name . "</font> that the coin landed <font color=#ffff00>" . $rand_num . "</font>.";
			return "The coin landed <font color=#ffff00>" . $rand_num . "</font>, to verify do /tell " .
			"<botname> <pre>verify " . $ver_num;
		}
		else
		return "You may only flip once every " . $this -> bot -> core("settings") -> get("Roll", "RollTime") . " seconds.";
	}
}
?>
