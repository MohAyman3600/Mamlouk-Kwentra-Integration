<?php
// Define your custom WPBakery element
function my_custom_booking_tracking_form_element() {
   vc_map(
      array(
         'name' => 'Booking Tracking Form',
         'base' => 'booking_tracking_form',
         'category' => 'My Category',
         'params' => array(
            array(
               'type' => 'textfield',
               'heading' => 'Header Title',
               'param_name' => 'header_title',
               'value' => 'Check Your Reservation',
               'description' => 'Enter the title for the header',
            ),
            array(
               'type' => 'textfield',
               'heading' => 'Text',
               'param_name' => 'text',
               'value' => 'To track your order please enter your Order ID in the box below and press the "Track" button. This was given to you on your receipt and in the confirmation email you should have received.',
               'description' => 'Enter the text to be displayed above the form',
            ),
            // Add your custom parameters for the booking tracking form
         ),
      )
   );
}
add_action('vc_before_init', 'my_custom_booking_tracking_form_element');

function booking_tracking_form_shortcode($atts, $content = null) {
   // Process attributes
   $atts = shortcode_atts(
      array(
         'email_field' => 'true',
         'header_title' => 'Check Your Reservation',
         'text' => '',
      ),
      $atts
   );

   // Generate the booking tracking form HTML
   $output = '';

   // Add the header with title
   $output .= '<h3 style="text-align:center">' . $atts['header_title'] . '</h3>';

   // Add the dynamic text paragraph above the form
   if (!empty($atts['text'])) {
      $output .= '<p>' . $atts['text'] . '</p>';
   }

   $output .= '<form class="booking-tracking-form" method="post">';
   $output .= '<input type="text" name="booking_id" placeholder="Enter your booking number" required>';
   
   if ($atts['email_field'] === 'true') {
      $output .= '<input type="email" name="user_email" placeholder="Enter your email" required>';
   }

   $output .= '<input type="submit" name="track_booking" value="Track Booking">';
   $output .= '</form>';

   // Check if the form is submitted
   if (isset($_POST['track_booking'])) {
       $booking_id = sanitize_text_field($_POST['booking_id']);
       $user_email = '';
       
       if ($atts['email_field'] === 'true') {
           $user_email = sanitize_email($_POST['user_email']);
       }

       // Retrieve the booking post using the booking number and user email
       $booking_args = array(
           'post_type' => 'shb_booking',
           'meta_query' => array(
               'relation' => 'AND',
               array(
                   'key' => 'id',
                   'value' => $booking_id,
                   'compare' => '=',
               ),
               array(
                   'key' => 'shb_custom_form_email',
                   'value' => $user_email,
                   'compare' => '=',
               ),
           ),
       );

      $booking = get_post_meta($booking_id);
      $log_file = '/home/wplive/web/wp-live/wp-content/themes/soho-hotel-child/post.log';
      file_put_contents($log_file, print_r($booking, true), FILE_APPEND);  

       if ($booking) {
           // Display the booking information
           if($booking['shb_custom_form_email'][0] == $user_email) {
               $output = '';   
               $get_order_id = $booking['shb_woocommerce_id'][0];
               $data = $booking['shb_booking_data']; 
               $data_array = json_decode($data[0], true);
               $room_id = $data_array[1]['room_id'];

               $output .= '<div class="shb-booking-page-wrapper shb-clearfix">';        
               $output .= '<div class="shb-booking-page-main shb-booking-complete-step">';
               $output .= '<div class="shb-booking-complete-wrapper">';
               if($wc_order_status == 'pending'){
                  $output .= '<h3> Booking On Hold! (#'. $booking_id .')</h3>';
                  $output .= '<div style="font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5; margin: 20px;">';
                  $output .= '<p>We apologize for the inconvenience, but it appears that there was an issue processing your payment. Your reservation has been successfully made and is currently in pending status. Please note your booking number for reference: <span style="font-weight: bold;">'.$booking_id.'</span>.</p>';
                  $output .= '<p>To ensure your reservation is confirmed, we kindly request that you complete the payment within the next 24 hours. You will receive an email shortly with payment instructions. Please note that if payment is not received within the given time frame, your reservation will be automatically canceled.</p>';
                  $output .= '<p>If you have any questions or need assistance with the payment process, please don\'t hesitate to contact our customer support team at <span style="color: #007bff; text-decoration: none;">reservations@mamloukpyramidshotel.com</span>. We are available 24/7 to help resolve any issues and ensure a smooth reservation experience for you.</p>';
                  $output .= '<p>Thank you for choosing our hotel reservation website. We appreciate your patience and cooperation in resolving this matter promptly.</p>';
                  $output .= '</div>';
               }else{
                  $output .= '<h3> Booking! (#'. $booking_id .')</h3>';
                  $output .= '<h3 class="sohohotel-title-20px sohohotel-clearfix sohohotel-title-left">' . __('Booking Info','sohohotel_booking') . '</h3>';
                  $output .= '<ul class="shb-booking-checkin-checkout">';

                  $customer_name = get_post_meta($booking_id,'shb_custom_form_first_name',TRUE).' '.get_post_meta($booking_id,'shb_custom_form_last_name',TRUE) ;
                  $customer_email = get_post_meta($booking_id,'shb_custom_form_email',TRUE);
                  $customer_phone = get_post_meta($booking_id,'shb_custom_form_phone',TRUE);
                 

                  $output .= '<li><span>' . __('Billing Name','sohohotel_booking') . ':</span> ' . $customer_name . '</li>';
                  $output .= '<li><span>' . __('Billing Email','sohohotel_booking') . ':</span> ' . $customer_email . '</li>';
                  $output .= '<li><span>' . __('Billing Email','sohohotel_booking') . ':</span> ' . $customer_phone . '</li>';

                  $output .= '</ul>';
                  $output .= '<h3 class="sohohotel-title-20px sohohotel-clearfix sohohotel-title-left">' . __('Check In','sohohotel_booking') . '</h3>';
                  $output .= '<ul class="shb-booking-checkin-checkout">';

                  $check_in_date = get_post_meta($booking_id,'shb_checkin',TRUE);
                  $check_out_date = get_post_meta($booking_id,'shb_checkout',TRUE);
                  $check_in_time = get_option('shb_checkin_time');
                  $check_out_time = get_option('shb_checkout_time');
                  $nights = $data_array[1]['nights'];
                  $adults = get_post_meta($booking_id,'shb_114_qty',TRUE);
                  $children = get_post_meta($booking_id,'shb_313_qty',TRUE);
                  $room_type = get_the_title($room_id);

                  $output .= '<li><span>' . __('Check In','sohohotel_booking') . ':</span> ' . sprintf(__( '%s at %s', 'sohohotel_booking' ), $check_in_date, $check_in_time) . '</li>';
                  $output .= '<li><span>' . __('Check Out','sohohotel_booking') . ':</span> ' . sprintf(__( '%s at %s', 'sohohotel_booking' ), $check_out_date, $check_out_time) . '</li>';
                  $output .= '<li><span>' . __('Nights','sohohotel_booking') . ':</span> ' . $nights . '</li>';
                  $output .= '<li><span>' . __('Adults','sohohotel_booking') . ':</span> ' . $adults . '</li>';
                  $output .= '<li><span>' . __('Children','sohohotel_booking') . ':</span> ' . $children . '</li>';
                  $output .= '<li><span>' . __('Room','sohohotel_booking') . ':</span> ' . $room_type . '</li>';

                  $output .= '</ul>';

                  // Start output buffering
                  ob_start();

                  // Trigger the WooCommerce action and capture the output
                  do_action('woocommerce_view_order', $get_order_id);

                  // Get the captured output and assign it to a variable
                  $output .= ob_get_clean();

                  $output .= "<style> .woocommerce-order-details__title{font-size:20px !important;} .woocommerce-order-details__title:after {margin: 32px 0;background: #b99470;content: '';display: block;width: 50px;height: 2px;}</style>";

               }  
               $output .= '</div></div></div>';
               
            }
       } else {
           // Display a notification if no booking is found
           $output .= '<div class="shb-booking-notification-wrapper">';
           $output .= '<p class="booking-not-found">No booking found with the provided details.</p>';
           $output .= '</div>';
       }

       // Reset the post data
       wp_reset_postdata();
   }

   return $output;
}
add_shortcode('booking_tracking_form', 'booking_tracking_form_shortcode');


 
 ?>