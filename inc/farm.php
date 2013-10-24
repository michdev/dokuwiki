<?php
/**
 * This overwrites DOKU_CONF. Each animal gets its own configuration and data directory.
 * This can be used together with preload.php. See preload.php.dist for an example setup.
 * For more information see http://www.dokuwiki.org/farms.
 *
 * The farm directory (constant DOKU_FARMDIR) can be any directory and needs to be set.
 * Animals are direct subdirectories of the farm directory.
 * There are two different approaches:
 *  * An .htaccess based setup can use any animal directory name:
 *    http://example.org/<path_to_farm>/subdir/ will need the subdirectory '$farm/subdir/'.
 *  * A virtual host based setup needs animal directory names which have to reflect
 *    the domain name: If an animal resides in http://www.example.org:8080/mysite/test/,
 *    directories that will match range from '$farm/8080.www.example.org.mysite.test/'
 *    to a simple '$farm/domain/'.
 *
 * @author Anika Henke <anika@selfthinker.org>
 * @author Michael Klier <chi@chimeric.de>
 * @author Christopher Smith <chris@jalakai.co.uk>
 * @author virtual host part of farm_confpath() based on conf_path() from Drupal.org's /includes/bootstrap.inc
 *   (see https://github.com/drupal/drupal/blob/7.x/includes/bootstrap.inc#L537)
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

// Save custom DOKU_CONF if defined in preload.php (conf dir security)
if(defined('DOKU_CONF')) define('DOKU_CUSTOM_CONF', DOKU_CONF);

// DOKU_FARMDIR needs to be set in preload.php, here the fallback is the same as DOKU_INC would be (if it was set already)
if(!defined('DOKU_FARMDIR')) define('DOKU_FARMDIR', fullpath(dirname(__FILE__).'/../').'/');
if(!defined('DOKU_CONF')) define('DOKU_CONF', farm_confpath(DOKU_FARMDIR));
if(!defined('DOKU_FARM')) define('DOKU_FARM', false);


/**
 * Find the appropriate configuration directory.
 *
 * If the .htaccess based setup is used, the configuration directory can be
 * any subdirectory of the farm directory.
 *
 * Otherwise try finding a matching configuration directory by stripping the
 * website's hostname from left to right and pathname from right to left. The
 * first configuration file found will be used; the remaining will ignored.
 * If no configuration file is found, return the default confdir './conf'.
 */
function farm_confpath($farm) {

    // htaccess based or cli
    // cli usage example: animal=your_animal bin/indexer.php
    if(isset($_REQUEST['animal']) || ('cli' == php_sapi_name() && isset($_SERVER['animal']))) {
        $mode = isset($_REQUEST['animal']) ? 'htaccess' : 'cli';
        $animal = $mode == 'htaccess' ? $_REQUEST['animal'] : $_SERVER['animal'];
        // check that $animal is a string and just a directory name and not a path
        if (!is_string($animal) || strpbrk($animal, '\\/') !== false)
            nice_die('Sorry! Invalid animal name!');
        if(!is_dir($farm.'/'.$animal))
            nice_die("Sorry! This Wiki doesn't exist!");
        if(!defined('DOKU_FARM')) define('DOKU_FARM', $mode);
        return $farm.'/'.$animal.'/conf/';
    }

    // virtual host based
    $uri = explode('/', $_SERVER['SCRIPT_NAME'] ? $_SERVER['SCRIPT_NAME'] : $_SERVER['SCRIPT_FILENAME']);
    $server = explode('.', implode('.', array_reverse(explode(':', rtrim($_SERVER['HTTP_HOST'], '.')))));
    for ($i = count($uri) - 1; $i > 0; $i--) {
        for ($j = count($server); $j > 0; $j--) {
            $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
            if(is_dir("$farm/$dir/conf/")) {
                if(!defined('DOKU_FARM')) define('DOKU_FARM', 'virtual');
                return "$farm/$dir/conf/";
            }
        }
    }

    // default conf directory in farm
    if(is_dir("$farm/default/conf/")) {
        if(!defined('DOKU_FARM')) define('DOKU_FARM', 'default');
        return "$farm/default/conf/";
    }
    // farmer
    if(defined('DOKU_CUSTOM_CONF'))
        return('DOKU_CUSTOM_CONF');
        
    return DOKU_INC.'conf/';
}

// default config files for dokuwiki
if(defined('DOKU_CUSTOM_CONF'))
    $default_conf = DOKU_CUST_CONF;
else
    $default_conf = DOKU_CONF;

// config file for farmer and animals
$local_conf = farm_confpath(DOKU_FARMDIR);

/* Use default config files and local animal config files */
$config_cascade = array(
    'main' => array(
        'default' => array($default_conf.'dokuwiki.php'),
        'local' => array($local_conf.'local.php'),
        'protected' => array($local_conf.'local.protected.php'),
    ),
    'acronyms' => array(
        'default' => array($default_conf.'acronyms.conf'),
        'local' => array($local_conf.'acronyms.local.conf'),
    ),
    'entities' => array(
        'default' => array($default_conf.'entities.conf'),
        'local' => array($local_conf.'entities.local.conf'),
    ),
    'interwiki' => array(
        'default' => array($default_conf.'interwiki.conf'),
        'local' => array($local_conf.'interwiki.local.conf'),
    ),
    'license' => array(
        'default' => array($default_conf.'license.php'),
        'local' => array($local_conf.'license.local.php'),
    ),
    'mediameta' => array(
        'default' => array($default_conf.'mediameta.php'),
        'local' => array($local_conf.'mediameta.local.php'),
    ),
    'mime' => array(
        'default' => array($default_conf.'mime.conf'),
        'local' => array($local_conf.'mime.local.conf'),
    ),
    'scheme' => array(
        'default' => array($default_conf.'scheme.conf'),
        'local' => array($local_conf.'scheme.local.conf'),
    ),
    'smileys' => array(
        'default' => array($default_conf.'smileys.conf'),
        'local' => array($local_conf.'smileys.local.conf'),
    ),
    'wordblock' => array(
        'default' => array($default_conf.'wordblock.conf'),
        'local' => array($local_conf.'wordblock.local.conf'),
    ),
    'acl' => array(
        'default' => $local_conf.'acl.auth.php',
    ),
    'plainauth.users' => array(
        'default' => $local_conf.'users.auth.php',
    ),
    'plugins' => array( // needed since Angua
        'default' => array($default_conf.'plugins.php'),
        'local' => array($local_conf.'plugins.local.php'),
        'protected' => array(
            $default_conf.'plugins.required.php',
            $local_conf.'plugins.protected.php',
        ),
    ),
    'userstyle' => array(
        'default' => $local_conf.'userstyle.css', // 'default' was renamed to 'screen' on 2011-02-26, so will be deprecated in the next version
        'screen' => $local_conf.'userstyle.css',
        'rtl' => $local_conf.'userrtl.css', // deprecated since version after 2012-04-09
        'print' => $local_conf.'userprint.css',
        'feed' => $local_conf.'userfeed.css',
        'all' => $local_conf.'userall.css',
    ),
    'userscript' => array(
        'default' => $local_conf.'userscript.js'
    ),
);
