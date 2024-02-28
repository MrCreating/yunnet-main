const realtime = {
	listen: function (data, callback) {
		let url = data.url;

		let x = _xmlHttpGet();
		x.onreadystatechange = function () {
			if (x.readyState !== 4) return;

			let response = x.responseText;
			try {
				response = JSON.parse(response);

				if (response.event === 'timeout' || response.event === 'closed') {
					return realtime.listen(data, callback);
				}

				if (response.error) {
					if (response.error.error_code === 500) {
						return console.log('[!] Re-connect to LP with auth key.');
					}
					if (response.error.error_code === 501) {
						return realtime.connect(callback);
					}
				}

				try {
					callback(response);
				} catch (e) {
					console.error(e);
				}

				return realtime.listen(data, callback);
			} catch (e) {
				console.log('[!] Incorrect data. Retry after 5 secs');

				setTimeout(function () {
					realtime.connect(callback);
				}, 5000)
			}
		}

		x.open('GET', url);
		x.send();
	},
	connect: function (callback) {
		let data = new FormData();

		data.append('action', 'get_events_link');

		return ui.Request({
			url: '/settings',
			method: 'POST',
			data: data,
			xhrFields: {
				withCredentials: true
			},
			success: function (response) {
				try {
					response = JSON.parse(response).response;
					realtime.listen(response, callback);
				} catch (e) {
					console.log('[!] LP sent incorrect data format.');
				}
			}
		});
	}
};

