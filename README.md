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
* Install Python 2.7 and prerequisites.

```
yum -y groupinstall "Development tools"
yum -y install zlib-devel bzip2-devel openssl-devel ncurses-devel sqlite-devel readline-devel tk-devel
cd /opt
wget http://python.org/ftp/python/2.7.6/Python-2.7.6.tgz
wget --no-check-certificate https://pypi.python.org/packages/source/d/distribute/distribute-0.6.49.tar.gz
tar -xf Python-2.7.6.tgz
tar -xf distribute-0.6.49.tar.gz
cd /opt/Python-2.7.6
./configure --prefix=/usr/local
make && make altinstall
cd /usr/local/bin
ln -s /usr/local/bin/python2.7 /usr/local/bin/python

cd /opt/distribute-0.6.49
cd distribute-0.6.49
python2.7 setup.py install
```
* Easy Install Celery with Redis, Flower, necessary modules

```
/usr/local/bin/easy_install-2.7 --upgrade pytz
/usr/local/bin/easy_install-2.7 --upgrade celery-with-redis
/usr/local/bin/easy_install-2.7 --upgrade flower
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
cp /opt/mage_scheduler/etc/init.d/celeryd /etc/init.d/celeryd
chmod 755 /etc/init.d/celeryd
ln -s /etc/init.d/mage_scheduler /etc/init.d/celeryd
cp /opt/mage_scheduler/etc/default/mage_scheduler.celeryd /etc/default/mage_scheduler
```
* On the beat server download and configure the beat init script for your environment.

```
cp /opt/mage_scheduler/etc/init.d/celerybeat /etc/init.d/celerybeat
chmod 755 /etc/init.d/celerybeat
ln -s /etc/init.d/mage_scheduler /etc/init.d/celerybeat
cp /opt/mage_scheduler/etc/default/mage_scheduler.celerybeat /etc/default/mage_scheduler
```

* On the beat server setup the flower GUI to launch on restart

```
/usr/local/bin/celery flower --broker=redis://redis.somewhere.com:6379/3 >/dev/null 2>&1 &
```

* On all servers change the redis host or IP in the only_one.py file to your redis host or IP.  You will have to do this until I get around to unifying all files off of a single serialized config file.

* Add iptables rules to allow everything to communicate to the redis/flower server
* vi /etc/sysconfig/iptables (then restart iptables)

```
# Redis TCP Port
-A INPUT -m state --state NEW -m tcp -p tcp --dport 6379 -j ACCEPT
# Flower web GUI port
-A INPUT -m state --state NEW -m tcp -p tcp --dport 5555 -j ACCEPT
```

Usage
-----
* Change to your Magento shell directory and execute the following command to build your environments task.py and celeryconfig.py Celery files.

```
php scheduler_task.php -action build -tfile "/opt/mage_scheduler/tasks.py" \
                                     -file "/opt/mage_scheduler/celeryconfig.py" \
                                     -broker "redis.somewhere.com"
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
chkconfig mage_scheduler on
/etc/init.d/mage_scheduler start
# On the Celery beat server
chkconfig mage_scheduler on
/etc/init.d/mage_scheduler on
```
* Check out whats happening with Celery Flower

```
# Launch the server
celery flower --broker=redis://redis.somewhere.com:6379/3
# Visit the server
http://redis.somewhere.com:5555
```
