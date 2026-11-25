<?php
/**
 * Questionnaire schema — the single source of truth for both page templates,
 * the AJAX validator and the admin screens.
 *
 * Every answer is stored by option KEY (never by translated label) so English
 * and Arabic submissions land in the same columns and can be compared side by
 * side. Labels are looked up from here when displaying / exporting.
 *
 * Question types:
 *   single  — one choice (radio)
 *   multi   — any number of choices (checkbox). 'source' => qid limits the
 *             options to whatever was ticked in that earlier question.
 *   scale   — 1–5 rating for each item
 *   rank    — order the top 3 of whatever was ticked in 'source'
 *   text    — open answer (textarea)
 *   input   — single-line contact field
 *
 * An option with 'specify' => true reveals a text box; its value is stored as
 * "{qid}_other".
 */

if (!defined('ABSPATH')) exit;

function ncq_other_option() {
    return ['en' => 'Other, specify', 'ar' => 'أخرى، حدد', 'specify' => true];
}

function ncq_yes_no() {
    return [
        'yes' => ['en' => 'Yes', 'ar' => 'نعم'],
        'no'  => ['en' => 'No',  'ar' => 'لا'],
    ];
}

/** Decision-factor list — used by EG3 (select all, then ranked in EG3a). */
function ncq_factor_options() {
    return [
        'country'    => ['en' => 'The country itself',                                  'ar' => 'البلد نفسها'],
        'developer'  => ['en' => 'Developer name / reputation',                         'ar' => 'اسم المطور / سمعته'],
        'location'   => ['en' => 'Area / location inside the country',                  'ar' => 'المنطقة / الموقع داخل البلد'],
        'facilities' => ['en' => 'Facilities / services',                               'ar' => 'المرافق / الخدمات'],
        'safety'     => ['en' => 'Safety concerns',                                     'ar' => 'الأمان'],
        'climate'    => ['en' => 'Climate',                                             'ar' => 'المناخ'],
        'benefits'   => ['en' => 'Benefits (Residency / Golden Visa)',                  'ar' => 'المزايا (الإقامة / الجولدن فيزا)'],
        'access'     => ['en' => 'Accessibility from home country',                     'ar' => 'سهولة الوصول من بلدك'],
        'cost'       => ['en' => 'Cost of living (more affordable quality of living)',  'ar' => 'تكلفة المعيشة (جودة حياة بسعر أنسب)'],
        'legal'      => ['en' => 'Legal ease',                                          'ar' => 'سهولة الإجراءات القانونية'],
        'lifestyle'  => ['en' => 'Lifestyle',                                           'ar' => 'نمط الحياة'],
        'year_round' => ['en' => 'Year-round usability',                                'ar' => 'إمكانية الاستخدام طوال العام'],
        'other'      => ncq_other_option(),
    ];
}

