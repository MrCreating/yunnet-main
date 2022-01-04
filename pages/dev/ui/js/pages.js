const pages = {
	tools: {
		panel: {
			buildItem: function (title, href = null, onclick = null) {
				let li = document.createElement('li');
				let a = document.createElement('a');
				li.appendChild(a);

				a.classList.add('waves-effect');
				if (href) a.href = href;
				if (onclick) a.onclick = onclick;

				a.innerText = title;

				return li;
			},
			buildLeftMenuItem: function () {}
		},
		buildNavigationPanel: function () {
			let navRoot = document.getElementsByClassName('navbar-fixed')[0];

			let nav = document.getElementsByTagName('nav')[0];
			if (nav.isDone)
				return nav;

			nav.classList = ['light-blue lighten'];

			let navWrapper = document.createElement('div');
			navWrapper.classList.add('nav-wrapper');
			nav.appendChild(navWrapper);

			if (ui.isMobile()) {
				let ul = document.createElement('ul');
				navWrapper.appendChild(ul);

				let li = document.createElement('li');
				ul.appendChild(li);

				let mainLink = document.createElement('a');
				mainLink.classList.add('valign-wrapper');
				li.appendChild(mainLink);

				mainLink.onclick = function (event) {
					return ui.parseAction(mainLink);
				}

				mainLink.innerHTML = '<i><svg id="nav_burger_icon" class="unt_icon" style="fill: white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"></path><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"></path></svg><svg id="nav_back_arrow_icon" style="fill: white" class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"></path><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path></svg></i>';
			
				let navHeaderTitle = document.createElement('div');
				navHeaderTitle.id = 'nav_header_title';
				navHeaderTitle.innerText = '...';
				navHeaderTitle.style.marginLeft = '18px';
				mainLink.appendChild(navHeaderTitle);

				let sidenavUl = document.createElement('ul');
				sidenavUl.classList.add('sidenav');
				sidenavUl.id = 'langs';
				navRoot.insertAdjacentElement('afterend', sidenavUl);

				if (settings.users.current) {
					let li = document.createElement('li');
					sidenavUl.appendChild(li);

					let userView = document.createElement('div');
					userView.classList.add('user-view');
					li.appendChild(userView);

					let bg = document.createElement('div');
					userView.appendChild(bg);
					bg.classList.add('background');
					bg.style.background = 'currentColor';

					let userLink = document.createElement('a');
					userLink.href = '/id' + settings.users.current.user_id;
					userView.appendChild(userLink);

					let img = document.createElement('img');
					img.classList.add('circle');
					img.id = 'user_avatar';
					img.alt = '';
					img.src = settings.users.current.photo_url;
					userLink.appendChild(img);

					let userLinkCredentials = document.createElement('a');
					userLinkCredentials.href = '/id' + settings.users.current.user_id;
					userView.appendChild(userLinkCredentials);
					let textCredentials = document.createElement('span');
					textCredentials.classList = ['white-text name'];
					userLinkCredentials.appendChild(textCredentials);
					textCredentials.innerText = settings.users.current.first_name + ' ' + settings.users.current.last_name;

					let userLinkStatus = document.createElement('a');
					userLinkStatus.href = '/id' + settings.users.current.user_id;
					userView.appendChild(userLinkStatus);
					let textStatus = document.createElement('span');
					textStatus.classList = ['white-text email'];
					userLinkCredentials.appendChild(textStatus);
					if (settings.users.current.status)
						textStatus.innerText = settings.users.current.status;
				}

				if (settings.users.current) {
					return;
				} else {
					sidenavUl.appendChild(pages.tools.panel.buildItem(settings.lang.getValue('logstart'), '/', null));
					sidenavUl.appendChild(pages.tools.panel.buildItem('Русский', null, function () {
						return settings.lang.change('ru')
					}));
					sidenavUl.appendChild(pages.tools.panel.buildItem('English', null, function () {
						return settings.lang.change('en')
					}));
				}
			} else {
				let containerDiv = document.createElement('div');
				containerDiv.classList.add('container');
				navWrapper.appendChild(containerDiv);

				let logo = document.createElement('a');
				containerDiv.appendChild(logo);
				logo.classList.add('brand-logo');
				logo.innerText = (window.location.host === 'dev.yunnet.ru' ? "yunNet. Dev" : "yunNet.");
				logo.href = '/';

				if (window.location.host === 'dev.yunnet.ru') return;

				let langsList = document.createElement('ul');
				containerDiv.appendChild(langsList);
				langsList.id = 'langs_list';
				langsList.classList = ['dropdown-content do-calc-width'];

				if (settings.users.current) {
					let itemsHTML = document.createElement('li');
					langsList.appendChild(itemsHTML);

					let userProfileLink = document.createElement('a');
					itemsHTML.appendChild(userProfileLink);
					userProfileLink.classList.add('waves-effect');
					userProfileLink.href = '/id' + settings.users.current.user_id;

					let infoContainer = document.createElement('div');
					infoContainer.classList.add('valign-wrapper');
					userProfileLink.appendChild(infoContainer);

					let img = document.createElement('img');
					img.classList.add('circle');
					img.style.marginRight = '10px';
					img.width = img.height = 36;
					img.id = 'user_avatar';
					img.src = settings.users.current.photo_url;
					infoContainer.appendChild(img);

					let credentialsInfo = document.createElement('div');
					credentialsInfo.classList.add('profile-credentials');
					infoContainer.appendChild(credentialsInfo);
					credentialsInfo.innerText = settings.users.current.first_name + ' ' + settings.users.current.last_name;
				}

				if (settings.users.current) {
					if (!settings.users.current.is_banned) {
						langsList.appendChild(pages.tools.panel.buildItem(settings.lang.getValue('edit'), '/edit', null));
						langsList.appendChild(pages.tools.panel.buildItem(settings.lang.getValue('settings'), '/settings', null));

						if (settings.users.current.user_level > 0)
							langsList.appendChild(pages.tools.panel.buildItem(settings.lang.getValue('management'), '/dev', null));
					}

					langsList.appendChild(pages.tools.panel.buildItem(settings.lang.getValue('logout'), null, function () {
						return settings.Logout(this);
					}));
				} else {
					langsList.appendChild(pages.tools.panel.buildItem(settings.lang.getValue('logstart'), '/', null));
					langsList.appendChild(pages.tools.panel.buildItem('Русский', null, function () {
						return settings.lang.change('ru')
					}));
					langsList.appendChild(pages.tools.panel.buildItem('English', null, function () {
						return settings.lang.change('en')
					}));
				}

				let actionsUl = document.createElement('ul');
				actionsUl.classList.add('right');
				containerDiv.appendChild(actionsUl);
				let li = document.createElement('li');
				actionsUl.appendChild(li);

				let trigger = document.createElement('a');
				trigger.classList.add('dropdown-trigger');
				li.appendChild(trigger);
				trigger.id = 'langs';
				trigger.setAttribute('data-target', 'langs_list');

				let containerDr = document.createElement('div');
				containerDr.classList.add('valign-wrapper');
				trigger.appendChild(containerDr);

				if (settings.users.current) {
					let img = document.createElement('img');
					img.classList.add('circle');
					img.style.marginRight = '10px';
					img.width = img.height = 25;
					img.src = settings.users.current.photo_url;
					containerDr.appendChild(img);

					let credentialsInfo = document.createElement('div');
					credentialsInfo.classList.add('profile-credentials');
					containerDr.appendChild(credentialsInfo);
					credentialsInfo.innerText = settings.users.current.first_name + ' ' + settings.users.current.last_name;
				} else {
					containerDr.innerText = settings.lang.getValue('actions');
				}

				let rightArrow = document.createElement('div');
				rightArrow.classList.add('arrow-icon');
				containerDr.appendChild(rightArrow);
				rightArrow.innerHTML = '<i class="right"><svg class="unt_icon" style="fill: white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M7 10l5 5 5-5z"></path><path d="M0 0h24v24H0z" fill="none"></path></svg></i>'	
			}

			nav.isDone = true;

			return nav;
		},
		buildLeftMenu: function () {
			let leftMenu = ui.isMobile() ? document.getElementById('langs') : document.getElementsByClassName('col s3')[0];

			let langValues = [];
			let identifiers = [];
			let icons = [];
			let links = [];

			let currentMenuItems = [];

			if (window.location.host === "dev.yunnet.ru") {
				langValues = ['main', 'api', 'apps', 'bots'];
				identifiers = ['main', 'api', 'apps', 'bots'];
				links = ['/', '/methods', '/apps', '/bots'];

				icons = [
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24" viewBox="0 0 24 24" width="24"><g><rect fill="none" height="24" width="24"/><path d="M17.6,9.48l1.84-3.18c0.16-0.31,0.04-0.69-0.26-0.85c-0.29-0.15-0.65-0.06-0.83,0.22l-1.88,3.24 c-2.86-1.21-6.08-1.21-8.94,0L5.65,5.67c-0.19-0.29-0.58-0.38-0.87-0.2C4.5,5.65,4.41,6.01,4.56,6.3L6.4,9.48 C3.3,11.25,1.28,14.44,1,18h22C22.72,14.44,20.7,11.25,17.6,9.48z M7,15.25c-0.69,0-1.25-0.56-1.25-1.25 c0-0.69,0.56-1.25,1.25-1.25S8.25,13.31,8.25,14C8.25,14.69,7.69,15.25,7,15.25z M17,15.25c-0.69,0-1.25-0.56-1.25-1.25 c0-0.69,0.56-1.25,1.25-1.25s1.25,0.56,1.25,1.25C18.25,14.69,17.69,15.25,17,15.25z"/></g></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.9 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/></svg>'
				];

				currentMenuItems = [1, 2, 3, 4];
			} else {
				if (!settings.users.current) return;

				currentMenuItems = settings.currentSettings.theming.menu_items;

				langValues = ['news', 'notifications', 'friends', 'messages', 'groups', 'archive', 'audios', 'settings'];
				identifiers = ['news_item', 'notifications_item', 'friends_item', 'messages_item', 'archive_item', 'audios_item', 'settings_item'];

				icons = [
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/><path d="M0 0h24v24H0z" fill="none"/></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/><path d="M0 0h24v24H0z" fill="none"/></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/><path d="M0 0h24v24H0z" fill="none"/></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24" viewBox="0 0 24 24" width="24"><rect fill="none" height="24" width="24"/><g><path d="M12,12.75c1.63,0,3.07,0.39,4.24,0.9c1.08,0.48,1.76,1.56,1.76,2.73L18,18H6l0-1.61c0-1.18,0.68-2.26,1.76-2.73 C8.93,13.14,10.37,12.75,12,12.75z M4,13c1.1,0,2-0.9,2-2c0-1.1-0.9-2-2-2s-2,0.9-2,2C2,12.1,2.9,13,4,13z M5.13,14.1 C4.76,14.04,4.39,14,4,14c-0.99,0-1.93,0.21-2.78,0.58C0.48,14.9,0,15.62,0,16.43V18l4.5,0v-1.61C4.5,15.56,4.73,14.78,5.13,14.1z M20,13c1.1,0,2-0.9,2-2c0-1.1-0.9-2-2-2s-2,0.9-2,2C18,12.1,18.9,13,20,13z M24,16.43c0-0.81-0.48-1.53-1.22-1.85 C21.93,14.21,20.99,14,20,14c-0.39,0-0.76,0.04-1.13,0.1c0.4,0.68,0.63,1.46,0.63,2.29V18l4.5,0V16.43z M12,6c1.66,0,3,1.34,3,3 c0,1.66-1.34,3-3,3s-3-1.34-3-3C9,7.34,10.34,6,12,6z"/></g></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M0 0h24v24H0z" fill="none"/><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>',
					'<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24" viewBox="0 0 24 24" width="24"><g><path d="M0,0h24v24H0V0z" fill="none"/><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></g></svg>'
				];

				links = ['/', '/notifications', '/friends', '/messages', '/groups', '/archive', '/audios', '/settings'];
			}

			let collectionDiv;
			if (!ui.isMobile()) {
				collectionDiv = document.createElement('div');
				collectionDiv.classList.add('collection');
				collectionDiv.style.marginLeft = '20%';
				leftMenu.appendChild(collectionDiv);
			}

			currentMenuItems.forEach(function (itemId) {
				if (settings.users.current && settings.users.current.is_banned) return;

				let menuItemIndex = itemId - 1;

				if (ui.isMobile()) {
					let li = document.createElement('li');
					let a = document.createElement('a');
					li.appendChild(a);

					a.classList.add('waves-effect');
					a.href = links[menuItemIndex];

					a.innerHTML = '<i>' + icons[menuItemIndex] + '</i>' + (settings.lang.getValue(langValues[menuItemIndex]) || 'API');
					leftMenu.appendChild(li);
				} else {
					if (itemId === 8) return;

					let a = document.createElement('a');
					a.classList = ['collection-item waves-effect'];

					a.innerText = (settings.lang.getValue(langValues[menuItemIndex]) || 'API');
					a.href = links[menuItemIndex];

					collectionDiv.appendChild(a);
				}
			})

			if (settings.users.current && settings.users.current.user_level > 0 && ui.isMobile() && window.location.host !== "dev.yunnet.ru") {
				leftMenu.appendChild(pages.tools.panel.buildItem(settings.lang.getValue('management'), 'https://yunnet.ru/dev', null));
			}

			if (settings.users.current && settings.users.current.is_banned && ui.isMobile() && window.location.host !== "dev.yunnet.ru") {
				leftMenu.appendChild(pages.tools.panel.buildItem(settings.lang.getValue('logout'), null, function () {
					return settings.Logout(this);
				}));
			}
		}
	},
	chats: {
		elements: {
			window: function (withCreationButton = true) {
				let mainData = document.createElement('div');
				mainData.classList = ['card full_section'];

				let dataDiv = document.createElement('div');
				mainData.appendChild(dataDiv);

				dataDiv.classList.add('valign-wrapper');

				let chatImageDiv = document.createElement('div');

				let img = new Image();
				chatImageDiv.appendChild(img);
				dataDiv.appendChild(chatImageDiv);

				img.width = img.height = 64;
				img.alt = '';
				img.classList.add('circle');

				let chatTItleDiv = document.createElement('div');
				dataDiv.appendChild(chatTItleDiv);

				chatTItleDiv.style.width = '100%';
				chatTItleDiv.style.marginLeft = '10px';

				let inputDiv = document.createElement('div');
				chatTItleDiv.appendChild(inputDiv);
				inputDiv.classList.add('input-field');

				let input = document.createElement('input');
				inputDiv.appendChild(input);
				input.type = 'text';
				input.placeholder = settings.lang.getValue("chat_title");

				if (withCreationButton) {
					let buttonDiv = document.createElement('div');
					mainData.appendChild(buttonDiv);
					buttonDiv.style.width = '100%';

					let createButton = pages.elements.createFAB(unt.Icon.SAVE, new Function());
					createButton.classList.remove('fixed-action-btn');
					createButton.style.textAlign = 'end';

					buttonDiv.appendChild(createButton);

					createButton.classList.add('scale-transition');
					createButton.classList.add('scale-out');

					mainData.getCreateButton = function () {
						return createButton;
					}

					let loader = pages.elements.getLoader();

					loader.classList.remove('center');
					loader.style.textAlign = 'end';

					buttonDiv.appendChild(loader);

					loader.style.display = 'none';
					mainData.setLoading = function (loading) {
						if (loading) {
							createButton.style.display = 'none';
							loader.style.display = '';
						} else {
							loader.style.display = 'none';
							createButton.style.display = '';
						}
					}
				}

				return mainData;
			},
			permissionsWindow: function (permissionsObject = {}, clickCallback = null, notPerms = false, maxLevel = 9) {
				let permissions = {
					can_change_title: 4,
					can_change_photo: 4,
					can_kick: 7,
					can_invite: 7,
					can_invite_bots: 8,
					can_mute: 5,
					can_pin_message: 4,
					delete_messages_2: 7,
					can_change_levels: 9,
					can_link_join: 0
				};

				for (let key in permissions) {
					if (permissionsObject[key] !== undefined) {
						if (permissionsObject[key] >= 0 && permissionsObject[key] <= 9) permissions[key] = permissionsObject[key];
					}
				}

				if (notPerms)
					permissions = permissionsObject;

				let element = document.createElement('ul');
				element.classList.add('collapsible');

				for (let key in permissions) {
					let li = document.createElement('li');
					element.appendChild(li);

					let colHeader = document.createElement('div');
					colHeader.classList.add('collapsible-header');

					li.appendChild(colHeader);
					colHeader.innerText = settings.lang.getValue(key);

					let colBody = document.createElement('div');
					colBody.classList.add('collapsible-body');
					li.appendChild(colBody);

					let radioForm = document.createElement('form');
					radioForm.action = '#';
					colBody.appendChild(radioForm);

					for (let i = 0; i <= maxLevel; i++) {
						let p = document.createElement('p');
						radioForm.appendChild(p);

						let selectionLabel = document.createElement('label');
						p.appendChild(selectionLabel);

						let input = document.createElement('input');
						input.oninput = function () {
							if (clickCallback) return clickCallback(key, i);
						}

						selectionLabel.appendChild(input);

						input.classList.add('with-gap');
						input.type = 'radio';
						input.name = key;

						if (i === permissions[key]) input.setAttribute('checked', 'true');

						let span = document.createElement('span');
						selectionLabel.appendChild(span);
						span.innerText = i;
					}
				}

				return element;
			}
		},
		create: function (internalData = {}) {
			if (!settings.users.current) return pages.news();

			let menuBody = pages.elements.menuBody().clear();

			if (!ui.isMobile()) document.getElementsByClassName('col s3')[1].innerHTML = '';

			ui.urls.push('https://' + window.location.host + '/messages');
			ui.canBack = true;

			document.title = "yunNet. - " + (settings.lang.getValue("create_chat"));

			let rightMenu = pages.elements.buildRightMenu().append();

			let mainItem = rightMenu.addItem(settings.lang.getValue("main"), function (event) {
				internalData.section = 'main';

				return pages.chats.create(internalData);
			});
			let infoItem = rightMenu.addItem(settings.lang.getValue("info"), function (event) {
				internalData.section = 'info';

				return pages.chats.create(internalData);
			});

			if (!ui.isMobile()) {
				let settingsData = settings.get();
				if (settingsData) {
					if (settingsData.theming.backButton && !ui.isMobile()) {
						menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('back')), function (event) {
							return ui.go(null, true);
						});
					}
				}
			} else {
				nav_burger_icon.style.display = 'none';
				nav_back_arrow_icon.style.display = '';
				nav_header_title.innerText = settings.lang.getValue("create_chat");
			}

			let currentData = internalData.data;
			if (!currentData) currentData = internalData.data = {
				title: '',
				src: 'https://dev.yunnet.ru/images/default.png',
				photo: '',
				members: [],
				permissions: {
					can_change_title: 4,
					can_change_photo: 4,
					can_kick: 7,
					can_invite: 7,
					can_invite_bots: 8,
					can_mute: 5,
					can_pin_message: 4,
					delete_messages_2: 7,
					can_change_levels: 9,
					can_link_join: 0
				}
			}

			let currentSection = 'main';
			if (internalData.section) currentSection = internalData.section;

			if (currentSection === 'main') {
				mainItem.select();
				let chatDiv = pages.chats.elements.window();

				chatDiv.getElementsByTagName('input')[0].value = currentData.title;
				unt.updateTextFields();

				chatDiv.getElementsByTagName('img')[0].src = currentData.src;
				chatDiv.getElementsByTagName('input')[0].oninput = function () {
					currentData.title = this.value;

					if (currentData.title.isEmpty() || currentData.members.length < 2) chatDiv.getCreateButton().classList.add('scale-out');
					else chatDiv.getCreateButton().classList.remove('scale-out');

					internalData.isCreating = true;
				}

				chatDiv.getElementsByTagName('img')[0].onclick = function () {
					return pages.elements.fileUploader({
						onFileSelected: function (event, files, uploader) {
							uploader.setLoading(true);

							return uploads
								.getURL()
								.then(function (url) {
									return uploads
										.upload(url, files[0], function (event) {
											return codes.callbacks.uploadResolve(event, uploader);
										}).then(function (attachment) {
											uploader.setLoading(false);

											internalData.data.photo = 'photo' + attachment.photo.owner_id + '_' + attachment.photo.id + '_' + attachment.photo.access_key;
											internalData.data.src = attachment.photo.url.main;

											chatDiv.getElementsByTagName('img')[0].src = currentData.src;

											return uploader.close();
										}).catch(function (err) {
											uploader.setLoading(false);

											return unt.toast({html: settings.lang.getValue("upload_error")});
										});
									})
						},
						afterClose: function () {}
					}).open().addFooterItem(settings.lang.getValue("delete_a_photo"), function (event, button, uploader) {
						chatDiv.getElementsByTagName('img')[0].src = 'https://dev.yunnet.ru/images/default.png';

						internalData.data.src = chatDiv.getElementsByTagName('img')[0].src;
						internalData.data.photo = '';

						return uploader.close();
					});
				}

				menuBody.appendChild(chatDiv);

				let friendsLoader = pages.elements.getLoader();
				menuBody.appendChild(friendsLoader);

				let friendsDiv = document.createElement('div');
				friendsDiv.style.display = 'none';

				friendsDiv.classList = ['card collection'];
				menuBody.appendChild(friendsDiv);

				if (currentData.title.isEmpty() || currentData.members.length < 2) chatDiv.getCreateButton().classList.add('scale-out');
				else chatDiv.getCreateButton().classList.remove('scale-out');

				chatDiv.getCreateButton().onclick = function () {
					chatDiv.getElementsByTagName('input')[0].setAttribute('disabled', 'true');

					chatDiv.getCreateButton().classList.add('scale-out');
					friendsDiv.style.display = 'none';

					chatDiv.setLoading(true);
					return messages.createChat(internalData.data).then(function (chatId) {
						return ui.go('https://' + window.location.host + '/messages?s=' + chatId);
					}).catch(function (err) {
						chatDiv.getElementsByTagName('input')[0].removeAttribute('disabled');

						chatDiv.getCreateButton().classList.remove('scale-out');
						friendsDiv.style.display = '';

						chatDiv.setLoading(false);
						internalData.isCreating = false;

						let errorText = settings.lang.getValue("upload_error");

						let code = err.errorCode;
						if (code === -1) errorText = settinga.lang.getValue('title_error');
						if (code === -2) errorText = settings.lang.getValue('users_error');

						return unt.toast({html: errorText});
					});
				}

				return settings.users.friends.get(settings.users.current.user_id, 'friends', 1).then(function (friends) {
					friends.forEach(function (user) {
						if (!user.can_invite_to_chat) return;

						let userElement = pages.elements.userItem(user);
						userElement.href = '';

						let selectionDiv = document.createElement('div');
						userElement.appendChild(selectionDiv);

						selectionDiv.classList.add('secondary-content');

						let form = document.createElement('form');
						selectionDiv.appendChild(form);

						let p = document.createElement('p');
						form.appendChild(p);

						let label = document.createElement('label');
						p.appendChild(label);

						let input = document.createElement('input');
						input.type = 'checkbox';
						label.appendChild(input);

						let span = document.createElement('span');
						label.appendChild(span);
						span.innerHTML = '';

						userElement.onclick = function (event) {
							event.preventDefault();

							return input.oninput(event);
						}

						input.oninput = function () {
							input.getAttribute('checked') === 'checked' ? input.removeAttribute('checked') : input.setAttribute('checked', 'checked');

							internalData.data.members.indexOf(user.user_id) === -1 ? internalData.data.members.push(user.user_id) : internalData.data.members.splice(internalData.data.members.indexOf(user.user_id), 1);
							
							if (currentData.title.isEmpty() || currentData.members.length < 2) chatDiv.getCreateButton().classList.add('scale-out');
							else chatDiv.getCreateButton().classList.remove('scale-out');
						}

						if (internalData.data.members.indexOf(user.user_id) !== -1) {
							input.setAttribute('checked', 'checked');
						}

						friendsDiv.appendChild(userElement);
					});

					friendsLoader.hide();
					friendsDiv.style.display = '';
				}).catch(function (err) {
					let uploadError = pages.elements.uploadError();

					friendsDiv.style.display = 'none';
					menuBody.appendChild(uploadError);

					friendsLoader.hide();
					ui.bindItems();
				});
			}
			if (currentSection === 'info') {
				infoItem.select();
				let permissionsDiv = pages.chats.elements.permissionsWindow(currentData.permissions, function (groupName, value) {
					if (currentData.permissions[groupName] === undefined) return;
					if (value < 0 || value > 9) return;

					currentData.permissions[groupName] = value;
				});

				menuBody.appendChild(permissionsDiv);
				unt.Collapsible.init(permissionsDiv);
			}

			console.log(currentData);
			internalData.data = currentData;
		}
	},
	unauth: {
		banned: function () {
			if (!settings.users.current) return pages.news();
			if (!settings.users.current.is_banned) return pages.news();

			let menuBody = pages.elements.menuBody().clear();
			document.title = 'yunNet. - ' + settings.lang.getValue('banned');

			let cardDiv = document.createElement('div');
			menuBody.appendChild(cardDiv);

			cardDiv.classList = ['card full_section'];

			let banText = document.createElement('div');
			cardDiv.appendChild(banText);

			banText.classList.add('center');
			banText.innerText = settings.users.current.first_name + ', ' + settings.lang.getValue('your_account_banned');
		},
		authPage: function () {
			if (settings.users.current) return pages.news();

			let menuBody = pages.elements.menuBody().clear();

			let siteLogo = document.createElement('img');
			menuBody.appendChild(siteLogo);
			siteLogo.src = "/favicon.ico";
			siteLogo.width = siteLogo.height = 96;
			siteLogo.style.borderRadius = "120px";
			siteLogo.style.boxShadow = "0 0 2px black";
			siteLogo.style.marginLeft = "calc(50% - 48px)";
			siteLogo.style.marginTop = "10%";

			menuBody.appendChild(document.createElement('br'));

			let errorMessage = document.createElement('div');
			menuBody.appendChild(errorMessage);
			errorMessage.classList = ['collection incorrect_message'];
			errorMessage.style.display = 'none';
			let collectionItem = document.createElement('a');
			errorMessage.appendChild(collectionItem);
			collectionItem.classList.add('collection-item');
			collectionItem.style = 'background-color: #42a5f5 !important; color: white; margin-left: 3% !important; margin-right: 3% !important';
			collectionItem.innerHTML = settings.lang.getValue('incorrect_login');

			let authFormContainer = document.createElement('div');

			if (!ui.isMobile()) {
				authFormContainer.style.width = "400px";
				authFormContainer.style.marginLeft = "calc(50% - 200px)";
				authFormContainer.classList.add("card");
			}
			menuBody.appendChild(authFormContainer);

			let authForm = document.createElement('form');
			authFormContainer.appendChild(authForm);

			authForm.method = "POST";
			authForm.name = "login";
			authForm.classList.add("container");
			authForm.action = "/?action=login";

			authForm.appendChild(document.createElement('br'));

			let loginInputField = document.createElement('div');
			loginInputField.classList.add('input-field');
			authForm.appendChild(loginInputField);
			let loginIcon = document.createElement('i');
			loginInputField.appendChild(loginIcon);
			loginIcon.innerHTML = '<svg class="unt_icon" style="height: 40 !important" xmlns="http://www.w3.org/2000/svg" height="40" viewBox="0 0 32 32" width="40"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"></path><path d="M0 0h24v24H0z" fill="none"></path></svg>';
			loginIcon.classList.add('prefix');
			let inputLogin = document.createElement('input');
			loginInputField.appendChild(inputLogin);
			inputLogin.id = "login_field";
			inputLogin.placeholder = "E-mail or phone";
			inputLogin.placeholder = (settings.lang.getValue("login"));
			inputLogin.type = "email";
			inputLogin.name = "email";
			inputLogin.autocomplete = "off";

			let passwordInputField = document.createElement('div');
			passwordInputField.classList.add('input-field');
			authForm.appendChild(passwordInputField);
			let passwordIcon = document.createElement('i');
			passwordInputField.appendChild(passwordIcon);
			passwordIcon.innerHTML = '<svg class="unt_icon" style="height: 40 !important" xmlns="http://www.w3.org/2000/svg" height="40" viewBox="0 0 32 32" width="40"><path d="M0 0h24v24H0z" fill="none"></path><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"></path></svg>';
			passwordIcon.classList.add('prefix');
			let passwordLogin = document.createElement('input');
			passwordInputField.appendChild(passwordLogin);
			passwordLogin.id = "password_field";
			passwordLogin.placeholder = "Password";
			passwordLogin.placeholder = (settings.lang.getValue("password"));
			passwordLogin.type = "password";
			passwordLogin.name = "password";
			passwordLogin.autocomplete = "off";

			let forgetPassDiv = document.createElement('div');
			authForm.appendChild(forgetPassDiv);
			forgetPassDiv.classList.add("center");
			let linkToRestore = document.createElement('a');
			forgetPassDiv.appendChild(linkToRestore);
			linkToRestore.href = "/restore";
			linkToRestore.innerText = "Forgot password?";
			linkToRestore.innerText = (settings.lang.getValue("forgot_password"))

			authForm.appendChild(document.createElement('br'));

			let loginButtonDiv = document.createElement('div');
			authForm.appendChild(loginButtonDiv);
			loginButtonDiv.classList.add("center");
			let loginButton = document.createElement('button');
			loginButtonDiv.appendChild(loginButton);
			loginButton.style.backgroundColor = "#64b5f6 !important";
			loginButton.classList = ["btn modal-trigger waves-effect btn-large waves-light"];
			let authTextDiv = document.createElement('div');
			loginButton.appendChild(authTextDiv);
			authTextDiv.id = "auth_text";
			let authText = document.createElement('t');
			authTextDiv.appendChild(authText);
			authText.innerText = settings.lang.getValue("logstart");
			let authIcon = document.createElement('i');
			authTextDiv.appendChild(authIcon);
			authIcon.innerHTML = '<svg style="fill: white" class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path><path d="M0 0h24v24H0z" fill="none"></path></svg>';
			authIcon.classList.add('right');
			authIcon.style.marginTop = '3px';
			let authLoader = document.createElement('div');
			loginButton.appendChild(authLoader);
			authLoader.style = 'display: none; margin-top: 20%';
			authLoader.id = 'auth_loader';
			let loaderDiv = document.createElement('div');
			authLoader.appendChild(loaderDiv);
			loaderDiv.innerHTML = '<svg width="40" height="40" viewBox="0 0 50 50"><path fill="white" transform="rotate(61.2513 25 25)" d="M25,5A20.14,20.14,0,0,1,45,22.88a2.51,2.51,0,0,0,2.49,2.26h0A2.52,2.52,0,0,0,50,22.33a25.14,25.14,0,0,0-50,0,2.52,2.52,0,0,0,2.5,2.81h0A2.51,2.51,0,0,0,5,22.88,20.14,20.14,0,0,1,25,5Z" fill="#42a5f5"><animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.4s" repeatCount="indefinite"></animateTransform></path></svg>';
			loginButton.onclick = function (event) {
				return account.auth(loginButton, event);
			}

			authForm.appendChild(document.createElement('br'));

			let registerDiv = document.createElement('div');
			authForm.appendChild(registerDiv);
			registerDiv.classList.add("center");
			let linkToReg = document.createElement('a');
			registerDiv.appendChild(linkToReg);
			
			linkToReg.style.cursor = 'pointer';
			linkToReg.innerText = settings.lang.getValue("regstart");
			linkToReg.onclick = function (event) {
				event.preventDefault();

				return pages.elements.confirm('', settings.lang.getValue('reg_privacy_warning'), function (response) {
					if (response)
						return ui.go('https://' + window.location.host + '/register');
				});
			}

			authForm.appendChild(document.createElement('br'));
			return ui.bindItems();
		}
	},
	cookies: function () {
		if (!settings.users.current) return pages.news();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("cookies_manage") : null;

		let menuBody = pages.elements.menuBody().clear();
		let settingsData = settings.get();
		if (settingsData && settingsData.theming.backButton && !ui.isMobile() && ui.canBack) {
			menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('cookies_manage')), function (event) {
				return ui.go(null, true);
			});
		}

		menuBody.appendChild(pages.elements.createButton(null, null, null, 'black', [
			[
				unt.Icon.COOKIE,
				settings.lang.getValue('cookies') + ': <b>' + settingsData.account.balance.cookies + '</b>',
				new Function()
			], [
				unt.Icon.BITE_COOKIE,
				settings.lang.getValue('half_cookies') + ': <b>' + settingsData.account.balance.half_cookies + '</b>',
				new Function()
			]
		]));
	},
	aboutPage: function () {
		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("about") : null;

		let menuBody = pages.elements.menuBody().clear();

		let settingsData = settings.get();
		if (settingsData) {
			if (settingsData.theming.backButton && !ui.isMobile() && ui.canBack) {
				menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('about')), function (event) {
					return ui.go(null, true);
				});
			}
		}
		
		let mainInfoCard = document.createElement('div');
		mainInfoCard.classList.add('card');
		menuBody.appendChild(mainInfoCard);

		let innerDiv = document.createElement('div');
		mainInfoCard.appendChild(innerDiv);
		innerDiv.style.width = '100%';

		let img = new Image();
		img.counter = 0;
		img.needed = 8;
		img.onclick = function () {
			img.counter++;

			if (img.needed <= img.counter) {
				img.counter = 0;

				return welcome.to.something.out();
			}
		}

		img.src = '/favicon.ico';
		innerDiv.appendChild(img);
		img.classList.add('circle');

		innerDiv.style.textAlign = '-webkit-center';
		innerDiv.style.padding = '20px';
		img.width = img.height = 64;

		let titleDiv = document.createElement('div');
		mainInfoCard.appendChild(titleDiv);
		titleDiv.innerHTML = '<b>yunNet.</b>';
		titleDiv.classList.add('center');

		let description = document.createElement('div');
		mainInfoCard.appendChild(description);
		description.innerHTML = '<p>' + (settings.lang.getValue('project-description')) + '</p>';

		let versionDiv = document.createElement('i');

		versionDiv.classList.add('center');
		description.appendChild(versionDiv);
		versionDiv.innerText = 'yunNet[alpha] 6.0.1 (2019-2021)';

		description.style.padding = '20px';

		let infoLoader = pages.elements.getLoader();
		menuBody.appendChild(infoLoader);

		settings.getStats().then(function (stats) {
			let collapsible = pages.elements.createCollapsible([
				[
					unt.Icon.STATS,
					settings.lang.getValue('stats')
				]
			]);

			let body = collapsible.getBody(0);

			let collection = document.createElement('div');
			collection.classList.add('collection');
			body.appendChild(collection);

			let usersItem = document.createElement('div');
			usersItem.classList.add('collection-item');
			collection.appendChild(usersItem);
			usersItem.innerText = settings.lang.getValue('registered_users') + ': ';

			let usersB = document.createElement('b');
			usersItem.appendChild(usersB);
			usersB.innerText = stats.users.toLocaleString('en-US');

			let messagesItem = document.createElement('div');
			messagesItem.classList.add('collection-item');
			collection.appendChild(messagesItem);
			messagesItem.innerText = settings.lang.getValue('sent_messages') + ': ';

			let messagesB = document.createElement('b');
			messagesItem.appendChild(messagesB);
			messagesB.innerText = stats.messages.toLocaleString('en-US');

			let botsItem = document.createElement('div');
			botsItem.classList.add('collection-item');
			collection.appendChild(botsItem);
			botsItem.innerText = settings.lang.getValue('registered_bots') + ': ';

			let botsB = document.createElement('b');
			botsItem.appendChild(botsB);
			botsB.innerText = stats.bots.toLocaleString('en-US');

			menuBody.appendChild(collapsible);
			unt.AutoInit();

			infoLoader.hide();
		}).catch(function (err) {
			return infoLoader.hide();
		})
	},
	chatsPage: function (internalData) {
		if (settings.users.current && settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("chat") : null;

		document.title = 'yunNet. - ' + settings.lang.getValue('chat');

		let menuBody = pages.elements.menuBody().clear();
		let currentQuery = window.location.href.split(window.location.host)[1].split('?')[1].split('c=')[1];
		
		let settingsData = settings.get();
		if (settingsData && settingsData.theming.backButton && ui.canBack && !ui.isMobile()) {
			menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('chat')), function (event) {
				return ui.go(null, true);
			});
		}

		let loader = pages.elements.getLoader();
		menuBody.appendChild(loader);
		loader.style.marginTop = '15px';

		return messages.utils.getChatInfoByLink(currentQuery).then(function (response) {
			loader.hide();

			let chatInfoCard = document.createElement('div');

			chatInfoCard.classList.add('card');
			chatInfoCard.classList.add('full_section');
			chatInfoCard.classList.add('valign-wrapper');

			chatInfoCard.style.width = '100%';

			menuBody.appendChild(chatInfoCard);

			let chatPhoto = document.createElement('img');
			chatPhoto.classList.add('circle');

			chatPhoto.width = chatPhoto.height = 64;
			chatPhoto.src = response.photo;
			chatInfoCard.appendChild(chatPhoto);

			let chatInfoDiv = document.createElement('div');
			chatInfoDiv.style.marginLeft = '15px';
			chatInfoDiv.style.width = '100%';

			chatInfoDiv.classList.add('halign-wrapper');
			chatInfoCard.appendChild(chatInfoDiv);

			let chatTitle = document.createElement('div');
			chatInfoDiv.appendChild(chatTitle);

			chatTitle.style.width = 'calc(100% - 150px)';
			chatTitle.style.whiteSpace = 'nowrap';
			chatTitle.style.overflow = 'hidden';
			chatTitle.style.textOverflow = 'ellipsis';

			let innerB = document.createElement('b');
			chatTitle.appendChild(innerB);
			innerB.innerText = response.title;

			let membersCount = document.createElement('div');
			chatInfoDiv.appendChild(membersCount);
			membersCount.innerText = (response.members_count + ' ' + (settings.lang.getValue("id") === "ru" ? pages.parsers.morph(response.members_count, pages.parsers.forms.MEMBERS_RUSSIAN) : settings.lang.getValue("members")));
			
			if (settings.users.current) {
				menuBody.appendChild(pages.elements.createButton(unt.Icon.SAVE, settings.lang.getValue('chat_join'), function () {
					let loading = pages.elements.loadingMode().getInstance();

					return messages.utils.joinByLink(currentQuery).then(function (new_chat_id) {
						loading.close();

						return ui.go('https://' + window.location.host + '/messages?s=' + new_chat_id);
					}).catch(function (err) {
						loading.close();

						return unt.toast({html: settings.lang.getValue('upload_error')});
					})
				}));

				if (ui.canBack)
					menuBody.appendChild(pages.elements.createButton(unt.Icon.CLOSE, settings.lang.getValue('cancel'), function () {
						return ui.go(null, true);
					}), 'red');
			} else {
				menuBody.appendChild(pages.elements.createButton(unt.Icon.GROUP, settings.lang.getValue('login_to_join_chat'), function () {
					return pages.unauth.authPage();
				}));
			}

		}).catch(function (err) {
			loader.hide();

			if (err.errorCode === 1) {
				return menuBody.appendChild(pages.elements.uploadError());
			}

			if (err.errorCode === 2) {
				return ui.go('https://' + window.location.host + '/messages?s=' + err.chatId);
			}

			if (err.errorCode === 3) {
				let profileBody_card = document.createElement('div');

				profileBody_card.classList.add('card');
				profileBody_card.classList.add('full_section');

				let notFoundText = document.createElement('div');
				profileBody_card.appendChild(notFoundText);

				notFoundText.innerText = settings.lang.getValue('error_join');
				notFoundText.classList.add('center');

				return menuBody.appendChild(profileBody_card);
			}
		})
	},
	rules: function () {
		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("rules") : null;
		let menuBody = pages.elements.menuBody().clear();

		let loader = pages.elements.getLoader();
		loader.style.marginTop = "15px";
		menuBody.appendChild(loader);

		let data = new FormData();

		data.append('action', 'get_rules_text');

		return ui.Request({
			url: '/rules',
			method: 'POST',
			data: data,
			success: function (response) {
				loader.hide();

				try {
					rulesText = JSON.parse(response).rules;
					if (!rulesText)
						throw new Error();

					let elementCard = document.createElement('div');
					elementCard.classList = ['card full_section'];

					menuBody.appendChild(elementCard);

					return elementCard.innerHTML = rulesText;
				} catch (e) {
					return menuBody.appendChild(pages.elements.uploadError());
				}
			}
		});
	},
	terms: function () {
		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("terms") : null;
		let menuBody = pages.elements.menuBody().clear();

		let loader = pages.elements.getLoader();
		loader.style.marginTop = "15px";
		menuBody.appendChild(loader);

		let data = new FormData();

		data.append('action', 'get_terms_text');

		return ui.Request({
			url: '/terms',
			method: 'POST',
			data: data,
			success: function (response) {
				loader.hide();

				try {
					termsText = JSON.parse(response).terms;
					if (!termsText)
						throw new Error();

					let elementCard = document.createElement('div');
					elementCard.classList = ['card full_section'];

					menuBody.appendChild(elementCard);

					return elementCard.innerHTML = termsText;
				} catch (e) {
					return menuBody.appendChild(pages.elements.uploadError());
				}
			}
		});
	},
	news: function () {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("news") : null;
		ui.userVisited = settings.users.current.user_id;

		let menuBody = pages.elements.menuBody().clear();
		let wallInput = 
						pages.elements.wallInput({
							photoUrl: settings.users.current.photo_url,
							hideInputButton: true,
							pasteCallback: function (event, files) {
								if (attachments.getElementsByTagName("div").length >= 10)
									return unt.toast({html: settings.lang.getValue('error_atts')});

								return uploads
									.getURL()
									.then(function (url) {
										return uploads
											.upload(url, files[0], function (event) {
												return;
											})
											.then(function (attachment) {
												attachments.appendChild(pages.parsers.attachment(attachment, function () {
													if (pages.parsers.attachmentsValid(attachments, write_text)) {
														return wallInput.showInputButton();
													} else {
														return wallInput.hideInputButton();
													}
												}));

												if (pages.parsers.attachmentsValid(attachments, write_text)) {
													return wallInput.showInputButton();
												} else {
													return wallInput.hideInputButton();
												}
											})
											.catch(function (err) {
												let errorString = settings.lang.getValue("upload_error");

												unt.toast({html: errorString});
											});
									})
									.catch(function (err) {
										let errorString = settings.lang.getValue("upload_error");

										unt.toast({html: errorString});
									});
							},
							attachFileCallback: function (event, attachItem) {
								if (attachments.getElementsByTagName("div").length >= 10)
									return unt.toast({html: settings.lang.getValue('error_atts')});

								return pages.elements.contextMenu([
									[
										settings.lang.getValue('photo'),
										function () {
											let fileUploader = pages.elements.fileUploader({
												onFileSelected: function (event, files, uploader) {
													uploader.setLoading(true);

													return uploads
														.getURL()
														.then(function (url) {
															return uploads
																.upload(url, files[0], function (event) {
																	return codes.callbacks.uploadResolve(event, uploader);
																})
																.then(function (attachment) {
																	uploader.setLoading(false);

																	attachments.appendChild(pages.parsers.attachment(attachment, function () {
																		if (pages.parsers.attachmentsValid(attachments, write_text)) {
																			return wallInput.showInputButton();
																		} else {
																			return wallInput.hideInputButton();
																		}
																	}));

																	if (pages.parsers.attachmentsValid(attachments, write_text)) {
																		wallInput.showInputButton();
																	} else {
																		wallInput.hideInputButton();
																	}

																	return uploader.close();
																})
																.catch(function (err) {
																	let errorString = settings.lang.getValue("upload_error");

																	unt.toast({html: errorString});
																	return uploader.setLoading(false);
																});
														})
														.catch(function (err) {
															let errorString = settings.lang.getValue("upload_error");

															unt.toast({html: errorString});
															return uploader.setLoading(false);
														});
												},
												afterClose: function (event, uploader) {
													if (pages.parsers.attachmentsValid(attachments, write_text)) {
														return wallInput.showInputButton();
													} else {
														return wallInput.hideInputButton();
													}
												}
											});

											return fileUploader.open();
										}
									],
									[
										settings.lang.getValue('poll'),
										function () {
											if (poll.pinned(attachments)) return unt.toast({html: settings.lang.getValue('upload_error')});

											return poll.creator(function (poll, creator) {
												let pollElement = pages.elements.createPollElement(poll.poll, function () {
													if (pages.parsers.attachmentsValid(attachments, write_text)) {
														return wallInput.showInputButton();
													} else {
														return wallInput.hideInputButton();
													}
												});

												attachments.appendChild(pollElement);
												if (pages.parsers.attachmentsValid(attachments, write_text)) {
													return wallInput.showInputButton();
												} else {
													return wallInput.hideInputButton();
												}
											}).show();
										}
									]
								]).open(event, true);
							},
							publishSettingsCallback: function (event, settingsItem) {

							},
							publishCallback: function (event, publishButton) {
								let attachmentsString = pages.parsers.attachmentsString(attachments);

								if (String(document.getElementById("write_text").value).isEmpty() && attachmentsString.isEmpty()) return false;

								publishButton.setLoading(true);
								return posts.actions.create(ui.userVisited, String(document.getElementById("write_text").value), attachmentsString).then(function (post) {
									let element = pages.elements.post(post);

									let alertWindow = document.getElementById("alertWindow");
									if (alertWindow) alertWindow.remove();

									publishButton.setLoading(false);
									wallInput.hideInputButton();
									document.getElementById("write_text").value = '';
									document.getElementById("write_text").innerHTML = '';

									unt.updateTextFields();
									attachments.innerHTML = '';

									unt.Collapsible.getInstance(wallInput).close();

									news_list.style.display = '';
									return news_list.prepend(element);
								}).catch(function (err) {
									publishButton.setLoading(false);

									let errorString = settings.lang.getValue("upload_error");
									return unt.toast({html: errorString});
								});
							},
							inputTextCallback: function (event, textareaInput) {
								if (pages.parsers.attachmentsValid(attachments, write_text)) {
									return wallInput.showInputButton();
								} else {
									return wallInput.hideInputButton();
								}
							}
						});
		let loader = pages.elements.getLoader();

		menuBody.innerHTML = "";
		loader.style.marginTop = "20px";

		menuBody.appendChild(wallInput);
		menuBody.appendChild(loader);
		unt.updateTextFields();

		let postsDiv = document.createElement('div');
		postsDiv.id = 'news_list';
		menuBody.appendChild(postsDiv);

		postsDiv.style.display = 'none';
		return posts.getAll(0, 1, 1)
			.then(function (posts) {
				let currentOffset = 0;

				if (posts.length > 0) {
					for (let i = 0; i < posts.length; i++) {
						let postElement = pages.elements.post(posts[i]);

						postsDiv.appendChild(postElement);
					}

					loader.hide();
					ui.bindItems();

					return postsDiv.style.display = '';
				} else {
					if (currentOffset === 0) {
						let windowOfAlert = pages.elements.alertWindow(unt.Icon.CLEAR, settings.lang.getValue("no_posts"), settings.lang.getValue("no_posts_t"));
						loader.hide();

						return menuBody.appendChild(windowOfAlert);
					} else {

					}
				}
			})
			.catch(function (error) {
				let uploadError = pages.elements.uploadError();

				postsDiv.style.display = 'none';
				menuBody.appendChild(uploadError);

				loader.hide();
				return ui.bindItems();
			})
	},
	restore: function () {
		if (!settings.users.current) pages.unauth.authPage();
		else return pages.news();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("restore_account") : null;
		document.title = 'yunNet - ' + settings.lang.getValue("restore_account");
		
		let workElement = document.createElement('div');
		workElement.classList.add('carousel');
		workElement.classList.add('carousel-slider');
		workElement.classList.add('center');

		document.getElementById('menu').prepend(workElement);

		function recreateElement () {
			let element = document.createElement('a');
			element.classList.add('carousel-item');
			element.classList.add(['red', 'blue', 'green'][getRandomInt(0, 2)]);

			return workElement.appendChild(element);
		}

		let currentSlider = recreateElement();
		unt.Carousel.init(workElement, {
			fullWidth: true
		})

		workElement.style.position = 'fixed';
		workElement.style.height = '100%';
		workElement.style.zIndex = 1000;

		let innerContainer = document.createElement('div');
		currentSlider.appendChild(innerContainer);
		innerContainer.classList.add('container');

		let welcomeDiv = document.createElement('div');
		innerContainer.appendChild(welcomeDiv);

		let buttonsDiv = document.createElement('div');
		innerContainer.appendChild(buttonsDiv);
		buttonsDiv.classList.add('right');

		let exitButton = document.createElement('a');
		exitButton.innerHTML = unt.Icon.CLOSE;
		buttonsDiv.appendChild(exitButton);

		let continueButton = document.createElement('a');
		continueButton.innerHTML = unt.Icon.ARROW_FWD;
		buttonsDiv.appendChild(continueButton);

		exitButton.getElementsByTagName('svg')[0].style.fill = 'white';
		continueButton.getElementsByTagName('svg')[0].style.fill = 'white';

		continueButton.getElementsByTagName('svg')[0].style.marginTop = '28%';
		exitButton.getElementsByTagName('svg')[0].style.marginTop = '28%';

		continueButton.classList = exitButton.classList = ['btn-floating btn-large waves-effect waves-light'];
		continueButton.style.marginLeft = exitButton.style.marginLeft = '15px';

		currentSlider.style.width = currentSlider.style.height = '100%';
		exitButton.onclick = function (event) {
			return pages.elements.confirm('', (settings.lang.getValue("confirm_exit")), function (response) {
				if (response) return account.restore.closeSession().then(function () {
					return ui.go('https://' + window.location.host + '/');
				});
			});
		}

		welcomeDiv.classList.add('white-text');
		return account.restore.getCurrentStage().then(function (stage) {
			let currentFunction = arguments.callee;

			let instance = unt.Carousel.getInstance(workElement);
			if (instance) instance.destroy();

			let url = (new URLParser(window.location.href)).parse();
			if (url.query && stage < 3) {
				if (url.query.length > 32) {
					welcomeDiv.innerHTML = '';

					let loader = pages.elements.getLoader();
					loader.style.padding = '50px';
					loader.getElementsByTagName('path')[0].style.fill = 'white';

					continueButton.style.display = 'none';
					welcomeDiv.appendChild(loader);
					return account.restore.validateQuery(url.query).then(function (stage) {
						if (stage === null) {
							return welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("error")) + '<h2><p>' + (settings.lang.getValue('link_is_bad')) + '</p>');
						}

						return currentFunction(stage);
					});
				}
			}

			if (stage === 0) {
				welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("welcome_message")) + '<h2><p>' + (settings.lang.getValue('validate_you')) + '</p>');
			
				let emailInput = document.createElement('div');
				emailInput.classList.add('input-field');

				let email = document.createElement('input');
				emailInput.appendChild(email);
				email.type = 'email';
				email.classList.add('validate');
				email.placeholder = settings.lang.getValue('email');
				email.style.color = 'white';

				welcomeDiv.appendChild(emailInput);
				continueButton.onclick = function (event) {
					if (email.value.isEmpty()) return email.classList.add('wrong');
					else email.classList.remove('wrong');

					let data = new FormData();
					email.setAttribute('disabled', 'true');

					data.append('email', email.value);
					return account.restore.sendSessionData(data).then(function (response) {
						return currentFunction(response);
					}).catch(function (error) {
						email.removeAttribute('disabled');

						if (error.errorCode === 2) email.classList.add('wrong');
						if (error.errorText) unt.toast({html: error.errorText});
					})
				}
			}
			if (stage === 1) {
				welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("lets_continue")) + '<h2><p>' + (settings.lang.getValue('need_some_more')) + '</p>');

				let lastNameInput = document.createElement('div');
				lastNameInput.classList.add('input-field');

				let last_name = document.createElement('input');
				lastNameInput.appendChild(last_name);
				last_name.type = 'text';
				last_name.classList.add('validate');
				last_name.placeholder = settings.lang.getValue('last_name');
				last_name.style.color = 'white';

				welcomeDiv.appendChild(lastNameInput);
				continueButton.onclick = function (event) {
					if (last_name.value.isEmpty() || last_name.value.match(/[^a-zA-Zа-яА-ЯёЁ'-]/ui)) return last_name.classList.add('wrong');
					else last_name.classList.remove('wrong');

					let data = new FormData();
					last_name.setAttribute('disabled', 'true');

					data.append('last_name', last_name.value);
					return account.restore.sendSessionData(data).then(function (response) {
						return currentFunction(response);
					}).catch(function (error) {
						last_name.removeAttribute('disabled');

						if (error.errorCode === 1 || error.errorCode === 2) last_name.classList.add('wrong');
						if (error.errorText) return unt.toast({html: error.errorText});
					})
				}
			}
			if (stage === 2) {
				welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("restore_account")) + '<h2><p>' + (settings.lang.getValue('i_sent_email')) + '</p>');

				continueButton.style.display = 'none';
			}
			if (stage === 3) {
				welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("good_work")) + '<h2><p>' + (settings.lang.getValue('glad_to_see_you')) + '</p>');

				let passwordInput = document.createElement('div');
				passwordInput.classList.add('input-field');

				let password = document.createElement('input');
				passwordInput.appendChild(password);
				password.type = 'password';
				password.classList.add('validate');
				password.placeholder = settings.lang.getValue('password');
				password.style.color = 'white';

				let repeatPasswordInput = document.createElement('div');
				repeatPasswordInput.classList.add('input-field');

				let repeatPassword = document.createElement('input');
				repeatPasswordInput.appendChild(repeatPassword);
				repeatPassword.type = 'password';
				repeatPassword.classList.add('validate');
				repeatPassword.placeholder = settings.lang.getValue('password2');
				repeatPassword.style.color = 'white';

				welcomeDiv.appendChild(passwordInput);
				welcomeDiv.appendChild(repeatPasswordInput);

				continueButton.style.display = '';
				exitButton.style.display = 'none';

				continueButton.onclick = function (event) {
					if (password.value.isEmpty()) return password.classList.add('wrong');
					else password.classList.remove('wrong');
					if (repeatPassword.value.isEmpty()) return repeatPassword.classList.add('wrong');
					else repeatPassword.classList.remove('wrong');

					if (password.value !== repeatPassword.value) {
						password.classList.add('wrong');
						repeatPassword.classList.add('wrong');
					} else {
						password.classList.remove('wrong');
						repeatPassword.classList.remove('wrong');
					}

					let data = new FormData();

					password.setAttribute('disabled', 'true');
					repeatPassword.setAttribute('disabled', 'true');

					data.append('password', password.value);
					data.append('repeat_password', repeatPassword.value);
					return account.restore.sendSessionData(data).then(function (response) {
						return currentFunction(response);
					}).catch(function (error) {
						password.removeAttribute('disabled');
						repeatPassword.removeAttribute('disabled');

						if (error.errorCode === 5) password.classList.add('wrong');
						if (error.errorCode === 6) repeatPassword.classList.add('wrong');

						if (error.errorText) unt.toast({html: error.errorText});
						if (error.requestedStage)
							return currentFunction(error.requestedStage);

						if (error.errorCode === 10) unt.toast({html: 'Unable to restore: Internal server error.'});
					})
				}
			}
			if (stage === 4) {
				welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("thanks")) + '<h2>');
				exitButton.style.display = 'none';
				continueButton.style.display = '';

				return continueButton.onclick = function (event) {
					let data = new FormData();
					return account.restore.sendSessionData(data).then(function (response) {
						if (response !== 5) return;
						unt.toast({html: 'Loading...'});

						continueButton.style.display = 'none';
						return setTimeout(function () {
							return window.location.href = '/';
						}, 1000);
					})
				}
			}
		});
	},
	register: function () {
		if (window.opener) window.addEventListener('beforeunload', function () {
			return window.opener.postMessage('cancelled', 'https://auth.yunnet.ru/');
		});

		if (!settings.users.current) pages.unauth.authPage();
		else return pages.news();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("regstart") : null;
		document.title = 'yunNet. - ' + settings.lang.getValue("regstart");

		let workElement = document.createElement('div');
		workElement.classList.add('carousel');
		workElement.classList.add('carousel-slider');
		workElement.classList.add('center');
		workElement.classList.add('white-text');

		workElement.id = 'regInfo';
		document.getElementById('menu').prepend(workElement);

		function recreateElement () {
			let element = document.createElement('div');
			element.classList.add('carousel-item');
			element.classList.add(['red', 'blue', 'green'][getRandomInt(0, 2)]);

			return workElement.appendChild(element) && element;
		}

		let currentSlider = recreateElement();
		unt.Carousel.init(regInfo, {
			fullWidth: true
		})

		workElement.style.position = 'fixed';
		workElement.style.height = '100%';
		workElement.style.zIndex = 1000;

		let innerContainer = document.createElement('div');
		currentSlider.appendChild(innerContainer);
		innerContainer.classList.add('container');

		let welcomeDiv = document.createElement('div');
		innerContainer.appendChild(welcomeDiv);

		let buttonsDiv = document.createElement('div');
		innerContainer.appendChild(buttonsDiv);
		buttonsDiv.classList.add('right');

		let exitButton = document.createElement('a');
		exitButton.innerHTML = unt.Icon.CLOSE;
		buttonsDiv.appendChild(exitButton);

		let continueButton = document.createElement('a');
		continueButton.innerHTML = unt.Icon.ARROW_FWD;
		buttonsDiv.appendChild(continueButton);

		exitButton.getElementsByTagName('svg')[0].style.fill = 'white';
		continueButton.getElementsByTagName('svg')[0].style.fill = 'white';

		continueButton.getElementsByTagName('svg')[0].style.marginTop = '28%';
		exitButton.getElementsByTagName('svg')[0].style.marginTop = '28%';

		continueButton.classList = exitButton.classList = ['btn-floating btn-large waves-effect waves-light'];
		continueButton.style.marginLeft = exitButton.style.marginLeft = '15px';

		currentSlider.style.width = currentSlider.style.height = '100%';
		exitButton.onclick = function (event) {
			return pages.elements.confirm('', (settings.lang.getValue("confirm_exit")), function (response) {
				if (response) return account.register.closeSession().then(function () {
					if (window.opener) {
						return window.opener.postMessage('cancelled', 'https://auth.yunnet.ru/');
					} else {
						return ui.go('https://' + window.location.host + '/');
					}
				});
			});
		}

		return account.register.getCurrentStage().then(function (stage) {
			let currentFunction = arguments.callee;

			let instance = unt.Carousel.getInstance(regInfo);
			if (instance) instance.destroy();

			if (stage === -1) {
				welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("error_closed_register")) + '<h2><p>' + (settings.lang.getValue('error_closed_register_message')) + '</p>');
			
				continueButton.style.display = 'none';
			}
			if (stage === 0) {
				welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("welcome_message")) + '<h2><p>' + (settings.lang.getValue('thanks_for_reg')) + '</p>');
			
				let firstNameInput = document.createElement('div');
				firstNameInput.classList.add('input-field');

				let first_name = document.createElement('input');
				firstNameInput.appendChild(first_name);
				first_name.type = 'text';
				first_name.classList.add('validate');
				first_name.placeholder = settings.lang.getValue('first_name');
				first_name.style.color = 'white';

				let lastNameInput = document.createElement('div');
				lastNameInput.classList.add('input-field');

				let last_name = document.createElement('input');
				lastNameInput.appendChild(last_name);
				last_name.type = 'text';
				last_name.classList.add('validate');
				last_name.placeholder = settings.lang.getValue('last_name');
				last_name.style.color = 'white';

				welcomeDiv.appendChild(firstNameInput);
				welcomeDiv.appendChild(lastNameInput);
				continueButton.onclick = function (event) {
					if (first_name.value.isEmpty() || first_name.value.match(/[^a-zA-Zа-яА-ЯёЁ'-]/ui)) return first_name.classList.add('wrong');
					else first_name.classList.remove('wrong');
					if (last_name.value.isEmpty() || last_name.value.match(/[^a-zA-Zа-яА-ЯёЁ'-]/ui)) return last_name.classList.add('wrong');
					else last_name.classList.remove('wrong');

					let data = new FormData();
					first_name.setAttribute('disabled', 'true');
					last_name.setAttribute('disabled', 'true');

					data.append('first_name', first_name.value);
					data.append('last_name', last_name.value);
					return account.register.sendSessionData(data).then(function (response) {
						settings.users.temp = {};

						settings.users.temp.first_name = first_name.value;
						settings.users.temp.last_name = last_name.value;

						return currentFunction(response);
					}).catch(function (error) {
						first_name.removeAttribute('disabled');
						last_name.removeAttribute('disabled');

						if (error.errorCode === 0) first_name.classList.add('wrong');
						if (error.errorCode === 1) last_name.classList.add('wrong');
						if (error.errorText) unt.toast({html: error.errorText});

						return unt.toast({html: error.errorMessage});
					})
				}
			}
			if (stage === 1) {
				account.register.restoreData().then(function (data) {
					settings.users.temp = data;

					welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("lets_continue")) + '<h2><p>' + htmlspecialchars((settings.lang.getValue('lets_welcome')).replace('%first_name%', settings.users.temp.first_name)) + '</p>');
					let emailInput = document.createElement('div');
					emailInput.classList.add('input-field');

					let email = document.createElement('input');
					emailInput.appendChild(email);
					email.type = 'email';
					email.classList.add('validate');
					email.placeholder = settings.lang.getValue('email');
					email.style.color = 'white';

					welcomeDiv.appendChild(emailInput);
					continueButton.onclick = function (event) {
						if (email.value.isEmpty()) return email.classList.add('wrong');
						else email.classList.remove('wrong');

						let data = new FormData();
						email.setAttribute('disabled', 'true');

						data.append('email', email.value);
						return account.register.sendSessionData(data).then(function (response) {
							return currentFunction(response);
						}).catch(function (error) {
							email.removeAttribute('disabled');

							if (error.errorCode === 2) email.classList.add('wrong');
							if (error.errorText) unt.toast({html: error.errorText});
						})
					}
				});
			}
			if (stage === 2) {
				account.register.restoreData().then(function (data) {
					settings.users.temp = data;

					welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("email_activation")) + '<h2><p>' + htmlspecialchars((settings.lang.getValue('please_activate')).replace('%first_name%', settings.users.temp.first_name)) + '</p>');
					let codeInput = document.createElement('div');
					codeInput.classList.add('input-field');

					let code = document.createElement('input');
					codeInput.appendChild(code);
					code.type = 'number';
					code.classList.add('validate');
					code.placeholder = settings.lang.getValue('code');
					code.style.color = 'white';

					welcomeDiv.appendChild(codeInput);
					continueButton.onclick = function (event) {
						if (code.value.isEmpty()) return code.classList.add('wrong');
						else code.classList.remove('wrong');

						let data = new FormData();
						code.setAttribute('disabled', 'true');

						data.append('email_code', code.value);
						return account.register.sendSessionData(data).then(function (response) {
							return currentFunction(response);
						}).catch(function (error) {
							code.removeAttribute('disabled');

							if (error.errorCode === 3) code.classList.add('wrong');
							if (error.errorText) unt.toast({html: error.errorText});
						})
					}
				})
			}
			if (stage === 3) {
				account.register.restoreData().then(function (data) {
					settings.users.temp = data;

					welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("good_work")) + '<h2><p>' + htmlspecialchars((settings.lang.getValue('finish_data')).replace('%first_name%', settings.users.temp.first_name)) + '</p>');

					exitButton.style.display = 'none';
					continueButton.style.display = '';

					let passwordInput = document.createElement('div');
					passwordInput.classList.add('input-field');

					let password = document.createElement('input');
					passwordInput.appendChild(password);
					password.type = 'password';
					password.classList.add('validate');
					password.placeholder = settings.lang.getValue('password');
					password.style.color = 'white';

					let repeatPasswordInput = document.createElement('div');
					repeatPasswordInput.classList.add('input-field');

					let repeatPassword = document.createElement('input');
					repeatPasswordInput.appendChild(repeatPassword);
					repeatPassword.type = 'password';
					repeatPassword.classList.add('validate');
					repeatPassword.placeholder = settings.lang.getValue('password2');
					repeatPassword.style.color = 'white';

					welcomeDiv.appendChild(passwordInput);
					welcomeDiv.appendChild(repeatPasswordInput);
					continueButton.onclick = function (event) {
						if (password.value.isEmpty()) return password.classList.add('wrong');
						else password.classList.remove('wrong');
						if (repeatPassword.value.isEmpty()) return repeatPassword.classList.add('wrong');
						else repeatPassword.classList.remove('wrong');

						if (password.value !== repeatPassword.value) {
							password.classList.add('wrong');
							repeatPassword.classList.add('wrong');
						} else {
							password.classList.remove('wrong');
							repeatPassword.classList.remove('wrong');
						}

						let data = new FormData();

						password.setAttribute('disabled', 'true');
						repeatPassword.setAttribute('disabled', 'true');

						data.append('password', password.value);
						data.append('repeat_password', repeatPassword.value);
						return account.register.sendSessionData(data).then(function (response) {
							return currentFunction(response);
						}).catch(function (error) {
							password.removeAttribute('disabled');
							repeatPassword.removeAttribute('disabled');

							if (error.errorCode === 5) password.classList.add('wrong');
							if (error.errorCode === 6) repeatPassword.classList.add('wrong');

							if (error.errorText) unt.toast({html: error.errorText});
							if (error.requestedStage)
								return currentFunction(error.requestedStage);

							if (error.errorCode === 10) unt.toast({html: 'Unable to register: Internal server error.'});
						})
					}
				})
			}
			if (stage === 4) {
				account.register.restoreData().then(function (data) {
					settings.users.temp = data;

					welcomeDiv.innerHTML = ('<h2>' + (settings.lang.getValue("thanks")) + '<h2>');

					exitButton.style.display = 'none';
					continueButton.style.display = '';

					return continueButton.onclick = function (event) {
						let data = new FormData();
						return account.register.sendSessionData(data).then(function (response) {
							if (response !== 5) return;

							unt.toast({html: 'Loading...'});

							continueButton.style.display = 'none';
							return setTimeout(function () {
								if (window.opener)
									return window.opener.postMessage('success', 'https://auth.yunnet.ru/');
								else
									return window.location.href = '/';
							}, 1000);
						})
					}
				});
			}
		});
	},
	edit: function () {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("edit") : null;

		history.replaceState(null, document.title, '/edit?section=main');
		let menuBody = pages.elements.menuBody().clear();
		menuBody.innerHTML = "";

		let rightMenu = pages.elements.buildRightMenu().append();

		let url = window.location.host.match(/localhost/) ? 'http://localhost' : 'https://yunnet.ru';

		let mainItem = rightMenu.addItem(settings.lang.getValue("main"), function () {
			return ui.go(url + '/edit?section=main');
		});
		mainItem.select();

		let mainPageElement = document.createElement('div');
		mainPageElement.classList.add('card');

		let mainData = document.createElement('div');
		mainPageElement.appendChild(mainData);
		mainData.classList.add('full_section');
		mainData.classList.add('valign-wrapper');

		let photoDiv = document.createElement('div');
		mainData.appendChild(photoDiv);

		let userImg = document.createElement('img');
		photoDiv.appendChild(userImg);
		userImg.classList.add('circle');
		userImg.width = userImg.height = 64;
		userImg.src = settings.users.current.photo_url;

		let inputsDiv = document.createElement('div');
		mainData.appendChild(inputsDiv);
		inputsDiv.style = 'margin-left: 20px; width: 100%';

		let inpForm = document.createElement('form');
		inputsDiv.appendChild(inpForm);

		let firstNameInput = document.createElement('div');
		firstNameInput.classList.add('input-field');

		let first_name = document.createElement('input');
		firstNameInput.appendChild(first_name);
		first_name.type = 'text';
		first_name.classList.add('validate');
		
		let lastNameInput = document.createElement('div');
		lastNameInput.classList.add('input-field');

		let last_name = document.createElement('input');
		lastNameInput.appendChild(last_name);
		last_name.type = 'text';
		last_name.classList.add('validate');
		
		inpForm.appendChild(firstNameInput);
		inpForm.appendChild(lastNameInput);

		first_name.value = settings.users.current.first_name;
		last_name.value = settings.users.current.last_name;
		menuBody.appendChild(mainPageElement);

		/////////////////////////////////////////////

		let infoCard = document.createElement('div');
		infoCard.classList.add('card');
		infoCard.classList.add('full_section');

		let infoText = document.createElement('div');
		infoText.style.paddingBottom = '5px';
		infoText.innerText = settings.lang.getValue('gender');
		infoCard.appendChild(infoText);

		let genderForm = document.createElement('form');
		genderForm.classList.add('valign-wrapper');

		infoCard.appendChild(genderForm);

		let pMale = document.createElement('p');
		genderForm.appendChild(pMale);
		pMale.style.marginRight = '20px';

		let pFemale = document.createElement('p');
		genderForm.appendChild(pFemale);

		let labelMale = document.createElement('label');
		let labelFemale = document.createElement('label');

		pMale.appendChild(labelMale);
		pFemale.appendChild(labelFemale);

		let inputMale = document.createElement('input');
		let inputFemale = document.createElement('input');

		inputMale.classList.add('with-gap');
		inputFemale.classList.add('with-gap');

		labelMale.appendChild(inputMale);
		labelFemale.appendChild(inputFemale);

		inputMale.name = inputFemale.name = 'gender';
		inputMale.type = inputFemale.type = 'radio';

		let spanMale = document.createElement('span');
		let spanFemale = document.createElement('span');

		labelMale.appendChild(spanMale);
		labelFemale.appendChild(spanFemale);

		spanMale.innerText = settings.lang.getValue('gen_sel_1');
		spanFemale.innerText = settings.lang.getValue('gen_sel_2');

		menuBody.appendChild(infoCard);
		if (settings.users.current.gender === 1)
			inputMale.checked = true;
		if (settings.users.current.gender === 2)
			inputFemale.checked = true;

		inputMale.oninput = function () {
			inputMale.disabled = true;
			inputFemale.disabled = true;

			inputMale.checked = !inputMale.checked
			inputFemale.checked = !inputFemale.checked

			return settings.users.setGender(1).then(function (response) {
				inputMale.disabled = false;
				inputFemale.disabled = false;

				if (response.unauth) return settings.reLogin();
				if (response.error) return unt.toast({html: settings.lang.getValue('upload_error')})

				return inputMale.checked = true;
			}).catch(function (err) {
				return unt.toast({html: settings.lang.getValue('upload_error')})
			})
		}

		inputFemale.oninput = function () {
			inputMale.disabled = true;
			inputFemale.disabled = true;

			inputMale.checked = !inputMale.checked
			inputFemale.checked = !inputFemale.checked

			return settings.users.setGender(2).then(function (response) {
				inputMale.disabled = false;
				inputFemale.disabled = false;

				if (response.unauth) return settings.reLogin();
				if (response.error) return unt.toast({html: settings.lang.getValue('upload_error')})

				return inputFemale.checked = true;
			})
		}

		////////////////////////////////////////////

		let infoPageElement = document.createElement('div');
		menuBody.appendChild(infoPageElement);
		infoPageElement.classList.add('card');

		let infoData = document.createElement('div');
		infoPageElement.appendChild(infoData);
		infoData.classList.add('full_section');
		infoData.classList.add('valign-wrapper');

		let svgDiv = document.createElement('div');
		infoData.appendChild(svgDiv);

		let screenNameDiv = document.createElement('div');
		infoData.appendChild(screenNameDiv);
		let screenNameInput = document.createElement('div');
		screenNameInput.classList.add('input-field');

		let screen_name = document.createElement('input');
		screenNameInput.appendChild(screen_name);
		screen_name.type = 'text';
		screen_name.classList.add('validate');
		
		svgDiv.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M0 0h24v24H0V0z" fill="none"/><path d="M18 1.01L8 1c-1.1 0-2 .9-2 2v3h2V5h10v14H8v-1H6v3c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM10 15h2V8H5v2h3.59L3 15.59 4.41 17 10 11.41z"/></svg>';
		svgDiv.style.marginRight = '15px';
		screenNameDiv.style.width = '100%';
		screen_name.value = settings.users.current.screen_name || ("id" + settings.users.current.user_id);

		let labelForFirstName = document.createElement('label');
		let labelForLastName = document.createElement('label');
		let labelForScreenName = document.createElement('label');

		firstNameInput.appendChild(labelForFirstName);
		lastNameInput.appendChild(labelForLastName);
		screenNameInput.appendChild(labelForScreenName);

		first_name.id = "first_name";
		last_name.id = "last_name";
		screen_name.id = "screen_name";

		first_name.setAttribute('maxlength', 64);
		last_name.setAttribute('maxlength', 64);
		screen_name.setAttribute('maxlength', 32);

		labelForFirstName.setAttribute('for', first_name.id);
		labelForLastName.setAttribute('for', last_name.id);
		labelForScreenName.setAttribute('for', screen_name.id);

		labelForFirstName.innerHTML = settings.lang.getValue("first_name");
		labelForLastName.innerHTML = settings.lang.getValue("last_name");
		labelForScreenName.innerHTML = settings.lang.getValue("screen_name");

		labelForFirstName.classList.add('active');
		labelForLastName.classList.add('active');
		labelForScreenName.classList.add('active');
		screenNameDiv.appendChild(screenNameInput);	

		let saveMainButtonDiv = document.createElement('div');
		mainPageElement.appendChild(saveMainButtonDiv);

		let saveMainButton = document.createElement('a');
		saveMainButton.innerHTML = unt.Icon.SAVE;
		saveMainButtonDiv.appendChild(saveMainButton);
		saveMainButton.getElementsByTagName('svg')[0].style.fill = 'white';

		saveMainButton.getElementsByTagName('svg')[0].style.marginTop = '28%';
		saveMainButton.classList = ['btn-floating btn-large waves-effect waves-light'];
		saveMainButton.style.marginLeft;

		saveMainButtonDiv.style.width = '100%';
		saveMainButtonDiv.style.textAlign = '-webkit-right';
		saveMainButtonDiv.style.padding = '0 20px 20px';

		let loader = pages.elements.getLoader();
		saveMainButtonDiv.appendChild(loader);
		loader.style.display = 'none';

		let saveInfoButtonDiv = document.createElement('div');
		infoPageElement.appendChild(saveInfoButtonDiv);

		let saveInfoButton = document.createElement('a');
		saveInfoButton.innerHTML = unt.Icon.SAVE;
		saveInfoButtonDiv.appendChild(saveInfoButton);
		saveInfoButton.getElementsByTagName('svg')[0].style.fill = 'white';

		saveInfoButton.getElementsByTagName('svg')[0].style.marginTop = '28%';
		saveInfoButton.classList = ['btn-floating btn-large waves-effect waves-light'];
		saveInfoButton.style.marginLeft;

		saveInfoButtonDiv.style.width = '100%';
		saveInfoButtonDiv.style.textAlign = '-webkit-right';
		saveInfoButtonDiv.style.padding = '0 20px 20px';

		let loaderInfo = pages.elements.getLoader();
		saveInfoButtonDiv.appendChild(loaderInfo);
		loaderInfo.style.display = 'none';

		loader.classList.remove('center');
		loaderInfo.classList.remove('center');

		saveMainButton.onclick = function (event) {
			if (first_name.value.isEmpty() || first_name.value.match(/[^a-zA-Zа-яА-ЯёЁ'-]/ui)) return first_name.classList.add('wrong');
			else first_name.classList.remove('wrong');
			if (last_name.value.isEmpty() || last_name.value.match(/[^a-zA-Zа-яА-ЯёЁ'-]/ui)) return last_name.classList.add('wrong');
			else last_name.classList.remove('wrong');

			saveMainButton.style.display = 'none';
			loader.style.display = '';

			return settings.users.edit({
				first_name: first_name.value,
				last_name: last_name.value
			}).then(function (response) {
				saveMainButton.style.display = '';
				loader.style.display = 'none';

				settings.users.current.first_name = first_name.value;
				settings.users.current.last_name = last_name.value;

				settings.users.profiles[settings.users.current.user_id].first_name = first_name.value;
				settings.users.profiles[settings.users.current.user_id].last_name = last_name.value;
			}).catch(function (error) {
				saveMainButton.style.display = '';
				loader.style.display = 'none';

				return unt.toast({html: (error.errorMessage ? error.errorMessage : (settings.lang.getValue("upload_error")))});
			});
		}

		saveInfoButton.onclick = function (event) {
			if (!screen_name.value.isEmpty())
				if (!screen_name.value.match(/^[a-z]{1}[a-z_\d\s]*[a-z_\s\d]{1}$/i)) return screen_name.classList.add('wrong');
				else screen_name.classList.remove('wrong');

			if (screen_name.value === settings.users.current.screen_name) return;

			saveInfoButton.style.display = 'none';
			loaderInfo.style.display = '';
			return settings.users.edit({
				screen_name: screen_name.value
			}).then(function (response) {
				saveInfoButton.style.display = '';
				loaderInfo.style.display = 'none';

				settings.users.current.screen_name = screen_name.value;
				settings.users.profiles[settings.users.current.user_id].screen_name = screen_name.value;
			}).catch(function (error) {
				saveInfoButton.style.display = '';
				loaderInfo.style.display = 'none';

				return unt.toast({html: (error.errorMessage ? error.errorMessage : (settings.lang.getValue("upload_error")))});
			});
		}

		userImg.onclick = function (event) {
			let uploader = pages.elements.fileUploader({
				onFileSelected: function (event, files, uploader) {
					uploader.setLoading(true);

					return uploads
							.getURL()
							.then(function (url) {
								return uploads
									.upload(url, files[0], function (event) {
										return codes.callbacks.uploadResolve(event, uploader);
									})
									.then(function (attachment) {
										let attachmentCredentials = 'photo' + attachment.photo.owner_id + "_" + attachment.photo.id + '_' + attachment.photo.access_key;
										return settings.users.edit({
											photo: attachmentCredentials
										}).then(function (response) {
											uploader.setLoading(false);
											userImg.src = response.photo.url.main;

											settings.users.current.photo_url = userImg.src;
											settings.users.profiles[settings.users.current.user_id].photo_url = userImg.src;

											return uploader.close();
										}).catch(function (error) {
											uploader.setLoading(false);

											return unt.toast({html: (settings.lang.getValue("upload_error"))});
										})
									})
									.catch(function (err) {
										let errorString = settings.lang.getValue("upload_error");

										unt.toast({html: errorString});
										return uploader.setLoading(false);
									});
							})
							.catch(function (err) {
								let errorString = settings.lang.getValue("upload_error");

								unt.toast({html: errorString});
								return uploader.setLoading(false);
							});
				},
				afterClose: function () {}
			});

			uploader.addFooterItem((settings.lang.getValue("delete_a_photo")), function (event, item) {
				item.setLoading(true);

				return settings.users.edit({
					photo: ''
				}).then(function (response) {
					item.setLoading(false);
					userImg.src = 'https://dev.yunnet.ru/images/default.png';

					settings.users.current.photo_url = userImg.src;
					settings.users.profiles[settings.users.current.user_id].photo_url = userImg.src;

					return uploader.close();
				}).catch(function (error) {
					uploader.setLoading(false);

					return unt.toast({html: (settings.lang.getValue("upload_error"))});
				})
			});

			return uploader.open();
		}
	},
	notifications: function () {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("notifications") : null;

		let menuBody = pages.elements.menuBody().clear();
		menuBody.innerHTML = "";

		let loader = pages.elements.getLoader();
		loader.style.marginTop = '10px';

		menuBody.appendChild(loader);

		let currentPage = 1;
		let count = 30;

		let notesDiv = document.createElement('div');
		notesDiv.classList.add('card');
		notesDiv.classList.add('collection');

		notesDiv.style.display = 'none';
		notesDiv.id = 'notifications_list';

		menuBody.appendChild(notesDiv);

		ui.bindItems();
		return notifications.get((currentPage * count) - count, count).then(function (notifications) {
			loader.hide();

			if (notifications.length <= 0) return menuBody.appendChild(pages.elements.alertWindow(unt.Icon.NO_NOTES, settings.lang.getValue('no_notes'), settings.lang.getValue('no_notes_text')));
			notifications.forEach(function (notification) {
				let element = pages.elements.notification(notification);

				return notesDiv.appendChild(element);
			});

			menuBody.appendChild(pages.elements.createFAB(unt.Icon.CLOSE, function (event) {
				console.log(event);
			}));

			notesDiv.style.display = '';

			return ui.bindItems();
		}).catch(function (err) {
			let uploadError = pages.elements.uploadError();
			loader.hide();

			return menuBody.appendChild(uploadError);
		});
	},
	friends: function (internalData) {
		if (!internalData) internalData = {};

		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("friends") : null;

		let menuBody = pages.elements.menuBody().clear();
		menuBody.innerHTML = "";

		let subAction = internalData ? (internalData.subaction ? internalData.subaction : null) : null;
		if (subAction === "write" && ui.canBack) {
			let settingsData = settings.get();
			if (settingsData) {
				if (settingsData.theming.backButton) {
					menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('select_to_write')), function (event) {
						return ui.go(null, true);
					});
				}
			}
		}
		if (subAction === "invite_user" && ui.canBack) {
			let settingsData = settings.get();
			if (settingsData) {
				if (settingsData.theming.backButton) {
					menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue("select_to_invite")), function (event) {
						return ui.go(null, true);
					});
				}
			}
		}

		let rightMenu = pages.elements.buildRightMenu().append();
		let selectedUserId = settings.users.current.user_id;
		let selectedAction = 'friends';

		if (internalData) {
			if (internalData.user_id) selectedUserId = internalData.user_id;
			if (internalData.action) selectedAction = internalData.action;
		}

		let friendsItem = rightMenu.addItem(settings.lang.getValue("friends"), function () {
			let internalDataNew = {
				action: 'friends',
				user_id: selectedUserId
			}

			if (subAction) internalDataNew.subaction = subAction;

			return ui.go(window.location.href, true, true, true, false, internalDataNew);
		});

		let subscribersItem = null;
		let outcomingItem = null;

		if (subAction !== "invite_user") {
			subscribersItem = rightMenu.addItem(settings.lang.getValue("subs"), function () {
				let internalDataNew = {
					action: 'subscribers',
					user_id: selectedUserId
				}

				if (subAction) internalDataNew.subaction = subAction;

				return ui.go(window.location.href, true, true, true, false, internalDataNew);
			});
			outcomingItem = null;

			if (selectedUserId === settings.users.current.user_id)
				outcomingItem = rightMenu.addItem(settings.lang.getValue("outcoming_requests"), function () {
					let internalDataNew = {
						action: 'outcoming',
						user_id: settings.users.current.user_id
					}

					if (subAction) internalDataNew.subaction = subAction;

					return ui.go(window.location.href, true, true, true, false, internalDataNew);
				});
		}

		if (selectedAction === "friends") friendsItem.select();
		if (selectedAction === "subscribers") subscribersItem.select();
		if (selectedAction === "outcoming") outcomingItem.select();

		let friendsBody = document.createElement('div');
		menuBody.appendChild(friendsBody);

		if (selectedAction === 'friends') {
			let tabsItem = pages.elements.createTabs([
				[settings.lang.getValue('friends'), function () {
					internalData.subFriends = 'friends';

					return ui.go(window.location.href, false, false, false, true, internalData);
				}], [settings.lang.getValue('friends_online'), function () {
					internalData.subFriends = 'online';

					return ui.go(window.location.href, false, false, false, true, internalData);
				}], [settings.lang.getValue('search_text'), function () {
					internalData.subFriends = 'search';

					return ui.go(window.location.href, false, false, false, true, internalData);
				}]
			]);

			friendsBody.insertAdjacentElement('beforebegin', tabsItem);
		}

		let loader = pages.elements.getLoader(true);
		loader.style.marginTop = '10px';
		menuBody.appendChild(loader);

		friendsBody.id = selectedAction;
		friendsBody.style.display = 'none';

		friendsBody.classList.add('card');
		friendsBody.classList.add('collection');

		let continueButton = pages.elements.createFAB(unt.Icon.SAVE, function () {
			continueButton.classList.add('scale-out');

			return internalData.chat.addMembers(internalData.members).then(function (response) {
				return ui.go('https://' + window.location.host + '/messages?s=' + internalData.chat.peer_id);
			}).catch(function (err) {
				let code = err.errorCode;
				let errorString = settings.lang.getValue('upload_error');

				if (code === 2) errorString = settings.lang.getValue('level_error');
				if (code === 3) errorString = settings.lang.getValue('uninvitable_1');
				if (code === 4) errorString = settings.lang.getValue('uninvitable_2');

				unt.toast({html: errorString});
				return continueButton.classList.remove('scale-out');
			});
		});

		continueButton.classList.add('scale-transition');
		continueButton.classList.add('scale-out');

		menuBody.appendChild(continueButton);

		let subFriendsMode = internalData.subFriends || 'friends';
		if (subFriendsMode === 'search') {
			loader.hide();

			return subPages.searchFriends(menuBody);
		}

		return settings.users.friends.get(selectedUserId, selectedAction, 1).then(function (response) {
			if (subFriendsMode === 'online') {
				let tmpResult = [];

				response.forEach(function (friend) {
					if (!friend.online.is_online) return;

					tmpResult.push(friend);
				});

				response = tmpResult;
			}

			if (response.length > 0) {
				friendsBody.style.display = '';

				for (let i = 0; i < response.length; i++) {
					let userElement = pages.elements.userItem(response[i]);
					userElement.id = response[i].user_id || (response[i].bot_id * -1);
					let user = response[i];

					if (subFriendsMode === 'friends' || subFriendsMode === 'online') {
						if (subAction === "write") {
							userElement.href = '/messages?s=' + user.user_id;

							friendsBody.appendChild(userElement);
						} else
						if (subAction === "invite_user") {
							userElement.href = '';

							let selectionDiv = document.createElement('div');
							userElement.appendChild(selectionDiv);

							selectionDiv.classList.add('secondary-content');

							let form = document.createElement('form');
							selectionDiv.appendChild(form);

							let p = document.createElement('p');
							form.appendChild(p);

							let label = document.createElement('label');
							p.appendChild(label);

							let input = document.createElement('input');
							input.type = 'checkbox';
							label.appendChild(input);

							let span = document.createElement('span');
							label.appendChild(span);
							span.innerHTML = '';

							userElement.onclick = function (event) {
								event.preventDefault();

								return input.oninput(event);
							}

							input.oninput = function () {
								input.getAttribute('checked') === 'checked' ? input.removeAttribute('checked') : input.setAttribute('checked', 'checked');

								internalData.members.indexOf(user.user_id) === -1 ? internalData.members.push(user.user_id) : internalData.members.splice(internalData.members.indexOf(user.user_id), 1);
							
								if (internalData.members.length < 1) continueButton.classList.add('scale-out');
								else continueButton.classList.remove('scale-out');
							}

							if (internalData.members.indexOf(user.user_id) !== -1) {
								input.setAttribute('checked', 'checked');
							}

							if (user.can_invite_to_chat) {
								friendsBody.appendChild(userElement);
							}
						} else 
						if (selectedAction === "subscribers" && selectedUserId === settings.users.current.user_id) {
							let actionsDiv = document.createElement('div');
							actionsDiv.classList.add('secondary-content');
							userElement.appendChild(actionsDiv);

							userElement.onclick = function (event) {
								event.preventDefault();

								if (event.target.nodeName === "svg" || event.target.nodeName === "path")
									return;

								return ui.go('https://' + window.location.host + '/id' + user.user_id);
							}

							let containerDiv = document.createElement('div');
							containerDiv.classList.add('valign-wrapper');
							actionsDiv.appendChild(containerDiv);

							let inProcess = false;

							if (!user.friend_state.is_hidden) {
								let hideItem = document.createElement('div');
								hideItem.classList.add('hide-item');

								hideItem.innerHTML = unt.Icon.CLOSE;
								containerDiv.appendChild(hideItem);
								hideItem.style.cursor = 'pointer';

								hideItem.onclick = function (event) {
									if (inProcess) return;
									inProcess = true;

									let loader = pages.elements.getLoader();
									loader.setArea(20);

									hideItem.getElementsByTagName('svg')[0].style.display = 'none';
									hideItem.appendChild(loader);

									return settings.users.friends.hideRequest(user.user_id);
								}
							}

							let acceptItem = document.createElement('div');
							acceptItem.classList.add('accept-item');

							acceptItem.innerHTML = unt.Icon.SAVE;
							containerDiv.appendChild(acceptItem);
							acceptItem.style.marginLeft = '10px';
							acceptItem.style.cursor = 'pointer';

							acceptItem.onclick = function (event) {
								if (inProcess) return;
								inProcess = true;

								let loader = pages.elements.getLoader();
								loader.setArea(20);

								acceptItem.getElementsByTagName('svg')[0].style.display = 'none';
								acceptItem.appendChild(loader);

								return settings.users.friends.acceptRequest(user.user_id);
							}

							friendsBody.appendChild(userElement);
						} else {
							friendsBody.appendChild(userElement);
						}
					}
				}
			} else {
				let alw = pages.elements.alertWindow(unt.Icon.FRIENDS, settings.lang.getValue('no_friends'), settings.lang.getValue('no_friends_text'));
				alw.style.display = '';

				menuBody.appendChild(alw);
			}

			ui.bindItems();
			return loader.hide();
		}).catch(function (error) {
			loader.hide();

			ui.go('https://' + window.location.host + '/id' + selectedUserId);
			return unt.toast({html: settings.lang.getValue("upload_error")});
		});
	},
	audios: function (internalData = {}) {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("audios") : null;

		let menuBody = pages.elements.menuBody().clear();
		menuBody.innerHTML = "";

		let rightMenu = pages.elements.buildRightMenu().append();
		let audiosItem = rightMenu.addItem(settings.lang.getValue('audios'), function () {
			return ui.go('https://' + window.location.host + '/audios', false, false, false, true, {
				section: 'audios'
			})
		});

		let vkItem = null;
		let loader = pages.elements.getLoader();
		loader.style.marginTop = '15px';

		menuBody.appendChild(loader);

		let currentSection = internalData ? internalData.section : 'audios';

		if (currentSection === 'audios') audiosItem.select();
		return settings.accounts.get().then(function (accounts) {
			if (accounts[0].bound) {
				vkItem = rightMenu.addItem('VK', function () {
					return ui.go('https://' + window.location.host + '/audios', false, false, false, true, {
						section: 'vk_audios'
					})
				});

				if (currentSection === 'vk_audios') vkItem.select();
			}

			return subPages.audios.manage(currentSection, internalData, rightMenu, menuBody, loader, accounts);
		}).catch(function (err) {
			let uploadError = pages.elements.uploadError();

			loader.hide();
			return menuBody.appendChild(uploadError);
		})
	},
	sessions: function () {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("activity_history") : null;

		let menuBody = pages.elements.menuBody().clear();

		let settingsData = settings.get();
		if (settingsData && settingsData.theming.backButton && ui.canBack && !ui.isMobile()) {
			let backButton = pages.elements.backArrowButton();

			menuBody.appendChild(backButton);
			ui.bindItems();
		}

		let sessionsDiv = document.createElement('div');
		sessionsDiv.classList = ['card collection'];
		menuBody.appendChild(sessionsDiv);
		sessionsDiv.style.display = 'none';

		let loader = pages.elements.getLoader();
		loader.style.marginTop = "20px";

		menuBody.appendChild(loader);

		return settings.data.getSessions().then(function (sessions) {
			sessions.forEach(function (session) {
				let sessionDiv = document.createElement('div');
				sessionDiv.classList = ['card collection-item full_section waves-effect waves-light'];
				sessionDiv.style.cursor = 'pointer';
				sessionDiv.style.width = '100%';

				let sessionTitle = document.createElement('b');
				sessionDiv.appendChild(sessionTitle);
				sessionTitle.innerText = session.id;
				if (session.is_current) 
					sessionTitle.innerText += ' (' + settings.lang.getValue('online') + ')';

				sessionDiv.appendChild(document.createElement('br'));

				let ipText = document.createElement('div');
				ipText.innerText = 'IP: ' + session.ip;
				sessionDiv.appendChild(ipText);

				let mobileText = document.createElement('div');
				mobileText.innerText = settings.lang.getValue('session_mobile') + ': ' + (session.data.mobile ? settings.lang.getValue('yes') : settings.lang.getValue('no'));
				sessionDiv.appendChild(mobileText);

				let timeDiv = document.createElement('div');
				timeDiv.innerText = settings.lang.getValue('session_time') + ': ' + pages.parsers.time(session.data.time);
				sessionDiv.appendChild(timeDiv);

				sessionDiv.addEventListener('click', function (event) {
					let windowElement = pages.elements.createWindow();

					let content = windowElement.getContent();
					let footer = windowElement.getFooter();

					let headerDiv = document.createElement('div');
					content.appendChild(headerDiv);
					headerDiv.innerHTML = settings.lang.getValue('session_info') + ' <b>' + session.id + '</b>';

					content.appendChild(document.createElement('br'));

					let mobileText = document.createElement('div');
					mobileText.innerHTML = settings.lang.getValue('session_mobile') + ': <b>' + (session.data.mobile ? settings.lang.getValue('yes') : settings.lang.getValue('no')) + '</b>';
					content.appendChild(mobileText);

					let onlineDiv = document.createElement('div');
					onlineDiv.innerHTML = settings.lang.getValue('online') + ': <b>' + (session.is_current ? settings.lang.getValue('yes') : settings.lang.getValue('no')) + '</b>';
					content.appendChild(onlineDiv);

					content.appendChild(document.createElement('br'));

					let ipText = document.createElement('div');
					ipText.innerHTML = 'IP: <b>' + session.ip + '</b>';
					content.appendChild(ipText);

					let timeDiv = document.createElement('div');
					timeDiv.innerHTML = settings.lang.getValue('session_time') + ': <b>' + pages.parsers.time(session.data.time) + '</b>';
					content.appendChild(timeDiv);

					content.appendChild(document.createElement('br'));

					let headersDiv = document.createElement('div');
					headersDiv.innerHTML = settings.lang.getValue('session_headers') + ': <b>' + session.login_data + '</b>';
					content.appendChild(headersDiv);

					if (session.is_closeable) {
						let okButton = document.createElement('a');
						okButton.classList = ['btn btn-flat waves-effect'];
						okButton.innerText = settings.lang.getValue('session_end');
						footer.appendChild(okButton);

						okButton.addEventListener('click', function () {
							return pages.elements.confirm('', settings.lang.getValue('session_end_confirm'), function (response) {
								if (response) {
									return settings.data.endSession(session.id).then(function () {
										windowElement.getInstance().close();

										return sessionDiv.remove();
									}).catch(function (err) {
										return pages.elements.alert(settings.lang.getValue('unable_to_end_session'));
									})
								}
							})
						});
					}

					let okButton = document.createElement('a');
					okButton.classList = ['btn btn-flat waves-effect modal-close'];
					okButton.innerText = 'OK';
					footer.appendChild(okButton);
				});

				sessionsDiv.appendChild(sessionDiv);
			});

			sessionsDiv.style.display = '';

			return loader.hide();
		}).catch(function (err) {
			let uploadError = pages.elements.uploadError();

			loader.hide();
			return menuBody.appendChild(uploadError);
		})
	},
	themes: function (internalData) {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("account_themes") : null;
		document.title = 'yunNet. - ' + settings.lang.getValue("account_themes");

		let menuBody = pages.elements.menuBody().clear();

		let settingsData = settings.get();
		if (settingsData && settingsData.theming.backButton && ui.canBack && !ui.isMobile()) {
			let backButton = pages.elements.backArrowButton();

			menuBody.appendChild(backButton);
			ui.bindItems();
		}

		let workedUrl = (new URLParser(window.location.href)).parse();
		if (workedUrl.section === 'installed')
		{
			return subPages.themes.installed(menuBody, workedUrl, internalData);
		}
		if (workedUrl.section === 'repo')
		{
			return subPages.themes.repo(menuBody);
		}

		menuBody.appendChild(pages.elements.createButton(null, null, null, null, [
			[
				unt.Icon.PALETTE,
				settings.lang.getValue('installed_themes'),
				function () {
					let url = window.location.host.match(/localhost/) ? 'http://localhost' : 'https://yunnet.ru';

					return ui.go(url + '/themes?section=installed');
				},
				'black'
			], [
				unt.Icon.GROUP,
				settings.lang.getValue('theme_repos'),
				function () {
					let url = window.location.host.match(/localhost/) ? 'http://localhost' : 'https://yunnet.ru';

					return ui.go(url + '/themes?section=repo');
				},
				'black'
			]
		]))
	},
	wall: function () {
		if (settings.users.current)
			if (settings.users.current.is_banned) return pages.unauth.banned();

		let offsets = {
			currentCommentsPage: 1
		};
		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("wall") : null;

		let menuBody = pages.elements.menuBody().clear();

		let settingsData;
		try {
			settingsData = settings.get();
		} catch (e) {
			settingsData = null;
		}

		let doneDiv = document.createElement('div');
		menuBody.appendChild(doneDiv);
		if (settingsData) {
			if (settingsData.theming.backButton && ui.canBack && !ui.isMobile()) {
				let backButton = pages.elements.backArrowButton();

				doneDiv.appendChild(backButton);
				ui.bindItems();
			}
		} else {
			if (ui.canBack && !ui.isMobile()) {
				let backButton = pages.elements.backArrowButton();

				doneDiv.appendChild(backButton);
				ui.bindItems();
			}
		}

		doneDiv.style.display = "flex";
		doneDiv.style.flexDirection = "column";
		doneDiv.style.height = "100%";

		let loader = pages.elements.getLoader();

		loader.style.marginTop = "20px";
		doneDiv.appendChild(loader);

		doneDiv.id = 'post';
		doneDiv.addEventListener('dragenter', function (event) {
			event.preventDefault();

			let dragHandler = document.getElementById('drag-manager');
			if (dragHandler) {
				if (dragHandler.style.display !== '')
					dragHandler.style.display = '';
			}
		})

		document.body.addEventListener('drop', function (event) {
		    let dragHandler = document.getElementById('drag-manager');
			if (dragHandler) {
				if (dragHandler.style.display !== 'none')
					dragHandler.style.display = 'none';
			}
		})

		window.onblur = function (event) {
			let dragHandler = document.getElementById('drag-manager');
			if (dragHandler) {
				if (dragHandler.style.display !== 'none')
					dragHandler.style.display = 'none';
			}
		}
		document.onmouseout = function (event) {
			let dragHandler = document.getElementById('drag-manager');
			if (dragHandler) {
				if (dragHandler.style.display !== 'none')
					dragHandler.style.display = 'none';
			}
		}

		let currentData = String(window.location.href.split('wall')[1]).split('_');
		return posts.data.get(Number(currentData[0]), Number(currentData[1]))
			.then(function (post) {
				let postElement = pages.elements.post(post, true, true);
				doneDiv.appendChild(postElement);

				let commentsDiv = document.createElement('div');
				doneDiv.appendChild(commentsDiv);
				commentsDiv.style.height = "100%";

				let commentsList = document.createElement('div');
				commentsList.classList.add('collection');

				commentsDiv.appendChild(commentsList);

				if (post.fields) {
					if (post.fields.can_comment) {
						let commentsForm = pages.elements.inputForm((settings.lang.getValue("write_a_comment")), {
							onInputCallback: function (event, textarea) {
								if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
									return commentsForm.getSendItem().enable();
								} else {
									return commentsForm.getSendItem().disable();
								}
							},
							pasteCallback: function (event, files) {
								if (attachments_list.getElementsByTagName("div").length >= 10)
									return unt.toast({html: settings.lang.getValue('error_atts')});

								return pages.elements.fileUploader({
									onFileSelected: function (event, files, uploader) {
										return uploads
											.getURL()
											.then(function (url) {
												uploader.setLoading(true);

												return uploads
													.upload(url, files[0], function (event) {
														return codes.callbacks.uploadResolve(event, uploader);
													})
													.then(function (attachment) {
														uploader.setLoading(false);
														uploader.close();

														attachments_list.appendChild(pages.parsers.attachment(attachment, function () {
															if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
																return commentsForm.getSendItem().enable();
															} else {
																return commentsForm.getSendItem().disable();
															}
														}));

														if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
															return commentsForm.getSendItem().enable();
														} else {
															return commentsForm.getSendItem().disable();
														}
													})
													.catch(function (err) {
														let errorString = settings.lang.getValue("upload_error");

														unt.toast({html: errorString});
														uploader.setLoading(false);

														uploader.close();
													});
											})
											.catch(function (err) {
												let errorString = settings.lang.getValue("upload_error");

												unt.toast({html: errorString});
												uploader.setLoading(false);

												uploader.close();
											});
									},
									afterClose: function () {
										if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
											return commentsForm.getSendItem().enable();
										} else {
											return commentsForm.getSendItem().disable();
										}
									}
								}).selectFile(files).open();
							},
							attachmentsItemCallback: function (event, item) {
								if (attachments_list.getElementsByTagName("div").length >= 10)
									return unt.toast({html: settings.lang.getValue('error_atts')});

								return pages.elements.fileUploader({
									onFileSelected: function (event, files, uploader) {
										uploader.setLoading(true);

										return uploads
											.getURL()
											.then(function (url) {
												return uploads
													.upload(url, files[0], function (event) {
														return codes.callbacks.uploadResolve(event, uploader);
													})
													.then(function (attachment) {
														uploader.setLoading(false);

														attachments_list.appendChild(pages.parsers.attachment(attachment, function () {
															if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
																return commentsForm.getSendItem().enable();
															} else {
																return commentsForm.getSendItem().disable();
															}
														}));

														if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
															commentsForm.getSendItem().enable();
														} else {
															commentsForm.getSendItem().disable();
														}

														return uploader.close();
													})
													.catch(function (err) {
														let errorString = settings.lang.getValue("upload_error");

														unt.toast({html: errorString});
														return uploader.setLoading(false);
													});
											})
											.catch(function (err) {
												let errorString = settings.lang.getValue("upload_error");

												unt.toast({html: errorString});
												return uploader.setLoading(false);
											});
									},
									afterClose: function () {
										if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
											return commentsForm.getSendItem().enable();
										} else {
											return commentsForm.getSendItem().disable();
										}
									}
								}).open()
							},
							sendItemCallback: function (event, sender) {
								sender.setLoading(true);

								let attachmentsString = pages.parsers.attachmentsString(attachments_list);
								let valueText = write_data.value;

								return posts.comments.send(post.user_id, post.id, valueText.toString(), attachmentsString.toString())
									.then(function (response) {
										sender.setLoading(false);
										let element = pages.elements.comment(response);

										attachments_list.innerHTML = '';
										write_data.innerHTML = '';
										write_data.value = '';
										unt.updateTextFields();

										commentsList.appendChild(element);
										return window.scrollTo(0, commentsList.scrollHeight);
									})
									.catch(function (error) {
										sender.setLoading(false);

										let errorString = settings.lang.getValue("upload_error");
										return unt.toast({html: errorString});
									});
							}
						});

						commentsForm.getSendItem().disable();

						commentsForm.style = "margin: 0px; bottom: 0px; position: sticky !important;";
						
						doneDiv.appendChild(commentsForm);

						commentsList.onscroll = function (event) {
							console.log(event);
						}
					}
				}
				let loaderOfComments = pages.elements.getLoader();
				commentsList.appendChild(loaderOfComments);

				let commentsCount = Number(post.comments.count) || 0;
				posts.comments.get(post.user_id, post.id, 30, Number(commentsCount) - (offsets.currentCommentsPage*30 > commentsCount ? (commentsCount) : offsets.currentCommentsPage*30))
					.then(function (comments) {
						loaderOfComments.remove();
						
						return comments.forEach(function (comment) {
							let element = pages.elements.comment(comment);

							ui.bindItems();
							return commentsList.appendChild(element);
						});
					}
				);

				ui.bindItems();
				return loader.hide();
			})
			.catch(function (error) {
				loader.hide();

				return ui.canBack ? ui.go(null, true) : window.location.href = "/";
			});
	},
	photo: function () {
		if (settings.users.current)
			if (settings.users.current.is_banned) return pages.unauth.banned();

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("photo") : null;

		let menuBody = pages.elements.menuBody().clear();
		let loader = pages.elements.getLoader();

		loader.style.marginTop = '10px';

		menuBody.appendChild(loader);

		let attachmentCredentials = window.location.href.split(window.location.host)[1].split('/')[1].split('?')[0];
		return photos.getByCredentials(attachmentCredentials).then(function (attachment) {
			let url = attachment.photo.url.main;

			return settings.users.get(attachment.photo.owner_id).then(function (user) {
				let infoCard = document.createElement('div');
				infoCard.classList.add('card');
				infoCard.classList.add('full_section');

				let userInfoDiv = document.createElement('div');
				userInfoDiv.classList.add('valign-wrapper');
				infoCard.appendChild(userInfoDiv);

				let userPhoto = document.createElement('img');
				userPhoto.classList.add('circle');
				userPhoto.width = userPhoto.height = 32;
				userPhoto.src = user.photo_url;
				userInfoDiv.appendChild(userPhoto);

				menuBody.appendChild(infoCard);

				let credentialsDiv = document.createElement('a');
				credentialsDiv.classList.add('alink-name');
				credentialsDiv.style.marginLeft = '15px';

				userInfoDiv.appendChild(credentialsDiv);
				credentialsDiv.innerText = user.name || user.first_name + ' ' + user.last_name;
				credentialsDiv.href = user.account_type === 'user' ? ('/id' + user.user_id) : ('/bot' + user.bot_id);
				ui.bindItems();

				let mainImage = document.createElement('img');
				infoCard.appendChild(mainImage);

				mainImage.style.width = '100%';
				mainImage.style.marginTop = '15px';
				mainImage.src = url;
				mainImage.setAttribute('attachment', 'photo' + attachment.photo.owner_id + '_' + attachment.photo.id + '_' + attachment.photo.access_key);
				mainImage.onclick = function (event) {
					return photos.show(mainImage, attachment.photo);
				}

				infoCard.appendChild(document.createElement('br'));

				let likeItem = document.createElement('div');
				likeItem.classList.add('valign-wrapper');
				infoCard.appendChild(likeItem);

				let notLike = document.createElement('div');
				let setLike = document.createElement('div');

				likeItem.appendChild(notLike);
				likeItem.appendChild(setLike);

				notLike.innerHTML = unt.Icon.LIKE;
				setLike.innerHTML = unt.Icon.LIKE_SET;

				notLike.getElementsByTagName('path')[1].style.stroke = 'white';
				setLike.getElementsByTagName('svg')[0].style.display = '';

				if (attachment.photo && attachment.photo.meta.likes.liked_by_me) {
					notLike.style.display = 'none';
					setLike.style.display = '';
				} else {
					notLike.style.display = '';
					setLike.style.display = 'none';
				}

				let likesCountDiv = document.createElement('div');
				likeItem.appendChild(likesCountDiv);
				likesCountDiv.innerText = attachment.photo.meta.likes.count > 0 ? pages.parsers.niceString(attachment.photo.meta.likes.count) : '';;

				likesCountDiv.style.marginLeft = '10px';
				likesCountDiv.style.marginBottom = '7px';

				notLike.onclick = 
				setLike.onclick = 
				function (event) {
					return photos.like(attachmentCredentials).then(function (result) {
						likesCountDiv.innerText = result.new_count > 0 ? pages.parsers.niceString(result.new_count) : '';

						if (result.state === 0) {
							notLike.style.display = '';
							setLike.style.display = 'none';
						} else {
							notLike.style.display = 'none';
							setLike.style.display = '';
						}
					}).catch(function (error) {
						return;
					});
				}

				loader.hide();
			}).catch(function (err) {
				throw new TypeError('Unable to show deleted accounts');
			})
		}).catch(function (err) {
			loader.hide();

			return menuBody.appendChild(pages.elements.uploadError());
		})
	},
	profile: function () {
		if (settings.users.current)
			if (settings.users.	current.is_banned) return pages.unauth.banned();

		function doAction (action, currentUserId) {
			let data = new FormData();
			data.append('action', action.toString());

			return ui.Request({
				data: data,
				method: 'POST',
				success: function () {
					delete settings.users.profiles[currentUserId];

					return ui.go(window.location.href, false, false, true);
				}
			});
		}

		let url = window.location.href.split(window.location.host)[1];
		if (url === "/") return;

		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("profile") : null;

		let menuBody = pages.elements.menuBody().clear();

		let profileBody = document.createElement("div");

		let profileBody_card = document.createElement("div");
		profileBody_card.classList = ["card full_section"];
		profileBody.appendChild(profileBody_card);

		let userInfoDiv = document.createElement("div");
		userInfoDiv.classList = ['valign-wrapper'];

		let userPhotoDiv = document.createElement("div");
		let img = document.createElement("img");

		img.width = 64;
		img.height = 64;
		img.classList.add("circle");
		img.alt = '';

		userPhotoDiv.appendChild(img);
		userInfoDiv.appendChild(userPhotoDiv);

		let userCrDiv = document.createElement("div");
		userCrDiv.classList.add("halign-wrapper");

		userCrDiv.style.marginLeft = '15px';

		userInfoDiv.appendChild(userCrDiv);

		let userNameDiv = document.createElement('div');
		userNameDiv.classList.add('valign-wrapper');

		userCrDiv.appendChild(userNameDiv);
		let innerB = document.createElement('b');
		userNameDiv.appendChild(innerB);
		innerB.innerText = settings.lang.getValue('loading');

		let onlineDiv = document.createElement('div');
		userCrDiv.appendChild(onlineDiv);
		let small = document.createElement('small');
		onlineDiv.appendChild(small);
		small.innerText = '...';

		profileBody_card.appendChild(userInfoDiv);
		menuBody.appendChild(profileBody);

		let statusDiv = document.createElement('div');
		statusDiv.style.width = '100%';

		statusDiv.classList.add('valign-wrapper');
		statusDiv.classList.add('hidet-aone');

		profileBody_card.appendChild(statusDiv);

		return settings.users.resolveScreenName(url)
			.then(function (user) {
				if (user.screen_name) history.replaceState(null, document.title, '/' + user.screen_name);

				ui.userVisited = (user.user_id ? user.user_id : user.bot_id*-1);
				if (user.status) {
					statusDiv.style.cursor = 'pointer';
					statusDiv.style.marginTop = '20px';
					statusDiv.innerHTML = unt.Icon.STATS;

					let statusTextDiv = document.createElement('div');
					statusTextDiv.classList.add('credentials');
					statusTextDiv.classList.add('hidet-aone');

					statusDiv.appendChild(statusTextDiv);

					statusTextDiv.innerText = user.status;
					statusTextDiv.style.width = '100%';

					statusDiv.onclick = function () {
						return pages.elements.alert(user.status);
					}
				}

				if (settings.users.current && (ui.userVisited === settings.users.current.user_id)) {
					if (!user.status) {
						statusDiv.style.cursor = 'pointer';
						statusDiv.style.marginTop = '20px';
						statusDiv.innerHTML = unt.Icon.EDIT;

						let statusTextDiv = document.createElement('div');
						statusTextDiv.classList.add('credentials');
						statusTextDiv.classList.add('hidet-aone');

						statusDiv.appendChild(statusTextDiv);

						statusTextDiv.innerText = settings.lang.getValue("edit_status");
						statusTextDiv.style.width = '100%';
					}

					statusDiv.onclick = function () {
						let windowElement = pages.elements.createWindow();

						let continueButton = document.createElement('a');
						continueButton.classList = ['btn btn-flat waves-effect'];
						continueButton.innerText = settings.lang.getValue('continue');

						windowElement.getFooter().appendChild(continueButton);

						let postEditorHeader = document.createElement('div');
						windowElement.getContent().appendChild(postEditorHeader);

						postEditorHeader.classList.add('valign-wrapper');
						postEditorHeader.style.width = '100%';

						let headerText = document.createElement('div');
						postEditorHeader.appendChild(headerText);
						headerText.style.width = '100%';
						headerText.innerText = settings.lang.getValue('edit_status');

						let closeButton = document.createElement('div');
						closeButton.style.cursor = 'pointer';
						closeButton.style.marginTop = '5px';
						postEditorHeader.appendChild(closeButton);
						closeButton.innerHTML = unt.Icon.CLOSE;

						closeButton.addEventListener('click', function () {
							windowElement.getInstance().close();
						});

						let statusInput = pages.elements.createInputField("HMMM :///", user.status ? true : false);
						if (user.status)
							statusInput.setText(user.status);

						windowElement.getContent().appendChild(statusInput);
						continueButton.onclick = function () {
							continueButton.innerText = settings.lang.getValue('loading');

							continueButton.classList.add('disabled');
							statusInput.setDisabled(true);

							settings.users.setStatus(statusInput.getValue()).then(function (status) {
								windowElement.getInstance().close();

								if (!status.isEmpty())
									settings.users.profiles[settings.users.current.user_id].status = status;

								return pages.profile();
							}).catch(function (err) {
								continueButton.innerText = settings.lang.getValue('continue');

								continueButton.classList.remove('disabled');
								statusInput.setDisabled(false);

								return unt.toast({html: settings.lang.getValue('upload_error')});
							})
						}
					}
				}

				if (user.photo_object)
					img.setAttribute('attachment', 'photo' + user.photo_object.owner_id + '_' + user.photo_object.id + '_' + user.photo_object.access_key);
				else
					img.setAttribute('attachment', 'photo' + ui.userVisited +  '_1_all');

				img.onclick = function () {
					return photos.show(img, user.photo_object);
				}

				let currentUserId = ui.userVisited;
				img.src = user.photo_url;

				let onlineString = pages.parsers.getOnlineState(user);
				innerB.innerText = (user.account_type === "user" ? user.first_name + " " + user.last_name : user.name);

				document.title = "yunNet. - " + innerB.innerText;
				small.innerText  = onlineString;

				if (user.is_verified) {
					let iconDiv = document.createElement('div');
					iconDiv.classList.add('credentials');

					userNameDiv.appendChild(iconDiv);

					let innerIconDiv = document.createElement('div')
					innerIconDiv.classList.add('card');

					innerIconDiv.style.margin = 0;
					innerIconDiv.style.cursor = 'pointer';
					innerIconDiv.style.width = innerIconDiv.style.height = '20px';
					innerIconDiv.style.borderRadius = '30px';

					innerIconDiv.innerHTML = unt.Icon.PALETTE_ANIM;
					iconDiv.appendChild(innerIconDiv);
				}

				if (settings.users.current) {
					let actions = [];
					let isBlocked = false;

					if (user.account_type === "user") {
						if (user.user_id === settings.users.current.user_id) {
							actions.push([settings.lang.getValue("edit"), function () {
								return ui.go("https://" + window.location.host + "/edit");
							}]);
						} else {
							if (!user.is_banned) {
								if (user.can_write_messages && !user.is_me_blacklisted && !user.is_blacklisted) {
									actions.push([settings.lang.getValue("write_p"), function () {
										return ui.go("https://" + window.location.host + "/messages?s=" + (ui.userVisited > 0 ? ui.userVisited : "b"+ui.userVisited*-1));
									}]);
								}

								if (!user.is_me_blacklisted && !user.is_blacklisted) {
									if (typeof user.friend_state === "object") {
										if (user.friend_state.state === 2) {
											actions.push([settings.lang.getValue("delete_friend"), function () {
												return doAction("delete", currentUserId);
											}]);
										}
										if (user.friend_state.state === 1) {
											let initier = user.friend_state.user1;
											let accepter = user.friend_state.user2;

											if (initier === settings.users.current.user_id) {
												actions.push([settings.lang.getValue("cancel_request"), function () {
													return doAction("delete", currentUserId);
												}]);
											} else {
												actions.push([settings.lang.getValue("accept_request"), function () {
													return doAction("add", currentUserId);
												}]);
											}
										}
										if (user.friend_state.state === 0) {
											actions.push([settings.lang.getValue("add_to_the_friends"), function () {
												doAction("add", currentUserId);

												return unt.Notification(unt.Icon.SAVE, settings.lang.getValue('done'), settings.lang.getValue('friends_request_sent'), [], {
													timeout: 3,
													sound: false
												});
											}]);
										}
									}
								}
							}

							actions.push([user.is_blacklisted ? settings.lang.getValue("unblock") : settings.lang.getValue("block"), function () {
								return doAction("block", currentUserId);
							}]);
						}
						if (!user.is_me_blacklisted && user.can_access_closed) {
							actions.push([settings.lang.getValue("show_friends"), function () {
								return ui.go("https://" + window.location.host + "/friends?id=" + ui.userVisited, false, false, false, true, {
									action: 'friends',
									user_id: ui.userVisited
								});
							}]);
						}

						if (user.is_me_blacklisted) isBlocked = true;
					}
					if (user.account_type === "bot") {
						if (user.can_write_messages && !user.is_banned && !user.is_deleted) {
							if (user.bot_can_write_messages)
								actions.push([settings.lang.getValue("write_p"), function () {
									return ui.go("https://" + window.location.host + "/messages?s=" + (ui.userVisited > 0 ? ui.userVisited : "b"+ui.userVisited*-1));
								}]);

							if (user.can_invite_to_chat)
								actions.push([settings.lang.getValue("invite_to_the_chat"), function () {
									return ui.go("https://" + window.location.host + "/messages", false, false, false, true, {
										action: 'invite_to_chat',
										user_id: ui.userVisited
									});
								}]);
						}

						if (user.owner_id === settings.users.current.user_id) {
							actions.push([settings.lang.getValue("edit"), function () {
								return window.location.href = "https://dev.yunnet.ru/bots";
							}]);
						}

						if (user.bot_can_write_messages) {
							actions.push([settings.lang.getValue("block_messages"), function () {
								return doAction("toggle_send_access", currentUserId);
							}]);
						} else {
							actions.push([settings.lang.getValue("unblock_messages"), function () {
								return doAction("toggle_send_access", currentUserId);
							}]);
						}
					}

					if (!(user.permissions_type > 0))
						profileBody.appendChild(pages.elements.actionsMenu(actions, isBlocked, true));

					if (user.can_write_on_wall) {
						let wallInput = pages.elements.wallInput({
							photoUrl: settings.users.current.photo_url,
							hideInputButton: true,
							attachFileCallback: function (event, attachItem) {
								if (attachments.getElementsByTagName("div").length >= 10)
									return unt.toast({html: settings.lang.getValue('error_atts')});

								return pages.elements.contextMenu([
									[
										settings.lang.getValue('photo'),
										function () {
											let fileUploader = pages.elements.fileUploader({
												onFileSelected: function (event, files, uploader) {
													uploader.setLoading(true);

													return uploads
														.getURL()
														.then(function (url) {
															return uploads
																.upload(url, files[0], function (event) {
																	return codes.callbacks.uploadResolve(event, uploader);
																})
																.then(function (attachment) {
																	uploader.setLoading(false);

																	attachments.appendChild(pages.parsers.attachment(attachment, function () {
																		if (pages.parsers.attachmentsValid(attachments, write_text)) {
																			return wallInput.showInputButton();
																		} else {
																			return wallInput.hideInputButton();
																		}
																	}));

																	if (pages.parsers.attachmentsValid(attachments, write_text)) {
																		wallInput.showInputButton();
																	} else {
																		wallInput.hideInputButton();
																	}

																	return uploader.close();
																})
																.catch(function (err) {
																	let errorString = settings.lang.getValue("upload_error");

																	unt.toast({html: errorString});
																	return uploader.setLoading(false);
																});
														})
														.catch(function (err) {
															let errorString = settings.lang.getValue("upload_error");

															unt.toast({html: errorString});
															return uploader.setLoading(false);
														});
												},
												afterClose: function (event, uploader) {
													if (pages.parsers.attachmentsValid(attachments, write_text)) {
														return wallInput.showInputButton();
													} else {
														return wallInput.hideInputButton();
													}
												}
											});

											return fileUploader.open();
										}
									],
									[
										settings.lang.getValue('poll'),
										function () {
											if (poll.pinned(attachments)) return unt.toast({html: settings.lang.getValue('upload_error')});

											return poll.creator(function (poll, creator) {
												let pollElement = pages.elements.createPollElement(poll.poll, function () {
													if (pages.parsers.attachmentsValid(attachments, write_text)) {
														return wallInput.showInputButton();
													} else {
														return wallInput.hideInputButton();
													}
												});

												attachments.appendChild(pollElement);
												if (pages.parsers.attachmentsValid(attachments, write_text)) {
													return wallInput.showInputButton();
												} else {
													return wallInput.hideInputButton();
												}
											}).show();
										}
									]
								]).open(event, true);
							},
							publishSettingsCallback: function (event, settingsItem) {

							},
							publishCallback: function (event, publishButton) {
								let attachmentsString = pages.parsers.attachmentsString(attachments);

								if (String(document.getElementById("write_text").value).isEmpty() && attachmentsString.isEmpty()) return false;

								publishButton.setLoading(true);
								return posts.actions.create(ui.userVisited, String(document.getElementById("write_text").value), attachmentsString).then(function (post) {
									let element = pages.elements.post(post);

									let alertWindow = document.getElementById("alertWindow");
									if (alertWindow) alertWindow.remove();

									publishButton.setLoading(false);
									wallInput.hideInputButton();

									document.getElementById("write_text").value = '';
									document.getElementById("write_text").innerHTML = '';

									unt.updateTextFields();
									attachments.innerHTML = '';

									unt.Collapsible.getInstance(wallInput).close();

									posts_list.style.display = '';
									posts_list.prepend(element);

									return ui.bindItems();
								}).catch(function (err) {
									publishButton.setLoading(false);

									let errorString = settings.lang.getValue("upload_error");
									return unt.toast({html: errorString});
								});
							},
							inputTextCallback: function (event, textareaInput) {
								if (pages.parsers.attachmentsValid(attachments, write_text)) {
									return wallInput.showInputButton();
								} else {
									return wallInput.hideInputButton();
								}
							}
						});

						profileBody.appendChild(wallInput);
					}

					unt.AutoInit();
					unt.updateTextFields();
				} else {
					profileBody.appendChild(pages.elements.actionsMenu(null, false, false));
				}

				if (user.is_banned) {
					let windowOfAlert = pages.elements.alertWindow(unt.Icon.CLEAR, settings.lang.getValue("user_banned"), settings.lang.getValue("user_banned_text"));

					return profileBody.appendChild(windowOfAlert);
				} else {
					if (!user.is_me_blacklisted && (user.can_access_closed || user.account_type === "bot")) {
						let loader = pages.elements.getLoader();
						loader.id = "posts_loader";

						let posts_div = document.createElement("div");
						posts_div.id = "posts_list";

						profileBody.appendChild(loader);
						profileBody.appendChild(posts_div);
						posts.getAll(currentUserId, 0, 20)
							.then(function (posts) {
								let currentOffset = 0;
								posts_div.style.display = 'none';

								if (posts.length > 0) {
									for (let i = 0; i < posts.length; i++) {
										let postElement = pages.elements.post(posts[i]);

										posts_div.appendChild(postElement);
									}

									loader.hide();
									posts_div.style.display = '';

									return ui.bindItems();
								} else {
									if (currentOffset === 0) {
										let windowOfAlert = pages.elements.alertWindow(unt.Icon.CLEAR, settings.lang.getValue("no_posts"), settings.lang.getValue("no_posts_t"));
										loader.hide();

										return profileBody.appendChild(windowOfAlert);
									} else {
										loader.hide();
									}
								}
								
							});
					} else if (!user.can_access_closed && !user.is_me_blacklisted) {
						let windowOfAlert = pages.elements.alertWindow(unt.Icon.LOCKED, settings.lang.getValue("closed_profile"), settings.lang.getValue("closed_profile_message"));

						return profileBody.appendChild(windowOfAlert);
					} else if (user.is_me_blacklisted) {

					}
				}
			})
			.catch(function (error) {
				ui.userVisited = 0;

				profileBody_card.innerHTML = '';

				let notFoundText = document.createElement('div');
				profileBody_card.appendChild(notFoundText);

				notFoundText.innerText = settings.lang.getValue('user_not_found');
				notFoundText.classList.add('center');

				document.title = 'yunNet.';
			})
	},
	archive: function () {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		let menuBody = pages.elements.menuBody().clear();
		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("archive") : null;
	},
	groups: function () {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		let menuBody = pages.elements.menuBody().clear();
		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("groups") : null;
	},
	settings: function () {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		let menuBody = pages.elements.menuBody().clear();
		document.title = 'yunNet. - ' + settings.lang.getValue('settings');

		let workedUrl = (new URLParser(window.location.href)).parse();
		let rightMenu = null;
		let rightMenuItems = [];

		let sections = ['main', 'notifications', 'privacy', 'security', 'blacklist', 'accounts', 'theming', 'about'];
		let selectedSection = workedUrl.section;

		if (!ui.isMobile() && sections.indexOf(selectedSection) === -1) selectedSection = 'main'; 

		let url = window.location.host.match(/localhost/) ? 'http://localhost' : 'https://yunnet.ru';

		if (!ui.isMobile()) {
			let rightMenu = pages.elements.buildRightMenu().append();
			sections.forEach(function (itemName) {
				let element = rightMenu.addItem(settings.lang.getValue(itemName), function (itemSelected, itemElement) {
					return ui.go(url + '/settings?section=' + itemElement.getAttribute('category'));
				});

				element.setAttribute('category', itemName);
				rightMenuItems.push(element);
			});

			if (sections.indexOf(selectedSection) !== -1) {
				rightMenuItems[sections.indexOf(selectedSection)].select();
			}
		} else {
			if (sections.indexOf(selectedSection) === -1) {
				nav_header_title.innerText = settings.lang.getValue('settings');

				let profileCard = document.createElement('div');
				profileCard.classList = ['card full_section'];

				menuBody.appendChild(profileCard);

				let valignDiv = document.createElement('div');
				valignDiv.classList.add('valign-wrapper');

				profileCard.appendChild(valignDiv);

				let imgDiv = document.createElement('div');
				let credDiv = document.createElement('div');

				valignDiv.appendChild(imgDiv);
				valignDiv.appendChild(credDiv);

				let img = document.createElement('img');
				imgDiv.appendChild(img);

				img.classList.add('circle');
				img.alt = '';
				img.width = img.height = 48;
				img.src = settings.users.current.photo_url;

				let nameDiv = document.createElement('div');
				credDiv.appendChild(nameDiv);

				nameDiv.classList.add('alink-name');
				credDiv.style.marginLeft = '15px';

				let inB = document.createElement('b');
				nameDiv.appendChild(inB);
				inB.innerText = settings.users.current.first_name + ' ' + settings.users.current.last_name;

				let onlineDiv = document.createElement('div');
				credDiv.appendChild(onlineDiv);
				credDiv.classList.add('halign-wrapper');

				onlineDiv.innerHTML = '<small style="color: gray">' + pages.parsers.getOnlineState(settings.users.current) + '</small>';

				menuBody.appendChild(pages.elements.createButton(null, null, null, null, [
					[
						unt.Icon.ACCOUNT,
						settings.lang.getValue('main'),
						function () { return ui.go(url + '/settings?section=main') }
					], [
						unt.Icon.NOTIFICATIONS,
						settings.lang.getValue('notifications'),
						function () { return ui.go(url + '/settings?section=notifications') }
					]
				]));

				menuBody.appendChild(pages.elements.createButton(null, null, null, null, [
					[
						unt.Icon.LOCKED,
						settings.lang.getValue('privacy'),
						function () { return ui.go(url + '/settings?section=privacy') }
					], [
						unt.Icon.SECURITY,
						settings.lang.getValue('security'),
						function () { return ui.go(url + '/settings?section=security') }
					], [
						unt.Icon.LIST,
						settings.lang.getValue('blacklist'),
						function () { return ui.go(url + '/settings?section=blacklist') }
					]
				]));

				menuBody.appendChild(pages.elements.createButton(null, null, null, null, [
					[
						unt.Icon.GROUP,
						settings.lang.getValue('accounts'),
						function () { return ui.go(url + '/settings?section=accounts') }
					], [
						unt.Icon.PALETTE,
						settings.lang.getValue('theming'),
						function () { return ui.go(url + '/settings?section=theming') }
					]
				]));

				menuBody.appendChild(pages.elements.createButton(null, null, null, null, [
					[
						unt.Icon.STATS,
						settings.lang.getValue('about'),
						function () { return ui.go(url + '/settings?section=about') }
					]
				]));

				menuBody.appendChild(pages.elements.createButton('', settings.lang.getValue("logout"), function () {
					return pages.elements.confirm('', settings.lang.getValue('logout_qq'), function (response) {
						if (response) {
							return settings.Logout();
						}
					});
				}, 'red'));
			} else {
				nav_header_title.innerText = settings.lang.getValue(sections[sections.indexOf(selectedSection)]);

				document.title = 'yunNet. - ' + nav_header_title.innerText;
			}
		}

		return codes.callbacks.Settings(selectedSection, menuBody, rightMenu, workedUrl, sections);
	},
	messages: function (internalData = {}) {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();

		let menuBody = pages.elements.menuBody().clear();
		let workedUrl = (new URLParser(window.location.href)).parse();
		let currentPage = 1;

		if (workedUrl.action === 'info' && workedUrl.s) {
			if (!ui.isMobile()) {
				let settingsData = settings.get();
				if (settingsData) {
					if (settingsData.theming.backButton && !ui.isMobile() && ui.canBack) {
						menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('chat')), function (event) {
							return ui.go(null, true);
						});
					}
				}
			} else {
				nav_header_title.innerText = settings.lang.getValue("chat");
			}

			let chat = messages.utils.getChatInstance(workedUrl.s);
			if (!chat) return ui.go(null, true);

			let loader = pages.elements.getLoader();

			loader.style.marginTop = '10px';
			menuBody.appendChild(loader);

			if (!internalData) internalData = {};

			chat.getInfo().then(function (chatObject) {
				chat.getPermissions().then(function (permissions) {
					chat.getCurrentPermissionsLevel().then(function (myAccessLevel) {
						if (workedUrl.mode === 'management') {
							return codes.callbacks.showManagementPage(chatObject, permissions, myAccessLevel, menuBody, loader, chat);
						}

						if (workedUrl.mode === 'edit' && internalData.userObject && internalData.menuBody && internalData.chat) {
							return codes.callbacks.showUserManagementPage(internalData.userObject, chatObject, myAccessLevel, permissions, internalData.menuBody, loader, internalData.chat);
						}

						return codes.callbacks.chatInfoCallback(chatObject, permissions, myAccessLevel, menuBody, loader, chat);
					}).catch(function (err) {
						return codes.callbacks.chatInfoCallback(chatObject, {}, -1, menuBody, loader, chat);
					});
				}).catch(function (err) {
					return codes.callbacks.chatInfoCallback(chatObject, {}, -1, menuBody, loader, chat);
				});
			});
		}	
		else if (workedUrl.s) {
			menuBody.style.height = '100%';
			let currentPage = 1;

			document.title = "yunNet. - " + settings.lang.getValue("message");
			messages.getChatByPeer(workedUrl.s).then(function (dialogObject) {
				if (!dialogObject) return ui.go("https://" + window.location.host + "/messages");
				let chatWindow = document.createElement('div');

				chatWindow.addEventListener('dragenter', function (event) {
					event.preventDefault();

					let dragHandler = document.getElementById('drag-manager');
					if (dragHandler) {
						if (dragHandler.style.display !== '')
							dragHandler.style.display = '';
					}
				})

				document.body.addEventListener('drop', function (event) {
				    let dragHandler = document.getElementById('drag-manager');
					if (dragHandler) {
						if (dragHandler.style.display !== 'none')
							dragHandler.style.display = 'none';
					}
				})

				window.onblur = function (event) {
					let dragHandler = document.getElementById('drag-manager');
					if (dragHandler) {
						if (dragHandler.style.display !== 'none')
							dragHandler.style.display = 'none';
					}
				}
				document.onmouseout = function (event) {
					let dragHandler = document.getElementById('drag-manager');
					if (dragHandler) {
						if (dragHandler.style.display !== 'none')
							dragHandler.style.display = 'none';
					}
				}

				chatWindow.id = "chat";
				chatWindow.classList.add("hidesc");

				chatWindow.style.height = "100%";
				chatWindow.style.display = "flex";
				chatWindow.style.flexDirection = "column";

				let header = pages.elements.messages.buildChatHeader(dialogObject);
				let actsHeader = pages.elements.messages.buildActionsHeader('0/100', [], function () {
					let messages = document.querySelectorAll('.selected');

					for (let i = 0; i < messages.length; i++) {
						let messageElement = messages[i];
						let messageId = Number(messageElement.id);

						messageElement.classList.remove('selected');
						chat.selectedMessages.splice(chat.selectedMessages.indexOf(messageId), 1);
					}

					chatHeader.style.display = '';
					actsHeader.style.display = 'none';
					editHeader.style.display = 'none';

					return actsHeader.setText(chat.selectedMessages.length + '/100');
				});

				let editHeader = pages.elements.messages.buildEditHeader(function () {
					if (chat.isSaving) return;

					let messages = document.querySelectorAll('.selected');

					for (let i = 0; i < messages.length; i++) {
						let messageElement = messages[i];
						let messageId = Number(messageElement.id);

						messageElement.classList.remove('selected');
						chat.selectedMessages.splice(chat.selectedMessages.indexOf(messageId), 1);
					}

					chatHeader.style.display = '';
					actsHeader.style.display = 'none';
					editHeader.style.display = 'none';

					chat.editMode = false;
					inputForm.getSendItem().setIcon(unt.Icon.SEND);

					write_data.value = '';
					unt.textareaAutoResize(write_data);
					unt.updateTextFields();

					attachments_list.innerHTML = '';
					if (document.getElementById('fwditem'))
						fwditem.remove();

					inputForm.getSendItem().disable();
				})

				editHeader.style.display = 'none';

				let chat = messages.utils.getChatInstance(workedUrl.s);
				if (!chat) return ui.go('https://' + window.location.host + '/messages');

				actsHeader.style.display = 'none';
				actsHeader.id = 'actsHeader';

				if (ui.isMobile()) {
					document.getElementsByClassName("nav-wrapper")[0].getElementsByTagName("ul")[0].appendChild(header);
					document.getElementsByClassName("nav-wrapper")[0].insertAdjacentElement('afterend', actsHeader);
					document.getElementsByClassName("nav-wrapper")[0].insertAdjacentElement('afterend', editHeader);
				} else {
					chatWindow.appendChild(header);
					chatWindow.appendChild(actsHeader);
					chatWindow.appendChild(editHeader);
				}

				let messagesDiv = document.createElement('div');
				messagesDiv.id = "messages_" + workedUrl.s;

				chatWindow.appendChild(messagesDiv);
				messagesDiv.classList = ["messages hidesc center"];
				messagesDiv.style = "flex-wrap: wrap; overflow-x: hidden; height: 100%";

				pages.messages.alreadyTyping = false;

				let inputForm = pages.elements.inputForm((settings.lang.getValue("write_a_message")), {
					onInputCallback: function (event) {
						if (write_data.value === 'this is the sad unt :(')
							inputForm.getAttachmentsItem().innerHTML = unt.Icon.SMILE_BAD;
						else
							inputForm.getAttachmentsItem().innerHTML = unt.Icon.ATTACHMENT;

						if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
							inputForm.getSendItem().enable();
						} else {
							inputForm.getSendItem().disable();
						}

						if (pages.messages.alreadyTyping) return false;

						pages.messages.alreadyTyping = true;
						pages.messages.lastTimeout = setTimeout(function () {
							pages.messages.alreadyTyping = false;
						}, 5*1000);

						let data = new FormData();

						data.append('action', 'set_typing_state');
						data.append('state', 'typing');
						data.append('peer_id', workedUrl.s);

						return ui.Request({
							data: data,
							url: '/messages',
							method: 'POST'
						});
					},
					pasteCallback: function (event, files) {
						if (attachments_list.getElementsByTagName("div").length >= 10)
							return unt.toast({html: settings.lang.getValue('error_atts')});

						return pages.elements.fileUploader({
							onFileSelected: function (event, files, uploader) {
								return uploads
									.getURL()
									.then(function (url) {
										uploader.setLoading(true);

										return uploads
											.upload(url, files[0], function (event) {
												return codes.callbacks.uploadResolve(event, uploader);
											})
											.then(function (attachment) {
												uploader.setLoading(false);
												uploader.close();

												attachments_list.appendChild(pages.parsers.attachment(attachment, function () {
													if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
														return inputForm.getSendItem().enable();
													} else {
														return inputForm.getSendItem().disable();
													}
												}));

												if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
													return inputForm.getSendItem().enable();
												} else {
													return inputForm.getSendItem().disable();
												}
											})
											.catch(function (err) {
												let errorString = settings.lang.getValue("upload_error");

												unt.toast({html: errorString});
												uploader.setLoading(false);

												uploader.close();
											});
									})
									.catch(function (err) {
										let errorString = settings.lang.getValue("upload_error");

										unt.toast({html: errorString});
										uploader.setLoading(false);

										uploader.close();
									});
							},
							afterClose: function () {
								if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
									return inputForm.getSendItem().enable();
								} else {
									return inputForm.getSendItem().disable();
								}
							}
						}).selectFile(files).open();
					},
					attachmentsItemCallback: function (event) {
						if (attachments_list.getElementsByTagName("div").length >= 10)
							return unt.toast({html: settings.lang.getValue('error_atts')});

						return pages.elements.contextMenu([
							[
								settings.lang.getValue('photo'),
								function () {
									return pages.elements.fileUploader({
										onFileSelected: function (event, files, uploader) {
											uploader.setLoading(true);

											return uploads
												.getURL()
												.then(function (url) {
													return uploads
														.upload(url, files[0], function (event) {
															return codes.callbacks.uploadResolve(event, uploader);
														})
														.then(function (attachment) {
															uploader.setLoading(false);

															attachments_list.appendChild(pages.parsers.attachment(attachment, function () {
																if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
																	return inputForm.getSendItem().enable();
																} else {
																	return inputForm.getSendItem().disable();
																}
															}));

															if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
																inputForm.getSendItem().enable();
															} else {
																inputForm.getSendItem().disable();
															}
															
															return uploader.close();
														})
														.catch(function (err) {
															let errorString = settings.lang.getValue("upload_error");

															unt.toast({html: errorString});
															return uploader.setLoading(false);
														});
												})
												.catch(function (err) {
													let errorString = settings.lang.getValue("upload_error");

													unt.toast({html: errorString});
													return uploader.setLoading(false);
												});
										},
										afterClose: function () {
											if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
												return inputForm.getSendItem().enable();
											} else {
												return inputForm.getSendItem().disable();
											}
										}
									}).open();
								}
							], 
							[
								settings.lang.getValue('poll'),
								function () {
									if (poll.pinned(attachments_list)) return unt.toast({html: settings.lang.getValue('upload_error')});

									return poll.creator(function (poll, creator) {
										let pollElement = pages.elements.createPollElement(poll.poll, function () {
											if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
												return inputForm.getSendItem().enable();
											} else {
												return inputForm.getSendItem().disable();
											}
										});

										attachments_list.appendChild(pollElement);
										if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
											return inputForm.getSendItem().enable();
										} else {
											return inputForm.getSendItem().disable();
										}
									}).show();
								}
							]
						]).open(event, true);
					},
					sendItemCallback: function (event, sendItem, thisHandler) {
						let text = String(write_data.value);
						let attachments = pages.parsers.attachmentsString(attachments_list);
						let fwd = '';
						let peer_id = String(workedUrl.s);

						let chat = messages.utils.getChatInstance(peer_id);

						let messageObject = {};
						if (!chat.editMode) {
							messageObject = {
								from_id: settings.users.current.user_id || 0,
								id: 0,
								text: text,
								attachments: pages.parsers.attachmentsArray(attachments_list),
								fwd: [],
								time: Math.floor(new Date() / 1000)
							};

							if (document.getElementsByClassName('to_fwd')[0]) {
								let fwdForm = document.getElementsByClassName('to_fwd')[0];
								let selectedToFwd = fwdForm.fwdSelected;

								selectedToFwd.forEach(function (messageId) {
									let selChat = messages.utils.getChatInstance(chat.fwdPeer || peer_id);

									messageObject.fwd.push(selChat.messagesCache[messageId]);
								});

								fwdForm.remove();
								fwd = selectedToFwd.join(',') + '_' + (chat.fwdPeer || peer_id);

								chat.fwdPeer = null;
							}

							if (peer_id.startsWith('b')) 
								messageObject.bot_peer_id = Number(String(peer_id).split('b').join(''))
							else
								messageObject.peer_id = Number(peer_id);
						}

						if (text.isEmpty() && attachments.isEmpty() && fwd.isEmpty()) return;
							sendItem.setLoading(true);

						let messageElement;

						if (!chat.editMode) {
							messageElement = messages.elements.message(messageObject, true);
							messageElement.onclick = function (event) {
								return codes.callbacks.messageCallback(this, chat, actsHeader, chatHeader, event);
							}

							messagesDiv.appendChild(messageElement);
							messagesDiv.scrollTo(0, messagesDiv.scrollHeight);
						}

						write_data.value = '';
						unt.updateTextFields();
						unt.textareaAutoResize(write_data);

						attachments_list.innerHTML = '';

						if (chat.editMode) sendItem.setIcon(unt.Icon.SEND);
						function doSend () {
							pages.messages.alreadyTyping = false;
							clearTimeout(pages.messages.lastTimeout);

							return chat.sendMessage(text, attachments, fwd).then(function (id) {
								if (document.getElementById(id))
									messageElement.remove();

								messageElement.id = id;
								messageObject.id = id;

								sendItem.setLoading(false);
								sendItem.disable();
								messageElement.setSending(false);

								realtime.connect.pending[id] = messageElement;
							}).catch(function (error) {
								sendItem.setLoading(false);
								sendItem.disable();

								messageElement.setError(function (event) {
									return pages.elements.contextMenu([[
										settings.lang.getValue('repeat'), function () {
											messageElement.setSending(true);

											return doSend();
										}
									], [
										settings.lang.getValue('delete'), function () {
											return messageElement.remove();
										}
									]]).open(event);
								});
							});
						}

						function doSave() {
							chat.isSaving = true;
							let messageId = Number(document.getElementsByClassName('selected')[0].parentNode.parentNode.id);

							return chat.saveMessage(messageId, text, attachments, fwd).then(function (id) {
								chat.isSaving = false;
								editHeader.close();

								sendItem.setLoading(false);
								sendItem.disable();
							}).catch(function (err) {
								chat.isSaving = false;

								sendItem.setLoading(false);
							});
						};

						if (!chat.editMode) return doSend();
						else return doSave();
					}
				});

				let keyboardOpen = false;

				inputForm.addItem(unt.Icon.KEYBOARD, 'chat-keyboard-toggle', function (event, item, openMode) {
					keyboardOpen = !keyboardOpen;
					if (!event)
						keyboardOpen = openMode;

					if (keyboardOpen) {
						item.innerHTML = unt.Icon.HIDE_KEYBOARD;

						if (chat.keyboard && chat.keyboard.elements) {
							inputForm.getElementsByClassName('odiv')[0].appendChild(chat.keyboard.elements.keyboard);
						}
					}
					if (!keyboardOpen) {
						item.innerHTML = unt.Icon.KEYBOARD;

						inputForm.getElementsByClassName('odiv')[0].innerHTML = '';
					}
				}).hide();

				inputForm.getSendItem().disable();
				inputForm.onkeydown = function (event) {
					if (event.keyCode === 17) {
						inputForm.isPressed = true;
					}

					if (!ui.isMobile() && !inputForm.isPressed && event.keyCode === unt.keys.ENTER) {
						event.preventDefault();

						return inputForm.arguments[1].sendItemCallback(event, inputForm.getSendItem(), inputForm.arguments[1].sendItemCallback);
					}

					if (!ui.isMobile() && inputForm.isPressed && event.keyCode === unt.keys.ENTER) {
						write_data.value += '\r\n';

						unt.updateTextFields();
						unt.textareaAutoResize(write_data);
						write_data.focus();
					}
				}
				inputForm.onkeyup = function (event) {
					if (event.keyCode === 17) {
						inputForm.isPressed = false;
					}
				}

				let deleteItem = actsHeader.addItem(unt.Icon.DELETE, 'delete', new Function());
				let editItem = actsHeader.addItem(unt.Icon.EDIT, 'edit', function (event, item) {
					actsHeader.style.display = 'none';
					chatHeader.style.display = 'none';
					editHeader.style.display = '';

					chat.editMode = true;
					inputForm.getSendItem().setIcon(unt.Icon.SAVE);

					let selectedMessageElement = document.querySelectorAll('.selected')[0];
					let selectedMessageId = Number(selectedMessageElement.parentNode.parentNode.id);

					let messageObject = chat.messagesCache[selectedMessageId];
					if (messageObject.text) {
						write_data.value = messageObject.text;

						unt.updateTextFields();
						unt.textareaAutoResize(write_data);
					}

					if (messageObject.attachments.length > 0) {
						messageObject.attachments.forEach(function (attachment) {
							attachments_list.appendChild(pages.parsers.attachment(attachment, function (event) {
								if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
									return inputForm.getSendItem().enable();
								} else {
									return inputForm.getSendItem().disable();
								}
							}));
						});
					}

					if (messageObject.fwd.length > 0) {
						let resultingMessages = new Selection();

						messageObject.fwd.forEach(function (item) {
							resultingMessages.push(item.id);
						});

						let fwdForm = pages.elements.buildFwdItem(resultingMessages, function () {
							if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
								return inputForm.getSendItem().enable();
							} else {
								return inputForm.getSendItem().disable();
							}
						});
						inputForm.insertAdjacentElement('afterbegin', fwdForm);
					}

					if (pages.parsers.attachmentsValid(attachments_list, write_data)) {
						return inputForm.getSendItem().enable();
					} else {
						return inputForm.getSendItem().disable();
					}
				});
				let replyItem = actsHeader.addItem(unt.Icon.REPLY, 'reply', function (event, item) {
					if (document.getElementsByClassName('to_fwd')[0])
						document.getElementsByClassName('to_fwd')[0].remove();

					let fwdForm = pages.elements.buildFwdItem(chat.selectedMessages);
					inputForm.insertAdjacentElement('afterbegin', fwdForm);

					inputForm.getSendItem().enable();
					return actsHeader.close();
				});
				let fwdItem = actsHeader.addItem(unt.Icon.FWD, 'fwd', function () {
					let selectedMessages = [];
					chat.selectedMessages.forEach(function (messageId) {
						return selectedMessages.push(messageId);
					});

					let internalData = {
						action: 'fwd',
						fwdFrom: chat.peer_id,
						fwdSelected: selectedMessages
					};

					actsHeader.close();
					return ui.go('https://' + window.location.host + '/messages', false, false, false, false, internalData);
				});

				editItem.disable();
				deleteItem.disable();

				inputForm.id = 'messagesInput';
				if (dialogObject.metadata.permissions) {
					if (dialogObject.metadata.permissions.is_muted || dialogObject.metadata.permissions.is_kicked)
						inputForm.setErrorMessage(settings.lang.getValue("cant_chat"));
				}

				if (!dialogObject.chat_info.is_multi_chat) {
					if (!dialogObject.chat_info.data.can_write_messages)
						inputForm.setErrorMessage(settings.lang.getValue("cant_chat"));
				}

				let dialogLoader = document.createElement('div');
				messagesDiv.appendChild(dialogLoader);
				dialogLoader.style.position = "sticky";
				dialogLoader.style.top = "40%";

				let loader = pages.elements.getLoader();
				loader.setArea(40);
				dialogLoader.appendChild(loader);

				chatWindow.appendChild(inputForm);
				menuBody.appendChild(chatWindow);

				ui.bindItems();
				chat.getInfo().then(function (chatObject) {
					chat.getMessages(currentPage).then(function (messagesList) {
						let messagesArray = messagesList.list;
						let pinnedMessages = messagesList.pinned;

						let currentChatWindow = document.getElementById('messages_' + workedUrl.s);
						if (internalData) {
							if (internalData.action === "fwd") {
								let fwdFrom = internalData.fwdFrom;
								let selection = internalData.fwdSelected;

								let newSelection = new Selection();
								selection.forEach(function (messageId) {
									newSelection.push(messageId);
								});

								chat.selectedMessages = newSelection;
								chat.fwdPeer = internalData.fwdFrom;

								if (document.getElementsByClassName('to_fwd')[0])
									document.getElementsByClassName('to_fwd')[0].remove();

								let fwdForm = pages.elements.buildFwdItem(chat.selectedMessages);
								inputForm.insertAdjacentElement('afterbegin', fwdForm);

								actsHeader.close();
								inputForm.getSendItem().enable();
							}
						}

						let lastMessage = null;
						for (let i = 0; i < messagesArray.length; i++) {
							let message = messagesArray[i];

							let messageElement = messages.elements.message(message);
							messageElement.onclick = function (event) {
								return codes.callbacks.messageCallback(this, chat, actsHeader, chatHeader, event);
							}

							let time = pages.parsers.time(message.time, false, true);

							let createdDate = chat.createdDates[time];
							if (!createdDate) {
								let line = document.createElement('line');
								line.innerHTML = time;

								if (currentChatWindow) currentChatWindow.appendChild(line);
								chat.createdDates[time] = time;
							}

							if (currentChatWindow) currentChatWindow.appendChild(messageElement);

							lastMessage = message;
						}

						if (currentPage === 1) {
							if (lastMessage && lastMessage.keyboard && !chat.keyboard) {
								chat.keyboard = {
									elements: {
										keyboard: pages.elements.buildKeyboard(lastMessage.keyboard.keyboard, chat)
									},
									params: lastMessage.keyboard.params,
									items: lastMessage.keyboard.keyboard
								};
							}
							
							if (chat.keyboard) {
								let currentInputElement = document.getElementsByClassName('chat-keyboard-toggle')[0];

								currentInputElement.show();
								if (chat.keyboard.params.autoShow) {
									currentInputElement.click();
								}
							}
						}

						chat.createdDates = {};

						loader.hide();
						currentChatWindow.scrollTo(0, currentChatWindow.scrollHeight);
						
						return ui.bindItems();
					})
				})
			})
		} else {
			document.title = "yunNet. - " + settings.lang.getValue("messages");

			ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("messages") : null;
			if (internalData) {
				if (internalData.action === 'fwd') {
					menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue("select_a_dialog"), function (event) {
						event.preventDefault();

						return ui.go('https://' + window.location.host + '/messages?s=' + internalData.fwdFrom);
					}));
				}
				if (internalData.action === 'invite_to_chat') {
					menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue("invite_to_chat"), function (event) {
						event.preventDefault();

						return ui.go(null, true);
					}));
				}
			} else {
				let FAB = pages.elements.createFAB(unt.Icon.ADD, null, [
					[unt.Icon.EDIT, function (event, item) {
						let internalDataNew = {
							subaction: 'write'
						}

						return ui.go('https://' + window.location.host + '/friends', false, false, false, true, internalDataNew);
					}],
					[unt.Icon.FRIENDS, function () {
						return pages.chats.create();
					}]
				]);

				menuBody.appendChild(FAB);
			}

			let loader = pages.elements.getLoader();
			let messagesDiv = document.createElement('div');

			messagesDiv.id = "messages_list";
			menuBody.appendChild(messagesDiv);

			let isLoading = false;

			let loadMoreButton = pages.elements.createButton(unt.Icon.ADD, settings.lang.getValue('load_more'), function () {
				loadMessages(currentPage++);
			});

			loadMoreButton.style.display = 'none';
			menuBody.appendChild(loadMoreButton)
			/*menuBody.onscroll = function () {
				if (isLoading) return;

				if (window.scrollY === (document.body.scrollHeight - document.body.offsetHeight)) {
					isLoading = true;

					loader.show();
					loadMessages(currentPage++);
				}
			}*/

			loader.style.marginTop = "10px";
			menuBody.appendChild(loader);

			messagesDiv.classList.add('collection');
			messagesDiv.classList.add('card');

			ui.bindItems();
			loadMessages(currentPage++, internalData && internalData.action === 'invite_to_chat');

			function loadMessages (currentPage = 1, onlyChats = false) {
				return messages.get((((Number(currentPage) > 0 ? Number(currentPage) : 1) - 1) * 30), 30, onlyChats).then(function (messagesList) {
					if (messagesList.length > 0) {
						for (let i = 0; i < messagesList.length; i++) {
							let chat = messagesList[i];
							let element = pages.elements.messages.dialog(chat, {
								onContext: function (dialogItem, event, chatObject) {
									let contextMenu = pages.elements.contextMenu([[settings.lang.getValue("clear_messages_history"), function (event) {
										return pages.elements.confirm('', settings.lang.getValue('chat_clear_confirmation'), function (response) {
											if (response) 
												return messages.utils.getChatInstance(chat.peer_id || ("b" + (chat.bot_peer_id * -1))).clearHistory().catch(function (err) {
													return unt.toast({html: settings.lang.getValue('upload_error')});
												});
										});
									}]]);

									if (contextMenu.open) return contextMenu.open(event);
								}
							}, internalData);

							if (internalData && internalData.action === 'invite_to_chat') 
								element.onclick = function (event) {
									event.preventDefault();

									pages.elements.loadingMode();
									return messages.utils.getChatInstance(chat.peer_id || ("b" + (chat.bot_peer_id * -1))).addMembers([internalData.user_id]).then(function (response) {
										pages.elements.loadingMode().getInstance().close();

										if (response) {
											return ui.go("https://" + window.location.host + "/messages?s=" + (chat.peer_id || ("b" + (chat.bot_peer_id * -1))));
										}
									}).catch(function (err) {
										let code = err.errorCode;
										let errorString = settings.lang.getValue('upload_error');

										if (code === 2) errorString = settings.lang.getValue('level_error');
										if (code === 3) errorString = settings.lang.getValue('uninvitable_1');
										if (code === 4) errorString = settings.lang.getValue('uninvitable_2');

										pages.elements.loadingMode().getInstance().close();

										return unt.toast({html: errorString});
									})
								}

							if (element) messagesDiv.appendChild(element);
						}

						if (messagesList.length >= 20)
							loadMoreButton.style.display = '';
						else
							loadMoreButton.style.display = 'none';

						isLoading = false;
					}

					if (messagesList.length <= 0 && currentPage === 1) {
						messagesDiv.style.display = 'none';

						let noMessages = pages.elements.alertWindow(unt.Icon.CLEAR, (settings.lang.getValue("no_messages")), (settings.lang.getValue("no_messages_text")));
						noMessages.id = 'no_messages';

						messagesDiv.parentNode.appendChild(noMessages);
					}

					ui.bindItems();
					return loader.hide();
				}).catch(function (err) {
					messagesDiv.style.display = 'none';

					let uploadError = pages.elements.uploadError();
					messagesDiv.parentNode.appendChild(uploadError);

					ui.bindItems();
					return loader.hide();
				});
			}
		}
	},

	elements: {
		buildKeyboard: function (keyboard, chat = false) {
			let element = document.createElement('div');
			element.style.padding = '7px';

			keyboard.forEach(function (row) {
				let rowDiv = document.createElement('div');
				rowDiv.classList.add('valign-wrapper');

				row.forEach(function (keyboardElement) {
					let keyboardDiv = document.createElement('a');
					keyboardDiv.classList = ['card unselectable waves-effect'];

					keyboardDiv.style.width = '100%';
					keyboardDiv.style.margin = 4 + 'px';
					keyboardDiv.style.textAlign = 'center';
					keyboardDiv.style.padding = '10px';
					keyboardDiv.style.backgroundColor = ('#' + keyboardElement.color[0] + keyboardElement.color[1] + keyboardElement.color[2]);
					keyboardDiv.style.color = ('#' + keyboardElement.textColor[0] + keyboardElement.textColor[1] + keyboardElement.textColor[2]);
					keyboardDiv.innerText = keyboardElement.text;

					keyboardDiv.style.cursor = 'pointer';
					rowDiv.appendChild(keyboardDiv);

					if (chat) {
						keyboardDiv.addEventListener('click', function () {
							chat.sendMessage(keyboardElement.text, '', '');
						});
					}
				});

				element.appendChild(rowDiv);
			});

			return element;
		},
		alert: function (alertText) {
			return new Promise(function (resolve) {
				let windowElement = pages.elements.createWindow();

				let content = windowElement.getContent();
				content.innerHTML = alertText;

				let footer = windowElement.getFooter();

				let okButton = document.createElement('a');
				okButton.classList = ['btn btn-flat waves-effect modal-close'];
				okButton.innerText = 'OK';

				okButton.onclick = function () {
					return resolve();
				}

				footer.appendChild(okButton);

				return windowElement;
			});
		},
		createPollElement: function (pollObject, afterDeletionCallback) {
			let element = document.createElement('div');
			element.classList.add('card');

			element.style = 'background-color: #818181 !important';
			element.style.textAlign = 'center';
			element.style.cursor = 'pointer';
			element.style.padding = '5px';
			element.style.color = 'white';
			element.style.margin = 0;

			element.onclick = function (event) {
				return pages.elements.contextMenu([
					[
						settings.lang.getValue('edit'),
						new Function()
					], [
						settings.lang.getValue('delete'),
						function () {
							element.remove();

							if (afterDeletionCallback)
								afterDeletionCallback();
						}
					]
				]).open(event);
			}

			let textDiv = document.createElement('div');
			textDiv.style.width = textDiv.style.height = '100%';

			let textDiv2 = document.createElement('div');
			textDiv2.innerText = settings.lang.getValue('poll');

			textDiv.appendChild(textDiv2);
			element.appendChild(textDiv);

			textDiv2.style.position = 'absolute';
			textDiv2.style.top = '45%';
			textDiv2.style.left = '50%';
			textDiv2.style.marginRight = '-50%';
			textDiv2.style.transform = 'translate(-50%, -50%)';

			element.style.width = element.style.height = (54 + 'px');
			element.setAttribute('attachment', 'poll' + pollObject.owner_id + '_' + pollObject.id + '_' + pollObject.access_key);

			return element;
		},
		loadingMode: function () {
			if (pages.elements.loadingMode.already) return pages.elements.loadingMode.preloader;

			let element = document.createElement('div');
			element.classList.add('modal');

			if (ui.isMobile()) element.classList.add('bottom-sheet');
			pages.elements.menuBody().appendChild(element);

			let content = document.createElement('div');
			content.classList.add('modal-content');
			element.appendChild(content);

			let dataDiv = document.createElement('div');
			content.appendChild(dataDiv);
			dataDiv.classList.add('valign-wrapper');

			let loader = pages.elements.getLoader();
			dataDiv.appendChild(loader);

			let textDiv = document.createElement('div');
			dataDiv.appendChild(textDiv);
			textDiv.innerText = settings.lang.getValue('loading');

			textDiv.style.marginLeft = '15px';
			textDiv.style.marginBottom = '5px';

			let instance = unt.Modal.init(element, {
				startingTop: '35%',
				endingTop: '40%',
				dismissible: false,
				onCloseEnd: function () {
					pages.elements.loadingMode.already = false;
					pages.elements.loadingMode.preloader = undefined;

					return element.remove();
				}
			});

			if (instance) {
				element.getInstance = function () {
					return instance;
				}

				instance.open();
			}

			pages.elements.loadingMode.already = true;
			pages.elements.loadingMode.preloader = element;

			return element;
		},
		tokenItem: function (tokenObject, onedit) {
			let element = document.createElement('div');

			element.classList.add('card');
			element.classList.add('collection-item');
			element.classList.add('full_section');
			element.classList.add('valign-wrapper');

			let tokenInfoDiv = document.createElement('div');
			element.appendChild(tokenInfoDiv);

			tokenInfoDiv.style.width = '100%';

			let type = 'bots';

			if (tokenObject.app_id === 0) {
				tokenInfoDiv.innerText = 'Owner: ' + (tokenObject.owner.name || tokenObject.owner.first_name + ' ' + tokenObject.owner.last_name);
			} else {
				type = 'apps';
				tokenInfoDiv.innerText = 'APP ID:' + tokenObject.app_id;
			}

			let editButton = document.createElement('a');

			editButton.style.marginTop = '4px';
			editButton.style.cursor = 'pointer';
			editButton.innerHTML = unt.Icon.EDIT;
			element.appendChild(editButton);

			editButton.onclick = function (event) {
				if (onedit) {
					return onedit(event);
				}
			}

			return element;
		},
		createChecker: function (text, oncheck) {
			let element = document.createElement('form');
			element.action = '#';

			let p = document.createElement('p');
			element.appendChild(p);

			let label = document.createElement('label');
			p.appendChild(label);

			let input = document.createElement('input');
			label.appendChild(input);
			input.type = 'checkbox';
			input.classList.add('filled-in');

			let span = document.createElement('span');
			label.appendChild(span);
			span.innerText = text || '';
			input.oninput = function (event) {
				if (oncheck) return oncheck(event, element);
			}

			element.setChecked = function (checked) {
				input.checked = checked || false;

				return element;
			}

			element.checked = function () {
				return input.checked;
			}

			element.setDisabled = function (disabled) {
				input.disabled = disabled || false;

				return element;
			}

			return element;
		},
		createTabs: function (items = []) {
			let element = document.createElement('ul');
			element.classList = ['tabs card'];

			element.style.margin = 0;
			element.style.width = 'fit-content';
			element.style.marginTop = '5px';
			element.style.marginBottom = '-8px';

			items.forEach(function (item) {
				let text = item[0];
				let callback = item[1];

				let li = document.createElement('li');
				li.classList.add('tab');

				element.appendChild(li);

				let a = document.createElement('a');
				li.appendChild(a);
				a.onclick = function (event) {
					return callback(event, li);
				}

				a.style.cursor = 'pointer';
				a.style.maxWidth = '120px';
				a.innerText = text || '';
			});

			return element;
		},
		createWindow: function () {
			let element = document.createElement('div');
			element.classList.add('modal');

			if (ui.isMobile())
				element.classList.add('bottom-sheet');

			let modalContent = document.createElement('div');
			modalContent.classList.add('modal-content');
			element.appendChild(modalContent);

			let modalFooter = document.createElement('div');
			modalFooter.classList.add('modal-footer');
			element.appendChild(modalFooter);

			element.getContent = function () {
				return modalContent;
			}

			element.getFooter = function () {
				return modalFooter;
			}

			pages.elements.menuBody().appendChild(element);

			let instance = unt.Modal.init(element, {
				onCloseEnd: function () {
					return element.remove();
				}
			});

			instance.open();
			element.getInstance = function () {
				return instance;
			}

			return element;
		},
		setupCounters: function (counters) {
			return new Promise(function (resolve) {
				if (typeof counters !== "object") return resolve(false);

				let messagesItem = document.getElementById('messages_item');
				if (messagesItem) {
					if (counters.messages > 0)
						messagesItem.innerText = String(counters.messages);
					else
						messagesItem.innerText = '';
				}

				let notificationsItem = document.getElementById('notifications_item');
				if (notificationsItem) {
					if (counters.notifications > 0)
						notificationsItem.innerText = String(counters.notifications);
					else
						notificationsItem.innerText = '';
				}

				let friendsItem = document.getElementById('friends_item');
				if (friendsItem) {
					if (counters.friends > 0)
						friendsItem.innerText = String(counters.friends);
					else
						friendsItem.innerText = '';
				}

				return resolve(true);
			});
		},
		notification: function (notification) {
			console.log(notification);
			let element = document.createElement('div');
			let noteTime = notification.data.time || Math.floor(new Date() / 1000);

			element.classList.add('card');
			element.classList.add('collection-item');
			element.classList.add('avatar');

			let notificationIcon = notification.data.user_id ? new Image() : document.createElement('i');
			if (notification.data.user_id) {
				settings.users.get(notification.data.user_id).then(function (user) {
					notificationIcon.src = user.photo_url;

					ui.bindItems();
				}).catch(function (err) {
					return;
				})
			}

			notificationIcon.classList.add('circle');

			element.appendChild(notificationIcon);

			let type = notification.type;
			let headerDiv = document.createElement('span');
			headerDiv.classList.add('title');

			element.appendChild(headerDiv);
			let b = document.createElement('b');
			headerDiv.appendChild(b);

			let moreTextDiv = document.createElement('div');
			element.appendChild(moreTextDiv);

			let timeDiv = document.createElement('small');
			element.appendChild(timeDiv);
			timeDiv.innerHTML = pages.parsers.time(noteTime);

			if (type === 'account_login' || type === 'login') {
				b.innerHTML = settings.lang.getValue('note_logged');

				moreTextDiv.innerHTML = settings.lang.getValue('logged_text').replace('%ip%', notification.data.ip);
			}
			if (type === 'deleted_friend') {
				b.innerHTML = settings.lang.getValue('you_deleted_from_friends');

				settings.users.get(notification.data.user_id).then(function (user) {
					moreTextDiv.innerHTML = settings.lang.getValue('deleted_you_friend')
												.replace('%usernick%', user.is_deleted ? settings.lang.getValue('deleted_account') : ('<a href="/' + (user.name ? ('bot' + user.bot_id) : ('id' + user.user_id)) + '">' + (user.name || user.first_name + ' ' + user.last_name)) + '</a>')
					
					return ui.bindItems();
				}).catch(function (err) {
					return;
				})
			}
			if (type === 'friendship_requested') {
				b.innerHTML = settings.lang.getValue('friends_adding');

				settings.users.get(notification.data.user_id).then(function (user) {
					moreTextDiv.innerHTML = settings.lang.getValue('want_to_add')
												.replace('%usernick%', user.is_deleted ? settings.lang.getValue('deleted_account') : ('<a href="/' + (user.name ? ('bot' + user.bot_id) : ('id' + user.user_id)) + '">' + (user.name || user.first_name + ' ' + user.last_name)) + '</a>')
					
					return ui.bindItems();
				}).catch(function (err) {
					return;
				})
			}
			if (type === 'friendship_accepted') {
				b.innerHTML = settings.lang.getValue('friends_added');
				settings.users.get(notification.data.user_id).then(function (user) {
					moreTextDiv.innerHTML = settings.lang.getValue('added_you')
												.replace("(а)", user.gender === 2 ? "а" : "")
												.replace('%usernick%', user.is_deleted ? settings.lang.getValue('deleted_account') : ('<a href="/' + (user.name ? ('bot' + user.bot_id) : ('id' + user.user_id)) + '">' + (user.name || user.first_name + ' ' + user.last_name)) + '</a>')

					return ui.bindItems();
				}).catch(function (err) {
					return;
				})
			}
			if (type === 'post_like') {
				b.innerHTML = settings.lang.getValue('post_like');
				settings.users.get(notification.data.user_id).then(function (user) {
					moreTextDiv.innerHTML = settings.lang.getValue('post_liked')
												.replace("(а)", user.gender === 2 ? "а" : "")
												.replace('%username%', user.is_deleted ? settings.lang.getValue('deleted_account') : ('<a href="/' + (user.name ? ('bot' + user.bot_id) : ('id' + user.user_id)) + '">' + (user.name || user.first_name + ' ' + user.last_name)) + '</a>')
												.replace('%wall%', '/wall' + notification.data.data.wall_id + '_' + notification.data.data.post_id);
				
					return ui.bindItems();
				}).catch(function (err) {
					return;
				})
			}
			if (type === 'photo_like')
			{
				b.innerHTML = settings.lang.getValue('photo_like');
				settings.users.get(notification.data.user_id).then(function (user) {
					moreTextDiv.innerHTML = settings.lang.getValue('photo_liked')
												.replace("(а)", user.gender === 2 ? "а" : "")
												.replace('%username%', user.is_deleted ? settings.lang.getValue('deleted_account') : ('<a href="/' + (user.name ? ('bot' + user.bot_id) : ('id' + user.user_id)) + '">' + (user.name || user.first_name + ' ' + user.last_name)) + '</a>')
												.replace('%photo%', '/photo' + notification.data.data.owner_id + '_' + notification.data.data.id + '_' + notification.data.data.access_key);
				
					return ui.bindItems();
				}).catch(function (err) {
					return;
				})
			}

			let actionsItem = document.createElement('div');
			actionsItem.classList.add('secondary-content');
			element.appendChild(actionsItem);

			let actions = document.createElement('div');
			actionsItem.appendChild(actions);
			actions.classList.add('valign-wrapper');

			let hideItem = document.createElement('div');
			actions.appendChild(hideItem);
			hideItem.innerHTML = unt.Icon.DONE_ALL;
			hideItem.style.cursor = 'pointer';
			hideItem.onclick = function (event) {
				let loader = pages.elements.getLoader();
				loader.classList.add('hide-loader');
				hideItem.appendChild(loader);

				hideItem.getElementsByTagName('svg')[0].style.display = 'none';
				loader.setArea(20);

				return notifications.hide(notification.id);
			}

			if (notification.is_hidden)
				hideItem.style.display = 'none';

			let readItem = document.createElement('div');
			actions.appendChild(readItem);
			readItem.innerHTML = unt.Icon.CLOSE;
			readItem.style.cursor = 'pointer';
			readItem.style.marginLeft = '10px';
			readItem.onclick = function (event) {
				let loader = pages.elements.getLoader();
				loader.classList.add('read-loader');
				readItem.appendChild(loader);

				readItem.getElementsByTagName('svg')[0].style.display = 'none';
				loader.setArea(20);

				return notifications.read(notification.id);
			}

			element.id = notification.id;
			return element;
		},
		theme: function (theme = null) {
			let element = document.createElement('div');
			element.style.cursor = 'pointer';

			element.classList = ['card collection-item'];

			let dataContainer = document.createElement('div');
			dataContainer.classList.add('valign-wrapper');

			element.appendChild(dataContainer);

			let nameDiv = document.createElement('div');
			nameDiv.style.width = '100%';

			dataContainer.appendChild(nameDiv);

			nameDiv.innerHTML = theme ? theme.data.title : 'Default theme';

			let currentTheme = settings.get().theming.current_theme;
			let credentials = theme ? ('theme' + theme.owner_id + '_' + theme.id) : null;

			let contextMenuArray = [];
			if (currentTheme !== credentials) {
				contextMenuArray.push([
					settings.lang.getValue('apply'),
					function () {
						load.style.display = '';
						load_indicator.style.display = '';
						load_text_info.innerText = settings.lang.getValue('theme_updating');

						return themes.apply(credentials);
					}
				])
			}

			if (theme && theme.id !== 1) {
				contextMenuArray.push([
					settings.lang.getValue('delete'),
					function () {
						return pages.elements.confirm('', settings.lang.getValue('delete_theme') + '?', function (response) {
							if (response) {
								return themes.delete(theme.owner_id, theme.id).then(function () {
									return ui.go(window.location.href, false, true, false, true);
								}).catch(function (err) {
									return ui.go({html: settings.lang.getValue('upload_error')});
								});
							}
						})
					}
				])
			}

			if (theme && theme.owner_id === settings.users.current.user_id) {
				contextMenuArray.push([
					settings.lang.getValue('edit'),
					function () {
						return ui.go('https://' + window.location.host + '/themes?section=installed&mode=edit', false, false, false, true, {
							theme: theme
						});
					}
				])
			}

			element.themeData = credentials || null;
			if (currentTheme === credentials) {
				let appliedIcon = document.createElement('div');
				appliedIcon.innerHTML = unt.Icon.SAVE;
				appliedIcon.style.marginTop = '5px';

				appliedIcon.getElementsByTagName('svg')[0].classList.add('appliedThemeIcon');

				dataContainer.appendChild(appliedIcon);
			} else {
				dataContainer.style.paddingBottom = dataContainer.style.paddingTop = '10px';
			}

			element.oncontextmenu = element.onclick = 
			function (event) {
				event.preventDefault();
				if (contextMenuArray.length <= 0) return;

				return pages.elements.contextMenu(contextMenuArray).open(event);
			}

			return element;
		},
		audioItem: function (audioItem, player = null, miniPlayer = null) {
			let element = document.createElement('div');
			element.classList = ['card collection-item avatar valign-wrapper'];

			element.setAttribute('internalData', audioItem.service.internal_credentials);
			element.style.paddingLeft = '20px';

			element.boundAudio = audios.audiosList[audioItem.service.internal_credentials] || new Audio(audioItem.url);
			element.boundAudio.boundObject = audioItem;

			if (!audios.audiosList[audioItem.service.internal_credentials]) {
				audios.audiosList[audioItem.service.internal_credentials] = element.boundAudio;
				audios.audioIndex.push(audioItem.service.internal_credentials);
			}

			let controlButton = pages.elements.createFAB(unt.Icon.PLAY, new Function());
			controlButton.classList.remove('fixed-action-btn');

			controlButton.classList = ['scale-transition scale-out'];
			if (element.boundAudio.readyState === 4)
				controlButton.classList.remove('scale-out');

			element.boundAudio.addEventListener('canplaythrough', function (event) {
				controlButton.classList.remove('scale-out');
			});

			controlButton.onclick = function () {
				return element.boundAudio.paused ? element.boundAudio.play() : element.boundAudio.pause();
			}

			element.boundAudio.addEventListener('pause', function (event) {
				if (player) {
					if (audios.audiosList[audios.current].paused)
						player.getControlButton().setIcon(unt.Icon.PLAY);
				}

				if (miniPlayer) {
					if (audios.audiosList[audios.current].paused) {
						miniPlayer.getControlButton().setIcon(unt.Icon.PLAY);
						miniPlayer.style.display = '';
					}
				}

				controlButton.setIcon(unt.Icon.PLAY);
			})

			element.boundAudio.addEventListener('ended', function (event) {
				if (player) {
					player.style.display = 'none';
					player.getControlButton().setIcon(unt.Icon.PLAY);
				}

				if (miniPlayer) {
					miniPlayer.getControlButton().setIcon(unt.Icon.PLAY);
					miniPlayer.style.display = 'none';
				}

				audios.current = null;

				audios.playNext(audioItem.service.internal_credentials);
				controlButton.setIcon(unt.Icon.PLAY);
			});

			element.boundAudio.addEventListener('play', function (event) {
				audios.pauseAll(audioItem.service.internal_credentials);

				if (player) {
					player.getControlButton().setIcon(unt.Icon.PAUSE);
					player.style.display = '';

					player.setArtist(audioItem.artist);
					player.setTitle(audioItem.title);
				}

				if (miniPlayer) {
					miniPlayer.getControlButton().setIcon(unt.Icon.PAUSE);
					miniPlayer.style.display = '';
					miniPlayer.setData(audioItem.title + ' - ' + audioItem.artist);
				}

				audios.current = audioItem.service.internal_credentials;
				controlButton.setIcon(unt.Icon.PAUSE);

				player.getRange().getInput().setAttribute('max', String(audios.audiosList[audios.current].duration))
			});

			element.boundAudio.addEventListener('timeupdate', function (event) {
				if (player && audios.current === audioItem.service.internal_credentials) {
					player.getRange().getInput().value = Number(audios.audiosList[audios.current].currentTime);
				
					player.getMaxTime().innerText = pages.parsers.durations(parseInt(audios.audiosList[audios.current].currentTime))
				}
			});

			if (player) {
				if (audios.current) {
					if (!audios.audiosList[audios.current].paused) {
						player.getControlButton().setIcon(unt.Icon.PAUSE);
					}

					player.setArtist(audios.audiosList[audios.current].boundObject.artist);
					player.setTitle(audios.audiosList[audios.current].boundObject.title);

					player.getRange().getInput().setAttribute('max', String(audios.audiosList[audios.current].duration))
				}

				player.getRange().getInput().oninput = function (event) {
					audios.audiosList[audios.current].currentTime = Number(this.value);
				}
			}

			element.appendChild(controlButton);

			let songData = document.createElement('div');
			let span = document.createElement('span');
			span.classList.add('title');

			songData.appendChild(span);
			let bTitle = document.createElement('b');
			span.appendChild(bTitle);
			bTitle.innerText = audioItem.title;

			let p = document.createElement('p');

			p.classList.add('hidet');
			songData.appendChild(p);

			p.innerText = audioItem.artist;
			songData.style.marginLeft = '15px';
			element.appendChild(songData);

			let secContent = document.createElement('div');
			secContent.classList.add('secondary-content');
			element.appendChild(secContent);

			secContent.innerText = pages.parsers.durations(audioItem.duration);
			if (audios.current === audioItem.service.internal_credentials) {
				controlButton.setIcon(unt.Icon.PAUSE);
			}

			return element;
		},
		createInputField: function (text, active = false) {
			let inputField = document.createElement('div');
			inputField.classList.add('input-field');

			let input = document.createElement('input');
			inputField.appendChild(input);
			input.type = 'text';

			let identifier = 'input_' + getRandomInt(100, 10000000);
			input.id = identifier;

			let innLabel = document.createElement('label');
			if (active) innLabel.classList.add('active');

			innLabel.innerText = text;
			innLabel.setAttribute('for', identifier);
			inputField.appendChild(innLabel);

			inputField.getValue = function () {
				return input.value;
			}

			inputField.setDisabled = function (disabled) {
				input.disabled = disabled || false;

				return inputField;
			}

			inputField.setType = function (type) {
				input.type = type;

				return inputField;
			}

			inputField.getInput = function () {
				return input;
			}

			inputField.maxLength = function (length) {
				input.setAttribute('maxlength', String(length));

				return inputField;
			}

			inputField.setText = function (text) {
				if (Number(input.getAttribute('maxlength')) <= String(text).length)
					input.value = text;

				return inputField;
			}

			inputField.setReadOnly = function (readonly = true) {
				if (readonly)
					input.setAttribute('readonly', 'true');
				else
					input.removeAttribute('readonly');

				return inputField;
			}

			return inputField;
		},
		uploadError: function () {
			let uploadError = document.createElement('div');
			uploadError.classList = ['card full_section'];

			let currentOutDiv = document.createElement('div');
			uploadError.appendChild(currentOutDiv);

			currentOutDiv.classList.add('valign-wrapper');

			let errorText = document.createElement('div');
			currentOutDiv.appendChild(errorText);

			errorText.innerText = settings.lang.getValue('upload_error');
			errorText.style.width = '100%';

			let refreshDiv = document.createElement('div');
			currentOutDiv.appendChild(refreshDiv);

			let button = pages.elements.createFAB(unt.Icon.REFRESH, function () {
				return ui.go(window.location.href, false, true, false, true, {});
			});

			button.classList.remove('fixed-action-btn');
			refreshDiv.appendChild(button);

			return uploadError;
		},
		createSwitchButton: function (items = []) {
			let element = document.createElement('ul');
			element.classList.add('collapsible');

			items.forEach(function (item, index) {
				let inLi = document.createElement('li');
				element.appendChild(inLi);

				let header = document.createElement('div');
				header.classList.add('collapsible-header');
				header.classList.add('alink-name');
				inLi.appendChild(header);

				header.style = 'align-items: center; position: relative';
				header.innerHTML = item[0];

				let textDiv = document.createElement('div');
				header.appendChild(textDiv);
				textDiv.style.marginLeft = '8px';
				textDiv.innerText = item[1];

				let switchDiv = document.createElement('div');
				header.appendChild(switchDiv);
				switchDiv.classList.add('switch');
				switchDiv.style = 'right: 0; margin-bottom: 5px; position: absolute';

				let label = document.createElement('label');
				switchDiv.appendChild(label);

				let input = document.createElement('input');
				label.appendChild(input);

				input.type = 'checkbox';
				input.oninput = function (event) {
					return item[2](event, input);
				}

				let span = document.createElement('span');
				span.classList.add('lever');
				label.appendChild(span);

				input.checked = Boolean(item[3]);
			});

			element.selectItem = function (index, state) {
				let item = element.getElementsByClassName('collapsible-header')[index];

				if (item) {
					let input = item.getElementsByTagName('input')[0];
					if (input) {
						input.checked = state;
					}
				}

				return element;
			}

			element.disable = function (index) {
				let item = element.getElementsByClassName('collapsible-header')[index];

				if (item) {
					let input = item.getElementsByTagName('input')[0];
					if (input) {
						input.disabled = true;
					}
				}

				return element;
			}

			element.enable = function (index) {
				let item = element.getElementsByClassName('collapsible-header')[index];

				if (item) {
					let input = item.getElementsByTagName('input')[0];
					if (input) {
						input.disabled = false;
					}
				}

				return element;
			}

			return element;
		},
		createSelector: function (groupName = 'default', items = []) {
			let element = document.createElement('div');
			element.classList.add('collection');

			items.forEach(function (item, index) {
				let p = document.createElement('p');
				element.appendChild(p);

				let label = document.createElement('label');
				p.appendChild(label);

				let input = document.createElement('input');
				label.appendChild(input);

				input.classList.add('with-gap');
				input.type = 'radio';
				input.name = groupName;

				let span = document.createElement('span');
				label.appendChild(span);
				span.innerText = item[0];
				input.oninput = function (event) {
					return item[1](event, p);
				};
			});

			element.selectItem = function (index) {
				let elementCurrent = element.getElementsByTagName('p')[index];
				if (elementCurrent) {
					let input = elementCurrent.getElementsByTagName('input')[0];
					if (input) {
						input.setAttribute('checked', true);
					}
				}

				return element;
			}

			return element;
		},
		createCollapsible: function (items = []) {
			let collapsible = document.createElement('ul');
			collapsible.classList.add('collapsible');

			items.forEach(function (item, index) {
				let inli = document.createElement('li');
				collapsible.appendChild(inli);

				let header = document.createElement('div');
				header.classList.add('collapsible-header');
				inli.appendChild(header);

				let body = document.createElement('div');
				body.classList.add('collapsible-body');
				inli.appendChild(body);

				header.innerHTML = item[0];
				let textDiv = document.createElement('div');
					
				textDiv.style.marginLeft = '10px';
				textDiv.style.marginTop = '1px';

				header.appendChild(textDiv);
				textDiv.innerText = item[1];
			});

			collapsible.getHeader = function (index) {
				let items = collapsible.getElementsByClassName('collapsible-header');

				return items[index] || null;
			}
			collapsible.getBody = function (index) {
				let items = collapsible.getElementsByClassName('collapsible-body');

				return items[index] || null;
			}

			return collapsible;
		},
		createButton: function (icon, text, onclickHandler, textColor = 'black', itemGroup = []) {
			let managementUl = document.createElement('ul');

			managementUl.classList.add('collapsible');

			let inli = document.createElement('li');
			managementUl.appendChild(inli);

			if (itemGroup.length > 0) {
				itemGroup.forEach(function (item, index) {
					let header = document.createElement('div');
					inli.appendChild(header);
					header.classList.add('collapsible-header');

					if (!String(icon).isEmpty()) header.innerHTML = item[0];

					let textDiv = document.createElement('div');
					header.appendChild(textDiv);

					textDiv.innerHTML = item[1];

					if (!String(icon).isEmpty()) textDiv.style.marginLeft = '10px';
					textDiv.style.marginTop = '1px';
					textDiv.style.color = item[3] || 'black';

					header.onclick = function (event) {
						return item[2] ? item[2](event) : null;
					}
				});
			} else {
				let header = document.createElement('div');
				inli.appendChild(header);
				header.classList.add('collapsible-header');

				if (!String(icon).isEmpty()) header.innerHTML = icon;

				let textDiv = document.createElement('div');
				header.appendChild(textDiv);

				textDiv.innerHTML = text;

				if (!String(icon).isEmpty()) textDiv.style.marginLeft = '10px';
				textDiv.style.marginTop = '1px';
				textDiv.style.color = textColor;

				managementUl.onclick = function (event) {
					return onclickHandler ? onclickHandler(event) : null;
				}
			}

			return managementUl;
		},
		createFAB: function (icon, callback, items = []) {
			let element = document.createElement('div');
			element.classList.add('fixed-action-btn');

			let mainButton = document.createElement('a');
			element.appendChild(mainButton);

			mainButton.classList = ['btn-floating btn-large waves-effect waves-light ' + ['red', 'yellow', 'green', 'blue'][getRandomInt(0, 2)]];
			mainButton.innerHTML = icon;

			mainButton.getElementsByTagName('svg')[0].style.fill = 'white';
			mainButton.getElementsByTagName('svg')[0].style.marginTop = '28%';

			mainButton.onclick = function (event) {
				if (!callback) return;

				return callback(event, mainButton);
			}

			let buttonItems = document.createElement('ul');
			if (items.length > 0) {
				element.appendChild(buttonItems);

				items.forEach(function (item) {
					let icon = item[0];
					let clbk = item[1];

					if (!icon || !clbk) return;

					let elementLi = document.createElement('li');
					buttonItems.appendChild(elementLi);

					let innerA = document.createElement('a');
					elementLi.appendChild(innerA);
					innerA.classList = ['btn-floating waves-effect waves-light ' + ['red', 'yellow', 'green'][getRandomInt(0, 2)]];

					innerA.innerHTML = icon;
					innerA.getElementsByTagName('svg')[0].style.fill = 'white';
					innerA.getElementsByTagName('svg')[0].style.marginTop = '20%';

					elementLi.onclick = function (event) {
						return clbk(event, elementLi);
					}
				});
			}

			element.setIcon = function (icon) {
				mainButton.innerHTML = icon;

				mainButton.getElementsByTagName('svg')[0].style.fill = 'white';
				mainButton.getElementsByTagName('svg')[0].style.marginTop = '28%';

				return element;
			}

			return element;
		},
		appItem: function (app) {
			let element = document.createElement('a');
			element.classList = ['card collection-item avatar'];

			element.href = '/app' + app.id;

			let img = document.createElement('img');
			img.classList.add('circle');
			element.appendChild(img);

			img.src = app.photo_url;

			let appTitle = document.createElement('span');
			appTitle.classList.add('title');
			let b = document.createElement('b');
			appTitle.appendChild(b);
			b.innerHTML = app.title || '';

			element.appendChild(appTitle);

			let appInfo = document.createElement('p');
			element.appendChild(appInfo);
			appInfo.innerText = 'APP ID: ' + app.id;

			return element;
		},
		userItem: function (userObject) {
			let element = document.createElement('a');

			element.classList = ['card collection-item avatar'];

			let userImage = document.createElement('img');
			element.appendChild(userImage);

			userImage.src = userObject.photo_url;
			if (!userObject.account_type) {
				userImage.src = 'https://dev.yunnet.ru/images/default.png';
			}

			userImage.classList.add('circle');

			let titleSpan = document.createElement('span');

			titleSpan.classList.add('title');
			let innerB = document.createElement('b');

			titleSpan.appendChild(innerB);
			innerB.innerText = userObject.name || (userObject.first_name + ' ' + userObject.last_name);
			if (!userObject.account_type) {
				innerB.innerText = settings.lang.getValue("deleted_account");	
			}

			if (userObject.is_verified) {
				titleSpan.classList.add('valign-wrapper');
				let iconDiv = document.createElement('div');

				titleSpan.appendChild(iconDiv);
				iconDiv.style.marginLeft = '5px';
				iconDiv.innerHTML = unt.Icon.PALETTE_ANIM;

				iconDiv.getElementsByTagName('svg')[0].height.baseVal.value = 15;
				iconDiv.getElementsByTagName('svg')[0].width.baseVal.value = 15;
			}

			element.appendChild(titleSpan);
			element.href = userObject.account_type === 'bot' ? ('/bot' + userObject.bot_id) : ('/id' + userObject.user_id);

			if (!userObject.account_type) {
				element.href = userObject.user_id ? ('/id' + userObject.user_id) : ('/bot' + userObject.bot_id);
			}

			let onlineString = pages.parsers.getOnlineState(userObject);
			if (!userObject.account_type) {
				onlineString = '';	
			}

			let infoP = document.createElement('p');
			element.appendChild(infoP);
			infoP.innerText = onlineString;

			return element;
		},
		buildRightMenu: function () {
			let menuElement = document.createElement('div');
			let titleDiv = null;
			let ulItems = null;

			let newLi = null;
			let navWraperUl = null;

			if (ui.isMobile()) {
				menuElement = document.createElement('ul');
				
				navWraperUl = document.getElementsByClassName('nav-wrapper')[0].getElementsByTagName('ul')[0];
				newLi = document.createElement('li');
				newLi.id = 'dropDownLi';

				let innerUl = document.createElement('ul');
				newLi.appendChild(innerUl);
				let innerUlLi = document.createElement('li');
				innerUl.appendChild(innerUlLi);

				let dropdownTrigger = document.createElement('a');
				dropdownTrigger.classList.add('dropdown-trigger');

				dropdownTrigger.id = 'ui_info';
				dropdownTrigger.setAttribute('data-target', 'ui_items');
				innerUlLi.appendChild(dropdownTrigger);

				dropdownTrigger.style.padding = 0;
				dropdownTrigger.style.marginLeft = '-15px';

				let h = document.createElement('h');
				titleDiv = h;
				dropdownTrigger.appendChild(h);

				let i = document.createElement('i');
				i.classList.add('right');
				dropdownTrigger.appendChild(i);
				i.innerHTML = '<svg class="unt_icon" style="fill: white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M7 10l5 5 5-5z"></path><path d="M0 0h24v24H0z" fill="none"></path></svg>';

				ulItems = document.createElement('ul');
				ulItems.classList.add('dropdown-content');
				ulItems.id = 'ui_items';
				innerUlLi.appendChild(ulItems);
			} else {
				menuElement.classList.add('collection');

				menuElement.style.marginRight = '20%';
			}

			menuElement.addItem = function (text, callback) {
				if (!text) throw new TypeError('Text must be provided');
				if (!callback) throw new TypeError('Callback must be provided');

				if (ui.isMobile()) {
					let itemElement = document.createElement('li');
					ulItems.appendChild(itemElement);

					itemElement.select = function () {
						titleDiv.innerText = itemElement.innerText;
					}

					let innerA = document.createElement('a');
					innerA.classList.add('waves-effect');
					innerA.innerText = text;
					itemElement.appendChild(innerA);
					innerA.onclick = function (event) {
						return callback(event, itemElement);
					}

					return itemElement;
				} else {
					let currentCollection = menuElement;
					let itemElement = document.createElement('a');

					itemElement.classList.add('collection-item');
					itemElement.classList.add('waves-effect');

					itemElement.onclick = function (event) {
						return callback(event, itemElement);
					}

					itemElement.innerText = text;
					itemElement.select = function () {
						menuElement.querySelectorAll('.active-left').forEach(function (item) {
							item.classList.remove('active-left');
						});

						itemElement.classList.add('active-left');
						return itemElement;
					}

					currentCollection.appendChild(itemElement);
					return itemElement;
				}
			}

			menuElement.append = function () {
				try {
					if (ui.isMobile()) {
						nav_header_title.innerHTML = '';

						navWraperUl.appendChild(newLi);

						ulItems.style.width = 'unset';
						ulItems.style.height = 'unset';
					} else {
						document.getElementsByClassName('col s3')[1].appendChild(menuElement);
					}
				} catch (e) {}

				return menuElement;
			}

			return menuElement;
		},
		buildFwdItem: function (fwdArray, afterRemoveCallback) {
			let element = document.createElement('div');
			let resultSelect = [];

			fwdArray.forEach(function (messageId) {
				return resultSelect.push(messageId);
			});

			element.fwdSelected = resultSelect;

			element.classList.add('to_fwd');
			element.style.padding = '5px 10px 0 10px';

			let blockq = document.createElement('blockquote');
			element.appendChild(blockq);

			let headerDiv = document.createElement('div');
			blockq.appendChild(headerDiv);

			let innerB = document.createElement('b');
			headerDiv.appendChild(innerB);
			innerB.innerText = settings.lang.getValue('rs');

			let textDiv = document.createElement('div');
			blockq.appendChild(textDiv);
			textDiv.innerHTML = (settings.lang.getValue("selects")) + ": " + fwdArray.length + '/100';
			element.onclick = function (event) {
				element.remove();

				if (afterRemoveCallback) afterRemoveCallback(event);
			}

			element.id = 'fwditem';
			return element;
		},
		hate: function (apiDo) {
			let menuBody = pages.elements.menuBody().clear();
			document.title = 'yunNet.';
			
			if (!ui.isMobile()) {
				let menus = document.getElementsByClassName('col s3');

				let leftMenu = menus[0];
				let rightMenu = menus[1];

				leftMenu.innerHTML = rightMenu.innerHTML = '';
			} else {
				try {
					nav_burger_icon.style.display = 'none';
					nav_header_title.innerText = 'Error message';
				} catch (e) {}
			}

			let headerError = 'Unable to connect to flex API';
			if (apiDo === null) {
				headerError += ': Incorrect response. Do you have internet connection?'
			}
			if (apiDo === true) {
				headerError += ': API is blocked. important data can not load now. Try again later'
			}

			let errorElement = pages.elements.alertWindow(unt.Icon.LOCKED, 'Error', headerError);

			let allLinks = document.querySelectorAll("a");
		    for (let i = 0; i < allLinks.length; i++) {
		      if (!allLinks[i].onclick && !allLinks[i].href.isEmpty()) {
		      	allLinks[i].setAttribute('disabled', 'true');

		        allLinks[i].onclick = function (event) {
		          return event.preventDefault();
		        }
		      }
		    }
			unt.AutoInit();
			if (!ui.isMobile()) document.getElementsByClassName("row")[0].style['justifyContent'] = 'center';

		    try {
		    	site_loader.style.display = "none";
		    	setTimeout(function () {
		       		return load.remove();
		    	}, 1000);
		    } catch (e) {}

		    load_indicator.style.display = 'none';
		    pages.elements.alert('Sorry, but client-side API is temporaly hidden. Please, go later.').then(function (argument) {
		    	load.style.display = 'none';
		    })

			return menuBody.appendChild(errorElement);
		},
		messages: {
			getSubstring: function (chatObject) {
				if (chatObject.chat_info.is_multi_chat) {
					if (chatObject.metadata.permissions) {
						if (chatObject.metadata.permissions.is_leaved) {
							return (settings.lang.getValue("you_leaved"));
						}
						if (chatObject.metadata.permissions.is_kicked) {
							return (settings.lang.getValue("you_kicked"));
						}
					}

					if ((settings.lang.getValue("id")) === "ru")
						return chatObject.chat_info.data.members_count + " " +pages.parsers.morph(chatObject.chat_info.data.members_count, pages.parsers.forms.MEMBERS_RUSSIAN);
					else
						return (chatObject.chat_info.data.members_count > 1) ? (chatObject.chat_info.data.members_count + " member") : (chatObject.chat_info.data.members_count + " members");
				} else {
					if (chatObject.chat_info.is_bot_chat) {
						return (settings.lang.getValue("bot"))
					} else {
						if (!chatObject.chat_info.data.account_type)
							return '...';

						return pages.parsers.getOnlineState(chatObject.chat_info.data);
					}
				}

				return "State is empty.";
			},
			buildEditHeader: function (callback) {
				if (ui.isMobile()) {
					let header = document.createElement('div');
					header.classList.add('nav-wrapper');

					header.id = 'editHeader';
					let ulTable = document.createElement('ul');
					header.appendChild(ulTable);

					let backButton = document.createElement('li');
					ulTable.appendChild(backButton);

					backButton.classList.add('valign-wrapper');

					let aLink = document.createElement('a');
					backButton.appendChild(aLink);
					aLink.classList.add('valign-wrapper');
					aLink.style.width = '100%';

					let i = document.createElement('i');
					aLink.appendChild(i);
					i.innerHTML = unt.Icon.CLOSE;

					aLink.onclick = function (event) {
						if (callback) return callback(event, aLink);
					}

					i.getElementsByTagName('svg')[0].style.fill = 'white';
					i.getElementsByTagName('svg')[0].classList.add('unt_icon');

					let titleDiv = document.createElement('div');

					aLink.appendChild(titleDiv);
					titleDiv.classList.add('credentials');
					titleDiv.innerHTML = settings.lang.getValue("edit");
					header.close = function () {
						return callback(null, header);
					}

					return header;
				} else {
					let header = document.createElement('div');

					header.classList.add("card");
					header.classList.add("valign-wrapper");

					header.style = "padding: 15px; flex-wrap: wrap; width: 100%";

					header.id = 'editHeader';
					let itemsData = document.createElement('div');
					itemsData.classList.add("valign-wrapper");

					itemsData.style.width = '100%';
					header.appendChild(itemsData);

					let closeButton = document.createElement('div');
					closeButton.classList.add('back-btn');
					itemsData.appendChild(closeButton);

					let cButton = document.createElement('a');
					closeButton.appendChild(cButton);
					cButton.classList.add('alink-name');
					cButton.innerHTML = unt.Icon.CLOSE;
					cButton.onclick = function (event) {
						if (callback) return callback(event, cButton);
					}

					let dataCounter = document.createElement('div');
					itemsData.appendChild(dataCounter);
					dataCounter.classList = ['credentials dial-data valign-wrapper'];
					dataCounter.style.width = '100%';

					let innerCounter = document.createElement('div');
					dataCounter.appendChild(innerCounter);
					dataCounter.style.marginBottom = '7px';

					let actionsDiv = document.createElement('div');
					dataCounter.appendChild(actionsDiv);

					actionsDiv.classList.add('valign-wrapper');
					innerCounter.style.width = '100%';
					innerCounter.innerText = settings.lang.getValue("edit");
					header.close = function () {
						return callback(null, header);
					}

					return header;
				}
			},
			buildActionsHeader: function (dataText = '', items = [], callback) {
				if (ui.isMobile()) {
					let header = document.createElement('div');
					header.classList.add('nav-wrapper');

					let ulTable = document.createElement('ul');
					header.appendChild(ulTable);

					let backButton = document.createElement('li');
					ulTable.appendChild(backButton);

					backButton.classList.add('valign-wrapper');

					let aLink = document.createElement('a');
					backButton.appendChild(aLink);
					aLink.classList.add('valign-wrapper');
					aLink.style.width = '100%';

					let i = document.createElement('i');
					aLink.appendChild(i);
					i.innerHTML = unt.Icon.CLOSE;

					aLink.onclick = function (event) {
						if (callback) return callback(event, aLink);
					}

					i.getElementsByTagName('svg')[0].style.fill = 'white';
					i.getElementsByTagName('svg')[0].classList.add('unt_icon');

					let titleDiv = document.createElement('div');

					aLink.appendChild(titleDiv);
					titleDiv.classList.add('credentials');

					header.setText = function (text) {
						titleDiv.innerText = String(text);
					}

					let rightItems = document.createElement('ul');
					header.appendChild(rightItems);
					rightItems.classList.add('right');

					header.addItem = function (icon, itemName, callback) {
						if (!icon) return null;
						if (!callback) return null;

						let itemLI = document.createElement('li');
						rightItems.appendChild(itemLI);
						
						let a = document.createElement('a');
						itemLI.appendChild(a);
						a.href = '#';

						let i = document.createElement('i');
						i.style.marginTop = '2px';

						a.appendChild(i);

						i.innerHTML = icon;
						i.getElementsByTagName('svg')[0].style.fill = 'white';

						a.onclick = function (event) {
							event.preventDefault();

							return callback(event, a);
						}

						a.enable = function () {
							return itemLI.style.display = '';
						}
						a.disable = function () {
							return itemLI.style.display = 'none';
						}

						a.classList.add(itemName);
						return a;
					}

					if (!String(dataText).isEmpty()) header.setText(dataText);
					if (items instanceof Array) {
						items.forEach(function (item) {
							let text = item[0];
							let itemName = item[1];
							let callback = item[2];

							return header.addItem(text, itemName, callback);
						});
					}

					header.close = function () {
						return callback(null, header);
					}

					return header;
				} else {
					let header = document.createElement('div');

					header.classList.add("card");
					header.classList.add("valign-wrapper");

					header.style = "padding: 15px; flex-wrap: wrap; width: 100%";

					let itemsData = document.createElement('div');
					itemsData.classList.add("valign-wrapper");

					itemsData.style.width = '100%';
					header.appendChild(itemsData);

					let closeButton = document.createElement('div');
					closeButton.classList.add('back-btn');
					itemsData.appendChild(closeButton);

					let cButton = document.createElement('a');
					closeButton.appendChild(cButton);
					cButton.classList.add('alink-name');
					cButton.innerHTML = unt.Icon.CLOSE;
					cButton.onclick = function (event) {
						if (callback) return callback(event, cButton);
					}

					let dataCounter = document.createElement('div');
					itemsData.appendChild(dataCounter);
					dataCounter.classList = ['credentials dial-data valign-wrapper'];
					dataCounter.style.width = '100%';

					let innerCounter = document.createElement('div');
					dataCounter.appendChild(innerCounter);
					dataCounter.style.marginBottom = '7px';

					let actionsDiv = document.createElement('div');
					dataCounter.appendChild(actionsDiv);

					actionsDiv.classList.add('valign-wrapper');
					innerCounter.style.width = '100%';

					header.setText = function (text) {
						innerCounter.innerText = String(text);
					}

					header.addItem = function (icon, itemName, callback) {
						if (!icon) return null;
						if (!callback) return null;

						let itemDiv = document.createElement('a');

						itemDiv.href = '#';
						actionsDiv.appendChild(itemDiv);

						itemDiv.classList.add('alink-name');
						itemDiv.innerHTML = icon;

						itemDiv.style.marginLeft = '8px';
						itemDiv.getElementsByTagName('svg')[0].style.marginTop = '25%';

						itemDiv.onclick = function (event) {
							event.preventDefault();

							return callback(event, itemDiv);
						}

						itemDiv.enable = function () {
							return itemDiv.style.display = '';
						};
						itemDiv.disable = function () {
							return itemDiv.style.display = 'none';
						}

						itemDiv.classList.add(itemName);
						return itemDiv;
					}

					if (!String(dataText).isEmpty()) header.setText(dataText);
					if (items instanceof Array) {
						items.forEach(function (item) {
							let text = item[0];
							let itemName = item[1];
							let callback = item[2];

							return header.addItem(text, itemName, callback);
						});
					}

					header.close = function () {
						return callback(null, header);
					}

					return header;
				}
			},
			buildChatHeader: function (chatObject) {
				if (!ui.isMobile()) {
					let header = document.createElement('div');

					header.classList.add("card");
					header.id = 'chatHeader';
					header.style = "padding: 15px; flex-wrap: wrap; width: 100%; margin-bottom: 3px";

					let chatData = document.createElement('div');
					chatData.classList.add("valign-wrapper");

					let backButton = document.createElement('div');

					backButton.classList.add('back-btn');
					chatData.appendChild(backButton);

					let bButton = document.createElement('a');
					backButton.appendChild(bButton);
					bButton.href = '/messages';
					bButton.classList.add("alink-name");
					bButton.innerHTML = '<svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"></path><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path></svg>';

					let chatCredentialsElement = document.createElement('div');
					chatCredentialsElement.classList = ["dial-data credentials full-width valign-wrapper"];

					let infoDiv = document.createElement('div');
					chatCredentialsElement.appendChild(infoDiv);
					infoDiv.style = "min-width: 60px; max-width: 200px";

					let chatTitle = document.createElement('a');
					chatTitle.classList = ["alink-name hidet"];
					infoDiv.appendChild(chatTitle);

					// substring element
					let subString = document.createElement("div");

					subString.classList.add('members-info');
					infoDiv.appendChild(subString);

					let smallText = document.createElement('small');
					subString.appendChild(smallText);

					smallText.style = "color: lightgrey; margin-top: 2px";
					smallText.innerText = pages.elements.messages.getSubstring(chatObject);
					////////////////////

					// typing element
					let typeString = document.createElement("div");
					infoDiv.appendChild(typeString);
					typeString.id = 'typingState';

					let typeSmallText = document.createElement('small');

					typeSmallText.classList.add("valign-wrapper");
					typeString.appendChild(typeSmallText);
					typeSmallText.style = "color: lightgrey; margin-top: 2px";

					typeString.style.display = "none";
					typeSmallText.innerHTML = unt.Icon.TYPING;

					let typeText = document.createElement('div');
					typeSmallText.appendChild(typeText);

					typeText.classList.add("credentials");
					typeText.classList.add("typing-info");
					/////////////////

					// chat photo element
					let chatPhotoContainer = document.createElement("div");
					chatPhotoContainer.classList.add("data-right");

					chatPhotoContainer.style.marginLeft = "auto";
					chatCredentialsElement.appendChild(chatPhotoContainer);

					let img = document.createElement("img");
					chatPhotoContainer.appendChild(img);

					img.alt = "";
					img.classList.add("circle");
					img.classList.add("chat-photo");
					img.height = img.width = 40;
					if (chatObject) img.src = chatObject.chat_info.data.photo_url;
					/////////////////////

					chatTitle.classList.add('chat-title');

					if (chatObject) {
						if (chatObject.chat_info.is_multi_chat) {
							chatTitle.innerText = chatObject.chat_info.data.title;
							chatTitle.href = '/messages?s=' + ((chatObject.peer_id || ('b' + chatObject.bot_peer_id * -1))) + '&action=info';
						} else {
							chatTitle.innerText = chatObject.chat_info.data.name || chatObject.chat_info.data.first_name + " " + chatObject.chat_info.data.last_name;
							if (!chatObject.chat_info.data.account_type) {
								chatTitle.innerText = settings.lang.getValue("deleted_account");
								img.src = 'https://dev.yunnet.ru/images/default.png';
							}

							chatTitle.href = '/' + (chatObject.chat_info.data.account_type === "bot" ? (chatObject.chat_info.data.screen_name || ('bot' + chatObject.chat_info.data.bot_id)) : (chatObject.chat_info.data.screen_name || ('id' + chatObject.chat_info.data.user_id)));
						}
					} else {
						chatTitle.innerText = "Chat";
					}

					chatData.appendChild(chatCredentialsElement);
					let messageActions = document.createElement('div');

					header.appendChild(chatData);
					header.appendChild(messageActions);
					header.close = function () {
						return callback(null, header);
					}

					return header;
				} else {
					let header = document.createElement('li');
					header.id = 'liDifferent';

					document.getElementsByClassName('nav-wrapper')[0].id = "chatHeader";

					let chatData = document.createElement('div');
					chatData.classList.add("valign-wrapper");

					chatData.style.height = "56px";
					chatData.style.width = "250px";

					header.appendChild(chatData);

					let img = document.createElement("img");
					chatData.appendChild(img);

					img.alt = "";
					img.classList.add("circle");
					img.classList.add("chat-photo");
					img.height = img.width = 32;
					if (chatObject) img.src = chatObject.chat_info.data.photo_url;

					let chatLink = document.createElement('a');
					chatData.appendChild(chatLink);

					if (!chatObject.chat_info.is_multi_chat) 
						chatLink.href = '/' + (chatObject.chat_info.is_bot_chat ? ("bot" + (chatObject.bot_peer_id * -1)) : ("id" + (chatObject.peer_id)))
					else
						chatLink.href = '/messages?s=' + chatObject.peer_id + '&action=info';

					let chatInfo = document.createElement('div');
					chatLink.appendChild(chatInfo);
					chatInfo.style.height = "100%";

					let chatTitle = document.createElement("div");
					chatInfo.appendChild(chatTitle);
					chatTitle.style.height = "20px";

					chatTitle.classList.add('chat-title');
					if (chatObject) {
						if (chatObject.chat_info.is_multi_chat) {
							chatTitle.innerText = chatObject.chat_info.data.title;
						} else {
							chatTitle.innerText = chatObject.chat_info.data.name || chatObject.chat_info.data.first_name + " " + chatObject.chat_info.data.last_name;
							if (!chatObject.chat_info.data.account_type) {
								chatTitle.innerText = settings.lang.getValue("deleted_account");
								img.src = 'https://dev.yunnet.ru/images/default.png';
							}
						}
					} else {
						chatTitle.innerText = "Chat";
					}

					let subText = document.createElement("subtext_m");
					chatInfo.appendChild(subText);
					subText.classList.add('members-info');

					subText.innerHTML = pages.elements.messages.getSubstring(chatObject);
					header.close = function () {
						return callback(null, header);
					}

					let typingState = document.createElement("subtext_m");
					typingState.id = 'typingState';

					chatInfo.appendChild(typingState);
					typingState.style.display = 'none';

					typingState.classList.add('valign-wrapper');
					typingState.innerHTML = '<div style="height: 56px">' + unt.Icon.TYPING + '</div>';

					let textTyping = document.createElement('div');
					textTyping.classList.add('typing-info');
					typingState.appendChild(textTyping);

					textTyping.style.height = '56px';
					textTyping.style.overflow = 'hidden';
					textTyping.style.marginLeft = '10px';

					return header;
				}
			},
			dialog: function (chatObject, params = {}, internalData = null) {
				let element = document.createElement('a');

				element.classList = ['card collection-item avatar'];
				element.onclick = function (event) {
					event.preventDefault();

					return ui.go('https://' + window.location.host + "/messages?s=" + (chatObject.chat_info.is_bot_chat ? ("b" + (chatObject.bot_peer_id * -1)) : chatObject.peer_id), false, false, false, (!!internalData ? true : false), internalData);
				}

				let img = document.createElement('img');

				img.classList.add('circle');

				img.alt = '';
				img.src = chatObject.chat_info.data.photo_url;

				element.appendChild(img);
				
				let titleElement = document.createElement('span');
				element.appendChild(titleElement);

				let b = document.createElement('b');
				b.classList.add('hidet');
				titleElement.appendChild(b);

				titleElement.classList.add('title');
				if (chatObject.chat_info.is_multi_chat) {
					b.innerText = chatObject.chat_info.data.title;
				} else {
					b.innerText = chatObject.chat_info.data.name || chatObject.chat_info.data.first_name + " " + chatObject.chat_info.data.last_name;
					if (!chatObject.chat_info.data.account_type) {
						b.innerText = settings.lang.getValue("deleted_account");
						img.src = 'https://dev.yunnet.ru/images/default.png';
					}

					if (chatObject.chat_info.data.is_verified) {
						titleElement.classList.add('valign-wrapper');
						let iconDiv = document.createElement('div');

						titleElement.appendChild(iconDiv);
						iconDiv.style.marginLeft = '5px';
						iconDiv.innerHTML = unt.Icon.PALETTE_ANIM;

						iconDiv.getElementsByTagName('svg')[0].height.baseVal.value = 15;
						iconDiv.getElementsByTagName('svg')[0].width.baseVal.value = 15;
					}
				}

				let messageObject = (chatObject.last_message ? messages.utils.toDefaultObject(chatObject.last_message) : null);
				if (!messageObject) return null;

				let textDiv = document.createElement('div');
				textDiv.innerText = 'Setting up preview...';
				let previewString = messages.utils.getPreviewString(messageObject).then(function (string) {
					textDiv.innerText = string;

					if (chatObject.last_message) {
						if (chatObject.last_message.event) {
							if (chatObject.last_message.event.action === 'invited_user') {
								settings.users.get(chatObject.last_message.event.to_id || settings.users.current.user_id).then(function (user) {
									textDiv.innerText = textDiv.innerText.replace('%when%', user.name || user.first_name + ' ' + user.last_name);
								}).catch(function (err) {
									textDiv.innerText = textDiv.innerText.replace('%when%', settings.lang.getValue("deleted_account"));
								})
							}
						}
					}
				});

				textDiv.classList.add("hidet");
				element.appendChild(textDiv);

				let secondaryData = document.createElement('div');
				secondaryData.classList.add('secondary-content');
				element.appendChild(secondaryData);

				let small = document.createElement('small');
				secondaryData.appendChild(small);
				small.innerText = pages.parsers.time(messageObject.time);

				if (!chatObject.metadata.is_read_by_me) {
					element.classList.add("unreaded");

					let thirdaryContent = document.createElement('div');
					element.appendChild(thirdaryContent);

					thirdaryContent.classList.add('thirdary-content');
					thirdaryContent.innerText = pages.parsers.niceString(chatObject.metadata.unread_count);
				}

				if (typeof params.onContext === "function") {
					element.oncontextmenu = function (event) {
						event.preventDefault();

						return params.onContext(element, event, chatObject);
					}
				}

				element.id = "dial_" + (chatObject.bot_peer_id ? ("b" + (chatObject.bot_peer_id * -1)) : (chatObject.peer_id));
				return element;
			}
		},
		inputForm: function (innerText = '', params = {onInputCallback: null, attachmentsItemCallback: null, sendItemCallback: null, pasteCallback: null}) {
			let inputElement = document.createElement('div');
			inputElement.draggable = true;

			let dragHandler = document.createElement('div');
			dragHandler.id = 'drag-manager';
			inputElement.appendChild(dragHandler);

			dragHandler.style.width = '100%';
			dragHandler.style.padding = '100px';

			dragHandler.classList.add('center');
			dragHandler.innerText = settings.lang.getValue('files_here_text');

			let ableToUpload = false;

			dragHandler.style.display = 'none';
			dragHandler.addEventListener('dragover', function (event) {
				event.preventDefault();

				if (!ableToUpload) {
					ableToUpload = true;
					dragHandler.style.backgroundColor = 'lightgrey';
				}
			});
			dragHandler.addEventListener('dragleave', function (event) {
				event.preventDefault();
				ableToUpload = false;
				dragHandler.style.backgroundColor = '';
			});
			dragHandler.addEventListener('drop', function (event) {
				event.preventDefault();

				dragHandler.style.backgroundColor = '';
				dragHandler.style.display = 'none';

				return params.pasteCallback(event, event.dataTransfer.files);
			})

			inputElement.classList = ['card halign-wrapper inputchat hidesc'];
			inputElement.style.margin = 0;

			let errorDiv = document.createElement("div");
			errorDiv.style.display = "none";
			inputElement.appendChild(errorDiv);

			let containerDivForError = document.createElement("div");
			errorDiv.appendChild(containerDivForError);
			containerDivForError.classList.add("container");

			let p = document.createElement("p");
			containerDivForError.appendChild(p);

			let attachmentsList = document.createElement('div');
			attachmentsList.id = "attachments_list";
			attachmentsList.style.overflow = 'auto';

			inputElement.appendChild(attachmentsList);
			attachmentsList.classList = ['valign-wrapper'];

			let valignDiv = document.createElement('div');
			inputElement.appendChild(valignDiv);

			let otherDiv = document.createElement('div');
			otherDiv.classList = ['odiv'];
			inputElement.appendChild(otherDiv);

			valignDiv.classList.add('valign-wrapper');

			valignDiv.style.width = '100%';
			valignDiv.style.paddingRight = valignDiv.style.paddingLeft = '10px';

			let textAreaDiv = document.createElement('div');
			valignDiv.appendChild(textAreaDiv);
			textAreaDiv.style.width = '100%';

			let textArea = document.createElement('textarea');
			textArea.id = "write_data";
			textArea.placeholder = String(innerText);
			textAreaDiv.appendChild(textArea);
			textArea.classList = ['materialize-textarea hidesc'];

			textArea.setAttribute('rows', '1');
			textArea.style = 'overflow-y: inherit; max-height: 250px !important; height: 43px;';
			textArea.oninput = function (event) {
				return params.onInputCallback(event, textArea);
			}

			textArea.onpaste = function (event) {
				if (event.clipboardData.files.length > 0)
					params.pasteCallback ? params.pasteCallback(event, event.clipboardData.files) : null;
			}

			let actionsDiv = document.createElement('div');
			valignDiv.appendChild(actionsDiv);
			actionsDiv.classList.add('valign-wrapper');
			actionsDiv.style = 'margin-top: auto; margin-bottom: 10px';

			let attachFileItem = document.createElement('a');
			actionsDiv.appendChild(attachFileItem);
			attachFileItem.classList.add('dark-its');
			attachFileItem.style.marginLeft = "10px";

			attachFileItem.innerHTML = unt.Icon.ATTACHMENT;
			attachFileItem.onclick = function (event) {
				return params.attachmentsItemCallback(event, attachFileItem);
			}

			let sendItem = document.createElement('a');
			actionsDiv.appendChild(sendItem);
			sendItem.classList.add('dark-its');
			sendItem.style.marginLeft = "10px";

			sendItem.innerHTML = unt.Icon.SEND;
			sendItem.onclick = function (event) {
				if (sendItem.classList.contains("disabled")) return null;

				return params.sendItemCallback(event, sendItem, params.sendItemCallback);
			}

			let loaderItem = document.createElement('div');
			actionsDiv.appendChild(loaderItem);

			loaderItem.style.marginLeft = "10px";
			loaderItem.style.marginRight = "5px";

			let innLoader = pages.elements.getLoader();
			loaderItem.appendChild(innLoader);
			innLoader.setArea(20);

			inputElement.getAttachmentsItem = function () {
				return attachFileItem;
			}

			inputElement.addItem = function (icon, identifierClass, onclickCallback) {
				let itemElement = document.createElement('a');
				actionsDiv.insertAdjacentElement('afterbegin', itemElement);

				itemElement.hide = function () {
					itemElement.style.display = 'none';

					return itemElement;
				}
				itemElement.show = function () {
					itemElement.style.display = '';

					return itemElement;
				}

				itemElement.addEventListener('click', function (event) {
					return onclickCallback(event, itemElement);
				});

				itemElement.classList.add('dark-its');
				itemElement.classList.add(identifierClass);

				itemElement.style.marginLeft = "10px";
				itemElement.innerHTML = icon;

				itemElement.getCallbackFunction = function () {
					return onclickCallback;
				}

				return itemElement;
			}

			sendItem.setLoading = function (showLoader) {
				if (showLoader) {
					sendItem.style.display = 'none';
					loaderItem.style.display = '';
				} else {
					sendItem.style.display = '';
					loaderItem.style.display = 'none';
				}

				return inputElement;
			}
			sendItem.setIcon = function (icon) {
				return sendItem.innerHTML = icon;
			}

			sendItem.disable = function () {
				return sendItem.classList.add('disabled');
			}
			sendItem.enable = function () {
				return sendItem.classList.remove('disabled');
			}

			inputElement.getSendItem = function () {
				return sendItem;
			}

			inputElement.setErrorMessage = function (errorMessage) {
				p.innerText = errorMessage;

				errorDiv.style.display = "";

				attachmentsList.style.display = "none";
				valignDiv.style.display = "none";

				return inputElement;
			}
			inputElement.hideErrorMessage = function () {
				p.innerText = "";

				errorDiv.style.display = "none";

				attachmentsList.style.display = "";
				valignDiv.style.display = "";

				return inputElement;
			}

			inputElement.arguments = arguments;

			return sendItem.setLoading(false);
		},
		postEditor: function (post) {
			if (!settings.users.current) return null;
			if (post.owner_id !== settings.users.current.user_id) return null;

			let editorWindow = document.createElement('div');

			editorWindow.classList.add("modal");
			editorWindow.classList.add("bottom-sheet");

			editorWindow.open = function () {
				document.body.appendChild(editorWindow);

				let instance = unt.Modal.init(editorWindow, {
					onCloseEnd: function () {
						return editorWindow.remove();
					}
				});

				if (instance)
					instance.open();

				unt.textareaAutoResize(textArea);
				unt.updateTextFields();

				editorWindow.style.top = 0;
				editorWindow.style.width = editorWindow.style.height = '100%';
				editorWindow.style.borderRadius = 0;

				return true;
			}
			editorWindow.close = function () {
				return unt.Modal.getInstance(editorWindow).close();
			}

			let modalContent = document.createElement('div');
			modalContent.classList.add('modal-content');

			let postEditorHeader = document.createElement('div');
			modalContent.appendChild(postEditorHeader);

			postEditorHeader.classList.add('valign-wrapper');
			postEditorHeader.style.width = '100%';

			let headerText = document.createElement('div');
			postEditorHeader.appendChild(headerText);
			headerText.style.width = '100%';
			headerText.innerText = settings.lang.getValue('edit_post');

			let closeButton = document.createElement('div');
			closeButton.style.cursor = 'pointer';
			closeButton.style.marginTop = '5px';
			postEditorHeader.appendChild(closeButton);
			closeButton.innerHTML = unt.Icon.CLOSE;

			closeButton.addEventListener('click', function () {
				editorWindow.close();
			});

			modalContent.appendChild(document.createElement('br'));

			let modalFooter = document.createElement('div');
			modalFooter.classList.add('modal-footer');
			modalFooter.classList.add('valign-wrapper');

			editorWindow.appendChild(modalContent);
			editorWindow.appendChild(modalFooter);

			let innerDiv = document.createElement("div");
			innerDiv.classList.add('valign-wrapper');
			modalContent.appendChild(innerDiv);

			let inputDiv = document.createElement('div');
			innerDiv.appendChild(inputDiv);
			inputDiv.classList.add('input-field');
			inputDiv.style.width = "100%";

			let textArea = document.createElement('textarea');
			inputDiv.appendChild(textArea);
			textArea.classList.add('materialize-textarea');
			textArea.id = "editorArea";

			let label = document.createElement('label');
			inputDiv.appendChild(label);
			label.setAttribute('for', textArea.id);

			if (!String(post.text).isEmpty())
				label.classList.add('active');

			label.innerHTML = settings.lang.getValue("write_a_post");

			textArea.innerHTML = String(post.text);
			unt.updateTextFields();

			let attachmentsDiv = document.createElement('div');
			modalContent.appendChild(attachmentsDiv);
			attachmentsDiv.classList.add("valign-wrapper");

			if (post.attachments) {
				post.attachments.forEach(function (attachment) {
					attachmentsDiv.appendChild(pages.parsers.attachment(attachment, function () {
						if (pages.parsers.attachmentsValid(attachmentsDiv, textArea)) {
							return continueButton.removeAttribute('disabled');
						} else {
							return continueButton.setAttribute('disabled', 'true');
						}
					}));
				});
			}

			textArea.oninput = function () {
				if (pages.parsers.attachmentsValid(attachmentsDiv, textArea)) {
					return continueButton.removeAttribute('disabled');
				} else {
					return continueButton.setAttribute('disabled', 'true');
				}
			}

			let attachmentFileDiv = document.createElement('div');
			modalFooter.appendChild(attachmentFileDiv);
			attachmentFileDiv.style.marginLeft = '10px';
			attachmentFileDiv.innerHTML = unt.Icon.ATTACHMENT;
			attachmentFileDiv.onclick = function () {
				return pages.elements.contextMenu([
					[
						settings.lang.getValue('photo'),
						function () {
							let fileUploader = pages.elements.fileUploader({
								onFileSelected: function (event, files, uploader) {
									uploader.setLoading(true);

									return uploads
										.getURL()
										.then(function (url) {
											return uploads
												.upload(url, files[0], function (event) {
													return codes.callbacks.uploadResolve(event, uploader);
												})
												.then(function (attachment) {
													uploader.setLoading(false);

													attachmentsDiv.appendChild(pages.parsers.attachment(attachment, function () {
														if (pages.parsers.attachmentsValid(attachmentsDiv, textArea)) {
															continueButton.removeAttribute('disabled');
														} else {
															continueButton.setAttribute('disabled', 'true');
														}
													}));

													return uploader.close();
												})
												.catch(function (err) {
													let errorString = settings.lang.getValue("upload_error");

													unt.toast({html: errorString});
													return uploader.setLoading(false);
												});
										})
										.catch(function (err) {
											let errorString = settings.lang.getValue("upload_error");

											unt.toast({html: errorString});
											return uploader.setLoading(false);
										});
								},
								afterClose: function (event, uploader) {
									if (pages.parsers.attachmentsValid(attachmentsDiv, textArea)) {
										continueButton.removeAttribute('disabled');
									} else {
										continueButton.setAttribute('disabled', 'true');
									}
								}
							});

							return fileUploader.open();
						}
					],
					[
						settings.lang.getValue('poll'),
						function () {
							if (poll.pinned(attachmentsDiv)) return unt.toast({html: settings.lang.getValue('upload_error')});

							return poll.creator(function (poll, creator) {
								let pollElement = pages.elements.createPollElement(poll.poll, function () {
									if (pages.parsers.attachmentsValid(attachmentsDiv, textArea)) {
										continueButton.removeAttribute('disabled');
									} else {
										continueButton.setAttribute('disabled', 'true');
									}
								});

								attachmentsDiv.appendChild(pollElement);
								if (pages.parsers.attachmentsValid(attachmentsDiv, textArea)) {
									continueButton.removeAttribute('disabled');
								} else {
									continueButton.setAttribute('disabled', 'true');
								}
							}).show();
						}
					]
				]).open(event, true);
			}

			let continueButton = document.createElement('a');
			continueButton.style.marginLeft = 'auto';
			modalFooter.appendChild(continueButton);

			continueButton.innerHTML = 'Continue';
			continueButton.classList = ['btn-flat alink-name waves-effect modal-close'];
			continueButton.innerHTML = settings.lang.getValue("continue");
			continueButton.setAttribute('disabled', 'true');

			continueButton.onclick = function () {
				let attachmentsString = pages.parsers.attachmentsString(attachmentsDiv);

				continueButton.setAttribute('disabled', 'true');
				return posts.actions.edit(post.user_id, post.id, String(textArea.value), attachmentsString).then(function (post) {
					continueButton.removeAttribute('disabled');

					let neededElement = document.getElementById('wall' + post.user_id + '_' + post.id);
					let newElement = pages.elements.post(post);

					editorWindow.close();
					return neededElement ? neededElement.replaceWith(newElement) : (document.getElementById("posts_list") ? posts_list.prepend(newElement) : news_list.prepend(newElement));
				}).catch(function (err) {
					continueButton.removeAttribute('disabled');

					let errorString = settings.lang.getValue("upload_error");
					return unt.toast({html: errorString});
				});
			};

			return editorWindow;
		},
		getLoader: function (legacy = true) {
			if (!legacy) {
				let loader = document.createElementNS("http://www.w3.org/2000/svg", "svg");

				loader.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
				loader.setAttribute('viewBox', '0 0 100 100');

				let circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
				loader.appendChild(circle);

				circle.setAttribute('cx', '50');
				circle.setAttribute('cy', '50');
				circle.setAttribute('r', '45');

				circle.style.maxWidth = '100px';
				circle.style.fill = 'transparent';
				circle.style.stroke = 'var(--unt-loader-color, #42a5f5)';
				circle.style.strokeWidth = '10px';
				circle.style.borderStyle = 'dashed';
				circle.style.strokeLinecap = 'round';
				circle.style.transformOrigin = '50% 50%';
				circle.style.strokeDasharray = '283';
				circle.style.strokeDashoffset = '0';

				loader.style.overflow = 'visible';

				loader.hide = function () {
					return loader.style.display = 'none';
				};
				loader.show = function () {
					return loader.style.display = '';
				};
				loader.setArea = function (area) {
					if (isNaN(Number(area))) return false;
					if (Number(area) <= 0) return false;

					return loader.style.width = (area + 'px');
				};

				loader.setProgress = function (percent = 0) {
					if (isNaN(Number(percent))) return false;
					if (percent > 100 || percent < 0) return false;

					let newStrokeOffset = Number(Number(circle.style.strokeDasharray) - (Number(circle.style.strokeDasharray) * Number(percent))/100);

					return circle.style.strokeDashoffset = newStrokeOffset;
				}

				loader.setColor = function (color) {
					circle.style.stroke = color;

					return loader;
				}

				loader.setArea(40);
				loader.classList.add("center");

				return loader;
			} else {
				let profileLoader = document.createElement("div");
				profileLoader.hide = function () {
					this.style.display = 'none';

					return this;
				};
				profileLoader.show = function () {
					this.style.display = '';

					return this;
				}
				profileLoader.setArea = function (area) {
					let svg = profileLoader.getElementsByTagName('svg')[0];

					if (svg) {
						svg.width.baseVal.value = Number(area);
						svg.height.baseVal.value = Number(area);

						return true;
					}

					return null;
				}

				profileLoader.setColor = function (color) {
					let svg = profileLoader.getElementsByTagName('path')[0];

					if (svg)
						svg.style.fill = color;

					return profileLoader;
				}

				profileLoader.classList.add("center");
				profileLoader.innerHTML = unt.Icon.LOADER;

				return profileLoader;
			}
		},
		menuBody: function () {
			let menuBody = (ui.isMobile() ? document.getElementById("menu") : document.getElementsByClassName("col s6")[0]);
			menuBody.addEventListener('drop', function (event) {
			    return event.preventDefault();
			})
			menuBody.addEventListener('dragstart', function (event) {
			    return event.preventDefault();
			})


			menuBody.clear = function () {
				let emptyDiv = document.createElement('div');
				menuBody.innerHTML = '';
				
				menuBody.appendChild(emptyDiv);
				return emptyDiv;
			}

			menuBody.getCurrent = function () {
				return menuBody.getElementsByTagName('div')[0];
			}

			return menuBody;
		},
		wallInput: function (params = {attachFileCallback: null, publishSettingsCallback: null, publishCallback: null, inputTextCallback: null, photoUrl: settings.users.current.photo_url, hideInputButton: false, pasteCallback: null}) {
			let wallInputElement = document.createElement('ul');
			wallInputElement.classList.add("collapsible");

			let innerElement = document.createElement('li');

			let header = document.createElement("div");
			let clBody = document.createElement("div");

			header.classList.add("collapsible-header");
			clBody.classList.add("collapsible-body");

			let headerDiv = document.createElement("div");
			headerDiv.classList.add("valign-wrapper");

			let headerDiv_writeIcon = document.createElement("div");
			let headerDiv_textMessg = document.createElement("div");

			headerDiv_writeIcon.innerHTML = unt.Icon.HAND;
			headerDiv_textMessg.innerText = settings.lang.getValue("write_a_post");
			headerDiv_textMessg.style = "margin-left: 10px; margin-bottom: 5px";

			headerDiv.appendChild(headerDiv_writeIcon);
			headerDiv.appendChild(headerDiv_textMessg);

			let clBody_postDiv = document.createElement("div");
			clBody_postDiv.classList.add("valign-wrapper");

			let clBody_postDiv_img = document.createElement("img");
			clBody_postDiv_img.alt = "";
			clBody_postDiv_img.src = params.photoUrl || settings.user.current_user.photo_url;
			clBody_postDiv_img.width = 32;
			clBody_postDiv_img.height = 32;
			clBody_postDiv_img.classList.add("circle");
			clBody_postDiv.appendChild(clBody_postDiv_img);

			let clBody_postDiv_postInputDiv = document.createElement("div");
			clBody_postDiv_postInputDiv.style = "width: 100%; margin-left: 15px";
			clBody_postDiv_postInputDiv.classList.add("input-field");

			let clBody_postDiv_postInputDiv_textarea = document.createElement("textarea");
			clBody_postDiv_postInputDiv_textarea.oninput = function (event) {
				return params.inputTextCallback(event, clBody_postDiv_postInputDiv_textarea);
			}
			clBody_postDiv_postInputDiv_textarea.onpaste = function (event) {
				if (event.clipboardData.files.length > 0)
					params.pasteCallback ? params.pasteCallback(event, event.clipboardData.files) : null;
			}

			clBody_postDiv_postInputDiv_textarea.classList.add("materialize-textarea");
			clBody_postDiv_postInputDiv_textarea.type = "text";
			clBody_postDiv_postInputDiv_textarea.style = "height: 43px;";
			clBody_postDiv_postInputDiv_textarea.id = "write_text";

			let clBody_postDiv_postInputDiv_textarea_label = document.createElement("label");
			clBody_postDiv_postInputDiv_textarea_label.setAttribute("for", clBody_postDiv_postInputDiv_textarea.id);
			clBody_postDiv_postInputDiv_textarea_label.innerText = settings.lang.getValue("write_a_post");
			clBody_postDiv_postInputDiv.appendChild(clBody_postDiv_postInputDiv_textarea);
			clBody_postDiv_postInputDiv.appendChild(clBody_postDiv_postInputDiv_textarea_label);
			clBody_postDiv.appendChild(clBody_postDiv_postInputDiv);

			let clBody_attachmentsDiv = document.createElement("div");
			clBody_attachmentsDiv.classList.add("valign-wrapper");
			clBody_attachmentsDiv.id = "attachments";

			let clBody_toolsDiv = document.createElement("div");
			clBody_toolsDiv.id = "tools";
			clBody_toolsDiv.classList.add("valign-wrapper");

			let itemsTools_div = document.createElement("div");
			itemsTools_div.id = "items_tools";
			itemsTools_div.classList.add("valign-wrapper");

			let publishSettingsItem = document.createElement("a");
			publishSettingsItem.onclick = function (event) {
				return params.publishSettingsCallback(event, publishSettingsItem);
			}

			publishSettingsItem.id = "publish_settings";
			publishSettingsItem.innerHTML = unt.Icon.SETTINGS;
			itemsTools_div.appendChild(publishSettingsItem);

			attachFileItem = document.createElement("a");
			attachFileItem.onclick = function (event) {
				return params.attachFileCallback(event, attachFileItem);
			}

			attachFileItem.id = "attach_file";
			attachFileItem.style = "margin-left: 10px";
			attachFileItem.innerHTML = unt.Icon.ATTACHMENT;
			itemsTools_div.appendChild(attachFileItem);
			clBody_toolsDiv.appendChild(itemsTools_div);

			let clBody_publishDiv = document.createElement("div");
			clBody_publishDiv.id = "publish";
			clBody_publishDiv.style = "text-align: end; width: 100%";

			let clBody_publishDiv_a = document.createElement("a");
			clBody_publishDiv_a.onclick = function (event) {
				return params.publishCallback(event, clBody_publishDiv_a);
			}

			clBody_publishDiv_a.id = "submit_data";
			clBody_publishDiv_a.classList = ["btn-floating red btn-large waves-effect waves-light scale-transition"];
			clBody_publishDiv_a.innerHTML = '<i><svg style="margin-top: 28%; fill: white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg></i>';
			clBody_publishDiv.appendChild(clBody_publishDiv_a);
			if (params.hideInputButton) 
				clBody_publishDiv_a.classList.add('scale-out');

			let clBody_publishDiv_loader = document.createElement("div");
			clBody_publishDiv_loader.id = "loader_file_ui_publish";
			clBody_publishDiv_loader.style.display = "none";
			clBody_publishDiv_loader.innerHTML = '<svg width="40" height="40" viewBox="0 0 50 50"><path id="loader_ui_spin" transform="rotate(61.2513 25 25)" d="M25,5A20.14,20.14,0,0,1,45,22.88a2.51,2.51,0,0,0,2.49,2.26h0A2.52,2.52,0,0,0,50,22.33a25.14,25.14,0,0,0-50,0,2.52,2.52,0,0,0,2.5,2.81h0A2.51,2.51,0,0,0,5,22.88,20.14,20.14,0,0,1,25,5Z" fill="#42a5f5"><animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.3s" repeatCount="indefinite"></animateTransform></path></svg>';
			clBody_publishDiv.appendChild(clBody_publishDiv_loader);
			clBody_toolsDiv.appendChild(clBody_publishDiv);

			clBody.appendChild(clBody_postDiv);
			clBody.appendChild(clBody_attachmentsDiv);
			clBody.appendChild(clBody_toolsDiv);

			header.appendChild(headerDiv);
			innerElement.appendChild(header);
			innerElement.appendChild(clBody);

			wallInputElement.appendChild(innerElement);

			// setting up additional methods
			clBody_publishDiv_a.setLoading = function (showLoader) {
				if (showLoader) {
					clBody_publishDiv_a.style.display = 'none';
					clBody_publishDiv_loader.style.display = '';
				} else {
					clBody_publishDiv_a.style.display = '';
					clBody_publishDiv_loader.style.display = 'none';
				}

				return true;
			}
			wallInputElement.showInputButton = function () {
				return clBody_publishDiv_a.classList.remove('scale-out');
			}
			wallInputElement.hideInputButton = function () {
				return clBody_publishDiv_a.classList.add('scale-out');
			}

			wallInputElement.getLoader = function () {
				return clBody_publishDiv_a;
			}

			return wallInputElement;
		},
		actionsMenu: function (elements = [], isMeBlocked = false, isLogged = false, isKicked = false) {
			let ulElement = document.createElement("ul");
			ulElement.classList.add("collapsible");

			let innerli = document.createElement("li");
			ulElement.appendChild(innerli);

			let header = document.createElement("div");
			let body = document.createElement("div");

			header.classList.add("collapsible-header");
			body.classList.add("collapsible-body");

			innerli.appendChild(header);
			innerli.appendChild(body);

			header.innerHTML = unt.Icon.DOWN_ARROW;
			if (isMeBlocked) header.innerHTML = unt.Icon.LOCKED;
			if (!isLogged) header.innerHTML = unt.Icon.LOCKED;
			if (isKicked) header.innerHTML = unt.Icon.LOCKED;

			let header_div = document.createElement("div");

			if (isMeBlocked) header_div.innerText = settings.lang.getValue("you_blocked");
			if (!isLogged) header_div.innerText = settings.lang.getValue("need_login");
			if (isLogged && !isMeBlocked) header_div.innerText = settings.lang.getValue("actions");
			if (isKicked) header_div.innerText = settings.lang.getValue("you_kicked");

			header_div.style['margin-left'] = '10px';
			header.appendChild(header_div);

			if (elements instanceof Array) {
				let collectionDiv = document.createElement("div");
				collectionDiv.classList.add("collection");
				body.appendChild(collectionDiv);

				elements.forEach(function (item) {
					let buttonName = item[0];
					let onclickFun = item[1];
					let itemName = item[2] || 'no';

					if (typeof buttonName !== "string") throw new TypeError("Button name must be a string");
					if (typeof onclickFun !== "function") throw new TypeError("Click handler must be a function");

					let element = document.createElement("a");
					element.classList.add("collection-item");

					element.innerText = buttonName;
					element.onclick = function (event) {
						return onclickFun(event, element);
					};

					element.classList.add(itemName);

					return collectionDiv.appendChild(element);
				})
			}

			return ulElement;
		},
		comment: function (comment) {
			let myUserId = settings.users.current ? (Number(settings.users.current.user_id) || 0) : 0;

			let commentElement = document.createElement('div');

			commentElement.classList.add('collection-item');
			commentElement.classList.add('avatar');

			let img = document.createElement('img');
			img.classList.add('circle');
			commentElement.appendChild(img);

			let titleSpan = document.createElement('span');
			commentElement.appendChild(titleSpan);

			let bText = document.createElement('a');
			titleSpan.appendChild(bText);
			bText.href = comment.owner_id > 0 ? ("/id" + comment.owner_id) : ("/bot" + comment.owner_id * -1);

			let innerB = document.createElement('b');
			bText.appendChild(innerB);
			bText.classList.add("alink-name");

			innerB.innerText = '...';
			innerB.classList.add('title');

			if (comment.text) {
				let commText = document.createElement('div');

				commText.classList.add('comment-text-hidden');
				commentElement.appendChild(commText);
				commText.onclick = function () {
					if (commText.classList.contains("comment-text-hidden"))
						return commText.classList.remove("comment-text-hidden");
					else
						return commText.classList.add("comment-text-hidden");
				}

				commText.innerHTML = nl2br(htmlspecialchars(comment.text)).linkify();
			}
			if (comment.attachments) {
				let attachmentsGroup = pages.elements.attachmentsGroup(comment.attachments);

				let attachmentDiv = document.createElement('div');
				attachmentDiv.appendChild(document.createElement('br'));
				
				commentElement.appendChild(attachmentDiv);
				attachmentDiv.appendChild(attachmentsGroup);
			}

			if (settings.users.current) {
				let actionsDiv = document.createElement('div');
				actionsDiv.classList.add('secondary-content');

				actionsDiv.onclick = commentElement.oncontextmenu = function (event) {
					event.preventDefault();

					if (comment.owner_id === myUserId) {
						return pages.elements.contextMenu([
							[(settings.lang.getValue("edit")), new Function()],
							[(settings.lang.getValue("delete")), function () {
								return pages.elements.confirm("", (settings.lang.getValue("delete_comment")) + "?", function (result) {
									if (result) {
										let data = String(String(window.location.href.split(window.location.host)[1]).split('/wall')[1]).split('_');

										let wall_id = Number(data[0]);
										let post_id = Number(data[1]);
										let comm_id = Number(comment.comment_id);

										return posts.comments.delete(wall_id, post_id, comm_id).then(function () {
											return commentElement.remove();
										}).catch(function (err) {
											let errorString = settings.lang.getValue("upload_error");

											return unt.toast({html: errorString});
										});
									}
								});
							}]
						]).open(event);
					} else {
						return pages.elements.contextMenu([
							[(settings.lang.getValue("report")), new Function()]
						]).open(event);
					}
				}; 

				actionsDiv.innerHTML = unt.Icon.MORE;
				commentElement.appendChild(actionsDiv);
			}

			let actionsDiv = document.createElement('div');
			actionsDiv.classList.add('valign-wrapper');

			commentElement.appendChild(actionsDiv);

			let timeString = pages.parsers.time(comment.time);
			let small = document.createElement('small');

			small.style.color = "lightgrey";
			small.style.width = '100%';

			actionsDiv.appendChild(small);
			small.innerHTML = timeString;

			commentElement.classList.add('card');
			commentElement.style.borderRadius = 0;
			settings.users.get(comment.owner_id).then(function (user) {
				innerB.innerText = user.account_type === "bot" ? user.name : user.first_name + " " + user.last_name;
				img.src = user.photo_url;

				if (user.is_verified) {
					titleSpan.classList.add('valign-wrapper');
					let iconDiv = document.createElement('div');

					titleSpan.appendChild(iconDiv);
					iconDiv.style.marginLeft = '5px';
					iconDiv.innerHTML = unt.Icon.PALETTE_ANIM;

					iconDiv.getElementsByTagName('svg')[0].height.baseVal.value = 13;
					iconDiv.getElementsByTagName('svg')[0].width.baseVal.value = 13;
				}

				if (settings.users.current) {
					let replyElement = document.createElement('a');
					replyElement.style.cursor = 'pointer';

					actionsDiv.appendChild(replyElement);

					replyElement.innerHTML = unt.Icon.REPLY;
					replyElement.onclick = function () {
						try {
							write_data.value = '@' + (user.screen_name ? user.screen_name : (user.name ? ("bot" + user.bot_id) : ("id" + user.user_id))) + " " + "(" + (user.name || user.first_name) + "), ";
						
							unt.textareaAutoResize(write_data);
							unt.updateTextFields();

							return write_data.focus();
						} catch (e) {
							return replyElement.remove();
						}
					}
				}
			}).catch(function (err) {
				innerB.innerText = settings.lang.getValue("deleted_account");
				img.src = 'https://dev.yunnet.ru/images/default.png';
			});

			return commentElement;
		},
		post: function (post, submenu = true, full_data = false, inWindow = false) {
			if (!post.text)
				post.text = '';

			if (!post.comments)
				post.comments = {
					count: 0
				};

			const PIN_ICON = unt.Icon.PIN;
			const LIKED_IC = unt.Icon.LIKE_SET; 
			const NOT_LIKE = unt.Icon.LIKE;
			const COMM_ICO = unt.Icon.COMMENTS;

			let element = document.createElement('div');
				
			element.classList.add('card');
			element.classList.add('full_section');
			element.id = 'wall' + post.user_id + '_' + post.id;

			if (submenu)
				element.oncontextmenu = function (event) {
					event.preventDefault();
					let menu = pages.parsers.menu.posts.buildContextMenu(post);

					return menu.open(event);
				}

			let div = document.createElement('div');
			div.classList.add('valign-wrapper');
			div.classList.add('post_info_and_actions');
			element.appendChild(div);

			let a = document.createElement('a');
			a.classList.add('post_owner');
			a.classList.add('valign-wrapper');
			a.href = post.owner_id > 0 ? "/id"+post.owner_id : "/bot"+post.owner_id*-1;
			a.style.width = '100%';
			div.appendChild(a);

			let img = document.createElement('img');
				
			img.classList.add('circle');
			img.alt = '';
			img.width = 32;
			img.height = 32;

			a.appendChild(img);
			let cred_div = document.createElement('div');
					
			cred_div.classList.add('credentials');
			cred_div.classList.add('halign-wrapper');
			a.appendChild(cred_div);

			let inner_cred_div_1 = document.createElement('div');
			inner_cred_div_1.classList.add('name_data');
			inner_cred_div_1.classList.add('alink-name');
			cred_div.appendChild(inner_cred_div_1);

			let inner_cred_div_2 = document.createElement('div');
			inner_cred_div_2.classList.add('valign-wrapper');
			inner_cred_div_1.appendChild(inner_cred_div_2);

			let credentials_data = document.createElement('div');
			let pinned_icon = document.createElement('div');

			pinned_icon.style.transform = 'rotate(45deg)';
			pinned_icon.style['margin-left'] = '5px';
			pinned_icon.style['margin-top'] = '3px';

			credentials_data.innerText = settings.lang.getValue('loading');
			inner_cred_div_2.appendChild(credentials_data);
			inner_cred_div_2.appendChild(pinned_icon);
			if (post.is_pinned) {
				pinned_icon.innerHTML = PIN_ICON;
			}

			let post_time_div = document.createElement('div');
			let small = document.createElement('small');
					
			small.style.color = "lightgrey";
			post_time_div.appendChild(small);
			small.innerHTML = pages.parsers.time(post.time);

			inner_cred_div_1.appendChild(post_time_div);
			if (settings.users.current) {
				if (settings.users.current.user_id > 0 && submenu) {
					let actions = document.createElement('a');

					actions.classList.add('actions-btn');
					actions.onclick = function (event) {
						let menu = pages.parsers.menu.posts.buildContextMenu(post);

						return menu.open(event);
					}
					
					actions.style.color = 'black';
					actions.style['margin-left'] = '15px';
					actions.innerHTML = unt.Icon.MORE;

					div.appendChild(actions);
				}
			}

			element.appendChild(document.createElement('br'));

			let eventDiv = document.createElement('div');
			if (post.event) {
				let type = post.event.type;

				if (type === "updated_photo") {
					eventDiv.style.color = 'lightgrey';

					eventDiv.innerHTML = settings.lang.getValue("updated_profile_photo");

					element.appendChild(eventDiv);
					element.appendChild(document.createElement('br'));
				}
			}
					
			let post_text_data = document.createElement('div');

			post_text_data.style.width = '100%';
			post_text_data.style.wordBreak = 'break-word';

			if (inWindow) {
				post_text_data.style.overflow = 'auto';
				post_text_data.style.maxHeight = '370px';
			}

			post_text_data.innerHTML = nl2br(htmlspecialchars(post.text)).linkify();

			if (!inWindow)
				post_text_data.onclick = function (event) {
					if (event.target.nodeName === 'A') return;

					return (!ui.isMobile() ? pages.parsers.menu.posts.openWindowPost(post) : ui.go('https://' + window.location.host + '/wall'+post.user_id+'_'+post.id));
				}

			element.appendChild(post_text_data);

			let isShown = false;

			if (!full_data && post.text.length > 256) {
				post_text_data.classList.add('post_data');

				let showFullDiv = document.createElement('div');
				showFullDiv.style.cursor = 'pointer';
				showFullDiv.style.color = '#2196f3';
				showFullDiv.innerText = settings.lang.getValue('show_more');

				showFullDiv.onclick = function (event) {
					if (!isShown) {
						post_text_data.classList.remove('post_data');
						showFullDiv.innerText = settings.lang.getValue('hide') + '...';
					} else {
						post_text_data.classList.add('post_data');
						showFullDiv.innerText = settings.lang.getValue('show_more');
					}

					isShown = !isShown;
				}

				element.appendChild(showFullDiv);
			}

			let post_attachments = pages.elements.attachmentsGroup(post.attachments);
			element.appendChild(post_attachments);
					
			element.appendChild(document.createElement('br'));
			let post_counters = document.createElement('div');
					
			post_counters.classList.add('post_counters');
			post_counters.classList.add('valign-wrapper');

			let likes_el = null;
			if (settings.users.current) {
				likes_el = document.createElement('div');
				if (settings.users.current.user_id > 0) {
					likes_el.onclick = function () {
						return likes.set('wall'+post.user_id+'_'+post.id, function (result, newCount, error) {
							if (result === 1) {
								likes_el.getElementsByClassName('not_like')[0].style.display = 'none';
								likes_el.getElementsByClassName('liked_ok')[0].style.display = '';
							}
							if (result === 0) {
								likes_el.getElementsByClassName('not_like')[0].style.display = '';
								likes_el.getElementsByClassName('liked_ok')[0].style.display = 'none';
							}

							let count_element = likes_el.getElementsByClassName("count")[0];
							if (count_element) count_element.innerHTML = newCount;
						});
					}
				} else {
					likes_el.onclick = function () {
						return ui.go(window.location.host);
					}
				}

				likes_el.id = 'like_it';

				likes_el.classList.add('valign-wrapper');

				let like_icons = document.createElement('div');
				like_icons.classList.add('valign-wrapper');
				like_icons.innerHTML = NOT_LIKE + LIKED_IC;

				let counters = document.createElement('div');
				counters.classList.add('count');
				counters.style['margin-left'] = '6px';
				counters.style['font-size'] = '115%';

				likes_el.appendChild(like_icons);
				likes_el.appendChild(counters);

				if (post.like_me) {
					likes_el.getElementsByClassName('not_like')[0].style.display = 'none';
					likes_el.getElementsByClassName('liked_ok')[0].style.display = '';
				}
				if (post.likes > 0) {
					likes_el.getElementsByClassName('count')[0].innerHTML = String(Number(post.likes));
				}
			}

			let icons_comments = document.createElement('div');
						
			icons_comments.classList.add('valign-wrapper');
			icons_comments.innerHTML = COMM_ICO;		
			if (settings.users.current) post_counters.appendChild(likes_el);

			let counters_comments = '';
			if (submenu) {
				let comms = document.createElement('div');
							
				comms.classList.add('valign-wrapper');
				if (settings.users.current) comms.style['margin-left'] = '10px';

				comms.onclick = function () {
					return ui.go('https://' + window.location.host + '/wall'+post.user_id+'_'+post.id);
				}

				counters_comments = document.createElement('div');
				counters_comments.classList.add('comms_count');
				counters_comments.style = 'margin-left: 6px; margin-right: 6px; font-size: 115%';
							
				comms.appendChild(icons_comments);
				comms.appendChild(counters_comments);

				post_counters.appendChild(comms);
			}
						
			element.appendChild(post_counters);
			if (post.comments.count > 0) {
				counters_comments.innerHTML = String(Number(post.comments.count));
			}

			settings.users.get(post.owner_id).then(function (user) {
				if (eventDiv)
					eventDiv.innerHTML = settings.lang.getValue("updated_profile_photo").replace("(а)", (user.gender === 2 ? "а" : ""));

				img.src = user.photo_url;
				credentials_data.innerText = user.user_id ? user.first_name+' '+user.last_name : user.name;

				if (user.is_verified) {
					let iconDiv = document.createElement('div');
					iconDiv.style.marginLeft = '5px';

					credentials_data.appendChild(iconDiv);
					credentials_data.classList.add('valign-wrapper');

					let innerIconDiv = document.createElement('div')

					innerIconDiv.style.margin = 0;
					innerIconDiv.style.cursor = 'pointer';
					innerIconDiv.style.width = innerIconDiv.style.height = '20px';
					innerIconDiv.style.borderRadius = '30px';

					innerIconDiv.innerHTML = unt.Icon.PALETTE_ANIM;
					iconDiv.appendChild(innerIconDiv);
				}
			}).catch(function (err) {
				img.src = 'https://dev.yunnet.ru/images/default.png';
				credentials_data.innerText = settings.lang.getValue("deleted_account");
			});

			return element;
		},
		attachmentsGroup: function (attachments = [], new_id = false) {
			let element = document.createElement('div');
			if (new_id)
				element.id = 'out_atts';

			element.classList.add('attachments');
			element.style.maxWidth = '340px';

			let doneObjects = [];
			
			let pollElement = null;
			for (let i = 0; i < attachments.length; i++) {
				if (attachments[i].type === 'poll') {
					pollElement = attachments[i];
				} else {
					doneObjects.push(attachments[i]);
				}
			}

			if (pollElement)
				doneObjects.push(pollElement);

			if (doneObjects.length > 0) {
				let group_1_has = false;
				let group_2_has = false;
				let group_3_has = false;

				let currentGroup = 1;
				let currentElement = null;
				let width = 100;

				for (let i = 0; i < doneObjects.length; i++) {
					if (i >= 10) break;

					if (doneObjects[i].type === 'poll') {
						let pollContainer = document.createElement('div');

						pollContainer.boundAttachment = doneObjects[i];
						pollContainer.setAttribute('attachment', 'poll' + doneObjects[i].poll.owner_id + '_' + doneObjects[i].poll.id + '_' + doneObjects[i].poll.access_key);

						pollContainer.classList.add('card');
						pollContainer.classList.add('full_section');

						pollContainer.style = 'background-color: #909090 !important';
						element.appendChild(pollContainer);

						let pollTitle = document.createElement('div');
						pollTitle.style.textAlign = 'center';

						let inB = document.createElement('b');
						pollTitle.appendChild(inB);
						inB.style.color = 'var(--unt-poll-title-color, white)';

						pollContainer.appendChild(pollTitle);
						inB.innerText = doneObjects[i].poll.data.title;

						let pollInfo = document.createElement('div');
						pollInfoText = document.createElement('small');

						pollInfo.style.textAlign = 'center';
						pollInfoText.style.color = 'var(--unt-poll-info-color, lightgrey)';
						pollContainer.appendChild(pollInfo);
						pollInfo.appendChild(pollInfoText);
						pollInfoText.innerText = (doneObjects[i].poll.data.is_anonymous ? settings.lang.getValue('poll_anonymous') : settings.lang.getValue('poll_public')) + (' • ') + pages.parsers.time(doneObjects[i].poll.creation_time);

						pollContainer.appendChild(document.createElement('br'));

						let pollVariants = document.createElement('div');
						pollVariants.style.display = 'grid';
						pollVariants.style.width = '100%';
						pollContainer.appendChild(pollVariants);

						let pollVoting = false;
						let pollVoted = true;

						doneObjects[i].poll.variants_list.forEach(function (variant) {
							let answer = document.createElement('a');
							answer.classList = ['card waves-effect waves-light valign-wrapper hidet-aone'];
							answer.style = 'background-color: gray !important';

							let answerTextDiv = document.createElement('div');
							answer.appendChild(answerTextDiv);

							answerTextDiv.innerText = variant.text;

							answer.style.cursor = 'pointer';
							answer.style.display = 'flex';
							answer.style.justifyContent = 'space-between';
							answer.style.padding = '10px';
							answer.style.margin = '3px';
							answer.style.color = 'white';
							answer.style.zIndex = 0;

							let answerStates = document.createElement('div');
							answer.appendChild(answerStates);

							let pollLoaderProgress = pages.elements.getLoader().setColor('white');
							pollLoaderProgress.setArea(15);
							pollLoaderProgress.style.display = 'none';
							answerStates.appendChild(pollLoaderProgress);

							let doneDiv = document.createElement('div');
							doneDiv.innerHTML = unt.Icon.SAVE;
							answerStates.appendChild(doneDiv);
							doneDiv.getElementsByTagName('svg')[0].height.baseVal.value = 15;
							doneDiv.getElementsByTagName('svg')[0].width.baseVal.value = 15;
							doneDiv.style.display = 'none';

							answer.addEventListener('click', function (event) {
								if (pollVoting) return;

								pollVoting = true;

								pollLoaderProgress.style.display = '';
								return setTimeout(function () {
									pollLoaderProgress.style.display = 'none';
									doneDiv.style.display = '';

									pollVoted = true;
								}, 2000);
							})

							return pollVariants.appendChild(answer);
						});
					}
					if (doneObjects[i].type === 'photo') {
						if (i >= 0 && i <= 1 && !group_1_has) {
							let attachmentsGroupItem = document.createElement('div');
							attachmentsGroupItem.classList.add('attachments_line_1');

							group_1_has = true;
							currentGroup = 1;
							width = 50;

							let innerP = document.createElement('p');
							attachmentsGroupItem.appendChild(innerP);
							innerP.classList.add('valign-wrapper');
							innerP.style.height = '150px';

							currentElement = innerP;
							element.appendChild(attachmentsGroupItem);
						}
						if (i >= 2 && i <= 4 && !group_2_has) {
							let attachmentsGroupItem = document.createElement('div');
							attachmentsGroupItem.classList.add('attachments_line_2');

							group_2_has = true;
							currentGroup = 2;
							width = 33.3;

							let innerP = document.createElement('p');
							attachmentsGroupItem.appendChild(innerP);
							innerP.classList.add('valign-wrapper');
							innerP.style.height = '100px';

							currentElement = innerP;
							element.appendChild(attachmentsGroupItem);
						}
						if (i >= 5 && i <= 9 && !group_3_has) {
							let attachmentsGroupItem = document.createElement('div');
							attachmentsGroupItem.classList.add('attachments_line_3');

							group_3_has = true;
							currentGroup = 3;
							width = 20;

							let innerP = document.createElement('p');
							attachmentsGroupItem.appendChild(innerP);
							innerP.classList.add('valign-wrapper');
							innerP.style.height = '50px';

							currentElement = innerP;
							element.appendChild(attachmentsGroupItem);
						}

						let imgDiv = document.createElement('div');
						
						imgDiv.style.height = '100%';
						imgDiv.style.maxWidth = '100%';
						imgDiv.style.margin = '2px';
						currentElement.appendChild(imgDiv);

						let img = new Image();
						img.width = doneObjects[i].photo.meta.width;
						img.height = doneObjects[i].photo.meta.height

						imgDiv.appendChild(img);
						img.loading = 'lazy';

						img.src = doneObjects[i].photo.url.main;
						img.classList.add('attachment_tile');

						img.style.minWidth = width;
						img.setAttribute('attachment', 'photo' + doneObjects[i].photo.owner_id + '_' + doneObjects[i].photo.id + '_' + doneObjects[i].photo.access_key);
						
						img.boundAttachment = doneObjects[i];
						img.onclick = function () {
							return photos.show(this, doneObjects[i].photo);
						}
					}
				}
			}

			return element;
		},
		alertWindow: function (icon, header, text) {
			let cardDiv = document.createElement('div');

			cardDiv.id = "alertWindow";
			cardDiv.classList.add('card');

			let innerDiv = document.createElement('div');
			innerDiv.classList = ['container center'];
			cardDiv.appendChild(innerDiv);

			innerDiv.appendChild(document.createElement('br'));

			let tmpDiv = document.createElement('div');
			tmpDiv.innerHTML = icon;

			let svgIc = tmpDiv.getElementsByTagName('svg')[0];
			innerDiv.appendChild(svgIc);

			svgIc.width.baseVal.value = 96;
			svgIc.height.baseVal.value = 96;

			let textDiv = document.createElement('div');
			innerDiv.appendChild(textDiv);

			let h4 = document.createElement('h4');
			let b = document.createElement('b');

			b.innerText = String(header);
			h4.appendChild(b);
			textDiv.appendChild(h4);

			if (!String(text).isEmpty())
				textDiv.appendChild(document.createElement('br'));

			let finalDiv = document.createElement('div');
			finalDiv.innerText = String(text);
			textDiv.appendChild(finalDiv);

			if (!String(text).isEmpty())
				textDiv.appendChild(document.createElement('br'));

			return cardDiv;
		},
		backArrowButton: function (text, onclickHandler) {
			let element = document.createElement('ul');
			element.classList.add("collapsible");

			let innerli = document.createElement('li');
			element.appendChild(innerli);

			let colHeader = document.createElement('div');
			colHeader.classList.add("collapsible-header");

			innerli.appendChild(colHeader);

			let valDiv = document.createElement('div');
			valDiv.classList.add('valign-wrapper');
			colHeader.appendChild(valDiv);

			let alink = document.createElement('a');

			alink.innerHTML = unt.Icon.BACK_ARROW;
			alink.onclick = function (event) {
				if (!onclickHandler)
					return ui.go(null, true);

				return onclickHandler(event);
			}

			valDiv.appendChild(alink);

			let textDiv = document.createElement('div');
			textDiv.style.marginLeft = '10px';
			textDiv.style.marginBottom = '5px';

			if (!text) textDiv.innerHTML = settings.lang.getValue("back");
			else textDiv.innerHTML = text;

			element.setText = function (text) {
				textDiv.innerHTML = text || settings.lang.getValue("back");
			}

			valDiv.appendChild(textDiv);

			return element;
		},
		contextMenu: function (elements = []) {
			if (ui.isMobile()) {
				let modalElement = document.createElement('div');

				modalElement.classList = ['modal bottom-sheet'];

				let modalContent = document.createElement('div');
				modalElement.appendChild(modalContent);

				let itemsCollection = document.createElement('div');
				itemsCollection.classList.add('collection');

				itemsCollection.style.backgroundColor = 'inherit';
				modalContent.appendChild(itemsCollection);

				elements.forEach(function (item) {
					let a = document.createElement('a');
					a.classList.add('unselectable');

					a.style.backgroundColor = 'inherit';

					let textItem = item[0];
					let onclickFunction = item[1];

					if (typeof textItem !== "string") throw new TypeError("Only strings as text allowed");
					if (typeof onclickFunction !== "function") throw new TypeError("Only functions allowed to onclick handler")

					a.innerText = textItem;
					a.addEventListener('click', function (event) {
						onclickFunction(event, a, modalElement);

						return modalElement.close();
					});

					a.classList = ['collection-item alink-name'];

					itemsCollection.appendChild(a);
				});

				modalElement.open = function () {
					document.body.appendChild(this);

					let instance = unt.Modal.init(this, {
						onCloseEnd: function () {
							return modalElement.remove();
						}
					});

					return instance ? instance.open() : null;
				}
				modalElement.close = function () {
					let instance = unt.Modal.getInstance(modalElement)

					return instance ? instance.close() : null;
				}

				return modalElement;
			} else {
				let itemsElement = null;
				if (!document.getElementById('tmp-dropdown')) {
					itemsElement = document.createElement('ul');

					itemsElement.classList.add('dropdown-content');
				} else {
					itemsElement = document.getElementById('tmp-dropdown');
					itemsElement.innerHTML = '';
				}

				let aTrigger = null;
				if (!document.getElementById('tmp-trigger')) {
					aTrigger = document.createElement('a');
					aTrigger.id = 'tmp-trigger';
				} else {
					aTrigger = document.getElementById('tmp-trigger');
				}

				elements.forEach(function (item) {
					let innerli = document.createElement('li');
					innerli.classList.add('unselectable');

					let textItem = item[0];
					let onclickFunction = item[1];

					if (typeof textItem !== "string") throw new TypeError("Only strings as text allowed");
					if (typeof onclickFunction !== "function") throw new TypeError("Only functions allowed to onclick handler")

					let a = document.createElement('a');
					innerli.appendChild(a);

					a.innerText = textItem;
					a.addEventListener('click', function (event) {
						onclickFunction(event, a, itemsElement);

						return itemsElement.close();
					});

					itemsElement.id = 'tmp-dropdown';
					itemsElement.open = function (event, openUp = false) {
						if (!event) return null;

						let x = Number(event.pageX);
						let y = Number(event.pageY);
						
						aTrigger.classList.add('dropdown-trigger');
						aTrigger.setAttribute('data-target', itemsElement.id);

						if (!document.getElementById(aTrigger.id)) document.body.appendChild(aTrigger);
						if (!document.getElementById(itemsElement.id)) document.body.appendChild(itemsElement);

						let instance = unt.Dropdown.init(aTrigger, {
							onCloseEnd: function () {
								aTrigger.remove();
								itemsElement.remove();
							}
						});

						instance.open();

						this.style.position = 'absolute';
						this.style.top = String(openUp ? (y - 100) : y) + 'px';
						this.style.left = String(x) + 'px';
						this.style.width = 'auto';
						this.style.height = '';

						return true;
					}

					itemsElement.close = function () {
						let instance = unt.Dropdown.getInstance(aTrigger);

						return instance ? instance.close() : null;
					}

					return itemsElement.appendChild(innerli);
				});


				return itemsElement;
			}
		},
		confirm: function (header, body, callback, closeOnResponse = true) {
			let windowElement = document.createElement('div');

			windowElement.classList.add('modal');
			if (ui.isMobile())
				windowElement.classList.add('bottom-sheet');

			let content = document.createElement('div');
			content.classList.add('modal-content');

			let footer = document.createElement('div');
			footer.classList.add('modal-footer');

			windowElement.appendChild(content);
			windowElement.appendChild(footer);

			let noButton = document.createElement('a');
			noButton.classList = ['waves-effect btn-flat'];
			if (!closeOnResponse)
				noButton.classList.remove('modal-close');

			noButton.innerText = settings.lang.getValue("no");
			footer.appendChild(noButton);

			let yesButton = document.createElement('a');
			yesButton.classList = ['waves-effect btn-flat'];
			if (!closeOnResponse)
				yesButton.classList.remove('modal-close');

			yesButton.innerText = settings.lang.getValue("yes");
			footer.appendChild(yesButton);

			if (!callback) return null;
			if (!String(header).isEmpty()) {
				let h4 = document.createElement('h4');

				h4.innerText = String(header);
				content.appendChild(h4);
			}

			let p = document.createElement('p');
			if (String(header).isEmpty()) p = document.createElement('div');

			p.innerHTML = String(body);
			content.appendChild(p);

			document.body.appendChild(windowElement);
			let instance = unt.Modal.init(windowElement, {
				endingTop: closeOnResponse ? '35%' : '10%',
				onCloseEnd: function () {
					windowElement.remove();

					if (!noCallback)
						return callback(false, instance, yesButton, noButton);
				}
			});

			let noCallback = false;
			yesButton.addEventListener('click', function () {
				callback(true, instance, yesButton, noButton);

				noCallback = true;

				if (closeOnResponse)
					instance.close();
			});
			noButton.addEventListener('click', function () {
				callback(false, instance, yesButton, noButton);

				noCallback = true;

				if (closeOnResponse)
					instance.close();
			});
			
			if (instance) instance.open();

			windowElement.getBody = function () {
				return content;
			}

			return windowElement;
		},
		fileUploader: function (params = {onFileSelected: null, afterClose: null, fileTypes: 'image/*'}) {
			let fileUploaderElement = document.createElement('div');
			fileUploaderElement.classList.add('modal');
			
			if (ui.isMobile()) fileUploaderElement.classList.add("bottom-sheet");

			let content = document.createElement('div');
			fileUploaderElement.appendChild(content);

			content.classList.add('modal-content');
			content.innerHTML = '<h4>' + (settings.lang.getValue("select_a_file")) + '</h4>';

			let uploadForm = document.createElement('div');
			fileUploaderElement.appendChild(uploadForm);

			uploadForm.style.marginLeft = '27px';
			uploadForm.style.marginRight = '27px';

			let innerForm = document.createElement('form');
			uploadForm.appendChild(innerForm);

			let fileUploaderDiv = document.createElement('div');
			innerForm.appendChild(fileUploaderDiv);
			fileUploaderDiv.classList = ['file-field input-field'];

			let innerBtn = document.createElement('div');
			fileUploaderDiv.appendChild(innerBtn);
			innerBtn.classList = ['btn'];

			let innerSpan = document.createElement('span');
			innerBtn.appendChild(innerSpan);
			innerSpan.innerText = (settings.lang.getValue("select_file"));

			let inputSelector = document.createElement('input');
			innerBtn.appendChild(inputSelector);
			inputSelector.type = "file";
			inputSelector.accept = params.fileTypes || 'image/*';
			inputSelector.oninput = function (event) {
				return params.onFileSelected(event, event.target.files, fileUploaderElement);
			}

			fileUploaderElement.selectFile = function (files) {
				fileUploaderElement.getElementsByClassName('file-path')[0].value = files[0].name;
				params.onFileSelected(new Event('input'), files, fileUploaderElement);

				return fileUploaderElement;
			}

			let filePathWrapper = document.createElement('div');
			fileUploaderDiv.appendChild(filePathWrapper);
			filePathWrapper.classList = ['file-path-wrapper'];

			let fpInnerInput = document.createElement('input');
			filePathWrapper.appendChild(fpInnerInput);

			fpInnerInput.classList = ['file-path validate'];
			fpInnerInput.placeholder = (settings.lang.getValue("selected"));
			fpInnerInput.type = "text";

			let footer = document.createElement('div');
			fileUploaderElement.appendChild(footer);
			footer.classList.add('modal-footer');

			let button = document.createElement('a');
			footer.appendChild(button);

			button.classList = ['btn-flat'];
			let innerDiv = document.createElement('div');
			innerDiv.innerText = (settings.lang.getValue("continue"));
			innerDiv.classList.add('modal-close');
			innerDiv.onclick = function (event) {
				return params.afterClose(event, fileUploaderElement);
			}

			let loader = pages.elements.getLoader(false);

			loader.style.display = 'none';
			loader.setArea(20);
			loader.classList.remove('center');

			button.appendChild(loader);
			button.appendChild(innerDiv);
			
			fileUploaderElement.setLoading = function (loading) {
				if (loading) {
					loader.setProgress(0);

					loader.style.display = '';
					innerDiv.style.display = 'none';
				} else {
					loader.style.display = '';
					innerDiv.style.display = 'none';
				}

				return true;
			}

			fileUploaderElement.setProgress = function (percent) {
				return loader.setProgress(percent);
			}

			fileUploaderElement.toggleFileSelection = function () {
				if (inputSelector.getAttribute('disabled') === 'true') {
					inputSelector.setAttribute('disabled', 'false');
					fpInnerInput.setAttribute('disabled', 'false');
				} else {
					inputSelector.setAttribute('disabled', 'true');
					fpInnerInput.setAttribute('disabled', 'true');
				}

				return true;
			}

			fileUploaderElement.open = function () {
				document.body.appendChild(fileUploaderElement);

				unt.Modal.init(fileUploaderElement, {
					dismissible: false,
					onCloseEnd: function () {
						return fileUploaderElement.remove();
					}
				}).open();

				return fileUploaderElement;
			}
			fileUploaderElement.close = function () {
				return unt.Modal.getInstance(fileUploaderElement).close();
			}
			fileUploaderElement.addFooterItem = function (buttonText, callback) {
				if (String(buttonText).isEmpty()) return null;
				if (typeof callback !== "function") return null;

				let button = document.createElement('a');
				footer.insertAdjacentElement('afterbegin', button);

				button.classList = ['btn-flat'];
				let innerDiv = document.createElement('div');
				innerDiv.innerText = buttonText;

				button.appendChild(innerDiv);

				let loader = pages.elements.getLoader();
				loader.style.display = 'none';
				loader.setArea(20);
				loader.classList.remove('center');

				button.appendChild(loader);

				button.setLoading = function (loading) {
					if (loading) {
						loader.style.display = '';
						innerDiv.style.display = 'none';
					} else {
						loader.style.display = '';
						innerDiv.style.display = 'none';
					}
				}

				if (callback) button.onclick = function (event) {
					return callback(event, button, fileUploaderElement);
				};

				return button;
			}

			fileUploaderElement.style.overflow = 'hidden';
			return fileUploaderElement;
		}
	},
	parsers: {
		attachmentsValid: function (attachments_list, write_field) {
			let currentLength = attachments_list.getElementsByTagName('div').length;
			
			if (currentLength > 0) {
				return true;
			} else {
				if (String(write_field.value).isEmpty()) {
					return false;
				} else {
					return true;
				}
			}
		},
		typingText: function (userIdentifiers, typingElement = null) {
			if (!typingElement)
				typingElement = document.createElement('div');

			typingElement.innerHTML = '';
			for (let currentIndex = 0; currentIndex < userIdentifiers.length; currentIndex++) {
				let user_id = userIdentifiers[currentIndex];

				settings.users.get(user_id).then(function (user) {
					if (currentIndex > 2) {

						if (currentIndex === 3) {
							typingElement.innerHTML += settings.lang.getValue('and_more') + ' ' + (userIdentifiers.length - (currentIndex)) + ' ';

							if (settings.lang.getValue("id") === "ru")
								typingElement.innerHTML += pages.parsers.morph((userIdentifiers.length - (currentIndex)), pages.parsers.forms.PEOPLE_RUSSIAN);
							else
								typingElement.innerHTML += settings.lang.getValue("people");
						}

					} else {
						typingElement.innerHTML += (user.name || user.first_name + ' ' + user.last_name.substr(0, 1)) + '.';

						if (currentIndex !== (userIdentifiers.length - 1))
							typingElement.innerHTML += ', ';
					}
				}).catch(function (err) {
					return;
				})
			}
		},		
		durations: function (duration) {
			let minutesCount = parseInt(duration / 60);
			if (minutesCount < 10) minutesCount = '0' + minutesCount;

			let secondsCount = duration - (minutesCount * 60);
			if (secondsCount < 10) secondsCount = '0' + secondsCount;

			return minutesCount + ':' + secondsCount;
		},
		niceString: function (integer) {
			let result = String(integer)

			if (integer >= 1000) result = Number(parseFloat(Number(integer)/1000)).toFixed(1) + "K";
			if (integer >= 1000000) result = Number(parseFloat(Number(integer)/1000000)).toFixed(1) + "М";
			if (integer >= 1000000000) result = Number(parseFloat(Number(integer)/1000000000)).toFixed(1) + "В";

			return result;
		},
		attachmentsString: function (attachmentsList) {
			let doneString = '';

			let list = attachmentsList.getElementsByTagName('div');

			for (let i = 0; i < list.length; i++) {
				if (String(list[i].getAttribute('attachment')) === 'null') continue;

				doneString += list[i].getAttribute('attachment') + ',';
			}

			return doneString;
		},
		attachmentsArray: function (attachmentsList) {
			let doneArray = [];

			let list = attachmentsList.getElementsByTagName('div');
			for (let i = 0; i < list.length; i++) {
				if (!list[i].boundAttachment) continue;

				doneArray.push(list[i].boundAttachment);
			}

			return doneArray;
		},
		menu: {
			posts: {
				buildContextMenu: function (post) {
					if (!settings.users.current)
					 return null;

					let itemsArray = [];
					if (post.owner_id === settings.users.current.user_id) {
						if (post.user_id === settings.users.current.user_id) {
							if (post.is_pinned) {
								itemsArray.push([settings.lang.getValue("unpin"), function () {
									let currentUserId = ui.userVisited || settings.users.current.user_id;
									let posts_div = document.getElementById('posts_list');

									let inNews = true;
									posts_div === null ? posts_div = document.getElementById('news_list') : inNews = false;

									posts.actions.pin(post).then(function (element) {
										posts.getAll(inNews ? 0 : currentUserId, 0, 20).then(function (posts) {
											let currentOffset = 0;

											posts_div.innerHTML = '';
											if (posts.length > 0) {
												for (let i = 0; i < posts.length; i++) {
													let postElement = pages.elements.post(posts[i]);

													posts_div.appendChild(postElement);
												}

												return posts_div.style.display = '';
											} else {
												if (currentOffset === 0) {
													let windowOfAlert = pages.elements.alertWindow(unt.Icon.CLEAR, settings.lang.getValue("no_posts"), settings.lang.getValue("no_posts_t"));
													
													return profileBody.appendChild(windowOfAlert);
												}
											}
										});
									});
								}]);
							} else {
								itemsArray.push([settings.lang.getValue("pin"), function () {
									let currentUserId = settings.users.current.user_id;
									let posts_div = document.getElementById('posts_list');

									let inNews = true;
									posts_div === null ? posts_div = document.getElementById('news_list') : inNews = false;

									posts.actions.pin(post).then(function (element) {
										posts.getAll(inNews ? 0 : currentUserId, 0, 20).then(function (posts) {
											let currentOffset = 0;

											posts_div.innerHTML = '';
											if (posts.length > 0) {
												for (let i = 0; i < posts.length; i++) {
													let postElement = pages.elements.post(posts[i]);

													posts_div.appendChild(postElement);
												}

												return posts_div.style.display = '';
											} else {
												if (currentOffset === 0) {
													let windowOfAlert = pages.elements.alertWindow(unt.Icon.CLEAR, settings.lang.getValue("no_posts"), settings.lang.getValue("no_posts_t"));
													
													return profileBody.appendChild(windowOfAlert);
												}
											}
											
										});
									});
								}]);
							}
						}

						if (!post.event) {
							itemsArray.push([settings.lang.getValue("edit"), function (event, item, list) {
								let editor = pages.elements.postEditor(post);

								return editor.open();
							}]);
						}

						itemsArray.push([settings.lang.getValue("delete"), function (event, item, list) {
							let deletePost = settings.lang.getValue("delete_post_confirm");

							return pages.elements.confirm("", deletePost, function (response) {
								if (response) {
									posts.actions.delete(post.user_id, post.id).then(function (result) {
										if (result) {
											let currentUserId = ui.userVisited || settings.users.current.user_id;
											let posts_div = document.getElementById('posts_list');

											let inNews = true;
											posts_div === null ? posts_div = document.getElementById('news_list') : inNews = false;
											posts.actions.pin(post).then(function (element) {
												posts.getAll(inNews ? 0 : currentUserId, 0, 20).then(function (posts) {
													let currentOffset = 0;

													posts_div.innerHTML = '';
													if (posts.length > 0) {
														for (let i = 0; i < posts.length; i++) {
															let postElement = pages.elements.post(posts[i]);

															posts_div.appendChild(postElement);
														}

														return posts_div.style.display = '';
													} else {
														if (currentOffset === 0) {
															let windowOfAlert = pages.elements.alertWindow(unt.Icon.CLEAR, settings.lang.getValue("no_posts"), settings.lang.getValue("no_posts_t"));
															
															posts_div.style.display = 'none';
															return posts_div.parentNode.appendChild(windowOfAlert);
														}
													}
													
												});
											})
										} else {
											let loadErrorText = settings.lang.getValue("upload_error");

											return unt.toast({html: loadErrorText});
										}
									})
								}
							});
						}]);
					} else {
						itemsArray.push([settings.lang.getValue("report"), new Function()]);

						if (post.user_id === settings.users.current.user_id) {
							itemsArray.push([settings.lang.getValue("delete"), function (event, item, list) {
								let deletePost = settings.lang.getValue("delete_post_confirm");

								return pages.elements.confirm("", deletePost, function (response) {
									if (response) {
										posts.actions.delete(post.user_id, post.id).then(function (result) {
											if (result) {
												let currentUserId = ui.userVisited || settings.users.current.user_id;
												let posts_div = document.getElementById('posts_list');

												let inNews = true;
												posts_div === null ? posts_div = document.getElementById('news_list') : inNews = false;

												posts.actions.pin(post).then(function (element) {
													posts.getAll(inNews ? 0 : currentUserId, 0, 20).then(function (posts) {
														let currentOffset = 0;

														posts_div.innerHTML = '';
														if (posts.length > 0) {
															for (let i = 0; i < posts.length; i++) {
																let postElement = pages.elements.post(posts[i]);

																posts_div.appendChild(postElement);
															}

															return posts_div.style.display = '';
														} else {
															if (currentOffset === 0) {
																let windowOfAlert = pages.elements.alertWindow(unt.Icon.CLEAR, settings.lang.getValue("no_posts"), settings.lang.getValue("no_posts_t"));
																
																posts_div.style.display = 'none';
																return posts_div.parentNode.appendChild(windowOfAlert);
															}
														}
														
													});
												});
											} else {
												let loadErrorText = settings.lang.getValue("upload_error");

												return unt.toast({html: loadErrorText});
											}
										})
									}
								});
							}]);
						}
					}

					return pages.elements.contextMenu(itemsArray)
				},
				openWindowPost: function (post) {
					let modalWindow = document.createElement('div');

					modalWindow.classList.add('modal');
					document.body.appendChild(modalWindow);

					let instance = unt.Modal.init(modalWindow, {
						onCloseEnd: function () {
							return modalWindow.remove();
						}
					});

					let element = pages.elements.post(post, true, true, true);
					element.classList.remove('card');

					modalWindow.appendChild(element);

					return instance.open();
				}
			}
		},
		time: function (timestamp, forMessage = false, withOutHours = false) {
			if (!timestamp) return "";

			let time = new Date(Number(timestamp) * 1000);

			let fullYear = String(time.getFullYear());
			let fullMonth = ((time.getMonth() + 1) < 10) ? ("0" + String(time.getMonth() + 1)) : (String(time.getMonth() + 1));
			let fullDay = ((time.getDate()) < 10) ? ("0" + String(time.getDate())) : (String(time.getDate()));

			let fullHours = ((time.getHours()) < 10) ? ("0" + String(time.getHours())) : (String(time.getHours()));
			let fullMinutes =  ((time.getMinutes()) < 10) ? ("0" + String(time.getMinutes())) : (String(time.getMinutes()));

			let fullTimeString = fullDay + '.' + fullMonth + '.' + fullYear + ', ' + fullHours + ':' + fullMinutes;
			if (forMessage) return fullHours + ':' + fullMinutes;

			if (withOutHours) {
				return fullDay + '.' + fullMonth + '.' + fullYear;
			}

			return fullTimeString;
		},
		forms: {
			MINUTE_RUSSIAN: ["минуту", "минуты", "минут"],
			HOURS_RUSSIAN: ["час", "часа", "часов"],
			DAYS_RUSSIAN: ["день", "дня", "дней"],
			MONTH_RUSSIAN: ["месяц", "месяца", "месяцев"],
			WEEK_RUSSIAN: ["неделю", "недели", "недель"],
			YEAR_RUSSIAN: ["год", "года", "лет"],
			MEMBERS_RUSSIAN: ["участник", "участника", "участников"],
			PEOPLE_RUSSIAN: ["человек", "человека", "человек"]
		},
		attachment: function (attachment, afterRemoveCallback) {
			if (attachment.type === 'photo') {
				let attachmentDiv = document.createElement('div');
				attachmentDiv.boundAttachment = attachment;

				let attachmentElement = document.createElement('img');
				attachmentDiv.appendChild(attachmentElement);

				attachmentElement.src = attachment.photo.url.main;
				attachmentElement.style.width = attachmentElement.style.height = '100%';

				attachmentDiv.style.width = attachmentDiv.style.height = (64 + 'px');
				attachmentDiv.style.padding = 5 + 'px';
				attachmentDiv.style.background = "transparent";
				attachmentDiv.style.cursor = 'pointer';

				attachmentDiv.setAttribute('attachment', 'photo'+attachment.photo.owner_id+'_'+attachment.photo.id+'_'+attachment.photo.access_key);
				
				attachmentDiv.onclick = function () {
					attachmentDiv.remove();

					if (afterRemoveCallback)
						return afterRemoveCallback(attachmentDiv);
				}

				return attachmentDiv;
			}
			if (attachment.type === 'poll') {
				let element = pages.elements.createPollElement(attachment.poll, afterRemoveCallback);
				element.boundAttachment = attachment;

				return element;
			}
		},
		morph: function (number, forms = this.forms.MINUTE_RUSSIAN) {
			number = Math.abs(number) % 100;

			let numberOne = number % 10;

			if (number > 10 && number < 20) return forms[2];
			if (numberOne > 1 && numberOne < 5) return forms[1];

			if (numberOne === 1) return forms[0];

			return forms[2];
		},
		getOnlineState: function (user) {
			let onlineString = "...";
			if (user.is_banned) {
				return (settings.lang.getValue("user_banned"))
			} else if (user.permissions_type === 1) {
				return (settings.lang.getValue("work_account"))
			} else {
				if (user.account_type === "user") {
					if (user.online.is_online) {
						return (settings.lang.getValue("online"))
					} else {
						if (user.online.hidden_online)
							return settings.lang.getValue("hidden_online");

						let timeString = pages.parsers.time(user.online.last_online_time);
						if (timeString === '')
							return settings.lang.getValue("offline");

						return (settings.lang.getValue("was_online").replace("(а)", (user.gender === 2 ? "а" : ""))) + " " + pages.parsers.time(user.online.last_online_time)
					}
				} else {
					return (settings.lang.getValue("bot"));
				}
			}

			return onlineString;
		}
	}
};

