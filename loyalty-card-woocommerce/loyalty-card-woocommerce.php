<?php
/**
 * Plugin Name: Loyalty Card System - WooCommerce Integration
 * Plugin URI: https://tuosito.com
 * Description: Integrazione WooCommerce per il sistema Carta Fedeltà - permette di usare prodotti WooCommerce come premi e guadagnare punti automaticamente dagli ordini
 * Version: 1.0.0
 * Author: Il tuo nome
 * Requires Plugins: loyalty-card-system, woocommerce
 * License: GPL v2 or later
 */

// Evita accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti
define('LOYALTY_WOO_VERSION', '1.0.0');
define('LOYALTY_WOO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOYALTY_WOO_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Classe principale del plugin addon
 */
class LoyaltyCardWooCommerce {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Verifica dipendenze
        add_action('admin_init', array($this, 'check_dependencies'));

        // Carica plugin se dipendenze OK
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Verifica che i plugin richiesti siano attivi
     */
    public function check_dependencies() {
        $missing_plugins = array();

        // Verifica Loyalty Card System
        if (!class_exists('LoyaltyCardSystem')) {
            $missing_plugins[] = 'Loyalty Card System';
        }

        // Verifica WooCommerce
        if (!class_exists('WooCommerce')) {
            $missing_plugins[] = 'WooCommerce';
        }

        // Se mancano dipendenze, mostra avviso e disattiva
        if (!empty($missing_plugins)) {
            add_action('admin_notices', function() use ($missing_plugins) {
                ?>
                <div class="notice notice-error">
                    <p>
                        <strong>Loyalty Card WooCommerce Integration</strong> richiede i seguenti plugin:
                    </p>
                    <ul style="list-style: disc; padding-left: 20px;">
                        <?php foreach ($missing_plugins as $plugin) : ?>
                            <li><?php echo esc_html($plugin); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p>Per favore installa e attiva questi plugin prima di utilizzare l'integrazione WooCommerce.</p>
                </div>
                <?php
            });

            // Disattiva questo plugin
            deactivate_plugins(plugin_basename(__FILE__));

            return false;
        }

        return true;
    }

    /**
     * Inizializza il plugin
     */
    public function init() {
        // Ricontrolla dipendenze
        if (!$this->check_dependencies()) {
            return;
        }

        // Carica classi
        $this->load_classes();

        // Hook attivazione
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Hook deattivazione
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Carica le classi del plugin
     */
    private function load_classes() {
        // Core integration
        require_once LOYALTY_WOO_PLUGIN_DIR . 'includes/class-woo-integration.php';
        require_once LOYALTY_WOO_PLUGIN_DIR . 'includes/class-woo-product-meta.php';
        require_once LOYALTY_WOO_PLUGIN_DIR . 'includes/class-woo-points-manager.php';
        require_once LOYALTY_WOO_PLUGIN_DIR . 'includes/class-woo-coupon-generator.php';
        require_once LOYALTY_WOO_PLUGIN_DIR . 'includes/class-woo-admin-settings.php';

        // Inizializza componenti
        Loyalty_Woo_Integration::get_instance();
        Loyalty_Woo_Product_Meta::get_instance();
        Loyalty_Woo_Points_Manager::get_instance();
        Loyalty_Woo_Coupon_Generator::get_instance();
        Loyalty_Woo_Admin_Settings::get_instance();
    }

    /**
     * Attivazione plugin
     */
    public function activate() {
        // Verifica versione WordPress
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('Questo plugin richiede WordPress 5.0 o superiore.');
        }

        // Aggiungi opzioni di default
        if (!get_option('loyalty_woo_points_per_euro')) {
            add_option('loyalty_woo_points_per_euro', 1);
        }

        if (!get_option('loyalty_woo_coupon_expiry_days')) {
            add_option('loyalty_woo_coupon_expiry_days', 30);
        }

        if (!get_option('loyalty_woo_auto_points_enabled')) {
            add_option('loyalty_woo_auto_points_enabled', true);
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deattivazione plugin
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Inizializza plugin
LoyaltyCardWooCommerce::get_instance();
