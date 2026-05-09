<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Salon_Manager_Appointments {

    public static function get_by_date( $date ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT a.*, CONCAT(c.first_name,' ',c.last_name) as customer_name, c.phone
             FROM {$wpdb->prefix}salon_appointments a
             JOIN {$wpdb->prefix}salon_customers c ON c.id = a.customer_id
             WHERE a.appointment_date = %s AND a.status != 'cancelled'
             ORDER BY a.appointment_time",
            $date
        ) );
    }

    public static function get_by_week( $start_date, $end_date ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT a.*, CONCAT(c.first_name,' ',c.last_name) as customer_name, c.phone
             FROM {$wpdb->prefix}salon_appointments a
             JOIN {$wpdb->prefix}salon_customers c ON c.id = a.customer_id
             WHERE a.appointment_date BETWEEN %s AND %s AND a.status != 'cancelled'
             ORDER BY a.appointment_date, a.appointment_time",
            $start_date, $end_date
        ) );
    }

    public static function get_by_month( $year, $month ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT a.*, CONCAT(c.first_name,' ',c.last_name) as customer_name
             FROM {$wpdb->prefix}salon_appointments a
             JOIN {$wpdb->prefix}salon_customers c ON c.id = a.customer_id
             WHERE YEAR(a.appointment_date) = %d AND MONTH(a.appointment_date) = %d AND a.status != 'cancelled'
             ORDER BY a.appointment_date, a.appointment_time",
            $year, $month
        ) );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, CONCAT(c.first_name,' ',c.last_name) as customer_name, c.phone
             FROM {$wpdb->prefix}salon_appointments a
             JOIN {$wpdb->prefix}salon_customers c ON c.id = a.customer_id
             WHERE a.id = %d",
            $id
        ) );
    }

    public static function get_for_customer( $customer_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}salon_appointments WHERE customer_id = %d ORDER BY appointment_date DESC, appointment_time DESC",
            $customer_id
        ) );
    }

    public static function save( $data, $id = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'salon_appointments';
        $fields = [
            'customer_id'      => absint( $data['customer_id'] ),
            'service'          => sanitize_text_field( $data['service'] ),
            'appointment_date' => sanitize_text_field( $data['appointment_date'] ),
            'appointment_time' => sanitize_text_field( $data['appointment_time'] ),
            'duration'         => absint( $data['duration'] ?? 60 ),
            'price'            => (float) $data['price'],
            'status'           => sanitize_text_field( $data['status'] ?? 'scheduled' ),
            'notes'            => sanitize_textarea_field( $data['notes'] ?? '' ),
        ];
        if ( $id > 0 ) {
            $wpdb->update( $table, $fields, [ 'id' => $id ] );
            return $id;
        }
        $wpdb->insert( $table, $fields );
        return $wpdb->insert_id;
    }

    public static function update_status( $id, $status ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'salon_appointments',
            [ 'status' => sanitize_text_field( $status ) ],
            [ 'id' => $id ]
        );
    }

    public static function delete( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'salon_appointments', [ 'id' => $id ] );
    }

    public static function get_upcoming( $limit = 10 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT a.*, CONCAT(c.first_name,' ',c.last_name) as customer_name, c.phone
             FROM {$wpdb->prefix}salon_appointments a
             JOIN {$wpdb->prefix}salon_customers c ON c.id = a.customer_id
             WHERE a.appointment_date >= CURDATE() AND a.status = 'scheduled'
             ORDER BY a.appointment_date, a.appointment_time
             LIMIT %d",
            $limit
        ) );
    }

    public static function count_today() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}salon_appointments WHERE appointment_date = CURDATE() AND status != 'cancelled'"
        );
    }

    public static function count_month() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}salon_appointments WHERE YEAR(appointment_date) = YEAR(CURDATE()) AND MONTH(appointment_date) = MONTH(CURDATE()) AND status != 'cancelled'"
        );
    }

    public static function revenue_month() {
        global $wpdb;
        return (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}salon_payments WHERE YEAR(payment_date) = YEAR(CURDATE()) AND MONTH(payment_date) = MONTH(CURDATE())"
        );
    }

    public static function get_available_slots( $date, $settings ) {
        $day_of_week = date( 'N', strtotime( $date ) );
        $day_map = [ 1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat', 7 => 'sun' ];
        $day_key = $day_map[ $day_of_week ];

        if ( $day_key === 'fri' ) {
            if ( empty( $settings['friday_enabled'] ) ) return [];
            $start = $settings['friday_start'];
            $end   = $settings['friday_end'];
        } elseif ( $day_key === 'sat' ) {
            return [];
        } else {
            if ( ! in_array( $day_key, (array) $settings['working_days'] ) ) return [];
            $start = $settings['weekday_start'];
            $end   = $settings['weekday_end'];
        }

        global $wpdb;
        $booked = $wpdb->get_col( $wpdb->prepare(
            "SELECT appointment_time FROM {$wpdb->prefix}salon_appointments WHERE appointment_date = %s AND status != 'cancelled'",
            $date
        ) );

        $slots    = [];
        $duration = (int) ( $settings['slot_duration'] ?? 60 );
        $current  = strtotime( $date . ' ' . $start );
        $finish   = strtotime( $date . ' ' . $end );

        while ( $current + ( $duration * 60 ) <= $finish ) {
            $slot_time = date( 'H:i', $current );
            $slots[]   = [
                'time'    => $slot_time,
                'booked'  => in_array( $slot_time . ':00', $booked ) || in_array( $slot_time, $booked ),
            ];
            $current += $duration * 60;
        }
        return $slots;
    }
}
