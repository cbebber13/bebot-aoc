<?php
/*
* Maintenance.php - Database Maintenance module.
*
* BeBot - An Anarchy Online Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stens�s, ShadowRealm Creations and the BeBot development team.
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
* Revision: $Id: AltsUI.php 1554 2008-05-11 23:15:50Z temar $
*/

$maintenance = new Maintenance($bot);

/*
The Class itself...
*/
class Maintenance extends BaseActiveModule
{
	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command("all", "maintenance", "OWNER");

		$this -> register_event("cron", "5sec");
		$this -> register_event("connect");

		// TODO: help
		
	}

	function command_handler($name, $msg, $origin)
	{
		$msg = strtolower($msg);
		$vars = explode(" ", $msg, 4);

		switch($vars[0])
		{
			case 'maintenance':
				if(!empty($vars[1]))
				{
					if(!empty($vars[2]))
					{
						Switch($vars[2])
						{
							case 1:
							case 'start':
								return $this -> step1($name, $vars[1], $origin);
							case 'dont':
								return $this -> dontdo($name, $vars[3], $origin);
							case 'check':
							case 'refresh':
								$inside = $this -> check($this -> old_data, $this -> new_data, $this -> compare);
								Return ("Maintenance ToDo list: ".$this -> bot -> core("tools") -> make_blob("Click to view", $inside));
							case 'done':
								Return $this -> step3($vars[1]);
							Default:
								Return("Error: Unknown Action ".$vars[2]);
								
								
						}
					}
				}
				else
					return $this -> main($name, $origin);
			default:
				return "Broken plugin, recieved unhandled command: $command in Maintenance.php";
		}
	}

	function connect()
	{
		$table = $this -> bot -> db -> select("SHOW TABLES LIKE '#___settings_maintenance'");
		if(!empty($table))
		{
			$rostermod = $this -> bot -> core("roster_core");
			$this -> bot -> unregister_event("cron", "24hour", &$rostermod);
		}
	}

	function cron()
	{
		$this -> croncount++;
		Switch($this -> croncount)
		{
			case 1: // Skip first cron just to make sure bot if fully loaded.
				Break;
			case 2:
				$table = $this -> bot -> db -> select("SHOW TABLES LIKE '#___settings_maintenance'");
				if(!empty($table))
				{
					$info = $this -> bot -> db -> select("SELECT value FROM #___settings_maintenance WHERE module = 'Maintenance' AND setting = 'info'");
					$info = explode(" ", $info[0][0]);
					Switch($info[0])
					{
						case 'settings':
							Switch($info[3])
							{
								case 2:
									$this -> step2($info[1], "settings", $info[2]);
									Break;
								//case 3:
								//	$this -> step3($info[1], "settings", $info[2]);
								//	Break;
								Default:
									$this -> bot -> send_output($info[1], "Error: Unknown Step number: ".$info[3], $info[2]);
							}
							Break;
						Default:
							$this -> bot -> send_output($info[1], "Error: Unknown Mode: ".$info[0], $info[2]);
					}
				}
			Default:
				$this -> unregister_event("cron", "5sec");
		}
	}

	function main($origin)
	{
		$table = $this -> bot -> db -> select("SHOW TABLES LIKE '#___settings_maintenance'");
		if(!empty($table))
		{
			$inside = "##blob_title##     Maintenance Screen - settings##end##\n\n";
			$inside .= "##blob_text##".$this -> bot -> core("tools") -> chatcmd("maintenance settings check", "Refresh ToDo list", $origin)."\n";
			$inside .= $this -> bot -> core("tools") -> chatcmd("maintenance settings done", "Run Settings Maintenance", $origin)."\n##end##";
			Return ("Maintenance Control Panel :: ".$this -> bot -> core("tools") -> make_blob("Click to view", $inside));
		}
		else
		{
			$inside = "##blob_title##     Maintenance Main Screen##end##\n\n";
			$inside .= "##blob_text##".$this -> bot -> core("tools") -> chatcmd("maintenance settings start", "Settings", $origin)." (will restart)\n##end##";
			Return ("Maintenance Control Panel :: ".$this -> bot -> core("tools") -> make_blob("Click to view", $inside));
		}
	}

	function step1($name, $mode, $origin)
	{
		$mode = strtolower($mode);
		Switch($mode)
		{
			case 'settings':
				$this -> bot -> core("settings") -> create("Maintenance", "info", "settings $name $origin 2", "");
				$this -> bot -> db -> query("RENAME TABLE #___settings TO #___settings_maintenance");
				$this -> bot -> send_output("", "Restarting for Maintenance", "both");
				$this -> bot -> disconnect();
				die("Restarting for Maintinance");
			Default:
				Return("Error Unknown Maintenance mode: $mode");
		}
	}

	function step2($name, $mode, $origin)
	{
		$mode = strtolower($mode);
		Switch($mode)
		{
			case 'settings':
				$olddata = $this -> bot -> db -> select("SELECT module, setting, datatype, longdesc, defaultoptions, hidden, disporder FROM #___settings_maintenance");
				$newdata = $this -> bot -> db -> select("SELECT module, setting, datatype, longdesc, defaultoptions, hidden, disporder FROM #___settings");
				if(!empty($olddata) && !empty($newdata))
				{
					foreach($olddata as $o)
					{
						$this -> old_data[strtolower($o[0])][strtolower($o[1])] = array($o[2], $o[3], $o[4], $o[5], $o[6]);
					}
					unset($this -> old_data["maintenance"]["info"]);
					foreach($newdata as $n)
					{
						$this -> new_data[strtolower($n[0])][strtolower($n[1])] = array($n[2], $n[3], $n[4], $n[5], $n[6]);
					}
					$this -> compare = array("datatype", "longdesc", "defaultoptions", "hidden", "disporder");
					$inside = $this -> check($this -> old_data, $this -> new_data, $this -> compare);
					$this -> bot -> send_output($name, "Maintenance ToDo list: ".$this -> bot -> core("tools") -> make_blob("Click to view", $inside), $origin);
				}
				else
					$this -> bot -> send_output($name, "Error: Old or New Table is Empty or doesnt Exist", $origin);
				Break;
			Default:
				Return("Error Unknown Maintenance mode: $mode");
		}
	}

	function check($old, $new, $compare)
	{
		$this -> del = array();
		$this -> update = array();
		foreach($old as $mod => $data)
		{
			foreach($data as $set => $value)
			{
				if(!isset($new[strtolower($mod)][strtolower($set)]))
				{
					$this -> del[] = array($mod, $set);
				}
				else
				{
					if($compare)
					{
						foreach($compare as $id => $name)
						{
							if(!isset($this -> dontupdate[$mod][$set][$name]) && $value[$id] != $new[strtolower($mod)][strtolower($set)][$id])
								$this -> update[] = array($mod, $set, $name, $value[$id], $new[strtolower($mod)][strtolower($set)][$id]);
						}
					}
				}
			}
		}
		$inside = "title...\n\n";
		$inside .= " ::: ".$this -> bot -> core("tools") -> chatcmd("maintenance settings check", "refresh")." ::: ".$this -> bot -> core("tools") -> chatcmd("maintenance settings done", "done")." ::: \n\n";
		$inside .= "Delete:\n";
		if(!empty($this -> del))
		{
			foreach($this -> del as $d)
			{
				$inside .= "    ".$d[0]." => ".$d[1]."  ".$this -> bot -> core("tools") -> chatcmd("maintenance settings dont del ".$d[0]." ".$d[1], "[Dont Delete]")."\n";
			}
		}
		$inside .= "\nChange:\n";
		if(!empty($this -> update))
		{
			foreach($this -> update as $u)
			{
				$inside .= $u[0]." => ".$u[1].":\n    ".$u[2].": ".$u[3]." => ".$u[4]."  ".$this -> bot -> core("tools") -> chatcmd("maintenance settings dont update ".$u[0]." ".$u[1]." ".$u[2], "[Dont Change]")."\n";
			}
		}
		$inside .= "\n ::: ".$this -> bot -> core("tools") -> chatcmd("maintenance settings check", "refresh")." ::: ".$this -> bot -> core("tools") -> chatcmd("maintenance settings done", "done")." ::: ";
		Return $inside;
	}

	function dontdo($name, $msg, $origin)
	{
		$msg = explode(" ", $msg, 4);
		Switch($msg[0])
		{
			case 'del':
				if(isset($this -> old_data[$msg[1]][$msg[2]]))
				{
					unset($this -> old_data[$msg[1]][$msg[2]]);
					Return ($msg[1]." => ".$msg[2]." Will not be Deleted.");
				}
				else
					Return ("Error: Setting ".$msg[1]." => ".$msg[2]." Not found");
			case 'update':
				if(isset($this -> old_data[$msg[1]][$msg[2]]))
				{
					$compare = array_flip($this -> compare);
					if(isset($compare[$msg[3]]))
					{
						$this -> dontupdate[$msg[1]][$msg[2]][$msg[3]] = TRUE;
						Return ($msg[1]." => ".$msg[2]." => ".$msg[3]." Will not be Changed.");
					}
					else
						Return ("Error: Field ".$msg[3]." Not found");
				}
				else
					Return ("Error: Setting ".$msg[1]." => ".$msg[2]." Not found");
			Default:
				Return("Error: Unknown Action (Valid: del, update)");
		}
	}

	function step3($mode)
	{
		$mode = strtolower($mode);
		Switch($mode)
		{
			case 'settings':
				$table = $this -> bot -> db -> select("SHOW TABLES LIKE '#___settings_maintenance'");
				if(!empty($table))
				{
					$this -> check($this -> old_data, $this -> new_data, $this -> compare);
					if(!empty($this -> del))
					{
						foreach($this -> del as $del)
						{
							$this -> bot -> db -> query("DELETE FROM #___settings_maintenance WHERE module = '".$del[0]."' AND setting = '".$del[1]."'");
						}
					}
					if(!empty($this -> update))
					{
						foreach($this -> update as $up)
						{
							$this -> bot -> db -> query("UPDATE #___settings_maintenance SET ".$up[2]." = '".$up[4]."' WHERE module = '".$up[0]."' AND setting = '".$up[1]."'");
						}
					}
					$this -> bot -> db -> query("DELETE FROM #___settings_maintenance WHERE module = 'Maintenance' AND setting = 'info'");
					$this -> bot -> db -> query("DROP TABLE #___settings");
					$this -> bot -> db -> query("RENAME TABLE #___settings_maintenance TO #___settings");
					$this -> bot -> send_output("", "Maintenance Complete", "both");
					$this -> bot -> core("settings") -> load_all();
					Return FALSE;
				}
				else
					Return("Error: Maintenance Table for Settings Not Found. Aborting.");
				Break;
			Default:
				Return("Error Unknown Maintenance mode: $mode");
		}
	}

}
