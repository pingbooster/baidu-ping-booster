# Baidu Ping Booster

![Version](https://img.shields.io/badge/version-2.1.0-00a4ef)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-21759b)
![Tested](https://img.shields.io/badge/tested%20up%20to-7.0.4-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)

Baidu Ping Booster is a free WordPress toolkit for submitting URLs to Baidu, managing background queues, generating a dedicated sitemap, reviewing crawler diagnostics, and monitoring submission activity.

> URL submission does not guarantee crawling, indexing, rankings, or traffic.

## Features

- Baidu API Push using your own site token
- Automatic background submission queue
- Manual and bulk submission of up to 500 URLs
- Configurable post types and submission cooldowns
- Legacy XML-RPC ping fallback
- Submission logs, statistics, retries, and diagnostics
- Dedicated Baidu sitemap and robots.txt integration
- Optional Baidu-oriented SEO fields, metadata, and language settings
- Optional Baidu Tongji integration
- Settings import and export

## Requirements

- WordPress 5.0 or later
- PHP 7.4 or later
- A verified Baidu Search Resource Platform site and token for API Push

The plugin had 30+ active installations when this repository was published.

## Installation

1. Install **Baidu Ping Booster** from WordPress.org, or upload the plugin directory to `/wp-content/plugins/`.
2. Activate it in **Plugins**.
3. Open the Baidu Ping Booster settings page.
4. Add your verified Baidu site URL and API token.
5. Configure the post types and submission behavior you want.

## External services

Depending on enabled settings, the plugin may communicate with:

- Baidu URL Submission API: `http://data.zz.baidu.com/urls`
- Baidu XML-RPC ping endpoint: `http://ping.baidu.com/ping/RPC2`
- Baidu Tongji script: `https://hm.baidu.com/hm.js`
- PingBooster's public blog feed for product news

Your Baidu token is stored in the WordPress database and sent only to the configured Baidu submission endpoint. Never commit tokens, exported settings, or production data to this repository.

## Official links

- [WordPress.org plugin](https://wordpress.org/plugins/baidu-ping-booster/)
- [PingBooster product page](https://pingbooster.site/free-plugins/baidu-ping-booster/)
- [Baidu Ping Booster 2.1.0 release notes](https://blog.pingbooster.site/updates/baidu-ping-booster-v2-1-0-released/)
- [WordPress.org support forum](https://wordpress.org/support/plugin/baidu-ping-booster/)

## Development

```bash
git clone https://github.com/pingbooster/baidu-ping-booster.git
```

Test changes on a non-production WordPress installation. Contributions should follow WordPress coding and security practices.

## License

Baidu Ping Booster is licensed under the GNU General Public License v2.0 or later. See [LICENSE](LICENSE).
