<?php
/**
 * Pagina Admin: Notifiche
 */

if (!defined('ABSPATH')) {
    exit;
}

// Salva configurazioni
if (isset($_POST['loyalty_save_notifications'])) {
    
    // Verifica nonce con nome più specifico
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'loyalty_notifications')) {
        echo '<div class="notice notice-error"><p>Errore di sicurezza. Ricarica la pagina e riprova.</p></div>';
    } else {
        
        // Tab Configurazione
        if (isset($_POST['tab']) && $_POST['tab'] === 'config') {
        // SMTP
        update_option('loyalty_smtp_enabled', isset($_POST['smtp_enabled']));
        update_option('loyalty_smtp_host', sanitize_text_field($_POST['smtp_host']));
        update_option('loyalty_smtp_port', intval($_POST['smtp_port']));
        update_option('loyalty_smtp_user', sanitize_text_field($_POST['smtp_user']));
        if (!empty($_POST['smtp_pass'])) {
            update_option('loyalty_smtp_pass', sanitize_text_field($_POST['smtp_pass']));
        }
        update_option('loyalty_smtp_secure', sanitize_text_field($_POST['smtp_secure']));
        update_option('loyalty_smtp_from_email', sanitize_email($_POST['smtp_from_email']));
        update_option('loyalty_smtp_from_name', sanitize_text_field($_POST['smtp_from_name']));
        
        // Evolution API
        update_option('loyalty_evolution_url', esc_url_raw($_POST['evolution_url']));
        update_option('loyalty_evolution_instance', sanitize_text_field($_POST['evolution_instance']));
        update_option('loyalty_evolution_apikey', sanitize_text_field($_POST['evolution_apikey']));
        
        // Orari
        update_option('loyalty_block_night_notifications', isset($_POST['block_night']));
        update_option('loyalty_night_start_hour', intval($_POST['night_start_hour']));
        update_option('loyalty_night_end_hour', intval($_POST['night_end_hour']));
        
        echo '<div class="notice notice-success"><p>Configurazione salvata!</p></div>';
    }
    
    // Tab Template Email
    if (isset($_POST['tab']) && $_POST['tab'] === 'email') {
        $events = array('points_added', 'reward_redeemed', 'threshold_reached');
        
        foreach ($events as $event) {
            $enabled_key = 'loyalty_email_' . $event . '_enabled';
            $subject_key = 'loyalty_email_' . $event . '_subject';
            $body_key = 'loyalty_email_' . $event . '_body';
            
            update_option($enabled_key, isset($_POST[$enabled_key]));
            update_option($subject_key, sanitize_text_field($_POST[$subject_key]));
            update_option($body_key, wp_kses_post($_POST[$body_key]));
        }
        
        echo '<div class="notice notice-success"><p>Template email salvati!</p></div>';
    }
    
    // Tab Template WhatsApp
    if (isset($_POST['tab']) && $_POST['tab'] === 'whatsapp') {
        $events = array('points_added', 'reward_redeemed', 'threshold_reached');
        
        foreach ($events as $event) {
            $enabled_key = 'loyalty_whatsapp_' . $event . '_enabled';
            $text_key = 'loyalty_whatsapp_' . $event . '_text';
            
            update_option($enabled_key, isset($_POST[$enabled_key]));
            update_option($text_key, sanitize_textarea_field($_POST[$text_key]));
        }
        
        echo '<div class="notice notice-success"><p>Template WhatsApp salvati!</p></div>';
    }
    
    } // Fine check nonce
}

// Test SMTP
if (isset($_POST['test_smtp'])) {
    if (!isset($_POST['_wpnonce_test_smtp']) || !wp_verify_nonce($_POST['_wpnonce_test_smtp'], 'loyalty_test_smtp')) {
        echo '<div class="notice notice-error"><p>Errore di sicurezza test SMTP.</p></div>';
    } else {
        // Prima salva i dati SMTP se presenti
        if (isset($_POST['smtp_enabled'])) {
            update_option('loyalty_smtp_enabled', true);
        } else {
            update_option('loyalty_smtp_enabled', false);
        }
        if (isset($_POST['smtp_host'])) {
            update_option('loyalty_smtp_host', sanitize_text_field($_POST['smtp_host']));
        }
        if (isset($_POST['smtp_port'])) {
            update_option('loyalty_smtp_port', intval($_POST['smtp_port']));
        }
        if (isset($_POST['smtp_user'])) {
            update_option('loyalty_smtp_user', sanitize_text_field($_POST['smtp_user']));
        }
        if (isset($_POST['smtp_pass']) && !empty($_POST['smtp_pass'])) {
            update_option('loyalty_smtp_pass', sanitize_text_field($_POST['smtp_pass']));
        }
        if (isset($_POST['smtp_secure'])) {
            update_option('loyalty_smtp_secure', sanitize_text_field($_POST['smtp_secure']));
        }
        
        $result = Loyalty_Email_Sender::test_smtp_connection();
        $class = $result['success'] ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . $class . '"><p>' . esc_html($result['message']) . '</p></div>';
    }
}

