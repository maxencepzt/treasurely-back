import { ToastManager } from 'modal-manager';
import { FileDragDrop } from 'file-drag-drop';
import { RiddleManager } from "riddle-manager";

class HuntFormManager {
    constructor() {
        this.huntForm = document.querySelector('form[data-hunt-form]');
        this.imageInput = document.getElementById('hunt_image');
        this.imagePreview = document.querySelector('.w-24.h-24');

        // Initialiser le ToastManager
        this.toast = new ToastManager('globalToast');

        // Initialiser le RiddleManager
        this.riddleManager = new RiddleManager();

        // Déterminer le mode (création ou édition)
        this.mode = this.huntForm?.dataset.mode || 'create';
        this.huntId = this.huntForm?.dataset.huntId || this.extractHuntIdFromUrl();

        this.init();
    }

    extractHuntIdFromUrl() {
        // Extraire l'ID de la chasse depuis l'URL pour le mode édition
        const match = window.location.pathname.match(/\/hunt\/(\d+)\/edit/);
        return match ? parseInt(match[1]) : null;
    }

    init() {
        if (!this.huntForm) {
            console.error('Formulaire de chasse non trouvé dans le DOM');
            return;
        }

        // Initialiser le drag and drop pour l'image
        if (this.imageInput) {
            this.fileDragDrop = new FileDragDrop(this.imageInput, {
                onFileSelected: (file) => {
                    console.log('Fichier sélectionné:', file.name);
                    this.updateImagePreview(file);
                },
                onError: (message) => this.showError(message),
                allowedTypes: ['image/jpeg', 'image/png', 'image/gif'],
                maxSize: 5 * 1024 * 1024, // 5MB
                previewElement: this.imagePreview
            });
        }

        if (this.riddleManager) {

            if (this.mode === 'edit') {
                // Charger les énigmes existantes pour l'édition
                const existingRiddlesData = this.huntForm.dataset.existingRiddles;
                const existingRiddles = existingRiddlesData ? JSON.parse(existingRiddlesData) : [];

                this.riddleManager.loadRiddles(existingRiddles);
            }
        }

        // Attacher les événements
        this.attachEventListeners();
    }

    attachEventListeners() {
        // Gestion de la soumission du formulaire
        this.huntForm.addEventListener('submit', (e) => {
            e.preventDefault();

            // Récupérer l'action (draft ou publish)
            const submitButton = e.submitter;
            const action = submitButton?.value || 'draft';

            this.submitHunt(action);
        });

        // Validation en temps réel
        this.attachValidationListeners();
    }

    attachValidationListeners() {
        const huntName = document.getElementById('hunt_name');
        const huntDescription = document.getElementById('hunt_description');
        const huntDuration = document.getElementById('hunt_duration');

        if (huntName) {
            huntName.addEventListener('blur', () => {
                if (!huntName.value.trim()) {
                    this.showFieldError(huntName, 'Le nom de la chasse est requis');
                } else {
                    this.clearFieldError(huntName);
                }
            });
        }

        if (huntDescription) {
            huntDescription.addEventListener('blur', () => {
                if (!huntDescription.value.trim()) {
                    this.showFieldError(huntDescription, 'La description est requise');
                } else if (huntDescription.value.length > 3000) {
                    this.showFieldError(huntDescription, 'La description ne doit pas dépasser 3000 caractères');
                } else {
                    this.clearFieldError(huntDescription);
                }
            });

            // Compteur de caractères
            huntDescription.addEventListener('input', () => {
                const remaining = 3000 - huntDescription.value.length;
                const hint = huntDescription.parentElement.querySelector('.text-gray-500');
                if (hint) {
                    if (remaining < 0) {
                        hint.textContent = `Dépassement de ${Math.abs(remaining)} caractères`;
                        hint.classList.add('text-red-500');
                        hint.classList.remove('text-gray-500');
                    } else {
                        hint.textContent = `${remaining} caractères restants`;
                        hint.classList.remove('text-red-500');
                        hint.classList.add('text-gray-500');
                    }
                }
            });
        }

        if (huntDuration) {
            huntDuration.addEventListener('blur', () => {
                const value = parseInt(huntDuration.value);
                if (isNaN(value) || value < 15) {
                    this.showFieldError(huntDuration, 'La durée minimale est de 15 minutes');
                } else {
                    this.clearFieldError(huntDuration);
                }
            });
        }
    }

    showFieldError(field, message) {
        field.classList.add('border-red-500', 'focus:ring-red-500');
        field.classList.remove('border-gray-300', 'focus:ring-green-500');

        // Ajouter un message d'erreur si pas déjà présent
        let errorMsg = field.parentElement.querySelector('.field-error');
        if (!errorMsg) {
            errorMsg = document.createElement('p');
            errorMsg.className = 'field-error mt-1 text-sm text-red-500';
            field.parentElement.appendChild(errorMsg);
        }
        errorMsg.textContent = message;
    }

