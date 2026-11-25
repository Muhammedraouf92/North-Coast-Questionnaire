<?php
/**
 * Front-end markup for the questionnaire, generated from ncq_schema().
 * Both page templates call ncq_render_questionnaire() with their language.
 */

if (!defined('ABSPATH')) exit;

function ncq_img($file) {
    return NCQ_URL . 'assets/img/' . $file;
}

function ncq_arrow_svg() {
    return '<svg class="ncq-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16M14 6l6 6-6 6"/></svg>';
}

/** Logo: assets/img/logo.svg or logo.png if present, otherwise the site name. */
function ncq_logo_html($class = 'ncq-logo') {
    foreach (['logo.svg', 'logo.png', 'logo.webp', 'logo.jpg'] as $f) {
        if (file_exists(NCQ_DIR . 'assets/img/' . $f)) {
            return '<img class="' . esc_attr($class) . '-img" src="' . esc_url(ncq_img($f)) . '" alt="' . esc_attr(get_bloginfo('name')) . '">';
        }
    }
    return '<span class="' . esc_attr($class) . '-text">' . esc_html(get_bloginfo('name')) . '</span>';
}

/** Polylang language switcher (hidden if Polylang isn't active). */
function ncq_lang_switcher($s) {
    if (!function_exists('pll_the_languages')) return;
    $langs = pll_the_languages(['raw' => 1, 'hide_if_no_translation' => 0, 'hide_current' => 0]);
    if (empty($langs) || count($langs) < 2) return;
    echo '<nav class="ncq-langs" aria-label="' . esc_attr($s['lang_label']) . '">';
    foreach ($langs as $l) {
        $current = !empty($l['current_lang']);
        echo '<a class="ncq-lang' . ($current ? ' is-current' : '') . '" href="' . esc_url($l['url']) . '" hreflang="' . esc_attr($l['slug']) . '" lang="' . esc_attr($l['slug']) . '"' . ($current ? ' aria-current="true"' : '') . '>';
        echo '<span class="ncq-lang-full">' . esc_html($l['name']) . '</span>';
        echo '<span class="ncq-lang-short">' . esc_html(strtoupper($l['slug'])) . '</span>';
        echo '</a>';
    }
    echo '</nav>';
}

