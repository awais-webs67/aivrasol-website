<?php
declare(strict_types=1);

require APP_PATH . '/forms.php';

$router = new Router();

$router->get('/', fn () => view('pages/home', [
    'pageTitle' => setting('site_name', 'AivraSol') . ' | ' . setting('site_tagline'),
    'metaDescription' => setting('meta_description'),
    'services' => Content::offerings('service'),
    'projects' => Content::projects(8),
    'plans' => Content::plans('home'),
    'testimonials' => Content::testimonials(),
    'faqs' => Content::faqs('general'),
    'stats' => Content::stats(),
    'isHome' => true,
]));

$router->get('/services', fn () => view('pages/offerings', [
    'pageTitle' => 'Our Services | ' . setting('site_name', 'AivraSol'),
    'metaDescription' => 'Web, mobile, UI/UX, cloud, AI and SEO services from AivraSol. One team from idea to launch.',
    'type' => 'service',
    'items' => Content::offerings('service'),
    'crumbs' => ['Services' => 'services'],
]));

$router->get('/services/{slug}', function (string $slug) {
    $item = Content::offering('service', $slug) ?? abort(404);
    return view('pages/offering', [
        'pageTitle' => $item['meta_title'] ?: $item['title'] . ' | ' . setting('site_name', 'AivraSol'),
        'metaDescription' => $item['meta_description'] ?: $item['summary'],
        'item' => $item,
        'type' => 'service',
        'faqs' => Content::faqs($slug) ?: Content::faqs('general'),
        'crumbs' => ['Services' => 'services', $item['title'] => 'services/' . $slug],
        'schema' => [service_schema($item, 'services/' . $slug)],
    ]);
});

$router->get('/hire', fn () => view('pages/offerings', [
    'pageTitle' => 'Hire Developers | ' . setting('site_name', 'AivraSol'),
    'metaDescription' => 'Hire vetted React, Node.js, Laravel, Python, React Native and DevOps developers from AivraSol on flexible terms.',
    'type' => 'hire',
    'items' => Content::offerings('hire'),
    'crumbs' => ['Hire developers' => 'hire'],
]));

$router->get('/hire/{slug}', function (string $slug) {
    $item = Content::offering('hire', $slug) ?? abort(404);
    return view('pages/offering', [
        'pageTitle' => $item['meta_title'] ?: $item['title'] . ' | ' . setting('site_name', 'AivraSol'),
        'metaDescription' => $item['meta_description'] ?: $item['summary'],
        'item' => $item,
        'type' => 'hire',
        'faqs' => Content::faqs($slug) ?: Content::faqs('pricing'),
        'crumbs' => ['Hire developers' => 'hire', $item['title'] => 'hire/' . $slug],
        'schema' => [service_schema($item, 'hire/' . $slug)],
    ]);
});

$router->get('/portfolio', fn () => view('pages/portfolio', [
    'pageTitle' => 'Portfolio: Web, Mobile and E-commerce Projects | ' . setting('site_name', 'AivraSol'),
    'metaDescription' => 'Case studies of web apps, mobile apps, online stores and business systems built by AivraSol, with the challenge, our solution and the results.',
    'projects' => Content::projects(),
    'crumbs' => ['Portfolio' => 'portfolio'],
]));

$router->get('/portfolio/{slug}', function (string $slug) {
    $project = Content::project($slug) ?? abort(404);
    return view('pages/project', [
        'pageTitle' => $project['title'] . ' | ' . setting('site_name', 'AivraSol') . ' Portfolio',
        'metaDescription' => $project['summary'],
        'ogImage' => $project['image'],
        'project' => $project,
        'crumbs' => ['Portfolio' => 'portfolio', $project['title'] => 'portfolio/' . $slug],
        'more' => array_values(array_filter(Content::projects(), fn ($p) => $p['slug'] !== $slug)),
    ]);
});

$router->get('/pricing', function () {
    $selected = Content::plan((int) ($_GET['plan'] ?? 0));
    return view('pages/pricing', [
        'pageTitle' => 'Pricing & Free Quote | ' . setting('site_name', 'AivraSol'),
        'metaDescription' => 'Flexible pricing for product development, dedicated teams and hired developers. Get a free, no-obligation quote.',
        'plans' => Content::plans(),
        'selected' => $selected,
        'q1' => is_string($_GET['q1'] ?? null) ? $_GET['q1'] : '',
        'q2' => is_string($_GET['q2'] ?? null) ? $_GET['q2'] : '',
        'q1Other' => is_string($_GET['q1_other'] ?? null) ? mb_substr($_GET['q1_other'], 0, 150) : '',
        'q2Other' => is_string($_GET['q2_other'] ?? null) ? mb_substr($_GET['q2_other'], 0, 150) : '',
        'role' => in_array($_GET['role'] ?? '', array_column(Content::offerings('hire'), 'short_title'), true) ? $_GET['role'] : '',
        'faqs' => Content::faqs('pricing'),
        'crumbs' => ['Pricing' => 'pricing'],
    ]);
});

