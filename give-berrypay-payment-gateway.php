<?php
/*
Plugin Name:  BerryPay GiveWP Payment Gateway
Plugin URI:   https://github.com/technicalbpm/givewp-berrypay
Description:  BerryPay Payment Gateway Support for Give Donation Platform
Version:      1.0
Author:       BerryPay
Author URI:   https://berrypay.com/
License:      GPL3
License URI:  https://github.com/feedsbrain/give-berrypay-payment-gateway/blob/master/LICENSE
*/
if (! defined( 'ABSPATH' )) {
    exit;
}

/* Plugin Debugging */
if (!function_exists('write_log')) {
    function write_log($log)  {
       if (is_array($log) || is_object($log)) {
          error_log(print_r($log, true));
       } else {
          error_log($log);
       }
    }
 }
 /* End of Plugin Debugging */

 /* BerryPay Functions */
 if (!function_exists('format_amount')){
    function format_amount($amt)
    {
        $remove_dot = str_replace('.', '', $amt);
        $remove_comma = str_replace(',', '', $remove_dot);
        return $remove_comma;
    }
}
if (!function_exists('berrypay_signature')){
    function berrypay_signature($source)
    {
        return base64_encode(hex2bin(sha1($source)));
    }
}
if (!function_exists('hex2bin')){
    function hex2bin($hexSource)
    {
        for ($i=0;$i<strlen($hexSource);$i=$i+2)
        {
            $bin .= chr(hexdec(substr($hexSource,$i,2)));
        }
        return $bin;
    }
}
 /* End of BerryPay Functions */

/* Plugin Dependencies */
function check_give_plugin_dependency() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'give/give.php' ) ) {
        add_action( 'admin_notices', 'give_plugin_notification' );

        deactivate_plugins( plugin_basename( __FILE__ ) ); 

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}
function give_plugin_notification(){
    ?><div class="error"><p>Sorry, but <strong>Give BerryPay Payment Gateway</strong> requires the <strong><a href="/wp-admin/plugin-install.php?tab=plugin-information&plugin=give">Give - Donation Plugin</a></strong> to be installed and active.</p></div><?php
}
add_action( 'admin_init', 'check_give_plugin_dependency' );
/* End of Plugin Dependencies */

/* Disabled Plugin Activation Link */
function give_berrypay_payment_gateway_activation( $links, $file ) {
    if ( 'give-berrypay-payment-gateway/give-berrypay-payment-gateway.php' == $file and isset($links['activate']) )
        $links['activate'] = '<span>Activate</span>';

    return $links;
}
add_filter( 'plugin_action_links', 'give_berrypay_payment_gateway_activation', 10, 2 );
/* End of Disabled Plugin Activation Link */

/* Payment Gateway Section */
function add_berrypay_payment_gateway($gateways)
{
    $gateways['berrypay'] = array(
        'admin_label'    => __( 'BerryPay', 'give' ),
        'checkout_label' => __( 'BerryPay', 'give' ),
    );
    return $gateways;
}
add_filter( 'give_payment_gateways', 'add_berrypay_payment_gateway');
/* End of Payment Gateway Section */

/* Gateway Section */
function add_berrypay_gateway_section($sections)
{
    $sections['berrypay'] = __( 'BerryPay', 'give' );
    return $sections;
}
add_filter( 'give_get_sections_gateways', 'add_berrypay_gateway_section');
/* End of Gateway Section */

