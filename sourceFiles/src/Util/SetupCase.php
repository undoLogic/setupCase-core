<?php

namespace App\Util;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\Table;
use Cake\Datasource\FactoryLocator;
use ReflectionMethod;
use DOMDocument;
use DOMXPath;

class SetupCase {


    function createPdf($url, $filename)
    {



        $this->setSize(8.5, 11);

        $cmd = "wkhtmltopdf "

            . " --dpi " . $this->dpi . " --page-width " . $this->width_pixels . " --page-height " . $this->height_pixels
            . " --margin-top 0 --margin-right 0 --margin-bottom 0 --margin-left 0 " . $url . " " . TMP . $filename;
        if (isset($_GET['debug'])) {
            die($cmd); //used to debug and get the link for testing
        }

        exec($cmd);
        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename=\"$filename\"");
        readfile(TMP . $filename);

        exit;
    }

    var $dpi = 30;
    var $factor = 1;
    function setSize($width, $height)
    {
        $this->width_pixels = $width * (($this->dpi * $this->factor) + 0); //1 in = 96 px / add fine adjustments to the end
        $this->height_pixels = $height * (($this->dpi * $this->factor) + 0); //1 in = 96 px
    }












    var $liveDomains = [
        //domain => //NAME
    ];

    //environments
    public function env_isLive() {
        $currentDomain = $_SERVER['HTTP_HOST'];

        if (isset($this->liveDomains[ $currentDomain ])) {
            if ($this->liveDomains[ $currentDomain ] == 'LIVE') {
                return true;
            }
        }
        return false;
    }
    public function env_getActive() {
        // allows to define rules how to determine where your software is located
        // you can change this file as you wish

        $currentDomain = $_SERVER['HTTP_HOST'];

        if (isset($this->liveDomains[ $currentDomain ])) {
            return $this->liveDomains[ $currentDomain ];
        } else {
            dd('ERROR: unknown location');
        }

    }


    public static function isLanguageAllowed($currentLang, $currentWebsite, $websiteLanguages) {
        foreach ($websiteLanguages as $eachWebsite => $langs) {
            if ($currentWebsite == $eachWebsite) {
                if (in_array($currentLang, $langs)) {
                    //this language is allowed
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    public static function getEnglishLink($oldPath) {
        //$oldPath = $this->request->getUri()->getPath();
        return str_replace('es', 'en', $oldPath);
    }


    //is LIVE
    public static function isLIVE($currentDomain, $liveDomains) {
        if (in_array($currentDomain, $liveDomains)) {
            return true;
        }
        return false;
    }



    //SECURE COMPONENT
    var $ths; //$this

    /*
     * When testing is detected in the path, require a basic password to access
     * add to app_controller in beforeFilter
     * $this->Secure->requirePasswordExcept(array('www.website.com', 'website.com'), $_SERVER, $this->Session [,1234]);
     */
    function requirePasswordExcept($exceptions, $server, $session, $password = false) {

        //pr ($exceptions);
        //exit;
        if ($password) {
            //we are using a custom password
            die ('not implented yet');
        } else {

            $passwords = array(
                date('m').date('m'),
                date('m')
            );

            //pr ($server['HTTP_HOST']);

            if (isset($server['HTTP_HOST'])) {

                if (in_array($server['HTTP_HOST'], $exceptions)) {

                    //this is an exception, so let's not enforce a password
                } else {
                    //let's ensure a password

                    if (isset($_GET['login'])) {
                        if ($_GET['login'] == 'logout') {
                            $session->write('TempAccessGiven', 'FALSE');
                            $this->showForm();
                        }
                    }

                    //this is a testing site
                    $isAllowed = $session->read('TempAccessGiven');

                    if ($isAllowed == 'TRUE') {
                        //they are allowed
                    } else {
                        //we need to see if we are allowed.
                        if (isset($_GET['login'])) {
                            if (in_array($_GET['login'], $passwords)) {
                                //they have the right password
                                $session->write('TempAccessGiven', 'TRUE');
                                return 2;

                            } elseif ($_GET['login'] == 'logout') {
                                die ('Logged OUT');
                            } else {
                                die ('NO ACCESS: CODE not correct');
                            }
                        } else {

                            $this->showForm();

                            die ('NO ACCESS: Code Require to access this site');

                        }

                    }


                }
            } else {
                //no http host
            }
        }

    }

    public function requireSSLExcept($exceptions, $ths) {
        $this->ths = $ths;

        if (in_array($_SERVER[ 'SERVER_NAME' ], $exceptions)) {
            //ignore ssl on this host
        } else {
            //if we are NOT ssl activate ssl
            if (!$this->__isSSL()) {
                $this->__redirectSSL();
            } else {
                //already ssl - do nothing
            }
        }
    }

    ////public
    public function forceSSL($ths) {
        $this->ths = $ths;
        if ($this->__isLocal()) {
            return FALSE; //we are local, no ssl
        } elseif (!$this->__isSSL()) {
            $this->__redirectSSL();
        }
    }

    public function forceNoSSL($ths, $path = false) {
        $this->ths = $ths;
        if ($this->__isLocal()) {
            return FALSE; //we are local, no ssl
        } elseif ($this->__isSSL()) {
            $this->__redirectNoSSL($path);
        }
    }

    function __isSSL() {
        if (env('SERVER_PORT') == 443) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function __redirectSSL() {
        $this->ths->redirect('https://' . $this->__url());
    }

    function __redirectNoSSL($path) {
        $this->ths->redirect('http://' . $this->__url($path));
    }

    function __url($path = '') {
        $path = $path ? $path : env('REQUEST_URI');
        return env('SERVER_NAME') . $path;
    }

    function __isLocal() {
        if ($_SERVER[ 'SERVER_NAME' ] == 'localhost') {
            return TRUE;
        }
        return FALSE;
    }

    function assureCorrectSubDomain($ignore, $shouldBe, $ths) {
        if ($this->__isLocal()) {
            return true;//we are local it is ok
        } else {
            if (in_array($_SERVER['HTTP_HOST'], $ignore)) {
                //this domain is ignored
                return true;
            } else {
                if (in_array($_SERVER['HTTP_HOST'], $shouldBe)) {
                    return true;
                } else {
                    //let's redirect
                    $first = reset($shouldBe);
                    $ths->redirect('http://'.$first.'/'.$ths->params->url, 301);
                }
            }
        }
    }

    function showForm() {
        $c = '';
        $c .= '<div style="width: 300px;">';
        $c .= '<form action="" method="GET">';
        $c .= '<input name="login"/>';
        $c .= '</form>';
        $c .= '</div>';
        echo $c;
    }


    public static function sendEmail(
        $to,
        $template,
        $from,
        $subject,
        array $vars,
        $cc = false,
        array $attachments = []
    ): bool {
        try {
            $mailer = new Mailer('default');
            $env = Configure::read('App.current_env_profile');

            if ($env === 'LIVE') {

                die('LIVE EMAIL SENDING !!!! (remove when ready to go live)');

                $mailer->setTo($to);

                if ($cc) {
                    $mailer->setCc($cc);
                }
                $mailer->setFrom([$from => 'SetupCase']);
            } else {
                $mailer->setTo('testing@undoweb.com');
                $mailer->setFrom([$from => 'TESTING Emails']);
            }

            $mailer
                ->setSubject($subject)
                ->setEmailFormat('both')
                ->setViewVars($vars)
                ->viewBuilder()
                ->setTemplate($template)
                ->setLayout('default');

            // Attachments (safe)
            if (!empty($attachments)) {
                $mailer->setAttachments($attachments);
            }

            $mailer->send();

            // If we reach here, SMTP accepted the message
            return true;

        } catch (\Throwable $e) {

            // Log, but do NOT block caller
            Log::error('Email send failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }




    public static function extractFunction($class, $functionName)
    {

        $ref = new ReflectionMethod($class, $functionName);
        $code = implode("", array_slice(file($ref->getFileName()), $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine()));
        $code = preg_replace('/^\s*\/\/\s*IGNORE\b[\s\S]*?\/\/\s*IGNORE-END.*$/m', '', $code);

        return $code;
    }

    public static function getActiveMonth($currentDate = false)
    {

        if (!$currentDate) {
            $currentDate = self::getDate('Y-m-d H:i:s');
        }

        $thirdThurs = self::getThirdThursday($currentDate);

        $prevSunday = date('Y-m-d', strtotime('previous monday', strtotime($thirdThurs)));
        //$nextSunday = date('Y-m-d', strtotime('next sunday', strtotime($thirdThurs)));

        $prevSunday = $prevSunday . ' 00:00:00';
        $showTime = $thirdThurs . ' 13:00:00';

        if (strtotime($currentDate) < strtotime($prevSunday)) {
            //last month
            $cuttOff = date('Y-M', strtotime($currentDate));

            $cuttOffPrev = date('Y-M', strtotime('-1 months', strtotime($currentDate)));

            $nextClosingDate = $prevSunday;

            $closingDate = date('Y-m-d H:i:s', strtotime('previous monday', strtotime($thirdThurs)));

        } else {
            //this month
            //fix weird glitch end of month giving +2 months
            $currentDateCalc = date('Y-m-01', strtotime($currentDate));
            $cuttOff = date('Y-M', strtotime('+1 month', strtotime($currentDateCalc)));

            //$cuttOff = date('Y-M', strtotime($currentDate));
            $cuttOffPrev = date('Y-M', strtotime($currentDateCalc));

            $nextThirdThurs = self::getThirdThursday(date('Y-m-d', strtotime('+1 month', strtotime($currentDateCalc))));
            $nextClosingDate = date('Y-m-d H:i:s', strtotime('previous monday', strtotime($nextThirdThurs)));
            $closingDate = date('Y-m-d H:i:s', strtotime('previous monday', strtotime($thirdThurs)));

        }

        if (
            strtotime($currentDate) < strtotime($closingDate)
        ) {
            $registrationActive = true;
            $msg = 1;
        } else if (strtotime($currentDate) >= strtotime($showTime)) {
            $registrationActive = true;
            $msg = 2;
        } else {
            $registrationActive = false;
            $msg = 3;
        }

        $difference = strtotime($nextClosingDate) - strtotime($currentDate);
        $days = $difference / 86400;
        if ($days > 2) {
            $nextClosingDateDaysRemain = "in " . floor($days) . " days";
        } else if ($days > 1) {
            $nextClosingDateDaysRemain = 'Tomorrow';
        } else if ($days > 0) {
            $nextClosingDateDaysRemain = 'Today';
        } else {
            $nextClosingDateDaysRemain = $days;
        }

        return array(
            'currentDate' => $currentDate,
            'cutOffPrev' => strtoupper($cuttOffPrev),
            'cutOffPrev_year_and_month' => date('Y-m-01', strtotime($cuttOffPrev)),
            'cutOff' => strtoupper($cuttOff),
            'cutOff_year_and_month' => date('Y-m-01', strtotime($cuttOff)),
            'thirdThurs' => $thirdThurs,
            'nextClosingDate' => $nextClosingDate,
            'nextClosingDateDaysRemain' => $nextClosingDateDaysRemain,
            'closingDate' => $closingDate,
            'showTime' => $showTime,
            'registrationActive' => $registrationActive,
            'votingActive' => $registrationActive, //currently the same as registration
            'msg' => $msg
        );
    }// activeMonth

    public function getNextThirdThursday($date){

        $thirdThur = $this->getThirdThursday($date);

        if (strtotime($thirdThur) < strtotime($date)) {
            //it's last month, let's get the next month
            $nextMonth = date('Y-m-d', strtotime('+1 month', strtotime($date)));

            $thirdThur = $this->getThirdThursday($nextMonth);
        }

        return $thirdThur;
    }

    public static function getDate($format = 'Y-m-d H:i:s')
    {
        if (isset($_GET['testDate'])) {
            return date($format, strtotime($_GET['testDate']));
        } else {
            return date($format);
        }
    }// getDate

    public function getThirdThursday($date){

        //$date = '2022-11-29';
        // pr($this->thirdThurs($date)); exit;
        $today = date('l', strtotime($date));


        $first = date('Y-m-01', strtotime("$date"));
        $last = date('Y-m-31', strtotime("$date"));

        $date = $first;
        $count = 0;

        do {

            if(date('l', strtotime($date)) === 'Thursday'){
                $count++;
            }
            if($count == 3){
                return $date;
            }

            $date = date('Y-m-d', strtotime($date. "+1day"));


        } while ($date <= $last);

        return false;

    }

    public static function getEncrypt($decrypted) {
        return base64_encode($decrypted);
    }

    public static function getDecrypt($encrypted){
        return base64_decode($encrypted);
    }

    public static function updateCase(){
        require_once(WWW_ROOT.DS.'updateCase-v5.php');
        $updateCase = new UpdateCase([
            'debug' => true,
            'variant_id' => 227,
            'version' => 5,
            'lang' => 'en' ]);
        return $updateCase;

    }

    public static function FilterValidateEmail($emailOrg){

        //all lower case
        $emailOrg = strtolower($emailOrg);

        $email = '';

        $parts = explode('@', $emailOrg);

        if ($parts[1] == 'gmail.com' || $parts[1] == 'gmail.ca') {
            $email = str_replace('.', '', $parts[0]) . '@' . $parts[1];

            // Use a regular expression to remove the +alias part
            $email = preg_replace('/\+[^@]+/', '', $email);

        } else {
            $email = $emailOrg; //not a gmail
        }

        //remove spaces
        $email = str_replace(' ',"", $email);

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            return false; //not a valid email
        }else{
            return $email;
        }

    }//FilterValidateEmail

    public static function prepareString($string){
        $string = str_replace(' ',"", $string);
        $string = strtolower($string);

        return trim($string);

    }

    public static function writeToLog($level, $msg, $newLine = true) {
        $levels = explode(',', $level);
        foreach ($levels as $eachLevel) {
            Log::write($eachLevel, $msg);
        }
    }





    public static function styleTables(string $html): string
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');

        // Load HTML safely
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new DOMXPath($dom);

        $borderColor = '#d0d0d0';
        $headerBg    = '#f4f4f4';
        $textColor  = '#333333';

        // Tables
        foreach ($xpath->query('//table') as $table) {
            self::styleTables_appendStyle($table, 'border-collapse:collapse;width:100%;');
        }

        // Headers
        foreach ($xpath->query('//th') as $th) {
            self::styleTables_appendStyle(
                $th,
                "border:1px solid {$borderColor};padding:8px;background:{$headerBg};color:{$textColor};font-weight:bold;"
            );
        }

        // Cells
        foreach ($xpath->query('//td') as $td) {
            self::styleTables_appendStyle(
                $td,
                "border:1px solid {$borderColor};padding:8px;color:{$textColor};"
            );
        }

        return $dom->saveHTML();
    }

    private static function styleTables_appendStyle($node, string $style): void
    {
        $existing = $node->getAttribute('style');
        $node->setAttribute(
            'style',
            rtrim($existing, ';') . ';' . $style
        );
    }



}