function ncq_schema() {
    static $schema = null;
    if ($schema !== null) return $schema;

    $schema = [

        // ── 1. LET'S START WITH YOU ─────────────────────────────────────────
        [
            'key'   => 'you',
            'image' => 'sidi-beach.jpg',
            'place' => ['en' => 'Sidi Abdel Rahman', 'ar' => 'سيدي عبد الرحمن'],
            'title' => ['en' => 'Let’s start with you', 'ar' => 'لنبدأ بك'],
            'intro' => ['en' => 'A few quick details so we can understand your perspective.', 'ar' => 'بعض التفاصيل السريعة لنفهم وجهة نظرك.'],
            'questions' => [
                [
                    'id' => 'gender', 'code' => 'S1', 'type' => 'single', 'required' => true, 'layout' => 'pair',
                    'label' => ['en' => 'Please specify your gender.', 'ar' => 'يرجى تحديد الجنس.'],
                    'options' => [
                        'male'   => ['en' => 'Male',   'ar' => 'ذكر'],
                        'female' => ['en' => 'Female', 'ar' => 'أنثى'],
                    ],
                ],
                [
                    'id' => 'nationality', 'code' => 'S2', 'type' => 'single', 'required' => true,
                    'label' => ['en' => 'Where are you from?', 'ar' => 'من أين أنت؟'],
                    'hint'  => ['en' => 'Please select your nationality.', 'ar' => 'ما هي جنسيتك؟'],
                    'options' => [
                        'ksa'    => ['en' => 'KSA',    'ar' => 'السعودية'],
                        'uk'     => ['en' => 'UK',     'ar' => 'بريطانيا'],
                        'china'  => ['en' => 'China',  'ar' => 'الصين'],
                        'russia' => ['en' => 'Russia', 'ar' => 'روسيا'],
                        'canada' => ['en' => 'Canada', 'ar' => 'كندا'],
                        'kuwait' => ['en' => 'Kuwait', 'ar' => 'الكويت'],
                        'uae'    => ['en' => 'UAE',    'ar' => 'الإمارات'],
                        'other'  => ncq_other_option(),
                    ],
                ],
                [
                    'id' => 'other_nat', 'code' => 'S3', 'type' => 'single', 'required' => true, 'layout' => 'pair',
                    'label' => ['en' => 'Do you hold any other nationalities?', 'ar' => 'هل تحمل أي جنسيات أخرى؟'],
                    'hint'  => ['en' => 'If yes, please specify.', 'ar' => 'إذا كانت الإجابة نعم، يرجى التحديد.'],
                    'options' => [
                        'yes' => ['en' => 'Yes', 'ar' => 'نعم', 'specify' => true,
                                  'placeholder' => ['en' => 'Which nationality?', 'ar' => 'ما هي الجنسية؟']],
                        'no'  => ['en' => 'No',  'ar' => 'لا'],
                    ],
                ],
                [
                    'id' => 'age', 'code' => 'S4', 'type' => 'single', 'required' => true,
                    'label' => ['en' => 'Which age group are you in?', 'ar' => 'ما هي فئتك العمرية؟'],
                    'options' => [
                        'under_25' => ['en' => 'Under 25', 'ar' => 'أقل من 25', 'terminate' => true],
                        '25_34'    => ['en' => '25 – 34',  'ar' => '25 – 34'],
                        '35_44'    => ['en' => '35 – 44',  'ar' => '35 – 44'],
                        '45_54'    => ['en' => '45 – 54',  'ar' => '45 – 54'],
                        '55_64'    => ['en' => '55 – 64',  'ar' => '55 – 64'],
                        '65_plus'  => ['en' => '65+',      'ar' => '65+'],
                    ],
                ],
            ],
        ],

        // ── SUMMER HOMES ────────────────────────────────────────────────────
        [
            'key'   => 'homes',
            'image' => 'alamein-towers-sunset.jpg',
            'place' => ['en' => 'New Alamein', 'ar' => 'العلمين الجديدة'],
            'title' => ['en' => 'Now, let’s talk about summer homes', 'ar' => 'والآن، لنتحدث عن المنازل الصيفية'],
            'questions' => [
                [
                    'id' => 'summer_home', 'code' => 'S5', 'type' => 'single', 'required' => true, 'layout' => 'pair',
                    'label' => ['en' => 'Are you interested in buying a summer home?', 'ar' => 'هل أنت مهتم بشراء منزل صيفي؟'],
                    'options' => [
                        'yes' => ['en' => 'Yes', 'ar' => 'نعم'],
                        'no'  => ['en' => 'No',  'ar' => 'لا', 'terminate' => true],
                    ],
                ],
                [
                    'id' => 'budget', 'code' => 'S6', 'type' => 'single', 'required' => true,
                    'label' => ['en' => 'What kind of budget are you considering?', 'ar' => 'ما هي الميزانية التي تفكر فيها؟'],
                    'hint'  => ['en' => 'Choose the range that best matches you.', 'ar' => 'اختر النطاق الأنسب لك.'],
                    'options' => [
                        '0_300k'     => ['en' => 'USD 0 – 300K',      'ar' => '0 – 300 ألف دولار',             'terminate' => true],
                        '300k_500k'  => ['en' => 'USD 300K – 500K',   'ar' => '300 – 500 ألف دولار'],
                        '500k_800k'  => ['en' => 'USD 500K – 800K',   'ar' => '500 – 800 ألف دولار'],
                        '800k_1_5m'  => ['en' => 'USD 800K – 1.5M',   'ar' => '800 ألف – 1.5 مليون دولار'],
                        '1_5m_plus'  => ['en' => 'USD 1.5M+',         'ar' => 'أكثر من 1.5 مليون دولار'],
                    ],
                ],
                [
                    'id' => 'visited_nc', 'code' => 'S7', 'type' => 'single', 'required' => true, 'layout' => 'pair',
                    'eyebrow' => ['en' => 'One last question before we dive in', 'ar' => 'سؤال أخير قبل أن نبدأ'],
                    'label'   => ['en' => 'Have you ever visited or spent time at the North Coast?', 'ar' => 'هل سبق لك زيارة الساحل الشمالي أو قضاء وقت فيه؟'],
                    'options' => [
                        'yes' => ['en' => 'Yes', 'ar' => 'نعم'],
                        'no'  => ['en' => 'No',  'ar' => 'لا', 'terminate' => true],
                    ],
                ],
            ],
        ],

        // ── 2. YOUR VIEW OF EGYPT ───────────────────────────────────────────
        [
            'key'   => 'egypt',
            'image' => 'porto-marina-overlook.jpg',
            'place' => ['en' => 'Porto Marina, El Alamein', 'ar' => 'بورتو مارينا، العلمين'],
            'title' => ['en' => 'Your view of Egypt', 'ar' => 'نظرتك إلى مصر'],
            'questions' => [
                [
                    'id' => 'eg1', 'code' => 'EG1', 'type' => 'scale', 'required' => true,
                    'label' => ['en' => 'Let’s put Egypt in perspective.', 'ar' => 'لنتعرف على نظرتك إلى مصر.'],
                    'hint'  => ['en' => 'On a scale of 1 to 5, how would you rate Egypt as a:', 'ar' => 'على مقياس من 1 إلى 5، كيف تقيّم مصر كـ:'],
                    'scale' => [
                        1 => ['en' => 'Very poor', 'ar' => 'ضعيف جدًا'],
                        2 => ['en' => 'Poor',      'ar' => 'ضعيف'],
                        3 => ['en' => 'Neutral',   'ar' => 'محايد'],
                        4 => ['en' => 'Good',      'ar' => 'جيد'],
                        5 => ['en' => 'Very good', 'ar' => 'ممتاز'],
                    ],
                    'items' => [
                        'tourist'     => ['en' => 'Tourist destination',        'ar' => 'وجهة سياحية'],
                        'holiday'     => ['en' => 'Holiday destination',        'ar' => 'وجهة لقضاء العطلات'],
                        'live'        => ['en' => 'Place to live in / Retire',  'ar' => 'مكان للعيش / التقاعد'],
                        'second_home' => ['en' => 'Place to own a second home', 'ar' => 'مكان لامتلاك منزل ثانٍ'],
                        'investment'  => ['en' => 'Investment destination',     'ar' => 'وجهة استثمارية'],
                    ],
                ],
                [
                    'id' => 'eg2', 'code' => 'EG2', 'type' => 'multi', 'required' => true,
                    'label' => ['en' => 'Now imagine you’re researching a property investment in Egypt.', 'ar' => 'تخيل أنك تبحث عن استثمار عقاري في مصر.'],
                    'hint'  => ['en' => 'Which sources of information would you trust? Select all that apply.', 'ar' => 'ما هي مصادر المعلومات التي تثق بها؟ اختر جميع الإجابات التي تنطبق عليك.'],
                    'options' => [
                        'social'     => ['en' => 'Social media',                    'ar' => 'وسائل التواصل الاجتماعي'],
                        'portals'    => ['en' => 'Real estate websites / portals',  'ar' => 'المواقع والبوابات العقارية'],
                        'expos'      => ['en' => 'Real estate expos / events',      'ar' => 'المعارض والفعاليات العقارية'],
                        'broker'     => ['en' => 'Broker / real estate agent',      'ar' => 'الوسيط / الوكيل العقاري'],
                        'friends'    => ['en' => 'Friends / family',                'ar' => 'الأصدقاء / العائلة'],
                        'developer'  => ['en' => 'Developer website',               'ar' => 'الموقع الإلكتروني للمطور'],
                        'press'      => ['en' => 'Newspapers / magazines',          'ar' => 'الصحف / المجلات'],
                        'tv_radio'   => ['en' => 'TV / radio ads',                  'ar' => 'إعلانات التلفزيون / الراديو'],
                        'billboards' => ['en' => 'Billboards',                      'ar' => 'اللوحات الإعلانية'],
                        'other'      => ncq_other_option(),
                    ],
                ],
                [
                    'id' => 'eg2a', 'code' => 'EG2a', 'type' => 'rank', 'required' => true, 'source' => 'eg2',
                    'label' => ['en' => 'Now, rank your top sources.', 'ar' => 'الآن، رتّب أهم مصادرك.'],
                    'hint'  => ['en' => 'Which three do you trust most? Rank them.', 'ar' => 'ما هي أهم ثلاثة مصادر تثق بها؟ رتّبها.'],
                    'ranks' => [
                        ['en' => '#1 most trusted', 'ar' => 'المصدر الأول الأكثر ثقة'],
                        ['en' => '#2 most trusted', 'ar' => 'المصدر الثاني الأكثر ثقة'],
                        ['en' => '#3 most trusted', 'ar' => 'المصدر الثالث الأكثر ثقة'],
                    ],
                ],
            ],
        ],

        // ── WHAT MATTERS IN THE DECISION ────────────────────────────────────
        [
            'key'   => 'decision',
            'image' => 'porto-marina-harbour.jpg',
            'place' => ['en' => 'Porto Marina, El Alamein', 'ar' => 'بورتو مارينا، العلمين'],
            'title' => ['en' => 'What matters when making the decision?', 'ar' => 'ما الذي يهم عند اتخاذ القرار؟'],
            'questions' => [
                [
                    'id' => 'eg3', 'code' => 'EG3', 'type' => 'multi', 'required' => true,
                    'label' => ['en' => 'When considering a property investment in Egypt, what factors would you take into account?', 'ar' => 'عند التفكير في استثمار عقاري في مصر، ما هي العوامل التي تأخذها بعين الاعتبار؟'],
                    'hint'  => ['en' => 'Select all that apply.', 'ar' => 'اختر جميع الإجابات التي تنطبق عليك.'],
                    'options' => ncq_factor_options(),
                ],
                [
                    'id' => 'eg3a', 'code' => 'EG3a', 'type' => 'rank', 'required' => true, 'source' => 'eg3',
                    'label' => ['en' => 'Now, make the call.', 'ar' => 'الآن، اختر الأهم بالنسبة لك.'],
                    'hint'  => ['en' => 'Which three matter most to you? Rank them.', 'ar' => 'ما هي أهم ثلاثة عوامل بالنسبة لك؟ رتّبها.'],
                    'ranks' => [
                        ['en' => '#1 choice driver', 'ar' => 'العامل الأول في قرارك'],
                        ['en' => '#2 choice driver', 'ar' => 'العامل الثاني في قرارك'],
                        ['en' => '#3 choice driver', 'ar' => 'العامل الثالث في قرارك'],
                    ],
                ],
            ],
        ],

        // ── 3. LET'S TALK NORTH COAST ───────────────────────────────────────
        [
            'key'   => 'coast',
            'image' => 'sidi-waves.jpg',
            'place' => ['en' => 'Sidi Abdel Rahman', 'ar' => 'سيدي عبد الرحمن'],
            'title' => ['en' => 'Let’s talk North Coast', 'ar' => 'لنتحدث عن الساحل الشمالي'],
            'questions' => [
                [
                    'id' => 'nc1', 'code' => 'NC1', 'type' => 'multi', 'required' => true,
                    'condition' => ['q' => 'visited_nc', 'v' => 'yes'],
                    'label' => ['en' => 'Picture your time at the North Coast.', 'ar' => 'تخيل وقتك في الساحل الشمالي.'],
                    'hint'  => ['en' => 'What activities do you usually do when you’re there? Select all that apply.', 'ar' => 'ما هي الأنشطة التي تقوم بها عادةً عند تواجدك هناك؟ اختر جميع الإجابات التي تنطبق عليك.'],
                    'options' => [
                        'beach'       => ['en' => 'Beach',                   'ar' => 'الشاطئ / البحر'],
                        'dining'      => ['en' => 'Dining out',              'ar' => 'تناول الطعام خارج المنزل'],
                        'nightlife'   => ['en' => 'Nightlife',               'ar' => 'السهر / الحياة الليلية'],
                        'watersports' => ['en' => 'Watersports',             'ar' => 'الرياضات المائية'],
                        'shopping'    => ['en' => 'Shopping',                'ar' => 'التسوق'],
                        'family'      => ['en' => 'Family / kids activities', 'ar' => 'أنشطة عائلية / للأطفال'],
                        'socializing' => ['en' => 'Socializing',             'ar' => 'التجمع مع الأصدقاء'],
                        'sports'      => ['en' => 'Sports / Padel',          'ar' => 'رياضة / بادل'],
                        'other'       => ncq_other_option(),
                    ],
                ],
                [
                    'id' => 'nc2', 'code' => 'NC2', 'type' => 'text', 'required' => true,
                    'label' => ['en' => 'Which areas come to mind when you think about the North Coast?', 'ar' => 'ما هي المناطق التي تخطر ببالك عندما تفكر في الساحل الشمالي؟'],
                ],
                [
                    'id' => 'nc2b', 'code' => 'NC2b', 'type' => 'text', 'required' => true,
                    'label' => ['en' => 'Any other areas come to mind when you think about the North Coast?', 'ar' => ' هل هناك مناطق أخرى تخطر ببالك عندما تفكر في الساحل الشمالي؟'],
                ],
                [
                    'id' => 'nc2a', 'code' => 'NC2a', 'type' => 'multi', 'required' => true,
                    'label' => ['en' => 'And how many of these areas do you recognize?', 'ar' => 'والآن، أي من هذه المناطق تعرفها؟'],
                    'hint'  => ['en' => 'Select all that you know or have heard of.', 'ar' => 'اختر جميع المناطق التي تعرفها أو سبق أن سمعت عنها.'],
                    'options' => [
                        'sidi_abdel_rahman' => ['en' => 'Sidi Abdel Rahman',     'ar' => 'سيدي عبد الرحمن'],
                        'sidi_heneish'      => ['en' => 'Sidi Heneish',          'ar' => 'سيدي حنيش'],
                        'ras_el_hekma'      => ['en' => 'Ras El Hekma',          'ar' => 'رأس الحكمة'],
                        'alam_el_roum'      => ['en' => 'Alam El Roum',          'ar' => 'علم الروم'],
                        'alamein'           => ['en' => 'Alamein / New Alamein', 'ar' => 'العلمين / العلمين الجديدة'],
                        'dabaa'             => ['en' => 'Dabaa',                 'ar' => 'الضبعة'],
                        'fouka'             => ['en' => 'Fouka',                 'ar' => 'فوكة'],
                        'other'             => ncq_other_option(),
                    ],
                ],
                [
                    'id' => 'nc4', 'code' => 'NC4', 'type' => 'multi', 'required' => true, 'source' => 'nc2a', 'none' => ['en' => 'None', 'ar' => 'لا شيء'],
                    'condition' => ['q' => 'visited_nc', 'v' => 'yes'],
                    'label' => ['en' => 'Now, which of these have you actually visited?', 'ar' => 'أي من هذه المناطق قمت بزيارتها بنفسك؟'],
                    'hint'  => ['en' => 'Select all that apply.', 'ar' => 'اختر جميع الإجابات التي تنطبق عليك.'],
                    'empty' => ['en' => 'Select the areas you recognize above and they’ll appear here.', 'ar' => 'اختر المناطق التي تعرفها في السؤال السابق وستظهر هنا.'],
                ],
                [
                    'id' => 'nc4a', 'code' => 'NC4a', 'type' => 'text', 'required' => true,
                    'condition' => ['q' => 'visited_nc', 'v' => 'yes'],
                    'label' => ['en' => 'Let’s talk about the places you’ve experienced.', 'ar' => 'لنتحدث قليلًا عن المناطق التي زرتها.'],
                    'hint'  => ['en' => 'Thinking about the area or areas you visited, what do you like most about them?', 'ar' => 'بالنظر إلى المنطقة أو المناطق التي زرتها، ما أكثر ما يعجبك فيها؟'],
                ],
                [
                    'id' => 'nc5', 'code' => 'NC5', 'type' => 'multi', 'required' => true,
                    'label' => ['en' => 'Now, imagine making the North Coast an even stronger international destination.', 'ar' => 'والآن، تخيل الساحل الشمالي كوجهة أكثر قوة على المستوى الدولي.'],
                    'hint'  => ['en' => 'In your opinion, what is currently missing?', 'ar' => 'في رأيك، ما الذي ينقص الساحل الشمالي حاليًا؟'],
                    'options' => [
                        'flights'        => ['en' => 'Direct flights / better airport access',      'ar' => 'رحلات طيران مباشرة / وصول أفضل للمطارات'],
                        'hotels'         => ['en' => 'International hotel brands',                  'ar' => 'علامات فندقية عالمية'],
                        'schools'        => ['en' => 'International schools',                       'ar' => 'مدارس دولية'],
                        'healthcare'     => ['en' => 'Healthcare facilities / hospitals',           'ar' => 'مرافق الرعاية الصحية / المستشفيات'],
                        'entertainment'  => ['en' => 'Entertainment & family attractions',          'ar' => 'وسائل الترفيه والأنشطة العائلية'],
                        'infrastructure' => ['en' => 'Year-round infrastructure (not just summer)', 'ar' => 'بنية تحتية تعمل طوال العام (وليس الصيف فقط)'],
                        'brands'         => ['en' => 'International F&B and retail brands',         'ar' => 'علامات عالمية للمطاعم والتجزئة'],
                        'roads'          => ['en' => 'Better road infrastructure / connectivity',   'ar' => 'طرق أفضل / سهولة الربط والتنقل'],
                        'marina'         => ['en' => 'Marina / yacht facilities',                   'ar' => 'مراسي ومرافق لليخوت'],
                        'other'          => ncq_other_option(),
                    ],
                ],
            ],
        ],

        // ── 4. NORTH COAST REAL ESTATE ──────────────────────────────────────
        [
            'key'   => 'realestate',
            'image' => 'new-alamein.jpg',
            'place' => ['en' => 'New Alamein', 'ar' => 'العلمين الجديدة'],
            'title' => ['en' => 'Now, think North Coast real estate', 'ar' => 'الآن، لنتحدث عن مشروعات الساحل الشمالي'],
            'questions' => [
                [
                    'id' => 'p1', 'code' => 'P1', 'type' => 'text', 'required' => true,
                    'eyebrow' => ['en' => 'Without looking at a list', 'ar' => 'دون النظر إلى قائمة'],
                    'label'   => ['en' => 'Which North Coast projects come to mind?', 'ar' => 'ما هي مشروعات الساحل الشمالي التي تخطر ببالك؟'],
                ],
                [
                    'id' => 'p1a', 'code' => 'P1a', 'type' => 'text', 'required' => true,
                    'label' => ['en' => 'Any other projects come to mind?', 'ar' => 'هل تخطر ببالك أي مشروعات أخرى؟'],
                ],
                [
                    'id' => 'p1b', 'code' => 'P1b', 'type' => 'multi', 'required' => true,
                    'label' => ['en' => 'Now let’s see which of these you recognize.', 'ar' => 'والآن، لنرَ أيًا من هذه المشروعات تعرفه.'],
                    'hint'  => ['en' => 'Which of the following North Coast projects have you heard of? Select all that apply.', 'ar' => 'أي من مشروعات الساحل الشمالي التالية سمعت عنها؟ اختر جميع الإجابات التي تنطبق عليك.'],
                    'options' => [
                        'southmed'      => ['en' => 'Southmed',      'ar' => 'ساوث ميد'],
                        'marassi'       => ['en' => 'Marassi',       'ar' => 'مراسي'],
                        'almaza_bay'    => ['en' => 'Almaza Bay',    'ar' => 'ألماظة باي'],
                        'fouka_bay'     => ['en' => 'Fouka Bay',     'ar' => 'فوكا باي'],
                        'hacienda'      => ['en' => 'Hacienda',      'ar' => 'هاسيندا'],
                        'modon'         => ['en' => 'Modon',         'ar' => 'مدن'],
                        'amwaj'         => ['en' => 'Amwaj',         'ar' => 'أمواج'],
                        'seashell'      => ['en' => 'Seashell',      'ar' => 'سي شل'],
                        'mountain_view' => ['en' => 'Mountain View', 'ar' => 'ماونتن فيو'],
                        'other'         => ncq_other_option(),
                    ],
                ],
            ],
        ],

        // ── 5. ONE LAST THING — MODON (only if Modon was ticked in P1b) ─────
        [
            'key'       => 'modon',
            'image'     => 'alamein-towers.jpg',
            'place'     => ['en' => 'New Alamein', 'ar' => 'العلمين الجديدة'],
            'title'     => ['en' => 'One last thing — Modon', 'ar' => 'أمر أخير — مدن'],
            'condition' => ['q' => 'p1b', 'v' => 'modon'],
            'questions' => [
                [
                    'id' => 'm1', 'code' => 'M1', 'type' => 'text', 'required' => true,
                    'label' => ['en' => 'You mentioned that you know Modon. What do you know about it?', 'ar' => 'ذكرت أنك تعرف مدن. ماذا تعرف عنها؟'],
                    'hint'  => ['en' => 'Tell us anything that comes to mind.', 'ar' => 'أخبرنا بأي شيء يخطر ببالك.'],
                ],
                [
                    'id' => 'm2', 'code' => 'M2', 'type' => 'multi', 'required' => true,
                    'label' => ['en' => 'And how did you first hear about Modon?', 'ar' => 'وكيف سمعت عن مدن لأول مرة؟'],
                    'hint'  => ['en' => 'Select all that apply.', 'ar' => 'اختر جميع الإجابات التي تنطبق عليك.'],
                    'options' => [
                        'social'      => ['en' => 'Social media',       'ar' => 'وسائل التواصل الاجتماعي'],
                        'news'        => ['en' => 'News',               'ar' => 'الأخبار'],
                        'friends'     => ['en' => 'Friends / family',   'ar' => 'الأصدقاء / العائلة'],
                        'advertising' => ['en' => 'Advertising',        'ar' => 'الإعلانات'],
                        'outreach'    => ['en' => 'Developer outreach', 'ar' => 'تواصل المطور المباشر'],
                        'other'       => ncq_other_option(),
                    ],
                ],
            ],
        ],

        // ── STAY IN TOUCH ────────────────────────────────────────────────────
        [
            'key'         => 'contact',
            'all_at_once' => true,
            'image' => 'porto-edited-7.webp',
            'place' => ['en' => 'Porto Marina, El Alamein', 'ar' => 'بورتو مارينا، العلمين'],
            'title' => ['en' => 'Stay in touch', 'ar' => 'لنبقَ على تواصل'],
            'intro' => ['en' => 'Finally, leave your details so we can keep you updated on new North Coast opportunities.', 'ar' => 'أخيرًا، اترك بياناتك لنبقيك على اطلاع بأحدث فرص الساحل الشمالي.'],
            'questions' => [
                ['id' => 'name',  'type' => 'input', 'input' => 'text',  'autocomplete' => 'name',  'required' => true,
                 'label' => ['en' => 'Full name', 'ar' => 'الاسم بالكامل']],
                ['id' => 'email', 'type' => 'input', 'input' => 'email', 'autocomplete' => 'email', 'required' => true,
                 'label' => ['en' => 'Email address', 'ar' => 'البريد الإلكتروني']],
                ['id' => 'phone', 'type' => 'input', 'input' => 'tel',   'autocomplete' => 'tel',   'required' => true,
                 'label' => ['en' => 'Phone', 'ar' => 'الهاتف']],
            ],
        ],
    ];

    return $schema;
}

