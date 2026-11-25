# North Coast Questionnaire — WordPress Plugin Summary

**Author:** Raouf (Mohamed Abdelraouf) · **Version:** 1.1 · **Type:** Custom WordPress plugin

## What it is

A bilingual (English / Arabic) market-research questionnaire plugin built for a North Coast (Egypt) summer-homes study — the "TBC – North Coast Pre Season Opening 2026" project. It screens and profiles prospective summer-home buyers: who they are, their budget, how they view Egypt as a destination, what drives their purchase decision, and their view of North Coast real estate, ending with contact details (respondents are told they'll receive a voucher on submission).

## How it is used on a site

- **Not shortcodes** — the plugin adds **two full-page templates**: "North Coast Questionnaire (English)" and "North Coast Questionnaire (Arabic, RTL)".
- Because Elementor rebuilds the native template dropdown, the plugin adds its own **"Questionnaire Layout" metabox** on pages that writes the template directly to `_wp_page_template`, with nonce + capability checks, and never overwrites a template set by the theme or another plugin.
- The questionnaire is a standalone page (its own header/footer, page content ignored), presented as a premium multi-step experience (~5 minutes) with a hero, progress bar, chapter navigation, and North Coast photography (Sidi Abdel Rahman, New Alamein, Porto Marina — Wikimedia Commons images with credits built in).

## The questionnaire

- **7 sections, 27 questions**, all defined in one central schema (`includes/schema.php`): "Let's start with you" → "Summer homes" → "Your view of Egypt" → "What matters when making the decision?" → "Let's talk North Coast" → "North Coast real estate" → "Stay in touch" (name / email / phone).
- **6 question types:** single choice (7), multiple choice (8), 1–5 rating scale (1), rank-your-top-3 (2), open text (6), and contact inputs (3).
- **Screening logic:** certain answers end the questionnaire politely (under 25, not interested in a summer home, budget under USD 300K, never visited the North Coast).
- **Smart follow-ups:** conditional steps/questions, ranking questions built only from options picked in a previous question, subset questions limited to earlier picks, and "Other / Yes, specify" free-text boxes.

## Bilingual architecture

Both languages run off the **single schema** — every label exists in English and Arabic in one place. Answers are stored by **option key, never by translated label**, so English and Arabic submissions land in the same columns and can be compared and exported together.

## Data & security

- Submissions are saved by AJAX (`wp_ajax_` + `wp_ajax_nopriv_`) to a **custom database table** (`wp_ncq_responses`), created with `dbDelta` on activation (with an `admin_init` self-heal if the plugin was installed without activation).
- Headline answers (name, email, phone, gender, nationality, age group, summer-home interest, budget, visited North Coast) get their own columns; the full answer set is stored as JSON.
- Every answer is **re-validated server-side against the schema** — unknown fields are dropped, option values must be known keys, ranks must come from what the respondent actually picked, and answers to questions that shouldn't have been shown are blanked out.
- Protected by a **nonce** and a **honeypot** anti-spam field; email is validated and phone must be 7–15 digits.

## Admin side

- A **"Questionnaire" menu** in wp-admin, gated by a custom `ncq_manage` capability granted to administrators and editors.
- Responses list with tabs — All / English Page / Arabic Page — pagination, and a per-response detail view with answers shown under English labels.
- **CSV export** of all answers (nonce-protected), one file per tab, dated filename.
- An **HTML email notification** is sent for every new submission (to muh...@awstreams.com) with the full answer table and a link to the admin page; the respondent's address is set as Reply-To, and a mail failure never fails the submission itself.

## Codebase

~3,900 lines across 8 files: main plugin file (397), `includes/schema.php` (492), `includes/render.php` (337), `includes/admin.php` (301), `assets/questionnaire.js` (695), `assets/questionnaire.css` (1,589), and two thin templates (26 lines each). The ZIP also bundles 12 location photos and the 9.5 MB TBC source PDF, which is why the ZIP is ~12 MB.
