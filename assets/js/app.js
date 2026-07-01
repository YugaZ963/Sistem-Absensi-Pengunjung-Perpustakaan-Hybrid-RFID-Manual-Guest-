(function () {
    var searchPanel = document.getElementById("panel-search");
    var nimInput = document.getElementById("nim_key");
    var rfidInput = document.getElementById("rfid_key");
    var minScannerLength = 4;
    var autoSubmitDelayMs = 120;
    var keyBuffer = "";
    var keyBufferTimer = null;
    var inputSubmitTimer = null;
    var isSubmitting = false;

    if (!searchPanel || !rfidInput) {
        return;
    }

    function isSearchTabActive() {
        return !searchPanel.classList.contains("hidden");
    }

    function isEditableElement(element) {
        if (!element || !(element instanceof HTMLElement)) {
            return false;
        }

        if (element.isContentEditable) {
            return true;
        }

        var tagName = element.tagName;
        return tagName === "INPUT" || tagName === "TEXTAREA" || tagName === "SELECT";
    }

    function normalizeValue(value) {
        return String(value || "").replace(/[\r\n\t]/g, "").trim();
    }

    function clearBufferTimer() {
        if (keyBufferTimer) {
            clearTimeout(keyBufferTimer);
            keyBufferTimer = null;
        }
    }

    function clearInputSubmitTimer() {
        if (inputSubmitTimer) {
            clearTimeout(inputSubmitTimer);
            inputSubmitTimer = null;
        }
    }

    function submitSearch(value) {
        var finalValue = normalizeValue(value);
        var form;

        if (isSubmitting) {
            return;
        }

        if (finalValue.length < minScannerLength) {
            return;
        }

        rfidInput.value = finalValue;
        form = rfidInput.form;
        if (!form) {
            return;
        }

        isSubmitting = true;
        clearBufferTimer();
        clearInputSubmitTimer();

        if (typeof form.requestSubmit === "function") {
            form.requestSubmit();
            return;
        }
        form.submit();
    }

    function flushScannerBuffer() {
        var value = normalizeValue(keyBuffer);
        keyBuffer = "";
        clearBufferTimer();
        submitSearch(value);
    }

    function scheduleFlush() {
        clearBufferTimer();
        keyBufferTimer = setTimeout(flushScannerBuffer, 150);
    }

    function scheduleInputSubmit() {
        clearInputSubmitTimer();
        inputSubmitTimer = setTimeout(function () {
            submitSearch(rfidInput.value);
        }, autoSubmitDelayMs);
    }

    function keepSearchFocus() {
        if (document.hidden || !isSearchTabActive()) {
            return;
        }

        if (document.activeElement === rfidInput || document.activeElement === nimInput) {
            return;
        }

        if (isEditableElement(document.activeElement)) {
            return;
        }

        rfidInput.focus();
    }

    keepSearchFocus();
    setInterval(keepSearchFocus, 700);

    rfidInput.addEventListener("keydown", function (event) {
        if (!isSearchTabActive()) {
            return;
        }
        if (event.key === "Enter" || event.key === "NumpadEnter" || event.key === "Tab") {
            event.preventDefault();
            submitSearch(rfidInput.value);
        }
    });

    rfidInput.addEventListener("input", function () {
        if (!isSearchTabActive()) {
            return;
        }
        scheduleInputSubmit();
    });

    rfidInput.addEventListener("paste", function () {
        if (!isSearchTabActive()) {
            return;
        }
        setTimeout(scheduleInputSubmit, 0);
    });

    document.addEventListener("keydown", function (event) {
        if (!isSearchTabActive()) {
            return;
        }
        if (event.ctrlKey || event.altKey || event.metaKey) {
            return;
        }

        if (isEditableElement(event.target) && event.target !== rfidInput) {
            return;
        }

        if (event.key === "Enter" || event.key === "NumpadEnter" || event.key === "Tab") {
            if (keyBuffer !== "") {
                event.preventDefault();
                flushScannerBuffer();
                return;
            }
            if (event.target === rfidInput) {
                event.preventDefault();
                submitSearch(rfidInput.value);
            }
            return;
        }

        if (event.key.length !== 1) {
            return;
        }

        keyBuffer += event.key;
        scheduleFlush();
    });

    window.addEventListener("pageshow", function () {
        isSubmitting = false;
    });
})();
