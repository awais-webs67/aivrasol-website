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
    'pageTitle' => 'Our Services | AivraSol',
    'metaDescription' => 'Web, mobile, UI/UX, cloud, AI and SEO services from AivraSol. One team from idea to launch.',
    'type' => 'service',
    'items' => Content::offerings('service'),
]));

$router->get('/services/{slug}', function (string $slug) {
    $item = Content::offering('service', $slug) ?? abort(404);
    return view('pages/offering', [
        'pageTitle' => $item['meta_title'] ?: $item['title'] . ' | AivraSol',
        'metaDescription' => $item['meta_description'] ?: $item['summary'],
        'item' => $item,
        'type' => 'service',
        'faqs' => Content::faqs($slug) ?: Content::faqs('general'),
    ]);
});

$router->get('/hire', fn () => view('pages/offerings', [
    'pageTitle' => 'Hire Developers | AivraSol',
    'metaDescription' => 'Hire vetted React, Node.js, Laravel, Python, React Native and DevOps developers from AivraSol on flexible terms.',
    'type' => 'hire',
    'items' => Content::offerings('hire'),
]));

$router->get('/hire/{slug}', function (string $slug) {
    $item = Content::offering('hire', $slug) ?? abort(404);
    return view('pages/offering', [
        'pageTitle' => $item['meta_title'] ?: $item['title'] . ' | AivraSol',
        'metaDescription' => $item['meta_description'] ?: $item['summary'],
        'item' => $item,
        'type' => 'hire',
        'plans' => Content::plans('hire'),
        'faqs' => Content::faqs($slug) ?: Content::faqs('pricing'),
    ]);
});

$router->get('/portfolio', fn () => view('pages/portfolio', [
    'pageTitle' => 'Portfolio | AivraSol',
    'metaDescription' => 'Selected web, mobile and e-commerce projects built by AivraSol.',
    'projects' => Content::projects(),
]));

$router->get('/portfolio/{slug}', function (string $slug) {
    $project = Content::project($slug) ?? abort(404);
    return view('pages/project', [
        'pageTitle' => $project['title'] . ' | AivraSol Portfolio',
        'metaDescription' => $project['summary'],
        'ogImage' => $project['image'],
        'project' => $project,
        'more' => array_values(array_filter(Content::projects(), fn ($p) => $p['slug'] !== $slug)),
    ]);
});

$router->get('/pricing', function () {
    $selected = Content::plan((int) ($_GET['plan'] ?? 0));
    return view('pages/pricing', [
        'pageTitle' => 'Pricing & Free Quote | AivraSol',
        'metaDescription' => 'Flexible pricing for product development, dedicated teams and hired developers. Get a free, no-obligation quote.',
        'plans' => Content::plans(),
        'selected' => $selected,
        'q1' => is_string($_GET['q1'] ?? null) ? $_GET['q1'] : '',
        'q2' => is_string($_GET['q2'] ?? null) ? $_GET['q2'] : '',
        'faqs' => Content::faqs('pricing'),
    ]);
});

$router->get('/careers', fn () => view('pages/careers', [
    'pageTitle' => 'Careers | AivraSol',
    'metaDescription' => 'Join AivraSol. Open roles, internships and benefits for developers, designers and marketers.',
    'jobs' => Content::jobs(),
    'faqs' => Content::faqs('careers'),
]));

$router->get('/careers/{slug}', function (string $slug) {
    $job = Content::job($slug) ?? abort(404);
    return view('pages/job', [
        'pageTitle' => $job['title'] . ' | Careers at AivraSol',
        'metaDescription' => $job['summary'],
        'job' => $job,
    ]);
});

$router->get('/about', fn () => view('pages/about', [
    'pageTitle' => 'About Us | AivraSol',
    'metaDescription' => 'AivraSol is a software development company that builds web apps, mobile apps and AI solutions for growing businesses.',
    'stats' => Content::stats(),
]));

$router->get('/contact', fn () => view('pages/contact', [
    'pageTitle' => 'Contact Us | AivraSol',
    'metaDescription' => 'Talk to AivraSol about your project. Email, phone, WhatsApp or send a message and we reply within one business day.',
]));

foreach (['privacy-policy' => 'Privacy Policy', 'terms' => 'Terms & Conditions', 'cookie-policy' => 'Cookie Policy'] as $slug => $title) {
    $router->get('/' . $slug, fn () => view('pages/legal/' . $slug, [
        'pageTitle' => $title . ' | AivraSol',
        'metaDescription' => $title . ' for the AivraSol website.',
        'legalTitle' => $title,
    ]));
}

$router->get('/sitemap.xml', function () {
    header('Content-Type: application/xml; charset=utf-8');
    return render('sitemap', [
        'services' => Content::offerings('service'),
        'hires' => Content::offerings('hire'),
        'projects' => Content::projects(),
        'jobs' => Content::jobs(),
    ]);
});

$router->get('/robots.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    return "User-agent: *\nDisallow: /admin\nDisallow: /submit\n\nSitemap: " . absolute_url('sitemap.xml') . "\n";
});

$router->post('/submit/consultation', fn () => handle_consultation());
$router->post('/submit/contact', fn () => handle_contact());
$router->post('/submit/quote', fn () => handle_quote());
$router->post('/submit/apply', fn () => handle_application());

require APP_PATH . '/admin/routes.php';

return $router;
