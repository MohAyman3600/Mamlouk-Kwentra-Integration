<?php
// Include the order_tracking.php file
include_once __DIR__ . '/order_tracking.php';

/* ----------------------------------------------------------------------------

   Load CSS

---------------------------------------------------------------------------- */
function my_theme_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );



/* ----------------------------------------------------------------------------

   Load Page Builder Modifications

---------------------------------------------------------------------------- */
if (class_exists('WPBakeryVisualComposerAbstract')) {
	require_once(get_template_directory() . '/framework/inc/pagebuilder/pagebuilder_theme.php');
}



/* ----------------------------------------------------------------------------

   Set Page Builder Template Directory

---------------------------------------------------------------------------- */
if (class_exists('WPBakeryVisualComposerAbstract')) {
	$dir = get_stylesheet_directory() . '/framework/inc/pagebuilder/pagebuilder_templates';
	vc_set_shortcodes_templates_dir( $dir );
}

function getRoomRates($room_id){
    $url =  "https://manage.kwentra.com/api/reservation/rate/v3?filter%7Brate_details.room_types%7D=".$room_id;

     // Request arguments
     $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( 'sally.elakl:Sally8888' ), // Replace with your actual username and password
        ),
    );

    // Send the API request
    $response = wp_remote_request( $url, $args );

    // Check if the request was successful
    if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
        // Request was successful, handle the response data
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        return $data['rates'];
        // Process the response data as needed
    } else {
        // Request failed, handle the error
        $error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Request failed';
        // Handle the error appropriately
        return $error_message;
    }
}

function getRoomTypes(){
    $url =  "https://manage.kwentra.com/api/reservation/rooms/types/";

     // Request arguments
     $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( 'sally.elakl:Sally8888' ), // Replace with your actual username and password
        ),
    );

    // Send the API request
    $response = wp_remote_request( $url, $args );

    // Check if the request was successful
    if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
        // Request was successful, handle the response data
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        return $data;
        // Process the response data as needed
    } else {
        // Request failed, handle the error
        $error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Request failed';
        // Handle the error appropriately
        return $error_message;
    }
}




// Get cart data before loading the checkout form
// function log_data() {
//     // Get the cart object
//     $cart = WC()->cart;
    
//     // Get cart items
//     $cart_items = $cart->get_cart_contents();
// 	$log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/cart.log';
// 	file_put_contents($log_file, print_r($cart_items, true), FILE_APPEND);

// 	// Log the form data to a custom log file
// 	$rooms_avail = getRoomTypes('2023-12-16', '2023-12-17');
//     // $s = $rooms_avail['Superior Twin Room'][0]['room_type_id'];
//     $room_type_id = array_search('Superior Twin Room', array_column($rooms_avail,'name'));
//     $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/avail.log';
// 	file_put_contents($log_file, print_r(getRoomRates($rooms_avail[$room_type_id]['id']), true), FILE_APPEND);

//     $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/session.log';
//     file_put_contents($log_file, print_r($_SESSION, true), FILE_APPEND);

//     $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/room.log';
//     $room = get_post_meta($_SESSION['shb_booking_data'][1]['room_id']);
//     file_put_contents($log_file, print_r($room, true), FILE_APPEND);

//     $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/rate.log';
//     $rate = get_post_meta($_SESSION['shb_booking_data'][1]['rate_id']);
//     file_put_contents($log_file, print_r($rate, true), FILE_APPEND);

//     $guests = $_SESSION['shb_booking_data'][1]['guests'];
//     $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/guests.log';
//     foreach($guests as $key => $value){
//         $s1 =  get_post_meta($key);
//         file_put_contents($log_file, print_r($s1, true), FILE_APPEND);
//     }
		
   
// }
// add_action('woocommerce_before_checkout_form', 'log_data');


