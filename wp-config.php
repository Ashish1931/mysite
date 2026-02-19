<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

define('KYLAS_API_TOKEN', '8a884d3b-0e85-435f-aaa4-523192e0f3ae:15803');


// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'mysite' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'Apple123!@#' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '2?lRceebJy]SBe?Ky|])j,b>pm2&q3gkH{MFv/E22a~&P0ik(J:d:0Tnkf`5zvb!' );
define( 'SECURE_AUTH_KEY',  '[EF}w <WcyOp;nt_?|#_<L>TbH=Dm>cK@P0Y ?EZYN4Rz(UC/IcxHo-O>#zcrBoM' );
define( 'LOGGED_IN_KEY',    'C!;MuuP+ j?UolN3[wvdd}/;!e6b%VP`UQt=<S`/-_B$-EW PVwwwM+Jf7M!-Et/' );
define( 'NONCE_KEY',        ']RQ^LaoA>-*v|qQq[9~y,A7[X;fNx,MFS_^FeJC|8:f^o[.cs:KHrco|XJdp+CFB' );
define( 'AUTH_SALT',        'nUI~{X$MB+JN:>Awx~w_1:U/Yq#E28.q,0hN.W[j^6;;ti:bBU2[nucW~^$8M<jU' );
define( 'SECURE_AUTH_SALT', 'Oi,pGy]WB{Gv1S7ax&PT>?k,1S6gNiS$.slp9Z^9_.n*hY3-u5iY{l>/CT^P#5&v' );
define( 'LOGGED_IN_SALT',   'Op [3YWv@N_4Jxx;V+z#~~]a89%HJN.|kMc;v?Y|t)DM(J!B+}hSPi3.-Xr.SM9?' );
define( 'NONCE_SALT',       ']Zjdt%70f~sm99dUErCKMzwMig6f!A`x_n0,W&AO3:n/=YtLh;KYh1.+*_:0;kGq' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);


/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';




