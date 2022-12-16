window.addEventListener('message', function (event) {
    window.sentData = event.data;
});

const settings = {
    widgets: {
        auth: {
            init: function () {
                document.title = 'yunNet. Auth';
                if (!settings.users.current)
                    document.body.style = 'background: #e0f2f1 !important';
                document.body.innerHTML = '';

                if (ui.isMobile()) {
                    document.body.innerHTML = '<div id="menu"></div>';
                } else {
                    document.body.innerHTML = '<div class="row"><div class="col s3"></div><div class="col s6"></div><div class="col s3"></div></div>'
                }

                let grantedParams = (new URLParser()).parse();

                let menuBody = pages.elements.menuBody();

                let untLogoDiv = document.createElement('div');
                untLogoDiv.style.textAlign = '-webkit-center';
                untLogoDiv.style.padding = '25px';
                menuBody.appendChild(untLogoDiv);

                let untLogo = document.createElement('img');
                untLogo.src = '/favicon.ico';
                untLogo.classList.add('circle');
                untLogo.style.cursor = 'pointer';
                untLogo.width = untLogo.height = 96;
                untLogoDiv.appendChild(untLogo);

                if (!window.opener && !grantedParams.returns)
                    untLogo.addEventListener('click', function () {
                        return pages.elements.confirm('', settings.lang.getValue('return_to_auth'), function (result) {
                            if (result)
                                return window.location.reload();
                        });
                    });

                let mainCard = document.createElement('div');
                mainCard.classList.add('card');
                mainCard.classList.add('full_section');

                let loader = pages.elements.getLoader();
                mainCard.appendChild(loader);
                menuBody.appendChild(mainCard);

                let errorDiv = document.createElement('div');
                errorDiv.classList.add('full_section');
                mainCard.appendChild(errorDiv);
                if (!grantedParams.app_id) {
                    errorDiv.innerText = settings.lang.getValue('app_id_is_invalid');

                    return loader.hide();
                } else {
                    dev.apps.getById(grantedParams.app_id).then(function (appInfo) {
                        errorDiv.style.display = 'none';

                        let infoHeaderDiv = document.createElement('div');
                        mainCard.appendChild(infoHeaderDiv);

                        infoHeaderDiv.style.padding = '10px';

                        if (settings.users.current) {
                            infoHeaderDiv.innerText = settings.lang.getValue('app_requests').replace('%username%', settings.users.current.first_name + ' ' + settings.users.current.last_name)
                                                                                            .replace('%app_name%', appInfo.title);

                            let permissions = String(grantedParams.permissions || '').split(',');
                            if (permissions.length > 4)
                                permissions.length = 4;
                            if (permissions.length === 1 && permissions[0] === "")
                                permissions = [];

                            let requestsPermissionsTextDiv = document.createElement('div');
                            mainCard.appendChild(requestsPermissionsTextDiv);
                            requestsPermissionsTextDiv.style.padding = '10px';

                            if (permissions.length > 0) {
                                requestsPermissionsTextDiv.innerText = settings.lang.getValue('permissions_needed');

                                let collectionDiv = document.createElement('div');
                                collectionDiv.classList.add('collection');
                                mainCard.appendChild(collectionDiv);
                                collectionDiv.style.padding = '10px';

                                permissions.forEach(function (permissionId) {
                                    let permission = Number(permissionId);
                                    if (isNaN(permission) || permission > 4 || permission < 1)
                                        return;

                                    let collectionItem = document.createElement('a');
                                    collectionItem.classList.add('collection-item');
                                    collectionDiv.appendChild(collectionItem);

                                    switch (permission) {
                                        case 1:
                                            collectionItem.innerText = settings.lang.getValue('friends');
                                        break;
                                        case 2:
                                            collectionItem.innerText = settings.lang.getValue('messages');
                                        break;
                                        case 3:
                                            collectionItem.innerText = settings.lang.getValue('settings');
                                        break;
                                        case 4:
                                            collectionItem.innerText = settings.lang.getValue('management');
                                        break;
                                    }
                                });
                            } else {
                                requestsPermissionsTextDiv.innerText = settings.lang.getValue('permissions_not_get');
                            }

                            let actionsDiv = document.createElement('div');
                            actionsDiv.classList.add('valign-wrapper');
                            mainCard.appendChild(actionsDiv);
                            actionsDiv.style.justifyContent = 'flex-end';

                            let agreeLoader = pages.elements.getLoader();
                            agreeLoader.style.display = 'none';
                            agreeLoader.style.marginLeft = '20px';

                            let authInProcess = false;

                            let logoutFAB = pages.elements.createFAB(unt.Icon.LOGOUT, function () {
                                if (authInProcess) return;

                                return settings.Logout(1);
                            });
                            let denyFAB = pages.elements.createFAB(unt.Icon.CLOSE, function () {
                                if (authInProcess) return;

                                mainCard.innerHTML = '';

                                let errorDiv = document.createElement('div');
                                errorDiv.classList.add('full_section');
                                mainCard.appendChild(errorDiv);

                                mainCard.innerText = settings.lang.getValue('access_denied');
                                if (window.opener && grantedParams.returns) {
                                    return window.opener.postMessage({status: -1}, '*');
                                }

                                if (!decodeURIComponent(grantedParams.redirect_url) || !String(decodeURIComponent(grantedParams.redirect_url)).isURL())
                                    return mainCard.innerText += '\n\n' + settings.lang.getValue('redirect_url_not_given');
                                else {
                                    let needRedirect = decodeURIComponent(grantedParams.redirect_url);

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
                            let agreeFAB = pages.elements.createFAB(unt.Icon.SAVE, function () {
                                if (authInProcess) return;
                                authInProcess = true;

                                logoutFAB.classList.add('scale-out');
                                denyFAB.classList.add('scale-out');

                                agreeLoader.style.display = '';
                                agreeFAB.style.display = 'none';

                                return dev.auth.agree(Number(grantedParams.app_id) || 0, permissions).then(function (result) {
                                    authInProcess = false;

                                    logoutFAB.classList.remove('scale-out');
                                    denyFAB.classList.remove('scale-out');

                                    agreeLoader.style.display = 'none';
                                    agreeFAB.style.display = '';

                                    mainCard.innerHTML = '';

                                    let errorDiv = document.createElement('div');
                                    errorDiv.classList.add('full_section');
                                    mainCard.appendChild(errorDiv);

                                    mainCard.innerText = settings.lang.getValue('access_granted');
                                    if (window.opener && grantedParams.returns) {
                                        return window.opener.postMessage({status: 1, token: result.token, tokenId: result.id, user: settings.users.current}, '*');
                                    }

                                    if (!decodeURIComponent(grantedParams.redirect_url) || !String(decodeURIComponent(grantedParams.redirect_url)).isURL()){
                                        mainCard.innerText += '\n\n' + settings.lang.getValue('redirect_url_not_given') + '\n\n';

                                        let tokenField = pages.elements.createInputField('access_key', true).setType('text');
                                        mainCard.appendChild(tokenField);

                                        tokenField.getInput().setAttribute('readonly', 'true');
                                        tokenField.getInput().value = result.token;

                                        let tokenIdField = pages.elements.createInputField('token_id', true).setType('number');
                                        mainCard.appendChild(tokenIdField);

                                        tokenIdField.getInput().setAttribute('readonly', 'true');
                                        tokenIdField.getInput().value = result.id;

                                        let userIdField = pages.elements.createInputField('user_id', true).setType('number');
                                        mainCard.appendChild(userIdField);

                                        userIdField.getInput().setAttribute('readonly', 'true');
                                        userIdField.getInput().value = settings.users.current.user_id;
                                    } else {
                                        let needRedirect = decodeURIComponent(grantedParams.redirect_url);

                                        let paramDel = '?';
                                        if (needRedirect.split('?').length > 1)
                                            paramDel = '&';

                                        if (!needRedirect.startsWith('http://') && !needRedirect.startsWith('https://'))
                                            needRedirect = 'http://' + needRedirect;
                                        needRedirect += paramDel + 'result=1&time=' + Math.floor(new Date() / 1000) + '&access_key=' + result.token + '&token_id=' + result.id + '&user_id=' + settings.users.current.user_id;

                                        return setTimeout(function () {
                                            return window.location.href = needRedirect;
                                        }, 1000);
                                    }
                                }).catch(function (err) {
                                    authInProcess = false;

                                    logoutFAB.classList.remove('scale-out');
                                    denyFAB.classList.remove('scale-out');

                                    agreeLoader.style.display = 'none';
                                    agreeFAB.style.display = '';

                                    return unt.toast({html: settings.lang.getValue('upload_error')});
                                });
                            });

                            logoutFAB.classList.remove('fixed-action-btn');
                            denyFAB.classList.remove('fixed-action-btn');
                            denyFAB.style.marginLeft = '20px';
                            agreeFAB.classList.remove('fixed-action-btn');
                            agreeFAB.style.marginLeft = '20px';
                            logoutFAB.classList.add('scale-transition');
                            agreeFAB.classList.add('scale-transition');
                            denyFAB.classList.add('scale-transition');

                            actionsDiv.appendChild(logoutFAB);
                            actionsDiv.appendChild(denyFAB);

                            actionsDiv.appendChild(agreeLoader);
                            actionsDiv.appendChild(agreeFAB);
                        } else {
                            infoHeaderDiv.innerText = settings.lang.getValue('for_app_auth').replace('%app_name%', appInfo.title);

                            let loginField = pages.elements.createInputField(settings.lang.getValue('login'));
                            let passwordField = pages.elements.createInputField(settings.lang.getValue('password'));

                            let loginForm = document.createElement('form');
                            loginForm.style.padding = '5px';
                            mainCard.appendChild(loginForm);

                            let errorMessage = document.createElement('div');
                            loginForm.appendChild(errorMessage);
                            errorMessage.classList = ['collection incorrect_message'];
                            errorMessage.style.display = 'none';
                            let collectionItem = document.createElement('a');
                            errorMessage.appendChild(collectionItem);
                            collectionItem.classList.add('collection-item');
                            collectionItem.style = 'background-color: #42a5f5 !important; color: white; margin-left: 3% !important; margin-right: 3% !important';
                            collectionItem.innerHTML = settings.lang.getValue('incorrect_login');

                            passwordField.setType('password');
                            loginField.setType('email');

                            loginForm.appendChild(loginField);
                            loginForm.appendChild(passwordField);

                            let loginButtonDiv = document.createElement('div');
                            loginForm.appendChild(loginButtonDiv);
                            loginButtonDiv.classList.add("center");
                            let loginButton = document.createElement('button');
                            loginButton.action = 'submit';
                            loginForm.onsubmit = function (event) {
                                return event.preventDefault();
                            }
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
                                return account.auth(loginButton, event, loginField.getInput(), passwordField.getInput());
                            }

                            loginForm.appendChild(document.createElement('br'));

                            let registerDiv = document.createElement('div');
                            loginForm.appendChild(registerDiv);
                            registerDiv.classList.add("center");
                            let linkToReg = document.createElement('a');
                            registerDiv.appendChild(linkToReg);
            
                            linkToReg.style.cursor = 'pointer';
                            linkToReg.innerText = settings.lang.getValue("regstart");
                            linkToReg.onclick = function (event) {
                                event.preventDefault();

                                return pages.elements.alert(settings.lang.getValue('after_registration')).then(function (response) {
                                    return pages.elements.confirm('', settings.lang.getValue('reg_privacy_warning'), function (response) {
                                        if (response) {
                                            let win = window.open('/register', 'yunNet.', 'resizable,scrollbars,status');

                                            let cancelled = false;

                                            if (win) {
                                                window.addEventListener('message', function (event) {
                                                    if (event.data === 'success') {
                                                        window.location.refresh();

                                                        return win.close();
                                                    }
                                                    if (event.data === 'cancelled') {
                                                        if (cancelled) return;

                                                        cancelled = true;

                                                        pages.elements.alert(settings.lang.getValue('register_cancel'));

                                                        return win.close();
                                                    }
                                                });
                                            }
                                        }
                                    });
                                });
                            }

                            ui.bindItems();
                        }

                        return loader.hide();
                    }).catch(function (err) {
                        errorDiv.innerText = settings.lang.getValue('app_is_invalid');

                        return loader.hide();
                    })
                }
            }
        }
    },
    audio: new Audio('https://dev.yunnet.ru/sounds/message.wav'),
    playCurrentSound: function () {
        this.audio.currentTime = 0;
        this.audio.play();
    },

    getCounters: function () {
        let counters = settings.getCounters.current;

        return new Promise(function (resolve, reject) {
            if (!settings.users.current) return resolve(new TypeError('Unauthorized!'));
            if (typeof counters === "object") return resolve(counters);

            let data = new FormData();

            data.append('action', 'get_counters');
            return ui.Request({
                url: '/flex',
                data: data,
                method: 'POST',
                success: function (response) {
                    response = JSON.parse(response);

                    if (response.unauth) return settings.reLogin();
                    if (response.error) return reject(new TypeError('Unable to fetch counters'));

                    return resolve(settings.getCounters.current = response);
                }
            });
        });
    },
    accounts: {
        unbound: function (accountType = 1) {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'unbound_account');
                data.append('type', Number(accountType));
                return ui.Request({
                    url: '/audios',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);

                        if (response.error) return reject(new TypeError('Accounts fetching failed'));
                        return resolve(true);
                    }
                });
            });
        },
        get: function () {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'get_accounts');
                return ui.Request({
                    url: '/settings',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);

                        if (response.error) return reject(new TypeError('Accounts fetching failed'));
                        return resolve(response.response);
                    }
                });
            });
        },
        auth: function (login, password, accountType, authCode = null) {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'bound_account');
                data.append('type', accountType);
                data.append('login', login);
                data.append('password', password);
                if (authCode)
                    data.append('auth_code', Number(authCode));

                return ui.Request({
                    url: '/audios',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);

                        return resolve(response);
                    }
                });
            });
        },
    },
    reLogin: function () {
        this.isLogged = false;

        return settings.Logout();
    },
    Logout: function (element) {
        if (element) {
            return pages.elements.confirm('', settings.lang.getValue('logout_qq'), function (response) {
                if (response)
                    return settings.Logout();
            });
        }

        if (document.getElementById('logout')) return null;
        if (!settings.users.current) return null;

        let loader = pages.elements.getLoader();

        let windowElement = document.createElement('div');
        windowElement.id = 'logout';

        windowElement.classList.add('modal');
        if (ui.isMobile())
            windowElement.classList.add('bottom-sheet');

        document.body.appendChild(windowElement);
        let instance = unt.Modal.init(windowElement, {
            endingTop: '40%',
            dismissible: false,
            onCloseEnd: function () {
                windowElement.remove();
            }
        });

        let content = document.createElement('div');
        content.classList.add('modal-content');
        windowElement.appendChild(content);

        let innerDiv = document.createElement('div');
        innerDiv.classList.add('valign-wrapper');
        content.appendChild(innerDiv);

        innerDiv.appendChild(loader);
        loader.classList.remove('center');

        let textDiv = document.createElement('div');
        innerDiv.appendChild(textDiv);
        textDiv.style.marginLeft = '15px';
        textDiv.innerText = 'Logging out...';
        if (instance)
            instance.open();

        return setTimeout(function () {
            document.cookie = "";

            let data = new FormData();

            data.append('action', 'logout');

            let x = _xmlHttpGet();
            x.open('POST', '/settings');

            x.withCredentials = true;
            x.onreadystatechange = function () {
                if (x.readyState !== 4) return;

                return window.location.reload();
            }

            return x.send(data);
        }, 1000);
    },
    lang: {
        values: {},
        change: function (lang) {
            let data = new FormData();

            data.append('action', 'change_language');
            data.append('lang', String(lang).toLowerCase());

            return new Promise(function (resolve, reject) {
                return ui.Request({
                    url: '/settings',
                    method: 'POST',
                    data: data,
                    success: function () {
                        settings.lang.values = {};

                        return resolve(window.location.reload());
                    }
                });
            });
        },
        getValue: function (value) {
            return settings.lang.values[value] || '';
        }
    },
    users: {
        profiles: {},
        setGender: function (newGender) {
            return new Promise(function (resolve, reject) {
                if (newGender !== 1 && newGender !== 2)
                    return reject(new TypeError('Gender can be only male or female'));

                let data = new FormData();
                
                data.append('action', 'set_gender');
                data.append('gender', Number(newGender) || 0);

                return ui.Request({
                    data: data,
                    url: '/settings',
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);

                        if (response.unauth) return settings.reLogin();
                        if (response.error) return reject(new TypeError('Unable to change gender'));

                        return resolve(response.success);
                    }
                })
            });
        },
        openWindow: function (screen_name) {
            return new Promise(function (resolve) {
                return settings.users.resolveScreenName(screen_name).then(function (user) {
                    let windowEl = pages.elements.createWindow();

                    let content = windowEl.getContent();

                    let footer = windowEl.getFooter();

                    let goButton = document.createElement('a');
                    goButton.style.cursor = 'pointer';
                    goButton.classList = ['btn btn-flat waves-effect'];
                    goButton.innerText = settings.lang.getValue('go_to_the_profile');

                    goButton.setAttribute('target', '_blank');
                    goButton.setAttribute('href', '/' + (user.screen_name ? user.screen_name : (user.user_id ? ("id" + user.user_id) : ("bot" + user.bot_id))));

                    footer.appendChild(goButton);

                    let userInfoDiv = document.createElement('div');
                    userInfoDiv.classList.add('valign-wrapper');

                    content.appendChild(userInfoDiv);

                    let img = document.createElement('img');
                    img.style.marginRight = '15px';
                    img.classList.add('circle');

                    img.width = img.height = 64;
                    img.src = user.photo_url;
                    userInfoDiv.appendChild(img);

                    let infoDiv = document.createElement('div');
                    userInfoDiv.appendChild(infoDiv);

                    let credentialsDiv = document.createElement('b');
                    infoDiv.appendChild(credentialsDiv);
                    credentialsDiv.innerText = user.name || (user.first_name + ' ' + user.last_name);

                    infoDiv.appendChild(document.createElement('br'));

                    let onlineDiv = document.createElement('small');
                    onlineDiv.style.color = 'gray';
                    infoDiv.appendChild(onlineDiv);
                    onlineDiv.innerText = pages.parsers.getOnlineState(user);

                    let attributesDiv = document.createElement('div');
                    content.appendChild(attributesDiv);

                    if (user.friend_state === 2) {
                        attributesDiv.appendChild(document.createElement('br'));

                        let inFriendsDiv = document.createElement('div');
                        inFriendsDiv.classList.add('valign-wrapper');

                        attributesDiv.appendChild(inFriendsDiv);

                        let iconItem = document.createElement('div');
                        iconItem.innerHTML = unt.Icon.SAVE;
                        inFriendsDiv.appendChild(iconItem);

                        iconItem.style.marginTop = '5px';
                        iconItem.style.marginRight = '5px';

                        let text = document.createElement('div');
                        text.innerText = settings.lang.getValue('in_friends_now');
                        inFriendsDiv.appendChild(text);
                    }

                    return resolve(windowEl);
                }).catch(function (err) {
                    return resolve(null);
                })
            });
        },
        setStatus: function (status = '') {
            return new Promise(function (resolve, reject) {
                let data = new FormData();
                
                data.append('action', 'set_status');
                data.append('new_status', String(status));

                return ui.Request({
                    data: data,
                    url: '/flex',
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);

                        if (response.unauth) return settings.reLogin();
                        if (response.error) return reject(new TypeError('Unable to change status'));

                        return resolve(response.status);
                    }
                })
            });
        },
        search: function (query = '', params = {}, offset = 0, count = 30) {
            return new Promise(function (resolve, reject) {
                if (typeof query !== "string" || query.isEmpty()) return reject(new TypeError('Query is invalid'));

                let data = new FormData();

                data.append('action', 'search');
                data.append('query', query.toString());
                data.append('offset', Number(offset) || 0);
                data.append('count', Number(count) || 30);

                if (params.onlineOnly)
                    data.append('online_only', '1');
                if (params.searchBots)
                    data.append('search_bots', '1');
                if (params.searchBotsOnly)
                    data.append('only_bots', '1');

                return ui.Request({
                    data: data,
                    url: '/friends',
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.error) return rejct(new TypeError('Unable to search'));

                        return resolve(response);
                    }
                });
            });
        },
        changePassword: function (oldPassword, newPassword) {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'change_password');
                data.append('old_password', oldPassword);
                data.append('new_password', newPassword);
                return ui.Request({
                    data: data,
                    url: '/settings',
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.error) return reject(new TypeError('Password check failed'));

                        return resolve(Boolean(response.state));
                    }
                });
            });
        },
        verifyPassword: function (password) {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'verify_password');
                data.append('password', password);
                return ui.Request({
                    url: '/settings',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.error) return reject(new TypeError('Password check failed'));

                        return resolve(Boolean(response.state));
                    }
                });
            });
        },
        getBlacklisted: function (page = 1) {
            return new Promise(function (resolve, reject) {
                let currentOffset = (page - 1) * 30;

                let data = new FormData();
                data.append('action', 'get_blacklisted');
                data.append('offset', currentOffset);
                data.append('count', 30);

                return ui.Request({
                    url: '/settings',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.error) return reject(new TypeError('Blacklist fetch error'));

                        return resolve(response);
                    }
                });
            });
        },
        get: function (entity_id, fields = "name_cases,bot_can_write_messages,can_write_messages,main_photo_as_object,can_access_closed,can_write_on_wall,friend_state,is_me_blacklisted,is_blacklisted,can_invite_to_chat") {
            return new Promise (function (resolve, reject) {
                if (settings.users.profiles[entity_id]) return resolve(settings.users.profiles[entity_id]);

                let data = new FormData();

                data.append('action', 'get_user_data');
                data.append('fields', String(fields));
                data.append('id', String(entity_id));

                return ui.Request({
                    method: 'POST',
                    url: '/' + 'fl' + 'ex',
                    data: data,
                    success: function (response) {
                        response = JSON.parse(response);

                        if (response.error) return reject(new TypeError("User not found"));
                        if (response.response) return resolve(settings.users.profiles[Number(response.response.user_id || (response.response.bot_id*-1))] = response.response);
                    }
                });
            });
        },
        resolveScreenName: function (screen_name) {
            return new Promise (function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'get_user_data_by_link');
                data.append('screen_name', String(screen_name));

                return ui.Request({
                    method: 'POST',
                    url: '/' + 'fl' + 'ex',
                    data: data,
                    success: function (response) {
                        response = JSON.parse(response);

                        if (response.error) return reject(new TypeError("User not found"));
                        if (response.id) {
                            delete settings.users.profiles[response.id]

                            return settings.users.get(response.id).then(resolve).catch(reject)
                        }
                    }
                });
            });
        },
        edit: function (dataObject) {
            return new Promise(function (resolve, reject) {
                let allowedKeys = ['first_name', 'last_name', 'screen_name', 'photo'];

                let data = new FormData();

                data.append('action', 'save');
                for (let key in dataObject) {
                    if (allowedKeys.indexOf(key) !== -1) data.append(key, dataObject[key] || '');
                }

                return ui.Request({
                    url: '/edit',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        try {
                            response = JSON.parse(response);
                            if (response.error) {
                                let error = new Error('Edit failed');
                                if (response.message)
                                    error.errorMessage = response.message;

                                return reject(error);
                            }

                            return resolve(response);
                        } catch (e) {
                            return reject(new Error('Edit failed'));
                        }
                    }
                });
            });
        },
        friends: {
            acceptRequest: function (user_id) {
                return new Promise(function (resolve, reject) {
                    let data = new FormData();
                    
                    data.append('action', 'add');
                    return ui.Request({
                        data: data,
                        method: 'POST',
                        url: '/id' + (Number(user_id) || 1),
                        success: function (response) {
                            return resolve(true);
                        }
                    });
                });
            },
            hideRequest: function (user_id) {
                return new Promise(function (resolve, reject) {
                    let data = new FormData();

                    data.append('action', 'hide_request');
                    data.append('user_id', Number(user_id) || 0);
                    return ui.Request({
                        url: '/friends',
                        data: data,
                        method: 'POST',
                        success: function (response) {
                            response = JSON.parse(response);
                            if (response.error) return reject(new TypeError('Unable to hide request'));

                            return resolve(response.success);
                        },
                    });
                });
            },
            cache: {},
            get: function (user_id, section = 'friends', page = 1) {
                return new Promise(function (resolve, reject) {
                    if (settings.users.current.user_id === user_id && settings.users.friends.cache[section] && page === 1) return resolve(settings.users.friends.cache[section]);

                    let data = new FormData();

                    data.append('action', 'get_friends');
                    data.append('user_id', user_id);
                    data.append('section', section);
                    data.append('offset', (Number(page)-1)*30);

                    return ui.Request({
                        data: data,
                        url: '/flex',
                        method: 'POST',
                        success: function (response) {
                            response = JSON.parse(response);
                            if (response.unauth) return settings.reLogin();

                            if (response.error) return reject(new Error('Friends fetch error'));
                            if (settings.users.current.user_id === user_id && page === 1) settings.users.friends.cache[section] = response;

                            return resolve(response);
                        }
                    });
                });
            }
        }
    },
    get: function (update = false) {
        if (!update) return settings.currentSettings;

        return new Promise(function (resolve, reject) {
            if (settings.currentSettings) return resolve(settings.currentSettings);

            let data = new FormData();
            data.append('action', 'get_settings');

            return ui.Request({
                url: '/flex',
                data: data,
                method: 'POST',
                success: function (response) {
                    response = JSON.parse(response);

                    if (response.unauth) return settings.reLogin();
                    if (response.error) return reject(new TypeError("Settings resolving failed"));

                    return resolve(settings.currentSettings = response);
                }
            });
        });
    },
    data: {
        endSession: function (sessionId) {
            return new Promise(function (resolve, reject) {
                if (!sessionId) return reject(new TypeError('Session id must be provided'));
                if (sessionId.toString().isEmpty()) return reject(new TypeError('Session id must be not empty'));

                let data = new FormData();

                data.append('action', 'end_session');
                data.append('session_id', String(sessionId));
                return ui.Request({
                    data: data,
                    method: 'POST',
                    url: '/settings',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.error) return reject(new TypeError('Unable to fetch the sessions list'));
                        if (response.unauth) return settings.reLogin();

                        return resolve(response.success);
                    }
                });
            });
        },
        getSessions: function () {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'get_sessions_list');
                return ui.Request({
                    data: data,
                    method: 'POST',
                    url: '/settings',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.error) return reject(new TypeError('Unable to fetch the sessions list'));
                        if (response.unauth) return settings.reLogin();

                        return resolve(response);
                    }
                });
            });
        },
        toggleSoundSettings: function (item) {
            if (item < 1 || item > 2) return false;

            let groups = ['notifications', 'sound'];
            let currentGroup = groups[item - 1];

            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'toggle_push_settings');
                data.append('settings_group', currentGroup);
                data.append('new_value', Number(!settings.get().push[currentGroup]));
                return ui.Request({
                    data: data,
                    url: '/settings',
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);

                        if (response.error) return reject(new TypeError('Settings changing error'));
                        settings.get().push[currentGroup] = response.response;

                        return resolve(response.response);
                    }
                });
            });
        },
        toggleNotifications: function () {
            return this.toggleSoundSettings(1);
        },
        toggleSound: function () {
            return this.toggleSoundSettings(2);
        },
        toggleProfileState: function () {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'toggle_profile_state');
                return ui.Request({
                    url: '/settings',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.unauth) return settings.reLogin();
                        if (response.error) return reject(new TypeError('Settings changing failed'));

                        settings.get().account.is_closed = !settings.get().account.is_closed;
                        return resolve(settings.get().account.is_closed);
                    }
                });
            });
        },
        setPrivacy: function (groupId, newValue) {
            let groups = [
                'can_write_messages',
                'can_write_on_wall',
                'can_invite_to_chats',
                'can_comment_posts'
            ];

            let currentGroup = groups[Number(groupId)-1];
            if (!currentGroup) return false;
            if (isNaN(Number(newValue))) return false;

            if (Number(newValue) < 0 || Number(newValue) > 2) return false;

            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'set_privacy_settings');
                data.append('group', Number(groupId));
                data.append('value', Number(newValue));
                return ui.Request({
                    url: '/settings',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.unauth) return settings.reLogin();

                        if (response.error) return reject(new TypeError('Settings changing failed'));
                        if (settings.get().privacy[currentGroup] !== undefined) {
                            settings.get().privacy[currentGroup] = Number(newValue);
                        }

                        return resolve(true);
                    }
                });
            });
        },
        test: function () {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'test');
                return ui.Request({
                    data: data,
                    method: 'POST',
                    url: '/flex',
                    success: function (response) {
                        try {
                            response = JSON.parse(response);

                            if (response.unauth) return settings.reLogin();
                            if (response.blocked) return resolve(settings.data.test.isBlocked = true);

                            return resolve(false);
                        } catch (e) {
                            return resolve(null);
                        }
                    }
                });
            });
        }
    },
    currentSettings: null,
    getStats: function () {
        return new Promise(function (resolve, reject) {
            let data = new FormData();

            data.append('action', 'get_stats');
            return ui.Request({
                url: '/about',
                method: 'POST',
                data: data,
                success: function (response) {
                    response = JSON.parse(response);

                    if (response.error) return reject(new TypeError('Unable to get yunNet. stats'));
                    return resolve(response);
                }
            });
        });
    }
};

