unt.modules.messenger = {
	getList: function () {},
	createChat: function () {},

	cachedChats: {},
	
	chat: class Chat {
		cachedMessages = [];

		chatObject = null;

		constructor (chatObject) {
			if (!(chatObject instanceof Object))
				throw new Error('Only the objects allowed');

			this.chatObject = chatObject;
		}

		sendMessage (text, attachments, fwd) {}

		clear () {}

		getMessages (ofset, count) {}

		deleteMessge () {}

		setTitle (title) {}

		setPhoto (photo = null) {}

		addEntity (entity_id) {}

		removeEntity (entity_id) {}

		changeWriteAccess (entity_id) {}

		setUserPermissionsLevel (entity_id, new_level) {}

		changeOwner (user_id) {}

		getInfo () {}

		getMembers () {}

		setPermissions (group, value) {}

		toggleNOtifications () {}

		togglePinnedMessage () {}

		getInviteLink () {}

		updateInviteLink () {}

		getPhoto () {}

		getTitle () {}

		getChatObject () {}

		read () {}
	}
};