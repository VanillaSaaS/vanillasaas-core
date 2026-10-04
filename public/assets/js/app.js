/* =============================================================================
   public/assets/js/app.js — OPTIONAL PROGRESSIVE ENHANCEMENTS
   =============================================================================

   The app works completely WITHOUT this file. Every form is a normal HTML
   form that posts to PHP. This script only makes things nicer:

     1. "Show / Hide" buttons on password fields
     2. A confirmation prompt on forms marked data-confirm="Question?"
     3. Disabling submit buttons on forms marked data-once (no double posts)

   It's loaded with <script defer> from our own domain, which our
   Content-Security-Policy allows. Inline <script> blocks would be refused —
   so put new behaviour in this file (or another .js file), never inline.
   ========================================================================== */

(() => {
  "use strict";

  /* ---------------------------------------------------------------------------
     1. Password visibility toggles
     ------------------------------------------------------------------------ */
  // For each password input wrapped in .password-wrap, add a button that
  // switches the input between type="password" and type="text".
  document.querySelectorAll(".password-wrap").forEach((wrap) => {
    const input = wrap.querySelector("input");
    if (!input) return;

    const button = document.createElement("button");
    button.type = "button"; // never submits the form
    button.className = "password-toggle";
    button.textContent = "Show";
    button.setAttribute("aria-controls", input.id);
    button.setAttribute("aria-pressed", "false");
    button.setAttribute("aria-label", "Show password");

    button.addEventListener("click", () => {
      const showing = input.type === "text";
      input.type = showing ? "password" : "text";
      button.textContent = showing ? "Show" : "Hide";
      button.setAttribute("aria-pressed", String(!showing));
      button.setAttribute("aria-label", showing ? "Show password" : "Hide password");
      input.focus();
    });

    wrap.appendChild(button);
  });

  /* ---------------------------------------------------------------------------
     2 & 3. Form submit behaviour
     ------------------------------------------------------------------------ */
  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", (event) => {
      // 2. Ask first, for destructive actions.
      const question = form.dataset.confirm;
      if (question && !window.confirm(question)) {
        event.preventDefault();
        return;
      }

      // 3. Prevent a nervous double-click from submitting twice.
      if (form.hasAttribute("data-once")) {
        if (form.dataset.submitting === "true") {
          event.preventDefault();
          return;
        }
        form.dataset.submitting = "true";
        form.querySelectorAll('button[type="submit"]').forEach((btn) => {
          btn.setAttribute("aria-busy", "true");
        });
      }
    });
  });

  // If the user comes BACK to this page with the browser's Back button, the
  // page may be restored from cache with buttons still "busy". Reset them.
  window.addEventListener("pageshow", () => {
    document.querySelectorAll("form[data-submitting]").forEach((form) => {
      delete form.dataset.submitting;
      form.querySelectorAll('[aria-busy="true"]').forEach((btn) => btn.removeAttribute("aria-busy"));
    });
  });
})();
