# MichaComingSoon - Coming Soon & Maintenance Mode for Shopware 6

Shows visitors a configurable coming soon / maintenance page - per sales channel, SEO-safe, with countdown and preview access.

## Features

- **Per sales channel**: enable the page for one shop while others stay online
- **Specific URLs**: instead of the whole channel, block only individual paths (e.g. `/summer-sale`) - works on SEO and technical URLs, with sub-page and `*` wildcard matching
- **Fully configurable**: headline, rich text, background image, logo, accent color
- **Countdown**: optional launch countdown, optionally opens the shop automatically when it expires
- **Access control**: IP whitelist (IPv4/IPv6/CIDR) and a secret preview link (`?michaPreview=TOKEN`, sets a 7-day cookie)
- **SEO-safe**: responds with HTTP 503 + `Retry-After` + `noindex` headers, so search engines never index the maintenance state
- **No content leaks**: the page is rendered standalone (no theme layout), navigation/products can't leak through
- **No build step**: works without `bin/build-storefront.sh` or theme compilation
- **Cache-aware**: HTTP cache is cleared automatically when the page is toggled

## Requirements

- Shopware 6.6.x or 6.7.x
- PHP >= 8.2

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install --activate MichaComingSoon
bin/console cache:clear
```

## Configuration

Admin -> Extensions -> My extensions -> MichaComingSoon -> Configure.

**Important:** select the sales channel in the dropdown at the top to configure (and enable) the page per sales channel.

| Setting | Description |
| --- | --- |
| Enable coming soon page | Blocks the whole sales channel |
| Specific URLs (paths) | Block only individual paths (one per line, e.g. `/summer-sale`); works independently of the master switch |
| Headline / Text | Empty = translated defaults (de-DE / en-GB) |
| Background image / Logo / Accent color | Page design |
| Show countdown + launch date | Countdown to your launch |
| Open shop automatically | When the countdown expires, visitors are let through without manual action |
| IP whitelist | e.g. `203.0.113.10, 192.168.0.0/16` |
| Preview token | Open `https://your-shop.example/?michaPreview=TOKEN` to browse the shop normally |

## FAQ

**Why HTTP 503 and not 200?**
503 + `Retry-After` tells search engines the downtime is temporary. A 200 coming soon page would get indexed and could replace your real pages in search results.

**Visitors still see the normal shop after enabling the page?**
The plugin clears the HTTP cache automatically when you toggle it. If you use an external reverse proxy (Varnish/Fastly/Cloudflare), purge it there too.

**Does the admin keep working?** Yes. `/admin`, `/api` and `/store-api` are never blocked.

## License

Proprietary - Michael Barabanov, https://github.com/MichaelBarabanov
<img width="1909" height="906" alt="Screenshot 2026-06-15 002804" src="https://github.com/user-attachments/assets/ba9e0dab-6852-4074-b5f3-e78ed3b12d9b" />