const account = {
    user: {},
    restore: {
        closeSession: function () {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'close_session');
                return ui.Request({
                    url: '/restore',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        return resolve();
                    }
                });
            });
        },
        validateQuery: function (query) {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'check_query');
                data.append('query', String(query));

                return ui.Request({
                    url: '/restore',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        response = JSON.parse(response);

                        if (!response.stage) return resolve(null);
                        return resolve(response.stage);
                    }
                });
            });
        },
        getCurrentStage: function () {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'get_state');
                return ui.Request({
                    url: '/restore',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        try {
                            response = JSON.parse(response);
                            if (!response.state) return resolve(0);

                            return resolve(response.state);
                        } catch (e) {
                            return resolve(1);
                        }
                    }
                });
            });
        },
        sendSessionData: function (formData) {
            return new Promise(function (resolve, reject) {
                 formData.append('action', 'continue');

                return ui.Request({
                    url: '/restore',
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        try {
                            response = JSON.parse(response);
                            if (!response.stage) {
                                let newError = new Error('Data send error');

                                newError.errorCode = response.error_code;
                                if (response.error_message) 
                                    newError.errorText = response.error_message;

                                if (response.new_stage)
                                    newError.requestedStage = response.new_stage;

                                return reject(newError);
                            }

                            return resolve(response.stage);
                        } catch (e) {
                            return reject(e);
                        }
                    }
                });
            });
        }
    },
    register: {
        restoreData: function () {
            return new Promise(function (resolve, reject) {
                let data = new FormData();
                data.append('action', 'get_data');

                return ui.Request({
                    url: '/register',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        return resolve(JSON.parse(response));
                    }
                });
            });
        },
        closeSession: function () {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'close_session');
                return ui.Request({
                    url: '/register',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        return resolve();
                    }
                });
            });
        },
        getCurrentStage: function () {
            return new Promise(function (resolve, reject) {
                let data = new FormData();

                data.append('action', 'get_state');
                return ui.Request({
                    url: '/register',
                    data: data,
                    method: 'POST',
                    success: function (response) {
                        try {
                            response = JSON.parse(response);

                            if (response.closed) return resolve(-1);
                            if (!response.state) return resolve(0);

                            return resolve(response.state);
                        } catch (e) {
                            return resolve(1);
                        }
                    }
                });
            });
        },
        sendSessionData: function (formData) {
            return new Promise(function (resolve, reject) {
                 formData.append('action', 'continue');

                return ui.Request({
                    url: '/register',
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        try {
                            response = JSON.parse(response);
                            if (!response.stage) {
                                let newError = new Error('Data send error');

                                newError.errorCode = response.error_code;
                                if (response.error_message) 
                                    newError.errorText = response.error_message;

                                if (response.new_stage)
                                    newError.requestedStage = response.new_stage;

                                return reject(newError);
                            }

                            return resolve(response.stage);
                        } catch (e) {
                            return reject(e);
                        }
                    }
                });
            });
        }
    },
    auth: function (button, event, loginField, passwordField) {
        event.preventDefault();
        document.getElementsByClassName("incorrect_message")[0].style.display = "none";

        let login_field = document.getElementById('login_field');
        if (!login_field)
            login_field = loginField;

        let password_field = document.getElementById('password_field');
        if (!password_field)
            password_field = passwordField;

        if (login_field.value.isEmpty()) return login_field.classList.add("invalid");
        if (password_field.value.isEmpty()) return  password_field.classList.add("invalid");

        auth_text.style.display = "none";
        auth_loader.style.display = "";

        settings.users.get().then(function (user) {
            return window.location.reload();
        }).catch(function (err) {
            let data = new FormData();

            data.append('action', 'login');
            data.append('email', String(login_field.value));
            data.append('password', String(password_field.value));

            let x = _xmlHttpGet();
            x.withCredentials = true;
            x.onreadystatechange = function () {
                if (x.readyState !== 4) return;

                let response = x.responseText;
                response = JSON.parse(response);

                if (response.error) {
                    auth_text.style.display = "";
                    auth_loader.style.display = "none";

                    return document.getElementsByClassName("incorrect_message")[0].style.display = "";
                }

                if (response.success) {
                    return window.location.reload();
                }
            }

            x.open('POST', '/login');
            x.send(data);
        });
    }
};

