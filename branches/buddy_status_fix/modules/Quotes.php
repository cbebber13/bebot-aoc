<?php
  /*
   * Quotes.php - Module template.
   *
   * BeBot - An Anarchy Online Chat Automaton
   * Copyright (C) 2004 Jonas Jax
   *
   * Developed by Sabkor (RK1)
   *
   * File last changed at $LastChangedDate: 2004-12-29 01:41:32 +0100 (Wed, 29 Dec 2004) $
   * Revision: $Id: _ClassName.php 8 2004-12-29 00:41:32Z blondengy $
   */
   
  /*
    Add a "_" at the beginning of the file (_quotes.php) if you do not want it to be loaded.
  */

  $db -> query("CREATE TABLE IF NOT EXISTS quotes
              (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, quote BLOB, contributor VARCHAR(15))");
  
  $quotes = new Quotes($bot);


  /*
    The Class itself...
  */
  class Quotes Extends BaseActiveModule
  {
    var $bot;
    
    

    /*
      Constructor:
        Hands over a referance to the "Bot" class.
    */
    function __construct (&$bot)
    {
		parent::__construct(&$bot, get_class($this));

		$this -> register_command('all', 'quotes', 'MEMBER');

		$this -> help['description'] = 'Immortalize your friends and enemies.';
		$this -> help['command']['quotes']="Display a random quote from the database.";
		$this -> help['command']['quotes #']="Display quote number # from the database.";
		$this -> help['command']['quotes add text']="Add text to the quotes databases.";
		$this -> help['command']['quotes rem #']="Remove quote number # from the database.";
		$this -> help['command']['quotes remove #']="Remove quote number # from the database.";
		$this -> help['command']['quotes del #']="Remove quote number # from the database.";
		$this -> help['command']['quotes delete #']="Remove quote number # from the database.";
    }

    function command_handler($name, $msg, $origin)
    {
          if (preg_match("/^quotes ([0-9]+)$/i", $msg, $info))
          {
                $msg = $this -> send_quote($info[1]);
		$this -> bot -> send_output($name, $msg, $origin);
                //$this -> bot ->send_gc($msg);
		if ($origin == 'gc') { $this -> bot -> send_irc("", "", $msg);}
          }
          else if (preg_match("/^quotes add (.+)$/i", $msg, $info))
          {
                $msg = $this -> add_quote($info[1], $name);
		$this -> bot -> send_output($name, $msg, $origin);
                //$this -> bot ->send_gc($msg);
		if ($origin == 'gc') { $this -> bot -> send_irc("", "", $msg);}
          }
          else if (preg_match("/^quotes (remove|del|rem|delete) ([0-9]+)$/i", $msg, $info))
          {
              $msg = $this -> del_quote($info[2], $name);
		$this -> bot -> send_output($name, $msg, $origin);
                //$this -> bot ->send_gc($msg);
		if ($origin == 'gc') { $this -> bot -> send_irc("", "", $this -> del_quote($info[2]));}
          }
          else
          {
                $msg = $this -> send_quote(-1);
		$this -> bot -> send_output($name, $msg, $origin);
                //$this -> bot ->send_gc($msg);
		if ($origin == 'gc') { $this -> bot -> send_irc("", "", $msg);}
          }
    }


	function add_quote($strquote, $name)
	{
	    $this -> bot -> db -> query("INSERT INTO quotes (quote, contributor) VALUES ('" . addslashes($strquote) . "', '" . $name . "')");	    $num = $this -> bot -> db -> select("SELECT id FROM quotes ORDER BY id DESC");
	    $strmsg = "Thank you, your quote has been added as id #" . $num[0][0];
   	   
   	    return $strmsg;    	   
	}
	
	function del_quote($qnum, $name)
	{
   	    $result = $this -> bot -> db -> select("SELECT * FROM quotes WHERE id=" . $qnum);
	   	if (!empty($result)) {
	   		$candel = true;
			//if($this -> bot -> admin -> in_group($name, "superadmin")) $candel = true;
			//if($this -> bot -> admin -> in_group($name, "admin")) $candel = true;
			if($result[0][2]=$name) $candel = true;
			
			if ($candel==true) {
				$this -> bot -> db -> query("DELETE FROM quotes WHERE id=". $qnum);
				$reply = "Quote removed.";
			}
			else
				$reply = "Unable to delete quote with ID of " . $qnum . ". You must be an admin, or the contributor of the quote to delete it.";
			
		} else {
			$num = $this -> bot -> db -> select("SELECT id FROM quotes ORDER BY id DESC");
			$reply = "Quote with id of " . $qnum . " not found. (Highest quote ID is " . $num[0][0] . ".)";
		}
		
		return $reply;
	}

   function send_quote($qnum)
   {
      $strquote = "";
      if($qnum == -1) {
         $num = $this -> bot -> db -> select("SELECT id FROM quotes ORDER BY id DESC");
              $result = $this -> bot -> db -> select("SELECT * FROM quotes");
           
              if($num[0][0] > 0) {
			     $found = false;
				 while ($found == false)
				 {
	                 $row = rand(0,$num[0][0]);
					 if (!empty($result[$row][0]))
					 {
					 	$strquote = "#" . $result[$row][0] . " - " . $result[$row][1] . " [Contributed by: " . $result[$row][2] . "]";
					 $found = true;
					 }
				 }
              } else {
                 $strquote = "No quotes exist. Add some!";
              }
      } else {
              $result = $this -> bot -> db -> select("SELECT * FROM quotes WHERE id=" . $qnum);
              if (!empty($result)) {
                 $strquote = "#" . $result[0][0] . " - " . $result[0][1] . " [Contributed by: " . $result[0][2] . "]";
            } else {
               $num = $this -> bot -> db -> select("SELECT id FROM quotes ORDER BY id DESC");
               $strquote = "Quote with id of " . $qnum . " not found. (Highest quote ID is " . $num[0][0] . ".)";
            }         
      }

      return $strquote;
   }

   function search_quote($qtext)
   {
	$strquote = "";
	
	$num = $this -> bot -> db -> select("SELECT id FROM quotes WHERE quote LIKE '".$qtext."'");
	$result = $this -> bot -> db -> select("SELECT * FROM quotes");
	
	if($num[0][0] > 0) {
		$found = false;
		while ($found == false) {
			$row = rand(0,$num[0][0]);
			if (!empty($result[$row][0])) {
				$strquote = "#" . $result[$row][0] . " - " . $result[$row][1] . " [Contributed by: " . $result[$row][2] . "]";
				$found = true;
			}
		}
	} else {
		$strquote = "No quotes found!";
	}
	return $strquote;
	}
  }
?>
