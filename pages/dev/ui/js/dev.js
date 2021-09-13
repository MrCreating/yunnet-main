const dev = {
	auth: {
		agree: function (appId = 0, permissions = []) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'resolve_auth');
				data.append('app_id', Number(appId));
				data.append('permissions', permissions.join(','));
				return ui.Request({
					url: '/flex',
					data: data,
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);

						if (response.error)
							return reject(new TypeError('Unable to fetch the token'));

						return resolve(response);
					}
				});
			});
		},
	},
	go: function (url = window.location.href, back = false, onlyParse = false, refresh = false, indicateInHistory = true, internalData = null) {
		if (settings.data.test.isBlocked) return unt.toast({html: 'API is blocked. Unable to go.'});

	    try {
	      	if (ui.isMobile()) unt.Sidenav.getInstance(langs).close();
	      	else unt.Dropdown.getInstance(langs).close();
	    } catch (e) {}

	    if (document.getElementsByClassName('image-view')[0])
	     	return document.getElementsByClassName('image-view')[0].remove();

	    if (!refresh) {
	      	if (back) {
	        	url = ui.urls[ui.urls.length-1];
	        	if (!url) url = "/";

	        	ui.urls.splice(ui.urls.length-1, 1);
	        	if (ui.urls.length <= 0) ui.canBack = false;
	      	} else {
	        	if (!onlyParse) {
	          		ui.urls.push(window.location.href);
	          		ui.canBack = true;
	        	}
	      	}
	    }

	    let elements = document.getElementsByClassName('modal');
	    for (let i = 0; i < elements.length; i++) {
	      	let instance = unt.Modal.getInstance(elements[i])

	      	if (instance) instance.close();
	    }

	    if (!onlyParse) history.pushState(null, document.title, url);
	    return dev.parse(null, internalData);
	},
	parse: function (newElement = null, internalData = null) {
		let elementsOfCarousel = document.getElementsByClassName('carousel');
    	for (let i = 0; i < elementsOfCarousel.length; i++) elementsOfCarousel[i].remove();

		let url = window.location.href.split(window.location.host)[1].split('?')[0];
	    if (ui.isMobile()) {
	    	nav_back_arrow_icon.style.display = "none";
	    	nav_burger_icon.style.display = "";
	    	nav_header_title.innerText = "";

	    	if (ui.canBack) {
        		nav_back_arrow_icon.style.display = "";
        		nav_burger_icon.style.display = "none";
      		}
	  	}

	  	let tempUrl = String(String(String(url).split(window.location.host)[1]).split("?")[0]);
	    if (tempUrl === 'undefined') tempUrl = url;

	    if (dev.isUrlDefault(tempUrl)) {
	    	if (tempUrl === '/') dev.pages.main(internalData);
	  		if (tempUrl === '/methods') dev.pages.methods(internalData);
	  		if (tempUrl === '/apps') dev.pages.apps(internalData);
	  		if (tempUrl === '/bots') dev.pages.bots(internalData);
	    } else {
	    	dev.pages.notfound();
	    }

	    ui.bindItems();
	    return unt.AutoInit();
	},
	isUrlDefault: function (url) {
		let defaultUrls = [
			'/', '/methods', '/apps', '/bots'
		];

		url = String(url.split('?')[0]).toLowerCase();
    	
    	return defaultUrls.indexOf(url) !== -1;
	},
	actions: {
		methods: {
			execute: function (method, params) {
				return new Promise(function (resolve, reject) {
					let data = new FormData();

					for (let key in params) {
						data.append(key, params[key]);
					}

					let x = _xmlHttpGet();

					x.onreadystatechange = function () {
						if (x.readyState !== 4) return;

						let response = x.responseText;
						
						return resolve(JSON.parse(response));
					}

					x.open('POST', 'https://api.yunnet.ru/' + method + '?auth=local');
					x.withCredentials = true;

					return x.send(data);
				});
			},
			getInfo: function (methodName) {
				if (!dev.actions.methods.getInfo.list)
					dev.actions.methods.getInfo.list = {};

				return new Promise(function (resolve, reject) {
					if (dev.actions.methods.getInfo.list[methodName])
						return resolve(dev.actions.methods.getInfo.list[methodName]);

					let data = new FormData();

					data.append('action', 'get_method_info');
					data.append('method_name', String(methodName));
					
					return ui.Request({
						url: '/methods',
						data: data,
						method: 'POST',
						success: function (response) {
							try {
								return resolve(dev.actions.methods.getInfo.list[methodName] = JSON.parse(response));
							} catch (e) {
								return reject(e);
							}
						}
					});
				});
			},
			getList: function () {
				return new Promise(function (resolve, reject) {
					if (dev.actions.methods.getList.list)
						return resolve(dev.actions.methods.getList.list);

					let data = new FormData();

					data.append('action', 'get_methods_list');
					return ui.Request({
						url: '/methods',
						data: data,
						method: 'POST',
						success: function (response) {
							try {
								return resolve(dev.actions.methods.getList.list = JSON.parse(response));
							} catch (e) {
								return reject(e);
							}
						}
					});
				});
			}
		}
	},
	tokens: {
		create: function (objectPermissions = [], bot_id = 0, app_id = 0) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'create_token');
				data.append('permissions', objectPermissions.join(','));

				if (bot_id !== 0)
					data.append('bot_id', Number(bot_id));
				if (app_id !== 0)
					data.append('app_id', Number(app_id));

				return ui.Request({
					data: data,
					url: '/apps',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to fetch tokens'));

						return resolve(response.response);
					}
				});
			});
		},
		updatePermissions: function (token_id, objectPermissions = []) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'update_token');
				data.append('token_id', Number(token_id) || 0);
				data.append('permissions', objectPermissions.join(','));

				return ui.Request({
					data: data,
					url: '/apps',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to fetch tokens'));

						return resolve(response.success);
					}
				});
			});
		},
		delete: function (token_id) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'delete_token');
				data.append('token_id', Number(token_id));
				return ui.Request({
					data: data,
					url: '/apps',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to fetch tokens'));

						return resolve(response);
					}
				});
			});
		}
	},
	bots: {
		getTokens: function (bot_id) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'get_tokens');
				data.append('bot_id', Number(bot_id));
				return ui.Request({
					data: data,
					url: '/bots',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to fetch tokens'));

						return resolve(response);
					}
				});
			});
		},
		create: function (bot_name = '') {
			return new Promise(function (resolve, reject) {
				if (bot_name.isEmpty()) return reject(new TypeError('Title is empty'));
				if (bot_name.length > 64) return reject(new TypeError('Title is too long'));

				let data = new FormData();

				data.append('action', 'create_bot');
				data.append('bot_name', bot_name);
				return ui.Request({
					url: '/bots',
					method: 'POST',
					data: data,
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to create a new bot'));

						return resolve(response);
					}
				});
			});
		},
		setScreenName: function (bot_id, new_screen_name = '') {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('bot_id', Number(bot_id));
				data.append('action', 'change_screen_name');
				data.append('new_screen_name', String(new_screen_name));
				
				return ui.Request({
					data: data,
					method: 'POST',
					url: '/bots',
					success: function (response) {
						response = JSON.parse(response);

						if (response.error) {
							let error = new TypeError('Unable to change screen name.');

							if (response.error_message)
								error.errorMessage = response.error_message;

							return reject(error);
						}

						resolve(response.state);
					}
				});
			});
		},
		setPrivacy: function (bot_id, groupId, newValue) {
			let groups = [
                'can_write_messages',
                'can_write_on_wall',
                'can_invite_to_chats'
            ];

            let currentGroup = groups[Number(groupId)-1];
            if (!currentGroup) return false;
            if (isNaN(Number(newValue))) return false;

            if (Number(newValue) < 0 || Number(newValue) > 2) return false;

            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'set_privacy_settings');
                data.append('bot_id', Number(bot_id));
                data.append('group_id', Number(groupId));
                data.append('new_value', Number(newValue));

                return ui.Request({
                    url: '/bots',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.unauth) return settings.reLogin();

                        if (response.error) return reject(new TypeError('Settings changing failed'));
                        
                        return resolve(true);
                    }
                });
            });
		},
		setTitle: function (bot_id, new_title) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'set_title');
				data.append('bot_id', Number(bot_id));
				data.append('new_title', String(new_title));

				return ui.Request({
					data: data,
					url: '/bots',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to change photo!'));

						return resolve(response.state);
					}
				});
			});
		},
		setPhoto: function (bot_id, attachment = null) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('bot_id', Number(bot_id));
				if (attachment) {
					data.append('action', 'set_photo');
					data.append('photo', String(attachment));
				} else {
					data.append('action', 'delete_photo');
				}

				return ui.Request({
					url: '/bots',
					data: data,
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to change photo!'));

						return resolve(response.state);
					}
				});
			});
		},
		get: function () {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'get_bots_list');
				return ui.Request({
					url: '/flex',
					data: data,
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);

						if (response.error) return reject(new TypeError('Unable to fetch bots list.'));
						if (response.unauth) return settings.reLogin();

						return resolve(response);
					}
				});
			});
		}
	},	
	apps: {
		getTokens: function (app_id) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'get_tokens');
				data.append('app_id', Number(app_id));
				return ui.Request({
					data: data,
					url: '/apps',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to fetch tokens'));

						return resolve(response);
					}
				});
			});
		},
		create: function (app_title = '') {
			return new Promise(function (resolve, reject) {
				if (app_title.isEmpty()) return reject(new TypeError('Title is empty'));
				if (app_title.length > 64) return reject(new TypeError('Title is too long'));

				let data = new FormData();

				data.append('action', 'create_app');
				data.append('app_title', app_title);
				return ui.Request({
					url: '/apps',
					method: 'POST',
					data: data,
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to create a new app'));

						return resolve(response);
					}
				});
			});
		},
		delete: function (app_id) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'delete_app');
				data.append('app_id', Number(app_id) || 0);

				return ui.Request({
					data: data,
					method: 'POST',
					url: '/apps',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to change photo!'));

						return resolve(response.success);
					}
				});
			});
		},
		setTitle: function (app_id, new_title) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'set_title');
				data.append('app_id', Number(app_id));
				data.append('new_title', String(new_title));

				return ui.Request({
					data: data,
					url: '/apps',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to change photo!'));

						return resolve(response.success);
					}
				});
			});
		},
		setPhoto: function (app_id, attachment = null) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('app_id', Number(app_id));
				if (attachment) {
					data.append('action', 'set_photo');
					data.append('photo', String(attachment));
				} else {
					data.append('action', 'delete_photo');
				}

				return ui.Request({
					url: '/apps',
					data: data,
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to change photo!'));

						return resolve(response.success);
					}
				});
			});
		},
		getById: function (app_id) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'get_app_by_id');
				data.append('app_id', Number(app_id) || 0);
				return ui.Request({
					url: '/flex',
					data: data,
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);

						if (response.error) return reject(new TypeError('Unable to fetch app info.'));

						return resolve(response);
					}
				});
			});
		},
		get: function (page = 1, count = 30) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'get_apps_list');
				data.append('offset', ((Number(page) * 30) - 30) || 0);
				data.append('count', Number(count) || 30);

				return ui.Request({
					url: '/flex',
					data: data,
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);

						if (response.error) return reject(new TypeError('Unable to fetch apps list.'));
						if (response.unauth) return settings.reLogin();

						return resolve(response);
					}
				});
			});
		}
	},
	pages: {
		notfound: function () {
			let menuBody = pages.elements.menuBody().clear();

			let profileCard = document.createElement('div');
			profileCard.classList = ['card full_section'];

			menuBody.appendChild(profileCard);

			let notFoundText = document.createElement('div');
			profileCard.appendChild(notFoundText);

			notFoundText.innerText = settings.lang.getValue('user_not_found');
			notFoundText.classList.add('center');

			document.title = 'yunNet.';
		},
		sub: {
			tokenMenu: function (mode, type, id, menuBody, tokenObject = {}) {
				if (mode === 1) {
					let cardItem = document.createElement('div');
					cardItem.classList.add('card');
					cardItem.classList.add('full_section');

					let inputToken = pages.elements.createInputField(settings.lang.getValue('token'), true).setType('text');
					cardItem.appendChild(inputToken);

					inputToken.getInput().setAttribute('readonly', 'true');
					menuBody.appendChild(cardItem);

					inputToken.getInput().value = tokenObject.token;
				}

				let objectPermissions = tokenObject.permissions || [];

				let permissionsDiv = document.createElement('div');
				menuBody.appendChild(permissionsDiv);

				permissionsDiv.classList.add('card');
				permissionsDiv.classList.add('full_section');

				let permissionsHeader = document.createElement('div');
				permissionsDiv.appendChild(permissionsHeader);
				permissionsHeader.innerText = settings.lang.getValue('set_new_permissions') + ':';

				let permissionsInfo = document.createElement('div');
				permissionsDiv.appendChild(permissionsInfo);
				permissionsInfo.classList.add('collection');

				let friendsPermission = document.createElement('a');
				friendsPermission.classList.add('collection-item');
				friendsPermission.innerText = settings.lang.getValue('friends');

				let messagesPermission = document.createElement('a');
				messagesPermission.classList.add('collection-item');
				messagesPermission.innerText = settings.lang.getValue('messages');

				let settingsPermission = document.createElement('a');
				settingsPermission.classList.add('collection-item');
				settingsPermission.innerText = settings.lang.getValue('settings');

				let managementPermission = document.createElement('a');
				managementPermission.classList.add('collection-item');
				managementPermission.innerText = settings.lang.getValue('management');

				permissionsInfo.appendChild(friendsPermission);
				permissionsInfo.appendChild(messagesPermission);
				permissionsInfo.appendChild(settingsPermission);
				permissionsInfo.appendChild(managementPermission);

				if (objectPermissions.indexOf(1) !== -1)
					friendsPermission.classList.add('active');
				if (objectPermissions.indexOf(2) !== -1)
					messagesPermission.classList.add('active');
				if (objectPermissions.indexOf(3) !== -1)
					settingsPermission.classList.add('active');
				if (objectPermissions.indexOf(4) !== -1)
					managementPermission.classList.add('active');

				friendsPermission.onclick = function () {
					friendsPermission.classList.toggle('active');

					let index = objectPermissions.indexOf(1);
					if (index !== -1) objectPermissions.splice(index, 1);
					else objectPermissions.push(1);
				}
				messagesPermission.onclick = function () {
					messagesPermission.classList.toggle('active');

					let index = objectPermissions.indexOf(2);
					if (index !== -1) objectPermissions.splice(index, 1);
					else objectPermissions.push(2);
				}
				settingsPermission.onclick = function () {
					settingsPermission.classList.toggle('active');

					let index = objectPermissions.indexOf(3);
					if (index !== -1) objectPermissions.splice(index, 1);
					else objectPermissions.push(3);
				}
				managementPermission.onclick = function () {
					managementPermission.classList.toggle('active');

					let index = objectPermissions.indexOf(4);
					if (index !== -1) objectPermissions.splice(index, 1);
					else objectPermissions.push(4);
				}

				menuBody.appendChild(pages.elements.createButton('', settings.lang.getValue('continue'), function () {
					if (mode === 0) {
						ui.go(window.location.href);

						return dev.tokens.create(objectPermissions, (type === 'bots' ? id : 0), (type === 'apps' ? id : 0)).then(function (response) {
							let token = response.token;

							let objectWindow = pages.elements.createWindow();

							let footer = objectWindow.getFooter();
							let content = objectWindow.getContent();

							footer.remove();
							let inputField = pages.elements.createInputField(settings.lang.getValue('token'), true).setType('text');
							inputField.getInput().setAttribute('readonly', 'true');
							inputField.getInput().value = response.token;

							let headerDiv = document.createElement('div');
							headerDiv.innerText = settings.lang.getValue('key_created');

							content.appendChild(headerDiv);
							content.appendChild(inputField);
						}).catch(function (error) {
							return unt.toast({html: settings.lang.getValue('upload_error')});
						});
					}
					if (mode === 1) {
						return dev.tokens.updatePermissions(tokenObject.id, objectPermissions).then(function (response) {
							return unt.toast({html: settings.lang.getValue('saved')});
						}).catch(function (error) {
							return unt.toast({html: settings.lang.getValue('upload_error')});
						});
					}
				}));

				if (mode === 1) {
					menuBody.appendChild(pages.elements.createButton('', settings.lang.getValue('delete_key'), function () {
						return pages.elements.confirm('', (settings.lang.getValue('delete_key') + '?'), function (response) {
							if (response) {
								return dev.tokens.delete(tokenObject.id).then(function () {
									return ui.go(window.location.href, false, false, false, false);
								}).catch(function (error) {
									return unt.toast({html: settings.lang.getValue('upload_error')});
								});
							}
						})
					}, 'red'));
				}
			},
			tokensEditor: function (type, internalData) {
				let menuBody = pages.elements.menuBody().clear();
				let stringText = 'tokens';

				if (internalData.tokenAction === 'create') stringText = 'create_token';
				if (internalData.tokenAction === 'edit') stringText = 'edit';

				if (ui.isMobile()) {
			    	nav_header_title.innerText = settings.lang.getValue(stringText);
			  	} else if (ui.canBack) {
			  		menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue(stringText)), function (event) {
						return ui.go(null, true);
					});
			  	}

			  	let currentObject = internalData[type.substr(0, 3)];

			  	if (internalData.tokenAction === 'create') {
			  		return dev.pages.sub.tokenMenu(0, type, (type === 'apps' ? internalData.app.id : internalData.bot.bot_id), menuBody);
			  	} else if (internalData.tokenAction === 'edit') {
			  		return dev.pages.sub.tokenMenu(1, type, 0, menuBody, internalData.token);
			  	} else {
			  		menuBody.appendChild(pages.elements.createButton(unt.Icon.ADD, settings.lang.getValue('create_token'), function () {
				  		internalData.tokenAction = 'create';

				  		return ui.go(window.location.href, false, false, false, true, internalData);
				  	}));

				  	let loader = pages.elements.getLoader();
				  	loader.style.marginTop = '15px';

				  	menuBody.appendChild(loader);

				  	let tokensDiv = document.createElement('div');
				  	menuBody.appendChild(tokensDiv);

				  	tokensDiv.classList.add('card');
				  	tokensDiv.classList.add('collection');

				  	tokensDiv.style.display = 'none';
				  	return dev[type].getTokens(currentObject.bot_id || currentObject.id).then(function (response) {
				  		if (response.length > 0) {
				  			response.forEach(function (tokenObject) {
				  				let tokenItem = pages.elements.tokenItem(tokenObject, function (event) {
				  					internalData.tokenAction = 'edit';
				  					internalData.token = tokenObject;

									return ui.go('https://' + window.location.host + '/' + type + '?action=edit&section=tokens', false, false, false, true, internalData);
				  				});

				  				tokensDiv.appendChild(tokenItem);
				  			});

				  			tokensDiv.style.display = '';
				  			return loader.hide();
				  		}

				  		loader.hide();

				  		return menuBody.appendChild(pages.elements.alertWindow(unt.Icon.DEV, settings.lang.getValue('no_keys_bot'), settings.lang.getValue('no_keys_text')));
			  		}).catch(function (err) {
			  			loader.hide();

			  			return menuBody.appendChild(pages.elements.uploadError());
			  		})
			  	}
			},
		},
		main: function () {
			if (settings.users.current && settings.users.current.is_banned) return pages.unauth.banned();
			ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("main") : null;
			
			let menuBody = pages.elements.menuBody().clear();

			menuBody.appendChild(pages.elements.alertWindow(unt.Icon.DEV, settings.lang.getValue('welcome_dev'), settings.lang.getValue('welcome_dev_text')));
		},
		methods: function () {
			if (settings.users.current && settings.users.current.is_banned) return pages.unauth.banned();
			ui.isMobile() ? nav_header_title.innerText = "API" : null;

			let menuBody = pages.elements.menuBody().clear();
			let section = (new URLParser(window.location.href)).parse().section || '';

			let sectionsList = ['getstarted', 'methods', 'method', 'widgets', 'keyboard'];
			let currentIndex = sectionsList.indexOf(section);

			if (currentIndex === -1) {
				menuBody.appendChild(pages.elements.createButton(unt.Icon.DEV, settings.lang.getValue('get_started'), function () {
					return ui.go('https://' + window.location.host + '/methods?section=getstarted');
				}));
				menuBody.appendChild(pages.elements.createButton(unt.Icon.ATTACHMENT, settings.lang.getValue('methods_list'), function () {
					return ui.go('https://' + window.location.host + '/methods?section=methods');
				}));
				menuBody.appendChild(pages.elements.createButton(unt.Icon.GROUP, settings.lang.getValue('widgets_and_auth'), function () {
					return ui.go('https://' + window.location.host + '/methods?section=widgets');
				}));
			} else {
				if (section === sectionsList[0]) {
					return dev.methods.getstarted();
				}
				if (section === sectionsList[1]) {
					return dev.methods.methodslist();
				}
				if (section === sectionsList[2]) {
					return dev.methods.methodinfo();
				}
				if (section === sectionsList[3]) {
					return dev.methods.widgets();
				}
				if (section === sectionsList[4]) {
					return dev.methods.keyboard();
				}
			}
		},
		apps: function (internalData) {
			if (settings.users.current && settings.users.current.is_banned) return pages.unauth.banned();
			ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("apps") : null;

			let menuBody = pages.elements.menuBody().clear();
			if (!settings.users.current) return window.location.href = 'https://yunnet.ru/';

			let currentUrl = (new URLParser(window.location.href).parse());
			if (currentUrl.action === 'edit') {
				if (internalData && internalData.app && (internalData.app.owner_id === settings.users.current.user_id)) {
					if (currentUrl.section === 'tokens') {
			  			return dev.pages.sub.tokensEditor('apps', internalData);
			  		}

					if (ui.isMobile()) {
			    		nav_header_title.innerText = settings.lang.getValue('edit_app');
			  		} else if (ui.canBack) {
			  			menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('edit_app')), function (event) {
							return ui.go(null, true);
						});
			  		}

			  		let editData = pages.chats.elements.window();
			  		menuBody.appendChild(editData);

			  		let editInput = editData.getElementsByTagName('input')[0];
			  		let editImg = editData.getElementsByTagName('img')[0];

			  		editInput.placeholder = settings.lang.getValue('application_title');
			  		editInput.value = internalData.app.title;
			  		editImg.src = internalData.app.photo_url;

			  		editInput.oninput = function (event) {
			  			if (editInput.value.isEmpty() || editInput.value === internalData.app.title) return editData.getCreateButton().classList.add('scale-out');

			  			return editData.getCreateButton().classList.remove('scale-out');
			  		}

			  		editImg.onclick = function (event) {
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

												let atttachmentData = 'photo' + attachment.photo.owner_id + '_' + attachment.photo.id + '_' + attachment.photo.access_key;

												editImg.src = attachment.photo.url.main;
												return dev.apps.setPhoto(internalData.app.id, atttachmentData).then(function () {
													return uploader.close();
												})
											}).catch(function (err) {
												uploader.setLoading(false);

												return unt.toast({html: settings.lang.getValue("upload_error")});
											});
										})
							},
							afterClose: function () {}
						}).open().addFooterItem(settings.lang.getValue("delete_a_photo"), function (event, button, uploader) {
							editImg.src = 'https://dev.yunnet.ru/images/default.png';
							return dev.apps.setPhoto(internalData.app.id)	.then(function () {
								return uploader.close();
							})
						});
			  		}

			  		editData.getCreateButton().onclick = function () {
			  			editInput.classList.remove('wrong');
			  			if (editInput.value.length > 32) return editInput.classList.add('wrong');

			  			editInput.disabled = true;
			  			editData.getCreateButton().classList.add('scale-out');

			  			return dev.apps.setTitle(internalData.app.id, editInput.value).then(function () {
			  				editInput.disabled = false;

			  				internalData.app.title = editInput.value;
			  			}).catch(function (error) {

			  				editData.getCreateButton().classList.remove('scale-out');
			  				editInput.disabled = false;

			  				return unt.toast({html: settings.lang.getValue('upload_error')});
			  			})
			  		}

			  		menuBody.appendChild(pages.elements.createButton('', settings.lang.getValue('tokens'), function (event) {
						return ui.go('https://' + window.location.host + '/apps?action=edit&section=tokens', false, false, false, true, internalData);
					}));

			  		menuBody.appendChild(pages.elements.createButton('', settings.lang.getValue('delete_application'), function (event) {
			  			return pages.elements.confirm('', settings.lang.getValue('delete_application') + '?', function (response) {
			  				if (response) {
			  					return dev.apps.delete(internalData.app.id).then(function (result) {
			  						return ui.go('https://' + window.location.host + '/apps');
			  					}).catch(function (err) {
			  						return unt.toast({html: settings.lang.getValue('upload_error')});
			  					});
			  				}
			  			});
			  		}, 'red'));

			  		return;
				}

				return ui.go('https://' + window.location.host + '/apps', false, false, false, false, null);
			} else {
				let loader = pages.elements.getLoader();
				loader.style.marginTop = '15px';

				menuBody.appendChild(loader);
				
				let appsList = document.createElement('div');
				menuBody.appendChild(appsList);
				appsList.style.display = 'none';
				appsList.classList = ['card collection'];

				menuBody.appendChild(pages.elements.createFAB(unt.Icon.ADD, function () {
					let modalWindow = pages.elements.createWindow();

					let continueButton = document.createElement('a');
					continueButton.innerText = settings.lang.getValue('continue');
					continueButton.classList = ['btn-flat waves-effect'];

					let windowHeader = document.createElement('div');
					windowHeader.innerText = settings.lang.getValue('create_app');
					modalWindow.getContent().appendChild(windowHeader);

					modalWindow.getContent().appendChild(document.createElement('br'));

					let inputField = pages.elements.createInputField(settings.lang.getValue('application_title'), false).maxLength(64).setType('text');
					modalWindow.getContent().appendChild(inputField);

					continueButton.onclick = function (event) {
						inputField.getInput().classList.remove('wrong');

						if (inputField.getValue().isEmpty() || inputField.getValue().length > 64) return inputField.getInput().classList.add('wrong');

						continueButton.classList.add('disabled');
						inputField.getInput().disabled = true;

						return dev.apps.create(inputField.getValue()).then(function (result) {
							return ui.go('https://' + window.location.host + '/apps');
						}).catch(function (err) {
							continueButton.classList.remove('disabled');
						
							inputField.getInput().disabled = false;
							return unt.toast({html: settings.lang.getValue('upload_error')});
						});
					}

					modalWindow.getFooter().appendChild(continueButton);
				}));

				return dev.apps.get().then(function (apps) {
					if (apps.length <= 0) {
						loader.hide();

						return menuBody.appendChild(pages.elements.alertWindow(unt.Icon.CLEAR, settings.lang.getValue('no_apps'), settings.lang.getValue('no_apps_text')));
					}

					apps.forEach(function (app) {
						let appElement = pages.elements.appItem(app);
						appElement.onclick = function (event) {
							event.preventDefault();

							return ui.go('/apps?action=edit', false, false, false, true, {
								app: app
							});
						}

						let secondaryContentDiv = document.createElement('div');
						appElement.appendChild(secondaryContentDiv);
						secondaryContentDiv.classList.add('secondary-content');
						secondaryContentDiv.innerHTML = unt.Icon.EDIT;

						appsList.appendChild(appElement);
					});

					loader.hide();
					appsList.style.display = '';
				}).catch(function (error) {
					let uploadError = pages.elements.uploadError();

					loader.hide();
					return menuBody.appendChild(uploadError);
				})
			}

			let loader = pages.elements.getLoader();
			loader.style.marginTop = '15px';

			menuBody.appendChild(loader);
		},
		bots: function (internalData) {
			if (settings.users.current && settings.users.current.is_banned) return pages.unauth.banned();
			ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("bots") : null;

			let menuBody = pages.elements.menuBody().clear();
			if (!settings.users.current) return window.location.href = 'https://yunnet.ru/';

			let currentUrl = (new URLParser(window.location.href).parse());

			if (currentUrl.action === 'edit') {
				if (internalData && internalData.bot && (internalData.bot.owner_id === settings.users.current.user_id)) {
					if (currentUrl.section === 'tokens') {
			  			return dev.pages.sub.tokensEditor('bots', internalData);
			  		}

					if (ui.isMobile()) {
			    		nav_header_title.innerText = settings.lang.getValue('edit_bot');
			  		} else if (ui.canBack) {
			  			menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('edit_bot')), function (event) {
							return ui.go(null, true);
						});
			  		}

			  		let editData = pages.chats.elements.window();
			  		menuBody.appendChild(editData);

			  		let editInput = editData.getElementsByTagName('input')[0];
			  		let editImg = editData.getElementsByTagName('img')[0];

			  		editInput.placeholder = settings.lang.getValue('bot_name');
			  		editInput.value = internalData.bot.name;
			  		editImg.src = internalData.bot.photo_url;

			  		editInput.oninput = function (event) {
			  			if (editInput.value.isEmpty() || editInput.value === internalData.bot.name) return editData.getCreateButton().classList.add('scale-out');

			  			return editData.getCreateButton().classList.remove('scale-out');
			  		}

			  		editImg.onclick = function (event) {
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

												let atttachmentData = 'photo' + attachment.owner_id + '_' + attachment.id + '_' + attachment.access_key;

												editImg.src = attachment.url.main;
												return dev.bots.setPhoto(internalData.bot.bot_id, atttachmentData).then(function () {
													return uploader.close();
												})
											}).catch(function (err) {
												uploader.setLoading(false);

												return unt.toast({html: settings.lang.getValue("upload_error")});
											});
										})
							},
							afterClose: function () {}
						}).open().addFooterItem(settings.lang.getValue("delete_a_photo"), function (event, button, uploader) {
							editImg.src = 'https://dev.yunnet.ru/images/default.png';

							return dev.bots.setPhoto(internalData.bot.bot_id).then(function () {
								return uploader.close();
							})
						});
			  		}

			  		editData.getCreateButton().onclick = function () {
			  			editInput.classList.remove('wrong');
			  			if (editInput.value.length > 32) return editInput.classList.add('wrong');

			  			editInput.disabled = true;
			  			editData.getCreateButton().classList.add('scale-out');

			  			return dev.bots.setTitle(internalData.bot.bot_id, editInput.value).then(function () {
			  				editInput.disabled = false;

			  				internalData.bot.name = editInput.value;
			  			}).catch(function (error) {
			  				editData.getCreateButton().classList.remove('scale-out');
			  				editInput.disabled = false;

			  				return unt.toast({html: settings.lang.getValue('upload_error')});
			  			})
			  		}

			  		let screenNameDiv = document.createElement('div');
			  		screenNameDiv.classList = ['card full_section'];
					menuBody.appendChild(screenNameDiv);

					let inputField = pages.elements.createInputField(settings.lang.getValue('bot_link'), true).maxLength(32).setType('text');
					screenNameDiv.appendChild(inputField);

					inputField.getInput().value = internalData.bot.screen_name || 'bot' + internalData.bot.bot_id;

					let saveButton = pages.elements.createFAB(unt.Icon.SAVE, function (event) {
						saveButton.getElementsByTagName('a')[0].classList.add('disabled');
						inputField.getInput().disabled = true;

						return dev.bots.setScreenName(internalData.bot.bot_id, inputField.getInput().value).then(function (result) {
							saveButton.getElementsByTagName('a')[0].classList.remove('disabled');
							inputField.getInput().disabled = false;

							saveButton.classList.add('scale-out');
							if (result === 1) {
								internalData.bot.screen_name = inputField.getInput().value;
							}
							if (result === 0) {
								delete internalData.bot.screen_name;

								inputField.getInput().value = ('bot' + internalData.bot.bot_id);
							}
						}).catch(function (error) {
							saveButton.getElementsByTagName('a')[0].classList.remove('disabled');
							inputField.getInput().disabled = false;

							return unt.toast({html: (error.errorMessage ? error.errorMessage : (settings.lang.getValue("upload_error")))});
						})
					});

					inputField.getInput().oninput = function () {
						if (inputField.getInput().value === internalData.bot.screen_name) {
							saveButton.classList.add('scale-out');
						} else {
							saveButton.classList.remove('scale-out');
						}
					}

					saveButton.classList.remove('fixed-action-btn');

					saveButton.classList.add('scale-transition');
					saveButton.classList.add('scale-out');

					let saveButtonDiv = document.createElement('div');
					saveButtonDiv.appendChild(saveButton);
					screenNameDiv.appendChild(saveButtonDiv);

					saveButtonDiv.style.width = '100%';
					saveButtonDiv.style.textAlign = 'end';

					menuBody.appendChild(pages.elements.createButton('', settings.lang.getValue('tokens'), function (event) {
						return ui.go('https://' + window.location.host + '/bots?action=edit&section=tokens', false, false, false, true, internalData);
					}));

			  		let callPrivacyGroup = pages.elements.createCollapsible([
						[unt.Icon.MESSAGES, settings.lang.getValue('who_can_write_to_bot')],
						[unt.Icon.WALL, settings.lang.getValue('who_can_write_on_bot_wall')],
						[unt.Icon.CHATS, settings.lang.getValue('who_can_invite_to_chat_bot')]
					]);
					menuBody.appendChild(callPrivacyGroup);

					callPrivacyGroup.getBody(0).appendChild(pages.elements.createSelector('can_write_messages', [
						[
							settings.lang.getValue('all'),
							function () { dev.bots.setPrivacy(internalData.bot.bot_id, 1, 0) }
						], [
							settings.lang.getValue('only_subscribers'),
							function () { dev.bots.setPrivacy(internalData.bot.bot_id, 1, 1) }
						], [
							settings.lang.getValue('nobody'),
							function () { dev.bots.setPrivacy(internalData.bot.bot_id, 1, 2) }
						]
					]).selectItem(internalData.bot.privacy.can_write_messages));

					callPrivacyGroup.getBody(1).appendChild(pages.elements.createSelector('can_write_on_wall', [
						[
							settings.lang.getValue('all'),
							function () { dev.bots.setPrivacy(internalData.bot.bot_id, 2, 0) }
						], [
							settings.lang.getValue('only_subscribers'),
							function () { dev.bots.setPrivacy(internalData.bot.bot_id, 2, 1) }
						], [
							settings.lang.getValue('nobody'),
							function () { dev.bots.setPrivacy(internalData.bot.bot_id, 2, 2) }
						]
					]).selectItem(internalData.bot.privacy.can_write_on_wall));

					callPrivacyGroup.getBody(2).appendChild(pages.elements.createSelector('can_comment_posts', [
						[
							settings.lang.getValue('all'),
							function () { dev.bots.setPrivacy(internalData.bot.bot_id, 3, 0) }
						], [
							settings.lang.getValue('only_subscribers'),
							function () { dev.bots.setPrivacy(internalData.bot.bot_id, 3, 1) }
						], [
							settings.lang.getValue('nobody'),
							function () { dev.bots.setPrivacy(internalData.bot.bot_id, 3, 2) }
						]
					]).selectItem(internalData.bot.privacy.can_invite_to_chats));

			  		return;
				}

				return ui.go('https://' + window.location.host + '/bots', false, false, false, false, null);
			} else {
				let loader = pages.elements.getLoader();
				loader.style.marginTop = '15px';

				menuBody.appendChild(loader);
				
				let botsList = document.createElement('div');
				menuBody.appendChild(botsList);
				botsList.style.display = 'none';
				botsList.classList = ['card collection'];

				return dev.bots.get().then(function (bots) {
					if (bots.length < 30) {
						menuBody.appendChild(pages.elements.createFAB(unt.Icon.ADD, function () {
							let modalWindow = pages.elements.createWindow();

							let continueButton = document.createElement('a');
							continueButton.innerText = settings.lang.getValue('continue');
							continueButton.classList = ['btn-flat waves-effect'];

							let windowHeader = document.createElement('div');
							windowHeader.innerText = settings.lang.getValue('create_bot');
							modalWindow.getContent().appendChild(windowHeader);

							modalWindow.getContent().appendChild(document.createElement('br'));

							let inputField = pages.elements.createInputField(settings.lang.getValue('bot_name'), false).maxLength(64).setType('text');
							modalWindow.getContent().appendChild(inputField);

							continueButton.onclick = function (event) {
								inputField.getInput().classList.remove('wrong');

								if (inputField.getValue().isEmpty() || inputField.getValue().length > 64) return inputField.getInput().classList.add('wrong');

								continueButton.classList.add('disabled');
								inputField.getInput().disabled = true;

								return dev.bots.create(inputField.getValue()).then(function (result) {
									return ui.go('https://' + window.location.host + '/bots');
								}).catch(function (err) {
									continueButton.classList.remove('disabled');
								
									inputField.getInput().disabled = false;
									return unt.toast({html: settings.lang.getValue('upload_error')});
								});
							}

							modalWindow.getFooter().appendChild(continueButton);
						}));
					}

					if (bots.length <= 0) {
						loader.hide();

						return menuBody.appendChild(pages.elements.alertWindow(unt.Icon.CLEAR, settings.lang.getValue('no_bots'), settings.lang.getValue('no_bots_text')));
					}

					bots.forEach(function (bot) {
						let botElement = pages.elements.userItem(bot);
						botElement.onclick = function (event) {
							event.preventDefault();

							return ui.go('/bots?action=edit', false, false, false, true, {
								bot: bot
							});
						}

						let secondaryContentDiv = document.createElement('div');
						botElement.appendChild(secondaryContentDiv);
						secondaryContentDiv.classList.add('secondary-content');
						secondaryContentDiv.innerHTML = unt.Icon.EDIT;

						botsList.appendChild(botElement);
					});
					
					loader.hide();
					botsList.style.display = '';
				}).catch(function (err) {
					let uploadError = pages.elements.uploadError();

					loader.hide();
					return menuBody.appendChild(uploadError);
				})
			}
		}
	},

	settings: {
		lang: {
			values: {},
			getValue: function (value = '*', update = true) {
				if (dev.settings.lang.values[value] && !update) return dev.settings.lang.values[value];
				if (!dev.settings.lang.values[value] && !update) return '';

				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'get_dev_lang_value');
					data.append('value', value);
					return ui.Request({
						url: '/flex',
						data: data,
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error)
								return reject(new TypeError('Unable to find language value'));

							if (value === '*')
								dev.settings.lang.values = response[0];
							else
								dev.settings.lang.values[value] = response.value;

							return resolve(response.value || response);
						}
					});
				})
			},
		}
	},

	methods: {
		editors: {
			keyboard: function () {
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

				let windowHeader = document.createElement('div');
				startWindow.appendChild(windowHeader);

				windowHeader.classList.add('valign-wrapper');
				windowHeader.style.width = '100%';

				let headerText = document.createElement('div');
				windowHeader.appendChild(headerText);
				headerText.style.width = '100%';

				windowHeader.style.padding = '20px';

				headerText.innerText = settings.lang.getValue('keyboard_editor');

				let closeButton = document.createElement('div');
				
				closeButton.style.cursor = 'pointer';
				closeButton.style.marginTop = '5px';
				windowHeader.appendChild(closeButton);
				closeButton.innerHTML = unt.Icon.CLOSE;

				closeButton.addEventListener('click', function () {
					return unt.Modal.getInstance(startWindow).close();
				});

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

				return startWindow.open();
			}
		},
		widgets: function () {
			let menuBody = pages.elements.menuBody().clear();

			let settingsData = settings.get();
			if (settingsData && settingsData.theming.backButton && ui.canBack && !ui.isMobile()) {
				menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('widgets_and_auth')), function (event) {
					return ui.go(null, true);
				});
			}

			if (ui.isMobile())
				nav_header_title.innerText = settings.lang.getValue('widgets_and_auth');

			menuBody.appendChild(pages.elements.createButton(unt.Icon.KEYBOARD, dev.settings.lang.getValue('bots_keyboard', false), function () {
				return ui.go('https://' + window.location.host + '/methods?section=keyboard');
			}));
		},
		keyboard: function () {
			let menuBody = pages.elements.menuBody().clear();

			let settingsData = settings.get();
			if (settingsData && settingsData.theming.backButton && ui.canBack && !ui.isMobile()) {
				menuBody.appendChild(pages.elements.backArrowButton(dev.settings.lang.getValue('bots_keyboard', false)), function (event) {
					return ui.go(null, true);
				});
			}

			if (ui.isMobile())
				nav_header_title.innerText = dev.settings.lang.getValue('bots_keyboard', false);

			let para1 = document.createElement('div');
			para1.classList.add('card');
			para1.classList.add('full_section');

			menuBody.appendChild(para1);

			let keyboardHeader = document.createElement('div');
			keyboardHeader.classList.add('center');

			let hB = document.createElement('b');
			keyboardHeader.appendChild(hB);
			hB.innerText = dev.settings.lang.getValue('bots_keyboard', false);
			para1.appendChild(keyboardHeader);

			para1.appendChild(document.createElement('br'));

			let para1text = document.createElement('div');
			para1text.innerHTML = dev.settings.lang.getValue('bots_keyboard_1', false);
			para1.appendChild(para1text);

			let pre = document.createElement('pre');
			para1.appendChild(pre);

			let code = document.createElement('code');
			pre.appendChild(code);

			code.style.backgroundColor = 'aliceblue';
			code.innerHTML = 
`[
  [
    {...}, {...}, {...}
  ],
  [
    {...}, {...}
  ]
]`;

			let para1cont = document.createElement('div');
			para1.appendChild(para1cont);
			para1cont.innerHTML = dev.settings.lang.getValue('bots_keyboard_2', false);

			let pre2 = document.createElement('pre');
			para1.appendChild(pre2);

			let code2 = document.createElement('code');
			pre2.appendChild(code2);

			code2.style.backgroundColor = 'aliceblue';
			code2.innerHTML = 
`{
  params: {
    autoShow: false || true,
    oneTime: false || true
  },
  keyboard: [
    [
      {
        id: 1,
        color: [42, 42, 42],
        textColor: [0, 0, 0],
        text: 'Hello! :)'
      }
    ],
    [
      {
        id: 1,
        color: [255, 255, 255],
        textColor: [0, 0, 0],
        text: 'Goodbye! :)'
      }
    ]
  ]
}`;
			
			menuBody.appendChild(pages.elements.createButton(unt.Icon.ADD, settings.lang.getValue('create_keyboard'), function () {
				return dev.methods.editors.keyboard();
			}));
		},
		methodinfo: function () {
			let menuBody = pages.elements.menuBody().clear();

			let settingsData = settings.get();
			if (settingsData && settingsData.theming.backButton && ui.canBack && !ui.isMobile()) {
				menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('methods_list')), function (event) {
					return ui.go(null, true);
				});
			}

			if (ui.isMobile())
				nav_header_title.innerText = settings.lang.getValue('methods_list');

			let loader = pages.elements.getLoader();
			loader.style.marginTop = '15px';

			menuBody.appendChild(loader);
			return dev.actions.methods.getInfo((new URLParser(window.location.href)).parse().method).then(function (info) {
				loader.hide();

				let headerDiv = document.createElement('div');
				headerDiv.classList.add('card');
				headerDiv.classList.add('full_section');
				headerDiv.classList.add('center');
				menuBody.appendChild(headerDiv);

				let methodNameDiv = document.createElement('b');
				methodNameDiv.innerText = info.header;
				headerDiv.appendChild(methodNameDiv);

				headerDiv.appendChild(document.createElement('br'));
				headerDiv.appendChild(document.createElement('br'));

				let descriptionDiv = document.createElement('div');
				descriptionDiv.innerHTML = info.description;
				headerDiv.appendChild(descriptionDiv);

				if (info.params && info.params.length > 0) {
					let paramsInfo = document.createElement('div');
					paramsInfo.classList.add('card');
					paramsInfo.classList.add('full_section');
					paramsInfo.classList.add('center');

					let paramsHeader = document.createElement('b');
					paramsHeader.innerText = dev.settings.lang.getValue('params', false);
					paramsInfo.appendChild(paramsHeader);
					menuBody.appendChild(paramsInfo);

					let paramsTable = document.createElement('table');
					paramsTable.classList.add('striped');

					let paramsHead = document.createElement('thead');
					paramsTable.appendChild(paramsHead);

					let tr = document.createElement('tr');
					paramsHead.appendChild(tr);

					let paramNameTh = document.createElement('th');
					let paramDescTh = document.createElement('th');

					paramsHead.appendChild(paramNameTh);
					paramsHead.appendChild(paramDescTh);

					paramNameTh.innerText = dev.settings.lang.getValue('param_name', false);
					paramDescTh.innerText = dev.settings.lang.getValue('param_desc', false);

					paramsInfo.appendChild(paramsTable);

					let tableBody = document.createElement('tbody');
					paramsTable.appendChild(tableBody);
					info.params.forEach(function (paramName) {
						let row = document.createElement('tr');

						let td = document.createElement('td');
						td.innerText = paramName;
						row.appendChild(td);

						let td2 = document.createElement('td');
						td2.innerHTML = dev.settings.lang.getValue(paramName + '_param', false);
						row.appendChild(td2);

						tableBody.appendChild(row);
					});
				} else {
					let noParams = document.createElement('div');
					noParams.classList.add('card');
					noParams.classList.add('full_section');
					noParams.classList.add('center');

					noParams.innerText = dev.settings.lang.getValue('no_params', false);
					menuBody.appendChild(noParams);
				}

				if (settings.users.current) {
					let requestExample = document.createElement('div');
					requestExample.classList.add('card');
					requestExample.classList.add('full_section');
					requestExample.classList.add('center');

					let exampleHeader = document.createElement('b');
					exampleHeader.innerText = dev.settings.lang.getValue('request', false);
					requestExample.appendChild(exampleHeader);

					menuBody.appendChild(requestExample);

					let paramsTable = document.createElement('table');
					paramsTable.classList.add('striped');
					let paramsHead = document.createElement('thead');
					paramsTable.appendChild(paramsHead);
					let tr = document.createElement('tr');
					paramsHead.appendChild(tr);

					let paramNameTh = document.createElement('th');
					let paramDescTh = document.createElement('th');

					paramsHead.appendChild(paramNameTh);
					paramsHead.appendChild(paramDescTh);

					paramNameTh.innerText = dev.settings.lang.getValue('params', false);
					paramDescTh.innerText = dev.settings.lang.getValue('result', false);

					requestExample.appendChild(paramsTable);

					let tableBody = document.createElement('tbody');
					paramsTable.appendChild(tableBody);

					let row = document.createElement('tr');

					let td = document.createElement('td');
					row.appendChild(td);

					let td2 = document.createElement('td');
					row.appendChild(td2);
					tableBody.appendChild(row);

					let paramsForm = document.createElement('div');
					td.appendChild(paramsForm);

					td.style.width = '40%';
					info.params.forEach(function (paramName) {
						let paramField = pages.elements.createInputField(paramName, false);
						paramField.classList.add(paramName + '_param');

						paramsForm.appendChild(paramField);
					});

					let resultDiv = document.createElement('div');
					td2.appendChild(resultDiv);

					let pre = document.createElement('pre');
					resultDiv.appendChild(pre);

					let code = document.createElement('code');
					pre.appendChild(code);

					code.style.wordBreak = 'break-word';
					code.style.whiteSpace = 'pre-wrap';
					code.style.width = '100%';

					let runButton = pages.elements.createFAB(unt.Icon.SAVE, function () {
						runLoader.style.display = '';
						runButton.style.display = 'none';

						let params = {};

						info.params.forEach(function (paramName) {
							let value = paramsForm.getElementsByClassName(paramName + '_param')[0].getValue();

							params[paramName] = value;
						});

						return dev.actions.methods.execute(info.header, params).then(function (response) {
							runLoader.style.display = 'none';
							runButton.style.display = '';

							return code.innerText = JSON.stringify(response, null, 4);
						}).catch(function (err) {
							runLoader.style.display = 'none';
							runButton.style.display = '';

							return unt.toast({html: settings.lang.getValue('upload_error')})
						})
					});

					let runLoader = pages.elements.getLoader();
					runLoader.style.display = 'none';

					runButton.classList.remove('fixed-action-btn');
					runButton.classList.add('center');

					paramsForm.appendChild(runButton);
					paramsForm.appendChild(runLoader);
				}
			}).catch(function (err) {
				loader.hide();

				return menuBody.appendChild(pages.elements.uploadError());
			})
		},
		methodslist: function () {
			let menuBody = pages.elements.menuBody().clear();

			let settingsData = settings.get();
			if (settingsData && settingsData.theming.backButton && ui.canBack && !ui.isMobile()) {
				menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('methods_list')), function (event) {
					return ui.go(null, true);
				});
			}

			if (ui.isMobile())
				nav_header_title.innerText = settings.lang.getValue('methods_list');

			let loader = pages.elements.getLoader();
			loader.style.marginTop = '15px';

			menuBody.appendChild(loader);
			return dev.actions.methods.getList().then(function (methodsList) {
				loader.hide();

				let items = [];

				for (let methodsGroupName in methodsList) {
					items.push([
						unt.Icon[methodsGroupName.toUpperCase()],
						methodsGroupName
					]);
				};

				let collapsible = pages.elements.createCollapsible(items);

				for (let i = 0; i < items.length; i++) {
					let body = collapsible.getBody(i);

					let methodslist = document.createElement('div');
					methodslist.classList.add('collection');

					let methodsGroupName = items[i][1];
					for (let i = 0; i < methodsList[methodsGroupName].length; i++) {
						let methodLink = document.createElement('a');
						methodLink.classList.add('collection-item');

						methodLink.href = '/methods?section=method&method=' + methodsGroupName + '.' + methodsList[methodsGroupName][i];

						methodLink.innerText = (methodsGroupName + '.' + methodsList[methodsGroupName][i] + ' - ' + dev.settings.lang.getValue(methodsGroupName + '.' + methodsList[methodsGroupName][i], false));
						methodslist.appendChild(methodLink);
					}

					body.appendChild(methodslist);
				}

				menuBody.appendChild(collapsible);

				ui.bindItems();
				return unt.AutoInit();
			}).catch(function (err) {
				loader.hide();

				return menuBody.appendChild(pages.elements.uploadError());
			})
		},
		getstarted: function () {
			let menuBody = pages.elements.menuBody().clear();

			let settingsData = settings.get();
			if (settingsData && settingsData.theming.backButton && ui.canBack && !ui.isMobile()) {
				menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('get_started')), function (event) {
					return ui.go(null, true);
				});
			}

			if (ui.isMobile())
				nav_header_title.innerText = settings.lang.getValue('get_started');

			let mainInfoDiv = document.createElement('div');
			mainInfoDiv.classList.add('card');
			mainInfoDiv.classList.add('full_section');

			let headerDiv = document.createElement('div');
			mainInfoDiv.appendChild(headerDiv);
			headerDiv.style.width = '100%';
			headerDiv.classList.add('center');

			let b = document.createElement('b');
			headerDiv.appendChild(b);
			b.innerText = settings.lang.getValue('get_started');

			mainInfoDiv.appendChild(document.createElement('br'));

			let infoDiv = document.createElement('div');
			mainInfoDiv.appendChild(infoDiv);

			let part1 = document.createElement('div');
			part1.innerHTML = dev.settings.lang.getValue('get_started_1', false);
			infoDiv.appendChild(part1);
			infoDiv.appendChild(document.createElement('br'));

			let apiLink = document.createElement('pre');
			apiLink.style = 'white-space: pre-wrap;background-color: #f0f0f0;padding: 5px;';
			apiLink.innerHTML = '<code>https://api.yunnet.ru/{method}?key={access_key}&{param1}={value1}&{param2}={value2}</code>';
			infoDiv.appendChild(apiLink);

			/////////////////

			let tokensInfoDiv = document.createElement('div');
			tokensInfoDiv.classList.add('card');
			tokensInfoDiv.classList.add('full_section');

			let headerDiv2 = document.createElement('div');
			tokensInfoDiv.appendChild(headerDiv2);
			headerDiv2.style.width = '100%';
			headerDiv2.classList.add('center');

			let b2 = document.createElement('b');
			headerDiv2.appendChild(b2);
			b2.innerText = dev.settings.lang.getValue('get_keys', false);
			tokensInfoDiv.appendChild(document.createElement('br'));

			let infoDiv2 = document.createElement('div');
			tokensInfoDiv.appendChild(infoDiv2);

			let part2 = document.createElement('div');
			part2.innerHTML = dev.settings.lang.getValue('get_started_2', false);
			infoDiv2.appendChild(part2);

			///////////////////

			let appsInfoDIv = document.createElement('div');
			appsInfoDIv.classList.add('card');
			appsInfoDIv.classList.add('full_section');

			let headerDiv3 = document.createElement('div');
			appsInfoDIv.appendChild(headerDiv3);
			headerDiv3.style.width = '100%';
			headerDiv3.classList.add('center');

			let b3 = document.createElement('b');
			headerDiv3.appendChild(b3);
			b3.innerText = dev.settings.lang.getValue('apps_and_bots', false);
			appsInfoDIv.appendChild(document.createElement('br'));

			let infoDiv3 = document.createElement('div');
			appsInfoDIv.appendChild(infoDiv3);

			let part3 = document.createElement('div');
			part3.innerHTML = dev.settings.lang.getValue('get_started_3', false);
			infoDiv3.appendChild(part3);

			menuBody.appendChild(mainInfoDiv);
			menuBody.appendChild(tokensInfoDiv);
			menuBody.appendChild(appsInfoDIv);
		}
	}
};