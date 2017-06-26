<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use RRule\RRule;

class Maintenance_model extends CI_Model {

  public $id;
  public $repetition;
  public $weekDays;
  public $monthDays;
  public $months;
  public $startTime;
  public $endTime;
  public $description;
  public $defectMaintenanceWindows = array('count' => 0, 'objects' => array());
  public $maintenanceWindows = array();


  # add some nice logging
  public function log($message,$indentLvl){

    $indentLvls = array( 0 => "|_", 1 => "|__", 2 => "|____", 3 => "|______", 4 => "|_______", 5 => "|__________");
    $date = date('d.m.Y H:i:s');

    if($this->config->item('debug'))
      echo sprintf("[%s]%s %s\r\n",$date,$indentLvls[$indentLvl],$message);

  }

  # this will generate the maintenaces according to the definition in the object comments
  public function generateMaintenance($objecttype){

      global $maintenanceWindows;
      global $defectMaintenanceWindows;

      error_reporting(0);
      // get all sensors, devices and groups first

      $objects  = (json_decode(file_get_contents($this->getPrtgUrl($objecttype)),false,5))->$objecttype;

      if(count($objects) > 0){
        $this->log(sprintf("Found %s %s with maintenance windows configured.",count($objects),$objecttype),1);

        foreach($objects as $key => $object){

          $lineCount = 1;

          preg_match_all("/maintenance\[([^\]]*)\]/", $object->comments, $matches);

          switch ( $object->basetype ){
            case "group" : $this->log(sprintf("Checking maintenances for %s (%s), located on the probe '%s'",
                           $object->name,$object->objid, $object->probe),2);
                           break;
            case "device": $this->log(sprintf("Checking maintenances for %s (%s), located in the group '%s' (%s) on the probe '%s'",
                           $object->name, $object->objid, $object->group, $object->parentid, $object->probe),2);
                           break;
            case "sensor": $this->log(sprintf("Checking maintenances for %s (%s), located on the device '%s' in the group '%s' on the probe '%s'",
                           $object->name,$object->objid,$object->device,$object->group,$object->probe),2);
                           break;
          }

          # remove all other object maintenance windows first
          $maintenanceWindows = [];

          $this->log(sprintf("Found %s configured maintenance window(s). Analyzing...",count($matches[1])),3);

          foreach($matches[1] as $maintenanceWindowString)
          {
            $maintenanceWindow = new \stdClass;

            try{

              # split up the maintenance window
              $maintenanceWindow->definition = explode("|",$maintenanceWindowString);
              $maintenanceWindow->disabled   = False;

              $maintenanceWindow = $this->checkMaintenance($maintenanceWindow);

              switch($maintenanceWindow->type_raw){
                case   "onetime":   $this->createOneTimeMaintenance($maintenanceWindow, $object->objid); break;
                case  "spanning":  $this->createSpanningMaintenance($maintenanceWindow, $object->objid); break;
                case "recurring": $this->createRecurringMaintenance($maintenanceWindow, $object->objid); break;
              }

            }
            catch (Exception $ex){
              echo $ex->getMessage();
              $defectMaintenanceWindows['count']++;
              $defectMaintenanceWindows['objects'][$object->objid][] = $lineCount;
              $this->log(sprintf('[ERROR] The maintenance definition %s could not be parsed. Skipping.',$maintenanceWindowString),3);
            }
            $lineCount++;
            }

          # sort the maintenance windows so we can have the closest maintenance window
          krsort($maintenanceWindows);
          # point the key function to the first key of the maintenance
          reset($maintenanceWindows);

          # add the closest start/end timestamp to the object
          $object->nextMaintenanceStart    = $maintenanceWindows[key($maintenanceWindows)]->start_date;
          $object->nextMaintenanceEnd      = $maintenanceWindows[key($maintenanceWindows)]->end_date;
          $object->nextMaintenanceDuration = $maintenanceWindows[key($maintenanceWindows)]->duration;

            if($object->nextMaintenanceStart !== $null){
              $this->log(sprintf("Next Maintenance: %s, %s - %s, %s. Duration: %s",
                $object->nextMaintenanceStart->format("d/m/Y"),
                $object->nextMaintenanceStart->format("H:i"),
                $object->nextMaintenanceEnd->format("d/m/Y"),
                $object->nextMaintenanceEnd->format("H:i"),
                $object->nextMaintenanceDuration),3);
            } else {
              $this->log(sprintf('The object only had one defective maintenance. Please fix it to for it to be activated!',$maintenanceWindowString),3);
              unset($objects[$key]);
            }

          $object->maintenanceWindows = $maintenanceWindows;
        }
        return array($objects,$defectMaintenanceWindows);
    }
    else
    { $this->log("No $objecttype with configured maintenances found.",1); }

  }