// Get cart data before loading the checkout form
function create_kwentra_booking() {
    $session_booking_data = $_SESSION['shb_booking_data'];
    $summary_data_encoded_full['shb_booking_data'][0] = json_encode($_SESSION['shb_booking_data']);
    $booking_summary_full = shb_get_booking_summary($summary_data_encoded_full);
    $sumary_log = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/sumary.log';
    $grouped_data = groupBookingsByDates($session_booking_data, $booking_summary_full['items']);
    file_put_contents($sumary_log, print_r($grouped_data, true), FILE_APPEND);

    $reservations = array();
    foreach($grouped_data as $booking_data){
        // get adults and children guest count
        $adults = 0;
        $children = 0;
        foreach($booking_data as $booking){
            foreach($booking['guests'] as $guest_id => $qty){
                $guest_meta = get_post_meta($guest_id);

                if($guest_meta['shb_guestclass_title_plural'][0] == 'Adults'){
                    if($qty != NULL){
                        $adults += (int) $qty;
                    }
                }else{
                    if($qty != NULL){
                        $children += (int) $qty;
                    }
                }
            }
        }
        
        $reservation = array(
            "roomsData" => array(
                "id"=> 0,
                "adults"=> $adults,
                "children"=> $children
            ),
            "totalAdults"=> $adults,
            "totalChildren" => $children,
            "conf"=> array(
                "selling_type"=> "rooms",
                "currency"=> "EGP",
                "company"=> "147",
                "instance_ids"=> "496",
                "lang"=> "en-us"
            ),
            "total"=> $booking_data['price'],
            "payment_code"=> "",
            "date_format"=> "DD-MM-YYYY",
            "payment_currency"=> "EGP",
            "room_count"=> count($booking_data),
            "selectedBeds"=> [],
            "terms_read"=> false,
            "multiple_instances"=> false,
            "selected_hotel"=> "496",
            "numberOfBeds"=> 0,
            "status"=> null,
            "remarks"=> "",
            "arrival_date"=> $booking_data[0]['checkin'],
            "rooms"=> count($booking_data),
            "departure_date"=> $booking_data[0]['checkout'],
            "room_type"=> ""
        );
        $room_types = getRoomTypes();
        $selectedRooms = array();
        $id_count = 0;
        // Remove price price from the array to avoid looping over it
        unset($booking_data['price']);
        // Loop through each booking room
        foreach ($booking_data as $booking) {
            // Access booking item data
            $room = get_post($booking['room_id']);
            $rate = get_post($booking['rate_id']);
            $room_type_index = array_search($room->post_title, array_column($room_types, 'name'));
            $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/data.log';
            file_put_contents($log_file, print_r([json_encode($room_type_index), json_encode(gettype($room_type_index)), $room->post_title, json_encode(($room_type_index !== false))], true), FILE_APPEND);
            if($room_type_index !== false){
                $room_type_id = $room_types[$room_type_index]['id'];
                $room_rates = getRoomRates($room_type_id);
                if ($rate->post_title == 'Bed and Breakfast'){
                    $rate_name = 'Bed & Breakfast Rate';
                    $rate_code = 'BB Rate';
                }elseif ($rate->post_title == 'Half Board'){
                    $rate_name = 'Half Board Rate';
                    $rate_code = 'HB Rate';
                }elseif ($rate->post_title == 'Bed Only'){
                    $rate_name = 'Bed Only Rate';
                    $rate_code = 'BO Rate';
                }else{
                    $rate_name = '';
                    $rate_code = '';
                }
                $rate_index = array_search($rate_code, array_column($room_rates, 'code'));
                $rate_id = $room_rates[$rate_index]['id'];
                $board_type_id = $room_rates[$rate_index]['board_type'];
            }else{
                $room_type_id = -1;
                $rate_id = -1;
                $rate_code = '';
                $rate_name = '';
                $board_type_id = -1;
            }
            $adults = 0;
            $children = 0;
            foreach($booking['guests'] as $guest_id => $qty){
                $guest_meta = get_post_meta($guest_id);

                if($guest_meta['shb_guestclass_title_plural'][0] == 'Adults'){
                    if($qty != NULL){
                        $adults = (int) $qty;
                    }
                }else{
                    if($qty != NULL){
                        $children = (int) $qty;
                    }
                }
            }
            $single_booking = array(
                'id' => $id_count,
                'roomType' => $room->post_title,
                'rateId' => $rate_id,
                'roomTypeID' => $room_type_id,
                'boardType' => $rate->post_title,
                'boardTypeID' => $board_type_id,
                'ratePlan' => $rate_code,
                'ratePlanID' => $rate_id,
                "amount" => $booking['room_price'],
                'adults' => $adults,
                'children' => $children,
                'selectedRate' => array(
                    'id' => $rate_id,
                    'description' => $rate_name,
                    "daily_rate" => [
                        $booking['room_price']
                      ],
                ),
                'info_set' => true
            );
            $id_count++;
            array_push($selectedRooms, $single_booking);
        }
        $single_reserv = array_merge($reservation, ['selectedRooms'=>$selectedRooms]);
        array_push($reservations, $single_reserv);
    }
    $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/reserv.log';
    file_put_contents($log_file, print_r($reservations, true), FILE_APPEND);

    update_option('current_reservation', $reservations);
}
// Hook the custom_get_cart_data function to an appropriate action
add_action('woocommerce_before_checkout_form', 'create_kwentra_booking');

