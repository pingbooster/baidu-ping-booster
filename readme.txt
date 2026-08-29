=== Baidu Ping Booster ===
Contributors: Samee Ullah Feroz
Donate link: https://pingbooster.site
Tags: baidu, seo, indexing, url submission, sitemap, baiduspider, tongji
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free Baidu URL submission, sitemap, crawler diagnostics, activity logs, Tongji settings, and Baidu-focused SEO tools for WordPress.

== Description ==

**Baidu Ping Booster** is a free WordPress toolkit for site owners who want to help Baidu discover public website URLs and manage Baidu-focused technical SEO settings from WordPress.

Version 2.1.0 adds Baidu API Push support, a submission queue, activity logging, dashboard statistics, crawler diagnostics, improved sitemap controls, WooCommerce/custom post type support, and a refreshed PingBooster resources page.

Baidu URL submission can help Baidu discover URLs faster, but submission does not guarantee crawling, indexing, ranking, or traffic.

Official website: [PingBooster](https://pingbooster.site)

= Main Features =

* **Baidu API Push:** Configure your verified Baidu site and API token and submit public URLs through Baidu's URL submission endpoint.
* **Automatic Submission Queue:** Queue published and updated content for background processing through WP-Cron.
* **Manual URL Submission:** Submit a single URL immediately and see the result in the Activity Log.
* **Bulk URL Queue:** Paste up to 500 URLs and process them in small background batches.
* **Posts, Pages, Products & Custom Post Types:** Select which public content types should be automatically submitted.
* **Smart Update Cooldown:** Prevent repeated submissions while editing content several times in a short period.
* **Legacy XML-RPC Fallback:** Optional compatibility fallback when Baidu API credentials are not configured.
* **Activity Log:** Track URL, date, method, status, HTTP response code, attempts, and response message.
* **Submission Statistics:** View today's submissions plus total successful, failed, and pending records.
* **Retry Failed Submissions:** Return failed requests to the queue for another attempt.
* **Baidu XML Sitemap:** Dedicated `/baidu-sitemap.xml` endpoint for posts, pages, WooCommerce products, categories, and tags.
* **Baiduspider robots.txt Rules:** Add Baidu-specific crawler rules to WordPress virtual robots.txt output.
* **Crawler Diagnostics:** Check search visibility, public URL accessibility, API configuration, sitemap status, HTTPS, and WP-Cron.
* **Baidu SEO Fields:** Optional SEO title, description, and canonical URL fields for posts, pages, and products.
* **Baidu-Friendly Meta Output:** Optional device/no-transform meta tags plus an opt-in Simplified Chinese language directive.
* **Baidu Tongji Integration:** Enter a Tongji tracking ID to load Baidu's official `hm.baidu.com` analytics script, with an optional webmaster/property reference field.
* **Import / Export:** Back up and restore plugin configuration as JSON.
* **PingBooster Resources:** View PingBooster news, SEO/GEO services, web development, plugin development, service packages, and related WordPress plugins.

== How to Use ==

= 1. Configure Baidu API Push =

1. Sign in to the Baidu Search Resource Platform and verify your website.
2. Open the URL/resource submission area and obtain the API submission site URL and token for your verified property.
3. In WordPress, open **Baidu Booster > Submission**.
4. Enter the verified Baidu Site and API Token.
5. Enable Automatic Submission and choose the public post types you want to submit.
6. Save the settings.

If an API token is not configured, you can optionally keep the legacy XML-RPC fallback enabled.

= 2. Submit a URL Manually =

1. Open **Baidu Booster > Submission**.
2. Enter a public URL from your verified site.
3. Click **Submit URL Now**.
4. Open **Activity Log** to review the recorded result.

= 3. Use the Bulk Queue =

1. Paste one URL per line in the Bulk Queue field.
2. Add the URLs to the queue.
3. Baidu Ping Booster processes queued URLs in small batches through WP-Cron.
4. Failed submissions can be returned to the queue from the Activity Log.

= 4. Configure the Baidu Sitemap =

Open **Baidu Booster > Sitemap & SEO** and choose which content groups should appear in the dedicated Baidu sitemap.

Default sitemap URL:
`https://example.com/baidu-sitemap.xml`

= 5. Review Diagnostics =

Open **Baidu Booster > Tools** to check:

* WordPress search-engine visibility
* Public/private site address
* Baidu API credentials
* Baidu sitemap status
* WP-Cron availability
* HTTPS status
* WordPress, PHP, and plugin versions

== Installation ==

1. Upload the plugin ZIP through **Plugins > Add New > Upload Plugin** or install it from the WordPress.org plugin directory.
2. Activate **Baidu Ping Booster**.
3. Open the **Baidu Booster** menu in WordPress admin.
4. Configure Submission, Sitemap & SEO, and optional Baidu settings.

== Frequently Asked Questions ==

= Is Baidu Ping Booster free? =

Yes. Baidu Ping Booster is a free plugin. There is no paid Baidu Ping Booster tier required to use the features included in this release.

= Does URL submission guarantee Baidu indexing? =

No. URL submission helps notify Baidu about public URLs, but crawling, indexing, ranking, and search visibility are controlled by Baidu and depend on many factors including accessibility and content quality.

= Where do I get the Baidu API token? =

Verify your website in the Baidu Search Resource Platform, then use the resource/URL submission area for that verified site to obtain the API submission site and token.

= Does it support WooCommerce products? =

Yes. If WooCommerce is active, products can be selected for automatic submission and included in the Baidu sitemap.

= Can it submit custom post types? =

Yes. Public custom post types appear in the Submission settings and can be selected for automatic queueing.

= What happens if WP-Cron is disabled? =

Manual submission still works. Background queue processing requires WP-Cron or a server cron setup that triggers WordPress cron events.

= Can I use it with Yoast SEO, Rank Math, or another SEO plugin? =

Yes. Baidu Ping Booster focuses on Baidu submission and Baidu-specific tools. If another SEO plugin already outputs meta descriptions and canonical URLs, you can disable Baidu Ping Booster's optional SEO fields to avoid duplicate meta output.

= What is the PingBooster tab? =

It provides links to PingBooster company news, SEO/GEO services, web development, plugin development, service packages, and related WordPress plugins. These references do not lock Baidu Ping Booster features.

== Screenshots ==

1. Dashboard with submission statistics, engine status, site health and recent activity.
2. Baidu API Push settings, automatic content submission and manual/bulk submission tools.
3. Baidu XML Sitemap, Baiduspider robots.txt rules and optional Baidu SEO output settings.
4. Activity Log with submission method, HTTP status and retry tools.
5. PingBooster resources with company services, packages, related plugins and news.

== Changelog ==

= 2.1.0 =
* Kept Baidu Ping Booster fully free and removed Pro-tier messaging from the plugin interface.
* Added Baidu API Push configuration for verified site URL and token.
* Added automatic submission queue for posts, pages, WooCommerce products, and public custom post types.
* Added configurable resubmission cooldown to reduce duplicate URL pushes during repeated edits.
* Added manual immediate URL submission.
* Added bulk URL queue supporting up to 500 pasted URLs per action.
* Added persistent Activity Log with success, failed, pending, method, HTTP code, attempts, and response message.
* Added dashboard counters for today's submissions, successful, failed, and pending records.
* Added Retry Failed and Clear Logs tools.
* Added crawler/site diagnostics for WordPress visibility, public URL, API credentials, sitemap, WP-Cron, and HTTPS.
* Improved dedicated Baidu XML sitemap controls for posts, pages, products, categories, and tags.
* Improved Baiduspider robots.txt handling and sitemap reference output.
* Made Simplified Chinese `lang="zh-CN"` output optional instead of forcing it on every website.
* Added optional Baidu SEO title, description, and canonical fields with conflict guidance for other SEO plugins.
* Improved JSON settings import/export.
* Added Baidu Tongji script loading from a configured tracking ID.
* Added PingBooster company services, packages, related plugins, and news references using the main PingBooster site until individual links are finalized.
* Updated branding to "Baidu Ping Booster" throughout the admin interface.
* Updated compatibility metadata for current WordPress releases.

= 2.0.0 =
* Major admin interface refresh.
* Added Baidu sitemap and Baiduspider configuration tools.
* Added Baidu Tongji settings fields.
* Added manual and automatic legacy Baidu ping workflow.
* Added SEO fields and import/export tools.

= 1.0.0 =
* Initial stable release with basic automated Baidu pinging and XML sitemap support.

== Upgrade Notice ==

= 2.1.0 =
Version 2.1.0 introduces Baidu API Push, background queueing, activity logs, diagnostics, improved sitemap controls, and a cleaner free-only product interface. Existing 2.0.0 settings are preserved where possible. After updating, open the Submission tab to add your Baidu API credentials if you want to use API Push.