/* Gateway Settings */
function add_berrypay_gateway_settings($settings)
{
    $current_section = give_get_current_setting_section();
    switch ($current_section) {
        case 'berrypay':
            $settings = array(
                array(
                    'type' => 'title',
                    'id'   => 'give_title_gateway_settings_berrypay',
                ),
                array(
                    'name' => __( 'Organization Name', 'give' ),
                    'desc' => __( 'Enter your organization name details to be displayed to your donors.', 'give' ),
                    'id'   => 'berrypay_merchant_name',
                    'type' => 'text',
                    ),
                array(
                    'name' => __( 'Merchant Public Key', 'give' ),
                    'desc' => __( 'BerryPay Public key. Your Public Key can be found in our dashboard.', 'give' ),
                    'id'   => 'berrypay_pub_key',
                    'type' => 'text',
                    ),
                array(
                    'name' => __( 'Merchant API Key', 'give' ),
                    'desc' => __( 'BerryPay API key. Your API Key can be found in our dashboard.', 'give' ),
                    'id'   => 'berrypay_api_key',
                    'type' => 'text',
                    ),
                array(
                    'name' => __( 'Merchant Secret Key', 'give' ),
                    'desc' => __( 'BerryPay Merchant Secret Key. Your Secret Key can be found in our dashboard.', 'give' ),
                    'id'   => 'berrypay_secret_key',
                    'type' => 'text',
                    ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'give_title_gateway_settings_berrypay',
                )
            );
            break;
    }
    return $settings;
}
add_filter( 'give_get_settings_gateways', 'add_berrypay_gateway_settings');
/* End of Gateway Settings */

/* berrypay Billing Details Form */
function give_berrypay_standard_billing_fields( $form_id ) {
    
    if ( give_is_setting_enabled( give_get_option( 'berrypay_billing_details' ) ) ) {
        give_default_cc_address_fields( $form_id );

        return true;
    }

    return false;

}
add_action( 'give_berrypay_cc_form', 'give_berrypay_standard_billing_fields' );
/* End of berrypay Billing Details Form */

/* Create Payment Data */
function create_berrypay_payment_data($insert_payment_data)
{
    $insert_payment_data['gateway'] = 'berrypay';
    return $insert_payment_data;
}
add_filter( 'give_create_payment', 'create_berrypay_payment_data');
/* End of Create Payment Data */
 
/* Process BerryPay Payment */
function give_process_berrypay_payment($payment_data)
{
    write_log('-- PROCESS PAYMENT --');
    write_log('Payment Data: ');
    write_log($payment_data);
    // Validate nonce.
    write_log('Validating nonce ...');
    give_validate_nonce( $payment_data['gateway_nonce'], 'give-gateway' );
    $payment_id = give_create_payment( $payment_data, 'berrypay' );
    write_log('Payment ID: ' . $payment_id);

    // Check payment.
    if (empty($payment_id)) {
        // Record the error.
        give_record_gateway_error(
            esc_html__( 'Payment Error', 'give' ),
            sprintf(
            /* translators: %s: payment data */
                esc_html__( 'Payment creation failed before sending donor to BerryPay. Payment data: %s', 'give' ),
                json_encode( $payment_data )
            ),
            $payment_id
        );
        // Problems? Send back.
        give_send_back_to_checkout( '?payment-mode=' . $payment_data['post_data']['give-gateway'] );
    }

    // Redirect to BerryPay.
    $result = construct_form_and_post($payment_id, $payment_data);
    write_log('Construct Result: ' . $result);
    exit;
}
add_action( 'give_gateway_berrypay', 'give_process_berrypay_payment' );
/* End of Process BerryPay Payment */

