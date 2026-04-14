const EMAIL_MASK_SELECTOR = 'input[data-mask-email="true"]';
const PHONE_RU_MASK_SELECTOR = 'input[data-mask-phone-ru="true"]';

const normalizeEmailValue = (value) => String(value ?? "")
    .replace(/\s+/g, "")
    .toLowerCase();

const normalizeRuPhoneValue = (value) => {
    let digits = String(value ?? "").replace(/\D/g, "");

    if (!digits.length) {
        return "";
    }

    if (digits.startsWith("8")) {
        digits = `7${digits.slice(1)}`;
    }

    if (!digits.startsWith("7")) {
        digits = `7${digits}`;
    }

    digits = digits.slice(0, 11);

    return `+${digits}`;
};

const formatRuPhoneMask = (value) => {
    const normalizedPhone = normalizeRuPhoneValue(value);

    if (!normalizedPhone.length) {
        return "";
    }

    const digits = normalizedPhone.slice(1);
    const localDigits = digits.slice(1);

    let masked = "+7";

    if (localDigits.length > 0) {
        masked += ` (${localDigits.slice(0, 3)}`;
    }

    if (localDigits.length >= 3) {
        masked += ")";
    }

    if (localDigits.length > 3) {
        masked += ` ${localDigits.slice(3, 6)}`;
    }

    if (localDigits.length > 6) {
        masked += `-${localDigits.slice(6, 8)}`;
    }

    if (localDigits.length > 8) {
        masked += `-${localDigits.slice(8, 10)}`;
    }

    return masked;
};

const applyEmailMask = (inputElement) => {
    if (!inputElement) {
        return;
    }

    inputElement.value = normalizeEmailValue(inputElement.value);
};

const applyRuPhoneMask = (inputElement) => {
    if (!inputElement) {
        return;
    }

    inputElement.value = formatRuPhoneMask(inputElement.value);
};

const installMaskEventListeners = () => {
    document.addEventListener("input", (event) => {
        const target = event.target;

        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches(EMAIL_MASK_SELECTOR)) {
            applyEmailMask(target);
        }

        if (target.matches(PHONE_RU_MASK_SELECTOR)) {
            applyRuPhoneMask(target);
        }
    });

    document.addEventListener("blur", (event) => {
        const target = event.target;

        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches(EMAIL_MASK_SELECTOR)) {
            applyEmailMask(target);
        }

        if (target.matches(PHONE_RU_MASK_SELECTOR)) {
            applyRuPhoneMask(target);
        }
    }, true);

    document.addEventListener("paste", (event) => {
        const target = event.target;

        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (
            !target.matches(EMAIL_MASK_SELECTOR)
            && !target.matches(PHONE_RU_MASK_SELECTOR)
        ) {
            return;
        }

        event.preventDefault();

        const pastedText = event.clipboardData?.getData("text") ?? "";

        if (target.matches(EMAIL_MASK_SELECTOR)) {
            target.value = normalizeEmailValue(pastedText);
        }

        if (target.matches(PHONE_RU_MASK_SELECTOR)) {
            target.value = formatRuPhoneMask(pastedText);
        }
    });
};

export default {
    install: () => {
        window.ShopInputMask = {
            normalizeEmailValue,
            normalizeRuPhoneValue,
            formatRuPhoneMask,
        };

        installMaskEventListeners();
    },
};
