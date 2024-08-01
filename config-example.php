<?php
declare (strict_types = 1);
/**
 * Configuration
 */

/**
 * Database
 */
define('WOBBIQAGRAR_DB_HOST', 'localhost');
define('WOBBIQAGRAR_DB_NAME', 'database');
define('WOBBIQAGRAR_DB_USER', 'root');
define('WOBBIQAGRAR_DB_PASSWORD', 'secret');
define('WOBBIQAGRAR_DB_FREEZE_FLAG', true);

/**
 * Redbean Model namespace
 */
//define('REDBEAN_MODEL_PREFIX', '');

/**
 * Damage code for liver damages
 */
define('WOBBIQAGRARDAMAGE_CODE_B_LIVER_GT5', 'LE2');

/**
 * Default company name
 */
define('WOBBIQAGRAR_DEFAULT_COMPANY_NAME', 'ACME company');

/**
 * Default company ident
 */
define('WOBBIQAGRAR_DEFAULT_COMPANY_IDENT', 'XX 777');

/**
 * Default sender mail address
 */
define('WOBBIQAGRAR_DEFAULT_SENDER_MAIL_ADDRESS', 'from@example.com');

/**
 * Default receiver mail address
 */
define('WOBBIQAGRAR_DEFAULT_RECEIVER_MAIL_ADDRESS', 'to@example.com');

/**
 * Default IP address of mail host
 */
define('WOBBIQAGRAR_DEFAULT_IP_ADDRESS_MAIL_HOST', '127.0.0.1');
