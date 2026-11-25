<?php
/**
 * Admin: "Questionnaire" menu — paginated responses table with language tabs,
 * a per-response detail view, and CSV export. Mirrors the Contact Leads page
 * in the Popup Form plugin.
 */

if (!defined('ABSPATH')) exit;

// ─── ANSWER FORMATTING (English labels, for admin + CSV) ──────────────────
function ncq_option_label($q, $key, $answers) {
    $opts  = ncq_question_options($q);
    $label = $opts[$key]['en'] ?? $key;
    // For subset / rank questions the "other" text lives on the source question.
    $owner = !empty($q['source']) ? $q['source'] : $q['id'];
    if (!empty($opts[$key]['specify'])) {
        $label = $key === 'other' ? 'Other' : $label;
        if (!empty($answers[$owner . '_other'])) $label .= ': ' . $answers[$owner . '_other'];
    }
    return $label;
}

/** One-line string for a question's answer ('' if unanswered). */
function ncq_format_answer($q, $answers) {
    $id  = $q['id'];
    $val = $answers[$id] ?? null;
    if ($val === null || $val === '' || $val === []) return '';

    switch ($q['type']) {
        case 'single':
            return ncq_option_label($q, $val, $answers);
        case 'multi':
            return implode('; ', array_map(function ($k) use ($q, $answers) { return ncq_option_label($q, $k, $answers); }, $val));
        case 'rank':
            $out = [];
            foreach (array_values($val) as $i => $k) $out[] = '#' . ($i + 1) . ' ' . ncq_option_label($q, $k, $answers);
            return implode('; ', $out);
        case 'scale':
            $out = [];
            foreach ($q['items'] as $item => $lbl) {
                if (isset($val[$item])) $out[] = $lbl['en'] . ': ' . $val[$item];
            }
            return implode('; ', $out);
        default:
            return (string) $val;
    }
}

/** Ordered CSV columns: [ header => callable(answers) ]. */
function ncq_csv_columns() {
    $cols = [];
    foreach (ncq_schema() as $step) {
        foreach ($step['questions'] as $q) {
            if ($q['type'] === 'input') continue; // contact fields have their own columns
            $code = !empty($q['code']) ? $q['code'] . ' – ' : '';
            $head = $code . $q['label']['en'];

            if ($q['type'] === 'scale') {
                foreach ($q['items'] as $item => $lbl) {
                    $cols[$q['code'] . ' – ' . $lbl['en']] = function ($a) use ($q, $item) {
                        return $a[$q['id']][$item] ?? '';
                    };
                }
            } elseif ($q['type'] === 'rank') {
                for ($i = 0; $i < 3; $i++) {
                    $cols[$q['code'] . ' – #' . ($i + 1)] = function ($a) use ($q, $i) {
                        $k = $a[$q['id']][$i] ?? null;
                        return $k === null ? '' : ncq_option_label($q, $k, $a);
                    };
                }
            } else {
                $cols[$head] = function ($a) use ($q) { return ncq_format_answer($q, $a); };
            }
        }
    }
    return $cols;
}

function ncq_admin_tab() {
    $tab = sanitize_key($_GET['tab'] ?? 'all');
    return in_array($tab, ['en', 'ar'], true) ? $tab : 'all';
}

