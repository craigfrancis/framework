
# Short Variables

List of the short variables used by this framework:

't' - Token, often in the query string; e.g. the password reset process.
'o' - Original Time, when the form was first loaded.
'r' - Request Identifier, a random value to distinguish between form submissions.
'q' - Query, not really used by the framework, but often used in search forms.

Cookies:

'c' - Cookie check, just set to the value 1.
'f' - CSRF cookie, to compare against 'csrf' value.
'b' - Browser tracker, a temporary token to check the same browser is being used, e.g. password reset.
'u' - Username cookie, to remember for next login.