function getBillingInfo(){
    $data = [
        'billing_first_name' => sanitize_text_field($_POST['billing_first_name']),
        'billing_last_name' => sanitize_text_field($_POST['billing_last_name']),
        'billing_email' => sanitize_email($_POST['billing_email']),
        'billing_phone' => sanitize_text_field($_POST['billing_phone']),
        'billing_phone' => sanitize_text_field($_POST['billing_phone']),
        'billing_address_1' => sanitize_text_field($_POST['billing_address_1']),
        'billing_country' => sanitize_text_field($_POST['billing_country']),
        'billing_city' => sanitize_text_field($_POST['billing_city']),
        'billing_first_name' =>  sanitize_text_field($_POST['billing_first_name']) . sanitize_text_field($_POST['billing_last_name']),
        'billing_phone' => sanitize_text_field($_POST['billing_phone']),
    ];
    $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/test.log';
    file_put_contents($log_file, print_r($data, true), FILE_APPEND);
    update_option('billing_info', $data);
}
add_action('woocommerce_checkout_order_processed', 'getBillingInfo');

function send_api_requests($order_id)
{
    // get billing info data
    $billing_data = get_option('billing_info');

    // Retrieve form data from the checkout form
    $data = [
        'method' => 'post',
        'url' => 'https://manage.kwentra.com/onlinebooking/api/orders/',
        'destList' => array(
            array(
                'id' => 20,
                'name' => 'Giza-Cairo',
                'code' => '20'
            )
        ),
        'firstName' => sanitize_text_field($billing_data['billing_first_name']),
        'lastName' => sanitize_text_field($billing_data['billing_last_name']),
        'email' => sanitize_email($billing_data['billing_email']),
        'mobile' => sanitize_text_field($billing_data['billing_phone']),
        'telephone' => sanitize_text_field($billing_data['billing_phone']),
        'address' => sanitize_text_field($billing_data['billing_address_1']),
        'country' => sanitize_text_field($billing_data['billing_country']),
        'payment_code' => '',
        'payment_currency' => 'EGP',
        'city' => sanitize_text_field($billing_data['billing_city']),
        'name' =>  sanitize_text_field($billing_data['billing_first_name']).' '.sanitize_text_field($billing_data['billing_last_name']),
        'terms_read' => false,
        'phone' => sanitize_text_field($billing_data['billing_phone']),
        'voucher' => '',
        'promo' => '',
        'multiple_instances' => false,
        'status' => null,
        'specific_rooms_selling' => false,
    ];
    $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/post.log';
    file_put_contents($log_file, print_r($data, true), FILE_APPEND);

    // Prepare the payload for the first API request
    $reservations = get_option('current_reservation');
    $successOrders = [];
    $failedOrders = [];
    foreach($reservations as $reservation){
        foreach($reservation['selectedRooms'] as &$room){
            $to_append = array(
                'firstName' => sanitize_text_field($billing_data['billing_first_name']),
                'lastName' => sanitize_text_field($billing_data['billing_last_name']),
                'email' => sanitize_email($billing_data['billing_email']),
                'mobile' => sanitize_text_field($billing_data['billing_phone']),
                'telephone' => sanitize_text_field($billing_data['billing_phone']),
                'address' => sanitize_text_field($billing_data['billing_address_1']),
                'country' => sanitize_text_field($billing_data['billing_country']),
                'remarks' => '',
                'city' => sanitize_text_field($billing_data['billing_city']),
                'primary' => false
            );
            
            $room += $to_append;
        }
        $selected_rooms_str = concatenateArrayToString($reservation['selectedRooms']);
        $first_api_payload = array_merge($data, $reservation);
        $first_api_payload['selected_rooms_str'] = $selected_rooms_str;
        $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/data.log';
        file_put_contents($log_file, print_r($first_api_payload, true), FILE_APPEND);
        $log_file_txt = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/log.txt';
        $json_data = json_encode($first_api_payload, JSON_UNESCAPED_SLASHES);
        file_put_contents($log_file_txt, $json_data . PHP_EOL, FILE_APPEND);


        // Send the first API request
        // Replace FIRST_API_URL with the URL of the first API endpoint
        $first_response = wp_remote_post('https://manage.kwentra.com/onlinebooking/api/orders/', array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode( 'sally.elakl:Sally8888' )
                ),
                'body' => json_encode($first_api_payload, JSON_UNESCAPED_SLASHES),
            )
        );
        $resp_log = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/response.log';
        file_put_contents($resp_log, print_r(wp_remote_retrieve_body($first_response), true), FILE_APPEND);
        
        //collect successfull and failed orders
        if (!is_wp_error($first_response) && wp_remote_retrieve_response_code($first_response) === 201) {
            $first_response_body = wp_remote_retrieve_body($first_response);
            file_put_contents($resp_log, print_r($first_response_body, true), FILE_APPEND);
            // Extract necessary information from the first response
            $first_response_data = json_decode($first_response_body, true);
            $orderID = $first_response_data['orderID'];
            array_push($successOrders, ['orderID'=>$orderID, 'order'=>$first_api_payload]);
            file_put_contents($resp_log, print_r($orderIDs, true), FILE_APPEND);
        }  else {
            // First API request failed
            $first_response_body = wp_remote_retrieve_body($first_response);
            // Extract necessary information from the first response
            $first_response_data = json_decode($first_response_body, true);
            array_push($failedOrders, ['status_code'=>wp_remote_retrieve_response_code($first_response), 'resp_body'=> $first_response_data, 'order'=>$first_api_payload]);
        }
    }

    // Handle the first API response
    if (count($failedOrders) >= 1) {
        // First API request failed
        $first_error_message = wp_remote_retrieve_response_message($first_response);
        // Update the order status to "failed" or any other desired status
        $order = wc_get_order($order_id);
        update_option('kwentra_failed_orders', ['woo_order_id'=> $order_id, 'failed_orders'=>$failedOrders, 'success_orders'=>$successOrders]);
        $fail_log = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/fail.log';
        file_put_contents($fail_log, print_r(['woo_order_id'=> $order_id, 'failed_orders'=>$failedOrders], true), FILE_APPEND);
        
        // Set woocommerce order to failed
         $order->update_status('failed');        
        // Add an error notice to inform the user
        // wc_add_notice(__('There was an issue confirming your order. Please contact support.'), 'error');
    }else{
        $failedPayments = [];
        $successPayments = [];
        foreach($successOrders as $KWENTRA_order){
            // Send the second API request (Payment Submit)
            $url =  "https://manage.kwentra.com/payment/receipt/?order_id=".$KWENTRA_order['orderID']."&hotel=496";
            // Request arguments
            $args = array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( 'sally.elakl:Sally8888' ),
                ),
            );
            // Send the API request
            $second_response = wp_remote_request( $url, $args );
            file_put_contents($resp_log, print_r(wp_remote_retrieve_body($second_response), true), FILE_APPEND);
            // check if failed response
            if (!is_wp_error($second_response) && wp_remote_retrieve_response_code($second_response) === 200) {
                $second_response_body = wp_remote_retrieve_body($second_response);
                array_push($successPayments, ['KWENTRA_order_id' => $KWENTRA_order['order_id'], 'order'=>$KWENTRA_order['order'],'payment_response_body'=> $second_response_body]);
            }else{
                $second_error_message = wp_remote_retrieve_response_message($second_response);
                array_push($failedPayments, ['KWENTRA_order_id' => $KWENTRA_order['order_id'], 'order'=>$KWENTRA_order['order'],'payment_error_message'=> $second_error_message]);
            }
        }

        if (count($failedPayments) >= 1) {
            // First API request failed
            $first_error_message = wp_remote_retrieve_response_message($first_response);

            // Update the order status to "failed" or any other desired status
            $order = wc_get_order($order_id);
            update_option('kwentra_failed_payments', ['woo_order_id'=> $order_id, 'failed_payments'=>$failedPayments]);
            
            // Check if no successful orders created set woocommerce order to failed
            
            // set failed status
            $order->update_status('failed');
            // Add an error notice to inform the user
            // wc_add_notice(__('There was an issue confirming your order. Please contact support.'), 'error');
        }else{
            $order = wc_get_order($order_id);
            $order->update_status('completed');
        }
    }
}


