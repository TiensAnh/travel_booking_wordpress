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
})();
