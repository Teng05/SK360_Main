    const groupDialog = document.createElement('dialog');
    groupDialog.className = 'm-auto w-[min(92vw,480px)] max-h-[85dvh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl backdrop:bg-black/50';
    groupDialog.setAttribute('aria-labelledby', 'groupDialogTitle');
    groupDialog.innerHTML = `
        <form id="newGroupForm" class="space-y-4">
            <h2 id="groupDialogTitle" class="text-xl font-bold">Create group chat</h2>
            <div>
                <label for="newGroupName" class="block text-sm font-semibold">Group name</label>
                <input id="newGroupName" required maxlength="80" class="mt-1 w-full rounded-lg border p-3" placeholder="Enter a group name">
            </div>
            <div>
                <label for="groupMemberSearch" class="block text-sm font-semibold">Add members</label>
                <input id="groupMemberSearch" type="search" class="mt-1 w-full rounded-lg border p-3" placeholder="Search registered accounts">
                <p class="mt-2 text-xs text-gray-500">You are included automatically. Select at least one member.</p>
            </div>
            <div id="groupMemberList" class="max-h-64 overflow-y-auto space-y-1"></div>
            <p id="groupSelectionCount" class="text-sm text-gray-500" aria-live="polite"></p>
            <p id="groupCreateError" class="text-sm text-red-600" role="alert"></p>
            <div class="flex justify-end gap-2">
                <button type="button" id="cancelNewGroup" class="rounded-lg border px-4 py-2">Cancel</button>
                <button type="submit" id="saveNewGroup" class="rounded-lg bg-red-600 px-4 py-2 text-white disabled:opacity-50">Create group</button>
            </div>
        </form>`;
    document.body.append(groupDialog);
    const groupForm = groupDialog.querySelector('form');
    const groupNameInput = groupDialog.querySelector('#newGroupName');
    const memberSearch = groupDialog.querySelector('#groupMemberSearch');
    const memberList = groupDialog.querySelector('#groupMemberList');
    const groupError = groupDialog.querySelector('#groupCreateError');
    const saveGroup = groupDialog.querySelector('#saveNewGroup');
    const cancelGroup = groupDialog.querySelector('#cancelNewGroup');
    const selectedGroupMembers = new Set();
    const eligibleGroupMembers = groupMembers.filter(member => String(member.id) !== String(currentUser.id));
    let savingGroup = false;

    function renderGroupMembers() {
        memberList.replaceChildren();
        const keyword = memberSearch.value.trim().toLowerCase();
        const matching = eligibleGroupMembers.filter(member => `${member.name} ${roleLabel(member.role)}`.toLowerCase().includes(keyword));
        matching.forEach(member => {
            const label = document.createElement('label');
            label.className = 'flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-gray-50';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = selectedGroupMembers.has(String(member.id));
            checkbox.disabled = savingGroup;
            checkbox.addEventListener('change', () => {
                checkbox.checked ? selectedGroupMembers.add(String(member.id)) : selectedGroupMembers.delete(String(member.id));
                updateGroupCount();
            });
            const text = document.createElement('span');
            text.textContent = `${member.name} — ${roleLabel(member.role)}`;
            text.className = 'text-sm';
            label.append(checkbox, text);
            memberList.append(label);
        });
        if (!matching.length) memberList.textContent = 'No matching registered accounts.';
        updateGroupCount();
    }

    function updateGroupCount() {
        groupDialog.querySelector('#groupSelectionCount').textContent = `${selectedGroupMembers.size} selected`;
    }

    function openFederationGroup() {
        groupForm.reset();
        selectedGroupMembers.clear();
        groupError.textContent = '';
        renderGroupMembers();
        groupDialog.showModal();
        groupNameInput.focus();
    }
    memberSearch.addEventListener('input', renderGroupMembers);
    cancelGroup.addEventListener('click', () => groupDialog.close());
    groupDialog.addEventListener('cancel', event => { if (savingGroup) event.preventDefault(); });
    groupForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (savingGroup) return;
        const name = groupNameInput.value.trim();
        const members = eligibleGroupMembers.filter(member => selectedGroupMembers.has(String(member.id)));
        if (!name || name.length > 80 || !members.length) {
            groupError.textContent = 'Enter a group name and select at least one registered account.';
            return;
        }
        members.push({ id: String(currentUser.id), name: currentUser.name, role: currentUser.role });
        savingGroup = true;
        saveGroup.disabled = cancelGroup.disabled = groupNameInput.disabled = memberSearch.disabled = true;
        saveGroup.textContent = 'Creating...';
        groupError.textContent = '';
        renderGroupMembers();
        try {
            const roomData = {
                name,
                type: 'group',
                groupKind: 'custom',
                createdBy: String(currentUser.id),
                createdAt: serverTimestamp(),
                memberIds: members.map(member => String(member.id)),
                memberNames: members.map(member => member.name)
            };
            const room = await addDoc(collection(db, 'chat_rooms'), roomData);
            if (!rooms.some(item => item.id === room.id)) rooms.unshift({
                id: room.id, name, subtitle: `${members.length} members`, color: 'bg-red-500',
                initials: initialsFor(name), createdAtSeconds: Date.now() / 1000
            });
            activeRoomId = room.id;
            roomSearch.value = '';
            renderRooms();
            subscribeToMessages();
            groupDialog.close();
        } catch (error) {
            groupError.textContent = 'Unable to create the group. Please try again.';
            console.error(error);
        } finally {
            savingGroup = false;
            saveGroup.disabled = cancelGroup.disabled = groupNameInput.disabled = memberSearch.disabled = false;
            saveGroup.textContent = 'Create group';
            renderGroupMembers();
        }
    });
