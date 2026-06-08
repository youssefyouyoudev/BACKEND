# Production Security And Compliance

## Content Policy

Allowed content:

- Video and live streams owned by RifiMedia.
- Content covered by a written distribution agreement or explicit license.
- Uploaded playlists whose every stream host appears in `STREAMING_ALLOWED_DOMAINS`.

Not allowed:

- Third-party IPTV packages without documented redistribution rights.
- Scraped, leaked, credential-shared, pirated, adult, malware, spam, or deceptive content.
- Arbitrary proxy or relay use.

Keep the license, contract, invoice, provider contact, allowed domains, territories, and expiry date for every source. Review the allowlist whenever a license changes.

## Emergency Removal

1. Set `STREAMING_ENABLE_EXTERNAL_STREAMS=false` and `STREAMING_BRIDGE_ENABLED=false`.
2. Run `php artisan config:cache`.
3. Unapprove the playlist in admin or set `is_public=0` and `approved_at=NULL`.
4. Remove the source from `STREAMING_ALLOWED_DOMAINS`.
5. Preserve the complaint and relevant masked logs. Do not preserve or forward unauthorized media.
6. Reply to the provider or rights holder with the removal time and internal case reference.

## Safe Deployment

```bash
cd /var/www/rifimedia/current
cp .env.example .env
php artisan key:generate
# Edit .env and list only domains backed by written rights.
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
rm -f public/hot
php artisan migrate --force
php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug=rwX,o= storage bootstrap/cache
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl enable --now rifimedia-queue.service
```

Scheduler:

```cron
* * * * * cd /var/www/rifimedia/current && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## Firewall And Fail2Ban

Keep the current SSH session open while enabling UFW:

```bash
sudo apt update
sudo apt install -y ufw fail2ban
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status verbose

sudo cp deploy/fail2ban/nginx-rifimedia.conf /etc/fail2ban/filter.d/
sudo cp deploy/fail2ban/jail.local /etc/fail2ban/jail.d/rifimedia.local
sudo fail2ban-client -t
sudo systemctl enable --now fail2ban
sudo fail2ban-client status
```

Do not expose MySQL, Redis, PHP-FPM, Vite, Horizon, or development ports publicly.

## Investigation Commands

```bash
sudo ss -lntup
sudo ps auxf --sort=-%cpu | head -40
sudo ps auxf --sort=-%mem | head -40
sudo journalctl -p warning --since "24 hours ago"
sudo journalctl -u nginx -u php8.3-fpm -u rifimedia-queue --since "24 hours ago"
sudo tail -n 200 /var/log/nginx/rifimedia.error.log
sudo awk '{print $1}' /var/log/nginx/rifimedia.access.log | sort | uniq -c | sort -nr | head
sudo du -xah /var/www/rifimedia /var/log | sort -h | tail -40
sudo find /var/www/rifimedia -type f -size +500M -ls
sudo crontab -l
sudo crontab -u www-data -l
sudo systemctl list-timers --all
```

## Required Production Values

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

STREAMING_ENABLE_EXTERNAL_STREAMS=false
STREAMING_REQUIRE_ADMIN_APPROVAL=true
STREAMING_ALLOWED_DOMAINS=
STREAMING_ALLOWED_PLAYLIST_DOMAINS=
STREAMING_RESOLVE_DNS=true
STREAMING_BRIDGE_ENABLED=false
```

Enable external sources only after adding the exact licensed domains. Domain entries also cover their subdomains. Never add broad shared hosting, URL shortener, cloud storage, or user-content domains unless you control the complete namespace.
