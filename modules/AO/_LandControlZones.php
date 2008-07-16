<?php
/*
* Database of the land control zones, based on code by Wolfbiter, modified by Pharexys.
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
* File last changed at $LastChangedDate$
* Revision: $Id: LandControlZones.php 1554 2008-05-11 23:15:50Z temar $
*/

$landcontrol = new LandControlZones($bot);



class LandControlZones extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("land_control_zones", "false") . " (
			`id` int(11) default NULL,
			`lrange` int(10) default NULL,
			`hrange` int(10) default NULL,
			`area` varchar(50) default NULL,
			`huge` varchar(10) default NULL,
			`x` varchar(10) default NULL,
			`y` varchar(10) default NULL,
			`name` varchar(250) default NULL,
			PRIMARY KEY (id),
			UNIQUE (area, name),
			INDEX (lrange),
			INDEX (hrange),
			INDEX (area)
			)");

		$this -> bot -> db -> query("INSERT IGNORE INTO #___land_control_zones VALUES (1,10,15,'Clondyke','7','2500','1220','Micron Slopes Notum Mine'),(2,10,15,
		'Greater Omni Forest','5','1500','1300','Greater Omni Forest South'),(3,10,15,'Galway Shire','1','500','1900','Blossom Valley'),
		(4,10,15,'Galway Shire','2','380','1300','Konty Passage Plains'),(5,10,15,'Galway Shire','5','380','580','Kontys Sixth Passage - West'),
		(6,10,15,'Galway Shire','6','620','540','Kontys Sixth Passage - East'),(7,10,15,'Holes in the Wall','4','740','820','Snake Mountain'),
		(8,10,15,'Lush Fields','7','2860','420','South East Lush Fields'),(9,10,15,'Omni Forest','4','700','2180','Rocky Upsurge'),(10,10,15,
		'Tir County','1','460','1300','Great W. Forest Vein'),(11,10,15,'Tir County','2','2940','980','The Hidden Notum Canal'),(12,10,15,
		'Tir County','4','580','580','Great W. Forest Dorsal'),(13,10,15,'Tir County','5','1500','460','Western Mountain names'),
		(14,11,16,'Aegean','2','2180','2580','West Wastelands'),(15,11,16,'Aegean','5','1180','1340','West of the Dead Forest'),(16,10,20,
		'Athen Shire','1','2660','2020','Sifter Beach'),(17,10,20,'Athen Shire','4','2660','820','Grindmoore'),(18,10,20,
		'Holes in the Wall','6','380','340','Transit Valley Ore'),(19,10,20,'Omni Forest','3','460','2180','Dungeon Hilltop'),(20,12,20,
		'Holes in the Wall','3','220','1060','Stret West Notum Ore'),(21,12,20,'Newland','1','1220','1060','In the Newland Desert'),
		(22,12,22,'Holes in the Wall','2','660','1500','Hound Land Mining'),(23,15,22,'Aegean','7','1420','1020','Canyon East'),(24,15,23,
		'Athen Shire','5','1380','380','Gladius Grove'),(25,10,25,'Greater Omni Forest','7','1940','900','Ring Mountain Range'),(26,14,25,
		'Greater Omni Forest','8','940','460','Southern Isle'),(27,15,25,'Athen Shire','3','1980','1340','Athen Fault'),(28,15,25,
		'Galway County','5','1140','1100','V-Hill'),(29,15,25,'Greater Omni Forest','2','1180','2460','Dragonback Ridge'),(30,15,25,
		'Galway Shire','4','780','900','Arthur\'s Pass'),(31,15,25,'Mutant Domain','1','780','1420','Mutant Domain North'),
		(32,15,25,'Newland','2','540','460','West of Newland Lake'),(33,15,25,'Omni Forest','5','340','1420','Northern Easy Swamps Notum Fields'),
		(34,15,25,'Stret West Bank','2','2300','2860','Pondus Beach'),(35,15,26,'Holes in the Wall','1','420','2020','Populous Mountain'),
		(36,15,26,'Omni Forest','6','460','820','Ocean Inlet'),(37,17,28,'Galway Shire','3','900','1220','Vas\' Pass'),(38,12,30,
		'Stret West Bank','6','1340','1220','Omni Outpost'),(39,15,30,'Omni Forest','2','980','3020','Moderate Grassland'),(40,15,30,
		'Stret West Bank','3','1700','2780','Hound Land'),(41,15,30,'Wailing Wastes','8','660','900','North of Yuttos'),(42,20,30,
		'Athen Shire','2','1780','1780','Academy Ore'),(43,20,30,'Galway County','6','1580','700','Lunder Hills - North'),(44,20,30,
		'Galway County','8','1220','380','Lunder Hills'),(45,20,30,'Greater Tir County','2','2900','2940','NE Desert Aperient'),(46,20,30,
		'Lush Fields','2','2420','3180','North East Lush Fields'),(47,20,30,'Lush Fields','6','1780','1820','Central Lush Fields'),
		(48,20,30,'Mutant Domain','2','500','860','Mutant Domain Central'),(49,20,30,'Stret West Bank','1','1700','3100','Hells Courtyard'),
		(50,20,30,'Stret West Bank','5','1700','1940','East Mutie'),(51,20,30,'Stret West Bank','7','660','1180','South Mutie'),(52,20,30,
		'Tir County','3','3220','620','Mountain names'),(53,20,30,'Wailing Wastes','6','580','1740','Charred Groove'),(54,20,35,
		'Greater Omni Forest','4','1860','1340','Waterfall Swamp'),(55,20,35,'Omni Forest','1','500','3220','Northern Grassland'),
		(56,25,35,'Aegean','8','820','780','Canyon South'),(57,25,35,'Wailing Wastes','4','340','2420','Powdered Dunes'),(58,10,40,
		'Lush Fields','3','3460','2940','Stret River Island'),(59,20,40,'Holes in the Wall','5','780','460','Southern Empty Wastes and Roads'),
		(60,20,40,'Stret West Bank','4','1980','2780','Hound Notum Field'),(61,25,40,'Galway County','7','2740','460','Galway Hills'),
		(62,25,40,'Galway County','9','2260','380','South-east Woods'),(63,25,40,'Greater Omni Forest','6','900','1220',
		'Northern Semi-Barren name'),(64,25,40,'Greater Tir County','4','2220','1900','Piercing Thundertube'),(65,25,40,
		'Greater Tir County','6','620','1660','Tir Prarie'),(66,25,40,'Greater Tir County','7','1180','1700','Crater Swamp'),(67,25,40,
		'Mutant Domain','3','780','460','Mutant Domain South'),(68,25,40,'Newland Desert','1','2940','2900','Rich Desert Ridge'),(69,12,45,
		'Wailing Wastes','7','940','1540','West of Perdition'),(70,30,45,'Aegean','4','2140','1660','Giant Green River Bank North'),
		(71,30,45,'Aegean','6','2100','1340','Giant Green River Bank South'),(72,30,45,'Andromeda','1','420','2700','Skop Notum Mine'),
		(73,30,45,'Clondyke','1','1100','4340','Yukon Source'),(74,30,45,'Clondyke','3','2140','2420','Round Hills'),(75,30,45,
		'Greater Omni Forest','1','1620','2660','Greater Omni Forest Swamps'),(76,30,45,'Greater Omni Forest','3','1900','1820',
		'Mountainous Regions'),(77,30,45,'Greater Tir County','5','2820','1940','Central Striking Ant'),(78,30,45,
		'Lush Fields','1','940','3260','North West Lush Fields'),(79,30,45,'Lush Fields','8','980','380','South West Lush Fields'),
		(80,30,45,'Newland Desert','2','1980','2580','East of Meetmedere'),(81,30,45,'Pleasant Meadows','9','740','460',
		'West of Versailles Tower'),(82,30,45,'Varmint Woods','1','2420','2980','By the Rivers Edge'),(83,30,45,
		'Varmint Woods','4','3740','2500','East Forest'),(84,32,45,'Wailing Wastes','5','2540','2060','Dust Bank'),(85,25,50,
		'The Longest Road','3','3420','1540','Fate Notum Field'),(86,25,50,'Varmint Woods','3','1300','2660','Along the Rivers Edge'),
		(87,25,50,'Varmint Woods','5','3140','2020','Rhino Hills'),(88,26,50,'Wailing Wastes','3','980','3140','Between the Craters'),(89,35,50,
		'4 Holes','2','940','2020','Mountain of Fourtyone'),(90,35,50,'4 Holes','3','1300','1980','Mountain in 4Holes'),(91,35,50,
		'4 Holes','6','1460','1260','Mountain of Fortytwo'),(92,35,50,'Aegean','9','900','460','By the River'),
		(93,35,50,'Clondyke','2','1460','2540','Frisko'),(94,35,50,'Clondyke','5','1260','1820','Borrowed Hill'),
		(95,35,50,'Clondyke','6','1340','1340','Narrow Lune'),(96,35,50,'Galway County','1','2140','2620','Nature Reserve - East'),
		(97,35,50,'Galway County','2','1900','2580','Nature Reserve - West'),(98,35,50,'Wailing Wastes','2','2220','3340','Carbon Grove'),
		(99,40,55,'Aegean','3','1020','2460','Mid Wastelands'),(100,30,60,'Andromeda','7','820','340','Mocnuf Notum Mine'),(101,30,60,
		'Stret West Bank','8','2260','1140','The Beach'),(102,35,60,'Lush Fields','5','1740','2460','East of Outpost'),
		(103,40,60,'Aegean','1','1220','2740','Northern Wastelands'),(104,40,60,'Andromeda','5','1420','1580','Flubu Notum Mine'),(105,40,60,
		'Lush Fields','4','1260','2460','West of Outpost'),(106,40,60,'Newland Desert','4','2580','1940','North of Rhino Village'),
		(107,40,60,'Newland Desert','5','2700','1260','South of Rhino Village'),(108,40,60,'Pleasant Meadows','1','1540','2660',
		'Pleasant Range Offense Hill'),(109,40,60,'Pleasant Meadows','7','2260','1140','Central Pleasant Plains'),(110,40,60,
		'The Longest Road','1','4220','1580','Illuminati'),(111,40,60,'Wailing Wastes','1','1700','3700','Styx Magma'),(112,45,60,
		'4 Holes','4','1660','1740','South of Ahenus'),(113,45,60,'Broken Shores','7','1260','2140','Notum Mountain'),(114,37,64,
		'Greater Tir County','3','1900','2700','Surrounding Temple of Three Winds'),(115,30,70,'Pleasant Meadows','4','3220','2220',
		'Pleasant Range Defense'),(116,30,70,'Pleasant Meadows','8','3020','1020','East Pleasant Plains'),
		(117,40,70,'Andromeda','6','4340','900','Plago'),(118,45,70,'4 Holes','8','1180','500','Ibreri'),(119,45,70,'4 Holes','9','460','420',
		'Jall Mountain'),(120,55,70,'Stret East Bank','1','700','2420','Northern River Bank'),(121,55,70,
		'Stret East Bank','4','2020','1740','Klompfot Defense'),(122,55,70,'Stret East Bank','7','1780','700',
		'Aprils Rock Offense'),(123,45,75,'Broken Shores','2','1260','3860','Notum Disruption Mountain'),
		(124,50,75,'Avalon','6','2420','1900','Waylander Mines'),(125,50,75,'Clondyke','4','2140','1900','Dense Drewen'),
		(126,50,75,'Clondyke','8','2100','540','High Juniper'),(127,50,75,'Clondyke','9','2300','460','High Juniper Notum Vein'),(128,50,75,
		'Galway County','3','1300','1900','Poole - West'),(129,50,75,'Galway County','4','1580','1820','Poole - East'),(130,50,75,
		'Milky Way','8','2460','540','The Silent Woods - East'),(131,50,75,'Newland Desert','3','540','2020','Middle of Western Desert'),
		(132,50,75,'Pleasant Meadows','3','580','2420','West of 20K'),(133,50,75,'The Longest Road','6','4020','620','Winterbottom'),
		(134,50,75,'Upper Stret East Bank','1','540','2820','West Pass'),(135,50,75,'Varmint Woods','2','620','2900','North Forest Road'),
		(136,50,75,'Varmint Woods','9','3220','1140','East of Crater'),(137,65,75,'Upper Stret East Bank','2','900','2300','Crowning Shallows'),
		(138,50,76,'Varmint Woods','6','580','1700','West Forest'),(139,45,80,'Broken Shores','5','1300','3060','Central Mountains'),
		(140,30,80,'Andromeda','2','2820','2340','Klor'),(141,60,80,'Andromeda','3','2820','1660','Harstad'),
		(142,60,80,'Andromeda','8','2260','380','Jucha'),(143,61,82,'Avalon','8','460','1380','Dome Ore'),
		(144,40,90,'Andromeda','4','540','1580','Ubleo'),(145,60,90,'4 Holes','1','1580','2380','Notum Ore in Buttu'),
		(146,60,90,'Avalon','5','580','3140','Western Desert'),(147,60,90,'Pleasant Meadows','2','2380','2500','Central Pleasant Range'),
		(148,60,90,'Pleasant Meadows','5','3220','1980','Pleasant River Defense'),(149,60,90,'Pleasant Meadows','6','3260','1500',
		'Pleasant River Offense'),(150,60,90,'Southern Artery Valley','1','1380','2780','Tetlies Land control name'),(151,60,90,
		'Southern Artery Valley','3','660','2460','West of outpost 10-3'),(152,60,90,'Stret East Bank','2','1780','2460',
		'Hawker Trench'),(153,60,90,'The Longest Road','8','2900','500','Summer'),(154,60,90,
		'Upper Stret East Bank','7','940','420','Greenslopes'),(155,60,90,'Varmint Woods','7','1940','1620','Crossroads'),(156,60,90,
		'Varmint Woods','8','1140','1500','Forestdawn'),(157,75,90,'Upper Stret East Bank','6','1820','740','Stret Woods'),
		(158,70,95,'Avalon','3','1740','3460','Dreadfire Volcano'),(159,1,100,'Mort','5','900','1460','Green Crater'),(160,55,100,
		'Broken Shores','8','2020','1980','Near Omni-Tek Outpost'),(161,60,100,'Southern Fouls Hills','1','1900','3020','North of Lenne'),
		(162,60,100,'Southern Fouls Hills','4','900','2100','By the Ocean'),(163,61,100,'Southern Fouls Hills','5','2300','1180','Birm'),
		(164,70,100,'4 Holes','5','1820','1340','Ibreri Woods North'),(165,70,100,'Avalon','7','1860','1700','North of Main Omni Base'),
		(166,75,100,'Broken Shores','3','1940','3860','The Notum Plains'),(167,70,105,'Andromeda','9','4380','380','Mune'),(168,70,105,
		'Stret East Bank','3','1460','1740','Klapam Forest Defense'),(169,70,105,'Stret East Bank','5','1900','1220','South of Trench'),
		(170,80,110,'Avalon','2','540','4180','Draught'),(171,71,120,'The Longest Road','4','580','820','Pegrama'),
		(172,80,120,'Avalon','4','2780','3420','Northeast Barren Lands'),(173,80,120,'Southern Artery Valley','2','2900','2660',
		'East of the Great Marsh'),(174,80,120,'Stret East Bank','6','1140','940','Nile Hills'),(175,84,120,
		'The Longest Road','5','1220','700','Grazeland Notum Field'),(176,90,120,'Milky Way','2','1900','1540','The Resilient Forest - East'),
		(177,90,120,'Southern Fouls Hills','3','2620','2660','Defense of Zoto'),(178,90,120,'The Longest Road','7','540','500',
		'Southern Forest of Illuminations'),(179,110,120,'Belial Forest','2','1100','2620','Muddy Pools'),(180,100,125,
		'Milky Way','4','1380','1180','Central Resilient Forest'),(181,100,125,'Milky Way','7','1740','860','The Resilient Forest - South'),
		(182,90,130,'Central Artery Valley','4','3340','2700','North-East Forest'),(183,90,130,'Milky Way','1','1460','1940',
		'The Resilient Forest - North'),(184,90,135,'Greater Tir County','1','1100','3100','The Mineral Mine'),(185,70,140,
		'Upper Stret East Bank','4','2020','1740','Stret Vale Deux Drilling Field'),(186,106,143,'Southern Artery Valley','5','2740','1180',
		'South of Forest of Geholva'),(187,55,15,'Broken Shores','6','380','2300','Surrounding Evil'),(188,80,150,
		'Broken Shores','1','940','4820','Central Desert North'),(189,80,150,'Stret East Bank','8','820','420','Southern Lower River Bank'),
		(190,80,150,'Stret East Bank','9','1700','340','Aprils Rock Defense'),(191,100,150,'4 Holes','7','1740','1060','Ibreri Woods'),
		(192,100,150,'Avalon','1','2740','4260','Griffon Frontier'),(193,100,150,'Avalon','9','2700','620','Crystal Forge Volcano'),
		(194,100,150,'Avalon','10','660','460','SW Low Plateau'),(195,100,150,'Belial Forest','3','1700','2300','West of Wine'),
		(196,100,150,'Broken Shores','4','940','3380','Near Clan Outpost'),(197,100,150,'Broken Shores','9','420','820','Shores Notum Vein'),
		(198,100,150,'Central Artery Valley','6','3100','980','Mid Clutching Forest'),(199,100,150,'Central Artery Valley','8','3180','620',
		'South Clutching Forest'),(200,100,150,'Deep Artery Valley','1','1140','3380','Old Ruins'),(201,100,150,
		'Deep Artery Valley','2','3180','2900','Plains of Defense'),(202,100,150,'Eastern Fouls Plain','7','2020','860','Central Sharewood'),
		(203,100,150,'Mort','8','540','540','South West Craterwall'),(204,100,150,'Milky Way','6','4020','980','The Barren Hills'),
		(205,100,150,'Perpetual Wastelands','7','1500','1340','Lower Plateu Zone'),(206,100,150,'Perpetual Wastelands','8','2100','1380',
		'The Mid Canyon Crossing'),(207,100,150,'Perpetual Wastelands','10','900','1060','West of Canyon'),(208,100,150,
		'Perpetual Wastelands','11','3180','940','The Canyon Mines'),(209,100,150,'Southern Artery Valley','4','2300','2020','Defense of Geholva'),
		(210,100,150,'Southern Artery Valley','8','2460','540','Bendelham forest Defense'),(211,100,150,
		'Southern Fouls Hills','2','860','2820','Little Hawaii Defense'),(212,100,150,'The Longest Road','2','500','1540',
		'Northern Forest of Illuminations'),(213,100,150,'Upper Stret East Bank','3','1660','2180','Haven Notum Crematorium'),
		(214,100,150,'Southern Fouls Hills','7','1860','500','South in Nightplain'),(215,120,150,'Belial Forest','8','2340','860','Borderline'),
		(216,110,160,'Mort','6','3100','1460','Oasis Ore'),(217,100,170,'Central Artery Valley','3','2900','2820','North Forest'),
		(218,100,170,'Mort','1','1500','3420','Terraform Edge'),(219,125,170,'Milky Way','3','2780','1380','Central Prowler Waste'),
		(220,125,170,'Milky Way','5','2860','1020','Southern Prowler Waste'),(221,130,170,'Central Artery Valley','5','860','1220',
		'North-West of Lava Ditches'),(222,130,170,'Central Artery Valley','7','860','780','South-West of Lava Ditches'),
		(223,130,170,'Mort','4','1220','2220','Middle Mort Desert'),(224,120,180,'Belial Forest','4','2940','2260','East of Wine'),
		(225,120,180,'Belial Forest','9','2020','420','Southern Belial Mine'),(226,120,180,'Eastern Fouls Plain','3','620','2980',
		'Plains of Jarga Defense'),(227,120,180,'Perpetual Wastelands','3','980','2060','South of Sabulum'),(228,120,180,
		'Perpetual Wastelands','9','3020','1220','Plains of dust'),(229,120,180,'Southern Artery Valley','6','860','900','Avid Crater'),
		(230,120,180,'Southern Artery Valley','7','1540','900','East of Avid Crater'),(231,120,180,'Southern Fouls Hills','6','2700','660',
		'SFH Defense'),(232,120,180,'Upper Stret East Bank','5','1340','1620','The Flooded Bottomland'),(233,130,180,
		'Deep Artery Valley','3','1740','2300','The Haunted Forest Outskirt'),(234,130,180,'Deep Artery Valley','4','900','2200',
		'Forest of Xzawkaz'),(235,130,180,'Deep Artery Valley','6','1420','1500','Island of Control'),(236,130,180,
		'Deep Artery Valley','7','1340','1140','The Swamp of Hope'),(237,130,190,'Belial Forest','6','2500','1660','River Delta'),
		(238,130,195,'Belial Forest','5','1900','1740','Central Belial Forest'),(239,130,200,'Eastern Fouls Plain','6','1540','1140',
		'Clefre Defense'),(240,140,200,'Belial Forest','10','620','380','Southwest Belial Mining District'),(241,140,200,
		'Central Artery Valley','1','1740','3100','By the Fisher Village'),(242,140,200,'Central Artery Valley','2','2100','3060',
		'Fisher Village Approach'),(243,150,200,'Eastern Fouls Plain','1','2700','3860','Krud the Lost Valley Defense'),
		(244,150,200,'Mort','7','2740','700','South East Craterwall'),(245,160,200,'Belial Forest','1','2940','2820','Forest Waters'),
		(246,160,200,'Belial Forest','7','2540','1220','Junction Forest'),(247,140,210,'Deep Artery Valley','9','2140','780',
		'Middle of the Foul Forest'),(248,160,210,'Mort','9','2780','540','Stormshelter'),(249,150,225,
		'Eastern Fouls Plain','2','1900','3180','Pranade'),(250,190,230,'Perpetual Wastelands','4','3940','2060',
		'Cyborg Border'),(251,190,230,'Perpetual Wastelands','12','2300','780','South of Canyon'),(252,170,250,'Mort','2','3060','3020',
		'West Spirals'),(253,170,250,'Mort','3','3500','2980','East Sprials'),(254,191,250,'Perpetual Wastelands','2','3780','2540',
		'Middle of Liberty'),(255,200,300,'Deep Artery Valley','5','2260','1860','In the Swamp of Horrors'),(256,200,300,
		'Deep Artery Valley','8','2900','1100','South of the Medusa'),(257,200,300,'Deep Artery Valley','10','540','540',
		'Southern Forest of Xzawkaz'),(258,200,300,'Eastern Fouls Plain','4','2460','2260','Old Plains'),(259,200,300,
		'Eastern Fouls Plain','5','1540','1780','Middle of Easter Fouls Plains'),(260,200,300,'Eastern Fouls Plain','8','820','540',
		'Pegradul'),(261,200,300,'Perpetual Wastelands','1','3220','3020','North of Cyborg Hideout'),(262,200,300,
		'Perpetual Wastelands','5','2820','1820','Middle of Perpetual Wastelands'),(263,200,300,'Perpetual Wastelands','6','3740','1700',
		'South of Cyborg Hideout')");

		$this -> help['description'] = 'Land Control Areas';
		$this -> help['command']['lc [name]']="Shows all towersites in [name]";
		$this -> help['command']['lc 50']="Shows all towersites in the 50 range";
		$this -> help['command']['lc 100 200'] = "Shows all towersites in the 100-200 range";
		$this -> help['command']['lc 100 200 [name]'] = "Shows all towersites in the 100-200 range in [name]";
		$this -> help['command']['lc'] = "Shows all Land Control Areas with a link to each area.";

		$this -> bot -> core("settings") -> create("LandControl", "SchemaVersion", 1, "The version of the table schema of the land control table.", NULL, TRUE);
		$this -> schemaversion = 4;

		if ($this -> schemaversion > $this -> bot -> core("settings") -> get("LandControl", "SchemaVersion"))
		{
			switch ($this -> bot -> core("settings") -> get("LandControl", "SchemaVersion"))
			{
				case 1:
					$this -> bot -> db -> query("UPDATE #___land_control_zones SET hrange = 150 "
					. "WHERE area = 'Broken Shores' AND name = 'Surrounding Evil'");
				case 2:
					$this -> bot -> db -> query("UPDATE #___land_control_zones SET hrange = 90 "
					. "WHERE area = 'Wailing Wastes' AND name = 'Styx Magma'");
				case 3:
					$this -> bot -> db -> query("UPDATE #___land_control_zones SET hrange = 90, lrange = 60 "
					. "WHERE area = 'Avalon' AND name = 'Griffon Frontier'");
				case 4:
				default:
			}

			$this -> bot -> core("settings") -> save("LandControl", "SchemaVersion", $this -> schemaversion);
		}

		$this -> register_command("all", "lc", "MEMBER");
	}

	function command_handler($name, $msg, $channel)
	{
		if (preg_match("/^lc  (.+)$/i", $msg, $info))
			return $this -> show_lc($info[1]);
		elseif (preg_match("/^lc (\d+) (\d+) (.+)$/i", $msg, $info))
			return $this -> show_lc($info[3],$info[1],$info[2]);
		elseif (preg_match("/^lc (\d+) ([^\d]+)$/i", $msg, $info))
			return $this -> show_lc($info[2],$info[1],$info[1]);
		elseif (preg_match("/^lc (\d+) (\d+)$/i", $msg, $info))
			return $this -> show_lc(NULL,$info[1],$info[2]);
		elseif (preg_match("/^lc (\d+)$/i", $msg, $info))
			return $this -> show_lc(NULL,$info[1],$info[1]);
		elseif (preg_match("/^lc ([^\d]+)$/i", $msg, $info))
			return $this -> show_lc($info[1]);
		elseif (preg_match("/^lc$/i", $msg, $info))
			return $this -> show_lc("--all--");
		return false;
	}

	function show_lc($iarea=NULL,$lrange=0,$hrange=300) {
		if ($iarea == "--all--") {
			$areas = $this->bot->db->select("select distinct(area),count(area) from #___land_control_zones group by area");
			if (!empty($areas)) {
				$return.="<div align=center><u><font color=#10a5e5>Land Control Areas</font></u></div>";
				foreach ($areas as $area)
					$return .= $this -> bot -> core("tools") -> chatcmd("lc  ".$area[0], $area[0])." (".$area[1].")<br>";
				return $this -> bot -> core("tools") -> make_blob("Land Control Areas",$return);
			} else
				return "No matches";
		}
		else {
			if (!$iarea)
				$areas = $this->bot->db->select("select distinct(area),count(area) from #___land_control_zones group by area");
			else
				$areas = $this->bot->db->select("select distinct(area),count(area) from #___land_control_zones where area like '%".$iarea."%' group by area");
			if (!empty($areas)) {
				foreach ($areas as $area) {
					unset($temp);
					if (isset($return))
						$temp="<br><br>";
					$temp.="<div align=center><u><font color=#10a5e5>".$area[0]." (".$area[1].")</font></u></div>";
					if ($lrange == $hrange)
						$lcs = $this->bot->db->select("select * from #___land_control_zones where area='".$area[0]."' AND lrange<=".$lrange." AND hrange>=".$hrange." order by huge");
					else
						$lcs = $this->bot->db->select("select * from #___land_control_zones where area='".$area[0]."' AND lrange>=".$lrange." AND hrange<=".$hrange." order by huge");
					if (!empty($lcs)) {
						foreach ($lcs as $lc) {
							$temp.=" Area: ".$lc[7]."<br> Range: ".$this->conv($lc[1])."-".$this->conv($lc[2])."<br> Coords: ".$this->coords($lc[5])."x".$this->coords($lc[6])."<br> Hugemap: ".$lc[4]."<br><br>";

						}
						$return.=$temp;
					}
				}
				return $this -> bot -> core("tools") -> make_blob("Land Control Areas",$return);
			} else
				return "No matches";
		}
	}
	function conv($num) {
		if (strlen($num)<2)
			return $num;
		elseif (strlen($num)<3)
			return $num;
		else
			return $num;
	}
	function coords($num) {
		if (strlen($num)<2)
			return $num;
		elseif (strlen($num)<3)
			return $num;
		elseif (strlen($num)<4)
			return $num;
		else
			return $num;
	}
}
?>
