<?php
/*

  Plugin Name: PageLines Section Amazon

  Description: A Section To Create An Amazon Affiliate Link for Pagelines Platform 5.

  Author:      TOCLOCO LTD

  Version:     1.0.0

  PageLines:   PL_Section_Amazon

  Tags:         amazon

  Category:     framework, sections

  Filter:       layout


*/


global $pl_amazon_db_version;
$pl_amazon_db_version_db_version = '2';


/* Initialisation */

register_activation_hook( __FILE__, 'pl_amazon_extension_install' );

function pl_amazon_extension_install() {
	global $wpdb;
	global $pl_amazon_db_version;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	$table_name1 = $wpdb->prefix . 'pl_amazon_asins_cache';	
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql1 = "CREATE TABLE $table_name1 (
		t_id        int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		t_asin      varchar(30) NOT NULL DEFAULT '',
		t_title     varchar(250) NOT NULL DEFAULT '',
		t_author    varchar(200) NOT NULL DEFAULT '',
		t_price     varchar(10) NOT NULL DEFAULT '',
		t_image     varchar(250) NOT NULL DEFAULT '',
		t_checked   varchar(100) DEFAULT NULL,
		PRIMARY KEY  (t_id)
	) $charset_collate;";
	dbDelta( $sql1 );	
	
	add_option( 'pl_amazon_db_version', $pl_amazon_db_version );
}

/* End of initialistaion section */



/* Settings section */



add_action( 'admin_menu', 'pagelines_amazon_extension__add_admin_menu' );
add_action( 'admin_init', 'pagelines_amazon_extension__settings_init' );


function pagelines_amazon_extension__add_admin_menu(  ) { 

	add_menu_page( 'Pagelines Amazon Extension', 'Pagelines Amazon Extension', 'manage_options', 'pagelines_amazon_extension', 'pagelines_amazon_extension__options_page' );

}


function pagelines_amazon_extension__settings_init(  ) { 

	register_setting( 'pl-section-amazon', 'pagelines_amazon_extension__settings' );

	add_settings_section(
		'pagelines_amazon_extension__pluginPage_section', 
		__( 'Amazon MWS Credentials', 'pl_am_ext_' ), 
		'pagelines_amazon_extension__settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'pagelines_amazon_extension_merchant', 
		__( 'Merchant ID', 'pl_am_ext_' ), 
		'pagelines_amazon_extension_merchant_render', 
		'pluginPage', 
		'pagelines_amazon_extension__pluginPage_section' 
	);

	add_settings_field( 
		'pagelines_amazon_extension_access', 
		__( 'MWS Access Key', 'pl_am_ext_' ), 
		'pagelines_amazon_extension_access_render', 
		'pluginPage', 
		'pagelines_amazon_extension__pluginPage_section' 
	);

	add_settings_field( 
		'pagelines_amazon_extension_secret', 
		__( 'MWS Secret Key', 'pl_am_ext_' ), 
		'pagelines_amazon_extension_secret_render', 
		'pluginPage', 
		'pagelines_amazon_extension__pluginPage_section' 
	);

	add_settings_field( 
		'pagelines_amazon_extension_marketplace', 
		__( 'Marketplace ID', 'pl_am_ext_' ), 
		'pagelines_amazon_extension_marketplace_render', 
		'pluginPage', 
		'pagelines_amazon_extension__pluginPage_section' 
	);


}


function pagelines_amazon_extension_merchant_render(  ) { 

	$options = get_option( 'pagelines_amazon_extension__settings' );
	?>
	<input type='text' name='pagelines_amazon_extension__settings[pagelines_amazon_extension_merchant]' value='<?php echo $options['pagelines_amazon_extension_merchant']; ?>'>
	<?php

}


function pagelines_amazon_extension_access_render(  ) { 

	$options = get_option( 'pagelines_amazon_extension__settings' );
	?>
	<input type='text' name='pagelines_amazon_extension__settings[pagelines_amazon_extension_access]' value='<?php echo $options['pagelines_amazon_extension_access']; ?>'>
	<?php

}


function pagelines_amazon_extension_secret_render(  ) { 

	$options = get_option( 'pagelines_amazon_extension__settings' );
	?>
	<input type='text' name='pagelines_amazon_extension__settings[pagelines_amazon_extension_secret]' value='<?php echo $options['pagelines_amazon_extension_secret']; ?>'>
	<?php

}


