## Introduction ##
BeBot Hyborian is a chat bot connecting to the [Age of Conan](http://www.ageofconan.com/) ingame chat server. BeBot Hyborian is based on [BeBot](http://bebot.shadow-realm.org) 0.5.2 for Anarchy Online. BeBot has a flexible plugin-structure making it very easy to integrate and develop 3rd party plugins for the bot. A wide range of plugins are already made for the Anarchy Online version which can be reused directly for BeBot Hyborian. You can find help and ready to install plugins at the BeBot support forums [here](http://bebot.shadow-realm.org/forum/).

## Latest Changes ##
Latest changes can be found [here](http://code.google.com/p/bebot-aoc/source/list).

## Standard Modules ##
BeBot Hyborian is shipped with a couple of modules:
  * items (a simple itemdatabase that centrally stores all the items the BeBots with this module installed sees and makes them searchable ingame)
  * whois (lookup players's levels and class)
  * is (is a player online)
  * gemcutting (get information on how to cut the gems you find)
  * IRC relay (relay guildchat to and from IRC)
  * raffle, roll, nroll (varies ways of rolling randomly)
  * chuck (get your favourite Chuck Norris facts)
  * quotes (save famous player quotes forever and have fun reading them later)
  * calc (do calculations ingame with a simple calculator interface)
  * craftclasses (add crafting classes on the whois information of people)
  * and a lot more

## Documentation ##
You can find a lot of documentation on the official [BeBot](http://bebot.shadow-realm.org) site and the official [BeBot forums](http://bebot.shadow-realm.org/forum/). You will not be finding any documentation here.

## Requirements ##
PHP 5.2.0 or later.

MySQL 5 or higher.

## Download ##
You can download the entire BeBot Hyborian code from the repository [here](http://code.google.com/p/bebot-aoc/source/checkout) with your favourite Subversion tool. For Windows I can recommend [TortoiseSVN](http://tortoisesvn.net/). You can find a zipped snapshot of the code [here](http://code.google.com/p/bebot-aoc/downloads/list). Windows users can additionally download the [PHP bundle](http://bebot-aoc.googlecode.com/files/bebot-php_v5.2.6.rar) in order to run Bebot Hyborian.

## Credits ##
Age of Conan adjustments for BeBot was done by Noer and Vrykolas. Refer to the credits file in the source for the full credits on the original code. The bot uses the [aochat.php library](http://code.google.com/p/aocchat/) originally made by Auno and modified for Age of Conan by Noer.

## License ##
BeBot Hyborian is distributed under its original GPL v2 license. This means in short that you are allowed to modify and release updates to the bot as long as you credit the original contributors. If you release anything using a modified version of this bot you have to release the complete source code of your modified bot under a GPL v2 license aswell.