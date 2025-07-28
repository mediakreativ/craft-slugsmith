// src/assets/js/slugsmithSettings.js

window.Slugsmith = window.Slugsmith || {};

/**
 * Opens the Slugsmith modal for creating or editing a rule
 *
 * @param {Object} rule The rule object to edit, or an empty object for a new rule
 */
function openSlugsmithRuleModal(
  rule = { id: "", name: "", search: "", replace: "" },
) {
  const siteUid = rule.siteUid ?? window.SlugsmithCurrentSiteUid;

  const siteName =
    Craft.sites?.find((site) => site.uid === siteUid)?.name ?? "";

  const titleText = rule.id
    ? Craft.t("slugsmith", "Edit Rule")
    : Craft.t("slugsmith", "Create new Rule");
  const fullTitle = siteName ? `${titleText} on ${siteName}` : titleText;

  const $modal = $(`
    <div class="modal fitted slugsmith-modal">
    <div class="header"><h1>${fullTitle}</h1></div>
      <div class="body">
        <form accept-charset="UTF-8">
          <input type="hidden" name="action" value="slugsmith/slugsmith/save-slug-rule">
          <input type="hidden" name="siteUid" value="${rule.siteUid ?? window.SlugsmithCurrentSiteUid}">
          ${rule.id ? `<input type="hidden" name="uid" value="${rule.id}">` : ""}
          <div class="field">
            <div class="heading"><label for="search">Match:</label></div>
            <div class="input"><input type="text" id="search" name="search" value="${rule.search ?? ""}" required></div>
          </div>
          <div class="field">
            <div class="heading"><label for="replace">Slug:</label></div>
            <div class="input"><input type="text" id="replace" name="replace" value="${rule.replace ?? ""}" required></div>
          </div>
          <div class="footer">
            <div class="right">
              <button type="button" class="btn cancel">Cancel</button>
              <button type="submit" class="btn submit">Save</button>
            </div>

            </div>
        </form>
      </div>
    </div>
  `);

  const modal = new Garnish.Modal($modal, {
    onHide() {
      $modal.remove();
    },
  });

  // Handle form submission and send to backend
  $modal.find("form").on("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    Craft.sendActionRequest("POST", "slugsmith/slugsmith/save-slug-rule", {
      data: formData,
    })
      .then((response) => {
        const data = response?.data;

        if (!data?.success || !data?.rule) {
          throw new Error(data?.message || "Invalid response.");
        }

        const rule = data.rule;
        const $tbody = document.querySelector(
          `.slugsmith-rules-sortable[data-site-uid="${rule.siteUid}"]`,
        );

        if (!$tbody) {
          Craft.cp.displayError(Craft.t("slugsmith", "Could not find rule list."));
          return;
        }

        const existingRow = $tbody.querySelector(`tr[data-id="${rule.uid}"]`);
        const newRowHtml = `
    <tr data-id="${rule.uid}">
      <td>
        <button type="button" class="btn-as-link slugsmith-edit-rule" data-id="${rule.uid}" data-search="${rule.search}" data-replace="${rule.replace}" data-site="${rule.siteUid}">
          <span class="slugsmith-search slugsmith-badge"><code>${rule.search}</code></span>
          <span class="arrow">→</span>
          <span class="slugsmith-replace slugsmith-badge"><code>${rule.replace}</code></span>
        </button>
      </td>
      <td class="thin slugsmith-rule-actions">
        <span class="drag-handle move icon" title="Reorder"></span>
        <a href="#" class="delete icon" title="Delete" data-id="${rule.uid}"></a>
      </td>
    </tr>
  `;

        const $newRow = $(newRowHtml);

        if (existingRow) {
          $(existingRow).replaceWith($newRow);
        } else {
          $tbody.appendChild($newRow[0]);
        }

        modal.hide();
        Craft.cp.displayNotice(Craft.t("slugsmith", "Rule saved."));
      })
      .catch((error) => {
        Craft.cp.displayError(Craft.t("slugsmith", "Could not save rule."));
      });
  });

  $modal.find(".cancel").on("click", () => modal.hide());
}

/**
 * Global click handler for rule interactions (add/edit/delete)
 */
document.addEventListener("click", (e) => {
  const newRuleBtn = e.target.closest(".slugsmith-new-rule-btn");
  if (newRuleBtn) {
    e.preventDefault();

    const siteUid =
      newRuleBtn.dataset.siteUid ?? window.SlugsmithCurrentSiteUid;
    openSlugsmithRuleModal({ siteUid });
    return;
  }

  const trigger = e.target.closest(".slugsmith-edit-rule");
  if (trigger) {
    e.preventDefault();

    const rule = {
      id: trigger.dataset.id,
      search: trigger.dataset.search || "",
      replace: trigger.dataset.replace || "",
      siteUid: trigger.dataset.site || window.SlugsmithCurrentSiteUid,
    };

    openSlugsmithRuleModal(rule);
    return;
  }

  const deleteBtn = e.target.closest(".delete[data-id]");
  if (deleteBtn) {
    e.preventDefault();

    if (confirm(Craft.t("slugsmith", "Delete this rule?"))) {
      const row = deleteBtn.closest("tr");
      if (!row) return;

      Craft.sendActionRequest("POST", "slugsmith/slugsmith/delete-slug-rule", {
        data: { uid: deleteBtn.dataset.id },
      })
        .then(() => {
          row.remove();
          Craft.cp.displayNotice(Craft.t("slugsmith", "Rule deleted."));
        })
        .catch(() => {
          Craft.cp.displayError(Craft.t("slugsmith", "Could not delete rule."));
        });
    }
  }
});

/**
 * Initializes drag-and-drop reordering for rule tables
 */
document.querySelectorAll(".slugsmith-rules-sortable").forEach((sortable) => {
  new Garnish.DragSort(sortable.children, {
    handle: ".drag-handle",
    axis: "y",
    collapseDraggees: true,
    magnetStrength: 4,
    helperLagBase: 1.5,

    onDragStart: () => {
      sortable.closest("table").classList.add("slugsmith-is-dragging");
    },

    onSortChange: function () {
      const $rows = sortable.querySelectorAll("tr");
      const ids = Array.from($rows).map((row) => row.dataset.id);

      const siteUid = sortable.getAttribute("data-site-uid");

      Craft.sendActionRequest(
        "POST",
        "slugsmith/slugsmith/reorder-slug-rules",
        {
          data: { ids, siteUid },
        },
      )
        .then(() => {
          Craft.cp.displayNotice(Craft.t("slugsmith", "Rules reordered."));
        })
        .catch(() => {
          Craft.cp.displayError(Craft.t("slugsmith", "Could not reorder rules."));
        });
    },

    onDragStop: () => {
      sortable.closest("table").classList.remove("slugsmith-is-dragging");
    },
  });
});
