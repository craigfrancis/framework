
# Cross Site Request Forgery

Otherwise known as CSRF.

This is handled automatically by the [form helper](../../doc/helpers/form.md).

It can also effect links, so never perform an action based on a simple link, e.g. to "delete" something. This can also be a problem if the browser pre-fetches the page.

However, while having a simple link to "logout" is also vulnerable (used as a denial of service attack for the user), the risk of the logout link not working due to a CSRF check is potentially even worse.
