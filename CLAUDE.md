# Smart Search Control Agent Reference

This file is a compact working memory for AI agents working on the Smart Search Control plugin. It is meant to reduce the need to re-read the full codebase on each task by capturing the most important structure, conventions, and safeguards.

## 1. Agent Objective

You are working on a WordPress plugin that provides configurable search forms, shortcode rendering, AJAX suggestions, and admin-managed search settings. Your job is to make changes that are safe, consistent, and aligned with the existing plugin structure.

## 2. Plugin Overview

- Plugin name: Smart Search Control
- Main plugin file: smart-search-control.php
- Primary purpose: add configurable search forms, shortcode-based rendering, AJAX suggestions, admin configuration, and result-page support for WordPress/WooCommerce-related searches.

## 3. Project Structure

- smart-search-control.php
  - Main plugin bootstrap
  - Defines constants and includes the required files
- includes/
  - smarseco-functions.php: shared helper functions
  - smart-search-control-shortcode.php: search shortcode and AJAX suggestion handling
  - smart-search-control-result-shortcode.php: result page shortcode logic
  - smart-search-control-database.php: database table creation logic
  - admin/
    - smart-search-control-admin-menu.php: admin page and form handling
    - smart-search-control-admin-submenu-setting.php: settings page handling
- templates/
  - template-smart-search-control.php: frontend search form template
  - template-smart-search-control-result.php: frontend result template
  - admin/
    - template-smart-search-control-admin-page.php: admin list page UI
    - template-smart-search-control-admin-setting-page.php: admin settings page UI
- assets/
  - css/: front-end and admin styles
  - js/: frontend AJAX logic and admin JS
- blocks/
  - smarseco-block.php: Gutenberg block registration

## 4. Main Entry Points For Common Tasks

- Adding or changing shortcode behavior:
  - includes/smart-search-control-shortcode.php
  - Key handler: smarseco_render_search_shortcode()
  - Related handler: smarseco_smart_search_control_suggestion()
- Changing admin UI or saved search form behavior:
  - includes/admin/smart-search-control-admin-menu.php
  - Key handlers: smarseco_admin_menu_page(), smarseco_process_search_form(), smarseco_generate_taxonomy_options_html()
  - Template: templates/admin/template-smart-search-control-admin-page.php
- Changing settings page behavior:
  - includes/admin/smart-search-control-admin-submenu-setting.php
  - Template: templates/admin/template-smart-search-control-admin-setting-page.php
- Changing frontend rendering:
  - templates/template-smart-search-control.php
  - templates/template-smart-search-control-result.php
  - Related logic is often driven by the shortcode and result shortcode handlers.
- Changing database setup:
  - includes/smart-search-control-database.php
  - Main plugin bootstrap: smart-search-control.php
  - Key handler: smarseco_smart_search_control_create_table()

## 5. Important Implementation Notes

- Search settings are stored as JSON in the database table prefixed with smart_search_control_parameters.
- The shortcode uses saved entry data to control placeholder text, CSS ID/class, post types, categories, and tags.
- AJAX suggestion handling is wired through the search shortcode logic and must always validate nonces.
- Admin templates must escape output and sanitize request fields before processing.
- Preserve the existing plugin behavior unless the task explicitly asks for a change.

## 6. What Agents Must Do

- Start from the correct entry point based on the task type.
- Keep changes focused and avoid unrelated refactors.
- Preserve backward compatibility for shortcode usage and saved search entries.
- Follow the coding style exactly; do not improvise formatting.
- Verify that security and escaping rules are respected before considering the task complete.

## 7. What Agents Must Avoid

- Do not introduce inline styles in PHP templates.
- Do not bypass nonce validation in AJAX handlers.
- Do not output unsanitized values from request data.
- Do not add redundant DB queries when the same data can be reused.
- Do not change plugin behavior without checking the shortcode and admin flow.

## 8. Coding Style (MANDATORY — apply to every file touched)

These rules override any defaults. Every piece of code written or modified must conform exactly.

### 8.1 Braces — opening brace on the same line

```php
// CORRECT
if( $condition ) {
    // ...
}

function smarseco_generate_taxonomy_options_html( $terms, $post_type ) {
    // ...
}

class SMARSECO_Smart_Search_Control_Admin_Menu {
    // ...
}

// WRONG — never put the opening brace on its own line
if( $condition )
{
    // ...
}
```

### 8.2 Spacing around parentheses

- No space between the keyword/function name and `(`.
- One space after `(` and one space before `)`.

```php
// CORRECT
if( $condition ) { }
foreach( $items as $item ) { }
while( $condition ) { }
function smarseco_render_search_shortcode( $atts ) { }
smarseco_render_search_shortcode( $atts );
$wpdb->prepare( "SELECT ...", $id );

// WRONG — space before the opening paren
if ( $condition ) { }
foreach ( $items as $item ) { }
smarseco_render_search_shortcode ( $atts );

// WRONG — no inner gap
if($condition) { }
smarseco_render_search_shortcode($atts);
```

