(function () {
  const strings = window.tamAdminShell || {};
  const confirmTitle = strings.confirmTitle || "Xac nhan thao tac";
  const confirmButton = strings.confirmButton || "Tiep tuc";
  const cancelButton = strings.cancelButton || "Huy";
  const defaultMessage = strings.defaultMessage || "Ban co chac muon thuc hien thao tac nay khong?";

  let pendingForm = null;
  let modal = null;

  function ensureModal() {
    if (modal) {
      return modal;
    }

    modal = document.createElement("div");
    modal.className = "tam-admin-modal";
    modal.innerHTML = `
      <div class="tam-admin-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tam-admin-modal-title">
        <h3 id="tam-admin-modal-title">${confirmTitle}</h3>
        <p data-tam-modal-message>${defaultMessage}</p>
        <div class="tam-admin-modal__actions">
          <button type="button" class="button button-secondary" data-tam-modal-cancel>${cancelButton}</button>
          <button type="button" class="button button-primary" data-tam-modal-confirm>${confirmButton}</button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    modal.addEventListener("click", (event) => {
      if (event.target === modal || event.target.hasAttribute("data-tam-modal-cancel")) {
        closeModal();
      }
    });

    modal.querySelector("[data-tam-modal-confirm]").addEventListener("click", () => {
      if (!pendingForm) {
        closeModal();
        return;
      }

      pendingForm.dataset.tamConfirmed = "1";
      pendingForm.submit();
      closeModal();
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && modal.classList.contains("is-open")) {
        closeModal();
      }
    });

    return modal;
  }

  function openModal(message) {
    const activeModal = ensureModal();
    const messageNode = activeModal.querySelector("[data-tam-modal-message]");

    if (messageNode) {
      messageNode.textContent = message || defaultMessage;
    }

    activeModal.classList.add("is-open");
  }

  function closeModal() {
    if (!modal) {
      return;
    }

    modal.classList.remove("is-open");
    pendingForm = null;
  }

  function setupTourImagePreview() {
    const singleImageCard = document.querySelector("[data-tam-tour-image]");

    if (singleImageCard) {
      const input = singleImageCard.querySelector("[data-tam-tour-image-input]");
      const preview = singleImageCard.querySelector("[data-tam-tour-image-preview]");
      const placeholder = singleImageCard.querySelector("[data-tam-tour-image-placeholder]");
      const frame = singleImageCard.querySelector("[data-tam-tour-image-frame]");

      if (!(input instanceof HTMLInputElement) || !preview || !placeholder) {
        return;
      }

      const existingSrc = preview.getAttribute("data-existing-src") || "";
      let objectUrl = "";

      function cleanupObjectUrl() {
        if (objectUrl) {
          URL.revokeObjectURL(objectUrl);
          objectUrl = "";
        }
      }

      function showImage(src) {
        preview.setAttribute("src", src);
        preview.hidden = false;
        placeholder.hidden = true;

        if (frame) {
          frame.classList.add("has-image");
        }
      }

      function showPlaceholder() {
        preview.hidden = true;
        placeholder.hidden = false;

        if (frame) {
          frame.classList.remove("has-image");
        }
      }

      function restoreOriginal() {
        cleanupObjectUrl();

        if (existingSrc) {
          showImage(existingSrc);
          return;
        }

        preview.removeAttribute("src");
        showPlaceholder();
      }

      input.addEventListener("change", () => {
        cleanupObjectUrl();

        const file = input.files && input.files[0];

        if (!file) {
          restoreOriginal();
          return;
        }

        objectUrl = URL.createObjectURL(file);
        showImage(objectUrl);
      });

      window.addEventListener("beforeunload", cleanupObjectUrl, { once: true });
      return;
    }

    const slots = Array.from(document.querySelectorAll("[data-tam-tour-slot]"));

    if (!slots.length) {
      return;
    }

    const cleanupCallbacks = [];

    slots.forEach((slot) => {
      const input = slot.querySelector("[data-tam-tour-slot-input]");
      const preview = slot.querySelector("[data-tam-tour-slot-preview]");
      const placeholder = slot.querySelector("[data-tam-tour-slot-placeholder]");
      const frame = slot.querySelector("[data-tam-tour-slot-frame]");

      if (!(input instanceof HTMLInputElement) || !preview || !placeholder) {
        return;
      }

      const existingSrc = preview.getAttribute("data-existing-src") || "";
      let objectUrl = "";

      function cleanupObjectUrl() {
        if (objectUrl) {
          URL.revokeObjectURL(objectUrl);
          objectUrl = "";
        }
      }

      function showImage(src) {
        preview.setAttribute("src", src);
        preview.hidden = false;
        placeholder.hidden = true;

        if (frame) {
          frame.classList.add("has-image");
        }
      }

      function showPlaceholder() {
        preview.hidden = true;
        placeholder.hidden = false;

        if (frame) {
          frame.classList.remove("has-image");
        }
      }

      function restoreOriginal() {
        cleanupObjectUrl();

        if (existingSrc) {
          showImage(existingSrc);
          return;
        }

        preview.removeAttribute("src");
        showPlaceholder();
      }

      input.addEventListener("change", () => {
        cleanupObjectUrl();

        const file = input.files && input.files[0];

        if (!file) {
          restoreOriginal();
          return;
        }

        objectUrl = URL.createObjectURL(file);
        showImage(objectUrl);
      });

      cleanupCallbacks.push(cleanupObjectUrl);
    });

    window.addEventListener("beforeunload", () => {
      cleanupCallbacks.forEach((cleanup) => cleanup());
    });
  }

  function setupItineraryBuilder() {
    const builders = Array.from(document.querySelectorAll("[data-tam-itinerary-builder]"));

    if (!builders.length) {
      return;
    }

    function cleanValue(value) {
      return String(value || "").replace(/\|/g, " ").trim();
    }

    function getItemValues(item) {
      const label = item.querySelector("[data-tam-itinerary-label]");
      const title = item.querySelector("[data-tam-itinerary-title]");
      const description = item.querySelector("[data-tam-itinerary-description]");

      return {
        label: label instanceof HTMLInputElement ? cleanValue(label.value) : "",
        title: title instanceof HTMLInputElement ? cleanValue(title.value) : "",
        description: description instanceof HTMLTextAreaElement ? cleanValue(description.value) : "",
      };
    }

    function syncOutput(builder) {
      const output = builder.querySelector("[data-tam-itinerary-output]");
      const items = Array.from(builder.querySelectorAll("[data-tam-itinerary-item]"));

      if (!(output instanceof HTMLTextAreaElement)) {
        return;
      }

      output.value = items
        .map((item, index) => {
          const values = getItemValues(item);

          const isDefaultDayLabel = /^(ngày|ngay|day)\s*\d+$/i.test(values.label);

          if (!values.title && !values.description && (!values.label || isDefaultDayLabel)) {
            return "";
          }

          return [values.label || `Ngày ${index + 1}`, values.title, values.description].join(" | ");
        })
        .filter(Boolean)
        .join("\n");
    }

    function updateIndexes(builder) {
      const items = Array.from(builder.querySelectorAll("[data-tam-itinerary-item]"));

      items.forEach((item, index) => {
        const dayNumber = index + 1;
        const badge = item.querySelector("[data-tam-itinerary-badge]");
        const label = item.querySelector("[data-tam-itinerary-label]");
        const title = item.querySelector("[data-tam-itinerary-title]");
        const description = item.querySelector("[data-tam-itinerary-description]");

        if (badge) {
          badge.textContent = `NGÀY ${dayNumber}`;
        }

        if (label instanceof HTMLInputElement) {
          label.placeholder = `Ngày ${dayNumber}`;

        }

        if (title instanceof HTMLInputElement) {
          title.placeholder = `Tiêu đề ngày ${dayNumber}`;
        }

        if (description instanceof HTMLTextAreaElement) {
          description.placeholder = `Mô tả nhịp trải nghiệm ngày ${dayNumber}`;
        }
      });
    }

    function createItem(builder) {
      const list = builder.querySelector("[data-tam-itinerary-list]");

      if (!list) {
        return null;
      }

      const dayNumber = list.querySelectorAll("[data-tam-itinerary-item]").length + 1;
      const item = document.createElement("article");
      item.className = "tam-admin-itinerary__item";
      item.setAttribute("data-tam-itinerary-item", "");
      item.innerHTML = `
        <div class="tam-admin-itinerary__badge" data-tam-itinerary-badge>NGÀY ${dayNumber}</div>
        <div class="tam-admin-itinerary__card">
          <div class="tam-admin-itinerary__card-head">
            <input class="tam-admin-itinerary__title" type="text" value="" placeholder="Tiêu đề ngày ${dayNumber}" data-tam-itinerary-title />
            <button type="button" class="tam-admin-itinerary__remove" data-tam-itinerary-remove>Xóa</button>
          </div>
          <input class="tam-admin-itinerary__label" type="text" value="" placeholder="Ngày ${dayNumber}" data-tam-itinerary-label />
          <textarea class="tam-admin-itinerary__description" rows="4" placeholder="Mô tả nhịp trải nghiệm ngày ${dayNumber}" data-tam-itinerary-description></textarea>
        </div>
      `;

      list.appendChild(item);
      updateIndexes(builder);
      syncOutput(builder);

      const title = item.querySelector("[data-tam-itinerary-title]");

      if (title instanceof HTMLInputElement) {
        title.focus();
      }

      return item;
    }

    builders.forEach((builder) => {
      const addButton = builder.querySelector("[data-tam-itinerary-add]");

      updateIndexes(builder);
      syncOutput(builder);

      if (addButton) {
        addButton.addEventListener("click", () => {
          createItem(builder);
        });
      }

      builder.addEventListener("input", (event) => {
        if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
          syncOutput(builder);
        }
      });

      builder.addEventListener("click", (event) => {
        const removeButton = event.target instanceof Element ? event.target.closest("[data-tam-itinerary-remove]") : null;

        if (!removeButton) {
          return;
        }

        const item = removeButton.closest("[data-tam-itinerary-item]");
        const list = builder.querySelector("[data-tam-itinerary-list]");

        if (!item || !list) {
          return;
        }

        item.remove();

        if (!list.querySelector("[data-tam-itinerary-item]")) {
          createItem(builder);
        }

        updateIndexes(builder);
        syncOutput(builder);
      });

      const form = builder.closest("form");

      if (form) {
        form.addEventListener("submit", () => syncOutput(builder));
      }
    });
  }
  document.addEventListener("submit", (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    const message = form.getAttribute("data-tam-confirm");

    if (!message || form.dataset.tamConfirmed === "1") {
      if (form.dataset.tamConfirmed === "1") {
        delete form.dataset.tamConfirmed;
      }
      return;
    }

    event.preventDefault();
    pendingForm = form;
    openModal(message);
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => { setupTourImagePreview(); setupItineraryBuilder(); }, { once: true });
  } else {
    setupTourImagePreview();
    setupItineraryBuilder();
  }
})();
