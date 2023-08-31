window.addEventListener('DOMContentLoaded', () => {
    const selector = document.querySelector('#phone');
    const im = new Inputmask('1-999-999-9999', {
        clearMaskOnLostFocus: true,
    });

    im.mask(selector);

    const myModal = new HystModal({
        linkAttributeName: 'data-hystmodal',
        catchFocus: true,
        waitTransitions: true,
        closeOnEsc: true,
        beforeOpen: function (modal) {
            setTimeout(() => myModal.close(), 3000);
        },
    });

    ItcSubmitForm.getOrCreateInstance('#feedback-form');

    document.addEventListener('itc.successSendForm', () => {
        myModal.open('#myModal');
    });

    const elSelectControl = document.querySelector('.select-control');
    const elSelect = document.querySelector('.select-invalid');

    elSelect.addEventListener('click', () => {
        elSelectControl.classList.toggle('on')
    });
});
