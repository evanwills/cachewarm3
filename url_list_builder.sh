#!/bin/bash

# get the contents of the URL specified in the config.php file
sourceURL=$( echo $( grep '^$source_list\s*=\s*' config.php | awk '{gsub( /\$source_list = .|..;/ , "" ); print;}' ) );
sourceFile='tmp_listing';
#echo 'about to curl -vvL '$sourceURL;

curl -L $sourceURL > $sourceFile;

tmp='tmpURLlist';
output='resources/list_all';
ok=0;

while IFS='' read -r line || [[ -n "$line" ]];
do
	line=$( echo $line | awk '{gsub(/^[[:cntrl:][:space:]]+|[[:cntrl:][:space:]]+$/,""); print;}' );
	if [ ! -z $line ]
	then	# echo "about to grab '$line'"
		curl -s $line >> $tmp
		ok=1;
	fi
done < "$sourceFile"

# sed -i '/^\s*$/d' $tmp;
grep 'http.\+/' $tmp > $output;

if [ -f $tmp ]
then	rm $tmp;
fi

if [ -f $sourceFile ]
then	# echo about to delete $sourceFile;
	rm $sourceFile;
fi

ls -alh resources/;
