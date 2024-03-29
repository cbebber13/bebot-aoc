<?php

abstract class BaseActiveModule extends BasePassiveModule
{
	public $help; //A window containing help text for this module
	protected $source;

	function __construct (&$bot, $module_name)
	{
		//Save reference to bot
		parent::__construct(&$bot, $module_name);
	}

	// Prototype for the command_handler
	abstract protected function command_handler($name, $msg, $origin);

	// Interface to register command. Now with checks for duplicate command definitions.
	// $channel is the channel the command should be registered for. "all" can be used to register a command for gc, pgmsg and tell at once.
	// $command is the command to register.
	// $access is the minimum access level required to use the command on default.
	// $subcommands is an array with keys of subcommands and entries of access levels to define access
	// rights for possible subcommands. If $subcommands is NULL it will be ignored.
	protected function register_command($channel, $command, $access = "OWNER", $subcommands = NULL)
	{
		$levels=array('ANONYMOUS', 'GUEST', 'MEMBER', 'LEADER', 'ADMIN', 'SUPERADMIN', 'OWNER');
		$channels = array('gc', 'pgmsg', 'tell', 'extpgmsg', 'all');
		$allchannels = array ('gc', 'pgmsg', 'tell');
		if((in_array($channel, $channels))&&(in_array($access, $levels)))
		{
			if(!$this -> bot -> exists_command($channel, $command))
			{
				$this -> bot -> register_command($channel, $command, &$this);
				$this -> bot -> core("access_control") -> create($channel, $command, $access);
				if ($subcommands != NULL)
				{
					foreach ($subcommands AS $subcommand => $subacl)
					{
						$this -> bot -> core("access_control") -> create_subcommand($channel, $command, $subcommand, $subacl);
					}
				}
			}
			else
			{
				//Say something useful for modules not registering commands properly.
				$old_module = $this -> bot -> get_command_handler($channel, $command);
				$this -> error -> set("Duplicate command definition! The command '$command' for channel '$channel'".
				" has already been registered by '$old_module' and is attempted re-registered by {$this->module_name}");
			}
		}
		else
		{
			$this->error->set("Illegal channel or access level when registering command '$command'");
		}
	}

	protected function unregister_command($channel, $command)
	{
		$channels = array('gc', 'pgmsg', 'tell', 'extpgmsg', 'all');
		$allchannels = array ('gc', 'pgmsg', 'tell');
		if (in_array($channel, $channels))
		{
			if($this -> bot -> exists_command($channel, $command))
			{
				$this -> bot -> unregister_command($channel, $command, &$this);
			}
		}
	}

	// Registers a command alias for an already defined command.
	protected function register_alias($command, $alias)
	{
		$this -> bot -> core("command_alias") -> register($command, $alias);
	}

	protected function unregister_alias($alias)
	{
		$this -> bot -> core("command_alias") -> del($alias);
	}

	// This function aids in parsing the command.
	protected function parse_com($command, $pattern = array('com', 'sub', 'args'))
	{
		//preg_match for items and insert a replacement.
		$item_count = preg_match_all('/'.$this -> bot -> core('items') -> itemPattern .'/i', $command, $items, PREG_SET_ORDER);
		for($cnt = 0; $cnt < $item_count; $cnt++)
			$command = preg_replace('/'.$this -> bot -> core('items') -> itemPattern .'/i', "##item_$cnt##", $command, 1);

		//Split the command
		$num_pieces=count($pattern);
		$num_com=count(explode(' ', $command));
		$pieces = explode(' ', $command, $num_pieces);
		$com = array_combine(array_slice($pattern, 0, $num_com), $pieces);

		//Replace any item references with the original item strings.
		foreach($com as &$com_item)
		{
			for($cnt = 0; $cnt < $item_count; $cnt++)
				$com_item=str_replace("##item_$cnt##", $items[$cnt][0], $com_item);
		}
		unset($com_item);
		return ($com);
	}

	/************************************************************************
	 Default to replying in the same channel as the command has been recieved
	*************************************************************************/

	public function reply($name, $msg)
	{
		if ($msg != false)
		{
			if($msg instanceof BotError)
			{
				//We got an error. Return the error message.
				$this->reply($name, $msg->message());
			}
			else
			{
				$this -> output($name, "##normal##$msg##end##", SAME);
			}
		}
	}

	public function tell($name, $msg)
	{
		$this->source=TELL;
		$this->error->reset();
		$reply = $this -> command_handler($name, $msg, "tell");
		if(($reply !== false) && ($reply !==''))
		{
			$this->reply($name, $reply);
		}
	}

	public function gc($name, $msg)
	{
		$this->source=GC;
		$this->error->reset();
		$reply = $this -> command_handler($name, $msg, "gc");
		if(($reply !== false) && ($reply !==''))
		{
			$this->reply($name, $reply);
		}
	}

	public function pgmsg($name, $msg)
	{
		$this->source=PG;
		$this->error->reset();
		$reply = $this -> command_handler($name, $msg, "pgmsg");
		if(($reply !== false) && ($reply !==''))
		{
			$this->reply($name, $reply);
		}
	}
}
?>
