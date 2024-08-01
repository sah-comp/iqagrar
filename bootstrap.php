<?php
declare (strict_types = 1);

/**
 * Bootstrap
 */

/**
 * Database connection
 */
R::setup('mysql:host=' . WOBBIQAGRAR_DB_HOST . ';dbname=' . WOBBIQAGRAR_DB_NAME, WOBBIQAGRAR_DB_USER, WOBBIQAGRAR_DB_PASSWORD);

/**
 * Set the "freeze" state of our database
 */
R::freeze(WOBBIQAGRAR_DB_FREEZE_FLAG);
