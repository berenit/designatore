

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Modale di conferma condivisa per le azioni di eliminazione in tutta l'app
// (sostituisce il confirm() nativo del browser). Vedi layouts/app.blade.php.
Alpine.store('confirmModal', {
    open: false,
    message: '',
    form: null,

    ask(message, form) {
        this.message = message;
        this.form = form;
        this.open = true;
    },

    confirm() {
        this.form?.submit();
        this.open = false;
    },

    cancel() {
        this.open = false;
        this.form = null;
    },
});

Alpine.start();
