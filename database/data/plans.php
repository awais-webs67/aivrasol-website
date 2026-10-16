<?php
// Starter pricing plans. "[hire-roles]" in an options list is replaced on the website
// by the list of live "Hire a developer" pages, so new roles appear automatically.
return [
    [
        'category' => 'project', 'title' => 'Product Development', 'subtitle' => 'Get your product built from scratch', 'icon' => 'box',
        'features' => "Fixed scope and clear milestones\nDesign, development and testing in one team\nWeekly demos and progress reports\nSource code and full ownership are yours\n3 months of free bug fixing after launch",
        'q1_label' => 'What are you looking to build?', 'q1_options' => "MVP\nComplete product\nUI/UX design only\nWorking prototype\nSomething else",
        'q2_label' => 'Which platform?', 'q2_options' => "Web app\nMobile app (iOS and Android)\nWeb and mobile\nDesktop app\nNot sure yet",
        'is_highlighted' => 0, 'show_on_home' => 1, 'sort_order' => 1,
    ],
    [
        'category' => 'project', 'title' => 'Website & E-commerce', 'subtitle' => 'A fast, modern website or online store', 'icon' => 'monitor',
        'features' => "Custom design that matches your brand\nMobile friendly and fast loading\nSEO basics set up from day one\nEasy admin panel to edit content\nPayment gateways and shipping for stores",
        'q1_label' => 'What do you need?', 'q1_options' => "Company website\nOnline store\nLanding page\nWebsite redesign\nSomething else",
        'q2_label' => 'When do you need it?', 'q2_options' => "As soon as possible\nWithin 1 month\n1 to 3 months\nJust exploring",
        'is_highlighted' => 0, 'show_on_home' => 0, 'sort_order' => 2,
    ],
    [
        'category' => 'project', 'title' => 'Maintenance & Support', 'subtitle' => 'Keep your app secure, fast and up to date', 'icon' => 'shield',
        'features' => "Security updates and backups\nBug fixes and small changes\nUptime and performance monitoring\nMonthly report in plain language\nPriority help when something breaks",
        'q1_label' => 'What should we look after?', 'q1_options' => "Website\nWeb app\nMobile app\nServers and cloud\nNot sure",
        'q2_label' => 'Support level', 'q2_options' => "Basic (bug fixes)\nStandard (fixes and small updates)\nPremium (24/7 monitoring)",
        'is_highlighted' => 0, 'show_on_home' => 0, 'sort_order' => 3,
    ],
    [
        'category' => 'hire', 'title' => 'Hire a Developer', 'subtitle' => 'A full-time expert on your team', 'icon' => 'user',
        'features' => "Senior developers vetted by our tech leads\n160 hours a month, only on your project\nStart within one week\nWorks in your tools and time zone\nFree replacement if the fit is not right",
        'q1_label' => 'Which developer do you need?', 'q1_options' => '[hire-roles]',
        'q2_label' => 'For how long?', 'q2_options' => "1 to 3 months\n3 to 6 months\n6 to 12 months\n12+ months",
        'is_highlighted' => 1, 'show_on_home' => 1, 'sort_order' => 4,
    ],
    [
        'category' => 'hire', 'title' => 'Part-time or Hourly', 'subtitle' => 'Pay only for the hours you need', 'icon' => 'chat',
        'features' => "Flexible hours each month\nWeekly timesheets and updates\nGreat for small features and fixes\nScale hours up or down any month\nNo long-term contract",
        'q1_label' => 'Which developer do you need?', 'q1_options' => '[hire-roles]',
        'q2_label' => 'Hours per month', 'q2_options' => "20 to 40 hours\n40 to 80 hours\n80 to 120 hours",
        'is_highlighted' => 0, 'show_on_home' => 0, 'sort_order' => 5,
    ],
    [
        'category' => 'hire', 'title' => 'Dedicated Team', 'subtitle' => 'A full team working only on your project', 'icon' => 'users',
        'features' => "Developers, designer and project manager\nScale the team up or down each month\nWorks in your time zone and tools\nNo hiring or retention stress\nClear monthly billing",
        'q1_label' => 'Team size', 'q1_options' => "2 to 3 people\n4 to 6 people\n7 or more people\nNot sure yet",
        'q2_label' => 'For how long?', 'q2_options' => "1 to 3 months\n3 to 6 months\n6 to 12 months\n12+ months",
        'is_highlighted' => 0, 'show_on_home' => 1, 'sort_order' => 6,
    ],
];
