<?php
/**
 * Plugin Name: North Coast Questionnaire
 * Description: Adds two selectable page templates (English / Arabic) with a premium, multi-step North Coast questionnaire. Submissions are saved to their own table and listed under "Questionnaire" in the admin, with CSV export.
 * Version:     1.1
 * Author:      Raouf
 */

if (!defined('ABSPATH')) exit;

define('NCQ_VERSION', '1.1');
define('NCQ_DIR', plugin_dir_path(__FILE__));
define('NCQ_URL', plugin_dir_url(__FILE__));

// Registered template slug => [ label, template file (relative to templates/), language ]
define('NCQ_TEMPLATES', [
    'ncq-questionnaire-en.php' => ['label' => 'North Coast Questionnaire (English)', 'file' => 'questionnaire-en.php', 'lang' => 'en'],
    'ncq-questionnaire-ar.php' => ['label' => 'North Coast Questionnaire (Arabic)',  'file' => 'questionnaire-ar.php', 'lang' => 'ar'],
]);

require_once NCQ_DIR . 'includes/schema.php';
require_once NCQ_DIR . 'includes/render.php';
require_once NCQ_DIR . 'includes/admin.php';

/** Language of the questionnaire template on the current request, or null. */
function ncq_current_lang() {
    if (!is_singular()) return null;
    $slug = get_page_template_slug(get_queried_object_id());
    return NCQ_TEMPLATES[$slug]['lang'] ?? null;
}

// ─── SELECTOR METABOX ─────────────────────────────────────────────────────
// Same approach as the Blog Post Template plugin: Elementor rebuilds the
// native Template dropdown and strips templates added via filters, so this
// writes straight to '_wp_page_template' from its own metabox instead.
add_action('add_meta_boxes_page', function () {
    add_meta_box('ncq_template_box', 'Questionnaire Layout', 'ncq_render_template_meta_box', 'page', 'side', 'default');
});

