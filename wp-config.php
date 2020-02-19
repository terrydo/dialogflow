<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'dialogflow');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'wK,[@X)yKN>w9ukDS?qJhjfLtO%Y2uV< bBxbM5t1qq]nJ../sM3J< ZyWa[ZoD|');
define('SECURE_AUTH_KEY',  'D=Ov8csE+vBMS2^0H-6D3q6+bgB3jFN?I5(Ud9kRGAG-gJ|Zvn~I}5:S~x=d{x8p');
define('LOGGED_IN_KEY',    'k>1pk:&7`ES2-Sx9]31-Gl@ Mon+ R;:*E0)Vv#hK-7Gh_uxnEF sOc;E$9JCg-8');
define('NONCE_KEY',        'hVyE?xmoI+{;R0*}#yQcpw^<&J8B|389Jhy {PP@h~JzC=r@$}_Z(B1H<s Yz3xI');
define('AUTH_SALT',        'h-L+eRH@^4X-_0l.3<gwjyDq55p?0WzXDQeA)>*OWrLe89vjdo;Yyx;>yNuTS&&Q');
define('SECURE_AUTH_SALT', 'B8&(~G,17y-mjT~w}D-I;yp`yKm+[_n9>QNaq!m|/Y h8fX/^v13HMdf_!6fP!cl');
define('LOGGED_IN_SALT',   '0}s%qkP GZP3gk|tL<:[^XwuM=#-=RrUF|r*{*1!pHar_QSlYr;fZH|_8F=}y!$,');
define('NONCE_SALT',       'V8h_)%;APlS+{q=_y>bP-%WnR2Z?qN/iWAIp%?%_!<E~4P;P!h8%%Z*#>}r^mpq9');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
