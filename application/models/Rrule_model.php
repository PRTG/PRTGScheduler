<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use RRule\RRule;

class Rrule_model extends CI_Model {

    public function createRrule($maintenanceWindow){

      # set the time to UTC first
      $rrule_array = array();
      $timeZone   = new DateTimeZone(ucwords($maintenanceWindow->timeZone,'/'));

      if(is_string(explode("-",$maintenanceWindow->repetition)[2]))
      $dtStart = DateTime::createFromFormat('d/m/Y H:i',explode("-",$maintenanceWindow->repetition)[2]." ".$maintenanceWindow->startTime,$timeZone);

      date_default_timezone_set('UTC');

      # we only need to set byday,bymonth, and bymonthday when anything
      # but "all" is set omitting them will result in all months matching

      if($maintenanceWindow->weekDays  !== "all")
      { $rrule_array['byday'] = strtoupper($maintenanceWindow->weekDays); }

      if($maintenanceWindow->months    !== "all")
      { $rrule_array['bymonth'] = $maintenanceWindow->months; }

      if($maintenanceWindow->monthDays !== "all")
      { $rrule_array['bymonthday'] = $maintenanceWindow->monthDays; }

      $rrule_array['freq']     = explode("-",$maintenanceWindow->repetition)[0];
      $rrule_array['interval'] = explode("-",$maintenanceWindow->repetition)[1];

      if($dtStart instanceof DateTime)
      { $rrule_array['dtstart']  = $dtStart; }

      # add hours and minutes to the rrule and cast them to integer, to omit leading 0s
      $rrule_array['byhour']   = (int)(explode(":",$maintenanceWindow->startTime))[0];
      $rrule_array['byminute'] = (int)(explode(":",$maintenanceWindow->startTime))[1];
      $rrule_array['wkst']     = $this->config->item('weekstart');

      return new RRule($rrule_array);

    }

    /* Function Name: convertMaintenanceTime
  	#################################
  	Used For: convert the given maintenance time
  	#################################*/
  	public function convertMaintenanceTime($maintenanceWindow){

      # ne need to convert the time as UTC
      $timeZone = new DateTimeZone('UTC');

        if(isset($maintenanceWindow->rrule)){

          $timeZone   = new DateTimeZone($maintenanceWindow->timeZone);
          $now = DateTime::createFromFormat('d/m/Y H:i',date(),$timeZone);
          # get the next occurrence based on the current date
          $start_string = sprintf("%s %s",
                          $maintenanceWindow->rrule->getOccurrencesBetween($now,null,1)[0]->format('d/m/Y H:i'),
                          $maintenanceWindow->timeZone);
          $end_string   = sprintf("%s %s %s",
                          $maintenanceWindow->rrule->getOccurrencesBetween($now,null,1)[0]->format('d/m/Y'),
                          $maintenanceWindow->endTime,
                          $maintenanceWindow->timeZone);
    		}
        else
        { $start_string = sprintf("%s %s %s",
                          $maintenanceWindow->startDate,
                          $maintenanceWindow->startTime,
                          $maintenanceWindow->timeZone);

          # we'll need to discern if we have an actual end-date already, then it's the actual end, otherwise it's the start date
          if(isset($maintenanceWindow->endDate))
          { $end_string   = sprintf("%s %s %s",
                            $maintenanceWindow->endDate,
                            $maintenanceWindow->endTime,
                            $maintenanceWindow->timeZone);
          }
          else
          { $end_string   = sprintf("%s %s %s",
                            $maintenanceWindow->startDate,
                            $maintenanceWindow->endTime,
                            $maintenanceWindow->timeZone);
          }
        }

  		$date_start = DateTime::createFromFormat("d/m/Y H:i e", $start_string);
  		$date_end   = DateTime::createFromFormat("d/m/Y H:i e", $end_string);

      $date_start->setTimeZone($timeZone);
  		$date_end->setTimeZone($timeZone);

  		# if the end date is smaller than the start date, the maintenance ends the next day
  		if(($date_end < $date_start) && (!(isset($maintenanceWindow->endDate))))
  		{ $date_end->modify('+1 day'); }

			# configure the object accordingly
			$maintenanceWindow->start_date	  = $date_start;
			$maintenanceWindow->end_date      = $date_end;

      # calculate the duration
      $duration                         = $date_end->diff($date_start);
      $durationArray                    = array();

      # fill the array with the values that are above 0, everything else can be ommitted.
      if($duration->y > 0) { $durationArray['Years']   = $duration->y." ".$this->lang->line('duration_years'); }
      if($duration->m > 0) { $durationArray['Months']  = $duration->m." ".$this->lang->line('duration_months'); }
      if($duration->d > 0) { $durationArray['Days']    = $duration->d." ".$this->lang->line('duration_days'); }
      if($duration->h > 0) { $durationArray['Hours']   = $duration->h." ".$this->lang->line('duration_hours'); }
      if($duration->i > 0) { $durationArray['Minutes'] = $duration->i." ".$this->lang->line('duration_minutes'); }

      $maintenanceWindow->duration      = join(", ", $durationArray);


      return $maintenanceWindow;
    }
}
