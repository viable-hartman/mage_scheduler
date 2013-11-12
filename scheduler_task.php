<?php

require_once 'abstract.php';

class Aoe_Scheduler_Shell_Scheduler_Task extends Mage_Shell_Abstract {
    const TIMEZONE = 'America/Toronto';
    const CELERY_ENABLE_UTC = 'True';
    const CELERY_TASK_SERIALIZER = 'json';
    const CELERY_RESULT_SERIALIZER = 'json';
    const BROKER_URL = 'redis://localhost:6379/3';
    const CELERY_RESULT_BACKEND = 'redis://localhost:6379/4';
    const PHP_PATH = '/usr/bin/php'; 

	/**
	 * Run script
	 * 
	 * @return void
	 */
	public function run() {
		$action = $this->getArg('action');
		if (empty($action)) {
			echo $this->usageHelp();
		} else {
			$actionMethodName = $action.'Action';
			if (method_exists($this, $actionMethodName)) {
				$this->$actionMethodName();
			} else {
				echo "Action $action not found!\n";
				echo $this->usageHelp();
				exit(1);
			}
		}
	}

	/**
	 * Retrieve Usage Help Message
	 *
	 * @return string
	 */
	public function usageHelp() {
		$help = 'Available actions: ' . "\n";
        $methods = get_class_methods($this);
		foreach ($methods as $method) {
			if (substr($method, -6) == 'Action') {
				$help .= '    -action ' . substr($method, 0, -6);
				$helpMethod = $method.'Help';
				if (method_exists($this, $helpMethod)) {
					$help .= $this->$helpMethod();
				}
				$help .= "\n";
			}
		}
		return $help;
    }

    private function getOnlyOne($job) {
        $settings = array(
            'aoecachecleaner' => array(
                'oo_timeout' => 300,
            )
        );
        return $settings[$job];
    }

    private function getAnnotations($job) {
        $settings = array(
            'aoecachecleaner' => array(
                'rate_limit' => '10/s',
            )
        );
        return $settings[$job];
    }

    private function getRouteSettings($job) {
        $settings = array(
            'aoecachecleaner' => 'web1queue'
        );
        return $settings[$job];
    }

