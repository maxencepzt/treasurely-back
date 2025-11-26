export class FileDragDrop {
    constructor(inputSelector, options = {}) {
        this.input = typeof inputSelector === 'string'
            ? document.querySelector(inputSelector)
            : inputSelector;

        if (!this.input) {
            console.error('Input file non trouvé:', inputSelector);
            return;
        }

        this.options = {
            onFileSelected: options.onFileSelected || null,
            onError: options.onError || null,
            allowedTypes: options.allowedTypes || ['image/jpeg', 'image/png', 'image/gif'],
            maxSize: options.maxSize || 5 * 1024 * 1024, // 5MB par défaut
            previewElement: options.previewElement || null
        };

        this.dropZone = null;
        this.previewContainer = null;

        this.init();
    }

    init() {
        // Trouver la drop zone (label parent ou élément spécifié)
        this.dropZone = this.input.closest('label') || this.input.parentElement;

        if (!this.dropZone) {
            console.warn('Drop zone non trouvée pour l\'input file');
            return;
        }

        // Trouver le conteneur de prévisualisation si spécifié
        if (this.options.previewElement) {
            this.previewContainer = typeof this.options.previewElement === 'string'
                ? document.querySelector(this.options.previewElement)
                : this.options.previewElement;
        }

        this.attachEvents();
    }

    attachEvents() {
        // Événements pour le drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, this.preventDefaults.bind(this), false);
            document.body.addEventListener(eventName, this.preventDefaults.bind(this), false);
        });

        // Highlight de la drop zone
        ['dragenter', 'dragover'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, () => this.highlight(), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, () => this.unhighlight(), false);
        });

        // Gestion du drop
        this.dropZone.addEventListener('drop', this.handleDrop.bind(this), false);

        // Gestion du changement via le sélecteur de fichiers classique
        this.input.addEventListener('change', this.handleInputChange.bind(this), false);
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    highlight() {
        // Ajouter une bordure bleue à la zone de drop
        const dropZoneDiv = this.dropZone.querySelector('div');
        if (dropZoneDiv) {
            dropZoneDiv.classList.remove('border-gray-300');
            dropZoneDiv.classList.add('border-blue-400', 'bg-blue-50');
        }
    }

    unhighlight() {
        // Retirer la bordure bleue de la zone de drop
        const dropZoneDiv = this.dropZone.querySelector('div');
        if (dropZoneDiv) {
            dropZoneDiv.classList.remove('border-blue-500', 'bg-blue-50');
            dropZoneDiv.classList.add('border-gray-300');
        }
    }

    showError() {
        // Animation d'erreur avec Tailwind
        const dropZoneDiv = this.dropZone.querySelector('div');
        if (dropZoneDiv) {
            dropZoneDiv.classList.add('border-red-500', 'bg-red-50');

            // Retirer après 2 secondes
            setTimeout(() => {
                dropZoneDiv.classList.remove('border-red-500', 'bg-red-50');
            }, 2000);
        }
    }

    handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            this.handleFiles(files);
        }
    }

    handleInputChange(e) {
        const files = e.target.files;
        if (files.length > 0) {
            this.handleFiles(files);
        }
    }

    handleFiles(files) {
        // Prendre uniquement le premier fichier (input file simple)
        const file = files[0];

        // Valider le fichier
        const validation = this.validateFile(file);
        if (!validation.valid) {
            this.showErrorMessage(validation.error);
            this.showError();
            return;
        }

        // Mise à jour de l'input file
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        this.input.files = dataTransfer.files;

        // Prévisualisation si c'est une image
        if (file.type.startsWith('image/')) {
            this.previewImage(file);
        }

        // Callback personnalisé
        if (this.options.onFileSelected) {
            this.options.onFileSelected(file);
        }
    }

    validateFile(file) {
        // Vérifier le type
        if (!this.options.allowedTypes.includes(file.type)) {
            return {
                valid: false,
                error: `Type de fichier non autorisé. Formats acceptés: ${this.getReadableTypes()}`
            };
        }

        // Vérifier la taille
        if (file.size > this.options.maxSize) {
            return {
                valid: false,
                error: `Fichier trop volumineux. Taille maximale: ${this.formatFileSize(this.options.maxSize)}`
            };
        }

        return { valid: true };
    }

    getReadableTypes() {
        return this.options.allowedTypes
            .map(type => type.split('/')[1].toUpperCase())
            .join(', ');
    }

    formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    previewImage(file) {
        const reader = new FileReader();

        reader.onload = (e) => {
            const previewTarget = this.previewContainer ||
                                  this.dropZone.closest('.flex.items-center')?.querySelector('.w-24.h-24');

            if (previewTarget) {
                // Chercher ou créer l'élément img
                let img = previewTarget.querySelector('img');
                if (!img) {
                    img = document.createElement('img');
                    img.className = 'w-full h-full object-cover rounded-xl';
                    previewTarget.innerHTML = '';
                    previewTarget.appendChild(img);
                }
                img.src = e.target.result;
                img.alt = file.name;
            }
        };

        reader.readAsDataURL(file);
    }

    showErrorMessage(message) {
        if (this.options.onError) {
            this.options.onError(message);
        } else {
            console.error('Erreur de fichier:', message);
            alert(message);
        }
    }

    // Méthode publique pour réinitialiser
    reset() {
        this.input.value = '';
        this.unhighlight();

        // Réinitialiser la prévisualisation
        const previewTarget = this.previewContainer ||
                              this.dropZone.closest('.flex.items-center')?.querySelector('.w-24.h-24');
        if (previewTarget) {
            const img = previewTarget.querySelector('img');
            if (img) {
                previewTarget.innerHTML = '?';
            }
        }
    }

    // Méthode publique pour obtenir le fichier sélectionné
    getFile() {
        return this.input.files.length > 0 ? this.input.files[0] : null;
    }
}

