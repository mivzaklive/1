<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Salon_Manager_Admin {

    private $settings;

    public function __construct() {
        $this->settings = get_option( 'salon_manager_settings', Salon_Manager_DB::default_settings() );
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_post_salon_save_customer', [ $this, 'handle_save_customer' ] );
        add_action( 'admin_post_salon_delete_customer', [ $this, 'handle_delete_customer' ] );
        add_action( 'admin_post_salon_save_appointment', [ $this, 'handle_save_appointment' ] );
        add_action( 'admin_post_salon_delete_appointment', [ $this, 'handle_delete_appointment' ] );
        add_action( 'admin_post_salon_update_appointment_status', [ $this, 'handle_update_status' ] );
        add_action( 'admin_post_salon_save_payment', [ $this, 'handle_save_payment' ] );
        add_action( 'admin_post_salon_delete_payment', [ $this, 'handle_delete_payment' ] );
        add_action( 'admin_post_salon_save_settings', [ $this, 'handle_save_settings' ] );
        add_action( 'wp_ajax_salon_get_slots', [ $this, 'ajax_get_slots' ] );
        add_action( 'wp_ajax_salon_get_service_info', [ $this, 'ajax_get_service_info' ] );
    }

    public function register_menus() {
        add_menu_page(
            'ניהול סלון', 'סלון יופי', 'manage_options',
            'salon-manager', [ $this, 'page_dashboard' ],
            'dashicons-scissors', 30
        );
        add_submenu_page( 'salon-manager', 'לוח בקרה', 'לוח בקרה', 'manage_options', 'salon-manager', [ $this, 'page_dashboard' ] );
        add_submenu_page( 'salon-manager', 'תורים', 'תורים', 'manage_options', 'salon-appointments', [ $this, 'page_appointments' ] );
        add_submenu_page( 'salon-manager', 'לקוחות', 'לקוחות', 'manage_options', 'salon-customers', [ $this, 'page_customers' ] );
        add_submenu_page( 'salon-manager', 'תשלומים', 'תשלומים', 'manage_options', 'salon-payments', [ $this, 'page_payments' ] );
        add_submenu_page( 'salon-manager', 'הגדרות', 'הגדרות', 'manage_options', 'salon-settings', [ $this, 'page_settings' ] );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'salon' ) === false ) return;
        wp_enqueue_style( 'salon-manager', SALON_MANAGER_URL . 'admin/css/admin.css', [], SALON_MANAGER_VERSION );
        wp_enqueue_script( 'salon-manager', SALON_MANAGER_URL . 'admin/js/admin.js', [ 'jquery' ], SALON_MANAGER_VERSION, true );
        wp_localize_script( 'salon-manager', 'salonAjax', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'salon_nonce' ),
        ] );
    }

    private function redirect( $page, $args = [] ) {
        $url = admin_url( 'admin.php?page=' . $page );
        foreach ( $args as $k => $v ) $url = add_query_arg( $k, $v, $url );
        wp_safe_redirect( $url );
        exit;
    }

    /* ============================================================
       PAGE HANDLERS
    ============================================================ */

    public function page_dashboard() {
        $upcoming  = Salon_Manager_Appointments::get_upcoming( 8 );
        $today     = Salon_Manager_Appointments::count_today();
        $month     = Salon_Manager_Appointments::count_month();
        $revenue   = Salon_Manager_Payments::total_revenue( date('Y'), date('m') );
        $customers = Salon_Manager_Customers::count();
        $debtors   = Salon_Manager_Payments::get_debtors();
        $settings  = $this->settings;
        include SALON_MANAGER_PATH . 'admin/views/dashboard.php';
    }

    public function page_appointments() {
        $action   = $_GET['action'] ?? 'list';
        $settings = $this->settings;

        if ( $action === 'new' || $action === 'edit' ) {
            $appointment = $action === 'edit' ? Salon_Manager_Appointments::get( absint( $_GET['id'] ?? 0 ) ) : null;
            $customers   = Salon_Manager_Customers::get_all();
            include SALON_MANAGER_PATH . 'admin/views/appointment-form.php';
        } else {
            $view = $_GET['view'] ?? 'week';
            $date = sanitize_text_field( $_GET['date'] ?? date( 'Y-m-d' ) );
            if ( $view === 'day' ) {
                $appointments = Salon_Manager_Appointments::get_by_date( $date );
            } elseif ( $view === 'month' ) {
                $y = (int) date( 'Y', strtotime( $date ) );
                $m = (int) date( 'm', strtotime( $date ) );
                $appointments = Salon_Manager_Appointments::get_by_month( $y, $m );
            } else {
                $mon   = date( 'Y-m-d', strtotime( 'monday this week', strtotime( $date ) ) );
                $sun   = date( 'Y-m-d', strtotime( 'sunday this week', strtotime( $date ) ) );
                if ( date( 'N', strtotime($date) ) == 7 ) { $mon = $date; $sun = $date; }
                $appointments = Salon_Manager_Appointments::get_by_week( $mon, $sun );
            }
            include SALON_MANAGER_PATH . 'admin/views/appointments.php';
        }
    }

    public function page_customers() {
        $action = $_GET['action'] ?? 'list';

        if ( $action === 'new' || $action === 'edit' ) {
            $customer = $action === 'edit' ? Salon_Manager_Customers::get( absint( $_GET['id'] ?? 0 ) ) : null;
            include SALON_MANAGER_PATH . 'admin/views/customer-form.php';
        } elseif ( $action === 'view' ) {
            $customer     = Salon_Manager_Customers::get( absint( $_GET['id'] ?? 0 ) );
            $appointments = Salon_Manager_Appointments::get_for_customer( $customer->id );
            $payments     = Salon_Manager_Payments::get_for_customer( $customer->id );
            $stats        = Salon_Manager_Customers::get_stats( $customer->id );
            $settings     = $this->settings;
            include SALON_MANAGER_PATH . 'admin/views/customer-view.php';
        } else {
            $search    = sanitize_text_field( $_GET['s'] ?? '' );
            $customers = Salon_Manager_Customers::get_all( $search );
            include SALON_MANAGER_PATH . 'admin/views/customers.php';
        }
    }

    public function page_payments() {
        $payments = Salon_Manager_Payments::get_all();
        $debtors  = Salon_Manager_Payments::get_debtors();
        $year     = (int) date('Y');
        $month    = (int) date('m');
        $report   = Salon_Manager_Payments::monthly_report( $year, $month );
        $revenue  = Salon_Manager_Payments::total_revenue( $year, $month );
        $settings = $this->settings;
        $customers = Salon_Manager_Customers::get_all();
        include SALON_MANAGER_PATH . 'admin/views/payments.php';
    }

    public function page_settings() {
        $settings = $this->settings;
        $msg      = $_GET['msg'] ?? '';
        include SALON_MANAGER_PATH . 'admin/views/settings.php';
    }

    /* ============================================================
       POST HANDLERS
    ============================================================ */

    public function handle_save_customer() {
        check_admin_referer( 'salon_save_customer' );
        $id  = absint( $_POST['customer_id'] ?? 0 );
        $new = Salon_Manager_Customers::save( $_POST, $id );
        $this->redirect( 'salon-customers', [ 'action' => 'view', 'id' => $new, 'saved' => 1 ] );
    }

    public function handle_delete_customer() {
        check_admin_referer( 'salon_delete_customer' );
        Salon_Manager_Customers::delete( absint( $_POST['customer_id'] ) );
        $this->redirect( 'salon-customers', [ 'deleted' => 1 ] );
    }

    public function handle_save_appointment() {
        check_admin_referer( 'salon_save_appointment' );
        $id  = absint( $_POST['appointment_id'] ?? 0 );
        $new = Salon_Manager_Appointments::save( $_POST, $id );
        $date = sanitize_text_field( $_POST['appointment_date'] ?? '' );
        $this->redirect( 'salon-appointments', [ 'view' => 'day', 'date' => $date, 'saved' => 1 ] );
    }

    public function handle_delete_appointment() {
        check_admin_referer( 'salon_delete_appointment' );
        Salon_Manager_Appointments::delete( absint( $_POST['appointment_id'] ) );
        $this->redirect( 'salon-appointments', [ 'deleted' => 1 ] );
    }

    public function handle_update_status() {
        check_admin_referer( 'salon_update_status' );
        Salon_Manager_Appointments::update_status( absint( $_POST['appointment_id'] ), $_POST['status'] );
        wp_safe_redirect( $_SERVER['HTTP_REFERER'] ?? admin_url( 'admin.php?page=salon-appointments' ) );
        exit;
    }

    public function handle_save_payment() {
        check_admin_referer( 'salon_save_payment' );
        Salon_Manager_Payments::save( $_POST );
        $redirect = ! empty( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : admin_url( 'admin.php?page=salon-payments&saved=1' );
        wp_safe_redirect( $redirect );
        exit;
    }

    public function handle_delete_payment() {
        check_admin_referer( 'salon_delete_payment' );
        Salon_Manager_Payments::delete( absint( $_POST['payment_id'] ) );
        wp_safe_redirect( $_SERVER['HTTP_REFERER'] ?? admin_url( 'admin.php?page=salon-payments' ) );
        exit;
    }

    public function handle_save_settings() {
        check_admin_referer( 'salon_save_settings' );
        $data = $_POST['settings'];

        $services = [];
        if ( ! empty( $data['services'] ) ) {
            foreach ( $data['services'] as $s ) {
                if ( empty( $s['name'] ) ) continue;
                $services[] = [
                    'name'     => sanitize_text_field( $s['name'] ),
                    'duration' => absint( $s['duration'] ),
                    'price'    => (float) $s['price'],
                ];
            }
        }

        $working_days = isset( $data['working_days'] ) ? array_map( 'sanitize_text_field', (array) $data['working_days'] ) : [];

        $settings = [
            'business_name'  => sanitize_text_field( $data['business_name'] ?? '' ),
            'owner_name'     => sanitize_text_field( $data['owner_name'] ?? '' ),
            'phone'          => sanitize_text_field( $data['phone'] ?? '' ),
            'currency'       => sanitize_text_field( $data['currency'] ?? '₪' ),
            'slot_duration'  => absint( $data['slot_duration'] ?? 60 ),
            'working_days'   => $working_days,
            'friday_enabled' => ! empty( $data['friday_enabled'] ),
            'weekday_start'  => sanitize_text_field( $data['weekday_start'] ?? '13:00' ),
            'weekday_end'    => sanitize_text_field( $data['weekday_end'] ?? '21:00' ),
            'friday_start'   => sanitize_text_field( $data['friday_start'] ?? '09:00' ),
            'friday_end'     => sanitize_text_field( $data['friday_end'] ?? '13:00' ),
            'services'       => $services,
        ];
        update_option( 'salon_manager_settings', $settings );
        $this->redirect( 'salon-settings', [ 'msg' => 'saved' ] );
    }

    /* ============================================================
       AJAX
    ============================================================ */

    public function ajax_get_slots() {
        check_ajax_referer( 'salon_nonce', 'nonce' );
        $date  = sanitize_text_field( $_POST['date'] ?? '' );
        $slots = Salon_Manager_Appointments::get_available_slots( $date, $this->settings );
        wp_send_json_success( $slots );
    }

    public function ajax_get_service_info() {
        check_ajax_referer( 'salon_nonce', 'nonce' );
        $name     = sanitize_text_field( $_POST['service'] ?? '' );
        $services = $this->settings['services'] ?? [];
        foreach ( $services as $s ) {
            if ( $s['name'] === $name ) {
                wp_send_json_success( $s );
            }
        }
        wp_send_json_error();
    }
}
