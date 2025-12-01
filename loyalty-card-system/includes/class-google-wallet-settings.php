<?php
/**
 * Pagina Admin Settings per Google Wallet
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Google_Wallet_Settings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Aggiungi pagina al menu
        add_action('admin_menu', array($this, 'add_settings_page'));

        // Registra impostazioni
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Aggiungi pagina settings
     */
    public function add_settings_page() {
        add_submenu_page(
            'loyalty-card',
            'Google Wallet',
            '💳 Google Wallet',
            'manage_options',
            'loyalty-google-wallet',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Registra impostazioni
     */
    public function register_settings() {
        register_setting('loyalty_gw_settings', 'loyalty_gw_issuer_id');
        register_setting('loyalty_gw_settings', 'loyalty_gw_class_id');
        register_setting('loyalty_gw_settings', 'loyalty_gw_service_account');
        register_setting('loyalty_gw_settings', 'loyalty_gw_logo_url');
    }

    /**
     * Render pagina settings
     */
    public function render_settings_page() {
        // Salva impostazioni
        if (isset($_POST['loyalty_gw_save_settings'])) {
            check_admin_referer('loyalty_gw_settings');

            update_option('loyalty_gw_issuer_id', sanitize_text_field($_POST['issuer_id']));
            update_option('loyalty_gw_class_id', sanitize_text_field($_POST['class_id']));
            update_option('loyalty_gw_service_account', wp_unslash($_POST['service_account']));
            update_option('loyalty_gw_logo_url', esc_url_raw($_POST['logo_url']));

            echo '<div class="notice notice-success"><p>✅ Impostazioni salvate!</p></div>';
        }

        // Crea classe Generic
        if (isset($_POST['loyalty_gw_create_class'])) {
            check_admin_referer('loyalty_gw_create_class');

            try {
                $wallet = Loyalty_Google_Wallet::get_instance();
                $result = $wallet->create_generic_class();

                echo '<div class="notice notice-success"><p>✅ Classe Generic creata con successo! ID: ' . esc_html($result['id']) . '</p></div>';
            } catch (Exception $e) {
                echo '<div class="notice notice-error"><p>❌ Errore: ' . esc_html($e->getMessage()) . '</p></div>';
            }
        }

        // Recupera valori
        $issuer_id = get_option('loyalty_gw_issuer_id', '');
        $class_id = get_option('loyalty_gw_class_id', 'loyalty_card_class');
        $service_account = get_option('loyalty_gw_service_account', '');
        $logo_url = get_option('loyalty_gw_logo_url', site_url('/wp-content/plugins/loyalty-card-system/assets/images/logo.png'));

        $is_configured = !empty($issuer_id) && !empty($service_account);

        ?>
        <div class="wrap">
            <h1>💳 Integrazione Google Wallet</h1>
            <p>Configura l'integrazione con Google Wallet per permettere ai clienti di salvare la carta fedeltà sul loro telefono.</p>

            <?php if ($is_configured) : ?>
                <div class="notice notice-success" style="margin: 20px 0;">
                    <p><strong>✅ Google Wallet configurato correttamente!</strong></p>
                    <p>Gli utenti vedranno il pulsante "Aggiungi a Google Wallet" nella loro dashboard.</p>
                </div>
            <?php else : ?>
                <div class="notice notice-warning" style="margin: 20px 0;">
                    <p><strong>⚠️ Configurazione incompleta</strong></p>
                    <p>Compila tutti i campi richiesti per abilitare Google Wallet.</p>
                </div>
            <?php endif; ?>

            <!-- Configurazione -->
            <form method="post" action="">
                <?php wp_nonce_field('loyalty_gw_settings'); ?>

                <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2>🔧 Configurazione Google Cloud</h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="issuer_id">Issuer ID <span style="color: red;">*</span></label></th>
                            <td>
                                <input type="text" name="issuer_id" id="issuer_id" value="<?php echo esc_attr($issuer_id); ?>" class="regular-text" required>
                                <p class="description">
                                    Il tuo Issuer ID da Google Cloud Console.<br>
                                    Formato: <code>3388000000012345678</code><br>
                                    <a href="https://pay.google.com/business/console" target="_blank">Vai a Google Pay & Wallet Console →</a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="class_id">Class ID</label></th>
                            <td>
                                <input type="text" name="class_id" id="class_id" value="<?php echo esc_attr($class_id); ?>" class="regular-text">
                                <p class="description">
                                    ID della classe Generic Pass. Default: <code>loyalty_card_class</code><br>
                                    Puoi usare questo valore di default.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="service_account">Service Account JSON <span style="color: red;">*</span></label></th>
                            <td>
                                <textarea name="service_account" id="service_account" rows="10" class="large-text code" required><?php echo esc_textarea($service_account); ?></textarea>
                                <p class="description">
                                    Incolla qui il contenuto del file JSON del Service Account.<br>
                                    <strong>Come ottenerlo:</strong><br>
                                    1. Vai su <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank">Google Cloud Console → IAM & Admin → Service Accounts</a><br>
                                    2. Crea un nuovo Service Account (o usa uno esistente)<br>
                                    3. Aggiungi il ruolo <code>Google Wallet API Issuer</code><br>
                                    4. Crea una chiave JSON e scaricala<br>
                                    5. Apri il file JSON e incolla tutto il contenuto qui sopra
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="logo_url">Logo URL</label></th>
                            <td>
                                <input type="url" name="logo_url" id="logo_url" value="<?php echo esc_attr($logo_url); ?>" class="regular-text">
                                <p class="description">
                                    URL pubblico del logo del negozio (apparirà sulla carta).<br>
                                    Dimensioni consigliate: 660x660px, formato PNG o JPG.
                                </p>
                                <?php if (!empty($logo_url)) : ?>
                                    <br><img src="<?php echo esc_url($logo_url); ?>" alt="Logo preview" style="max-width: 100px; border: 1px solid #ddd; padding: 5px; margin-top: 10px;">
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button('💾 Salva Configurazione', 'primary', 'loyalty_gw_save_settings'); ?>
                </div>
            </form>

            <!-- Creazione Classe Generic -->
            <?php if ($is_configured) : ?>
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2>🎯 Inizializzazione</h2>
                <p>Dopo aver salvato la configurazione, devi creare la classe Generic Pass su Google Wallet.</p>
                <p><strong>Nota:</strong> Questa operazione va fatta <u>una volta sola</u>. Se la classe esiste già, riceverai un errore (è normale).</p>

                <form method="post" action="">
                    <?php wp_nonce_field('loyalty_gw_create_class'); ?>
                    <button type="submit" name="loyalty_gw_create_class" class="button button-secondary">
                        ⚡ Crea Classe Generic Pass
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Guida Setup -->
            <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border-left: 4px solid #2196F3; border-radius: 5px;">
                <h3 style="margin-top: 0;">📚 Guida Setup Completa</h3>

                <h4>1️⃣ Abilita Google Wallet API</h4>
                <ol>
                    <li>Vai su <a href="https://console.cloud.google.com/apis/library/walletobjects.googleapis.com" target="_blank">Google Cloud Console → APIs & Services</a></li>
                    <li>Cerca "Google Wallet API"</li>
                    <li>Clicca "Enable"</li>
                </ol>

                <h4>2️⃣ Registra come Google Pay API Issuer</h4>
                <ol>
                    <li>Vai su <a href="https://pay.google.com/business/console" target="_blank">Google Pay & Wallet Console</a></li>
                    <li>Clicca "Get Started" e completa la registrazione</li>
                    <li>Ottieni il tuo Issuer ID (lo trovi nella dashboard)</li>
                </ol>

                <h4>3️⃣ Crea Service Account</h4>
                <ol>
                    <li>Vai su <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank">IAM & Admin → Service Accounts</a></li>
                    <li>Clicca "Create Service Account"</li>
                    <li>Nome: <code>loyalty-wallet-service</code></li>
                    <li>Ruolo: <code>Google Wallet API Issuer</code></li>
                    <li>Crea chiave JSON e scaricala</li>
                </ol>

                <h4>4️⃣ Configura questo plugin</h4>
                <ol>
                    <li>Incolla l'Issuer ID nel campo sopra</li>
                    <li>Apri il file JSON del Service Account e incolla tutto il contenuto</li>
                    <li>Aggiungi URL del logo (opzionale)</li>
                    <li>Clicca "Salva Configurazione"</li>
                    <li>Clicca "Crea Classe Generic Pass"</li>
                </ol>

                <h4>✅ Fatto!</h4>
                <p>Gli utenti vedranno il pulsante "Aggiungi a Google Wallet" nella loro dashboard loyalty.</p>
            </div>

            <!-- Test -->
            <?php if ($is_configured) : ?>
            <div style="background: #f0f0f1; padding: 20px; margin: 20px 0; border-radius: 8px;">
                <h3 style="margin-top: 0;">🧪 Test Configurazione</h3>
                <p>Per testare l'integrazione:</p>
                <ol>
                    <li>Vai alla dashboard loyalty come utente loggato</li>
                    <li>Dovresti vedere il pulsante "Aggiungi a Google Wallet"</li>
                    <li>Cliccalo per generare il pass</li>
                    <li>Verifica che si apra la pagina di Google Wallet</li>
                </ol>
                <p>
                    <a href="<?php echo site_url(); ?>" class="button">Vai al Sito →</a>
                </p>
            </div>
            <?php endif; ?>

        </div>
        <?php
    }
}
