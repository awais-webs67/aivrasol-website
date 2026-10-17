<?php
// Ready-made certificates and letters for the Issuance section. Admins and HR can edit them or add their own.
// Placeholders: {name} {designation} {department_part} {join_date} {end_date} {duration} {company}
// {employment_type} {id_part} {details} {he} {He} {him} {his} {His}
return [
    [
        'code' => 'EXP', 'name' => 'Experience certificate', 'kind' => 'letter', 'title' => 'Experience Certificate',
        'salutation' => 'To whom it may concern', 'needs_end_date' => 1, 'details_label' => '', 'sort_order' => 1,
        'body' => "This is to certify that {name}{id_part} worked with {company} as {designation}{department_part} from {join_date} to {end_date}, a period of {duration}.\n\nDuring this time, {name} handled {his} responsibilities with dedication, honesty and professionalism, and worked well with colleagues and clients alike.\n\n{name} left the company on good terms. We thank {him} for {his} contribution and wish {him} every success in the future.\n\nThis letter is issued on request for whatever purpose it may serve.",
    ],
    [
        'code' => 'VER', 'name' => 'Employment verification letter', 'kind' => 'letter', 'title' => 'Employment Verification Letter',
        'salutation' => 'To whom it may concern', 'needs_end_date' => 0, 'details_label' => '', 'sort_order' => 2,
        'body' => "This is to confirm that {name}{id_part} has been employed with {company} since {join_date} and currently works as {designation}{department_part} on a {employment_type} basis.\n\n{He} is a valued member of our team.\n\nThis letter is issued at {his} request for whatever lawful purpose it may serve, and creates no financial liability for {company}.",
    ],
    [
        'code' => 'INT', 'name' => 'Internship completion certificate', 'kind' => 'certificate', 'title' => 'Certificate of Internship',
        'salutation' => 'This is to certify that', 'needs_end_date' => 1, 'details_label' => '', 'sort_order' => 3,
        'body' => "has successfully completed an internship with {company} as {designation}{department_part} from {join_date} to {end_date}. During the internship, {he} showed dedication, a strong willingness to learn and a professional attitude. We wish {him} every success in {his} career.",
    ],
    [
        'code' => 'APR', 'name' => 'Certificate of appreciation', 'kind' => 'certificate', 'title' => 'Certificate of Appreciation',
        'salutation' => 'This certificate is proudly presented to', 'needs_end_date' => 0, 'details_label' => 'What are you thanking them for?', 'sort_order' => 4,
        'body' => "in recognition of outstanding work as {designation}. {details} Thank you for your hard work, commitment and the difference you make at {company}.",
    ],
    [
        'code' => 'EOM', 'name' => 'Employee of the month', 'kind' => 'certificate', 'title' => 'Employee of the Month',
        'salutation' => 'This certificate is proudly presented to', 'needs_end_date' => 0, 'details_label' => 'Month, for example October 2026', 'sort_order' => 5,
        'body' => "for being named Employee of the Month for {details}, in recognition of excellent performance, great teamwork and real commitment to our clients.",
    ],
    [
        'code' => 'TRN', 'name' => 'Training completion certificate', 'kind' => 'certificate', 'title' => 'Certificate of Completion',
        'salutation' => 'This is to certify that', 'needs_end_date' => 0, 'details_label' => 'Course or training name', 'sort_order' => 6,
        'body' => "has successfully completed the {details} training programme at {company}. We commend {him} for {his} commitment to learning and professional growth.",
    ],
];
