#!/usr/bin/env python2.7

from __future__ import absolute_import
from celery.utils.log import get_task_logger
from mage_scheduler.celery import celery
from mage_scheduler.only_one import only_one
from subprocess import Popen, PIPE
from celery.task import Task

logger = get_task_logger(__name__)


@celery.task
class Responsys_exportcustomers(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code Responsys_exportcustomers'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class Responsys_exportorders(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code Responsys_exportorders'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class aoecachecleaner(Task):
    @only_one(key="aoecachecleaner", timeout=300)
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code aoecachecleaner'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class aoecachecleaner_cleanimages(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code aoecachecleaner_cleanimages'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class aoecachecleaner_cleanmedia(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code aoecachecleaner_cleanmedia'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class aoecachecleaner_flushall(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code aoecachecleaner_flushall'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class aoecachecleaner_flushsystem(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code aoecachecleaner_flushsystem'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class intelligenteye(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code intelligenteye'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class quotesys_expire_email(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code quotesys_expire_email'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class send_quotesys_expiry_reminder_email(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code send_quotesys_expiry_reminder_email'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class send_quotesys_expiry_reminder_email2(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code send_quotesys_expiry_reminder_email2'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output


@celery.task
class update_quotesys_status(Task):
    def run(self, shell_dir):
        cmd = 'scheduler.php -action runNow -code update_quotesys_status'
        logger.info(cmd)
        p = Popen([cmd], executable='/usr/bin/php', stdout=PIPE, stderr=PIPE, cwd=shell_dir)
        output = p.communicate()[0]
        if p.returncode > 0:
            self.update_state(state='FAILURE')
            if output:
                raise Exception(output)
            else:
                raise Exception('Unknown')
        return output