const uploads = {
    type: {
        IMAGE: 'image',
        THEME: 'theme'
    },
    getURL: function (type = this.type.IMAGE) {
        return new Promise(function (resolve, reject) {
            let data = new FormData();

            data.append('action', 'get');
            data.append('type', type);

            return ui.Request({
                url: '/upload',
                method: 'POST',
                data: data,
                success: function (response) {
                    response = JSON.parse(response);
                    if (response.error) return reject(new TypeError("URL resolving error"));

                    return resolve(response.url);
                },
                error: function (err) {
                    return reject(err);
                }
            });
        });
    },
    upload: function (url, file, uploadCallback = null) {
        return new Promise(function (resolve, reject) {
            let data = new FormData();

            data.append('attachment', file);
            let x = _xmlHttpGet();
            x.onreadystatechange = function () {
                if (x.readyState !== 4) return;

                if (x.status !== 200 && x.status !== 301 && x.status !== 302 && x.status !== 300)
                    return reject(new TypeError('Upload failed'));

                let response = x.responseText;

                response = JSON.parse(response);
                if (response.error) return reject(new TypeError("Upload failed"));

                return resolve(response);
            }

            if (uploadCallback) {
                x.upload.onprogress = function (event) {
                    return uploadCallback(event);
                }
            }

            x.onerror = function (err) {
                return reject(err);
            }

            x.open('POST', url);
            
            return x.send(data);
        });
    }
}