const messages = {
	createChat: function (dataObject) {
		return new Promise(function (resolve, reject) {
			if (dataObject.title.isEmpty() || dataObject.members.length < 2) return reject();

			if (!settings.users.current) return reject(new ChatError("Unauthorized user."));

			let data = new FormData();

			data.append('action', 'chat_create');
			data.append('title', dataObject.title);
			data.append('members', dataObject.members.join());
			data.append('photo', dataObject.photo);

			for (let key in dataObject.permissions) {
				data.append('permission_' + key, dataObject.permissions[key]);
			}

			return ui.Request({
				url: '/messages',
				method: 'POST',
				data: data,
				success: function (response) {
					response = JSON.parse(response);

					if (response.error)
					{
						let error = new ChatError('Chat creation failed.');

						error.errorCode = response.error;
						return reject(error);
					}

					return resolve(response.response);
				}
			});
		});
	},
	elements: {
		message: function (messageObject, isSending = false) {
			let messageElement = document.createElement('div');
			messageElement.id = messageObject.id;

			let messageState;
			if (!messageObject.event) {
				messageElement.classList.add('valign-wrapper');

				let innerDiv;
				if (messageObject.from_id === settings.users.current.user_id) {
					innerDiv = document.createElement('div');
					messageElement.appendChild(innerDiv);

					innerDiv.style.position = 'sticky';
					innerDiv.style.left = '100%';
					innerDiv.classList.add('valign-wrapper');
				}

				let messageDiv = document.createElement('div');
				let timeDiv = document.createElement('div');
				let editDiv = document.createElement('div');
				if (messageObject.is_edited) {
					editDiv.innerHTML = unt.Icon.EDIT;
					editDiv.style.marginBottom = '16px';
					editDiv.style.marginTop = 'auto';

					editDiv.getElementsByTagName('svg')[0].width.baseVal.value = 12;
					editDiv.getElementsByTagName('svg')[0].height.baseVal.value = 12;

					if (messageObject.from_id === settings.users.current.user_id) {
						innerDiv.appendChild(editDiv);

						editDiv.style.marginRight = '3px';
					}
				}

				timeDiv.style.marginBottom = '20px';
				timeDiv.style.marginTop = 'auto';
				if (messageObject.from_id === settings.users.current.user_id) {
					innerDiv.appendChild(timeDiv);

					messageDiv.classList = ['from_me from-me msg'];
					innerDiv.appendChild(messageDiv);

					timeDiv.style.textAlign = '-webkit-right';
					timeDiv.style.marginRight = '10px';
				} else {
					messageDiv.classList = ['from_another from-them msg'];
					timeDiv.style.marginLeft = '10px';

					let profileLink = document.createElement('a');
					messageElement.appendChild(profileLink);

					let userPhoto = document.createElement('img');
					userPhoto.width = userPhoto.height = 32;
					userPhoto.classList = ['circle'];

					profileLink.style.marginRight = '10px';
					profileLink.style.marginBottom = 'auto';

					profileLink.appendChild(userPhoto);
					messageElement.appendChild(messageDiv);
					profileLink.href = '/' + ((messageObject.from_id > 0) ? ('id' + messageObject.from_id) : ('bot' + (messageObject.from_id * -1)))
					profileLink.setAttribute('target', '_blank');

					settings.users.get(messageObject.from_id).then(function (user) {
						userPhoto.src = user.photo_url;
						userPhoto.alt = user.name || user.first_name + ' ' + user.last_name;
					}).catch(function (err) {
						userPhoto.src = 'https://dev.yunnet.ru/images/default.png';
						userPhoto.alt = '';
					});
				}

				let timeDivSmall = document.createElement('small');
				timeDiv.appendChild(timeDivSmall);

				messageState = document.createElement('small');
				timeDivSmall.appendChild(messageState);

				if (messageObject.text) {
					if (!messageObject.text.isEmpty()) {
						let messageTextDiv = document.createElement('div');
						messageTextDiv.style.wordBreak = 'break-word';
						messageDiv.appendChild(messageTextDiv);

						messageTextDiv.innerHTML = nl2br(htmlspecialchars(messageObject.text)).linkify();
					}
				}

				if (messageObject.attachments.length > 0) {
					let attachmentsElement = pages.elements.attachmentsGroup(messageObject.attachments);

					messageDiv.appendChild(attachmentsElement);
				}

				if (messageObject.fwd.length > 0) {
					let element = messages.elements.fwd(messageObject.fwd);

					messageDiv.appendChild(element);
				}

				if (messageObject.from_id !== settings.users.current.user_id) messageElement.appendChild(timeDiv);

				if (messageObject.is_edited) {
					if (messageObject.from_id !== settings.users.current.user_id) {
						messageElement.appendChild(editDiv);

						editDiv.style.marginLeft = '3px';
					}
				}
			} else {
				let eventDiv = document.createElement('div');
				messageElement.appendChild(eventDiv);

				eventDiv.style.color = 'var(--unt-events-textcolor, black)';

				messageElement.style.padding = '15px';

				eventDiv.innerHTML = '...';
				settings.users.get(messageObject.from_id).then(function (fromObject) {
					eventDiv.innerHTML = messages.utils.getEventString(messageObject.event.action || messageObject.event.type, fromObject, null, false);

					if (messageObject.event.to_id) {
						settings.users.get(messageObject.event.to_id).then(function (toObject) {
							eventDiv.innerHTML = messages.utils.getEventString(messageObject.event.action || messageObject.event.type, fromObject, toObject, false);
						}).catch(function (err) {
							eventDiv.innerHTML = eventDiv.innerHTML.replace('%when%', settings.lang.getValue("deleted_account"));
						});
					} else {
						eventDiv.innerHTML = messages.utils.getEventString(messageObject.event.action || messageObject.event.type, fromObject, null, false);
					}
				});
			}

			messageElement.setSending = function (sending) {
				messageElement.isSending = sending;
				messageElement.sendError = false;

				if (sending) {
					if (messageState)
						messageState.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 57 57" stroke="#000"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle cx="5" cy="50" r="5"><animate attributeName="cy" begin="0s" dur="2.2s" values="50;5;50;50" calcMode="linear" repeatCount="indefinite"></animate><animate attributeName="cx" begin="0s" dur="2.2s" values="5;27;49;5" calcMode="linear" repeatCount="indefinite"></animate></circle><circle cx="27" cy="5" r="5"><animate attributeName="cy" begin="0s" dur="2.2s" from="5" to="5" values="5;50;50;5" calcMode="linear" repeatCount="indefinite"></animate><animate attributeName="cx" begin="0s" dur="2.2s" from="27" to="27" values="27;49;5;27" calcMode="linear" repeatCount="indefinite"></animate></circle><circle cx="49" cy="50" r="5"><animate attributeName="cy" begin="0s" dur="2.2s" values="50;50;5;50" calcMode="linear" repeatCount="indefinite"></animate><animate attributeName="cx" from="49" to="49" begin="0s" dur="2.2s" values="49;5;27;49" calcMode="linear" repeatCount="indefinite"></animate></circle></g></g></svg>';
				} else {
					if (messageState)
						messageState.innerText = pages.parsers.time(messageObject.time, true);
				}

				return messageElement;
			}

			messageElement.setError = function (onclickHandler) {
				let timeElement = messageElement.getElementsByTagName('small')[1];

				timeElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="red" height="18" viewBox="0 0 24 24" width="18"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';

				messageElement.isSending = false;
				messageElement.sendError = true;
				if (onclickHandler)
					timeElement.getElementsByTagName('svg')[0].onclick = function (event) {
						event.preventDefault();

						return onclickHandler(event);
					}

				return messageElement;
			}

			return messageElement.setSending(isSending);
		},
		fwd: function (fwdObject) {
			let fwdElement = document.createElement('blockquote');

			fwdObject.forEach(function (message) {
				let credentialdsDiv = document.createElement('a');
				fwdElement.appendChild(credentialdsDiv);

				credentialdsDiv.classList.add('valign-wrapper');
				credentialdsDiv.classList.add('alink-name');

				credentialdsDiv.href = (message.from_id > 0) ? ('/id' + message.from_id) : ('/bot' + (message.from_id * -1));
				credentialdsDiv.setAttribute('target', '_blank');

				let userPhoto = document.createElement('img');
				credentialdsDiv.appendChild(userPhoto);

				userPhoto.width = userPhoto.height = 24;
				userPhoto.classList.add('circle');

				let userCredentialsInnerDiv = document.createElement('div');
				credentialdsDiv.appendChild(userCredentialsInnerDiv);
				credentialdsDiv.style.paddingBottom = '5px';

				userCredentialsInnerDiv.style.marginLeft = '5px';

				userCredentialsInnerDiv.style.fontSize = '85%';
				settings.users.get(message.from_id).then(function (user) {
					userCredentialsInnerDiv.innerText = user.name || user.first_name + ' ' + user.last_name;
					userPhoto.src = user.photo_url;
				}).catch(function (err) {
					userCredentialsInnerDiv.innerText = settings.lang.getValue("deleted_account");
					userPhoto.src = 'https://dev.yunnet.ru/images/default.png';
				});

				if (!message.text.isEmpty()) {
					let textDiv = document.createElement('div');

					textDiv.style.fontSize = '95%';
					textDiv.innerHTML = nl2br(htmlspecialchars(message.text)).linkify();

					fwdElement.appendChild(textDiv);
				}

				if (message.attachments.length > 0) {
					let attachmentsElement = pages.elements.attachmentsGroup(message.attachments);

					fwdElement.appendChild(attachmentsElement);
				}

				if (message.fwd.length > 0) {
					fwdElement.appendChild(messages.elements.fwd(message.fwd));
				}
			});

			return fwdElement;
		}
	},
	utils: {
		getChatInfoByLink: function (query) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'get_chat_info_by_link');
				data.append('link_query', query);

				return ui.Request({
					data: data,
					url: '/messages',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) {
							let error = new TypeError('Unable to show chat info');

							if (response.chat_id)
								error.chatId = response.chat_id;

							error.errorCode = response.error;

							return reject(error);
						}

						return resolve(response);
					}
				});
			})
		},
		joinByLink: function (query) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'join_to_chat_by_link');
				data.append('link_query', query);

				return ui.Request({
					data: data,
					url: '/messages',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) {
							let error = new TypeError('Unable to show chat info');

							if (response.chat_id)
								error.chatId = response.chat_id;

							error.errorCode = response.error;

							return reject(error);
						}

						return resolve(response.response);
					}
				});
			})
		},
		getEventString: function (eventType, fromObject, toObject = null, preview = false) {
			let resultString = '';

			switch (eventType) {
				case 'mute_user':
					resultString = settings.lang.getValue('mute_user').replace("(а)", fromObject.gender === 2 ? "а" : "");

					if (toObject && !preview)
						resultString = resultString.replace('%when%', ('<a style="color: var(--unt-event-names-color, gray) !important" href="/' + (toObject.name ? ("bot" + toObject.bot_id) : ("id" + toObject.user_id)) + '" target="_blank" style="font-weight: bold">' + htmlspecialchars(toObject.name || toObject.name_cases.first_name.dat + " " + toObject.name_cases.last_name.dat) + '</a>'));
					if (toObject && preview)
						resultString = resultString.replace('%when%', ((htmlspecialchars(toObject.name || toObject.name_cases.first_name.dat + " " + toObject.name_cases.last_name.dat))));
					break;
				case 'unmute_user':
					resultString = settings.lang.getValue('unmute_user').replace("(а)", fromObject.gender === 2 ? "а" : "");

					if (toObject && !preview)
						resultString = resultString.replace('%when%', ('<a style="color: var(--unt-event-names-color, gray) !important" href="/' + (toObject.name ? ("bot" + toObject.bot_id) : ("id" + toObject.user_id)) + '" target="_blank" style="font-weight: bold">' + htmlspecialchars(toObject.name || toObject.name_cases.first_name.dat + " " + toObject.name_cases.last_name.dat) + '</a>'));
					if (toObject && preview)
						resultString = resultString.replace('%when%', ((htmlspecialchars(toObject.name || toObject.name_cases.first_name.dat + " " + toObject.name_cases.last_name.dat))));
					break;
				case 'returned_to_chat':
					resultString = settings.lang.getValue('returned_to_chat').replace("лся(-ась)", fromObject.gender === 2 ? "лась" : "лся");
					break;
				case 'join_by_link':
					resultString = settings.lang.getValue('join_by_link').replace("лся(-ась)", fromObject.gender === 2 ? "лась" : "лся");
					break;
				case 'leaved_chat':
					resultString = settings.lang.getValue('leaved_the_chat').replace("(а)", fromObject.gender === 2 ? "а" : "");
					break;
				case 'updated_photo':
					resultString = settings.lang.getValue('updated_photo').replace("(а)", fromObject.gender === 2 ? "а" : "");
					break;
				case 'deleted_photo':
					resultString = settings.lang.getValue('deleted_photo').replace("(а)", fromObject.gender === 2 ? "а" : "");
					break;
				case 'kicked_user':
					resultString = settings.lang.getValue('kicked_user').replace("(а)", fromObject.gender === 2 ? "а" : "");

					if (toObject && !preview)
						resultString = resultString.replace('%when%', ('<a style="color: var(--unt-event-names-color, gray) !important" href="/' + (toObject.name ? ("bot" + toObject.bot_id) : ("id" + toObject.user_id)) + '" target="_blank" style="font-weight: bold">' + htmlspecialchars(toObject.name || toObject.name_cases.first_name.acc + " " + toObject.name_cases.last_name.acc) + '</a>'));
					if (toObject && preview)
						resultString = resultString.replace('%when%', ((htmlspecialchars(toObject.name || toObject.name_cases.first_name.acc + " " + toObject.name_cases.last_name.acc))));
					break;
				case 'invited_user':
					resultString = settings.lang.getValue('invited_user').replace("(а)", fromObject.gender === 2 ? "а" : "");

					if (toObject && !preview)
						resultString = resultString.replace('%when%', ('<a style="color: var(--unt-event-names-color, gray) !important" href="/' + (toObject.name ? ("bot" + toObject.bot_id) : ("id" + toObject.user_id)) + '" target="_blank" style="font-weight: bold">' + htmlspecialchars(toObject.name || toObject.name_cases.first_name.acc + " " + toObject.name_cases.last_name.acc) + '</a>'));
					if (toObject && preview)
						resultString = resultString.replace('%when%', ((htmlspecialchars(toObject.name || toObject.name_cases.first_name.acc + " " + toObject.name_cases.last_name.acc))));
					break;
				case 'change_title':
					resultString = settings.lang.getValue('changed_title').replace("(а)", fromObject.gender === 2 ? "а" : "");
					break;
				case 'chat_create':
					resultString = settings.lang.getValue('chat_create');
					break;
				default:
					break;
			}

			if (!preview)
				resultString = resultString.replace('%who%', ('<a style="color: var(--unt-event-names-color, gray) !important" href="/' + (fromObject.name ? ("bot" + fromObject.bot_id) : ("id" + fromObject.user_id)) + '" target="_blank" style="font-weight: bold">' + htmlspecialchars(fromObject.name || fromObject.first_name + " " + fromObject.last_name) + '</a>'));
			else
				resultString = resultString.replace('%who%', fromObject.name || (fromObject.first_name + ' ' + fromObject.last_name));

			return resultString.replace('%username%', '%who%');
		},
		getChatInstance: function (peer_id, chatObject) {
			if (!messages.cache[peer_id] && peer_id !== 0) return new Chat(peer_id, chatObject);

			return ((messages.cache[peer_id] instanceof Chat) ? (messages.cache[peer_id]) : (null));
		},
		toDefaultObject: function (message) {
			let defaultObject = {
				attachments: [],
				fwd: [],
				from_id: 0,
				id: 0,
				text: '',
				time: 0
			};

			if (message.message) {
				defaultObject.from_id = message.message.from_id;
				defaultObject.time = message.message.time;
				defaultObject.fwd = message.message.fwd;
				defaultObject.attachments = message.message.attachments;
				defaultObject.text = message.message.text;
				defaultObject.id = message.message.id;

				if (message.message.is_edited)
					defaultObject.is_edited = true;

				if (message.message.action) {
					defaultObject.event = message.message.action;
				}
			} else {
				defaultObject.from_id = message.from_id;
				defaultObject.time = message.time;
				defaultObject.fwd = message.fwd;
				defaultObject.attachments = message.attachments;
				defaultObject.text = message.text;
				defaultObject.id = message.id;

				if (message.is_edited)
					defaultObject.is_edited = true;

				if (message.event) {
					defaultObject.event = message.event;
				}
			}

			if (message.peer_id) defaultObject.peer_id = message.peer_id;
			if (message.bot_peer_id) defaultObject.bot_peer_id = message.bot_peer_id;

			return defaultObject;
		},
		getPreviewString: function (messageObject) {
			return new Promise(function (resolve, reject) {
				let previewString = "";
				let myUserId = settings.users.current.user_id;

				if (messageObject.event) {
					settings.users.get(messageObject.from_id).then(function (fromObject) {
						if (messageObject.event.to_id) {
							settings.users.get(messageObject.event.to_id).then(function (toObject) {
								previewString = messages.utils.getEventString(messageObject.event.action || messageObject.event.type, fromObject, toObject, true);

								return resolve(previewString);
							}).catch(function (err) {
								previewString = messages.utils.getEventString(messageObject.event.action || messageObject.event.type, fromObject, null, true);

								return resolve(previewString);
							});
						} else {
							previewString = messages.utils.getEventString(messageObject.event.action || messageObject.event.type, fromObject, null, true);

							return resolve(previewString);
						}
					}).catch(function (err) {
						previewString = '';

						return resolve(previewString);
					});
				} else {
					if (myUserId === messageObject.from_id) {
						previewString += (settings.lang.getValue("you")) + ":";
						if (messageObject.text)
							previewString += " " + messageObject.text;

						if (messageObject.fwd.length > 0) {
							previewString += " [" + messageObject.fwd.length + " FWD]"
						} else if (messageObject.attachments.length > 0) {
							previewString += " [" + messageObject.attachments.length + " ATT]"
						}

						return resolve(previewString);
					} else {
						settings.users.get(messageObject.from_id).then(function (user) {
							previewString += (user.account_type === "user" ? user.first_name : user.name) + ":";

							if (messageObject.text)
								previewString += " " + messageObject.text;

							if (messageObject.fwd.length > 0) {
								previewString += " [" + messageObject.fwd.length + " FWD]"
							} else if (messageObject.attachments.length > 0) {
								previewString += " [" + messageObject.attachments.length + " ATT]"
							}

							return resolve(previewString);
						}).catch(function (err) {
							previewString += "O:";

							if (messageObject.text)
								previewString += " " + messageObject.text;

							if (messageObject.fwd.length > 0) {
								previewString += " [" + messageObject.fwd.length + " FWD]"
							} else if (messageObject.attachments.length > 0) {
								previewString += " [" + messageObject.attachments.length + " ATT]"
							}

							return resolve(previewString);
						});
					}
				}
			});
		}
	},
	cache: {},
	get: function (offset = 0, count = 30, onlyChats = false) {
		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'get_chats');
			data.append('offset', Number(offset));
			data.append('count', Number(count) || 30);
			data.append('only_chats', Number(onlyChats));

			return ui.Request({
				url: '/messages',
				method: 'POST',
				data: data,
				success: function (response) {
					response = JSON.parse(response);
					if (response.error)
						return reject(new TypeError('Chats fetching error'));

					for (let i = 0; i < response.length; i++) {
						let peer_id = (response[i].chat_info.is_bot_chat ? ("b" + (response[i].bot_peer_id * -1)) : response[i].peer_id)

						messages.cache[peer_id] = messages.utils.getChatInstance(peer_id, response[i]);
					}

					return resolve(response);
				}
			});
		});
	},
	getChatByPeer: function (peer_id) {
		return new Promise(function (resolve, reject) {
			messages.utils.getChatInstance(peer_id, null).getInfo().then(function (response) {
				return resolve(response);
			}).catch(function (err) {
				return reject(err);
			});
		});
	}
};

