<?php

// phpcs:ignoreFile

/**
 * @file
 * Local development override configuration feature.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/default/settings.local.php'. Then, go to the bottom of
 * 'sites/default/settings.php' and uncomment the commented lines that mention
 * 'settings.local.php'.
 *
 * If you are using a site name in the path, such as 'sites/example.com', copy
 * this file to 'sites/example.com/settings.local.php', and uncomment the lines
 * at the bottom of 'sites/example.com/settings.php'.
 */

/**
 * Assertions.
 *
 * The Drupal project primarily uses runtime assertions to enforce the
 * expectations of the API by failing when incorrect calls are made by code
 * under development.
 *
 * @see http://php.net/assert
 * @see https://www.drupal.org/node/2492225
 *
 * If you are using PHP 7.0 it is strongly recommended that you set
 * zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
 * or runtime) on development machines and to 0 in production.
 *
 * @see https://wiki.php.net/rfc/expectations
 */
assert_options(ASSERT_ACTIVE, TRUE);
assert_options(ASSERT_EXCEPTION, TRUE);

/**
 * Enable custom development services.
 */
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/custom.services.yml';

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Disable the render cache.
 *
 * Note: you should test with the render cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the render cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Only use this setting once the site has been installed.
 */
# $settings['cache']['bins']['render'] = 'cache.backend.null';

/**
 * Disable caching for migrations.
 *
 * Uncomment the code below to only store migrations in memory and not in the
 * database. This makes it easier to develop custom migrations.
 */
# $settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';

/**
 * Disable Internal Page Cache.
 *
 * Note: you should test with Internal Page Cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the page cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Only use this setting once the site has been installed.
 */
# $settings['cache']['bins']['page'] = 'cache.backend.null';

/**
 * Disable Dynamic Page Cache.
 *
 * Note: you should test with Dynamic Page Cache enabled, to ensure the correct
 * cacheability metadata is present (and hence the expected behavior). However,
 * in the early stages of development, you may want to disable it.
 */
# $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

/**
 * Allow test modules and themes to be installed.
 *
 * Drupal ignores test modules and themes by default for performance reasons.
 * During development it can be useful to install test extensions for debugging
 * purposes.
 */
# $settings['extension_discovery_scan_tests'] = TRUE;

/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
#$settings['rebuild_access'] = TRUE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Exclude modules from configuration synchronization.
 *
 * On config export sync, no config or dependent config of any excluded module
 * is exported. On config import sync, any config of any installed excluded
 * module is ignored. In the exported configuration, it will be as if the
 * excluded module had never been installed. When syncing configuration, if an
 * excluded module is already installed, it will not be uninstalled by the
 * configuration synchronization, and dependent configuration will remain
 * intact. This affects only configuration synchronization; single import and
 * export of configuration are not affected.
 *
 * Drupal does not validate or sanity check the list of excluded modules. For
 * instance, it is your own responsibility to never exclude required modules,
 * because it would mean that the exported configuration can not be imported
 * anymore.
 *
 * This is an advanced feature and using it means opting out of some of the
 * guarantees the configuration synchronization provides. It is not recommended
 * to use this feature with modules that affect Drupal in a major way such as
 * the language or field module.
 */
# $settings['config_exclude_modules'] = ['devel', 'stage_file_proxy'];

/* Database settings */
$databases['default']['default'] = [
  'database' => getenv('DB_NAME'),
  'driver' => 'mysql',
  'host' => getenv('DB_HOST'),
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'password' => getenv('DB_PASSWORD'),
  'port' => getenv('DB_PORT'),
  'prefix' => '',
  'username' => getenv('DB_USER'),
];

$databases['default']['default']['init_commands'] = [
  'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
];

/* Trusted host settings */
$settings['trusted_host_patterns'] = [
  '^' . preg_quote(getenv('PROJECT_BASE_URL')) . '$',
];

/* Disable SMTP in local development */
#$config['system.mail']['interface']['default'] = 'php_mail';

/* Assign environment indicator values */
$env_name = getenv('ENV_NAME');
$env_fg = '#ffffff';
switch ($env_name) {
  case 'Local':
    $env_bg = '#198754';
    break;

  case 'Testing':
    $env_bg = '#087990';
    break;

  case 'Staging':
    $env_bg = '#0a58ca';
    break;

  case 'Production':
    $env_bg = '#b02a37';
    break;

  default:
    $env_name = 'undefined ENV_NAME';
    $env_bg = '#ff00bf';
    break;
}
$config['environment_indicator.indicator']['bg_color'] = $env_bg;
$config['environment_indicator.indicator']['fg_color'] = $env_fg;
$config['environment_indicator.indicator']['name'] = $env_name;

/* OpenID Connect settings */
#$config['openid_connect.settings']['user_login_display'] = 'hidden';

/* EUF IDP settings */
#$config['openid_connect.settings.eufidp']['enabled'] = FALSE;
#$config['openid_connect.settings.eufidp']['settings']['client_id'] = 'real_client_id';
#$config['openid_connect.settings.eufidp']['settings']['client_secret'] = 'real_client_secret';

/* MyAcademicID settings */
#$config['openid_connect.settings.myacademicid']['enabled'] = FALSE;
#$config['openid_connect.settings.myacademicid']['settings']['client_id'] = 'real_client_id';
#$config['openid_connect.settings.myacademicid']['settings']['client_secret'] = 'real_client_secret';

/* Cookies module settings */
#$config['cookies.config']['cookie_domain'] = getenv('PROJECT_BASE_URL');
