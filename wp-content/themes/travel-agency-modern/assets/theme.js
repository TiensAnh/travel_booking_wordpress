document.addEventListener("DOMContentLoaded", function () {
  const body = document.body;
  const menuToggle = document.querySelector("[data-menu-toggle]");
  const menuClose = document.querySelector("[data-menu-close]");
  const mobilePanel = document.querySelector("[data-mobile-panel]");

  const closeMenu = () => {
    body.classList.remove("is-menu-open");
    if (menuToggle) {
      menuToggle.setAttribute("aria-expanded", "false");
    }
  };

  const openMenu = () => {
    body.classList.add("is-menu-open");
    if (menuToggle) {
      menuToggle.setAttribute("aria-expanded", "true");
    }
  };

  if (menuToggle && mobilePanel) {
    menuToggle.addEventListener("click", function () {
      if (body.classList.contains("is-menu-open")) {
        closeMenu();
      } else {
        openMenu();
      }
    });
  }

  if (menuClose) {
    menuClose.addEventListener("click", closeMenu);
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeMenu();
    }
  });

  window.addEventListener("resize", function () {
    if (window.innerWidth > 1024) {
      closeMenu();
    }
  });

  const themeConfig = window.tamTheme || {};
  const authModal = document.querySelector("[data-auth-modal]");
  const authTriggers = document.querySelectorAll("[data-auth-open]");

  if (authModal && authTriggers.length) {
    const authTabs = authModal.querySelectorAll(".tam-auth-modal__tab");
    const authTabTriggers = authModal.querySelectorAll("[data-auth-tab]");
    const authPanels = authModal.querySelectorAll("[data-auth-panel]");
    const authForms = authModal.querySelectorAll("[data-auth-form]");
    const authCloseButtons = authModal.querySelectorAll("[data-auth-close]");
    let lastFocusedElement = null;
    let shouldRestoreFocusOnClose = false;

    const clearFieldError = function (field) {
      const wrapper = field.closest("[data-auth-field]");
      const errorNode = authModal.querySelector('[data-auth-error-for="' + field.name + '"]');

      if (wrapper) {
        wrapper.classList.remove("is-error");
      }

      field.setAttribute("aria-invalid", "false");

      if (errorNode) {
        errorNode.textContent = "";
      }
    };

    const setFieldError = function (form, fieldName, message) {
      const field = form.querySelector('[name="' + fieldName + '"]');
      const errorNode = form.querySelector('[data-auth-error-for="' + fieldName + '"]');

      if (!field) {
        return;
      }

      const wrapper = field.closest("[data-auth-field]");

      if (wrapper) {
        wrapper.classList.add("is-error");
      }

      field.setAttribute("aria-invalid", "true");

      if (errorNode) {
        errorNode.textContent = message;
      }
    };

    const clearFormErrors = function (form) {
      form.querySelectorAll("[data-auth-field] input").forEach(function (field) {
        clearFieldError(field);
      });
    };

    const showFormMessage = function (form, type, message) {
      const messageNode = form.querySelector("[data-auth-message]");

      if (!messageNode) {
        return;
      }

      if (!message) {
        messageNode.textContent = "";
        messageNode.classList.remove("is-visible", "is-error", "is-success");
        return;
      }

      messageNode.textContent = message;
      messageNode.classList.add("is-visible");
      messageNode.classList.toggle("is-error", type === "error");
      messageNode.classList.toggle("is-success", type === "success");
    };

    const toggleFormLoading = function (form, isLoading) {
      const submitButton = form.querySelector("[data-auth-submit]");
      const submitLabel = form.querySelector(".tam-auth-form__submit-label");

      form.classList.toggle("is-loading", isLoading);

      if (!submitButton || !submitLabel) {
        return;
      }

      if (!submitButton.hasAttribute("data-default-text")) {
        submitButton.setAttribute("data-default-text", submitLabel.textContent.trim());
      }

      if (isLoading) {
        submitButton.disabled = true;
        submitLabel.textContent = submitButton.getAttribute("data-loading-text") || submitLabel.textContent;
      } else {
        submitButton.disabled = false;
        submitLabel.textContent = submitButton.getAttribute("data-default-text") || submitLabel.textContent;
      }
    };

    const focusActiveField = function () {
      const activePanel = authModal.querySelector('[data-auth-panel="' + (authModal.getAttribute("data-auth-state") || "login") + '"]');

      if (!activePanel) {
        return;
      }

      const nextField = activePanel.querySelector("input:not([type='hidden'])");

      if (nextField) {
        nextField.focus();
      }
    };

    const setAuthTab = function (tabName, shouldFocus) {
      const nextTab = tabName === "register" ? "register" : "login";

      authModal.setAttribute("data-auth-state", nextTab);

      authTabs.forEach(function (tab) {
        const isActive = tab.getAttribute("data-auth-tab") === nextTab;
        tab.classList.toggle("is-active", isActive);
        tab.setAttribute("aria-selected", isActive ? "true" : "false");
        tab.setAttribute("tabindex", isActive ? "0" : "-1");
      });

      authPanels.forEach(function (panel) {
        const isActive = panel.getAttribute("data-auth-panel") === nextTab;
        panel.setAttribute("aria-hidden", isActive ? "false" : "true");
      });

      if (shouldFocus) {
        window.setTimeout(focusActiveField, 180);
      }
    };

    const openAuthModal = function (tabName, restoreFocusOnClose) {
      lastFocusedElement = document.activeElement;
      shouldRestoreFocusOnClose = Boolean(restoreFocusOnClose);
      closeMenu();
      authForms.forEach(function (form) {
        clearFormErrors(form);
        showFormMessage(form, "", "");
        toggleFormLoading(form, false);
      });
      authModal.hidden = false;
      authModal.setAttribute("aria-hidden", "false");
      setAuthTab(tabName, false);

      window.requestAnimationFrame(function () {
        body.classList.add("is-auth-modal-open");
        authModal.classList.add("is-active");
        window.setTimeout(focusActiveField, 180);
      });
    };

    const closeAuthModal = function () {
      if (authModal.hidden) {
        return;
      }

      body.classList.remove("is-auth-modal-open");
      authModal.classList.remove("is-active");
      authModal.setAttribute("aria-hidden", "true");

      window.setTimeout(function () {
        authModal.hidden = true;
      }, 260);

      if (lastFocusedElement) {
        if (shouldRestoreFocusOnClose && typeof lastFocusedElement.focus === "function") {
          lastFocusedElement.focus();
        } else if (typeof lastFocusedElement.blur === "function") {
          lastFocusedElement.blur();
        }
      }

      shouldRestoreFocusOnClose = false;
    };

    const validateAuthForm = function (form) {
      const formType = form.getAttribute("data-auth-form");
      const requiredFields = form.querySelectorAll("input[required]");
      const messages = {
        login_email: "Vui l\u00f2ng nh\u1eadp email.",
        login_password: "Vui l\u00f2ng nh\u1eadp m\u1eadt kh\u1ea9u.",
        register_name: "Vui l\u00f2ng nh\u1eadp h\u1ecd t\u00ean.",
        register_email: "Vui l\u00f2ng nh\u1eadp email.",
        register_password: "Vui l\u00f2ng nh\u1eadp m\u1eadt kh\u1ea9u.",
        register_confirm_password: "Vui l\u00f2ng x\u00e1c nh\u1eadn m\u1eadt kh\u1ea9u."
      };
      let isValid = true;
      let firstInvalidField = null;

      clearFormErrors(form);
      showFormMessage(form, "", "");

      requiredFields.forEach(function (field) {
        const value = field.value.trim();

        if (!value) {
          setFieldError(form, field.name, messages[field.name] || "Vui l\u00f2ng ho\u00e0n t\u1ea5t tr\u01b0\u1eddng n\u00e0y.");
          if (!firstInvalidField) {
            firstInvalidField = field;
          }
          isValid = false;
          return;
        }

        if (field.type === "email") {
          const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

          if (!emailPattern.test(value)) {
            setFieldError(form, field.name, "Email ch\u01b0a \u0111\u00fang \u0111\u1ecbnh d\u1ea1ng.");
            if (!firstInvalidField) {
              firstInvalidField = field;
            }
            isValid = false;
          }
        }
      });

      if (formType === "register") {
        const passwordField = form.querySelector('[name="register_password"]');
        const confirmField = form.querySelector('[name="register_confirm_password"]');

        if (passwordField && passwordField.value && passwordField.value.length < 6) {
          setFieldError(form, "register_password", "M\u1eadt kh\u1ea9u c\u1ea7n t\u1ed1i thi\u1ec3u 6 k\u00fd t\u1ef1.");
          if (!firstInvalidField) {
            firstInvalidField = passwordField;
          }
          isValid = false;
        }

        if (passwordField && confirmField && passwordField.value !== confirmField.value) {
          setFieldError(form, "register_confirm_password", "M\u1eadt kh\u1ea9u x\u00e1c nh\u1eadn ch\u01b0a kh\u1edbp.");
          if (!firstInvalidField) {
            firstInvalidField = confirmField;
          }
          isValid = false;
        }
      }

      if (!isValid) {
        showFormMessage(form, "error", "Vui l\u00f2ng ki\u1ec3m tra l\u1ea1i th\u00f4ng tin b\u1ea1n v\u1eeba nh\u1eadp.");

        if (firstInvalidField) {
          firstInvalidField.focus();
        }
      }

      return isValid;
    };

    authTriggers.forEach(function (trigger) {
      trigger.addEventListener("click", function (event) {
        openAuthModal(trigger.getAttribute("data-auth-open"), event.detail === 0);
      });
    });

    authCloseButtons.forEach(function (button) {
      button.addEventListener("click", closeAuthModal);
    });

    authTabTriggers.forEach(function (button) {
      button.addEventListener("click", function () {
        setAuthTab(button.getAttribute("data-auth-tab"), true);
      });
    });

    authModal.querySelectorAll("input").forEach(function (field) {
      field.addEventListener("input", function () {
        clearFieldError(field);
      });
      field.addEventListener("blur", function () {
        if (field.value.trim()) {
          clearFieldError(field);
        }
      });
    });

    authModal.querySelectorAll("[data-password-toggle]").forEach(function (toggle) {
      toggle.addEventListener("click", function () {
        const control = toggle.closest(".tam-auth-form__control");
        const field = control ? control.querySelector("input") : null;
        const icon = toggle.querySelector("i");

        if (!field) {
          return;
        }

        const shouldShow = field.getAttribute("type") === "password";
        field.setAttribute("type", shouldShow ? "text" : "password");
        toggle.setAttribute("aria-label", shouldShow ? "\u1ea8n m\u1eadt kh\u1ea9u" : "Hi\u1ec7n m\u1eadt kh\u1ea9u");

        if (icon) {
          icon.classList.toggle("fa-eye", !shouldShow);
          icon.classList.toggle("fa-eye-slash", shouldShow);
        }
      });
    });

    authForms.forEach(function (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();

        if (!validateAuthForm(form)) {
          return;
        }

        const endpoint = themeConfig.ajaxUrl || window.ajaxurl;

        if (!endpoint) {
          showFormMessage(form, "error", "Kh\u00f4ng t\u1ea3i \u0111\u01b0\u1ee3c d\u1ecbch v\u1ee5 x\u00e1c th\u1ef1c. Vui l\u00f2ng th\u1eed l\u1ea1i sau.");
          return;
        }

        toggleFormLoading(form, true);

        fetch(endpoint, {
          method: "POST",
          credentials: "same-origin",
          body: new FormData(form)
        })
          .then(function (response) {
            return response.json().catch(function () {
              return {};
            }).then(function (payload) {
              if (!response.ok || !payload.success) {
                throw payload.data || { message: "Thao t\u00e1c ch\u01b0a th\u1ec3 ho\u00e0n t\u1ea5t. Vui l\u00f2ng th\u1eed l\u1ea1i." };
              }

              return payload.data || {};
            });
          })
          .then(function (payload) {
            showFormMessage(form, "success", payload.message || "Thao t\u00e1c th\u00e0nh c\u00f4ng.");

            if (payload.switchTab) {
              const targetForm = authModal.querySelector('[data-auth-form="' + payload.switchTab + '"]');
              setAuthTab(payload.switchTab, true);

              if (targetForm) {
                showFormMessage(targetForm, "success", payload.message || "Thao t\u00e1c th\u00e0nh c\u00f4ng.");
              }

              if (payload.prefillEmail && targetForm) {
                const emailField = targetForm.querySelector("input[type='email']");

                if (emailField) {
                  emailField.value = payload.prefillEmail;
                }
              }

              return;
            }

            window.setTimeout(function () {
              window.location.href = payload.redirectUrl || themeConfig.redirectUrl || window.location.href;
            }, Number(themeConfig.successDelay || 900));
          })
          .catch(function (payload) {
            const errors = payload && payload.errors ? payload.errors : {};
            const message = payload && payload.message ? payload.message : "Thao t\u00e1c ch\u01b0a th\u1ec3 ho\u00e0n t\u1ea5t. Vui l\u00f2ng th\u1eed l\u1ea1i.";
            const errorKeys = Object.keys(errors);

            showFormMessage(form, "error", message);

            errorKeys.forEach(function (fieldName) {
              setFieldError(form, fieldName, errors[fieldName]);
            });

            if (errorKeys.length) {
              const firstField = form.querySelector('[name="' + errorKeys[0] + '"]');

              if (firstField) {
                firstField.focus();
              }
            }
          })
          .finally(function () {
            toggleFormLoading(form, false);
          });
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeAuthModal();
      }
    });
  }

  const galleryMain = document.querySelector("[data-tour-gallery-main]");
  const galleryThumbs = document.querySelectorAll("[data-tour-gallery-thumb]");

  if (galleryMain && galleryThumbs.length) {
    galleryThumbs.forEach(function (thumb) {
      thumb.addEventListener("click", function () {
        const nextUrl = thumb.getAttribute("data-image-url");
        const nextAlt = thumb.getAttribute("data-image-alt") || "";

        if (!nextUrl) {
          return;
        }

        galleryMain.setAttribute("src", nextUrl);
        galleryMain.setAttribute("alt", nextAlt);

        galleryThumbs.forEach(function (item) {
          item.classList.remove("is-active");
        });

        thumb.classList.add("is-active");
      });
    });
  }

  const bookingBox = document.querySelector("[data-tour-booking-box]");

  if (bookingBox) {
    const bookingDateField = bookingBox.querySelector("[data-booking-date]");
    const bookingPeopleField = bookingBox.querySelector("[data-booking-people]");
    const bookingTotal = bookingBox.querySelector("[data-booking-total]");
    const bookingSubmit = bookingBox.querySelector("[data-booking-submit]");
    const bookingSummary = document.querySelector("[data-booking-summary]");
    const inquiryMessage = document.querySelector("[data-tour-inquiry-message]");
    const inquiryDate = document.querySelector("[data-tour-inquiry-date]");
    const inquiryParty = document.querySelector("[data-tour-inquiry-party]");
    const inquiryTourField = document.querySelector("[data-tour-interest-field]");
    const inquirySection = document.getElementById("tour-inquiry");
    const tourTitle = bookingBox.getAttribute("data-tour-title") || "";
    const tourId = bookingBox.getAttribute("data-tour-id") || "";
    const checkoutUrl = bookingBox.getAttribute("data-checkout-url") || "";
    const basePrice = parseInt(bookingBox.getAttribute("data-base-price") || "0", 10);

    const formatPrice = function (value) {
      if (!value) {
        return "Li\u00ean h\u1ec7";
      }

      return new Intl.NumberFormat("vi-VN").format(value) + "d";
    };

    const syncBookingState = function () {
      const people = Math.max(parseInt((bookingPeopleField && bookingPeopleField.value) || "1", 10) || 1, 1);
      const selectedOption = bookingDateField && bookingDateField.options ? bookingDateField.options[bookingDateField.selectedIndex] : null;
      const dateLabel = selectedOption ? selectedOption.text : bookingDateField ? bookingDateField.value : "";

      if (bookingTotal) {
        bookingTotal.textContent = formatPrice(basePrice > 0 ? basePrice * people : 0);
      }

      if (inquiryDate) {
        inquiryDate.value = dateLabel;
      }

      if (inquiryParty) {
        inquiryParty.value = String(people);
      }

      if (bookingSummary) {
        bookingSummary.textContent = dateLabel
          ? "B\u1ea1n \u0111ang ch\u1ecdn " + dateLabel + " cho " + people + " kh\u00e1ch. \u0110\u1ec3 l\u1ea1i th\u00f4ng tin b\u00ean d\u01b0\u1edbi, ADN Travel s\u1ebd gi\u1eef ch\u1ed7 v\u00e0 g\u1ecdi l\u1ea1i x\u00e1c nh\u1eadn."
          : "Ch\u1ecdn ng\u00e0y kh\u1edfi h\u00e0nh v\u00e0 s\u1ed1 l\u01b0\u1ee3ng kh\u00e1ch \u1edf box b\u00ean ph\u1ea3i \u0111\u1ec3 \u0111\u1ed9i ng\u0169 chu\u1ea9n b\u1ecb b\u00e1o gi\u00e1 ch\u00ednh x\u00e1c h\u01a1n.";
      }
    };

    if (bookingDateField) {
      bookingDateField.addEventListener("change", syncBookingState);
    }

    if (bookingPeopleField) {
      bookingPeopleField.addEventListener("input", syncBookingState);
      bookingPeopleField.addEventListener("change", syncBookingState);
    }

    if (bookingSubmit) {
      bookingSubmit.addEventListener("click", function () {
        syncBookingState();

        if (checkoutUrl && tourId) {
          const people = Math.max(parseInt((bookingPeopleField && bookingPeopleField.value) || "1", 10) || 1, 1);
          const nextUrl = new URL(checkoutUrl, window.location.origin);

          nextUrl.searchParams.set("tour_id", tourId);
          nextUrl.searchParams.set("party_size", String(people));

          if (bookingDateField && bookingDateField.value) {
            nextUrl.searchParams.set("travel_date", bookingDateField.value);
          }

          window.location.href = nextUrl.toString();
          return;
        }

        if (inquiryTourField && !inquiryTourField.value.trim()) {
          inquiryTourField.value = tourTitle;
        }

        if (inquiryMessage && !inquiryMessage.value.trim()) {
          const people = Math.max(parseInt((bookingPeopleField && bookingPeopleField.value) || "1", 10) || 1, 1);
          const selectedOption = bookingDateField && bookingDateField.options ? bookingDateField.options[bookingDateField.selectedIndex] : null;
          const dateLabel = selectedOption ? selectedOption.text : bookingDateField ? bookingDateField.value : "";

          inquiryMessage.value = dateLabel
            ? "T\u00f4i mu\u1ed1n \u0111\u1eb7t tour " + tourTitle + " v\u00e0o " + dateLabel + " cho " + people + " kh\u00e1ch. Nh\u1edd ADN Travel t\u01b0 v\u1ea5n v\u00e0 gi\u1eef ch\u1ed7 gi\u00fap t\u00f4i."
            : "T\u00f4i mu\u1ed1n \u0111\u1eb7t tour " + tourTitle + ". Nh\u1edd ADN Travel t\u01b0 v\u1ea5n v\u00e0 gi\u1eef ch\u1ed7 gi\u00fap t\u00f4i.";
        }

        if (inquirySection) {
          inquirySection.scrollIntoView({ behavior: "smooth", block: "start" });
        }

        if (inquiryMessage) {
          window.setTimeout(function () {
            inquiryMessage.focus();
          }, 250);
        }
      });
    }

    syncBookingState();
  }

  const checkoutForm = document.querySelector("[data-checkout-form]");

  if (checkoutForm) {
    const summaryPeople = checkoutForm.querySelector("[data-checkout-summary-people]");
    const summaryDate = checkoutForm.querySelector("[data-checkout-summary-date]");
    const summaryPayment = checkoutForm.querySelector("[data-checkout-summary-payment]");
    const summaryTotal = checkoutForm.querySelector("[data-checkout-total]");
    const peopleField = checkoutForm.querySelector("[data-checkout-people]");
    const dateField = checkoutForm.querySelector("[data-checkout-date]");
    const paymentFields = checkoutForm.querySelectorAll("[data-checkout-payment]");
    const errorBox = checkoutForm.querySelector("[data-checkout-error]");

    const updatePaymentSelection = function () {
      paymentFields.forEach(function (field) {
        const wrapper = field.closest("[data-checkout-payment-option]");

        if (!wrapper) {
          return;
        }

        wrapper.classList.toggle("is-selected", field.checked);

        if (field.checked && summaryPayment) {
          const copy = wrapper.querySelector(".tam-checkout__payment-copy strong");
          summaryPayment.textContent = copy ? copy.textContent.trim() : field.value;
        }
      });
    };

    const syncCheckoutSummary = function () {
      const basePrice = parseInt((summaryTotal && summaryTotal.getAttribute("data-base-price")) || "0", 10);
      const people = Math.max(parseInt((peopleField && peopleField.value) || "1", 10) || 1, 1);
      const selectedOption = dateField && dateField.options ? dateField.options[dateField.selectedIndex] : null;
      const dateLabel = selectedOption ? selectedOption.text : dateField ? dateField.value : "";

      if (summaryPeople) {
        summaryPeople.textContent = String(people);
      }

      if (summaryDate && dateLabel) {
        summaryDate.textContent = dateLabel;
      }

      if (summaryTotal) {
        summaryTotal.textContent = basePrice > 0 ? new Intl.NumberFormat("vi-VN").format(basePrice * people) + "d" : "Li\u00ean h\u1ec7";
      }

      updatePaymentSelection();
    };

    const clearCheckoutErrors = function () {
      checkoutForm.querySelectorAll(".is-error").forEach(function (node) {
        node.classList.remove("is-error");
      });

      if (errorBox) {
        errorBox.textContent = "";
        errorBox.classList.remove("is-visible");
      }
    };

    const validateField = function (field) {
      const wrapper = field.closest("[data-checkout-field]");
      let isValid = true;

      if (field.type === "checkbox") {
        isValid = field.checked;
      } else {
        isValid = field.checkValidity();
      }

      if (wrapper) {
        wrapper.classList.toggle("is-error", !isValid);
      }

      field.setAttribute("aria-invalid", isValid ? "false" : "true");

      return isValid;
    };

    checkoutForm.querySelectorAll("[data-checkout-required]").forEach(function (field) {
      const eventName = field.type === "checkbox" || field.tagName === "SELECT" ? "change" : "blur";
      field.addEventListener(eventName, function () {
        validateField(field);
      });
    });

    if (peopleField) {
      peopleField.addEventListener("input", syncCheckoutSummary);
      peopleField.addEventListener("change", syncCheckoutSummary);
    }

    if (dateField) {
      dateField.addEventListener("change", syncCheckoutSummary);
    }

    paymentFields.forEach(function (field) {
      field.addEventListener("change", syncCheckoutSummary);
    });

    checkoutForm.addEventListener("submit", function (event) {
      clearCheckoutErrors();

      const requiredFields = checkoutForm.querySelectorAll("[data-checkout-required]");
      let firstInvalid = null;

      requiredFields.forEach(function (field) {
        if (!validateField(field) && !firstInvalid) {
          firstInvalid = field;
        }
      });

      if (firstInvalid) {
        event.preventDefault();

        if (errorBox) {
          errorBox.textContent = "Vui l\u00f2ng ho\u00e0n t\u1ea5t \u0111\u1ea7y \u0111\u1ee7 th\u00f4ng tin b\u1eaft bu\u1ed9c tr\u01b0\u1edbc khi thanh to\u00e1n.";
          errorBox.classList.add("is-visible");
        }

        firstInvalid.focus();
      }
    });

    syncCheckoutSummary();
  }

  const tourFilterForm = document.querySelector("[data-tour-filter-form]");
  const tourResults = document.querySelector("[data-tour-results]");

  if (tourFilterForm && tourResults) {
    const searchInput = tourFilterForm.querySelector("[data-tour-search-input]");
    const destinationSelect = tourFilterForm.querySelector("[data-tour-destination-select]");
    const resetButton = tourFilterForm.querySelector("[data-tour-filter-reset]");
    const statusNode = tourFilterForm.querySelector("[data-tour-filter-status]");
    const resultsSurface = tourResults.querySelector("[data-tour-results-surface]");
    const resultsContent = tourResults.querySelector("[data-tour-results-content]");
    const resultsOverlay = tourResults.querySelector("[data-tour-results-overlay]");
    const endpoint = themeConfig.ajaxUrl || window.ajaxurl;
    const actionName = themeConfig.tourFilterAction || "tam_filter_tours";
    const requestNonce = themeConfig.tourFilterNonce || "";
    const pageUrl = tourFilterForm.getAttribute("data-page-url") || window.location.href;
    const searchDelay = Math.max(300, Math.min(500, parseInt(themeConfig.tourSearchDelay || "380", 10) || 380));
    const minimumLoadTime = Math.max(500, Math.min(1000, parseInt(themeConfig.tourSearchMinLoad || "650", 10) || 650));
    let activeController = null;
    let activeRequestId = 0;
    let lastAppliedState = "";

    const debounce = function (callback, delay) {
      let timeoutId = 0;

      return function () {
        const args = arguments;

        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(function () {
          callback.apply(null, args);
        }, delay);
      };
    };

    const parsePageFromUrl = function (urlValue) {
      try {
        const url = new URL(urlValue, window.location.origin);
        const paged = parseInt(url.searchParams.get("paged") || url.searchParams.get("page") || "1", 10);

        if (paged > 0) {
          return paged;
        }

        const pathMatch = url.pathname.match(/\/page\/(\d+)\/?$/);
        return pathMatch ? parseInt(pathMatch[1], 10) || 1 : 1;
      } catch (error) {
        return 1;
      }
    };

    const setStatus = function (message) {
      if (statusNode && message) {
        statusNode.textContent = message;
      }
    };

    const getState = function (pageOverride) {
      return {
        search_tour: searchInput ? searchInput.value.trim() : "",
        destination: destinationSelect ? destinationSelect.value : "",
        paged: Math.max(1, parseInt(String(pageOverride || "1"), 10) || 1)
      };
    };

    const getStateKey = function (state) {
      return [state.search_tour, state.destination, state.paged].join("::");
    };

    const updateResetButton = function () {
      if (!resetButton) {
        return;
      }

      resetButton.disabled = !(searchInput && searchInput.value.trim()) && !(destinationSelect && destinationSelect.value);
    };

    const updateHistory = function (state) {
      const url = new URL(pageUrl, window.location.origin);

      if (state.search_tour) {
        url.searchParams.set("search_tour", state.search_tour);
      }

      if (state.destination) {
        url.searchParams.set("destination", state.destination);
      }

      if (state.paged > 1) {
        url.searchParams.set("paged", String(state.paged));
      }

      window.history.replaceState({ tamTourFilters: state }, "", url.toString());
    };

    const setLoading = function (isLoading, source) {
      if (resultsSurface) {
        resultsSurface.classList.toggle("is-loading", isLoading);
        resultsSurface.setAttribute("aria-busy", isLoading ? "true" : "false");
      }

      if (resultsOverlay) {
        resultsOverlay.classList.toggle("is-visible", isLoading);
        resultsOverlay.setAttribute("aria-hidden", isLoading ? "false" : "true");
      }

      if (!isLoading) {
        return;
      }

      if (source === "filter") {
        setStatus("Đang lọc tour theo điểm đến...");
        return;
      }

      if (source === "pagination") {
        setStatus("Đang tải thêm tour phù hợp...");
        return;
      }

      if (source === "reset") {
        setStatus("Đang xóa bộ lọc và làm mới danh sách tour...");
        return;
      }

      setStatus("Đang tìm tour phù hợp...");
    };

    const animateResults = function () {
      if (!resultsContent) {
        return;
      }

      resultsContent.classList.remove("is-animating");
      void resultsContent.offsetWidth;
      resultsContent.classList.add("is-animating");

      window.setTimeout(function () {
        resultsContent.classList.remove("is-animating");
      }, 520);
    };

    const fetchResults = function (source, page, forceRefresh) {
      if (!endpoint || !requestNonce || !resultsContent) {
        return;
      }

      const state = getState(page);
      const stateKey = getStateKey(state);

      updateResetButton();

      if (!forceRefresh && stateKey === lastAppliedState) {
        updateHistory(state);
        return;
      }

      if (activeController) {
        activeController.abort();
      }

      activeController = typeof AbortController !== "undefined" ? new AbortController() : null;
      activeRequestId += 1;

      const currentRequestId = activeRequestId;
      const requestBody = new URLSearchParams();

      requestBody.set("action", actionName);
      requestBody.set("nonce", requestNonce);
      requestBody.set("search_tour", state.search_tour);
      requestBody.set("destination", state.destination);
      requestBody.set("paged", String(state.paged));
      requestBody.set("page_url", pageUrl);

      setLoading(true, source);

      const minimumDelay = new Promise(function (resolve) {
        window.setTimeout(resolve, minimumLoadTime);
      });

      const requestPromise = fetch(endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
        },
        body: requestBody.toString(),
        signal: activeController ? activeController.signal : undefined
      })
        .then(function (response) {
          return response.json().catch(function () {
            return {};
          }).then(function (payload) {
            if (!response.ok || !payload.success || !payload.data) {
              throw payload.data || { message: "Không thể tải kết quả tour. Vui lòng thử lại." };
            }

            return payload.data;
          });
        });

      Promise.all([requestPromise, minimumDelay])
        .then(function (values) {
          const payload = values[0];

          if (currentRequestId !== activeRequestId) {
            return;
          }

          resultsContent.innerHTML = payload.html || "";
          lastAppliedState = stateKey;
          updateResetButton();
          updateHistory(state);
          setStatus(payload.summary || "Đã cập nhật kết quả tour.");
          animateResults();
        })
        .catch(function (error) {
          if (error && error.name === "AbortError") {
            return;
          }

          setStatus((error && error.message) || "Không thể tải kết quả tour. Vui lòng thử lại.");
        })
        .finally(function () {
          if (currentRequestId !== activeRequestId) {
            return;
          }

          setLoading(false, source);
          activeController = null;
        });
    };

    const debouncedSearch = debounce(function () {
      fetchResults("search", 1, false);
    }, searchDelay);

    lastAppliedState = getStateKey(getState(parsePageFromUrl(window.location.href)));
    updateResetButton();

    tourFilterForm.addEventListener("submit", function (event) {
      event.preventDefault();
      fetchResults("search", 1, true);
    });

    if (searchInput) {
      searchInput.addEventListener("input", function () {
        debouncedSearch();
      });
    }

    if (destinationSelect) {
      destinationSelect.addEventListener("change", function () {
        fetchResults("filter", 1, true);
      });
    }

    if (resetButton) {
      resetButton.addEventListener("click", function () {
        if (searchInput) {
          searchInput.value = "";
        }

        if (destinationSelect) {
          destinationSelect.value = "";
        }

        fetchResults("reset", 1, true);
      });
    }

    if (resultsContent) {
      resultsContent.addEventListener("click", function (event) {
        const paginationLink = event.target.closest(".tam-pagination a");

        if (!paginationLink) {
          return;
        }

        event.preventDefault();
        fetchResults("pagination", parsePageFromUrl(paginationLink.href), true);
      });
    }

    window.addEventListener("popstate", function () {
      const currentUrl = new URL(window.location.href);
      const nextState = {
        search_tour: currentUrl.searchParams.get("search_tour") || "",
        destination: currentUrl.searchParams.get("destination") || "",
        paged: parsePageFromUrl(currentUrl.toString())
      };

      if (searchInput) {
        searchInput.value = nextState.search_tour;
      }

      if (destinationSelect) {
        destinationSelect.value = nextState.destination;
      }

      fetchResults("pagination", nextState.paged, true);
    });
  }
});
