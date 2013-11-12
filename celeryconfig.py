#!/usr/bin/python

from __future__ import absolute_import
from celery.schedules import crontab
BROKER_URL = 'redis://localhost:6379/3'
BROKER_TRANSPORT_OPTIONS = {'visibility_timeout': 3600}  # Seconds to wait before message is redelivered to another broker
CELERY_TASK_SERIALIZER = 'json'
CELERY_TIMEZONE = 'America/Toronto'
CELERY_ENABLE_UTC = True

# List of modules to import when celery starts.
CELERY_IMPORTS = ("mage_scheduler.tasks", )

# Using redis to store task state and results.
CELERY_RESULT_SERIALIZER = 'json'
CELERY_RESULT_BACKEND = 'redis://localhost:6379/4'

CELERY_ROUTES = {
    'tasks.aoecachecleaner': 'web1queue',
}
CELERY_ANNOTATIONS = {
    'tasks.aoecachecleaner': {'rate_limit': '10/s', }
}
CELERYBEAT_SCHEDULE = {
    'Responsys_exportcustomers': {
        'task': 'mage_scheduler.tasks.Responsys_exportcustomers',
        'schedule': crontab(minute='01', hour='0', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'Responsys_exportorders': {
        'task': 'mage_scheduler.tasks.Responsys_exportorders',
        'schedule': crontab(minute='02', hour='0', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'aoecachecleaner': {
        'task': 'mage_scheduler.tasks.aoecachecleaner',
        'schedule': crontab(minute='18', hour='*', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'aoecachecleaner_cleanimages': {
        'task': 'mage_scheduler.tasks.aoecachecleaner_cleanimages',
        'schedule': crontab(minute='*/1', hour='*', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'aoecachecleaner_cleanmedia': {
        'task': 'mage_scheduler.tasks.aoecachecleaner_cleanmedia',
        'schedule': crontab(minute='*/1', hour='*', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'aoecachecleaner_flushall': {
        'task': 'mage_scheduler.tasks.aoecachecleaner_flushall',
        'schedule': crontab(minute='*/1', hour='*', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'aoecachecleaner_flushsystem': {
        'task': 'mage_scheduler.tasks.aoecachecleaner_flushsystem',
        'schedule': crontab(minute='*/1', hour='*', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'intelligenteye': {
        'task': 'mage_scheduler.tasks.intelligenteye',
        'schedule': crontab(minute='*', hour='22', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'quotesys_expire_email': {
        'task': 'mage_scheduler.tasks.quotesys_expire_email',
        'schedule': crontab(minute='01', hour='22', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'send_quotesys_expiry_reminder_email': {
        'task': 'mage_scheduler.tasks.send_quotesys_expiry_reminder_email',
        'schedule': crontab(minute='01', hour='20', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'send_quotesys_expiry_reminder_email2': {
        'task': 'mage_scheduler.tasks.send_quotesys_expiry_reminder_email2',
        'schedule': crontab(minute='01', hour='21', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
    'update_quotesys_status': {
        'task': 'mage_scheduler.tasks.update_quotesys_status',
        'schedule': crontab(minute='01', hour='19', day_of_month='*', month_of_year='*', day_of_week='*'),
        'args': ('<SOME_DIR_PATH>/shell',),
    },
}
