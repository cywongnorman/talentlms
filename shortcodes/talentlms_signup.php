<?php
$site_info = tl_get_siteinfo();
if($site_info instanceof TalentLMS_ApiError) {
	$output .= "<div class='alert alert-error'>";
	$output .= $e -> getMessage();
	$output .= "</div>";
	return false;
}

$custom_fields = tl_get_custom_fields();
if($custom_fields instanceof TalentLMS_ApiError) {
	$output .= "<div class='alert alert-error'>";
	$output .= $e -> getMessage();
	$output .= "</div>";
	return false;
}


if ($_POST['tl-singup-post']) {
	$post = true;
	
	if (!$_POST['first-name']) {
		$first_name_error = __('First Name is mandatory');
		$first_name_error_class = 'tl-singup-error';
		$post = false;
	}
	if (!$_POST['last-name']) {
		$last_name_error = __('Last Name is mandatory');
		$last_name_error_class = 'tl-singup-error';
		$post = false;
	}
	if (!$_POST['email']) {
		$email_error = __('Email is mandatory');
		$email_error_class = 'tl-singup-error';
		$post = false;
	}
	if (!$_POST['login']) {
		$login_error = __('Username is mandatory');
		$login_error_class = 'tl-singup-error';
		$post = false;
	}
	if (!$_POST['password']) {
		$password_error = __('Password is mandatory');
		$password_error_class = 'tl-singup-error';
		$post = false;
	}
	if (is_array($custom_fields)) {
		foreach ($custom_fields as $key => $custom_field) {
			if ($custom_field['mandatory'] == 'yes' && !$_POST[$custom_field['key']]) {
				$custom_fields[$key]['error'] = $custom_field['name'] . " " . __('is mandatory');
				$custom_fields[$key]['error_class'] = 'tl-singup-error';
				$post = false;
			}
		}
	}

	
	if($post) {
		try {
			$signup_arguments = array('first_name' => $_POST['first-name'], 'last_name' => $_POST['last-name'], 'email' => $_POST['email'], 'login' => $_POST['login'], 'password' => $_POST['password']);
			if (is_array($custom_fields)) {
				foreach ($custom_fields as $custom_field) {
					$signup_arguments[$custom_field['key']] = $_POST[$custom_field['key']];
				}
			}
			$newUser = TalentLMS_User::signup($signup_arguments);
			$tl_signup_failed = false;
		} catch (Exception $e){
			if ($e instanceof TalentLMS_ApiError) {
				$tl_signup_failed = true;
				$tl_signup_fail_message .= $e -> getMessage();
			}
		}
		
		if (get_option('tl-signup-sync') && !$tl_signup_failed) {
			$new_wp_user_id = wp_insert_user(array('user_login' => $_POST['login'], 'user_pass' => $_POST['password'], 'user_email' => $_POST['email'], 'first_name' => $_POST['first-name'], 'last_name' => $_POST['last-name']));
			if (is_array($custom_fields)) {
				foreach($custom_fields as $custom_field) {
					update_user_meta($user_id, $custom_field['key'], $_POST[$custom_field['key']]);
				}
			}
			if (is_wp_error($new_wp_user_id)) {
				$tl_signup_fail_message .= $new_wp_user_id->get_error_message();
			}			
		}
		
		if($tl_signup_failed) {
			$output .= "<div class='alert alert-error'>";
			$output .= "<strong>" . _('Something is wrong!') . "</strong> " . $e -> getMessage();
			$output .= "</div>";
		} else {
			if (get_option('tl-signup-redirect') == 'talentlms') {
				$login = TalentLMS_User::login(array('login' => $_POST['login'], 'password' => $_POST['password'], 'logout_redirect' => (get_option('tl-logout') == 'wordpress') ? get_bloginfo('wpurl') : 'http://'.get_option('talentlms-domain')));
				$output .= "<script type='text/javascript'>window.location = '" . tl_talentlms_url($login['login_key']) . "'</script>";
			} else {
				$login = TalentLMS_User::login(array('login' => $_POST['login'], 'password' => $_POST['password'], 'logout_redirect' => (get_option('tl-logout') == 'wordpress') ? get_bloginfo('wpurl') : 'http://'.get_option('talentlms-domain')));
				session_start();
				$_SESSION['talentlms_user_id'] = $login['user_id'];
				$_SESSION['talentlms_user_login'] = $_POST['login'];
				$_SESSION['talentlms_user_pass'] = $_POST['password'];
				$creds = array();
				$creds['user_login'] = $_SESSION['talentlms_user_login'];
				$creds['user_password'] = $_SESSION['talentlms_user_pass'];
				$wpuser = wp_signon($creds, false);
				wp_redirect(admin_url('admin.php?page=talentlms-subscriber'));
			}
		}		
	}
}
$output .= "<form id='tl-singup-form' action='" . tl_current_page_url() . "' method='post'>";
$output .= "<input type='hidden' name='tl-singup-post' value='1'>";

