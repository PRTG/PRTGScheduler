<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PrtgScheduler extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */

	 public function __construct(){
        parent::__construct();
				$this->lang->load('scheduler_lang', $this->config->item('preferred_language'));
	}


	public function index()
	{
		echo "Hello World!";
	}

	public function setMaintenance(){

			if($this->config->item('debug') === True){
				echo "<pre>";
			}
			header('content-type: text/html; charset=utf-8');

			$this->maintenance_model->log(sprintf("PHP %s | CodeIgniter %s | PRTG Scheduler %s", phpversion(), CI_VERSION, $this->config->item("app_version")),0);
			$this->maintenance_model->checkPrtgMenu();

			# lets generate the maintenance windows first
			$sensorMaintenances = $this->maintenance_model->generateMaintenance("sensors");
			$deviceMaintenances = $this->maintenance_model->generateMaintenance("devices");
			$groupMaintenances  = $this->maintenance_model->generateMaintenance("groups");

			# add the maintenance windows to the PRTG channels
			$prtgChannels[$this->lang->line('channel_sensorMaintenances')]    = count($sensorMaintenances[0]);
			$prtgChannels[$this->lang->line('channel_deviceMaintenances')]    = count($deviceMaintenances[0]);
			$prtgChannels[$this->lang->line('channel_groupMaintenances')]     = count($groupMaintenances[0]);
			$prtgChannels[$this->lang->line('channel_totalMaintenances')]     = count($sensorMaintenances[0]) + count($deviceMaintenances[0]) + count($groupMaintenances[0]);
			$prtgChannels[$this->lang->line('channel_erroneousDefinitions')]  = array_merge((array)$sensorMaintenances[1], (array)$deviceMaintenances[1], (array)$groupMaintenances[1])['count'];

			# now set the maintenances
			$this->maintenance_model->setMaintenance($sensorMaintenances[0]);
			$this->maintenance_model->setMaintenance($deviceMaintenances[0]);
			$this->maintenance_model->setMaintenance($groupMaintenances[0]);

			# now we create the tables for each object type
			$data['sensors'] = $this->table_model->generateMaintenanceTable($sensorMaintenances[0]);
			$data['devices'] = $this->table_model->generateMaintenanceTable($deviceMaintenances[0]);
			$data['groups']  = $this->table_model->generateMaintenanceTable($groupMaintenances[0]);

			# join the defective maintenance windows, then we'll create the PRTG output
			$this->maintenance_model->prtgOut($prtgChannels,$defectMaintenanceWindows);

			# generate the maintenance report
			file_put_contents("C:\Program Files (x86)\PRTG Network Monitor\webroot\config_report_maintenances.htm",(string)$this->load->view('prtg',$data,True));


	}

}
