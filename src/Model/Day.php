<?php

declare(strict_types=1);

/**
 * Day
 */
class Model_Day extends RedBean_SimpleModel
{
    /**
     * Holds the csv parser
     *
     * @var \ParseCsv\Csv
     */
    private $parser;

    /**
     * Holds the mailer
     *
     * @var \PHPMailer\PHPMailer\PHPMailer
     */
    private $mailer;

    /**
     * Holds the damage codes and descriptions of damage1
     *
     * @var array
     */
    private $codes_damage_1 = [
        '01' => 'Freibank',
        '02' => 'Eber',
        '03' => 'Binneneber',
        '04' => 'Zwitter',
        '05' => 'Untauglich',
        '06' => 'Vorläufig',
        '07' => 'Kotelett',
        '08' => 'Hinterschinken',
        '09' => 'Schulter',
        '10' => 'Teilschaden'
    ];

    /**
     * Holds the damage codes and descriptions of damage2
     *
     * @var array
     */
    private $codes_damage_2 = [
        'L' => 'Leberschaden'
    ];

    /**
     * Read the data from a csv that Matthäus Classification Software produces.
     *
     * @param string $file the path/to/file to import stock from
     * @return RedBeanPHP\OODBBean
     */
    public function importFromCsv(\ParseCsv\Csv $parser, string $file): RedBeanPHP\OODBBean
    {
        $this->parser = $parser;

        $this->bean->count = 0;
        $this->parser->encoding('UTF-8', 'UTF-8');
        $this->parser->delimiter = ";";
        $this->parser->heading   = false;
        $this->parser->parse($file);

        foreach ($this->parser->data as $key => $row) {

            $this->bean->count++;
            $stock = R::dispense('stock');

            $stock->dateOfSlaughter = date_create_from_format('d.m.Y', $row[0])->format('Y-m-d');
            $stock->damage1         = ''; //set to empty string
            $stock->damage2         = ''; //set to empty string, null causes trouble
            $stock->number          = (int) $row[1];
            $stock->earmark         = strtoupper($row[4]);
            $stock->supplier        = strtoupper($row[3]);
            $stock->quality         = strtoupper($row[6]);
            $stock->weight          = (float)$row[10];
            $stock->mfa             = (float)$row[7];
            $stock->flesh           = (float)$row[8];
            $stock->speck           = (float)$row[9];
            $stock->tare            = (float)$row[11];
            $stock->vvvo            = $row[5];

            if (trim($row[12])) {
                $stock->damage1 = strtoupper(trim($row[12]));
            }

            if (trim($row[13])) {
                $befund = strtoupper(trim($row[13]));
                if (strpos($befund, WOBBIQAGRARDAMAGE_CODE_B_LIVER_GT5) !== false) {
                    $stock->damage2 = 'L';
                } else {
                    $stock->damage2 = '';
                }
            }

            if ($stock->quality == 'Z') {
                $stock->mfa = 0;
            }

            $this->bean->xownStockList[] = $stock;
        }
        $this->bean->dateOfSlaughter = $stock->dateOfSlaughter;
        R::store($this->bean);
        return $this->bean;
    }

