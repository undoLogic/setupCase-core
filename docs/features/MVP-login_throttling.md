## Login throttling

SetupCase Core slows down password guessing by temporarily blocking repeated failed logins.

- **What is counted:** failed attempts per IP address plus the submitted username (lowercased and trimmed). The username counts whether or not the account exists, so attackers cannot use the block to find out which accounts are real.
- **Limit:** after a set number of failures (default 5) within a time window, further attempts are refused for a cooldown period (default 5 minutes). Both values are configurable.
- **Storage:** attempts are stored server-side (database or cache), never in the session, so clearing cookies does not reset the count.
- **Real IP:** if the app sits behind a proxy or CDN, configure trusted proxies. Otherwise forwarded IP headers can be faked or every request may appear to come from the proxy.
- **Shared networks:** offices behind a single public IP share that IP. Because blocking also depends on the username, one person's mistakes do not lock out colleagues.
- **Scope:** this only defends against brute-force password guessing. It does not protect against vulnerabilities in PHP, frameworks or libraries.
