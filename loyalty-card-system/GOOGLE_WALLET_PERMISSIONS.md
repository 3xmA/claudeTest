# Fix 403 Permission Denied - Google Wallet

## Problema
Errore 403 "Permission denied" quando si crea la classe Generic Pass.

## Causa
Il Service Account non ha i permessi corretti per usare Google Wallet API con il tuo Issuer ID.

## Soluzione Passo-Passo

### 1. Abilita Google Wallet API nel Progetto

1. Vai su: https://console.cloud.google.com/apis/library/walletobjects.googleapis.com
2. Assicurati di essere nel progetto **plasma-block-466616-j7**
3. Se non è abilitata, clicca **"Abilita"** (Enable)
4. Attendi il completamento (circa 10 secondi)

### 2. Verifica Ruoli del Service Account

1. Vai su: https://console.cloud.google.com/iam-admin/iam
2. Cerca: `loyalty-card-system-sa@plasma-block-466616-j7.iam.gserviceaccount.com`
3. Clicca sulla matita (modifica)
4. Verifica che abbia ALMENO uno di questi ruoli:
   - **Google Wallet API Admin** (consigliato)
   - **Owner** o **Editor** del progetto

5. Se non li ha, aggiungili:
   - Clicca "+ AGGIUNGI ALTRO RUOLO"
   - Cerca "Google Wallet API Admin"
   - Salva

### 3. Autorizza Service Account nell'Issuer (IMPORTANTE!)

Questo è il passaggio più importante che spesso viene saltato:

1. Vai su: https://pay.google.com/business/console
2. Seleziona il tuo Issuer ID (3388000000023043841)
3. Vai su **Settings** o **API Access**
4. Aggiungi il Service Account:
   ```
   loyalty-card-system-sa@plasma-block-466616-j7.iam.gserviceaccount.com
   ```
5. Assegna permesso **"Can manage passes"** o simile
6. Salva

### 4. Alternative: Usa Google Cloud Console per Issuer

Se non trovi le impostazioni nella Pay Console:

1. Vai su: https://console.cloud.google.com/
2. Seleziona progetto `plasma-block-466616-j7`
3. Cerca "Wallet Objects" nel menu
4. Vai su "API Access" o "Service Accounts"
5. Collega il Service Account all'Issuer ID

### 5. Attendi 5 Minuti

Le modifiche ai permessi possono richiedere fino a 5 minuti per propagarsi.

### 6. Riprova

1. Torna su WordPress Admin
2. Vai su **Carta Fedeltà > Google Wallet**
3. Clicca di nuovo **"Crea Classe Generic Pass"**

## Note Importanti

### Service Account Email
```
loyalty-card-system-sa@plasma-block-466616-j7.iam.gserviceaccount.com
```

### Issuer ID
```
3388000000023043841
```

### Progetto
```
plasma-block-466616-j7
```

## Verifica Rapida Permessi

Esegui questo comando in Google Cloud Shell per verificare i permessi:

```bash
gcloud projects get-iam-policy plasma-block-466616-j7 \
  --flatten="bindings[].members" \
  --filter="bindings.members:loyalty-card-system-sa@plasma-block-466616-j7.iam.gserviceaccount.com"
```

Dovresti vedere almeno:
- `roles/walletobjects.admin` o
- `roles/editor` o
- `roles/owner`

## Link Utili

- Google Cloud Console IAM: https://console.cloud.google.com/iam-admin/iam?project=plasma-block-466616-j7
- Google Pay & Wallet Console: https://pay.google.com/business/console
- Google Wallet API: https://console.cloud.google.com/apis/library/walletobjects.googleapis.com?project=plasma-block-466616-j7

## Se Ancora Non Funziona

Prova a creare un NUOVO Service Account con questi passaggi:

1. Vai su: https://console.cloud.google.com/iam-admin/serviceaccounts?project=plasma-block-466616-j7
2. Clicca "Create Service Account"
3. Nome: `wallet-issuer`
4. Ruolo: **Google Wallet API Admin**
5. Crea chiave JSON
6. Scarica il JSON
7. Sostituiscilo nella configurazione WordPress
8. Riprova

## Troubleshooting

### Errore: "Wallet Objects API has not been used in project"
- Vai su https://console.cloud.google.com/apis/library/walletobjects.googleapis.com
- Clicca "Enable"

### Errore: "The caller does not have permission"
- Il Service Account deve essere autorizzato nell'Issuer ID
- Vai su Google Pay Console e aggiungi il Service Account

### Errore: "Invalid issuer"
- Verifica che l'Issuer ID sia corretto: 3388000000023043841
- L'Issuer deve essere attivo e approvato
