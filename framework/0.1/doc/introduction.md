
# Introduction

PHP Prime is a framework built for the clients of [Code Poets](https://www.code-poets.co.uk/).

I would not recommend that anyone creates a framework of their own. I have one because it existed before most frameworks (since 2003), and I find that it gives me a more advanced base to work with.

I should point out that this framework is **not supported** for anyone else, however you may find some of the ideas different to some of the typical frameworks, for example:

* Security, Accessibility, and Performance are key elements.

* SQL is preferred over an ORM, as you need to know what the database is doing if you want to improve performance and be aware of security.

* Objects are used to avoid code duplication, but procedural code is used in [units](../doc/setup/units.md) to avoid too many abstractions (easier to read and understand what is going on).

* The system is self testing (as the page loads), where it will complain if anything is wrong. This is a slightly different approach to normal TDD, where tests are separate.

* [Debug mode](../doc/setup/debug.md) provides considerable amounts of help, such as reminding you about key features, showing the processing time, and checking/displaying the SQL.

* It is **very** strict, e.g. using "application/xhtml+xml" in development (ensuring correct HTML nesting), and applying a limited CSP header (e.g. no inline JS).

---

# Next steps

At least scan over the notes on [security](../doc/security.md), which applies to **all** websites.

Then [setup](../doc/setup.md) your project.
