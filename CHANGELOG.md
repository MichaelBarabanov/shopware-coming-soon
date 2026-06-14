# 1.1.1

- Fix: "Specific URLs" now also match SEO URLs of categories and products (e.g. /summer-sale), which Shopware internally rewrites to /navigation/<id> before the check ran. Both the SEO path and the technical path are matched now.

# 1.1.0

- New "Specific URLs" option: block individual paths (e.g. /summer-sale) instead of the whole sales channel
- Path matching is segment-aware and case-insensitive, supports sub-pages and the * wildcard
- Specific URLs work independently of the channel-wide toggle; IP whitelist, preview link and countdown auto-end still apply
- Compatible with Shopware 6.7

# 1.0.0

- Coming soon / maintenance page per sales channel
- Configurable title, text, background image, logo and accent color
- Optional countdown with configurable launch date
- Optional automatic deactivation when the countdown expires
- IP whitelist (IPv4/IPv6, CIDR notation supported)
- Preview link with secret token for testing while the page is active
- SEO-safe: responds with HTTP 503, Retry-After and noindex headers
- HTTP cache is cleared automatically when the plugin is toggled