// Test Evolution API
if (isset($_POST['test_evolution'])) {
    if (!isset($_POST['_wpnonce_test_evolution']) || !wp_verify_nonce($_POST['_wpnonce_test_evolution'], 'loyalty_test_evolution')) {
        echo '<div class="notice notice-error"><p>Errore di sicurezza test Evolution.</p></div>';
    } else {
        // Prima salva i dati se presenti nel POST
        if (isset($_POST['evolution_url'])) {
            update_option('loyalty_evolution_url', esc_url_raw($_POST['evolution_url']));
        }
        if (isset($_POST['evolution_instance'])) {
            update_option('loyalty_evolution_instance', sanitize_text_field($_POST['evolution_instance']));
        }
        if (isset($_POST['evolution_apikey'])) {
            update_option('loyalty_evolution_apikey', sanitize_text_field($_POST['evolution_apikey']));
        }
        
        $result = Loyalty_WhatsApp_Sender::test_connection();
        $class = $result['success'] ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . $class . '"><p>' . esc_html($result['message']) . '</p></div>';
    }
}

// Test Email
if (isset($_POST['test_email_send'])) {
    if (!isset($_POST['_wpnonce_test_email']) || !wp_verify_nonce($_POST['_wpnonce_test_email'], 'loyalty_test_email')) {
        echo '<div class="notice notice-error"><p>Errore di sicurezza test email.</p></div>';
    } else {
        $to = sanitize_email($_POST['test_email_to']);
        $template = sanitize_text_field($_POST['test_email_template']);
        
        $result = Loyalty_Email_Sender::send_test_email($to, $template);
        $class = $result ? 'notice-success' : 'notice-error';
        $message = $result ? 'Email di test inviata!' : 'Errore invio email.';
        echo '<div class="notice ' . $class . '"><p>' . esc_html($message) . '</p></div>';
    }
}

// Test WhatsApp
if (isset($_POST['test_whatsapp_send'])) {
    if (!isset($_POST['_wpnonce_test_whatsapp']) || !wp_verify_nonce($_POST['_wpnonce_test_whatsapp'], 'loyalty_test_whatsapp')) {
        echo '<div class="notice notice-error"><p>Errore di sicurezza test WhatsApp.</p></div>';
    } else {
        $phone = sanitize_text_field($_POST['test_whatsapp_phone']);
        $template = sanitize_text_field($_POST['test_whatsapp_template']);
        
        $result = Loyalty_WhatsApp_Sender::send_test_message($phone, $template);
        $class = $result ? 'notice-success' : 'notice-error';
        $message = $result ? 'Messaggio WhatsApp di test inviato!' : 'Errore invio WhatsApp.';
        echo '<div class="notice ' . $class . '"><p>' . esc_html($message) . '</p></div>';
    }
}

// Tab attivo
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'config';

// Statistiche
$email_stats = Loyalty_Email_Sender::get_stats(30);
$whatsapp_stats = Loyalty_WhatsApp_Sender::get_stats(30);
?>

