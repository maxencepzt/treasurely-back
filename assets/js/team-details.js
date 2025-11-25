import { ModalManager, ToastManager } from 'modal-manager';
import { MemberAutocomplete } from 'member-autocomplete';

// Initialiser les gestionnaires
const modal = new ModalManager('globalModal');
const toast = new ToastManager('globalToast');

// Variable pour stocker l'instance d'autocomplétion et l'utilisateur sélectionné
let memberAutocomplete = null;
let selectedUser = null;

// Bouton de transfert de la propriété de l'équipe
const transferOwnershipBtn = document.getElementById('transferOwnershipBtn');
// TODO : autocomplete pour le nouveau propriétaire parmi les membres existants
if (transferOwnershipBtn) {
    transferOwnershipBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const teamId = this.dataset.teamId;
        const teamName = this.dataset.teamName;

        modal.show({
            type: 'warning',
            title: 'Transférer la propriété de l\'équipe ?',
            message: `Êtes-vous sûr de vouloir transférer la propriété de l'équipe <strong>${teamName}</strong> ?`,
            warning: 'Vous ne serez plus le propriétaire de cette équipe et perdrez vos droits d\'administration de l\'équipe.',
            confirmText: 'Transférer',
            customIcon: {
                container: 'bg-yellow-100',
                icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
            },
            onConfirm: (modalInstance) => {
                modalInstance.setLoading('Transfert en cours...');

                fetch(`/designer/team/${teamId}/transfer-ownership`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    modalInstance.hide();
                    if (data.success) {
                        toast.success(data.message);
                        setTimeout(() => {
                            window.location.href = '/designer/team';
                        }, 1000);
                    }
                    else {
                        toast.error(data.error || 'Une erreur est survenue');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modalInstance.hide();
                    toast.error('Une erreur est survenue lors du transfert de propriété de l\'équipe');
                });
            }
        });
    });
}

// Bouton de suppression d'équipe
const deleteTeamBtn = document.getElementById('deleteTeamBtn');
if (deleteTeamBtn) {
    deleteTeamBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const teamId = this.dataset.teamId;
        const teamName = this.dataset.teamName;

        modal.show({
            type: 'danger',
            title: 'Supprimer l\'équipe ?',
            message: `Êtes-vous sûr de vouloir supprimer l'équipe <strong>${teamName}</strong> ?`,
            warning: '⚠️ Cette action est irréversible et supprimera également toutes les chasses au trésor associées.',
            confirmText: 'Supprimer',
            onConfirm: (modalInstance) => {
                modalInstance.setLoading('Suppression...');

                fetch(`/designer/team/${teamId}/delete`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    modalInstance.hide();
                    if (data.success) {
                        toast.success(data.message);
                        setTimeout(() => {
                            window.location.href = '/designer/team';
                        }, 1500);
                    } else {
                        toast.error(data.error || 'Une erreur est survenue');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modalInstance.hide();
                    toast.error('Une erreur est survenue lors de la suppression de l\'équipe');
                });
            }
        });
    });
}

// Bouton de sortie d'équipe
const leaveTeamBtn = document.getElementById('leaveTeamBtn');
if (leaveTeamBtn) {
    leaveTeamBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const teamId = this.dataset.teamId;
        const teamName = this.dataset.teamName;

        modal.show({
            type: 'warning',
            title: 'Quitter l\'équipe ?',
            message: `Êtes-vous sûr de vouloir quitter l'équipe <strong>${teamName}</strong> ?<br><br>Vous n'aurez plus accès aux chasses au trésor de cette équipe.`,
            confirmText: 'Quitter',
            onConfirm: (modalInstance) => {
                modalInstance.setLoading('En cours...');

                fetch(`/designer/team/${teamId}/leave`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    modalInstance.hide();
                    if (data.success) {
                        toast.success(data.message);
                        setTimeout(() => {
                            window.location.href = '/designer/team';
                        }, 1500);
                    } else {
                        toast.error(data.error || 'Une erreur est survenue');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modalInstance.hide();
                    toast.error('Une erreur est survenue');
                });
            }
        });
    });
}