    clearFieldError(field) {
        field.classList.remove('border-red-500', 'focus:ring-red-500');
        field.classList.add('border-gray-300', 'focus:ring-green-500');

        const errorMsg = field.parentElement.querySelector('.field-error');
        if (errorMsg) {
            errorMsg.remove();
        }
    }

    updateImagePreview(file) {
        if (!this.imagePreview) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            // Remplacer le contenu de la preview par l'image
            this.imagePreview.innerHTML = `
                <img src="${e.target.result}" 
                     alt="Aperçu" 
                     class="w-full h-full object-cover rounded-xl" />
            `;
        };
        reader.readAsDataURL(file);
    }

    validateForm() {
        const errors = [];

        // Nom de la chasse
        const huntName = document.getElementById('hunt_name')?.value;
        if (!huntName || huntName.trim() === '') {
            errors.push('Le nom de la chasse est requis');
        }

        // Description
        const huntDescription = document.getElementById('hunt_description')?.value;
        if (!huntDescription || huntDescription.trim() === '') {
            errors.push('La description de la chasse est requise');
        } else if (huntDescription.length > 3000) {
            errors.push('La description ne doit pas dépasser 3000 caractères');
        }

        // Équipe
        const huntTeam = document.getElementById('hunt_team')?.value;
        if (!huntTeam) {
            errors.push('Veuillez sélectionner une équipe');
        }

        // Type de chasse (checkboxes)
        const huntTypeCheckboxes = document.querySelectorAll('.hunt-type-checkbox:checked');
        if (huntTypeCheckboxes.length === 0) {
            errors.push('Veuillez sélectionner au moins un type de chasse');
        }

        // Difficulté
        const huntDifficulty = document.getElementById('hunt_difficulty')?.value;
        if (!huntDifficulty) {
            errors.push('Veuillez sélectionner une difficulté');
        }

        // Durée estimée
        const huntDuration = document.getElementById('hunt_duration')?.value;
        if (!huntDuration || parseInt(huntDuration) < 15) {
            errors.push('La durée estimée doit être d\'au moins 15 minutes');
        }

        // Ville
        const huntCity = document.getElementById('hunt_city')?.value;
        if (!huntCity || huntCity.trim() === '') {
            errors.push('La ville est requise');
        }

        return errors;
    }

    async submitHunt(action = 'draft') {
        // Valider le formulaire
        const errors = this.validateForm();
        if (errors.length > 0) {
            this.showError(errors.join('<br>'));
            return;
        }

        const formData = new FormData();

        // Informations de base
        formData.append('name', document.getElementById('hunt_name').value.trim());
        formData.append('description', document.getElementById('hunt_description').value.trim());

        // Configuration
        formData.append('designer_team_id', document.getElementById('hunt_team').value);

        // Types de chasse (checkboxes)
        const huntTypeCheckboxes = document.querySelectorAll('.hunt-type-checkbox:checked');
        const selectedTypes = Array.from(huntTypeCheckboxes).map(cb => cb.value);
        formData.append('hunt_types', JSON.stringify(selectedTypes));

        formData.append('difficulty', document.getElementById('hunt_difficulty').value);
        formData.append('estimated_duration', document.getElementById('hunt_duration').value);

        // Options
        const isPublic = document.getElementById('hunt_is_public')?.checked || false;
        formData.append('is_public', isPublic ? '1' : '0');

        // Localisation
        formData.append('city', document.getElementById('hunt_city').value.trim());

        // Action (draft ou publish)
        formData.append('action', action);

        // Image (via FileDragDrop)
        if (this.fileDragDrop) {
            const imageFile = this.fileDragDrop.getFile();
            if (imageFile) {
                formData.append('image', imageFile);
            }
        }

        if (this.riddleManager) {
            const riddles = this.riddleManager.getRiddlesData();
            formData.append('riddles', JSON.stringify(riddles));
        }

        // Déterminer l'URL et le message en fonction du mode
        const url = this.mode === 'edit'
            ? `/designer/hunt/${this.huntId}/edit`
            : '/designer/hunt/create';

        const actionText = action === 'publish'
            ? (this.mode === 'edit' ? 'modifiée et publiée' : 'créée et publiée')
            : (this.mode === 'edit' ? 'modifiée' : 'créée en brouillon');

        const successMessage = `Chasse ${actionText} avec succès !`;

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const result = await response.json();
                throw new Error(result.error || `Erreur lors de ${this.mode === 'edit' ? 'la modification' : 'la création'} de la chasse`);
            }

            const result = await response.json();
            this.showSuccess(successMessage);

            // Redirection après succès
            setTimeout(() => {
                window.location.href = this.mode === 'edit'
                    ? `/designer/hunt/${this.huntId}/details`
                    : `/designer/hunt/${result.hunt.id}/details`;
            }, 1500);

        } catch (error) {
            console.error(`Erreur lors de ${this.mode === 'edit' ? 'la modification' : 'la création'} de la chasse:`, error);
            this.showError(error.message || 'Une erreur est survenue');
        }
    }

    showError(message) {
        this.toast.error(message);
    }

    showSuccess(message) {
        this.toast.success(message);
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    new HuntFormManager();
});

