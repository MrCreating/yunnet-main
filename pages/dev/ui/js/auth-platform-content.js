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
        accountCredentials.href = 'https://yunnet.ru/' + (unt.settings.users.current.screen_name ? unt.settings.users.current.screen_name : ('id' + unt.settings.users.current.user_id));
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

        if (!grantedParams.getQueryValue('app_id')) {
        	errorDiv.show();
        	loader.hide();
        	errorDiv.innerText = unt.settings.lang.getValue('app_id_is_invalid');
        } else {
        	let app_id = Number(grantedParams.getQueryValue('app_id'));

        	unt.dev.apps.getById(app_id).then(function (appInfo) {
        		loader.hide();

        		let infoHeaderDiv = document.createElement('div');
                authContentDiv.appendChild(infoHeaderDiv);
                infoHeaderDiv.classList.add('unselectable');

                infoHeaderDiv.style.padding = '10px';
                infoHeaderDiv.innerText = unt.settings.lang.getValue('app_requests').replace('%username%', unt.settings.users.current.first_name + ' ' + unt.settings.users.current.last_name)
                                                                                            .replace('%app_name%', appInfo.title);
        	
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
        	}).catch(function () {
        		loader.hide();
        		errorDiv.show();
        		errorDiv.innerText = unt.settings.lang.getValue('app_is_invalid');
        	});
        }
	}
});