#!/bin/bash

# ===============================================
# @file url_list_builder.sh
# @author Evan Wills
# @date 2016-02-12 
#
# Because of the large number of URLs, Matrix can
# no longer generate the source_list file and so
# returns a 500 error. To get around this, I've
# divided the source_list listing up into a dozen
# or so separate listings. This script downloads
# each individual listing and assembles them into
# a single file listing all (currently) 17,000+
# URLs to be warmed.
#
# ===============================================

## ----------------------------------------------
# @var string $sourceURL the URL to be used to
#	get all the other source_list URLs.
#	specified in the config.php file
##
sourceURL=$( echo $( grep '^$parent_source_list\s*=\s*' config.php | awk '{gsub( /\$parent_source_list = .|\/?.;/ , "" ); print;}' ) );


## ----------------------------------------------
# @var string $output the file location to store
#	the output of this script.
#	specified in the config.php file
##
output=$( echo $( grep '^$source_list\s*=\s*' config.php | awk '{gsub( /\$source_list = .|.;/ , "" ); print;}' ) );


## ----------------------------------------------
# @var string $sourceFile path to file for
#	storing the downloaded contents of the parent
#	source list
##
sourceFile='tmpParentURLlist';


## ----------------------------------------------
# @var string $tmp path to file for storing the
#	accumulated list of URLs before it's cleaned
#	up and permanently stored at $output
##
tmp='tmpURLlist';


# ================================================

# Download the parent source URL list and store it
# on the file system
curl -sL $sourceURL > $sourceFile;


# Loop through each line of the URLs in the parent
# source list
while IFS='' read -r line || [[ -n "$line" ]];
do
	# strip white space from the begining and end of
	# the line and that it starts with HTTP:// or
	# HTTPS://
	line=$( echo $line | awk '{gsub(/^[[:cntrl:][:space:]]+|[[:cntrl:][:space:]]+$/,""); print;}' | grep -i '^https\?://' );

	# Check if the line is empty
	if [ ! -z $line ]
	then
		# ================================================
		# We have a valid URL add its contents and add
		# that to the list of URLs
		curl -s $line >> $tmp
	fi
done < "$sourceFile"

# Put all lines starting with HTTP into the output file
grep 'http.\+/' $tmp > $output;

# delete the temporary list of URLs
if [ -f $tmp ]
then	rm $tmp;
fi

# delete the temporary list of source URLs
if [ -f $sourceFile ]
then	# echo about to delete $sourceFile;
	rm $sourceFile;
fi

#ls -alh resources/;
