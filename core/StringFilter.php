<?php
/*
*
* StringFilter.php - Performs some common text filtering.
* Can be used as a word censor. This functionality is not enabled by default.
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
* Revision: $Id: StringFilter.php 1554 2008-05-11 23:15:50Z temar $
*/

$stringfilter_core = new stringfilter_core($bot);

/*
The Class itself...
*/
class stringfilter_core extends BasePassiveModule
{ // Start Class
	var $stringlist;
	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		// Create Table
		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS ".$this -> bot -> db -> define_tablename("string_filter", "true")."
			(search varchar(255) NOT NULL,
				new VARCHAR(255) NOT NULL DEFAULT '**bleep**',
				PRIMARY KEY (search))");

		$this -> register_module("stringfilter");
		$this -> register_event("connect");

		$this -> stringlist = array();
		$this -> bot -> core("settings") -> create ("Filter", "Enabled", FALSE, "Enable bot output text filter.", "On;Off", FALSE, 1);
		$this -> bot -> core("settings") -> create ("Filter", "Funmode", "off", "Select a fun bot output filter. (See documentation)", "off;chef;eleet;fudd;pirate;nofont;", FALSE, 10);
	}

	/*
	This gets called when bot connects
	*/
	function connect()
	{ // Start function connect()
		$this -> get_strings(TRUE);
	} // End function connect()

	function output_filter($text)
	{ // Start function output_filter()
		foreach ($this -> stringlist as $search => $new)
		{
			$text = preg_replace("/".$search."/i", $new, $text);
			// $text = str_ireplace($search, $new, $text); // str_ireplace is php5+
		}
		if ($this -> bot -> core("settings") -> get("Filter", "Funmode") <> "off")
		{
			echo "\nCaling funmode()!\n";
			$text = $this -> funmode($text, $this -> bot -> core("settings") -> get("Filter", "Funmode"));
		}
		return $text;
	} // End function output_filter()

	/*
	This function can be used to filter input against the string list.
	What else could we do for input filtering?
	*/
	function input_filter($text)
	{ // Start function input_filter()
		foreach ($this -> stringlist as $search => $new)
		{
			$text = preg_replace("/".stripslashes($search)."/i", stripslashes($new), $text);
			// $text = str_ireplace($search, $new, $text); // str_ireplace is php5+
		}
		return $text;
	} // End function input_filter()

	/*
	Gets the filterd string list from the database.
	If update is true, the array is refreshed from the database.
	*/
	function get_strings($update=FALSE)
	{ // Start function get_strings()
		if ($update)
		{
			$sql = "SELECT * FROM #___string_filter";
			$result = $this -> bot -> db -> select($sql, MYSQL_ASSOC);
			if (empty($result))
			{
				return FALSE;
			}
			else
			{
				foreach ($result as $info)
				{
					$this -> stringlist[$info["search"]] = $info["new"];
				}
				unset ($result);
			}
		}
		return $this -> stringlist;
	} // End function get_strings()

	/*
	Adds a string to the filtered string list.
	*/
	function add_string($search, $new=NULL)
	{ // Start function add_string()
		$tmp['error'] = FALSE;
		$tmp['errordesc'] = "";
		$tmp['content'] = "";
		$search = mysql_real_escape_string(strtolower($search));
		if (isset($this -> stringlist[$search]))
		{
			$tmp['error'] = TRUE;
			$tmp['errordesc'] = "The string '".$search."' is already on the filtered word list.";
			return $tmp;
		}
		if (!is_null($new))
		{
			$new = mysql_real_escape_string(strtolower($new));
			$sql = "INSERT INTO #___string_filter (search, new) VALUES ('".$search."', '".$new."')";
		}
		else
		{
			$sql = "INSERT INTO #___string_filter (search) VALUES ('".$search."')";
			$new = "**bleep**";
		}
		$this -> bot -> db -> query($sql);
		$this -> stringlist[$search] = $new;
		$tmp['content'] = "Added '".$search."' to the filterd string list. It will be replaced with '".$new."'";
		return $tmp;
	} // End function add_string()

	function rem_string($search)
	{ // Start function rem_string()
		$tmp['error'] = FALSE;
		$tmp['errordesc'] = "";
		$tmp['content'] = "";
		$search = mysql_real_escape_string(strtolower($search));
		if (isset($this -> stringlist[$search]))
		{
			unset($this -> stringlist[$search]);
			$sql = "DELETE FROM #___string_filter WHERE search = '".$search."'";
			$this -> bot -> db -> query($sql);
			$tmp['content'] = "Removed ".$search." from the filtered string list.";
		}
		else
		{
			$tmp['error'] = TRUE;
			$tmp['errordesc'] = $search." is not on the filtered string list.";
		}
		return $tmp;
	} // End function rem_string()

	/*
	Returns garbled text. ;-)
	*/
	function funmode($text, $filter)
	{ // Start function funmode()
		$filter = strtolower($filter);
		switch ($filter)
		{
			case "rot13":
				return $this -> bot -> core("funfilters") -> rot13($text);
			break;
			case "chef":
				return $this -> bot -> core("funfilters") -> chef($text);
			break;
			case "eleet":
				return $this -> bot -> core("funfilters") -> eleet($text);
			break;
			case "fudd":
				return $this -> bot -> core("funfilters") -> fudd($text);
			break;
			case "pirate":
				return $this -> bot -> core("funfilters") -> pirate($text);
			break;
			case "nofont":
				return $this -> bot -> core("funfilters") -> nofont($text);
			break;
			default:
				$this -> bot -> log("FILTER", "ERROR", $filter." is not a valid fun mode.");
				return $text;
			break;
		}
	} // End function funmode()

} // End of Class
?>
