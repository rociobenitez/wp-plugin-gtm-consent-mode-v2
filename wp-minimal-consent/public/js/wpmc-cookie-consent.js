(() => {
  "use strict";

  // Configuración desde PHP vía wp_localize_script
  const config = window.WPMC_CONFIG || {
    //cookieName: '__Host-cmp_consent',  // ejemplo de nombre con __Host-
    cookieName: "wpmc_consent",
    policyVersion: 1,
  };

  const CONSENT_COOKIE_NAME = config.cookieName;
  const CURRENT_POLICY_VERSION = Number(config.policyVersion) || 1;

  const getDefaultPrefs = () => ({
    v: CURRENT_POLICY_VERSION,
    ts: Math.floor(Date.now() / 1000),
    prefs: {
      necessary: true,
      functional: false,
      analytics: false,
      ads: false,
    },
  });

  // DEBUG: Log inicial
  // if (config.isDebug) {
  //   console.log("[RcConsent] init", {
  //     cookieName: CONSENT_COOKIE_NAME,
  //     policyVersion: CURRENT_POLICY_VERSION,
  //   });
  // }

  const setCookie = (name, value, options = {}) => {
    let cookieStr = `${name}=${encodeURIComponent(value)}`;

    // Max-Age en segundos (por defecto 1 año)
    const maxAge =
      typeof options.maxAge === "number" ? options.maxAge : 31536000;
    cookieStr += `; Max-Age=${maxAge}`;

    const path = options.path || "/";
    cookieStr += `; Path=${path}`;

    // Secure por defecto (solo HTTPS)
    const isLocal =
      location.hostname === "localhost" ||
      location.hostname === "127.0.0.1" ||
      location.hostname.endsWith(".local");
    if (options.secure !== false && !isLocal) {
      cookieStr += "; Secure";
    }

    const sameSite = options.sameSite || "Lax";
    cookieStr += `; SameSite=${sameSite}`;

    document.cookie = cookieStr;

    // DEBUG: Verificar si se ha guardado
    // if (config.isDebug)
    //   console.log(
    //     "[RcConsent] Cookie set try:",
    //     document.cookie.indexOf(name) > -1 ? "Success" : "Failed"
    //   );
  };

  const getCookie = (name) => {
    if (!document.cookie) return null;

    const cookies = document.cookie.split(";");
    for (let i = 0; i < cookies.length; i += 1) {
      const cookie = cookies[i].trim();
      if (cookie.startsWith(`${name}=`)) {
        return decodeURIComponent(cookie.substring(name.length + 1));
      }
    }
    return null;
  };

  const deleteCookie = (name, path = "/") => {
    // Sobrescribimos la cookie con Max-Age=0
    document.cookie = `${name}=; Path=${path}; Max-Age=0; Secure; SameSite=Lax`;
  };

  const readConsent = () => {
    const raw = getCookie(CONSENT_COOKIE_NAME);
    if (!raw) return null;

    try {
      const parsed = JSON.parse(raw);

      if (
        typeof parsed !== "object" ||
        typeof parsed.v !== "number" ||
        typeof parsed.ts !== "number" ||
        typeof parsed.prefs !== "object"
      ) {
        return null;
      }

      // Si la versión está desfasada, lo consideramos inválido
      if (parsed.v < CURRENT_POLICY_VERSION) {
        return null;
      }

      return parsed;
    } catch (e) {
      return null;
    }
  };

  const writeConsent = (prefs) => {
    const base = getDefaultPrefs();

    base.prefs = {
      ...base.prefs,
      ...prefs,
    };
    base.ts = Math.floor(Date.now() / 1000);

    setCookie(CONSENT_COOKIE_NAME, JSON.stringify(base), {
      maxAge: 31536000, // 1 año
      path: "/",
      secure: true,
      sameSite: "Lax",
    });

    return base;
  };

  const getConsent = () => {
    const existing = readConsent();
    return existing || null;
  };

  const setConsent = (prefs) => writeConsent(prefs);

  const hasConsent = (category) => {
    const consent = getConsent();

    // necessary siempre true
    if (category === "necessary") return true;

    // Sin cookie válida → tratamos como false para todo lo no necesario
    if (!consent || !consent.prefs) return false;

    if (typeof consent.prefs[category] === "undefined") return false;

    return !!consent.prefs[category];
  };

  // Exponer API global mínima
  window.RcConsent = {
    getConsent,
    setConsent,
    hasConsent,
    _debug: {
      readConsent,
      deleteCookie: () => deleteCookie(CONSENT_COOKIE_NAME),
    },
  };

  window.wpmcCanRun = (category) => {
    // necessary siempre true
    if (category === "necessary") return true;

    // si no hay decisión, todo lo no necesario es false
    return window.RcConsent?.hasConsent?.(category) === true;
  };
})();