function pagelines_amazon_extension_marketplace_render(  ) { 

	$options = get_option( 'pagelines_amazon_extension__settings' );
	?>
	<input type='text' name='pagelines_amazon_extension__settings[pagelines_amazon_extension_marketplace]' value='<?php echo $options['pagelines_amazon_extension_marketplace']; ?>'>
	<?php

}


function pagelines_amazon_extension__settings_section_callback(  ) { 

	echo __( 'Acquire these from Amazon', 'pl_am_ext_' );

}


function pagelines_amazon_extension__options_page(  ) { 

	?>
	<form action='options.php' method='post'>

		<h2>Pagelines Amazon Extension</h2>

		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>

	</form>
	<?php

}


/* End of settings section */





/** Check that PL is installed */
if( ! class_exists('PL_Section') ){
  return;
}

class PL_Section_Amazon extends PL_Section {
	function section_persistent(){
	}

	//Use the pl_script and pl_style functions (which enqueues the files)
	function section_styles(){
  		//include any js
  		//pl_script( $this->id, $this->base_url . '/join.js' );
  		pl_style(   'pl-section-amazon-css',  plugins_url( '/css/pl-section-amazon.css', __FILE__ ) );
	}

	function section_opts(){
		$options = array(
			array(
				'label'    => __( 'ASIN', 'pagelines' ),
	        	'type'  => 'text',
	            'key'   => 'asin',
	            'default'  => 'ASIN'
			),
			array(
				'label'    => __( 'Description', 'pagelines' ),
	        	'type'  => 'richtext',
	            'key'   => 'itemDescription',
	            'default'  => 'Item description'
			),
			array(
				'label'    => __( 'Affiliate URL', 'pagelines' ),
	        	'type'  => 'text',
	            'key'   => 'itemURL',
	            'default'  => ''
			),
			pl_std_opt(
				'background_color', 
				array(
					'label' => 'Background color', 
					'help' => __('Choose a background color'),
					'default'   => '#ffffff',
					'key'   => 'back1',
				)
			),
		);
		return $options;
	}

	function amazon_xml($searchTerm) {	
		$timeStamp = trim(date("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()));
		$params = array(
			'Action' => "GetMatchingProductForId",
			'AWSAccessKeyId' => AWS_ACCESS_KEY_ID,
			'MarketplaceId' => MARKETPLACE_ID,
			'SellerId' => MERCHANT_ID,
			'SignatureMethod' => "HmacSHA256",
			'SignatureVersion' => "2",
			'Timestamp'=> $timeStamp,
			'Version'=> "2011-10-01",
			'IdType'=> "ASIN",
			'IdList.Id.1' => $searchTerm[0],
		);
	
		$timeStamp = str_replace('%7E', '~', rawurlencode($timeStamp));	
		
		$url_parts = array();
		ksort($params);
		foreach(array_keys($params) as $key)
		$url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params[$key]));
		//sort($url_parts);
		$url_string = implode("&", $url_parts); $string_to_sign = "GET\nmws.amazonservices.co.uk\n/Products/2011-10-01\n" . $url_string;
		$signature = hash_hmac("sha256", $string_to_sign, AWS_SECRET_ACCESS_KEY, TRUE);$signature = urlencode(base64_encode($signature));
		$url = "https://mws.amazonservices.co.uk/Products/2011-10-01" . '?' . $url_string . "&Signature=" . $signature;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$response = curl_exec($ch);
		//echo "<br><br>" . $response . "<br><br>";
		if (strpos($response,'ErrorResponse') !== false) {
	    	echo 'Error:<br>';
			echo $response . "<br><br>";
			exit();
		}
		$response = str_ireplace("ns2:", "", $response);
		$parsed_xml = simplexml_load_string($response);
		return ($parsed_xml);
	}

