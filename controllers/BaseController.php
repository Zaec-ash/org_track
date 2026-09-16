<?php
class BaseController {
	public $model;
	//public $url = "https://orgtrack.great-site.net";
	public $url = "https://localhost/org_track";
	// public $url = "https://orgtrack.great-site.net";
	function render($page){
		include $page;
	}

	function generateCSRFToken($form) {
        $_SESSION["csrf_token"][$form] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'][$form];
	}

	 function verifyCSRFToken($form) {
        if(!isset($_SESSION['csrf_token'][$form]) || !isset($_POST["csrf_token"]) || !hash_equals($_SESSION['csrf_token'][$form], $_POST["csrf_token"])){
          	header("Location:" . this->$url . "/no-access");
            die();
        } 
    }
	
}