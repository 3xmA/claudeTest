<?php
/**
 * Classe per generazione QR Code
 */
class Loyalty_QRCode {
    
    /**
     * Genera QR code per utente
     * Usa API QR Code Generator (più affidabile)
     */
    public static function generate_user_qr($user_id, $size = 300) {
        $data = self::encode_user_data($user_id);
        // Usa api.qrserver.com - più affidabile e senza rate limit
        $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
        return $url;
    }
    
    /**
     * Genera QR code per premio riscattato
     */
    public static function generate_reward_qr($redemption_code, $size = 300) {
        $data = self::encode_reward_data($redemption_code);
        $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
        return $url;
    }
    
    /**
     * Codifica dati utente per QR
     */
    private static function encode_user_data($user_id) {
        // Formato: LOYALTY-USER-{user_id}-{hash}
        // L'hash serve per evitare QR code fasulli
        $secret = get_option('loyalty_qr_secret', wp_generate_password(32, false));
        $hash = substr(md5($user_id . $secret), 0, 8);
        
        return "LOYALTY-USER-{$user_id}-{$hash}";
    }
    
    /**
     * Codifica dati premio per QR
     */
    private static function encode_reward_data($redemption_code) {
        // Formato: LOYALTY-REWARD-{code}
        return "LOYALTY-REWARD-{$redemption_code}";
    }
    
    /**
     * Decodifica QR code utente
     */
    public static function decode_user_qr($qr_data) {
        // Verifica formato
        if (!preg_match('/^LOYALTY-USER-(\d+)-([a-f0-9]{8})$/', $qr_data, $matches)) {
            return false;
        }
        
        $user_id = intval($matches[1]);
        $hash = $matches[2];
        
        // Verifica hash
        $secret = get_option('loyalty_qr_secret', '');
        $expected_hash = substr(md5($user_id . $secret), 0, 8);
        
        if ($hash !== $expected_hash) {
            return false;
        }
        
        return $user_id;
    }
    
    /**
     * Decodifica QR code premio
     */
    public static function decode_reward_qr($qr_data) {
        // Verifica formato
        if (!preg_match('/^LOYALTY-REWARD-([A-Z0-9]{8})$/', $qr_data, $matches)) {
            return false;
        }
        
        return $matches[1];
    }
    
    /**
     * Valida QR code generico
     */
    public static function validate_qr($qr_data) {
        if (strpos($qr_data, 'LOYALTY-USER-') === 0) {
            return array(
                'type' => 'user',
                'data' => self::decode_user_qr($qr_data)
            );
        } elseif (strpos($qr_data, 'LOYALTY-REWARD-') === 0) {
            return array(
                'type' => 'reward',
                'data' => self::decode_reward_qr($qr_data)
            );
        }
        
        return false;
    }
    
    /**
     * Genera QR code come immagine base64 (alternativa a Google Charts)
     * Richiede libreria esterna come phpqrcode
     */
    public static function generate_qr_base64($data, $size = 10) {
        // Verifica se phpqrcode è disponibile
        if (!class_exists('QRcode')) {
            // Fallback a Google Charts
            return self::generate_user_qr(0, 300);
        }
        
        ob_start();
        QRcode::png($data, null, QR_ECLEVEL_L, $size, 2);
        $imageString = base64_encode(ob_get_contents());
        ob_end_clean();
        
        return 'data:image/png;base64,' . $imageString;
    }
}
