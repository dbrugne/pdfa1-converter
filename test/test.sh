#!/bin/bash

#FQDN=scor.eyefinity.fr
#KEY=xxx

FQDN=localhost:8080
KEY=cledetestsecurite


MAX=10
COUNT=1

if [ ! -d "out" ]
then
  mkdir out
else
  rm -f ./out/*
fi
for i in ./samples/*.pdf; do
    
    echo "["$COUNT"] Now working on "$i
    filename=${i:10}
    outfilename='./out/'$filename
    b64filename=$outfilename'.b64'
    xmlfilename=outfilename'.xml'

    # conversion
    curl --silent -X POST -F source=@$i -F key=$KEY -F store_id=testeyefinity -o $b64filename --url "http://$FQDN/convert"
    echo '  + '$filename' converted'

    # base64_decode
    cat $b64filename | base64 --decode > $outfilename
    rm $b64filename
    echo '  + '$filename' decoded'

    /www/jhove/jhove -l OFF -h XML $outfilename > $xmlfilename
    status="$(xpath $xmlfilename '/jhove/repInfo/status/text()' 2> /dev/null)" # Well-Formed and valid
    profile="$(xpath $xmlfilename '/jhove/repInfo/profiles/profile/text()' 2> /dev/null)" # ISO PDF/A-1, Level B

	if [[ ("$status" = "Well-Formed and valid") && ("$profile" = "ISO PDF/A-1, Level B") ]]
	then
	    echo -e "  = Valid!"
	    rm $xmlfilename
	else
		echo -e "  = ERROR:"
		echo -e "  ! $status"
		echo -e "  ! $profile"
	fi

    COUNT=$(( $COUNT + 1 ))
    if [[ $COUNT -gt $MAX ]]; then
      break;
    fi

done

echo "Done "$(( $COUNT - 1 ))" files"