/* Hidden Form Generation */
function construct_form_and_post($payment_id, $payment_data) {
    
    $post_url = give_is_test_mode() ?  'https://secure.berrpaystaging.com/api/v2/app/payment/' . give_get_option('berrypay_pub_key') : 'https://securepay.berrypay.com/api/v2/app/payment/' . give_get_option('berrypay_pub_key') ;
    $phone = '-';
    $remark = '';

    // Get the success url.
    $return_url = add_query_arg( array(
        'payment-confirmation' => 'berrypay',
        'payment-id'           => $payment_id,
    ), get_permalink( give_get_option( 'success_page' ) ) );

    write_log('Constructing Form ...');
    write_log('Payment ID:');
    write_log($payment_id);
    write_log('');
    write_log('Payment Data:');
    write_log($payment_data);

    // Item name.
    $item_name = give_build_berrypay_item_title($payment_data);
    
    // Setup BerryPay API params.
    $args = array(
        'ref_no'        => $payment_id,
        'amount'        => $payment_data['price'],
        'currency'      => give_get_currency(),
        'prod_desc'     => stripslashes( $item_name ),
        'user_name'     => $payment_data['user_info']['first_name'] . ' ' . $payment_data['user_info']['last_name'],
        'user_email'    => $payment_data['user_email'],
        'user_contact'  => $phone,
        'remark'        => $remark,
        'lang'          => get_bloginfo( 'charset' ),
        'return'        => $return_url,
        'cbt'           => get_bloginfo( 'name' ),
        'bn'            => 'givewp_SP',
    );

    $format_amt = format_amount($args['amount']);

    write_log('');
    write_log('Payment Args:');
    write_log($args);
    $txn_prod_desc = "Payment Order ID: " . $args['ref_no'];
   
    ?>
    <form id="form" action="<?php echo $post_url; ?>" method="POST">
        <input type="hidden" name="txn_order_id" value="<?php echo $args['ref_no']; ?>">
        <input type="hidden" name="txn_amount" value="<?php echo $args['amount']; ?>">
        <input type="hidden" name="txn_product_name" value="<?php echo give_get_option('berrypay_merchant_name'); ?>">
        <input type="hidden" name="txn_product_desc" value="<?php echo $txn_prod_desc; ?>">
        <input type="hidden" name="txn_buyer_name" value="<?php echo $args['user_name']; ?>">
        <input type="hidden" name="txn_buyer_email" value="<?php echo $args['user_email']; ?>">
        <input type="hidden" name="txn_buyer_phone" value="<?php echo $args['user_contact']; ?>">
        <input type="hidden" name="return_url" value="<?php echo $args['return']; ?>">
        <input type="hidden" name="api_key" value="<?php echo give_get_option('berrypay_api_key'); ?>">
        <?php
            	$secret_key = give_get_option('berrypay_secret_key');

                $api_key = give_get_option('berrypay_api_key');

                $prod_name = give_get_option('berrypay_merchant_name');

                $string = $api_key . "|" . $args['amount'] . "|" . $args['user_email'] . "|" . $args['user_name'] . "|" . $args['user_contact'] . "|" . $args['ref_no'] . "|" . $txn_prod_desc . "|" . $prod_name;

                $signature = hash_hmac('sha256', $string, $secret_key);
        ?>
        <input type="hidden" name="signature" value="<?php echo $signature ?>">
    </form>

    <script src="https://code.jquery.com/jquery-3.2.1.min.js" crossorigin="anonymous"></script>
    <script>
        jQuery(document).ready(function(){
            jQuery('#form').submit();
        });
    </script>       
    <?php

    return 'success ...';
}
/* End of Hidden Form Generation */

/* Return URL Processing */

