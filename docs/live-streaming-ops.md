# Live streaming operations

Use PHP streaming only as a compatibility bridge. For busy channels, prefer local HLS restreaming so one backend process pulls the source and Nginx serves many viewers.

## Nginx

```nginx
send_timeout 3600;
keepalive_timeout 65;
client_body_timeout 60;
reset_timedout_connection on;

location /play/iptv/ {
    proxy_buffering off;
    proxy_request_buffering off;
    gzip off;
    add_header X-Accel-Buffering no always;
}

location ~ \.php$ {
    fastcgi_buffering off;
    fastcgi_request_buffering off;
    fastcgi_read_timeout 3600;
    fastcgi_send_timeout 3600;
    fastcgi_connect_timeout 60;
}

location /hls/ {
    types {
        application/vnd.apple.mpegurl m3u8;
        video/mp2t ts;
        video/mp4 m4s;
    }

    add_header Cache-Control "no-cache";
    add_header Access-Control-Allow-Origin "*" always;
    add_header Access-Control-Allow-Methods "GET, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Range, Origin, Accept, Content-Type" always;

    root /var/www/live.rifimedia.com/public;
}
```

For HLS segment caching, cache only short-lived segment files and never cache signed playlists for long. A few seconds of `.ts` or `.m4s` cache can let many viewers share the same segment.

## PHP-FPM

Tune for the actual server RAM:

```ini
pm = dynamic
pm.max_children = 30
pm.start_servers = 4
pm.min_spare_servers = 4
pm.max_spare_servers = 10
pm.max_requests = 500
```

Lower `pm.max_children` on small servers. Raise it carefully only when RAM allows it.

## Restreaming recommendation

For high-viewer channels:

1. Pull the remote source once with FFmpeg, MediaMTX, Nginx-RTMP, or Restreamer.
2. Remux to local HLS in `storage/app/hls/{channel_id}` or a dedicated live host path.
3. Serve `/hls/{channel_id}/index.m3u8` through Nginx.
4. Auto-stop the restream after no viewers remain for several minutes.

This avoids 100 viewers creating 100 remote IPTV connections through PHP-FPM.

## Logging

Do not log every segment or chunk request. Keep logs to important events only:

- `source_failed`
- `switched_server`
- `all_servers_failed`

Use Admin > Stream Health for cached aggregate status.