// Hook the callback function to the woocommerce_order_status_processing action
add_action('woocommerce_order_status_processing', 'send_api_requests');

function concatenateArrayToString($array) {
    $jsonString = json_encode($array);
    $concatenatedString = str_replace('"', '\"', $jsonString);
    return $jsonString;
}


function groupBookingsByDates($bookingData, $priceData) {
    $groupedBookings = array();
    
    foreach ($bookingData as $booking) {
        $checkin = $booking['checkin'];
        $checkout = $booking['checkout'];
        
        $key = $checkin . '-' . $checkout;
        
        if (!isset($groupedBookings[$key])) {
            $groupedBookings[$key] = array();
        }
        $price_arrays = searchArrayByColumns($priceData, 'checkin', $checkin, 'checkout', $checkout);
        // Add the corresponding price to the booking
        $price = 0;
        foreach($price_arrays as $price_array){
            $booking['room_price'] = $price_array['price'];
            $price += $price_array['price'];
        }
        $groupedBookings[$key]['price'] = $price;
        
        $groupedBookings[$key][] = $booking;
    }
    
    return array_values($groupedBookings);
}

function searchArrayByColumns($array, $column1, $value1, $column2, $value2) {
    $results = array();

    foreach ($array as $item) {
        if ($item[$column1] == $value1 && $item[$column2] == $value2) {
            $results[] = $item;
        }
    }

    return $results;
}


