class POSTData {
	#resultData = null;

	constructor () {
		(this.#resultData = new FormData());
	}

	append (key, value) {
		this.#resultData.append(key, value);

		return this;
	}

	delete (key) {
		this.#resultData.delete(key);

		return this;
	}

	entries () {
		return this.#resultData.entries();
	}

	forEach (callback) {
		this.#resultData.forEach(callback);

		return this;
	}

	get (value) {
		return this.#resultData.get(value);
	}

	getAll (value) {
		return this.#resultData.getAll(value);
	}

	has (value) {
		return this.#resultData.has(value);
	}

	keys () {
		return this.#resultData.keys();
	}

	set (key, value) {
		this.#resultData.set(key, value);

		return this;
	}

	values () {
		return this.#resultData.values();
	}

	build () {
		return this.#resultData;
	}
}

class URLParser {
	currentDomain = null;
	currentPage = null;
	queryStringParams = null;

	constructor (url = window.location.href) {
		let splittedUrl = (typeof url === 'string' ? url : String(url)).split('/');
		
		let domainUrl = splittedUrl.length == 2 ? splittedUrl[0] : (splittedUrl.length >= 4 ? splittedUrl[2] : splittedUrl[splittedUrl.length - 3]);
		let pageAndParams = splittedUrl.length == 2 ? splittedUrl[1] : (splittedUrl.length >= 4 ? splittedUrl[3] : splittedUrl[splittedUrl.length - 1]);

		this.currentDomain = domainUrl;
		this.currentPage = pageAndParams.split('?')[0];

		let params = String(pageAndParams.split('?')[1]).split('&');
		let resultedObject = {};

		params.forEach(function (keyPair) {
			let data = keyPair.split('=');

			let key = data[0];
			let value = data[1];

			if (!value || value.isEmpty())
				return;
			if (!key)
				return;

			resultedObject[key] = value;
		});

		this.queryStringParams = resultedObject;
	}

	getDomain () {
		return this.currentDomain;
	}

	getPage () {
		return this.currentPage;
	}

