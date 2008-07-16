<?php
/*
* Colors.php - central color storage and retrieving
*
* Written by Alreadythere
* Copyright (C) 2006 Christian Plog
*
* colorize() by Wolfbiter
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
* File last changed at $LastChangedDate: 2008-05-16 07:12:55 +0100 (Fri, 16 May 2008) $
* Revision: $Id: 15_Colors.php 1572 2008-05-16 06:12:55Z temar $
*/

$colors_core = new Colors_Core($bot);



/*
The Class itself...
*/
class Colors_Core extends BasePassiveModule
{
	private $no_tags;
	private $color_tags;
	private $theme_info;
	private $theme;

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_module("colors");
		$this -> register_event("cron", "1hour");

		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("colors", "false")
		. " (
						name varchar(25) NOT NULL default '',
						code varchar(25) NOT NULL default '',
						PRIMARY KEY  (name)
					)");

		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("color_schemes", "true")
		. " (
						module varchar(25) NOT NULL default '',
						name varchar(25) NOT NULL default '',
						color_code varchar(25) NOT NULL default '',
						PRIMARY KEY (module, name)
					)");

		$this -> startup = TRUE;

		$this -> bot -> core("settings") -> create("Color", "Theme", "Default", "What is the name of the theme file to use?", "", TRUE);

		$this -> define_color("aqua", "#00FFFF");
		$this -> define_color("beige", "#FFE3A1");
		$this -> define_color("black", "#000000");
		$this -> define_color("blue", "#0000FF");
		$this -> define_color("bluegray", "#8CB6FF");
		$this -> define_color("bluesilver", "#9AD5D9");
		$this -> define_color("brown", "#999926");
		$this -> define_color("darkaqua", "#2299FF");
		$this -> define_color("darklime", "#00A651");
		$this -> define_color("darkorange", "#DF6718");
		$this -> define_color("darkpink", "#FF0099");
		$this -> define_color("forestgreen", "#66AA66");
		$this -> define_color("fuchsia", "#FF00FF");
		$this -> define_color("gold", "#CCAA44");
		$this -> define_color("gray", "#808080");
		$this -> define_color("green", "#008000");
		$this -> define_color("lightbeige", "#FFFFC9");
		$this -> define_color("lightfuchsia", "#FF63FF");
		$this -> define_color("lightgray", "#D9D9D2");
		$this -> define_color("lightgreen", "#00DD44");
		$this -> define_color("brightgreen", "#00F000");
		$this -> define_color("lightmaroon", "#FF0040");
		$this -> define_color("lightteal", "#15E0A0");
		$this -> define_color("dullteal", "#30D2FF");
		$this -> define_color("lightyellow", "#DEDE42");
		$this -> define_color("lime", "#00FF00");
		$this -> define_color("maroon", "#800000");
		$this -> define_color("navy", "#000080");
		$this -> define_color("olive", "#808000");
		$this -> define_color("orange", "#FF7718");
		$this -> define_color("pink", "#FF8CFC");
		$this -> define_color("purple", "#800080");
		$this -> define_color("red", "#FF0000");
		$this -> define_color("redpink", "#FF61A6");
		$this -> define_color("seablue", "#6699FF");
		$this -> define_color("seagreen", "#66FF99");
		$this -> define_color("silver", "#C0C0C0");
		$this -> define_color("tan", "#DDDD44");
		$this -> define_color("teal", "#008080");
		$this -> define_color("white", "#FFFFFF");
		$this -> define_color("yellow", "#FFFF00");
		$this -> define_color("omni", "#00ffff");
		$this -> define_color("clan", "#ff9933");
		$this -> define_color("neutral", "#ffffff");

		$this -> define_scheme("ao", "admin", "pink");
		$this -> define_scheme("ao", "cash", "gold");
		$this -> define_scheme("ao", "ccheader", "white");
		$this -> define_scheme("ao", "cctext", "lightgray");
		$this -> define_scheme("ao", "clan", "brightgreen");
		$this -> define_scheme("ao", "emote", "darkpink");
		$this -> define_scheme("ao", "error", "red");
		$this -> define_scheme("ao", "feedback", "yellow");
		$this -> define_scheme("ao", "gm", "redpink");
		$this -> define_scheme("ao", "infoheader", "lightgreen");
		$this -> define_scheme("ao", "infoheadline", "tan");
		$this -> define_scheme("ao", "infotext", "forestgreen");
		$this -> define_scheme("ao", "infotextbold", "white");
		$this -> define_scheme("ao", "megotxp", "yellow");
		$this -> define_scheme("ao", "meheald", "bluegray");
		$this -> define_scheme("ao", "mehitbynano", "white");
		$this -> define_scheme("ao", "mehitother", "lightgray");
		$this -> define_scheme("ao", "menubar", "lightteal");
		$this -> define_scheme("ao", "misc", "white");
		$this -> define_scheme("ao", "monsterhitme", "red");
		$this -> define_scheme("ao", "mypet", "orange");
		$this -> define_scheme("ao", "newbie", "seagreen");
		$this -> define_scheme("ao", "news", "brightgreen");
		$this -> define_scheme("ao", "none", "fuchsia");
		$this -> define_scheme("ao", "npcchat", "bluesilver");
		$this -> define_scheme("ao", "npcdescription", "yellow");
		$this -> define_scheme("ao", "npcemote", "lightbeige");
		$this -> define_scheme("ao", "npcooc", "lightbeige");
		$this -> define_scheme("ao", "npcquestion", "lightgreen");
		$this -> define_scheme("ao", "npcsystem", "red");
		$this -> define_scheme("ao", "npctrade", "lightbeige");
		$this -> define_scheme("ao", "otherhitbynano", "bluesilver");
		$this -> define_scheme("ao", "otherpet", "darkorange");
		$this -> define_scheme("ao", "pgroup", "white");
		$this -> define_scheme("ao", "playerhitme", "red");
		$this -> define_scheme("ao", "seekingteam", "seablue");
		$this -> define_scheme("ao", "seekingteam", "seablue");
		$this -> define_scheme("ao", "shout", "lightbeige");
		$this -> define_scheme("ao", "skillcolor", "beige");
		$this -> define_scheme("ao", "system", "white");
		$this -> define_scheme("ao", "team", "seagreen");
		$this -> define_scheme("ao", "tell", "aqua");
		$this -> define_scheme("ao", "tooltip", "black");
		$this -> define_scheme("ao", "tower", "lightfuchsia");
		$this -> define_scheme("ao", "vicinity", "lightyellow");
		$this -> define_scheme("ao", "whisper", "dullteal");

		// No tags cache created yet:
		$this -> startup = FALSE;
		$this -> no_tags = TRUE;
		$this -> theme_info = "";
		$this -> theme = array();
		$this -> create_color_cache();
	}

	/*
	This makes sure the cache is up-to-date with the tables.
	*/
	function cron()
	{
		$this -> create_color_cache();
	}


	function get($color)
	{
		if ($this -> color_tags['##' . $color . '##'] != '')
		return $this -> color_tags['##' . $color . '##'];
		else
		return "<font color=#000000>";
	}

	function colorize($color, $text)
	{
		if ($this -> color_tags['##' . $color . '##'] != '')
		return $this -> color_tags['##' . $color . '##'] . $text . "</font>";
		else
		return $text;
	}

	// defines a new color:
	function define_color($name, $code)
	{
		$this -> bot -> db -> query("INSERT IGNORE INTO #___colors (name, code) VALUES ('" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($code) . "')");

		$this -> no_tags = TRUE;
		$this -> create_color_cache();
	}

	// defines a new color scheme:
	function define_scheme($module, $scheme, $color_name)
	{
		$this -> bot -> db -> query("INSERT IGNORE INTO #___color_schemes"
		. " (module, name, color_code) VALUES ('" . mysql_real_escape_string($module) . "', '"
		. mysql_real_escape_string($scheme) . "', '" . mysql_real_escape_string($color_name) . "')");

		$this -> no_tags = TRUE;
		$this -> create_color_cache();
	}

	// defines a new color scheme, using a new color (at least it's assumed that the color is new):
	function define_color_scheme($module, $scheme, $color_name, $color_code)
	{
		// first add color:
		$this -> bot -> db -> query("INSERT IGNORE INTO #___colors"
		. " (name, code) VALUES ('" . mysql_real_escape_string($color_name) . "', '" . mysql_real_escape_string($color_code) . "')");

		// then add scheme:
		$this -> bot -> db -> query("INSERT IGNORE INTO #___color_schemes"
		. " (module, name, color_code) VALUES ('" . mysql_real_escape_string($module) . "', '"
		. mysql_real_escape_string($scheme) . "', '" . mysql_real_escape_string($color_name) . "')");

		$this -> no_tags = TRUE;
		$this -> create_color_cache();
	}

	// changes the color reference for a scheme:
	function update_scheme($module, $scheme, $new_color_name)
	{
		$this -> bot -> db -> query("UPDATE #___color_schemes"
		. " SET color_code = '" . mysql_real_escape_string($new_color_name) . "' WHERE module = '"
		. mysql_real_escape_string($module) . "' AND name = '" . mysql_real_escape_string($scheme) . "'");
		$this -> no_tags = TRUE;
		$this -> create_color_cache();
	}

	// Read scheme file in, update all schemes in the bot with new information out of the file
	function read_scheme_file($filename)
	{
		$theme_dir = "./themes/";
		$ret['error'] = false;

		// Make sure filename is valid
		if (!preg_match("/^([a-z01-9-_]+)$/i", $filename))
		{
			$ret['error'] = TRUE;
			$ret['errordesc'] = "Illegal filename for scheme file! The filename must only contain letters, numbers, - and _!";
			return $ret;
		}

		$scheme_file = file($theme_dir . $filename . ".scheme.xml");
		if (!$scheme_file)
		{
			$ret['error'] = TRUE;
			$ret['errordesc'] = "Scheme file not existing or empty!";
			return $ret;
		}

		foreach ($scheme_file AS $scheme_line)
		{
			if (preg_match("/scheme module=\"([a-z_]+)\" name=\"([a-z_]+)\" code=\"([a-z]+)\"/i", $scheme_line, $info))
			{
				$this -> bot -> db -> query("UPDATE #___color_schemes"
				. " SET color_code = '" . mysql_real_escape_string($info[3]) . "' WHERE module = '"
				. mysql_real_escape_string($info[1]) . "' AND name = '" . mysql_real_escape_string($info[2]) . "'");
			}
		}
		$this -> no_tags = TRUE;
		$this -> create_color_cache();

		$ret['content'] = "Theme file " . $filename . " read, schemes updated!";
		return $ret;
	}

	// Creates a scheme file containing all schemes in the bot table
	function create_scheme_file($filename, $name)
	{
		$theme_dir = "./themes/";
		$ret['error'] = false;

		// Make sure filename is valid
		if (!preg_match("/^([a-z01-9-_]+)$/i", $filename))
		{
			$ret['error'] = TRUE;
			$ret['errordesc'] = "Illegal filename for scheme file! The filename must only contain letters, numbers, - and _!";
			return $ret;
		}

		$header = '<schemes name="Scheme for ' . ucfirst(strtolower($this -> bot -> botname)) . '" version="1.0" author="' . ucfirst(strtolower($name)) . '" link="">';
		$footer = '</schemes>';

		$filename = $filename .= ".scheme.xml";

		$handle = fopen($theme_dir . $filename, "w");
		if (!$handle)
		{
			$ret['error'] = TRUE;
			$ret['errordesc'] = "Can't open scheme file " . $filename . "!";
			return $ret;
		}
		
		$schemes = $this -> bot -> db -> select("SELECT * FROM #___color_schemes ORDER BY module ASC, name ASC");
		if (empty($schemes))
		{
			$ret['error'] = TRUE;
			$ret['errordesc'] = "No schemes defined!";
			return $ret;
		}

		$status = TRUE;
		if (!fwrite($handle, $header . "\n"))
		{
			$status = FALSE;
		}
		foreach ($schemes as $scheme)
		{
			if (!fwrite($handle, '<scheme module="' . $scheme[0] . '" name="' . $scheme[1] . '" code="' . $scheme[2] . '" />' . "\n"))
			{
				$status = FALSE;
			}
		}
		if (!fwrite($handle, $footer . "\n"))
		{
			$status = FALSE;
		}

		fclose($handle);

		if (!$status)
		{
			$ret['error'] = TRUE;
			$ret['errordesc'] = "Error while writing schemes!";
			return $ret;
		}

		$ret['content'] = "Scheme file " . $filename . " created!";
		return $ret;
	}

	// Creates default theme file with given name
	function create_theme_file($name)
	{
		$theme_file[] = '<theme name="Default theme for BeBot" version="1.0" author="Alreadythere" link="">';
		$theme_file[] = '<color name="normal" color="lightyellow" />';
		$theme_file[] = '<color name="highlight" color="forestgreen" />';
		$theme_file[] = '<color name="error" color="red" />';
		$theme_file[] = '<color name="blob_title" color="tan" />';
		$theme_file[] = '<color name="blob_text" color="forestgreen" />';
		$theme_file[] = '</theme>';

		// Try to open the theme file, bail on error
		$handle = fopen($name, "a");
		if (!$handle)
		{
			return FALSE;
		}

		// Now write the lines for the default theme file, mark errors to bail after closing file
		$status = TRUE;
		foreach ($theme_file as $theme_line)
		{
			if (!fwrite($handle, $theme_line . "\n"))
			{
				$status = FALSE;
			}
		}

		// Close file
		fclose($handle);

		// If error while writing bail
		if (!$status)
		{
			return FALSE;
		}

		// Return default theme file
		$this -> bot -> log("COLOR", "THEME", "Created default theme!");
		return $theme_file;
	}

	// Reads the selected theme file. If the file doesn't exist it creates one with default colors
	function read_theme()
	{
		$theme_dir = "./themes/";

		// Security check, theme filename HAS to be all letters or numbers, otherwise dying here for security reasons!
		if (!preg_match("/^([a-z01-9-_]+)$/i", $this -> bot -> core("settings") -> get("Color", "Theme")))
		{
			die("POSSIBLE SECURITY PROBLEM! The theme filename can only contain letters, numbers - and _ for security reasons!\nThe bot has been shutdown.\n");
		}

		$theme_file_name = $theme_dir . $this -> bot -> core("settings") -> get("Color", "Theme") . ".colors.xml";

		// If theme file doesn't exist try to create it
		if (!is_file($theme_file_name))
		{
			$theme_file = $this -> create_theme_file($theme_file_name);
		}
		else
		{
			$theme_file = file($theme_file_name);
		}

		// If we don't got a theme file here yet we are in serious trouble, bail out!
		if (!$theme_file)
		{
			die("CRITICAL ERROR: Could not read nor create color theme file!\nThe bot has been shutdown.\n");
		}

		// Initialize theme array with the colors required by a theme
		$this -> theme = array();
                $this -> theme["normal"] = "#000000";
                $this -> theme["highlight"] = "#000000";
                $this -> theme["error"] = "#000000";
                $this -> theme["blob_title"] = "#000000";
                $this -> theme["blob_text"] = "#000000";

		// Parse the input file now
		foreach ($theme_file as $theme_line)
		{
			$theme_line = trim($theme_line);
			if (preg_match("/color name=\"([a-z_]+)\" code=\"(#[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f])\"/i", $theme_line, $info))
			{
				$this -> theme[strtolower($info[1])] = $info[2];
			}
			elseif (preg_match("/color name=\"([a-z_]+)\" color=\"([a-z]+)\"/i", $theme_line, $info))
			{
				$cols = $this -> bot -> db -> select("SELECT code FROM #___colors WHERE name = '" . mysql_real_escape_string($info[2]) . "'");
				if (empty($cols))
				{
					$this -> theme[strtolower($info[1])] = "#000000";
				}
				else
				{
					$this -> theme[strtolower($info[1])] = $cols[0][0];
				}
			}
			elseif (preg_match("/theme name=\"(.*)\" version=\"(.*)\" author=\"(.*)\" link=\"(.*)\"/i", $theme_line, $info))
			{
				$this -> theme_info = "Name of theme: " . $info[1] . "\n";
				$this -> theme_info .= "Version: " . $info[2] . "\n";
				$this -> theme_info .= "Author: " . $info[3] . "\n";
				$this -> theme_info .= "Link: " . $info[4];
			}
		}
	}

	// caches all possible color tags in the $this -> color_tags() array:
	function create_color_cache()
	{
		// Don't create the cache before the initialising is done!
		if ($this -> startup)
		{
			return;
		}

		$this -> no_tags = FALSE;
		$this -> color_tags = array();

		// Parse theme file
		$this -> read_theme();

		// Create color tags for themes
		$theme_strings = array();
		foreach ($this -> theme as $color => $colorcode)
		{
			$this -> color_tags["##" . strtolower($color) . "##"] = "<font color=" . $colorcode . ">";
			$theme_strings[] = "color_code = '" . $color . "'";
		}
		$theme_string = implode(" OR ", $theme_strings);

		// Create all other color tags
		$cols = $this -> bot -> db -> select("SELECT name, code FROM #___colors");
		if (!empty($cols))
		{
			foreach ($cols as $col)
			{
				if (!isset($this -> color_tags["##" . strtolower($col[0]) . "##"]))
				{
					$this -> color_tags["##" . strtolower($col[0]) . "##"] = "<font color=" . $col[1] . ">";
				}
			}
		}

		// Create all scheme tags for schemes using theme colors
		$cols = $this -> bot -> db -> select("SELECT concat(module, '_', name) AS scheme, color_code FROM #___color_schemes "
		. "WHERE " . $theme_string . " UNION SELECT name AS scheme, color_code FROM #___color_schemes WHERE (" . $theme_string
		. ") AND module = 'global'");

		if (!empty($cols))
		{
			foreach ($cols as $col)
			{
				if (!isset($this -> color_tags["##" . strtolower($col[0]) . "##"]))
				{
					$this -> color_tags["##" . strtolower($col[0]) . "##"] = "<font color=" . $this -> theme[strtolower($col[1])] . ">";
				}
			}
		}

		// Now create all scheme tags for schemes using no theme colors
		$cols = $this -> bot -> db -> select("SELECT concat(t1.module, '_', t1.name) AS name, t2.code FROM "
		. "#___color_schemes AS t1, #___colors AS t2 WHERE t1.color_code = t2.name AND NOT (" . $theme_string . ") UNION "
		. "SELECT t1.name AS name, t2.code AS code FROM #___color_schemes AS t1, #___colors AS t2 WHERE "
		. "t1.color_code = t2.name AND t1.module = 'global' AND NOT (" . $theme_string . ")");

		$this -> color_tags["##end##"] = "</font>";
		if (!empty($cols))
		{
			foreach ($cols as $col)
			{
				if (!isset($this -> color_tags["##" . strtolower($col[0]) . "##"]))
				{
					$this -> color_tags["##" . strtolower($col[0]) . "##"] = "<font color=" . $col[1] . ">";
				}
			}
		}
	}

	// replaces all color tags with the corresponding font commands:
	function parse($text)
	{
		if ($this -> no_tags)
		{
			$this -> create_color_cache();
		}

		// No replacing if no tags can be in the text
		if (strpos($text, "##") === false)
		{
			return $text;
		}

		// Go ahead and replace all tags
		foreach ($this -> color_tags as $tag => $font)
		{
			$text = eregi_replace($tag, $font, $text);
		}

		return $text;
	}

	function get_theme()
	{
		return $this -> theme;
	}

	function check_theme($col)
	{
		return isset($this -> theme[strtolower($col)]);
	}
}
?>
