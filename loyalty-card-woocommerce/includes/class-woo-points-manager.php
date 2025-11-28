<?php
/**
 * Gestione punti automatici per ordini WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Woo_Points_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook ordine completato
        add_action('woocommerce_order_status_completed', array($this, 'award_points_for_order'));

        // Hook ordine annullato/rimborsato - rimuovi punti
        add_action('woocommerce_order_status_cancelled', array($this, 'remove_points_for_order'));
        add_action('woocommerce_order_status_refunded', array($this, 'remove_points_for_order'));
    }

    /**
     * Assegna punti quando ordine completato
     */
    public function award_points_for_order($order_id) {
        // Verifica che la funzione sia abilitata
        if (!get_option('loyalty_woo_auto_points_enabled', true)) {
            return;
        }

        // Evita di assegnare punti due volte
        if (get_post_meta($order_id, '_loyalty_points_awarded', true)) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return; // Ordine guest, niente punti
        }

        // Calcola punti dall'importo ordine
        $points = $this->calculate_points_for_order($order);

        if ($points <= 0) {
            return;
        }

        // Aggiungi punti al database
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_transactions';

        $result = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'points' => $points,
            'transaction_type' => 'earn',
            'amount' => $order->get_total(),
            'note' => sprintf('Ordine #%d completato', $order_id),
        ));

        if ($result) {
            // Marca ordine come processato
            update_post_meta($order_id, '_loyalty_points_awarded', $points);
            update_post_meta($order_id, '_loyalty_points_awarded_date', current_time('mysql'));

            // Aggiungi nota all'ordine
            $order->add_order_note(sprintf(
                'Cliente ha guadagnato %d punti fedeltà.',
                $points
            ));

            // Calcola nuovo saldo
            $balance = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(points) FROM $table WHERE user_id = %d",
                $user_id
            ));

            // Invia notifiche
            $this->send_points_notification($user_id, $points, $balance, $order);

            error_log(sprintf(
                'Loyalty WooCommerce: %d punti assegnati all\'utente #%d per ordine #%d',
                $points,
                $user_id,
                $order_id
            ));
        }
    }

    /**
     * Rimuovi punti se ordine cancellato/rimborsato
     */
    public function remove_points_for_order($order_id) {
        // Verifica se i punti erano stati assegnati
        $points_awarded = get_post_meta($order_id, '_loyalty_points_awarded', true);

        if (!$points_awarded) {
            return; // Nessun punto assegnato
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        // Rimuovi punti dal database (transazione negativa)
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_transactions';

        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'points' => -$points_awarded,
            'transaction_type' => 'adjustment',
            'note' => sprintf('Ordine #%d cancellato/rimborsato - punti rimossi', $order_id),
        ));

        // Rimuovi meta
        delete_post_meta($order_id, '_loyalty_points_awarded');
        delete_post_meta($order_id, '_loyalty_points_awarded_date');

        // Nota ordine
        $order->add_order_note(sprintf(
            '%d punti fedeltà rimossi (ordine cancellato/rimborsato).',
            $points_awarded
        ));

        error_log(sprintf(
            'Loyalty WooCommerce: %d punti rimossi dall\'utente #%d (ordine #%d cancellato)',
            $points_awarded,
            $user_id,
            $order_id
        ));
    }

    /**
     * Calcola punti per un ordine
     */
    private function calculate_points_for_order($order) {
        $total = $order->get_total();

        // Sottrai spedizione se configurato
        if (get_option('loyalty_woo_exclude_shipping', false)) {
            $total -= $order->get_shipping_total();
        }

        // Sottrai tasse se configurato
        if (get_option('loyalty_woo_exclude_taxes', false)) {
            $total -= $order->get_total_tax();
        }

        // Controlla se ci sono coupon esclusi
        if (get_option('loyalty_woo_exclude_coupon_orders', false)) {
            $coupons = $order->get_coupon_codes();
            if (!empty($coupons)) {
                return 0; // Niente punti per ordini con coupon
            }
        }

        // Verifica importo minimo
        $min_amount = get_option('loyalty_woo_min_amount', 0);
        if ($total < $min_amount) {
            return 0;
        }

        // Calcola punti base
        $points_per_euro = get_option('loyalty_woo_points_per_euro', 1);
        $points = floor($total * $points_per_euro);

        // Applica moltiplicatori per categoria/prodotto (opzionale)
        $points = apply_filters('loyalty_woo_calculate_order_points', $points, $order, $total);

        return max(0, $points);
    }

    /**
     * Invia notifiche punti guadagnati
     */
    private function send_points_notification($user_id, $points, $balance, $order) {
        // Verifica se le notifiche sono abilitate
        if (!get_option('loyalty_woo_send_notifications', true)) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        // Dati per notifica
        $data = array(
            'user_id' => $user_id,
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'points' => $points,
            'new_balance' => intval($balance),
            'amount' => $order->get_total(),
            'note' => sprintf('Ordine #%d', $order->get_id()),
        );

        // Usa il sistema di notifiche del plugin principale
        // (Email + WhatsApp se configurato)
        do_action('loyalty_send_notification', 'points_added', $data);

        error_log('Loyalty WooCommerce: Notifica inviata all\'utente #' . $user_id);
    }
}
