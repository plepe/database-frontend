<?php
class Page_login {
  function content($param) {
    global $auth;

    if($auth->is_logged_in()) {
      return "You are already logged in. <a href='" . page_url(array()) . "'>Index</a>.";
    }

    $domain_names = array_keys($auth->domains());

    $form_def = array(
      'username' => array(
        'type'     => 'text',
	'html_attributes' => array("autofocus" => true),
	'name'     => 'Username',
	'req'      => true,
      ),
      'password' => array(
        'type'     => 'password',
	'name'     => 'Password',
	'req'      => true,
      ),
      'domain' => array(
        'type'     => 'select',
	'name'     => 'Domain',
	'values'   => $domain_names,
	'default'  => $domain_names[0],
	'req'      => true,
      ),
    );

    $form_login = new form('login', $form_def);
    
    $login_error = false;

    if($form_login->is_complete()) {
      $data = $form_login->get_data();

      $result = $auth->authenticate($data['username'], $data['password'], $data['domain']);

      if($result !== true) {
	if($result === false)
	  $login_error = "Username or Password invalid";
	else
	  $login_error = implode("<br>", $result);
      }
      else {
	if(array_key_exists('return_to', $param))
	  page_reload($param['return_to']);
	else
	  page_reload(array());

	return "Login successful";
      }
    }

    if($form_login->is_empty()) {
    }

    $ret  = "<form method='post'>\n";
    $ret .= $form_login->show();
    if($login_error != false) {
      $ret .= "<div id='login_error'>$login_error</div>";
    }
    $ret .= "<input type='submit' value='Login' />\n";

    if(array_key_exists('return_to', $param)) {
      $ret .= "<input type='hidden' name='return_to' value='" . htmlspecialchars(page_url($param['return_to'])) . "'/>";
    }
    elseif(array_key_exists('HTTP_REFERER', $_SERVER)) {
      $ret .= "<input type='hidden' name='return_to' value='" . htmlspecialchars($_SERVER['HTTP_REFERER']) . "'/>";
    }

    $ret .= "</form>\n";

    return $ret;
  }
}