$router->get('/careers', fn () => view('pages/careers', [
    'pageTitle' => 'Careers: Jobs and Internships | ' . setting('site_name', 'AivraSol'),
    'metaDescription' => 'Join AivraSol. Open roles, internships and benefits for developers, designers and marketers.',
    'jobs' => Content::jobs(),
    'faqs' => Content::faqs('careers'),
    'crumbs' => ['Careers' => 'careers'],
]));

$router->get('/careers/{slug}', function (string $slug) {
    $job = Content::job($slug) ?? abort(404);
    return view('pages/job', [
        'pageTitle' => $job['title'] . ' | Careers at ' . setting('site_name', 'AivraSol'),
        'metaDescription' => $job['summary'],
        'job' => $job,
        'crumbs' => ['Careers' => 'careers', $job['title'] => 'careers/' . $slug],
        'schema' => [job_schema($job)],
    ]);
});

$router->get('/about', fn () => view('pages/about', [
    'pageTitle' => 'About Us: Software Development Company | ' . setting('site_name', 'AivraSol'),
    'metaDescription' => 'AivraSol is a software development company that builds web apps, mobile apps and AI solutions for growing businesses.',
    'stats' => Content::stats(),
    'crumbs' => ['About us' => 'about'],
]));

$router->get('/contact', fn () => view('pages/contact', [
    'pageTitle' => 'Contact Us | ' . setting('site_name', 'AivraSol'),
    'metaDescription' => 'Talk to AivraSol about your project. Email, phone, WhatsApp or send a message and we reply within one business day.',
    'crumbs' => ['Contact us' => 'contact'],
]));

foreach (['privacy-policy' => 'Privacy Policy', 'terms' => 'Terms & Conditions', 'cookie-policy' => 'Cookie Policy'] as $slug => $title) {
    $router->get('/' . $slug, fn () => view('pages/legal/' . $slug, [
        'pageTitle' => $title . ' | ' . setting('site_name', 'AivraSol'),
        'metaDescription' => [
            'privacy-policy' => 'How ' . setting('site_name', 'AivraSol') . ' collects, uses and protects your personal data, how long we keep it, and how to exercise your privacy rights.',
            'terms' => 'The terms and conditions for using the ' . setting('site_name', 'AivraSol') . ' website and our software development services, quotes and hiring plans.',
            'cookie-policy' => 'Which cookies the ' . setting('site_name', 'AivraSol') . ' website uses, why we use them, and how to change your cookie choices at any time.',
        ][$slug],
        'legalTitle' => $title,
    ]));
}

$router->get('/blog', function () {
    $posts = Content::posts();
    $cat = is_string($_GET['category'] ?? null) ? $_GET['category'] : '';
    $cats = array_values(array_unique(array_column($posts, 'category')));
    if ($cat !== '' && !in_array($cat, $cats, true)) {
        $cat = '';
    }
    return view('pages/blog', [
        'pageTitle' => 'Blog: Software, Hiring and Growth Guides | ' . setting('site_name', 'AivraSol'),
        'metaDescription' => 'Practical guides on software development costs, hiring developers, tech stacks, AI and growing your business online, from the AivraSol team.',
        'posts' => $cat === '' ? $posts : array_values(array_filter($posts, fn ($p) => $p['category'] === $cat)),
        'categories' => $cats,
        'category' => $cat,
        'metaRobots' => $cat === '' ? null : 'noindex, follow',
        'canonicalPath' => 'blog',
        'crumbs' => ['Blog' => 'blog'],
    ]);
});

$router->get('/blog/feed.xml', function () {
    header('Content-Type: application/rss+xml; charset=utf-8');
    return render('feed', ['posts' => array_slice(Content::posts(), 0, 20)]);
});

$router->get('/blog/{slug}', function (string $slug) {
    $post = Content::post($slug) ?? abort(404);
    return view('pages/post', [
        'pageTitle' => $post['meta_title'] ?: $post['title'] . ' | ' . setting('site_name', 'AivraSol'),
        'metaDescription' => $post['meta_description'] ?: $post['excerpt'],
        'ogImage' => $post['image'] ?: null,
        'ogType' => 'article',
        'post' => $post,
        'more' => array_slice(array_values(array_filter(Content::posts(), fn ($p) => $p['slug'] !== $slug)), 0, 3),
        'crumbs' => ['Blog' => 'blog', $post['title'] => 'blog/' . $slug],
        'schema' => [article_schema($post)],
    ]);
});

$router->get('/llms.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    return render('llms', ['full' => false]);
});

$router->get('/llms-full.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    return render('llms', ['full' => true]);
});

$router->get('/indexnow-key.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    return Seo::indexNowKey();
});

