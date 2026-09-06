import { ToastManager } from 'modal-manager';

/**
 * Page de détails d'une chasse : les boutons de transition (fermer, republier)
 * envoient au workflow, qui reste seul juge de ce qui est permis.
 */
class HuntDetailsManager {
    constructor() {
        this.toast = new ToastManager('globalToast');
        document.querySelectorAll('[data-transition-url]').forEach((button) => {
            button.addEventListener('click', () => this.transition(button));
        });
    }

    async transition(button) {
        if (button.dataset.confirm && !window.confirm(button.dataset.confirm)) {
            return;
        }

        const formData = new FormData();
        formData.append('_token', button.dataset.csrfToken);

        try {
            const response = await fetch(button.dataset.transitionUrl, {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json' },
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(result.error || 'Une erreur est survenue.');
            }
            this.toast.success(result.message);
            window.setTimeout(() => window.location.reload(), 800);
        } catch (error) {
            this.toast.error(error.message);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => new HuntDetailsManager());
