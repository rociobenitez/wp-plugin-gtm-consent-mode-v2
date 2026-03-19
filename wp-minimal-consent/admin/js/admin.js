/* WP Minimal Consent — Admin color picker */
(function () {
  "use strict";

  document.querySelectorAll(".wpmc-color-wrap").forEach(function (wrap) {
    const hidden = wrap.querySelector(".wpmc-color-hidden");
    const picker = wrap.querySelector(".wpmc-color-picker");
    const reset  = wrap.querySelector(".wpmc-color-reset");
    const swatch = wrap.querySelector(".wpmc-color-swatch");

    if (!hidden || !picker || !reset) return;

    // Sincroniza picker → hidden + swatch al cambiar color
    picker.addEventListener("input", function () {
      hidden.value = picker.value;
      if (swatch) swatch.style.background = picker.value;
      wrap.classList.add("wpmc-color--active");
      reset.disabled = false;
    });

    // Restablecer: limpia valor guardado, picker vuelve al default visual
    reset.addEventListener("click", function () {
      hidden.value = "";
      picker.value  = picker.dataset.default || "#1a1a1a";
      if (swatch) swatch.style.background = "";
      wrap.classList.remove("wpmc-color--active");
      reset.disabled = true;
    });
  });
}());
