<?php
/**
 * Classe per gestione invio email
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Email_Sender {
    
    /**
     * Invia email con template
     */
    public static function send($to, $subject, $body, $event = '') {
        // Verifica orari (no notturne se configurato)
        if (!self::is_allowed_time()) {
            self::log_send($to, 'email', $event, 'blocked', 'Orario non consentito');
            return false;
        }
        
        // Ottieni configurazione SMTP
        $smtp_enabled = get_option('loyalty_smtp_enabled', false);
        
        if ($smtp_enabled) {
            $result = self::send_smtp($to, $subject, $body);
        } else {
            $result = self::send_wp_mail($to, $subject, $body);
        }
        
        // Log invio
        $status = $result ? 'sent' : 'failed';
        self::log_send($to, 'email', $event, $status, $result ? '' : 'Errore invio');
        
        return $result;
    }
    
    /**
     * Invia via SMTP configurato
     */
    private static function send_smtp($to, $subject, $body) {
        try {
            // Configurazione SMTP da opzioni
            $smtp_host = get_option('loyalty_smtp_host', '');
            $smtp_port = get_option('loyalty_smtp_port', 587);
            $smtp_user = get_option('loyalty_smtp_user', '');
            $smtp_pass = get_option('loyalty_smtp_pass', '');
            $smtp_secure = get_option('loyalty_smtp_secure', 'tls');
            $from_email = get_option('loyalty_smtp_from_email', get_option('admin_email'));
            $from_name = get_option('loyalty_smtp_from_name', get_bloginfo('name'));
            
            // Usa PHPMailer di WordPress
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configurazione SMTP
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            $mail->SMTPSecure = $smtp_secure;
            $mail->Port = $smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Mittente
            $mail->setFrom($from_email, $from_name);
            
            // Destinatario
            $mail->addAddress($to);
            
            // Contenuto
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            
            // Invia
            return $mail->send();
            
        } catch (Exception $e) {
            error_log('Loyalty Email Error: ' . $e->getMessage());
            
            // Fallback a wp_mail
            return self::send_wp_mail($to, $subject, $body);
        }
    }
    
    /**
     * Invia via wp_mail (fallback)
     */
    private static function send_wp_mail($to, $subject, $body) {
        $from_email = get_option('loyalty_smtp_from_email', get_option('admin_email'));
        $from_name = get_option('loyalty_smtp_from_name', get_bloginfo('name'));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );
        
        return wp_mail($to, $subject, $body, $headers);
    }
    
    /**
     * Sostituisce variabili nel template
     */
    public static function replace_variables($template, $data) {
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
            '{site_url}' => get_site_url(),
            '{current_date}' => date_i18n('d/m/Y'),
            '{current_time}' => date_i18n('H:i'),
        );
        
        return str_replace(array_keys($variables), array_values($variables), $template);
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
        
        // Se start > end (es: 23-8), blocca se ora è >= 23 O < 8
        if ($start_hour > $end_hour) {
            return !($current_hour >= $start_hour || $current_hour < $end_hour);
        }
        
        // Altrimenti blocca se ora è tra start e end
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
     * Test connessione SMTP
     */
    public static function test_smtp_connection() {
        try {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $smtp_host = get_option('loyalty_smtp_host', '');
            $smtp_port = get_option('loyalty_smtp_port', 587);
            $smtp_user = get_option('loyalty_smtp_user', '');
            $smtp_pass = get_option('loyalty_smtp_pass', '');
            $smtp_secure = get_option('loyalty_smtp_secure', 'tls');
            
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            $mail->SMTPSecure = $smtp_secure;
            $mail->Port = $smtp_port;
            
            // Timeout breve per test
            $mail->Timeout = 10;
            
            // Prova connessione
            $mail->smtpConnect();
            $mail->smtpClose();
            
            return array('success' => true, 'message' => 'Connessione SMTP riuscita!');
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    /**
     * Invia email di test
     */
    public static function send_test_email($to, $template_key = 'points_added') {
        $subject_key = 'loyalty_email_' . $template_key . '_subject';
        $body_key = 'loyalty_email_' . $template_key . '_body';
        
        $subject = get_option($subject_key, 'Test Email');
        $body = get_option($body_key, '<p>Test email</p>');
        
        // Dati di test
        $test_data = array(
            'user_name' => 'Mario Rossi',
            'user_email' => $to,
            'user_phone' => '393331234567',
            'points' => 50,
            'new_balance' => 150,
            'amount' => 50.00,
            'reward_name' => 'Sconto 10€',
            'redemption_code' => 'TEST1234',
            'note' => 'Test acquisto',
        );
        
        $subject = self::replace_variables($subject, $test_data);
        $body = self::replace_variables($body, $test_data);
        
        return self::send($to, $subject, $body, 'test');
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
            "SELECT COUNT(*) FROM $table WHERE type = 'email' AND sent_at >= %s",
            $date_from
        ));
        
        $sent = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE type = 'email' AND status = 'sent' AND sent_at >= %s",
            $date_from
        ));
        
        $failed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE type = 'email' AND status = 'failed' AND sent_at >= %s",
            $date_from
        ));
        
        $blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE type = 'email' AND status = 'blocked' AND sent_at >= %s",
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
