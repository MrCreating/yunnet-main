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
unt.actions = new Object({
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
		define: function () {
			let currentObject = this;

			let navIcon = document.getElementById('nav_burger_icon');
			if (navIcon && !navIcon.defined) {
				navIcon.addEventListener('click', function (event) {
					unt.Sidenav.getInstance(document.getElementById('user-navigation')).open();

					navIcon.defined = true;
				});
			}

			let backIcon = document.getElementById('nav_back_arrow_icon');
			if (backIcon && !backIcon.defined) {
				backIcon.addEventListener('click', function (event) {
					unt.actions.linkWorker.go(unt.actions.history[unt.actions.linkWorker.currentPage.id === 0 ? 0 : (unt.actions.linkWorker.currentPage.id - 1)].url, false);

					backIcon.defined = true;
				});
			}

			let allLinksOnPage = document.querySelectorAll('a');
			for (let i = 0; i < allLinksOnPage.length; i++) {
				let linkElement = allLinksOnPage[i];

				if (linkElement.href !== null && linkElement.href !== "" && !String(linkElement.href).isEmpty() && !linkElement.defined) {
					linkElement.defined = true;
					linkElement.addEventListener('click', function handleElementClick (event) {
						event.stopPropagation();
						event.preventDefault();

						if (unt.tools.isMobile()) {
							unt.Sidenav.getInstance(document.getElementById('user-navigation')).close();
						}

						currentObject.go(linkElement.href);
					})
				}
			}
		}
	})
});

unt.components = new Object({
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
			windowElement.style.height = '100%';
			windowElement.style.flexDirection = 'column';
			windowElement.style.display = 'none';

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
				unt.components.mainBlock.style.display = 'none';
				windowElement.style.display = 'flex';
				document.body.appendChild(windowElement);
			}

			function closeWindow () {
				unt.components.mainBlock.style.display = '';
				windowElement.style.display = 'none';
				windowElement.remove();
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
		if (!unt.settings.users.current) return null;

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
			i.innerHTML = '<svg id="nav_burger_icon" class="unt_icon" style="fill: white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"></path><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"></path></svg><svg id="nav_back_arrow_icon" style="fill: white; display: none" class="unt_icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"></path><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path></svg>';
		
			let titleDiv = document.createElement('div');
			a.appendChild(titleDiv);
			titleDiv.style.marginLeft = '15px';

			navFixed.setTitle = function (title) {
				titleDiv.innerText = title;

				return navFixed;
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
			navContainer.style = 'margin: 0; height: 100%';
			wrapper.appendChild(navContainer);

			let logoWrapper = document.createElement('div');
			navContainer.appendChild(logoWrapper);
			logoWrapper.style = 'width: 250px';

			let logoContainer = document.createElement('div');
			logoWrapper.appendChild(logoContainer);
			logoContainer.style = 'margin-left: 15px';

			let logo = document.createElement('a');
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

			let currentPage = document.createElement('div');
			currentPageTitleValign.appendChild(currentPage);
			currentPage.style.width = '100%';
			currentPage.classList.add('current-title');

			let partialActions = document.createElement('div');
			navContainer.appendChild(partialActions);
			partialActions.style.flex = 'auto';

			let partialActionsContainer = document.createElement('div');
			partialActionsContainer.style.flex = 'auto';
			partialActionsContainer.classList.add('valign-wrapper');
			partialActionsContainer.classList.add('right');
			partialActions.appendChild(partialActionsContainer);

			let settingsIcon = document.createElement('a');
			settingsIcon.href = '/settings';
			partialActionsContainer.appendChild(settingsIcon);
			settingsIcon.style.cursor = 'pointer';
			settingsIcon.classList = ['tooltipped valign-wrapper'];
			settingsIcon.setAttribute('data-position', 'bottom');
			settingsIcon.setAttribute('data-tooltip', unt.settings.lang.getValue('settings'));
			settingsIcon.style.marginRight = '15px';
			settingsIcon.innerHTML = '<i><svg class="unt_icon" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24" viewBox="0 0 24 24" width="24"><g><path d="M0,0h24v24H0V0z" fill="none"></path><path fill="white" d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"></path></g></svg></i>'

			navFixed.setTitle = function (title) {
				currentPage.innerText = title;

				return navFixed;
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
		mainDiv.style.height = '100%';
		document.body.appendChild(mainDiv);

		unt.components.mainBlock = mainDiv;

		if (unt.settings.users.current) {
			let navPanel = unt.components.createNavigationPanel();
			mainDiv.appendChild(navPanel);
			unt.components.navPanel = navPanel;
		}

		let menuContainer = document.createElement('div');
		let collectionUl = document.createElement('ul');

		if (unt.tools.isMobile()) {
			if (unt.settings.users.current) {
				mainDiv.appendChild(collectionUl);

				collectionUl.classList.add('sidenav');
				collectionUl.id = 'user-navigation';

				let a = document.createElement('a');
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
			menuContainer.style.margin = 0;
			menuContainer.style.height = '100%';
			mainDiv.appendChild(menuContainer);

			if (unt.settings.users.current) {
				let leftMenuContainer = document.createElement('div');
				leftMenuContainer.classList = ['col s3'];
				leftMenuContainer.style.padding = 0;
				leftMenuContainer.style.height = '100%';
				leftMenuContainer.style.position = 'fixed';
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

				let li = document.createElement('li');
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
				b.innerText = (unt.settings.users.current.first_name + ' ' + unt.settings.users.current.last_name);

				unt.components.buildMenuItemsTable(unt.settings.current.theming.menu_items, collectionUl);

				return resultedMenuPlaceholder;
			} else {
				let resultedMenuPlaceholder = document.createElement('div');
				resultedMenuPlaceholder.classList = ['col s12'];
				resultedMenuPlaceholder.style.padding = 0;
				resultedMenuPlaceholder.style.height = '100%';
				menuContainer.appendChild(resultedMenuPlaceholder);

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
			if (!unt.tools.isMobile() && itemId === 8) return;

			if (unt.settings.users.current && unt.settings.users.current.is_banned) return;

			let menuItemIndex = itemId - 1;

			let li = document.createElement('li');
			if (!unt.tools.isMobile())
				li.classList.add('collection-item');

			if (!unt.tools.isMobile())
				li.style.width = '100%';

			let a = document.createElement('a');
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
		get: function (userId = 0) {
			return new Promise(function (resolve, reject) {
				if (unt.settings.users.users[userId])
					return resolve(unt.settings.users.users[userId]);

				return unt.tools.Request({
					url: '/flex',
					method: 'POST',
					data: (new POSTData()).append('action', 'get_user_data').append('id', (Number(userId) || 0)).build(),
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