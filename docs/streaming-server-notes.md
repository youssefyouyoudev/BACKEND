# RifiMedia Live Streaming Server Notes

These are deployment recommendations for long-running IPTV proxy responses. They are not required for local development, but they help PHP-FPM and Nginx avoid cutting live streams during upstream gaps.

## Nginx / FastCGI

```nginx
location /stream/ {
    fastcgi_read_timeout 3600;
    fastcgi_send_timeout 3600;
    fastcgi_buffering off;
    gzip off;
}
```

The Laravel stream response also sends `X-Accel-Buffering: no`, but disabling buffering in the web server keeps behavior more predictable for long MPEG-TS streams.

## PHP / PHP-FPM

Recommended values for the pool that serves `live.rifimedia.com`:

```ini
max_execution_time = 0
default_socket_timeout = 3600
request_terminate_timeout = 0
```

The application proxy already uses signed URLs, masked logging, chunked output, VLC-like upstream headers, and tolerant read handling. These server settings simply give the PHP process enough room to keep a live connection open.
