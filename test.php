<?php
function custom_curleejo_forgot_password_form()

{

	ob_start();

	?>

	<form id="curleejo-forgot-password-form">

		<div class="form_content">

			<h2>Forgot Password</h2>

			<p>Enter your email to continue to reset your password.</p>

		</div>

		<div class="from-group">

			<input type="email" name="email" required>

			<span class="custom-placeholder">Enter your registered email <span class="asterisk">*</span></span>

			<div id="email-error-message" class="error-message"></div>

		</div>



		<div class="from-group">

			<button type="submit">Confirm</button>

			<img id="loader" src="<?php echo get_stylesheet_directory_uri() . '/assets/images/loader.gif'; ?>" alt="Loader"

				style="display:none;">

		</div>

		<div class="singup_text">

			Back to <a class="signup" href="<?php echo home_url('/login'); ?>">Login</a>

		</div>

	</form>

	<div id="forgot-password-message" style="display:none;">

		<div class="form_content">

			<h2>Email Sent</h2>

			<p>An email has been sent to this address with instructions on how to reset your password.</p>

		</div>

		<div class="from-group">

			<a href="<?php echo home_url('/login'); ?>" type="submit">Back To Login</a>

		</div>

	</div>







	<script>

		jQuery(document).ready(function ($) {

			$("#curleejo-forgot-password-form").submit(function (e) {

				e.preventDefault();

				$('#curleejo-forgot-password-form #loader').show();

				$("#email-error-message").html("");

				var formData = $(this).serialize();



				$.ajax({

					type: "POST",

					url: "<?php echo admin_url('admin-ajax.php'); ?>",

					data: formData + "&action=curleejo_forgot_password",

					dataType: "json",

					success: function (response) {

						$('#curleejo-forgot-password-form #loader').hide();



						if (response.success) {

							$("#forgot-password-message").show();

							$("#curleejo-forgot-password-form").hide();

						} else {

							$("#email-error-message").html(response.message);

						}

					}

				});

			});

		});

	</script>

	<?php

	return ob_get_clean();

}

add_shortcode('curleejo_forgot_password', 'custom_curleejo_forgot_password_form');





function curleejo_forgot_password_ajax_handler()

{

	if (empty($_POST['email'])) {

		wp_send_json(['success' => false, 'message' => 'Please enter your email.']);

	}



	$email = sanitize_email($_POST['email']);



	if (!is_email($email)) {

		wp_send_json(['success' => false, 'message' => 'Please enter a valid email address.']);

	}


	


	$user = get_user_by('email', $email);



	if (!$user) {

		wp_send_json(['success' => false, 'message' => 'This email is not registered.']);

	}



	// Generate a unique reset key

	$reset_key = wp_generate_password(32, false);

	update_user_meta($user->ID, 'curleejo_reset_password_key', $reset_key);

	update_user_meta($user->ID, 'curleejo_reset_password_expiry', time() + 3600); // 1-hour expiry



	// Reset URL

	$reset_url = home_url("/reset-password/?key=$reset_key&user_id=" . $user->ID);

	$first_name = $user->first_name; // Assuming user object has the first name

	$email = $user->user_email; // Adjust if your email variable is different



	$subject = "Password Reset Request";



	$message = '

	<!DOCTYPE html>

	<html>

	<head>

	<meta charset="UTF-8" />

	<style>

		body {

		  font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;

		  background-color: #f4f6f8;

		  margin: 0;

		  padding: 0;

		}

		.email-container {

		  max-width: 500px;

		  margin: 40px auto;

		  background-color: #ffffff;

		  border-radius: 8px;

		  border: 1px solid #ddd; 

		  padding: 30px;

		}

		h2, h5 {

		  color: #333333;

		}

		p {

		  font-size: 16px;

		  color: #555555;

		}

		.button {

		    display: inline-block;

            padding: 10px 20px;

            margin-top: 20px;

            background-color: #ff3b3b;

            color: #ffffff !important;

            text-decoration: none;

            border-radius: 5px;

            margin-bottom: 20px;

		}

		.footer {

		  font-size: 12px;

		  color: #999999;

		  text-align: center;

		  margin-top: 30px;

		}

	</style>

	</head>

	<body>

	<div class="email-container">

		<p style="text-align: center;"><img src="' . get_stylesheet_directory_uri() . '/assets/images/curleejo-logo.png" style="max-width: 200px;width: 100%; padding-bottom: 50px;"></p>

		<h2>Hello ' . esc_html($first_name) . ',</h2>

		<p>We received a request to reset your password. Click the button below to reset it:</p>

		<p style="text-align: center"><a href="' . esc_url($reset_url) . '" class="button">Reset Your Password</a></p>

		<p>If you did not request a password reset, please ignore this email or contact support if you have questions.</p>

		<div class="footer">

			&copy; ' . date('Y') . ' EDI - Qatar Foundation. All rights reserved.

		</div>

	</div>

	</body>

	</html>

	';



	$headers = ['Content-Type: text/html; charset=UTF-8'];



	wp_mail($email, $subject, $message, $headers);



	wp_send_json(['success' => true, 'message' => 'A password reset link has been sent to your email.']);

}

