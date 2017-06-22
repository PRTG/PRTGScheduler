<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Table_model extends CI_Model {

  public function generateMaintenanceTable($objects){

    # configure the table
    $this->table->set_heading(
      $this->lang->line('table_objid'),
      $this->lang->line('table_name'),
      $this->lang->line('table_group'),
      $this->lang->line('table_probe'),
      array('data' => $this->lang->line('table_nextMaintenance'), "colspan"=>3,'width'=>'400px', 'valign'=>'top'),
      array('data'=> $this->lang->line('table_nextStart'),'width'=>150),
      $this->lang->line('table_nextEnd'),
      $this->lang->line('table_duration'));

    $template = array('table_open' => '<table class="table hoverable" style="width: auto;">');
    $this->table->set_template($template);

    foreach($objects as $object)
    {

      $objidUrl    = "<a href='".$object->basetype.".htm?id=".$object->objid."'>".$object->objid."</a>";

      # we only need the device link when it's a sensor
      if($object->basetype == "sensor")
        $objName     = "<a href='".$object->basetype.".htm?id=".$object->objid."'>".$object->name."</a><br /><a href='/device.htm?id=".$object->parentid."'>".$object->device."</a>";
      else
        $objName     = "<a href='".$object->basetype.".htm?id=".$object->objid."'>".$object->name."</a>";

      $objDevice   = "<a href='".$object->basetype.".htm?id=".$object->objid."'>".$object->name."</a>";
      $objProbe    = "<a href=''>".$object->probe."</a>";
      $objGroup    = "<a href=''>".$object->group."</a>";

      # create the table headers including the corresponding rowspans
      $objid   = array('data' => $objidUrl,  'rowspan' => count($object->maintenanceWindows)+2, 'valign' => 'top',);
      $name    = array('data' => $objName,   'rowspan' => count($object->maintenanceWindows)+2, 'valign' => 'top' );
      $device  = array('data' => $objDevice, 'rowspan' => count($object->maintenanceWindows)+2, 'valign' => 'top' );
      $group   = array('data' => $objGroup,  'rowspan' => count($object->maintenanceWindows)+2, 'valign' => 'top' );
      $probe   = array('data' => $objProbe,  'rowspan' => count($object->maintenanceWindows)+2, 'valign' => 'top' );


      # the upcoming maintenance will be the top of the list
      # all maintenances for the object will be listed in a different table in the object row
      $upcomingMaintenance = $object->maintenanceWindows[key($object->maintenanceWindows)];

      # create the maintenance rows and add the start/end date of the maintenance as unix timestamp
      # this will then be converted to the local timezone of the browser that opens the maintenance overview
      # allowing to store maintenances in the timezone they should be executed at but display them in user's tz as well
      $nextMaintenance           = array('data' => $upcomingMaintenance->description, 'colspan' => 3, 'valign' => 'top' );
      $nextMaintenanceStart      = array('data' => '', 'class' => 'timestamp', 'data-timestamp' => $upcomingMaintenance->start_date->format('U'),   'valign' => 'top' );
      $nextMaintenanceEnd        = array('data' => '', 'class' => 'timestamp', 'data-timestamp' => $upcomingMaintenance->end_date->format('U'), 'valign' => 'top' );
      $nextMaintenanceDuration   = array('data' => $upcomingMaintenance->duration, 'valign' => 'top' );

      $this->table->add_row($objid,$name,$group,$probe,$nextMaintenance,$nextMaintenanceStart,$nextMaintenanceEnd,$nextMaintenanceDuration);
      $this->table->add_row(array(
          'data' => "<b>".$this->lang->line('table_description')."</b>", 'colspan' => 3 ),
          "<b>".$this->lang->line('table_nextStart')."</b>",
          "<b>".$this->lang->line('table_nextEnd')."</b>",
          "<b>".$this->lang->line('table_duration')."</b>");

      # now, every configured maintenance window of the object will be added to the table
      foreach($object->maintenanceWindows as $maintenanceWindow){


          $disabled = "";
        

        $this->table->add_row(
          array('data' => sprintf('%s<span class="label label-default">%s</span> <span class="label label-default">%s</span> %s<br />',$disabled, ucwords($maintenanceWindow->timeZone,'/'),$maintenanceWindow->type,$maintenanceWindow->description), 'colspan' => 3, 'title'=> ucfirst($maintenanceWindow->rruleReadable)),
          array('data' => '', 'class' => 'timestamp','data-timestamp' => $maintenanceWindow->start_date->format('U')),
          array('data' => '', 'class' => 'timestamp','data-timestamp' => $maintenanceWindow->end_date->format('U')),
          $maintenanceWindow->duration
        );
      }
    }
    if(empty($objects)){ return "<h4>".$this->lang->line('table_noMaintenances')."</h4>"; }
    else               { return $this->table->generate(); }
  }
}
