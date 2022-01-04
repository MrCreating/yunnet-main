unt.pages = new Object({
	auth: function (internalData) {
		unt.components.navPanel ? unt.components.navPanel.hide() : null;
		let menu = unt.components.menuElement;

		let authContainer = document.createElement('div');
		authContainer.style.width = '100%';
		menu.appendChild(authContainer);

		let authCard = document.createElement('div');
		authContainer.appendChild(authCard);

		authCard.classList.add('card');

		authContainer.style.textAlign = '-webkit-center';
		authContainer.style.position = 'absolute';
		authContainer.style.left = '50%';
		authContainer.style.marginRight = '-50%';
		authContainer.style.transform = 'translate(-50%, -50%)';
		authContainer.style.top = '45%';
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

		let authResultMessage = document.createElement('div');
		authResultMessage.style.marginBottom = '10px';
		authResultMessage.style.padding = '10px';
		authResultMessage.innerText = unt.settings.lang.getValue('login_to_continue');
		authCard.appendChild(authResultMessage);

		let authForm = unt.actions.authForm();
		authCard.appendChild(authForm);

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
	},
	main: function (internalData) {
		unt.components.navPanel ? unt.components.navPanel.hide() : null;
		let menu = unt.components.menuElement;

		let grantedParams = (new URLParser());

		let app_id = Number(grantedParams.getQueryValue('app_id'));
		let permissions = grantedParams.getQueryValue('permissions').isEmpty() ? [] : String(grantedParams.getQueryValue('permissions') || '').split(',').map(function (element) {
        	return Number(element);
        });
        if (permissions.length > 4)
            permissions.length = 4;
        if (permissions.length === 1 && permissions[0] === "")
            permissions = [];

        let donePermissions = [];
        for (let i = 0; i < permissions.length; i++) {
        	if (donePermissions.indexOf(permissions[i]) !== -1) continue;

        	if (permissions[i] >= 1 && permissions[i] <= 4)
        		donePermissions.push(permissions[i]);
        }
        permissions = donePermissions;

		let authContainer = document.createElement('div');
		authContainer.style.width = '100%';
		menu.appendChild(authContainer);

		authContainer.style.textAlign = '-webkit-center';
		authContainer.style.position = 'absolute';
		authContainer.style.left = '50%';
		authContainer.style.marginRight = '-50%';
		authContainer.style.transform = 'translate(-50%, -50%)';
		authContainer.style.top = '45%';
		
		let mainCard = document.createElement('div');
        mainCard.classList.add('card');
        mainCard.style.padding = '30px';
        authContainer.appendChild(mainCard);
        if (!unt.tools.isMobile()) {
			authContainer.style.maxWidth = '40%';
			authContainer.style.minWidth = '35%';
		} else {
			authContainer.style.maxWidth = '100%';
			authContainer.style.minWidth = '40%';

			if (window.screen.width >= 500)
				mainCard.style.width = parseInt(window.screen.width / 2) + 'px';
			else
				mainCard.style.width = window.screen.width + 'px';
		}

		let accountActions = document.createElement('div');
		mainCard.appendChild(accountActions);
		accountActions.classList.add('valign-wrapper');
		accountActions.classList.add('unselectable');

		let accountInfo = document.createElement('div');
        accountActions.appendChild(accountInfo);
        accountInfo.classList.add('valign-wrapper');
        accountInfo.style.textAlign = 'initial';

        let accountPhoto = document.createElement('img');
        accountPhoto.src = unt.settings.users.current.photo_url;
        accountInfo.appendChild(accountPhoto);
        accountPhoto.classList.add('circle');
        accountPhoto.style.marginRight = '15px';
        accountPhoto.width = accountPhoto.height = 32;

        let accountCredentials = document.createElement('a');
        accountCredentials.innerText = unt.settings.users.current.first_name + ' ' + unt.settings.users.current.last_name;
        accountCredentials.href = (window.location.host.match(/localhost/) ? 'http://localhost' : 'https://yunnet.ru') + '/' + (unt.settings.users.current.screen_name ? unt.settings.users.current.screen_name : ('id' + unt.settings.users.current.user_id));
        accountInfo.appendChild(accountCredentials);
        accountCredentials.setAttribute('target', '_blank');
        accountCredentials.style.color = 'black';

        let authContentDiv = document.createElement('div');
        mainCard.appendChild(authContentDiv);
        authContentDiv.style.marginTop = '20px';

        let accountSettingsAction = document.createElement('a');
        accountActions.appendChild(accountSettingsAction);
        accountSettingsAction.innerHTML = unt.icons.edit;
        accountSettingsAction.style.flex = 'auto';
        accountSettingsAction.style.textAlign = 'end';
        accountSettingsAction.style.cursor = 'pointer';
        accountSettingsAction.addEventListener('click', function () {
        	let win = unt.components.windows.createWindow({title: unt.settings.lang.getValue('actions'), fullScreen: true});
        	let menuItem = win.getMenu();

        	let collectionDiv = document.createElement('div');
        	collectionDiv.classList.add('unselectable');
            collectionDiv.classList.add('collection');
            menuItem.appendChild(collectionDiv);
            collectionDiv.style.padding = '10px';

            let collectionItem = document.createElement('a');
            collectionItem.classList.add('collection-item');
            collectionDiv.appendChild(collectionItem);
            collectionItem.style.cursor = 'pointer';
            collectionItem.innerText = unt.settings.lang.getValue('logout');
            collectionItem.addEventListener('click', function () {
            	return unt.actions.dialog(unt.settings.lang.getValue('logout'), unt.settings.lang.getValue('confirm_exit')).then(function (response) {
            		if (response)
            			return unt.settings.users.current.logout();
            	})
            });

            return win.show();
        });

		let loader = unt.components.loaderElement();
        authContentDiv.appendChild(loader);
        let errorDiv = document.createElement('div');

        errorDiv.hide();
        authContentDiv.appendChild(errorDiv);

        let buttonsDiv = document.createElement('div');
        buttonsDiv.classList.add('valign-wrapper');
        buttonsDiv.style.width = '100%';
        buttonsDiv.style.justifyContent = 'end';

        let declareDiv = document.createElement('a');
        let declareIcon = document.createElement('div');
        declareIcon.classList.add('tooltipped');
        declareIcon.setAttribute('data-position', 'bottom');
        declareIcon.setAttribute('data-tooltip', unt.settings.lang.getValue('cancel'));
        declareDiv.appendChild(declareIcon);
        declareIcon.innerHTML = unt.icons.close;
        let declareDivLoader = unt.components.loaderElement();
        declareDiv.appendChild(declareDivLoader);
        declareIcon.style.cursor = 'pointer';
        declareDiv.style.marginRight = '15px';
        declareDivLoader.setArea(24);
        declareDivLoader.hide();
        buttonsDiv.appendChild(declareDiv);
        declareIcon.addEventListener('click', function () {
        	authContentDiv.innerHTML = '';

			let errorDiv = document.createElement('div');
            errorDiv.classList.add('full_section');
            authContentDiv.appendChild(errorDiv);

            authContentDiv.innerText = unt.settings.lang.getValue('access_denied');
            if (window.opener && grantedParams.getQueryValue('returns')) {
                return window.opener.postMessage({status: -1}, '*');
            }

            if (!decodeURIComponent(grantedParams.getQueryValue('redirect_url')) || !String(decodeURIComponent(grantedParams.getQueryValue('redirect_url'))).isURL())
                return authContentDiv.innerText += '\n\n' + unt.settings.lang.getValue('redirect_url_not_given');
            else {
            	let needRedirect = decodeURIComponent(grantedParams.getQueryValue('redirect_url'));

                let paramDel = '?';
                if (needRedirect.split('?').length > 1)
                    paramDel = '&';

                if (!needRedirect.startsWith('http://') && !needRedirect.startsWith('https://'))
                    needRedirect = 'http://' + needRedirect;
                needRedirect += paramDel + 'result=0&time=' + Math.floor(new Date() / 1000);

                return setTimeout(function () {
                    return window.location.href = needRedirect;
                }, 1000);
            }
        });

        let acceptDiv = document.createElement('a');
        let acceptIcon = document.createElement('div');
        acceptIcon.classList.add('tooltipped');
        acceptIcon.setAttribute('data-position', 'bottom');
        acceptIcon.setAttribute('data-tooltip', unt.settings.lang.getValue('continue'));
        acceptDiv.appendChild(acceptIcon);
        acceptIcon.innerHTML = unt.icons.done;
        let acceptDivLoader = unt.components.loaderElement();
        acceptDiv.appendChild(acceptDivLoader);
        acceptIcon.style.cursor = 'pointer';
        acceptDivLoader.setArea(24);
        acceptDivLoader.hide();
        buttonsDiv.appendChild(acceptDiv);
        acceptIcon.addEventListener('click', function () {
        	acceptIcon.hide();
        	acceptDivLoader.show();
        	declareDiv.hide();

        	return unt.tools.Request({
        		url: '/flex',
        		data: (new POSTData()).append('action', 'resolve_auth').append('app_id', Number(grantedParams.getQueryValue('app_id'))).append('permissions', permissions.join(',')).build(),
        		method: 'POST',
        		success: function (response) {
        			acceptIcon.show();
        			acceptDivLoader.hide();
        			declareDiv.show();

        			try {
        				response = JSON.parse(response);
        				if (response.error) {
        					return unt.toast({html: unt.settings.lang.getValue('upload_error')});
        				}

        				authContentDiv.innerHTML = '';

        				let errorDiv = document.createElement('div');
                        errorDiv.classList.add('full_section');
                        authContentDiv.appendChild(errorDiv);

                        authContentDiv.innerText = unt.settings.lang.getValue('access_granted');
                        if (window.opener && grantedParams.getQueryValue('returns')) {
                        	return window.opener.postMessage({status: 1, token: response.token, tokenId: response.id, user: JSON.parse(JSON.stringify(unt.settings.users.current))}, '*');
                        }

                        if (!decodeURIComponent(grantedParams.getQueryValue('redirect_url')) || !String(decodeURIComponent(grantedParams.getQueryValue('redirect_url'))).isURL()) {
                        	authContentDiv.innerText += '\n\n' + unt.settings.lang.getValue('redirect_url_not_given') + '\n\n';

                        	let tokenField = unt.components.textField('access_key');
                            authContentDiv.appendChild(tokenField);

                            tokenField.getInput().setAttribute('readonly', 'true');
                            tokenField.getInput().value = response.token;

                            let tokenIdField = unt.components.textField('token_id');
                            authContentDiv.appendChild(tokenIdField);

                            tokenIdField.getInput().setAttribute('readonly', 'true');
                            tokenIdField.getInput().value = response.id;

                            let userIdField = unt.components.textField('user_id');
                            authContentDiv.appendChild(userIdField);

                            userIdField.getInput().setAttribute('readonly', 'true');
                            userIdField.getInput().value = unt.settings.users.current.user_id;

                            unt.updateTextFields()
                        } else {
                        	let needRedirect = decodeURIComponent(grantedParams.getQueryValue('redirect_url'));

                            let paramDel = '?';
                            if (needRedirect.split('?').length > 1)
                                paramDel = '&';

                            if (!needRedirect.startsWith('http://') && !needRedirect.startsWith('https://'))
                                needRedirect = 'http://' + needRedirect;
                            needRedirect += paramDel + 'result=1&time=' + Math.floor(new Date() / 1000) + '&access_key=' + response.token + '&token_id=' + response.id + '&user_id=' + unt.settings.users.current.user_id;

                            return setTimeout(function () {
                                return window.location.href = needRedirect;
                            }, 1000);
                        }
        			} catch (e) {
        				console.log(e);
        				return unt.toast({html: unt.settings.lang.getValue('upload_error')});
        			}
        		},
        		error: function () {
        			acceptIcon.show();
        			acceptDivLoader.hide();
        			declareDiv.show();

        			return unt.toast({html: unt.settings.lang.getValue('upload_error')});
        		}
        	});
        });

        if (!grantedParams.getQueryValue('app_id')) {
        	errorDiv.show();
        	loader.hide();
        	errorDiv.innerText = unt.settings.lang.getValue('app_id_is_invalid');

        	authContentDiv.appendChild(buttonsDiv);
        } else {
        	unt.dev.apps.getById(app_id).then(function (appInfo) {
        		loader.hide();

        		let infoHeaderDiv = document.createElement('div');
                authContentDiv.appendChild(infoHeaderDiv);
                infoHeaderDiv.classList.add('unselectable');

                infoHeaderDiv.style.padding = '10px';
                infoHeaderDiv.innerText = unt.settings.lang.getValue('app_requests').replace('%username%', unt.settings.users.current.first_name + ' ' + unt.settings.users.current.last_name)
                                                                                            .replace('%app_name%', appInfo.title);

	            let requestsPermissionsTextDiv = document.createElement('div');
                authContentDiv.appendChild(requestsPermissionsTextDiv);
                requestsPermissionsTextDiv.style.padding = '10px';
                requestsPermissionsTextDiv.classList.add('unselectable');

                if (permissions.length > 0) {
                	requestsPermissionsTextDiv.innerText = unt.settings.lang.getValue('permissions_needed');

                	let collectionDiv = document.createElement('div');
                	collectionDiv.classList.add('unselectable');
                    collectionDiv.classList.add('collection');
                    authContentDiv.appendChild(collectionDiv);
                    collectionDiv.style.padding = '10px';

                    permissions.forEach(function (permissionId) {
                        let permission = Number(permissionId);
                        if (isNaN(permission) || permission > 4 || permission < 1)
                            return;

                        let collectionItem = document.createElement('a');
                        collectionItem.classList.add('collection-item');
                        collectionDiv.appendChild(collectionItem);
                        collectionItem.style.cursor = 'pointer';

                        switch (permission) {
                            case 1:
                                collectionItem.innerText = unt.settings.lang.getValue('friends');
                            break;
                            case 2:
                                collectionItem.innerText = unt.settings.lang.getValue('messages');
                            break;
                            case 3:
                                collectionItem.innerText = unt.settings.lang.getValue('settings');
                            break;
                            case 4:
                                collectionItem.innerText = unt.settings.lang.getValue('management');
                            break;
                        }
                    });
                } else {
                	requestsPermissionsTextDiv.innerText = unt.settings.lang.getValue('permissions_not_get');
                }

                authContentDiv.appendChild(buttonsDiv);
        	}).catch(function () {
        		loader.hide();
        		errorDiv.show();
        		errorDiv.innerText = unt.settings.lang.getValue('app_is_invalid');

        		authContentDiv.appendChild(buttonsDiv);
        	});
        }
	}
});