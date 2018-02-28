<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class MUsers_model extends CI_Model {
    public function __construct(){
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->load->helper('string');
        $this->load->helper('mail');
        if(!$this->db->table_exists('users')) $this->create_users_table();
    }
    
    public function create_users_table(){
        $this->db->query("CREATE TABLE `users` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL, `prename` text COLLATE utf8_unicode_ci NOT NULL, `surname` text COLLATE utf8_unicode_ci NOT NULL, `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL, `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL, `gender` enum('Male','Female') COLLATE utf8_unicode_ci NOT NULL, `phone` varchar(15) COLLATE utf8_unicode_ci NOT NULL, `created` datetime NOT NULL, `modified` datetime NOT NULL, `status` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0', `regkey` text COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    }
    
    public function authenticate($user, $password){
        return password_verify($password, $user->password);
    }
    
    public function authenticate_error($user, $password){
        if(authenticate){
            return true;
        }
        else{
            show_error("Wrong username or password", 400, "Permission denied");
            return false;   
        }
    }
    
    public function logout(){
        $this->session->unset_userdata('isUserLoggedIn');
        $this->session->unset_userdata('userId');
        $this->session->sess_destroy();
    }
    
    public function register($owner = "Administrator"){
        $userData = array(
            'name' => strtolower(strip_tags($this->input->post('name'))),
            'prename' => strip_tags($this->input->post('prename')),
            'surname' => strip_tags($this->input->post('surname')),
            'email' => strip_tags($this->input->post('email')),
            'password' => $this->password($this->input->post('password')),
            'gender' => $this->input->post('gender'),
            'phone' => strip_tags($this->input->post('phone')),
            'regkey' => random_string('alnum', 32),
            'created' => date("Y-m-d H:i:s"),
            'modified' => date("Y-m-d H:i:s")
        );
        $insert = $this->db->insert("users", $userData);
        $success = $this->db->affected_rows() > 0;
        if($success){
            $at = $userData["prename"]." ".$userData["surname"]." (".$userData["name"].")";
                    $domain = $this->input->server("SERVER_NAME");
                    $activatescript = base_url("users/activate");
                    $email = urlencode($userData["email"]);
                    $code = urlencode($userData["regkey"]);
                    $owner = $this->owner;
                    $msg = $this->assemble_mail(json_decode(json_encode($userData)), $owner, array("Your registration at $domain was successful.", "", "to activate your account you have to visit <a href=\"https://$domain"."$activatescript/$email/$code\">https://$domain"."$activatescript/$email/$code</a>"));
                    $this->register_mail_sent = mmail(urldecode($email), "Your registration at $domain", $msg, "DragonIgnite <anonymous.lgs.pupil@gmail.com>");
        }
        else{
            $this->register_mail_sent = false;
        }
        return $success;
    }
    
    public function reset_password_mail($owner = "Administrator"){
        $u = $this->users->getUserByEMail($this->input->post("email"));
        if($u->status == '0') $u = null;
        if($u === null){
            $this->reset_password_mail_sent = false;
        }
        else{
            $rk = random_string('alnum', 32);
            $mail = urlencode($u->email);
            $this->db->update('users', array('regkey' => $rk), array('id' => $u->id));
            $rk = urlencode($rk);
            $domain = $this->input->server("SERVER_NAME");
            $msg = $this->assemble_mail($u, $owner, array("You've requested an password reset. To reset your password please call: <a href=\"https://$domain".base_url("users/reset_password/$mail/$rk")."\">https://$domain".base_url("users/reset_password/$mail/$rk")."</a><br>"));
            $this->reset_password_mail_sent = mmail(urldecode($mail), "Your password reset", $msg, "DragonIgnite <anonymous.lgs.pupil@gmail.com>");
        }
        return $u !== null;
    }
    protected $reset_password_mail_sent = false;
    public function reset_password_mail_sent(){
        return $this->reset_password_mail_sent;
    }
    
    public function reset_password($email, $code){
        $return = array("success" => false, "error" => "nothing done");
        
        $u = $this->users->getUserByEMail($email);
            if($u !== null){
                if($u->regkey == $code){
                    $this->formbuilder->setMethod("POST");
                    $this->formbuilder->addField()->setType("password")->setName("password")->setDescription("Password")->setRule("required");
                    $this->formbuilder->addField()->setType("password")->setName("reppassword")->setDescription("Repeat Password")->setRule("required|matches[password]");
                    $this->formbuilder->addField()->setType("submit")->setName("submit")->setFillBothRows(true)->setValue("Set new password");
                    $this->formbuilder->setup();
                    if($this->formbuilder->validate()){
                        $data = array(
                            'password' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
                            'regkey' => ''
                            );
                        $this->db->update("users", $data, array('id' => $u->id));
                        return array("success" => true, "error" => "You password has been changed successfully.");
                    }
                    else{
                        return array("success" => null, "error" => $this->formbuilder->getForm());
                    }
                }
                else{
                    return array("success" => false, "error" => "Error: user does not exist!");
                }
            }
            else{
                $this->load->view("templates/echo", array('echo' => '<h1 style="color: red;">Error: user does not exist!</h1>'));
            }
        
        return $return;
    }
    
    public function change_password(){
        $u = $this->users->getCurrentUser();
        if($u === null) return false;
        $this->db->update("users", array('modified' => date("Y-m-d H:i:s"), 'password' => $this->password($this->input->post("pw"))), array('id' => $u->id));
        $this->logout();
        return $this->db->affected_rows() > 0;
    }
    
    protected function password($password){
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    protected function assemble_mail($user, $owner, $lines){
        $at = $user->prename." ".$user->surname." (".$user->name.")";
        $mail = "<p>";
        $mail .= "Dear $at,<br>";
        $mail .= "<br>";
        foreach($lines as $line){
            $mail .= $line."<br>";
        }
        $mail .= "<br>";
        $mail .= "Greetings,<br>";
        $mail .= $owner."<br>";
        $mail .= "</p>";
        return $mail;
    }
    
    public function change_mail($owner = "Administrator"){
        $u = $this->users->getCurrentUser();
        if($u === null) return false;
        if($this->authenticate_error($u, $this->input->post('password'))){
            $regkey = random_string('alnum', 32);
            $this->db->update('users', array('modified' => date("Y-m-d H:i:s"), 'status' => '0', 'regkey' => $regkey, 'email' => $this->input->post('email')));
            $this->logout();
            $at = $u->prename." ".$u->surname." (".$u->name.")";
            $domain = $this->input->server("SERVER_NAME");
            $activatescript = base_url("users/activate");
            $email = urlencode($this->input->post('email'));
            $code = urlencode($regkey);
            $msg = $this->assemble_mail($u, $owner, array("Your mail change at $domain was successful.", "", "to reactivate your account you have to visit <a href=\"https://$domain"."$activatescript/$email/$code\">https://$domain"."$activatescript/$email/$code</a>"));
            return mmail(urldecode($email), "Your mail change at $domain", $msg, "DragonIgnite <anonymous.lgs.pupil@gmail.com>");
        }
    }
    protected $register_mail_sent = false;
    public function register_mail_sent(){
        return $this->register_mail_sent;
    }
    
    public function get_user(){
        $checkLogin = $this->$this->users->getUserByEMail($this->input->post('email'));
        if($checkLogin === null || $checkLogin->status == '0') $checkLogin = false;
        if(!$checkLogin){
            $checkLogin = $this->$this->users->getUserByUserName(strtolower($this->input->post('email')));
            if($checkLogin === null || $checkLogin->status == '0') $checkLogin = false;
        }
        return $checkLogin;
    }
    
    public function activate($email, $key){
        $this->load->view("templates/header", array('title' => 'Account Activation'));
        $this->db->update("users", array('status' => '1', 'regkey' => ''), array('regkey' => $key, 'email' => $email, 'status' => '0'));
        return $this->db->affected_rows() > 0;
    }
    
    public function update(){
        $u = $this->users->getCurrentUser();
        if($u === null) return false;
        $data = array(
            'prename' => $this->input->post('prename'),
            'surname' => $this->input->post('surname'),
            'phone' => $this->input->post('phone'),
            'gender' => $this->input->post('gender')
        );
        $this->db->update('users', $data, array('id' => $u->id));
        if($this->db->affected_rows() > 0){
            $this->db->update('users', array('modified' => date("Y-m-d H:i:s")), array('id' => $u->id));
            return '<a href=""><h1>Saved changes successfully.</h1></a>';
        }
        else{
            return '<a href=""><h1>There was no change to save.</h1></a>';
        }
    }
    
    public function get_all_users(){
        return $this->db->get('users')->result();
    }
}