function ncq_render_template_meta_box($post) {
    wp_nonce_field('ncq_save_template', 'ncq_template_nonce');
    $current = get_page_template_slug($post);
    ?>
    <label for="ncq_template" class="screen-reader-text">Questionnaire Layout</label>
    <select name="ncq_template" id="ncq_template" style="width:100%;">
        <option value=""<?php selected(!isset(NCQ_TEMPLATES[$current])); ?>>— None (theme / other template) —</option>
        <?php foreach (NCQ_TEMPLATES as $slug => $tpl) : ?>
            <option value="<?php echo esc_attr($slug); ?>"<?php selected($current, $slug); ?>>
                <?php echo esc_html($tpl['label']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description" style="margin-top:8px;">The page content is ignored — the questionnaire fills the whole page.</p>
    <?php
}

add_action('save_post_page', function ($post_id) {
    if (!isset($_POST['ncq_template_nonce']) || !wp_verify_nonce($_POST['ncq_template_nonce'], 'ncq_save_template')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $value   = isset($_POST['ncq_template']) ? sanitize_text_field(wp_unslash($_POST['ncq_template'])) : '';
    $current = get_page_template_slug($post_id);

    if ($value !== '' && !isset(NCQ_TEMPLATES[$value])) return; // unknown value, ignore

    if ($value !== '') {
        update_post_meta($post_id, '_wp_page_template', $value);
    } elseif (isset(NCQ_TEMPLATES[$current])) {
        // Only clear the meta if it currently points at one of OUR templates —
        // never wipe a theme / other plugin template the page is using.
        delete_post_meta($post_id, '_wp_page_template');
    }
});

// ─── TEMPLATE ROUTING ─────────────────────────────────────────────────────
add_filter('template_include', function ($template) {
    if (is_singular()) {
        $slug = get_page_template_slug(get_queried_object_id());
        if (isset(NCQ_TEMPLATES[$slug])) {
            $custom = NCQ_DIR . 'templates/' . NCQ_TEMPLATES[$slug]['file'];
            if (file_exists($custom)) return $custom;
        }
    }
    return $template;
});

add_filter('body_class', function ($classes) {
    $lang = ncq_current_lang();
    if ($lang) {
        $classes[] = 'ncq-template';
        $classes[] = 'ncq-template-' . $lang;
    }
    return $classes;
});

// ─── ASSETS (only on questionnaire pages) ─────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    $lang = ncq_current_lang();
    if (!$lang) return;

    $fonts = $lang === 'ar'
        ? 'https://fonts.googleapis.com/css2?family=El+Messiri:wght@500;600&family=Tajawal:wght@300;400;500;700&display=swap'
        : 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Jost:wght@300;400;500&display=swap';

    wp_enqueue_style('ncq-fonts', $fonts, [], null);
    // Version = file modified time, so browsers / caches pick up every update.
    $ver = function ($f) { return NCQ_VERSION . '.' . (int) @filemtime(NCQ_DIR . 'assets/' . $f); };
    // intl-tel-input — same handles / version as the Popup Form plugin, so it only loads once if both are active.
    wp_enqueue_style('iti-style', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/css/intlTelInput.css', [], '23');
    wp_enqueue_script('iti-script', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/js/intlTelInput.min.js', [], '23', true);

    wp_enqueue_style('ncq-style', NCQ_URL . 'assets/questionnaire.css', ['ncq-fonts', 'iti-style'], $ver('questionnaire.css'));
    wp_enqueue_script('ncq-script', NCQ_URL . 'assets/questionnaire.js', ['iti-script'], $ver('questionnaire.js'), true);

    $s = ncq_strings($lang);
    wp_localize_script('ncq-script', 'NCQ', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ncq_nonce'),
        'lang'     => $lang,
        // PDF downloaded automatically after a successful submission (filterable).
        'pdf_url'  => apply_filters('ncq_pdf_url', NCQ_URL . rawurlencode('TBC _ North Coast Pre Season Opening - 2026.pdf')),
        'pdf_name' => 'North Coast Pre Season Opening - 2026.pdf',
        'i18n'     => [
            'chapter'     => $s['chapter'],
            'of'          => $s['of'],
            'sending'     => $s['sending'],
            'submit'      => $s['submit'],
            'rank_empty'  => $s['rank_empty'],
            'err_single'  => $s['err_single'],
            'err_multi'   => $s['err_multi'],
            'err_scale'   => $s['err_scale'],
            'err_rank'    => $s['err_rank'],
            'err_specify' => $s['err_specify'],
            'err_name'    => $s['err_name'],
            'err_email'   => $s['err_email'],
            'err_phone'   => $s['err_phone'],
            'err_text'    => $s['err_text'],
            'err_generic' => $s['err_generic'],
            'err_network' => $s['err_network'],
        ],
    ]);
});

// ─── DB TABLE ─────────────────────────────────────────────────────────────
// Same pattern as the popup form plugin: dbDelta on activation, plus an
// admin_init self-heal in case the plugin was dropped in without activation.
// Headline answers get their own columns for quick scanning; the full set
// of answers is kept as JSON in `answers`.
function ncq_create_db_table() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$wpdb->prefix}ncq_responses (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        lang varchar(5) NOT NULL DEFAULT '',
        name varchar(200) NOT NULL DEFAULT '',
        email varchar(200) NOT NULL DEFAULT '',
        phone varchar(50) NOT NULL DEFAULT '',
        gender varchar(20) NOT NULL DEFAULT '',
        nationality varchar(150) NOT NULL DEFAULT '',
        age_group varchar(20) NOT NULL DEFAULT '',
        summer_home varchar(10) NOT NULL DEFAULT '',
        budget varchar(20) NOT NULL DEFAULT '',
        visited_nc varchar(10) NOT NULL DEFAULT '',
        answers longtext NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY lang (lang),
        KEY created_at (created_at)
    ) $charset;");
}
/** Grant the custom `ncq_manage` capability to administrator and editor roles. */
function ncq_grant_caps() {
    foreach (['administrator', 'editor'] as $role_name) {
        $role = get_role($role_name);
        if ($role) $role->add_cap('ncq_manage');
    }
}

register_activation_hook(__FILE__, function () {
    ncq_create_db_table();
    ncq_grant_caps();
});

add_action('admin_init', function () {
    global $wpdb;
    $table = $wpdb->prefix . 'ncq_responses';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
        ncq_create_db_table();
    }
    // Safety-net: grant caps on already-active installs too.
    ncq_grant_caps();
});

