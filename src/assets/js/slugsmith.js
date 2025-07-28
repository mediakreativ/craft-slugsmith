// src/assets/js/slugsmith.js

(function (window) {
  const { Craft, Garnish, $ } = window;
  if (!Craft || !Garnish || !$) {
    return;
  }

  // window.SlugsmithDebug = true;

  function debugLog(...args) {
    if (!window.SlugsmithDebug) return;
    console.log("[Slugsmith]", ...args);
  }

  /**
   * Slugsmith plugin for Craft CMS
   *
   * Advanced Slug Control for Craft CMS. Refresh & Customize Your Slugs.
   *
   * @author Christian Schindler
   * @copyright (c) 2025 Christian Schindler
   * @link https://mediakreativ.de
   * @license https://craftcms.github.io/license/ Craft License
   *
   */

  Craft.Slugsmith = Garnish.Base.extend({
    settings: {},

    /**
     * Initializes the Slugsmith functionality with provided settings
     *
     * @param {Object} config Configuration object containing settings
     */
    init: function (config) {
      const self = this;
      this.settings = config.settings || {};
    },
  });

  $(function () {
    const $titleField = $("#title");
    const $slugField = $("#slug-field");

    let $slugInput;
    let $btn;
    let isUpdatingManually = false;
    let lastExpectedSlug = "";

    /**
     * Sends the title to the backend to receive a slugified version
     * @param {string} title
     * @returns {Promise<string>}
     */
    function fetchSlugifiedTitle(title) {
      return fetch("/actions/slugsmith/slugsmith/slugify", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-Token": Craft.csrfTokenValue,
        },
        credentials: "same-origin",
        body: JSON.stringify({ title, siteHandle: getCurrentSiteHandle() }),
      })
        .then((res) => res.json())
        .then((data) => data.slug || "")
        .catch((err) => {
          console.error("[Slugsmith] Error fetching slug:", err);
          return "";
        });
    }

    /**
     * Compares the current slug to the last expected one
     * and toggles the visual state of the refresh button
     */
    function updateButtonState() {
      const currentSlug = $slugInput.val().trim();

      if (!lastExpectedSlug) {
        debugLog("No expected slug to compare.");
        return;
      }

      const isCurrent = currentSlug === lastExpectedSlug;
      debugLog(
        "Current:",
        currentSlug,
        "| Expected:",
        lastExpectedSlug,
        "| Match:",
        isCurrent,
      );

      $btn.toggleClass("is-current", isCurrent);
    }

    // Only proceed if title and slug field are present
    if ($slugField.length && $titleField.length) {
      $slugField.addClass("slugsmith-slug");
      $slugInput = $slugField.find("input");
      let autoSlugActive = false;

      // Auto-generate slug if initially empty
      if ($slugInput.val() === "") {
        autoSlugActive = true;

        (async () => {
          const title = $titleField.val();
          const slug = await fetchSlugifiedTitle(title);
          lastExpectedSlug = slug;
          $slugInput.val(slug).trigger("input").trigger("change");
          updateButtonState();
        })();
      }

      // Create and inject refresh button
      $btn = $(`
      <button class="slugsmith-slug-refresh" type="button" title="${Craft.t("slugsmith", "Refresh slug from title")}">
        <span class="icon" data-icon="refresh"></span>
      </button>
    `);

      /**
       * Handles manual slug refresh when clicking the button
       */
      $btn.on("click", async () => {
        isUpdatingManually = true;

        const title = $titleField.val();
        const slug = await fetchSlugifiedTitle(title);
        lastExpectedSlug = slug;
        $slugInput.val(slug).trigger("input").trigger("change");

        const $icon = $btn.find("span.icon");
        $btn.addClass("spin");

        setTimeout(() => {
          $btn.removeClass("spin");
          $icon.attr("data-icon", "check");

          setTimeout(() => {
            $icon.attr("data-icon", "refresh");
            isUpdatingManually = false;
            updateButtonState();
          }, 1500);
        }, 500);
      });

      $slugField.find(".heading").append($btn);

      // Initial sync if not auto mode
      (async () => {
        const title = $titleField.val();

        if (!autoSlugActive && title) {
          const slug = await fetchSlugifiedTitle(title);
          lastExpectedSlug = slug;
          updateButtonState();
        }
      })();

      updateButtonState();

      /**
       * Live update: When typing in the title field
       */
      $titleField.on("input", async () => {
        const title = $titleField.val();

        const slug = await fetchSlugifiedTitle(title);
        lastExpectedSlug = slug;

        if (autoSlugActive) {
          $slugInput.val(slug).trigger("input").trigger("change");
        }

        updateButtonState();
      });

      /**
       * Compare slug input manually (when changed)
       */
      $slugInput.on("input change", () => {
        if (!isUpdatingManually) {
          updateButtonState();
        }
      });
    }
  });

  /**
   * Determines the current site handle from URL or Craft object
   * @returns {string}
   */
  function getCurrentSiteHandle() {
    const urlParams = new URLSearchParams(window.location.search);
    const siteFromUrl = urlParams.get("site");
    const siteFromCraft =
      typeof Craft !== "undefined" && Craft.site && Craft.site.handle;

    return siteFromUrl || siteFromCraft || "default";
  }
})(window);
