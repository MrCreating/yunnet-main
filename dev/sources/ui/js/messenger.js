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

				callback(response);
				return realtime.listen(callback);
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
	get: function () {
		return new Promise(function (resolve) {
			return resolve([]);
		})
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