// ─── SANITISE ANSWERS AGAINST THE SCHEMA ──────────────────────────────────
// Anything not described by the schema is dropped; option values must be
// known keys; ranks / subset questions must come from what was actually
// picked in their source question.
function ncq_sanitize_answers($raw) {
    $raw   = is_array($raw) ? $raw : [];
    $clean = [];

    $pick_text = function ($v, $max = 2000) {
        return is_string($v) ? mb_substr(sanitize_textarea_field($v), 0, $max) : '';
    };

    foreach (ncq_schema() as $step) {
        foreach ($step['questions'] as $q) {
            $id   = $q['id'];
            $val  = $raw[$id] ?? null;
            $opts = ncq_question_options($q);

            switch ($q['type']) {
                case 'single':
                    $clean[$id] = (is_string($val) && isset($opts[$val])) ? $val : '';
                    break;

                case 'multi':
                    $allowed = array_keys($opts);
                    if (!empty($q['source'])) {
                        $allowed = array_intersect($allowed, $clean[$q['source']] ?? []);
                    }
                    $clean[$id] = is_array($val) ? array_values(array_unique(array_intersect(array_map('strval', $val), $allowed))) : [];
                    break;

                case 'scale':
                    $clean[$id] = [];
                    foreach ($q['items'] as $item => $_) {
                        $n = isset($val[$item]) ? (int) $val[$item] : 0;
                        if ($n >= 1 && $n <= 5) $clean[$id][$item] = $n;
                    }
                    break;

                case 'rank':
                    // Rank from a fixed list, or from what was picked in 'source'.
                    $pool = !empty($q['source']) ? ($clean[$q['source']] ?? []) : array_keys($opts);
                    $clean[$id] = is_array($val)
                        ? array_slice(array_values(array_unique(array_intersect(array_map('strval', $val), $pool))), 0, 3)
                        : [];
                    break;

                case 'text':
                    $clean[$id] = $pick_text($val);
                    break;

                case 'input':
                    if (($q['input'] ?? '') === 'email') {
                        $email      = is_string($val) ? sanitize_email($val) : '';
                        $clean[$id] = is_email($email) ? $email : '';
                    } else {
                        $clean[$id] = is_string($val) ? mb_substr(sanitize_text_field($val), 0, 200) : '';
                    }
                    break;
            }

            // "Other, specify" / "Yes, specify" text boxes.
            foreach ($opts as $key => $opt) {
                if (empty($opt['specify'])) continue;
                $picked = is_array($clean[$id] ?? null) ? in_array($key, $clean[$id], true) : (($clean[$id] ?? '') === $key);
                if ($picked) {
                    $clean[$id . '_other'] = is_string($raw[$id . '_other'] ?? null)
                        ? mb_substr(sanitize_text_field($raw[$id . '_other']), 0, 200)
                        : '';
                }
            }
        }
    }

    // Conditional questions / steps: blank out anything that shouldn't have been shown.
    foreach (ncq_schema() as $step) {
        $step_on = ncq_condition_met($step['condition'] ?? null, $clean);
        foreach ($step['questions'] as $q) {
            if (!$step_on || !ncq_condition_met($q['condition'] ?? null, $clean)) {
                unset($clean[$q['id']], $clean[$q['id'] . '_other']);
            }
        }
    }

    return $clean;
}

function ncq_condition_met($cond, $answers) {
    if (empty($cond)) return true;
    $v = $answers[$cond['q']] ?? null;
    return is_array($v) ? in_array($cond['v'], $v, true) : $v === $cond['v'];
}