	getQueryValue (value) {
		return this.queryStringParams[value] || '';
	}
}

unt.actions = new Object({
	authForm: function () {
		let authForm = document.createElement('form');
		authForm.action = '/login';

		let loginField = unt.components.textField(unt.settings.lang.getValue('email'));
		loginField.getInput().type = 'email';
		loginField.getInput().name = 'email';
		let passField = unt.components.textField(unt.settings.lang.getValue('password'));
		passField.getInput().type = 'password';
		passField.getInput().name = 'password';

		authForm.appendChild(loginField);
		authForm.appendChild(passField);

		let authActionsDiv = document.createElement('div');
		authForm.appendChild(authActionsDiv);
		authActionsDiv.style.display = 'table-caption';

		let registerLink = document.createElement('a');
		registerLink.innerText = unt.settings.lang.getValue('regstart');
		authActionsDiv.appendChild(registerLink);
		registerLink.href = '/register';

		let delimDiv = document.createElement('br');
		authActionsDiv.appendChild(delimDiv);

		let restoreLink = document.createElement('a');
		restoreLink.innerText = unt.settings.lang.getValue('forgot_password');
		authActionsDiv.appendChild(restoreLink);
		restoreLink.href = '/restore';

		authActionsDiv.appendChild(document.createElement('br'));
		authActionsDiv.appendChild(document.createElement('br'));

		let authButton = document.createElement('button');
		authButton.type = 'submit';
		authButton.classList.add('btn');
		authButton.classList.add('btn-large');
		authActionsDiv.appendChild(authButton);

		let loginText = document.createElement('div');
		authButton.appendChild(loginText);
		loginText.innerText = unt.settings.lang.getValue('logstart');

		let loaderElement = unt.components.loaderElement().setColor('white');
		loaderElement.getElementsByTagName('svg')[0].style.marginTop = '18%';
		authButton.appendChild(loaderElement);
		loaderElement.style.display = 'none';

		let isPending = false;

		authForm.addEventListener('submit', function (event) {
			event.preventDefault();
			if (isPending) return;

			if (loginField.getText().isEmpty())
				return loginField.getInput().classList.add('wrong');
			else
				loginField.getInput().classList.remove('wrong');

			if (passField.getText().isEmpty())
				return passField.getInput().classList.add('wrong');
			else
				passField.getInput().classList.remove('wrong');

			isPending = true;
			loginText.style.display = 'none';
			loaderElement.style.display = '';
			loginField.disable();
			passField.disable();

			return unt.tools.Request({
				url: '/login',
				method: 'POST',
				data: (new POSTData()).append('action', 'login').append('email', loginField.getInput().value).append('password', passField.getInput().value).build(),
				withCredentials: true,
				success: function (response) {
					isPending = false;
					loginText.style.display = '';
					loaderElement.style.display = 'none';
					loginField.enable();
					passField.enable();

					try {
						response = JSON.parse(response);
						if (response.error)
							if (authForm.onauthresult)
								return authForm.onauthresult(-1);
							else
								return unt.toast({html: unt.settings.lang.getValue('auth_failed')});

						if (authForm.onauthresult)
							return authForm.onauthresult(1);
						else
							return unt.toast({html: 'OK'}), window.location.reload();
					} catch (e) {
						if (authForm.onauthresult)
							return authForm.onauthresult(0);

						return unt.toast({html: unt.settings.lang.getValue('upload_error')});
					}
				},
				error: function () {
					isPending = false;
					loginText.style.display = '';
					loaderElement.style.display = 'none';
					loginField.enable();
					passField.enable();

					if (authForm.onauthresult)
						return authForm.onauthresult(0);

					return unt.toast({html: unt.settings.lang.getValue('upload_error')});
				}
			});
		});

		return authForm;
	},
	dialog: function (header, title, fullScreen = false, asImportantWindow = false) {
		return new Promise(function (resolve) {
			let contentWindow = document.createElement('div');
			let textDivElement = document.createElement('div');

			if (!asImportantWindow)
				contentWindow.appendChild(document.createElement('br'));

			textDivElement.innerText = title;
			contentWindow.appendChild(textDivElement);

			let disagree = document.createElement('a');
			disagree.classList = ['btn btn-flat waves-effect'];
			disagree.innerText = unt.settings.lang.getValue('no');

			let agree = document.createElement('a');
			agree.classList = ['btn btn-flat waves-effect'];
			agree.innerText = unt.settings.lang.getValue('yes');

			function closeWin (win, response) {
				inOutClosed = true;
				win.close();

				return resolve(response);
			}

			let inOutClosed = false;

			if (asImportantWindow) {
				contentWindow.style.padding = '15px';

				contentWindow.appendChild(document.createElement('br'));

				let footerDiv = document.createElement('div');
				contentWindow.appendChild(footerDiv);
				footerDiv.style.width = '100%';
				footerDiv.style.textAlign = 'end';

				let buttonsDiv = document.createElement('div');
				footerDiv.appendChild(buttonsDiv);

				buttonsDiv.appendChild(disagree);
				buttonsDiv.appendChild(agree);

				let win = unt.components.windows.createImportantWindow({
					onClose: function () {
						if (!inOutClosed)
							return resolve(false);
					}
				}).setTitle(header).show();

				win.getMenu().appendChild(contentWindow);

				agree.addEventListener('click', function () {
					closeWin(win, true);
				});
				disagree.addEventListener('click', function () {
					closeWin(win, false);
				});
			} else {
				let win = unt.components.windows.createWindow({
					onClose: function () {
						if (!inOutClosed)
							return resolve(false);
					},
					fullScreen: fullScreen
				}).show().setTitle(header);

				win.getMenu().appendChild(contentWindow);
				win.getFooter().appendChild(disagree);
				win.getFooter().appendChild(agree);

				agree.addEventListener('click', function () {
					closeWin(win, true);
				});
				disagree.addEventListener('click', function () {
					closeWin(win, false);
				});
			}
		});
	},
	linkWorker: new Object({
		history: [],
		currentPage: null,
		returnable: function () {
			return !(this.currentPage.id === 0);
		},
		clearHistory: function () {
			this.currentPage.id = 0;
			this.history = [];

			history.replaceState(this.currentPage, document.title, window.location.href);
		},
		define: function () {
			let currentObject = this;

			let allLinksOnPage = document.querySelectorAll('a');
			for (let i = 0; i < allLinksOnPage.length; i++) {
				let linkElement = allLinksOnPage[i];

				if (linkElement.href !== null && linkElement.href !== "" && !String(linkElement.href).isEmpty() && !linkElement.defined) {
					linkElement.defined = true;
					linkElement.addEventListener('click', function handleElementClick (event) {
						event.stopPropagation();
						event.preventDefault();

						if (unt.settings.users.current && unt.tools.isMobile()) {
							unt.Sidenav.getInstance(document.getElementById('user-navigation')).close();
						}

						if (linkElement.classList.contains('no-continue-browsing')) {
							unt.actions.linkWorker.clearHistory();
						}

						currentObject.go(linkElement.href);
					})
				}
			}

			unt.components.navPanel ? unt.components.navPanel.setTitle(document.title) : '';
			if (unt.settings.users.current) {
				if (unt.actions.linkWorker.returnable()) {
					unt.components.navPanel.getBackButton().style.display = '';

					if (unt.tools.isMobile())
						unt.components.navPanel.getMenuButton().style.display = 'none';
				} else {
					unt.components.navPanel.getBackButton().style.display = 'none';

					if (unt.tools.isMobile())
						unt.components.navPanel.getMenuButton().style.display = '';
				}
			}
		}
	})
});

unt.parsers = new Object({
	online: function (user) {
		if (user.permissions_type > 0) return unt.settings.lang.getValue("work_account");
		if (user.is_banned) return unt.settings.lang.getValue("user_banned");
		if (user.account_type === "bot") return unt.settings.lang.getValue("bot");
		if (user.online.is_online) return unt.settings.lang.getValue("online");
		if (user.online.hidden_online) return unt.settings.lang.getValue("hidden_online");

		let timeString = unt.parsers.time(user.online.last_online_time);
		if (timeString === '') return unt.settings.lang.getValue("offline");
		
		return unt.settings.lang.getValue("was_online").replace("(а)", (user.gender === 2 ? "а" : "")) + " " + timeString;
	},
	form: function (int, form) {
		let number = Math.abs(int) % 100;

		let numberOne = number % 10;

		if (number > 10 && number < 20) return form[2];
		if (numberOne > 1 && numberOne < 5) return form[1];

		if (numberOne === 1) return form[0];

		return form[2];
	},
	chatStateString: function (chatObject) {
		if (chatObject.metadata.permissions) {
			if (chatObject.metadata.permissions.is_leaved) return unt.settings.lang.getValue('you_leaved');
			if (chatObject.metadata.permissions.is_kicked) return unt.settings.lang.getValue('you_kicked');
		}

		if (chatObject.chat_info.data.members_count) {
			if (unt.settings.lang.getValue('id') === 'ru') return chatObject.chat_info.data.members_count + ' ' + this.form(chatObject.chat_info.data.members_count, ['участник', 'участника', 'участников']);
			else 
				return chatObject.chat_info.data.members_count + ' members';
		}

		return unt.settings.lang.getValue('chat');
	},
	attachments: function (attachmentsArray) {
		let resultElement = document.createElement('div');

		if (!Array.isArray(attachmentsArray))
			return resultElement;

		let doneAttachmentsArray = [];
		for (let i = 0; i < attachmentsArray.length; i++) {
			if (attachmentsArray[i].type !== 'photo')
				doneAttachmentsArray.push(attachmentsArray[i]);
		}
		for (let i = 0; i < attachmentsArray.length; i++) {
			if (attachmentsArray[i].type === 'photo')
				doneAttachmentsArray.push(attachmentsArray[i]);
		}

		let arrayWithoutNoPhotoAttachments = doneAttachmentsArray.filter(function (attachment) {
			return attachment.type === 'photo';
		});

		let firstAttachmentsDiv = document.createElement('div');
		firstAttachmentsDiv.style.display = 'flex';

		let secondAttachmentsDiv = document.createElement('div');
		let thirdAttachmentsDiv = document.createElement('div');

		arrayWithoutNoPhotoAttachments.forEach(function (attachment, index) {
			if (arrayWithoutNoPhotoAttachments.length === 1) {
				if (index === 0) {
					resultElement.appendChild(firstAttachmentsDiv);

					let image = document.createElement('img');
					image.src = attachment.photo.url.main;
					firstAttachmentsDiv.appendChild(image);

					image.style.maxWidth = '100%';
					image.style.maxHeight = parseInt(attachment.photo.meta.height / 2) + 'px';
				}
			}
			if (arrayWithoutNoPhotoAttachments.length === 2) {
				if (index === 0) {
					resultElement.appendChild(firstAttachmentsDiv);

					let image = document.createElement('img');
					image.src = attachment.photo.url.main;
					firstAttachmentsDiv.appendChild(image);

					image.style.maxWidth = '50%';
					image.style.marginRight = '5px';
					//image.style.maxHeight = parseInt(attachment.photo.meta.height / 2) + 'px';
				}
				if (index === 1) {
					let image = document.createElement('img');
					image.src = attachment.photo.url.main;
					firstAttachmentsDiv.appendChild(image);

					image.style.maxWidth = '50%';
					//image.style.maxHeight = parseInt(attachment.photo.meta.height / 2) + 'px';
				}
				console.log(attachment);
			}
			if (arrayWithoutNoPhotoAttachments.length === 3) {}
			if (arrayWithoutNoPhotoAttachments.length === 4) {}
			if (arrayWithoutNoPhotoAttachments.length === 5) {}
			if (arrayWithoutNoPhotoAttachments.length === 6) {}
			if (arrayWithoutNoPhotoAttachments.length === 7) {}
			if (arrayWithoutNoPhotoAttachments.length === 8) {}
			if (arrayWithoutNoPhotoAttachments.length === 9) {}
			if (arrayWithoutNoPhotoAttachments.length >= 10) {}
		});

		let restAttachmentsArray = doneAttachmentsArray.filter(function (attachment) {
			return attachment.type !== 'photo';
		});

		return resultElement;
	},
	time: function (timestamp, withOutDate = false, withOutHours = false) {
		if (withOutHours && withOutDate) {
			withOutDate = !withOutDate;
		}

		if (!timestamp) return "";
		let time = new Date(Number(timestamp) * 1000);

		let fullYear = String(time.getFullYear());
		let fullMonth = ((time.getMonth() + 1) < 10) ? ("0" + String(time.getMonth() + 1)) : (String(time.getMonth() + 1));
		let fullDay = ((time.getDate()) < 10) ? ("0" + String(time.getDate())) : (String(time.getDate()));

		let fullHours = ((time.getHours()) < 10) ? ("0" + String(time.getHours())) : (String(time.getHours()));
		let fullMinutes =  ((time.getMinutes()) < 10) ? ("0" + String(time.getMinutes())) : (String(time.getMinutes()));

		let resultedString = '';
		if (!withOutDate) resultedString += (fullDay + '.' + fullMonth + '.' + fullYear);
		if (!withOutHours) resultedString += (', ' + fullHours + ':' + fullMinutes);

		return resultedString;
	}
});

unt.icons = new Object({
	done: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>',
	close: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
	profileStatus: '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-5h2v5zm4 0h-2v-3h2v3zm0-5h-2v-2h2v2zm4 5h-2V7h2v10z"/></svg>',
	edit: '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
	logout: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>',
	pin: '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="15" viewBox="0 0 24 24" width="15"><g><rect fill="none" height="24" width="24"/></g><g><path d="M16,9V4l1,0c0.55,0,1-0.45,1-1v0c0-0.55-0.45-1-1-1H7C6.45,2,6,2.45,6,3v0 c0,0.55,0.45,1,1,1l1,0v5c0,1.66-1.34,3-3,3h0v2h5.97v7l1,1l1-1v-7H19v-2h0C17.34,12,16,10.66,16,9z" fill-rule="evenodd"/></g></svg>',
	cookies: '<?xml version="1.0" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd"><svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 77.000000 77.000000" preserveAspectRatio="xMidYMid meet"><g style="fill: var(--svg-fill-color, black);" transform="translate(0.000000,77.000000) scale(0.100000,-0.100000)" stroke="none"><path d="M296 760 c-59 -15 -153 -69 -186 -107 -40 -45 -89 -143 -99 -198 -12 -62 13 -208 44 -260 34 -59 117 -132 183 -163 48 -22 72 -26 147 -27 80 0 97 3 155 31 82 39 124 75 167 141 63 99 79 218 42 325 -38 113 -97 179 -206 230 -69 33 -178 45 -247 28z m64 -210 c25 -25 25 -45 -1 -71 -27 -27 -75 -15 -79 21 -5 35 1 51 23 60 32 13 35 12 57 -10z m250 -200 c25 -25 25 -45 -1 -71 -27 -27 -75 -15 -79 21 -5 35 1 51 23 60 32 13 35 12 57 -10z m-300 -50 c25 -25 25 -45 -1 -71 -27 -27 -75 -15 -79 21 -5 35 1 51 23 60 32 13 35 12 57 -10z"/></g></svg>',
	biteCookies: '<?xml version="1.0" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd"><svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 80.000000 77.000000" preserveAspectRatio="xMidYMid meet"><g style="fill: var(--svg-fill-color, black);" transform="translate(0.000000,77.000000) scale(0.100000,-0.100000)" stroke="none"><path d="M290 754 c-139 -38 -253 -159 -279 -299 -10 -54 9 -186 35 -241 25 -53 119 -145 175 -172 153 -74 353 -40 465 80 52 56 100 151 109 221 7 44 6 47 -16 47 -70 0 -163 85 -175 162 -5 30 -11 38 -27 38 -64 0 -157 82 -172 151 -8 34 -28 36 -115 13z m70 -204 c25 -25 25 -45 -1 -71 -27 -27 -75 -15 -79 21 -5 35 1 51 23 60 32 13 35 12 57 -10z m250 -200 c25 -25 25 -45 -1 -71 -27 -27 -75 -15 -79 21 -5 35 1 51 23 60 32 13 35 12 57 -10z m-300 -50 c25 -25 25 -45 -1 -71 -27 -27 -75 -15 -79 21 -5 35 1 51 23 60 32 13 35 12 57 -10z"/></g></svg>',
	paletteAnimated: '<svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 24 24" width="20"><path d="M0 0h24v24H0z" fill="none"></path><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"></path><g><circle r="2" cx="6.3" cy="10" fill="#ffee00" id="circle-1"><animate id="circle-1" attributeName="fill"  attributeType="XML" values="#fff59d;#fff176;#ffeb3b;#fff59d" dur="2.9s" repeatCount="indefinite"/></circle><circle r="2" cx="9.7" cy="7" fill="#ff0000" id="circle-2"><animate id="circle-2" attributeName="fill" attributeType="XML" values="#ef9a9a;#ef5350;#f44336;#ef9a9a" dur="2.9s" repeatCount="indefinite"/></circle><circle r="2" cx="14.3" cy="6.7" fill="#4dff00" id="circle-3"><animate id="circle-3" attributeName="fill" attributeType="XML" values="#bbdefb;#90caf9;#42a5f5;#bbdefb" dur="2.9s" repeatCount="indefinite"/></circle><circle r="2" cx="17.3" cy="10" fill="#00aeff" id="circle-4"><animate id="circle-4" attributeName="fill" attributeType="XML" values="f8bbd0;f48fb1;ec407a;f8bbd0;" dur="2.9s" repeatCount="indefinite"/></circle></g></svg>',
	downArrow: '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/></svg>',
	forbidden: '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>',
	message: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
	add_friend: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
	failed: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8 0-1.85.63-3.55 1.69-4.9L16.9 18.31C15.55 19.37 13.85 20 12 20zm6.31-3.1L7.1 5.69C8.45 4.63 10.15 4 12 4c4.42 0 8 3.58 8 8 0 1.85-.63 3.55-1.69 4.9z"/></svg>',
	main: '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><path d="M0,0h24v24H0V0z" fill="none"/><path d="M12,7V3H2v18h20V7H12z M6,19H4v-2h2V19z M6,15H4v-2h2V15z M6,11H4V9h2V11z M6,7H4V5h2V7z M10,19H8v-2h2V19z M10,15H8v-2h2 V15z M10,11H8V9h2V11z M10,7H8V5h2V7z M20,19h-8v-2h2v-2h-2v-2h2v-2h-2V9h8V19z M18,11h-2v2h2V11z M18,15h-2v2h2V15z"/></g></svg>',
	notifications: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>',
	lock: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>',
	security: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>',
	list: '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0,0h24v24H0V0z" fill="none"/><g><path d="M19.5,3.5L18,2l-1.5,1.5L15,2l-1.5,1.5L12,2l-1.5,1.5L9,2L7.5,3.5L6,2v14H3v3c0,1.66,1.34,3,3,3h12c1.66,0,3-1.34,3-3V2 L19.5,3.5z M19,19c0,0.55-0.45,1-1,1s-1-0.45-1-1v-3H8V5h11V19z"/><rect height="2" width="6" x="9" y="7"/><rect height="2" width="2" x="16" y="7"/><rect height="2" width="6" x="9" y="10"/><rect height="2" width="2" x="16" y="10"/></g></svg>',
	accounts: '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><path d="M0,0h24v24H0V0z" fill="none"/></g><g><g><circle cx="10" cy="8" r="4"/><path d="M10.67,13.02C10.45,13.01,10.23,13,10,13c-2.42,0-4.68,0.67-6.61,1.82C2.51,15.34,2,16.32,2,17.35V20h9.26 C10.47,18.87,10,17.49,10,16C10,14.93,10.25,13.93,10.67,13.02z"/><path d="M20.75,16c0-0.22-0.03-0.42-0.06-0.63l1.14-1.01l-1-1.73l-1.45,0.49c-0.32-0.27-0.68-0.48-1.08-0.63L18,11h-2l-0.3,1.49 c-0.4,0.15-0.76,0.36-1.08,0.63l-1.45-0.49l-1,1.73l1.14,1.01c-0.03,0.21-0.06,0.41-0.06,0.63s0.03,0.42,0.06,0.63l-1.14,1.01 l1,1.73l1.45-0.49c0.32,0.27,0.68,0.48,1.08,0.63L16,21h2l0.3-1.49c0.4-0.15,0.76-0.36,1.08-0.63l1.45,0.49l1-1.73l-1.14-1.01 C20.72,16.42,20.75,16.22,20.75,16z M17,18c-1.1,0-2-0.9-2-2s0.9-2,2-2s2,0.9,2,2S18.1,18,17,18z"/></g></g></svg>',
	palette: '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><path d="M12,2C6.49,2,2,6.49,2,12s4.49,10,10,10c1.38,0,2.5-1.12,2.5-2.5c0-0.61-0.23-1.2-0.64-1.67c-0.08-0.1-0.13-0.21-0.13-0.33 c0-0.28,0.22-0.5,0.5-0.5H16c3.31,0,6-2.69,6-6C22,6.04,17.51,2,12,2z M17.5,13c-0.83,0-1.5-0.67-1.5-1.5c0-0.83,0.67-1.5,1.5-1.5 s1.5,0.67,1.5,1.5C19,12.33,18.33,13,17.5,13z M14.5,9C13.67,9,13,8.33,13,7.5C13,6.67,13.67,6,14.5,6S16,6.67,16,7.5 C16,8.33,15.33,9,14.5,9z M5,11.5C5,10.67,5.67,10,6.5,10S8,10.67,8,11.5C8,12.33,7.33,13,6.5,13S5,12.33,5,11.5z M11,7.5 C11,8.33,10.33,9,9.5,9S8,8.33,8,7.5C8,6.67,8.67,6,9.5,6S11,6.67,11,7.5z"/></g></svg>',
	sound: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none" opacity=".1"/><path d="M12 1c-4.97 0-9 4.03-9 9v7c0 1.66 1.34 3 3 3h3v-8H5v-2c0-3.87 3.13-7 7-7s7 3.13 7 7v2h-4v8h4v1h-7v2h6c1.66 0 3-1.34 3-3V10c0-4.97-4.03-9-9-9z"/></svg>',
	addPerson: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
	messagesOk: '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24" x="0"/><path d="M17.34,20l-3.54-3.54l1.41-1.41l2.12,2.12l4.24-4.24L23,14.34L17.34,20z M12,17c0-3.87,3.13-7,7-7c1.08,0,2.09,0.25,3,0.68 V4c0-1.1-0.9-2-2-2H4C2.9,2,2,2.9,2,4v18l4-4h6v0c0-0.17,0.01-0.33,0.03-0.5C12.01,17.34,12,17.17,12,17z"/></g></svg>',
	wallPost: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M4 4h7V2H4c-1.1 0-2 .9-2 2v7h2V4zm6 9l-4 5h12l-3-4-2.03 2.71L10 13zm7-4.5c0-.83-.67-1.5-1.5-1.5S14 7.67 14 8.5s.67 1.5 1.5 1.5S17 9.33 17 8.5zM20 2h-7v2h7v7h2V4c0-1.1-.9-2-2-2zm0 18h-7v2h7c1.1 0 2-.9 2-2v-7h-2v7zM4 13H2v7c0 1.1.9 2 2 2h7v-2H4v-7z"/></svg>',
	comments: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1z"/></svg>',
	backArrow: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>'
});

unt.components = new Object({
	tabs: function (tabsObject) {
		let tabsElement = document.createElement('div');
		tabsElement.classList.add('card');
		tabsElement.style.width = 'inherit';

		let ul = document.createElement('ul');
		tabsElement.appendChild(ul);
		ul.classList.add('tabs');

		tabsObject.forEach(function (tabObject) {
			let li = document.createElement('li');
			li.classList.add('tab');
			li.classList.add('unselectable');
			li.classList.add('waves-effect');

			li.innerText = tabObject.title;
			if (tabObject.active) {
				li.style = 'border-bottom: inset; border-bottom-color: rgba(238, 110, 115, .7) !important';
			}
			li.addEventListener('click', function () {
				return unt.actions.linkWorker.go(tabObject.link, false, tabObject.internalData);
			});

			return ul.appendChild(li);
		});

		return tabsElement;
	},
	wall: new Object({
		post: function (wallPostObject, isFullWindow = false) {
			let element = document.createElement('div');
			element.classList.add('card');
			element.classList.add('waves-effect');
			element.boundObject = wallPostObject;
			element.style.padding = '20px';
			element.style.width = '100%';
			element.style.marginBottom = 0;

			let ownerInfoDiv = document.createElement('div');
			element.appendChild(ownerInfoDiv);
			ownerInfoDiv.classList.add('valign-wrapper');

			let photoDiv = document.createElement('div');
			ownerInfoDiv.appendChild(photoDiv);
			photoDiv.style.marginRight = '15px';

			let userImage = document.createElement('img');
			userImage.classList.add('circle');
			userImage.classList.add('unselectable');
			userImage.width = userImage.height = 42;
			photoDiv.appendChild(userImage);

			let credentialsDiv = document.createElement('div');
			credentialsDiv.classList.add('unselectable');
			ownerInfoDiv.appendChild(credentialsDiv);
			credentialsDiv.classList.add('halign-wrapper');

			let usernameDiv = document.createElement('a');
			credentialsDiv.appendChild(usernameDiv);
			usernameDiv.innerText = '...';
			usernameDiv.style.color = 'black';
			usernameDiv.style.fontWeight = 'bold';
			usernameDiv.style.fontSize = '110%';

			let postTimeDiv = document.createElement('div');
			credentialsDiv.appendChild(postTimeDiv);
			postTimeDiv.innerText = unt.parsers.time(wallPostObject.time);

			unt.settings.users.get(wallPostObject.owner_id).then(function (entity) {
				userImage.src = entity.photo_url;
				usernameDiv.innerText = entity.account_type === 'bot' ? entity.name : (entity.first_name + ' ' + entity.last_name);
				usernameDiv.setAttribute('target', '_blank');
				usernameDiv.href = '/' + (entity.screen_name ? entity.screen_name : (entity.account_type === 'user' ? ("id" + entity.user_id) : ("bot" + entity.bot_id)));
			}).catch(function (err) {
				usernameDiv.innerText = unt.settings.lang.getValue('deleted_account');
				photoDiv.hide();
			});

			if (wallPostObject.text && !wallPostObject.text.isEmpty()) {
				element.appendChild(document.createElement('br'));

				let postTextDiv = document.createElement('div');
				postTextDiv.classList.add('post_data');
				postTextDiv.innerHTML = nl2br(htmlspecialchars(wallPostObject.text ? wallPostObject.text : '').linkify());
				element.appendChild(postTextDiv);

				if (!isFullWindow) {
					postTextDiv.addEventListener('click', function (event) {
						if (unt.tools.isMobile()) {
							return unt.actions.linkWorker.go('/wall' + wallPostObject.owner_id + '_' + wallPostObject.id, true, wallPostObject);
						} else {

						}
					})
				}

				if (wallPostObject.text.length > 300) {
					let showFullPostButton = document.createElement('a');
					element.appendChild(showFullPostButton);
					showFullPostButton.innerText = unt.settings.lang.getValue('show_more');
					showFullPostButton.style.color = 'red';
					showFullPostButton.style.cursor = 'pointer';
					showFullPostButton.style.fontWeight = 'bold';

					showFullPostButton.addEventListener('click', function (event) {
						if (postTextDiv.classList.contains('post_data')) {
							postTextDiv.classList.remove('post_data');
							showFullPostButton.innerText = unt.settings.lang.getValue('hide') + '...';
						} else {
							postTextDiv.classList.add('post_data');
							showFullPostButton.innerText = unt.settings.lang.getValue('show_more');
						}
					});
				}
			}

			if (Array.isArray(wallPostObject.attachments) && wallPostObject.attachments.length > 0) {
				let attachmentsDiv = unt.parsers.attachments(wallPostObject.attachments);

				element.appendChild(attachmentsDiv);
			}

			return element;
		}
	}),
	alertBanner: function (icon, header, alertionText) {
		let alertBannerCard = document.createElement('div');
		alertBannerCard.classList.add('card');
		alertBannerCard.style.marginBottom = 0;

		let alertContainer = document.createElement('div');
		alertBannerCard.appendChild(alertContainer);
		alertContainer.classList.add('valign-wrapper');
		alertContainer.style.padding = '30px';

		let iconDiv = document.createElement('div');
		iconDiv.innerHTML = icon;
		iconDiv.getElementsByTagName('svg')[0].width.baseVal.value = 72;
		iconDiv.getElementsByTagName('svg')[0].height.baseVal.value = 72;
		alertContainer.appendChild(iconDiv);

		let informationContainer = document.createElement('div');
		alertContainer.appendChild(informationContainer);
		informationContainer.classList.add('halign-wrapper');
		informationContainer.style.marginLeft = '15px';

		let headerText = document.createElement('b');
		headerText.style.fontSize = '130%';
		informationContainer.appendChild(headerText);
		headerText.innerText = header;

		let alertionTextDiv = document.createElement('div');
		informationContainer.appendChild(alertionTextDiv);
		alertionTextDiv.innerText = alertionText;

		return alertBannerCard;
	},
	floatingActionButton: function (icon, description, fixed = false) {
		let floatingButton = document.createElement('div');
		if (fixed)
			floatingButton.classList.add('fixed-action-btn');

		floatingButton.classList.add('tooltipped');
		floatingButton.setAttribute('data-position', 'left');
		floatingButton.setAttribute('data-tooltip', description);

		let button = document.createElement('a');
		button.classList = ['btn-floating btn-large red waves-effect waves-light'];
		floatingButton.appendChild(button);

		let iconContainer = document.createElement('i');
		button.appendChild(iconContainer);
		iconContainer.innerHTML = icon;
		iconContainer.style.marginTop = '6%';
		iconContainer.getElementsByTagName('svg')[0].style.fill = 'white';

		return floatingButton;
	},
	downfallingOptionsMenu: function (icon, title) {
		let resultedElement = document.createElement('ul');
		resultedElement.classList.add('collapsible');
		resultedElement.classList.add('waves-effect');
		resultedElement.style.width = '100%';
		resultedElement.style.marginBottom = 0;

		let someElement = document.createElement('li');
		resultedElement.appendChild(someElement);

		let headerDiv = document.createElement('div');
		someElement.appendChild(headerDiv);
		headerDiv.classList.add('collapsible-header');
		headerDiv.innerHTML = icon;

		let titleContainer = document.createElement('div');
		titleContainer.innerText = title;
		headerDiv.appendChild(titleContainer);
		titleContainer.style.marginLeft = '15px';

		let bodyCol = document.createElement('div');
		bodyCol.classList.add('collapsible-body');
		someElement.appendChild(bodyCol);

		let itemsCollection = document.createElement('div');
		itemsCollection.classList.add('collection');
		bodyCol.appendChild(itemsCollection);

		resultedElement.addOption = function (title, onClickHandler) {
			let aElement = document.createElement('a');
			aElement.innerText = title;
			aElement.classList.add('collection-item');
			aElement.addEventListener('click', function (event) {
				if (onClickHandler)
					return onClickHandler(event, aElement);
			});

			itemsCollection.appendChild(aElement);
			return resultedElement;
		}

		return resultedElement;
	},
	switchCardButtonsGroup: function (groupTitle) {
		let resultedElement = document.createElement('ul');
		resultedElement.classList.add('collapsible');
		resultedElement.style.width = '100%';
		resultedElement.style.marginBottom = 0;

		let elements = [];

		resultedElement.addCardButton = function (icon, title, onSwitchHandler) {
			let someElement = document.createElement('li');
			resultedElement.appendChild(someElement);

			elements.push(someElement);

			let headerDiv = document.createElement('div');
			someElement.appendChild(headerDiv);
			headerDiv.classList.add('collapsible-header');
			headerDiv.innerHTML = icon;

			let titleContainer = document.createElement('div');
			titleContainer.innerText = title;
			titleContainer.style.width = '100%';
			headerDiv.appendChild(titleContainer);
			titleContainer.style.marginLeft = '15px';

			let switchDivClass = document.createElement('div');
			switchDivClass.classList.add('switch');
			switchDivClass.style.marginTop = '-2px';
			switchDivClass.style.marginRight = '-15px';

			let label = document.createElement('label');
			switchDivClass.appendChild(label);
			headerDiv.appendChild(switchDivClass);

			let input = document.createElement('input');
			input.type = 'checkbox';
			label.appendChild(input);
			input.addEventListener('input', function (event) {
				if (input.checked)
					switchDivClass.style.marginTop = '4px';
				else
					switchDivClass.style.marginTop = '-2px';

				if (onSwitchHandler)
					onSwitchHandler(event, someElement, input.checked);
			});

			someElement.setChecked = function (checked) {
				input.checked = checked;
				if (input.checked)
					switchDivClass.style.marginTop = '4px';
				else
					switchDivClass.style.marginTop = '-2px';

				return resultedElement;
			}


			someElement.disable = function () {
				input.setAttribute('disabled', 'true');

				return someElement;
			}

			someElement.enable = function () {
				input.removeAttribute('disabled');

				return someElement;
			}

			let span = document.createElement('span');
			label.appendChild(span);
			span.classList.add('lever');

			return resultedElement;
		}

		resultedElement.getSwitchCardButton = function (index) {
			return elements[index] || null;
		}

		return resultedElement;
	},
	switchCardButton: function (icon, title, onSwitchHandler) {
		let resultedElement = document.createElement('ul');
		resultedElement.classList.add('collapsible');
		resultedElement.classList.add('waves-effect');
		resultedElement.style.width = '100%';
		resultedElement.style.marginBottom = 0;

		let someElement = document.createElement('li');
		resultedElement.appendChild(someElement);

		let headerDiv = document.createElement('div');
		someElement.appendChild(headerDiv);
		headerDiv.classList.add('collapsible-header');
		headerDiv.innerHTML = icon;

		let titleContainer = document.createElement('div');
		titleContainer.innerText = title;
		titleContainer.style.width = '100%';
		headerDiv.appendChild(titleContainer);
		titleContainer.style.marginLeft = '15px';

		let switchDivClass = document.createElement('div');
		switchDivClass.classList.add('switch');
		switchDivClass.style.marginTop = '-2px';
		switchDivClass.style.marginRight = '-15px';

		let label = document.createElement('label');
		switchDivClass.appendChild(label);
		headerDiv.appendChild(switchDivClass);

		let input = document.createElement('input');
		input.type = 'checkbox';
		label.appendChild(input);
		input.addEventListener('input', function (event) {
			if (input.checked)
				switchDivClass.style.marginTop = '4px';
			else
				switchDivClass.style.marginTop = '-2px';

			if (onSwitchHandler)
				onSwitchHandler(event, resultedElement, input.checked);
		});

		resultedElement.setChecked = function (checked) {
			input.checked = checked;
			if (input.checked)
				switchDivClass.style.marginTop = '4px';
			else
				switchDivClass.style.marginTop = '-2px';

			return resultedElement;
		}

		resultedElement.disable = function () {
			input.setAttribute('disabled', 'true');

			return resultedElement;
		}

		resultedElement.enable = function () {
			input.removeAttribute('disabled');

			return resultedElement;
		}

		let span = document.createElement('span');
		label.appendChild(span);
		span.classList.add('lever');

		return resultedElement;
	},
	cardButtonsGroup: function (groupTitle) {
		let resultedElement = document.createElement('ul');
		resultedElement.classList.add('collapsible');
		resultedElement.classList.add('waves-effect');
		resultedElement.style.width = '100%';
		resultedElement.style.marginBottom = 0;

		resultedElement.addCardButton = function (icon, title, onClickHandler) {
			let someElement = document.createElement('li');
			resultedElement.appendChild(someElement);

			let headerDiv = document.createElement('div');
			someElement.appendChild(headerDiv);
			headerDiv.classList.add('collapsible-header');
			headerDiv.innerHTML = icon;

			let titleContainer = document.createElement('div');
			titleContainer.innerText = title;
			headerDiv.appendChild(titleContainer);
			headerDiv.classList.add('waves-effect');
			headerDiv.style.width = '100%';
			titleContainer.style.marginLeft = '15px';

			someElement.addEventListener('click', function () {
				if (onClickHandler)
					return onClickHandler(event, resultedElement);
			});

			return resultedElement;
		}

		return resultedElement;
	},
	cardButton: function (icon, title, onClickHandler) {
		let resultedElement = document.createElement('ul');
		resultedElement.classList.add('collapsible');
		resultedElement.style.width = '100%';
		resultedElement.style.marginBottom = 0;

		let someElement = document.createElement('li');
		resultedElement.appendChild(someElement);

		let headerDiv = document.createElement('div');
		someElement.appendChild(headerDiv);
		headerDiv.classList.add('collapsible-header');
		headerDiv.classList.add('waves-effect');
		headerDiv.style.width = '100%';
		headerDiv.innerHTML = icon;

		let titleContainer = document.createElement('div');
		titleContainer.innerText = title;
		headerDiv.appendChild(titleContainer);
		titleContainer.style.marginLeft = '15px';

		resultedElement.addEventListener('click', function () {
			if (onClickHandler)
				return onClickHandler(event, resultedElement);
		});

		return resultedElement;
	},
	image: function (attachmentObject, classList, width, height) {
		let image = document.createElement('img');
		image.src = attachmentObject ? attachmentObject.photo.url.main : 'https://dev.yunnet.ru/images/default.png';
		image.classList = classList;
		image.width = Number(width);
		image.height = Number(height);
		image.style.cursor = 'pointer';
		image.addEventListener('click', function (event) {
			return unt.actions.dialog('A', 'Soon?', false, true);
		});

		return image;
	},
	textField: function (hintTitle) {
		let inputDiv = document.createElement('div');
		inputDiv.classList.add('input-field');

		let inputId = getRandomInt(-99999999999, 9999999999) + '_inp';

		let input = document.createElement('input');
		inputDiv.appendChild(input);
		input.type = 'text';
		input.id = inputId;

		let label = document.createElement('label');
		inputDiv.appendChild(label);
		label.setAttribute('for', inputId);
		label.innerText = hintTitle;

		inputDiv.setText = function (text) {
			if (!text) text = '';
			if (!text.isEmpty())
				label.classList.add('active');
			else
				label.classList.remove('active');

			input.value = text;

			return inputDiv;
		}

		inputDiv.getInput = function () {
			return input;
		}

		inputDiv.getText = function () {
			return input.value;
		}

		inputDiv.disable = function () {
			input.setAttribute('disabled', true);

			return inputDiv;
		}

		inputDiv.enable = function () {
			input.removeAttribute('disabled');

			return inputDiv;
		}

		return inputDiv;
	},
	initDefaultForm: function () {
		return new Promise(function (resolve, reject) {
			document.body.addEventListener('drop', function (event) {
			    event.preventDefault();
			    event.stopPropagation();
			})
			document.body.addEventListener('dragleave', function (event) {
			    event.preventDefault();
			    event.stopPropagation();
			})
			document.body.addEventListener('dragover', function (event) {
			    event.preventDefault();
			    event.stopPropagation();
			})
			document.body.addEventListener('dragenter', function (event) {
			    event.preventDefault();
			    event.stopPropagation();
			})

			try {
				if ('serviceWorker' in navigator) {
			 		navigator.serviceWorker.getRegistrations().then(function (registrations) {
						if (!registrations[0]) {
			    			navigator.serviceWorker.register('/internal/sw.js', {scope: '/'})
			    		}
			   		});
			  	}
			} catch (e) {
				console.log('[SW] Failed.');
			}

			unt.settings.users.get().then(function (user) {
				unt.settings.users.current = user;
				unt.settings.users.current.logout = function () {
					return new Promise(function (resolve) {
						return unt.tools.Request({
							url: '/settings',
							method: 'POST',
							data: (new POSTData()).append('action', 'logout').build(),
							success: function () {
								return window.location.reload();
							},
							error: function () {
								return unt.toast({html: unt.settings.lang.getValue('upload_error')});
							}
						});
					});
				};

				unt.settings.users.current.profile = {
					toggleClose: function () {
						return new Promise(function (resolve) {
							return unt.tools.Request({
								url: '/settings',
								method: 'POST',
								data: (new POSTData()).append('action', 'toggle_profile_state').build(),
								success: function (response) {
									return resolve(unt.settings.current.account.is_closed = !unt.settings.current.account.is_closed);
								},
								error: function () {
									return resolve(unt.settings.current.account.is_closed);
								}
							});
						});
					}
				};

				unt.settings.users.current.edit = {
					status: function () {
						let win = unt.components.windows.createImportantWindow({
						    title: unt.settings.lang.getValue('edit_status')
						});

						let winMenu = win.getMenu();
						return new Promise(function (resolve) {
							win.show();

							let cardArea = document.createElement('div');
							winMenu.appendChild(cardArea);
							cardArea.style.padding = '10px';

							let textField = unt.components.textField(unt.settings.lang.getValue('edit_status')).setText(unt.settings.users.current.status);
							cardArea.appendChild(textField);
							textField.style.margin = '14px';

							let buttonsDiv = document.createElement('div');
							buttonsDiv.style.width = '100%';
							buttonsDiv.style.textAlign = 'end';
							buttonsDiv.style.padding = '0 9px 9px 0';
							winMenu.appendChild(buttonsDiv);

							let okButton = document.createElement('a');
							okButton.classList = ['btn btn-flat waves-effect'];
							okButton.innerText = 'OK';
							buttonsDiv.appendChild(okButton);
							okButton.addEventListener('click', function () {
								if (unt.settings.users.current.status == textField.getText().trim()) return;

								okButton.classList.add('disabled');
								textField.disable();

								return unt.tools.Request({
									url: '/id' + unt.settings.users.current.user_id,
									method: 'POST',
									data: (new POSTData()).append('action', 'set_new_status').append('new_status', textField.getText().trim()).build(),
									success: function (response) {
										okButton.classList.remove('disabled');
										textField.enable();
										try {
											response = JSON.parse(response);
											if (response.success) {
												win.close();

												return resolve(textField.getText().trim());
											}
										} catch (e) {	
											return unt.toast({html: unt.settings.lang.getValue('upload_error')});
										}
									},
									error: function () {
										okButton.classList.remove('disabled');
										textField.enable();

										return unt.toast({html: unt.settings.lang.getValue('upload_error')});
									}
								});
							});
						});
					}
				};

				throw new Error('User pass');
			}).catch(function (err) {
				if (unt.settings.users.current) {
					unt.settings.get().then(function (settings) {
						unt.settings.lang.download().then(function (lang) {
							return resolve(true);
						}).catch(function () {
							return resolve(null);
						})
					}).catch(function () {
						return resolve(null);
					})
				} else {
					unt.settings.lang.download().then(function (lang) {
						return resolve(false);
					}).catch(function () {
						return resolve(null);
					})
				}
			})
		});
	},
	loaderElement: function (determinate = false) {
		if (determinate) {
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

			profileLoader.innerHTML = '<svg width="40" height="40" viewBox="0 0 50 50"><path id="loader_ui_spin" transform="rotate(61.2513 25 25)" d="M25,5A20.14,20.14,0,0,1,45,22.88a2.51,2.51,0,0,0,2.49,2.26h0A2.52,2.52,0,0,0,50,22.33a25.14,25.14,0,0,0-50,0,2.52,2.52,0,0,0,2.5,2.81h0A2.51,2.51,0,0,0,5,22.88,20.14,20.14,0,0,1,25,5Z" style="fill: var(--unt-loader-color, #42a5f5);"><animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.4s" repeatCount="indefinite"></animateTransform></path></svg>';

			return profileLoader;
		}
	},
	windows: new Object({
		createWindow: function (params = {}) {
			let defaultParams = {
				onClose: new Function(),
				fullScreen: false,
				title: ''
			};

			for (let key in defaultParams) {
				if (defaultParams.hasOwnProperty(key) && (typeof defaultParams[key] === typeof params[key])) {
					defaultParams[key] = params[key];
				}
			}

			let modalWindow = document.createElement('div');
			modalWindow.classList.add('modal');
			if (unt.tools.isMobile())
				modalWindow.classList.add('bottom-sheet');

			let modalContent = document.createElement('div');
			let modalFooter  = document.createElement('div');

			modalContent.classList.add('modal-content');
			modalFooter.classList.add('modal-footer');

			modalWindow.appendChild(modalContent);
			modalWindow.appendChild(modalFooter);

			let header = document.createElement('div');
			header.classList.add('unselectable');
			modalContent.appendChild(header);

			header.classList.add('valign-wrapper');
			header.style.width = '100%';

			let headerText = document.createElement('div');
			header.appendChild(headerText);
			headerText.style.width = '100%';

			let closeButton = document.createElement('div');
			closeButton.style.cursor = 'pointer';
			closeButton.style.marginTop = '5px';
			header.appendChild(closeButton);

			let b = document.createElement('b');
			headerText.appendChild(b);

			closeButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="close unt_icon" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';

			closeButton.addEventListener('click', function () {
				closeWindow();
			});

			function openWindow () {
				document.body.appendChild(modalWindow);

				if (defaultParams.fullScreen) {
					if (!modalWindow.classList.contains('bottom-sheet'))
						modalWindow.classList.add('bottom-sheet');
				}
			
				unt.Modal.init(modalWindow, {
					onCloseEnd: function () {
						modalWindow.remove();

						defaultParams.onClose();
					}
				}).open();

				if (defaultParams.fullScreen) {
					modalWindow.style.width = '100%';
					modalWindow.style.height = '100%';
				}
			}

			let menuElement = document.createElement('div');
			modalContent.appendChild(menuElement);
			menuElement.style.width = '100%';

			function setTitle (title) {
				b.innerText = title;
			}

			setTitle(defaultParams.title);

			function closeWindow () {
				unt.Modal.getInstance(modalWindow).close();
			}

			return {
				setTitle: function (title) {
					setTitle(title);

					return this;
				},
				getElement: function () {
					return modalWindow;
				},
				getMenu: function () {
					return menuElement;
				},
				getFooter: function () {
					return modalFooter;
				},
				show: function () {
					openWindow();

					return this;
				},
				close: function () {
					closeWindow();

					return this;
				}
			};
		},
		createImportantWindow: function (params = {}) {
			let defaultParams = {
				closeAble: true,
				onClose: new Function(),
				title: ''
			};

			for (let key in defaultParams) {
				if (defaultParams.hasOwnProperty(key) && (typeof defaultParams[key] === typeof params[key])) {
					defaultParams[key] = params[key];
				}
			}

			let windowElement = document.createElement('div');
			windowElement.classList.add('important-window');

			let navigationDiv = document.createElement('div');
			navigationDiv.classList.add('navbar-fixed');
			windowElement.appendChild(navigationDiv);

			let nav = document.createElement('nav');
			nav.style = '-webkit-box-shadow: unset;background-color: transparent !important; box-shadow: unset;';
			navigationDiv.appendChild(nav);

			let navWrapper = document.createElement('div');
			navWrapper.classList.add('nav-wrapper');
			nav.appendChild(navWrapper);

			let ul = document.createElement('ul');
			navWrapper.appendChild(ul);

			let li = document.createElement('li');
			ul.appendChild(li);

			let a = document.createElement('a');
			a.classList.add('valign-wrapper');
			li.appendChild(a);

			let i = document.createElement('i');
			a.appendChild(i);

			i.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="close unt_icon" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
			if (!defaultParams.closeAble) {
				i.style.display = 'none';
			}

			let titleDiv = document.createElement('div');
			a.appendChild(titleDiv);
			i.style.marginRight = '15px';
			titleDiv.classList.add('unselectable');
			titleDiv.style.color = 'var(--unt-window-headers-color, black)';
			a.onclick = function () {
				if (!defaultParams.closeAble) return;

				let result = defaultParams.onClose();
				if (result == false) {
					return;
				}

				return closeWindow();
			}

			function showWindow () {
				document.body.appendChild(windowElement);
				unt.mmm({
					targets: '.main-menu',
					opacity: '0',
					duration: 200,
					easing: 'easeOutCirc',
					begin: function () {
						unt.components.mainBlock.style.display = '';
						windowElement.style.display = 'none';
					},
					complete: function () {
						unt.components.mainBlock.style.display = 'none';

						unt.mmm({
							targets: '.important-window',
							opacity: '1',
							duration: 250,
							easing: 'easeInCirc',
							begin: function () {
								windowElement.style.display = 'flex';
							}
						});
					}
				});
			}

			function closeWindow () {
				unt.mmm({
					targets: '.important-window',
					opacity: '0',
					duration: 250,
					easing: 'easeOutCirc',
					complete: function () {
						windowElement.remove();
						unt.components.mainBlock.style.display = '';

						unt.mmm({
							targets: '.main-menu',
							opacity: '1',
							duration: 200,
							easing: 'easeInCirc'
						});
					}
				});
			}

			function setTitle (title) {
				titleDiv.innerText = title;
			}

			setTitle(defaultParams.title);

			let menuElementContainer = document.createElement('div');
			menuElementContainer.style.width = '100%';
			menuElementContainer.style.height = '100%';
			windowElement.appendChild(menuElementContainer);

			let innerContainer = document.createElement('div');
			menuElementContainer.appendChild(innerContainer);

			if (!unt.tools.isMobile()) {
				innerContainer.style = 'top: 40%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%); padding-left: 5px; padding-right: 5px; padding-top: 5px; padding-bottom: 5px';
				innerContainer.style.width = '50%';
			} else {
				innerContainer.style.padding = '5px';
				innerContainer.style.margin = 0;
				innerContainer.style.marginTop = '10%';
			}

			let resultedMenuSpace = document.createElement('div');
			innerContainer.appendChild(resultedMenuSpace);
			innerContainer.classList.add('card');

			function getMenuElement () {
				return resultedMenuSpace;
			}

			return {
				getMenu: function () {
					return getMenuElement();
				},
				show: function show () {
					showWindow();

					return this;
				},
				close: function hide () {
					a.click();

					return this;
				},
				setTitle: function (title) {
					setTitle(title);

					return this;
				}
			};
		}
	}),
	createNavigationPanel: function () {
		let navFixed = document.createElement('div');
		navFixed.classList.add('navbar-fixed');

		let nav = document.createElement('nav');
		nav.classList = ['light-blue lighten-1'];
		navFixed.appendChild(nav);

		let wrapper = document.createElement('div');
		wrapper.classList.add('nav-wrapper');
		nav.appendChild(wrapper);

		if (unt.tools.isMobile()) {
			let ul = document.createElement('ul');
			wrapper.appendChild(ul);

			let li = document.createElement('li');
			ul.appendChild(li);

			let a = document.createElement('a');
			a.classList.add('valign-wrapper');
			li.appendChild(a);

			let i = document.createElement('i');
			a.appendChild(i);
			i.style.marginRight = '15px';
			i.innerHTML = '<svg id="nav_burger_icon" class="unt_icon" style="fill: white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"></path><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"></path></svg><svg id="nav_back_arrow_icon" style="fill: white; display: none" class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"></path><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path></svg>';
			i.getElementsByTagName('svg')[0].addEventListener('click', function () {
				unt.Sidenav.getInstance(document.getElementById('user-navigation')).open();
			});
			i.getElementsByTagName('svg')[1].addEventListener('click', function () {
				unt.actions.linkWorker.returnable() ? history.back() : '';
			});

			if (!unt.settings.users.current) {
				i.getElementsByTagName('svg')[0].style.display = 'none';
			}

			let titleDiv = document.createElement('div');
			titleDiv.classList.add('unselectable');
			a.appendChild(titleDiv);

			let additionalDiv = document.createElement('div');
			additionalDiv.classList.add('unselectable');
			a.appendChild(additionalDiv);

			navFixed.setTitle = function (title) {
				titleDiv.innerText = title;

				return navFixed;
			}

			navFixed.getAdditionalHeader = function () {
				return additionalDiv;
			}
			navFixed.getDefaultHeader = function () {
				return titleDiv;
			}

			navFixed.getBackButton = function () {
				return document.getElementById('nav_back_arrow_icon');
			}

			navFixed.getMenuButton = function () {
				return document.getElementById('nav_burger_icon');
			}
		} else {
			let navContainer = document.createElement('div');
			navContainer.classList.add('row');
			navContainer.style = 'max-width: 1500px; height: 100%';
			wrapper.appendChild(navContainer);

			let logoWrapper = document.createElement('div');
			navContainer.appendChild(logoWrapper);
			logoWrapper.style = unt.settings.users.current ? 'width: 25%' : 'width: 20%';

			let logoContainer = document.createElement('div');
			logoWrapper.appendChild(logoContainer);
			logoContainer.style = 'margin-left: 15px';

			let logo = document.createElement('a');
			logo.classList.add('no-continue-browsing');
			logo.classList.add('valign-wrapper');
			logo.href = '/';
			logoContainer.appendChild(logo);

			let logoImage = document.createElement('img');
			logoImage.classList.add('circle');
			logoImage.width = logoImage.height = 32;
			logoImage.src = 'https://yunnet.ru/favicon.ico';
			logo.appendChild(logoImage);

			let siteTitle = document.createElement('b');
			logo.appendChild(siteTitle);
			siteTitle.style.marginLeft = '15px';
			siteTitle.innerText = 'yunNet.';
			logo.classList.add('unselectable');

			let currentPageTitleContainer = document.createElement('div');
			currentPageTitleContainer.classList = ['col s6'];
			navContainer.appendChild(currentPageTitleContainer);

			let currentPageTitleValign = document.createElement('div');
			currentPageTitleValign.classList.add('valign-wrapper');
			currentPageTitleContainer.appendChild(currentPageTitleValign);

			let currentPageBack = document.createElement('a');
			currentPageBack.style.cursor = 'pointer';
			currentPageBack.style.display = 'none';
			currentPageBack.style.marginRight = '15px';
			currentPageTitleValign.appendChild(currentPageBack);
			currentPageBack.innerHTML = '<i style="margin-bottom: -3px"><svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"></path><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" fill="white"></path></svg></i>';
			currentPageBack.getElementsByTagName('svg')[0].addEventListener('click', function () {
				unt.actions.linkWorker.returnable() ? history.back() : this.style.display = 'none';
			});

			let currentPage = document.createElement('div');
			currentPageTitleValign.appendChild(currentPage);
			currentPage.style.width = '100%';
			currentPage.style.fontSize = '90%';
			currentPage.classList.add('current-title');
			currentPage.classList.add('unselectable');

			let additionalPage = document.createElement('div');
			currentPageTitleValign.appendChild(additionalPage);
			additionalPage.classList.add('current-additional');
			additionalPage.classList.add('unselectable');
			additionalPage.hide();

			let partialActions = document.createElement('div');
			navContainer.appendChild(partialActions);
			partialActions.style.flex = 'auto';

			let partialActionsContainer = document.createElement('div');
			partialActionsContainer.style.flex = 'auto';
			partialActionsContainer.classList.add('valign-wrapper');
			partialActionsContainer.classList.add('right');
			partialActions.appendChild(partialActionsContainer);

			if (unt.settings.users.current) {
				let userInfoActionContainer = document.createElement('a');

				userInfoActionContainer.classList.add('dropdown-trigger');
				userInfoActionContainer.style.cursor = 'pointer';
				userInfoActionContainer.classList.add('unselectable');
				userInfoActionContainer.classList.add('valign-wrapper');
				userInfoActionContainer.style.width = '100%';
				partialActionsContainer.appendChild(userInfoActionContainer);

				let userImage = document.createElement('img');
				userInfoActionContainer.appendChild(userImage);
				userImage.classList.add('circle');
				userImage.width = userImage.height = 28;
				userImage.src = unt.settings.users.current.photo_url;

				let userCredentials = document.createElement('div');
				userCredentials.style.marginLeft = '15px';
				userCredentials.style.fontSize = '90%';
				userCredentials.innerText = unt.settings.users.current.first_name + ' ' + unt.settings.users.current.last_name;
				userInfoActionContainer.appendChild(userCredentials);

				let arrowButton = document.createElement('i');
				userInfoActionContainer.appendChild(arrowButton);
				arrowButton.innerHTML = unt.icons.downArrow;
				arrowButton.style.marginLeft = arrowButton.style.marginRight = '10px';
				arrowButton.getElementsByTagName('svg')[0].style.fill = 'white';
				arrowButton.getElementsByTagName('svg')[0].style.marginTop = '13px';

				let ulDropdownContent = document.createElement('ul');
				ulDropdownContent.classList.add('dropdown-content');
				ulDropdownContent.id = 'actionsId';
				userInfoActionContainer.setAttribute('data-target', ulDropdownContent.id);
				partialActionsContainer.appendChild(ulDropdownContent);

				let userInfoContent = document.createElement('li');
				ulDropdownContent.appendChild(userInfoContent);

				let currentUserProfileLink = document.createElement('a');
				userInfoContent.appendChild(currentUserProfileLink);
				currentUserProfileLink.href = '/' + (unt.settings.users.current.screen_name ? unt.settings.users.current.screen_name : ('id' + unt.settings.users.current.user_id));

				let infoDiv = document.createElement('div');
				infoDiv.classList.add('valign-wrapper');
				currentUserProfileLink.appendChild(infoDiv);

				let userImageDrop = document.createElement('img');
				infoDiv.appendChild(userImageDrop);
				userImageDrop.classList.add('circle');
				userImageDrop.width = userImage.height = 28;
				userImageDrop.src = unt.settings.users.current.photo_url;
				userImageDrop.style.marginRight = '15px';

				let userCredentialsInfo = document.createElement('div');
				userCredentialsInfo.innerText = unt.settings.users.current.first_name + ' ' + unt.settings.users.current.last_name;
				infoDiv.appendChild(userCredentialsInfo);

				let fdividerLi = document.createElement('li');
				fdividerLi.classList.add('divider');
				ulDropdownContent.appendChild(fdividerLi);

				let settingsButtonLi = document.createElement('li');
				ulDropdownContent.appendChild(settingsButtonLi);

				let settingsButton = document.createElement('a');
				settingsButtonLi.appendChild(settingsButton);
				settingsButton.innerText = unt.settings.lang.getValue('settings');
				settingsButton.addEventListener('click', function () {
					return unt.actions.linkWorker.go('/settings');
				});

				let dividerLi = document.createElement('li');
				dividerLi.classList.add('divider');
				ulDropdownContent.appendChild(dividerLi);

				let logoutButtonLi = document.createElement('li');
				ulDropdownContent.appendChild(logoutButtonLi);

				let logoutButton = document.createElement('a');
				logoutButtonLi.appendChild(logoutButton);
				logoutButton.innerText = unt.settings.lang.getValue('logout');
				logoutButton.addEventListener('click', function () {
					return unt.actions.dialog(unt.settings.lang.getValue('logout_q'), unt.settings.lang.getValue('logout_qq'), true, true).then(function (response) {
						if (response) {
							unt.toast({html: unt.settings.lang.getValue('logout_q') + '...'});

							return unt.settings.users.current.logout();
						}
					});
				});
			}

			navFixed.setTitle = function (title) {
				currentPage.innerText = title;

				return navFixed;
			}

			navFixed.getAdditionalHeader = function () {
				return additionalPage;
			}
			navFixed.getDefaultHeader = function () {
				return currentPage;
			}

			navFixed.getMenuButton = function () {
				return navFixed.getBackButton();
			}

			navFixed.getBackButton = function () {
				return currentPageBack;
			}
		}

		return navFixed;
	},
	buildDefaultPageForm: function () {
		let mainDiv = document.createElement('div');
		mainDiv.classList.add('main-menu');
		document.body.appendChild(mainDiv);

		unt.components.mainBlock = mainDiv;

		let navPanel = unt.components.createNavigationPanel();
		mainDiv.appendChild(navPanel);
		unt.components.navPanel = navPanel;

		if (!unt.settings.users.current)
			unt.components.navPanel.hide();

		let menuContainer = document.createElement('div');
		let collectionUl = document.createElement('ul');

		if (unt.tools.isMobile()) {
			if (unt.settings.users.current) {
				mainDiv.appendChild(collectionUl);
				menuContainer.style.maxWidth = '1500px';
				menuContainer.style.margin = 'auto';

				collectionUl.classList.add('sidenav');
				collectionUl.id = 'user-navigation';

				let a = document.createElement('a');
				a.classList.add('no-continue-browsing');
				mainDiv.appendChild(a);
				a.classList.add('sidenav-trigger');

				a.setAttribute('data-target', collectionUl.id);
				a.href = '#';

				let li = document.createElement('li');
				collectionUl.appendChild(li);

				let userView = document.createElement('div');
				userView.classList.add('user-view');
				li.appendChild(userView);

				let bg = document.createElement('div');
				userView.appendChild(bg);
				bg.classList.add('background');
				bg.style.background = 'currentColor';

				let userLink = document.createElement('a');
				userLink.href = '/' + (unt.settings.users.current.screen_name ? unt.settings.users.current.screen_name : ('id' + unt.settings.users.current.user_id));
				userView.appendChild(userLink);

				let img = document.createElement('img');
				img.classList.add('circle');
				img.id = 'user_avatar';
				img.alt = '';
				img.src = unt.settings.users.current.photo_url;
				userLink.appendChild(img);

				let userLinkCredentials = document.createElement('a');
				userLinkCredentials.href = '/' + (unt.settings.users.current.screen_name ? unt.settings.users.current.screen_name : ('id' + unt.settings.users.current.user_id));
				userView.appendChild(userLinkCredentials);
				let textCredentials = document.createElement('span');
				textCredentials.classList = ['white-text name'];
				userLinkCredentials.appendChild(textCredentials);
				textCredentials.innerText = unt.settings.users.current.first_name + ' ' + unt.settings.users.current.last_name;

				let userLinkStatus = document.createElement('a');
				userLinkStatus.href = '/' + (unt.settings.users.current.screen_name ? unt.settings.users.current.screen_name : ('id' + unt.settings.users.current.user_id));
				userView.appendChild(userLinkStatus);
				let textStatus = document.createElement('span');
				textStatus.classList = ['white-text email'];
				userLinkCredentials.appendChild(textStatus);
				if (unt.settings.users.current.status)
					textStatus.innerText = unt.settings.users.current.status;

				unt.components.buildMenuItemsTable(unt.settings.current.theming.menu_items, collectionUl);
			}

			mainDiv.appendChild(menuContainer);

			return menuContainer;
		} else {
			menuContainer.classList.add('row');
			menuContainer.style.maxWidth = '1500px';
			menuContainer.style.height = '100%';
			mainDiv.appendChild(menuContainer);

			if (unt.settings.users.current) {
				let leftMenuContainer = document.createElement('div');
				leftMenuContainer.classList = ['col s3'];
				leftMenuContainer.style.padding = 0;
				leftMenuContainer.style.height = '100%';
				leftMenuContainer.style.position = 'fixed';
				leftMenuContainer.style.maxWidth = '370px';
				menuContainer.appendChild(leftMenuContainer);

				let resultedMenuPlaceholder = document.createElement('div');
				resultedMenuPlaceholder.classList = ['col s9'];
				resultedMenuPlaceholder.style.padding = 0;
				resultedMenuPlaceholder.style.height = '100%';
				menuContainer.appendChild(resultedMenuPlaceholder);

				let cardModel = document.createElement('div');
				leftMenuContainer.appendChild(cardModel);
				cardModel.classList.add('card');
				cardModel.style = 'height: 100%; padding: 0; margin: 0; width: 100%; border-radius: 0';

				cardModel.appendChild(collectionUl);
				collectionUl.classList.add('collection');

				collectionUl.style.margin = 0;
				collectionUl.style.padding = 0;

				/*let li = document.createElement('li');
				collectionUl.appendChild(li);
				li.classList.add('collection-item');
				li.classList.add('waves-effect');
				li.style = 'padding: 10px; padding-top: 15px; width: 100%; border-bottom: unset;';

				let a = document.createElement('a');
				li.appendChild(a);
				a.href = '/' + (unt.settings.users.current.screen_name ? unt.settings.users.current.screen_name : ('id' + unt.settings.users.current.user_id));
				a.classList.add('valign-wrapper');

				let img = document.createElement('img');
				a.appendChild(img);
				img.classList.add('circle');
				img.style = 'margin-left: 10px; margin-top: 2px; margin-bottom: 3px';
				img.width = img.height = 48;
				img.src = unt.settings.users.current.photo_url;

				let credDiv = document.createElement('div');
				a.appendChild(credDiv);
				credDiv.style = 'margin-left: 15px; color: black; font-size: 115%';

				let b = document.createElement('b');
				credDiv.appendChild(b);
				b.innerText = (unt.settings.users.current.first_name + ' ' + unt.settings.users.current.last_name);*/

				unt.components.buildMenuItemsTable(unt.settings.current.theming.menu_items, collectionUl);

				resultedMenuPlaceholder.style.paddingLeft = '7px';
				resultedMenuPlaceholder.style.paddingRight = '7px';

				return resultedMenuPlaceholder;
			} else {
				let resultedMenuPlaceholder = document.createElement('div');
				resultedMenuPlaceholder.classList = ['col s12'];
				resultedMenuPlaceholder.style.padding = 0;
				resultedMenuPlaceholder.style.height = '100%';
				resultedMenuPlaceholder.style.maxWidth = '60%';
				resultedMenuPlaceholder.style.margin = 'auto';
				menuContainer.appendChild(resultedMenuPlaceholder);

				resultedMenuPlaceholder.style.paddingLeft = '7px';
				resultedMenuPlaceholder.style.paddingRight = '7px';

				return resultedMenuPlaceholder;
			}
		}
	},
	buildMenuItemsTable: function (currentMenuItems = [1, 2, 3, 4, 5, 6], ulToAppend) {
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

		currentMenuItems.forEach(function (itemId) {
			if (unt.settings.users.current && unt.settings.users.current.is_banned) return;
			if (itemId === 8 && !unt.tools.isMobile()) return;

			let menuItemIndex = itemId - 1;

			let li = document.createElement('li');
			if (!unt.tools.isMobile())
				li.classList.add('collection-item');

			if (!unt.tools.isMobile())
				li.style.width = '100%';

			let a = document.createElement('a');
			a.classList.add('no-continue-browsing');

			if (!unt.tools.isMobile())
				a.classList.add('valign-wrapper');
			li.appendChild(a);

			if (unt.tools.isMobile())
				a.classList.add('waves-effect');
			else
				li.classList.add('waves-effect');

			a.href = links[menuItemIndex];

			a.innerHTML = (unt.tools.isMobile() ? '<i>' : '') + icons[menuItemIndex] + (unt.tools.isMobile() ? '</i>' : '') + '<div style="margin-left: 15px; color: black;">' + (unt.settings.lang.getValue(langValues[menuItemIndex]) || 'API') + '</div>';
			ulToAppend.appendChild(li);
		});

		return ulToAppend;
	}
});

unt.tools = new Object({
	isMobile: function () {
		if ("m.yunnet.ru" === window.location.host) return true;

        let t = false;
        let e;

        return e = navigator.userAgent || navigator.vendor || window.opera,
        (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(e) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(e.substr(0, 4))) && (t = !0),
        t
	},
	Request: function (requestParams = {}) {
		return new Promise(function (resolve, reject) {
			let params = {
				url: window.location.href,
				method: 'GET',
				data: null,
				context: window,
				success: function (result) {
					return resolve(result);
				},
				error: function (error) {
					return reject(error);
				},
				withCredentials: false
			};

			if (typeof requestParams !== "object") return params.error(null);

			for (let key in params) {
				if (requestParams.hasOwnProperty(key) && (typeof params[key] === typeof requestParams[key])) {
					params[key] = requestParams[key];
				}
			}

			try {
				let requestObject = _xmlHttpGet();
				requestObject.open(params.method, params.url);

				if (params.withCredentials)
					requestObject.withCredentials = true;

				requestObject.onreadystatechange = function () {
					if (requestObject.readyState !== XMLHttpRequest.DONE) return;

					let responseResult = requestObject.responseText;
					let responseState = requestObject.status || 0;

					if (responseState !== 200) {
						return params.error(new (class RequestError extends Error {
							construct (message) {
								this.name = 'RequestError';
								this.message = message;
								this.responseCode = responseState;
							}

							retry () {
								return unt.tools.Request(requestParams)
										.then(resolve).catch(reject);
							}
						})('Request to server failed'));
					} else {
						return params.success(responseResult, requestObject);
					}
				}

				return requestObject.send(params.data);
			} catch (e) {
				return params.error(e);
			}
		});
	}
});

unt.settings = new Object({
	current: null,
	get: function () {
		return new Promise(function (resolve, reject) {
			if (unt.settings.current)
				return resolve(unt.settings.current);

			return unt.tools.Request({
				url: '/flex',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_settings').build(),
				success: function (response) {
					try {
						response = JSON.parse(response);

						if (response.error)
							return reject(new TypeError('Failed to fetch the settings'));

						return resolve(unt.settings.current = response);
					} catch (e) {
						return reject(null);
					}
				},
				error: function (error) {
					return reject(error);
				}
			});
		});
	},

	managers: {
		privacyManager: function (privacyGroup) {
			if (!privacyGroup || (typeof privacyGroup !== 'number')) return null;

			let groups = {
				1: {name: 'can_write_messages', values: [0, 1, 2], lang: 'messages_privacy'},
				2: {name: 'can_write_on_wall', values: [0, 1, 2], lang: 'wall_privacy'},
				4: {name: 'can_comment_posts', values: [0, 1, 2], lang: 'who_can_comment_my_posts'},
				3: {name: 'can_invite_to_chats', values: [1, 2], lang: 'chat_privacy'}
			};

			let privacyValues = {
				0: 'all',
				1: 'only_friends',
				2: 'nobody'
			};

			if (!groups[privacyGroup]) return null;

			let windowParams = {title: unt.settings.lang.getValue('privacy'), fullScreen: false};

			let winObject = unt.tools.isMobile() ? unt.components.windows.createWindow(windowParams) : unt.components.windows.createImportantWindow(windowParams);
			let winMenu = winObject.getMenu();

			if (!unt.tools.isMobile())
				winMenu.style.padding = '10px';

			let descriptionDiv = document.createElement('div');
			if (unt.tools.isMobile())
				descriptionDiv.style.marginTop = '20px';
			else
				descriptionDiv.style.padding = '15px 18px 10px';

			descriptionDiv.innerText = unt.settings.lang.getValue(groups[privacyGroup].lang);
			winMenu.appendChild(descriptionDiv);

			let winForm = document.createElement('form');
			winMenu.appendChild(winForm);

			if (!unt.tools.isMobile())
				winForm.style.padding = '0px 15px';

			groups[privacyGroup].values.forEach(function (value) {
				let p = document.createElement('p');
				winForm.appendChild(p);

				let label = document.createElement('label');
				p.appendChild(label);

				let input = document.createElement('input');
				input.setAttribute('name', groups[privacyGroup].name);
				input.type = 'radio';
				input.classList.add('with-gap');
				label.appendChild(input);

				if (unt.settings.current.privacy[groups[privacyGroup].name] === value)
					input.checked = true;

				input.addEventListener('input', function () {
					return unt.tools.Request({
						url: '/settings',
						method: 'POST',
						data: (new POSTData()).append('action', 'set_privacy_settings').append('group', Number(privacyGroup)).append('value', Number(value)).build(),
						success: function (response) {
							try {
								response = JSON.parse(response);
								if (response.error)
									return unt.toast({html: unt.settings.lang.getValue('upload_error')});

								return unt.settings.current.privacy[groups[privacyGroup].name] = value;
							} catch (e) {
								return unt.toast({html: unt.settings.lang.getValue('upload_error')});
							}
						},
						error: function () {
							return unt.toast({html: unt.settings.lang.getValue('upload_error')});
						}
					});
				});

				let span = document.createElement('span');
				label.appendChild(span);
				span.innerText = unt.settings.lang.getValue(privacyValues[value]);
			});

			return winObject.show();
		}
	},

	lang: new Object({
		current: null,
		download: function () {
			return new Promise(function (resolve, reject) {
				return unt.tools.Request({
					url: '/flex',
					method: 'POST',
					data: (new POSTData()).append('action', 'get_language_value').append('value', '*').build(),
					success: function (response) {
						try {
							response = JSON.parse(response);

							if (response.error === 1)
								return reject(new TypeError('Failed to language download'));

							return resolve(unt.settings.lang.current = response);
						} catch (e) {
							return reject(null);
						}
					},
					error: function (error) {
						return reject(error);
					}
				});
			});
		},
		getValue (value) {
			if (!unt.settings.lang.current)
				return '';

			return (unt.settings.lang.current[value] || '');
		},
	}),

	users: new Object({
		current: null,
		users: {},
		resolve: function (screen_name, fields = '*') {
			return new Promise(function (resolve, reject) {
				return unt.tools.Request({
					url: '/flex',
					method: 'POST',
					data: (new POSTData()).append('action', 'get_user_data_by_link').append('screen_name', screen_name).build(),
					success: function (response) {
						try {
							response = JSON.parse(response);
							if (response.error)
								return reject(new TypeError('User not found'));

							return unt.settings.users.get(response.id, fields).then(function (user) {
								return resolve(user);
							}).catch(function (err) {
								return reject(err);
							})
						} catch (e) {
							return reject(null);
						}
					},
					error: function (error) {
						return reject(error);
					}
				});
			})
		},
		get: function (userId = 0, fields = '*') {
			return new Promise(function (resolve, reject) {
				if (unt.settings.users.users[userId])
					return resolve(unt.settings.users.users[userId]);

				return unt.tools.Request({
					url: '/flex',
					method: 'POST',
					data: (new POSTData()).append('action', 'get_user_data').append('fields', '*').append('id', (Number(userId) || 0)).build(),
					success: function (response) {
						try {
							response = JSON.parse(response);
							if (response.error)
								return reject(new TypeError('User not found'));

							return resolve(unt.settings.users.users[response.response.user_id || (response.response.bot_id * -1)] = response.response);
						} catch (e) {
							return reject(null);
						}
					},
					error: function (error) {
						return reject(error);
					}
				});
			}); 
		}
	})
});