// ─── CSV EXPORT (fires before headers are sent) ───────────────────────────
add_action('admin_init', function () {
    if (
        !isset($_GET['page'], $_GET['ncq_export']) ||
        $_GET['page'] !== 'ncq-responses' ||
        $_GET['ncq_export'] !== 'csv' ||
        !current_user_can('ncq_manage')
    ) return;

    check_admin_referer('ncq_export_csv');

    global $wpdb;
    $table = $wpdb->prefix . 'ncq_responses';
    $tab   = ncq_admin_tab();
    $rows  = $tab === 'all'
        ? $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A)
        : $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE lang = %s ORDER BY created_at DESC", $tab), ARRAY_A);

    $cols     = ncq_csv_columns();
    $filename = 'north-coast-questionnaire' . ($tab === 'all' ? '' : '-' . $tab) . '-' . gmdate('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, array_merge(['ID', 'Language', 'Date', 'Name', 'Email', 'Phone'], array_keys($cols)));
    foreach ($rows as $row) {
        $answers = json_decode($row['answers'], true) ?: [];
        $line    = [$row['id'], strtoupper($row['lang']), $row['created_at'], $row['name'], $row['email'], $row['phone']];
        foreach ($cols as $fn) $line[] = $fn($answers);
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
});

// ─── ADMIN MENU & PAGE ────────────────────────────────────────────────────
add_action('admin_menu', function () {
    add_menu_page(
        'Questionnaire Responses',
        'Questionnaire',
        'ncq_manage',
        'ncq-responses',
        'ncq_admin_page',
        'dashicons-clipboard',
        31
    );
});

function ncq_admin_page() {
    if (!current_user_can('ncq_manage')) wp_die('Access denied.');

    if (!empty($_GET['view'])) {
        ncq_admin_detail((int) $_GET['view']);
        return;
    }

    global $wpdb;
    $table    = $wpdb->prefix . 'ncq_responses';
    $tab      = ncq_admin_tab();
    $per_page = 25;
    $paged    = max(1, intval($_GET['paged'] ?? 1));
    $offset   = ($paged - 1) * $per_page;
    $where    = $tab === 'all' ? '' : $wpdb->prepare('WHERE lang = %s', $tab);

    $total       = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table $where");
    $rows        = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset));
    $total_pages = (int) ceil($total / $per_page);
    $base_url    = admin_url('admin.php?page=ncq-responses');
    $export_url  = wp_nonce_url($base_url . '&tab=' . $tab . '&ncq_export=csv', 'ncq_export_csv');
    $q           = ncq_questions();
    $labels      = [
        'all' => 'All Responses',
        'en'  => 'English Page',
        'ar'  => 'Arabic Page',
    ];
    ?>
    <div class="wrap">
        <h1>North Coast Questionnaire</h1>

        <nav class="nav-tab-wrapper" style="margin-bottom:0;">
            <?php foreach ($labels as $key => $label) : ?>
                <a href="<?php echo esc_url($base_url . '&tab=' . $key); ?>"
                   class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </nav>

        <div style="background:#fff;border:1px solid #c3c4c7;border-top:none;padding:16px 20px 20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <p style="margin:0;color:#666;">
                    <?php echo esc_html($labels[$tab]); ?> &mdash; Total: <strong><?php echo esc_html($total); ?></strong>
                </p>
                <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">⬇ Export CSV (all answers)</a>
            </div>

            <?php if (empty($rows)) : ?>
                <p style="color:#666;">No responses yet.</p>
            <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="width:50px">Lang</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Nationality</th>
                        <th>Age</th>
                        <th>Summer Home</th>
                        <th>Budget</th>
                        <th>Visited NC</th>
                        <th style="width:145px">Date</th>
                        <th style="width:60px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r) :
                        $a        = json_decode($r->answers, true) ?: [];
                        $view_url = $base_url . '&tab=' . $tab . '&view=' . (int) $r->id;
                        ?>
                    <tr>
                        <td><?php echo esc_html($r->id); ?></td>
                        <td><?php echo esc_html(strtoupper($r->lang)); ?></td>
                        <td><?php echo esc_html($r->name ?: '—'); ?></td>
                        <td><?php echo $r->email ? '<a href="mailto:' . esc_attr($r->email) . '">' . esc_html($r->email) . '</a>' : '—'; ?></td>
                        <td><?php echo esc_html($r->phone ?: '—'); ?></td>
                        <td><?php echo esc_html(ncq_format_answer($q['gender'], $a)); ?></td>
                        <td><?php echo esc_html(ncq_format_answer($q['nationality'], $a)); ?></td>
                        <td><?php echo esc_html(ncq_format_answer($q['age'], $a)); ?></td>
                        <td><?php echo esc_html(ncq_format_answer($q['summer_home'], $a)); ?></td>
                        <td><?php echo esc_html(ncq_format_answer($q['budget'], $a)); ?></td>
                        <td><?php echo esc_html(ncq_format_answer($q['visited_nc'], $a)); ?></td>
                        <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($r->created_at))); ?></td>
                        <td><a href="<?php echo esc_url($view_url); ?>" class="button button-small">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom" style="margin-top:12px;">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'current'   => $paged,
                        'total'     => $total_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ]);
                    ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function ncq_admin_detail($id) {
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ncq_responses WHERE id = %d", $id));
    $back = admin_url('admin.php?page=ncq-responses&tab=' . ncq_admin_tab());

    echo '<div class="wrap">';
    echo '<p><a href="' . esc_url($back) . '">&larr; Back to all responses</a></p>';

    if (!$row) {
        echo '<h1>Response not found</h1></div>';
        return;
    }

    $a = json_decode($row->answers, true) ?: [];
    ?>
    <h1>Response #<?php echo esc_html($row->id); ?></h1>
    <p style="color:#666;margin-top:0;">
        <?php echo esc_html($row->lang === 'ar' ? 'Arabic page' : 'English page'); ?> &middot;
        <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($row->created_at))); ?>
    </p>

    <?php foreach (ncq_schema() as $step) :
        $shown = array_filter($step['questions'], function ($q) use ($a) { return array_key_exists($q['id'], $a); });
        if (empty($shown)) continue;
        ?>
        <h2 style="margin-top:28px;"><?php echo esc_html($step['title']['en']); ?></h2>
        <table class="widefat striped" style="max-width:1000px;">
            <tbody>
                <?php foreach ($shown as $q) : ?>
                    <tr>
                        <th style="width:42%;font-weight:600;">
                            <?php if (!empty($q['code'])) : ?><span style="color:#a58d67;"><?php echo esc_html($q['code']); ?></span>&nbsp; <?php endif; ?>
                            <?php echo esc_html($q['label']['en']); ?>
                        </th>
                        <td>
                            <?php
                            if ($q['type'] === 'scale' || $q['type'] === 'rank' || $q['type'] === 'multi') {
                                $parts = array_filter(explode('; ', ncq_format_answer($q, $a)));
                                echo $parts ? implode('<br>', array_map('esc_html', $parts)) : '<span style="color:#999;">—</span>';
                            } else {
                                $txt = ncq_format_answer($q, $a);
                                echo $txt !== '' ? nl2br(esc_html($txt)) : '<span style="color:#999;">—</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
    </div>
    <?php
}