const audios = {
    current: null,
    repeatMode: 1,
    shuffle: false,
    audioIndex: [],
    audiosList: {},
    buildPlayerElement: function (minify = false) {
        let element = document.createElement('div');

        element.classList = ['card full_section'];
        if (minify) {
            element.classList.remove('full_section');

            element.style.padding = '10px';
            element.setup = function () {
                if (ui.isMobile()) {
                    element.style.position = 'absolute';
                    element.style.width = '100%';
                    element.style.bottom = '7%';

                    document.getElementsByClassName('sidenav')[0].appendChild(element);
                } else {
                    document.getElementsByClassName('col s3')[0].appendChild(element);
                }
            }

            let containerButton = document.createElement('div');
            element.appendChild(containerButton);
            containerButton.classList.add('valign-wrapper');

            let controlButton = document.createElement('a');
            controlButton.href = '#';

            controlButton.style.marginTop = '5px';
            containerButton.appendChild(controlButton);

            controlButton.setIcon = function (icon) {
                controlButton.innerHTML = icon;

                return controlButton;
            }

            element.getControlButton = function () {
                return controlButton;
            }

            controlButton.setIcon(unt.Icon.PLAY);
            controlButton.onclick = function (event) {
                event.preventDefault();

                 return audios.audiosList[audios.current].paused ? audios.audiosList[audios.current].play() : audios.audiosList[audios.current].pause();
            }

            let credentialsDiv = document.createElement('div');
            containerButton.appendChild(credentialsDiv);
            credentialsDiv.classList.add('hidet');

            element.setData = function (data) {
                credentialsDiv.innerText = data;

                return element;
            }

            audios.miniPlayerCreated = true;
        } else {
            let musicData = document.createElement('div');
            musicData.classList.add('valign-wrapper');
            element.appendChild(musicData);

            let controlButton = pages.elements.createFAB(unt.Icon.PLAY, function (event) {
                return audios.audiosList[audios.current].paused ? audios.audiosList[audios.current].play() : audios.audiosList[audios.current].pause();
            });

            controlButton.classList.remove('fixed-action-btn');
            musicData.appendChild(controlButton);

            element.getControlButton = function () {
                return controlButton;
            }

            let songDataDiv = document.createElement('div');
            songDataDiv.style.marginLeft = '15px';
            musicData.appendChild(songDataDiv);

            let innerB = document.createElement('b');
            songDataDiv.appendChild(innerB);

            let artistInfo = document.createElement('div');
            artistInfo.classList.add('hidet');
            songDataDiv.appendChild(artistInfo);

            element.setTitle = function (newTitle) {
                innerB.innerText = newTitle;
            }
            element.setArtist = function (newArtist) {
                artistInfo.innerText = newArtist;
            }

            let valueForm = document.createElement('form');
            element.appendChild(valueForm);

            let p = document.createElement('p');
            p.classList.add('range-field');
            valueForm.appendChild(p);

            let input = document.createElement('input');
            p.appendChild(input);
            input.type = 'range';

            p.style.marginBottom = '0px';

            input.setAttribute('min', '0');
            input.setAttribute('max', '0');

            element.getRange = function () {
                return p;
            }

            p.getInput = function () {
                return input;
            }

            let timesDiv = document.createElement('div');
            timesDiv.classList.add('valign-wrapper');
            element.appendChild(timesDiv);

            let minTime = document.createElement('small');
            minTime.style.width = '100%';

            let maxTime = document.createElement('small');

            timesDiv.appendChild(minTime);
            timesDiv.appendChild(maxTime);

            minTime.innerText = '00:00';

            element.getMinTime = function () {
                return minTime;
            }
            element.getMaxTime = function () {
                return maxTime;
            }

            let manageTrack = document.createElement('div');
            element.appendChild(manageTrack);
            manageTrack.classList.add('center');

            let repeatMode = document.createElement('a');
            let skipPrev = document.createElement('a');
            let skipNext = document.createElement('a');
            let shuffleMode = document.createElement('a');

            repeatMode.href = '#';
            skipPrev.href = '#';
            skipNext.href = '#';
            shuffleMode.href = '#';

            manageTrack.appendChild(repeatMode);
            manageTrack.appendChild(skipPrev);
            manageTrack.appendChild(skipNext);
            manageTrack.appendChild(shuffleMode);

            repeatMode.innerHTML = unt.Icon.REPEAT;
            skipPrev.innerHTML = unt.Icon.SKIP_PREV;
            skipNext.innerHTML = unt.Icon.SKIP_NEXT;
            shuffleMode.innerHTML = unt.Icon.SHUFFLE;

            if (audios.repeatMode === 2)
                repeatMode.innerHTML = unt.Icon.REPEAT_ONE;
            if (audios.shuffle)
                shuffleMode.getElementsByTagName('svg')[0].style.fill = 'lightgray';

            repeatMode.onclick = function (event) {
                event.preventDefault();

                if (audios.repeatMode === 2) {
                    audios.repeatMode = 1;
                    repeatMode.innerHTML = unt.Icon.REPEAT;
                } else {
                    audios.repeatMode = 2;
                    repeatMode.innerHTML = unt.Icon.REPEAT_ONE;
                }
            }

            shuffleMode.onclick = function (event) {
                event.preventDefault();

                if (audios.shuffle) {
                    audios.shuffle = false;
                    shuffleMode.getElementsByTagName('svg')[0].style.fill = '';
                } else {
                    audios.shuffle = true;
                    shuffleMode.getElementsByTagName('svg')[0].style.fill = 'lightgray';
                }
            }

            skipPrev.onclick = function (event) {
                event.preventDefault();

                return audios.prev();
            };

            skipNext.onclick = function (event) {
                event.preventDefault();

                return audios.next();
            }
        }

        return element;
    },
    next: function () {
        if (!this.current) return false;

        let currentIndex = this.audioIndex.indexOf(this.current);
        if (currentIndex === -1) return false;

        let nextCredentials = this.audioIndex[currentIndex + 1];
        if (!nextCredentials)
            nextCredentials = this.audioIndex[0];

        if (!nextCredentials) return false;
        return this.audiosList[nextCredentials].play();
    },
    prev: function () {
        if (!this.current) return false;

        let currentIndex = this.audioIndex.indexOf(this.current);
        if (currentIndex === -1) return false;

        let nextCredentials = this.audioIndex[currentIndex - 1];
        if (!nextCredentials)
            nextCredentials = this.audioIndex[this.audioIndex.length - 1];

        if (!nextCredentials) return false;
        return this.audiosList[nextCredentials].play();
    },
    playNext: function (currentAudio) {
        let currentIndex = this.audioIndex.indexOf(currentAudio);

        if (currentIndex === -1) return false;

        let nextCredentials = this.audioIndex[currentIndex + 1];
        if (audios.shuffle) {
            let randomIndex = getRandomInt(0, this.audioIndex.length - 1);

            nextCredentials = this.audioIndex[randomIndex];
        } else {
            if (!nextCredentials && this.repeatMode === 1)
                nextCredentials = this.audioIndex[0];
            if (this.repeatMode === 2)
                nextCredentials = currentAudio;
        }

        if (!nextCredentials) return false;

        return this.audiosList[nextCredentials].play();
    },
    pauseAll: function (intCredentials) {
        for (let key in this.audiosList) {
            if (key === intCredentials) continue;

            if (!this.audiosList[key].paused) {
                this.audiosList[key].currentTime = 0;

                this.audiosList[key].pause();
            }
        }
    },
    get: function (accountType, currentPage = 1, count = 30) {
        let currentOffset = (currentPage - 1) * count;

        return new Promise(function (resolve, reject) {
            let data = new FormData();

            data.append('action', 'get_audio');
            data.append('type', accountType);
            data.append('offset', currentOffset);
            data.append('count', Number(count));
            return ui.Request({
                url: '/audios',
                data: data,
                method: 'POST',
                success: function (response) {
                    response = JSON.parse(response);

                    if (response.error) return reject(new TypeError('Audios fetch failed'));
                    return resolve(response.response);
                }
            });
        });
    }
}