  # this function will set the maintenance windows for all given objects
  public function setMaintenance($objects){

    // first, let's get all currently set maintenances - to avoid setting them twice
    $list = json_decode(simplexml_load_string(file_get_contents(sprintf($this->getPrtgUrl('rproperty'),$this->config->item('prtg_sensorid'),"comments")))->result);

    if(count($list->active_maint_windows) > 0){
       $this->log(sprintf("Retrieved applied maintenance list, containing %s entries.",count($list->active_maint_windows)),1);
       foreach($list->active_maint_windows as $entry) {$this->log(sprintf("ID: %s",$entry),2); }
    }
    else{
       $list = new \stdClass;
       $list->active_maint_windows = array();
       $this->log("Maintenance list is empty, creating a new one.",1);
    }

    foreach($objects as $object)
    {
      if(count($object->maintenanceWindows) > 0){

      # if the current ID is in the list of active maintenance windows, we don't need to set it again.
      if((in_array($object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $list->active_maint_windows))){

        # but! if the maintenance has been set already and the user outcommented it, let's unset it for him
        if($object->maintenanceWindows[key($object->maintenanceWindows)]->disabled === True){

          $this->log(sprintf("Maintenance %s for object '%s' (%s) has already been set, but is flagged as comment. Removing.",$object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $object->name, $object->objid),1);
          file_get_contents(sprintf($this->getPrtgUrl('removemaintenance'),$object->objid));

          if(strpos($http_response_header[0], " 302 "))
          {
            $object->setMaintenance = 2;
            $this->log(sprintf("Maintenance %s for object '%s' (%s) has been removed.",$object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $object->name, $object->objid),1);

            # remove it from the active maintenances - get the position first:
            $position = array_search($object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $list->active_maint_windows);
            unset($list->active_maint_windows[$position]);
          }
          else
          { $object->setMaintenance = 0; $this->log(sprintf("[ERROR] Maintenance %s for object '%s' (%s) could not be removed. Error: ",$object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $object->name, $object->objid,$http_response_header[0]),1); }
        }
        else
        {$this->log(sprintf("Maintenance %s for object '%s' (%s) has already been set. Skipping.",$object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $object->name, $object->objid),0); continue;}

      }
      else {

          # if the maintenance has been set already and the user outcommented it, let's unset it for him
          if($object->maintenanceWindows[key($object->maintenanceWindows)]->disabled === True){

            $this->log(sprintf("Maintenance %s for object '%s' (%s) has been disabled. Ignoring",$object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $object->name, $object->objid),1);
            # remove it from the active maintenances - get the position first:
            $position = array_search($object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $list->active_maint_windows);

            if($position !== null)
            unset($list->active_maint_windows[$position]);

          }
          else {
            # don't overwrite the maintenance window if the object is currently within a scheduled maintenance window
            if((int)$object->status_raw != 9){


              $maintenance = array( $object->objid,
                                    $object->maintenanceWindows[key($object->maintenanceWindows)]->start_date->format('Y-m-d-H-i-s'),
                                    $object->maintenanceWindows[key($object->maintenanceWindows)]->end_date->format('Y-m-d-H-i-s'));

              # maintenance windows should only be set when they're close enough (i.e T-5 minutes)
              # Otherwise, the internal maintenance windows of PRTG would be rendered useless.
              # 5 minutes gives us enough time to make sure it's set, even if the sensor takes long to execute.
              if($this->config->item('set_on_demand') === True)
              {
                $this->log(sprintf('Maintenance windows are configured to be set on demand. Checking time diff of %s until start.',$object->maintenanceWindows[key($object->maintenanceWindows)]->ID),2);

                # check the current time in UTC
                $now        = new DateTime('now');
                $timezone   = new DateTimeZone('UTC');
                $now->setTimeZone($timezone);
                $diff = $object->maintenanceWindows[key($object->maintenanceWindows)]->start_date->diff($now);

                $minutes = $diff->days * 24 * 60;
                $minutes += $diff->h * 60;
                $minutes += $diff->i;

                if($minutes < $this->config->item('on_demand_threshold') && $minutes > 0){
                  $this->log(sprintf('Maintenance Window %s is close enough. Setting maintenance.',$object->maintenanceWindows[key($object->maintenanceWindows)]->ID),2);
                  file_get_contents(vsprintf($this->getPrtgUrl('setmaintenance'),$maintenance));
                }
                else {$this->log(sprintf('Maintenance Window %s not near enough (%s more minutes). Skipping for now.', $object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $minutes - 4),2);}
              }
              else {
                $this->log(sprintf('Maintenance Window %s will be set in advance',$object->maintenanceWindows[key($object->maintenanceWindows)]->ID),2);
                file_get_contents(vsprintf($this->getPrtgUrl('setmaintenance'),$maintenance));
              }

              if(strpos($http_response_header[0], " 302 ")){
                $object->setMaintenance = 1; $this->log(sprintf("Maintenance %s for object '%s' (%s) has been set succesfully.",$object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $object->name, $object->objid),1);
                # add the id to the list of active maintenance windows
                array_push($list->active_maint_windows, $object->maintenanceWindows[key($object->maintenanceWindows)]->ID);
              }
              else
              { if($object->maintenanceWindows[key($object->maintenanceWindows)]->disabled !== False)
                { $object->setMaintenance = 0; $this->log(sprintf("Maintenance %s for object '%s' (%s) could not be set. Error: ",$object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $object->name, $object->objid,$http_response_header[0]),1); }
              }


            }
            else {
                $this->log(sprintf("Maintenance %s for object '%s' will not be set. Object is currently within a maintenance window", $object->maintenanceWindows[key($object->maintenanceWindows)]->ID, $object->objid),1);
            }
          }
        }
      }
    }

    # we're done! now we can update the list of PRTG Scheduler
    file_get_contents(sprintf($this->getPrtgUrl('wproperty'),$this->config->item('prtg_sensorid'),"comments",json_encode($list)));

    if(strpos($http_response_header[0], " 200 "))
    { $this->log(sprintf("Active maintenances have been updated (%s)",count($list->active_maint_windows)),1); }
    else
    { $this->log(sprintf("Active maintenances couldn't be updated. Error: (%s)",count($list->$http_response_header[0])),1); }

  }