// ─── AJAX HANDLER ─────────────────────────────────────────────────────────
function ncq_handle_ajax() {
    if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ncq_nonce')) {
        wp_send_json_error('Security check failed.');
    }

    // Honeypot — real visitors never see or fill this field.
    if (!empty($_POST['ncq_website'])) {
        wp_send_json_success('ok');
    }

    $lang = sanitize_key($_POST['lang'] ?? '');
    if (!in_array($lang, ['en', 'ar'], true)) $lang = 'en';

    $raw     = json_decode(wp_unslash($_POST['answers'] ?? ''), true);
    $answers = ncq_sanitize_answers($raw);

    // The opening questions are required in every path through the form.
    foreach (['gender', 'nationality', 'age', 'summer_home', 'budget', 'visited_nc'] as $req) {
        if (empty($answers[$req])) {
            wp_send_json_error('Please answer all required questions.');
        }
    }

    // Contact details are required (email is already blanked by the sanitiser if invalid).
    $phone_digits = preg_replace('/\D+/', '', $answers['phone'] ?? '');
    if (empty($answers['name']) || empty($answers['email']) || strlen($phone_digits) < 7 || strlen($phone_digits) > 15) {
        wp_send_json_error($lang === 'ar'
            ? 'يرجى إدخال الاسم والبريد الإلكتروني ورقم هاتف صحيح.'
            : 'Please provide your name, a valid email and a valid phone number.');
    }

    $nationality = $answers['nationality'];
    if ($nationality === 'other' && !empty($answers['nationality_other'])) {
        $nationality = 'other: ' . $answers['nationality_other'];
    }

    global $wpdb;
    $ok = $wpdb->insert(
        $wpdb->prefix . 'ncq_responses',
        [
            'lang'        => $lang,
            'name'        => $answers['name']  ?? '',
            'email'       => $answers['email'] ?? '',
            'phone'       => $answers['phone'] ?? '',
            'gender'      => $answers['gender'],
            'nationality' => mb_substr($nationality, 0, 150),
            'age_group'   => $answers['age'],
            'summer_home' => $answers['summer_home'],
            'budget'      => $answers['budget'],
            'visited_nc'  => $answers['visited_nc'],
            'answers'     => wp_json_encode($answers, JSON_UNESCAPED_UNICODE),
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );

    if ($ok === false) {
        wp_send_json_error('Could not save your answers.');
    }

    ncq_send_notification($answers, $lang, (int) $wpdb->insert_id);

    wp_send_json_success('ok');
}

// ─── ADMIN EMAIL NOTIFICATION ─────────────────────────────────────────────
define('NCQ_NOTIFY_TO', 'muhammed.raouf@awstreams.com');

function ncq_send_notification($answers, $lang, $id) {
    $esc = function ($s) { return nl2br(esc_html((string) $s)); };

    $rows = '';
    foreach (ncq_schema() as $step) {
        $section = '';
        foreach ($step['questions'] as $q) {
            $val = ncq_format_answer($q, $answers);
            if ($val === '') continue;
            $code     = !empty($q['code']) ? $q['code'] . ' – ' : '';
            $section .= '<tr><td style="padding:6px 10px;border-bottom:1px solid #eee;vertical-align:top;width:45%;color:#555;">'
                      . $esc($code . $q['label']['en']) . '</td>'
                      . '<td style="padding:6px 10px;border-bottom:1px solid #eee;vertical-align:top;">' . $esc($val) . '</td></tr>';
        }
        if ($section !== '') {
            $rows .= '<tr><td colspan="2" style="padding:14px 10px 6px;font-weight:bold;font-size:15px;background:#f6f3ee;">'
                   . $esc($step['title']['en']) . '</td></tr>' . $section;
        }
    }

    $link = admin_url('admin.php?page=ncq-responses');
    $body = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#222;">'
          . '<h2 style="margin:0 0 6px;">New North Coast questionnaire response</h2>'
          . '<p style="margin:0 0 16px;color:#666;">Language: ' . ($lang === 'ar' ? 'Arabic' : 'English') . ' · Response #' . $id . ' · ' . esc_html(wp_date('Y-m-d H:i')) . '</p>'
          . '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;max-width:760px;">' . $rows . '</table>'
          . '<p style="margin-top:20px;"><a href="' . esc_url($link) . '">View all responses in WordPress</a></p>'
          . '</div>';

    $subject = 'New questionnaire response – ' . ($answers['name'] ?? '');
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    if (!empty($answers['email'])) {
        $name      = str_replace(["\r", "\n", '"'], '', $answers['name'] ?? '');
        $headers[] = 'Reply-To: "' . $name . '" <' . $answers['email'] . '>';
    }

    // A mail failure must not fail the submission — the response is already saved.
    @wp_mail(NCQ_NOTIFY_TO, $subject, $body, $headers);
}
add_action('wp_ajax_ncq_submit',        'ncq_handle_ajax');
add_action('wp_ajax_nopriv_ncq_submit', 'ncq_handle_ajax');
