'<?php
/*
* Autouseradd v1.0, By Noer
* This module automatically adds new users it sees chat on the guildchat to the user database.
*
*
*/
$Autouseradd = new Autouseradd($bot);

class Autouseradd extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		
		$this -> register_event("gmsg", "org");

	}

	function command_handler($name, $msg, $origin)
	{
	}

	/*
	This gets called on a msg in the group
	*/
	function gmsg($name, $group, $msg)
	{
		$userlevel = $this -> bot -> core("security") -> get_access_level($name);
		if ($userlevel < 2)
		{
			$this -> bot -> core("user") -> add ($this -> bot -> botname, $name, 0, MEMBER, 0, 0);
		}
	}

}
?>