  # this creates a one time maintenance that spans a maximum of two days
  public function createOneTimeMaintenance($maintenanceWindow, $objid){
    global $maintenanceWindows;

    # add it to the object properties
    $maintenanceWindow->type       = $this->lang->line('table_once');
    $maintenanceWindow->startDate  = $maintenanceWindow->definition[0] ;
    $maintenanceWindow->startTime  = $maintenanceWindow->definition[1];
    $maintenanceWindow->endTime    = $maintenanceWindow->definition[2];

    # optional description
    if(isset($maintenanceWindow->definition[3]))
    {  $maintenanceWindow->description = $maintenanceWindow->definition[3]; }

    # convert the time of the maintenance
    $maintenanceWindow = $this->rrule_model->convertMaintenanceTime($maintenanceWindow);

    # we need to be able to sort the maintenance in to get the closest one
    $maintenanceWindow->startDateSortable = $maintenanceWindow->start_date->format("dmYHis");

    $maintenanceWindow->ID = md5(sprintf("%s-%s-%s",$objid,
                                                    $maintenanceWindow->maintenanceWindowDefinition,
                                                    $maintenanceWindow->start_date->format("dmYHis")));

    # log the maintenance window rule found in the prtg object
    $this->logObject($maintenanceWindow);

    $maintenanceWindows[$maintenanceWindow->startDateSortable] = $maintenanceWindow;

  }

  # this will create a maintenance that spans from  one date to the other
  public function createSpanningMaintenance($maintenanceWindow, $objid){

      global $maintenanceWindows;

      $maintenanceWindow->type       = $this->lang->line('table_once');
      $maintenanceWindow->startDate  = $maintenanceWindow->definition[0];
      $maintenanceWindow->endDate    = $maintenanceWindow->definition[1];
      $maintenanceWindow->startTime  = $maintenanceWindow->definition[2];
      $maintenanceWindow->endTime    = $maintenanceWindow->definition[3];

      # optional description
      if(isset($maintenanceWindow->definition[4]))
      {  $maintenanceWindow->description = $maintenanceWindow->definition[4]; }

      $maintenanceWindow = $this->rrule_model->convertMaintenanceTime($maintenanceWindow);

      # we need to be able to sort the maintenance in to get the closest one
      $maintenanceWindow->startDateSortable = $maintenanceWindow->start_date->format("dmYHis");

      $maintenanceWindow->ID = md5(sprintf("%s-%s-%s",$objid,
                                                      $maintenanceWindow->maintenanceWindowDefinition,
                                                      $maintenanceWindow->start_date->format("dmYHis")));

      # log the maintenance window rule found in the prtg object
      $this->logObject($maintenanceWindow);

      # add  the window to the maintenanceWindow array
      $maintenanceWindows[$maintenanceWindow->startDateSortable] = $maintenanceWindow;

  }