function give_berrypay_success_page_content( $content ) {
    write_log('-- PROCESSING RESPONSE --');
    write_log('Response Content:');
    write_log($_REQUEST);

    // $merchantcode = $_REQUEST["MerchantCode"];
    $paymentid = $_REQUEST["txn_order_id"];
    $refno = $_REQUEST["txn_ref_id"];
    $amount = $_REQUEST["txn_amount"];
    $ecurrency = 'RM';
    $remark = $_REQUEST["txn_msg"];
    $transid = $_REQUEST["txn_ref_id"];
    // $authcode = $_REQUEST["signature"];
    $estatus = $_REQUEST["txn_status_id"];
    // $errdesc = $_REQUEST["ErrDesc"];
    $signature = $_REQUEST["signature"];

    if (!isset( $_GET['payment-id'] ) && ! give_get_purchase_session() ) {
        return $content;
    }
    $payment_id = isset( $_GET['payment-id'] ) ? absint( $_GET['payment-id'] ) : false;
    if ( ! $payment_id ) {
        $session    = give_get_purchase_session();
        $payment_id = give_get_purchase_id_by_key( $session['purchase_key'] );
    }
    $payment = get_post( $payment_id );
    if ( $payment && 'pending' === $payment->post_status ) {
        // Payment is still pending so show processing indicator to fix the race condition.
        ob_start();
        give_get_template_part( 'payment', 'processing' );
        $content = ob_get_clean();
    }
    write_log('Processing Status from BerryPay ...');
    if ($estatus === "1") {
        //TODO: COMPARE Return Signature with Generated Response Signature
        // $requery = requery($merchantcode, $refno, $amount);    

        write_log('BerryPay Requery Result:');
        // write_log($requery);
        
        write_log('Logging Success: Payment ID = ' . $payment_id . ' successful ...');
        // Link `Transaction ID` to the donation.
        give_set_payment_transaction_id( $payment_id, $transid );
        give_update_payment_status( $payment_id, 'publish' );
        // Send donor to `Donation Confirmation` page.
        give_send_to_success_page();
    }
    else {
        write_log('Logging Error: Payment ID = ' . $payment_id . ', Error = ' . $errdesc);
        give_record_gateway_error( __( 'BerryPay Error', 'give' ), sprintf(__( $errdesc, 'give' ), json_encode( $_REQUEST ) ), $payment_id );
        give_set_payment_transaction_id( $payment_id, $transid );
        give_update_payment_status( $payment_id, 'failed' );
        give_insert_payment_note( $payment_id, __( $errdesc, 'give' ) );
        wp_redirect( give_get_failed_transaction_uri() );
    }
    
    return $content;
}
add_filter('give_payment_confirm_berrypay', 'give_berrypay_success_page_content');
/* End of Return URL Processing */

/* Build Item Title */
function give_build_berrypay_item_title($payment_data)
{
    $form_id   = intval( $payment_data['post_data']['give-form-id'] );
    $item_name = $payment_data['post_data']['give-form-title'];

    // Verify has variable prices.
    if (give_has_variable_prices( $form_id ) && isset( $payment_data['post_data']['give-price-id'] )) {
        $item_price_level_text = give_get_price_option_name( $form_id, $payment_data['post_data']['give-price-id'] );
        $price_level_amount    = give_get_price_option_amount( $form_id, $payment_data['post_data']['give-price-id'] );

        // Donation given doesn't match selected level (must be a custom amount).
        if ($price_level_amount != give_sanitize_amount( $payment_data['price'] )) {
            $custom_amount_text = give_get_meta( $form_id, '_give_custom_amount_text', true );
            // user custom amount text if any, fallback to default if not.
            $item_name .= ' - ' . give_check_variable( $custom_amount_text, 'empty', esc_html__( 'Custom Amount', 'give' ) );
        } //Is there any donation level text?
        elseif (! empty( $item_price_level_text )) {
            $item_name .= ' - ' . $item_price_level_text;
        }
    } //Single donation: Custom Amount.
    elseif (give_get_form_price( $form_id ) !== give_sanitize_amount( $payment_data['price'] )) {
        $custom_amount_text = give_get_meta( $form_id, '_give_custom_amount_text', true );
        // user custom amount text if any, fallback to default if not.
        $item_name .= ' - ' . give_check_variable( $custom_amount_text, 'empty', esc_html__( 'Custom Amount', 'give' ) );
    }

    return $item_name;
}
/* End of Build Item Title */

/* Add Phone Number Field */
function give_phone_number_form_fields( $form_id ) {
	?>
   
	<?php
} 
add_action( 'give_donation_form_after_email', 'give_phone_number_form_fields', 10, 1 );
/* End of Add Phone Number Field */

/* Make Phone Number Field Required */
// function give_required_phone_number($required_fields)
// {
//     $required_fields['give_phone'] =  array(
// 		'give_phone' => array(
// 			'error_id'      => 'invalid_phone',
// 			'error_message' => __( 'Please enter phone number.', 'give' ),
// 		));
//     return $required_fields;
// }
// add_filter( 'give_donation_form_required_fields', 'give_required_phone_number');

/* End of berrypay Requery Function */
