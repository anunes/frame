const releasePageLoadTransitions = () => {
  if (document.body) {
    document.body.classList.remove("page-loading");
  }
};

if (typeof window.requestAnimationFrame === "function") {
  window.requestAnimationFrame(() => {
    window.requestAnimationFrame(releasePageLoadTransitions);
  });
} else {
  window.addEventListener("load", releasePageLoadTransitions, { once: true });
}

$(document).ready(function () {
  $(".flash-message").delay(5000).slideUp(300);

  $('[data-bs-toggle="tooltip"]').tooltip();

  // Ensure dropdown menu links always navigate (some libs may call preventDefault)
  // - Supports anchors with href
  // - Respects modifier keys (Cmd/Ctrl/Shift/middle-click)
  // - Falls back to data-href if provided
  $(document).on("click", ".dropdown-menu .dropdown-item", function (e) {
    // Ignore non-primary clicks and keyboard modifiers
    if (e.which === 2 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

    const el = this;
    const href = el.getAttribute("href") || el.getAttribute("data-href");
    if (!href || href === "#" || href.startsWith("javascript:")) return; // nothing to do

    const target = el.getAttribute("target");
    e.preventDefault(); // avoid duplicate navigation
    if (target === "_blank") {
      window.open(href, "_blank");
    } else {
      window.location.assign(href);
    }
  });

  $("#regname").on("input", function () {
    this.value = this.value.replace(/[^a-z]/g, "");
  });

  /*  new DataTable(".user-table", {
    language: {
      // url: "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-PT.json",
    },
  });*/
});