function ncq_render_questionnaire($lang) {
    $lang   = $lang === 'ar' ? 'ar' : 'en';
    $is_rtl = $lang === 'ar';
    $s      = ncq_strings($lang);
    $steps  = ncq_schema();
    $hero   = ncq_img('hero-sidi-abdel-rahman.jpg');
    ?>
    <div id="ncq" class="ncq ncq-lang-<?php echo esc_attr($lang); ?>" dir="<?php echo $is_rtl ? 'rtl' : 'ltr'; ?>" lang="<?php echo esc_attr($lang); ?>">

        <!-- ── HEADER ───────────────────────────────────────────────── -->
        <header class="ncq-header">
            <div class="ncq-header-inner">
                <a class="ncq-logo" href="<?php echo esc_url(home_url('/north-coast')); ?>" rel="home"><?php echo ncq_logo_html('ncq-logo'); ?></a>
                <?php ncq_lang_switcher($s); ?>
            </div>
        </header>

        <!-- ── HERO ─────────────────────────────────────────────────── -->
        <section class="ncq-hero">
            <div class="ncq-hero-media">
                <img src="<?php echo esc_url($hero); ?>" alt="" fetchpriority="high" decoding="async">
            </div>
            <div class="ncq-hero-shade" aria-hidden="true"></div>

            <div class="ncq-hero-content">
                <p class="ncq-eyebrow"><?php echo esc_html($s['eyebrow']); ?></p>
                <h1 class="ncq-hero-title"><?php echo wp_kses($s['hero_title'], ['em' => []]); ?></h1>
                <p class="ncq-hero-lead"><?php echo esc_html($s['hero_lead']); ?></p>
                <ul class="ncq-hero-meta">
                    <li><?php echo esc_html($s['meta_time']); ?></li>
                    <li><?php echo esc_html($s['meta_steps']); ?></li>
                    <li><?php echo esc_html($s['meta_conf']); ?></li>
                </ul>
                <a href="#ncq-survey" class="ncq-btn ncq-btn-gold ncq-begin">
                    <span><?php echo esc_html($s['begin']); ?></span><?php echo ncq_arrow_svg(); ?>
                </a>
            </div>

            
        </section>

        <!-- ── SURVEY ───────────────────────────────────────────────── -->
        <section id="ncq-survey" class="ncq-survey">
            <div class="ncq-shell">

                <aside class="ncq-visual" aria-hidden="true">
                    <div class="ncq-visual-frame">
                        <?php foreach ($steps as $i => $step) : ?>
                            <figure class="ncq-visual-img<?php echo $i === 0 ? ' is-active' : ''; ?>" data-visual="<?php echo esc_attr($step['key']); ?>">
                                <img src="<?php echo esc_url(ncq_img($step['image'])); ?>" alt="" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>" decoding="async">
                            </figure>
                        <?php endforeach; ?>
                        <figure class="ncq-visual-img" data-visual="thanks">
                            <img src="<?php echo esc_url(ncq_img('alamein-beach-sunset.jpg')); ?>" alt="" loading="lazy" decoding="async">
                        </figure>
                        <figure class="ncq-visual-img" data-visual="terminated">
                            <img src="<?php echo esc_url(ncq_img('alamein-beach-sunset.jpg')); ?>" alt="" loading="lazy" decoding="async">
                        </figure>
                        <div class="ncq-visual-shade"></div>
                    </div>
                </aside>

                <div class="ncq-main">
                    <div class="ncq-progress">
                        <p class="ncq-progress-label">
                            <span class="ncq-progress-count"></span>
                            <span class="ncq-progress-sep" aria-hidden="true"></span>
                            <span class="ncq-progress-title"><?php echo esc_html($steps[0]['title'][$lang]); ?></span>
                        </p>
                        <div class="ncq-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span></span></div>
                    </div>

                    <form id="ncq-form" class="ncq-form" novalidate>
                        <div class="ncq-hp" aria-hidden="true">
                            <label>Website <input type="text" name="ncq_website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <?php foreach ($steps as $i => $step) : ?>
                            <fieldset class="ncq-step"
                                      data-step="<?php echo esc_attr($step['key']); ?>"
                                      data-place="<?php echo esc_attr($step['place'][$lang]); ?>"
                                      data-title="<?php echo esc_attr($step['title'][$lang]); ?>"
                                      <?php if (!empty($step['condition'])) : ?>data-condition="<?php echo esc_attr(wp_json_encode($step['condition'])); ?>"<?php endif; ?>
                                      <?php if (!empty($step['all_at_once'])) : ?>data-all="1"<?php endif; ?>
                                      <?php echo $i === 0 ? '' : 'hidden'; ?>>
                                <legend class="ncq-step-head">
                                    <span class="ncq-step-kicker"><?php echo esc_html($s['chapter']); ?> <span class="ncq-step-n"></span></span>
                                    <span class="ncq-step-title"><?php echo esc_html($step['title'][$lang]); ?></span>
                                </legend>
                                <?php if (!empty($step['intro'])) : ?>
                                    <p class="ncq-step-intro"><?php echo esc_html($step['intro'][$lang]); ?></p>
                                <?php endif; ?>

                                <?php foreach ($step['questions'] as $q) ncq_render_question($q, $lang, $s); ?>
                            </fieldset>
                        <?php endforeach; ?>

                        <p class="ncq-form-error" role="alert" aria-live="assertive"></p>

                        <div class="ncq-nav">
                            <button type="button" class="ncq-btn ncq-btn-ghost" data-ncq-prev hidden>
                                <?php echo ncq_arrow_svg(); ?><span><?php echo esc_html($s['back']); ?></span>
                            </button>
                            <button type="button" class="ncq-btn ncq-btn-gold" data-ncq-next>
                                <span><?php echo esc_html($s['next']); ?></span><?php echo ncq_arrow_svg(); ?>
                            </button>
                            <button type="submit" class="ncq-btn ncq-btn-gold" data-ncq-submit hidden>
                                <span class="ncq-btn-label"><?php echo esc_html($s['submit']); ?></span>
                                <span class="ncq-spinner" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>

                    <div class="ncq-terminated" hidden tabindex="-1" data-place="<?php echo esc_attr($is_rtl ? 'العلمين' : 'El Alamein'); ?>">
                        <svg class="ncq-thanks-mark" viewBox="0 0 64 64" fill="none" stroke="currentColor" aria-hidden="true">
                            <circle cx="32" cy="32" r="30" stroke-width="1.5" stroke-dasharray="190" stroke-dashoffset="190" style="animation:ncq-draw 1.1s cubic-bezier(0.22,0.61,0.36,1) 0.2s forwards"/>
                            <path d="M20 32h24" stroke-width="1.5" stroke-linecap="round" stroke-dasharray="28" stroke-dashoffset="28" style="animation:ncq-draw 0.5s cubic-bezier(0.22,0.61,0.36,1) 1s forwards"/>
                        </svg>
                        <h2 class="ncq-thanks-title"><?php echo esc_html($s['term_title']); ?></h2>
                        <p class="ncq-thanks-text"><?php echo esc_html($s['term_text']); ?></p>
                        <a class="ncq-btn ncq-btn-ghost" href="<?php echo esc_url(home_url('/north-coast')); ?>">
                            <span><?php echo esc_html($s['thanks_home']); ?></span><?php echo ncq_arrow_svg(); ?>
                        </a>
                    </div>

                    <div class="ncq-thanks" hidden tabindex="-1" data-place="<?php echo esc_attr($is_rtl ? 'العلمين' : 'El Alamein'); ?>">
                        <svg class="ncq-thanks-mark" viewBox="0 0 64 64" aria-hidden="true">
                            <circle cx="32" cy="32" r="30" fill="none" stroke-width="1.5"/>
                            <path d="M20 33l8 8 16-17" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 class="ncq-thanks-title"><?php echo esc_html($s['thanks_title']); ?></h2>
                        <p class="ncq-thanks-text"><?php echo esc_html($s['thanks_text']); ?></p>
                        <a class="ncq-btn ncq-btn-ghost" href="<?php echo esc_url(home_url('/north-coast')); ?>">
                            <span><?php echo esc_html($s['thanks_home']); ?></span><?php echo ncq_arrow_svg(); ?>
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- ── FOOTER ───────────────────────────────────────────────── -->
        <footer class="ncq-footer">
            <div class="ncq-footer-inner">
                <div class="ncq-footer-top">
                    <a class="ncq-footer-logo" href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php echo ncq_logo_html('ncq-footer-logo'); ?></a>
                    <?php ncq_lang_switcher($s); ?>
                </div>
                <div class="ncq-footer-bottom">
                    <p class="ncq-footer-copy">&copy; <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?>. <?php echo esc_html($s['rights']); ?></p>
                    
                </div>
            </div>
        </footer>
    </div>
    <?php
}

