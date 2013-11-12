#!/usr/bin/python

from __future__ import absolute_import
import redis

#POOL = redis.ConnectionPool(max_connections=4, host='localhost', db=5, port=6379)
#REDIS_CLIENT = redis.Redis(connection_pool=POOL)
REDIS_CLIENT = redis.Redis(host='localhost', db=5, port=6379)


def only_one(function=None, key="", timeout=None):
    """Enforce only one celery task at a time."""

    def _dec(run_func):
        """Decorator."""

        def _caller(*args, **kwargs):
            """Caller."""
            ret_value = None
            have_lock = False
            lock = REDIS_CLIENT.lock(key, timeout=timeout)
            try:
                have_lock = lock.acquire(blocking=False)
                if have_lock:
                    ret_value = run_func(*args, **kwargs)
            finally:
                if have_lock:
                    lock.release()

            return ret_value

        return _caller

    return _dec(function) if function is not None else _dec
