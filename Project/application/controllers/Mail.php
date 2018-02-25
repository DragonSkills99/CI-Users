<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mail extends CI_Controller {
    
    function auth(){
        if($this->input->get("code") === null){
            show_error('Auth Error', 400, 'Missing parameter \'code\'');
        }
        else{
            $this->load->helper("mail");
            verify_auth_token($this->input->get("code"));
        }
    }   
}