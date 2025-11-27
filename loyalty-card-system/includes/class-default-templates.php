<?php
/**
 * Template di default per notifiche
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Default_Templates {
    
    /**
     * Installa template di default
     */
    public static function install() {
        // Template Email: Punti Aggiunti
        if (!get_option('loyalty_email_points_added_subject')) {
            update_option('loyalty_email_points_added_enabled', true);
            update_option('loyalty_email_points_added_subject', '🎉 Hai guadagnato {points} punti!');
            update_option('loyalty_email_points_added_body', self::get_email_points_added());
        }
        
        // Template Email: Premio Riscattato
        if (!get_option('loyalty_email_reward_redeemed_subject')) {
            update_option('loyalty_email_reward_redeemed_enabled', true);
            update_option('loyalty_email_reward_redeemed_subject', '🎁 Premio Riscattato con Successo!');
            update_option('loyalty_email_reward_redeemed_body', self::get_email_reward_redeemed());
        }
        
        // Template Email: Soglia Raggiunta
        if (!get_option('loyalty_email_threshold_reached_subject')) {
            update_option('loyalty_email_threshold_reached_enabled', true);
            update_option('loyalty_email_threshold_reached_subject', '🔥 Puoi riscattare un premio!');
            update_option('loyalty_email_threshold_reached_body', self::get_email_threshold_reached());
        }
        
        // Template WhatsApp: Punti Aggiunti
        if (!get_option('loyalty_whatsapp_points_added_text')) {
            update_option('loyalty_whatsapp_points_added_enabled', true);
            update_option('loyalty_whatsapp_points_added_text', self::get_whatsapp_points_added());
        }
        
        // Template WhatsApp: Premio Riscattato
        if (!get_option('loyalty_whatsapp_reward_redeemed_text')) {
            update_option('loyalty_whatsapp_reward_redeemed_enabled', true);
            update_option('loyalty_whatsapp_reward_redeemed_text', self::get_whatsapp_reward_redeemed());
        }
        
        // Template WhatsApp: Soglia Raggiunta
        if (!get_option('loyalty_whatsapp_threshold_reached_text')) {
            update_option('loyalty_whatsapp_threshold_reached_enabled', true);
            update_option('loyalty_whatsapp_threshold_reached_text', self::get_whatsapp_threshold_reached());
        }
    }
    
    /**
     * Template Email: Punti Aggiunti
     */
    private static function get_email_points_added() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; padding: 20px; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #4f46e5; margin: 0; font-size: 28px; }
        .points-badge { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 25px; border-radius: 12px; text-align: center; margin: 25px 0; }
        .points-value { font-size: 56px; font-weight: bold; margin: 15px 0; }
        .info-box { background: #f9fafb; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #4f46e5; }
        .footer { text-align: center; color: #6b7280; font-size: 14px; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Carta Fedeltà</h1>
        </div>
        
        <p style="font-size: 16px;">Ciao <strong>{user_name}</strong>,</p>
        
        <p style="font-size: 16px;">Ottima notizia! Hai appena guadagnato nuovi punti! 🎉</p>
        
        <div class="points-badge">
            <div style="font-size: 18px; opacity: 0.95;">Punti Guadagnati</div>
            <div class="points-value">+{points}</div>
            <div style="font-size: 18px; opacity: 0.95;">per un acquisto di €{amount}</div>
        </div>
        
        <div class="info-box">
            <p style="margin: 5px 0; font-size: 15px;"><strong>📊 Il Tuo Saldo:</strong> {new_balance} punti</p>
            <p style="margin: 5px 0; font-size: 14px; color: #6b7280;">{note}</p>
        </div>
        
        <p style="font-size: 15px; text-align: center;">Continua ad accumulare punti per sbloccare fantastici premi! 🎁</p>
        
        <div class="footer">
            <p>Grazie per essere un cliente fedele!</p>
            <p style="margin-top: 10px; font-size: 12px;">Questo è un messaggio automatico da {site_name}</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Template Email: Premio Riscattato
     */
    private static function get_email_reward_redeemed() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; padding: 20px; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #10b981; margin: 0; font-size: 28px; }
        .reward-box { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; border-radius: 12px; text-align: center; margin: 25px 0; }
        .reward-name { font-size: 32px; font-weight: bold; margin: 15px 0; }
        .code-box { background: #f9fafb; color: #1f2937; padding: 25px; border-radius: 10px; margin: 25px 0; text-align: center; border: 2px solid #10b981; }
        .code-value { font-size: 40px; font-weight: bold; color: #10b981; letter-spacing: 4px; }
        .qr-container { text-align: center; margin: 25px 0; }
        .qr-code { max-width: 280px; height: auto; border-radius: 10px; }
        .info-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 25px 0; border-radius: 8px; }
        .footer { text-align: center; color: #6b7280; font-size: 14px; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎁 Premio Riscattato!</h1>
        </div>
        
        <p style="font-size: 16px;">Ciao <strong>{user_name}</strong>,</p>
        
        <p style="font-size: 16px;">Complimenti! Hai riscattato con successo il tuo premio! 🎉</p>
        
        <div class="reward-box">
            <div style="font-size: 56px; margin-bottom: 15px;">🏆</div>
            <div class="reward-name">{reward_name}</div>
        </div>
        
        <div class="code-box">
            <p style="margin: 5px 0; font-size: 14px; color: #6b7280;">Il Tuo Codice Premio</p>
            <div class="code-value">{redemption_code}</div>
        </div>
        
        <div class="qr-container">
            <p style="color: #6b7280; font-size: 14px; margin-bottom: 15px;">Mostra questo QR Code al negoziante:</p>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=LOYALTY-REWARD-{redemption_code}" class="qr-code" alt="QR Premio">
        </div>
        
        <div class="info-box">
            <p style="margin: 0 0 10px 0; font-weight: bold;">⚠️ Come utilizzare il premio:</p>
            <ol style="margin: 10px 0; padding-left: 25px; line-height: 1.8;">
                <li>Mostra questo QR code al negoziante</li>
                <li>Il negoziante lo scansionerà con lo scanner</li>
                <li>Il premio verrà validato e applicato</li>
            </ol>
        </div>
        
        <div style="background: #f9fafb; padding: 20px; border-radius: 10px; margin: 25px 0;">
            <p style="margin: 5px 0; font-size: 15px;"><strong>📊 Riepilogo:</strong></p>
            <p style="margin: 5px 0;">Punti utilizzati: <strong>{points}</strong></p>
            <p style="margin: 5px 0;">Saldo rimanente: <strong>{new_balance} punti</strong></p>
        </div>
        
        <div class="footer">
            <p>Continua ad accumulare punti per altri fantastici premi! ❤️</p>
            <p style="margin-top: 10px; font-size: 12px;">Questo è un messaggio automatico da {site_name}</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Template Email: Soglia Raggiunta
     */
    private static function get_email_threshold_reached() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; padding: 20px; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #f59e0b, #ef4444); color: white; padding: 35px; border-radius: 12px; text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 36px; }
        .points-highlight { font-size: 64px; font-weight: bold; color: #f59e0b; text-align: center; margin: 30px 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.1); }
        .cta-button { display: inline-block; background: #10b981; color: white; padding: 18px 50px; border-radius: 10px; text-decoration: none; font-size: 20px; font-weight: bold; margin: 25px 0; box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .cta-button:hover { background: #059669; }
        .footer { text-align: center; color: #6b7280; font-size: 14px; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔥 FANTASTICO!</h1>
            <p style="margin: 15px 0 0 0; font-size: 20px;">Puoi riscattare un premio!</p>
        </div>
        
        <p style="font-size: 18px;">Ciao <strong>{user_name}</strong>,</p>
        
        <p style="font-size: 18px;">Complimenti! Hai raggiunto una soglia importante! 🎉</p>
        
        <div class="points-highlight">
            {new_balance} PUNTI
        </div>
        
        <p style="text-align: center; font-size: 20px; margin: 30px 0;">Hai abbastanza punti per riscattare dei premi fantastici!</p>
        
        <div style="text-align: center; margin: 35px 0;">
            <a href="{site_url}" class="cta-button">Riscatta Ora! 🎁</a>
        </div>
        
        <p style="text-align: center; font-size: 15px; color: #6b7280;">Non lasciarti scappare questa occasione!</p>
        
        <div class="footer">
            <p>Grazie per la tua fedeltà! ❤️</p>
            <p style="margin-top: 10px; font-size: 12px;">Questo è un messaggio automatico da {site_name}</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Template WhatsApp: Punti Aggiunti
     */
    private static function get_whatsapp_points_added() {
        return 'Ciao *{user_name}*! 🎉

Hai appena guadagnato *{points} punti*!

💰 Importo: €{amount}
⭐ Saldo attuale: *{new_balance} punti*

Continua ad accumulare per sbloccare premi fantastici! 🎁

Grazie per la tua fedeltà! ❤️';
    }
    
    /**
     * Template WhatsApp: Premio Riscattato
     */
    private static function get_whatsapp_reward_redeemed() {
        return 'Complimenti *{user_name}*! 🎁

Hai riscattato con successo:
🏆 *{reward_name}*

Il tuo codice premio:
🎫 *{redemption_code}*

Mostra questo messaggio al negoziante per ritirare il tuo premio!

📊 Saldo rimanente: *{new_balance} punti*

Continua a guadagnare punti! 💪';
    }
    
    /**
     * Template WhatsApp: Soglia Raggiunta
     */
    private static function get_whatsapp_threshold_reached() {
        return '🔥 *FANTASTICO!* 🔥

Ciao *{user_name}*, hai raggiunto *{new_balance} punti*!

Puoi già riscattare dei premi! 🎁

Vai sulla tua carta fedeltà e scopri cosa puoi ottenere!

Non lasciarti scappare questa occasione! ⏰';
    }
}
