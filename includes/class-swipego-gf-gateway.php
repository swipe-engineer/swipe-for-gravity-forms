<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Swipego_GF_Gateway extends GFPaymentAddOn {

    protected $_version = SWIPEGO_GF_VERSION;
    protected $_min_gravityforms_version = '1.8.12';
    protected $_slug = 'gravityformsswipego';
    protected $_path = 'gravityformsswipego/swipego.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Swipe for Gravity Forms';
    protected $_short_title = 'Swipe';
    protected $_supports_callbacks = true;
    protected $_requires_credit_card = false;

    private $swipego;

    private static $_instance = null;

    // Returns an instance of this class, and stores it in the $_instance property
    public static function get_instance() {

        if ( self::$_instance == null ) {
            self::$_instance = new self();
        }

        return self::$_instance;

    }

    // Register hooks
    public function init() {

        parent::init();

        add_action( 'wp_ajax_swipego_gf_retrieve_api_credentials', array( $this, 'retrieve_api_credentials' ) );
        add_action( 'wp_ajax_swipego_gf_set_webhook', array( $this, 'set_webhook' ) );

        add_action( 'wp', array( $this, 'maybe_thankyou_page' ), 5 );

    }

    // Settings icon
    public function get_menu_icon() {
        return SWIPEGO_URL . 'assets/images/icon-swipe.svg';
    }

    // Enqueue scripts
    public function scripts() {

        $scripts = array(
            array(
                'handle'  => 'sweetalert2',
                'enqueue' => array(
                    array(
                        'admin_page' => array(
                            'entry_edit',
                            'form_settings',
                        ),
                    ),
                ),
            ),
            array(
                'handle'  => 'swipego-gf-admin',
                'src'     => SWIPEGO_GF_URL . 'assets/js/admin.js',
                'version' => SWIPEGO_GF_VERSION,
                'deps'    => array( 'jquery', 'sweetalert2' ),
                'callback' => array( $this, 'localize_admin_script' ),
                'enqueue' => array(
                    array(
                        'admin_page' => array(
                            'entry_edit',
                            'form_settings',
                        ),
                    ),
                ),
            ),
        );

        return array_merge( parent::scripts(), $scripts );

    }

    // Localize admin script
    public function localize_admin_script() {

        wp_localize_script( 'swipego-gf-admin', 'swipego_gf_retrieve_api_credentials', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'swipego_gf_retrieve_api_credentials_nonce' ),
        ) );

        wp_localize_script( 'swipego-gf-admin', 'swipego_gf_set_webhook', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'swipego_gf_set_webhook_nonce' ),
        ) );

    }

    // Retrieve API credentials from Swipe
    public function retrieve_api_credentials() {

        check_ajax_referer( 'swipego_gf_retrieve_api_credentials_nonce', 'nonce' );

        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : null;
        $business_id = isset( $_POST['business_id'] ) ? sanitize_text_field( $_POST['business_id'] ) : null;

        if ( !wp_verify_nonce( $nonce, 'swipego_gf_retrieve_api_credentials_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid nonce', 'swipego' ),
            ), 400 );
        }

        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'No permission to update the settings', 'swipego' ),
            ), 400 );
        }

        if ( !$business_id ) {
            wp_send_json_error( array(
                'message' => __( 'No business selected', 'swipego' ),
            ), 400 );
        }

        $business = swipego_gf_get_business( $business_id );

        if ( !$business ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid business', 'swipego' ),
            ), 400 );
        }

        wp_send_json_success( $business );

    }

    // Set Gravity Forms webhook URL in Swipe
    public function set_webhook() {

        check_ajax_referer( 'swipego_gf_set_webhook_nonce', 'nonce' );

        $nonce          = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : null;
        $business_id    = isset( $_POST['business_id'] ) ? sanitize_text_field( $_POST['business_id'] ) : null;
        $environment    = isset( $_POST['environment'] ) ? sanitize_text_field( $_POST['environment'] ) : null;
        $integration_id = isset( $_POST['integration_id'] ) ? sanitize_text_field( $_POST['integration_id'] ) : null;

        if ( !wp_verify_nonce( $nonce, 'swipego_gf_set_webhook_nonce' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid nonce', 'swipego' ),
            ), 400 );
        }

        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'No permission to update the settings', 'swipego' ),
            ), 400 );
        }

        if ( !$business_id ) {
            wp_send_json_error( array(
                'message' => __( 'No business selected', 'swipego' ),
            ), 400 );
        }

        if ( !$integration_id ) {
            wp_send_json_error( array(
                'message' => __( 'Missing integration ID for selected business', 'swipego' ),
            ), 400 );
        }

        try {

            $swipego = new Swipego_GF_API();
            $swipego->set_access_token( swipego_get_access_token() );
            $swipego->set_environment( $environment );

            swipego_gf_logger( __METHOD__ . sprintf( '(): env #%d', $environment ) );

            // Get all webhooks because we need to delete existing webhook first
            // 1 = payment.created
            list( $code, $response ) = $swipego->get_webhooks( $business_id, $integration_id );

            $webhooks = isset( $response['data']['data'] ) ? $response['data']['data'] : array();

            if ( $webhooks ) {
                foreach ( $webhooks as $webhook ) {
                    if ( !isset( $webhook['_id'] ) ) {
                        continue;
                    }

                    // Delete existing webhook first
                    $swipego->delete_webhook( $business_id, $integration_id, $webhook['_id'], array( 'enabled' => true ) );
                }
            }

            $params = array(
                'name'    => 'payment.created',
                'url'     => add_query_arg( 'callback', $this->_slug, site_url( '/' ) ),
                'enabled' => true,
            );

            list( $code, $response ) = $swipego->store_webhook( $business_id, $integration_id, $params );

            $errors = isset( $response['errors'] ) ? $response['errors'] : false;

            if ( $errors ) {
                foreach ( $errors as $error ) {
                    throw new Exception( $error[0] );
                }
            }

        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'message' => $e->getMessage(),
            ), 400 );
        }

        wp_send_json_success( $business_id );

    }

    // Feed settings
    public function feed_settings_fields() {

        $businesses = swipego_gf_get_businesses();

        $businesses = array_map( function( $business ) {
            return array(
                'label' => $business['name'],
                'value' => $business['id'],
            );
        }, $businesses );

        array_unshift( $businesses, array(
            'label' => $businesses ? __( 'Select a business', 'swipego-gf' ) : __( 'No business found', 'swipego-gf' ),
            'value' => '',
        ) );

        $default_settings = parent::feed_settings_fields();

        $fields = array(
            array(
                'name'       => 'business',
                'label'      => esc_html__( 'Business Selection', 'swipego-gf' ),
                'type'       => 'select',
                'required'   => true,
                'choices'    => $businesses,
            ),
            array(
                'name'       => 'integration',
                'type'       => 'hidden',
            ),
            array(
                'name'       => 'environment',
                'label'      => esc_html__( 'Environment', 'swipego-gf' ),
                'type'       => 'radio',
                'required'   => true,
                'horizontal' => true,
                'choices'    => array(
                    array(
                        'label' => __( 'Production', 'swipego-gf' ),
                        'value' => 'production',
                    ),
                    array(
                        'label' => __( 'Sandbox', 'swipego-gf' ),
                        'value' => 'sandbox',
                    ),
                ),
            ),
            array(
                'name'       => 'api_key',
                'label'      => esc_html__( 'API Access Key', 'swipego-gf' ),
                'type'       => 'text',
                'class'      => 'medium',
                'required'   => true,
                'readonly'   => true,
            ),
            array(
                'name'       => 'signature_key',
                'label'      => esc_html__( 'API Signature Key', 'swipego-gf' ),
                'type'       => 'text',
                'class'      => 'medium',
                'required'   => true,
                'readonly'   => true,
            ),
            array(
                'name'       => 'webhook',
                'label'      => esc_html__( 'Webhook', 'swipego-gf' ),
                'type'       => 'html',
                'html'       => '<p>' . __( 'Save Gravity Forms webhook URL in Swipe to receive payment notification.', 'swipego-gf' ) . '</p><button type="button" id="set-webhook" class="primary button">' . esc_html__( 'Set Webhook', 'swipego-gf' ) . '</button>',
            ),
        );

        $default_settings = $this->add_field_after( 'feedName', $fields, $default_settings );

        // Remove Subscription from Transaction Type dropdown
        $transaction_type = $this->get_field( 'transactionType', $default_settings );
        unset( $transaction_type['choices'][2] );

        $default_settings = $this->replace_field( 'transactionType', $transaction_type, $default_settings );

        return $default_settings;

    }

    // Extra billing information fields
    public function billing_info_fields() {

        return array(
            array(
                'name'     => 'email',
                'label'    => __( 'Email', 'swipego-gf' ),
                'required' => true,
            ),
            array(
                'name'     => 'phone',
                'label'    => __( 'Phone', 'swipego-gf' ),
                'required' => true,
            ),
        );

    }

    // Remove Options field (before Conditional Logic field)
    public function option_choices() {
        return false;
    }

    // Initialize API
    private function init_api( $data ) {

        if ( !$this->swipego ) {
            $api_key       = isset( $data['api_key'] ) ? $data['api_key'] : null;
            $signature_key = isset( $data['signature_key'] ) ? $data['signature_key'] : null;
            $environment   = isset( $data['environment'] ) ? $data['environment'] : null;
            $debug         = defined( 'SWIPEGO_GF_API_DEBUG' ) ? SWIPEGO_GF_API_DEBUG : false;

            $this->swipego = new Swipego_GF_API();

            $this->swipego->set_api_key( $api_key );
            $this->swipego->set_signature_key( $signature_key );
            $this->swipego->set_environment( $environment );
            $this->swipego->set_debug( $debug );
        }

    }

    // Create a payment link
    public function redirect_url( $feed, $submission_data, $form, $entry ) {

        swipego_gf_logger( __METHOD__ . '(): Creating payment link for entry #' . $entry['id'] );

        $this->init_api( $feed['meta'] );

        try {
            swipego_gf_logger( __METHOD__ . sprintf( '(): Creating payment link for entry #%s', $entry['id'] ) );

            $params = array(
                'email'        => rgar( $submission_data, 'email' ),
                'currency'     => $entry['currency'],
                'amount'       => (float) rgar( $submission_data, 'payment_amount' ),
                'title'        => get_bloginfo() . ' - ' . rgar( $submission_data, 'form_title' ),
                'phone_no'     => preg_replace('/[^0-9]/', '', rgar( $submission_data, 'phone' ) ),
                'description'  => sprintf( __( 'Payment for Submission #%d', 'swipego-gf' ), $entry['id'] ),
                'redirect_url' => $this->get_return_url( $form['id'], $entry['id'] ),
                'reference'    => $entry['id'],
                'reference_2'  => 'gravity-forms',
                'send_email'   => true,
            );

            list( $code, $response ) = $this->swipego->create_payment_link( $params );

            if ( isset( $response['data']['_id'] ) ) {
                gform_update_meta( $entry['id'], '_id', $response['data']['_id'] );

                swipego_gf_logger( __METHOD__ . sprintf( '(): Payment link created for entry #%d', $entry['id'] ) );
            }

            if ( isset( $response['data']['payment_url'] ) ) {
                return $response['data']['payment_url'];
            }

        } catch ( Exception $e ) {
            swipego_gf_logger( __METHOD__ . sprintf( '(): Error creating payment link for entry #%1$d: %2$s', $entry['id'], $e->getMessage() ) );
        }

    }

    // Handle IPN response
    public function callback() {

        if ( !$this->is_gravityforms_supported() ) {
            return false;
        }

        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            return false;
        }

        $data     = file_get_contents( 'php://input' );
        $data     = json_decode( $data, true );
        $entry_id = $data['data']['payment_link_reference'] ?? null;

        if ( !$entry_id ) {
            return false;
        }

        $entry = GFAPI::get_entry( $entry_id );

        if ( is_wp_error( $entry ) ) {
            $this->log_error( __METHOD__ . '(): Entry #' . $entry_id . ' not found.' );
            return false;
        }

        if ( $entry['status'] == 'spam' ) {
            swipego_gf_logger( __METHOD__ . '(): Entry #' . $entry['id'] . 'is marked as spam.' );
            return false;
        }

        $payment_id = gform_get_meta( $entry['id'], '_id' );

        if ( !$payment_id ) {
            swipego_gf_logger( __METHOD__ . '(): Payment for entry #' . $entry['id'] . ' not found.' );
            return false;
        }

        $feed = $this->get_payment_feed( $entry );

        // Check if payment gateway is still active for specified form
        if ( !$feed || !rgar( $feed, 'is_active' ) ) {
            swipego_gf_logger( __METHOD__ . '(): Swipe no longer active for form #' . $entry['form_id'] );
            return false;
        }

        //////////////////////////////////////////////////////

        $this->init_api( $feed['meta'] );

        $response = $this->swipego->get_ipn_response();

        try {
            swipego_gf_logger( __METHOD__ . '(): Verifying hash for entry #' . $entry_id );
            $this->swipego->validate_ipn_response( $response );
        } catch ( Exception $e ) {
            swipego_gf_logger( $e->getMessage() );
            wp_die( $e->getMessage(), 'Swipe IPN', array( 'response' => 200 ) );
        } finally {
            swipego_gf_logger( __METHOD__ . '(): Verified hash for entry #' . $entry_id );
        }

        switch ( $response['payment_status'] ) {
            case 1:
                $type = 'complete_payment';
                $payment_status = 'Paid';
                break;

            case 2:
                $type = 'add_pending_payment';
                $payment_status = 'Pending';
                break;

            default:
                $type = 'fail_payment';
                $payment_status = 'Failed';
                break;
        }

        return array(
            'id'               => $response['payment_link_id'],
            'type'             => $type,
            'amount'           => (float) $response['payment_amount'],
            'transaction_id'   => $response['payment_link_id'],
            'entry_id'         => $entry_id,
            'payment_status'   => $payment_status,
            'payment_date'     => $response['payment_time'],
            'payment_method'   => $this->_short_title,
        );

    }

    // Generate thank you page URL
    private function get_return_url( $form_id, $entry_id ) {

        $referer_url  = ! empty( $_SERVER['HTTP_REFERER'] ) 
            ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) 
            : sanitize_url( $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] );

        // Hashing form and entry ID in thank you page URL
        $ids_query = "ids={$form_id}|{$entry_id}";
        $ids_query .= '&hash=' . wp_hash( $ids_query );

        $page_url = add_query_arg( 'swipego_gf_return', base64_encode( $ids_query ), $referer_url );

        return $page_url;

    }

    // Handle thank you page
    public function maybe_thankyou_page() {

        if ( !$this->is_gravityforms_supported() ) {
            return;
        }

        if ( $str = rgget( 'swipego_gf_return' ) ) {
            $str = base64_decode( $str );

            parse_str( $str, $query );

            if ( wp_hash( 'ids=' . $query['ids'] ) == $query['hash'] ) {
                list( $form_id, $entry_id ) = explode( '|', $query['ids'] );

                $form = GFAPI::get_form( $form_id );
                $lead = GFAPI::get_entry( $entry_id );

                if ( !class_exists( 'GFFormDisplay' ) ) {
                    require_once( GFCommon::get_base_path() . '/form_display.php' );
                }

                $confirmation = GFFormDisplay::handle_confirmation( $form, $lead, false );

                if ( is_array( $confirmation ) && isset( $confirmation['redirect'] ) ) {
                    wp_redirect( $confirmation['redirect'] );
                    exit;
                }

                GFFormDisplay::$submission[ $form_id ] = array(
                    'is_confirmation'      => true,
                    'confirmation_message' => $confirmation,
                    'form'                 => $form,
                    'lead'                 => $lead,
                );
            }
        }

    }

    // Register public hooks
    public function init_frontend() {

        parent::init_frontend();

        // Disable post creation and notification on form submit.
        // We will handle this after payment received.
        add_filter( 'gform_disable_post_creation', '__return_true' );
        add_filter( 'gform_disable_notification', '__return_true' );

    }

    // Register admin hooks
    public function init_admin() {

        parent::init_admin();

        // Allow user to update payment details
        add_action( 'gform_payment_status', array( $this, 'admin_edit_payment_status' ), 3, 3);
        add_action( 'gform_payment_date', array( $this, 'admin_edit_payment_date' ), 3, 3);
        add_action( 'gform_payment_transaction_id', array( $this, 'admin_edit_payment_transaction_id' ), 3, 3);
        add_action( 'gform_payment_amount', array( $this, 'admin_edit_payment_amount' ), 3, 3);
        add_action( 'gform_after_update_entry', array( $this, 'admin_update_payment' ), 4, 2);

    }

    // Register supported notification events
    public function supported_notification_events( $form ) {

        return array(
            'complete_payment' => esc_html__( 'Payment Completed', 'swipego-gf' ),
            'fail_payment'     => esc_html__( 'Payment Failed', 'swipego-gf' ),
        );

    }

    // Payment status field (admin side)
    public function admin_edit_payment_status( $payment_status, $form, $entry ) {

        if ( !$this->is_allowed_edit_payment( $entry ) ) {
            return $payment_status;
        }

        $input = gform_tooltip( 'swipego_gravityforms_edit_payment_status', true );
        $input .= '<select id="payment_status" name="payment_status">';
        $input .= '<option value="' . $payment_status . '"selected>' . $payment_status . '</option>';
        $input .= '<option value="' . esc_html__( 'Paid', 'swipego-gf' ) . '"selected>' . esc_html__( 'Paid', 'swipego-gf' ) . '</option>';
        $input .= '</select>';

        return $input;

    }

    // Payment date field (admin side)
    public function admin_edit_payment_date( $payment_date, $form, $entry ) {

        if ( !$this->is_allowed_edit_payment( $entry ) ) {
            return $payment_date;
        }

        $payment_date = $entry['payment_date'];
        if ( empty( $payment_date ) ) {
            $payment_date = get_the_date( 'd-m-Y h:i:s a' );
        }

        return '<input type="text" id="payment_date" name="payment_date" value="' . $payment_date . '">';

    }

    // Transaction ID field (admin side)
    public function admin_edit_payment_transaction_id( $transaction_id, $form, $entry ) {

        if ( !$this->is_allowed_edit_payment( $entry ) ) {
            return $transaction_id;
        }

        return '<input type="text" id="' . $this->id . '_transaction_id" name="' . $this->id . '_transaction_id" value="' . $transaction_id . '">';

    }

    // Payment amount field (admin side)
    public function admin_edit_payment_amount( $payment_amount, $form, $entry ) {

        if ( !$this->is_allowed_edit_payment( $entry ) ) {
            return $payment_amount;
        }

        if ( empty( $payment_amount ) ) {
            $payment_amount = GFCommon::get_order_total( $form, $entry );
        }

        return '<input type="text" id="payment_amount" name="payment_amount" class="gform_currency" value="' . $payment_amount . '">';

    }

    // Handle payment details update
    public function admin_update_payment( $form, $entry_id ) {

        check_admin_referer( 'gforms_save_entry', 'gforms_save_entry' );

        global $current_user;

        if ( !$current_user ) {
            return;
        }

        $current_user_data = get_userdata( $current_user->ID );

        $entry = GFFormsModel::get_lead( $entry_id );

        if ( !$this->is_allowed_edit_payment( $entry, 'update' ) ) {
            return;
        }

        $payment_status = rgpost( 'payment_status' );

        // If no payment status, get it from entry
        if ( empty( $payment_status ) ) {
            $payment_status = $entry['payment_status'];
        }

        $payment_date   = rgpost( 'payment_date' );
        $payment_amount = GFCommon::to_number( rgpost( 'payment_amount' ) );
        $transaction_id = rgpost( $this->id . '_transaction_id' );

        $status_unchanged         = $entry['payment_status'] == $payment_status;
        $date_unchanged           = $entry['payment_date'] == $payment_date;
        $amount_unchanged         = $entry['payment_amount'] == $payment_amount;
        $transaction_id_unchanged = $entry['transaction_id'] == $transaction_id;

        // If no change on all payment details, don't update it
        if ( $status_unchanged && $date_unchanged && $amount_unchanged && $transaction_id_unchanged ) {
            return;
        }

        if ( !$payment_date ) {
            $payment_date = get_the_date( 'd-m-Y h:i:s a' );
        }

        $entry['payment_status'] = $payment_status;
        $entry['payment_date']   = $payment_date;
        $entry['payment_amount'] = $payment_amount;
        $entry['payment_method'] = $this->_short_title;
        $entry['transaction_id'] = $transaction_id;

        // Check if payment is paid and not already fulfilled
        if ( $payment_status == 'Paid' && !$entry['is_fulfilled'] ) {
            $action['id']             = $transaction_id;
            $action['type']           = 'complete_payment';
            $action['amount']         = $payment_amount;
            $action['transaction_id'] = $transaction_id;
            $action['entry_id']       = $entry['id'];
            $action['payment_status'] = $payment_status;

            $this->complete_payment( $entry, $action );
        }

        GFAPI::update_entry( $entry );

        $note = sprintf(
            esc_html__( 'Payment details was manually updated. Payment Method: %s. Transaction ID: %s. Amount: %s. Status: %s. Date: %s.', 'swipego-gf' ),
            $entry['payment_method'],
            $entry['transaction_id'],
            GFCommon::to_money( $entry['payment_amount'], $entry['currency'] ),
            $entry['payment_status'],
            $entry['payment_date']
        );
        GFFormsModel::add_note( $entry['id'], $current_user->ID, $current_user->display_name, $note );

    }

    // Check if have permission to edit the payment
    private function is_allowed_edit_payment( $entry, $action = 'edit' ) {

        // Don't allow if payment gateway for the entry is not our payment gateway
        if ( !$this->is_payment_gateway( $entry['id'] ) ) {
            return false;
        }

        // Don't allow if payment  status already paid or transaction type is subscription
        if ( rgar( $entry, 'payment_status' ) == 'Paid' || rgar( $entry, 'transaction_type' ) == 2 ) {
            return false;
        }

        // Allow if in edit page
        if ( $action == 'edit' && rgpost( 'screen_mode' ) == 'edit' ) {
            return true;
        }

        if ( $action == 'update' && rgpost( 'screen_mode' ) == 'view' && rgpost( 'action' ) == 'update' ) {
            return true;
        }

        return false;

    }

}