	function section_template(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'pl_amazon_asins_cache';	
		
		$options = get_option( 'pagelines_amazon_extension__settings' );
		define('AWS_ACCESS_KEY_ID', $options['pagelines_amazon_extension_access']);
		define ('MERCHANT_ID', $options['pagelines_amazon_extension_merchant']);
		define ('MARKETPLACE_ID', $options['pagelines_amazon_extension_marketplace']);
		define('AWS_SECRET_ACCESS_KEY', $options['pagelines_amazon_extension_secret']);		
		
		$asin = $this->opt('asin');
		$affiliate = $this->opt('affiliate');
		$itemDescription = $this->opt('itemDescription');
		$itemURL = $this->opt('itemURL');
		
		$base_url = "https://mws.amazonservices.co.uk/Products/2011-10-01";
		$method = "GET";
		$host = "mws.amazonservices.co.uk";
		$uri = "/Products/2011-10-01";
		
		$query = "SELECT * FROM $table_name  WHERE t_asin = '$asin'";
		$items = $wpdb->get_results($query);
		if(count($items) == 0){
			$SKUs=array($asin);		
			$itemsXML = $this -> amazon_xml($SKUs);
			$author = $itemsXML -> GetMatchingProductForIdResult -> Products -> Product -> AttributeSets -> ItemAttributes -> Author[0];
			$price  = $itemsXML -> GetMatchingProductForIdResult -> Products -> Product -> AttributeSets -> ItemAttributes -> ListPrice -> Amount;
			$image  = $itemsXML -> GetMatchingProductForIdResult -> Products -> Product -> AttributeSets -> ItemAttributes -> SmallImage -> URL;
			$title  = $itemsXML -> GetMatchingProductForIdResult -> Products -> Product -> AttributeSets -> ItemAttributes -> Title; 
			
			$wpdb->insert($table_name,array(
				't_asin' 		=> wp_unslash($asin), 
				't_author' 		=> wp_unslash($author), 
				't_price' 		=> wp_unslash($price), 
				't_image' 		=> wp_unslash($image), 
				't_title' 		=> wp_unslash($title), 
				't_checked' 	=> wp_unslash(time()),
			));
		} else {
			foreach($items as $item){
				$title 		= $item -> t_title;
				$author 	= $item -> t_author;
				$price 		= $item -> t_price;
				$image 		= $item -> t_image;		
				$delay 		= time() - $item -> t_checked;
				
			}
			
			if($delay > 86400){
				$SKUs=array($asin);		
				$itemsXML = $this -> amazon_xml($SKUs);
				$author = $itemsXML -> GetMatchingProductForIdResult -> Products -> Product -> AttributeSets -> ItemAttributes -> Author[0];
				$price  = $itemsXML -> GetMatchingProductForIdResult -> Products -> Product -> AttributeSets -> ItemAttributes -> ListPrice -> Amount;
				$image  = $itemsXML -> GetMatchingProductForIdResult -> Products -> Product -> AttributeSets -> ItemAttributes -> SmallImage -> URL;
				$title  = $itemsXML -> GetMatchingProductForIdResult -> Products -> Product -> AttributeSets -> ItemAttributes -> Title; 
				$time = time();
				$query = "UPDATE $table_name SET t_title=%s, t_author=%s, t_price=%s, t_image=%s, t_checked=%s WHERE t_asin = %s";
				$rows_affected = $wpdb->query($wpdb->prepare($query, $title, $author, $price, $image, $time ));
			}
		}
		
		
		
		
		
				
		?>
		
		
        <div class="amazon-container pl-trigger" data-bind="style: {'background-color': back1}">
	        <div class="highlightContainer">
	        	<div class="itemImageHolder"><p><img src="<?php echo str_ireplace('_SL75_.', '', $image) ; ?>"></p></div>
	        	<div class="highlights"><h4><?php echo $title; ?></h4>
	        <p><center><?php echo $author; ?></center></p>
	        <p><center>£<?php echo $price; ?></center></p></div>
	        <div class="pl-btn-wrap pl-alignment-default-center">
		        <a class="pl-btn pl-btn-primary pl-btn-st" href="<?php echo $itemURL; ?>"  target="_blank">BUY NOW</a></div>
	        
	        </div>
	        <div class="description">
		        <?php echo $itemDescription; ?>    
	        </div>
	        
	        
	        
		</div>
		<!--
		<div class="join-container2 pl-trigger" data-bind="style: {'background-color': back2, 'height': arrowSize}">
		</div>
		-->
	   <?php
	}
}