> Exception: empty parameter lists have no inner spaces — `function smarseco_foo() { }`.

- No space between the `!` negation operator and its operand in `if`, `elseif`, `while`, `switch`, and other conditional expressions.

```php
// CORRECT
if( !isset( $post_type ) ) { }
if( !empty( $term_ids ) && !$tax_obj ) { }
$show_tags = !empty( $tags_display ) ? esc_html( $tags_display ) : '-';

// WRONG — space after !
if( ! isset( $post_type ) ) { }
if( ! empty( $term_ids ) && ! $tax_obj ) { }
```

### 8.3 Indentation

- 4 spaces per level (no tabs).
- Align multi-line arrays and chained calls consistently.

```php
$data = [
    'key_one' => $value_one,
    'key_two' => $value_two,
];

$result = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id FROM {$table} WHERE post_id = %d",
        $post_id
    )
);
```

### 8.4 Code optimisation rules

- No redundant queries — never run the same DB query twice in a request; store the result in a variable.
- No N+1 queries — fetch related data in bulk (IN clauses, JOINs) rather than inside loops.
- Early returns — guard clauses first, happy path last.

```php
if( empty( $post_id ) ) {
    return [];
}
// main logic here
```

- No dead code — remove commented-out blocks before committing.
- Use strict comparisons — `===` / `!==` over `==` / `!=` unless type coercion is intentional.

### 8.5 WordPress object cache (`wp_cache_*`)

Use the WP object cache for any value that:
- Comes from a DB query that may be called more than once per page load, or
- Is expensive to compute (hierarchy traversal, closure-table queries, aggregation).

Standard pattern:

```php
$cache_key   = 'smarseco_search_data_' . $ssc_id;
$cache_group = 'smart_search_control';
$result      = wp_cache_get( $cache_key, $cache_group );

if( false === $result ) {
    // run the DB query / computation
    $result = $wpdb->get_row( $wpdb->prepare( "SELECT data FROM {$wpdb->prefix}smart_search_control_parameters WHERE id = %d", $ssc_id ) );
    wp_cache_set( $cache_key, $result, $cache_group, 12 * HOUR_IN_SECONDS );
}

return $result;
```

- Always use a consistent group name (`'smart_search_control'`) so cache entries can be reasoned about together.
- Call `wp_cache_delete( $cache_key, $cache_group )` whenever the underlying data is written or updated.
- Do not cache user-specific data under a shared key — include `$user_id` in the key when the result varies per user.

### 8.6 Naming conventions

| Thing | Convention | Example |
|-------|-----------|---------|
| PHP classes | `SMARSECO_Pascal_Case` | `SMARSECO_Smart_Search_Control` |
| Methods / functions | `smarseco_snake_case` | `smarseco_render_search_shortcode()` |
| Variables | `snake_case` | `$smarseco_search_entries` |
| Constants | `SMARSECO_UPPER_SNAKE_CASE` | `SMARSECO_VERSION` |
| DB table variables | `$table_name` / `$wpdb->prefix . 'smart_search_control_...'` | — |
| JS variables | `camelCase` | `searchForm` |
| JS functions | `camelCase` | `handleSearchSubmit()` |
| CSS classes | `kebab-case` prefixed `smarseco-` | `smarseco-categories-wrapper` |

### 8.7 General hygiene

- No inline styles in PHP templates — CSS goes in the stylesheet.
- Always sanitize input (`intval`, `sanitize_text_field`, `absint`, `esc_url_raw`) at the AJAX/request boundary.
- Always escape output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`) before rendering.
- Nonce verification is required on every AJAX handler before any other logic.
- Keep functions single-responsibility — if a function is doing two distinct things, split it.

## 9. Quick Start for Agents

When starting a new task, treat this section as the short memory checklist:

1. Identify whether the request touches the admin UI, the shortcode output, the AJAX suggestion flow, or the result page.
2. Open the most relevant file from the main entry points section above before editing.
3. Keep changes scoped to one responsibility and follow the mandatory coding style exactly.
4. Sanitize request input and escape output in every new or modified path.
5. If the task affects search settings, confirm the data is still saved and loaded correctly from the plugin database table.
6. Do not assume the plugin structure from memory; use this file as the first reference point before exploring deeper.

## 10. Quick Working Checklist

Before finishing a task:

1. Review the relevant file in includes/, templates/, and assets/.
2. Follow the mandatory style rules above.
3. Sanitize and escape any new input/output.
4. Verify the change does not break the shortcode, AJAX flow, or admin UI.
