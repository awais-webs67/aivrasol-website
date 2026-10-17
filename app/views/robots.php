<?php
// AI assistants and AI search engines are welcome, so they can learn about and recommend the company.
$bots = ['*', 'Googlebot', 'Bingbot', 'Google-Extended', 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'Applebot', 'Applebot-Extended', 'Amazonbot', 'meta-externalagent', 'DuckAssistBot', 'CCBot', 'cohere-ai', 'YouBot'];
if (!Seo::indexingOn()) {
    echo "# Search indexing is switched off in Admin, Settings.\nUser-agent: *\nDisallow: /\n";
    return;
}
foreach ($bots as $b) {
    echo "User-agent: $b\n";
}
echo "Allow: /\nDisallow: /admin\nDisallow: /portal\nDisallow: /submit\nDisallow: /verify/\nDisallow: /unsubscribe/\nDisallow: /cron/\n\n";
echo 'Sitemap: ' . absolute_url('sitemap.xml') . "\n";
echo '# A plain summary for AI assistants: ' . absolute_url('llms.txt') . ' (full details: ' . absolute_url('llms-full.txt') . ")\n";
