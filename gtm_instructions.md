# Configuración de GTM para WP Minimal Consent

Esta guía explica cómo configurar **Google Tag Manager (GTM)** para que funcione correctamente con el plugin.

> **Idea clave**: el plugin gestiona el consentimiento del usuario y envía las señales a GTM. GTM decide qué etiquetas se disparan y cuándo. No dupliques lógica en los dos sitios.

---

## Antes de empezar — cómo funciona la integración

```
Plugin (WordPress)                    GTM
─────────────────                     ───
1. consent default = denied    →      GTM recibe el estado inicial
2. GTM carga                   →      Tags quedan bloqueados
3. Usuario acepta              →
4. consent update = granted    →      GTM desbloquea los tags correctos
5. wpmc_consent_update event   →      (opcional) activa tags adicionales
```

El plugin nunca bloquea GTM. GTM siempre carga, pero con todo en `denied` hasta que el usuario decida.

---

## 1. Activar Consent Overview (obligatorio)

En GTM ve a:

```
Admin → Container Settings → Enable consent overview
```

Esto te permite ver qué etiquetas tienen (o no tienen) configuración de consentimiento, detectar conflictos y validar que todo funciona correctamente.

**Hazlo antes de configurar ninguna etiqueta.**

---

## 2. Etiquetas de Google (GA4, Google Ads, Floodlight)

Las etiquetas oficiales de Google leen el estado de Consent Mode automáticamente. No necesitan configuración adicional.

**Lo que debes hacer:**
- Usa las etiquetas oficiales de Google normalmente
- Deja la configuración de consentimiento en su valor por defecto

**Lo que NO debes hacer:**
- No bloquees manualmente el Google tag
- No añadas triggers condicionales de consentimiento
- No crees etiquetas del tipo "Consent Initialization" o "Consent default" — el plugin ya lo hace antes de que GTM cargue

Las etiquetas de Google detectan automáticamente el estado de `analytics_storage`, `ad_storage`, `ad_user_data` y `ad_personalization` y se comportan en consecuencia.

---

## 3. Etiquetas no-Google (Clarity, Hotjar, Meta, TikTok, LinkedIn…)

Estos proveedores **no respetan Consent Mode automáticamente**. Hay que controlarlos mediante triggers condicionales.

### Paso 1 — Activa las variables integradas de estado de consentimiento

En GTM ve a **Variables → Variables integradas → Configurar** y activa:

- `Consent State - analytics_storage`
- `Consent State - ad_storage`
- `Consent State - ad_user_data`
- `Consent State - ad_personalization`

### Paso 2 — Crea variables derivadas (recomendado)

Evita repetir condiciones en cada etiqueta creando dos variables de tipo **Fórmula JavaScript personalizada**:

**Variable: `WPMC - Analytics Granted`**
```js
function() {
  return {{Consent State - analytics_storage}} === 'granted';
}
```

**Variable: `WPMC - Marketing Granted`**
```js
function() {
  return {{Consent State - ad_storage}} === 'granted'
    && {{Consent State - ad_user_data}} === 'granted'
    && {{Consent State - ad_personalization}} === 'granted';
}
```

### Paso 3 — Crea los triggers condicionales

**Trigger: `Page View - Analytics Granted`**
- Tipo: Vista de página
- Activación: Algunas vistas de página
- Condición: `WPMC - Analytics Granted` es igual a `true`

**Trigger: `Page View - Marketing Granted`**
- Tipo: Vista de página
- Activación: Algunas vistas de página
- Condición: `WPMC - Marketing Granted` es igual a `true`

### Paso 4 — Asigna los triggers a las etiquetas

| Etiqueta | Trigger a usar |
|---|---|
| Microsoft Clarity | Page View - Analytics Granted |
| Hotjar | Page View - Analytics Granted |
| Meta Pixel | Page View - Marketing Granted |
| TikTok Pixel | Page View - Marketing Granted |
| LinkedIn Insight | Page View - Marketing Granted |

---

## 4. Evento de actualización de consentimiento (opcional)

Cuando el usuario cambia sus preferencias **sin recargar la página**, el plugin emite:

```js
dataLayer.push({ event: 'wpmc_consent_update' })
```

Útil para reactivar etiquetas después de aceptar cookies sin necesitar recarga.

**Cómo configurarlo:**

Crea un trigger:
- Tipo: **Evento personalizado**
- Nombre del evento: `wpmc_consent_update`
- Nombre sugerido: `WPMC - Consent Update`

Puedes combinarlo con las variables `WPMC - Analytics Granted` o `WPMC - Marketing Granted` para disparar solo si la categoría relevante fue aceptada.

---

## 5. Validación

Antes de publicar, comprueba en **GTM Preview Mode**:

- [ ] Consent Overview no muestra etiquetas sin configuración de consentimiento
- [ ] Las etiquetas de Google se ven pero están restringidas antes del consentimiento
- [ ] El `dataLayer` contiene `consent default` al cargar la página
- [ ] El `dataLayer` contiene `consent update` tras aceptar
- [ ] El `dataLayer` contiene el evento `wpmc_consent_update`
- [ ] Las etiquetas no-Google solo disparan después de dar consentimiento en la categoría correcta

**Herramientas recomendadas:**

- GTM Preview Mode — para ver qué etiquetas disparan y cuándo
- Chrome DevTools → Application → Cookies — para ver el valor de `wpmc_consent`
- DebugView en GA4 — para confirmar que llegan hits
- Tag Assistant — como complemento, no como referencia principal

---

## 6. Errores frecuentes

| Error | Consecuencia | Solución |
|---|---|---|
| Crear etiquetas "Consent default" en GTM | El consentimiento se define dos veces, puede sobrescribir lo del plugin | Eliminar esas etiquetas — el plugin ya lo hace |
| Bloquear el tag de GTM con condiciones | GTM no carga y Consent Mode no funciona | No bloquear GTM nunca |
| Etiquetas no-Google sin trigger condicional | Disparan antes del consentimiento | Añadir trigger con variable derivada |
| Disparar por Page View sin condiciones | Tracking sin consentimiento | Usar triggers condicionales siempre para no-Google |
| Confiar solo en el banner sin validar GTM | Tags pueden disparar igual | Siempre validar con Preview Mode |
