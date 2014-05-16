#! /bin/sh

# ==========================
# How many instances of cachewarmer3.cli.php can be run at one time
max_instances=1;

# ==========================
# The path to the directory this script is stored in
path="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )";

# ==========================
# The number of cachewarmer3.cli.php instances currently running
running=$( ps aux | grep 'cachewarmer3.cli.php' |grep -vc 'grep' );

# ==========================
# Lets see if we can run more than the default number of instances
case $1 in
	''|*[!0-9]) # Input was not a number
		;;
	*)	# Input was a number but was it greater than 1?
		if [ $1 -gt 0 ]
		then	# Yes! Lets reset our max instances to the
			# user defined limit
			max_instances=$1;
		fi
		;;
esac

if [ $running -lt $max_instances ]
then	# ==========================
	# We could be running more instances of cachewarmer3.cli.php
	# Let's do it!

	php -f $path/cachewarmer3.cli.php $running &

#	echo;
#	echo "We can have up to $max_instances instances of the cachewarmer";
#	echo "running at any one time. We only have $running instances"
#	echo 'running at the moment, so we have started a new one'
#	echo;
	echo 'last instance started at '$( date ) >> $path/log/cachewarm_auto-restart.log;
fi