add_action('wp_ajax_curleejo_forgot_password', 'curleejo_forgot_password_ajax_handler');

add_action('wp_ajax_nopriv_curleejo_forgot_password', 'curleejo_forgot_password_ajax_handler');





function custom_curleejo_reset_password_form()

{

	if (!isset($_GET['key']) || !isset($_GET['user_id'])) {

		return "<p>Invalid password reset link.</p>";

	}



	$key = sanitize_text_field($_GET['key']);

	$user_id = intval($_GET['user_id']);



	$stored_key = get_user_meta($user_id, 'curleejo_reset_password_key', true);

	$expiry_time = get_user_meta($user_id, 'curleejo_reset_password_expiry', true);



	// Check for invalid or expired key

	if (!$stored_key || $stored_key !== $key || time() > $expiry_time) {

		return "<p>This password reset link is invalid or has expired.</p>";

	}



	ob_start();

	?>

	<form id="curleejo-reset-password-form">



		<input type="hidden" name="key" value="<?php echo esc_attr($_GET['key']); ?>">

		<input type="hidden" name="user_id" value="<?php echo esc_attr($_GET['user_id']); ?>">



		<div class="from-group password-group">

			<input type="password" name="new_password" id="new_password" autocomplete="new-password" required>

			<span class="custom-placeholder">New Password <span class="asterisk">*</span></span>

			<span class="toggle-passwords show_pass" data-target="#new_password">

				<i class="eye-icon fa fa-eye-slash"></i>

			</span>

		</div>



		<div class="from-group password-group">

			<input type="password" name="confirm_password" id="confirm_password" autocomplete="new-password" required>

			<span class="custom-placeholder">Confirm New Password <span class="asterisk">*</span></span>

			<span class="toggle-passwords show_pass" data-target="#confirm_password">

				<i class="eye-icon fa fa-eye-slash"></i>

			</span>

			<p id="reset-password-message" class="error-message"></p>

		</div>



		<div class="from-group">

			<button type="submit">Reset Password <img id="loader"

					src="<?php echo get_stylesheet_directory_uri() . '/assets/images/loader.gif'; ?>" alt="Loader"

					style="display:none;"></button>

		</div>



		<div class="singup_text">

			Back to <a class="signup" href="<?php echo home_url('/login'); ?>">Login</a>

		</div>

	</form>



	<script>

		jQuery(document).ready(function ($) {

			// Toggle password visibility

			$(".toggle-passwords").on("click", function () {

				var target = $(this).data("target"); // Get the input field's target

				var input = $(target);



				// Toggle the input type between password and text

				if (input.attr("type") === "password") {

					input.attr("type", "text"); // Show password

					$(this).html('<i class="eye-icon fa fa-eye"></i>'); // Change icon to eye-slash

				} else {

					input.attr("type", "password"); // Hide password

					$(this).html('<i class="eye-icon fa fa-eye-slash"></i>'); // Change icon back to eye

				}

			});



			// Handle form submission via AJAX

			$("#curleejo-reset-password-form").submit(function (e) {

				$('#curleejo-reset-password-form #loader').show();

				e.preventDefault();

				var formData = $(this).serialize(); // Serialize the form data

				$.ajax({

					type: "POST",

					url: "<?php echo admin_url('admin-ajax.php'); ?>", // WordPress AJAX URL

					data: formData + "&action=curleejo_reset_password", // Add the action parameter

					dataType: "json",

					success: function (response) {

						$('#curleejo-reset-password-form #loader').hide();



						var messageContainer = $("#reset-password-message");

						messageContainer.html(response.message); // Show response message



						if (response.success) {

							messageContainer.removeClass("error").addClass("success"); // Add success class and remove error class

							setTimeout(function () {

								window.location.href = "<?php echo home_url('/login/'); ?>"; // Redirect after success

							}, 2000);

						} else {

							messageContainer.removeClass("success").addClass("error"); // Add error class and remove success class

						}

					}

				});

			});





			$('#curleejo-reset-password-form input').on('input', function () {

				$(this).next('.error-message').remove();

				$("#reset-password-message").html("");

			});

		});

	</script>

	<?php

	return ob_get_clean();

}

