# unpack-burp

This is a small tool created by [Frans Rosén](https://twitter.com/fransrosen). For unpacking base64:ed "Save items"-content from Burp.

This allows you to extract certain parts of the requests which allows you do to things like:

* Take all request to target X and extract all parameters from the request body
* Get the request body for all requests that responded with header X
* Collect all browsed javascript-files and merge them locally into one large JS to then prettify them together.
* Create wordlists based on all responses and/or request params/headers

The JSON-option below is helpful if you need to create more complex logic later on in the pipeline, such as "If request header X then extract response body param Y". The regular plain-text outputs are simpler if you are just looking for extracting the raw data for additional grep:ing or similar.

### Usage

In Burp, in the search-popups as well as the proxy you are able to select multiple requests and select "Save items". This will save a XML-file with request and response as base64. Make sure you have the "Base64-encode requests and responses"-checkbox selected.

```
php unpack-burp.php <file> [reqb,resb,...]
```

Options, can be combined using a comma-separated list (ie `reqp,resb`):

* `reqp` - Request path (ie: `GET / HTTP/1.1`)
* `reqh` - Request headers (excluding method+path)
* `reqb` - Request body
* `resc` - Response code (ie: `HTTP/1.1 200 OK`)
* `resh` - Response headers (excluding status code)
* `resb` - Response body (default)
* `jsonh` - Request/Response headers in a compact JSON as `reqp` `reqh`, `resc` and `resh`
* `jsonb` - Request/Response body in a compact JSON as `reqb` and `resb`
* `jsonreq` - Request in a compact JSON as `reqp`, `reqh` and `reqb`
* `jsonres` - Response in a compact JSON as `resc`, `resh` and `resb`
* `all` - compact JSON with all props from above

### Examples:

Request and response headers:

```
php unpack-burp.php target.xml reqh,resh

POST / HTTP/1.1
Host: example.com
Content-Length: 96
Sec-Ch-Ua: "Chromium";v="118", "Google Chrome";v="118", "Not=A?Brand";v="99"
Sec-Ch-Ua-Mobile: ?0
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_19_7) AppleWebKit/531.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/531.36
Content-Type: application/json
Accept: */*
HTTP/1.1 200 OK
Access-Control-Allow-Origin: *
Content-Type: application/json
Content-Length: 31546
Date: Mon, 06 Nov 2023 22:58:24 GMT
Connection: close

POST / HTTP/1.1
Host: example.com
HTTP/1.1 200 OK
Access-Control-Allow-Origin: *
...
```

Request and response body:

```
php unpack-burp.php target.xml reqb,resb
{"requestData":{}}
{"data":{"items":[{...

body=xxx&param_id=111
<html><title>Test</title>

body=xxx&paramId=222
<html><title>Test</title>
...
```

Request and response headers as JSON:

```
php unpack-burp.php target.xml jsonh
{"reqp":"POST / HTTP/1.1","reqh":"Host: example.com\r\nContent-Length: 96\r\nSec-Ch-Ua: \"Chromium\";v=\"118\", \"Google Chrome\";v=\"118\", \"Not=A?Brand\";v=\"99\"\r\nSec-Ch-Ua-Mobile: ?0\r\n...","reqb":"HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 31546\r\nDate: Mon, 06 Nov 2023 22:58:34 GMT\r\nConnection: close"}
{"reqp":"POST / HTTP/1.1","reqh":"Host: example2.com\r\nContent-Length: 96\r\nSec-Ch-Ua: \"Chromium\";v=\"118\", \"Google Chrome\";v=\"118\", \"Not=A?Brand\";v=\"99\"\r\nSec-Ch-Ua-Mobile: ?0\r\n...","reqb":"HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 31546\r\nDate: Mon, 06 Nov 2023 22:58:34 GMT\r\nConnection: close"}
```

Use it to get a list of all unique response headers from a bunch of requests:

```
php unpack-burp.php target.xml resh | \
  cut -d ":" -f 1 | sort -uf
Access-Control-Allow-Credentials
Access-Control-Allow-Headers
Access-Control-Allow-Methods
Access-Control-Allow-Origin
Access-Control-Expose-Headers
Connection
Content-Length
Content-Type
Date
Server
Strict-Transport-Security
Vary
X-Path
```

Or, list all paths where `Access-Control-Allow-Origin`-header is returned:

```
php unpack-burp.php target.xml jsonh | \
  jq -r '. | select(.resh | test("\naccess-control-allow-origin:"; "i")) | .reqp' | \
  cut -d ' ' -f 2 | sort -u
/api/items
/api/users
```
