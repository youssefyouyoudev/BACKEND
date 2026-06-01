# RifiMedia Streaming Deployment Notes

`live.rifimedia.com` runs behind Cloudflare Tunnel, so Laravel must not relay IPTV video bytes through PHP-FPM. Long MPEG-TS or HLS segment responses can exhaust workers, trigger Cloudflare 502/530/1033 errors, and make unrelated pages feel down even when the stream origin is the real failure.

## Playback Architecture

- Laravel generates short-lived signed playback routes.
- `/stream/{encodedUrl}` and `/go/{channel}` validate the signature and source URL, log masked metadata, then return a redirect to the upstream stream.
- The browser, native player, or external player connects to the IPTV host directly after the redirect.
- Plain HTTP streams are marked as external-player sources on HTTPS pages. The embedded web player skips them and shows a clear fallback instead of forcing mixed-content playback.
- Do not add `response()->stream()`, Guzzle streaming, or PHP chunk loops for IPTV playback.

## Cloudflare Tunnel

Keep Cloudflare Tunnel focused on the Laravel app:

```yaml
ingress:
  - hostname: live.rifimedia.com
    service: http://127.0.0.1:8000
  - service: http_status:404
```

Recommended service settings:

- Run `cloudflared` as a system service with restart enabled.
- Monitor `cloudflared` logs for origin connection errors before debugging Laravel.
- A 502 usually means the local origin app or upstream stream failed.
- A 530/1033 usually points to tunnel/DNS/configuration problems.

## Laravel And PHP-FPM

Laravel should stay responsive even when IPTV origins fail:

- External API and playlist fetches use short connect/read timeouts.
- Stream health is checked by `php artisan streams:check-health`, scheduled for popular sources every five minutes and all active sources hourly.
- Health results are stored on `channel_streams` and in `stream_server_statuses`.
- Admin dashboard cards expose online/offline/unknown counts and recent failed sources.

Useful production checks:

```bash
php artisan schedule:list
php artisan streams:check-health --popular --limit=25
php artisan queue:work --timeout=60 --tries=3
```

## Optional Nginx Stream Proxy

If a stream must be proxied to solve provider restrictions, keep that proxy outside Laravel. Use Nginx or a dedicated media gateway with strict allowlists, rate limits, connection limits, and cache/buffer settings. Laravel may issue a signed redirect to that gateway, but PHP-FPM should not hold the video connection open.
