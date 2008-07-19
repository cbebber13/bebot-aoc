'<?php
/*
* Central Items Database v2.5, By Noer
* This module submits anonymous data of all items it sees in chat and makes it searchable.
* The module also features server and world first discoveries.
*
*/
$Itemsdb = new Itemsdb($bot);

class Itemsdb extends BaseActiveModule
{
	var $server = 'http://aocdb.lunevo.net/';

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_event("gmsg", "org");
		$this -> register_event("gmsg", "Trade");
		$this -> register_event("gmsg", "RegionAquilonia");
		$this -> register_event("gmsg", "RegionCimmeria");
		$this -> register_event("gmsg", "RegionStygia");
		$this -> register_event("gmsg", "Playfield");

		$this -> bot -> core("colors") -> define_scheme("items", "discover", "lightteal");

		$this -> register_command('all', 'items', 'GUEST');
		$this -> register_command('all', 'item', 'GUEST');
		$this -> register_command('all', 'itemreg', 'GUEST');

		$this -> help['description'] = 'Searches the database for information about an item.';
		$this -> help['command']['items <text>']="Searches for items with the <text> in name.";
		$this -> help['command']['item <id>']="Displays information about a given item with a given <id>.";
		$this -> help['command']['itemreg <item ref>']="Submits the item(s) to the central item database. Several references can be send in same submit.";

		$this -> bot -> core("settings") -> create("Items", "Autosubmit", TRUE, "Automatically submit new items the bot sees to central database?");
		$this -> bot -> core("settings") -> create("Items", "Itemaware", TRUE, "Notify if a user discovers an item as first on server or in world?");
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match('/^items/i', $msg, $info)) {
			$words = trim(substr($msg, strlen('items')));
			if (!empty($words)) {
				$url  = $this->server."botsearch/";
				$url .= '?search='.urlencode($words);
				$url .= '&botname='.$this->bot->botname;
				$url .= '&pre='.urlencode($this -> bot -> commpre);
				$result = $this -> bot -> core("tools") -> get_site($url, 1);
				if (!empty($result["content"]))
					return $result["content"];
				else
					return "Error in query to database";
			} else {
				return "Usage: items [text]";
			}
		} elseif (preg_match('/^itemreg/i', $msg, $info)) {
			$words = trim(substr($msg, strlen('item')));
			if (!empty($words))
			{
				$this->submit($words,$origin,$name);
			} else {
				return "Usage: itemreg [itemref]";
			}
		} elseif (preg_match('/^item/i', $msg, $info)) {
			$words = trim(substr($msg, strlen('item')));
			if (!empty($words) && (is_numeric($words)))
			{
				$url  = $this->server."botsearch/";
				$url .= '?single=1';
				$url .= '&id='.$words;
				$result = $this -> bot -> core("tools") -> get_site($url, 1);
				if(strstr($result['content'], 'mysql_real_escape_string')!==false)
					return("Recieved garbled reply from vhabot!");
				if (!empty($result["content"]))
				{
					return $result["content"];
				}
				else
				{
					return "Error in query to database";
				}
			} else {
				return "Usage: \"<pre>item [id]\". To search for an item use <pre>!items.";
			}
		} else {
			$this -> bot -> send_help($name);
		}
	}

	/*
	This gets called on a msg in the group
	*/
	function gmsg($name, $group, $msg)
	{
		if ($this -> bot -> core("settings") -> get("Items", "Autosubmit"))
                {
			$this->submit($msg, $group, $name);
		}
	}

	/*
	Autosubmits a link.
	*/
	function submit($msg, $group = "tell", $name)
	{
		if (preg_match_all("/(<a style=\"text-decoration:none\" href=\"itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\/([0-9a-f]*\:[0-9a-f]*\:[0-9a-f]*:[0-9a-f]*)\/([0-9a-f]*\:[0-9a-f]*\:[0-9a-f]*:[0-9a-f]*)\"><font color=#([0-9a-f]*)>\[([a-zA-Z0-9_'&\s]*)\]<\/font><\/a>)/i",$msg,$matches,PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$lowid          = $match[2];
				$highid         = $match[3];
				$ql             = $match[4];
				$lowcrc         = $match[5];
				$highcrc        = $match[6];
				$color		= $match[7];
				$itemname       = $match[8];
				$checksum       = md5('aocitems' + $lowid + $highid + $ql + $lowcrc + highcrc + $color + $itemname + $this -> bot -> dimension + $this -> bot -> guild + $name);

				$url  = $this->server."botsubmit/v3/";
				$url .= '?lowid='.urlencode($lowid);
				$url .= '&highid='.urlencode($highid);
				$url .= '&ql='.urlencode($ql);
				$url .= '&lowcrc='.urlencode($lowcrc);
				$url .= '&highcrc='.urlencode($highcrc);
				$url .= '&color='.urlencode($color);
				$url .= '&name='.urlencode($itemname);
				$url .= '&server='.urlencode($this -> bot -> dimension);
				$url .= '&guildname='.urlencode($this -> bot -> guild);
				$url .= '&username='.urlencode($name);
				$url .= '&checksum='.urlencode($checksum);
				$result = $this -> bot -> core("tools") -> get_site($url, 1);

				if ($group == "org")
				{
					$group = "gc";
				}

				if (($result['content'] == "0") && ($group == "tell"))
				{
					$output = "Thank you for submitting the item, however the item was already discovered by others.";
					$this -> bot -> send_output($name, $output, $group);
				}
				elseif (($result['content'] == "1") && (($group == "gc" || $group == "tell")) && ($this -> bot -> core("settings") -> get("Items", "Itemaware") ))
				{
					$output = "Congratulations!! You are the ##items_discover##world's first##end## to discover ".$match[1]."!";
					$this -> bot -> send_output($name, $output, $group);
				}
				elseif (($result['content'] == "2") && (($group == "gc" || $group == "tell")) && ($this -> bot -> core("settings") -> get("Items", "Itemaware")))
				{
					$output = "Congratulations!! You are the ##items_discover##first on this server##end## to discover this ".$match[1]."!";
					$this -> bot -> send_output($name, $output, $group);
				}
			}
		}
		elseif ($group == "tell")
		{
			$output = "There is no item reference in your item registration.";
			$this -> bot -> send_output($name, $output, $group);
		}
	}

}
?>
