<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require "./google-api-php-client/vendor/autoload.php";
define('APPLICATION_NAME', 'School Mailer');
define('CREDENTIALS_PATH', APPPATH.'credentials.json');
define('CLIENT_SECRET_PATH', APPPATH.'secret.json');
define('SCOPES', implode(' ', array(
        'https://mail.google.com/',
        'https://www.googleapis.com/auth/gmail.compose',
        'https://www.googleapis.com/auth/gmail.modify',
        'https://www.googleapis.com/auth/gmail.readonly'
    )
));
if(!function_exists("mmail")){
    function mmail($sendto, $subject = null, $message = null, $sender = null){
        $client = getClient();
        $objGMail = new Google_Service_Gmail($client);
        $msg = new MainMail();
        $res = parsemail($sendto);
        $msg->setRecipientName($res['name'])->setRecipient($res['mail']);
        if($sender === null){
            $msg->setSenderName("DragonMailer")->setSender("anonymous.lgs.pupil@gmail.com");
        }
        else{
            $res = parsemail($sender);
            $msg->setSenderName($res['name'])->setSender($res['mail']);
        }
        if($subject !== null) $msg->setSubject($subject);
        if($message !== null) $msg->setMessage($message);
        //echo strtr(base64_decode($msg->base64()."=="), '-_', '+/');
        return in_array("SENT", $msg->send($objGMail)->labelIds);
    }
}

if(!class_exists("MainMail")){
    class MainMail{
        private $charset = "utf-8";
        private $receiver_name = "Anonymous";
        private $receiver;
        private $sender_name = "Anonymous";
        private $sender;
        private $subject = "Sender was to lazy to set an subject.";
        private $message = "<h1>This seems to be an test message</h1>";
        
        public function setCharset($value){
            $this->charset = $value;
            return $this;
        }
        
        public function setRecipientName($value){
            $this->receiver_name = $value;
            return $this;
        }
        
        public function setRecipient($value){
            $this->receiver = $value;
            return $this;
        }
        
        public function setSenderName($value){
            $this->sender_name = $value;
            return $this;
        }
        
        public function setSender($value){
            $this->sender = $value;
            return $this;
        }
        
        public function setSubject($value){
            $this->subject = $value;
            return $this;
        }
        
        public function setMessage($value){
            $this->message = $value;
            return $this;
        }
        
        public function send($gmail_service){
            $msg = new Google_Service_Gmail_Message();
            $msg->setRaw($this->base64());
            return $gmail_service->users_messages->send("me", $msg);
        }
        
        public function text(){
            $msg = "";
            $msg .= "To: ".parsemail(parsemail($this->receiver_name, $this->receiver))."\r\n";
            $msg .= "From: ".parsemail(parsemail($this->sender_name, $this->sender))."\r\n";
            $msg .= "Subject: =?".$this->charset."?B?".base64_encode($this->subject)."?=\r\n";
            $msg .= "MIME-Version: 1.0\r\n";
            $msg .= 'Content-Type: text/html; charset=' . $this->charset . "\r\n";
            $msg .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
            $msg .= $this->message. "\r\n";
            return $msg;
        }
        
        public function base64(){
            return rtrim(strtr(base64_encode($this->text()), '+/', '-_'), '=');
        }
    }
}

if(!function_exists("parsemail")){
    function parsemail($name, $mail = null){
        if(is_string($name)){
            if($mail == null){
                if(preg_match("/(.*)<(.*)>/", $name, $regs)){
                    return array('name' => $regs[1], 'mail' => $regs[2]);
                }
                else if(preg_match("/<(.*)>/", $name, $regs)){
                    return array('name' => '', 'mail' => $regs[1]);
                }
                else return array('name' => '', 'mail' => $name);
            }
            else return array('name' => $name, 'mail' => $mail);
        }
        else if(is_array($name)){
            if(!isset($name['name']) || $name['name'] === null || $name['name'] == '' || empty($name['name'])){
                return $name['mail'];
            }
            else{
                return $name['name']." <".$name['mail'].">";
            }
        }
        else{
            return null;
        }
    }
}

if(!function_exists("encodeRecipients")){
    function encodeRecipients($recipient){
        $recipientsCharset = 'utf-8';
        if (preg_match("/(.*)<(.*)>/", $recipient, $regs)) {
            $recipient = '=?' . $recipientsCharset . '?B?'.base64_encode($regs[1]).'?= <'.$regs[2].'>';
        }
        return $recipient;
    }
}

if(!function_exists("verify_auth_token")){
    function verify_auth_token($token){
        $client = getClient(true);
        $accessToken = $client->fetchAccessTokenWithAuthCode($token);
        file_put_contents(CREDENTIALS_PATH, json_encode($accessToken));
    }
}

if(!function_exists("getClient")){
    function getClient($force_client = false){
        $client = new Google_Client();
        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
        $client->setAuthConfig(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        if(file_exists(CREDENTIALS_PATH)){
            $client->setAccessToken(json_decode(file_get_contents(CREDENTIALS_PATH), true));
        }
        else{
            $authurl = $client->createAuthUrl();
            if(!$force_client) redirect($authurl);
            else return $client;
        }
        if($client->isAccessTokenExpired()){
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents(CREDENTIALS_PATH, json_encode($client->getAccessToken()));
        }
        return $client;
    }
}