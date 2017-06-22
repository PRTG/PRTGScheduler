<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 _                                       __ _                       _   _
| |__   __ _ ___  ___    ___ ___  _ __  / _(_) __ _ _   _ _ __ __ _| |_(_) ___  _ __
| '_ \ / _` / __|/ _ \  / __/ _ \| '_ \| |_| |/ _` | | | | '__/ _` | __| |/ _ \| '_ \
| |_) | (_| \__ \  __/ | (_| (_) | | | |  _| | (_| | |_| | | | (_| | |_| | (_) | | | |
|_.__/ \__,_|___/\___|  \___\___/|_| |_|_| |_|\__, |\__,_|_|  \__,_|\__|_|\___/|_| |_|
                                              |___/
*/
$config['app_version']        = "1.0";

$config['prtg_protocol']      = "http";
$config['prtg_server']        = "";
$config['prtg_port']          = 80;
$config['prtg_sensorid']      = 0;

# /!\ IMPORTANT /!\ #
# make sure that this user account has its timezone set to UTC!
$config['prtg_username']      = "";
$config['prtg_passhash']      = 0;
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

$config['default_timezone']   = "Europe/Berlin";
$config['weekstart']          = "MO";
$config['preferred_language'] = "en";
$config['debug']              = True;

# This decides if the maintenance windows should only be set at least within 5 minutes before they actually start.
# If set to false, the scheduler will set the upcoming maintenances right away, rendering PRTGs internal option to
# set maintenance windows practically useless - setting maintenances via PRTG will result in overwriting the automatic one.
$config['set_on_demand']        = True;
$config['on_demand_threshold']  = 5;
