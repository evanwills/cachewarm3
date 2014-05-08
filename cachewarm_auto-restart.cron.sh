#! /bin/sh

# ==========================
# How many instances of cachewarmer3.cli.php can be run at one time
max_instances=5;

# ==========================
# The path to the directory this script is stored in
path="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";

# ==========================
# The number of cachewarmer3.cli.php instances currently running
running=$( ps aux | grep 'cachewarmer3.cli.php' |grep -vc 'grep' );

if [ $running -lt $max_instances ]
then	# ==========================
	# We could be running more instances of cachewarmer3.cli.php
	# Let's do it!

	php -f $path/cachewarmer3.cli.php &

#	echo;
#	echo "We can have up to $max_instances instances of the cachewarmer";
#	echo "running at any one time. We only have $running instances"
#	echo 'running at the moment, so we have started a new one'
#	echo;
	echo 'last instance started at '$( date ) >> $path/log/cachewarm_auto-restart.log;
fi
