unt.modules.messenger = {
	components: {
		dialog: function (chatObject) {
			let element = document.createElement('div');
			element.addEventListener('click', function (event) {
				return unt.actions.linkWorker.go('/messages?s=' + (chatObject.peer_id || ("b" + (chatObject.bot_peer_id * -1))));
			});

			element.classList = ['collection-item card waves-effect'];
			element.style.margin = '2px';
			element.style.height = '90px';
			element.style.marginLeft = element.style.marginRight = 0;
			element.style.width = '100%';
			element.style.padding = '20px';

			let chatInfoContainer = document.createElement('div');
			chatInfoContainer.classList.add('valign-wrapper');
			chatInfoContainer.style.height = '100%';
			element.appendChild(chatInfoContainer);

			let chatPhoto = document.createElement('img');
			chatPhoto.style.marginRight = '15px';
			chatInfoContainer.appendChild(chatPhoto);
			chatPhoto.classList.add('circle');
			chatPhoto.width = chatPhoto.height = 48;
			chatPhoto.src = chatObject.chat_info.data.photo_url;

			let chatInfo = document.createElement('div');
			chatInfo.style.height = '100%';
			chatInfoContainer.appendChild(chatInfo);

			let chatTitle = document.createElement('div');
			chatInfo.appendChild(chatTitle);
			chatTitle.style.height = '25px';
			chatTitle.style.overflow = 'hidden';
			chatTitle.style.textOverflow = 'ellipsis';

			let chatB = document.createElement('b');
			chatTitle.appendChild(chatB);
			chatB.innerText = chatObject.chat_info.data.title ? chatObject.chat_info.data.title : (chatObject.chat_info.data.name ? chatObject.chat_info.data.name : (chatObject.chat_info.data.first_name + ' ' + chatObject.chat_info.data.last_name));

			let previewMessage = document.createElement('div');
			previewMessage.innerText = unt.settings.lang.getValue('loading') + '...';
			chatInfo.appendChild(previewMessage);
			previewMessage.style.height = '25px';
			previewMessage.style.overflow = 'hidden';
			previewMessage.style.textOverflow = 'ellipsis';

			let previewMessageText = unt.modules.messenger.components.previewString(chatObject.last_message).then(function (string) {
				previewMessage.innerText = string;
			});

			return element;
		},
		previewString: function (lastMessage) {
			return new Promise(function (resolve) {
				return resolve(lastMessage.text || '...');
			});
		}
	},

	pages: new Object({
		realtime: function (event) {

		},

		dialog: function (url, internalData) {
			document.title = unt.settings.lang.getValue('message');
		},
		functions: {
			loadChats: function (resultDiv, loader, messagesDiv, loaderDiv, page = 1) {
				loaderDiv.show();
				loader.show();

				return new Promise(function (resolve, reject) {
					return unt.modules.messenger.getList((Number(page) * 30) - 30, 30).then(function (chats) {
						loaderDiv.hide();

						if (chats.length === 0 && page === 1) {
							resultDiv.show();

							return;
						} else if (chats.length === 0 && page != 1) {
							return;
						}

						for (let i = 0; i < chats.length; i++) {
							let messageElement = unt.modules.messenger.components.dialog(chats[i]);

							messagesDiv.appendChild(messageElement);
						}

						resultDiv.hide();
						messagesDiv.show();
					}).catch(function (err) {
						if (page === 1)
							resultDiv.show();

						console.log(err);

						return;
					});
				});
			}
		},
		dialogsList: function (url, internalData) {
			document.title = unt.settings.lang.getValue('messages');

			let menu = unt.components.menuElement;

			let chatActions = unt.components.floatingActionButton(unt.icons.edit, unt.settings.lang.getValue('write'), true);
			menu.appendChild(chatActions);

			let resultDiv = document.createElement('div');
			menu.appendChild(resultDiv);

			let messagesDiv = document.createElement('div');
			menu.appendChild(messagesDiv);

			let loaderDiv = document.createElement('div');
			menu.appendChild(loaderDiv);

			let loader = unt.components.loaderElement();
			loaderDiv.appendChild(loader);

			loaderDiv.style.padding = '20px';
			loaderDiv.style.textAlign = 'center';

			resultDiv.hide();
			messagesDiv.hide();
			loaderDiv.hide();

			messagesDiv.classList.add('collecion');

			return unt.modules.messenger.pages.functions.loadChats(resultDiv, loader, messagesDiv, loaderDiv);
		}
	}),

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