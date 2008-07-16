'<?php
/*
* Gemcutting helper module v1.0, By Noer
* This module helps people with identifying gems and how to cut gems.
*
*
*/
$Gemcut = new Gemcut($bot);

class Gemcut extends BaseActiveModule
{
	var $server = 'http://aocdb.lunevo.net/';

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		
		$this -> register_command('all', 'gem', 'GUEST');
		$this -> register_command('all', 'gemcut', 'GUEST');

		$this -> bot -> core("colors") -> define_scheme("gemcut", "highlight", "yellow");
		$this -> bot -> core("colors") -> define_scheme("gemcut", "normal", "white");
		$this -> bot -> core("colors") -> define_scheme("gemcut", "info", "lightgreen");

		$this -> help['description'] = 'This module helps people with identifying gems and how to cut gems.';
		$this -> help['command']['gem <itemref>']="Displays what bonuses an uncut gem will give and how to cut it.";
		$this -> help['command']['gemcut <tier>']="Displays a list of gems for the specified tier.";
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match('/^gemcut/i', $msg, $info)) {
			$words = trim(substr($msg, strlen('gemcut')));
			if (!empty($words)) 
			{
				return $this -> gemtiers($words);
			} else {
				return "Usage: gemcut [tier]";
			}
		} elseif (preg_match('/^gem/i', $msg, $info)) {
                        $words = trim(substr($msg, strlen('item')));
                        if (!empty($words))
                        {
                                return $this -> identify($words);
                        } else {
                                return "Usage: gem [itemref]";
                        }
		} else {
			$this -> bot -> send_help($name);
		}
	}

	/*
	Identifies a gem.
	*/
	function identify($msg)
        {
		if (preg_match("/.*<a style=\"text-decoration:none\" href=\"itemref:\/\/([0-9]*)\/([0-9]*)\/([0-9]*)\/([0-9a-f]*\:[0-9a-f]*\:[0-9a-f]*:[0-9a-f]*)\/([0-9a-f]*\:[0-9a-f]*\:[0-9a-f]*:[0-9a-f]*)\"><font color=html_link_color>\[(.*)\]<\/font>.*/i",$msg,$matches))
		{
			$lowid          = $matches[1];
			$highid         = $matches[2];
			$ql             = $matches[3];
			$lowcrc         = $matches[4];
			$highcrc        = $matches[5];
			$name           = $matches[6];
			preg_match("/(Flawless|Uncut)\s(.*)/",$name,$matches);

			if ($matches[1] == "Flawless")
			{
				$rare = true;
			}
			else
			{
				$rare = false;
			}

			if (preg_match("/(Obsidian|Onyx|Jet|Black Jasper|Nightstar|Black Diamond)/",$matches[2]))
			{
				$type = "black";
			}
			elseif (preg_match("/(Azurite|Lapis Lazuli|Turquoise|Aquamarine|Sapphire|Star Saphire)/",$matches[2]))
			{
				$type = "blue";
			}
			elseif (preg_match("/(Chrysophrase|Malachite|Peridot|Sphene|Jade|Emerald)/",$matches[2]))
			{
				$type = "green";
			}
			elseif (preg_match("/(Carnelian|Tiger Eye|Chalcedony|Sunstone|Fire Agate|Padpaasahsa)/",$matches[2]))
                        {
                                $type = "orange";
                        }
			elseif (preg_match("/(Rose Quartz|Iolite|Amethyst|Duskstone|Royal Azel|Tyrian Sapphire)/",$matches[2]))
                        {
                                $type = "purple";
                        }
			elseif (preg_match("/(Spinel|Jasper|Garnet|Blood Opal|Ruby|Star Ruby)/",$matches[2]))
                        {
                                $type = "red";
                        }
			elseif (preg_match("/(Quartz|Zircon|Moonstone|Achroite|White Opal|Diamond)/",$matches[2]))
                        {
                                $type = "white";
                        }
			elseif (preg_match("/(Citrine|Chrysoberyl|Sagenite|Topaz|Heliodor|Golden Beryl)/",$matches[2]))
                        {
                                $type = "yellow";
                        }
			else
			{
				$type = "unknown";
			}

			if (preg_match("/(Obsidian|Azurite|Chrysophrase|Carnelian|Rose Quartz|Spinel|Quartz|Citrine)/",$matches[2]))
			{
				$tier = 1;
			}
			elseif (preg_match("/(Onyx|Lapis Lazuli|Malachite|Tiger Eye|Iolite|Jasper|Zircon|Chrysoberyl)/",$matches[2]))
                        {
                                $tier = 2;
                        }
			elseif (preg_match("/(Jet|Turquoise|Peridot|Chalcedony|Amethyst|Garnet|Moonstone|Sagenite)/",$matches[2]))
                        {
                                $tier = 3;
                        }
			elseif (preg_match("/(Black Jasper|Aquamarine|Sphene|Sunstone|Duskstone|Blood Opal|Achroite|Topaz)/",$matches[2]))
                        {
                                $tier = 4;
                        }
			elseif (preg_match("/(Nightstar|Sapphire|Jade|Fire Agate|Royal Azel|Ruby|White Opal|Heliodor)/",$matches[2]))
                        {
                                $tier = 5;
                        }
			elseif (preg_match("/(Black Diamond|Star Saphire|Emerald|Padpaasahsa|Tyrian Sapphire|Star Ruby|Diamond|Golden Beryl)/",$matches[2]))
                        {
                                $tier = 6;
                        }
			else
			{
				$tier = 0;
			}

			if (($type != "unknown") && ($tier != 0))
			{
				$txt = "##gemcut_normal##";
				if ($rare)
				{
					$txt .= "Quality: ##gemcut_highlight##Trillion/Teardrop/Cabochon (fits in size 2 sockets - shape 10)##end##\n";
				}
				else
				{
					$txt .= "Quality: ##gemcut_highlight##Rhombic/Oval/Oblique (fits in size 1 sockets - shape 5)##end##\n";
				}

				$txt .= "Tier: ##gemcut_highlight##".$tier." (lvl ".(($tier+3)*10).")##end##\n";

				$txt .= "Effects: ##gemcut_highlight##";

				switch ($type)
				{
					case "black":
						$txt .= "Unholy dmg / Unholy dd / Poison dmg / Poison dd";
						break;
					case "blue":
						$txt .= "Tap mana / Tap stamina / Tap health / Cold dmg / Cold dd / Wisdom / Intelligence";
						break;
					case "green":
						$txt .= "Fatality / Dexterity / Melee dmg / Pierce dmg / Crushing immune / Pierce immune / Slashing immune / Tap stamina / Bow dmg";
						break;
					case "orange":
						$txt .= "Fire dmg / Fire DD / Cold Immunity";
						break;
					case "purple":
						$txt .= "Hate / Taunt / Magic immunities";
						break;
					case "red":
						$txt .= "Crushing dmg / Slashing dmg / Tap health / Strength / Stamina + melee immunities / Holy dmg";
						break;
					case "white":
						$txt .= "Electrical dmg / Electrical dd / Electrical Immunity / Perception";
						break;
					case "yellow":
						$txt .= "Magic dmg / Holy dmg / Holy dd / Poison immunity / Unholy immunity";
						break;
				}
				$txt .= "##end##\n\n##gemcut_info##The gem effect is randomly determined when the gem is cut.##end##\n\n";

				if ($rare)
				{
					$txt .= "Shapes: ##gemcut_highlight##Trillion(1 handed), Teardrop(2 handed) and Cabochon(Armor). Can be used in blue crafted armor and weapons .##end##\n\n";
				}
				else
				{
					$txt .= "Shapes: ##gemcut_highlight##Rhombic(1 handed), Oval(2 handed) and Oblique(Armor). Can be used in green crafted armor and weapons.##end##\n\n";
				}

				$txt .= "##gemcut_info##Socketing:\n";
				$txt .= "- Make sure both the item and the gem are in your inventory and NOT equipped.\n";
				$txt .= "- Select the gem, the items that the gem can be added to will be surrounded with a green border.\n";
				$txt .= "- Drag and drop the gem onto the selected item.##end##";

				$txt .= "##end##";

				return "Result: " . $this -> bot -> core("tools") -> make_blob("identification", $txt);				
			}
			else
			{
				return "The gem could not be identified.";
			}
		}
        }

	function gemtiers ($msg)
	{
		switch ($msg)
		{
			case 1:
				$txt = "Tier 1 gems are (level 40-49): Obsidian, Azurite, Chrysophrase, Carnelian, Rose Quartz, Spinel, Quartz and Citrine. Drops in: Field of the Dead or Noble District.";
				break;
			case 2:
                                $txt = "Tier 2 gems are (level 50-59): Onyx, Lapis Lazuli, Malachite, Tiger Eye, Iolite, Jasper, Zircon and Chrysoberyl. Drops in: Eiglophian Mountains.";
                                break;
			case 3:
                                $txt = "Tier 3 gems are (level 60-69): Jet, Turquoise, Peridot, Chalcedony, Amethyst, Garnet, Moonstone and Sagenite. Drops in: Thunder River, Atzel's Approach";
                                break;
			case 4:
				$txt = "Tier 4 gems are (level 70-74): Black Jasper, Aquamarine, Sphene, Sunstone, Duskstone, Blood Opal, Achronite and Topaz. Drops in: Atzel's Approach, Keshatta";
				break;
			case 5:
                                $txt = "Tier 5 gems are (level 75-79): Nightstar, Sapphire, Jade, Fire Agate, Royal Azel, Ruby, White Opal and Heliodor. Drops in: Keshatta";
                                break;
			case 6:
                                $txt = "Tier 6 gems are (level 80+): Black Diamond, Star Saphire, Emerald, Padpaasahsa, Tyrian Sapphire, Star Ruby, Diamond and Golden Beryl. Drops in: Keshatta (listed as lvl 75)";
                                break;
			default:
				$txt = "Valid tiers are: 1-6";
				break;
		}
		return $txt;
	}
}
?>
