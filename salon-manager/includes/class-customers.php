<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Salon_Manager_Customers {

    public static function get_all( $search = '' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salon_customers';
        if ( $search ) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE first_name LIKE %s OR last_name LIKE %s OR phone LIKE %s ORDER BY first_name, last_name",
                    '%' . $wpdb->esc_like( $search ) . '%',
                    '%' . $wpdb->esc_like( $search ) . '%',
                    '%' . $wpdb->esc_like( $search ) . '%'
                )
            );
        }
        return $wpdb->get_results( "SELECT * FROM $table ORDER BY first_name, last_name" );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}salon_customers WHERE id = %d", $id
        ) );
    }

    public static function save( $data, $id = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salon_customers';
        $fields = [
            'first_name' => sanitize_text_field( $data['first_name'] ),
            'last_name'  => sanitize_text_field( $data['last_name'] ),
            'phone'      => sanitize_text_field( $data['phone'] ),
            'email'      => sanitize_email( $data['email'] ?? '' ),
            'address'    => sanitize_textarea_field( $data['address'] ?? '' ),
            'notes'      => sanitize_textarea_field( $data['notes'] ?? '' ),
        ];
        if ( $id > 0 ) {
            $wpdb->update( $table, $fields, [ 'id' => $id ] );
            return $id;
        }
        $wpdb->insert( $table, $fields );
        return $wpdb->insert_id;
    }

    public static function delete( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'salon_customers', [ 'id' => $id ] );
    }

    public static function get_stats( $id ) {
        global $wpdb;
        $appointments = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}salon_appointments WHERE customer_id = %d AND status != 'cancelled'", $id
        ) );
        $total_paid = $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}salon_payments WHERE customer_id = %d", $id
        ) );
        $total_owed = $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(price),0) FROM {$wpdb->prefix}salon_appointments WHERE customer_id = %d AND status NOT IN ('cancelled')", $id
        ) );
        $last_visit = $wpdb->get_var( $wpdb->prepare(
            "SELECT MAX(appointment_date) FROM {$wpdb->prefix}salon_appointments WHERE customer_id = %d AND status = 'completed'", $id
        ) );
        return [
            'appointments' => (int) $appointments,
            'total_paid'   => (float) $total_paid,
            'total_owed'   => (float) $total_owed,
            'balance'      => (float) $total_owed - (float) $total_paid,
            'last_visit'   => $last_visit,
        ];
    }

    public static function count() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}salon_customers" );
    }
}
