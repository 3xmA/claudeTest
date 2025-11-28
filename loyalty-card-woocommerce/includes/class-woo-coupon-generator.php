<?php
/**
 * Generazione coupon WooCommerce per premi riscattati
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Woo_Coupon_Generator {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook al riscatto premio (quando premio è un prodotto WooCommerce)
        add_action('loyalty_reward_redeemed', array($this, 'handle_product_reward_redemption'), 10, 3);
    }

    /**
     * Gestisce il riscatto di un premio prodotto WooCommerce
     *
     * @param int $redemption_id ID del riscatto
     * @param int $user_id ID utente
     * @param object $reward Oggetto premio
     */
    public function handle_product_reward_redemption($redemption_id, $user_id, $reward) {
        // Verifica se è un premio prodotto WooCommerce
        $product_id = get_post_meta($reward->id, '_loyalty_woo_product_id', true);

        if (!$product_id) {
            return; // Non è un premio WooCommerce, ignora
        }

        // Genera coupon
        $coupon_code = $this->generate_coupon($user_id, $product_id, $reward, $redemption_id);

        if ($coupon_code) {
            // Salva codice coupon nel riscatto
            global $wpdb;
            $table = $wpdb->prefix . 'loyalty_redemptions';

            $wpdb->update($table,
                array('redemption_code' => $coupon_code),
                array('id' => $redemption_id)
            );

            error_log(sprintf(
                'Loyalty WooCommerce: Coupon %s generato per utente #%d (prodotto #%d)',
                $coupon_code,
                $user_id,
                $product_id
            ));
        }
    }

    /**
     * Genera coupon WooCommerce
     */
    private function generate_coupon($user_id, $product_id, $reward, $redemption_id) {
        // Ottieni configurazione prodotto
        $discount_type = get_post_meta($product_id, '_loyalty_discount_type', true);
        $discount_value = get_post_meta($product_id, '_loyalty_discount_value', true);

        if (!$discount_type) {
            $discount_type = 'free';
        }

        // Genera codice coupon univoco
        $coupon_code = 'LOYALTY-' . strtoupper(wp_generate_password(8, false));

        // Determina tipo coupon WooCommerce
        $woo_discount_type = 'percent'; // default
        $woo_amount = 100; // default 100%

        if ($discount_type === 'free') {
            $woo_discount_type = 'percent';
            $woo_amount = 100;
        } elseif ($discount_type === 'percentage') {
            $woo_discount_type = 'percent';
            $woo_amount = floatval($discount_value);
        } elseif ($discount_type === 'fixed') {
            $woo_discount_type = 'fixed_cart';
            $woo_amount = floatval($discount_value);
        }

        // Crea coupon
        $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => sprintf(
                'Coupon fedeltà generato per il riscatto del premio: %s (ID riscatto: %d)',
                $reward->name,
                $redemption_id
            ),
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon'
        );

        $new_coupon_id = wp_insert_post($coupon);

        if (!$new_coupon_id) {
            error_log('Loyalty WooCommerce: Errore creazione coupon');
            return false;
        }

        // Meta del coupon
        update_post_meta($new_coupon_id, 'discount_type', $woo_discount_type);
        update_post_meta($new_coupon_id, 'coupon_amount', $woo_amount);
        update_post_meta($new_coupon_id, 'individual_use', 'yes');
        update_post_meta($new_coupon_id, 'usage_limit', '1');
        update_post_meta($new_coupon_id, 'usage_limit_per_user', '1');
        update_post_meta($new_coupon_id, 'limit_usage_to_x_items', '1');

        // Prodotti applicabili (solo il prodotto specifico)
        update_post_meta($new_coupon_id, 'product_ids', array($product_id));

        // Utenti consentiti (solo chi ha riscattato)
        $user = get_user_by('id', $user_id);
        if ($user) {
            update_post_meta($new_coupon_id, 'customer_email', array($user->user_email));
        }

        // Data scadenza
        $expiry_days = get_option('loyalty_woo_coupon_expiry_days', 30);
        if ($expiry_days > 0) {
            $expiry_date = date('Y-m-d', strtotime("+{$expiry_days} days"));
            update_post_meta($new_coupon_id, 'date_expires', strtotime($expiry_date . ' 23:59:59'));
        }

        // Meta custom per tracciamento
        update_post_meta($new_coupon_id, '_loyalty_redemption_id', $redemption_id);
        update_post_meta($new_coupon_id, '_loyalty_user_id', $user_id);
        update_post_meta($new_coupon_id, '_loyalty_product_id', $product_id);
        update_post_meta($new_coupon_id, '_loyalty_generated_date', current_time('mysql'));

        return $coupon_code;
    }

    /**
     * Ottieni info coupon da redemption code
     */
    public static function get_coupon_info($redemption_code) {
        $coupon = new WC_Coupon($redemption_code);

        if (!$coupon->get_id()) {
            return false;
        }

        return array(
            'code' => $redemption_code,
            'discount_type' => $coupon->get_discount_type(),
            'amount' => $coupon->get_amount(),
            'expiry_date' => $coupon->get_date_expires(),
            'usage_count' => $coupon->get_usage_count(),
            'usage_limit' => $coupon->get_usage_limit(),
            'product_ids' => $coupon->get_product_ids(),
        );
    }

    /**
     * Verifica se coupon è ancora valido
     */
    public static function is_coupon_valid($redemption_code) {
        $coupon = new WC_Coupon($redemption_code);

        if (!$coupon->get_id()) {
            return false;
        }

        // Verifica scadenza
        $expiry_date = $coupon->get_date_expires();
        if ($expiry_date && time() > $expiry_date->getTimestamp()) {
            return false;
        }

        // Verifica utilizzo
        if ($coupon->get_usage_count() >= $coupon->get_usage_limit()) {
            return false;
        }

        return true;
    }
}
