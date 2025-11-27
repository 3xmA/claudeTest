<?php
/**
 * Classe per gestione invio WhatsApp via Evolution API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_WhatsApp_Sender {
    
    /**
     * Invia messaggio WhatsApp
     */
    public static function send($phone, $message, $event = '') {
        // Verifica orari (no notturne se configurato)
        if (!self::is_allowed_time()) {
            self::log_send($phone, 'whatsapp', $event, 'blocked', 'Orario non consentito');
            return false;
        }
        
        // Verifica numero valido
        if (empty($phone)) {
            self::log_send($phone, 'whatsapp', $event, 'failed', 'Numero telefono mancante');
            return false;
        }
        
        // Ottieni configurazione Evolution API
        $api_url = get_option('loyalty_evolution_url', '');
        $instance = get_option('loyalty_evolution_instance', '');
        $api_key = get_option('loyalty_evolution_apikey', '');
        
        if (empty($api_url) || empty($instance) || empty($api_key)) {
            self::log_send($phone, 'whatsapp', $event, 'failed', 'Evolution API non configurato');
            return false;
        }
        
        // Rimuovi + se presente (Evolution lo vuole senza)
        $phone = str_replace('+', '', $phone);
        
        // Prepara richiesta
        $url = rtrim($api_url, '/') . '/message/sendText/' . $instance;
        
        $body = array(
            'number' => $phone,
            'text' => $message,
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $api_key,
            ),
            'body' => json_encode($body),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            self::log_send($phone, 'whatsapp', $event, 'failed', $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($status_code >= 200 && $status_code < 300) {
            self::log_send($phone, 'whatsapp', $event, 'sent');
            return true;
        } else {
            self::log_send($phone, 'whatsapp', $event, 'failed', 'HTTP ' . $status_code . ': ' . $response_body);
            return false;
        }
    }
    
    /**
     * Invia immagine WhatsApp (es: QR code premio)
     */
    public static function send_media($phone, $media_url, $caption = '', $event = '') {
        // Verifica orari
        if (!self::is_allowed_time()) {
            self::log_send($phone, 'whatsapp', $event, 'blocked', 'Orario non consentito');
            return false;
        }
        
        // Ottieni configurazione
        $api_url = get_option('loyalty_evolution_url', '');
        $instance = get_option('loyalty_evolution_instance', '');
        $api_key = get_option('loyalty_evolution_apikey', '');
        
        if (empty($api_url) || empty($instance) || empty($api_key)) {
            return false;
        }
        
        // Rimuovi +
        $phone = str_replace('+', '', $phone);
        
        // Prepara richiesta
        $url = rtrim($api_url, '/') . '/message/sendMedia/' . $instance;
        
        $body = array(
            'number' => $phone,
            'mediatype' => 'image',
            'media' => $media_url,
            'caption' => $caption,
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $api_key,
            ),
            'body' => json_encode($body),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return ($status_code >= 200 && $status_code < 300);
    }
    
    /**
     * Sostituisce variabili nel messaggio
     */
    public static function replace_variables($message, $data) {
        $variables = array(
            '{user_name}' => isset($data['user_name']) ? $data['user_name'] : '',
            '{user_email}' => isset($data['user_email']) ? $data['user_email'] : '',
            '{user_phone}' => isset($data['user_phone']) ? $data['user_phone'] : '',
            '{points}' => isset($data['points']) ? $data['points'] : 0,
            '{new_balance}' => isset($data['new_balance']) ? $data['new_balance'] : 0,
            '{amount}' => isset($data['amount']) ? number_format($data['amount'], 2, ',', '.') : '0,00',
            '{reward_name}' => isset($data['reward_name']) ? $data['reward_name'] : '',
            '{redemption_code}' => isset($data['redemption_code']) ? $data['redemption_code'] : '',
            '{note}' => isset($data['note']) ? $data['note'] : '',
            '{site_name}' => get_bloginfo('name'),
            '{current_date}' => date_i18n('d/m/Y'),
            '{current_time}' => date_i18n('H:i'),
        );
        
        return str_replace(array_keys($variables), array_values($variables), $message);
    }
    
    /**
     * Verifica se è orario consentito per invio
     */
    private static function is_allowed_time() {
        $block_night = get_option('loyalty_block_night_notifications', false);
        
        if (!$block_night) {
            return true;
        }
        
        $start_hour = get_option('loyalty_night_start_hour', 23);
        $end_hour = get_option('loyalty_night_end_hour', 8);
        $current_hour = (int) current_time('H');
        
        if ($start_hour > $end_hour) {
            return !($current_hour >= $start_hour || $current_hour < $end_hour);
        }
        
        return !($current_hour >= $start_hour && $current_hour < $end_hour);
    }
    
    /**
     * Log invio notifica
     */
    private static function log_send($recipient, $type, $event, $status, $error = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_notification_logs';
        
        // Verifica se la tabella esiste
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            self::create_logs_table();
        }
        
        $wpdb->insert($table, array(
            'recipient' => $recipient,
            'type' => $type,
            'event' => $event,
            'status' => $status,
            'error_message' => $error,
            'sent_at' => current_time('mysql'),
        ));
    }
    
    /**
     * Test connessione Evolution API
     */
    public static function test_connection() {
        $api_url = get_option('loyalty_evolution_url', '');
        $instance = get_option('loyalty_evolution_instance', '');
        $api_key = get_option('loyalty_evolution_apikey', '');
        
        if (empty($api_url)) {
            return array(
                'success' => false,
                'message' => 'URL Evolution API mancante. Inserisci l\'URL e salva prima di testare.'
            );
        }
        
        if (empty($instance)) {
            return array(
                'success' => false,
                'message' => 'Nome istanza mancante. Inserisci il nome dell\'istanza e salva prima di testare.'
            );
        }
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'API Key mancante. Inserisci l\'API key e salva prima di testare.'
            );
        }
        
        // Test connessione: verifica stato istanza
        $url = rtrim($api_url, '/') . '/instance/connectionState/' . $instance;
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'apikey' => $api_key,
            ),
            'timeout' => 10,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Errore connessione: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        $body = json_decode($body_raw, true);
        
        // Debug: mostra risposta se non è come ci aspettiamo
        if ($status_code === 200) {
            // Prova diversi formati di risposta Evolution API
            
            // Formato 1: {state: "open"}
            if (isset($body['state'])) {
                $state = $body['state'];
                
                if ($state === 'open') {
                    return array(
                        'success' => true,
                        'message' => 'WhatsApp connesso e pronto! ✅',
                        'state' => $state
                    );
                } else {
                    return array(
                        'success' => false,
                        'message' => 'WhatsApp non connesso. Stato: ' . $state . ' - Scansiona il QR code in Evolution API per connettere.',
                        'state' => $state
                    );
                }
            }
            
            // Formato 2: {instance: {..., state: "open"}}
            if (isset($body['instance']) && isset($body['instance']['state'])) {
                $state = $body['instance']['state'];
                
                if ($state === 'open') {
                    return array(
                        'success' => true,
                        'message' => 'WhatsApp connesso e pronto! ✅',
                        'state' => $state
                    );
                } else {
                    return array(
                        'success' => false,
                        'message' => 'WhatsApp non connesso. Stato: ' . $state . ' - Scansiona il QR code in Evolution API.',
                        'state' => $state
                    );
                }
            }
            
            // Formato 3: risposta array vuoto o stringa
            if (empty($body) && !empty($body_raw)) {
                return array(
                    'success' => false,
                    'message' => 'Risposta API: ' . substr($body_raw, 0, 200) . ' - Formato non riconosciuto. Verifica versione Evolution API.'
                );
            }
            
            // Formato non riconosciuto - mostra cosa abbiamo ricevuto
            return array(
                'success' => false,
                'message' => 'Formato risposta non riconosciuto. Ricevuto: ' . json_encode($body) . ' - Contatta supporto.'
            );
        }
        
        // Codici di errore HTTP
        if ($status_code === 401) {
            return array(
                'success' => false,
                'message' => 'API Key non valida. HTTP 401 - Verifica che l\'API key sia corretta.'
            );
        }
        
        if ($status_code === 404) {
            return array(
                'success' => false,
                'message' => 'Istanza non trovata. HTTP 404 - Verifica che il nome istanza "' . $instance . '" sia corretto.'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Errore HTTP ' . $status_code . ' - Risposta: ' . substr($body_raw, 0, 200)
        );
    }
    
    /**
     * Invia messaggio WhatsApp di test
     */
    public static function send_test_message($phone, $template_key = 'points_added') {
        $message_key = 'loyalty_whatsapp_' . $template_key . '_text';
        $message = get_option($message_key, 'Test WhatsApp');
        
        // Dati di test
        $test_data = array(
            'user_name' => 'Mario Rossi',
            'user_phone' => $phone,
            'points' => 50,
            'new_balance' => 150,
            'amount' => 50.00,
            'reward_name' => 'Sconto 10€',
            'redemption_code' => 'TEST1234',
            'note' => 'Test acquisto',
        );
        
        $message = self::replace_variables($message, $test_data);
        
        return self::send($phone, $message, 'test');
    }
    
    /**
     * Ottieni statistiche invii
     */
    public static function get_stats($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_notification_logs';
        
        // Verifica se la tabella esiste, altrimenti creala
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            self::create_logs_table();
        }
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE type = 'whatsapp' AND sent_at >= %s",
            $date_from
        ));
        
        $sent = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE type = 'whatsapp' AND status = 'sent' AND sent_at >= %s",
            $date_from
        ));
        
        $failed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE type = 'whatsapp' AND status = 'failed' AND sent_at >= %s",
            $date_from
        ));
        
        $blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE type = 'whatsapp' AND status = 'blocked' AND sent_at >= %s",
            $date_from
        ));
        
        $success_rate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
        
        return array(
            'total' => (int) $total,
            'sent' => (int) $sent,
            'failed' => (int) $failed,
            'blocked' => (int) $blocked,
            'success_rate' => $success_rate,
        );
    }
    
    /**
     * Crea tabella log se non esiste
     */
    private static function create_logs_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_notification_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        dbDelta($sql);
    }
}
