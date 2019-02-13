# PRTGScheduler
![https://img.shields.io/badge/status-abandoned-lightgrey.svg](https://img.shields.io/badge/status-abandoned-lightgrey.svg) 

# Attention
As the conversion to ruby has unfortunately has been canclled, I've only commited what has been developed with Ruby so far into the ruby branch. It basically does not much yet, but it's a start. It uses Ice_Cube for reccuring event parsing and some other ruby gems which are installed automatically at the first run. I tried to make comments as verbose as possible. Feel free to fork it and finish it. In case you need to use it desperately, version 1 of the master branch should actually still work to a certain extend.

# Description

With PRTG Scheduler, you can configure customized maintenance windows for every PRTG object (Sensors, Devices, and Groups). It allows for various ways of occurrences, like every first Sunday in January, February and March, or only the first week of every month.

# Features
* Three types of maintenance windows: Repetitive, One/Two Day, Spanning (From-To)
* Every maintenance can have its own timezone or use the default one
* Report of currently configured and upcoming maintenances including their start/end date
and duration, accessible via Reports | Configuration Reports | Maintenance Windows
*Maintenance Windows can be disabled by outcommenting them
* Support for seven languages (English, German, Spanish, French, Italian, Japanese, Dutch)
in the report / sensor channels. New languages can be added easily.

Frequently asked questions can be [found in the issues, labeled as questions](https://github.com/PaesslerAG/PRTGScheduler/issues?q=is%3Aissue+is%3Aclosed).

Check the [Wiki](https://github.com/PaesslerAG/PRTGScheduler/wiki) for installation instructions.

# Beta
Note that this software is currently in a beta state and may contain errors. Mainly the validation of maintenance setups needs some work and wrong configurations will cause the sensor to stop working. You'll need to check the debug log for the erroneous configuration then. 
