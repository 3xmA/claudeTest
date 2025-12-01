# Google Wallet Integration

Integrazione completa di Google Wallet per permettere ai clienti di salvare la carta fedeltà direttamente nel loro telefono Android.

## 📋 Requisiti

- Account Google Cloud attivo
- Google Wallet API abilitata
- Service Account con ruolo "Google Wallet API Issuer"
- Registrazione come Google Pay API Issuer

## 🚀 Setup Completo

### 1. Abilita Google Wallet API

1. Vai su [Google Cloud Console](https://console.cloud.google.com/apis/library/walletobjects.googleapis.com)
2. Seleziona il tuo progetto (o creane uno nuovo)
3. Cerca "Google Wallet API"
4. Clicca **Enable**

### 2. Registra come Google Pay API Issuer

1. Vai su [Google Pay & Wallet Console](https://pay.google.com/business/console)
2. Accedi con il tuo account Google Business
3. Clicca **Get Started** e completa la registrazione aziendale
4. Una volta approvato, riceverai il tuo **Issuer ID**
5. Copia l'Issuer ID (formato: `3388000000012345678`)

### 3. Crea Service Account

1. Vai su [IAM & Admin > Service Accounts](https://console.cloud.google.com/iam-admin/serviceaccounts)
2. Clicca **Create Service Account**
3. Configura:
   - **Name**: `loyalty-wallet-service`
   - **Service account ID**: `loyalty-wallet-service`
4. Clicca **Create and Continue**
5. Assegna ruolo:
   - Cerca e seleziona: **Google Wallet API Issuer**
6. Clicca **Done**
7. Nella lista, clicca sul Service Account appena creato
8. Vai su **Keys** tab
9. Clicca **Add Key > Create new key**
10. Seleziona **JSON**
11. Clicca **Create** - il file JSON verrà scaricato automaticamente

### 4. Configura il Plugin

1. Vai su **WordPress Admin > Carta Fedeltà > Google Wallet**
2. Compila i campi:

   **Issuer ID:**
   ```
   3388000000012345678
   ```

   **Class ID:** (lascia il valore di default)
   ```
   loyalty_card_class
   ```

   **Service Account JSON:**
   - Apri il file JSON scaricato con un editor di testo
   - Copia **tutto il contenuto**
   - Incollalo nel campo textarea

   **Logo URL:** (opzionale)
   ```
   https://tuosito.com/wp-content/uploads/logo.png
   ```
   - Dimensioni consigliate: 660x660px
   - Formato: PNG o JPG
   - URL deve essere pubblicamente accessibile

3. Clicca **Salva Configurazione**

### 5. Inizializza Google Wallet

1. Dopo aver salvato la configurazione, clicca **Crea Classe Generic Pass**
2. Attendi il messaggio di successo
3. **Nota:** Se ricevi un errore "Class already exists", è normale - significa che la classe è già stata creata in precedenza

## ✅ Test

1. Vai alla dashboard loyalty come utente registrato
2. Dovresti vedere il pulsante blu **"Aggiungi a Google Wallet"**
3. Clicca il pulsante
4. Si aprirà una nuova finestra con Google Wallet
5. Clicca **"Add to Google Wallet"**
6. La carta apparirà nel tuo Google Wallet sul telefono

## 📱 Funzionalità

### Dati sulla Carta

La carta Google Wallet include:

- **Logo**: Logo del negozio (configurabile)
- **Titolare**: Nome dell'utente
- **Punti Disponibili**: Visualizzati in grande al centro
- **QR Code**: Stesso formato dello scanner (`LOYALTY-USER-{id}-{hash}`)
- **Statistiche**:
  - Totale punti guadagnati
  - Numero premi riscattati

### Aggiornamento Automatico

Ogni volta che l'utente genera il pass:
- I punti sono sempre aggiornati in tempo reale
- Il QR code rimane invariato (stesso utente)
- Le statistiche sono calcolate al momento della generazione

### Sicurezza

- JWT firmato con RSA-SHA256
- Service Account con permessi limitati
- QR code con hash di sicurezza
- Access token con cache automatica (50 minuti)

## 🔧 Personalizzazione

### Colore della Carta

Il colore di default è blu (`#4f46e5`). Per cambiarlo:

```php
// Nel file class-google-wallet.php, linea ~88
'hexBackgroundColor' => '#4f46e5', // Cambia questo
```

### Campi Aggiuntivi

Per aggiungere campi alla carta:

```php
// Nel metodo generate_jwt(), aggiungi a textModulesData:
array(
    'id' => 'custom_field',
    'header' => 'Titolo Campo',
    'body' => 'Valore Campo'
)
```

## 🐛 Troubleshooting

### Pulsante non appare

1. Verifica che la configurazione sia completa
2. Controlla che Issuer ID e Service Account siano compilati
3. Svuota cache WordPress/browser

### Errore "Class already exists"

È normale! Significa che la classe Generic Pass è già stata creata. Non è necessario ricrearla.

### Errore "Invalid JWT"

1. Verifica che il Service Account JSON sia completo
2. Controlla che non ci siano caratteri extra (spazi, newline)
3. Verifica che la private_key nel JSON sia corretta

### Pass non si genera

1. Controlla i log WordPress (`wp-content/debug.log`)
2. Verifica che il Service Account abbia il ruolo corretto
3. Controlla che la Google Wallet API sia abilitata
4. Verifica che l'Issuer ID sia corretto

### Logo non appare

1. Verifica che l'URL del logo sia pubblico (non richieda autenticazione)
2. Dimensioni consigliate: 660x660px
3. Formato PNG o JPG
4. Controlla che l'URL sia HTTPS

## 📊 API Endpoint

### Generate Pass JWT

```
GET /wp-json/loyalty/v1/google-wallet/pass
```

**Headers:**
```
Cookie: wordpress_logged_in_...
```

**Response:**
```json
{
  "success": true,
  "url": "https://pay.google.com/gp/v/save/eyJhbGc...",
  "message": "Pass generato con successo"
}
```

## 🔐 Permessi

Il Service Account richiede il ruolo:
- **Google Wallet API Issuer** (`roles/walletobjects.issuer`)

Questo ruolo permette di:
- Creare classi Generic Pass
- Creare oggetti Generic Pass
- Firmare JWT per Google Wallet

## 📝 Note Tecniche

### JWT Structure

```json
{
  "iss": "service-account@project.iam.gserviceaccount.com",
  "aud": "google",
  "typ": "savetowallet",
  "iat": 1234567890,
  "origins": ["https://tuosito.com"],
  "payload": {
    "genericObjects": [...]
  }
}
```

### QR Code Format

Il QR code sulla carta usa lo stesso formato dello scanner:
```
LOYALTY-USER-{user_id}-{hash}
```

Dove `hash` è calcolato come:
```php
substr(md5($user_id . AUTH_KEY), 0, 8)
```

### Caching

Access token OAuth2 è cachato per 50 minuti usando WordPress Transients:
```php
set_transient('loyalty_gw_access_token', $token, 3000);
```

## 🆘 Supporto

Per problemi o domande:

1. Verifica i log WordPress
2. Controlla la documentazione ufficiale:
   - [Google Wallet API](https://developers.google.com/wallet)
   - [Generic Pass](https://developers.google.com/wallet/generic)
3. Controlla che tutti i requisiti siano soddisfatti

## 📄 Licenza

GPL v2 or later

## 🎉 Credits

Integrazione Google Wallet per Sistema Carta Fedeltà