  # this will create the repeating maintenance windows
  public function createRecurringMaintenance($maintenanceWindow, $objid){

      global $maintenanceWindows;

      $maintenanceWindow->type       = $this->lang->line('table_recurrence');

      # if we're using the default timezone (i.e. nothing is set),
      # the array index shifts one position to the left
      if(isset($maintenanceWindow->defaultTz)) { $index =  0;  }
      else                                     { $index = -1; }

      $maintenanceWindow->repetition = $maintenanceWindow->definition[$index++];
      $maintenanceWindow->weekDays   = $maintenanceWindow->definition[$index++];
      $maintenanceWindow->monthDays  = $maintenanceWindow->definition[$index++];
      $maintenanceWindow->months     = $maintenanceWindow->definition[$index++];
      $maintenanceWindow->startTime  = $maintenanceWindow->definition[$index++];
      $maintenanceWindow->endTime    = $maintenanceWindow->definition[$index++];

      # optional description
      if(isset($maintenanceWindow->definition[6]))
      {  $maintenanceWindow->description = $maintenanceWindow->definition[6]; }

      # create the rrule
      $maintenanceWindow->rrule         = $this->rrule_model->createRrule($maintenanceWindow);
      try
      { $maintenanceWindow->rruleReadable = $maintenanceWindow->rrule->humanReadable(['locale' => $this->config->item('preferred_language')]); }
      catch (Exception $ex)
      { $maintenanceWindow->rruleReadable = $maintenanceWindow->rrule->humanReadable(['locale' => 'en']); }

      # convert the maintenance time to UTC so we can set it correctly.
      $maintenanceWindow = $this->rrule_model->convertMaintenanceTime($maintenanceWindow);

      # we need to be able to sort the maintenance in to get the closest one
      $maintenanceWindow->startDateSortable = $maintenanceWindow->start_date->format("dmYHis");

      $maintenanceWindow->ID = md5(sprintf("%s-%s-%s",$objid,
                                                      $maintenanceWindow->maintenanceWindowDefinition,
                                                      $maintenanceWindow->start_date->format("dmYHis")));

      unset($maintenanceWindow->rrule);

      # log the maintenance window rule found in the prtg object
      $this->logObject($maintenanceWindow);

      # add the maintenance to an the maintenanceWindows array using the startDateSortable as key
      $maintenanceWindows[$maintenanceWindow->startDateSortable] = $maintenanceWindow;

  }

  # this will check the type of our maintenance and set its timezone accordingly
  public function checkMaintenance($maintenanceWindow){

      # check if the maintenance is disabled, i.e. starts with a #
      if(substr($maintenanceWindow->definition[0], 0, 1)=="#"){
        # set the disabled attribute to true
        $maintenanceWindow->disabled = True;
        # set the definition to the definition itself, sans the #
        $maintenanceWindow->definition[0] = ltrim($maintenanceWindow->definition[0],'#');
      }

      # check the timezone and set it to the default one if it doesn't match any IANA timezone
      if((preg_match('#([a-zA-Z]+\/[a-zA-Z]+)#',$maintenanceWindow->definition[0])) == 0){
        # set the timezone to the default one as per the configuration file
        $maintenanceWindow->timeZone   = $this->config->item('default_timezone');
        # set the repetition property to the actual definition for the rrule method
        $maintenanceWindow->repetition = $maintenanceWindow->definition[0];
        # set the defaultTz attribute to true
        $maintenanceWindow->defaultTz  = True;
      }
      # otherwise, set it to maintenance as per the definition
      else
      {
        $maintenanceWindow->timeZone = $maintenanceWindow->definition[0];
        $maintenanceWindow->defaultTz = False;
        # we don't need the timezone anymore as we do have it in the maintenanceWindow object
        array_splice($maintenanceWindow->definition, 0, 1);
      }

      # now we check the kind of repetition we have
      if(preg_match('/(daily-|weekly-|monthly-)/', $maintenanceWindow->definition[0]))
      { $maintenanceWindow->type_raw = "recurring"; }
      else
      {
        # if the second
        if(preg_match('#\d{1,2}/\d{1,2}/\d{4}#', $maintenanceWindow->definition[1]) != 0)
        { $maintenanceWindow->type_raw = "spanning"; }
        else
        { $maintenanceWindow->type_raw = "onetime"; }
      }

        return $maintenanceWindow;
  }


