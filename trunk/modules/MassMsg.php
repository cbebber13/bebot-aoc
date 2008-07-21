<?php
/*
* MassMsg.php - Sends out mass messages and invites.
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
* File last changed at $LastChangedDate: 2008-06-01 18:39:06 +0100 (Sun, 01 Jun 2008) $
* Revision: $Id: MassMsg.php 1649 2008-06-01 17:39:06Z blueeagle $
*/


$massmsg = new MassMsg($bot);

/*
The Class itself...
*/
class MassMsg extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command('all', 'announce', 'LEADER');
		$this -> register_command('all', 'massinv', 'LEADER');

		$this -> help['description'] = 'Sends out mass messages and invites.';
		$this -> help['command']['announce <message>']="Sends out announcement <message> as tells to all online members.";
		$this -> help['command']['massinv <message>'] = "Sends out announcement <message> as tells to all online members and invites them to the private group.";

		$this -> bot -> core("settings") -> create('MassMsg', 'MassMsg', 'Both', 'Who should get mass messages and invites?', 'Guild;Private;Both');
		$this -> bot -> core("settings") -> create('MassMsg', 'MinAccess', 'GUEST', 'Which access level must characters online have to receive mass messages and invites?', 'ANONYMOUS;GUEST;MEMBER;LEADER;ADMIN;SUPERADMIN;OWNER');
		$this -> bot -> core("settings") -> create('MassMsg', 'IncludePrefLink', 'Yes', 'Should a link to preferences be included in the messages/invites?', 'Yes;No');
		
		$this -> bot -> core('prefs') -> create('MassMsg', 'recieve_message', 'Do you want to recieve mass-messages?', 'Yes', 'Yes;No');
		$this -> bot -> core('prefs') -> create('MassMsg', 'recieve_invites', 'Do you want to recieve mass-invites?', 'Yes', 'No;Yes');
	}



	function command_handler($name, $msg, $origin)
	{
		$com = $this->parse_com($msg, array('com', 'args'));
		switch($com['com'])
		{
			case 'announce':
				$this -> bot -> send_output($name, "Mass message being sent. Please stand by...", $origin);
				return($this->mass_msg($name, $com['args'], 'Message'));
				break;
			case 'massinv':
				$this -> bot -> send_output($name, "Mass invite being sent. Please stand by...", $origin);
				return($this->mass_msg($name, $com['args'], 'Invite'));
				break;
			default:
				$this->bot->send_help($name);
		}
	}
	
	function mass_msg($sender, $msg, $type)
	{
		//get a list of online users in the configured channel.
		$users = $this->bot->core('online')->list_users($this->bot->core('settings')->get('MassMsg', 'MassMsg'));
		if($users instanceof BotError)
		{
			return($users);
		}
		
		$msg = "$type from ##highlight##$sender##end##: $msg";
		
		//Add link to preferences according to settings
		foreach($users as $recipient)
		{
			//Need to do this in the loop to get the recipient name for the blob.
			if($this->bot->core('settings')->get('MassMsg', 'IncludePrefLink')=='Yes')
			{
 				$message=$msg."\n##seablue##You can disable reciept of mass messages and invites in the ##end##".$this->bot->core('prefs')->show_prefs($recipient, 'MassMsg');
			}
			else
			{
				$message = $msg;
			}
			
			//If they want messages they will get them regardless of type
			if($this->bot->core('prefs')->get($recipient, 'MassMsg', 'recieve_message')=='Yes')
			{
				$this->bot->send_tell($recipient, $message);
				$status[$recipient]['sendt']=true;
			}
			else
			{
				$status[$recipient]['sendt']=false;
			}
			
			//If type is an invite and they want invites, they will recieve both a message and an invite regardless of recieve_message setting
			if($type=='Invite')
			{
				if($this->bot->core('prefs')->get($recipient, 'MassMsg', 'recieve_invites')=='Yes')
				{
					//Check if they've already gotten the tell so we don't spam unneccessarily.
					if(!$status[$recipient]['sendt'])
					{
						$this->bot->send_tell($recipient, $message);
						$status[$recipient]['sendt']=true;
					}
					$this->bot->core('chat')->pgroup_invite($recipient);
					$status[$recipient]['invited']=true;
				}
				else
				{
					$status[$recipient]['invited']=false;
				}
			}
		}
		
		return("Mass messages complete. ".$this->make_status_blob($status));
	}
	
	function make_status_blob($status_array)
	{
		$window = "<center>##blob_title##::: Status report for mass message :::##end##</center>\n";
		foreach($status_array as $recipient=>$status)
		{
			$window.="\n##highlight##$recipient##end## - Message: ";
			if($status['sendt'])
				$window.="##lime##Sendt to user##end##";
			else
				$window.="##error##Blocked by preferences##end##";
			if(isset($status['invited']))
			{
				if($status['invited'])
					$window.=" - Invite to pgroup: ##lime##sendt to user##end##";
				else
					$window.=" - Invite to pgroup: ##error##blocked by preferences##end##";
			}
		}
		return($this->bot->core('tools')->make_blob('report', $window));
	}
		
}
?>