add_shortcode('curleejo_reset_password', 'custom_curleejo_reset_password_form');







function curleejo_reset_password_ajax_handler()

{

	if (!isset($_POST['key']) || !isset($_POST['user_id']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {

		wp_send_json(['success' => false, 'message' => 'Invalid request.']);

	}



	$key = sanitize_text_field($_POST['key']);

	$user_id = intval($_POST['user_id']);

	$new_password = $_POST['new_password'];

	$confirm_password = $_POST['confirm_password'];



	if (strlen($new_password) < 8) {

		wp_send_json(['success' => false, 'message' => 'Password must be at least 8 characters long.']);

	}



	if ($new_password !== $confirm_password) {

		wp_send_json(['success' => false, 'message' => 'Passwords do not match.']);

	}



	$stored_key = get_user_meta($user_id, 'curleejo_reset_password_key', true);

	$expiry_time = get_user_meta($user_id, 'curleejo_reset_password_expiry', true);



	if (!$stored_key || $stored_key !== $key || time() > $expiry_time) {

		wp_send_json(['success' => false, 'message' => 'Invalid or expired reset link.']);

	}



	// Update password

	wp_set_password($new_password, $user_id);



	// Remove reset key and expiry

	delete_user_meta($user_id, 'curleejo_reset_password_key');

	delete_user_meta($user_id, 'curleejo_reset_password_expiry');



	// Send confirmation email

	$user_info = get_userdata($user_id);

	$email = $user_info->user_email;

	$subject = "Password Successfully Changed";

	$first_name = $user_info->first_name; // Make sure to fetch this from the user object



	$message = '

	<!DOCTYPE html>

	<html>

	<head>

	<meta charset="UTF-8" />

	<style>

		body {

		  font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;

		  background-color: #f4f6f8;

		  margin: 0;

		  padding: 0;

		}

		.email-container {

		  max-width: 500px;

		  margin: 40px auto;

		  background-color: #ffffff;

		  border-radius: 8px;

		  border: 1px solid #ddd;

		  padding: 30px;

		}

		h2, h5 {

		  color: #333333;

		}

		p {

		  font-size: 16px;

		  color: #555555;

		}

		.footer {

		  font-size: 12px;

		  color: #999999;

		  text-align: center;

		  margin-top: 30px;

		}

	</style>

	</head>

	<body>

	<div class="email-container">

		<p style="text-align: center;"><img src="' . get_stylesheet_directory_uri() . '/assets/images/curleejo-logo.png" style="max-width: 200px;width: 100%; padding-bottom: 50px;"></p>

		<h2>Hello ' . esc_html($first_name) . ',</h2>

		<p>This is a confirmation that your password has been successfully changed.</p>

		<p>If you did not request a password reset, please contact our <a href="' . home_url() . '" style="color: #1a73e8 !important;">support team</a> immediately.</p>

		

		<div class="footer">

			&copy; ' . date('Y') . ' EDI - Qatar Foundation. All rights reserved.

		</div>

	</div>

	</body>

	</html>

	';



	$headers = ['Content-Type: text/html; charset=UTF-8'];



	wp_mail($email, $subject, $message, $headers);





	wp_send_json(['success' => true, 'message' => 'Password successfully updated. Redirecting to login...']);

}

add_action('wp_ajax_curleejo_reset_password', 'curleejo_reset_password_ajax_handler');

add_action('wp_ajax_nopriv_curleejo_reset_password', 'curleejo_reset_password_ajax_handler');





