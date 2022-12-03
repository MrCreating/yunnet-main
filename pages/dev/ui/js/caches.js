class ChatError extends Error {
	constructor (text) {
		super(text);

		this.message = text;
		this.name = "ChatError";
	}
}

class SendError extends Error {
	constructor (text) {
		super(text);

		this.message = text;
		this.name = "SendError";
	}
}

class Data extends Array {
	addValue (key, value) {
		return ((this[key] = value) && this);
	}
}

class Selection extends Array {}

class Chat {
	constructor (peer_id = 0, chatObject = null) {
		this.chat = Chat;
		this.current = this;

		this.selectedMessages = new Selection();
		this.peer_id = 0;
		this.messagesCache = new (class MessageCache extends Data {
			addValue (key, value) {
				if (this.getLength() >= 100) {
					for (let key in this) {
						delete this[key];

						if (this.getLength() < 100) break;
					}
				}

				this[key] = value;
			}

			getLength () {
				for (let key in this) {
					let length = 0;

					if (typeof this[key] !== "function") length++;

					return length;
				}
			} 
		})();
	
		this.createdDates = new Data();
		this.chatObject = null;

		if (messages.cache[peer_id]) throw new ChatError("Chat already has been initialized");
		if (!peer_id) throw new ChatError("Peer id must be not 0");

		this.peer_id = peer_id;

		if (this.peer_id < 0) {
			this.permissions = {};

			this.toggleUser = function () {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'toggle_my_state');
					data.append('peer_id', currentChat.peer_id);
					return ui.Request({
						url: '/messages',
						data: data,
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);

							if (response.error) return reject(new ChatError('State changing error'));
							return resolve(response.result);
						}
					});
				});
			}

			this.setUserLevel = function (user_id, new_level) {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'set_user_level');
					data.append('peer_id', currentChat.peer_id);
					data.append('user_id', user_id);
					data.append('new_level', new_level);
					return ui.Request({
						url: '/messages',
						data: data,
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error) return reject(new ChatError('Setting user level failed by error from server'));

							return resolve(true);
						}
					});
				});
			}

			this.toggleWriteAccess = function (user_id) {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'toggle_write_access');
					data.append('peer_id', currentChat.peer_id);
					data.append('user_id', user_id);
					return ui.Request({
						url: '/messages',
						data: data,
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error) return reject(new ChatError('Toggle write access failed by error from server'));

							return resolve(response.state || 0);
						}
					});
				});
			}

			this.removeUser = function (user_id) {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'remove_user');
					data.append('peer_id', currentChat.peer_id);
					data.append('user_id', user_id);

					return ui.Request({
						url: '/messages',
						data: data,
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error) return reject(new ChatError('Kick failed by error from server'));

							return resolve(true);
						}
					});
				});
			}

			this.getMembers = function () {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					if (currentChat.cachedMembers) return resolve(currentChat.cachedMembers);

					let data = new FormData();

					data.append('action', 'get_members');
					data.append('peer_id', currentChat.peer_id);
					return ui.Request({
						data: data,
						method: 'POST',
						url: '/messages',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error) return reject(new ChatError('Messages fetching error'));

							return resolve(currentChat.cachedMembers = response);
						}
					});
				});
			}

			this.setPermissions = function (groupName, value) {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'update_chat_permissions');
					data.append('group_name', groupName);
					data.append('value', Number(value));
					data.append('peer_id', currentChat.peer_id);

					return ui.Request({
						data: data,
						url: '/messages',
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);

							if (response.error) return reject(new ChatError('Permissions changing error'));
							return resolve(true);
						}
					});
				});
			}

			this.addMembers = function (members = []) {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'add_user');
					data.append('user_ids', members.join(','));
					data.append('peer_id', currentChat.peer_id);

					return ui.Request({
						data: data,
						url: '/messages',
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);

							if (response.response) {
								return resolve(true);
							}

							if (response.error) {
								let error = new ChatError('Invitation error.');

								error.errorCode = response.error;
								return reject(error);
							}
						}
					});
				});
			}

			this.getCurrentPermissionsLevel = function () {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					if (currentChat.currentPermissionsLevel) return resolve(currentChat.currentPermissionsLevel);

					let data = new FormData();

					data.append('action', 'get_my_permissions_level');
					data.append('peer_id', currentChat.peer_id);

					return ui.Request({
						data: data,
						url: '/flex',
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);
							if (response.unauth) return settings.raLogin();

							if (response.error) return reject(new ChatError("Chat permissions level fetch error"));
							return resolve(currentChat.currentPermissionsLevel = response.level);
						}
					});
				});
			}

			this.getPermissions = function () {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					if (currentChat.chatPermissions) return resolve(currentChat.chatPermissions);

					let data = new FormData();

					data.append('action', 'get_chat_permissions');
					data.append('peer_id', currentChat.peer_id);

					return ui.Request({
						data: data,
						url: '/flex',
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);
							if (response.unauth) return settings.raLogin();

							if (response.error) return reject(new ChatError("Chat permissions fetch error"));
							return resolve(currentChat.chatPermissions = response);
						}
					});
				});
			}

			this.getJoinLink = function () {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					if (currentChat.currentLink) return resolve(currentChat.currentLink);

					let data = new FormData();

					data.append('action', 'get_chat_link');
					data.append('peer_id', currentChat.peer_id);
					return ui.Request({
						data: data,
						method: 'POST',
						url: '/messages',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error) return reject(new TypeError('Unable to fetch the link'));

							if (response.unauth) return settings.reLogin();

							currentChat.currentLink = response.response;
							return resolve(response.response);
						}
					});
				});
			}

			this.updateJoinLink = function () {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'update_chat_link');
					data.append('peer_id', currentChat.peer_id);
					return ui.Request({
						data: data,
						method: 'POST',
						url: '/messages',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error) return reject(new TypeError('Unable to fetch the link'));

							if (response.unauth) return settings.reLogin();

							currentChat.currentLink = response.response;
							return resolve(response.response);
						}
					});
				});
			}

			this.updatePhoto = function (photoCredentials = null) {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					if (photoCredentials !== null) {
						data.append('action', 'update_chat_photo');
						data.append('photo', photoCredentials);
					}
					else
						data.append('action', 'delete_chat_photo');
					
					data.append('peer_id', currentChat.peer_id);
					return ui.Request({
						data: data,
						url: '/messages',
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error) return reject(new ChatError('Unable to edit chat photo'));

							return resolve(true);
						}
					});
				});
			}

			this.setTitle = function (newTitle = '') {
				let currentChat = this;

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'set_chat_title');
					data.append('new_title', newTitle.toString());
					data.append('peer_id', currentChat.peer_id);

					return ui.Request({
						data: data,
						url: '/messages',
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error) return reject(new ChatError('Unable to edit chat photo'));

							return resolve(true);
						}
					});
				});
			}
		}

		this.chatObject = chatObject;
		return messages.cache[peer_id] = this;
	}

	clearChatCache () {
		this.chatObject = null;
		this.permissions = null;
		this.chatPermissions = null;

		messages.cache[this.peer_id] = null;

		return this;
	}

	read () {
		let currentChat = this;

		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'read_chat');
			data.append('peer_id', currentChat.peer_id);
			return ui.Request({
				url: '/messages',
				method: 'POST',
				data: data
			});
		});
	}

	clearHistory () {
		let currentChat = this;

		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'clear');
			data.append('chat_id', currentChat.peer_id);
			return ui.Request({
				url: '/messages',
				method: 'POST',
				data: data,
				success: function (response) {
					if (String(response).isEmpty()) return reject(new ChatError('Chat clearing fail.'));

					response = JSON.parse(response);
					if (response.unauth) return settings.reLogin();

					if (!(response instanceof Array)) return reject(new ChatError('Chat clearing fail.'));
					return resolve(currentChat.clearChatCache());
				}
			});
		});
	}

	saveMessage (message_id, text = '', attachments = '', fwd = '') {
		let currentChat = this;

		return new Promise(function (resolve, reject) {
			currentChat.getInfo().then(function (chatObject) {
				if (chatObject.permissions) {
					if (chatObject.permissions.is_kicked) return reject(new SendError('You kicked from this chat'));
					if (chatObject.permissions.is_muted) return reject(new SendError('You muted in this chat'));
				}
			})

			if (String(text).isEmpty() && String(attachments).isEmpty() && String(fwd).isEmpty()) 
				return reject(new SendError('Text or attachments or fwd must not be empty'));

			let data = new FormData();

			data.append('action', 'save_message');
			data.append('peer_id', currentChat.peer_id.toString());
			data.append('text', text.toString());
			data.append('attachments', attachments.toString());
			data.append('fwd', fwd.toString());
			data.append('message_id', Number(message_id));
			return ui.Request({
				url: '/messages',
				data: data,
				xhrFields: {
					withCredentials: true
				},
				method: 'POST',
				success: function (response) {
					if (String(response).isEmpty()) return reject(new SendError('Response is empty'));
					try {
						response = JSON.parse(response);
						if (response.unauth) return settings.reLogin();

						if (response.error) return reject(new SendError('Unable to send message'));

						return resolve(response.id);
					} catch (e) {
						return reject(new SendError('Received an invalid response'));
					}
				}
			});
		})
	}

	toggleNotifications () {
		let currentChat = this;

		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'toggle_notifications');
			data.append('peer_id', currentChat.peer_id.toString());
			data.append('new_value', Number(!currentChat.chatObject.metadata.notifications).toString());

			return ui.Request({
				url: '/messages',
				data: data,
				method: 'POST',
				success: function (response) {
					if (String(response).isEmpty()) return reject(new SendError('Response is empty'));
					try {
						response = JSON.parse(response);
						if (response.unauth) return settings.reLogin();

						if (response.error) return reject(new SendError('Unable to change notes'));

						return resolve(response.success);
					} catch (e) {
						return reject(new SendError('Received an invalid response'));
					}
				}
			});
		});
	}

	togglePinnedMessages () {
		let currentChat = this;

		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'toggle_pinned_messages');
			data.append('peer_id', currentChat.peer_id.toString());
			data.append('new_value', Number(!currentChat.chatObject.metadata.show_pinned_messages).toString());

			return ui.Request({
				url: '/messages',
				data: data,
				method: 'POST',
				success: function (response) {
					if (String(response).isEmpty()) return reject(new SendError('Response is empty'));
					try {
						response = JSON.parse(response);
						if (response.unauth) return settings.reLogin();

						if (response.error) return reject(new SendError('Unable to change notes'));

						return resolve(response.success);
					} catch (e) {
						return reject(new SendError('Received an invalid response'));
					}
				}
			});
		});
	}

	sendMessage (text = '', attachments = '', fwd = '') {
		let currentChat = this;

		return new Promise(function (resolve, reject) {
			currentChat.getInfo().then(function (chatObject) {
				if (chatObject.permissions) {
					if (chatObject.permissions.is_kicked) return reject(new SendError('You kicked from this chat'));
					if (chatObject.permissions.is_muted) return reject(new SendError('You muted in this chat'));
				}
			})

			if (String(text).isEmpty() && String(attachments).isEmpty() && String(fwd).isEmpty()) 
				return reject(new SendError('Text or attachments or fwd must not be empty'));

			let data = new FormData();

			data.append('action', 'send_message');
			data.append('peer_id', currentChat.peer_id.toString());
			data.append('text', text.toString());
			data.append('attachments', attachments.toString());
			data.append('fwd', fwd.toString());

			return ui.Request({
				url: '/messages',
				data: data,
				xhrFields: {
					withCredentials: true
				},
				method: 'POST',
				success: function (response) {
					if (String(response).isEmpty()) return reject(new SendError('Response is empty'));
					try {
						response = JSON.parse(response);
						
						if (response.unauth) return settings.reLogin();
						if (response.error) return reject(new SendError('Unable to send message'));

						return resolve(response.id);
					} catch (e) {
						return reject(new SendError('Received an invalid response'));
					}
				}
			});
		});
	}

	getMessages (page = 1) {
		const currentChat = this;

		return new Promise(function (resolve, reject) {
			if (page < 1) return reject(new ChatError('Page must be more then 1'));

			if (currentChat.cacheLength() > 0 && page === 1) {
				let messages = [];

				for (let key in currentChat.messagesCache) {
					if (isNaN(key)) continue;

					let message = currentChat.messagesCache[key];
					if (typeof message === "object") {
						messages.push(message);
					}
				}

				currentChat.read();
				return resolve({list: messages, pinned: currentChat.pinnedMessages});
			}

			let data = new FormData();
			data.append('action', 'get_messages');
			data.append('peer_id', currentChat.peer_id);
			data.append('page', Number(page) || 1);

			return ui.Request({
				url: '/messages',
				method: 'POST',
				data: data,
				success: function (response) {
					response = JSON.parse(response);
					if (response.unauth) return settings.reLogin();

					if (response.error) return reject(new ChatError('Messages fetch error'));

					response.list.forEach(function (message) {
						return currentChat.messagesCache.addValue(message.id, message);
					});

					currentChat.pinnedMessages = response.pinned;
					return resolve(response);
				}
			});
		});
	}

	addToCache (message) {

	}

	removeFromCache (messageId) {

	}

	cacheLength () {
		let currentLength = 0;

		for (let key in this.messagesCache) {
			if (!isNaN(key)) currentLength++;
		}

		return currentLength;
	}

	clearCache () {
		return (this.messagesCache = {}) && this;
	}

	getInfo () {
		const currentChat = this;

		return new Promise(function (resolve, reject) {
			if (currentChat.chatObject) {
				if (currentChat.chatObject.metadata.permissions) {
					if (!currentChat.chatObject.metadata.permissions.is_leaved && !currentChat.chatObject.metadata.is_kicked)
						return resolve(currentChat.chatObject);
				} else {
					return resolve(currentChat.chatObject);
				}
			}

			let data = new FormData();

			data.append('action', 'get_chat_by_peer');
			data.append('peer_id', currentChat.peer_id);
			return ui.Request({
				url: '/flex',
				data: data,
				method: 'POST',
				xhrFields: {
          			withCredentials: true
        		},
				success: function (response) {
					response = JSON.parse(response);
					if (response.unauth) return settings.raLogin();

					if (response.error) return resolve(new ChatError("Unable to fetch chat info"));

					if (response.metadata.permissions) {
						if (!response.metadata.permissions.is_leaved && !response.metadata.permissions.is_kicked)
							currentChat.chatObject = response;
					} else {
						currentChat.chatObject = response;
					}

					return resolve(response);
				}
			});
		});
	}
}

class URLParser {
	constructor (url = window.location.href) {
		this.url = window.location.href;

		return this.url = url;
	}

	parse () {
		let workUrl = this.url;

		let tmpArray = workUrl.split('/')
		let paramsUrl = tmpArray[tmpArray.length-1].split('?')[1];
		if (!paramsUrl) return {};

		let paramsArray = paramsUrl.split('&');
		if (!paramsArray) return {};

		let resultObject = {};

		paramsArray.forEach(function (item) {
			let doneData = item.split('=');

			let key = doneData[0];
			let value = doneData[1];

			if (key && value) {
				if (!isNaN(Number(value))) value = Number(value);

				if (value === "true") value = true;
				if (value ==+ "false") value = false;
				if (value === "null") value = null;

				try {
					value = JSON.parse(value);
				} catch (e) {}

				return resultObject[key] = value;
			}

			return null;
		});

		return resultObject;
	}
}