const notifications = {
    cache: [],
    get: function (offset, count) {
        return new Promise(function (resolve, reject) {
            if (offset < count && notifications.cache.length >= count) return resolve(notifications.cache);

            if (offset < 0) offset = 0;
            if (count > 100) count = 100;

            let data = new FormData();

            data.append('action', 'get_notifications');
            data.append('offset', Number(offset));
            data.append('count', Number(count));

            return ui.Request({
                url: '/notifications',
                data: data,
                method: 'POST',
                success: function (response) {
                    response = JSON.parse(response);
                    if (response.error) return reject(new TypeError('Unable to notifications fetch'));

                    return resolve(notifications.cache = response);
                }
            });
        });
    },
    getById: function (notificationId) {},
    read: function (notificationId) {
        return new Promise(function (resolve, reject) {
            let data = new FormData();

            data.append('action', 'notification_read');
            data.append('notification_id', Number(notificationId));
            return ui.Request({
                url: '/notifications',
                data: data,
                method: 'POST',
                success: function (response) {
                    response = JSON.parse(response);
                    if (response.error) return reject(new TypeError('Unable to read notification'));

                    return resolve(true);
                }
            });
        });
    },
    hide: function (notificationId) {
        return new Promise(function (resolve, reject) {
            let data = new FormData();

            data.append('action', 'notification_hide');
            data.append('notification_id', Number(notificationId));
            return ui.Request({
                url: '/notifications',
                data: data,
                method: 'POST',
                success: function (response) {
                    response = JSON.parse(response);
                    if (response.error) return reject(new TypeError('Unable to hide notification'));

                    return resolve(true);
                }
            });
        });
    },
};