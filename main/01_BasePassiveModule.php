<?php

class BasePassiveModule
{
	protected $bot; // A reference to the bot
	public $module_name; //Name of the module extending this class.
	protected $error; //This holds an error class.
	protected $link_name;
	
	function __construct (&$bot, $module_name)
	{
		//Save reference to bot
		$this -> bot = &$bot;
		$this -> module_name = $module_name;
		$this -> link_name = NULL;
		$this -> error = new BotError($bot, $module_name);
	}

	protected function register_event($event, $target=false)
	{
		$ret = $this -> bot -> register_event($event, $target, &$this);
		if ($ret)
		{
			$this->error->set($ret);
		}
	}

	protected function unregister_event($event, $target=false)
	{
		$ret = $this -> bot -> unregister_event($event, $target, &$this);
		if ($ret)
		{
			$this->error->set($ret);
		}
	}

	protected function register_module($name)
	{
		if ($this -> link_name == NULL)
		{
			$this -> link_name = strtolower($name);
			$this -> bot -> register_module(&$this, strtolower($name));
		}
	}

	protected function unregister_module()
	{
		if ($this -> link_name != NULL)
		{
			$this -> bot -> unregister_module($this -> link_name);
		}
	}

	protected function output($name, $msg, $channel=false)
	{
		if($channel!==false)
		{
			if($channel & SAME)
			{
				if($channel & $this->source)
				{
					$channel -= SAME;
				}
				else
				{
					$channel += $this->source;
				}
			}
		}
		else
		{
			$channel += $this->source;
		}
			
		if ($channel & TELL)
			$this->bot->send_tell($name, $msg);
		if ($channel & GC)
			$this->bot->send_gc($msg);
		if($channel & PG)
			$this->bot->send_pgroup($msg);
		if($channel & RELAY)
			$this->bot->core("relay")->relay_to_pgroup($name, $msg);
		if($channel & IRC)
			$this->bot->send_irc($this->module_name, $name, $msg);
	}
	
	public function __call($name, $args)
	{
		$args=implode(', ', $args);
		$msg = "Undefined function $name($args)!";
		$this->error->set($msg);
		return $this->error->message();
	}
}
?>
