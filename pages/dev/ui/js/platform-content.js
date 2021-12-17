unt.pages = new Object({
	auth: function (internalData) {
		unt.components.navPanel ? unt.components.navPanel.hide() : null;
		let menu = unt.components.menuElement;

		let authContainer = document.createElement('div');
		authContainer.style.width = '100%';

		let cookielol = document.createElement('div');
		authContainer.classList.add('unselectable');
		authContainer.appendChild(cookielol);
		cookielol.style.marginBottom = '20px';

		let cookie = document.createElement('img');
		cookie.src = '/favicon.ico';
		cookielol.appendChild(cookie);
		cookie.width = cookie.height = 96;
		cookie.classList.add('circle');

		let authCard = document.createElement('div');
		authContainer.appendChild(authCard);

		let authResultMessage = document.createElement('div');
		authResultMessage.style.marginBottom = '10px';
		authResultMessage.style.padding = '10px';
		authResultMessage.innerText = unt.settings.lang.getValue('auth_welcome');
		authCard.appendChild(authResultMessage);

		authCard.classList.add('card');

		authContainer.style.textAlign = '-webkit-center';
		authContainer.style.position = 'absolute';
		authContainer.style.left = '50%';
		authContainer.style.marginRight = '-50%';
		authContainer.style.transform = 'translate(-50%, -50%)';
		authContainer.style.top = '40%';
		authCard.style.padding = '20px';

		if (!unt.tools.isMobile()) {
			authContainer.style.maxWidth = '40%';
			authContainer.style.minWidth = '35%';
		} else {
			authContainer.style.maxWidth = '100%';
			authContainer.style.minWidth = '40%';

			if (window.screen.width >= 500)
				authCard.style.width = parseInt(window.screen.width / 2) + 'px';
			else
				authCard.style.width = window.screen.width + 'px';
		}

		let authForm = unt.actions.authForm();
		authForm.onauthresult = function (authResult) {
			if (authResult === -1) {
				return authResultMessage.innerText = unt.settings.lang.getValue('auth_failed');
			}
			if (authResult === 0) {
				return authResultMessage.innerHTML = unt.settings.lang.getValue('upload_error');
			}
			if (authResult === 1) {
				let loader = unt.components.loaderElement();
				document.body.innerHTML = '';

				loader.style.position = 'absolute';
				loader.style.left = '50%';
				loader.style.marginRight = '-50%';
				loader.style.transform = 'translate(-50%, -50%)';
				loader.style.top = '45%';

				document.body.appendChild(loader);

				return setTimeout(function () {
					return window.location.reload();
				}, 1000);
			}
		}

		authCard.appendChild(authForm);
		menu.appendChild(authContainer);
	},
	register: function (internalData) {
		if (unt.settings.users.current)
			return unt.actions.linkWorker.go('/');

		unt.components.navPanel ? unt.components.navPanel.hide() : null;
		let menu = unt.components.menuElement;

		let registerDiv = document.createElement('div');
		menu.appendChild(registerDiv);
		registerDiv.style.marginTop = '100px';

		let button = unt.components.cardButton(unt.icons.backArrow, unt.settings.lang.getValue('logout'), function () {
			return unt.actions.dialog(unt.settings.lang.getValue('logout'), unt.settings.lang.getValue('confirm_exit'), true, true).then(function (response) {
				if (response) {
					return unt.tools.Request({
						url: '/register',
						method: 'POST',
						data: (new POSTData()).append('action', 'close_session').build(),
						success: function (response) {
							try {
								response = JSON.parse(response);
								if (response.response)
									return unt.actions.linkWorker.go('/');
							} catch (e) {
								return unt.toast({html: unt.settings.lang.getValue('upload_error')});
							}
						},
						error: function () {
							return unt.toast({html: unt.settings.lang.getValue('upload_error')});
						}
					});
				}
			});
		});
		button.hide();

		let cardDivForm = document.createElement('div');
		cardDivForm.classList.add('card');
		cardDivForm.style.padding = '10px';

		registerDiv.appendChild(button);
		registerDiv.appendChild(cardDivForm);

		let loader = unt.components.loaderElement();
		cardDivForm.appendChild(loader);
		cardDivForm.style.textAlign = '-webkit-center';

		return unt.tools.Request({
			url: '/register',
			method: 'POST',
			data: (new POSTData()).append('action', 'get_state').build(),
			success: function (response) {
				try {
					response = JSON.parse(response);

					return unt.modules.accountActions.register(response.closed ? -1 : response.state, registerDiv, cardDivForm, button);
				} catch (e) {
					return unt.toast({html: unt.settings.lang.getValue('upload_error')});
				}
			},
			error: function () {
				return unt.toast({html: unt.settings.lang.getValue('upload_error')});
			}
		});
	},
	restore: function (internalData) {
		unt.components.navPanel ? unt.components.navPanel.hide() : null;

		if (unt.settings.users.current)
			return unt.actions.linkWorker.go('/');

		unt.components.navPanel ? unt.components.navPanel.hide() : null;
		let menu = unt.components.menuElement;

		let restoreDiv = document.createElement('div');
		menu.appendChild(restoreDiv);
		restoreDiv.style.marginTop = '100px';

		let button = unt.components.cardButton(unt.icons.backArrow, unt.settings.lang.getValue('logout'), function () {
			return unt.actions.dialog(unt.settings.lang.getValue('logout'), unt.settings.lang.getValue('confirm_exit'), true, true).then(function (response) {
				if (response) {
					return unt.tools.Request({
						url: '/restore',
						method: 'POST',
						data: (new POSTData()).append('action', 'close_session').build(),
						success: function (response) {
							try {
								response = JSON.parse(response);
								if (response.response)
									return unt.actions.linkWorker.go('/');
							} catch (e) {
								return unt.toast({html: unt.settings.lang.getValue('upload_error')});
							}
						},
						error: function () {
							return unt.toast({html: unt.settings.lang.getValue('upload_error')});
						}
					});
				}
			});
		});
		button.hide();

		let cardDivForm = document.createElement('div');
		cardDivForm.classList.add('card');
		cardDivForm.style.padding = '10px';

		restoreDiv.appendChild(button);
		restoreDiv.appendChild(cardDivForm);

		let loader = unt.components.loaderElement();
		cardDivForm.appendChild(loader);
		cardDivForm.style.textAlign = '-webkit-center';

		return unt.tools.Request({
			url: '/restore',
			method: 'POST',
			data: (new POSTData()).append('action', 'get_state').build(),
			success: function (response) {
				try {
					response = JSON.parse(response);

					return unt.modules.accountActions.restore(response.closed ? -1 : response.state, restoreDiv, cardDivForm, button);
				} catch (e) {
					return unt.toast({html: unt.settings.lang.getValue('upload_error')});
				}
			},
			error: function () {
				return unt.toast({html: unt.settings.lang.getValue('upload_error')});
			}
		});
	},
	banned: function (internalData) {
		unt.components.navPanel ? unt.components.navPanel.hide() : null;
	},
	news: function (internalData) {
		document.title = unt.settings.lang.getValue('news');
		let menu = unt.components.menuElement;

		let untNewsContainer = document.createElement('div');
		menu.appendChild(untNewsContainer);

		let newsDiv = document.createElement('div');
		untNewsContainer.appendChild(newsDiv);

		let actionsDiv = document.createElement('div');
		untNewsContainer.appendChild(actionsDiv);

		if (!unt.tools.isMobile()) {
			untNewsContainer.classList.add('row');
			newsDiv.classList = ['col s8'];

			actionsDiv.classList = ['col s4'];
			actionsDiv.style.paddingLeft = 0;
		}

		if (unt.tools.isMobile()) {
			actionsDiv.appendChild(unt.components.floatingActionButton(unt.icons.edit, unt.settings.lang.getValue('write_a_post'), true));
		} else {
			actionsDiv.appendChild(unt.components.cardButton(unt.icons.edit, unt.settings.lang.getValue('write_a_post'), new Function()));
		}

		let newsLoadCard = document.createElement('div');
		newsLoadCard.classList.add('valign-wrapper');
		newsLoadCard.classList.add('card');
		newsDiv.appendChild(newsLoadCard);

		let loader = unt.components.loaderElement();
		loader.style.padding = '20px';
		newsLoadCard.appendChild(loader);

		let loaderText = document.createElement('div');
		newsLoadCard.appendChild(loaderText);
		loaderText.innerText = unt.settings.lang.getValue('loading');
		return unt.actions.wall.getNews().then(function (posts) {
			newsLoadCard.hide();

			if (posts.length <= 0)
				return profileContent.appendChild(unt.components.alertBanner(unt.icons.failed, unt.settings.lang.getValue('no_posts'), unt.settings.lang.getValue('no_posts_t')));
				
			newsDiv.hide();
			posts.forEach(function (post) {
				newsDiv.appendChild(unt.components.wall.post(post));
			});

			return newsDiv.show();
		}).catch(function (err) {
			newsDiv.show();

			return newsDiv.appendChild(unt.components.alertBanner(unt.icons.failed, unt.settings.lang.getValue('upload_error'), unt.settings.lang.getValue('unknown_error')));
		});
	},
	messages: function (internalData) {
		let url = new URLParser();

		let menu = unt.components.menuElement;
		if (url.getQueryValue('s').isEmpty()) {
			document.title = unt.settings.lang.getValue('messages');

			let messagesDiv = document.createElement('div');
			menu.appendChild(messagesDiv);

			let dialogsList = document.createElement('div');
			let resultMessageDiv = document.createElement('div');
			let loaderDiv = unt.components.loaderElement();

			loaderDiv.style.marginTop = '15px';
			loaderDiv.classList.add('center');

			dialogsList.hide();
			resultMessageDiv.hide();
			loaderDiv.hide();

			messagesDiv.appendChild(dialogsList);
			messagesDiv.appendChild(resultMessageDiv);
			messagesDiv.appendChild(loaderDiv);

			unt.modules.messenger.pages.functions.loadChats(resultMessageDiv, dialogsList, loaderDiv, 1);
		} else {
			document.title = unt.settings.lang.getValue('message');

			unt.components.navPanel.getDefaultHeader().hide();
			unt.components.navPanel.getAdditionalHeader().show();

			let photoIm = document.createElement('img');

			function createChatPage (chatObject) {
				let f = unt.components.navPanel.getAdditionalHeader();

				unt.components.navPanel.getAdditionalHeader().innerHTML = '';

				let chatContainer = document.createElement('div');
				chatContainer.classList.add('valign-wrapper');
				f.appendChild(chatContainer);
				chatContainer.parentNode.parentNode.style.height = '100%';

				let element = document.createElement('img');
				element.src = chatObject.chat_info.data.photo_url;
				element.width = element.height = 32;
				element.classList.add('circle');

				chatContainer.appendChild(element);

				let chatInfoDiv = document.createElement('div');
				chatInfoDiv.style.marginLeft = '15px';

				chatContainer.appendChild(chatInfoDiv);
				chatInfoDiv.classList.add('halign-wrapper');

				let dialogInf = document.createElement('div');
				dialogInf.style.lineHeight = 'normal';
				dialogInf.innerText = chatObject.chat_info.is_multi_chat ? chatObject.chat_info.data.title : (chatObject.chat_info.data.name || (chatObject.chat_info.data.first_name + ' ' + chatObject.chat_info.data.last_name));

				let dialogState = document.createElement('div');
				dialogState.style.lineHeight = 'normal';
				dialogState.style.color = 'lightgrey';
				dialogState.style.fontSize = '80%';
				dialogState.innerText = chatObject.chat_info.is_multi_chat ? unt.parsers.chatStateString(chatObject) : unt.parsers.online(chatObject.chat_info.data);

				chatInfoDiv.appendChild(dialogInf);
				chatInfoDiv.appendChild(dialogState);
			}

			if (internalData)
				return createChatPage(internalData);

			return unt.modules.messenger.getChatByPeerId(url.getQueryValue('s')).then(createChatPage).catch(function () {
				return unt.actions.linkWorker.go('/messages');
			});
		}
	},
	notifications: function (internalData) {
		document.title = unt.settings.lang.getValue('notifications');
	},
	friends: function (internalData) {
		document.title = unt.settings.lang.getValue('friends');
	},
	groups: function (internalData) {
		document.title = unt.settings.lang.getValue('groups');
	},
	group: function (internalData) {},
	archive: function (internalData) {
		document.title = unt.settings.lang.getValue('archive');
	},
	audios: function (internalData) {
		document.title = unt.settings.lang.getValue('audios');
	},
	settings: function (internalData) {
		document.title = unt.settings.lang.getValue('settings');
		let menu = unt.components.menuElement;

		let categories = ['main', 'notifications', 'privacy', 'security', 'blacklist', 'accounts', 'theming', 'about'];
		let iconsIds   = ['main', 'notifications', 'lock', 'security', 'list', 'accounts', 'palette', 'cookies'];
		let catMenus = [];

		let urlData = new URLParser();
		let currentSection = urlData.getQueryValue('section') || (unt.tools.isMobile() ? '' : 'main');

		if (!unt.tools.isMobile()) {
			let tabsUl = document.createElement('ul');
			tabsUl.classList.add('hidesc');
			tabsUl.classList.add('tabs');
			tabsUl.classList.add('card');
			tabsUl.style.marginTop = 0;
			tabsUl.style.marginTop = '5px';
			menu.appendChild(tabsUl);

			categories.forEach(function (category, index) {
				let li = document.createElement('li');
				li.classList.add('tab');
				li.classList.add('waves-effect');

				let a = document.createElement('a');
				a.classList.add('no-continue-browsing');
				a.classList.add('tooltipped');
				if (category === currentSection)
					a.style.borderBottom = 'inset';

				a.setAttribute('data-position', 'bottom');
				a.setAttribute('data-tooltip', unt.settings.lang.getValue(category));

				a.href = '/settings?section=' + category;
				a.classList.add('unselectable');
				a.style.cursor = 'pointer';
				li.appendChild(a);
				a.innerHTML = unt.icons[iconsIds[index]];
				a.getElementsByTagName('svg')[0].style.marginTop = '11px';
				tabsUl.appendChild(li);

				let categoryMenuElement = document.createElement('div');
				categoryMenuElement.hide();
				categoryMenuElement.classList.add('settings-category');

				menu.appendChild(categoryMenuElement);
			});
			unt.AutoInit();
		} else {
			if (currentSection === '') {
				let profileCard = document.createElement('div');
				profileCard.classList.add('card');
				profileCard.style.marginBottom = 0;
				profileCard.style.padding = '20px';
				menu.appendChild(profileCard);

				let userInfoDiv = document.createElement('div');
				userInfoDiv.classList.add('valign-wrapper');
				profileCard.appendChild(userInfoDiv);

				let userPhoto = document.createElement('img');
				userInfoDiv.appendChild(userPhoto);
				userPhoto.classList.add('circle');
				userPhoto.width = userPhoto.height = 64;
				userPhoto.src = unt.settings.users.current.photo_url;
				userPhoto.style.marginRight = '15px';

				let userCredentialsDiv = document.createElement('div');
				userInfoDiv.appendChild(userCredentialsDiv);
				userCredentialsDiv.classList.add('halign-wrapper');

				let usernameDiv = document.createElement('b');
				userCredentialsDiv.appendChild(usernameDiv);
				usernameDiv.innerText = unt.settings.users.current.first_name + ' ' + unt.settings.users.current.last_name;
				usernameDiv.style.fontSize = '120%';

				let onlineDiv = document.createElement('div');
				userCredentialsDiv.appendChild(onlineDiv);
				onlineDiv.innerText = unt.parsers.online(unt.settings.users.current);

				let editPageButton = document.createElement('a');
				editPageButton.classList = ['btn btn-flat'];
				editPageButton.innerText = unt.settings.lang.getValue('edit');
				editPageButton.href = '/edit';
				editPageButton.style.padding = 0;
				editPageButton.style.marginTop = '15px';
				profileCard.appendChild(editPageButton);

				let mainGroup = unt.components.cardButtonsGroup()
									.addCardButton(unt.icons.main, unt.settings.lang.getValue('main'), function () { unt.actions.linkWorker.go('/settings?section=main') })
									.addCardButton(unt.icons.notifications, unt.settings.lang.getValue('notifications'), function () { unt.actions.linkWorker.go('/settings?section=notifications') });

				let securityGroup = unt.components.cardButtonsGroup()
									.addCardButton(unt.icons.lock, unt.settings.lang.getValue('privacy'), function () { unt.actions.linkWorker.go('/settings?section=privacy') })
									.addCardButton(unt.icons.security, unt.settings.lang.getValue('security'), function () { unt.actions.linkWorker.go('/settings?section=security') })
									.addCardButton(unt.icons.list, unt.settings.lang.getValue('blacklist'), function () { unt.actions.linkWorker.go('/settings?section=blacklist') });

				let themingGroup = unt.components.cardButtonsGroup()
									.addCardButton(unt.icons.accounts, unt.settings.lang.getValue('accounts'), function () { unt.actions.linkWorker.go('/settings?section=accounts') })
									.addCardButton(unt.icons.palette, unt.settings.lang.getValue('theming'), function () { unt.actions.linkWorker.go('/settings?section=theming') });

				let projectGroup = unt.components.cardButtonsGroup()
									.addCardButton(unt.icons.cookies, unt.settings.lang.getValue('about'), function () { unt.actions.linkWorker.go('/settings?section=about') });

				mainGroup.style.marginBottom = 0;
				securityGroup.style.marginBottom = 0;
				themingGroup.style.marginBottom = 0;
				projectGroup.style.marginBottom = 0;

				menu.appendChild(mainGroup);
				menu.appendChild(securityGroup);
				menu.appendChild(themingGroup);
				menu.appendChild(projectGroup);

				menu.appendChild(unt.components.cardButton(unt.icons.logout, unt.settings.lang.getValue('logout'), function () {
					return unt.actions.dialog(unt.settings.lang.getValue('logout_q'), unt.settings.lang.getValue('logout_qq')).then(function (response) {
						if (response) {
							unt.toast({html: unt.settings.lang.getValue('logout_q') + '...'});

							return unt.settings.users.current.logout();
						}
					});
				}));
			}
		}

		return currentSection !== '' ? unt.modules.settings[currentSection](menu) : null;
	},
	edit: function (internalData) {
		document.title = unt.settings.lang.getValue('edit');

		let menu = unt.components.menuElement;

		let sections = ['main', 'contacts'];

		let section = 'main';

		let sectionContainer = document.createElement('div');
		menu.appendChild(sectionContainer);

		let url = new URLParser();
		if (sections.indexOf(url.getQueryValue('section').toLowerCase()) !== -1) {
			section = url.getQueryValue('section').toLowerCase();
		}

		if (section === 'amin') {
			return unt.modules.edit.pages.main(internalData);
		}
		if (section === 'contacts') {
			return unt.modules.edit.pages.contacts(internalData);
		}
	},
	profile: function (internalData) {
		unt.components.navPanel ? unt.components.navPanel.show() : null;

		document.title = unt.settings.lang.getValue('profile');
		let menu = unt.components.menuElement;

		let profileLoadCard = document.createElement('div');
		profileLoadCard.classList.add('valign-wrapper');
		profileLoadCard.classList.add('card');
		menu.appendChild(profileLoadCard);

		let loader = unt.components.loaderElement();
		loader.style.padding = '20px';
		profileLoadCard.appendChild(loader);

		let loaderText = document.createElement('div');
		profileLoadCard.appendChild(loaderText);
		loaderText.innerText = unt.settings.lang.getValue('loading');

		return unt.settings.users.resolve(window.location.href.split(window.location.host)[1].split('?')[0]).then(function (user) {
			unt.actions.wall.currentId = user.account_type === 'user' ? user.user_id : (user.bot_id * -1);
			profileLoadCard.hide();

			let profileCard = document.createElement('div');
			profileCard.style.marginBottom = 0;
			profileCard.classList.add('card');
			menu.appendChild(profileCard);

			let mainData = document.createElement('div');
			profileCard.appendChild(mainData);
			mainData.style.padding = '30px';

			let profileCredentials = document.createElement('div');
			profileCredentials.classList.add('unselectable');
			profileCredentials.classList.add('valign-wrapper');
			mainData.appendChild(profileCredentials);
			let userImage = unt.components.image(user.photo_object, ['circle'], 72, 72);
			profileCredentials.appendChild(userImage);
			userImage.style.marginRight = '20px';

			let userCredentials = document.createElement('div');
			userCredentials.classList.add('halign-wrapper');
			profileCredentials.appendChild(userCredentials);
			let userName = document.createElement('b');
			userName.style.fontSize = '130%';
			userCredentials.appendChild(userName);
			userName.innerText = (user.account_type == 'bot' ? user.name : (user.first_name + ' ' + user.last_name));

			let profileState = document.createElement('div');
			profileState.innerText = unt.parsers.online(user);
			userCredentials.appendChild(profileState);

			document.title = userName.innerText;
			unt.components.navPanel.setTitle(document.title);
			if (user.screen_name) history.replaceState(unt.actions.linkWorker.currentPage, document.title, '/' + user.screen_name);

			let statusDiv = document.createElement('div');
			statusDiv.classList.add('valign-wrapper');
			statusDiv.style.cursor = 'pointer';
			mainData.appendChild(statusDiv);

			let iconDiv = document.createElement('div');
			statusDiv.appendChild(iconDiv);

			let icon = unt.settings.users.current && unt.settings.users.current.user_id == user.user_id ? unt.icons.edit : unt.icons.profileStatus;
			iconDiv.innerHTML = icon;
			iconDiv.getElementsByTagName('svg')[0].style.marginTop = '4px';

			let statusTextDiv = document.createElement('div');
			statusDiv.classList.add('unselectable');
			statusTextDiv.innerText = user.status ? user.status : (unt.settings.users.current && unt.settings.users.current.user_id == user.user_id ? unt.settings.lang.getValue('edit_status') : '');
			statusDiv.appendChild(statusTextDiv);
			statusDiv.style.paddingTop = '20px';
			iconDiv.style.marginRight = '15px';

			if (!user.status && unt.settings.users.current && unt.settings.users.current.user_id != user.user_id) statusDiv.hide();
			if (!user.status && !unt.settings.users.current) statusDiv.hide();

			statusDiv.addEventListener('click', function () {
				if (unt.settings.users.current && unt.settings.users.current.user_id === user.user_id)
					return unt.settings.users.current.edit.status().then(function (newStatus) {
						if (newStatus.isEmpty())
							statusTextDiv.innerText = unt.settings.lang.getValue('edit_status');
						else
							statusTextDiv.innerText = newStatus;

						if (!newStatus.isEmpty())
							unt.settings.users.current.status = newStatus;
						else
							delete unt.settings.users.current.status;

						return unt.toast({html: unt.settings.lang.getValue('saved')});
					});
				else
					return;
			});

			if (user.can_write_on_wall) {
				let writeButton = unt.components.floatingActionButton(unt.icons.edit, unt.settings.lang.getValue('write_a_post'), true);
				mainData.appendChild(writeButton);
			}

			let profileContentMainContainer = document.createElement('div');
			let actionsDiv = document.createElement('div');
			let profileContent = document.createElement('div');

			menu.appendChild(profileContentMainContainer);

			if (!unt.tools.isMobile()) {
				profileContentMainContainer.appendChild(profileContent);
				profileContentMainContainer.appendChild(actionsDiv);
			} else {
				profileContentMainContainer.appendChild(actionsDiv);
				profileContentMainContainer.appendChild(profileContent);
			}

			if (!unt.tools.isMobile()) {
				profileContentMainContainer.classList.add('row');
				actionsDiv.classList = ['col s4'];
				actionsDiv.style.paddingLeft = '7px';
				profileContent.classList = ['col s8'];
				profileContent.style.paddingRight = 0;
			}

			let actionsMenu = unt.components.downfallingOptionsMenu(unt.icons.downArrow, unt.settings.lang.getValue('actions'));
			if (!unt.settings.users.current)
				actionsMenu = unt.components.cardButton(unt.icons.forbidden, unt.settings.lang.getValue('login_to_continue'), function () {
					let win = unt.components.windows.createImportantWindow({
						title: unt.settings.lang.getValue('logstart')
					});

					let menu = win.getMenu();

					let authResultMessage = document.createElement('div');
					authResultMessage.style.marginBottom = '10px';
					authResultMessage.style.padding = '10px';
					authResultMessage.innerText = unt.settings.lang.getValue('login_to_continue');
					menu.appendChild(authResultMessage);

					let authForm = unt.actions.authForm();
					authForm.onauthresult = function (authResult) {
						if (authResult === -1) {
							return authResultMessage.innerText = unt.settings.lang.getValue('auth_failed');
						}
						if (authResult === 0) {
							return authResultMessage.innerHTML = unt.settings.lang.getValue('upload_error');
						}
						if (authResult === 1) {
							win.close();

							return setTimeout(function () {
								let loader = unt.components.loaderElement();
								document.body.innerHTML = '';

								loader.style.position = 'absolute';
								loader.style.left = '50%';
								loader.style.marginRight = '-50%';
								loader.style.transform = 'translate(-50%, -50%)';
								loader.style.top = '45%';

								document.body.appendChild(loader);

								return setTimeout(function () {
									return window.location.reload();
								}, 1000);
							}, 1000);
						}
					}

					menu.appendChild(authForm);

					menu.style.padding = '10px';
					menu.style.textAlign = 'center';

					return win.show();
				});

			let fastAction = null;
			if (!fastAction && user.can_write_messages && unt.settings.users.current && user.user_id !== unt.settings.users.current.user_id)
				fastAction = unt.components.cardButton(unt.icons.message, unt.settings.lang.getValue('write_p'), function () {
					return unt.actions.linkWorker.go('/messages?s=' + (user.account_type === 'user' ? user.user_id : ('b' + user.bot_id)));
				}), fastAction.id = 'message';
			if (!fastAction && unt.settings.users.current && unt.settings.users.current.user_id === user.user_id)
				fastAction = unt.components.cardButton(unt.icons.edit, unt.settings.lang.getValue('edit'), function () {
					return unt.actions.linkWorker.go('/edit');
				}), fastAction.id = 'edit';

			if (!unt.settings.users.current)
				fastAction = null;

			if (user.permissions_type === 0) {
				if (unt.settings.users.current && !user.is_banned && user.account_type === 'user' && !user.is_me_blacklisted && !user.is_blacklisted && user.friend_state && user.friend_state.state === 0) {
					actionsMenu.addOption(unt.settings.lang.getValue('add_to_the_friends'), new Function());
				}
				if (unt.settings.users.current && !user.is_banned && user.account_type === 'user' && !user.is_me_blacklisted && !user.is_blacklisted && user.friend_state && user.friend_state.state === 1) {
					let initier = user.friend_state.user1;
					let accepter = user.friend_state.user2;
					if (initier === unt.settings.users.current.user_id) {
						actionsMenu.addOption(unt.settings.lang.getValue('cancel_request'), new Function());
					} else {
						actionsMenu.addOption(unt.settings.lang.getValue('accept_request'), new Function());
					}
				}
				if (unt.settings.users.current && user.friend_state && user.friend_state.state === 2) {
					actionsMenu.addOption(unt.settings.lang.getValue('delete_friend'), new Function());
				}

				if (unt.settings.users.current && !user.is_banned && user.account_type === 'user' && user.can_access_closed && !user.is_me_blacklisted && !user.is_blacklisted) {
					actionsMenu.addOption(unt.settings.lang.getValue('show_friends'), function () {
						return unt.actions.linkWorker.go('/friends?id=' + user.user_id);
					});
				}
			}

			if (user.permissions_type > 0) {
				fastAction = null;
				
				!unt.tools.isMobile() ? actionsMenu = unt.components.cardButton(unt.icons.biteCookies, unt.settings.lang.getValue('work_account_info'), new Function()) : actionsMenu.hide();
			}

			if (fastAction)
				actionsDiv.appendChild(fastAction);

			if (unt.settings.users.current && unt.settings.current.account.is_closed && user.user_id === unt.settings.users.current.user_id) {
				let closedProfileWarning = unt.components.cardButton(unt.icons.lock, unt.settings.lang.getValue('closed_profile'), function () {
					return unt.actions.dialog(unt.settings.lang.getValue('closed_profile'), unt.settings.lang.getValue('open_profile_attention'), false, true).then(function (response) {
						if (response)
							return unt.settings.users.current.profile.toggleClose().then(function (isClosed) {
								if (!isClosed)
									return closedProfileWarning.hide();
							});
					});
				});

				if (!unt.tools.isMobile())
					actionsDiv.appendChild(closedProfileWarning);
			}

			if (user.is_banned) {
				profileContent.appendChild(unt.components.alertBanner(unt.icons.forbidden, unt.settings.lang.getValue('user_banned'), unt.settings.lang.getValue('user_banned_text')));
			} else if (user.is_me_blacklisted) {
				profileContent.appendChild(unt.components.alertBanner(unt.icons.forbidden, unt.settings.lang.getValue('you_blocked'), unt.settings.lang.getValue('')));
			} else if (!user.can_access_closed && user.account_type === 'user') {
				profileContent.appendChild(unt.components.alertBanner(unt.icons.forbidden, unt.settings.lang.getValue('closed_profile'), unt.settings.lang.getValue('closed_profile_message')));
			} else {
				let loader = unt.components.loaderElement();
				profileContent.appendChild(loader);
				loader.classList.add('center');
				loader.style.marginTop = '20px';

				let postsDiv = document.createElement('div');
				postsDiv.hide();
				profileContent.appendChild(postsDiv);

				unt.actions.wall.getPosts(unt.actions.wall.currentId).then(function (posts) {
					loader.hide();

					if (posts.length <= 0)
						return profileContent.appendChild(unt.components.alertBanner(unt.icons.failed, unt.settings.lang.getValue('no_posts'), unt.settings.lang.getValue('no_posts_t')));
				
					posts.forEach(function (post) {
						postsDiv.appendChild(unt.components.wall.post(post));
					});

					return postsDiv.show();
				}).catch(function (err) {
					return profileContent.appendChild(unt.components.alertBanner(unt.icons.failed, unt.settings.lang.getValue('upload_error'), unt.settings.lang.getValue('unknown_error')));
				});
			}

			actionsDiv.appendChild(actionsMenu);
			unt.AutoInit();
		}).catch(function (err) {
			profileLoadCard.hide();

			let notFoundCard = document.createElement('div');
			notFoundCard.classList.add('card');
			menu.appendChild(notFoundCard);

			let textDivCard = document.createElement('div');
			notFoundCard.appendChild(textDivCard);
			textDivCard.style.padding = '20px';
			textDivCard.innerHTML = '<b>' + unt.settings.lang.getValue('not_found') + '</b>: ' + unt.settings.lang.getValue('not_found_m');
		});
	},
	about: function (internalData) {
		document.title = unt.settings.lang.getValue('about');
	},
	wall: function (internalData) {
		document.title = unt.settings.lang.getValue('wall');
	},
	photo: function (internalData) {
		document.title = unt.settings.lang.getValue('photo');
	}
});