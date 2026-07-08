API verification report for toza_mobile

- Date: 2026-07-08
- Configuration source: `toza_mobile/.env` and `toza_mobile/lib/core/api_client.dart`
- Found backend URL in `.env`:
  - `TOZA_API_BASE=https://tozradar.com/tozastarter/api/index.php`
- Notes and analysis:
  - The code in `api_client.dart` expects `TOZA_API_BASE` in the environment via `flutter_dotenv`.
  - A prior error referenced `https://toza.tozradar.com/api/index.php` and failed with "No such host is known"; `.env` intentionally points to the main domain path `https://tozradar.com/tozastarter/api/index.php` as a working fallback when the `toza.tozradar.com` subdomain is not provisioned.
  - I did not modify `.env` — the current value points at `tozradar.com/tozastarter/api/index.php`, which should resolve in DNS. I did not attempt live HTTP requests from this run; DNS/network verification beyond `flutter doctor`'s network check is outside the scope here.

- Action items / blockers:
  - If you need the app to use `toza.tozradar.com` (subdomain), provision the DNS and TLS for that host; until then the `.env` fallback is correct.
  - If `.env` must be changed, update `TOZA_API_BASE` only after verifying the target host resolves and serves the API.
