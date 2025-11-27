# Sistema Carta Fedeltà WordPress 🎫

Sistema completo di carta fedeltà con QR code, punti e premi per WordPress.

## 🚀 Caratteristiche

- ✅ Carta fedeltà virtuale con QR code
- ✅ Sistema punti automatico
- ✅ Gestione premi riscattabili
- ✅ API REST complete
- ✅ Integrazione n8n per automazioni
- ✅ Dashboard admin completa
- ✅ Responsive e mobile-friendly

## 📦 Installazione

### 1. Carica il Plugin

Copia la cartella `loyalty-card-system` nella directory dei plugin di WordPress:
```
wp-content/plugins/loyalty-card-system/
```

### 2. Attiva il Plugin

Vai in **WordPress Admin > Plugin** e attiva "Sistema Carta Fedeltà"

### 3. Configurazione Base

1. Vai su **Carta Fedeltà > Dashboard** nel menu admin
2. Configura:
   - **Punti per Euro**: quanti punti per ogni euro speso (default: 1)
   - **API Key**: per autenticare l'app del negoziante
   - **Webhook URL**: l'URL del webhook n8n (opzionale)

### 4. Crea una Pagina per gli Utenti

1. Crea una nuova pagina (es: "La Mia Carta")
2. Inserisci lo shortcode: `[loyalty_card]`
3. Pubblica la pagina

✅ Fatto! Gli utenti registrati potranno vedere la loro carta fedeltà con QR code.

## 🎯 Come Funziona

### Per i Clienti

1. L'utente si registra sul sito
2. Accede alla pagina con lo shortcode `[loyalty_card]`
3. Vede il suo QR code personale
4. Mostra il QR al negoziante ad ogni acquisto
5. Accumula punti automaticamente
6. Può riscattare premi quando raggiunge i punti necessari

### Per il Negoziante

Il negoziante avrà bisogno di un'app/webapp per:
1. Scansionare il QR code del cliente
2. Inserire l'importo dell'acquisto
3. Il sistema calcola e aggiunge i punti automaticamente

## 🔌 API REST

### Base URL
```
https://tuosito.com/wp-json/loyalty/v1/
```

### Autenticazione

Per endpoint protetti, aggiungi l'header:
```
X-API-Key: [la tua API key dalle impostazioni]
```

### Endpoint Disponibili

#### 1. Aggiungi Punti
```http
POST /add-points
Content-Type: application/json
X-API-Key: your-api-key-here

{
  "user_id": 123,
  "points": 50,
  "amount": 50.00,
  "note": "Acquisto in negozio"
}
```

**Risposta:**
```json
{
  "success": true,
  "transaction_id": 456,
  "new_balance": 250,
  "message": "Punti aggiunti con successo"
}
```

#### 2. Ottieni Saldo
```http
GET /balance/123
```

**Risposta:**
```json
{
  "user_id": 123,
  "balance": 250
}
```

#### 3. Lista Premi
```http
GET /rewards
```

**Risposta:**
```json
{
  "success": true,
  "rewards": [
    {
      "id": 1,
      "name": "Sconto 5€",
      "description": "Buono sconto di 5€",
      "points_required": 100,
      "reward_type": "discount",
      "reward_value": "5"
    }
  ]
}
```

#### 4. Riscatta Premio
```http
POST /redeem
Content-Type: application/json
X-WP-Nonce: [nonce WordPress]

{
  "reward_id": 1
}
```

**Risposta:**
```json
{
  "success": true,
  "redemption_code": "ABC12345",
  "new_balance": 150,
  "reward": {...}
}
```

#### 5. Valida Premio Riscattato
```http
POST /validate-reward
Content-Type: application/json
X-API-Key: your-api-key-here

{
  "redemption_code": "ABC12345"
}
```

**Risposta:**
```json
{
  "success": true,
  "message": "Premio validato con successo",
  "redemption": {...}
}
```

## 🔄 Integrazione n8n

### Setup Webhook n8n

1. Crea un nuovo workflow in n8n
2. Aggiungi un nodo "Webhook"
3. Copia l'URL del webhook
4. Incollalo nelle impostazioni WordPress (Carta Fedeltà > Dashboard)

### Eventi Disponibili

Il sistema invia notifiche webhook per questi eventi:

#### `points_added`
```json
{
  "event": "points_added",
  "timestamp": "2025-01-15 10:30:00",
  "data": {
    "user_id": 123,
    "user_email": "cliente@example.com",
    "user_name": "Mario Rossi",
    "points": 50,
    "new_balance": 250,
    "amount": 50.00,
    "note": "Acquisto in negozio"
  }
}
```

