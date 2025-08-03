<?php
// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ecehc_Form_Page {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_survey_form_menu' ) );
		add_action( 'admin_post_ecehc_submit_survey_form', array( $this,'ecehc_handle_survey_form_submission' ) );
	}

	public function add_survey_form_menu() {

		add_submenu_page(
        	NULL, 
        	'Form', 
        	'Form',
        	'manage_options',
        	'ecehc-survey-form-page',
        	array( $this, 'ecehc_render_form_page' )
    	);
	}

	public function ecehc_render_form_page() {

		include_once ecehc_PLUGIN_PATH . 'views/form-page.php';
	}

	/**
 	* Handles the form submission to Google Sheet.
 	* This function hooks into 'admin_post_ecom_submit_to_google_sheet' action.
 	* This action is triggered when a form with <input type="hidden" name="action" value="ecom_submit_to_google_sheet">
 	* is submitted to admin-post.php.
 	*/
	public function ecehc_handle_survey_form_submission() {
		// 1. Verify Nonce for security.
		// This checks if the request originated from our form and not a malicious site.
		if ( ! isset( $_POST['ecehc_survey_form_nonce'] ) || ! wp_verify_nonce( $_POST['ecehc_survey_form_nonce'], 'ecehc_submit_survey_form' ) ) {
			wp_die( 'Security check failed. Please try again.' );
		}

		// 2. Check if the current user has the required capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		// 3. Collect and sanitize form data.
		// Use sanitize_text_field for basic text inputs.
		$name    = sanitize_text_field( $_POST['name'] ?? '' );
		$email   = sanitize_email( $_POST['email'] ?? '' );
		$message = sanitize_textarea_field( $_POST['message'] ?? '' );

		// Basic validation.
		if ( empty( $name ) || empty( $email ) || empty( $message ) || ! is_email( $email ) ) {
			add_settings_error(
				'ecehc_survey_form',
				'ecom_form_error',
				__( 'Please fill in all required fields correctly.', 'ecehc' ),
				'error'
			);
			// Redirect back to the form page with the error.
			wp_redirect( add_query_arg( 'page', 'ecom-google-sheet-form', admin_url( 'admin.php' ) ) );
			exit();
		}

		// 4. Prepare data for sending to Google Apps Script.
		$formData = [
			'name'    => $name,
			'email'   => $email,
			'message' => $message
		];

		// 5. Use WordPress's HTTP API (wp_remote_post) to send the POST request to Google Apps Script.
		// This is preferred over cURL directly as it handles various transport methods and errors.
		$response = wp_remote_post( ecehc_GSS_WEB_APP_URL, [
			'method'      => 'POST',
			'timeout'     => 45, // seconds
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => [],
			'body'        => $formData, // wp_remote_post will automatically encode this for POST.
			'cookies'     => []
		]);

		// 6. Handle the response from Google Apps Script.
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			add_settings_error(
				'ecehc_survey_form',
				'ecehc_survey_form_error',
				sprintf( __( 'Error submitting form: %s', 'ecehc' ), $error_message ),
				'error'
			);
		} else {
			$http_code = wp_remote_retrieve_response_code( $response );
			$body      = wp_remote_retrieve_body( $response );

			if ( $http_code >= 200 && $http_code < 300 ) {
				add_settings_error(
					'ecehc_survey_form',
					'ecehc_survey_form_success',
					__( 'Form data successfully sent to Google Sheet!', 'ecehc' ),
					'success'
				);
			} else {
				add_settings_error(
					'ecehc_survey_form',
					'ecehc_survey_form_error',
					sprintf( __( 'Failed to send data to Google Sheet. HTTP Code: %d. Response: %s', 'ecehc' ), $http_code, $body ),
					'error'
				);
			}
		}

		// Redirect back to the form page.
		wp_redirect( add_query_arg( 'page', 'ecehc-survey-form-page', admin_url( 'admin.php' ) ) );
		exit();
	}


}