    /**
     * Generate the data to be sent to iQ-Agrar using ADS format.
     *
     * @param string $company_ident identifiction of the company that sends ADS data
     * @param string $company_name name of the company (slaughterhouse) that sends the ADS data
     * @return RedBeanPHP\OODBBean
     */
    public function generateADS(string $company_ident, string $company_name): RedBeanPHP\OODBBean
    {
        $ts = time();

        $this->bean->companyIdent = $company_ident;
        $this->bean->companyName  = $company_name;
        $this->bean->ts           = $ts;

        $file = '';

        // ADIS header
        $file .= "DH990001000000000800090000208000900003080009000040600090000624000900009080" . "\r\n";
        $header = [
            'VH990001',
            str_pad("DD:", 8, " ", STR_PAD_RIGHT),
            str_pad("1996", 8, " ", STR_PAD_RIGHT),
            date('Ymd', $ts),
            date('hms', $ts),
            str_pad($company_name, 24, " ", STR_PAD_RIGHT),
            str_pad('AGRO2017', 8, " ", STR_PAD_RIGHT)
        ];
        $file .= implode($header) . "\r\n";

        // ADIS data stock
        $file .= "DN61010100610301140006103140800061031015000610308140006103150520061031603100610319031006103180310061032002000610014150" . "\r\n";

        // cycle through all our piggies
        $stocks = $this->bean->with(' ORDER BY earmark, mfa DESC')->xownStockList;
        foreach ($stocks as $id => $stock) {
            $data = [
                'VN610101',
                str_pad($company_ident, 14, " ", STR_PAD_LEFT),
                str_replace("-", "", $stock->dateOfSlaughter),
                str_pad($stock->number, 15, " ", STR_PAD_LEFT),
                str_pad($stock->earmark, 14, " ", STR_PAD_LEFT),
                str_pad((string) ($stock->weight * 100), 5, "0", STR_PAD_LEFT),
                str_pad((string) ($stock->mfa * 10), 3, "0", STR_PAD_LEFT),
                str_pad((string) ($stock->speck * 10), 3, "0", STR_PAD_LEFT),
                str_pad((string) ($stock->flesh * 10), 3, "0", STR_PAD_LEFT),
                str_pad($stock->quality, 2, " ", STR_PAD_LEFT),
                str_pad($stock->vvvo, 15, " ", STR_PAD_LEFT)
            ];
            $file .= implode($data) . "\r\n";
        }

        // ADIS data damages
        $file .= "DN6101050061030114000610314080006103101500061030904000610539500" . "\r\n";

        // cycle through all our damage1 stock
        $stocks = $this->bean->withCondition(" damage1 != '' ORDER BY earmark, mfa DESC")->xownStockList;
        foreach ($stocks as $id => $stock) {
            $damage_description = '';
            if (isset($this->codes_damage_1[$stock->damage1])) {
                $damage_description = $this->codes_damage_1[$stock->damage1];
            }
            $data = [
                'VN610105',
                str_pad($company_ident, 14, " ", STR_PAD_LEFT),
                str_replace("-", "", $stock->dateOfSlaughter),
                str_pad($stock->number, 15, " ", STR_PAD_LEFT),
                str_pad($stock->damage1, 4, " ", STR_PAD_LEFT),
                str_pad($damage_description, 50, " ", STR_PAD_LEFT)
            ];
            $file .= implode($data) . "\r\n";
        }

        // cycle through all our damage2 stock
        $stocks = $this->bean->withCondition(" damage2 != '' ORDER BY earmark, mfa DESC")->xownStockList;
        foreach ($stocks as $id => $stock) {
            $damage_description = '';
            if (isset($this->codes_damage_2[$stock->damage2])) {
                $damage_description = $this->codes_damage_2[$stock->damage2];
            }
            $data = [
                'VN610105',
                str_pad($company_ident, 14, " ", STR_PAD_LEFT),
                str_replace("-", "", $stock->dateOfSlaughter),
                str_pad($stock->number, 15, " ", STR_PAD_LEFT),
                str_pad('9999', 4, " ", STR_PAD_LEFT), // use of 9999 because ADIS allows only numeric data
                str_pad($damage_description, 50, " ", STR_PAD_LEFT)
            ];
            $file .= implode($data) . "\r\n";
        }

        $file .= "ZN" . "\r\n";

        $this->bean->ADS = $file;
        R::store($this->bean);

        return $this->bean;
    }

    /**
     * Mail the ADS data to a certain e-mail address.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer
     * @param string $to receiver e-mail address
     * @param string $from sender e-mail address
     * @param string $host ip address of mail relay
     * @return RedBeanPHP\OODBBean
     */
    public function mailTo(\PHPMailer\PHPMailer\PHPMailer $mailer, string $to, string $from, string $host): RedBeanPHP\OODBBean
    {
        $this->mailer                 = $mailer;
        $this->bean->toEmailAddress   = $to;
        $this->bean->fromEmailAddress = $from;
        $this->bean->host             = $host;

        // semd the mail
        if ($host != 'localhost') {
            $mailer->SMTPDebug = 1; // Set debug mode, 1 = err/msg, 2 = msg
            /**
             * uncomment this block to get verbose error logging in your error log file
             */
            /*
            $mail->Debugoutput = function($str, $level) {
                error_log("debug level $level; message: $str");
            };
            */
            $mailer->isSMTP(); // Set mailer to use SMTP
            $mailer->Host = $host; // Specify main and backup server
            /*
            if ($smtp['auth']) {
                $mailer->SMTPAuth = true;                           // Enable SMTP authentication
            } else {
                $mailer->SMTPAuth = false;                          // Disable SMTP authentication
            }
            $mailer->Port = $smtp['port'];                          // SMTP port
            $mailer->Username = $smtp['user'];                      // SMTP username
            $mailer->Password = $smtp['password'];                  // SMTP password
            */
            $mailer->SMTPSecure = 'tls'; // Enable encryption, 'ssl' also accepted

            /**
             * @see https://stackoverflow.com/questions/30371910/phpmailer-generates-php-warning-stream-socket-enable-crypto-peer-certificate
             */
            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];
        }

        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($from, $this->bean->companyName);
        $mailer->addBCC($from, $this->bean->companyName);
        $mailer->addReplyTo($from, $this->bean->companyName);
        $mailer->addAddress($to);

        //$mailer->WordWarp = 50;
        $mailer->isHTML(true);
        $mailer->Subject = $this->bean->companyIdent . ' ' . $this->bean->dateOfSlaughter;
        $text            = "Schlachtdaten vom " . $this->bean->dateOfSlaughter;
        $mailer->Body    = "<h1>" . $text . "</h1>";
        $mailer->AltBody = $text;

        $mailer->addStringAttachment($this->bean->ads, $this->bean->companyIdent . '-' . $this->bean->dateOfSlaughter . '.ads');

        if ($mailer->send()) {
            $this->bean->sent = true;
        } else {
            $this->bean->err = $mailer->ErrorInfo;
        }

        R::store($this->bean);
        return $this->bean;
    }

    /**
     * Dispense the day bean
     */
    public function dispense()
    {
        $this->bean->sent = false;
    }
}
