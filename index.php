<?php

declare(strict_types=1);

/**
 * Send ADS formatted file to an e-mail address
 *
 * This cli php application lets you send an ADS formatted file to an e-mail address.
 * ADS is used by iQAgrar to collect data of stock from slaughterhouses in germany.
 */

/**
 * Autoloader
 */
require __DIR__ . '/vendor/autoload.php';

/**
 * Configuration
 */
require __DIR__ . '/config.php';

/**
 * Bootstrap
 */
require __DIR__ . '/bootstrap.php';

/**
 * Usage
 *
 * How this script can be used from the command line interface.
 */
$documentation = <<<DOC
iQAgrar ADS

This script generates a file in the ADS format required by iQAgrar service and sends it by e-mail.
To omit certain options set them in your config.php file. Make a copy of the config-example.php file
and fill in your own values. Now you do no longer need to pass them via options.

Usage: php -f index.php -- [-f] <file>

These options are available:

-c <company-name>    Name of the company (slaughterhouse)
-f <file>            Parse <file> and generate an ADS formatted file
-h                   This help
-i <company-ident>   Identification of the company (slaughterhouse)
-m <emailaddress>    E-Mailaddress where to send the ADS formatted file
-s <emailaddress>    E-Mailaddress from where to send the ADS formatted file
-t <ip-address>      IP address or name of the mail relay
                     Use localhost for your local mail service
DOC;

/**
 * Handle arguments from the command line interface
 */
$options = getopt("c:f:hi:m:s:t:");
if ($options === false) {
    echo "Failed to get options.\n";
}
//var_dump($options);

/**
 * Show help
 */
if (isset($options['h'])) {
    echo $documentation . "\n";
}

/**
 * Set company-ident
 */
$company_ident = WOBBIQAGRAR_DEFAULT_COMPANY_IDENT;
if (isset($options['i'])) {
    $company_ident = $options['i'];
}

/**
 * Set company-name
 */
$company_name = WOBBIQAGRAR_DEFAULT_COMPANY_NAME;
if (isset($options['c'])) {
    $company_name = $options['c'];
}

/**
 * Set to e-mail address
 */
$to_email_address = WOBBIQAGRAR_DEFAULT_RECEIVER_MAIL_ADDRESS;
if (isset($options['m'])) {
    $to_email_address = $options['m'];
}

/**
 * Set from e-mail address
 */
$from_email_address = WOBBIQAGRAR_DEFAULT_SENDER_MAIL_ADDRESS;
if (isset($options['s'])) {
    $from_email_address = $options['s'];
}

/**
 * Set IP address of mail relay
 */
$host_ip = WOBBIQAGRAR_DEFAULT_IP_ADDRESS_MAIL_HOST;
if (isset($options['t'])) {
    $host_ip = $options['t'];
}

/**
 * Import a .csv file
 */
if (isset($options['f'])) {

    $file   = $options['f'];
    if (!file_exists($file)) {
        echo 'File ' . $file . ' not found.' . "\n";
        exit();
    }
    $day    = R::dispense('day');
    $parser = new \ParseCsv\Csv();
    $mailer = new \PHPMailer\PHPMailer\PHPMailer();
    $day->importFromCsv($parser, $file)->generateADS($company_ident, $company_name)->mailTo($mailer, $to_email_address, $from_email_address, $host_ip);
    if (! $day->sent) {
        exit('The ADS file could not be e-mailed. Sorry.' . "\n" . $day->err);
    }
    echo "\niQAgrar Service\n\n";
    echo 'Imported file ' . $file . "\n";
    echo 'Company Ident: ' . $day->companyIdent . "\n";
    echo 'Company Name: ' . $day->companyName . "\n";
    echo 'Number of stock imported: ' . $day->count . "\n";
    echo 'Date of slaughtered stock imported: ' . $day->dateOfSlaughter . "\n";
    echo 'Mailed from: ' . $day->fromEmailAddress . "\n";
    echo 'Mailed to: ' . $day->toEmailAddress . "\n";
    echo 'Mailed via: ' . $day->host . "\n";
    echo "\nReady\n";
}
