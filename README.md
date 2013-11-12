mage_scheduler
==============

Replaces the inferior Magento Scheduler by utilizing Celery Scheduler as a drop in replacement.

Description of Files
--------------------

* celery.py: The main application file launched by celery that starts each worker or the beat scheduler.
* only_one.py: Python decorator loaded in the __init__ section of the mage_scheduler module that allows marking a Celery Task run method as runnable once and only once.  It uses Redis locks with timeouts to control access.
* __init__.py: The __init__ method of the Celery module that loads the celeryconfig, only_one, and task files for easier module access.
* scheduler_task.php: This script sits in Magento's shell directory and when called uses the AOE_Scheduler module to build the tasks.py and celerconfig.py files.
* tasks.py: Script written by scheduler_task.php that contains all the magento scheduled tasks.
* celeryconfig.py: Celery configuration file that is also written by scheduler_tasks.php and contains periodic task calls, routing, and annotations.

Installation
------------
* Build a Redis Server.  A quick install for CentOS is below

```
wget http://dl.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm
wget http://centos.alt.ru/repository/centos/6/x86_64/centalt-release-6-1.noarch.rpm
rpm -Uvh epel-release-6-8.noarch.rpm centalt-release-6-1.noarch.rpm
yum -y install redis
vi /etc/redis.conf
################################
daemonize yes
pidfile /var/run/redis/redis.pid
port 6379
timeout 0
loglevel notice
logfile /var/log/redis/redis.log
databases 6
rdbcompression no
dbfilename dump.rdb
dir /var/lib/redis/
slave-serve-stale-data yes
save 60 60
maxmemory 7gb
maxmemory-policy volatile-lru
appendonly no
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb
slowlog-log-slower-than 10000
slowlog-max-len 1024
list-max-ziplist-entries 512
list-max-ziplist-value 64
set-max-intset-entries 512
zset-max-ziplist-entries 128
zset-max-ziplist-value 64
activerehashing yes
```
* Install Python prerequisites.

```
yum -y install python-pip.noarch gcc python-devel
```
* Install Celery with Redis, Flower, necessary modules

```
pip install pytz
pip install -U celery-with-redis
pip install flower
```
* Checkout the code from this repo

```
cd /opt
git clone https://github.com/viable-hartman/mage_scheduler
```
* Copy or link scheduler_task.php to your Magento installation's shell directory, and edit the file to configure it for your Redis install.

```
cp /opt/mage_scheduler/scheduler_task.php <MAGE DIR>/shell/
```
* On each worker download and configure the celeryd init script for your environment.

```
wget -O /etc/init.d/celeryd https://github.com/celery/celery/blob/3.1/extra/generic-init.d/celeryd
```
* On the beat server download and configure the beat init script for your environment.

```
wget -O /etc/init.d/celerybeat https://raw.github.com/celery/celery/3.1/extra/generic-init.d/celerybeat
```

Usage
-----
* Change to your Magento shell directory and execute the following command to build your environments task.py and celeryconfig.py Celery files.

```
php scheduler_task.php -action build -tfile "/opt/mage_scheduler/tasks.py" -file "/opt/mage_scheduler/celeryconfig.py"
```
* Now copy all files in /opt/mage_scheduler to the rest of your Magento "workers" and to a server you plan running Celery's beat dameon on.
* Test your setup as follows:

```
# On each worker
cd /opt
celery worker --app=mage_scheduler -l info -Q celery
# On the Celery beat server
cd /opt
celery beat --app=mage_scheduler -s /opt/mage_scheduler/scheduler.db
```
* Set your daemon and workers to run permanently.

```
# On each worker
chkconfig celeryd on
/etc/init.d/celeryd start
# On the Celery beat server
chkconfig celerybeat on
/etc/init.d/celerybeat on
```
* Check out whats happening with Celery Flower

```
# Launch the server
celery flower --broker=redis://localhost:6379/3
# Visit the server
http://localhost:5555
```
