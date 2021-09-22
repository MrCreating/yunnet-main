unt.pages = new Object({
	auth: function (internalData) {},
	registrer: function (internalData) {},
	restore: function (internalData) {},
	banned: function (internalData) {},
	news: function (internalData) {
		document.title = unt.settings.lang.getValue('news');
	},
	messages: function (internalData) {
		document.title = unt.settings.lang.getValue('messages');
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
	},
	edit: function (internalData) {
		document.title = unt.settings.lang.getValue('edit');
	},
	profile: function (internalData) {
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

			let icon = unt.settings.users.current.user_id == user.user_id ? unt.icons.edit : unt.icons.profileStatus;
			iconDiv.innerHTML = icon;
			iconDiv.getElementsByTagName('svg')[0].style.marginTop = '4px';

			let statusTextDiv = document.createElement('div');
			statusDiv.classList.add('unselectable');
			statusTextDiv.innerText = user.status ? user.status : (unt.settings.users.current.user_id == user.user_id ? unt.settings.lang.getValue('edit_status') : '');
			statusDiv.appendChild(statusTextDiv);
			statusDiv.style.paddingTop = '20px';
			iconDiv.style.marginRight = '15px';

			if (!user.status && unt.settings.users.current.user_id != user.user_id) statusDiv.hide();
			statusDiv.addEventListener('click', function () {
				if (unt.settings.users.current.user_id === user.user_id)
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
				let writeButton = unt.components.floatingActionButton(unt.icons.edit, unt.settings.lang.getValue('write_a_post'));
				writeButton.style.position = 'absolute';
				writeButton.style.right = 0;
				writeButton.style.zIndex = 998;
				writeButton.style.marginRight = '20px';
				mainData.appendChild(writeButton);
			}

			let profileContentMainContainer = document.createElement('div');
			let actionsDiv = document.createElement('div');
			let profileContent = document.createElement('div');

			menu.appendChild(profileContentMainContainer);
			profileContentMainContainer.appendChild(actionsDiv);
			profileContentMainContainer.appendChild(profileContent);

			if (!unt.tools.isMobile()) {
				profileContentMainContainer.classList.add('row');
				actionsDiv.classList = ['col s4'];
				actionsDiv.style.paddingRight = '7px';
				profileContent.classList = ['col s8'];
				profileContent.style.paddingLeft = 0;
			}

			let actionsMenu = unt.components.downfallingOptionsMenu(unt.icons.downArrow, unt.settings.lang.getValue('actions'));
			if (!unt.settings.users.current)
				actionsMenu = unt.components.cardButton(unt.icons.forbidden, unt.settings.lang,getValue('login_to_continue'), new Function());

			let fastAction = null;
			if (!fastAction && user.can_write_messages && unt.settings.users.current && user.user_id !== unt.settings.users.current.user_id)
				fastAction = unt.components.cardButton(unt.icons.message, unt.settings.lang.getValue('write_p'), new Function()), fastAction.id = 'message';
			if (!fastAction && unt.settings.users.current && unt.settings.users.current.user_id === user.user_id)
				fastAction = unt.components.cardButton(unt.icons.edit, unt.settings.lang.getValue('edit'), new Function()), fastAction.id = 'edit';

			if (user.friend_state.state === 2) {
				actionsMenu.addOption(unt.settings.lang.getValue('delete_friend'), new Function());
			}

			if (fastAction)
				actionsDiv.appendChild(fastAction);

			if (user.is_banned) {
				profileContent.appendChild(unt.components.alertBanner(unt.icons.forbidden, unt.settings.lang.getValue('user_banned'), unt.settings.lang.getValue('user_banned_text')));
			} else if (user.is_me_blacklisted) {
				profileContent.appendChild(unt.components.alertBanner(unt.icons.forbidden, unt.settings.lang.getValue('you_blocked'), unt.settings.lang.getValue('')));
			} else if (!user.can_access_closed) {
				profileContent.appendChild(unt.components.alertBanner(unt.icons.forbidden, unt.settings.lang.getValue('closed_profile'), unt.settings.lang.getValue('closed_profile_message')));
			} else {
				// get posts
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