$router->get('/verify/{token}', function (string $token) {
    require_once APP_PATH . '/lib/Documents.php';
    header('X-Robots-Tag: noindex, nofollow');
    $data = ['pageTitle' => 'Verify a document | ' . setting('site_name', 'AivraSol'), 'metaRobots' => 'noindex, nofollow', 'emp' => null, 'doc' => null, 'limited' => false];
    if (!Security::rateLimit('verify', 40, 600)) {
        http_response_code(429);
        $data['limited'] = true;
    } elseif (preg_match('/^[a-z0-9]{16}$/', $token)) {
        $data['emp'] = Database::one('SELECT employee_code, full_name, designation, department, status, join_date, end_date FROM employees WHERE verify_token = ?', [$token]);
        if (!$data['emp']) {
            $data['doc'] = Database::one('SELECT d.ref_no, d.title, d.issued_at, d.revoked_at, d.data, e.employee_code FROM employee_documents d JOIN employees e ON e.id = d.employee_id WHERE d.verify_token = ?', [$token]);
        }
    }
    if (!$data['limited'] && !$data['emp'] && !$data['doc']) {
        http_response_code(404);
    }
    return view('pages/verify', $data);
});

$router->any('/unsubscribe/{token}', function (string $token) {
    header('X-Robots-Tag: noindex, nofollow');
    $contact = preg_match('/^[a-f0-9]{32}$/', $token) ? Database::one('SELECT id, email, status FROM contacts WHERE token = ?', [$token]) : null;
    $done = false;
    // The token in the link is the secret, so one-click unsubscribe from Gmail and Outlook (RFC 8058) works without a form token.
    if ($contact && is_post()) {
        if ($contact['status'] === 'subscribed') {
            Database::run("UPDATE contacts SET status = 'unsubscribed' WHERE id = ?", [$contact['id']]);
        }
        require_once APP_PATH . '/lib/Campaigns.php';
        Campaigns::suppress($contact['email'], 'unsubscribed');
        $done = true;
        if (($_POST['List-Unsubscribe'] ?? '') === 'One-Click') {
            http_response_code(200);
            return 'Unsubscribed';
        }
    }
    if (!$contact) {
        http_response_code(404);
    }
    return view('pages/unsubscribe', ['contact' => $contact, 'done' => $done || ($contact && $contact['status'] === 'unsubscribed'), 'pageTitle' => 'Unsubscribe | ' . setting('site_name', 'AivraSol'), 'metaRobots' => 'noindex, nofollow']);
});

$router->get('/cron/{token}', function (string $token) {
    require_once APP_PATH . '/lib/Campaigns.php';
    header('X-Robots-Tag: noindex, nofollow');
    header('Content-Type: text/plain; charset=utf-8');
    if (!hash_equals(Campaigns::cronToken(), $token)) {
        abort(404);
    }
    require_once APP_PATH . '/tasks.php';
    return run_scheduled_tasks(false);
});

$router->get('/team', function () {
    $members = Team::members();
    if (!$members || setting('team_list', '1') !== '1') {
        abort(404);
    }
    $site = setting('site_name', 'AivraSol');
    return view('pages/team', [
        'pageTitle' => 'Meet the Team: Developers, Designers and Experts | ' . $site,
        'metaDescription' => 'Meet the ' . $site . ' team. See each person\'s experience, skills and recent work, and talk to us about building your next project together.',
        'members' => $members,
        'crumbs' => ['Team' => 'team'],
    ]);
});

$router->get('/team/{slug}', function (string $slug) {
    $m = Team::member($slug) ?? abort(404);
    $site = setting('site_name', 'AivraSol');
    $about = Seo::plainText((string) ($m['about'] ?? ''));
    $desc = $m['name'] . ', ' . $m['headline'] . ' at ' . $site . '.' . ($about !== '' ? ' ' . $about : (!empty($m['skills']) ? ' Skills: ' . implode(', ', array_slice($m['skills'], 0, 8)) . '.' : ''));
    return view('pages/member', [
        'pageTitle' => $m['name'] . ', ' . $m['headline'] . ' | ' . $site,
        'metaDescription' => mb_strimwidth($desc, 0, 160, '...'),
        'ogImage' => $m['photo'] ?? '',
        'm' => $m,
        'preview' => false,
        'schema' => [Team::schema($m)],
        'crumbs' => setting('team_list', '1') === '1' ? ['Team' => 'team', $m['name'] => 'team/' . $slug] : [$m['name'] => 'team/' . $slug],
    ]);
});

$router->get('/sitemap.xml', function () {
    header('Content-Type: application/xml; charset=utf-8');
    return render('sitemap', [
        'services' => Content::offerings('service'),
        'hires' => Content::offerings('hire'),
        'projects' => Content::projects(),
        'jobs' => Content::jobs(),
        'posts' => Content::posts(),
        'members' => Team::members(),
    ]);
});

$router->get('/robots.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    return render('robots');
});

$router->post('/submit/consultation', fn () => handle_consultation());
$router->post('/submit/contact', fn () => handle_contact());
$router->post('/submit/quote', fn () => handle_quote());
$router->post('/submit/apply', fn () => handle_application());

require APP_PATH . '/portal.php';
require APP_PATH . '/admin/routes.php';

return $router;