// Modify the booking page short code to show custom responses in checkout based on the Kwentra API results
// To do this we modify the 'shb_booking_step_4' function responsibe for displaying th HTML in the step 4 if the checkout 
// the we modify the original shortcode function.
// Finnaly we register out new function to the shortcode and remove the old one
function modified_shb_booking_step_4() {
   
	
	if(!empty(get_option('shb_manual_booking_confirmation'))) {
		$booking_confirmation = get_option('shb_manual_booking_confirmation');
	} else {
		$booking_confirmation = 'automatic';
	}
	
	$get_wc_order_status = '';
	
	if(!empty($_GET['key'])) {
		$get_order_id = wc_get_order_id_by_order_key( $_GET['key'] );
		$get_wc_order = wc_get_order( $get_order_id );
		$get_wc_order_status = $get_wc_order->get_status();
        $fail_log = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/fail.log';
        file_put_contents($fail_log, print_r($get_wc_order_status, true), FILE_APPEND);
        // Handle displaying Kwentra Failed Orders to user
        $kwentra_failed_orders = get_option('kwentra_failed_orders');
        $kwentra_failed_payments = get_option('kwentra_failed_payments');
        $FAILED_KWENTRA_HTML = '';
        if(($kwentra_failed_orders['woo_order_id'] == $get_order_id) || ( $kwentra_failed_payments['woo_order_id'] == $get_order_id)){
            error_log('FAILED KWENTRA ORDERS\n');
            $FAILED_KWENTRA_HTML .= '<div class="shb-booking-notification-wrapper mt-3" style="text-align:center;font-weight: bold;">';
            foreach($kwentra_failed_orders['failed_orders'] as $failed_order){
                $FAILED_KWENTRA_HTML .=  '<p><i class="fa fa-frown-open"></i>Unfortunately, The following rooms can not be reserved at this dates:</p>';
                $FAILED_KWENTRA_HTML .=  '<p>Checkin: '.json_encode($failed_order['order']['arrival_date']).'</p>';
                $FAILED_KWENTRA_HTML .=  '<p>Checkout: '.json_encode($failed_order['order']['departure_date']).'</p>';
                foreach($failed_order['order']['selectedRooms'] as $room){
                    $FAILED_KWENTRA_HTML .=  '<p>Room:  '.json_encode($room['roomType']).'   </p>';
                }
            }
            foreach($kwentra_failed_payments['failed_payments'] as $failed_payment){
                $FAILED_KWENTRA_HTML .=  '<p><b>The following rooms can not be reserved at dates:</p>';
                $FAILED_KWENTRA_HTML .=  '<p>Checkin: '.json_encode($failed_payment['order']['arrival_date']).'</p>';
                $FAILED_KWENTRA_HTML .=  '<p>Checkout: '.json_encode($failed_payment['order']['departure_date']).'</p>';
                foreach($failed_order['order']['selectedRooms'] as $room){
                    $FAILED_KWENTRA_HTML .=  '<p>Room:  '.json_encode($room['roomType']).'   </p>';
                }
            }
            $FAILED_KWENTRA_HTML .= '<hr>';
            $FAILED_KWENTRA_HTML .=  '<p><i class="fa fa-smile"></i>The Good News is that the rest of the rooms can be reserved successfully.<br> Please contact support for further details.</p>';
            $FAILED_KWENTRA_HTML .=  '</div>';
            $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/data.log';
            file_put_contents($log_file, print_r($FAILED_KWENTRA_HTML, true), FILE_APPEND);                
        }
	}
	
	if ($get_wc_order_status == 'failed') {
		// echo '<p class="shb-booking-error-4">Error, payment failed</p>';
        ?>
        <div class="shb-booking-page-wrapper shb-clearfix <?php echo shb_get_booking_step_class(); ?>">
		
            <!-- BEGIN .shb-booking-page-main -->
            <div class="shb-booking-page-main shb-booking-complete-step">
        
                <!-- BEGIN .shb-booking-complete-wrapper -->
                <div class="shb-booking-complete-wrapper">
            
                    <i class="fas fa-frown"></i>
                    <h3><?php _e('Booking Failed','sohohotel_booking')?></h3>
        
                <!-- END .shb-booking-complete-wrapper -->
                </div>
                
                <?php echo $FAILED_KWENTRA_HTML; ?>
                    
            </div>
        </div>
        <?php
	} else {
		
		if(!empty($_GET['key'])) {
			$order_id = wc_get_order_id_by_order_key( $_GET['key'] );
			$booking_id = get_post_meta($order_id,'shb_booking_id',TRUE);
		}
	
		if( !empty($order_id) ) {
		
			if(empty($booking_id)) {
            
                
            
				$order_data = get_post_meta($order_id);
			
				if($booking_confirmation == 'automatic') {
					$booking_data['status'] = 'shb_confirmed';
				} else {
					$booking_data['status'] = 'shb_pending';
				}
			
				$booking_data['woocommerce_id'] = $order_id;
				$booking_data['woocommerce_price'] = $_SESSION['shb_total_price'];
				$booking_data['booking_data'] = $_SESSION['shb_booking_data'];
	
				$booking_data['custom_form']['shb_custom_form_first_name'] = $order_data['_billing_first_name'][0];
				$booking_data['custom_form']['shb_custom_form_last_name'] = $order_data['_billing_last_name'][0];
				$booking_data['custom_form']['shb_custom_form_email'] = $order_data['_billing_email'][0];
				$booking_data['custom_form']['shb_custom_form_phone'] = $order_data['_billing_phone'][0];
			
				if(function_exists('shb_add_new_booking_translate')){
				
					$booking_id = shb_add_new_booking_translate($booking_data);
				
				} else {
				
					$booking_id = shb_add_new_booking($booking_data);
				
				}
			
				update_post_meta($order_id,'shb_booking_id',$booking_id);
	
				$_SESSION['shb_booking_data'] = '';
		
			}

           
		
			if( (function_exists('switch_to_blog')) && function_exists('sh_translate') ){ 
				$current_blog_id = get_current_blog_id();
				switch_to_blog(1);
				$email = get_post_meta($booking_id,'shb_custom_form_email',TRUE);
				switch_to_blog($current_blog_id);
			} else {
				$email = get_post_meta($booking_id,'shb_custom_form_email',TRUE);
			}
	
			?>

			<?php if( (!empty($booking_data['woocommerce_id'])) || (!empty($order_id)) ) {
			
				if(!empty($booking_data['woocommerce_id'])) {
					$wc_order_id = $booking_data['woocommerce_id'];
				} else {
					$wc_order_id = $order_id;
				}
				
				$wc_order = wc_get_order( $wc_order_id );
				$wc_order_status  = $wc_order->get_status();
			
				if( ($wc_order_status == 'processing') || ($wc_order_status == 'completed') ) {
					
					if($booking_confirmation == 'automatic') {
						shb_send_booking_email($booking_id,'booking_confirmed','guest');
						shb_send_booking_email($booking_id,'booking_confirmed','admin');
					} else {
						shb_send_booking_email($booking_id,'booking_pending','guest');
						shb_send_booking_email($booking_id,'booking_pending','admin');
					}
					
					if($booking_confirmation == 'automatic') {
						$status = 'shb_confirmed';
					} else {
						$status = 'shb_pending';
					}
					
				} else {
					$status = 'shb_pending';
				}
			
				if( (function_exists('switch_to_blog')) && function_exists('sh_translate') ){ 
				
					$current_blog_id = get_current_blog_id();
					switch_to_blog(1);
				
					$booking = array( 'ID' => $booking_id, 'post_status' => $status );
					wp_update_post($booking);
				
					switch_to_blog($current_blog_id);
				
				} else {
				
					$booking = array( 'ID' => $booking_id, 'post_status' => $status );
					wp_update_post($booking);
				
				}
			
			
			} ?>


		
			<!-- BEGIN .shb-booking-page-wrapper -->
			<div class="shb-booking-page-wrapper shb-clearfix <?php echo shb_get_booking_step_class(); ?>">
		
				<!-- BEGIN .shb-booking-page-main -->
				<div class="shb-booking-page-main shb-booking-complete-step">
			
					<!-- BEGIN .shb-booking-complete-wrapper -->
					<div class="shb-booking-complete-wrapper">
                        
                    <?php if($wc_order_status == 'pending'){ ?>
                        <h3><?php _e('Booking On Hold!','sohohotel_booking') ?> (#<?php echo $booking_id; ?>)</h3>
                    <? }else{?>
						<i class="fas fa-check"></i>
						<h3><?php _e('Booking Complete!','sohohotel_booking') ?> (#<?php echo $booking_id; ?>)</h3>
                    <?php } ?>
					<!-- END .shb-booking-complete-wrapper -->
					</div>

                    <?php echo $FAILED_KWENTRA_HTML; ?>

                    <?php if($wc_order_status == 'pending'){ ?>
                        <div style="font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5; margin: 20px;">
                            <p>We apologize for the inconvenience, but it appears that there was an issue processing your payment. Your reservation has been successfully made and is currently in pending status. Please note your booking number for reference: <span style="font-weight: bold;"><?php echo $booking_id; ?></span>.</p>
                            <p>To ensure your reservation is confirmed, we kindly request that you complete the payment within the next 24 hours. You will receive an email shortly with payment instructions. Please note that if payment is not received within the given time frame, your reservation will be automatically canceled.</p>
                            <p>If you have any questions or need assistance with the payment process, please don't hesitate to contact our customer support team at <span style="color: #007bff; text-decoration: none;">reservations@mamloukpyramidshotel.com</span>. We are available 24/7 to help resolve any issues and ensure a smooth reservation experience for you.</p>
                            <p>Thank you for choosing our hotel reservation website. We appreciate your patience and cooperation in resolving this matter promptly.</p>
                        </div>
                    <?php }else{ ?>
			
                        <!-- BEGIN .shb-booking-notification-wrapper -->
                        <div class="shb-booking-notification-wrapper">
                    
                            <p><i class="fas fa-envelope"></i><?php echo sprintf( __( 'A confirmation email has been sent to %s', 'sohohotel_booking' ), $email ); ?></p>
                
                        <!-- END .shb-booking-notification-wrapper -->
                        </div>
                
                        <h3 class="sohohotel-title-20px sohohotel-clearfix sohohotel-title-left"><?php _e('Thank You','sohohotel_booking'); ?></h3>
                        <p class="shb-booking-confirmation-message"><?php echo get_option('shb_booking_success_message'); ?></p>
                
                        <h3 class="sohohotel-title-20px sohohotel-clearfix sohohotel-title-left"><?php _e('Check In','sohohotel_booking'); ?></h3>
                        <ul class="shb-booking-checkin-checkout">
                            
                            <?php $check_in_date = get_post_meta($booking_id,'shb_checkin',TRUE);
                            $check_out_date = get_post_meta($booking_id,'shb_checkout',TRUE);
                            $check_in_time = get_option('shb_checkin_time');
                            $check_out_time = get_option('shb_checkout_time'); ?>
                            
                            <li><span><?php _e('Check In','sohohotel_booking'); ?>:</span> <?php echo sprintf( __( '%s at %s', 'sohohotel_booking' ), $check_in_date, $check_in_time ); ?></li>
                            <li><span><?php _e('Check Out','sohohotel_booking'); ?>:</span> <?php echo sprintf( __( '%s at %s', 'sohohotel_booking' ), $check_out_date, $check_out_time ); ?></li>
                            
                        </ul>
                
                        <h3 class="sohohotel-title-20px sohohotel-clearfix sohohotel-title-left"><?php _e('Find Us','sohohotel_booking'); ?></h3>
                        <ul class="sohohotel-contact-details-list">
                            <li class="sohohotel-address clearfix"><?php echo get_option('shb_hotel_address'); ?></li>
                            <li class="sohohotel-phone clearfix"><a href="tel:<?php echo get_option('shb_hotel_phone'); ?>"><?php echo get_option('shb_hotel_phone'); ?></a></li>
                            <li class="sohohotel-email clearfix"><a href="mailto:<?php echo get_option('shb_email_address'); ?>"><?php echo get_option('shb_email_address'); ?></a></li>
                        </ul>
                
                    <!-- END .shb-booking-page-main -->
                    </div>
            
                <!-- END .shb-booking-page-wrapper -->
                </div>
            <?php } ?>
		<?php } else {
		
			echo '<p class="shb-booking-error-4">Error, no booking to be processed</p>';
		
		}
		
	}

}



function modified_shb_booking_page_shortcode( $atts, $content = null ) {
	
	ob_start(); 
	
	if ( class_exists( 'WooCommerce' ) ) {
		
		if(!empty($_GET['key'])) {
			$order_id = wc_get_order_id_by_order_key( $_GET['key'] );
		}
	
		if(!empty($order_id)) {
            // Use our fmodified function
			echo modified_shb_booking_step_4();
			
			echo '<div style="display: none;">';
			echo do_shortcode('[woocommerce_checkout]');
			echo '</div>';
			
		} else {
		
			if(!empty($_GET['shb-step'])) {
		
				if($_GET['shb-step'] == 2) {
					echo shb_booking_step_2();
				} elseif($_GET['shb-step'] == 3) {
					echo shb_booking_step_3();
				} elseif($_GET['shb-step'] == 4) {
                    // Use out modified function
					echo modified_shb_booking_step_4();
				} else {
					echo shb_booking_step_1();
				}
		
			} else {
				echo shb_booking_step_1();
			}
		
		}
		
	} else {
		
		echo '<p>Please install and activated the "WooCommerce" plugin.</p>';
		
	}
	
	return ob_get_clean();

}

function register_modified_shortcode() {
    remove_shortcode('shb_booking_page'); // Remove the original shortcode
    add_shortcode('shb_booking_page', 'modified_shb_booking_page_shortcode'); // Register the modified shortcode
}
add_action('after_setup_theme', 'register_modified_shortcode');



?>

