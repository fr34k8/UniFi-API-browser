<?php
/**
 * Copyright © 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
 */

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

/**
 * If we are using HTTPS, we need to set secure cookies.
 */
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    session_set_cookie_params(0, '/', '', true, true);
}

session_start();

/**
 * We check whether the user has requested to clear (force expiry) the PHP session, if so, we
 * clear the session and reload the page without the query string.
 * This feature can be useful when login errors occur, mostly after upgrades or credential changes.
 */
if (isset($_GET['reset_session']) && $_GET['reset_session']) {
    $_SESSION = [];
    session_unset();
    session_destroy();
    session_start();
    $current_url = $_SERVER['REQUEST_URI'];
    $current_url = strtok($current_url, '?');
    header("refresh: 0; url = $current_url");
}

/**
 * load required packages using the composer autoloader
 */
require_once 'vendor/autoload.php';

/**
 * include the common functions file
 *
 * @var string $curl_info
 * @var string $unknown_string
 * @var array $about_modal_params
 */
require_once 'common.php';

/**
 * load the file containing the collections for the menu options
 *
 * @var array $collections
 */
require_once 'collections.php';

/**
 * initialize the Twig loader early on in case we need to render the error page
 */
$loader = new FilesystemLoader('templates');
$twig   = new Environment($loader);

/**
 * load the configuration file, if readable, if not, stop and display an error message
 *
 * @var array $controllers
 * @var bool $debug
 * @var string $theme
 * @var string $navbar_class
 * @var string $navbar_bg_class
 * @var bool $about_modal_params
 */
if (is_file('config/config.php') && is_readable('config/config.php')) {
    require_once 'config/config.php';
} else {
    /**
     * render the config error page
     */
    try {
        echo $twig->render('config_error.html.twig', [
            'error_message' => 'The file <b>config/config.php</b> does not exist! Please create one based on the <b>config/config-template.php</b> file!<br>',
        ]);
    } catch (LoaderError $e) {
        error_log('Twig LoaderError: ' . $e->getMessage());
    } catch (RuntimeError $e) {
        error_log('Twig RuntimeError: ' . $e->getMessage());
    } catch (SyntaxError $e) {
        error_log('Twig SyntaxError: ' . $e->getMessage());
    }

    exit;
}

/**
 * inject Twig global variables for use across the templates
 */
$twig->addGlobal('tool_version', TOOL_VERSION);
$twig->addGlobal('debug', $debug);
$twig->addGlobal('session', $_SESSION);
$twig->addGlobal('navbar_class', $navbar_class);
$twig->addGlobal('navbar_bg_class', $navbar_bg_class);
$twig->addGlobal('about_modal_params', $about_modal_params);

/**
 * check whether the required PHP curl module is available, if not, stop and display an error message
 */
if (!function_exists('curl_version')) {
    /**
     * render the config error page
     */
    try {
        echo $twig->render('config_error.html.twig', [
            'error_message' => 'The <b>PHP curl</b> module is not installed! Please correct this before proceeding!<br>',
        ]);
    } catch (LoaderError $e) {
        error_log('Twig LoaderError: ' . $e->getMessage());
    } catch (RuntimeError $e) {
        error_log('Twig RuntimeError: ' . $e->getMessage());
    } catch (SyntaxError $e) {
        error_log('Twig SyntaxError: ' . $e->getMessage());
    }

    exit;
}

/**
 * check whether the minimum required PHP version (5.6.0) is met
 * - if not, stop and display an error message
 */
if (version_compare(PHP_VERSION, '5.6.0') < 0) {
    /**
     * render the config error page
     */
    try {
        echo $twig->render('config_error.html.twig', [
            'error_message' => 'The current PHP version (' . PHP_VERSION . ') does not meet the minimum required version which is 5.6.0. Please upgrade before proceeding!<br>',
        ]);
    } catch (LoaderError $e) {
        error_log('Twig LoaderError: ' . $e->getMessage());
    } catch (RuntimeError $e) {
        error_log('Twig RuntimeError: ' . $e->getMessage());
    } catch (SyntaxError $e) {
        error_log('Twig SyntaxError: ' . $e->getMessage());
    }

    exit;
}

/**
 * load the file containing user accounts, if readable
 */
if (is_file('config/users.php') && is_readable('config/users.php')) {
    require_once 'config/users.php';

    $user_authentication = true;

    if (!isset($users) || !is_array($users) || count($users) === 0) {
        $user_authentication = false;
        error_log('The $users array in the config/users.php file does not exist or is empty, proceeding without user authentication.');
    }
} else {
    $user_authentication = false;
    error_log('The file config/users.php does not exist, proceeding without user authentication.');
}

/**
 * if needed, we request the user to log in
 */
if ($user_authentication && (empty($_SESSION['logged_in']))) {
    $login_error = '';

    if (!empty($_SESSION['login_error_message'])) {
        $login_error                     = $_SESSION['login_error_message'];
        $_SESSION['login_error_message'] = '';
    }

    /**
     * render the login page
     */
    try {
        echo $twig->render('login.html.twig', [
            'login_error' => $login_error,
        ]);
    } catch (LoaderError $e) {
        error_log('Twig LoaderError: ' . $e->getMessage());
    } catch (RuntimeError $e) {
        error_log('Twig RuntimeError: ' . $e->getMessage());
    } catch (SyntaxError $e) {
        error_log('Twig SyntaxError: ' . $e->getMessage());
    }

    exit;
}

if (empty($_SESSION['controller'])) {
    /**
     * the user needs to select a controller first, so we render the appropriate template
     */
    try {
        echo $twig->render('controller_select.html.twig', [
            'controllers' => $controllers,
        ]);
    } catch (LoaderError $e) {
        error_log('Twig LoaderError: ' . $e->getMessage());
    } catch (RuntimeError $e) {
        error_log('Twig RuntimeError: ' . $e->getMessage());
    } catch (SyntaxError $e) {
        error_log('Twig SyntaxError: ' . $e->getMessage());
    }
} else {
    /**
     * proceed and render the collections_view template
     */
    try {
        echo $twig->render('collections_view.html.twig', [
            'controllers' => $controllers,
            'controller'  => $_SESSION['controller'],
            'collections' => $collections,
        ]);
    } catch (LoaderError $e) {
        error_log('Twig LoaderError: ' . $e->getMessage());
    } catch (RuntimeError $e) {
        error_log('Twig RuntimeError: ' . $e->getMessage());
    } catch (SyntaxError $e) {
        error_log('Twig SyntaxError: ' . $e->getMessage());
    }
}

exit;
