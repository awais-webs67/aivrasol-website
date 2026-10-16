<?php
/**
 * llms.txt: a plain summary of the company for AI assistants and AI search (see llmstxt.org).
 * Built from live content, so it stays correct when the admin panel changes.
 * @var bool $full  llms-full.txt adds service details, plans, FAQs and article summaries.
 */
$site = setting('site_name', 'AivraSol');
$line = fn (string $s) => trim(preg_replace('/\s+/', ' ', $s) ?? '');
$summary = setting('company_summary') !== '' ? setting('company_summary') : setting('meta_description');
$offices = Content::offices();
$countries = array_values(array_unique(array_column($offices, 'country')));
$out = "# $site\n\n> " . $line($summary) . "\n\n";
$out .= "$site is a software development company. It designs, builds and supports web apps, mobile apps, cloud systems and AI tools, and offers dedicated developers for hire on monthly plans.";
if ($countries) {
    $out .= ' It has teams in ' . (count($countries) > 1 ? implode(', ', array_slice($countries, 0, -1)) . ' and ' . end($countries) : $countries[0]) . ' and works with clients worldwide.';
}
$out .= "\n\n";
$facts = array_filter([
    'Website' => absolute_url(),
    'Founded' => setting('company_founded'),
    'Business email' => setting('email_info'),
    'Sales email' => setting('email_sales'),
    'Careers email' => setting('email_hr'),
    'Phone' => setting('phone'),
    'WhatsApp' => setting('whatsapp'),
    'Office hours' => setting('office_hours'),
]);
foreach (Content::stats() as $st) {
    $facts[$st['label']] = $st['value'];
}
foreach ($facts as $k => $v) {
    $out .= "- $k: $v\n";
}
$out .= "\n## Services\n\n";
foreach (Content::offerings('service') as $s) {
    $out .= '- [' . $s['title'] . '](' . absolute_url('services/' . $s['slug']) . '): ' . $line($s['summary']) . "\n";
}
$out .= "\n## Hire developers\n\n";
foreach (Content::offerings('hire') as $h) {
    $out .= '- [' . $h['title'] . '](' . absolute_url('hire/' . $h['slug']) . '): ' . $line($h['summary']) . "\n";
}
if ($offices) {
    $out .= "\n## Offices\n\n";
    foreach ($offices as $o) {
        $where = trim($o['address'] . ($o['address'] !== '' && $o['city'] !== '' ? ', ' : '') . $o['city']);
        $out .= '- ' . $o['country'] . ($o['is_head_office'] ? ' (head office)' : '') . ($where !== '' ? ": $where" : '') . ($o['phone'] !== '' ? ', phone ' . $o['phone'] : '') . "\n";
    }
}
$out .= "\n## Key pages\n\n";
foreach (['pricing' => 'Pricing and free quote: plans for projects and hired developers', 'portfolio' => 'Portfolio: case studies of delivered projects', 'about' => 'About the company, values and locations', 'careers' => 'Careers: open jobs and internships', 'contact' => 'Contact: email, phone, WhatsApp and contact form', 'blog' => 'Blog: guides on software costs, hiring and technology'] as $p => $d) {
    $out .= '- [' . strtok($d, ':') . '](' . absolute_url($p) . '):' . substr($d, strpos($d, ':') + 1) . "\n";
}
$posts = Content::posts();
if ($posts) {
    $out .= "\n## Articles\n\n";
    foreach (array_slice($posts, 0, $full ? 50 : 10) as $p) {
        $out .= '- [' . $p['title'] . '](' . absolute_url('blog/' . $p['slug']) . '): ' . $line($p['excerpt']) . "\n";
    }
}
if ($full) {
    $out .= "\n## Service details\n\n";
    foreach (array_merge(Content::offerings('service'), Content::offerings('hire')) as $s) {
        $out .= '### ' . $s['title'] . "\n\n" . $line($s['hero_text']) . "\n\n";
        foreach (pairs($s['features']) as ['title' => $t, 'text' => $d]) {
            $out .= "- $t: " . $line($d) . "\n";
        }
        if ($s['tech'] !== '') {
            $out .= '- Technologies: ' . $line($s['tech']) . "\n";
        }
        $out .= "\n";
    }
    $out .= "## Pricing plans\n\n";
    foreach (Content::plans() as $pl) {
        $price = trim($pl['price_label'] . ' ' . $pl['price_note']);
        $out .= '- ' . $pl['title'] . ($price !== '' ? " ($price)" : '') . ': ' . $line($pl['subtitle']) . "\n";
    }
    $out .= "\nEvery project gets a free, no-obligation quote: " . absolute_url('pricing') . "\n";
    $faqs = array_merge(Content::faqs('general'), Content::faqs('pricing'));
    if ($faqs) {
        $out .= "\n## Frequently asked questions\n\n";
        foreach ($faqs as $f) {
            $out .= '**' . $line($f['question']) . "**\n" . $line($f['answer']) . "\n\n";
        }
    }
} else {
    $out .= "\n## Optional\n\n- [Full details](" . absolute_url('llms-full.txt') . "): services, technologies, pricing plans and FAQs in one file\n";
}
echo $out;
