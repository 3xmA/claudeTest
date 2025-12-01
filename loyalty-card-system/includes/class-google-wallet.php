<?php
/**
 * Google Wallet Integration
 * Genera Generic Pass per Google Wallet con carta fedeltà
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Google_Wallet {

    private static $instance = null;

    // Google Wallet API endpoint
    const GOOGLE_WALLET_API = 'https://walletobjects.googleapis.com/walletobjects/v1';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Aggiungi hook per il pulsante nella dashboard
        add_action('loyalty_card_actions', array($this, 'render_wallet_button'));

        // Endpoint API per generare JWT
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
    }

    /**
     * Registra endpoint API
     */
    public function register_api_endpoints() {
        register_rest_route('loyalty/v1', '/google-wallet/pass', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_generate_pass'),
            'permission_callback' => '__return_true' // Verifica dentro il metodo
        ));
    }

    /**
     * Verifica se Google Wallet è configurato
     */
    public function is_configured() {
        $issuer_id = get_option('loyalty_gw_issuer_id', '');
        $service_account = get_option('loyalty_gw_service_account', '');

        return !empty($issuer_id) && !empty($service_account);
    }

    /**
     * Render pulsante "Aggiungi a Google Wallet"
     */
    public function render_wallet_button() {
        if (!$this->is_configured()) {
            return; // Non mostrare se non configurato
        }

        $user_id = get_current_user_id();

        ?>
        <div class="loyalty-google-wallet-button">
            <button id="addToGoogleWallet" class="btn-add-google-wallet">
                <img src="<?php echo plugins_url('assets/images/google-wallet-logo.svg', dirname(__FILE__)); ?>" alt="Google Wallet" class="wallet-logo">
                Aggiungi a Google Wallet
            </button>
        </div>

        <script>
        document.getElementById('addToGoogleWallet').addEventListener('click', async function() {
            const button = this;
            button.disabled = true;
            button.textContent = 'Generazione...';

            try {
                const response = await fetch('<?php echo rest_url('loyalty/v1/google-wallet/pass'); ?>', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                    }
                });

                const data = await response.json();

                // Gestisci successo
                if (data.success && data.url) {
                    window.open(data.url, '_blank');
                }
                // Gestisci WP_Error (formato: {code, message, data})
                else if (data.code && data.message) {
                    alert('Errore: ' + data.message);
                    console.error('WP Error:', data);
                }
                // Gestisci altri errori
                else {
                    alert('Errore nella generazione del pass: ' + (data.message || 'Errore sconosciuto'));
                    console.error('Error data:', data);
                }
            } catch (error) {
                console.error('Errore:', error);
                alert('Errore di connessione. Riprova.');
            } finally {
                button.disabled = false;
                button.innerHTML = '<img src="<?php echo plugins_url('assets/images/google-wallet-logo.svg', dirname(__FILE__)); ?>" alt="Google Wallet" class="wallet-logo">Aggiungi a Google Wallet';
            }
        });
        </script>
        <?php
    }

    /**
     * API endpoint per generare pass
     */
    public function api_generate_pass($request) {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_Error('unauthorized', 'Utente non autenticato', array('status' => 401));
        }

        // Nota: saltiamo il check class_exists() perché può dare falsi negativi con 403
        // La classe è stata creata (confermato da errore 409)

        try {
            $jwt = $this->generate_jwt($user_id);
            $save_url = "https://pay.google.com/gp/v/save/{$jwt}";

            // Log per debug
            error_log('Google Wallet JWT generato per user_id: ' . $user_id);
            error_log('JWT length: ' . strlen($jwt));

            return array(
                'success' => true,
                'url' => $save_url,
                'message' => 'Pass generato con successo',
                'debug' => array(
                    'user_id' => $user_id,
                    'jwt_length' => strlen($jwt)
                )
            );
        } catch (Exception $e) {
            error_log('Google Wallet Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return new WP_Error('generation_failed', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Genera JWT per Google Wallet Generic Pass
     */
    private function generate_jwt($user_id) {
        $user = get_user_by('id', $user_id);
        $stats = Loyalty_Points::get_user_stats($user_id);

        $issuer_id = get_option('loyalty_gw_issuer_id');
        $class_id = get_option('loyalty_gw_class_id', 'loyalty_card_class');
        $object_id = "{$issuer_id}.{$user_id}_" . time();

        // Genera QR code (stesso formato dello scanner)
        $qr_hash = substr(md5($user_id . AUTH_KEY), 0, 8);
        $qr_code = "LOYALTY-USER-{$user_id}-{$qr_hash}";

        // Logo URL (opzionale)
        $logo_url = get_option('loyalty_gw_logo_url', '');
        $logo_config = !empty($logo_url) ? array(
            'logo' => array(
                'sourceUri' => array(
                    'uri' => $logo_url
                )
            )
        ) : array();

        // Oggetto Generic Pass
        $generic_object = array_merge(array(
            'id' => $object_id,
            'classId' => "{$issuer_id}.{$class_id}",
            'genericType' => 'GENERIC_TYPE_UNSPECIFIED',
            'hexBackgroundColor' => '#4f46e5',
            'cardTitle' => array(
                'defaultValue' => array(
                    'language' => 'it',
                    'value' => 'Carta Fedeltà'
                )
            ),
            'subheader' => array(
                'defaultValue' => array(
                    'language' => 'it',
                    'value' => 'PUNTI DISPONIBILI'
                )
            ),
            'header' => array(
                'defaultValue' => array(
                    'language' => 'it',
                    'value' => (string)$stats['balance'] // Converti a stringa
                )
            ),
            'barcode' => array(
                'type' => 'QR_CODE',
                'value' => $qr_code,
                'alternateText' => $qr_code
            ),
            'textModulesData' => array(
                array(
                    'id' => 'user_name',
                    'header' => 'Titolare',
                    'body' => $user->display_name
                ),
                array(
                    'id' => 'total_earned',
                    'header' => 'Totale Guadagnati',
                    'body' => $stats['total_earned'] . ' punti'
                ),
                array(
                    'id' => 'rewards_count',
                    'header' => 'Premi Riscattati',
                    'body' => (string)$stats['rewards_count']
                )
            )
        ), $logo_config);

        // Payload JWT
        $payload = array(
            'iss' => $this->get_service_account_email(),
            'aud' => 'google',
            'origins' => array(site_url()),
            'typ' => 'savetowallet',
            'iat' => time(),
            'payload' => array(
                'genericObjects' => array($generic_object)
            )
        );

        // Log per debug
        error_log('Google Wallet Generic Object: ' . json_encode($generic_object, JSON_PRETTY_PRINT));
        error_log('Google Wallet Payload: ' . json_encode($payload, JSON_PRETTY_PRINT));

        // Firma JWT
        return $this->sign_jwt($payload);
    }

    /**
     * Firma JWT con Service Account
     */
    private function sign_jwt($payload) {
        $service_account_json = get_option('loyalty_gw_service_account');

        if (empty($service_account_json)) {
            throw new Exception('Service Account non configurato');
        }

        $service_account = json_decode($service_account_json, true);

        if (!isset($service_account['private_key'])) {
            throw new Exception('Private key non trovata nel Service Account');
        }

        // Header JWT
        $header = array(
            'alg' => 'RS256',
            'typ' => 'JWT'
        );

        // Encode
        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));
        $signature_input = "{$header_encoded}.{$payload_encoded}";

        // Firma con RSA-SHA256
        $private_key = openssl_pkey_get_private($service_account['private_key']);

        if (!$private_key) {
            throw new Exception('Impossibile caricare private key: ' . openssl_error_string());
        }

        openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256);
        openssl_free_key($private_key);

        $signature_encoded = $this->base64url_encode($signature);

        return "{$signature_input}.{$signature_encoded}";
    }

    /**
     * Base64 URL-safe encoding
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Ottieni email Service Account
     */
    private function get_service_account_email() {
        $service_account_json = get_option('loyalty_gw_service_account');

        if (empty($service_account_json)) {
            throw new Exception('Service Account non configurato');
        }

        $service_account = json_decode($service_account_json, true);

        if (!isset($service_account['client_email'])) {
            throw new Exception('Client email non trovata');
        }

        return $service_account['client_email'];
    }

    /**
     * Verifica se la classe Generic esiste
     */
    public function class_exists() {
        $issuer_id = get_option('loyalty_gw_issuer_id');
        $class_id = get_option('loyalty_gw_class_id', 'loyalty_card_class');
        $full_class_id = "{$issuer_id}.{$class_id}";

        try {
            $access_token = $this->get_access_token();

            $response = wp_remote_get(self::GOOGLE_WALLET_API . "/genericClass/{$full_class_id}", array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 30
            ));

            $status_code = wp_remote_retrieve_response_code($response);

            // 200 = esiste, 404 = non esiste
            return $status_code === 200;
        } catch (Exception $e) {
            error_log('Errore verifica classe: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea Generic Class (chiamare una volta sola)
     */
    public function create_generic_class() {
        $issuer_id = get_option('loyalty_gw_issuer_id');
        $class_id = get_option('loyalty_gw_class_id', 'loyalty_card_class');

        $generic_class = array(
            'id' => "{$issuer_id}.{$class_id}",
            'classTemplateInfo' => array(
                'cardTemplateOverride' => array(
                    'cardRowTemplateInfos' => array(
                        array(
                            'twoItems' => array(
                                'startItem' => array(
                                    'firstValue' => array(
                                        'fields' => array(
                                            array(
                                                'fieldPath' => "object.textModulesData['user_name']"
                                            )
                                        )
                                    )
                                ),
                                'endItem' => array(
                                    'firstValue' => array(
                                        'fields' => array(
                                            array(
                                                'fieldPath' => "object.textModulesData['total_earned']"
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );

        // Log classe da creare
        error_log('Google Wallet: Creazione classe Generic');
        error_log('Classe JSON: ' . json_encode($generic_class, JSON_PRETTY_PRINT));

        // Chiamata API per creare la classe
        $access_token = $this->get_access_token();

        $response = wp_remote_post(self::GOOGLE_WALLET_API . '/genericClass', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($generic_class),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            $error_msg = 'Errore chiamata API: ' . $response->get_error_message();
            error_log('Google Wallet Error: ' . $error_msg);
            throw new Exception($error_msg);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log('Google Wallet: Response status ' . $status_code);
        error_log('Google Wallet: Response body ' . $body);

        if ($status_code !== 200 && $status_code !== 201) {
            throw new Exception('Errore creazione classe: ' . $body);
        }

        error_log('Google Wallet: Classe creata con successo!');
        return json_decode($body, true);
    }

    /**
     * Ottieni Access Token con Service Account
     */
    private function get_access_token() {
        // Controlla cache
        $cached_token = get_transient('loyalty_gw_access_token');
        if ($cached_token) {
            return $cached_token;
        }

        $service_account_json = get_option('loyalty_gw_service_account');
        $service_account = json_decode($service_account_json, true);

        // Crea JWT per OAuth
        $jwt_payload = array(
            'iss' => $service_account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/wallet_object.issuer',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => time(),
            'exp' => time() + 3600
        );

        $jwt = $this->sign_jwt($jwt_payload);

        // Richiedi access token
        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            )
        ));

        if (is_wp_error($response)) {
            throw new Exception('Errore OAuth: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['access_token'])) {
            throw new Exception('Access token non ricevuto');
        }

        // Cache per 50 minuti (scade dopo 60)
        set_transient('loyalty_gw_access_token', $body['access_token'], 3000);

        return $body['access_token'];
    }
}
