<?php

// Include the GF framework
GFForms::include_addon_framework();


// CATCH ALL for CONSTANTS 
if (!defined('ZS_TOKEN_VALIDATION_ENDPOINT')){
	define('ZS_TOKEN_VALIDATION_ENDPOINT','');
}
if (!defined('ZS_SUBMISSION_ENDPOINT')){
	define('ZS_SUBMISSION_ENDPOINT','');
}


/*
 * Extend the GF addon class.. 
 * 
 * Add settings to the GF and 
 * Add a settings to individual forms 
 */
class GFzs_leads_addon extends GFAddOn {

	protected $_version = GF_SIMPLE_ADDON_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'zolaleads';
	protected $_path = 'zolaleads/zolaleads.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Zola CRM Add-on';
	protected $_short_title = 'Zola CRM';

	private static $_instance = null;

	
	/**
	 * Get an instance of this class.
	 * @return GFzs_leads_addon
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFzs_leads_addon();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
	}



	// # FRONTEND FUNCTIONS --------------------------------------------------------------------------------------------

	/**
	 * Add the text in the plugin settings to the bottom of the form if enabled for this form.
	 * @return string
	 */
	function form_submit_button( $button, $form ) {
		return $button;
	}


	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------
	
	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Form lead submission settings', 'zs_leads_addon' ),
				'fields' => array(
					array(
						'name'              => 'zs_token',
						'label'             => esc_html__( 'Token', 'zs_leads_addon' ),
						'tooltip'           => esc_html__( 'Unique Token which identifies the client with Zola CRM', 'zs_leads_addon' ),
						'type'              => 'text',
						'class'             => 'small',
						'feedback_callback' => array( $this, 'is_valid_token' ),
					),
				)
			)		
		);
	}

	
	/**
	 * Configures the settings which should be rendered on the Form Settings > tab.
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => esc_html__( 'Zola CRM Form Settings', 'zs_leads_addon' ),
				'fields' => array(
					array(
						'label'   => esc_html__( 'Enable lead submission', 'zs_leads_addon' ),
						'tooltip' => esc_html__( 'Allow the form to submit leads to Zola CRM', 'zs_leads_addon' ),			
						'type'    => 'checkbox',
						'name'    => 'enabled',
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'zs_leads_addon' ),
								'name'  => 'enabled',
							),
						),
					),
					array(
						'label'   => esc_html__( 'Field Mapping', 'zs_leads_addon' ),
						'type'    => 'field_map',
						'name'    => 'zs_mapping',
						'tooltip' => esc_html__( 'Map the fields that will submit to Zola CRM', 'zs_leads_addon' ),
						'field_map' => $this->standard_fields_for_feed_mapping(),
					),
				),
			),
		);
	}

	
	public function field_map_table_header(){
		
		$header = "
		<thead>
			<tr>
				<th>Zola CRM Fields</th>
				<th>Gravity Forms Fields</th>
			</tr>
		</thead>";

		return $header;
	}
	
	
	/**
	 * contains Array with the fields settings for mapping
	 * @return array
	 */	
	public function standard_fields_for_feed_mapping() {
		return array(
			array(
				'name'          => 'zs_first_name',
				'label'         => esc_html__( 'First Name', 'zs_leads_addon' ),
				'required'      => true,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'name', 3 ),
			),
			array(
				'name'          => 'zs_last_name',
				'label'         => esc_html__( 'Last Name', 'zs_leads_addon' ),
				'required'      => true,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'name', 6 ),
			),
			array(
				'name'          => 'zs_email_address',
				'label'         => esc_html__( 'Email Address', 'zs_leads_addon' ),
				#'required'      => true,
				'field_type'    => array( 'email', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'email' ),
			),
			array(
				'name'          => 'zs_phone',
				'label'         => esc_html__( 'Phone Number', 'zs_leads_addon' ),
				#'required'      => true,
				'field_type'    => array( 'phone', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'phone' ),
			),			
			array(
				'name'          => 'zs_message',
				'label'         => esc_html__( 'Message', 'zs_leads_addon' ),
				#'required'      => true,
				'field_type'    => array( 'textarea','text', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'textarea' ),
			)
		);
	}


	// # FORM SUBMISSION -----------------------------------------------------------------------------------------------	
	
	
	/**
	 * Performing a custom action at the end of the form submission process.
	 *
	 * @param array $entry The entry currently being processed.
	 * @param array $form The form currently being processed.
	 */
	public function after_submission( $entry, $form ) {

		// Get the settings from the form
		$settings = $this->get_form_settings( $form );
		$settings = is_array($settings) ? $settings : array( "enabled" => 0);

		// Get settings from plugin 
		$token = $this->get_plugin_setting('zs_token');
		$submission_endpoint = ZS_SUBMISSION_ENDPOINT;
		
		
		//Set the mapping values 
		$values = array();
		$values['Token'] = !empty($token)?$token:'';
		$values['FirstName'] = !empty($settings['zs_mapping_zs_first_name'])?rgar( $entry, $settings['zs_mapping_zs_first_name'] ):'';
		$values['LastName'] = !empty($settings['zs_mapping_zs_last_name'])?rgar( $entry, $settings['zs_mapping_zs_last_name'] ):'';
		$values['Email'] = !empty($settings['zs_mapping_zs_email_address'])?rgar( $entry, $settings['zs_mapping_zs_email_address'] ):'';
		$values['Phone'] = !empty($settings['zs_mapping_zs_phone'])?rgar( $entry, $settings['zs_mapping_zs_phone'] ):'';
		$values['Message'] = !empty($settings['zs_mapping_zs_message'])?rgar( $entry, $settings['zs_mapping_zs_message'] ):'';
		$values['SubmissionUrl'] = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$values['IpAddress'] = $this->zs_get_user_ip();
		$values['ReferrerUrl'] = $this->zs_get_cookie_referrer();
		$values['LandingPage'] = $this->zs_get_cookie_landing();
		$values['VisitorHistory'] = $this->zs_get_cookie_data();
		
		if ($settings['enabled'] != 0 || !empty($token) || !empty($submission_endpoint)){
			$submittoCRM = $this->zs_submit_curl($submission_endpoint,$values);
		}

	}


	// # HELPERS -------------------------------------------------------------------------------------------------------

	
	/*
	 * Validate the TOKEN to make sure all submissions work
	 * @return bool
	 */
	public function is_valid_token( $value ) {
		
		// Setting variables 
		$token_validation_endpoint = ZS_TOKEN_VALIDATION_ENDPOINT;
		$token = $this->get_plugin_setting('zs_token');
		
		$validate_token =  $this->zs_submit_curl(
			$token_validation_endpoint,
			array(
				'Token' => $token //'efa3a9abaa3d86c4'
			)
		);
		
		if ( !empty($validate_token['result']) && $validate_token['result'] == true ){
			return true;
		} else {
			return false;
		}
	}
	

	/* 
	 * Check to make sure its a valid URL 
	 * @return bool
	 */
	public function is_valid_url( $value ) {

		$validate_token =  $this->zs_submit_curl( $value,
			array(
				'Token' => 'validating' // Providing a fake token still returns a json response 
			)
		);
		
		if (isset($validate_token['result'])){
			return true;
		} else {
			return false;
		}
	}#End is_valid_url

	
	/* 
	 * Curl Funtion to POST data to endpoints 
	 * @return bool
	 */
	public function zs_submit_curl($enpoint,$data){
		
		if (empty($enpoint) || empty($data) ){
			return false;
		}

		$json = json_encode($data);
		$response = wp_remote_post( $enpoint, array(
			'method' => 'POST',
			'sslverify'   => false,
			//'timeout' => 45,
			//'redirection' => 5,
			//'httpversion' => '1.0',
			//'blocking' => true,
			'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
			'body' => $json,
			//'cookies' => array()
    	));
		
		$decoded = json_decode($response['body'], true);
		return $decoded;		
		
	}#End zs_submit_curl
	

	/* 
	 * Funtion gets and parses the cookie data 
	 * @return Array
	 */
	public function zs_get_cookie_data(){
		
		// Get the cookie data (which was already escaped in the JS) 
		$ck_referrer_date = !empty($_COOKIE['referrer_date'])? explode(";", sanitize_text_field(urldecode($_COOKIE['referrer_date'])) ):'';
		$ck_referrer = !empty($_COOKIE['referrer'])? explode(";", sanitize_text_field(urldecode($_COOKIE['referrer'])) ):'';
		$ck_current = !empty($_COOKIE['current'])? explode(";", sanitize_text_field(urldecode($_COOKIE['current'])) ):'';

		$visitor = array();
		$index = 0;
		if (is_array($ck_current)){
			foreach ($ck_current as $val){

				$date = strtotime($ck_referrer_date[$index]);
				$date2 = !empty($ck_referrer_date[$index + 1])?strtotime($ck_referrer_date[$index + 1]):time();

				$difference = $date2 - $date;

				// Set our time variables 
				$second= 1;$minute= 60*$second;$hour= 60*$minute;$day= 24*$hour;


				// Calculate time differences in days/hours/minutes/seconds
				$t["d"] = floor($difference/$day);
				$t["h"] = floor(($difference%$day)/$hour);
				$t["m"] = floor((($difference%$day)%$hour)/$minute);
				$t["s"] = floor(((($difference%$day)%$hour)%$minute)/$second);			

				// Build our "time spent string" 
				$timespent = "";
				$timespent .= (!empty($t['d']))? $t['d']." Days ":'';
				$timespent .= (!empty($t['h']))? $t['h']." Hours ":'';
				$timespent .= (!empty($t['m']))? $t['m']." Minutes ":'';
				$timespent .= (!empty($t['s']))? $t['m']." Seconds ":'';

				// Clean up string 
				$timespent = trim($timespent);

				// build the array 
				$visitor[] = array(
					"VisitorType" => 2,
					"Url" => $val,
					"VisitedDate" => date("Y, m, d",$date),
					"TimeSpentOnPage" =>  $timespent				
				);

				$index++;
			}#End foreach
		}#End if 
		
		return $visitor;
		
		
	}#End zs_get_cookie_data

	/* 
	 * Funtion gets and parses the cookie data and checks for the first "Referrer"
	 * @return String 
	 */
	public function zs_get_cookie_referrer(){
		
		$ck_referrer = !empty($_COOKIE['referrer'])?explode(";", sanitize_text_field(urldecode($_COOKIE['referrer']))):'';
		
		if (!empty($ck_referrer[0])){
			return($ck_referrer[0]);
		} else {
			return $_SERVER['HTTP_REFERER'];
		}
	}#End zs_get_cookie_referrer;
	

	/* 
	 * Funtion gets and parses the cookie data and checks for the first "landing page"
	 * @return String 
	 */
	public function zs_get_cookie_landing(){
		
		$ck_current = !empty($_COOKIE['current'])?explode(";", sanitize_text_field(urldecode($_COOKIE['current']))):'';
		
		if (!empty($ck_current[0])){
			return($ck_current[0]);
		} else {
			return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		}
	}#End zs_get_cookie_landing;	
	
	
	
	/* 
	 * Funtion gets the IP address of the user 
	 * @return Array
	 */
	public function zs_get_user_ip(){
		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];

		if(filter_var($client, FILTER_VALIDATE_IP))    {
			$ip = $client;
		}  elseif(filter_var($forward, FILTER_VALIDATE_IP))    {
			$ip = $forward;
		}  else   {
			$ip = $remote;
		}

		return $ip;
	}	

}

