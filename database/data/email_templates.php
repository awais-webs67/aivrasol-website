<?php
// Starter campaign templates. Edit them in Admin, Email templates.
// Placeholders: {first_name}, {last_name}, {company}, {website}, {country}, {sender_name}, {site_name}, {site_url}, {booking_url}.
// {{Hi|Hello|Hey}} picks one option at random for each email, so messages are not all identical.
return [
    [
        'name' => 'Cold intro: custom software',
        'kind' => 'cold',
        'layout' => 'plain',
        'subject' => '{{Quick question|Idea}} for {company}',
        'body' => "{{Hi|Hello}} {first_name},\n\nI came across {company} and had a quick idea. Many teams like yours lose hours every week to manual work: spreadsheets, copy and paste between tools, and reports built by hand.\n\nAt {site_name} we build simple custom tools that remove that work, usually in a few weeks and on a fixed price.\n\nWould it be worth a 15-minute call to see if there is a quick win for {company}?\n\nBest regards,\n{sender_name}\n{site_name}\n{site_url}",
        'sort_order' => 1,
    ],
    [
        'name' => 'Cold intro: hire developers',
        'kind' => 'cold',
        'layout' => 'plain',
        'subject' => 'Extra developers for {company}?',
        'body' => "{{Hi|Hello}} {first_name},\n\nIf {company} has more product work than developers, we can help. {site_name} provides vetted React, Node.js, Laravel, Python and mobile developers who join your team full time, on a simple monthly plan.\n\nYou interview them first, they start within days, and you can scale up or down at any time.\n\nIs this something you are looking at this quarter?\n\nThanks,\n{sender_name}\n{site_name}",
        'sort_order' => 2,
    ],
    [
        'name' => 'Follow-up (send 3 to 5 days later)',
        'kind' => 'follow_up',
        'layout' => 'plain',
        'subject' => 'Re: {company}',
        'body' => "{{Hi|Hello}} {first_name},\n\nJust following up on my last note in case it got buried.\n\nIf custom software or extra developers are not a priority right now, no problem at all. A one-line reply is enough and I will not follow up again.\n\nBest,\n{sender_name}",
        'sort_order' => 3,
    ],
    [
        'name' => 'Newsletter',
        'kind' => 'newsletter',
        'layout' => 'branded',
        'subject' => 'What is new at {site_name}',
        'body' => "{{Hi|Hello}} {first_name},\n\nHere is a short update from the {site_name} team.\n\nWhat we shipped this month:\n- A new online store with custom checkout for a retail client\n- An AI assistant that answers customer questions from a company knowledge base\n- A mobile app for field teams that works offline\n\nFrom the blog:\n- How much does it cost to build a web app in 2026?\n- Dedicated developer or freelancer: which one is right for you?\n\nRead more at {site_url}/blog\n\nIf you have a project in mind, just reply to this email.\n\nThe {site_name} team",
        'sort_order' => 4,
    ],
    [
        'name' => 'Announcement or offer',
        'kind' => 'announcement',
        'layout' => 'branded',
        'subject' => 'A free project review for {company}',
        'body' => "{{Hi|Hello}} {first_name},\n\nThis month we are offering a free 30-minute review of your website or app.\n\nOne of our senior engineers will look at speed, security and user experience, and send you a short list of improvements you can make, whether or not you work with us.\n\nBook your slot here: {booking_url}\n\nThe {site_name} team",
        'sort_order' => 5,
    ],
];
