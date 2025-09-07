<?php
/*
Plugin Name: YOURLS White Label
Plugin URI: https://yourls.org/
Description: A complete white-label solution for YOURLS. Customizes the login page, admin header, footer, titles, favicon, and allows for custom CSS.
Version: 1.0
Author: Resource Interactive
Author URI: https://resourceinteractive.net/
*/

// No direct call
if (!defined('YOURLS_ABSPATH')) {
    die();
}

// =============================================================================
// A. GLOBAL CONFIGURATION
// =============================================================================

define('WL_BRAND_NAME', 'MY BRAND');
define('WL_LOGO_FILENAME', 'logo.png'); // Place this image in this plugin's folder



// =============================================================================
// B. HEADER & LOGO CUSTOMIZATION 
// =============================================================================

/**
 * This single function now handles replacing the header on BOTH the login
 * page and the internal admin pages, using the 'pre_html_logo' hook.
 */
function wl_render_custom_header() {
    $logo_url = yourls_plugin_url(__DIR__) . '/' . WL_LOGO_FILENAME;
    $brand_name = WL_BRAND_NAME;

    // NEW, MORE RELIABLE CHECK:
    // We check the page 'context'. On the login screen, the context is always 'login'.
    if (yourls_get_html_context() == 'login') {
        // This is the header for the LOGIN page (logged out)
        echo <<<HTML
        <div id="custom-login-header">
            <img src="$logo_url" alt="$brand_name Logo" style="max-width: 250px;"/>
        </div>
HTML;

    } else {
        // This is the header for all other INTERNAL ADMIN pages (logged in)
        $admin_url = yourls_admin_url('index.php');
        echo <<<HTML
        <div id="custom-admin-header">
            <a href="$admin_url">
                <img src="$logo_url" alt="$brand_name Logo" style="max-height: 40px;"/>
            </a>
        </div>
HTML;
    }
}
// We attach our new function to the hook that exists in your file.
yourls_add_action('pre_html_logo', 'wl_render_custom_header');


// =============================================================================
// C. TITLE, FOOTER, CSS
// =============================================================================

// Customize the login page <title>
function wl_custom_title($original_title) {
    // Check if we are on the login page
    if (yourls_get_html_context() == 'login') {
        // Return a static title for the login page
        return WL_BRAND_NAME . ' &mdash; Login';

    } else {
        // For all other pages, take the original title (eg, "Admin interface &raquo; Smul")
        // and just replace the old brand name with our new one.
        $new_title = str_replace('Smul', WL_BRAND_NAME, $original_title);
        $new_title = str_replace('YOURLS', WL_BRAND_NAME, $new_title); // Just in case
        return $new_title;
    }
}
yourls_add_filter('html_title', 'wl_custom_title', 99);


// Customize the admin footer text
function wl_footer_text($text) {
    $current_year = date("Y");
    return '© ' . $current_year . ' ' . WL_BRAND_NAME . '. All Rights Reserved.';
}
yourls_add_filter('html_footer_text', 'wl_footer_text');


// Add custom favicon and CSS to hide the original header
function wl_add_custom_head_elements() {
    $plugin_dir_path = __DIR__;
    $plugin_url = yourls_plugin_url($plugin_dir_path);


    // 1. Add custom stylesheet with cache-busting (the correct way)
    $css_file_path = $plugin_dir_path . '/style.css';
    $css_file_url = $plugin_url . '/style.css';
    if (file_exists($css_file_path)) {
        $version = filemtime($css_file_path); // Get modification time for cache busting
        echo '<link rel="stylesheet" href="' . $css_file_url . '?v=' . $version . '" type="text/css" media="screen" />';
    }

    // 2. Add inline CSS to hide the original header and style our new one
    echo <<<CSS
    <style type="text/css">
        /* This hides the original <header> element from your file */
        body > #wrap > header[role="banner"] {
            display: none !important;
        }

        /* Center the new logo on the login page */
        #custom-login-header {
            text-align: center;
            padding: 20px 0;
        }
        
        /* Style the new header on internal admin pages */
        #custom-admin-header {
            float: left;
            margin: 5px 20px 0 20px;
        }
    </style>
CSS;
}
yourls_add_action('html_head', 'wl_add_custom_head_elements');

// =============================================================================
// D. GLOBAL TEXT REPLACEMENT
// =============================================================================

/**
 * Catches all translatable strings and replaces 'YOURLS' with the custom brand name.
 * This uses the 'gettext' filter, which is applied to all strings
 * passed through YOURLS's translation functions like yourls__() and yourls_e().
 *
 * @param string $translation The translated text.
 * @return string The modified text.
 */
function wl_translate_text($translation) {
    // Use case-insensitive replace to catch 'YOURLS', 'Yourls', 'yourls', etc.
    return str_ireplace('YOURLS', WL_BRAND_NAME, $translation);
}
// Add the filter with a priority of 10 and accepting 1 argument.
yourls_add_filter('gettext', 'wl_translate_text', 10, 1);