const poll = {
	actions: {
		create: function (pollTitle = '', variantsList = [], anonymousPoll = false, multipleSelection = false) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				if (pollTitle.toString().isEmpty()) 
					return reject(new TypeError('Unable to create poll: Title is empty.'));

				let doneVariants = [];
				for (let i = 0; i < variantsList.length; i++) {
					if (i >= 10) break;

					doneVariants.push(variantsList[i].toString());
				}

				data.append('poll_title', pollTitle);
				data.append('poll_anonymous', Number(anonymousPoll) || 0);
				data.append('poll_multi_selection', Number(multipleSelection) || 0);
				data.append('poll_answers_list', JSON.stringify(doneVariants));

				return ui.Request({
					url: '/upload?type=poll&action=get',
					method: 'POST',
					data: data,
					success: function (response) {
						response = JSON.parse(response);

						if (response.error)
							return reject(new TypeError('Unable to create the poll'));

						return resolve(response);
					}
				});
			});
		}
	},
	pinned: function (attsListElement) {
		let divElements = attsListElement.getElementsByTagName('div');

		for (let i = 0; i < divElements.length; i++) {
			if (divElements[i].getAttribute('attachment').startsWith('poll'))
				return true;
		}

		return false;
	},
	creator: function (callback) {
		const params = {
			title: '',
			answers: [],
			anonymous: false,
			multipleSelection: false,
			inProcess: false
		};

		let element = document.createElement('div');

		element.classList.add('modal');
		element.classList.add('hidesc');
		element.classList.add('bottom-sheet');

		element.style.maxHeight = 'unset';
		element.style.height = '100%';

		element.id = 'pollCreator';

		let modalContent = document.createElement('div');
		modalContent.classList.add('modal-content');
		element.appendChild(modalContent);

		let pollCreatorHeader = document.createElement('div');
		modalContent.appendChild(pollCreatorHeader);

		pollCreatorHeader.classList.add('valign-wrapper');
		pollCreatorHeader.style.width = '100%';

		let headerText = document.createElement('div');
		pollCreatorHeader.appendChild(headerText);
		headerText.style.width = '100%';
		headerText.innerText = settings.lang.getValue('create_poll');

		let closeButton = document.createElement('div');
		closeButton.style.cursor = 'pointer';
		closeButton.style.marginTop = '5px';
		pollCreatorHeader.appendChild(closeButton);
		closeButton.innerHTML = unt.Icon.CLOSE;

		closeButton.addEventListener('click', function () {
			if (params.inProcess) return;

			return element.getInstance().close();
		});

		let instance;
		element.show = function () {
			if (document.getElementById(element.id)) {
				instance = unt.Modal.getInstance(element);
			} else {
				pages.elements.menuBody().getCurrent().appendChild(element);

				instance = unt.Modal.init(element, {
					dismissible: false,
					onCloseEnd: function () {
						return element.remove();
					}
				});
			}

			return instance.open();
		}

		element.getInstance = function () {
			return instance;
		}

		element.createAnswer = function (closeable = true) {
			if (params.answers.length >= 10) return;

			let answerDiv = document.createElement('div');
			answerDiv.classList.add('valign-wrapper');

			let answerTitle = pages.elements.createInputField(settings.lang.getValue('answer_title')).maxLength(128);
			answerTitle.style.width = '100%';
			answerDiv.appendChild(answerTitle);
			answersDiv.appendChild(answerDiv);

			let closeItem = document.createElement('div');
			closeItem.style.cursor = 'pointer';
			closeItem.style.marginLeft = '10px';
			closeItem.innerHTML = unt.Icon.CLOSE;
			answerDiv.appendChild(closeItem);

			let answerObject = {
				id: params.answers.length + 1,
				title: answerTitle.getValue()
			};

			answerTitle.getInput().addEventListener('input', function () {
				answerObject.title = answerTitle.getValue()
			});

			if (!closeable)
				closeItem.style.display = 'none';

			closeItem.addEventListener('click', function () {
				if (params.inProcess) return;

				for (let i = 0; i < params.answers.length; i++) {
					if (params.answers[i].id === answerObject.id) {
						params.answers.splice(i, 1);

						answerDiv.remove();
						break;
					}
				}

				if (params.answers.length >= 10)
					createAnswerButton.classList.add('disabled');
				else
					createAnswerButton.classList.remove('disabled');

				if (!pollTitle.getValue().isEmpty() && params.answers.length >= 2) {
					continueButton.classList.remove('disabled');
				} else {
					continueButton.classList.add('disabled');
				}
			});

			params.answers.push(answerObject);

			return element;
		}

		let pollTitle = pages.elements.createInputField(settings.lang.getValue('poll_title')).maxLength(64);
		modalContent.appendChild(pollTitle);
		pollTitle.getInput().addEventListener('input', function (event) {
			if (params.inProcess) return event.preventDefault();

			params.title = pollTitle.getValue();

			if (!pollTitle.getValue().isEmpty() && params.answers.length >= 2) {
				continueButton.classList.remove('disabled');
			} else {
				continueButton.classList.add('disabled');
			}
		});

		let answersDiv = document.createElement('div');

		answersDiv.classList.add('card');
		answersDiv.classList.add('hidesc');

		modalContent.appendChild(answersDiv);

		answersDiv.style.maxHeight = '250px';
		answersDiv.style.overflow = 'auto';
		answersDiv.style.paddingLeft = '10px';
		answersDiv.style.paddingRight = '10px';

		let buttonDiv = document.createElement('div');
		modalContent.appendChild(buttonDiv);
		buttonDiv.style.width = '100%';
		buttonDiv.style.textAlign = 'end';

		let createAnswerButton = document.createElement('a');
		createAnswerButton.classList.add('btn');
		createAnswerButton.classList.add('unselectable');
		buttonDiv.appendChild(createAnswerButton);
		createAnswerButton.innerText = settings.lang.getValue('create_answer');
		createAnswerButton.addEventListener('click', function () {
			if (params.inProcess) return;

			element.createAnswer();

			if (params.answers.length >= 10)
				createAnswerButton.classList.add('disabled');
			else
				createAnswerButton.classList.remove('disabled');

			if (!pollTitle.getValue().isEmpty() && params.answers.length >= 2) {
				continueButton.classList.remove('disabled');
			} else {
				continueButton.classList.add('disabled');
			}
		});

		let anonymousPoll = pages.elements.createChecker(settings.lang.getValue('anonymous_poll'), function () {
			params.anonymous = anonymousPoll.checked();
		});
		modalContent.appendChild(anonymousPoll);
		let multiSelection = pages.elements.createChecker(settings.lang.getValue('multi_selection'), function () {
			params.multipleSelection = multiSelection.checked();
		});
		modalContent.appendChild(multiSelection);

		let footer = document.createElement('div');
		footer.classList.add('modal-footer')
		element.appendChild(footer);

		let continueButton = document.createElement('a');
		continueButton.classList = ['btn btn-flat unselectable waves-effect waves-light disabled'];
		continueButton.innerText = settings.lang.getValue('continue');
		footer.appendChild(continueButton);

		let pollObject;
		element.getPoll = function () {
			return pollObject;
		}

		continueButton.onclick = function () {
			continueButton.classList.add('disabled');
			continueButton.innerText = settings.lang.getValue('loading');
			params.inProcess = true;

			let resultedAnswers = [];
			params.answers.forEach(function (answer) {
				resultedAnswers.push(answer.title);
			});

			poll.actions.create(params.title, resultedAnswers, params.anonymous, params.multipleSelection).then(function (pollObject) {
				params.inProcess = false;

				continueButton.innerText = settings.lang.getValue('continue');
				continueButton.classList.remove('disabled');

				element.getInstance().close();
				if (typeof callback === "function")
					return callback(pollObject, element);
			}).catch(function (err) {
				unt.toast({html: settings.lang.getValue('upload_error')});

				params.inProcess = false;
				continueButton.innerText = settings.lang.getValue('continue');
				continueButton.classList.remove('disabled');
			})
		}

		return element;
	}
};