	/**
	 * Create a Celery Config File
	 *
	 * @return void
	 */
    public function buildConfigFileAction() {
        $filename = $this->getArg('file');
        if (empty($filename)) {
            echo "\nNo filename found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }

        $f = @fopen($filename, 'w');
        fwrite($f, '#!/usr/bin/env python2.7'."\n\n");

        fwrite($f, 'from __future__ import absolute_import'."\n");
        fwrite($f, 'from celery.schedules import crontab'."\n");

        fwrite($f, 'BROKER_URL = \''.self::BROKER_URL.'\''."\n");
        fwrite($f, 'BROKER_TRANSPORT_OPTIONS = {\'visibility_timeout\': 3600}  # Seconds to wait before message is redelivered to another broker'."\n");
        fwrite($f, 'CELERY_TASK_SERIALIZER = \''.self::CELERY_TASK_SERIALIZER.'\''."\n");
        fwrite($f, 'CELERY_TIMEZONE = \''.self::TIMEZONE.'\''."\n");
        fwrite($f, 'CELERY_ENABLE_UTC = '.self::CELERY_ENABLE_UTC.''."\n\n");
        fwrite($f, '# List of modules to import when celery starts.'."\n");
        fwrite($f, 'CELERY_IMPORTS = ("mage_scheduler.tasks", )'."\n\n");
        fwrite($f, '# Using redis to store task state and results.'."\n");
        fwrite($f, 'CELERY_RESULT_SERIALIZER = \''.self::CELERY_RESULT_SERIALIZER.'\''."\n");
        fwrite($f, 'CELERY_RESULT_BACKEND = \''.self::CELERY_RESULT_BACKEND.'\''."\n\n");
        $collection = Mage::getModel('aoe_scheduler/collection_crons');
        // Add any routes that exist for this task
        fwrite($f, 'CELERY_ROUTES = {'."\n");
        foreach ($collection as $configuration) {
            if( $configuration->getStatus() == 'enabled' ) {
                $route = $this->getRouteSettings($configuration->getId());
                if($route) {
                    fwrite($f, '    \'tasks.'.$configuration->getId().'\': \''.$route.'\','."\n");
                }
            }
        }
        fwrite($f, '}'."\n");
        // Add any annotations that exists for this task
        fwrite($f, 'CELERY_ANNOTATIONS = {'."\n");
        foreach ($collection as $configuration) {
            if( $configuration->getStatus() == 'enabled' ) {
                $annotations = $this->getAnnotations($configuration->getId());
                if($annotations) {
                    fwrite($f, '    \'tasks.'.$configuration->getId().'\': {');
                    foreach ($annotations as $annotation => $v) {
                        fwrite($f, '\''.$annotation.'\': \''.$v.'\',');
                    }
                    fwrite($f, ' }'."\n");
                }
            }
        }
        fwrite($f, '}'."\n");
        // Add a Scheduler for this Task
        fwrite($f, 'CELERYBEAT_SCHEDULE = {'."\n");
        foreach ($collection as $configuration) {
            if( $configuration->getStatus() == 'enabled' ) {
                fwrite($f, '    \''.$configuration->getId().'\': {'."\n");
                fwrite($f, '        \'task\': \'mage_scheduler.tasks.'.$configuration->getId().'\','."\n");
                $cron = explode(" ", $configuration->getCronExpr(), -1);
                if( $cron ) {
                    fwrite($f, '        \'schedule\': crontab(minute=\''.$cron[0].'\', hour=\''.$cron[1].'\', day_of_month=\''.$cron[2].'\', month_of_year=\''.$cron[3].'\', day_of_week=\'*\'),'."\n");
                } else {
                    fwrite($f, '        \'schedule\': crontab(minute=\'*/1\', hour=\'*\', day_of_month=\'*\', month_of_year=\'*\', day_of_week=\'*\'),'."\n");
                }
                fwrite($f, '        \'args\': (\''.Mage::getBaseDir('base').'/shell\',),'."\n");
                fwrite($f, '    },'."\n");
            }
        }
        fwrite($f, '}'."\n");
        fclose($f);
	}

	/**
	 * Display extra help for buildConfigFile
	 *
	 * @return string
	 */
	public function buildConfigFileActionHelp() {
		return " -file <file>\t\tCreate a Config File";
    }

	/**
     * Create a Celery Task File
     *
     * In the next version phpexe should be an abstract celery task class, and
     * all custom tasks should extend it and call phpexe's run function with a
     * code argument, but this is quicker for now.
	 *
	 * @return void
	 */
    public function buildTaskFileAction() {
        $filename = $this->getArg('tfile');
        if (empty($filename)) {
            echo "\nNo filename found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }

        $f = @fopen($filename, 'w');
        fwrite($f, '#!/usr/bin/env python2.7'."\n\n");

        fwrite($f, 'from __future__ import absolute_import'."\n");
        fwrite($f, 'from celery.utils.log import get_task_logger'."\n");
        fwrite($f, 'from mage_scheduler.celery import celery'."\n");
        fwrite($f, 'from mage_scheduler.only_one import only_one'."\n");
        fwrite($f, 'from subprocess import Popen, PIPE'."\n");
        fwrite($f, 'from celery.task import Task'."\n\n");
        fwrite($f, 'logger = get_task_logger(__name__)');

        $collection = Mage::getModel('aoe_scheduler/collection_crons');
        foreach ($collection as $configuration) {
            if( $configuration->getStatus() == 'enabled' ) {
                $oo = $this->getOnlyOne($configuration->getId());
                fwrite($f, "\n\n\n".'@celery.task'."\n");
                fwrite($f, 'class '.$configuration->getId().'(Task):'."\n");
                if($oo) {
                    fwrite($f, '    @only_one(key="'.$configuration->getId().'", timeout='.$oo['oo_timeout'].')'."\n");
                }
                fwrite($f, '    def run(self, shell_dir):'."\n");
                fwrite($f, '        cmd = \'scheduler.php -action runNow -code '.$configuration->getId().'\''."\n");
                fwrite($f, '        logger.info(cmd)'."\n");
                fwrite($f, '        p = Popen([cmd], executable=\''.self::PHP_PATH.'\', stdout=PIPE, stderr=PIPE, cwd=shell_dir)'."\n");
                fwrite($f, '        output = p.communicate()[0]'."\n");
                fwrite($f, '        if p.returncode > 0:'."\n");
                fwrite($f, '            self.update_state(state=\'FAILURE\')'."\n");
                fwrite($f, '            if output:'."\n");
                fwrite($f, '                raise Exception(output)'."\n");
                fwrite($f, '            else:'."\n");
                fwrite($f, '                raise Exception(\'Unknown\')'."\n");
                fwrite($f, '        return output');
            }
        }
        fclose($f);
	}

	/**
	 * Display extra help for buildTaskFile
	 *
	 * @return string
	 */
	public function buildTaskFileActionHelp() {
		return " -tfile <file>\t\tCreate a Task File";
    }

	/**
	 * BuildTaskFile and BuildConfigFile
	 *
	 * @return string
	 */
    public function buildAction() {
        $farg = $this->getArg('file');
        $targ = $this->getArg('tfile');
        if (empty($farg) || empty($targ)) {
            echo "\nNo File name(s) found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }
        $this->buildTaskFileAction();
        $this->buildConfigFileAction();
    }

	/**
	 * Display extra help for build
	 *
	 * @return string
	 */
	public function buildActionHelp() {
		return " -file <file> -tfile <file>\tCreate both the task file and configuration file.";
    }
}

$shell = new Aoe_Scheduler_Shell_Scheduler_Task();
$shell->run();
