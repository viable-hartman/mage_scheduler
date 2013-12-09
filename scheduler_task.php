<?php

require_once 'abstract.php';

class Aoe_Scheduler_Shell_Scheduler_Task extends Mage_Shell_Abstract {
    const PHP_PATH = '/usr/bin/php'; 

	/**
	 * Run script
	 * 
	 * @return void
	 */
	public function run() {
		$action = $this->getArg('action');
		$configf = $this->getArg('config');
		if (empty($action) || empty($configf)) {
			echo $this->usageHelp();
		} else {
			$actionMethodName = $action.'Action';
			if (method_exists($this, $actionMethodName)) {
                if( !file_exists($configf) ) { echo "Config file $configf not found!"; exit(1); }
                $config = json_decode(file_get_contents($configf));
				$this->$actionMethodName($config);
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
        /*
        $settings = array(
            'aoecachecleaner' => array(
                'oo_timeout' => 300,
            )
        );
        return $settings[$job];
        */
        return array('oo_timeout' => 18000);
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
            'aoecachecleaner' => 'web1queue',
            'catalog_product_index_price_reindex_all' => 'web1queue',
            'enterprise_catalog_index_refresh_price' => 'web1queue',
            'enterprise_index_clean_changelog' => 'web1queue',
            'enterprise_refresh_index' => 'web1queue',
            'enterprise_search_index_reindex_all' => 'web1queue',
            'enterprise_targetrule_index_reindex' => 'web1queue',
            'M2ePro_cron' => 'web1queue',
            'Edi_File_Processors' => 'web1queue',
            'reindexall' => 'web1queue'
        );
        return $settings[$job];
    }

	/**
	 * Create a Celery Config File
	 *
	 * @return void
	 */
    public function buildConfigFileAction($config) {
        $filename = $config->file;
        if (empty($filename)) {
            echo "\nBuildConfigFileAction: $filename not found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }
        if (empty($config->host)) {
            echo "\nNo broker host provided!\n\n";
            echo $this->usageHelp();
            exit(2);
        }

        $f = @fopen($filename, 'w');
        fwrite($f, '#!/usr/bin/env python2.7'."\n\n");

        fwrite($f, 'from __future__ import absolute_import'."\n");
        fwrite($f, 'from celery.schedules import crontab'."\n");

        fwrite($f, 'BROKER_URL = \'redis://'.$config->host.':'.$config->port.'/'.$config->db.'\''."\n");
        fwrite($f, 'BROKER_TRANSPORT_OPTIONS = {\'visibility_timeout\': 3600}  # Seconds to wait before message is redelivered to another broker'."\n");
        fwrite($f, 'CELERY_TASK_SERIALIZER = \''.$config->task_serializer.'\''."\n");
        fwrite($f, 'CELERY_TIMEZONE = \''.$config->timezone.'\''."\n");
        fwrite($f, 'CELERY_ENABLE_UTC = '.$config->utc.''."\n\n");
        fwrite($f, '# List of modules to import when celery starts.'."\n");
        fwrite($f, 'CELERY_IMPORTS = ("mage_scheduler.tasks", )'."\n\n");
        fwrite($f, '# Using redis to store task state and results.'."\n");
        fwrite($f, 'CELERY_RESULT_SERIALIZER = \''.$config->result_serializer.'\''."\n");
        fwrite($f, 'CELERYD_CONCURRENCY = \''.$config->concurrency.'\''."\n");
        fwrite($f, '# CELERY_RESULT_BACKEND = \''.$config->behost.':'.$config->beport.'/'.$config->bedb.'\''."\n\n");
        $collection = Mage::getModel('aoe_scheduler/collection_crons');
        // Add any routes that exist for this task
        fwrite($f, 'CELERY_ROUTES = {'."\n");
        foreach ($collection as $configuration) {
            if( $configuration->getStatus() == 'enabled' ) {
                $route = $this->getRouteSettings($configuration->getId());
                if($route) {
                    fwrite($f, '    \'mage_scheduler.tasks.'.$configuration->getId().'\': {\'queue\': \''.$route.'\'},'."\n");
                }
            }
        }
        // Addition of reindexall
        fwrite($f, '    \'mage_scheduler.tasks.reindexall\': {\'queue\': \''.$this->getRouteSettings('reindexall').'\'},'."\n");
        fwrite($f, '}'."\n");
        // Add any annotations that exists for this task
        fwrite($f, 'CELERY_ANNOTATIONS = {'."\n");
        foreach ($collection as $configuration) {
            if( $configuration->getStatus() == 'enabled' ) {
                $annotations = $this->getAnnotations($configuration->getId());
                if($annotations) {
                    fwrite($f, '    \'mage_scheduler.tasks.'.$configuration->getId().'\': {');
                    foreach ($annotations as $annotation => $v) {
                        fwrite($f, '\''.$annotation.'\': \''.$v.'\',');
                    }
                    fwrite($f, ' }'."\n");
                }
            }
        }
        // Add any annotations for reindexall
        $annotations = $this->getAnnotations('reindexall');
        if($annotations) {
            fwrite($f, '    \'mage_scheduler.tasks.reindexall\': {');
            foreach ($annotations as $annotation => $v) {
                fwrite($f, '\''.$annotation.'\': \''.$v.'\',');
            }
            fwrite($f, ' }'."\n");
        }
        fwrite($f, '}'."\n");
        // Add a Scheduler for this Task
        fwrite($f, 'CELERYBEAT_SCHEDULE = {'."\n");
        foreach ($collection as $configuration) {
            if( $configuration->getStatus() == 'enabled' ) {
                $cron = explode(" ", $configuration->getCronExpr(), -1);
                if( $cron ) {
                    fwrite($f, '    \''.$configuration->getId().'\': {'."\n");
                    fwrite($f, '        \'task\': \'mage_scheduler.tasks.'.$configuration->getId().'\','."\n");
                    fwrite($f, '        \'schedule\': crontab(minute=\''.$cron[0].'\', hour=\''.$cron[1].'\', day_of_month=\''.$cron[2].'\', month_of_year=\''.$cron[3].'\', day_of_week=\'*\'),'."\n");
                //} else {
                //    fwrite($f, '        \'schedule\': crontab(minute=\'*/1\', hour=\'*\', day_of_month=\'*\', month_of_year=\'*\', day_of_week=\'*\'),'."\n");
                    fwrite($f, '        \'args\': (\''.Mage::getBaseDir('base').'/shell\',),'."\n");
                    fwrite($f, '    },'."\n");
                }
            }
        }
        // Add Schedule for reindexall
        $cron = array("0", "3", "*", "*", "*");
        if( $cron ) {
            fwrite($f, '    \'reindexall\': {'."\n");
            fwrite($f, '        \'task\': \'mage_scheduler.tasks.reindexall\','."\n");
            fwrite($f, '        \'schedule\': crontab(minute=\''.$cron[0].'\', hour=\''.$cron[1].'\', day_of_month=\''.$cron[2].'\', month_of_year=\''.$cron[3].'\', day_of_week=\''.$cron[4].'\'),'."\n");
            fwrite($f, '        \'args\': (\''.Mage::getBaseDir('base').'/shell\',),'."\n");
            fwrite($f, '    },'."\n");
        }
        ///////
        fwrite($f, '}'."\n");
        fclose($f);
	}

	/**
	 * Display extra help for buildConfigFile
	 *
	 * @return string
	 */
	public function buildConfigFileActionHelp() {
		return " -config <file>\tCreate Celery config file.";
    }


    /**
     * print out individual Task info.
     */
    private function getTaskStr($id, $php_cmd, $args) {
        $oo = $this->getOnlyOne($id);
        $taskstr = "\n\n\n".'@celery.task'."\n";
        $taskstr .= 'class '.$id.'(Task):'."\n";
        if($oo) {
            $taskstr .= '    @only_one(key="'.$id.'", timeout='.$oo['oo_timeout'].')'."\n";
        }
        $taskstr .= '    def run(self, shell_dir):'."\n";
        $taskstr .= '        cmd = \'%s/'.$php_cmd.' '.implode(' ', $args).'\' % (shell_dir)'."\n";
        $taskstr .= '        logger.info(cmd)'."\n";
        $taskstr .= '        os.chdir(shell_dir)'."\n";
        $taskstr .= '        retcode = os.system(\''.self::PHP_PATH.' %s\' % (cmd))'."\n";
        $taskstr .= '        if retcode > 0:'."\n";
        $taskstr .= '            raise Exception(\'Error: %d\' % (retcode))'."\n";
        $taskstr .= '        return \'SUCCESS\'';
        return $taskstr;
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
    public function buildTaskFileAction($config) {
        $filename = $config->tfile;
        if (empty($filename)) {
            echo "\nBuildTaskFileAction: $filename not found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }

        $f = @fopen($filename, 'w');
        fwrite($f, '#!/usr/bin/env python2.7'."\n\n");

        fwrite($f, 'from __future__ import absolute_import'."\n");
        fwrite($f, 'from celery.utils.log import get_task_logger'."\n");
        fwrite($f, 'from celery.task import Task'."\n");
        fwrite($f, 'from mage_scheduler.celery import celery'."\n");
        fwrite($f, 'from mage_scheduler.only_one import only_one'."\n");
        fwrite($f, 'import os'."\n\n");
        fwrite($f, 'logger = get_task_logger(__name__)');

        $collection = Mage::getModel('aoe_scheduler/collection_crons');
        foreach ($collection as $configuration) {
            if( $configuration->getStatus() == 'enabled' ) {
                fwrite($f,
                    $this->getTaskStr($configuration->getId(),
                        'scheduler.php',
                        array('-action runNow', '-code '.$configuration->getId())));
            }
        }
        fwrite($f, $this->getTaskStr('reindexall', 'indexer.php', array('reindexall')));
        fclose($f);
	}

	/**
	 * Display extra help for buildTaskFile
	 *
	 * @return string
	 */
	public function buildTaskFileActionHelp() {
		return " -config <file>\tCreate a Celery tasks file.";
    }

	/**
	 * BuildTaskFile and BuildConfigFile
	 *
	 * @return string
	 */
    public function buildAction($config) {
        $farg = $config->file;
        $targ = $config->tfile;
        if (empty($farg) || empty($targ)) {
            echo "\nNo File name(s) found!\n\n";
            echo $this->usageHelp();
            exit(1);
        }
        if (empty($config->host)) {
            echo "\nNo broker host provided!\n\n";
            echo $this->usageHelp();
            exit(2);
        }
        $this->buildTaskFileAction($config);
        $this->buildConfigFileAction($config);
    }

	/**
	 * Display extra help for build
	 *
	 * @return string
	 */
	public function buildActionHelp() {
		return " -config <file>\t\tCreate both the Celery task and config files.";
    }
}

$shell = new Aoe_Scheduler_Shell_Scheduler_Task();
$shell->run();
