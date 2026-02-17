document.addEventListener("DOMContentLoaded", () => {
  // REFERENCIAS DOM
  const UI = {
    banner: document.getElementById("wpmc-banner"),
    modal: document.getElementById("wpmc-modal"),
    title: document.getElementById("wpmc-title"),
    floatBtn: document.getElementById("wpmc-preferences-btn"),
    btns: {
      accept: document.getElementById("wpmc-accept"),
      reject: document.getElementById("wpmc-reject"),
      manage: document.getElementById("wpmc-manage"),
      save: document.getElementById("wpmc-save"),
      close: document.getElementById("wpmc-close"),
    },
    toggles: {
      analytics: document.getElementById("wpmc-opt-analytics"),
      ads: document.getElementById("wpmc-opt-marketing"),
    },
  };

  if (!UI.banner || !UI.modal || !UI.btns.accept || !UI.btns.reject) return;

  // Variable para recordar qué botón abrió el modal
  // (para devolver el foco por accesibilidad)
  let lastFocusedElement = null;

  // Activa todos los scripts bloqueados de una categoría (analytics / ads / functional)
  const wpmcActivateCategory = (category) => {
    const nodes = document.querySelectorAll(
      `script[type="text/plain"][data-wpmc-category="${category}"]`,
    );

    if (!nodes.length) return 0;

    nodes.forEach((oldScript) => {
      const s = document.createElement("script");

      // type real
      s.type = oldScript.dataset.type || "text/javascript";

      // src o inline
      if (oldScript.src) {
        s.src = oldScript.src;
        if (oldScript.async) s.async = true;
        if (oldScript.defer) s.defer = true;
      } else {
        s.text = oldScript.text || oldScript.textContent || "";
      }

      // Reemplazo (esto dispara ejecución)
      oldScript.parentNode.replaceChild(s, oldScript);
    });

    if (window.WPMC_CONFIG?.isDebug) {
      console.log(
        "[WPMC] Activated category:",
        category,
        "scripts:",
        nodes.length,
      );
    }

    return nodes.length;
  };

  // Aplica el gating según el consentimiento actual (cookie)
  const wpmcApplyGatesFromConsent = () => {
    if (window.wpmcCanRun?.("functional")) wpmcActivateCategory("functional");
    if (window.wpmcCanRun?.("analytics")) wpmcActivateCategory("analytics");
    if (window.wpmcCanRun?.("ads")) wpmcActivateCategory("ads");
  };

  wpmcApplyGatesFromConsent();

  const wpmcUpdateConsentMode = (gtmState) => {
    window.dataLayer = window.dataLayer || [];
    // gtag puede no existir todavía, pero dataLayer sí
    window.dataLayer.push(["consent", "update", gtmState]);
    if (window.WPMC_CONFIG?.isDebug) {
      console.log("[WPMC] Updated GTM Consent Mode:", gtmState);
    }
  };

  // LÓGICA DE NEGOCIO (CONTROLADOR)
  const Controller = {
    // Mapea categorías simples a señales de Google
    mapToGTM: (prefs) => {
      return {
        analytics_storage: prefs.analytics ? "granted" : "denied",
        ad_storage: prefs.ads ? "granted" : "denied",
        ad_user_data: prefs.ads ? "granted" : "denied",
        ad_personalization: prefs.ads ? "granted" : "denied",
      };
    },

    // Guarda, actualiza GTM y maneja la UI
    saveConsent: (preferences) => {
      // Guardar en Cookie (Usando API propia)
      window.RcConsent.setConsent(preferences);
      wpmcApplyGatesFromConsent(); // Activar scripts según nueva cookie

      // Actualizar GTM
      const gtmState = Controller.mapToGTM(preferences);
      wpmcUpdateConsentMode(gtmState);

      // Disparar evento personalizado para GTM
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ event: "consent_update" });

      // Debug
      if (window.WPMC_CONFIG?.isDebug) {
        console.log("[WPMC] Saved & Updated:", preferences);
      }

      // Actualizar UI
      Controller.updateUI(true);

      // Cerrar modal tras guardar
      Controller.closeModal();
    },

    // Decide qué mostrar basado en si hay cookie o no
    updateUI: (hasConsent) => {
      if (hasConsent) {
        UI.banner.style.display = "none";
        if (UI.floatBtn) UI.floatBtn.style.display = "block";
      } else {
        UI.banner.style.display = "flex";
        if (UI.floatBtn) UI.floatBtn.style.display = "none";
      }
    },

    // Lee la cookie y marca los checkbox
    hydrate: () => {
      if (!UI.toggles.analytics || !UI.toggles.ads) return;

      const current = window.RcConsent.getConsent();

      // Debug para ver si realmente estamos leyendo la cookie
      if (window.WPMC_DEBUG)
        console.log("[WPMC] Hydrating form with:", current);

      // Si existe, usa valores guardados. Si no, false por defecto.
      UI.toggles.analytics.checked = current ? current.prefs.analytics : false;
      UI.toggles.ads.checked = current ? current.prefs.ads : false;
    },

    openModal: () => {
      // Guardar foco actual
      lastFocusedElement = document.activeElement;

      // Hidratar datos antes de mostrar
      Controller.hydrate();

      // Mostrar modal
      UI.modal.hidden = false;

      // Ocultar botón flotante y banner
      if (UI.floatBtn) UI.floatBtn.style.display = "none";
      UI.banner.style.display = "none";

      // Mover foco al modal (o al primer elemento focusable, ej: título o primer checkbox)
      // setTimeout asegura que el navegador ya procesó el cambio de 'hidden'
      setTimeout(() => {
        if (UI.title) UI.title.focus();
        else UI.btns.close.focus();
      }, 50);
    },

    closeModal: () => {
      UI.modal.hidden = true;

      // Restaurar foco al elemento que abrió el modal
      if (lastFocusedElement) lastFocusedElement.focus();

      // Comprobar el estado real en la cookie
      // Si hay cookie (true) -> updateUI mostrará el botón flotante
      // Si no hay cookie (false) -> updateUI volverá a sacar el banner inicial
      const hasCookie = !!window.RcConsent.getConsent();
      Controller.updateUI(hasCookie);
    },
  };

  // INICIALIZACIÓN
  const savedConsent = window.RcConsent.getConsent();
  Controller.updateUI(!!savedConsent);

  // EVENT LISTENERS
  // Aceptar Todo
  UI.btns.accept?.addEventListener("click", () => {
    Controller.saveConsent({
      necessary: true,
      functional: true,
      analytics: true,
      ads: true,
    });
  });

  // Rechazar Todo (menos necesarias)
  UI.btns.reject?.addEventListener("click", () => {
    Controller.saveConsent({
      necessary: true,
      functional: false,
      analytics: false,
      ads: false,
    });
  });

  // Gestionar / Abrir Modal
  UI.btns.manage?.addEventListener("click", Controller.openModal);
  UI.floatBtn?.addEventListener("click", Controller.openModal);

  // Guardar Preferencias (desde el modal)
  UI.btns.save?.addEventListener("click", () => {
    Controller.saveConsent({
      necessary: true,
      functional: false, // Opcional
      analytics: UI.toggles.analytics.checked,
      ads: UI.toggles.ads.checked,
    });
  });

  // Cerrar Modal
  UI.btns.close?.addEventListener("click", Controller.closeModal);
  document
    .querySelector(".wpmc-overlay")
    ?.addEventListener("click", Controller.closeModal);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !UI.modal.hidden) {
      Controller.closeModal();
    }
  });
});
