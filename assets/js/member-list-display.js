export class MemberListDisplay {
    constructor(options = {}) {
        this.container = options.container;
        this.members = new Map();
        this.onRemove = options.onRemove || (() => {});
        this.showRemoveButton = options.showRemoveButton !== false;
        this.emptyMessage = options.emptyMessage || 'Aucun membre ajouté pour le moment';
        this.emptySubMessage = options.emptySubMessage || 'Utilisez la recherche ci-dessus pour ajouter des concepteurs';

        if (!this.container) {
            throw new Error('container est requis');
        }

        this.init();
    }

    init() {
        this.loadExistingMembers();

        this.render();
    }

    loadExistingMembers() {
        const existingMembers = this.container.querySelectorAll('.existing-member');

        existingMembers.forEach(memberElement => {
            const userId = parseInt(memberElement.dataset.userId);
            const userFullName = memberElement.dataset.userFullname;
            const userNickname = memberElement.dataset.userNickname;

            if (userId && userFullName && userNickname) {
                this.addMember({
                    id: userId,
                    fullName: userFullName,
                    nickname: userNickname
                });
            }
        });
    }

    addMember(member) {
        if (this.members.has(member.id)) {
            return;
        }
        this.members.set(member.id, member);
        this.render();
    }

    removeMember(userId) {
        this.members.delete(userId);
        this.render();
    }

    getMembers() {
        return Array.from(this.members.values());
    }

    getMemberIds() {
        return Array.from(this.members.keys());
    }

    render() {
        const membersContainer = this.container.querySelector('.space-y-2') || this.createMembersContainer();
        const countElement = this.container.querySelector('h3');

        if (countElement) {
            countElement.textContent = `Membres ajoutés (${this.members.size})`;
        }

        membersContainer.innerHTML = '';

        if (this.members.size === 0) {
            membersContainer.innerHTML = this.createEmptyStateHTML();
            return;
        }

        let index = 0;
        this.members.forEach((user) => {
            const memberElement = this.createMemberElement(user, index);
            membersContainer.appendChild(memberElement);
            index++;
        });
    }

    createMembersContainer() {
        const container = document.createElement('div');
        container.className = 'space-y-2';
        this.container.appendChild(container);
        return container;
    }

    createEmptyStateHTML() {
        return `
            <div class="p-6 text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="text-gray-500 text-sm">${this.emptyMessage}</p>
                <p class="text-gray-400 text-xs mt-1">${this.emptySubMessage}</p>
            </div>
        `;
    }

    createMemberElement(user, index) {
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
            ${this.showRemoveButton ? `
                <button type="button" class="remove-member text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition cursor-pointer" title="Retirer" data-user-id="${user.id}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            ` : ''}
        `;

        if (this.showRemoveButton) {
            const removeButton = memberElement.querySelector('.remove-member');
            removeButton?.addEventListener('click', () => {
                memberElement.style.opacity = '0';
                memberElement.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.removeMember(user.id);
                    this.onRemove(user);
                }, 200);
            });
        }

        return memberElement;
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
}
