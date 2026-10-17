<?php
// Default automatic replies sent after each website form. {name}, {site_name} and {ref} are filled in.
// Admins can change them in Admin, Email. Blank lines make paragraphs; lines starting with "- " make a list.
return [
    'contact' => [
        'subject' => 'Thank you for contacting {site_name} (Ref. {ref})',
        'body' => "Thank you for contacting {site_name}. This email confirms that we have received your enquiry and passed it to the right member of our team.\n\nWhat happens next:\n- A specialist will review your message carefully.\n- You will receive a personal reply within one business day.\n- Where useful, we will suggest a short call to discuss your requirements in more detail.\n\nIf you would like to share further details or documents in the meantime, simply reply to this email.",
    ],
    'consultation' => [
        'subject' => 'Your free consultation request with {site_name} (Ref. {ref})',
        'body' => "Thank you for requesting a free consultation with {site_name}. We have received your request, and a senior consultant will contact you within one business day to arrange a convenient time.\n\nTo make the most of the session, you may wish to prepare:\n- A short description of your business and goals\n- Any existing designs, documents or links\n- Your preferred timeline and budget range\n\nYou are welcome to send these by replying to this email.",
    ],
    'quote' => [
        'subject' => 'Your quotation request with {site_name} (Ref. {ref})',
        'body' => "Thank you for requesting a quotation from {site_name}. Our team will review your requirements and prepare a clear, itemised proposal with no hidden costs.\n\nWhat happens next:\n- We review your requirements and may contact you with a few short questions.\n- You will receive your quotation within one business day.\n- The quotation is free and carries no obligation.",
    ],
    'hire' => [
        'subject' => 'Your request to hire a developer from {site_name} (Ref. {ref})',
        'body' => "Thank you for your interest in hiring a dedicated developer from {site_name}. We have received your request and will prepare a shortlist of pre-vetted developers whose skills match your needs.\n\nWhat happens next:\n- An account manager reviews your requirements.\n- Within one business day, we share suitable profiles and their availability.\n- You interview the candidates and choose who joins your team.",
    ],
    'application' => [
        'subject' => 'Application received: {site_name} (Ref. {ref})',
        'body' => "Thank you for your interest in joining {site_name}. This email confirms that we have received your application.\n\nOur Human Resources team reviews every application carefully. If your experience matches the role, we will contact you to arrange an interview.\n\nDue to the number of applications we receive, we are only able to contact shortlisted candidates. We appreciate the time you have taken to apply and wish you every success.",
    ],
];