function getCurrentTime () {
	return Math.floor(new Date() / 1000);
}

var welcome = {
	hehe: {
		no: {
			goodbye: function () {
				welcome.to.data.is.level = 0;
				welcome.to.data.is.score = 0;

				if (welcome.to.area) {
					welcome.to.area.clear();
					welcome.to.area.remove();
					welcome.to.area = null;
				}

				clearInterval(welcome.to.data.is.local);
				clearTimeout(welcome.to.data.is.timeout);
			},
		},
	},
	to: {
		area: null,
		fail: function (windowEl, header) {
			if (welcome.to.data.is.level !== 0) {
				pages.elements.alert('Level failed.\n\nLevel: ' + welcome.to.data.is.level + '\nScore: ' + welcome.to.data.is.score).then(function () {
					return welcome.to.level(windowEl, header); 
				});

				welcome.to.data.is.level = 0;
				welcome.to.data.is.score = 0;

				clearInterval(welcome.to.data.is.local);
				clearTimeout(welcome.to.data.is.timeout);
			}
		},
		level: function (windowEl, header) {
			if (this.area) {
				this.area.clear();
				this.area.remove();
			}

			let levelSeconds = 60 - (welcome.to.data.is.level * 1);
			if (levelSeconds <= 10)
				levelSeconds = 10;

			let element = document.createElement('div');
			element.clear = function () {
				let rounds = element.querySelectorAll('.game-round');

				for (let i = 0; i < rounds.length; i++) {
					rounds[i].delete();
				}

				clearInterval(welcome.to.data.is.local);
				clearTimeout(welcome.to.data.is.timeout);

				element.innerHTML = '';
			}

			element.classList.add('game-area');

			element.style.width = '100%';
			element.style.height = 'calc(100% - 75px)';

			welcome.to.data.is.level += 1;

			windowEl.appendChild(element);
			header.innerHTML = welcome.to.data.now();

			let currentStateLoader = pages.elements.getLoader(false);
			header.insertAdjacentElement('beforeend', currentStateLoader);
			currentStateLoader.setArea(20);
			currentStateLoader.style.marginLeft = '10px';

			let timeRemains = document.createElement('div');
			timeRemains.style.marginLeft = '5px';
			timeRemains.innerText = levelSeconds;
			header.insertAdjacentElement('beforeend', timeRemains)

			this.area = element;
			this.area.clear();

			let maxRoundsCount = 1;
			if (welcome.to.data.is.level >= 10)
				maxRoundsCount = welcome.to.data.is.level - 9;

			if (maxRoundsCount > 40)
				maxRoundsCount = 40;

			let currentRoundsCount = getRandomInt(1, maxRoundsCount);
			setTimeout(function () {
				for (let i = 0; i < currentRoundsCount; i++) {
					let round = welcome.to.round();

					welcome.to.area.appendChild(round);
				}

				welcome.to.area.addEventListener('click', function (event) {
					if (event.target.classList.contains("game-round")) {
						if (welcome.to.data.is.level === 0) return;

						event.target.delete();

						welcome.to.data.is.score += 1;
						levelstats.innerHTML = welcome.to.data.now();

						if (welcome.to.how.much() === 0) {
							return welcome.to.level(windowEl, header);
						}
					} else {
						return welcome.to.fail(windowEl, header);
					}
				});
			}, 200);

			let fullLevelSeconds = levelSeconds;
			welcome.to.data.is.local = setInterval(function () {
				levelSeconds -= 1;

				currentStateLoader.setProgress(levelSeconds / fullLevelSeconds * 100);
				timeRemains.innerText = levelSeconds;
			}, 1000)

			welcome.to.data.is.timeout = setTimeout(function () {
				return welcome.to.fail(windowEl, header);
			}, levelSeconds * 1000);

			return element;
		},
		how: {
			much: function () {
				let currentLevel = welcome.to.area;
				if (!currentLevel)
					return 0;

				let rounds = currentLevel.querySelectorAll('.game-round');

				return rounds.length;
			},
		},
		round: function () {
			let round = document.createElement('div');
			round.classList.add('game-round');

			let colors = ['blue', 'white', 'pink', 'green', 'red', 'yellow'];
			let currentColor = colors[getRandomInt(0, colors.length - 1)];

			round.style.backgroundColor = currentColor;
			round.style.width = round.style.height = getRandomInt(40, 120) + 'px';
			round.style.borderRadius = '120px';
			round.style.position = 'fixed';

			round.style.top = getRandomInt(80, (this.area.clientHeight - 100)) + 'px';
			round.style.left = getRandomInt(0, (this.area.clientWidth - 100)) + 'px';

			let intervalTime = 2000 - (welcome.to.data.is.level * 30);
			if (intervalTime < 300)
				intervalTime = 300;

			round.boundTimeout = setInterval(function () {
				round.style.top = getRandomInt(80, (welcome.to.area.clientHeight - 100)) + 'px';
				round.style.left = getRandomInt(0, (welcome.to.area.clientWidth - 100)) + 'px';
			}, intervalTime);

			round.delete = function () {
				clearInterval(round.boundTimeout);

				return round.remove();
			}

			return round;
		},
		data: {
			is: {
				level: 0,
				score: 0,
				timeout: 0
			},
			now: function () {
				return '<div id="levelstats">Level: <b>' + this.is.level + '</b>, Score: <b>' + this.is.score + '</b></div>';
			},
		},
		something: {
			game: function () {
				let startWindow = document.createElement('div');

				startWindow.classList.add("modal");
				startWindow.classList.add("bottom-sheet");
				startWindow.classList.add("unselectable");

				startWindow.addEventListener('dragstart', function (event) {
					return event.preventDefault();
				})
				startWindow.addEventListener('dragend', function (event) {
					return event.preventDefault();
				});

				startWindow.style = 'background-color: black !important';
				startWindow.open = function () {
					document.body.appendChild(startWindow);

					let instance = unt.Modal.init(startWindow, {
						onCloseEnd: function () {
							return startWindow.remove();
						}
					});

					if (instance)
						instance.open();

					startWindow.style.top = 0;
					startWindow.style.width = startWindow.style.height = '100%';
					startWindow.style.borderRadius = 0;

					return true;
				}

				let windowHeader = document.createElement('div');
				startWindow.appendChild(windowHeader);

				windowHeader.classList.add('valign-wrapper');
				windowHeader.style.width = '100%';

				let headerText = document.createElement('div');
				windowHeader.appendChild(headerText);
				headerText.classList.add('valign-wrapper');

				headerText.style.width = '100%';
				headerText.style.color = 'white';

				windowHeader.style.padding = '20px';

				headerText.innerHTML = welcome.to.data.now();

				let closeButton = document.createElement('div');
				
				closeButton.style.cursor = 'pointer';
				closeButton.style.marginTop = '5px';
				windowHeader.appendChild(closeButton);
				closeButton.innerHTML = unt.Icon.CLOSE;

				closeButton.getElementsByTagName('svg')[0].style.fill = 'white';

				closeButton.addEventListener('click', function () {
					if (welcome.to.data.is.level === 0) return;

					return pages.elements.confirm('', settings.lang.getValue('logout') + '?', function (response) {
						if (response) {
							clearTimeout(welcome.to.data.is.timeout);

							welcome.hehe.no.goodbye();
							unt.Modal.getInstance(startWindow).close();
						}
					})
				});

				welcome.to.level(startWindow, headerText);

				return startWindow.open();
			},
			out: function () {
				if (!settings.users.current) {
					return welcome.to.something.game();
				}

				let startWindow = document.createElement('div');

				startWindow.classList.add("modal");
				startWindow.classList.add("bottom-sheet");
				startWindow.classList.add("unselectable");

				startWindow.addEventListener('dragstart', function (event) {
					return event.preventDefault();
				})
				startWindow.addEventListener('dragend', function (event) {
					return event.preventDefault();
				});

				startWindow.style = 'background-color: black !important';

				let windowHeader = document.createElement('div');
				startWindow.appendChild(windowHeader);

				windowHeader.classList.add('valign-wrapper');
				windowHeader.style.width = '100%';

				let headerText = document.createElement('div');
				windowHeader.appendChild(headerText);
				headerText.style.width = '100%';
				headerText.style.color = 'white';

				windowHeader.style.padding = '20px';

				headerText.innerText = 'What is it?';

				let closeButton = document.createElement('div');
				
				closeButton.style.cursor = 'pointer';
				closeButton.style.marginTop = '5px';
				windowHeader.appendChild(closeButton);
				closeButton.innerHTML = unt.Icon.CLOSE;

				closeButton.getElementsByTagName('svg')[0].style.fill = 'white';

				closeButton.addEventListener('click', function () {
					return pages.elements.confirm('', settings.lang.getValue('logout') + '?', function (response) {
						if (response) {
							clearTimeout(welcome.to.data.is.timeout);

							welcome.hehe.no.goodbye();

							return unt.Modal.getInstance(startWindow).close();
						}
					})
				});

				let gameContent = document.createElement('div');
				startWindow.appendChild(gameContent);
				gameContent.style.width = '100%';

				let contentDiv = document.createElement('div');

				contentDiv.style.position = 'absolute';
				contentDiv.style.top = '50%';
				contentDiv.style.left = '50%';
				contentDiv.style.marginRight = '-50%';
				contentDiv.style.transform = 'translate(-50%, -50%)';
				gameContent.appendChild(contentDiv);

				let userPhotoDiv = document.createElement('div');
				userPhotoDiv.style.textAlign = '-webkit-center';
				contentDiv.appendChild(userPhotoDiv);
				let userPhoto = document.createElement('img');

				userPhoto.classList.add('circle');
				userPhoto.width = userPhoto.height = 135;
				userPhoto.src = settings.users.current.photo_url;
				userPhotoDiv.appendChild(userPhoto);

				contentDiv.appendChild(document.createElement('br'));

				let userInfo = document.createElement('div');
				userInfo.classList.add('center');
				userInfo.style.color = 'white';
				userInfo.style.fontSize = 'x-large';
				contentDiv.appendChild(userInfo);

				let innerB1 = document.createElement('b');
				userInfo.appendChild(innerB1);
				innerB1.innerText = settings.users.current.first_name + ' ' + settings.users.current.last_name;

				let userLink = document.createElement('div');
				userLink.classList.add('center');
				userLink.style.color = 'lightgrey';
				userLink.style.fontSize = 'large';
				userLink.innerText = "@" + (settings.users.current.screen_name ? settings.users.current.screen_name : ("id" + settings.users.current.user_id));
				contentDiv.appendChild(userLink);

				startWindow.open = function () {
					document.body.appendChild(startWindow);

					let instance = unt.Modal.init(startWindow, {
						onCloseEnd: function () {
							return startWindow.remove();
						}
					});

					if (instance)
						instance.open();

					startWindow.style.top = 0;
					startWindow.style.width = startWindow.style.height = '100%';
					startWindow.style.borderRadius = 0;

					return true;
				}

				let actionsDiv = document.createElement('div');
				startWindow.appendChild(actionsDiv);

				actionsDiv.style.position = 'absolute';
				actionsDiv.style.bottom = 0;
				actionsDiv.style.padding = '20px';
				actionsDiv.style.width = '100%';

				actionsDiv.classList.add('center');

				let startButton = pages.elements.createFAB(unt.Icon.PLAY, function () {
					startWindow.innerHTML = '';
					let contentDiv = document.createElement('div');

					contentDiv.style.position = 'absolute';
					contentDiv.style.top = '50%';
					contentDiv.style.left = '50%';
					contentDiv.style.marginRight = '-50%';
					contentDiv.style.transform = 'translate(-50%, -50%)';
					startWindow.appendChild(contentDiv);

					let loader = pages.elements.getLoader().setColor('white');
					contentDiv.appendChild(loader);

					return setTimeout(function () {
						unt.Modal.getInstance(startWindow).close();

						return welcome.to.something.game();
					}, 2000);
				});
				startButton.classList.remove('fixed-action-btn');
				actionsDiv.appendChild(startButton);

				return startWindow.open();
			}
		}
	}
}