unt.modules.messenger = {
	components: {
		fwd: function (msgFwdObject) {
			let element = document.createElement('blockquote');

			let messageAuthorAndTime = document.createElement('div');
			messageAuthorAndTime.classList.add('valign-wrapper');
			element.appendChild(messageAuthorAndTime);

			let userImage = document.createElement('img');
			userImage.width = userImage.height = 32;
			userImage.style.marginRight = '10px';
			userImage.classList.add('circle');
			messageAuthorAndTime.appendChild(userImage);

			let userData = document.createElement('div');
			messageAuthorAndTime.appendChild(userData);
			userData.classList.add('halign-wrapper');

			let userCredenials = document.createElement('a');
			userCredenials.setAttribute('target', '_blank');

			let messageTime = document.createElement('div');

			userData.appendChild(userCredenials);
			userData.appendChild(messageTime);

			messageTime.style.fontSize = '70%';
			userCredenials.style.color = 'black';
			userCredenials.style.fontWeight = 'bold';
			messageTime.innerText = unt.parsers.time(msgFwdObject.time);
			userCredenials.innerText = '...';

			let messageTextDiv = document.createElement('div');
			element.appendChild(messageTextDiv);
			messageTextDiv.innerHTML = nl2br(htmlspecialchars(msgFwdObject.text)).linkify();

			let attachmentsDiv = unt.parsers.attachments(msgFwdObject.attachments);
			element.appendChild(attachmentsDiv);

			unt.settings.users.get(msgFwdObject.from_id).then(function (user) {
				userImage.src = user.photo_url;
				userCredenials.innerText = user.name || (user.first_name + ' ' + user.last_name);
				userCredenials.href = '/' + (user.screen_name || (user.account_type === 'bot' ? ('bot' + user.bot_id) : ('id' + user.user_id)));
			}).catch(function (err) {
				userImage.remove();
				userCredenials.innerText = unt.settings.lang.getValue('deleted_accout');
			});

			if (msgFwdObject.fwd && msgFwdObject.fwd.length > 0) {
				for (let i = 0; i < msgFwdObject.fwd.length; i++) {
					element.appendChild(this.fwd(msgFwdObject.fwd[i]));
				}
			}

			return element;
		},
		serviceMessageText: function (msgObject) {
			let types = [
				"mute_user", "unmute_user", "returned_to_chat",
				"join_by_link", "leaved_chat", "updated_photo",
				"deleted_photo", "kicked_user", "invited_user",
				"change_title", "chat_create"
			];

			let event = msgObject.event ? msgObject.event.action : null;
			if (!event) return 'Event not supported';

			let resultedString = '';
			if (event === 'mute_user') resultedString = unt.settings.lang.getValue('mute_user');
			if (event === 'unmute_user') resultedString = unt.settings.lang.getValue('unmute_user');
			if (event === 'returned_to_chat') resultedString = unt.settings.lang.getValue('returned_to_chat');
			if (event === 'join_by_link') resultedString = unt.settings.lang.getValue('join_by_link');
			if (event === 'leaved_chat') resultedString = unt.settings.lang.getValue('leaved_the_chat');
			if (event === 'updated_photo') resultedString = unt.settings.lang.getValue('updated_photo');
			if (event === 'deleted_photo') resultedString = unt.settings.lang.getValue('deleted_photo');
			if (event === 'kicked_user') resultedString = unt.settings.lang.getValue('kicked_user');
			if (event === 'invited_user') resultedString = unt.settings.lang.getValue('invited_user');
			if (event === 'change_title') resultedString = unt.settings.lang.getValue('changed_title');
			if (event === 'chat_create') resultedString = unt.settings.lang.getValue('chat_create');

			return resultedString;
		},
		message: function (msgObject) {
			console.log(msgObject);

			let element = document.createElement('div');
			element.classList.add('valign-wrapper');
			element.style.display = 'flex';
			element.style.cursor = 'pointer';
			element.id = msgObject.id;

			let messageContainer = document.createElement('div');
			messageContainer.classList.add('card');
			messageContainer.style.margin = 0;
			element.style.marginTop = '10px';
			element.style.marginBottom = '3px';
			messageContainer.classList.add('message');
			messageContainer.classList.add('waves-effect');

			let messageTextBlock = document.createElement('div');
			messageTextBlock.innerHTML = nl2br(htmlspecialchars(msgObject.text)).linkify();

			let fwdDiv = document.createElement('div');
			let attachmentsDiv = unt.parsers.attachments(msgObject.attachments);

			let statesSection = document.createElement('div');
			statesSection.style.paddingTop = '2px';
			statesSection.style.textAlign = 'end';

			let timeDiv = document.createElement('div');
			timeDiv.classList.add('message-time');
			statesSection.appendChild(timeDiv);
			timeDiv.innerText = unt.parsers.time(msgObject.time, true);

			if (msgObject.from_id === unt.settings.users.current.user_id) {
				element.appendChild(messageContainer);
				element.style.justifyContent = 'end';

				messageContainer.classList.add('from-me');

				messageContainer.appendChild(messageTextBlock);
				messageContainer.appendChild(fwdDiv);
				messageContainer.appendChild(attachmentsDiv);
				messageContainer.appendChild(statesSection);
			} else {
				if (msgObject.type === 'message') {
					element.style.justifyContent = 'start';

					let userImage = document.createElement('img');
					userImage.style.marginBottom = 'auto';
					userImage.loading = 'lazy';

					userImage.style.marginRight = '10px';
					userImage.classList.add('circle');
					userImage.width = userImage.height = 32;

					element.appendChild(userImage);
					element.appendChild(messageContainer);

					messageContainer.classList.add('from-other');

					let userNameSection = document.createElement('a');
					userNameSection.setAttribute('target', '_blank');

					userNameSection.style.fontSize = '70%';
					messageContainer.appendChild(userNameSection);
					userNameSection.innerText = '...';

					messageContainer.appendChild(messageTextBlock);
					messageContainer.appendChild(fwdDiv);
					messageContainer.appendChild(attachmentsDiv);
					messageContainer.appendChild(statesSection);

					unt.settings.users.get(msgObject.from_id).then(function (user) {
						userImage.src = user.photo_url;
						userNameSection.innerText = user.name || (user.first_name + ' ' + user.last_name);
						userNameSection.href = '/' + (user.screen_name || (user.account_type === 'bot' ? ('bot' + user.bot_id) : ('id' + user.user_id)));
					}).catch(function (err) {
						userImage.remove();
						userNameSection.innerText = unt.settings.lang.getValue('deleted_accout');
					});
				}
			}
			if (msgObject.type === 'service_message') {
				element.innerHTML = '';
				element.style.justifyContent = 'center';

				let forTextDiv = document.createElement('div');
				forTextDiv.classList.add('waves-effect');
				forTextDiv.classList.add('message');
				forTextDiv.classList.add('card');
				forTextDiv.style.padding = '10px';
				forTextDiv.style.margin = 0;
				forTextDiv.style.textAlign = 'center';
				element.appendChild(forTextDiv);
				//forTextDiv.innerHTML = unt.settings.lang.getValue('loading');

				let loaderDiv = document.createElement('div');
				loaderDiv.classList.add('valign-wrapper');
				forTextDiv.appendChild(loaderDiv);

				let loader = unt.components.loaderElement();
				loader.setArea(20);
				loader.style.display = 'grid';
				loader.style.marginRight = '10px';
				loaderDiv.appendChild(loader);

				let loadText = document.createElement('div');
				loaderDiv.appendChild(loadText);
				loadText.innerText = unt.settings.lang.getValue('loading');

				let msgStringElement = document.createElement('div');
				msgStringElement.innerText = this.serviceMessageText(msgObject);
				forTextDiv.appendChild(msgStringElement);
				msgStringElement.hide();

				if (msgStringElement.innerText.match('%who%')) {
					unt.settings.users.get(msgObject.from_id).then(function (user) {
						msgStringElement.innerHTML = msgStringElement.innerHTML.replace('(а)', (user.gender == 2 ? 'а' : ''));
						msgStringElement.innerHTML = msgStringElement.innerHTML.replace('лся(-ась)', (user.gender == 2 ? 'лась' : 'лся'));

						msgStringElement.innerHTML = msgStringElement.innerHTML.replace('%who%', '<b><a target="_blank" href="/' + (user.screen_name || (user.account_type === 'bot' ? ('bot' + user.bot_id) : ('id' + user.user_id))) + '" style="color: black">' + (
							user.name || (user.first_name + ' ' + user.last_name)
						) + '</a></b>');

						loaderDiv.hide();
						msgStringElement.show();
					}).catch(function (err) {
						msgStringElement.innerHTML = msgStringElement.innerHTML.replace('%who%', '<b>' + unt.settings.lang.getValue('deleted_accout') + '</b>');

						loaderDiv.hide();
						msgStringElement.show();
					});
				}
				if (msgStringElement.innerText.match('%when%')) {
					unt.settings.users.get(msgObject.event.to_id || 0).then(function (user) {
						let change = 'acc';
						if (msgObject.event.action === 'mute_user' || msgObject.event.action === 'unmute_user') change = 'dat';

						msgStringElement.innerHTML = msgStringElement.innerHTML.replace('%when%', '<b><a target="_blank" href="/' + (user.screen_name || (user.account_type === 'bot' ? ('bot' + user.bot_id) : ('id' + user.user_id))) + '" style="color: black">' + (
							user.name || (user.name_cases.first_name[change] + ' ' + user.name_cases.last_name[change])
						) + '</a></b>');

						loaderDiv.hide();
						msgStringElement.show();
					}).catch(function (err) {
						msgStringElement.innerHTML = msgStringElement.innerHTML.replace('%when%', '<b>' + unt.settings.lang.getValue('deleted_accout') + '</b>');

						loaderDiv.hide();
						msgStringElement.show();
					});
				}
				if (msgStringElement.innerText.match('%title%')) {
					msgStringElement.innerHTML = msgStringElement.innerHTML.replace('%title%', '<b>' + htmlspecialchars(msgObject.event.new_title) + '</b>');
				}
			}

			if (msgObject.fwd && msgObject.fwd.length > 0) {
				for (let i = 0; i < msgObject.fwd.length; i++) {
					fwdDiv.appendChild(this.fwd(msgObject.fwd[i]));
				}
			}

			element.addEventListener('contextmenu', function (event) {
				event.preventDefault();

				let elements = [];

				if (!msgObject.text.isEmpty())
					elements.push([unt.settings.lang.getValue('copy_text'), function () {
						if (msgObject.text.copy()) return unt.toast({html: unt.settings.lang.getValue('copied')});
						else return unt.toast({html: unt.settings.lang.getValue('upload_error')});
					}]);

				if (elements.length > 0)
					return unt.components.contextMenu(elements).open(event);
			});

			return element;
		},
		dialog: function (chatObject) {
			let element = document.createElement('div');
			element.addEventListener('click', function (event) {
				return unt.actions.linkWorker.go('/messages?s=' + (chatObject.peer_id || ("b" + (chatObject.bot_peer_id * -1))), true, chatObject);
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
		dialog: function (url, internalData) {
			document.title = unt.settings.lang.getValue('message');
		},
		chat: function (chatObject) {
			let menu = unt.components.menuElement;

			document.title = unt.settings.lang.getValue('message') + ' | ' + (chatObject.chat_info.is_multi_chat ? chatObject.chat_info.data.title : (chatObject.chat_info.data.name || (chatObject.chat_info.data.first_name + ' ' + chatObject.chat_info.data.last_name)));
		
			let dialogContainer = document.createElement('div');
			menu.appendChild(dialogContainer);

			dialogContainer.style.display = 'flex';
			dialogContainer.style.flexDirection = 'column';
			dialogContainer.style.width = dialogContainer.style.height = '100%';

			let messagesContainer = document.createElement('div');
			messagesContainer.style.height = '100%';
			messagesContainer.style.display = 'flex';
			messagesContainer.style.flexDirection = 'column';
			dialogContainer.appendChild(messagesContainer);

			let chatInputField = unt.components.cardInputArea(unt.settings.lang.getValue('write_a_message'));
			chatInputField.style.display = 'flex';

			if ((chatObject.chat_info.is_multi_chat && (chatObject.metadata.permissions.is_kicked || chatObject.metadata.permissions.is_muted)) || 
				(!chatObject.chat_info.is_multi_chat && !chatObject.chat_info.data.can_write_messages)) {
				chatInputField.close(unt.settings.lang.getValue('cant_chat'));
			} else {
				chatInputField.open();
			}

			let messagesLoader = unt.components.loaderElement();
			messagesContainer.appendChild(messagesLoader);

			messagesLoader.style.display = 'flex';
			messagesLoader.style.justifyContent = 'center';
			messagesLoader.style.height = '100%';
			messagesLoader.style.alignItems = 'center';

			let resultDiv = document.createElement('div');
			messagesContainer.appendChild(resultDiv);
			resultDiv.hide();

			let resultTextDiv = document.createElement('b');
			resultDiv.appendChild(resultTextDiv);
			resultTextDiv.style.width = '60%';
			resultTextDiv.style.textAlign = 'center';
			resultTextDiv.style.display = 'flex';

			resultDiv.style.justifyContent = 'center';
			resultDiv.style.height = '100%';
			resultDiv.style.alignItems = 'center';

			let messageListContainer = document.createElement('div');
			messagesContainer.appendChild(messageListContainer);
			messageListContainer.hide();
			messageListContainer.style.width = '100%';
			messageListContainer.style.height = '100%';
			messageListContainer.style.flexDirection = 'column';
			messageListContainer.style.overflow = 'auto';

			messageListContainer.classList.add('hidesc');
			messageListContainer.classList.add('unselectable');

			messagesContainer.appendChild(chatInputField);
			unt.textareaAutoResize(chatInputField.getInput());

			return unt.modules.messenger.getMessages(chatObject.peer_id || ('b' + (chatObject.bot_peer_id * -1))).then(function (response) {
				if (response.length <= 0) {
					messagesLoader.hide();
					resultDiv.style.display = 'flex';
					
					return resultTextDiv.innerText = unt.settings.lang.getValue('empty_dialog');
				}

				response.forEach(function (message) {
					let messageElement = unt.modules.messenger.components.message(message);

					messageListContainer.appendChild(messageElement);
				});

				messagesLoader.hide();
				resultDiv.hide();
				messageListContainer.style.display = 'flex';

				messageListContainer.scrollTo(0, messageListContainer.scrollHeight);
			}).catch(function (err) {
				messagesLoader.hide();
				resultDiv.style.display = 'flex';
				resultTextDiv.innerText = unt.settings.lang.getValue('failed_to_load_chat');
			});
		},
		functions: {
			loadChats: function (resultDiv, messagesDiv, loaderDiv, page = 1) {
				loaderDiv.show();

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

	dialogs: {},
	messages: {},

	getMessages: function (chatId, page) {
		return new Promise(function (resolve, reject) {
			if (unt.modules.messenger.messages[chatId] && unt.modules.messenger.messages[chatId][page]) return resolve(unt.modules.messenger.messages[chatId][page]);

			return unt.tools.Request({
				url: '/messages',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_messages').append('peer_id', chatId).append('offset', Number(page - 1) * 100).append('count', Number(page) * 100).build(),
				success: function (response) {
					try {
						response = JSON.parse(response);
						if (response.error)
							return reject(e);

						if (!unt.modules.messenger.messages[chatId])
							unt.modules.messenger.messages[chatId] = {};

						return resolve(unt.modules.messenger.messages[chatId][page] = response.list);
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

	getChatByPeerId: function (peer_id) {
		let o = this;

		return new Promise(function (resolve, reject) {
			if (o.dialogs[peer_id]) return resolve(o.dialogs[peer_id]);

			return unt.tools.Request({
				method: 'POST',
				url: '/flex',
				data: (new POSTData()).append('action', 'get_chat_by_peer').append('peer_id', peer_id).build(),
				success: function (response) {
					try {
						response = JSON.parse(response);

						if (response.error)
							return reject(new Error('Invalid peer id?'));

						return resolve(o.dialogs[peer_id] = response);
					} catch (e) {
						return reject(e);
					}
				},
				error: reject
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