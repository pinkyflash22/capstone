<?php

return [
    'socials' => [
        'Facebook' => ['icon' => 'facebook-f', 'url' => env('PYROSENSE_FACEBOOK_URL')],
        'LinkedIn' => ['icon' => 'linkedin-in', 'url' => env('PYROSENSE_LINKEDIN_URL')],
        'Instagram' => ['icon' => 'instagram', 'url' => env('PYROSENSE_INSTAGRAM_URL')],
    ],
    // Path to an approved logo within public/, for example images/bfp-urbiztondo.png.
    'partner_logo' => env('PYROSENSE_BFP_LOGO_PATH'),
];
