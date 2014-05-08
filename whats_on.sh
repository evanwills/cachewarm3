#! /bin/sh
echo $(date);
ps auxh |grep 'cachewarm\|url' |grep -v 'grep'
