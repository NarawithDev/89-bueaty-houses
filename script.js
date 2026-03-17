/* =========================
   Product Tabs
========================= */
(function () {
  function initProductTabs(root) {
    if (!root || root.dataset.tabsInit === "1") return;
    root.dataset.tabsInit = "1";

    const tabs = root.querySelectorAll(".product-tabs__tab");
    const panels = root.querySelectorAll(".product-tabs__panel");

    function activate(name) {
      tabs.forEach((t) => {
        const on = t.dataset.tab === name;
        t.classList.toggle("is-active", on);
        t.setAttribute("aria-selected", on ? "true" : "false");
      });
      panels.forEach((p) => {
        p.classList.toggle("is-active", p.dataset.panel === name);
      });
    }

    tabs.forEach((tab) => {
      tab.addEventListener("click", function () {
        // ✅ Block only if truly disabled attribute exists
        if (tab.disabled) return;
        activate(tab.dataset.tab);
      });
    });
  }

  function bootTabs() {
    document.querySelectorAll(".product-tabs").forEach(initProductTabs);
  }

  document.addEventListener("DOMContentLoaded", bootTabs);
  window.addEventListener("load", bootTabs);
  setTimeout(bootTabs, 500);
  setTimeout(bootTabs, 1500);
})();


/* =========================
   PFG (Filter + Load more)
========================= */
(function ($) {
  function initPFG($root) {
    if (!$root.length) return;

    if (typeof window.PFG === "undefined" || !PFG.ajax_url || !PFG.nonce) return;

    const $grid = $root.find(".pfg-grid");
    const $more = $root.find(".pfg-more");
    const $label = $root.find(".pfg-current");
    const $count = $root.find(".pfg-count-num");
    const $filters = $root.find(".pfg-filter");

    if (!$grid.length || !$filters.length || !$label.length || !$count.length) return;

    if ($root.data("pfg-init")) return;
    $root.data("pfg-init", 1);

    const taxonomy = $root.attr("data-taxonomy") || "category-products";
    const perPage = parseInt($root.attr("data-per-page"), 10) || 8;
    const brand = ($root.attr("data-brand") || "").toString().trim();

    let current = ($root.attr("data-current") || "all").toString();
    let page = 1;

    function setLoading(isLoading) {
      $root.toggleClass("is-loading", !!isLoading);
      $more.prop("disabled", !!isLoading);
      $filters.prop("disabled", !!isLoading);
    }

    function renderEmpty(msg) {
      $grid.html('<div class="pfg-empty">' + (msg || "ไม่พบสินค้าในหมวดหมู่นี้") + "</div>");
      $more.hide();
    }

    function setActiveUI(term) {
      $filters.removeClass("is-active");
      $filters.filter('[data-term="' + term + '"]').addClass("is-active");
    }

    function updateUrl(term, mode) {
      const url = new URL(window.location.href);

      if (term && term !== "all") {
        url.searchParams.set("category", term);
      } else {
        url.searchParams.delete("category");
      }

      if (mode === "push") {
        window.history.pushState({}, "", url);
      } else {
        window.history.replaceState({}, "", url);
      }
    }

    function fetchData(opts) {
      setLoading(true);

      return $.ajax({
        url: PFG.ajax_url,
        method: "POST",
        dataType: "json",
        cache: false,
        data: {
          action: "pfg_fetch",
          nonce: PFG.nonce,
          taxonomy: taxonomy,
          term: (opts.term || "all").toString(),
          page: opts.page || 1,
          per_page: perPage,
          brand: brand,
        },
      })
        .always(function () {
          setLoading(false);
        })
        .fail(function () {
          renderEmpty("ไม่พบสินค้าในหมวดหมู่นี้");
        });
    }

    function loadCategory(term, historyMode) {
      page = 1;
      current = (term || "all").toString();

      setActiveUI(current);
      updateUrl(current, historyMode || "replace");

      fetchData({
        term: current,
        page: 1,
      }).done(function (res) {
        if (!res || !res.success || !res.data) {
          renderEmpty("ไม่พบสินค้าในหมวดหมู่นี้");
          return;
        }

        const data = res.data;

        $label.text(data.label || "สินค้าทั้งหมด");
        $count.text(parseInt(data.total || 0, 10));

        if (data.items_html) {
          $grid.html(data.items_html);
        } else {
          renderEmpty("ไม่พบสินค้าในหมวดหมู่นี้");
        }

        if (data.has_more) {
          $more.show();
        } else {
          $more.hide();
        }
      });
    }

    $filters.on("click", function () {
      const term = ($(this).attr("data-term") || "all").toString();
      if (term === current) return;
      loadCategory(term, "push");
    });

    $more.on("click", function () {
      const nextPage = page + 1;

      fetchData({
        term: current,
        page: nextPage,
      }).done(function (res) {
        if (!res || !res.success || !res.data) return;

        const data = res.data;
        page = nextPage;

        if (data.items_html) {
          $grid.append(data.items_html);
        }

        $label.text(data.label || "สินค้าทั้งหมด");
        $count.text(parseInt(data.total || 0, 10));

        if (data.has_more) {
          $more.show();
        } else {
          $more.hide();
        }
      });
    });

    const params = new URLSearchParams(window.location.search);
    const fromUrl = (params.get("category") || "").toString().trim();

    if (fromUrl) {
      loadCategory(fromUrl, "replace");
    } else {
      loadCategory(current, "replace");
    }
  }

  function bootPFG() {
    $(".pfg").each(function () {
      initPFG($(this));
    });
  }

  $(bootPFG);
  $(window).on("load", bootPFG);

})(jQuery);