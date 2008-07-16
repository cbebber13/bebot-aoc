<?php

$preferences = new Preferences_GUI(&$bot);

/*
The Class itself...
*/
class Preferences_GUI extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		//Create access settings for this module
		$this -> register_command("all", "preferences", "MEMBER", array('default'=>'SUPERADMIN'));

		$this -> help['description'] = 'Player Preferences';
		$this -> help['command']['preferences'] = "Shows the preferences interface.";
		$this -> help['notes'] = 'When a default is changed all users who have not customised ';
		$this -> help['notes'].= 'that setting will also have their preferences changed.<br>';
		$this -> help['notes'].= 'When a default is changed from option A to option B and back again ';
		$this -> help['notes'].= 'users who had customised their preference to option B will be reset ';
		$this -> help['notes'].= 'and have option A as default again.';
	}

	function command_handler($name, $msg, $origin)
	{
		$com = $this->parse_com($msg, array('com', 'sub', 'module', 'preference', 'value'));
		switch($com['sub'])
		{
			case '':
				//No arguments
				return($this -> bot -> core("prefs") -> show_modules($name));
				break;
			case 'show':
				//Show a spesific preference
				switch($com['module'])
				{
					case '':
						//Show all modules
						return($this -> bot -> core('prefs') -> show_modules($name));
						break;
					default:
						//Show module spesific preferences
						return($this -> bot -> core('prefs') -> show_prefs($name, $com['module']));
						break;
				}
				break;
			case 'set':
				//Set a given value
				return($this -> bot -> core("prefs") -> change($name, $com['module'], $com['preference'], $com['value']));
				break;
			case 'default':
				//Set a default value
				return ($this -> bot -> core("prefs") -> change_default($name, $com['module'], $com['preference'], $com['value']));
				break;
			case 'reset':
				return($this -> bot -> core("prefs") -> reset($name, $com['module']));
				break;
			default:
				$this->error->set("Unknown command ##highlight##'{$com['sub']}'##end##");
				return($this->error);
		}
	}
}
?>