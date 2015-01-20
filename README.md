pdfa1-converter
===============

A small service in Silex/PHP to convert PDF files in PDF/A-1 valid documents.

**Dependencies:**

* ghostscript
* for validation a functionnal JHOVE deployment: http://jhove.sourceforge.net/index.html

# Todo
* introduce console and add test on full file list
* MySQL autoclean (<1month)

# API

The service expose only one method that convert a POSTed file in a PDF/A-1b compliant file and return it as base64 string.

## /convert

- Method: **POST**
- URL: **http[s]://__FQDN__/convert**
- Parameters
  * **key**: secret security key 
  * **store_id**: remote store ID
  * **source**: the original file as multipart
- Return
  * If **success** returns the base64 string as response with HTTP code 200
  * If **error** happens return a 5/4xx code with the error detail as JSON in response

# Examples

Call the service with cURL:

```
curl \
  --form "source=@__FILENAME__" \
  --form key=__SECURITY_KEY__ \
  --form store_id=__STORE_ID__ \
  http://__FQDN__/convert
```

Full example:

```
cd test
rm temp.1.txt temp.2.pdf
curl --form "source=@samples/22369_1.pdf" --form key=cledetestsecurite --form store_id=44x --output temp.1.txt http://localhost/convert
certutil -decode temp.1.txt temp.2.pdf
C:\www\jhove\jhove -l OFF -h xml temp.2.pdf
```
