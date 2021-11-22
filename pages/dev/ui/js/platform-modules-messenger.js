unt.modules.messenger = {
	getList: function (offset = 0, count = 30) {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/messages',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_chats').append('offset', Number(offset) || 0).append('count', Number(count) || 0).build(),
				success: function (response) {
					try {
						response = JSON.parse(response);
						if (response.error)
							return reject(new TypeError('Unable to fetch messages'));
						
						return resolve(response);
					} catch (e) {
						return reject(e);
					}
				},
				error: function (err) {
					return reject(err);
				}
			});
		});
	},
	createChat: function () {},

	cachedChats: {},
	
	chat: class Chat {
		cachedMessages = [];

		chatObject = null;

		constructor (chatObject) {
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