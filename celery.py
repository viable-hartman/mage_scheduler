#!/usr/bin/python

from __future__ import absolute_import
from celery import Celery

#celery = Celery('mage_scheduler.celery', include=['mage_scheduler.tasks'])
celery = Celery()
# celery.conf.update( CELERY_TASK_SERIALIZER='json', )
celery.config_from_object('mage_scheduler.celeryconfig')

# Optional configuration, see the application user guide.
#celery.conf.update(
#        CELERY_TASK_RESULT_EXPIRES=3600,
#)

if __name__ == '__main__':
        celery.start()