// Bouton d'ajout de membre
const addMemberBtn = document.getElementById('addMemberBtn');
if (addMemberBtn) {
    addMemberBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const teamId = this.dataset.teamId;

        // Réinitialiser la sélection
        selectedUser = null;

        modal.show({
            type: 'info',
            title: 'Ajouter un membre à l\'équipe',
            message: `
                <div class="text-center">
                    <label for="modal_member_search" class="block text-sm font-semibold text-gray-700 mb-2">
                        Rechercher un utilisateur
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="modal_member_search" 
                            placeholder="Nom, prénom ou pseudo..." 
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            autocomplete="off"
                        />
                        <div id="modal_autocomplete_results" class="hidden absolute z-10 w-full mt-2 bg-white rounded-xl shadow-xl border border-gray-200 max-h-64 overflow-y-auto"></div>
                    </div>
                    <div id="modal_selected_member" class="hidden mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm overflow-hidden">
                                    <img id="modal_selected_avatar" src="" alt="" />
                                </div>
                                <div>
                                    <p id="modal_selected_name" class="font-semibold text-gray-800"></p>
                                    <p id="modal_selected_nickname" class="text-sm text-gray-500"></p>
                                </div>
                            </div>
                            <button type="button" id="modal_remove_selection" class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition" title="Retirer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `,
            warning: 'Le membre aura accès à toutes les chasses au trésor de cette équipe.',
            confirmText: 'Ajouter',
            onConfirm: (modalInstance) => {
                if (!selectedUser) {
                    toast.error('Veuillez sélectionner un utilisateur');
                    return;
                }

                modalInstance.setLoading('Ajout du membre...');

                fetch(`/designer/team/${teamId}/add-member/${selectedUser.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    modalInstance.hide();
                    if (data.success) {
                        toast.success(data.message);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        toast.error(data.error || 'Une erreur est survenue');
                    }
                })
                .catch(error => {
                    console.error('Erreur :', error);
                    modalInstance.hide();
                    toast.error('Une erreur est survenue lors de l\'ajout du membre à l\'équipe');
                });
            }
        });

        // Initialiser l'autocomplétion après l'affichage du modal
        setTimeout(() => {
            const searchInput = document.getElementById('modal_member_search');
            const autocompleteResults = document.getElementById('modal_autocomplete_results');
            const selectedMemberDiv = document.getElementById('modal_selected_member');
            const removeSelectionBtn = document.getElementById('modal_remove_selection');

            if (searchInput && autocompleteResults) {
                memberAutocomplete = new MemberAutocomplete({
                    searchInput,
                    autocompleteResults,
                    onMemberAdded: (user) => {
                        selectedUser = user;

                        // Afficher l'utilisateur sélectionné
                        document.getElementById('modal_selected_avatar').src = `/api/users/${user.id}/picture/`;
                        document.getElementById('modal_selected_avatar').alt = user.nickname;
                        document.getElementById('modal_selected_name').textContent = user.fullName;
                        document.getElementById('modal_selected_nickname').textContent = `@${user.nickname}`;

                        selectedMemberDiv.classList.remove('hidden');
                        searchInput.value = '';
                        autocompleteResults.classList.add('hidden');
                    },
                    onError: (message) => toast.error(message),
                    designerTeamId: teamId,
                });

                // Bouton pour retirer la sélection
                if (removeSelectionBtn) {
                    removeSelectionBtn.addEventListener('click', () => {
                        selectedUser = null;
                        selectedMemberDiv.classList.add('hidden');
                        if (memberAutocomplete) {
                            memberAutocomplete.clearSelectedMembers();
                        }
                    });
                }
            }
        }, 100);
    });
}

const removeMemberButtons = document.querySelectorAll('.removeMemberBtn');
removeMemberButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const teamId = this.dataset.teamId;
        const userId = this.dataset.userId;
        const userName = this.dataset.nickname;

        modal.show({
            type: 'warning',
            title: 'Retirer le membre de l\'équipe ?',
            message: `Êtes-vous sûr de vouloir retirer <strong>${userName}</strong> de l'équipe ?`,
            warning: 'Il n\'aura plus accès aux chasses au trésor de cette équipe.',
            confirmText: 'Retirer',
            onConfirm: (modalInstance) => {
                modalInstance.setLoading('En cours...');

                fetch(`/designer/team/${teamId}/remove-member/${userId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    modalInstance.hide();
                    if (data.success) {
                        toast.success(data.message);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        toast.error(data.error || 'Une erreur est survenue');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modalInstance.hide();
                    toast.error('Une erreur est survenue');
                });
            }
        });
    });
});