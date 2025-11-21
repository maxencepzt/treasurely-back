// Gestion de la création d'équipe avec autocomplétion des membres
class TeamCreator {
    constructor() {
        this.searchInput = document.getElementById('member_search');
        this.autocompleteResults = document.getElementById('autocomplete_results');
        this.membersList = document.getElementById('members_list');
        this.teamForm = document.querySelector('form');
        this.imageInput = document.getElementById('team_image');
        this.imagePreview = document.querySelector('.w-24.h-24');
        this.selectedMembers = new Map();
        this.selectedImage = null;
        this.searchTimeout = null;
        this.init();
    }
    init() {
        if (!this.searchInput || !this.autocompleteResults || !this.membersList || !this.teamForm) {
            console.error('Éléments requis non trouvés dans le DOM');
            return;
        }
        this.loadExistingMembers();
        this.attachEventListeners();
        this.updateMembersDisplay();
    }

    loadExistingMembers() {
        // Charger les membres existants depuis le DOM (pour le mode édition)
        const existingMembers = this.membersList.querySelectorAll('.existing-member');
        existingMembers.forEach(memberElement => {
            const userId = parseInt(memberElement.dataset.userId);
            const userFullName = memberElement.dataset.userFullname;
            const userNickname = memberElement.dataset.userNickname;

            if (userId && userFullName && userNickname) {
                this.selectedMembers.set(userId, {
                    id: userId,
                    fullName: userFullName,
                    nickname: userNickname
                });
            }
        });
    }
    attachEventListeners() {
        this.searchInput.addEventListener('input', () => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.searchUsers(this.searchInput.value);
            }, 300);
        });
        this.searchInput.addEventListener('focus', () => {
            if (this.autocompleteResults.children.length > 0) {
                this.autocompleteResults.classList.remove('hidden');
            }
        });
        document.addEventListener('click', (event) => {
            if (!this.searchInput.contains(event.target) && !this.autocompleteResults.contains(event.target)) {
                this.autocompleteResults.classList.add('hidden');
            }
        });
        this.teamForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitTeam();
        });
        if (this.imageInput) {
            this.imageInput.addEventListener('change', (e) => {
                this.handleImageSelect(e);
            });
        }
    }
    async searchUsers(query) {
        if (query.length < 2) {
            this.autocompleteResults.innerHTML = '';
            this.autocompleteResults.classList.add('hidden');
            return;
        }
        try {
            const response = await fetch(`/designer/team/search-users?q=${encodeURIComponent(query)}`);
            if (!response.ok) {
                throw new Error('Erreur lors de la recherche');
            }
            const users = await response.json();
            this.displayAutocompleteResults(users);
        } catch (error) {
            console.error('Erreur lors de la recherche des utilisateurs:', error);
            this.showError('Erreur lors de la recherche des utilisateurs');
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
            const userElement = document.createElement('div');
            userElement.className = `p-3 hover:bg-blue-50 cursor-pointer transition flex items-center space-x-3 ${index < users.length - 1 ? 'border-b border-gray-100' : ''}`;
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
    handleImageSelect(event) {
        const file = event.target.files[0];
        if (!file) {
            return;
        }
        const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            this.showError('Format d\'image non supporté. Utilisez PNG, JPG ou GIF.');
            this.imageInput.value = '';
            return;
        }
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            this.showError('L\'image est trop volumineuse. Taille maximum : 5MB');
            this.imageInput.value = '';
            return;
        }
        this.selectedImage = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            this.imagePreview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover rounded-xl" />
            `;
        };
        reader.readAsDataURL(file);
    }
    addMember(user) {
        if (this.selectedMembers.has(user.id)) {
            return;
        }
        this.selectedMembers.set(user.id, user);
        this.updateMembersDisplay();
    }
    removeMember(userId) {
        this.selectedMembers.delete(userId);
        this.updateMembersDisplay();
    }
    updateMembersDisplay() {
        const membersContainer = this.membersList.querySelector('.space-y-2') || this.createMembersContainer();
        const countElement = this.membersList.querySelector('h3');
        if (countElement) {
            countElement.textContent = `Membres ajoutés (${this.selectedMembers.size})`;
        }
        membersContainer.innerHTML = '';
        if (this.selectedMembers.size === 0) {
            membersContainer.innerHTML = `
                <div class="p-6 text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p class="text-gray-500 text-sm">Aucun membre ajouté pour le moment</p>
                    <p class="text-gray-400 text-xs mt-1">Utilisez la recherche ci-dessus pour ajouter des concepteurs</p>
                </div>
            `;
            return;
        }
        let index = 0;
        this.selectedMembers.forEach((user) => {
            const colorClass = this.getColorClass(index);
            const bgColorClass = this.getBgColorClass(index);
            const memberElement = document.createElement('div');
            memberElement.className = `flex items-center justify-between p-4 ${bgColorClass} rounded-xl border border-opacity-50`;
            memberElement.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br ${colorClass} rounded-full flex items-center justify-center text-white font-bold text-sm overflow-hidden">
                        <img src="/api/users/${user.id}/picture/" alt="${user.nickname}" />
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">${user.fullName}</p>
                        <p class="text-sm text-gray-500">@${user.nickname}</p>
                    </div>
                </div>
                <button type="button" class="remove-member text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition cursor-pointer" title="Retirer" data-user-id="${user.id}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            memberElement.querySelector('.remove-member').addEventListener('click', () => {
                memberElement.style.opacity = '0';
                memberElement.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.removeMember(user.id);
                }, 200);
            });
            membersContainer.appendChild(memberElement);
            index++;
        });
    }
    createMembersContainer() {
        const container = document.createElement('div');
        container.className = 'space-y-2';
        this.membersList.appendChild(container);
        return container;
    }
    async submitTeam() {
        const teamName = document.getElementById('team_name')?.value;
        const teamDescription = document.getElementById('team_description')?.value;
        if (!teamName || teamName.trim() === '') {
            this.showError('Le nom de l\'équipe est requis');
            return;
        }
        const memberIds = Array.from(this.selectedMembers.keys());
        const formData = new FormData();
        formData.append('name', teamName.trim());
        if (teamDescription && teamDescription.trim()) {
            formData.append('description', teamDescription.trim());
        }
        formData.append('members', JSON.stringify(memberIds));
        if (this.selectedImage) {
            formData.append('image', this.selectedImage);
        }
        try {
            const response = await fetch('/designer/team/create-team', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Erreur lors de la création de l\'équipe');
            }
            this.showSuccess('Équipe créée avec succès !');
            setTimeout(() => {
                window.location.href = `/designer/team/details/${result.team.id}`;
            }, 1000);
        } catch (error) {
            console.error('Erreur lors de la création de l\'équipe:', error);
            this.showError(error.message || 'Une erreur est survenue');
        }
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

    getBgColorClass(index) {
        const bgColors = [
            'bg-gradient-to-r from-blue-50 to-cyan-50 border-blue-200',
            'bg-gradient-to-r from-green-50 to-emerald-50 border-green-200',
            'bg-gradient-to-r from-purple-50 to-pink-50 border-purple-200',
            'bg-gradient-to-r from-orange-50 to-amber-50 border-orange-200',
            'bg-gradient-to-r from-pink-50 to-rose-50 border-pink-200',
            'bg-gradient-to-r from-indigo-50 to-blue-50 border-indigo-200',
            'bg-gradient-to-r from-red-50 to-pink-50 border-red-200',
            'bg-gradient-to-r from-teal-50 to-cyan-50 border-teal-200'
        ];
        return bgColors[index % bgColors.length];
    }
    showError(message) {
        this.showNotification(message, 'error');
    }
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 ${
            type === 'error' ? 'bg-red-500' : 'bg-green-500'
        } text-white max-w-md`;
        notification.innerHTML = `
            <div class="flex items-center space-x-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${type === 'error' ? 
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>' :
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
                    }
                </svg>
                <p class="font-semibold">${message}</p>
            </div>
        `;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100px)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new TeamCreator();
});
