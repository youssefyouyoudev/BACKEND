<?php

return [
    'max_channels' => 30,
    'query_limit' => 1500,

    /*
    |--------------------------------------------------------------------------
    | Curated public sports networks
    |--------------------------------------------------------------------------
    |
    | Order matters. Earlier networks are preferred when the catalog is capped.
    | Keep this list limited to channels you are licensed to distribute.
    |
    */
    'networks' => [
        ['label' => 'beIN Sports', 'keywords' => ['bein sports', 'beinsports']],
        ['label' => 'Arryadia', 'keywords' => ['arryadia', 'arriyadia', 'الرياضية المغربية']],
        ['label' => 'Abu Dhabi Sports', 'keywords' => ['abu dhabi sports', 'abu dhabi sport', 'ad sports', 'ad sport', 'أبوظبي الرياضية']],
        ['label' => 'SSC Sports', 'keywords' => ['ssc']],
        ['label' => 'Al Kass Sports', 'keywords' => ['alkass', 'al kass', 'الكأس الرياضية']],
        ['label' => 'Dubai Sports', 'keywords' => ['dubai sports', 'dubai sport', 'دبي الرياضية']],
        ['label' => 'OnTime Sports', 'keywords' => ['ontime sports', 'on time sports', 'أون تايم سبورتس']],
        ['label' => 'KSA Sports', 'keywords' => ['ksa sports', 'ksa sport', 'saudi sports', 'السعودية الرياضية']],
        ['label' => 'Kuwait Sports', 'keywords' => ['kuwait sports', 'kuwait sport', 'الكويت الرياضية']],
        ['label' => 'Oman Sports', 'keywords' => ['oman sports', 'oman sport', 'عمان الرياضية']],
        ['label' => 'Bahrain Sports', 'keywords' => ['bahrain sports', 'bahrain sport', 'البحرين الرياضية']],
        ['label' => 'Iraqia Sports', 'keywords' => ['iraqia sports', 'iraqi sports', 'العراقية الرياضية']],
        ['label' => 'Jordan Sports', 'keywords' => ['jordan sports', 'jordan sport', 'الأردن الرياضية']],
        ['label' => 'World Cup', 'keywords' => ['world cup', 'fifa world cup', 'كأس العالم']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Featured receiver lineup
    |--------------------------------------------------------------------------
    |
    | These patterns match normalized channel names after country, quality,
    | resolution, and event-only labels have been removed.
    |
    */
    'featured' => [
        ['label' => 'beIN Sports 1', 'pattern' => '/^bein sports 1$/i'],
        ['label' => 'beIN Sports 2', 'pattern' => '/^bein sports 2$/i'],
        ['label' => 'beIN Sports 3', 'pattern' => '/^bein sports 3$/i'],
        ['label' => 'beIN Sports 4', 'pattern' => '/^bein sports 4$/i'],
        ['label' => 'beIN Sports 5', 'pattern' => '/^bein sports 5$/i'],
        ['label' => 'beIN Sports 6', 'pattern' => '/^bein sports 6$/i'],
        ['label' => 'beIN Sports 7', 'pattern' => '/^bein sports 7$/i'],
        ['label' => 'beIN Sports 8', 'pattern' => '/^bein sports 8$/i'],
        ['label' => 'beIN Sports 9', 'pattern' => '/^bein sports 9$/i'],
        ['label' => 'beIN Sports News', 'pattern' => '/^bein sports news$/i'],
        ['label' => 'beIN Sports Max 1 - World Cup', 'pattern' => '/^bein sports max 1(?: world cup)?$/i'],
        ['label' => 'beIN Sports Max 2 - World Cup', 'pattern' => '/^bein sports max 2(?: world cup)?$/i'],
        ['label' => 'beIN Sports Max 3 - World Cup', 'pattern' => '/^bein sports max 3(?: world cup)?$/i'],
        ['label' => 'beIN Sports Max 4 - World Cup', 'pattern' => '/^bein sports max 4(?: world cup)?$/i'],
        ['label' => 'beIN Sports Max 5 - World Cup', 'pattern' => '/^bein sports max 5(?: world cup)?$/i'],
        ['label' => 'beIN Sports Max 6 - World Cup', 'pattern' => '/^bein sports max 6(?: world cup)?$/i'],
        ['label' => 'Arryadia', 'pattern' => '/^arryadia$/i'],
        ['label' => 'AD Sport 1', 'pattern' => '/^(?:ad|abu dhabi) sport(?:s)? 1$/i'],
        ['label' => 'AD Sport 2', 'pattern' => '/^(?:ad|abu dhabi) sport(?:s)? 2$/i'],
        ['label' => 'AD Sport Premium 1', 'pattern' => '/^(?:ad|abu dhabi) sport(?:s)? premium 1$/i'],
        ['label' => 'AD Sport Premium 2', 'pattern' => '/^(?:ad|abu dhabi) sport(?:s)? premium 2$/i'],
        ['label' => 'SSC 1', 'pattern' => '/^ssc 1$/i'],
        ['label' => 'SSC 2', 'pattern' => '/^ssc 2$/i'],
        ['label' => 'SSC 3', 'pattern' => '/^ssc 3$/i'],
        ['label' => 'SSC 4', 'pattern' => '/^ssc 4$/i'],
        ['label' => 'SSC 5', 'pattern' => '/^ssc 5$/i'],
        ['label' => 'SSC Extra 1', 'pattern' => '/^ssc extra 1$/i'],
        ['label' => 'SSC Extra 2', 'pattern' => '/^ssc extra 2$/i'],
        ['label' => 'SSC Extra 3', 'pattern' => '/^ssc extra 3$/i'],
        ['label' => 'Al Kass One', 'pattern' => '/^(?:al ?kass|alkass) (?:one|1)$/i'],
        ['label' => 'Al Kass Two', 'pattern' => '/^(?:al ?kass|alkass) (?:two|2)$/i'],
        ['label' => 'Al Kass Four', 'pattern' => '/^(?:al ?kass|alkass) (?:four|4)$/i'],
        ['label' => 'Dubai Sports 1', 'pattern' => '/^dubai sports 1$/i'],
        ['label' => 'Dubai Sports 2', 'pattern' => '/^dubai sports 2$/i'],
        ['label' => 'OnTime Sports', 'pattern' => '/^ontime sports$/i'],
        ['label' => 'OnTime Sports 2', 'pattern' => '/^ontime sports 2$/i'],
    ],
];