/** Flat map of question id => question definition. */
function ncq_questions() {
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    foreach (ncq_schema() as $step) {
        foreach ($step['questions'] as $q) {
            $q['step'] = $step['key'];
            $map[$q['id']] = $q;
        }
    }
    return $map;
}

/** Options for a question, resolving 'source' (subset questions) to the source's options. */
function ncq_question_options($q) {
    if (!empty($q['options'])) return $q['options'];
    if (!empty($q['source'])) {
        $all = ncq_questions();
        return $all[$q['source']]['options'] ?? [];
    }
    return [];
}

/** UI strings for the front end. */
function ncq_strings($lang) {
    $s = [
        'eyebrow'     => ['en' => 'Egypt · North Coast', 'ar' => 'مصر · الساحل الشمالي'],
        'hero_title'  => ['en' => 'Tell us about your <em>North Coast</em>.', 'ar' => 'حدّثنا عن <em>ساحلك الشمالي</em>.'],
        'hero_lead'   => ['en' => 'A short, confidential questionnaire on summer homes, Egypt and the Mediterranean shoreline from Sidi Abdel Rahman to New Alamein. Your answers help shape what comes next.',
                          'ar' => 'استبيان قصير وسري حول المنازل الصيفية ومصر وساحل البحر المتوسط من سيدي عبد الرحمن إلى العلمين الجديدة. إجاباتك تساعدنا في رسم ملامح المستقبل.'],
        'meta_time'   => ['en' => 'About 5 minutes', 'ar' => 'حوالي 5 دقائق'],
        'meta_steps'  => ['en' => 'Short chapters',  'ar' => 'أقسام قصيرة'],
        'meta_conf'   => ['en' => 'Confidential',    'ar' => 'سري تمامًا'],
        'begin'       => ['en' => 'Begin the questionnaire', 'ar' => 'ابدأ الاستبيان'],
        'chapter'     => ['en' => 'Chapter', 'ar' => 'القسم'],
        'of'          => ['en' => 'of', 'ar' => 'من'],
        'back'        => ['en' => 'Back', 'ar' => 'السابق'],
        'next'        => ['en' => 'Continue', 'ar' => 'التالي'],
        'submit'      => ['en' => 'Submit answers', 'ar' => 'إرسال الإجابات'],
        'sending'     => ['en' => 'Sending…', 'ar' => 'جارٍ الإرسال…'],
        'optional'    => ['en' => 'Optional', 'ar' => 'اختياري'],
        'open'        => ['en' => 'Open answer — share as much or as little as you like', 'ar' => 'إجابة مفتوحة — شاركنا بما تشاء'],
        'specify'     => ['en' => 'Please specify', 'ar' => 'يرجى التحديد'],
        'rank_help'   => ['en' => 'Tap your choices in order of importance. Tap again to remove.', 'ar' => 'اضغط على اختياراتك بالترتيب حسب الأهمية. اضغط مرة أخرى للإزالة.'],
        'rank_empty'  => ['en' => 'Select at least one option in the previous question first.', 'ar' => 'اختر خيارًا واحدًا على الأقل في السؤال السابق أولًا.'],
        'err_single'  => ['en' => 'Please choose an option.', 'ar' => 'يرجى اختيار إجابة.'],
        'err_multi'   => ['en' => 'Please select at least one option.', 'ar' => 'يرجى اختيار إجابة واحدة على الأقل.'],
        'err_scale'   => ['en' => 'Please rate every item.', 'ar' => 'يرجى تقييم جميع العناصر.'],
        'err_rank'    => ['en' => 'Please rank your top choices.', 'ar' => 'يرجى ترتيب أهم اختياراتك.'],
        'err_specify' => ['en' => 'Please fill in the highlighted field.', 'ar' => 'يرجى تعبئة الحقل المحدد.'],
        'err_name'    => ['en' => 'Please enter your full name.', 'ar' => 'يرجى إدخال الاسم بالكامل.'],
        'err_email'   => ['en' => 'Please enter a valid email address.', 'ar' => 'يرجى إدخال بريد إلكتروني صحيح.'],
        'err_phone'   => ['en' => 'Please enter a valid phone number.', 'ar' => 'الرجاء إدخال رقم هاتف صحيح.'],
        'err_generic' => ['en' => 'Something went wrong. Please try again.', 'ar' => 'حدث خطأ ما. يرجى المحاولة مرة أخرى.'],
        'err_network' => ['en' => 'Network error. Please check your connection and try again.', 'ar' => 'خطأ في الاتصال. يرجى التحقق من الاتصال والمحاولة مرة أخرى.'],
        'thanks_title'=> ['en' => 'Congratulations!', 'ar' => 'تهانينا!'],
        'thanks_text' => ['en' => 'You will receive a voucher upon submission.',
                          'ar' => 'ستتلقى قسيمة عند تقديم إجاباتك.'],
        'thanks_home' => ['en' => 'Back to homepage', 'ar' => 'العودة إلى الصفحة الرئيسية'],
        'err_text'    => ['en' => 'Please share your answer.', 'ar' => 'يرجى مشاركة إجابتك.'],
        'term_title'  => ['en' => 'Thank you for your time.', 'ar' => 'شكرًا على وقتك.'],
        'term_text'   => ['en' => 'Based on your answers, this questionnaire isn\'t the right fit for your profile at this stage — but we genuinely appreciate you taking part.',
                          'ar' => 'بناءً على إجاباتك، لا يتناسب هذا الاستبيان مع ملفك الشخصي في هذه المرحلة — لكننا نقدّر مشاركتك معنا حقًا.'],
        'credits'     => ['en' => 'Photography', 'ar' => 'الصور'],
        'rights'      => ['en' => 'All rights reserved.', 'ar' => 'جميع الحقوق محفوظة.'],
        'lang_label'  => ['en' => 'Language', 'ar' => 'اللغة'],
    ];
    $out = [];
    foreach ($s as $k => $v) $out[$k] = $v[$lang] ?? $v['en'];
    return $out;
}

