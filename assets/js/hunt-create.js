class RiddleManager {
    constructor() {
        this.riddles = [];
        this.currentEditIndex = null;
        this.riddleCounter = 0;
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateRiddlesList();
    }

    bindEvents() {
        // Bouton d'ajout d'énigme
        const addBtn = document.getElementById('add_riddle_btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.openRiddleModal());
        }

        // Fermeture du modal
        const closeBtn = document.getElementById('close_riddle_modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeRiddleModal());
        }

        const closeBtnBottom = document.getElementById('close_riddle_modal_btn');
        if (closeBtnBottom) {
            closeBtnBottom.addEventListener('click', () => this.closeRiddleModal());
        }

        // Clic en dehors du modal
        const modal = document.getElementById('riddle_modal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeRiddleModal();
                }
            });
        }

        // Changement de type d'énigme
        const typeSelect = document.getElementById('riddle_type');
        if (typeSelect) {
            typeSelect.addEventListener('change', (e) => {
                this.showRiddleTypeFields(e.target.value);
            });
        }

        // Bouton de sauvegarde d'énigme
        const saveBtn = document.getElementById('save_riddle_btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveRiddle());
        }

        // Gestion des choix MCQ
        const addChoiceBtn = document.getElementById('add_mcq_choice');
        if (addChoiceBtn) {
            addChoiceBtn.addEventListener('click', () => this.addMCQChoice());
        }
    }

    openRiddleModal(editIndex = null) {
        this.currentEditIndex = editIndex;
        const modal = document.getElementById('riddle_modal');

        if (editIndex !== null) {
            // Mode édition
            this.fillModalWithRiddle(this.riddles[editIndex]);
            document.getElementById('riddle_modal_title').textContent = 'Modifier l\'énigme';
        } else {
            // Mode création
            this.resetModal();
            document.getElementById('riddle_modal_title').textContent = 'Ajouter une énigme';
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    closeRiddleModal() {
        const modal = document.getElementById('riddle_modal');
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        this.resetModal();
    }

    resetModal() {
        document.getElementById('riddle_form').reset();
        this.showRiddleTypeFields('text');
        this.currentEditIndex = null;

        // Réinitialiser les choix MCQ
        const choicesContainer = document.getElementById('mcq_choices_container');
        choicesContainer.innerHTML = '';
        this.addMCQChoice();
        this.addMCQChoice();
    }

    showRiddleTypeFields(type) {
        // Cacher tous les champs spécifiques
        document.querySelectorAll('.riddle-type-fields').forEach(el => {
            el.classList.add('hidden');
        });

        // Afficher les champs du type sélectionné
        const fieldsToShow = document.getElementById(`${type}_fields`);
        if (fieldsToShow) {
            fieldsToShow.classList.remove('hidden');
        }
    }

    fillModalWithRiddle(riddle) {
        document.getElementById('riddle_title').value = riddle.title;
        document.getElementById('riddle_description').value = riddle.description;
        document.getElementById('riddle_difficulty').value = riddle.difficulty;
        document.getElementById('riddle_type').value = riddle.type;

        this.showRiddleTypeFields(riddle.type);

        // Remplir les champs spécifiques selon le type
        switch (riddle.type) {
            case 'text':
                document.getElementById('text_answer').value = riddle.answer || '';
                break;
            case 'gps':
                document.getElementById('gps_latitude').value = riddle.latitude || '';
                document.getElementById('gps_longitude').value = riddle.longitude || '';
                break;
            case 'mcq':
                this.fillMCQChoices(riddle.choices || [], riddle.answers || []);
                break;
            case 'qr':
                document.getElementById('qr_code').value = riddle.code || '';
                break;
        }
    }

    fillMCQChoices(choices, answers) {
        const container = document.getElementById('mcq_choices_container');
        container.innerHTML = '';

        choices.forEach((choice, index) => {
            this.addMCQChoice(choice, answers.includes(choice));
        });

        if (choices.length === 0) {
            this.addMCQChoice();
            this.addMCQChoice();
        }
    }

    addMCQChoice(value = '', isCorrect = false) {
        const container = document.getElementById('mcq_choices_container');
        const choiceIndex = container.children.length;

        const choiceDiv = document.createElement('div');
        choiceDiv.className = 'flex items-center space-x-2 mcq-choice-item';
        choiceDiv.innerHTML = `
            <input type="checkbox" class="mcq-choice-correct w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500" ${isCorrect ? 'checked' : ''}>
            <input type="text" class="mcq-choice-text flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                   placeholder="Choix ${choiceIndex + 1}" value="${value}">
            ${choiceIndex >= 2 ? `
                <button type="button" class="remove-choice-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            ` : '<div class="w-9"></div>'}
        `;

        // Ajouter l'événement de suppression
        const removeBtn = choiceDiv.querySelector('.remove-choice-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                choiceDiv.remove();
                this.updateMCQChoicePlaceholders();
            });
        }

        container.appendChild(choiceDiv);
    }

    updateMCQChoicePlaceholders() {
        const choices = document.querySelectorAll('.mcq-choice-text');
        choices.forEach((input, index) => {
            input.placeholder = `Choix ${index + 1}`;
        });
    }

    saveRiddle() {
        // Valider les champs de base
        const title = document.getElementById('riddle_title').value.trim();
        const description = document.getElementById('riddle_description').value.trim();
        const difficulty = document.getElementById('riddle_difficulty').value;
        const type = document.getElementById('riddle_type').value;

        if (!title) {
            alert('Veuillez saisir un titre pour l\'énigme');
            document.getElementById('riddle_title').focus();
            return;
        }

        if (!description) {
            alert('Veuillez saisir une description pour l\'énigme');
            document.getElementById('riddle_description').focus();
            return;
        }

        const riddleData = {
            title: title,
            description: description,
            difficulty: parseInt(difficulty),
            type: type,
        };

        // Ajouter et valider les données spécifiques selon le type
        switch (riddleData.type) {
            case 'text':
                const textAnswer = document.getElementById('text_answer').value.trim();
                if (!textAnswer) {
                    alert('Veuillez saisir la réponse attendue');
                    document.getElementById('text_answer').focus();
                    return;
                }
                riddleData.answer = textAnswer;
                break;
            case 'gps':
                const latitude = document.getElementById('gps_latitude').value;
                const longitude = document.getElementById('gps_longitude').value;

                if (!latitude || !longitude) {
                    alert('Veuillez saisir les coordonnées GPS (latitude et longitude)');
                    if (!latitude) {
                        document.getElementById('gps_latitude').focus();
                    } else {
                        document.getElementById('gps_longitude').focus();
                    }
                    return;
                }

                riddleData.latitude = parseFloat(latitude);
                riddleData.longitude = parseFloat(longitude);
                break;
            case 'mcq':
                const choices = [];
                const answers = [];
                document.querySelectorAll('.mcq-choice-item').forEach(item => {
                    const text = item.querySelector('.mcq-choice-text').value.trim();
                    const isCorrect = item.querySelector('.mcq-choice-correct').checked;
                    if (text) {
                        choices.push(text);
                        if (isCorrect) {
                            answers.push(text);
                        }
                    }
                });

                if (choices.length < 2) {
                    alert('Veuillez saisir au moins 2 choix de réponses pour le QCM');
                    return;
                }

                if (answers.length === 0) {
                    alert('Veuillez cocher au moins une bonne réponse pour le QCM');
                    return;
                }

                riddleData.choices = choices;
                riddleData.answers = answers;
                break;
            case 'qr':
                const qrCode = document.getElementById('qr_code').value.trim();
                if (!qrCode) {
                    alert('Veuillez saisir le code QR');
                    document.getElementById('qr_code').focus();
                    return;
                }
                riddleData.code = qrCode;
                break;
        }

        if (this.currentEditIndex !== null) {
            // Mode édition
            this.riddles[this.currentEditIndex] = riddleData;
        } else {
            // Mode ajout
            this.riddles.push(riddleData);
        }

        this.updateRiddlesList();
        this.closeRiddleModal();
    }

    updateRiddlesList() {
        const noRiddlesMsg = document.getElementById('no_riddles_message');
        const riddlesList = document.getElementById('riddles_list');
        const riddlesCount = document.getElementById('riddles_count');

        if (this.riddles.length === 0) {
            noRiddlesMsg.classList.remove('hidden');
            riddlesList.classList.add('hidden');
        } else {
            noRiddlesMsg.classList.add('hidden');
            riddlesList.classList.remove('hidden');
            riddlesList.innerHTML = this.riddles.map((riddle, index) =>
                this.createRiddleCard(riddle, index)
            ).join('');

            // Ajouter les événements aux boutons
            this.bindRiddleCardEvents();
        }

        // Mettre à jour le compteur
        if (riddlesCount) {
            riddlesCount.textContent = this.riddles.length;
        }
    }

    createRiddleCard(riddle, index) {
        const typeInfo = this.getRiddleTypeInfo(riddle.type);
        const difficultyStars = '⭐'.repeat(riddle.difficulty);

        return `
            <div class="p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl border-2 border-blue-200 group hover:shadow-md transition" data-riddle-index="${index}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 flex-1">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                ${index + 1}
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-800">${this.escapeHtml(riddle.title)}</h3>
                            <p class="text-xs text-gray-500 flex items-center space-x-2">
                                <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded">${typeInfo.icon} ${typeInfo.label}</span>
                                <span>•</span>
                                <span>${difficultyStars}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" class="edit-riddle-btn text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-100 transition" title="Modifier">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button type="button" class="delete-riddle-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition" title="Supprimer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    bindRiddleCardEvents() {
        // Boutons d'édition
        document.querySelectorAll('.edit-riddle-btn').forEach((btn, index) => {
            btn.addEventListener('click', () => {
                const card = btn.closest('[data-riddle-index]');
                const riddleIndex = parseInt(card.dataset.riddleIndex);
                this.openRiddleModal(riddleIndex);
            });
        });

        // Boutons de suppression
        document.querySelectorAll('.delete-riddle-btn').forEach((btn, index) => {
            btn.addEventListener('click', () => {
                const card = btn.closest('[data-riddle-index]');
                const riddleIndex = parseInt(card.dataset.riddleIndex);
                this.deleteRiddle(riddleIndex);
            });
        });
    }

    deleteRiddle(index) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette énigme ?')) {
            this.riddles.splice(index, 1);
            this.updateRiddlesList();
        }
    }

    getRiddleTypeInfo(type) {
        const types = {
            text: { icon: '✍️', label: 'Texte' },
            gps: { icon: '📍', label: 'GPS' },
            mcq: { icon: '☑️', label: 'QCM' },
            qr: { icon: '📱', label: 'QR Code' }
        };
        return types[type] || types.text;
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    getRiddlesData() {
        return this.riddles;
    }
}

// Initialiser le gestionnaire d'énigmes
document.addEventListener('DOMContentLoaded', () => {
    window.riddleManager = new RiddleManager();
});

