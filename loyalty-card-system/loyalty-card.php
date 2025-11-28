<?php
/**
 * Plugin Name: Sistema Carta Fedeltà
 * Plugin URI: https://tuosito.com
 * Description: Sistema di carta fedeltà con QR code e punti personalizzato
 * Version: 1.0.0
 * Author: Il tuo nome
 * License: GPL v2 or later
 */

// Evita accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti
define('LOYALTY_VERSION', '1.0.0');
define('LOYALTY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOYALTY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Classe principale del plugin
 */
class LoyaltyCardSystem {
    
    public function __construct() {
        // Hook per attivazione plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Hook per inizializzazione
        add_action('init', array($this, 'init'));
        
        // Menu admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Registra impostazioni
        add_action('admin_init', array($this, 'register_settings'));
        
        // Shortcode per mostrare la carta
        add_shortcode('loyalty_card', array($this, 'display_loyalty_card'));
        
        // API REST
        add_action('rest_api_init', array($this, 'register_api_routes'));
        
        // Enqueue scripts e styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Attivazione plugin - crea tabelle
     */
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabella transazioni punti
        $table_transactions = $wpdb->prefix . 'loyalty_transactions';
        $sql_transactions = "CREATE TABLE IF NOT EXISTS $table_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            points int(11) NOT NULL,
            transaction_type varchar(20) NOT NULL DEFAULT 'earn',
            amount decimal(10,2) DEFAULT NULL,
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Tabella premi
        $table_rewards = $wpdb->prefix . 'loyalty_rewards';
        $sql_rewards = "CREATE TABLE IF NOT EXISTS $table_rewards (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            points_required int(11) NOT NULL,
            reward_type varchar(50) DEFAULT 'discount',
            reward_value varchar(100),
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Tabella riscatti premi
        $table_redemptions = $wpdb->prefix . 'loyalty_redemptions';
        $sql_redemptions = "CREATE TABLE IF NOT EXISTS $table_redemptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            reward_id bigint(20) NOT NULL,
            points_used int(11) NOT NULL,
            redemption_code varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'active',
            redeemed_at datetime DEFAULT CURRENT_TIMESTAMP,
            used_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY redemption_code (redemption_code)
        ) $charset_collate;";
        
        // Tabella log notifiche
        $table_logs = $wpdb->prefix . 'loyalty_notification_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            recipient varchar(255) NOT NULL,
            type varchar(20) NOT NULL,
            event varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            error_message text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recipient (recipient),
            KEY sent_at (sent_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_transactions);
        dbDelta($sql_rewards);
        dbDelta($sql_redemptions);
        dbDelta($sql_logs);
        
        // Inizializza opzioni se non esistono
        if (!get_option('loyalty_api_key')) {
            add_option('loyalty_api_key', wp_generate_password(32, false));
        }
        
        if (!get_option('loyalty_qr_secret')) {
            add_option('loyalty_qr_secret', wp_generate_password(32, false));
        }
        
        if (!get_option('loyalty_points_per_euro')) {
            add_option('loyalty_points_per_euro', 1);
        }
        
        // Installa template notifiche di default
        require_once LOYALTY_PLUGIN_DIR . 'includes/class-default-templates.php';
        Loyalty_Default_Templates::install();
        
        // Aggiungi alcuni premi di esempio
        $this->add_sample_rewards();
    }
    
    /**
     * Aggiungi premi di esempio
     */
    private function add_sample_rewards() {
        global $wpdb;
        $table_rewards = $wpdb->prefix . 'loyalty_rewards';
        
        $sample_rewards = array(
            array('name' => 'Sconto 5€', 'description' => 'Buono sconto di 5€', 'points_required' => 100, 'reward_type' => 'discount', 'reward_value' => '5'),
            array('name' => 'Sconto 10€', 'description' => 'Buono sconto di 10€', 'points_required' => 200, 'reward_type' => 'discount', 'reward_value' => '10'),
            array('name' => 'Prodotto Gratuito', 'description' => 'Un prodotto in omaggio', 'points_required' => 500, 'reward_type' => 'free_product', 'reward_value' => '1'),
        );
        
        foreach ($sample_rewards as $reward) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_rewards WHERE name = %s",
                $reward['name']
            ));
            
            if ($exists == 0) {
                $wpdb->insert($table_rewards, $reward);
            }
        }
    }
    
    /**
     * Inizializzazione
     */
    public function init() {
        // Carica classi helper
        require_once LOYALTY_PLUGIN_DIR . 'includes/class-loyalty-points.php';
        require_once LOYALTY_PLUGIN_DIR . 'includes/class-loyalty-qrcode.php';
        require_once LOYALTY_PLUGIN_DIR . 'includes/class-email-sender.php';
        require_once LOYALTY_PLUGIN_DIR . 'includes/class-whatsapp-sender.php';
        require_once LOYALTY_PLUGIN_DIR . 'includes/class-default-templates.php';
    }
    
    /**
     * Menu admin
     */
    public function add_admin_menu() {
        add_menu_page(
            'Carta Fedeltà',
            'Carta Fedeltà',
            'manage_options',
            'loyalty-card',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
        
        add_submenu_page(
            'loyalty-card',
            'Transazioni',
            'Transazioni',
            'manage_options',
            'loyalty-transactions',
            array($this, 'transactions_page')
        );
        
        add_submenu_page(
            'loyalty-card',
            'Premi',
            'Premi',
            'manage_options',
            'loyalty-rewards',
            array($this, 'rewards_page')
        );
        
        add_submenu_page(
            'loyalty-card',
            'Utenti',
            'Utenti',
            'manage_options',
            'loyalty-users',
            array($this, 'users_page')
        );
        
        add_submenu_page(
            'loyalty-card',
            'Notifiche',
            'Notifiche',
            'manage_options',
            'loyalty-notifications',
            array($this, 'notifications_page')
        );
    }
    
    /**
     * Pagina admin principale
     */
    public function admin_page() {
        include LOYALTY_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    /**
     * Pagina transazioni
     */
    public function transactions_page() {
        include LOYALTY_PLUGIN_DIR . 'admin/transactions.php';
    }
    
    /**
     * Pagina premi
     */
    public function rewards_page() {
        include LOYALTY_PLUGIN_DIR . 'admin/rewards.php';
    }
    
    /**
     * Pagina utenti
     */
    public function users_page() {
        include LOYALTY_PLUGIN_DIR . 'admin/users.php';
    }
    
    /**
     * Pagina notifiche
     */
    public function notifications_page() {
        include LOYALTY_PLUGIN_DIR . 'admin/notifications.php';
    }
    
    /**
     * Registra impostazioni
     */
    public function register_settings() {
        register_setting('loyalty_settings', 'loyalty_points_per_euro', array(
            'type' => 'number',
            'default' => 1,
            'sanitize_callback' => 'floatval'
        ));
        
        register_setting('loyalty_settings', 'loyalty_api_key', array(
            'type' => 'string',
            'default' => wp_generate_password(32, false),
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('loyalty_settings', 'loyalty_webhook_url', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'esc_url_raw'
        ));
        
        register_setting('loyalty_settings', 'loyalty_qr_secret', array(
            'type' => 'string',
            'default' => wp_generate_password(32, false),
            'sanitize_callback' => 'sanitize_text_field'
        ));
    }
    
    /**
     * Registra API REST
     */
    public function register_api_routes() {
        // Endpoint per aggiungere punti
        register_rest_route('loyalty/v1', '/add-points', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_add_points'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));
        
        // Endpoint per ottenere saldo
        register_rest_route('loyalty/v1', '/balance/(?P<user_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_balance'),
            'permission_callback' => '__return_true',
        ));
        
        // Endpoint per lista premi
        register_rest_route('loyalty/v1', '/rewards', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_rewards'),
            'permission_callback' => '__return_true',
        ));
        
        // Endpoint per riscattare premio
        register_rest_route('loyalty/v1', '/redeem', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_redeem_reward'),
            'permission_callback' => 'is_user_logged_in',
        ));
        
        // Endpoint per validare QR premio
        register_rest_route('loyalty/v1', '/validate-reward', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_validate_reward'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));

        // Endpoint DEBUG: verifica dati utente (telefono, email, ecc.)
        register_rest_route('loyalty/v1', '/debug-user/(?P<user_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_debug_user'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));
    }
    
    /**
     * Controllo permessi API
     */
    public function check_api_permission() {
        // Qui puoi implementare autenticazione con API key o JWT
        // Per ora uso un sistema semplice con header
        $api_key = get_option('loyalty_api_key', 'your-secret-key-here');
        $request_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
        
        return $request_key === $api_key || current_user_can('manage_options');
    }
    
    /**
     * API: Aggiungi punti
     */
    public function api_add_points($request) {
        global $wpdb;
        
        $user_id = $request->get_param('user_id');
        $points = $request->get_param('points');
        $amount = $request->get_param('amount');
        $note = $request->get_param('note');
        
        if (!$user_id || !$points) {
            return new WP_Error('missing_params', 'User ID e punti sono richiesti', array('status' => 400));
        }
        
        // Verifica che l'utente esista
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('invalid_user', 'Utente non trovato', array('status' => 404));
        }
        
        // Inserisci transazione
        $table = $wpdb->prefix . 'loyalty_transactions';
        $result = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'points' => $points,
            'transaction_type' => 'earn',
            'amount' => $amount,
            'note' => $note,
        ));
        
        if ($result === false) {
            return new WP_Error('db_error', 'Errore database', array('status' => 500));
        }
        
        // Calcola nuovo saldo
        $balance = $this->get_user_balance($user_id);
        
        // Trigger webhook n8n
        $this->trigger_webhook('points_added', array(
            'user_id' => $user_id,
            'user_email' => $user->user_email,
            'user_name' => $user->display_name,
            'points' => $points,
            'new_balance' => $balance,
            'amount' => $amount,
            'note' => $note,
        ));
        
        return array(
            'success' => true,
            'transaction_id' => $wpdb->insert_id,
            'new_balance' => $balance,
            'message' => 'Punti aggiunti con successo',
        );
    }
    
    /**
     * API: Ottieni saldo
     */
    public function api_get_balance($request) {
        $user_id = $request->get_param('user_id');
        $balance = $this->get_user_balance($user_id);
        
        $user = get_user_by('id', $user_id);
        
        return array(
            'user_id' => $user_id,
            'balance' => $balance,
            'user_name' => $user ? $user->display_name : '',
            'user_email' => $user ? $user->user_email : '',
        );
    }
    
    /**
     * API: Lista premi
     */
    public function api_get_rewards() {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_rewards';
        
        $rewards = $wpdb->get_results("SELECT * FROM $table WHERE is_active = 1 ORDER BY points_required ASC");
        
        return array(
            'success' => true,
            'rewards' => $rewards,
        );
    }
    
    /**
     * API: Riscatta premio
     */
    public function api_redeem_reward($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $reward_id = $request->get_param('reward_id');
        
        // Ottieni info premio
        $table_rewards = $wpdb->prefix . 'loyalty_rewards';
        $reward = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_rewards WHERE id = %d AND is_active = 1", $reward_id));
        
        if (!$reward) {
            return new WP_Error('invalid_reward', 'Premio non trovato', array('status' => 404));
        }
        
        // Controlla saldo
        $balance = $this->get_user_balance($user_id);
        if ($balance < $reward->points_required) {
            return new WP_Error('insufficient_points', 'Punti insufficienti', array('status' => 400));
        }
        
        // Genera codice riscatto
        $redemption_code = strtoupper(wp_generate_password(8, false));
        
        // Inserisci riscatto
        $table_redemptions = $wpdb->prefix . 'loyalty_redemptions';
        $wpdb->insert($table_redemptions, array(
            'user_id' => $user_id,
            'reward_id' => $reward_id,
            'points_used' => $reward->points_required,
            'redemption_code' => $redemption_code,
        ));
        
        // Sottrai punti
        $table_transactions = $wpdb->prefix . 'loyalty_transactions';
        $wpdb->insert($table_transactions, array(
            'user_id' => $user_id,
            'points' => -$reward->points_required,
            'transaction_type' => 'redeem',
            'note' => 'Riscatto premio: ' . $reward->name,
        ));
        
        $user = wp_get_current_user();
        
        // Trigger webhook
        $this->trigger_webhook('reward_redeemed', array(
            'user_id' => $user_id,
            'user_email' => $user->user_email,
            'user_name' => $user->display_name,
            'reward_name' => $reward->name,
            'redemption_code' => $redemption_code,
            'points_used' => $reward->points_required,
            'new_balance' => $this->get_user_balance($user_id),
        ));
        
        return array(
            'success' => true,
            'redemption_code' => $redemption_code,
            'reward' => $reward,
            'new_balance' => $this->get_user_balance($user_id),
        );
    }
    
    /**
     * API: Valida premio riscattato
     */
    public function api_validate_reward($request) {
        global $wpdb;

        $redemption_code = $request->get_param('redemption_code');

        $table_redemptions = $wpdb->prefix . 'loyalty_redemptions';
        $table_rewards = $wpdb->prefix . 'loyalty_rewards';

        // Ottieni riscatto con dati premio
        $redemption = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, rw.name as reward_name, rw.description as reward_description, rw.reward_type, rw.reward_value
             FROM $table_redemptions r
             LEFT JOIN $table_rewards rw ON r.reward_id = rw.id
             WHERE r.redemption_code = %s AND r.status = 'active'",
            $redemption_code
        ));

        if (!$redemption) {
            return new WP_Error('invalid_code', 'Codice non valido o già utilizzato', array('status' => 404));
        }

        // Marca come utilizzato
        $wpdb->update($table_redemptions,
            array('status' => 'used', 'used_at' => current_time('mysql')),
            array('id' => $redemption->id)
        );

        // Ottieni dati utente
        $user = get_user_by('id', $redemption->user_id);

        return array(
            'success' => true,
            'message' => 'Premio validato con successo',
            'redemption' => array(
                'code' => $redemption->redemption_code,
                'points_used' => intval($redemption->points_used),
                'redeemed_at' => $redemption->redeemed_at,
                'used_at' => current_time('mysql'),
                'reward' => array(
                    'name' => $redemption->reward_name,
                    'description' => $redemption->reward_description,
                    'type' => $redemption->reward_type,
                    'value' => $redemption->reward_value,
                ),
                'user' => array(
                    'id' => $redemption->user_id,
                    'name' => $user ? $user->display_name : 'N/A',
                    'email' => $user ? $user->user_email : 'N/A',
                ),
            ),
        );
    }

    /**
     * API DEBUG: Mostra tutti i dati utente per debug notifiche
     */
    public function api_debug_user($request) {
        $user_id = $request->get_param('user_id');

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('invalid_user', 'Utente non trovato', array('status' => 404));
        }

        // Recupera telefono da vari campi
        $phone = $this->get_user_phone($user_id);

        // Dettaglio recupero telefono
        $phone_sources = array();

        // ACF
        if (function_exists('get_field')) {
            $acf_phone = get_field('field_6928079e6b6ec', 'user_' . $user_id);
            $phone_sources['acf_field_6928079e6b6ec'] = $acf_phone ? $acf_phone : 'vuoto';
        } else {
            $phone_sources['acf_field_6928079e6b6ec'] = 'ACF non disponibile';
        }

        // WooCommerce
        $billing_phone = get_user_meta($user_id, 'billing_phone', true);
        $phone_sources['billing_phone'] = $billing_phone ? $billing_phone : 'vuoto';

        // Generico
        $generic_phone = get_user_meta($user_id, 'phone', true);
        $phone_sources['phone'] = $generic_phone ? $generic_phone : 'vuoto';

        // Configurazione notifiche
        $notifications_status = array(
            'email_points_added' => array(
                'enabled' => get_option('loyalty_email_points_added_enabled', false),
                'subject' => get_option('loyalty_email_points_added_subject', 'non configurato'),
                'has_body' => !empty(get_option('loyalty_email_points_added_body', '')),
            ),
            'whatsapp_points_added' => array(
                'enabled' => get_option('loyalty_whatsapp_points_added_enabled', false),
                'has_message' => !empty(get_option('loyalty_whatsapp_points_added_text', '')),
            ),
            'evolution_api' => array(
                'url' => get_option('loyalty_evolution_url', 'non configurato'),
                'instance' => get_option('loyalty_evolution_instance', 'non configurato'),
                'apikey' => get_option('loyalty_evolution_apikey', '') ? '***configurata***' : 'non configurata',
            ),
        );

        return array(
            'success' => true,
            'user' => array(
                'id' => $user_id,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'phone_normalized' => $phone ? $phone : 'TELEFONO NON TROVATO',
            ),
            'phone_sources' => $phone_sources,
            'notifications_config' => $notifications_status,
            'balance' => $this->get_user_balance($user_id),
            'help' => 'Questo endpoint mostra tutti i dati utili per debuggare le notifiche. Il telefono normalizzato è quello che verrà usato per WhatsApp.',
        );
    }
    
    /**
     * Ottieni saldo utente
     */
    private function get_user_balance($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_transactions';

        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $table WHERE user_id = %d",
            $user_id
        ));

        return $balance ? intval($balance) : 0;
    }

    /**
     * Ottieni numero di telefono utente da vari campi
     */
    private function get_user_phone($user_id) {
        $phone = '';

        // 1. Prova campo ACF field_6928079e6b6ec
        if (function_exists('get_field')) {
            $acf_phone = get_field('field_6928079e6b6ec', 'user_' . $user_id);
            if (!empty($acf_phone)) {
                $phone = $acf_phone;
            }
        }

        // 2. Prova campo WooCommerce billing_phone
        if (empty($phone)) {
            $billing_phone = get_user_meta($user_id, 'billing_phone', true);
            if (!empty($billing_phone)) {
                $phone = $billing_phone;
            }
        }

        // 3. Prova campo generico phone
        if (empty($phone)) {
            $phone = get_user_meta($user_id, 'phone', true);
        }

        // Normalizza formato: rimuovi +, spazi, trattini, parentesi
        if (!empty($phone)) {
            $phone = str_replace(['+', ' ', '-', '(', ')'], '', $phone);
        }

        return $phone;
    }
    
    /**
     * Trigger webhook n8n
     */
    /**
     * Invia notifiche (Email + WhatsApp)
     */
    private function trigger_webhook($event, $data) {
        try {
            // Aggiungi telefono se presente
            if (isset($data['user_id'])) {
                $phone = $this->get_user_phone($data['user_id']);
                if (!empty($phone)) {
                    $data['user_phone'] = $phone;
                }
            }
            
            // Determina quale evento inviare
            $notification_event = $event;
            
            // Se punti aggiunti e saldo >= 100, invia anche soglia raggiunta
            if ($event === 'points_added' && isset($data['new_balance']) && $data['new_balance'] >= 100) {
                $this->send_notification('threshold_reached', $data);
            }
            
            // Invia notifica principale
            $this->send_notification($notification_event, $data);
            
            // Mantieni compatibilità con webhook n8n se configurato
            $webhook_url = get_option('loyalty_webhook_url', '');
            if (!empty($webhook_url)) {
                $payload = array(
                    'event' => $event,
                    'timestamp' => current_time('mysql'),
                    'data' => $data,
                );
                
                wp_remote_post($webhook_url, array(
                    'body' => json_encode($payload),
                    'headers' => array('Content-Type' => 'application/json'),
                    'timeout' => 5,
                    'blocking' => false,
                ));
            }
        } catch (Exception $e) {
            // Log errore ma non bloccare la risposta
            error_log('Errore invio notifiche: ' . $e->getMessage());
        }
    }
    
    /**
     * Invia singola notifica
     */
    private function send_notification($event, $data) {
        try {
            // Email
            $email_enabled = get_option('loyalty_email_' . $event . '_enabled', false);
            if ($email_enabled) {
                if (empty($data['user_email'])) {
                    error_log("Loyalty: Email non inviata per evento '{$event}' - email utente mancante");
                } else {
                    $subject = get_option('loyalty_email_' . $event . '_subject', '');
                    $body = get_option('loyalty_email_' . $event . '_body', '');

                    if (empty($subject) || empty($body)) {
                        error_log("Loyalty: Email non inviata per evento '{$event}' - template mancante");
                    } else {
                        $subject = Loyalty_Email_Sender::replace_variables($subject, $data);
                        $body = Loyalty_Email_Sender::replace_variables($body, $data);

                        $sent = Loyalty_Email_Sender::send($data['user_email'], $subject, $body, $event);
                        if ($sent) {
                            error_log("Loyalty: Email inviata con successo a {$data['user_email']} per evento '{$event}'");
                        }
                    }
                }
            } else {
                error_log("Loyalty: Email disabilitata per evento '{$event}'");
            }

            // WhatsApp
            $whatsapp_enabled = get_option('loyalty_whatsapp_' . $event . '_enabled', false);
            if ($whatsapp_enabled) {
                if (empty($data['user_phone'])) {
                    error_log("Loyalty: WhatsApp non inviato per evento '{$event}' - telefono utente mancante (user_id: {$data['user_id']})");
                } else {
                    $message = get_option('loyalty_whatsapp_' . $event . '_text', '');

                    if (empty($message)) {
                        error_log("Loyalty: WhatsApp non inviato per evento '{$event}' - template mancante");
                    } else {
                        $message = Loyalty_WhatsApp_Sender::replace_variables($message, $data);

                        $sent = Loyalty_WhatsApp_Sender::send($data['user_phone'], $message, $event);
                        if ($sent) {
                            error_log("Loyalty: WhatsApp inviato con successo a {$data['user_phone']} per evento '{$event}'");
                        }

                        // Se è premio riscattato, invia anche QR
                        if ($event === 'reward_redeemed' && !empty($data['redemption_code'])) {
                            $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=512x512&data=LOYALTY-REWARD-' . $data['redemption_code'];
                            $caption = 'Mostra questo QR al negoziante! 🎫\n\nCodice: ' . $data['redemption_code'];

                            Loyalty_WhatsApp_Sender::send_media($data['user_phone'], $qr_url, $caption, $event);
                        }
                    }
                }
            } else {
                error_log("Loyalty: WhatsApp disabilitato per evento '{$event}'");
            }
        } catch (Exception $e) {
            // Log errore ma non bloccare
            error_log('Errore send_notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Shortcode per mostrare carta fedeltà
     */
    public function display_loyalty_card($atts) {
        if (!is_user_logged_in()) {
            return '<p>Devi effettuare il login per visualizzare la tua carta fedeltà.</p>';
        }
        
        $user_id = get_current_user_id();
        $balance = $this->get_user_balance($user_id);
        
        ob_start();
        include LOYALTY_PLUGIN_DIR . 'templates/loyalty-card.php';
        return ob_get_clean();
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style('loyalty-card-style', LOYALTY_PLUGIN_URL . 'assets/css/style.css', array(), LOYALTY_VERSION);
        wp_enqueue_script('loyalty-card-script', LOYALTY_PLUGIN_URL . 'assets/js/script.js', array('jquery'), LOYALTY_VERSION, true);
        
        wp_localize_script('loyalty-card-script', 'loyaltyAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('loyalty/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }
}

// Inizializza plugin
new LoyaltyCardSystem();
