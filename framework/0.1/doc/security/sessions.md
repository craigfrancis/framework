
# Sessions

TODO: Incomplete

PHP provides a pretty good session handler, however due to its simplicity, it can be vulnerable to some security issues.

---

So we have a common understanding, a typical website works by a browser making a request (e.g. a page), and the server responds, and the connection is closed.

As you navigate around a website this happens many times, and as such, the server doesn't really keep track of the person... this is because HTTP is known as a "stateless" protocol.

So a person could login, and on the next request, the server has forgotten who they are.

This is where cookies come in, they allow a server to send a piece of data to the browser, and each time the browser makes a request from that point on, the cookie data is sent back.

---

When a session is created by PHP, it creates an ID such as "78e9cc6q3is6qodsf869uggdl3". This should be unique to the visitor, and can be seen as a key to access the session data.

Typically this ID is stored in a cookie, or passed in the URL (not advisable).

If the session ID is known by an attacker, they can create a cookie in their browser with the same value, and start using the session.




Session fixation.

Sessions created on another website on the same host.

	session::regenerate()

Session ID obtained by a third party / attacker (e.g. a hacker)... this can be via session fixation, packet sniffing, session being passed in query string (referrer header / image src), malware, etc.