#### `reward_redeemed`
```json
{
  "event": "reward_redeemed",
  "timestamp": "2025-01-15 11:00:00",
  "data": {
    "user_id": 123,
    "user_email": "cliente@example.com",
    "user_name": "Mario Rossi",
    "reward_name": "Sconto 10€",
    "redemption_code": "ABC12345",
    "points_used": 200,
    "new_balance": 50
  }
}
```

### Esempi di Automazioni n8n

**1. Invio Email di Conferma Punti**
```
Webhook → Set → Gmail/SendGrid
```

**2. Notifica Telegram**
```
Webhook → Set → Telegram
```

**3. SMS con Twilio**
```
Webhook → Switch (se punti > soglia) → Twilio
```

**4. Google Sheets Tracking**
```
Webhook → Google Sheets (Append Row)
```

**5. Marketing Automation**
```
Webhook → HTTP Request → Mailchimp/ActiveCampaign
```

## 🎨 Personalizzazione

### CSS Custom

Puoi personalizzare gli stili modificando:
```
/assets/css/style.css
```

Oppure aggiungere CSS custom nel tema:
```css
/* Cambia colore primario */
:root {
    --loyalty-primary: #your-color;
}

/* Personalizza la carta */
.loyalty-card {
    background: linear-gradient(135deg, #your-gradient);
}
```

### Template Custom

Per personalizzare il template della carta, copia:
```
/templates/loyalty-card.php
```
nel tuo tema child in:
```
/wp-content/themes/tuo-tema/loyalty-card/loyalty-card.php
```

## 📱 App Negoziante (prossimo step)

Per completare il sistema, dovrai creare una semplice webapp per il negoziante che:

1. Scansiona QR code (usando libreria come html5-qrcode)
2. Decodifica il QR per ottenere user_id
3. Mostra form per inserire importo
4. Chiama API `/add-points`

### Esempio Base HTML
```html
<!DOCTYPE html>
<html>
<head>
    <title>Scanner Carta Fedeltà</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
    <div id="qr-reader" style="width: 500px"></div>
    
    <script>
    const html5QrCode = new Html5Qrcode("qr-reader");
    
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 250 },
        onScanSuccess
    );
    
    function onScanSuccess(decodedText) {
        // decodedText = "LOYALTY-USER-123-abc12345"
        const userId = decodedText.split('-')[2];
        
        // Mostra form per importo
        const amount = prompt("Inserisci importo:");
        
        // Calcola punti e invia API
        const points = Math.floor(amount);
        
        fetch('https://tuosito.com/wp-json/loyalty/v1/add-points', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': 'YOUR-API-KEY'
            },
            body: JSON.stringify({
                user_id: userId,
                points: points,
                amount: amount,
                note: 'Acquisto in negozio'
            })
        })
        .then(res => res.json())
        .then(data => {
            alert('✅ Punti aggiunti! Nuovo saldo: ' + data.new_balance);
        });
    }
    </script>
</body>
</html>
```

## 🔐 Sicurezza

- ✅ Le API sono protette con API key
- ✅ I QR code contengono hash di sicurezza
- ✅ WordPress nonce per operazioni utente
- ✅ Validazione input su tutti gli endpoint
- ✅ Sanitizzazione output

### Best Practices

1. Cambia l'API key di default
2. Usa HTTPS per tutte le chiamate API
3. Limita l'accesso all'app negoziante solo a IP fidati
4. Monitora le transazioni sospette dalla dashboard

## 📊 Database

Il plugin crea 3 tabelle:

### `wp_loyalty_transactions`
Tutte le transazioni di punti (guadagno/spesa)

### `wp_loyalty_rewards`
Catalogo dei premi disponibili

### `wp_loyalty_redemptions`
Premi riscattati dagli utenti

## 🐛 Troubleshooting

### I QR code non si generano
- Verifica che il server abbia accesso a Google Charts API
- Oppure installa libreria phpqrcode per generazione locale

### Le API non funzionano
- Verifica i permalink (Impostazioni > Permalink > Salva)
- Controlla che l'API key sia corretta
- Verifica i log degli errori PHP

### n8n non riceve webhook
- Controlla che l'URL webhook sia corretto
- Verifica che n8n sia raggiungibile dall'esterno
- Testa il webhook manualmente con Postman

## 📝 TODO / Prossimi Sviluppi

- [ ] App mobile nativa per negoziante (React Native)
- [ ] Sistema di livelli/tier (Bronze, Silver, Gold)
- [ ] Punti con scadenza
- [ ] Coupon personalizzati
- [ ] Statistiche avanzate con grafici
- [ ] Import/export dati
- [ ] Multi-negozio support

## 🤝 Supporto

Per domande o problemi, crea un issue sul repository o contatta lo sviluppatore.

## 📄 Licenza

GPL v2 or later

---

Sviluppato con ❤️ per semplificare la gestione delle carte fedeltà
