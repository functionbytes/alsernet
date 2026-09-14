# AI Setup — Helpdesk

Guía para activar las features de AI/LLM (Fase 4 del ROADMAP).

## Variables `.env` requeridas

```env
# Master switch (false = todos los services AI son no-op)
HELPDESK_AI_ENABLED=true

# OpenAI (sugerencia respuesta, resumen, sentimiento, auto-tag, transcripción audio)
OPENAI_API_KEY=sk-proj-...
OPENAI_MODEL=gpt-4o-mini             # default; recomendado por costo/calidad

# DeepL (traducción inline, calidad superior a OpenAI para idiomas)
DEEPL_API_KEY=xxxx-xxxx-xxxx:fx       # plan free, 500.000 chars/mes
```

Después de cambiar `.env`:

```bash
php artisan config:clear
supervisorctl restart horizon:horizon_00
```

## Cómo obtener las credenciales

### OpenAI
1. https://platform.openai.com/signup
2. Settings → Billing → agregar método de pago (mínimo $5)
3. API Keys → Create new secret key
4. Copiar a `OPENAI_API_KEY`

### DeepL
1. https://www.deepl.com/pro-api → "Free Plan"
2. Verificar email + tarjeta (no se cobra)
3. Account → Authentication Key for DeepL API
4. Copiar a `DEEPL_API_KEY`

## Servicios y costo estimado

| Service | Modelo | Costo aprox. por uso | Notas |
|---|---|---|---|
| `SuggestReplyService::suggest()` | gpt-4o-mini | ~$0.0008 / sugerencia | 3 sugerencias por click |
| `ConversationSummaryService::summarize()` | gpt-4o-mini | ~$0.0015 / resumen | Solo al cerrar |
| `SentimentService::detect()` | gpt-4o-mini | ~$0.0003 / mensaje | Cada mensaje del cliente |
| `AutoTagService::categorize()` | gpt-4o-mini | ~$0.0005 / conversación | Solo primer mensaje |
| `AudioTranscriptionService::transcribe()` | whisper-1 | $0.006 / minuto audio | Solo audios entrantes |
| `DeepLTranslationService::translate()` | DeepL | gratis hasta 500k chars/mes | cacheado 24h |

**Estimación mensual** para un helpdesk con 1.000 conversaciones/mes:
- Sentimiento: 5.000 mensajes × $0.0003 = **$1.50**
- Auto-tag: 1.000 × $0.0005 = **$0.50**
- Resumen al cierre: 1.000 × $0.0015 = **$1.50**
- Sugerencias respuesta (asumiendo 30% uso, 3 por uso): 300 × $0.0008 = **$0.24**
- Whisper (asumiendo 100 audios/mes × 30s): ~$0.30
- **Total: ~$4 USD/mes**

## Endpoints disponibles

| Endpoint | Auth | Body | Respuesta |
|---|---|---|---|
| `POST /panel/helpdesk/conversations/{id}/ai/suggest-replies` | web | — | `{suggestions: [str, str, str]}` |
| `POST /panel/helpdesk/conversations/items/{item}/translate` | web | `{target: 'es'}` | `{translated: '...'}` |

## Listeners registrados (background)

| Listener | Evento | Qué hace |
|---|---|---|
| `AnalyzeSentimentOnIncoming` | `ConversationMessageCreated` | Detecta sentimiento; si negativo → `priority='urgent'` + tag `sentiment_negative` |
| `AutoTagFirstMessage` | `ConversationMessageCreated` (solo primer item del cliente) | Categoriza: facturación / soporte / ventas / reclamo |

## Hooks de AI

- `ConversationsController::close` → dispara `ConversationSummaryService::summarize()` después del CSAT y guarda como nota interna `is_internal=true`
- `DownloadConversationAttachmentsJob` (al finalizar download de audio) → dispatch `TranscribeAudioJob` que guarda transcripción en `metadata.transcription` del item

## Cómo desactivar todo

```env
HELPDESK_AI_ENABLED=false
```

Todos los services retornan `null`/no-op silenciosamente, listeners no hacen nada. Útil para desarrollo o cuando no hay créditos.

## Costo bajo control

- **Cache DeepL** 24h por hash del texto → traducciones repetidas no cobran
- **Whisper** solo para audios reales, no para video/imagen
- **gpt-4o-mini** en lugar de gpt-4 (10× más barato, calidad suficiente para soporte)
- **Sentimiento + auto-tag** solo en primer mensaje, no en cada item

## Probar localmente

```bash
# Probar sentimiento manualmente
php artisan tinker
>>> $svc = app(\Modules\Helpdesk\Services\AI\SentimentService::class);
>>> $svc->detect('Estoy super molesto, esto no funciona y nadie me ayuda');
=> ['label' => 'negative', 'score' => -0.8]

# Probar sugerencia
>>> $svc = app(\Modules\Helpdesk\Services\AI\SuggestReplyService::class);
>>> $svc->suggest(\Modules\Helpdesk\Models\Conversation::find(1));

# Probar traducción
>>> app(\Modules\Helpdesk\Services\AI\DeepLTranslationService::class)
    ->translate('Hello, how are you?', 'es');
=> 'Hola, ¿cómo estás?'
```

## Troubleshooting

**"Invalid OAuth access token"** al llamar OpenAI → revisa que `OPENAI_API_KEY` empiece con `sk-` y que no tenga espacios.

**Whisper falla con audios largos** → la API máxima es 25MB. El job `TranscribeAudioJob` debería skip silenciosamente si el audio supera ese tamaño y loggear warning.

**DeepL devuelve 403** → cuenta sin verificar pago (DeepL exige tarjeta aunque sea plan free).

**"Helpdesk AI is disabled"** en logs → `HELPDESK_AI_ENABLED=false` o no está en `.env`. Recuerda `config:clear`.
