/**
 * Gère la liste d'énigmes d'un formulaire de chasse : ouverture du modal, saisie par
 * type, et constitution de la charge utile envoyée au serveur. Une énigme existante
 * conserve son identifiant, c'est ce qui permet au serveur de la mettre à jour au lieu
 * de la recréer.
 */
class RiddleManager {
    /**
     * @param {{ notify?: (message: string) => void }} options
     */
    constructor(options = {}) {
        this.notify = options.notify || ((message) => window.alert(message));
        this.riddles = [];
        this.currentEditIndex = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadExistingRiddles();
        this.updateRiddlesList();
    }

    loadExistingRiddles() {
        document.querySelectorAll('.riddle-item[data-riddle-data]').forEach((item) => {
            try {
                this.riddles.push(JSON.parse(item.getAttribute('data-riddle-data')));
            } catch (e) {
                console.error('Énigme illisible dans le DOM :', e);
            }
        });
    }

    bindEvents() {
        document.getElementById('add_riddle_btn')?.addEventListener('click', () => this.openRiddleModal());
        document.getElementById('close_riddle_modal')?.addEventListener('click', () => this.closeRiddleModal());
        document.getElementById('close_riddle_modal_btn')?.addEventListener('click', () => this.closeRiddleModal());
        document.getElementById('save_riddle_btn')?.addEventListener('click', () => this.saveRiddle());
        document.getElementById('add_mcq_choice')?.addEventListener('click', () => this.addMCQChoice());
        document.getElementById('riddle_type')?.addEventListener('change', (e) => this.showRiddleTypeFields(e.target.value));

        const modal = document.getElementById('riddle_modal');
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeRiddleModal();
            }
        });
    }

    openRiddleModal(editIndex = null) {
        this.currentEditIndex = editIndex;

        if (editIndex !== null) {
            this.fillModalWithRiddle(this.riddles[editIndex]);
            document.getElementById('riddle_modal_title').textContent = 'Modifier l\'énigme';
        } else {
            this.resetModal();
            document.getElementById('riddle_modal_title').textContent = 'Ajouter une énigme';
        }

        document.getElementById('riddle_modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    closeRiddleModal() {
        document.getElementById('riddle_modal').classList.add('hidden');
        document.body.style.overflow = '';
        this.resetModal();
    }

    resetModal() {
        document.getElementById('riddle_form').reset();
        this.showRiddleTypeFields('text');
        this.currentEditIndex = null;

        const container = document.getElementById('mcq_choices_container');
        container.innerHTML = '';
        this.addMCQChoice();
        this.addMCQChoice();
    }

    showRiddleTypeFields(type) {
        document.querySelectorAll('.riddle-type-fields').forEach((el) => el.classList.add('hidden'));
        document.getElementById(`${type}_fields`)?.classList.remove('hidden');
    }

    fillModalWithRiddle(riddle) {
        document.getElementById('riddle_title').value = riddle.title;
        document.getElementById('riddle_description').value = riddle.description;
        document.getElementById('riddle_difficulty').value = riddle.difficulty;
        document.getElementById('riddle_type').value = riddle.type;
        document.getElementById('riddle_max_attempts').value = riddle.maxScoringAttempts ?? 3;

        this.showRiddleTypeFields(riddle.type);

        switch (riddle.type) {
            case 'text':
                document.getElementById('text_answer').value = riddle.answer || '';
                break;
            case 'gps':
                document.getElementById('gps_latitude').value = riddle.latitude ?? '';
                document.getElementById('gps_longitude').value = riddle.longitude ?? '';
                break;
            case 'mcq':
                this.fillMCQChoices(riddle.choices || [], riddle.answers || []);
                document.getElementById('mcq_reveal_count').checked = riddle.revealAnswerCount !== false;
                break;
            case 'qr':
                document.getElementById('qr_code_display').textContent = riddle.code || 'Attribué à l\'enregistrement';
                break;
        }
    }

    fillMCQChoices(choices, answers) {
        const container = document.getElementById('mcq_choices_container');
        container.innerHTML = '';

        choices.forEach((choice) => this.addMCQChoice(choice, answers.includes(choice)));

        while (container.children.length < 2) {
            this.addMCQChoice();
        }
    }

    addMCQChoice(value = '', isCorrect = false) {
        const container = document.getElementById('mcq_choices_container');
        const index = container.children.length;

        const row = document.createElement('div');
        row.className = 'flex items-center space-x-2 mcq-choice-item';
        row.innerHTML = `
            <input type="checkbox" class="mcq-choice-correct w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500" title="Bonne réponse">
            <input type="text" class="mcq-choice-text flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Choix ${index + 1}">
            <button type="button" class="remove-choice-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition" title="Retirer ce choix">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        // Valeurs affectées par propriété, jamais interpolées dans le HTML.
        row.querySelector('.mcq-choice-correct').checked = isCorrect;
        row.querySelector('.mcq-choice-text').value = value;
        row.querySelector('.remove-choice-btn').addEventListener('click', () => {
            if (container.children.length <= 2) {
                this.notify('Un QCM garde au moins deux choix.');
                return;
            }
            row.remove();
            this.updateMCQChoicePlaceholders();
        });

        container.appendChild(row);
    }

    updateMCQChoicePlaceholders() {
        document.querySelectorAll('.mcq-choice-text').forEach((input, index) => {
            input.placeholder = `Choix ${index + 1}`;
        });
    }

    /**
     * Lit le modal et renvoie l'énigme, ou null avec un message si un champ manque.
     * La validation complète est faite par le serveur ; ici on évite seulement les
     * allers-retours évidents.
     */
    readModal() {
        const title = document.getElementById('riddle_title').value.trim();
        const description = document.getElementById('riddle_description').value.trim();
        const type = document.getElementById('riddle_type').value;
        const maxScoringAttempts = parseInt(document.getElementById('riddle_max_attempts').value, 10);

        if (!title) {
            return this.reject('Veuillez saisir un titre pour l\'énigme.', 'riddle_title');
        }
        if (!description) {
            return this.reject('Veuillez saisir une description pour l\'énigme.', 'riddle_description');
        }
        if (Number.isNaN(maxScoringAttempts) || maxScoringAttempts < 1 || maxScoringAttempts > 20) {
            return this.reject('Le nombre d\'essais notés doit être compris entre 1 et 20.', 'riddle_max_attempts');
        }

        const riddle = {
            title,
            description,
            type,
            difficulty: parseInt(document.getElementById('riddle_difficulty').value, 10),
            maxScoringAttempts,
        };

        switch (type) {
            case 'text': {
                riddle.answer = document.getElementById('text_answer').value.trim();
                if (!riddle.answer) {
                    return this.reject('Veuillez saisir la réponse attendue.', 'text_answer');
                }
                break;
            }
            case 'gps': {
                const latitude = document.getElementById('gps_latitude').value;
                const longitude = document.getElementById('gps_longitude').value;
                if (latitude === '' || longitude === '') {
                    return this.reject('Veuillez saisir la latitude et la longitude.', latitude === '' ? 'gps_latitude' : 'gps_longitude');
                }
                riddle.latitude = parseFloat(latitude);
                riddle.longitude = parseFloat(longitude);
                break;
            }
            case 'mcq': {
                const choices = [];
                const answers = [];
                document.querySelectorAll('.mcq-choice-item').forEach((item) => {
                    const text = item.querySelector('.mcq-choice-text').value.trim();
                    if (!text) {
                        return;
                    }
                    choices.push(text);
                    if (item.querySelector('.mcq-choice-correct').checked) {
                        answers.push(text);
                    }
                });
                if (choices.length < 2) {
                    return this.reject('Un QCM doit proposer au moins deux choix.');
                }
                if (answers.length === 0) {
                    return this.reject('Cochez au moins une bonne réponse.');
                }
                riddle.choices = choices;
                riddle.answers = answers;
                riddle.revealAnswerCount = document.getElementById('mcq_reveal_count').checked;
                break;
            }
        }

        return riddle;
    }

    reject(message, focusId = null) {
        this.notify(message);
        if (focusId) {
            document.getElementById(focusId)?.focus();
        }
        return null;
    }

    saveRiddle() {
        const riddle = this.readModal();
        if (!riddle) {
            return;
        }

        if (this.currentEditIndex !== null) {
            const existing = this.riddles[this.currentEditIndex];
            if (existing.id !== undefined) {
                riddle.id = existing.id;
            }
            // Le code QR est attribué par le serveur à la création et imprimé : il ne change plus.
            if (existing.code !== undefined) {
                riddle.code = existing.code;
            }
            this.riddles[this.currentEditIndex] = riddle;
        } else {
            this.riddles.push(riddle);
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
            riddlesList.innerHTML = this.riddles.map((riddle, index) => this.createRiddleCard(riddle, index)).join('');
            this.bindRiddleCardEvents();
        }

        if (riddlesCount) {
            riddlesCount.textContent = this.riddles.length;
        }
    }

    getColorsByType(type) {
        const colorMap = {
            text: { bg: 'from-blue-50 to-cyan-50 border-blue-200', number: 'bg-blue-600', tag: 'bg-blue-100 text-blue-700', edit: 'text-blue-600 hover:text-blue-800 hover:bg-blue-100' },
            gps: { bg: 'from-green-50 to-emerald-50 border-green-200', number: 'bg-green-600', tag: 'bg-green-100 text-green-700', edit: 'text-green-600 hover:text-green-800 hover:bg-green-100' },
            mcq: { bg: 'from-purple-50 to-pink-50 border-purple-200', number: 'bg-purple-600', tag: 'bg-purple-100 text-purple-700', edit: 'text-purple-600 hover:text-purple-800 hover:bg-purple-100' },
            qr: { bg: 'from-orange-50 to-amber-50 border-orange-200', number: 'bg-orange-600', tag: 'bg-orange-100 text-orange-700', edit: 'text-orange-600 hover:text-orange-800 hover:bg-orange-100' },
        };

        return colorMap[type] || colorMap.text;
    }

    createRiddleCard(riddle, index) {
        const colors = this.getColorsByType(riddle.type);
        const flames = Array.from({ length: riddle.difficulty }, () =>
            '<svg class="w-4 h-4 inline text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.878A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"></path></svg>'
        ).join('');

        return `
            <div class="p-4 bg-gradient-to-r ${colors.bg} rounded-xl border-2 group hover:shadow-md transition" data-riddle-index="${index}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 flex-1">
                        <div class="w-8 h-8 ${colors.number} rounded-lg flex items-center justify-center text-white font-bold text-sm">${index + 1}</div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-800">${this.escapeHtml(riddle.title)}</h3>
                            <p class="text-xs text-gray-500 flex items-center space-x-2">
                                <span class="${colors.tag} px-2 py-0.5 rounded">${this.getRiddleTypeInfo(riddle.type)}</span>
                                <span>${flames}</span>
                                <span>${riddle.maxScoringAttempts ?? 3} essai(s) noté(s)</span>
                                ${riddle.type === 'qr' && riddle.code ? `<span class="font-mono">${this.escapeHtml(riddle.code)}</span>` : ''}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" class="edit-riddle-btn ${colors.edit} p-2 rounded-lg transition" title="Modifier">
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
        document.querySelectorAll('.edit-riddle-btn').forEach((btn) => {
            btn.addEventListener('click', () => this.openRiddleModal(this.indexOf(btn)));
        });
        document.querySelectorAll('.delete-riddle-btn').forEach((btn) => {
            btn.addEventListener('click', () => this.deleteRiddle(this.indexOf(btn)));
        });
    }

    indexOf(button) {
        return parseInt(button.closest('[data-riddle-index]').dataset.riddleIndex, 10);
    }

    deleteRiddle(index) {
        if (window.confirm('Retirer cette énigme de la chasse ? La suppression ne sera effective qu\'à l\'enregistrement.')) {
            this.riddles.splice(index, 1);
            this.updateRiddlesList();
        }
    }

    getRiddleTypeInfo(type) {
        const types = { text: 'Texte', gps: 'GPS', mcq: 'QCM', qr: 'QR Code' };
        return types[type] || types.text;
    }

    escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, (m) => map[m]);
    }

    getRiddlesData() {
        return this.riddles;
    }
}

export { RiddleManager };
