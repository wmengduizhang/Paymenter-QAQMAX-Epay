# QAQMAX Gateway for Paymenter

This extension implements QAQMAX (柠檬支付) as a Paymenter payment gateway.

- submit.php mode: builds a signed form and auto-posts to QAQMAX.
- mapi.php mode: server-side request, then redirect to payurl or show qrcode.

Configure in Paymenter admin:
- Base URL (e.g. https://b3a83171.qaqmax.com)
- pid
- key
