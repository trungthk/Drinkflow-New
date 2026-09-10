# DrinkFlow realtime gateway

The gateway is transport-only. Laravel signs short-lived user tokens and this
process verifies them with the same `APP_KEY` (or an explicit
`SOCKET_TOKEN_SECRET`). A connected user is automatically joined to their
authorized `user:{room_user_id}` and `room:{room_id}` channels. Admin and
superadmin tokens are limited to the `admin:{admin_id}`, `superadmin`,
`system`, and claimed room channels. Client supplied IDs are never used to
authorize a channel.

The gateway exposes a small `/health` endpoint for the Superadmin socket
monitor. It reports connection counts, room aggregates, recent disconnects,
and authentication failure counts without returning socket payloads.

Run locally with:

```sh
npm install
APP_KEY="base64:..." npm start
```
