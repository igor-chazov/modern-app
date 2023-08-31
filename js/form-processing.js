class ItcSubmitForm {

    static instances = [];

    static getOrCreateInstance(target, config) {
        const elForm = typeof target === 'string' ? document.querySelector(target) : target;
        const found = this.instances.find(el => el.target === elForm);

        if (found) {
            return found.instance;
        }

        const form = new this(elForm, config);
        this.instances.push({ target: elForm, instance: form });

        return this;
    }

    constructor(target, config = {}) {
        this._isCheckValidationOnClient = config['isCheckValidationOnClient'] !== false;
        this._elForm = target;
        this._init();
    }

    _setStateValidaion(input, state, message) {
        const className = state === 'success' ? 'is-valid' : 'is-invalid';
        const text = state === 'success' ? '' : message;

        input.classList.remove('is-valid');
        input.classList.remove('is-invalid');
        input.closest('.form-group').querySelector('.invalid-feedback').textContent = '';

        if (state === 'error' || state === 'success') {
            input.classList.add(className);
            input.closest('.form-group').querySelector('.invalid-feedback').textContent = text;
        }
    }

    _checkValidity() {
        let valid = true;

        this._elForm.querySelectorAll('input, textarea, select').forEach(el => {

            if (el.checkValidity()) {
                this._setStateValidaion(el, 'success');
            } else {
                this._setStateValidaion(el, 'error', el.validationMessage);
                valid = false;
            }
        })

        return valid;
    }

    _getFormData() {
        const formData = new FormData(this._elForm);

        return formData;
    };

    _successXHR(data) {
        this._elForm.querySelectorAll('input, textarea,select').forEach(el => {
            this._setStateValidaion(el);
        });

        if (data['result'] === 'success') {
            this._elForm.dispatchEvent(new Event('itc.successSendForm', { bubbles: true }));
            return;
        }

        this._elForm.querySelector('.form-error').classList.add('form-error_hide');
        // this._elForm.querySelector('.form-error').classList.remove('form-error_hidden');

        if (!Object.keys(data['errors']).length) {
            this._elForm.querySelector('.form-error').textContent = 'При отправке сообщения произошла ошибка. Пожалуйста, попробуйте ещё раз позже.';
        } else {
            this._elForm.querySelector('.form-error').textContent = 'В форме содержатся ошибки!';
        }

        this._elForm.querySelector('.form-error').classList.remove('form-error_hide');

        for (let key in data['errors']) {
            const el = this._elForm.querySelector('[name="' + key + '"]');
            el ? this._setStateValidaion(el, 'error', data['errors'][key]) : null;
        }

        this._elForm.querySelectorAll('input:not(.is-invalid), textarea:not(.is-invalid), select:not(.is-invalid)').forEach(el => {
            this._setStateValidaion(el, 'success', '');
        })

        data['logs'].forEach((message) => {
            console.log(message);
        });

        const elInvalid = this._elForm.querySelector('.is-invalid');
        if (elInvalid) {
            elInvalid.focus();
        }
    }

    _errorXHR() {
        this._elForm.querySelector('.form-error').classList.remove('d-none');
    }

    _onSubmit() {
        this._elForm.dispatchEvent(new Event('before-send'));

        if (this._isCheckValidationOnClient) {

            if (!this._checkValidity()) {
                const elInvalid = this._elForm.querySelector('.is-invalid');

                if (elInvalid) {
                    elInvalid.focus();
                }

                return;
            }
        }

        const submitWidth = this._elForm.querySelector('[type="submit"]').getBoundingClientRect().width;
        const submitHeight = this._elForm.querySelector('[type="submit"]').getBoundingClientRect().height;
        this._elForm.querySelector('[type="submit"]').textContent = '';
        this._elForm.querySelector('[type="submit"]').disabled = true;
        this._elForm.querySelector('[type="submit"]').style.width = `${submitWidth}px`;
        this._elForm.querySelector('[type="submit"]').style.height = `${submitHeight}px`;

        this._elForm.querySelector('.form-error').classList.add('form-error_hide');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', this._elForm.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.responseType = 'json';
        xhr.onload = () => {
            this._elForm.querySelector('[type="submit"]').textContent = this._submitText;
            this._elForm.querySelector('[type="submit"]').disabled = false;
            this._elForm.querySelector('[type="submit"]').style.width = '';
            this._elForm.querySelector('[type="submit"]').style.height = '';
            if (xhr.status == 200) {
                this._successXHR(xhr.response);
            } else {
                this._errorXHR();
            }
        }
        xhr.send(this._getFormData());
    };

    _init() {
        this._submitText = this._elForm.querySelector('[type="submit"]').textContent;
        this._addEventListener();
    }

    _addEventListener() {
        this._elForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this._onSubmit();
        });
    }

    reset() {
        if (this._elForm.querySelector('.form-error')) {
            this._elForm.querySelector('.form-error').classList.add('form-error_hide');
        }

        this._elForm.reset();

        this._elForm.querySelectorAll('input, textarea select').forEach(el => {
            this._setStateValidaion(el);
        });
    }
}
