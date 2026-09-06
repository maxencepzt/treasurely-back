import { ToastManager } from 'modal-manager';
import { FileDragDrop } from 'file-drag-drop';
import { RiddleManager } from 'riddle-manager';

/**
 * Formulaire de création et de modification d'une chasse. Le formulaire porte son URL
 * de soumission et son jeton CSRF en attributs ; la réponse du serveur donne l'URL de
 * redirection, le navigateur n'a rien à deviner.
 */
class HuntFormManager {
    constructor() {
        this.form = document.querySelector('form[data-hunt-form]');
        this.imageInput = document.getElementById('hunt_image');
        this.imagePreview = document.getElementById('hunt_image_preview');
        this.toast = new ToastManager('globalToast');
        this.riddleManager = new RiddleManager({ notify: (message) => this.toast.error(message) });

        this.init();
    }

    init() {
        if (!this.form) {
            console.error('Formulaire de chasse introuvable.');
            return;
        }

        if (this.imageInput) {
            this.fileDragDrop = new FileDragDrop(this.imageInput, {
                onFileSelected: (file) => this.updateImagePreview(file),
                onError: (message) => this.toast.error(message),
                allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                maxSize: 5 * 1024 * 1024,
                previewElement: this.imagePreview,
            });
        }

        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submit(e.submitter?.value || 'draft');
        });

        this.attachValidationListeners();
    }

    attachValidationListeners() {
        const description = document.getElementById('hunt_description');
        const hint = document.getElementById('hunt_description_hint');
        if (description && hint) {
            const max = parseInt(description.getAttribute('maxlength'), 10);
            description.addEventListener('input', () => {
                hint.textContent = `${max - description.value.length} caractères restants`;
            });
        }
    }

    updateImagePreview(file) {
        if (!this.imagePreview) {
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            this.imagePreview.innerHTML = '';
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Aperçu';
            img.className = 'w-full h-full object-cover rounded-xl';
            this.imagePreview.appendChild(img);
        };
        reader.readAsDataURL(file);
    }

    /**
     * Ne bloque que ce que le navigateur ne peut pas signaler seul ; le serveur reste
     * l'autorité et ses messages sont affichés tels quels.
     */
    validate() {
        const errors = [];
        if (document.querySelectorAll('.hunt-type-checkbox:checked').length === 0) {
            errors.push('Sélectionnez au moins un type de chasse.');
        }
        if (this.riddleManager.getRiddlesData().length === 0) {
            errors.push('Une chasse contient au moins une énigme.');
        }
        return errors;
    }

    buildFormData(action) {
        const value = (id) => document.getElementById(id).value.trim();
        const formData = new FormData();

        formData.append('_token', this.form.dataset.csrfToken);
        formData.append('name', value('hunt_name'));
        formData.append('description', value('hunt_description'));
        formData.append('designer_team_id', value('hunt_team'));
        formData.append('difficulty', value('hunt_difficulty'));
        formData.append('estimated_duration', value('hunt_duration'));
        formData.append('city', value('hunt_city'));
        formData.append('action', action);

        const huntTypes = Array.from(document.querySelectorAll('.hunt-type-checkbox:checked')).map((cb) => cb.value);
        formData.append('hunt_types', JSON.stringify(huntTypes));
        formData.append('riddles', JSON.stringify(this.riddleManager.getRiddlesData()));

        const image = this.fileDragDrop?.getFile();
        if (image) {
            formData.append('image', image);
        }

        return formData;
    }

    async submit(action) {
        if (!this.form.reportValidity()) {
            return;
        }
        const errors = this.validate();
        if (errors.length > 0) {
            this.toast.error(errors.join(' '));
            return;
        }

        try {
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: this.buildFormData(action),
                headers: { Accept: 'application/json' },
            });
            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(result.error || 'Une erreur est survenue lors de l\'enregistrement.');
            }

            this.toast.success(result.message || 'Chasse enregistrée.');
            window.setTimeout(() => {
                window.location.href = result.url;
            }, 1000);
        } catch (error) {
            this.toast.error(error.message);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => new HuntFormManager());
