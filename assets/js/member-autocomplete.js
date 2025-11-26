export class MemberAutocomplete {
    /**
     * @param {Object} options
     * @param {HTMLElement} options.searchInput - L'élément input pour la recherche
     * @param {HTMLElement} options.autocompleteResults - Le conteneur pour afficher les résultats
     * @param {number} [options.searchDelay=300] - Délai en ms avant de lancer la recherche
     * @param {number} [options.minSearchLength=2] - Longueur minimale de la chaîne de recherche
     * @param {string} [options.searchUrl='/designer/team/search-users'] - URL de l'API de recherche
     * @param {number} [options.designerTeamId=null] - ID de l'équipe de concepteurs (optionnel)
     * @param {string} [options.searchType='exclude'] - Type de recherche ('exclude' ou 'include')
     * @param {boolean} [options.singleMemberSelection=false] - Si true, permet uniquement la sélection d'un membre
     * @param {function} [options.onMemberAdded] - Callback lorsqu'un membre est ajouté
     * @param {function} [options.onMemberRemoved] - Callback lorsqu'un membre est supprimé
     * @param {function} [options.onError] - Callback en cas d'erreur
     */
    constructor(options = {}) {
        this.searchInput = options.searchInput;
        this.autocompleteResults = options.autocompleteResults;
        this.selectedMembers = new Map();
        this.searchTimeout = null;
        this.searchDelay = options.searchDelay || 300;
        this.minSearchLength = options.minSearchLength || 2;
        this.searchUrl = options.searchUrl || '/designer/team/search-users';
        this.designerTeamId = options.designerTeamId || null;
        this.searchType = options.searchType || 'exclude';
        this.singleMemberSelection = options.singleMemberSelection || false;
        
        // Callbacks optionnels
        this.onMemberAdded = options.onMemberAdded || (() => {});
        this.onMemberRemoved = options.onMemberRemoved || (() => {});
        this.onError = options.onError || ((message) => console.error(message));

        if (!this.searchInput || !this.autocompleteResults) {
            throw new Error('searchInput et autocompleteResults sont requis');
        }

        this.init();
    }

    init() {
        this.attachEventListeners();
    }

    attachEventListeners() {
        this.searchInput.addEventListener('input', () => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.searchUsers(this.searchInput.value);
            }, this.searchDelay);
        });

        this.searchInput.addEventListener('focus', () => {
            this.searchUsers(this.searchInput.value);
            if (this.autocompleteResults.children.length > 0) {
                this.autocompleteResults.classList.remove('hidden');
            }
        });

        document.addEventListener('click', (event) => {
            if (!this.searchInput.contains(event.target) &&
                !this.autocompleteResults.contains(event.target)) {
                this.autocompleteResults.classList.add('hidden');
                this.autocompleteResults.innerHTML = '';
            }
        });
    }

    async searchUsers(query) {
        if (query.length < this.minSearchLength) {
            this.autocompleteResults.innerHTML = '';
            this.autocompleteResults.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(
                `${this.searchUrl}?q=${encodeURIComponent(query)}${this.designerTeamId ? `&designerTeamId=${this.designerTeamId}` : ''}${this.searchType ? `&type=${this.searchType}` : ''}`
            );
            if (!response.ok) {
                throw new Error('Erreur lors de la recherche');
            }
            const users = await response.json();
            this.displayAutocompleteResults(users);
        } catch (error) {
            console.error('Erreur lors de la recherche des utilisateurs:', error);
            this.onError('Erreur lors de la recherche des utilisateurs');
        }
    }

    displayAutocompleteResults(users) {
        this.autocompleteResults.innerHTML = '';

        if (users.length === 0) {
            this.autocompleteResults.innerHTML = `
                <div class="p-6 text-center">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p class="text-gray-500">Aucun utilisateur trouvé</p>
                </div>
            `;
            this.autocompleteResults.classList.remove('hidden');
            return;
        }

        users.forEach((user, index) => {
            const isAlreadyAdded = this.selectedMembers.has(user.id);
            const colorClass = this.getColorClass(index);
            const userElement = this.createUserElement(user, isAlreadyAdded, colorClass, index, users.length);

            if (!isAlreadyAdded) {
                userElement.addEventListener('click', () => {
                    this.addMember(user);
                    this.searchInput.value = '';
                    this.autocompleteResults.classList.add('hidden');
                });
            }

            this.autocompleteResults.appendChild(userElement);
        });

        this.autocompleteResults.classList.remove('hidden');
    }

    createUserElement(user, isAlreadyAdded, colorClass, index, totalUsers) {
        const userElement = document.createElement('div');
        userElement.className = `p-3 hover:bg-blue-50 cursor-pointer transition flex items-center space-x-3 ${
            index < totalUsers - 1 ? 'border-b border-gray-100' : ''
        }`;
        
        userElement.innerHTML = `
            <div class="w-10 h-10 bg-gradient-to-br ${colorClass} rounded-full flex items-center justify-center text-white font-bold text-sm overflow-hidden">
                <img src="/api/users/${user.id}/picture/" alt="${user.nickname}" />
            </div>
            <div class="flex-1">
                <p class="font-semibold text-gray-800">${user.fullName}</p>
                <p class="text-sm text-gray-500">@${user.nickname}</p>
            </div>
            ${isAlreadyAdded ? 
                '<span class="text-green-600 text-sm font-semibold">Déjà ajouté</span>' :
                `<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>`
            }
        `;

        return userElement;
    }

    addMember(user) {
        if (this.selectedMembers.has(user.id)) {
            return;
        }

        if (this.singleMemberSelection) {
            this.clearSelectedMembers();
        }

        this.selectedMembers.set(user.id, user);
        this.onMemberAdded(user);
        this.autocompleteResults.innerHTML = '';
    }

    removeMember(userId) {
        const user = this.selectedMembers.get(userId);
        if (!user) {
            return;
        }

        this.selectedMembers.delete(userId);
        this.onMemberRemoved(user);
    }

    clearSelectedMembers() {
        this.selectedMembers.clear();
    }

    setSelectedMembers(members) {
        this.selectedMembers.clear();
        members.forEach(member => {
            this.selectedMembers.set(member.id, member);
        });
    }

    getColorClass(index) {
        const colors = [
            'from-blue-500 to-blue-600',
            'from-green-500 to-green-600',
            'from-purple-500 to-purple-600',
            'from-orange-500 to-orange-600',
            'from-pink-500 to-pink-600',
            'from-indigo-500 to-indigo-600',
            'from-red-500 to-red-600',
            'from-teal-500 to-teal-600'
        ];

        return colors[index % colors.length];
    }
}
