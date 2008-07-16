<?php
/*
* Tools.php - Module Containing Usefull Functions to be used by other Modules
*
* Made by Temar (most code is Simply Taken from elsewhere)
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
* File last changed at $LastChangedDate: 2008-03-20 09:46:44 -0500 (Thu, 20 Mar 2008) $
* Revision: $Id: FlexibleSecurity.php 1346 2008-03-20 14:46:44Z alreadythere $
*/



$tools = new tools($bot);

class tools extends BasePassiveModule
{

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_module("tools");
	}

	function chatcmd($link, $title, $origin = FALSE)
	{
		$origin = strtolower($origin);
		switch($origin)
		{
			case'gc':
			case'o':
			case 3:
				$chatcmd = "o <pre>";
				Break;
			case'pgmsg':
			case'pg':
			case 2:
				$chatcmd = "group ".$this -> bot -> botname." <pre>";
				Break;
			case 'start':
				$chatcmd = "start ";
				Break;
			case'tell':
			case 0:
			case 1:
			case FALSE:
				$chatcmd = "tell ".$this -> bot -> botname." <pre>";
				Break;
			Default:
				$chatcmd = $origin." ";
		}
		Return ('<a href=\'chatcmd:///'.$chatcmd.$link . '\'>' . $title . '</a>');
	}

	function get_site($url, $strip_headers = 0, $server_timeout = 25, $read_timeout = 30)
	{
		$return = $this -> get_site_data($url,$strip_headers,$server_timeout,$read_timeout);

		if ($return["error"] && $this -> use_proxy_server && !empty($this -> proxy_server_address))
		{
			echo "We're using a proxy\n";
			foreach ($this -> proxy_server_address as $proxy)
			{
				echo "Trying proxy: ".$proxy."\n";
				$return = $this -> get_site_data($url,$strip_headers,$server_timeout,$read_timeout,$proxy);

				if (!($return["error"]))
					break;
			}
		}

		return $return;
	}

	/*
	Gets the data from a URL
	*/
	function get_site_data($url, $strip_headers = 0, $server_timeout = 5, $read_timeout = 10, $proxy = '')
	{
		$get_url = parse_url($url);

		// Check to see if we're using a proxy, and get the IP address for the target host.
		if (!empty($proxy))
		{
			$proxy_address = explode(":", $proxy);
			$address = gethostbyname($proxy_address[0]);
			$service_port = $proxy_address[1];
		}
		else
		{
			$address = gethostbyname($get_url[host]);

			/* Get the port for the WWW service. */
			$service_port = getservbyname('www', 'tcp');
		}

		/* Create a TCP/IP socket. */
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		// Check to see if the socket failed to create.
		if ($socket === false) {
			$return["error"] = true;
			$return["errordesc"] = "Socket failed to create.";
			$return["content"] = socket_strerror(socket_last_error());
			return $return;
		}

		$connect_result = socket_connect($socket, $address, $service_port);

		// Make sure we have a connection
		if ($connect_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Failed to connect to server";
			$return["content"] = socket_strerror(socket_last_error());
			return $return;
		}

		$bot_version = $this -> botversion;

		$in = "GET $url HTTP/1.0\r\n";
		$in .= "Host: $get_url[host]\r\n";
		$in .= "Connection: Close\r\n";
		$in .= "User-Agent: BeBot/$bot_version\r\n\r\n";

		$write_result = socket_write($socket, $in, strlen($in));

		// Make sure we wrote to the server okay.
		if ($write_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Failed to write to server";
			$return["content"] = socket_strerror(socket_last_error());
			return $return;
		}

		$return["content"] = "";
		$read_result = socket_read($socket, 2048);
		while ($read_result != "" && $read_result !== false)
		{
			$return["content"] .= $read_result;
			$read_result = socket_read($socket, 2048);
		}

		// Make sure we got a response back from the server.
		if ($read_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Failed to read response";
			$return["content"] = socket_strerror(socket_last_error());
			return $return;
		}

		$close_result = socket_close($socket);

		// Make sure we closed our socket properly.  Open sockets are bad!
		if ($close_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Failed to close socket";
			$return["content"] = socket_strerror(socket_last_error());
			return $return;
		}

		// Did the calling function want http headers stripped?
		if ($strip_headers)
		{
			$split = split("\r\n\r\n",$return["content"]);
			$return["content"] = $split[1];
		}

		return $return;

	}

	/*
	Parse XML crap
	*/
	function xmlparse($xml, $tag)
	{
		$tmp = explode("<" . $tag . ">", $xml);
		$tmp = explode("</" . $tag . ">", $tmp[1]);
		return $tmp[0];
	}

	function make_blob($title, $content)
	{
		$content = str_replace("\"", "&quot;", $content);

		return "<a href=\"text://" . $content . "\">" . $title . "</a>";
	}

	/*
	Creates a text blob.  Alternate uses ' instead of ".
	*/
	function make_item($lowid, $highid, $ql, $name, $alternate = false)
	{
		if($alternate)
			return "<a href='itemref://" . $lowid . "/" . $highid . "/" . $ql . "'>" . $name . "</a>";
		else
			return "<a href=\"itemref://" . $lowid . "/" . $highid . "/" . $ql . "\">" . $name . "</a>";
	}

	/*
	Takes an item string and returns an array with lowid, highid, ql and name.
	If $item is unparsable it returns a BotError
	*/
	function parse_item($item)
	{
		$pattern='|<a href="itemref://([0-9]+)/([0-9]+)/([0-9]{1,3})">([^<]+)</a>|';
		preg_match($pattern, $item, $parts);
		if(empty($parts))
		{
			$this->error->set("Unable to parse item: '$item'");
			return($this->error);
		}
		$parsed['lowid'] = $parts[1];
		$parsed['highid'] = $parts[2];
		$parsed['ql']=$parts[3];
		$parsed['name']=$parts[4];
		return($parsed);
	}
	
	//Returns true if $item is an itemref, false otherwise.
	function is_item($item)
	{
		$pattern='|<a href="itemref://([0-9]+)/([0-9]+)/([0-9]{1,3})">([^<]+)</a>|';
		preg_match($pattern, $item, $parts);
		if(empty($parts))
		{
			return false;
		}
		return true;
	}

	/*
	Used to convert an overflowed (unsigned) integer to a string with the correct positive unsigned integer value
	If the passed integer is not negative, the integer is merely passed back in string form with no modifications.
	*/
	function int_to_string($int)
	{
		if ($int <= -1)
		{
			$int += (float)"4294967296";
		}
		return (string)$int;
	}

	/*
	Used to convert an unsigned interger in string form to an overflowed (negative) integere
	If the passed string is not an integer large enough to overflow, the string is merely passed back in integer form with no modifications.
	*/
	function string_to_int($string)
	{
		$int = (float)$string;
		if ($int > (float)2147483647)
		{
			$int -= (float)"4294967296";
		}
		return (int)$int;
	}
	
	/*
	Checks if a player name is valid and if the player exists.
	Returns BotError on failure
	Returns ucfirst(strtolower($name)) if the player exists.
	*/
	function validate_player($name, $check_exists=true)
	{
		$name=trim(ucfirst(strtolower($name)));
		if(strlen($name)<3 || strlen($name)>14)
		{
			$this->error->set("Player name has to be between 4 and 13 characters long (inclusive)");
			return($this->error);
		}
		if(preg_match("|([a-z]+[0-9]*[^a-z]*)|", $name)==0)
		{
			$this->error->set("Player name has to be alphabetical followed by 0 or more digits not followed by alphabetical characters.");
			return($this->error);
		}
		if($check_exists)
		{
			if(!$this->bot->core('chat')->get_uid($name))
			{
				$this->error->set("Player '$name' does not exist.");
				return($this->error);
			}
		}
		return($name);
	}
}
?>
