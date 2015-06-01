#!/bin/bash

FQDN=scor.eyefinity.fr
KEY=DFFSDLdfds34564574fdsfs464567FDGFDGgdffdgdDFg54353345345zre

#FQDN=localhost:8080
#KEY=cledetestsecurite

MAX=1000
PROCESSED=1
SUCCESS=0
ERRORC=0


if [ ! -d "out" ]
then
  mkdir out
else
  rm -f ./out/*
fi
for i in ./samples/*.pdf; do
    
    echo "["$PROCESSED"] Now working on "$i
    filename=${i:10}
    outfilename='./out/'$filename
    xmlfilename=outfilename'.xml'

    # conversion
    # @source: http://stackoverflow.com/questions/2220301/how-to-evaluate-http-response-codes-from-bash-shell-script
    RESPONSE=$(curl --write-out \\n%{http_code} --silent --output - -X POST -F source=@$i -F key=$KEY -F store_id=testeyefinity --url "http://$FQDN/convert")

    CODE=$(echo "$RESPONSE" | tail -n1)
    B64=$(echo "$RESPONSE" | sed \$d)

    if [[ ("$CODE" = "200") ]]
    then
        echo '  + '$filename' converted'

        # base64_decode
        echo $B64 | base64 --decode > $outfilename
        echo '  + '$filename' decoded'

        # JHOVE validation
        /www/jhove/jhove -l OFF -h XML $outfilename > $xmlfilename
        status="$(xpath $xmlfilename '/jhove/repInfo/status/text()' 2> /dev/null)" # Well-Formed and valid
        profile="$(xpath $xmlfilename '/jhove/repInfo/profiles/profile/text()' 2> /dev/null)" # ISO PDF/A-1, Level B

        if [[ ("$status" = "Well-Formed and valid") && ("$profile" = "ISO PDF/A-1, Level B") ]]
        then
            echo -e "  = Valid!"
            rm $xmlfilename

            SUCCESS=$(( SUCCESS + 1 ))
        else
            echo -e "  = JHOVE VALIDATION ERROR:"
            echo -e "  ! $status"
            echo -e "  ! $profile"

            ERRORC=$(( ERRORC + 1 ))
        fi
    else
        echo -e "  = SCOR CONVERSION ERROR:"
        echo -e "  ! $CODE"
        echo -e "  ! $RESPONSE"

        ERRORC=$(( ERRORC + 1 ))
    fi

    PROCESSED=$(( PROCESSED + 1 ))
    if [[ $PROCESSED -gt $MAX ]]; then
      break;
    fi

done

echo "Done "$(( $SUCCESS ))" file(s) with "$ERRORC" error(s)"