/** Photo credits (Wikimedia Commons, CC BY-SA) — required attribution. */
function ncq_photo_credits() {
    return [
        ['file' => 'hero-sidi-abdel-rahman.jpg', 'title' => 'Al Alamein Hotel, Sidi Abdel Rahman',           'author' => 'Michelle Santos',      'license' => 'CC BY-SA 4.0', 'url' => 'https://commons.wikimedia.org/wiki/File:Al_Alamein_Hotel.jpg'],
        ['file' => 'sidi-beach.jpg',             'title' => 'Sidi Abd El Rahman beach',                      'author' => 'Mmelouk',              'license' => 'CC BY-SA 4.0', 'url' => 'https://commons.wikimedia.org/wiki/File:Sidi_Abd_El_Rahman_beach.jpg'],
        ['file' => 'sidi-waves.jpg',             'title' => 'Sidi Abd El Rahman waves',                      'author' => 'Mmelouk',              'license' => 'CC BY-SA 4.0', 'url' => 'https://commons.wikimedia.org/wiki/File:Sidi_Abd_El_Rahman_waves.jpg'],
        ['file' => 'alamein-towers-sunset.jpg',  'title' => 'Al Alamein towers sunset',                      'author' => 'Ranistreem',           'license' => 'CC BY-SA 4.0', 'url' => 'https://commons.wikimedia.org/wiki/File:Al_alamein_towers_sunset,_Egypt_north_coast.jpg'],
        ['file' => 'alamein-towers.jpg',         'title' => 'Al Alamein towers',                             'author' => 'Ranistreem',           'license' => 'CC BY-SA 4.0', 'url' => 'https://commons.wikimedia.org/wiki/File:Al_alamein_towers,_Egypt_north_coast.jpg'],
        ['file' => 'alamein-beach-sunset.jpg',   'title' => 'Al Alamein beach sunset',                       'author' => 'Ranistreem',           'license' => 'CC BY-SA 4.0', 'url' => 'https://commons.wikimedia.org/wiki/File:Al_alamein_beach_sunset,_Egypt_north_coast.jpg'],
        ['file' => 'new-alamein.jpg',            'title' => 'New Alamein',                                   'author' => 'Abdelrhman 1990',      'license' => 'CC BY-SA 4.0', 'url' => 'https://commons.wikimedia.org/wiki/File:New_Alamein_1_(cropped).jpg'],
        ['file' => 'porto-marina-overlook.jpg',  'title' => 'Overlooking Porto Marina, El Alamein',          'author' => 'Brian Paul',           'license' => 'CC BY-SA 4.0', 'url' => 'https://commons.wikimedia.org/wiki/File:Overlooking_Porto_Marina,_El_Alamein_Marina_Lake_and_The_Mediterranean.webp'],
        ['file' => 'porto-marina-harbour.jpg',   'title' => 'Porto Marina (El Alamein)',                     'author' => 'TheEgyptian',          'license' => 'CC BY-SA 3.0', 'url' => 'https://commons.wikimedia.org/wiki/File:Porto_Marina_(El_Alamein).jpg'],
        ['file' => 'porto-marina-promenade.jpg', 'title' => 'Porto Marina',                                  'author' => 'Amr fayez / TheEgyptian', 'license' => 'CC BY-SA 3.0', 'url' => 'https://commons.wikimedia.org/wiki/File:Porto_Marina_4.JPG'],
    ];
}