$output .= "<div class='tl-form-group'>";
$output .= "	<label class='tl-form-label' for='first-name'>" . __('First Name') . "</label>";
$output .= "	<div class='tl-form-control " . $first_name_error_class . "'>";
$output .= "		<input type='text' id='first-name' name='first-name' value='" . $_POST['first-name'] . "'>";
$output .= "		<span class='tl-help-inline'>" . " " . $first_name_error . "</span>";
$output .= "	</div>";
$output .= "</div>";

$output .= "<div class='tl-form-group'>";
$output .= "	<label class='tl-form-label' for='last-name'>" . __('Last Name') . "</label>";
$output .= "	<div class='tl-form-control " . $last_name_error_class . " '>";
$output .= "		<input type='text' id='last-name' name='last-name' value='" . $_POST['last-name'] . "'>";
$output .= "		<span class='tl-help-inline'>" . " " . $last_name_error . "</span>";
$output .= "	</div>";
$output .= "</div>";

$output .= "<div class='tl-form-group'>";
$output .= "	<label class='tl-form-label' for='email'>" . __('Email') . "</label>";
$output .= "	<div class='tl-form-control " . $email_error_class . "'>";
$output .= "		<input type='text' id='email' name='email' value='" . $_POST['email'] . "'>";
$output .= "		<span class='tl-help-inline'>" . " " . $email_error . "</span>";
$output .= "	</div>";
$output .= "</div>";

$output .= "<hr />";

$output .= "<div class='tl-form-group'>";
$output .= "	<label class='tl-form-label' for='login'>" . __('Username') . "</label>";
$output .= "	<div class='tl-form-control " . $login_error_class . "'>";
$output .= "		<input type='text' id='login' name='login' value='" . $_POST['login'] . "'>";
$output .= "		<span class='tl-help-inline'>" . " " . $login_error . "</span>";
$output .= "	</div>";
$output .= "</div>";

$output .= "<div class='tl-form-group'>";
$output .= "	<label class='tl-form-label' for='password'>" . __('Password') . "</label>";
$output .= "	<div class='tl-form-control " . $password_error_class . "'>";
$output .= "		<input type='password' id='password' name='password' value='" . $_POST['password'] . "'>";
$output .= "		<span class='tl-help-inline'>" . " " . $password_error . "</span>";
$output .= "	</div>";
$output .= "</div>";

if (is_array($custom_fields)) {
	$output .= "<hr />";

	foreach ($custom_fields as $custom_field) {
		switch($custom_field['type']) {
			case 'text' :
				$output .= "<div class='tl-form-group'>";
				$output .= "	<label class='tl-form-label' for='" . $custom_field['key'] . "'>" . $custom_field['name'] . "</label>";
				$output .= "	<div class='tl-form-control " . $custom_field['error_class'] . "'>";
				$output .= "		<input id='" . $custom_field['key'] . "' name='" . $custom_field['key'] . "' type='text' value='" . $_POST[$custom_field['key']] . "'/>";
				$output .= "		<span class='tl-help-inline'>" . " " . $custom_field['error'] . "</span>";
				$output .= "	</div>";
				$output .= "</div>";
				break;
			case 'dropdown' :
				$dropdown_values = explode(';', $custom_field['dropdown_values']);
				foreach ($dropdown_values as $value) {
					if (preg_match('/\[(.*?)\]/', $value, $match)) {
						$default_value = $match[1];
						$value = $default_value;
					}
					$options[$value] = $value;
				}

				$output .= "<div class='tl-form-group'>";
				$output .= "	<label class='tl-form-label' for='" . $custom_field['key'] . "'>" . $custom_field['name'] . "</label>";
				$output .= "	<div class='tl-form-control " . $custom_field['error_class'] . "'>";
				$output .= "		<select id='" . $custom_field['key'] . "' name='" . $custom_field['key'] . "'>";
				foreach ($options as $key => $option) {
					$output .= "		<option value='" . trim($key) . "'>" . trim($option) . "</option>";
				}
				unset($options);
				$output .= "		</select>";
				$output .= "		<span class='tl-help-inline'>" . " " . $custom_field['error'] . "</span>";
				$output .= "	</div>";
				$output .= "</div>";
				break;
			case 'checkbox' :
				$output .= "<div class='tl-form-group'>";
				$output .= "	<label class='tl-form-label' for='" . $custom_field['name'] . "'>" . $custom_field['name'] . "</label>";
				$output .= "	<div class='tl-form-control " . $custom_field['error_class'] . "'>";
				if (trim($custom_field['checkbox_status']) == 'on') {
					$output .= "	<input id='" . $custom_field['key'] . "' name='" . $custom_field['key'] . "' type='checkbox' checked='checked' value='" . $custom_field['checkbox_status'] . "' />";
				} else {
					$output .= "	<input id='" . $custom_field['key'] . "' name='" . $custom_field['key'] . "' type='checkbox' value='" . $custom_field['checkbox_status'] . "' />";
				}
				$output .= "		<span class='tl-help-inline'>" . " " . $custom_field['error'] . "</span>";
				$output .= "	</div>";
				$output .= "</div>";
				break;
		}
	}
}

$output .= "<div class='form-actions'>";
$output .= "	<button class='btn' name='submit' type='submit' name='submit'>".__('Create account')."</button>";
$output .= "</div>";

$output .= "</form>";
?>