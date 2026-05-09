<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Salon_Manager_Payments {

    public static function get_all( $limit = 50, $offset = 0 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT p.*, CONCAT(c.first_name,' ',c.last_name) as customer_name
             FROM {$wpdb->prefix}salon_payments p
             JOIN {$wpdb->prefix}salon_customers c ON c.id = p.customer_id
             ORDER BY p.payment_date DESC, p.created_at DESC
             LIMIT %d OFFSET %d",
            $limit, $offset
        ) );
    }

    public static function get_for_appointment( $appointment_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}salon_payments WHERE appointment_id = %d",
            $appointment_id
        ) );
    }

    public static function get_for_customer( $customer_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT p.*, a.service, a.appointment_date
             FROM {$wpdb->prefix}salon_payments p
             LEFT JOIN {$wpdb->prefix}salon_appointments a ON a.id = p.appointment_id
             WHERE p.customer_id = %d
             ORDER BY p.payment_date DESC",
            $customer_id
        ) );
    }

    public static function save( $data ) {
        global $wpdb;
        $fields = [
            'customer_id'     => absint( $data['customer_id'] ),
            'appointment_id'  => ! empty( $data['appointment_id'] ) ? absint( $data['appointment_id'] ) : null,
            'amount'          => (float) $data['amount'],
            'payment_method'  => sanitize_text_field( $data['payment_method'] ?? 'cash' ),
            'payment_date'    => sanitize_text_field( $data['payment_date'] ?? date( 'Y-m-d' ) ),
            'notes'           => sanitize_textarea_field( $data['notes'] ?? '' ),
        ];
        $wpdb->insert( $wpdb->prefix . 'salon_payments', $fields );
        return $wpdb->insert_id;
    }

    public static function delete( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'salon_payments', [ 'id' => $id ] );
    }

    public static function monthly_report( $year, $month ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT p.payment_method, SUM(p.amount) as total, COUNT(*) as count
             FROM {$wpdb->prefix}salon_payments p
             WHERE YEAR(p.payment_date) = %d AND MONTH(p.payment_date) = %d
             GROUP BY p.payment_method",
            $year, $month
        ) );
    }

    public static function get_debtors() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT c.id, CONCAT(c.first_name,' ',c.last_name) as customer_name, c.phone,
                    COALESCE(SUM(a.price),0) as total_owed,
                    COALESCE((SELECT SUM(amount) FROM {$wpdb->prefix}salon_payments WHERE customer_id = c.id),0) as total_paid
             FROM {$wpdb->prefix}salon_customers c
             JOIN {$wpdb->prefix}salon_appointments a ON a.customer_id = c.id AND a.status NOT IN ('cancelled')
             GROUP BY c.id
             HAVING total_owed > total_paid
             ORDER BY (total_owed - total_paid) DESC"
        );
    }

    public static function total_revenue( $year = null, $month = null ) {
        global $wpdb;
        if ( $year && $month ) {
            return (float) $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}salon_payments WHERE YEAR(payment_date) = %d AND MONTH(payment_date) = %d",
                $year, $month
            ) );
        }
        return (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}salon_payments" );
    }
}
