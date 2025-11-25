import { ToastManager } from 'modal-manager';
import { MemberAutocomplete } from 'member-autocomplete';
import { MemberListDisplay } from 'member-list-display';

class TeamFormManager {
    constructor() {
        this.teamForm = document.querySelector('form[data-team-form]');
        this.imageInput = document.getElementById('team_image');
        this.imagePreview = document.querySelector('.w-24.h-24');
        this.selectedImage = null;

        // Initialiser le ToastManager
        this.toast = new ToastManager('globalToast');

        // Déterminer le mode (création ou édition)
        this.mode = this.teamForm?.dataset.mode || 'create';
        this.teamId = this.extractTeamIdFromUrl();

        this.init();
    }

    extractTeamIdFromUrl() {
        // Extraire l'ID de l'équipe depuis l'URL pour le mode édition
        const match = window.location.pathname.match(/\/team\/(\d+)\/edit/);
        return match ? parseInt(match[1]) : null;
    }

    init() {
        if (!this.teamForm) {
            console.error('Formulaire d\'équipe non trouvé dans le DOM');
            return;
        }

        // Initialiser l'autocomplétion des membres
        const searchInput = document.getElementById('member_search');
        const autocompleteResults = document.getElementById('autocomplete_results');

        if (searchInput && autocompleteResults) {
            this.memberAutocomplete = new MemberAutocomplete({
                searchInput,
                autocompleteResults,
                onMemberAdded: (user) => this.handleMemberAdded(user),
                onError: (message) => this.showError(message)
            });
        }

        // Initialiser l'affichage de la liste des membres
        const membersList = document.getElementById('members_list');
        if (membersList) {
            this.memberListDisplay = new MemberListDisplay({
                container: membersList,
                onRemove: (user) => this.handleMemberRemoved(user)
            });
        }

        // Charger les membres existants (en mode édition)
        this.loadExistingMembers();

        // Attacher les événements
        this.attachEventListeners();
    }

    loadExistingMembers() {
        if (!this.memberListDisplay) return;

        if (this.memberAutocomplete) {
            this.memberAutocomplete.setSelectedMembers(this.memberListDisplay.getMembers());
        }
    }

    attachEventListeners() {
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

    handleMemberAdded(user) {
        if (this.memberListDisplay) {
            this.memberListDisplay.addMember(user);
        }
    }

    handleMemberRemoved(user) {
        if (this.memberAutocomplete) {
            this.memberAutocomplete.removeMember(user.id);
        }
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

    async submitTeam() {
        const teamName = document.getElementById('team_name')?.value;
        const teamDescription = document.getElementById('team_description')?.value;

        if (!teamName || teamName.trim() === '') {
            this.showError('Le nom de l\'équipe est requis');
            return;
        }

        const memberIds = this.memberListDisplay?.getMemberIds() || [];
        const formData = new FormData();

        formData.append('name', teamName.trim());

        if (teamDescription && teamDescription.trim()) {
            formData.append('description', teamDescription.trim());
        }

        formData.append('members', JSON.stringify(memberIds));

        if (this.selectedImage) {
            formData.append('image', this.selectedImage);
        }

        // Déterminer l'URL et la méthode en fonction du mode
        const url = this.mode === 'edit'
            ? `/designer/team/${this.teamId}/edit`
            : '/designer/team/create';

        const successMessage = this.mode === 'edit'
            ? 'Équipe modifiée avec succès !'
            : 'Équipe créée avec succès !';

        const redirectPath = this.mode === 'edit'
            ? `/designer/team/${this.teamId}/details`
            : null; // Sera défini par la réponse en mode création

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || `Erreur lors de ${this.mode === 'edit' ? 'la modification' : 'la création'} de l'équipe`);
            }

            this.showSuccess(successMessage);

            setTimeout(() => {
                window.location.href = redirectPath || `/designer/team/${result.team.id}/details`;
            }, 1000);
        } catch (error) {
            console.error(`Erreur lors de ${this.mode === 'edit' ? 'la modification' : 'la création'} de l'équipe:`, error);
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

document.addEventListener('DOMContentLoaded', () => {
    new TeamFormManager();
});
