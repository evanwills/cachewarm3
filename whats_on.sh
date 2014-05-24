#! /bin/sh

# ===============================================
#
# This script is used as a shortcut to find out what's going on with
# cachwarmer3.cli.php on the system.
#

# ===============================================
# @var string $cron_name the name of the unix user
#	running the cron job that calls
#	cachewarm_auto-restart.cron.sh 
cron_user='evan'

echo;
echo;
top -u "$cron_user";
echo;
echo $(date);
echo;
ps aux |grep 'cachewarm\|url' |grep -v 'grep'
echo;
echo;
echo;

log_file='log/cachewarm_auto-restart.log';
if [ -f $log_file ]
then	tail $log_file;
fi
