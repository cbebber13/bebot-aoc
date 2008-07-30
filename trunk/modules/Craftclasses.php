<?php
///////////////////////////////////
// craftclasses.php 1.1 for BeBot
///////////////////////////////////
// (c) Copyright 2008 by Allan Noer
// All Rights Reserved
// Licensed for distribution under the GPL (Gnu General Public License) version 2.0 or later
///////////////////////////////////
// Updated to 1.1 by Buffarse - Added additional parsing and user feedback to !setclass
//

$craftclasses = new craftclasses($bot);

//////////////////////////////////////////////////////////////////////
// The Class itself...
class craftclasses Extends BaseActiveModule
{
	var $bot;
	var $help;

	var $last_log;
	var $start;
	
	// Constructor
	function __construct (&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS ".$this -> bot -> db -> define_tablename("craftingclass", "false")." (
			id int(11) NOT NULL auto_increment,
			name varchar(32) NOT NULL,
			class1 enum('Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith') NOT NULL,
			class2 enum('Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith') NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY name (name)
			);
		");

		$this -> last_log = array();
		$this -> start = time() + 3600;

		$this -> output = "group";
		$this -> result = "";

		$this -> register_event("buddy");
		$this -> register_event("connect");
		$this -> register_command("all", "setcraft", "MEMBER");
		$this -> register_command("all", "craft", "MEMBER");
		$this -> help['description'] = 'Used to set the crafting classes on a user.';
		$this -> help['command']['setcraft [class1] [class2]']="Sets the two crafting classes for you. Classes can be Alchemist, Architect, Armorsmith, Gemcutter and Weaponsmith";
		$this -> help['command']['craft']="Shows the classes you currently have assigned to you.";

		$this -> bot -> core("settings") -> create("Craftclasses", "Remind", TRUE, "Should users level 40+ be reminded to set their craft classes?");
	}
	
	function command_handler($name, $msg, $origin)
	{
		$output = "";
		if (preg_match("/^setcraft (.+)$/i", $msg, $info))
		{
			$options_str = $info[1];
			$options = array();
			$options = split(" ", $options_str);
			$craftclass = array("Alchemist", "Architect", "Armorsmith", "Gemcutter", "Weaponsmith");
			$options[0] = ucwords(strtolower($options[0]));
			$options[1] = ucwords(strtolower($options[1]));
			if ( empty($options[0]) || empty($options[1]) )
			{
			$output = "You MUST set both craft classes at the same time.";
			}
			elseif
			((array_search ($options[0], $craftclass) !== false) && (array_search ($options[1], $craftclass) !== false))
			{
			$this -> bot -> db -> query('INSERT INTO #___craftingclass (name,class1,class2) VALUES("'.$name.'","'.$options[0].'","'.$options[1].'") ON DUPLICATE KEY UPDATE class1=values(class1), class2=values(class2)');
			$output = "Thank you for updating your crafting information.";
			}
			else
			{
			$output = "Classes can ONLY be Alchemist, Architect, Armorsmith, Gemcutter and Weaponsmith and you MUST set both at the same time.";
			}
		}

		elseif (preg_match("/^craft$/i", $msg, $info))
		{
			$lookup = $this -> bot -> db -> select("SELECT * FROM #___craftingclass WHERE name = '" . $name . "'", MYSQL_ASSOC);
			if (!empty($lookup))
			{
				$output = "Your crafting classes are: ".$lookup[0]['class1']." and ".$lookup[0]['class2'];
			}
			else
			{
				$output = "You have no crafting information set. Please use '!setcraft [class1] [class2]'. Classes can be Alchemist, Architect, Armorsmith, Gemcutter and Weaponsmith.";
			}
		}
		return $output;
	}

	function buddy($name, $online, $level, $location)
	{
		if ($this -> bot -> core("notify") -> check($name) && $this -> bot -> core("settings") -> get("Craftclasses", "Remind"))
		{			
			$id = $this -> bot -> core("chat") -> get_uid($name);
			if ($msg == 1)
			{
				if ($this -> last_log["on"][$name] < (time() - 5))
				{
					$result = $this -> bot -> core("whois") -> lookup($name);
					if (empty($result["craft1"]) & $result["level"] > 40)
					{
						$msg = "You have no crafting information set and you are above level 40. Please use '!setcraft [class1] [class2]'. Classes can be Alchemist, Architect, Armorsmith, Gemcutter and Weaponsmith. If you havn't picked crafting classes yet this may be the time to do it.";
						$this -> bot -> send_tell($name, $msg);
					}
					$this -> last_log["on"][$name] = time();
				}
			}
			else
			{
				if ($this -> last_log["off"][$name] < (time() - 5))
				{
					$this -> last_log["off"][$name] = time();
				}
			}
		}
	}

	function connect()
	{
		$this -> start = time() + 3 * $this -> bot -> crondelay;
	}

}

?>