<div class="wrap">
    <h1>📧 Gestione Notifiche</h1>
    
    <!-- Tabs -->
    <h2 class="nav-tab-wrapper">
        <a href="?page=loyalty-notifications&tab=config" class="nav-tab <?php echo $active_tab === 'config' ? 'nav-tab-active' : ''; ?>">
            ⚙️ Configurazione
        </a>
        <a href="?page=loyalty-notifications&tab=email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">
            📧 Template Email
        </a>
        <a href="?page=loyalty-notifications&tab=whatsapp" class="nav-tab <?php echo $active_tab === 'whatsapp' ? 'nav-tab-active' : ''; ?>">
            📱 Template WhatsApp
        </a>
        <a href="?page=loyalty-notifications&tab=test" class="nav-tab <?php echo $active_tab === 'test' ? 'nav-tab-active' : ''; ?>">
            🧪 Test
        </a>
        <a href="?page=loyalty-notifications&tab=stats" class="nav-tab <?php echo $active_tab === 'stats' ? 'nav-tab-active' : ''; ?>">
            📊 Statistiche
        </a>
    </h2>
    
    <!-- Tab: Configurazione -->
    <?php if ($active_tab === 'config') : ?>
    <form method="post" action="">
        <?php wp_nonce_field('loyalty_notifications'); ?>
        <input type="hidden" name="tab" value="config">
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>📧 Configurazione SMTP</h2>
            
            <table class="form-table">
                <tr>
                    <th><label>Abilita SMTP</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="smtp_enabled" value="1" <?php checked(get_option('loyalty_smtp_enabled'), true); ?>>
                            Usa SMTP custom (altrimenti usa wp_mail)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_host">Server SMTP</label></th>
                    <td>
                        <input type="text" name="smtp_host" id="smtp_host" value="<?php echo esc_attr(get_option('loyalty_smtp_host', '')); ?>" class="regular-text" placeholder="smtp.gmail.com">
                        <p class="description">Esempio: smtp.gmail.com, smtp.office365.com</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_port">Porta SMTP</label></th>
                    <td>
                        <input type="number" name="smtp_port" id="smtp_port" value="<?php echo esc_attr(get_option('loyalty_smtp_port', 587)); ?>" class="small-text">
                        <p class="description">Solitamente 587 (TLS) o 465 (SSL)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_secure">Sicurezza</label></th>
                    <td>
                        <select name="smtp_secure" id="smtp_secure">
                            <option value="tls" <?php selected(get_option('loyalty_smtp_secure', 'tls'), 'tls'); ?>>TLS</option>
                            <option value="ssl" <?php selected(get_option('loyalty_smtp_secure'), 'ssl'); ?>>SSL</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_user">Username SMTP</label></th>
                    <td>
                        <input type="text" name="smtp_user" id="smtp_user" value="<?php echo esc_attr(get_option('loyalty_smtp_user', '')); ?>" class="regular-text" placeholder="tuo@email.com">
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_pass">Password SMTP</label></th>
                    <td>
                        <input type="password" name="smtp_pass" id="smtp_pass" value="" class="regular-text" placeholder="Lascia vuoto per non modificare">
                        <p class="description">Per Gmail usa una <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_from_email">Email Mittente</label></th>
                    <td>
                        <input type="email" name="smtp_from_email" id="smtp_from_email" value="<?php echo esc_attr(get_option('loyalty_smtp_from_email', get_option('admin_email'))); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="smtp_from_name">Nome Mittente</label></th>
                    <td>
                        <input type="text" name="smtp_from_name" id="smtp_from_name" value="<?php echo esc_attr(get_option('loyalty_smtp_from_name', get_bloginfo('name'))); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="submit" name="test_smtp" class="button" onclick="return confirm('Testare la connessione SMTP?')">
                    🔌 Test Connessione SMTP
                </button>
                <input type="hidden" name="_wpnonce_test_smtp" value="<?php echo wp_create_nonce('loyalty_test_smtp'); ?>">
            </p>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>📱 Configurazione Evolution API</h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="evolution_url">URL Evolution API</label></th>
                    <td>
                        <input type="url" name="evolution_url" id="evolution_url" value="<?php echo esc_attr(get_option('loyalty_evolution_url', 'http://82.165.174.240:8080')); ?>" class="regular-text">
                        <p class="description">URL base della tua Evolution API</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="evolution_instance">Nome Istanza</label></th>
                    <td>
                        <input type="text" name="evolution_instance" id="evolution_instance" value="<?php echo esc_attr(get_option('loyalty_evolution_instance', 'wappTest')); ?>" class="regular-text">
                        <p class="description">Il nome dell'istanza WhatsApp creata in Evolution</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="evolution_apikey">API Key</label></th>
                    <td>
                        <input type="text" name="evolution_apikey" id="evolution_apikey" value="<?php echo esc_attr(get_option('loyalty_evolution_apikey', 'wezemapi')); ?>" class="regular-text">
                        <p class="description">La tua API key di Evolution</p>
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="submit" name="test_evolution" class="button" onclick="return confirm('Testare la connessione Evolution API?')">
                    🔌 Test Connessione WhatsApp
                </button>
                <input type="hidden" name="_wpnonce_test_evolution" value="<?php echo wp_create_nonce('loyalty_test_evolution'); ?>">
            </p>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>🌙 Orari Notifiche</h2>
            
            <table class="form-table">
                <tr>
                    <th><label>Blocca notturne</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="block_night" value="1" <?php checked(get_option('loyalty_block_night_notifications'), true); ?>>
                            Non inviare notifiche durante la notte
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label>Orario notturno</label></th>
                    <td>
                        Da <select name="night_start_hour">
                            <?php for ($h = 0; $h < 24; $h++) : ?>
                                <option value="<?php echo $h; ?>" <?php selected(get_option('loyalty_night_start_hour', 23), $h); ?>>
                                    <?php echo sprintf('%02d:00', $h); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        
                        a <select name="night_end_hour">
                            <?php for ($h = 0; $h < 24; $h++) : ?>
                                <option value="<?php echo $h; ?>" <?php selected(get_option('loyalty_night_end_hour', 8), $h); ?>>
                                    <?php echo sprintf('%02d:00', $h); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        
                        <p class="description">Le notifiche in questo orario verranno bloccate</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button('💾 Salva Configurazione', 'primary', 'loyalty_save_notifications'); ?>
    </form>
    <?php endif; ?>
    
    <!-- Tab: Template Email -->
    <?php if ($active_tab === 'email') : ?>
        <?php include LOYALTY_PLUGIN_DIR . 'admin/notifications-tab-email.php'; ?>
    <?php endif; ?>
    
    <!-- Tab: Template WhatsApp, Test, Statistiche -->
    <?php include LOYALTY_PLUGIN_DIR . 'admin/notifications-tab-others.php'; ?>
    
</div><!-- .wrap -->
