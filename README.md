

This is a web callback PHP backend along with the relevant Asterisk extensions.

It plays French and English annoucements.

the PHP script expects two variables from the HTTP POST:

- The phone number to callback the visitor on
- The language for the announcement

When the script is called, a new object with the phoneNumber class is created. The checkNumber() method cleans the phone number to only digits, checks that the number is valid, appends '1' at the beginning of the number and puts it in the digits property. It also sets the isValid property to true.

It checks if the language property is valid ('FR' or 'EN').

If the number is valid and the language is valid, then it opens a TCP socket to Asterisk Manager Interface using a TLS connection and sends an originate message for the visitor to be called back, connected to the callback extension in the appropriate language and then bridges the call to my cell phone.

It handles socket errors (i.e. the Asterisk host is unreachable), AMI messages errors (i.e. the AMI credentials are invalid, the call cannot be originated etc) and returns a JSON object with the result of the API call.

The returned JSON object is:
{
	"socket":true|false,
	"amiLogin":true|false,
	"amiOrig":true|false,
	"amiLogoff":true|false,
	"callSpooled":true|false
}

If callSpooled is true, then all went well and parties will be rung as expected. If callSpooled is false, then it is possible to check what went wrong by checking the booleans for every stage of the callback operation.

"socket":false means the API couldn't reach the Asterisk host, either because the host is unreachable, there is a mistake in the hostname or the port number is wrong.
"amiLogin":false most likely means that the submitted AMI credentials are not valid
"amiOrig":false means that the Originate failed
"amiLogoff":false means that the API didn't log off AMI
"callSpooled":false when any preceding operation failed. As a result, there won't be any callback. If one doesn't wish to display detailed error messages then this would be the only property to check to assert the success or failure of the callback request.