function ncq_render_question($q, $lang, $s) {
    $id       = $q['id'];
    $type     = $q['type'];
    $required = !empty($q['required']);
    $label_id = 'ncq-l-' . $id;
    $attrs    = [
        'class'         => 'ncq-q ncq-q--' . $type,
        'data-q'        => $id,
        'data-type'     => $type,
        'data-required' => $required ? '1' : '0',
    ];
    if (!empty($q['condition'])) $attrs['data-condition'] = wp_json_encode($q['condition']);
    if (!empty($q['source']))    $attrs['data-source']    = $q['source'];
    if (!empty($q['condition'])) $attrs['hidden']         = 'hidden';

    echo '<div';
    foreach ($attrs as $k => $v) echo ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
    echo '>';

    if (!empty($q['eyebrow'])) {
        echo '<p class="ncq-q-eyebrow">' . esc_html($q['eyebrow'][$lang]) . '</p>';
    }
    echo '<h3 class="ncq-q-label" id="' . esc_attr($label_id) . '">' . esc_html($q['label'][$lang]) . '</h3>';
    if (!empty($q['hint'])) {
        echo '<p class="ncq-q-hint">' . esc_html($q['hint'][$lang]) . '</p>';
    }

    switch ($type) {
        case 'single':
        case 'multi':
            $input   = $type === 'single' ? 'radio' : 'checkbox';
            $layout  = $q['layout'] ?? 'grid';
            $role    = $type === 'single' ? ' role="radiogroup"' : ' role="group"';
            echo '<div class="ncq-opts ncq-opts--' . esc_attr($layout) . '"' . $role . ' aria-labelledby="' . esc_attr($label_id) . '">';
            if (!empty($q['source'])) {
                // Filled in by JS from the answers to the source question.
                echo '</div>';
                echo '<p class="ncq-q-empty">' . esc_html($q['empty'][$lang] ?? $s['rank_empty']) . '</p>';
                if (!empty($q['none'])) {
                    // Static "None" option — always visible, mutual-exclusive with the dynamic options.
                    echo '<div class="ncq-opts ncq-opts--none">';
                    echo '<label class="ncq-opt">';
                    echo '<input type="checkbox" name="' . esc_attr($id . '[]') . '" value="none" data-none="1">';
                    echo '<span class="ncq-opt-mark" aria-hidden="true"></span>';
                    echo '<span class="ncq-opt-text">' . esc_html($q['none'][$lang]) . '</span>';
                    echo '</label>';
                    echo '</div>';
                }
            } else {
                foreach ($q['options'] as $key => $opt) {
                    ncq_render_option($id, $input, $key, $opt[$lang], !empty($opt['specify']), !empty($opt['terminate']));
                }
                echo '</div>';
                foreach ($q['options'] as $key => $opt) {
                    if (empty($opt['specify'])) continue;
                    $ph = $opt['placeholder'][$lang] ?? $s['specify'];
                    echo '<div class="ncq-specify" data-for="' . esc_attr($key) . '" hidden>';
                    echo '<input type="text" class="ncq-line" name="' . esc_attr($id . '_other') . '" placeholder="' . esc_attr($ph) . '" aria-label="' . esc_attr($ph) . '" maxlength="200">';
                    echo '</div>';
                }
            }
            break;

        case 'scale':
            echo '<div class="ncq-scale">';
            echo '<div class="ncq-scale-legend" aria-hidden="true"><span></span><div class="ncq-scale-keys">';
            foreach ($q['scale'] as $n => $lbl) {
                echo '<span><b>' . (int) $n . '</b>' . esc_html($lbl[$lang]) . '</span>';
            }
            echo '</div></div>';
            foreach ($q['items'] as $item => $item_label) {
                $row_id = 'ncq-l-' . $id . '-' . $item;
                echo '<div class="ncq-scale-row" data-item="' . esc_attr($item) . '">';
                echo '<span class="ncq-scale-item" id="' . esc_attr($row_id) . '">' . esc_html($item_label[$lang]) . '</span>';
                echo '<div class="ncq-scale-opts" role="radiogroup" aria-labelledby="' . esc_attr($row_id) . '">';
                foreach ($q['scale'] as $n => $lbl) {
                    echo '<label class="ncq-scale-opt" title="' . esc_attr($lbl[$lang]) . '">';
                    echo '<input type="radio" name="' . esc_attr($id . '[' . $item . ']') . '" value="' . (int) $n . '" aria-label="' . esc_attr($n . ' — ' . $lbl[$lang]) . '">';
                    echo '<span>' . (int) $n . '</span></label>';
                }
                echo '</div></div>';
            }
            echo '</div>';
            break;

        case 'rank':
            $ranks = array_map(function ($r) use ($lang) { return $r[$lang]; }, $q['ranks']);
            echo '<p class="ncq-rank-help">' . esc_html($s['rank_help']) . '</p>';
            echo '<ol class="ncq-rank-slots">';
            foreach ($ranks as $i => $r) {
                echo '<li class="ncq-rank-slot" data-slot="' . (int) $i . '"><span class="ncq-rank-badge">' . ($i + 1) . '</span>';
                echo '<span class="ncq-rank-slot-text"><small>' . esc_html($r) . '</small><strong>—</strong></span></li>';
            }
            echo '</ol>';
            echo '<div class="ncq-rank-pool" role="group" aria-labelledby="' . esc_attr($label_id) . '">';
            if (empty($q['source'])) {
                // Fixed list — chips are printed here. Sourced lists are built by JS.
                foreach ($q['options'] as $key => $opt) {
                    echo '<button type="button" class="ncq-rank-chip" data-key="' . esc_attr($key) . '" aria-pressed="false">';
                    echo '<span class="ncq-rank-chip-n"></span><span>' . esc_html($opt[$lang]) . '</span></button>';
                }
            }
            echo '</div>';
            if (!empty($q['source'])) {
                echo '<p class="ncq-q-empty">' . esc_html($s['rank_empty']) . '</p>';
            } else {
                foreach ($q['options'] as $key => $opt) {
                    if (empty($opt['specify'])) continue;
                    echo '<div class="ncq-specify" data-for="' . esc_attr($key) . '" hidden>';
                    echo '<input type="text" class="ncq-line" name="' . esc_attr($id . '_other') . '" placeholder="' . esc_attr($s['specify']) . '" aria-label="' . esc_attr($s['specify']) . '" maxlength="200">';
                    echo '</div>';
                }
            }
            break;

        case 'text':
            echo '<textarea class="ncq-line ncq-textarea" name="' . esc_attr($id) . '" rows="3" maxlength="2000" aria-labelledby="' . esc_attr($label_id) . '" placeholder="' . esc_attr($s['open']) . '"></textarea>';
            break;

        case 'input':
            echo '<input class="ncq-line" type="' . esc_attr($q['input']) . '" name="' . esc_attr($id) . '" autocomplete="' . esc_attr($q['autocomplete'] ?? 'off') . '" maxlength="200" aria-labelledby="' . esc_attr($label_id) . '"' . ($required ? ' required aria-required="true"' : '') . ($q['input'] === 'tel' ? ' dir="ltr"' : '') . '>';
            break;
    }

    echo '<p class="ncq-q-error" role="alert"></p>';
    echo '</div>';
}

function ncq_render_option($qid, $input, $key, $label, $specify = false, $terminate = false) {
    ?>
    <label class="ncq-opt">
        <input type="<?php echo esc_attr($input); ?>" name="<?php echo esc_attr($qid . ($input === 'checkbox' ? '[]' : '')); ?>" value="<?php echo esc_attr($key); ?>"<?php echo $specify ? ' data-specify="1"' : ''; ?><?php echo $terminate ? ' data-terminate="1"' : ''; ?>>
        <span class="ncq-opt-mark" aria-hidden="true"></span>
        <span class="ncq-opt-text"><?php echo esc_html($label); ?></span>
    </label>
    <?php
}
