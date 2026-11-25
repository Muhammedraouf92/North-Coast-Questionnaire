<?php
/**
 * North Coast Questionnaire — English.
 * Loaded via template_include from north-coast-questionnaire.php when a page
 * has the "North Coast Questionnaire (English)" layout selected.
 *
 * Standalone page: outputs its own document with the plugin's header and
 * footer instead of the theme's (the site is just this page).
 */

if (!defined('ABSPATH')) exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('ncq-standalone'); ?>>
<?php wp_body_open(); ?>

<?php ncq_render_questionnaire('en'); ?>

<?php wp_footer(); ?>
</body>
</html>
