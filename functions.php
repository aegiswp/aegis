<?php
/**
 * Theme Functions
 *
 * Main entry point for the Aegis theme. This file is responsible for
 * bootstrapping the theme by loading necessary files and initializing the
 * Aegis Framework.
 *
 * @package    Aegis
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/aegis
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Pattern files call these helpers during registration on `init`.
require_once __DIR__ . '/src/helpers.php';

// Theme-level classes are bootstrapped via Composer files autoload (src/bootstrap.php).
// Register the framework immediately so after_setup_theme and init annotations attach in time.
Aegis::register( __FILE__ );

\Aegis\Utilities\I18n::register( \Aegis\Utilities\Data::from( __FILE__ ) );