window.addEventListener('authCompleted', function () {
	realtime.connect.pending = {};

	realtime.connect(function (event, thisFunction) {
		console.log(event);

		let eventName = event.event;
		let workedUrl = (new URLParser(window.location.href)).parse();

		if (eventName === "interface_event") {
			let action = event.data ? event.data.action : null;

			if (action && action === 'theme_changed') {
				load.style.display = '';
				load_indicator.style.display = '';
				load_text_info.innerText = settings.lang.getValue('theme_updating');

				let credentials = event.data.theme ? ('theme' + event.data.theme.owner_id + '_' + event.data.theme.id) : null
				settings.get().theming.current_theme = credentials;

				return themes.setup(credentials).then(function () {
					load.style.display = 'none';
					load_indicator.style.display = 'none';
					load_text_info.innerText = '';
				});
			}
		}

		if (eventName === "notification_read") {
			let id = event.data.id || 0;

			for (let i = 0; i < notifications.cache.length; i++) {
				if (notifications.cache[i].id === id) {
					notifications.cache.splice(i, 1);

					break;
				}
			}

			if (settings.getCounters.current && !event.data.is_hidden) {
				settings.getCounters.current.notifications -= 1;
				if (settings.getCounters.current.notifications < 0) settings.getCounters.current.notifications = 0;

				pages.elements.setupCounters(settings.getCounters.current);
			}

			if (document.getElementById('notifications_list') && document.getElementById(String(id))) {
				document.getElementById(String(id)).remove();

				if (notifications.cache.length <= 0) {
					document.getElementById('notifications_list').style.display = 'none';

					if (!document.getElementById('alertWindow'))
						pages.elements.menuBody().appendChild(pages.elements.alertWindow(unt.Icon.NO_NOTES, settings.lang.getValue('no_notes'), settings.lang.getValue('no_notes_text')));
					else
						document.getElementById('alertWindow').style.display = '';
				}
			}
		}

		if (eventName === "notification_hide") {
			let id = event.data.id || 0;

			for (let i = 0; i < notifications.cache.length; i++) {
				if (notifications.cache[i].id === id) {
					notifications.cache[i].is_hidden = true;

					break;
				}
			}

			if (document.getElementById('notifications_list') && document.getElementById(String(id))) {
				let notificationElement = document.getElementById(String(id))

				if (notificationElement.getElementsByClassName('hide-loader')[0])
					notificationElement.getElementsByClassName('hide-loader')[0].style.display = 'none';
			}

			if (settings.getCounters.current) {
				settings.getCounters.current.notifications -= 1;
				if (settings.getCounters.current.notifications < 0) settings.getCounters.current.notifications = 0;

				pages.elements.setupCounters(settings.getCounters.current);
			}
		}

		if (eventName === "new_notification") {
			let oldarray = notifications.cache.reverse();
			oldarray.push(event.notification);
			notifications.cache = oldarray.reverse();

			if (settings.getCounters.current) {
				settings.getCounters.current.notifications += 1;

				pages.elements.setupCounters(settings.getCounters.current);
			}

			let notesDiv = document.getElementById('notifications_list');

			if (notesDiv) {
				if (document.getElementById('alertWindow'))
					document.getElementById('alertWindow').style.display = 'none';

				notesDiv.style.display = '';

				let element = pages.elements.notification(event.notification);

				notesDiv.prepend(element);
				ui.bindItems();
			}

			if (settings.get().push.notifications) {
				let type = event.notification.type;

				if (type === "friendship_requested") {
					if (settings.getCounters.current) {
						settings.getCounters.current.friends += 1;
						
						pages.elements.setupCounters(settings.getCounters.current);
					}

					settings.users.get(event.notification.data.user_id).then(function (user) {
						let notification = unt.Notification(unt.Icon.GROUP, settings.lang.getValue('friends_adding'), settings.lang.getValue('want_to_add')
													.replace('%usernick%', user.is_deleted ? settings.lang.getValue('deleted_account') : ('<a href=' + (user.name ? ('bot' + user.bot_id) : ('id' + user.user_id)) + '"/public">' + (user.name || user.first_name + ' ' + user.last_name)) + '</a>'), [
														[settings.lang.getValue('accept'), function () {
															settings.users.friends.acceptRequest(event.notification.data.user_id);

															notification.close();
														}], [settings.lang.getValue('hide'), function () {
															settings.users.friends.hideRequest(event.notification.data.user_id);

															notification.close();
														}]
													], {
														sound: settings.get().push.sound
													});
					}).catch(function (err) {
						console.log(err);


					})
				}
			}
		}

		if (eventName === "request_hide") {
			if (settings.users.friends.cache.subscribers) {
				for (let i = 0; i < settings.users.friends.cache.subscribers.length; i++) {
					let userObject = settings.users.friends.cache.subscribers[i];

					if (userObject.user_id === event.user_id) {
						settings.users.friends.cache.subscribers[i].friend_state.is_hidden = true;

						break;
					}
				}
			}

			if (window.location.href.split(window.location.host)[1].split('?')[0] !== '/friends') return;

			let userElement = document.getElementById(event.user_id.toString());
			if (!userElement) return;

			let hideItem = userElement.getElementsByClassName('hide-item')[0];
			if (hideItem) return hideItem.style.display = 'none';
		}

		if (eventName === 'friendship_by_me_accepted') {
			if (settings.users.friends.cache.subscribers) {
				for (let i = 0; i < settings.users.friends.cache.subscribers.length; i++) {
					let userObject = settings.users.friends.cache.subscribers[i];

					if (userObject.user_id === event.user_id) {
						settings.users.friends.cache.subscribers.splice(i, 1);
						settings.users.friends.cache.friends.push(userObject);

						break;
					}
				}
			}

			if (window.location.href.split(window.location.host)[1].split('?')[0] !== '/friends') return;

			let userElement = document.getElementById(event.user_id.toString());
			if (!userElement) return;

			let hideItem = userElement.getElementsByClassName('accept-item')[0];
			if (hideItem) return hideItem.style.display = 'none';
		}
	});
});
