# GTM – Configuración recomendada

Estas instrucciones describen cómo configurar **Google Tag Manager (GTM)** para trabajar correctamente con **WP Minimal Consent** usando **Google Consent Mode v2 (Advanced Mode)**.

> ⚠️ El plugin **no dispara etiquetas ni gestiona proveedores**.
> GTM es el responsable final de decidir **qué etiquetas se ejecutan y cuándo**, en función del consentimiento recibido.

## 1. Principios clave

**_( Leer antes de configurar nada )_**

- **Google Tag Manager SIEMPRE se carga**
- El plugin define `consent default = denied` **antes de GTM**
- El plugin envía `consent update` cuando el usuario interactúa
- **NO debes duplicar lógica de consentimiento en GTM**
- **Consent Mode v2 es la fuente de verdad**

## 2. Configuración general del contenedor GTM

### 2.1 Activar Consent Overview (obligatorio)

En GTM:

```
Admin → Container Settings → Enable consent overview
```

Esto permite:

- Auditar qué etiquetas requieren consentimiento
- Ver conflictos de configuración
- Validar que Consent Mode está funcionando correctamente

### 2.2 NO crear etiquetas de consentimiento en GTM

❌ **NO hagas esto**:

- No crees etiquetas de tipo:
  - “Consent Initialization”
  - “Consent default”
  - “Consent update”
- No uses plantillas de consentimiento

> El plugin ya gestiona todo esto **antes de GTM**.

## 3. Google tags (GA4, Google Ads, Floodlight, etc.)

### Qué hacer

- Usa **tags oficiales de Google**
- Mantén la configuración por defecto de consentimiento
- Deja que GTM + Consent Mode gestionen el bloqueo

### Qué NO hacer ❌

- No bloquees el Google tag
- No añadas triggers condicionales
- No añadas reglas manuales de consentimiento

### Por qué

Los Google tags:

- Detectan automáticamente el estado de:
  - `analytics_storage`
  - `ad_storage`
  - `ad_user_data`
  - `ad_personalization`
- Se ejecutan o se limitan según Consent Mode v2

## 4. No-Google tags (Clarity, Hotjar, Meta, TikTok, etc.)

Estos proveedores **NO respetan Consent Mode automáticamente** y deben controlarse mediante **triggers condicionales**.

### 4.1 Variables de consentimiento (recomendado)

Crea las siguientes **Variables integradas de GTM** (Consent State):

```
{{CONSENT – analytics_storage}}
{{CONSENT – ad_storage}}
{{CONSENT – ad_user_data}}
{{CONSENT – ad_personalization}}
```

### 4.2 Variables derivadas (mejor práctica)

Para evitar repetir lógica en cada etiqueta, crea estas variables:

#### Analytics

```
{{WPMC – AnalyticsGranted}}
→ {{CONSENT – analytics_storage}} equals granted
```

#### Marketing / Ads

```
{{WPMC – MarketingGranted}}
→ {{CONSENT – ad_storage}} equals granted
AND {{CONSENT – ad_user_data}} equals granted
AND {{CONSENT – ad_personalization}} equals granted
```

### 4.3 Triggers recomendados

#### Analytics (Clarity, Hotjar, etc.)

- Tipo: **Page View**
- Activación: **Some Page Views**
- Condición:
  ```
  {{WPMC – AnalyticsGranted}} = true
  ```
- Nombre sugerido:
  ```
  Page View – Analytics Granted
  ```

Asigna este trigger a todas las etiquetas de analítica no-Google.

#### Marketing (Meta, TikTok, LinkedIn, etc.)

- Tipo: **Page View**
- Activación: **Some Page Views**
- Condición:
  ```
  {{WPMC – MarketingGranted}} = true
  ```
- Nombre sugerido:
  ```
  Page View – Marketing Granted
  ```

Asigna este trigger a todas las etiquetas de marketing.

## 5. Evento de actualización de consentimiento (opcional)

El plugin emite el siguiente evento cuando el usuario cambia su elección:

```
event: wpmc_consent_update
```

### Cuándo usarlo

- Para etiquetas que deben dispararse **después** de aceptar cookies
- Para reactivar scripts sin recargar la página

### Cómo configurarlo

- Trigger:
  - Tipo: **Custom Event**
  - Event name: `wpmc_consent_update`
- Nombre sugerido:
  ```
  WPMC – Consent Update
  ```

Este trigger puede combinarse con las variables `WPMC – AnalyticsGranted` o `WPMC – MarketingGranted`.

## 6. Validación y testing

- Consent Overview sin errores
- Google tags visibles pero restringidos antes del consentimiento
- `dataLayer` contiene:
  - `consent default`
  - `consent update`
  - `wpmc_consent_update`
- Las etiquetas no-Google solo disparan tras consentimiento

### Herramientas recomendadas

- GTM Preview Mode
- Chrome DevTools → Application → Cookies
- DebugView (GA4)
- Tag Assistant (solo como complemento)

## 7. Errores comunes a evitar

- Bloquear GTM
- Duplicar consentimiento en GTM
- Usar Consent Initialization tags
- Disparar etiquetas por Page View sin condiciones
- Confiar en banners sin validar GTM
