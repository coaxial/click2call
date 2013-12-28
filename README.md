This is the web callback PHP backend along with the relevant part of the dialplan.

Since my CV exists in two languages, it handles both FR and EN callbacks.

the PHP script expects two variables from the HTTP POST:
- the phone number to callback the visitor on
- the language for the announcement

A new object with the phoneNumber class is created.
The checkNumber() method cleans the phone number to only digits, checks that the number is valid, appends '1' at the beginning of the number and puts it in the digits property. It also sets the isValid property to true.

It also checks if the language is FR or EN.

If the number is valid and the language is valid, then it opens a TCP socket to Asterisk Manager Interface using a TLS connection and sends an originate message for the visitor to be called back, connected to the callback extension in the appropriate language and then bridges the call to my cell phone.