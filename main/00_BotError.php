<?php

class BotError
{
	private $bot;
	protected $status;
	private $is_fatal;
	private $description;
	private $source;
	
	function __construct(&$bot, $module)
	{
		$this-> status=false;
		$this->is_fatal=false;
		$this->description='';
		$this->source=$module;
		$this->bot=$bot;
	}
	
	function status()
	{
		return $this->status;
	}
	
	function reset()
	{
		$this->status=false;
		$this->is_fatal=false;
		$this->description='';
	}
	
	function set($description, $log=true, $fatal=false)
	{
		$this->description = $description;
		$this->is_error=true;
		$this->is_fatal = $fatal;
		if($log)
		{
			$this -> bot -> log('ERROR', $this->source, $description);
		}
		if($fatal)
		{
			$this -> bot -> log('FATAL', $this->source, $description);
			exit(1);
		}
	}
	
	function get()
	{
		return $this->description;
	}
	
	function message()
	{
		return "##error##Error: ##end##The module ##highlight##{$this->source}##end## returned the error ##error##{$this->description}##end##";
	}
}
?>
