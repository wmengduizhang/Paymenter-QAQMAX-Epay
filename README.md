# QAQMAX Gateway for Paymenter

This extension implements QAQMAX as a Paymenter payment gateway.

- submit.php mode: builds a signed form and auto-posts to QAQMAX.
- mapi.php mode: server-side request, then redirect to payurl or show qrcode.

Configure in Paymenter admin:
- Base URL (e.g. https://xxxx.5ssr.com)
- pid
- key
