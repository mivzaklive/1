<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Salon_Manager_DB {

    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$wpdb->prefix}salon_customers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            email varchar(150) DEFAULT '',
            address text DEFAULT '',
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY phone (phone)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}salon_appointments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            service varchar(200) NOT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            duration int(11) NOT NULL DEFAULT 60,
            price decimal(10,2) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'scheduled',
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY appointment_date (appointment_date),
            KEY customer_id (customer_id),
            KEY status (status)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}salon_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            appointment_id bigint(20) DEFAULT NULL,
            customer_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(50) NOT NULL DEFAULT 'cash',
            payment_date date NOT NULL,
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY appointment_id (appointment_id),
            KEY payment_date (payment_date)
        ) $charset;";

        dbDelta( $sql );

        if ( false === get_option( 'salon_manager_settings' ) ) {
            add_option( 'salon_manager_settings', self::default_settings() );
        }
        add_option( 'salon_manager_db_version', '1.0.0' );
    }

    public static function deactivate() {}

    public static function default_settings() {
        return [
            'business_name'    => 'סלון היופי',
            'owner_name'       => '',
            'phone'            => '',
            'currency'         => '₪',
            'slot_duration'    => 60,
            'working_days'     => [ 'sun', 'mon', 'tue', 'wed', 'thu' ],
            'friday_enabled'   => false,
            'weekday_start'    => '13:00',
            'weekday_end'      => '21:00',
            'friday_start'     => '09:00',
            'friday_end'       => '13:00',
            'services'         => [
                [ 'name' => 'פדיקור', 'duration' => 60, 'price' => 120 ],
                [ 'name' => 'מניקור', 'duration' => 45, 'price' => 90 ],
                [ 'name' => 'פדיקור + מניקור', 'duration' => 90, 'price' => 190 ],
                [ 'name' => 'ג\'ל', 'duration' => 60, 'price' => 130 ],
                [ 'name' => 'הסרת ג\'ל', 'duration' => 30, 'price' => 50 ],
            ],
        ];
    }
}
