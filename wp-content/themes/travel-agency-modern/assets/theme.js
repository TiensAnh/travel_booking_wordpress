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
  const pendingLoginRedirectKey = "tamPendingLoginRedirect";
  const getSessionStorage = function () {
    try {
      return window.sessionStorage;
    } catch (error) {
      return null;
    }
  };

  const setPendingLoginRedirect = function (url) {
    const storage = getSessionStorage();

    if (!storage || !url) {
      return;
    }

    storage.setItem(pendingLoginRedirectKey, url);
  };

  const consumePendingLoginRedirect = function () {
    const storage = getSessionStorage();

    if (!storage) {
      return "";
    }

    const redirectUrl = storage.getItem(pendingLoginRedirectKey) || "";

    if (redirectUrl) {
      storage.removeItem(pendingLoginRedirectKey);
    }

    return redirectUrl;
  };

  const requestLogin = function (redirectUrl) {
    if (redirectUrl) {
      setPendingLoginRedirect(redirectUrl);
    }

    const loginTrigger = document.querySelector('[data-auth-open="login"]');

    if (loginTrigger) {
      loginTrigger.click();
      return true;
    }

    return false;
  };
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
        register_phone: "Vui l\u00f2ng nh\u1eadp s\u1ed1 \u0111i\u1ec7n tho\u1ea1i.",
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
        const phoneField = form.querySelector('[name="register_phone"]');
        const passwordField = form.querySelector('[name="register_password"]');
        const confirmField = form.querySelector('[name="register_confirm_password"]');

        if (phoneField && phoneField.value && !/^[0-9]{9,11}$/.test(phoneField.value.trim())) {
          setFieldError(form, "register_phone", "S\u1ed1 \u0111i\u1ec7n tho\u1ea1i ph\u1ea3i g\u1ed3m 9-11 ch\u1eef s\u1ed1.");
          if (!firstInvalidField) {
            firstInvalidField = phoneField;
          }
          isValid = false;
        }

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
              const pendingRedirect = consumePendingLoginRedirect();
              window.location.href = pendingRedirect || payload.redirectUrl || themeConfig.redirectUrl || window.location.href;
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

        const galleryFrame = galleryMain.closest(".tam-tour-detail__gallery-frame");

        if (galleryFrame) {
          galleryFrame.style.setProperty("--tam-tour-image", "url(\"" + nextUrl.replace(/"/g, "\\\"") + "\")");
        }

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
    const departureOptionButtons = Array.from(bookingBox.querySelectorAll("[data-departure-option]"));
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

    const formatTravelDate = function (value) {
      if (!value) {
        return "";
      }

      const parts = String(value).match(/^(\d{4})-(\d{2})-(\d{2})$/);

      if (!parts) {
        return value;
      }

      return parts[3] + "/" + parts[2] + "/" + parts[1];
    };

    const getBookingDateLabel = function () {
      if (!bookingDateField || !bookingDateField.value) {
        return "";
      }

      if (bookingDateField.options) {
        const selectedOption = bookingDateField.options[bookingDateField.selectedIndex];
        return selectedOption ? selectedOption.text : bookingDateField.value;
      }

      const matchedButton = departureOptionButtons.find(function (button) {
        return button.getAttribute("data-value") === bookingDateField.value;
      });

      if (matchedButton) {
        const buttonLabel = matchedButton.getAttribute("data-label") || "";

        if (buttonLabel) {
          return buttonLabel;
        }
      }

      return formatTravelDate(bookingDateField.value);
    };

    const syncDepartureOptions = function () {
      if (!bookingDateField || !departureOptionButtons.length) {
        return;
      }

      const currentValue = bookingDateField.value || "";

      departureOptionButtons.forEach(function (button) {
        const isSelected = button.getAttribute("data-value") === currentValue;
        button.classList.toggle("is-selected", isSelected);
        button.setAttribute("aria-pressed", isSelected ? "true" : "false");
      });
    };

    const syncBookingState = function () {
      const people = Math.max(parseInt((bookingPeopleField && bookingPeopleField.value) || "1", 10) || 1, 1);
      const dateLabel = getBookingDateLabel();

      syncDepartureOptions();

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

    if (departureOptionButtons.length && bookingDateField) {
      departureOptionButtons.forEach(function (button) {
        button.addEventListener("click", function () {
          const nextValue = button.getAttribute("data-value") || "";

          if (!nextValue || bookingDateField.value === nextValue) {
            syncDepartureOptions();
            return;
          }

          bookingDateField.value = nextValue;
          bookingDateField.dispatchEvent(new Event("change", { bubbles: true }));
        });
      });
    }

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
          if (!bookingDateField || !bookingDateField.value) {
            if (bookingSummary) {
              bookingSummary.textContent = "Vui l\u00f2ng ch\u1ecdn ng\u00e0y kh\u1edfi h\u00e0nh tr\u01b0\u1edbc khi \u0111\u1eb7t tour.";
            }
            return;
          }

          const people = Math.max(parseInt((bookingPeopleField && bookingPeopleField.value) || "1", 10) || 1, 1);
          const nextUrl = new URL(checkoutUrl, window.location.origin);

          nextUrl.searchParams.set("tour_id", tourId);
          nextUrl.searchParams.set("party_size", String(people));

          if (bookingDateField && bookingDateField.value) {
            nextUrl.searchParams.set("travel_date", bookingDateField.value);
          }

          if (bookingBox.getAttribute("data-authenticated") !== "true") {
            requestLogin(nextUrl.toString());
            return;
          }

          window.location.href = nextUrl.toString();
          return;
        }

        if (inquiryTourField && !inquiryTourField.value.trim()) {
          inquiryTourField.value = tourTitle;
        }

        if (inquiryMessage && !inquiryMessage.value.trim()) {
          const people = Math.max(parseInt((bookingPeopleField && bookingPeopleField.value) || "1", 10) || 1, 1);
          const dateLabel = getBookingDateLabel();

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

  const bookingWizard = document.querySelector("[data-booking-wizard]");

  if (bookingWizard) {
    const bookingForm = bookingWizard.querySelector("[data-booking-form]");
    const stepPanels = bookingWizard.querySelectorAll("[data-step-panel]");
    const stepIndicators = bookingWizard.querySelectorAll("[data-step-indicator]");
    const progressFill = bookingWizard.querySelector("[data-booking-progress-fill]");
    const summaryCard = bookingWizard.querySelector("[data-booking-summary-card]");
    const summarySkeleton = bookingWizard.querySelector("[data-booking-summary-skeleton]");
    const couponInput = bookingWizard.querySelector("[data-booking-coupon]");
    const couponButton = bookingWizard.querySelector("[data-apply-coupon]");
    const paymentFields = bookingWizard.querySelectorAll("[data-payment-method]");
    const paymentPlanFields = bookingWizard.querySelectorAll("[data-payment-plan]");
    const payButton = bookingWizard.querySelector("[data-submit-payment]");
    const payLabel = bookingWizard.querySelector(".tam-booking-flow__pay-label");
    const payLoader = bookingWizard.querySelector(".tam-booking-flow__pay-loader");
    const reviewContactName = bookingWizard.querySelector("[data-review-contact-name]");
    const reviewContactEmail = bookingWizard.querySelector("[data-review-contact-email]");
    const reviewContactPhone = bookingWizard.querySelector("[data-review-contact-phone]");
    const reviewContactCountry = bookingWizard.querySelector("[data-review-contact-country]");
    const summaryDate = bookingWizard.querySelector("[data-summary-date]");
    const summaryHeadcount = bookingWizard.querySelector("[data-summary-headcount]");
    const summaryAdultPrice = bookingWizard.querySelector("[data-summary-adult-price]");
    const summaryChildPrice = bookingWizard.querySelector("[data-summary-child-price]");
    const summarySubtotal = bookingWizard.querySelector("[data-summary-subtotal]");
    const summaryTax = bookingWizard.querySelector("[data-summary-tax]");
    const summaryFee = bookingWizard.querySelector("[data-summary-fee]");
    const summaryDiscount = bookingWizard.querySelector("[data-summary-discount]");
    const summaryPaymentPlan = bookingWizard.querySelector("[data-summary-payment-plan]");
    const summaryPayNow = bookingWizard.querySelector("[data-summary-pay-now]");
    const summaryRemaining = bookingWizard.querySelector("[data-summary-remaining]");
    const summaryTotal = bookingWizard.querySelector("[data-summary-total]");
    const summaryStatus = bookingWizard.querySelector("[data-booking-summary-status]");
    const summaryMessage = bookingWizard.querySelector("[data-booking-summary-message]");
    const summaryTravelDate = bookingWizard.querySelector("[data-booking-summary-date]");
    const summaryTravellers = bookingWizard.querySelector("[data-booking-summary-travellers]");
    const ajaxUrl = themeConfig.ajaxUrl || window.ajaxurl || "";
    const quoteAction = themeConfig.checkoutQuoteAction || "tam_checkout_quote";
    const quoteNonce = themeConfig.checkoutQuoteNonce || "";
    const sessionAction = themeConfig.checkoutSessionAction || "tam_checkout_create_session";
    const sessionNonce = themeConfig.checkoutSessionNonce || "";
    const checkoutMessages = themeConfig.checkoutMessages || {};
    const canCheckout = bookingWizard.getAttribute("data-can-checkout") === "true";
    const basePrice = parseInt(bookingWizard.getAttribute("data-base-price") || "0", 10) || 0;
    const childPrice = parseInt(bookingWizard.getAttribute("data-child-price") || "0", 10) || Math.round(basePrice * 0.7);
    const taxRate = parseFloat(bookingWizard.getAttribute("data-tax-rate") || "0.08") || 0.08;
    const serviceFee = parseInt(bookingWizard.getAttribute("data-service-fee") || "39000", 10) || 39000;
    const depositRate = parseFloat(bookingWizard.getAttribute("data-deposit-rate") || "0.3") || 0.3;
    const accountUrl = bookingWizard.getAttribute("data-account-url") || "/";
    const stepOneFields = ["contact_name", "contact_phone", "contact_email", "contact_country", "adults_count", "children_count", "travel_date"];
    const stepThreeFields = ["accept_terms"];
    let currentStep = Math.max(1, Math.min(4, parseInt(bookingWizard.getAttribute("data-current-step") || "1", 10) || 1));
    let quoteController = null;
    let quoteSequence = 0;
    let cachedQuote = null;
    let lastQuoteSignature = "";
    let submitLocked = false;
    let checkoutRequestId = bookingWizard.getAttribute("data-request-id") || "";
    let checkoutBackendReady = true;
    const draftStorageKey = "tamCheckoutDraft";

    const saveCheckoutDraft = function () {
      const storage = getSessionStorage();

      if (!storage || !bookingForm) {
        return;
      }

      const draft = {
        tourId: bookingWizard.getAttribute("data-tour-id") || "",
        step: currentStep,
        savedAt: Date.now(),
        fields: {}
      };

      bookingForm.querySelectorAll("input, select, textarea").forEach(function (field) {
        if (!field.name) {
          return;
        }

        if (field.type === "checkbox") {
          draft.fields[field.name] = Boolean(field.checked);
          return;
        }

        if (field.type === "radio") {
          if (field.checked) {
            draft.fields[field.name] = field.value;
          }
          return;
        }

        draft.fields[field.name] = field.value;
      });

      storage.setItem(draftStorageKey, JSON.stringify(draft));
    };

    const restoreCheckoutDraft = function () {
      const storage = getSessionStorage();

      if (!storage || !bookingForm) {
        return;
      }

      const rawDraft = storage.getItem(draftStorageKey);

      if (!rawDraft) {
        return;
      }

      try {
        const draft = JSON.parse(rawDraft);

        if (!draft || String(draft.tourId || "") !== String(bookingWizard.getAttribute("data-tour-id") || "")) {
          return;
        }

        Object.keys(draft.fields || {}).forEach(function (fieldName) {
          const field = bookingForm.querySelector('[name="' + fieldName + '"]');

          if (!field) {
            return;
          }

          if (field.type === "checkbox") {
            field.checked = Boolean(draft.fields[fieldName]);
            return;
          }

          if (field.type === "radio") {
            bookingForm.querySelectorAll('[name="' + fieldName + '"]').forEach(function (radio) {
              radio.checked = radio.value === draft.fields[fieldName];
            });
            return;
          }

          field.value = draft.fields[fieldName];
        });

        if (draft.step && bookingWizard.getAttribute("data-current-step") !== "4") {
          currentStep = Math.max(1, Math.min(3, parseInt(draft.step, 10) || 1));
        }
      } catch (error) {
        storage.removeItem(draftStorageKey);
      }
    };

    const clearCheckoutDraft = function () {
      const storage = getSessionStorage();

      if (!storage) {
        return;
      }

      storage.removeItem(draftStorageKey);
    };

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

    const ensureToastRoot = function () {
      let root = document.querySelector(".tam-booking-toast-root");

      if (!root) {
        root = document.createElement("div");
        root.className = "tam-booking-toast-root";
        document.body.appendChild(root);
      }

      return root;
    };

    const showToast = function (message, tone) {
      if (!message) {
        return;
      }

      const root = ensureToastRoot();
      const toast = document.createElement("div");
      const currentTone = tone || "info";

      toast.className = "tam-booking-toast is-" + currentTone;
      toast.textContent = message;
      root.appendChild(toast);

      window.setTimeout(function () {
        toast.classList.add("is-visible");
      }, 20);

      window.setTimeout(function () {
        toast.classList.remove("is-visible");
        window.setTimeout(function () {
          toast.remove();
        }, 240);
      }, 3200);
    };

    const isMissingRouteMessage = function (message) {
      return /route not found/i.test(String(message || ""));
    };

    const normalizeCheckoutMessage = function (message, fallbackMessage) {
      if (isMissingRouteMessage(message)) {
        return checkoutMessages.backendUnavailable || "Backend checkout tạm thời chưa sẵn sàng. Vui lòng khởi động lại backend-api và tải lại trang.";
      }

      return message || fallbackMessage;
    };

    const markCheckoutBackendUnavailable = function () {
      checkoutBackendReady = false;

      if (summaryStatus) {
        summaryStatus.textContent = "Backend chua san sang";
      }

      if (summaryMessage) {
        summaryMessage.textContent = checkoutMessages.backendUnavailable || "Backend checkout tạm thời chưa sẵn sàng. Vui lòng khởi động lại backend-api và tải lại trang.";
      }

      if (payButton) {
        payButton.disabled = true;
      }
    };

    const formatPrice = function (value) {
      return new Intl.NumberFormat("vi-VN").format(Math.max(0, Math.round(Number(value || 0)))) + "đ";
    };

    const createRequestId = function () {
      if (window.crypto && typeof window.crypto.randomUUID === "function") {
        return "req_" + Date.now() + "_" + window.crypto.randomUUID();
      }

      return "req_" + Date.now() + "_" + Math.random().toString(16).slice(2, 10);
    };

    const normalizePaymentPlan = function (value) {
      return "FULL";
    };

    const getPaymentPlanLabel = function (value) {
      return "Thanh toán toàn bộ";
    };

    const resetCheckoutSessionAttempt = function () {
      checkoutRequestId = "";
      bookingWizard.removeAttribute("data-request-id");
    };

    const setSummaryLoading = function (isLoading) {
      if (!summaryCard) {
        return;
      }

      summaryCard.classList.toggle("is-loading", Boolean(isLoading));
      summaryCard.setAttribute("aria-busy", isLoading ? "true" : "false");

      if (summarySkeleton) {
        summarySkeleton.setAttribute("aria-hidden", isLoading ? "false" : "true");
      }
    };

    const getField = function (name) {
      return bookingForm ? bookingForm.querySelector('[name="' + name + '"]') : null;
    };

    const getFieldWrapper = function (name) {
      return bookingForm ? bookingForm.querySelector('[data-booking-field="' + name + '"]') : null;
    };

    const setFieldError = function (name, message) {
      const wrapper = getFieldWrapper(name);
      const field = getField(name);
      const errorNode = wrapper ? wrapper.querySelector("[data-field-error]") : null;

      if (wrapper) {
        wrapper.classList.add("is-error");
      }

      if (field) {
        field.setAttribute("aria-invalid", "true");
      }

      if (errorNode) {
        errorNode.textContent = message || "";
      }
    };

    const clearFieldError = function (name) {
      const wrapper = getFieldWrapper(name);
      const field = getField(name);
      const errorNode = wrapper ? wrapper.querySelector("[data-field-error]") : null;

      if (wrapper) {
        wrapper.classList.remove("is-error");
      }

      if (field) {
        field.setAttribute("aria-invalid", "false");
      }

      if (errorNode) {
        errorNode.textContent = "";
      }
    };

    const collectState = function () {
      const adultsCount = Math.max(parseInt((getField("adults_count") && getField("adults_count").value) || "1", 10) || 1, 1);
      const childrenCount = Math.max(parseInt((getField("children_count") && getField("children_count").value) || "0", 10) || 0, 0);
      const paymentField = bookingForm.querySelector("[data-payment-method]:checked");
      const paymentPlanField = bookingForm.querySelector("[data-payment-plan]:checked");

      return {
        tourId: bookingWizard.getAttribute("data-tour-id") || "",
        apiTourId: bookingWizard.getAttribute("data-api-tour-id") || "",
        contactName: (getField("contact_name") && getField("contact_name").value.trim()) || "",
        contactPhone: (getField("contact_phone") && getField("contact_phone").value.trim()) || "",
        contactEmail: (getField("contact_email") && getField("contact_email").value.trim()) || "",
        contactCountry: (getField("contact_country") && getField("contact_country").value.trim()) || "",
        travelDate: (getField("travel_date") && getField("travel_date").value.trim()) || "",
        adultsCount: adultsCount,
        childrenCount: childrenCount,
        travellers: adultsCount + childrenCount,
        specialRequests: (getField("special_requests") && getField("special_requests").value.trim()) || "",
        couponCode: (couponInput && couponInput.value.trim()) || "",
        paymentMethod: paymentField ? paymentField.value : bookingWizard.getAttribute("data-default-payment") || "vnpay",
        paymentPlan: normalizePaymentPlan(paymentPlanField ? paymentPlanField.value : "FULL"),
        acceptTerms: Boolean(getField("accept_terms") && getField("accept_terms").checked)
      };
    };

    const getDateLabel = function () {
      const dateField = getField("travel_date");
      const selectedOption = dateField && dateField.options ? dateField.options[dateField.selectedIndex] : null;
      return selectedOption ? selectedOption.text : (dateField ? dateField.value : "");
    };

    const buildEstimatedSummary = function (state) {
      const subtotal = (basePrice * state.adultsCount) + (childPrice * state.childrenCount);
      const taxAmount = Math.round(subtotal * taxRate);
      const feeAmount = subtotal > 0 ? serviceFee : 0;
      const totalAmount = Math.max(0, subtotal + taxAmount + feeAmount);
      const paymentPlan = normalizePaymentPlan(state.paymentPlan);
      const payableNowAmount = paymentPlan === "DEPOSIT"
        ? Math.max(0, Math.round(totalAmount * depositRate))
        : totalAmount;
      const remainingAmount = Math.max(0, totalAmount - payableNowAmount);

      return {
        pricing: {
          adultPrice: basePrice,
          childPrice: childPrice,
          subtotal: subtotal,
          taxAmount: taxAmount,
          feeAmount: feeAmount,
          discountAmount: 0,
          totalAmount: totalAmount,
          payableNowAmount: payableNowAmount,
          remainingAmount: remainingAmount,
          paymentPlan: paymentPlan
        },
        passengers: {
          adults: state.adultsCount,
          children: state.childrenCount,
          total: state.travellers
        },
        travelDate: state.travelDate,
        coupon: state.couponCode ? { code: state.couponCode } : null
      };
    };

    const renderReviewState = function (state) {
      if (reviewContactName) {
        reviewContactName.textContent = state.contactName || "--";
      }

      if (reviewContactEmail) {
        reviewContactEmail.textContent = state.contactEmail || "--";
      }

      if (reviewContactPhone) {
        reviewContactPhone.textContent = state.contactPhone || "--";
      }

      if (reviewContactCountry) {
        reviewContactCountry.textContent = state.contactCountry || "--";
      }

      if (summaryTravelDate) {
        summaryTravelDate.textContent = getDateLabel() || "--";
      }

      if (summaryTravellers) {
        summaryTravellers.textContent = String(state.travellers);
      }
    };

    const renderSummary = function (summary, options) {
      const state = collectState();
      const meta = options || {};
      const pricing = summary && summary.pricing ? summary.pricing : buildEstimatedSummary(state).pricing;
      const passengers = summary && summary.passengers ? summary.passengers : buildEstimatedSummary(state).passengers;
      const dateLabel = getDateLabel() || state.travelDate || "--";

      if (summaryDate) {
        summaryDate.textContent = dateLabel;
      }

      if (summaryHeadcount) {
        summaryHeadcount.textContent = String(passengers.total || state.travellers);
      }

      if (summaryAdultPrice) {
        summaryAdultPrice.textContent = formatPrice(pricing.adultPrice);
      }

      if (summaryChildPrice) {
        summaryChildPrice.textContent = formatPrice(pricing.childPrice);
      }

      if (summarySubtotal) {
        summarySubtotal.textContent = formatPrice(pricing.subtotal);
      }

      if (summaryTax) {
        summaryTax.textContent = formatPrice(pricing.taxAmount);
      }

      if (summaryFee) {
        summaryFee.textContent = formatPrice(pricing.feeAmount);
      }

      if (summaryDiscount) {
        summaryDiscount.textContent = "-" + formatPrice(pricing.discountAmount);
      }

      if (summaryPaymentPlan) {
        summaryPaymentPlan.textContent = getPaymentPlanLabel(pricing.paymentPlan || state.paymentPlan);
      }

      if (summaryPayNow) {
        summaryPayNow.textContent = formatPrice(pricing.payableNowAmount || pricing.totalAmount);
      }

      if (summaryRemaining) {
        summaryRemaining.textContent = formatPrice(pricing.remainingAmount || 0);
      }

      if (summaryTotal) {
        summaryTotal.textContent = formatPrice(pricing.totalAmount);
      }

      if (summaryStatus) {
        summaryStatus.textContent = meta.statusText || "Đã cập nhật";
      }

      if (summaryMessage) {
        summaryMessage.textContent = meta.message || "Gia duoc tinh theo du lieu moi nhat tu backend checkout.";
      }

      renderReviewState(state);
    };

    const updatePaymentSelection = function () {
      paymentFields.forEach(function (field) {
        const option = field.closest("[data-payment-option]");

        if (!option) {
          return;
        }

        option.classList.toggle("is-selected", field.checked);
      });

      paymentPlanFields.forEach(function (field) {
        const option = field.closest("[data-payment-plan-option]");

        if (!option) {
          return;
        }

        option.classList.toggle("is-selected", field.checked);
      });
    };

    const stripFailureQuery = function () {
      const currentUrl = new URL(window.location.href);
      let changed = false;

      ["checkout_result", "checkout_tx", "checkout_token"].forEach(function (key) {
        if (currentUrl.searchParams.has(key)) {
          currentUrl.searchParams.delete(key);
          changed = true;
        }
      });

      if (changed) {
        window.history.replaceState({}, "", currentUrl.toString());
      }
    };

    const setStep = function (nextStep) {
      currentStep = Math.max(1, Math.min(4, nextStep));
      bookingWizard.setAttribute("data-current-step", String(currentStep));
      saveCheckoutDraft();

      stepPanels.forEach(function (panel) {
        const panelStep = parseInt(panel.getAttribute("data-step-panel") || "1", 10);
        const isActive = panelStep === currentStep;
        panel.classList.toggle("is-active", isActive);
        panel.setAttribute("aria-hidden", isActive ? "false" : "true");
      });

      stepIndicators.forEach(function (indicator) {
        const indicatorStep = parseInt(indicator.getAttribute("data-step-indicator") || "1", 10);
        indicator.classList.toggle("is-active", indicatorStep === currentStep);
        indicator.classList.toggle("is-complete", indicatorStep < currentStep);
      });

      if (progressFill) {
        progressFill.style.width = String(currentStep * 25) + "%";
      }

      if (currentStep < 4) {
        stripFailureQuery();
      }

      const activePanel = bookingWizard.querySelector('[data-step-panel="' + String(currentStep) + '"]');
      const focusTarget = activePanel ? activePanel.querySelector("input, select, textarea, button") : null;

      if (focusTarget && currentStep !== 4) {
        window.setTimeout(function () {
          focusTarget.focus();
        }, 120);
      }
    };

    const validateField = function (name) {
      const state = collectState();
      const field = getField(name);

      if (!field) {
        return true;
      }

      clearFieldError(name);

      if (name === "contact_name" && !state.contactName) {
        setFieldError(name, "Vui lòng nhập họ tên.");
        return false;
      }

      if (name === "contact_phone") {
        const digits = state.contactPhone.replace(/\D/g, "");
        if (!digits) {
          setFieldError(name, "Vui lòng nhập số điện thoại.");
          return false;
        }
        if (digits.length < 9 || digits.length > 15) {
          setFieldError(name, "Số điện thoại cần từ 9 đến 15 chữ số.");
          return false;
        }
      }

      if (name === "contact_email") {
        if (!state.contactEmail) {
          setFieldError(name, "Vui lòng nhập email.");
          return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(state.contactEmail)) {
          setFieldError(name, "Email chua dung dinh dang.");
          return false;
        }
      }

      if (name === "contact_country" && !state.contactCountry) {
        setFieldError(name, "Vui lòng chọn quốc gia.");
        return false;
      }

      if (name === "travel_date" && !state.travelDate) {
        setFieldError(name, "Vui lòng chọn ngày khởi hành.");
        return false;
      }

      if (name === "adults_count") {
        if (state.adultsCount < 1) {
          setFieldError(name, "Cần ít nhất 1 người lớn.");
          return false;
        }
      }

      if (name === "children_count") {
        if (state.childrenCount < 0) {
          setFieldError(name, "Số trẻ em không hợp lệ.");
          return false;
        }
        if (state.travellers > 30) {
          setFieldError(name, "Tổng số hành khách tối đa là 30.");
          return false;
        }
      }

      if (name === "accept_terms" && !state.acceptTerms) {
        setFieldError(name, "Ban can dong y dieu khoan truoc khi thanh toan.");
        return false;
      }

      return true;
    };

    const validateFields = function (fieldNames) {
      let firstInvalid = null;

      fieldNames.forEach(function (fieldName) {
        if (!validateField(fieldName) && !firstInvalid) {
          firstInvalid = getField(fieldName);
        }
      });

      if (firstInvalid) {
        firstInvalid.focus();
      }

      return !firstInvalid;
    };

    const openLoginPrompt = function () {
      saveCheckoutDraft();
      requestLogin(window.location.href);
    };

    const validateStep = function (stepNumber) {
      if (stepNumber === 1) {
        return validateFields(stepOneFields);
      }

      if (stepNumber === 3) {
        return validateFields(stepThreeFields);
      }

      return true;
    };

    const goToNextStep = function (nextStep) {
      if (nextStep === 2) {
        if (!validateStep(1)) {
          return;
        }

        requestQuote({
          force: true,
          showToast: false,
          statusText: "Đã cập nhật",
          message: "Tổng thanh toán đã sẵn sàng cho bước xác nhận."
        })
          .catch(function () {
            return null;
          })
          .finally(function () {
            setStep(2);
          });

        return;
      }

      if (nextStep === 3) {
        if (bookingWizard.getAttribute("data-authenticated") !== "true") {
          showToast(checkoutMessages.loginRequired || "Ban can dang nhap truoc khi tiep tuc thanh toan.", "warning");
          openLoginPrompt();
          return;
        }

        if (!cachedQuote) {
          requestQuote({
            force: true,
            showToast: false
          }).catch(function () {});
        }
      }

      setStep(nextStep);
    };

    const buildQuoteSignature = function (state) {
      return [
        state.tourId,
        state.travelDate,
        state.adultsCount,
        state.childrenCount,
        state.couponCode,
        state.paymentPlan
      ].join("|");
    };

    const requestQuote = function (options) {
      const state = collectState();
      const settings = options || {};
      const signature = buildQuoteSignature(state);

      renderReviewState(state);

      if (!state.travelDate) {
        const fallbackSummary = buildEstimatedSummary(state);
        renderSummary(fallbackSummary, {
          statusText: "Cho chon ngay",
          message: "Chon ngay khoi hanh de backend tinh tong thanh toan chinh xac."
        });
        cachedQuote = fallbackSummary;
        return Promise.resolve(fallbackSummary);
      }

      if (!canCheckout || !checkoutBackendReady) {
        const localSummary = buildEstimatedSummary(state);
        renderSummary(localSummary, {
          statusText: !canCheckout ? "Can sync backend" : "Backend chua san sang",
          message: !canCheckout
            ? "Tour nay chua duoc sync sang backend, nen summary dang o che do uoc tinh."
            : (checkoutMessages.backendUnavailable || "Backend checkout tạm thời chưa sẵn sàng. Vui lòng khởi động lại backend-api và tải lại trang.")
        });
        cachedQuote = localSummary;
        return Promise.resolve(localSummary);
      }

      if (!settings.force && cachedQuote && signature === lastQuoteSignature) {
        renderSummary(cachedQuote, {
          statusText: settings.statusText || "Đã cập nhật",
          message: settings.message || "Tổng tiền đang sử dụng dữ liệu mới nhất."
        });
        return Promise.resolve(cachedQuote);
      }

      if (quoteController) {
        quoteController.abort();
      }

      quoteController = new AbortController();
      quoteSequence += 1;
      const requestIndex = quoteSequence;
      const formData = new FormData();

      formData.append("action", quoteAction);
      formData.append("nonce", quoteNonce);
      formData.append("tour_id", state.tourId);
      formData.append("travel_date", state.travelDate);
      formData.append("adults_count", String(state.adultsCount));
      formData.append("children_count", String(state.childrenCount));
      formData.append("coupon_code", state.couponCode);
      formData.append("payment_plan", state.paymentPlan);

      setSummaryLoading(true);

      return fetch(ajaxUrl, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        signal: quoteController.signal
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (payload) {
          if (requestIndex !== quoteSequence) {
            return null;
          }

          if (!payload.success) {
            const error = new Error(normalizeCheckoutMessage(payload.data && payload.data.message, checkoutMessages.genericError || "Không thể tính giá."));
            error.code = payload.data && payload.data.code ? payload.data.code : "";
            throw error;
          }

          cachedQuote = (payload.data && payload.data.summary) || buildEstimatedSummary(state);
          lastQuoteSignature = signature;
          renderSummary(cachedQuote, {
            statusText: payload.data && payload.data.couponValid === false ? "Coupon chưa hợp lệ" : "Đã cập nhật",
            message: (payload.data && payload.data.message) || "Tổng thanh toán đã được cập nhật."
          });

          if (settings.showToast && payload.data && payload.data.message) {
            showToast(payload.data.message, payload.data.couponValid === false ? "warning" : "success");
          }

          return cachedQuote;
        })
        .catch(function (error) {
          if (error && error.name === "AbortError") {
            return null;
          }

          if (error && error.code === "backend_route_missing") {
            markCheckoutBackendUnavailable();
          }

          const fallbackSummary = buildEstimatedSummary(state);
          cachedQuote = fallbackSummary;
          renderSummary(fallbackSummary, {
            statusText: error && error.code === "backend_route_missing" ? "Backend chua san sang" : "Ước tính tạm thời",
            message: normalizeCheckoutMessage(error && error.message, checkoutMessages.genericError || "Không thể tính giá lúc này.")
          });

          if (settings.showToast !== false) {
            showToast(normalizeCheckoutMessage(error && error.message, checkoutMessages.genericError || "Không thể tính giá lúc này."), "error");
          }

          throw error;
        })
        .finally(function () {
          if (requestIndex === quoteSequence) {
            setSummaryLoading(false);
          }
        });
    };

    const debouncedQuote = debounce(function () {
      requestQuote({
        showToast: false,
        statusText: "Đang cập nhật",
        message: checkoutMessages.quoteLoading || "Đang cập nhật tong thanh toan..."
      }).catch(function () {});
    }, 320);

    const setPayButtonState = function (isLoading) {
      if (!payButton) {
        return;
      }

      payButton.disabled = Boolean(isLoading) || !canCheckout;
      payButton.classList.toggle("is-loading", Boolean(isLoading));

      if (payLabel) {
        payLabel.textContent = isLoading
          ? (checkoutMessages.paymentLoading || "Đang chuyển sang cổng thanh toán...")
          : "Thanh toán ngay";
      }

      if (payLoader) {
        payLoader.setAttribute("aria-hidden", isLoading ? "false" : "true");
      }
    };

    const submitCheckout = function () {
      if (submitLocked) {
        return;
      }

      if (currentStep !== 3) {
        return;
      }

      if (!validateStep(1)) {
        setStep(1);
        showToast("Vui lòng hoàn tất thông tin khách hàng trước khi thanh toán.", "error");
        return;
      }

      if (!validateStep(3)) {
        setStep(3);
        showToast("Ban can dong y dieu khoan truoc khi thanh toan.", "error");
        return;
      }

      if (!canCheckout || !checkoutBackendReady) {
        showToast(
          !canCheckout
            ? "Tour nay chua duoc sync sang backend checkout."
            : (checkoutMessages.backendUnavailable || "Backend checkout tạm thời chưa sẵn sàng. Vui lòng khởi động lại backend-api và tải lại trang."),
          "error"
        );
        return;
      }

      if (bookingWizard.getAttribute("data-authenticated") !== "true") {
        showToast(checkoutMessages.loginRequired || "Ban can dang nhap truoc khi thanh toan.", "warning");
        openLoginPrompt();
        return;
      }

      const state = collectState();
      const retryTransactionCode = bookingWizard.getAttribute("data-last-transaction-code") || "";

      submitLocked = true;
      setPayButtonState(true);

      requestQuote({
        force: true,
        showToast: false,
        statusText: "Khoa gia cuoi cung",
        message: "Đang đồng bộ tổng thanh toán cuối cùng trước khi tạo booking."
      })
        .catch(function () {
          return null;
        })
        .then(function () {
          resetCheckoutSessionAttempt();
          checkoutRequestId = createRequestId();
          bookingWizard.setAttribute("data-request-id", checkoutRequestId);

          const formData = new FormData();
          formData.append("action", sessionAction);
          formData.append("nonce", sessionNonce);
          formData.append("tour_id", state.tourId);
          formData.append("request_id", checkoutRequestId);
          formData.append("travel_date", state.travelDate);
          formData.append("contact_name", state.contactName);
          formData.append("contact_phone", state.contactPhone);
          formData.append("contact_email", state.contactEmail);
          formData.append("contact_country", state.contactCountry);
          formData.append("special_requests", state.specialRequests);
          formData.append("payment_method", state.paymentMethod);
          formData.append("payment_plan", state.paymentPlan);
          formData.append("coupon_code", state.couponCode);
          formData.append("adults_count", String(state.adultsCount));
          formData.append("children_count", String(state.childrenCount));

          if (retryTransactionCode) {
            formData.append("retry_transaction_code", retryTransactionCode);
          }

          return fetch(ajaxUrl, {
            method: "POST",
            body: formData,
            credentials: "same-origin"
          });
        })
        .then(function (response) {
          return response.json();
        })
        .then(function (payload) {
          if (!payload.success) {
            const errorMessage = normalizeCheckoutMessage((payload.data && payload.data.message) || "", checkoutMessages.genericError || "Không thể tạo booking lúc này.");
            const errorCode = payload.data && payload.data.code ? payload.data.code : "";

            if (errorCode === "login_required") {
              bookingWizard.setAttribute("data-authenticated", "false");
              openLoginPrompt();
            }

            if (errorCode === "backend_route_missing") {
              markCheckoutBackendUnavailable();
            }

            throw new Error(errorMessage);
          }

          const redirectUrl = payload.data && payload.data.redirectUrl ? payload.data.redirectUrl : "";

          if (!redirectUrl) {
            throw new Error("Không nhận được địa chỉ redirect thanh toán.");
          }

          showToast((payload.data && payload.data.message) || "Đang chuyển sang cổng thanh toán...", "success");
          window.setTimeout(function () {
            window.location.href = redirectUrl;
          }, 220);
        })
        .catch(function (error) {
          submitLocked = false;
          setPayButtonState(false);
          showToast(normalizeCheckoutMessage(error && error.message, checkoutMessages.genericError || "Không thể tạo booking lúc này."), "error");
        });
    };

    bookingForm.querySelectorAll("input, select, textarea").forEach(function (field) {
      const eventName = field.tagName === "SELECT" || field.type === "checkbox" ? "change" : "input";

      field.addEventListener(eventName, function () {
        clearFieldError(field.name);
        renderReviewState(collectState());

        if (["adults_count", "children_count", "travel_date"].indexOf(field.name) > -1) {
          renderSummary(cachedQuote || buildEstimatedSummary(collectState()), {
            statusText: "Đang cập nhật",
            message: checkoutMessages.quoteLoading || "Đang cập nhật tong thanh toan..."
          });
          debouncedQuote();
        }
      });

      if (field.name === "contact_name" || field.name === "contact_phone" || field.name === "contact_email" || field.name === "contact_country") {
        field.addEventListener(eventName, function () {
          renderSummary(cachedQuote || buildEstimatedSummary(collectState()), {
            statusText: "Đã cập nhật",
            message: "Thông tin liên hệ đã được lưu tạm cho bước xác nhận."
          });
        });
      }
    });

    paymentFields.forEach(function (field) {
      field.addEventListener("change", function () {
        updatePaymentSelection();
      });
    });

    paymentPlanFields.forEach(function (field) {
      field.addEventListener("change", function () {
        updatePaymentSelection();
        renderSummary(cachedQuote || buildEstimatedSummary(collectState()), {
          statusText: "Đang cập nhật",
          message: checkoutMessages.quoteLoading || "Đang cập nhật tong thanh toan..."
        });
        debouncedQuote();
      });
    });

    bookingWizard.querySelectorAll("[data-step-next]").forEach(function (button) {
      button.addEventListener("click", function () {
        const nextStep = parseInt(button.getAttribute("data-step-next") || "1", 10);
        goToNextStep(nextStep);
      });
    });

    bookingWizard.querySelectorAll("[data-step-back]").forEach(function (button) {
      button.addEventListener("click", function () {
        const previousStep = parseInt(button.getAttribute("data-step-back") || "1", 10);
        setStep(previousStep);
      });
    });

    if (couponButton) {
      couponButton.addEventListener("click", function () {
        requestQuote({
          force: true,
          showToast: true
        }).catch(function () {});
      });
    }

    if (couponInput) {
      couponInput.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
          event.preventDefault();
          requestQuote({
            force: true,
            showToast: true
          }).catch(function () {});
        }
      });
    }

    if (bookingForm) {
      bookingForm.addEventListener("keydown", function (event) {
        if (event.key !== "Enter" || event.defaultPrevented) {
          return;
        }

        const target = event.target;

        if (!target || target.tagName === "TEXTAREA" || target === couponInput) {
          return;
        }

        if (target.type === "submit" || target.type === "button") {
          return;
        }

        event.preventDefault();

        if (currentStep === 1) {
          goToNextStep(2);
          return;
        }

        if (currentStep === 2) {
          goToNextStep(3);
        }
      });

      bookingForm.addEventListener("submit", function (event) {
        event.preventDefault();

        if (currentStep !== 3) {
          return;
        }

        if (event.submitter && payButton && event.submitter !== payButton) {
          return;
        }

        submitCheckout();
      });
    }

    restoreCheckoutDraft();
    if (currentStep > 1 && currentStep < 4) {
      setStep(currentStep);
    }
    updatePaymentSelection();
    renderSummary(buildEstimatedSummary(collectState()), {
      statusText: canCheckout ? "Đang đồng bộ" : "Ước tính tạm thời",
      message: canCheckout
        ? (checkoutMessages.quoteLoading || "Đang cập nhật tong thanh toan...")
        : "Tour nay chua sync sang backend, summary dang hien thi o che do uoc tinh."
    });

    if (currentStep === 4) {
      clearCheckoutDraft();
    }

    if (currentStep !== 4) {
      requestQuote({
        force: true,
        showToast: false
      }).catch(function () {});
    } else {
      setStep(4);
    }

    if (!canCheckout && payButton) {
      payButton.disabled = true;
    }
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
