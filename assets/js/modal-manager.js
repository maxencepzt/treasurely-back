class ModalManager {
    constructor(modalId = 'globalModal') {
        this.modal = document.querySelector(`[data-modal="${modalId}"]`);

        if (!this.modal) {
            console.error(`Modal avec l'ID "${modalId}" introuvable`);
            return;
        }

        this.elements = {
            container: this.modal,
            iconContainer: this.modal.querySelector('.modal-icon-container'),
            icon: this.modal.querySelector('.modal-icon'),
            title: this.modal.querySelector('.modal-title'),
            message: this.modal.querySelector('.modal-message'),
            warning: this.modal.querySelector('.modal-warning'),
            cancelBtn: this.modal.querySelector('.modal-cancel'),
            confirmBtn: this.modal.querySelector('.modal-confirm'),
            confirmIcon: this.modal.querySelector('.modal-confirm-icon'),
            confirmText: this.modal.querySelector('.modal-confirm-text')
        };

        this.currentConfig = null;
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Bouton annuler
        this.elements.cancelBtn.addEventListener('click', () => {
            this.hide();
            if (this.currentConfig?.onCancel) {
                this.currentConfig.onCancel();
            }
        });

        // Bouton confirmer
        this.elements.confirmBtn.addEventListener('click', () => {
            if (this.currentConfig?.onConfirm) {
                this.currentConfig.onConfirm(this);
            }
        });

        // Échap pour fermer
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.elements.container.classList.contains('hidden')) {
                this.hide();
            }
        });
    }

    /**
     * Affiche le modal avec une configuration personnalisée
     * @param {Object} config - Configuration du modal
     * @param {string} config.type - Type de modal : 'danger', 'warning', 'info', 'success'
     * @param {string} config.title - Titre du modal
     * @param {string} config.message - Message principal (peut contenir du HTML)
     * @param {string} config.warning - Message d'avertissement optionnel
     * @param {string} config.confirmText - Texte du bouton de confirmation
     * @param {string} config.cancelText - Texte du bouton d'annulation
     * @param {Function} config.onConfirm - Callback lors de la confirmation
     * @param {Function} config.onCancel - Callback lors de l'annulation
     * @param {Object} config.customIcon - Icône personnalisée {container : 'classes', icon : 'svg-path'}
     * @param {Object} config.customConfirmIcon - Icône personnalisée du bouton de confirmation
     */
    show(config) {
        this.currentConfig = config;

        // Définir les styles selon le type
        const types = {
            danger: {
                iconContainerClass: 'bg-red-100',
                iconClass: 'text-red-600',
                confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white',
                icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                confirmIcon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'
            },
            warning: {
                iconContainerClass: 'bg-yellow-100',
                iconClass: 'text-yellow-600',
                confirmBtnClass: 'bg-yellow-600 hover:bg-yellow-700 text-white',
                icon: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
                confirmIcon: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1'
            },
            info: {
                iconContainerClass: 'bg-blue-100',
                iconClass: 'text-blue-600',
                confirmBtnClass: 'bg-blue-600 hover:bg-blue-700 text-white',
                icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                confirmIcon: 'M5 13l4 4L19 7'
            },
            success: {
                iconContainerClass: 'bg-green-100',
                iconClass: 'text-green-600',
                confirmBtnClass: 'bg-green-600 hover:bg-green-700 text-white',
                icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                confirmIcon: 'M5 13l4 4L19 7'
            }
        };

        const typeConfig = types[config.type] || types.info;

        // Appliquer les styles de l'icône
        this.elements.iconContainer.className = `modal-icon-container w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 ${typeConfig.iconContainerClass}`;
        this.elements.icon.setAttribute('class', `modal-icon w-8 h-8 ${typeConfig.iconClass}`);

        if (config.customIcon) {
            this.elements.iconContainer.className = `modal-icon-container w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 ${config.customIcon.container}`;
            this.elements.icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${config.customIcon.icon}"></path>`;
        } else {
            this.elements.icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${typeConfig.icon}"></path>`;
        }

        // Appliquer le titre
        this.elements.title.textContent = config.title || 'Confirmation';

        // Appliquer le message (supporte HTML)
        if (typeof config.message === 'string') {
            this.elements.message.innerHTML = config.message;
        }

        // Gérer le message d'avertissement
        if (config.warning) {
            this.elements.warning.textContent = config.warning;
            this.elements.warning.classList.remove('hidden');
            this.elements.warning.className = 'modal-warning text-sm text-center mb-6';

            // Ajouter la couleur selon le type
            if (config.type === 'danger') {
                this.elements.warning.classList.add('text-red-600');
            } else if (config.type === 'warning') {
                this.elements.warning.classList.add('text-yellow-600');
            }
        } else {
            this.elements.warning.classList.add('hidden');
        }

        // Configurer le bouton de confirmation
        this.elements.confirmBtn.className = `cursor-pointer modal-confirm flex-1 px-4 py-3 font-semibold rounded-xl transition flex items-center justify-center space-x-2 ${typeConfig.confirmBtnClass}`;
        this.elements.confirmText.textContent = config.confirmText || 'Confirmer';

        if (config.customConfirmIcon) {
            this.elements.confirmIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${config.customConfirmIcon}"></path>`;
        } else {
            this.elements.confirmIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${typeConfig.confirmIcon}"></path>`;
        }

        // Configurer le bouton d'annulation
        if (config.cancelText) {
            this.elements.cancelBtn.textContent = config.cancelText;
        }

        // Afficher le modal
        this.elements.container.classList.remove('hidden');
    }

    /**
     * Cache le modal
     */
    hide() {
        this.elements.container.classList.add('hidden');
        this.resetButton();
    }

    /**
     * Affiche un état de chargement sur le bouton de confirmation
     * @param {string} loadingText - Texte à afficher pendant le chargement
     */
    setLoading(loadingText = 'En cours...') {
        this.elements.confirmBtn.disabled = true;
        this.elements.confirmBtn.dataset.originalHtml = this.elements.confirmBtn.innerHTML;
        this.elements.confirmBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>${loadingText}</span>
        `;
    }

    /**
     * Restaure l'état normal du bouton de confirmation
     */
    resetButton() {
        this.elements.confirmBtn.disabled = false;
        if (this.elements.confirmBtn.dataset.originalHtml) {
            this.elements.confirmBtn.innerHTML = this.elements.confirmBtn.dataset.originalHtml;
            delete this.elements.confirmBtn.dataset.originalHtml;
        }
    }
}

class ToastManager {
    constructor(toastId = 'globalToast') {
        this.toastId = toastId;
        this.toast = document.querySelector(`[data-toast="${toastId}"]`);

        if (!this.toast) {
            console.error(`Toast avec l'ID "${toastId}" introuvable`);
            return;
        }

        this.elements = {
            container: this.toast,
            icon: this.toast.querySelector('.toast-icon'),
            message: this.toast.querySelector('.toast-message')
        };
    }

    /**
     * Affiche un toast
     * @param {string} message - Message à afficher
     * @param {string} type - Type de toast: 'success', 'error', 'warning', 'info'
     * @param {number} duration - Durée d'affichage en millisecondes (par défaut: 3000)
     */
    show(message, type = 'success', duration = 3000) {
        const icons = {
            success: {
                container: 'bg-green-100',
                color: 'text-green-600',
                path: 'M5 13l4 4L19 7'
            },
            error: {
                container: 'bg-red-100',
                color: 'text-red-600',
                path: 'M6 18L18 6M6 6l12 12'
            },
            warning: {
                container: 'bg-yellow-100',
                color: 'text-yellow-600',
                path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
            },
            info: {
                container: 'bg-blue-100',
                color: 'text-blue-600',
                path: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
            }
        };

        const iconConfig = icons[type] || icons.info;

        this.elements.message.textContent = message;
        this.elements.icon.innerHTML = `
            <div class="w-10 h-10 ${iconConfig.container} rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 ${iconConfig.color}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconConfig.path}"></path>
                </svg>
            </div>
        `;

        this.elements.container.classList.remove('hidden');

        // Auto-hide après la durée spécifiée
        setTimeout(() => {
            this.hide();
        }, duration);
    }

    /**
     * Cache le toast
     */
    hide() {
        this.elements.container.classList.add('hidden');
    }

    // Méthodes de raccourci
    success(message, duration) {
        this.show(message, 'success', duration);
    }

    error(message, duration) {
        this.show(message, 'error', duration);
    }

    warning(message, duration) {
        this.show(message, 'warning', duration);
    }

    info(message, duration) {
        this.show(message, 'info', duration);
    }
}

export { ModalManager, ToastManager };