  # put everything into a PRTG conform format
  public function prtgOut($prtgChannels,$defectMaintenanceWindows){

    $counter = 0;
    $errorMsg = "";

    if(count($defectMaintenanceWindows))
    {
      foreach($defectMaintenanceWindows as $objid => $id)
      $errorMsg .= sprintf(" [%s] %s",$objid, join(", ",$defectMaintenanceWindows[$objid]));

    }

    foreach($prtgChannels as $prtgChannel => $prtgChannelValue){

      if($prtgChannelValue == null)
        $prtgChannelValue = 0;

      $prtgResult['prtg']['result'][$counter]['Channel'] = $prtgChannel;
      $prtgResult['prtg']['result'][$counter]['Value']   = $prtgChannelValue;
      $prtgResult['prtg']['text']                        = $errorMsg;

      $counter++;
    }
    echo json_encode($prtgResult);
  }

  # Add the entry to Reports > System Reports
  public function checkPrtgMenu()  {
    $jsCustomFile = file_get_contents("C:\Program Files (x86)\PRTG Network Monitor\webroot\javascript\scripts_custom.js");
    $pattern ="/(\/\/.PRTG.Scheduler.menu.entry)/";
    $line = "$('#main_menu > li:nth-child(7) > ul > li:nth-child(4) > ul > li:nth-child(3)').after('<li><a href=\"/config_report_maintenances.htm\" class=\"nohjax\" target=\"_blank\">".$this->lang->line('menu_entry')."</a></li>'); // PRTG Scheduler menu entry";
    if(!(preg_match($pattern,$jsCustomFile))){
      $this->log('Writing Maintenance Windows menu entry',1);
      $jsCustomFile .= PHP_EOL.$line;
      file_put_contents("C:\Program Files (x86)\PRTG Network Monitor\webroot\javascript\scripts_custom.js",$jsCustomFile);
    }
  }

  # log the maintenance window for each object
  public function logObject($maintenanceWindow){
    $this->log(sprintf("Setup: %s",       join("|",$maintenanceWindow->maintenanceWindowDefinition)),4);
    $this->log(sprintf("ID: %s",          $maintenanceWindow->ID),5);
    $this->log(sprintf("Disabled: %s",    ($maintenanceWindow->disabled) ? 'true' : 'false'),5);
    $this->log(sprintf("Type: %s",        $maintenanceWindow->type),5);
    $this->log(sprintf("Description: %s", $maintenanceWindow->description),5);
    $this->log(sprintf("Timezone: %s",    $maintenanceWindow->timeZone),5);
    $this->log(sprintf("DateSort: %s",    $maintenanceWindow->startDateSortable),5);

    # output the actual human readable rrule in the configured language
    if(isset($maintenanceWindow->rruleReadable))
      $this->log(sprintf("Translates into: %s",    $maintenanceWindow->rruleReadable),5);

    $this->log(sprintf("Next Start (UTC): %s",  $maintenanceWindow->start_date->format("d/m/Y H:i")),5);
    $this->log(sprintf("Next End (UTC)  : %s",  $maintenanceWindow->end_date->format("d/m/Y H:i")),5);
    $this->log(sprintf("Duration        : %s",  $maintenanceWindow->duration),5);

  }


