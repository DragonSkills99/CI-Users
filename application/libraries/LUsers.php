<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class LUsers {
    
    private $db;
    private $ci;
    private $users;
    
    function __construct(){
        $this->ci = get_instance();
        $this->ci->load->library('session');
        $this->ci->load->database();
        $this->db = $this->ci->db;
        $this->loadUserList();
    }
    
    public function getID($user){
        return $user->id;
    }
    
    public function getUsername($user){
        return $user->name;
    }
    
    public function getUsers(){
        return $this->users;
    }
    
    public function loggedIn(){
        return $this->ci->session->userdata('isUserLoggedIn');
    }
    
    public function needLogin(){
        if(!$this->loggedIn()){
            show_error("You have to be logged in, to visit this page.", 401, "Authentification Error");
        }
    }
    
    public function getCurrentUserID(){
        if($this->loggedIn()){
            return $this->ci->session->userdata('userId');
        }
        else return -1;
    }
    
    public function getUserByID($id){
        foreach ($this->users as $user) {
            if($user->id == $id) {
                return $user;
            }
        }
        return null;
    }
    
    public function getUserByEMail($mail){
        foreach ($this->users as $user) if($user->email == $mail) return $user;
        return null;
    }
    
    public function getUserByUserName($username){
        foreach ($this->users as $user) if($user->name == $username) return $user;
        return null;
    }
    
    public function getCurrentUser(){
        if($this->loggedIn()){
            return $this->getUserByID($this->getCurrentUserID());
        }
        else return null;
    }
    
    private function loadUserList(){
        $this->users = $this->db->get('users')->result();
    }
}