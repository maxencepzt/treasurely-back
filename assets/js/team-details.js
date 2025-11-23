/**
 * Gestionnaire spécifique pour les équipes
 * Utilise le système de modals global
 */

import { ModalManager, ToastManager } from 'modal-manager';

// Initialiser les gestionnaires
const modal = new ModalManager('globalModal');
const toast = new ToastManager('globalToast');

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