  /* Function Name: getPrtgUrl
#################################
Used For: This method provides access to various API methods.
#################################
Parameters:
- method: string
  The method can be one of the following:
  - setmaintenance - pause an object within PRTG
  - pauseforx      - pause an object within PRTG for x minutes
  - resume         - resume an object within PRTG
  - rproperty      - read given object property
  - wproperty      - write given value to given object property
  - groups         - retrieve all groups in PRTG that match a string
  - devices        - retrieve all devices in PRTG that match a string
  - sensors        - retrieve all sensors in PRTG that match a string
  - sensorstates   - retrieve all sensor states and their count
  - saveconfig     - save the current PRTG configuration, so we can force sync users
#################################*/
  public function getPrtgUrl(string $method)
  {
      switch ($method) {
          case "setmaintenance":
              $url = sprintf("%s://%s:%s/editsettings?id=%s&scheduledependency=0&maintenable_=1&maintstart_=%s&maintend_=%s&username=%s&passhash=%s",
              $this->config->item('prtg_protocol'),
              $this->config->item('prtg_server'),
              $this->config->item('prtg_port'),
              '%s', '%s', '%s',
              $this->config->item('prtg_username'),
              $this->config->item('prtg_passhash'));
              break;
          case "removemaintenance":
              $url = sprintf("%s://%s:%s/editsettings?id=%s&scheduledependency=0&maintenable_=0&username=%s&passhash=%s",
              $this->config->item('prtg_protocol'),
              $this->config->item('prtg_server'),
              $this->config->item('prtg_port'),
              '%s',
              $this->config->item('prtg_username'),
              $this->config->item('prtg_passhash'));
              break;
          case "pauseforx":
              $url = sprintf("%s://%s:%s/api/pauseobjectfor.htm?id=%s&pausemsg=%s&duration=%s&username=%s&passhash=%s",
              $this->config->item('prtg_protocol'),
              $this->config->item('prtg_server'),
              $this->config->item('prtg_port'),
              '%s', '%s', '%s',
              $this->config->item('prtg_username'),
              $this->config->item('prtg_passhash'));
              break;
          case "resume":
              $url = sprintf("%s://%s:%s/api/pauseobjectfor.htm?id=%s&action=1&username=%s&passhash=%s",
              $this->config->item('prtg_protocol'),
              $this->config->item('prtg_server'),
              $this->config->item('prtg_port'),
              '%s',
              $this->config->item('prtg_username'),
              $this->config->item('prtg_passhash'));
              break;
          case "wproperty":
               $url = sprintf("%s://%s:%s/api/setobjectproperty.htm?id=%s&name=%s&value=%s&username=%s&passhash=%s",
               $this->config->item('prtg_protocol'),
               $this->config->item('prtg_server'),
               $this->config->item('prtg_port'),
               '%s', '%s', '%s',
               $this->config->item('prtg_username'),
               $this->config->item('prtg_passhash'));
               break;
          case "rproperty":
              $url = sprintf("%s://%s:%s/api/getobjectproperty.htm?id=%s&name=%s&username=%s&passhash=%s",
              $this->config->item('prtg_protocol'),
              $this->config->item('prtg_server'),
              $this->config->item('prtg_port'),
              '%s', '%s',
              $this->config->item('prtg_username'),
              $this->config->item('prtg_passhash'));
              break;
          case "groups":
              $url = sprintf("%s://%s:%s/api/table.json?content=groups&output=json&columns=objid,basetype,name,device,probe,status,baselink,parentid,comments&filter_comments=@sub(maintenance)&sortby=-objid&username=%s&passhash=%s",
              $this->config->item('prtg_protocol'),
              $this->config->item('prtg_server'),
              $this->config->item('prtg_port'),
              $this->config->item('prtg_username'),
              $this->config->item('prtg_passhash'));
              break;
          case "devices":
              $url = sprintf("%s://%s:%s/api/table.json?content=devices&output=json&columns=objid,basetype,name,device,group,status,baselink,status,comments,parentid,probe&sortby=-objid&filter_comments=@sub(maintenance)&username=%s&passhash=%s",
              $this->config->item('prtg_protocol'),
              $this->config->item('prtg_server'),
              $this->config->item('prtg_port'),
              $this->config->item('prtg_username'),
              $this->config->item('prtg_passhash'));
              break;
          case "sensors":
              $url = sprintf("%s://%s:%s/api/table.json?content=sensors&output=json&columns=objid,basetype,name,device,group,status,baselink,probe,comments,parentid,probe,parent&sortby=-objid&filter_comments=@sub(maintenance)&username=%s&passhash=%s",
              $this->config->item('prtg_protocol'),
              $this->config->item('prtg_server'),
              $this->config->item('prtg_port'),
              $this->config->item('prtg_username'),
              $this->config->item('prtg_passhash'));
              break;
          default:
              echo "PRTG URL method not found.";
              break;
      }

      return $url;
  }
}
