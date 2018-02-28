<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Users extends CI_Controller {
    
    //e.g. array("gmail.com", "gmx.com", "web.de");
    protected $email_whitelist = array('lgs-hu.eu');
    //e.g. array("yahoo.com", "outlook.com");
    protected $email_blacklist = array();
    
    protected $profiles_public = false;
    
    protected $owner = "DragonSkills99";
    
    protected $gender = array('Male', 'Female', 'Both/None');
    
    function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->load->helper('string');
        $this->load->library('formBuilder');
        $this->load->model("MUsers_model");
        $this->load->library('lUsers');
        $this->users = $this->lusers;
        $this->musers_model = new MUsers_model();
        $this->form_validation = $this->formbuilder->form_validation;
    }
    
    public function profile($id = null){
        if(!$this->profiles_public) show_error("Profiles are not public", 400, "Profile error");
        if($id === null) show_error("This user does not exist.", 400, "User error");
        if(is_numeric($id)){
            $u = $this->users->getUserByID($id);
        }
        else if(is_string($id)){
            $id = urldecode($id);
            if(filter_var($id, FILTER_VALIDATE_EMAIL)){
                $u = $this->users->getUserByEMail($id);
            }
            else{
                $u = $this->users->getUserByUserName($id);
            }
        }
        else show_error("This user does not exist.", 400, "User error");
        if($u === null) show_error("This user does not exist.", 400, "User error");
        $this->formbuilder->addField()->setTag("a")->setDescription("Username:")->setInnerValue($u->name);
        $this->formbuilder->addField()->setTag("a")->setDescription("Prename:")->setInnerValue($u->prename);
        $this->formbuilder->addField()->setTag("a")->setDescription("Surname:")->setInnerValue($u->surname);
        $this->formbuilder->addField()->setTag("a")->setDescription("E-Mail:")->setInnerValue($u->email);
        $this->formbuilder->addField()->setTag("a")->setDescription("Gender:")->setInnerValue($u->gender);
        $this->formbuilder->addField()->setTag("a")->setDescription("Phone:")->setInnerValue($u->phone);
        $this->formbuilder->addField()->setTag("a")->setDescription("Joined:")->setInnerValue(date("H:i:s d.m.Y", strtotime($u->created)));
        $this->formbuilder->addField()->setTag("a")->setDescription("Last profile edit:")->setInnerValue(date("H:i:s d.m.Y", strtotime($u->modified)));
        $this->load->view("templates/header", array("title" => $u->name));
        $this->load->view("templates/echo", array('echo' => $this->formbuilder->getForm()));
        $this->load->view("templates/footer", array());
    }
    
    public function account($action = "show"){
        $data = array();
        $data['title'] = "User Account";
        $this->load->view('templates/header', $data);
        if($this->session->userdata('isUserLoggedIn')){
            switch ($action) {
                case 'show':
                case 'display':
                    $data['user'] = json_decode(json_encode($this->users->getUserByID($this->session->userdata('userId'))), true);
                    $u = $data["user"];
                    
                    $this->formbuilder->addField()->setTag("a")->setDescription("ID:")->setInnerValue($u["id"]);
                    $this->formbuilder->addField()->setTag("a")->setDescription("Username:")->setInnerValue($u["name"]);
                    $this->formbuilder->addField()->setTag("a")->setDescription("Prename:")->setInnerValue($u["prename"]);
                    $this->formbuilder->addField()->setTag("a")->setDescription("Surname:")->setInnerValue($u["surname"]);
                    $this->formbuilder->addField()->setTag("a")->setDescription("E-Mail:")->setInnerValue($u["email"]);
                    $this->formbuilder->addField()->setTag("a")->setDescription("Gender:")->setInnerValue($u["gender"]);
                    $this->formbuilder->addField()->setTag("a")->setDescription("Phone:")->setInnerValue($u["phone"]);
                    $this->formbuilder->addField()->setTag("a")->setDescription("Joined:")->setInnerValue(date("H:i:s d.m.Y", strtotime($u["created"])));
                    $this->formbuilder->addField()->setTag("a")->setDescription("Last profile edit:")->setInnerValue(date("H:i:s d.m.Y", strtotime($u["modified"])));
                    $this->formbuilder->addField()->setTag("a")->setInnerValue("edit profile")->setAttribute("href", base_url("users/account/edit"))->setFillBothRows(true)->setAttribute('style', 'color: cyan; text-decoration: underline;');
                    $this->formbuilder->addField()->setTag("a")->setInnerValue("edit email address")->setAttribute("href", base_url("users/account/editmail"))->setFillBothRows(true)->setAttribute('style', 'color: cyan; text-decoration: underline;');
                    $this->formbuilder->addField()->setTag("a")->setInnerValue("change password")->setAttribute("href", base_url("users/account/changepassword"))->setFillBothRows(true)->setAttribute('style', 'color: cyan; text-decoration: underline;');
                    
                    $this->load->view('templates/echo', array('echo' => '<style>.genformcell{white-space: nowrap;}.genformcell.label{padding-right: 20px;font-weight: bold;}</style>'.$this->formbuilder->getForm()));
                    break;
                case 'edit':
                    $data['user'] = $this->users->getCurrentUser();
                    $u = $data["user"];
                    
                    $this->formbuilder->setMethod("POST");
                    $this->formbuilder->addField()->setDescription("Prename:")->setName("prename")->setValue($u->prename);
                    $this->formbuilder->addField()->setDescription("Surname:")->setName("surname")->setValue($u->surname);
                        
                    $genders = $this->gender;
                        
                    foreach($genders as $gender){
                        $box = $this->formbuilder->addField()->setTag("label")->setInnerValue("$gender")->addChild()->setType("radio")->setName("gender")->setValue("$gender");
                        if(isset($u->gender)){
                            if($u->gender == $gender) $box->setAttribute('checked', '');
                        }
                        else{
                            if($gender == $genders[0]) $box->setAttribute('checked', '');
                        }
                        $box->setFillBothRows(true);
                    }
                    
                    $this->formbuilder->addField()->setDescription("Phone:")->setName("phone")->setValue($u->phone);
                    $this->formbuilder->addField()->setupSubmit("Save changes");
                    $this->formbuilder->setup();
                    if($this->input->post('submit')){
                        $this->load->view("templates/echo", array('echo' => $this->musers_model->update()));
                    }
                    else $this->load->view("templates/echo", array('echo' => $this->formbuilder->getForm()));
                    break;
                case 'editmail':
                    $this->formbuilder->setMethod("POST");
                    $this->formbuilder->addField()->setTag("h1")->setFillBothRows(true)->setInnerValue("Do you really want to change your mail? After changing your mail, you will be logged out and can only log in again, if you've validated that this mail is yours.");
                    $this->formbuilder->addField()->setType("email")->setDescription("New E-Mail Address")->setName("email")->setRule("required|valid_email|callback_email_whitelist|callback_email_blacklist");
                    $this->formbuilder->addField()->setType("email")->setDescription("Repeat new E-Mail Address")->setName("repemail")->setRule("required|valid_email|matches[email]");
                    $this->formbuilder->addField()->setType("password")->setDescription("Your password")->setName("password")->setRule("required");
                    $this->formbuilder->addField()->setupSubmit("Change E-Mail-Address");
                    $this->formbuilder->setup();
                    if($this->formbuilder->validate()){
                            if($this->musers_model->change_mail($this->owner))
                                $this->load->view('templates/echo', array('echo' => '<h1>Successfully changed mail</h1>'));
                            else
                                $this->load->view('templates/echo', array('echo' => '<h1 style="color: red;">Successfully changed mail, but we couldn\'t send your verification mail.</h1>'));
                    }
                    $this->load->view("templates/echo", array('echo' => $this->formbuilder->getForm()));
                    break;
                case 'changepassword':
                    $u = $this->users->getCurrentUser();
                    if($u == null) redirect("users/login");
                    $this->formbuilder->setMethod("POST");
                    $this->formbuilder->addField()->setType("password")->setDescription('Old password')->setName("opw")->setRule("required");
                    $this->formbuilder->addField()->setType("password")->setDescription('Password')->setName("pw")->setRule("required");
                    $this->formbuilder->addField()->setType("password")->setDescription('Repeat Password')->setName("rpw")->setRule("required|matches[pw]");
                    $this->formbuilder->addField()->setupSubmit("Change password");
                    $this->formbuilder->setup();
                    if($this->formbuilder->validate()){
                        if($this->musers_model->authenticate($u, $this->input->post("opw"))){
                            
                            if($this->musers_model->change_password()){
                                $this->load->view("templates/echo", array('echo' => '<h1>Password update successful</h1>'));
                            }
                            else{
                                $this->load->view("templates/echo", array('echo' => '<h1 style="color: red;">Nothing could be changed</h1>'));
                            }
                        }
                        else{
                            $this->load->view("templates/echo", array('echo' => '<h1 style="color: red;">Invalid old password</h1>'));
                        }
                    }
                    else{
                        $this->load->view("templates/echo", array('echo' => $this->formbuilder->getForm()));
                    }
                    break;
                default:
                    $this->load->view("templates/echo", array('echo' => '<h1 style="color: red;">Invalid action provided</h1>'));
                    break;
            }
        }else{
            redirect('users/login');
        }
        $this->load->view('templates/footer', $data);
    }
    
    public function login(){
        if($this->lusers->loggedIn()) redirect("users/account");
        $data = array();
        if($this->session->userdata('success_msg')){
            $data['success_msg'] = $this->session->userdata('success_msg');
            $this->session->unset_userdata('success_msg');
        }
        if($this->session->userdata('error_msg')){
            $data['error_msg'] = $this->session->userdata('error_msg');
            $this->session->unset_userdata('error_msg');
        }
        
        if(isset($data['success_msg'])) $success_msg = $this->formbuilder->addField()->setTag("h2")->setInnerValue($data['success_msg'])->setAttribute('style', 'color: lime;')->setFillBothRows(true);
        $error_msg = $this->formbuilder->addField()->setTag("h2")->setAttribute('style', 'color: red;')->setFillBothRows(true);
        
        if(isset($data['error_msg'])) $error_msg->setInnerValue($data['error_msg']);
        
        $this->formbuilder->setMethod("POST");
        $this->formbuilder->addField()->setType("text")->setName("email")->setDescription("Username / E-Mail:");
        $this->formbuilder->addField()->setType("password")->setName("password")->setDescription("Password:")->setRule("required");
        $this->formbuilder->addField()->setType("submit")->setName("loginSubmit")->setValue("Login")->setFillBothRows(true);
        $this->formbuilder->addField()->setTag("br");
        $this->formbuilder->addField()->setTag("div")->setFillBothRows(true)->addChild()->setTag("a")->setFillBothRows(true)->setInnerValue("Not registered? Register now.")->setAttribute('href', base_url("users/registration"))->setClass("register");
        $this->formbuilder->addField()->setTag("div")->setFillBothRows(true)->addChild()->setTag("a")->setFillBothRows(true)->setInnerValue("Lost password? Reset now.")->setAttribute('href', base_url("users/reset_password"))->setClass("passwordreset");
        $this->formbuilder->setup();
        
        if($this->input->post('loginSubmit')){
            if ($this->formbuilder->validate()) {
                $checkLogin = $this->get_user();
                if($checkLogin){
                    if($this->musers_model->authenticate($checkLogin, $this->input->post('password'))){
                        $this->session->set_userdata('isUserLoggedIn',TRUE);
                        $this->session->set_userdata('userId',$checkLogin->id);
                        redirect('users/account/');
                    }else{
                        $error_msg->setInnerValue('Wrong email or password, please try again.');
                    }
                }
                else{
                    $error_msg->setInnerValue('Wrong email or password, please try again.');
                }
            }
        }
        $data['title'] = "User Login";
        //load the view
        $this->load->view('templates/header', $data);
        
        $this->load->view('templates/echo', array('echo' => $this->formbuilder->getForm()));
        $this->load->view('templates/footer', $data);
    }
    
    public function activate($email, $key){
        $email = urldecode($email);
        $key = urldecode($key);
        if($this->musers_model->activate($email, $key)){
            $this->load->view("templates/echo", array('echo' => "Your Account was activated successfully."));
        }
        else{
            $this->load->view("templates/echo", array('echo' => "Activation failed"));
        }
        $this->load->view("templates/footer", array());
    }
    
    public function registration(){
        $data = array();
        $userData = array();
        
        $this->formbuilder->setMethod("POST");
        $this->formbuilder->addField()->setTag("h3")->setInnerValue($this->session->userdata("fail_msg"))->setAttribute("style", "color: red;");
        $this->session->set_userdata("fail_msg", "");
        $this->formbuilder->addField()->setType("text")->setName("name")->setDescription("Name")->setValue(!isset($userData['name']) ? '' : $userData['name'])->setAttribute('placeholder', 'Name')->setClass('form-control')->setAttribute('required', null)->setFillBothRows(true)->setRule("required");
        $this->formbuilder->addField()->setType("text")->setName("prename")->setValue(!isset($userData['prename']) ? '' : $userData['prename'])->setAttribute('placeholder', 'Prename')->setClass('form-control')->setFillBothRows(true);
        $this->formbuilder->addField()->setType("text")->setName("surname")->setValue(!isset($userData['surname']) ? '' : $userData['surname'])->setAttribute('placeholder', 'Surname')->setClass('form-control')->setFillBothRows(true);
        $this->formbuilder->addField()->setType("email")->setName("email")->setValue(!isset($userData['email']) ? '' : $userData['email'])->setAttribute('placeholder', 'E-Mail')->setClass('form-control')->setAttribute('required', null)->setFillBothRows(true)->setDescription("E-Mail")->setRule("required|valid_email|callback_email_whitelist|callback_email_blacklist");
        $this->formbuilder->addField()->setType("text")->setName("phone")->setValue(!isset($userData['phone']) ? '' : $userData['phone'])->setAttribute('placeholder', 'Phone')->setClass('form-control')->setFillBothRows(true);
        $this->formbuilder->addField()->setType("password")->setName("password")->setAttribute('placeholder', 'Password')->setClass('form-control')->setAttribute('required', null)->setFillBothRows(true)->setDescription("Password")->setRule("required");
        $this->formbuilder->addField()->setType("password")->setName("conf_password")->setAttribute('placeholder', 'Confirm Password')->setClass('form-control')->setAttribute('required', null)->setFillBothRows(true)->setDescription("Confirm Password")->setRule("required|matches[password]");
            
        $this->formbuilder->addField()->setTag("label")->addChild()->setTag("h3")->setInnerValue("Gender:");
            
        $genders = $this->gender;
            
        foreach($genders as $gender){
            $box = $this->formbuilder->addField()->setTag("label")->setInnerValue("$gender")->addChild()->setType("radio")->setName("gender")->setValue("$gender");
            if(isset($userData['gender'])){
                if($userData['gender'] == $gender) $box->setAttribute('checked', '');
            }
            else{
                if($gender == $genders[0]) $box->setAttribute('checked', '');
            }
            $box->setFillBothRows(true);
        }
            
        $this->formbuilder->addField()->setType("submit")->setName("regisSubmit")->setValue("Register");
        $this->formbuilder->addField()->setTag("br");
        $this->formbuilder->addField()->setTag("p")->setClass("footInfo")->setFillBothRows(true)->addChild()->setTag("a")->setInnerValue("Already have an account? ")->parent()->addChild()->setTag("a")->setAttribute('href', base_url('users/login'))->setInnerValue("Login here");
        
        if($this->input->post('regisSubmit')){
            $this->formbuilder->setup();
            if($this->formbuilder->validate()){
                $checkLogin = $this->users->getUserByEMail($this->input->post("email"));
                if($checkLogin){
                    $this->session->set_userdata('fail_msg', 'You\'ve already an account with this email.');
                    redirect('users/registration');
                }
                $checkLogin = $this->users->getUserByUserName($this->input->post("name"));
                if($checkLogin){
                    $this->session->set_userdata('fail_msg', 'Username already taken.');
                    redirect('users/registration');
                }
                
                if($this->musers_model->register()){
                    
                    if($this->musers_model->registration_mail_sent())
                        $this->session->set_userdata('success_msg', 'Your registration was successful. Please login to your account.');
                    else
                        $this->session->set_userdata('success_msg', 'Your registration was successful. But E-Mail couldn\'t be sent.');
                    redirect('users/login');
                }else{
                    $data['error_msg'] = 'Some problems occured, please try again.';
                }
            }
        }
        $data['title'] = "User Registration";
        $this->load->view('templates/header', $data);
        $this->load->view('templates/echo', array('echo' => $this->formbuilder->getForm()));
        $this->load->view('templates/footer', $data);
    }
    
    public function logout(){
        $this->musers_model->logout();
        redirect('users/login');
    }
    
    public function reset_password($email = null, $code = null){
        if(filter_var(urldecode($email), FILTER_VALIDATE_EMAIL) && $code !== null){
            $email = urldecode($email);
            $code = urldecode($code);
            $this->load->view("templates/header", array('title' => 'Password reset'));
            
            $answer = $this->musers_model->reset_password($email, $code);
            
            if($answer["success"] === null) $this->load->view("templates/echo", array("echo" => $answer["error"]));
            else if($answer["success"]) $this->load->view("templates/echo", array("echo" => "<h1>".$answer["error"]."</h1>"));
            else $this->load->view("templates/echo", array("echo" => "<h1 style=\"color: red;\">".$answer["error"]."</h1>"));
            
            $this->load->view("templates/footer", array());
        }
        else if($email !== null || $code !== null){
            $this->load->view("templates/header", array('title' => 'Password reset'));
            $this->load->view("templates/echo", array('echo' => '<h1>Invalid data</h1>'));
            $this->load->view("templates/footer", array());
        }
        else{
            $this->load->view("templates/header", array('title' => 'Password reset'));
            $this->formbuilder->setMethod("POST");
            $this->formbuilder->addField()->setDescription("E-Mail-Address")->setName("email")->setRule("required|valid_email")->setType("email");
            $this->formbuilder->addField()->setType("submit")->setValue("Request password reset")->setName("submit")->setFillBothRows(true);
            $this->formbuilder->setup();
            if($this->formbuilder->validate()){
                if($this->musers_model->reset_password_mail($this->owner)){
                    if($this->musers_model->reset_password_mail_sent()) $this->load->view("templates/echo", array('echo' => '<h1>Please check your mails for the password reset link.</h1>'));
                    else $this->load->view("templates/echo", array('echo' => '<h1 style="color: red;">E-Mail couldn\'t be sent.</h1>'));
                }
                else $this->load->view("templates/echo", array('echo' => '<h1 style="color: red;">Error: user does not exist!</h1>'));
            }
            else{
                $this->load->view("templates/echo", array('echo' => $this->formbuilder->getForm()));
            }
            $this->load->view("templates/footer", array());
        }
    }
    
    public function email_check($str){
        $checkEmail = $this->users->getUserByEMail($str);
        if($checkEmail !== null){
            $this->form_validation->set_message('email_check', 'The given email already exists.');
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    public function email_whitelist($email){
        if(!isset($this->email_whitelist) || !is_array($this->email_whitelist)) return true;
        if(count($this->email_whitelist) > 0){
            $hit = false;
            foreach($this->email_whitelist as $host){
                if(preg_match("/(.*)\@".preg_quote($host)."/", $email)) $hit = true;
            }
            
            if($hit){
                return true;
            }
            else{
                $this->formbuilder->form_validation->set_message("email_whitelist", "You're email is not whitelisted.");
                return false;
            }
        }
        else{
            return true;
        }
    }
    
    public function email_blacklist($email){
        if(!isset($this->email_blacklist) || !is_array($this->email_blacklist)) return true;
        if(count($this->email_blacklist) > 0){
            $hit = false;
            foreach($this->email_blacklist as $host){
                if(preg_match("/(.*)\@".preg_quote($host)."/", $email)) $hit = true;
            }
            
            if(!$hit){
                return true;
            }
            else{
                $this->formbuilder->form_validation->set_message("email_blacklist", "You're email is blacklisted.");
                return false;
            }
        }
        else{
            return true;
        }
    }
}