const photos = {
	show: function (img, attachmentObject) {
		let photoUrl = img.src;

		let photoContainer = document.createElement('div');

		photoContainer.classList.add('image-view');
		photoContainer.style.background = '#101010';
		photoContainer.style.position = 'fixed';
		photoContainer.style.zIndex = 1046;

		photoContainer.style.left = photoContainer.style.right = photoContainer.style.bottom = photoContainer.style.top = 0;
		document.body.appendChild(photoContainer);

		let innerDiv = document.createElement('div');
		photoContainer.appendChild(innerDiv);
		innerDiv.style.width = '100%';
		innerDiv.style.height = '100%';

		// actions view
		let actionsDiv = document.createElement('div');
		actionsDiv.style.width = '100%';
		actionsDiv.style.padding = '15px';
		actionsDiv.style.zIndex = 1;
		actionsDiv.style.position = 'absolute';
		actionsDiv.style.top = 0;
		actionsDiv.style.background = 'linear-gradient(black, transparent)';

		let closeDiv = document.createElement('a');
		actionsDiv.appendChild(closeDiv);
		closeDiv.innerHTML = unt.Icon.CLOSE;
		closeDiv.getElementsByTagName('svg')[0].style.fill = 'white';

		innerDiv.appendChild(actionsDiv);
		closeDiv.href = '#';
		closeDiv.onclick = function (event) {
			event.preventDefault();

			photoContainer.remove();
		}

		actionsDiv.classList.add('valign-wrapper');
		let attachmentInfo = img.getAttribute('attachment').split('photo')[1].split('_');

		let ownerInfoDiv = document.createElement('a');
		ownerInfoDiv.classList.add('valign-wrapper');

		ownerInfoDiv.style.marginBottom = '5px';
		ownerInfoDiv.style.marginLeft = '10px';
		actionsDiv.appendChild(ownerInfoDiv);

		let ownerPhotoDiv = document.createElement('div');

		let ownerPhoto = document.createElement('img');
		ownerPhoto.classList.add('circle');
		ownerPhoto.width = ownerPhoto.height = 24;
		ownerPhoto.alt = '';

		ownerPhotoDiv.appendChild(ownerPhoto);
		ownerInfoDiv.appendChild(ownerPhotoDiv);

		let credentialsData = document.createElement('div');
		ownerInfoDiv.appendChild(credentialsData);
		credentialsData.style.marginLeft = '10px';

		credentialsData.innerText = '...';
		credentialsData.style.color = 'white';

		///////////////

		// photo loader
		let loader = pages.elements.getLoader();
		loader.style = 'position: absolute; top: 50%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%);';

		loader.getElementsByTagName('path')[0].style.fill = 'white';
		innerDiv.appendChild(loader);
		///////////////

		////////////////////
		let mainImage = document.createElement('img');
		mainImage.src = photoUrl;

		let imageDiv = document.createElement('div');
		innerDiv.appendChild(imageDiv);
		imageDiv.style = 'position: absolute; top: 50%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%); display: none; text-align: -webkit-center';
		mainImage.style.width = mainImage.style.height = '100%';
			
		mainImage.onload = function () {
			loader.remove();

			imageDiv.style.display = '';

			let imageContainer = document.createElement('div');
			imageDiv.appendChild(imageContainer);

			imageContainer.style.position = 'relative';
			imageContainer.style.top = 0;
			imageContainer.style.maxWidth = '70%';

			imageContainer.appendChild(mainImage);

			var scale = 1,
		        panning = false,
		        pointX = 0,
		        pointY = 0,
		        start = { x: 0, y: 0 },
		        zoom = mainImage;

		      function setTransform() {
		        zoom.style.transform = "translate(" + pointX + "px, " + pointY + "px) scale(" + scale + ")";
		      }

		      mainImage.ondblclick = function (event) {
		      	panning = !panning;

		      	event.preventDefault();
		      	if (panning) {
		        	start = { x: event.clientX - pointX, y: event.clientY - pointY };

		        	pointX = (event.clientX - start.x);
		       		pointY = (event.clientY - start.y);

		       		scale = 2;

		       		setTransform();
		      	} else {
		      		start = { x: 0, y: 0 };
		      		scale = 1;
		      		panning = false;
		      		pointX = 0;
		      		pointY = 0;

		      		zoom.style.transform = '';
		      	}
		      }

		      zoom.onmousemove = function (e) {
		        e.preventDefault();
		        if (!panning || !mainImage.pressed) {
		          return;
		        }
		        pointX = (e.clientX - start.x);
		        pointY = (e.clientY - start.y);
		        setTransform();
		      }

		      zoom.onwheel = function (e) {
		        e.preventDefault();
		        var xs = (e.clientX - pointX) / scale,
		          ys = (e.clientY - pointY) / scale,
		          delta = (e.wheelDelta ? e.wheelDelta : -e.deltaY);
		        (delta > 0) ? (scale *= 1.2) : (scale /= 1.2);
		        pointX = e.clientX - xs * scale;
		        pointY = e.clientY - ys * scale;

		        setTransform();
		      }

		    mainImage.onmousedown = function (event) {
				if (panning) {
					mainImage.pressed = true;
				}
			}

			imageDiv.onmouseup = function (event) {
				if (panning) {
					mainImage.pressed = false;
				}
			}

		    mainImage.ondragstart = function(event) {
				return event.preventDefault();
			}

			mainImage.ondragend = function(event) {
				return event.preventDefault();
			}

			/*mainImage.ondblclick = function (event) {
				event.preventDefault();

				if (mainImage.already === true) {
					mainImage.style.transform = 'unset';
					mainImage.already = false;
					mainImage.pressed = false;

					imageContainer.style.top = 0;
					imageContainer.style.left = 0;
				} else {
					mainImage.already = true;
					mainImage.style.transform = 'scale(2)';

					startY = event.clientX - pointX;
					startX = event.clientY - pointY;

					mainImage.style.transform = 'translate(' + startX + 'px, ' + startY + 'px) scale(2)';
				}
			}

			mainImage.oncontextmenu = function (event) {
				return event.preventDefault();
			}

			mainImage.onmousedown = function (event) {
				if (mainImage.already) {
					mainImage.pressed = true;
				}
			}

			imageDiv.onmouseup = function (event) {
				if (mainImage.already) {
					mainImage.pressed = false;
				}
			}

			imageDiv.onmousemove = function (event) {
				if (mainImage.already && mainImage.pressed) {
					pointX = (event.clientX - startX);
					pointY = (event.clientY - startY);

					imageContainer.style.top = pointX + 'px';
					imageContainer.style.left = pointY + 'px';
				}
			}*/
		}
		mainImage.onerror = function () {
			return loader.remove();
		}

		ownerInfoDiv.href = "/id" + attachmentInfo[0];
		ownerInfoDiv.target = "_blank";

		// down actions menu
		let downActionsDiv = document.createElement('div');
		downActionsDiv.style.width = '100%';
		downActionsDiv.style.padding = '15px';
		downActionsDiv.style.background = 'linear-gradient(transparent, black)';
		downActionsDiv.style.position = 'absolute';
		downActionsDiv.style.zIndex = 1;
		downActionsDiv.style.bottom = 0;
		innerDiv.appendChild(downActionsDiv);

		let likeItem = document.createElement('div');
		likeItem.classList.add('valign-wrapper');

		downActionsDiv.appendChild(likeItem);

		let notLike = document.createElement('div');
		let setLike = document.createElement('div');

		likeItem.appendChild(notLike);
		likeItem.appendChild(setLike);

		notLike.innerHTML = unt.Icon.LIKE;
		setLike.innerHTML = unt.Icon.LIKE_SET;

		notLike.getElementsByTagName('path')[1].style = 'stroke: white !important';
		setLike.getElementsByTagName('svg')[0].style.display = '';

		notLike.style.display = '';
		setLike.style.display = 'none';
		
		photos.getByCredentials(img.getAttribute('attachment')).then(function (attachmentObject) {
			if (attachmentObject.photo && attachmentObject.photo.meta.likes.liked_by_me) {
				notLike.style.display = 'none';
				setLike.style.display = '';
			} else {
				notLike.style.display = '';
				setLike.style.display = 'none';
			}

			let likesCountDiv = document.createElement('div');
			likeItem.appendChild(likesCountDiv);
			likesCountDiv.innerText = attachmentObject.photo.meta.likes.count > 0 ? pages.parsers.niceString(attachmentObject.photo.meta.likes.count) : '';

			likesCountDiv.style.marginLeft = '10px';
			likesCountDiv.style.marginBottom = '7px';
			likesCountDiv.style.color = 'white';

			notLike.onclick = 
			setLike.onclick = 
			function (event) {
				return photos.like(img.getAttribute('attachment')).then(function (result) {
					likesCountDiv.innerText = result.new_count > 0 ? pages.parsers.niceString(result.new_count) : '';

					if (result.state === 0) {
						notLike.style.display = '';
						setLike.style.display = 'none';
					} else {
						notLike.style.display = 'none';
						setLike.style.display = '';
					}
				}).catch(function (error) {

				});
			}
		}).catch(function (err) {
			notLike.style.display = 'none';
			setLike.style.display = 'none';
		})

		return settings.users.get(Number(attachmentInfo[0])).then(function (user) {
			ownerPhoto.src = user.photo_url;
			credentialsData.innerText = user.account_type === "bot" ? user.name : (user.first_name + " " + user.last_name);
		}).catch(function (err) {
			loader.remove();

			return ownerInfoDiv.remove();
		});
	},
	like: function (photoCredentials) {
		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'like_photo');
			return ui.Request({
				data: data,
				url: '/' + photoCredentials,
				method: 'POST',
				success: function (response) {
					response = JSON.parse(response);

					if (response.error) return reject('Unable to change like state of photo');
					return resolve(response.response);
				}
			});
		});
	},
	getByCredentials: function (attachmentCredentials) {
		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'get_attachment_info');
			data.append('credentials', attachmentCredentials);

			return ui.Request({
				url: '/flex',
				data: data,
				method: 'POST',
				success: function (response) {
					response = JSON.parse(response);
					if (response.error) return reject(new TypeError('Unable to find or fetch attachment object'));

					return resolve(response.attachment);
				}
			});
		});
	}
};

const themes = {
	data: {
		export: function (owner_id, theme_id) {
			return new Promise(function (resolve, reject) {
				if (owner_id !== settings.users.current.user_id) return reject(new TypeError('You can not export this theme.'));

				let tmpElement = document.createElement('a');
				tmpElement.style.display = 'none';

				let thUrl = window.location.host.match(/localhost/) ? 'http://themes.localhost' : 'https://themes.yunnet.ru';

				tmpElement.href = thUrl + '/theme' + Number(owner_id) + '_' + Number(theme_id) + '?mode=export';
				tmpElement.download = 'theme' + Number(owner_id) + '_' + Number(theme_id) + '.uth';

				document.body.appendChild(tmpElement);
				tmpElement.onclick = function () {
					return resolve(true);
				};

				setTimeout(function () {
					tmpElement.click();

					return tmpElement.remove();
				}, 2000);
			});
		},
		code: {
			update: function (owner_id, theme_id, codeType = 'css', newCode = '') {
				if (codeType !== 'js_1') codeType = 'css';

				return new Promise(function (resolve, reject) {
					let data = new FormData();
					
					data.append('action', 'update_theme_code');
					data.append('theme_id', Number(theme_id) || 0);
					data.append('owner_id', Number(owner_id) || settings.users.current.user_id);
					data.append('code_type', String(codeType));
					data.append('new_code', String(newCode).trim());

					return ui.Request({
						url: '/themes',
						data: data,
						method: 'POST',
						success: function (response) {
							response = JSON.parse(response);
							if (response.error) {
								let error = new Error('Unable to update theme');
								if (response.message)
									error.errorMessage = response.message;

								return reject(error);
							}

							return resolve(response.success);
						}
					});
				});
			}
		},
		getCode: function (owner_id, theme_id, mode = 'css') {
			if (mode !== 'js_1') mode = 'css';

			return new Promise(function (resolve, reject) {
				let x = _xmlHttpGet();

				let thUrl = window.location.host.match(/localhost/) ? 'http://themes.localhost' : 'https://themes.yunnet.ru';

				x.open('GET', thUrl + '/theme' + Number(owner_id) + '_' + Number(theme_id) + '?mode=' + mode);
				x.withCredentials = true;
				x.onreadystatechange = function () {
					if (x.readyState !== 4) return;

					let response = x.responseText;
					if (response === '[]') return reject(new Error('Theme code not found'));

					try {
						response = JSON.parse(response);
						
						if (response.error) return reject(new Error('Unable to receive code'));

						return reject(new Error('Fetch failed'))
					} catch (err) {
						return resolve(response);
					}
				}

				x.withCredentials = true;
				return x.send();
			});
		},
		update: function (owner_id, theme_id, newTitle = '', newDescription = '', newPrivateMode = 0) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'update_theme_info');
				data.append('theme_id', Number(theme_id) || 0);
				data.append('owner_id', Number(owner_id) || settings.users.current.user_id);

				if (!newTitle.isEmpty() && newTitle.length <= 64)
					data.append('new_title', newTitle);
				if (!newDescription.isEmpty() && newDescription.length <= 128)
					data.append('new_description', newDescription);

				data.append('private_mode', Number(Boolean(Number(newPrivateMode))));
				return ui.Request({
					data: data,
					url: '/themes',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) return reject(new TypeError('Unable to update theme!'));

						return resolve(response.success);
					}
				});
			});
		}
	},
	create: function (title, description, privateTheme = 0) {
		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'create_theme');
			data.append('theme_title', String(title));
			data.append('theme_description', String(description));
			data.append('is_private', Number(privateTheme));

			return ui.Request({
				url: '/themes',
				data: data,
				method: 'POST',
				success: function (response) {
					response = JSON.parse(response);
					if (response.error) return reject(new TypeError('Unable to create the theme'));

					return resolve(response);
				}
			});
		});
	},
	delete: function (owner_id, theme_id) {
		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'delete_theme');
			data.append('owner_id', Number(owner_id));
			data.append('theme_id', Number(theme_id));

			return ui.Request({
				data: data,
				method: 'POST',
				url: '/themes',
				success: function (response) {
					response = JSON.parse(response);
					if (response.error) return reject(new TypeError('Unable to delete the theme'));

					return resolve(response.success);
				}
			});
		});
	},
	setup: function (themeCredentials = null) {
		return new Promise(function (resolve, reject) {
			let element = document.getElementById('themeCSS');
			if (element)
				element.remove();

			if (themeCredentials) {
				let x = _xmlHttpGet();
				x.withCredentials = true;
				x.onreadystatechange = function () {
				    if (x.readyState !== 4) return;

				    try {
				    	let themeData = JSON.parse(x.responseText);
				    	if (!themeData || themeData.error) return null;

				    	let defaultUrl = themeData.data.url;
				    	if (defaultUrl) {
				    		if (themeData.params.has_css) {
				    			let element = document.createElement('link');

					    		element.id = 'themeCSS';
					    		element.type = 'text/css_1';
					    		element.rel = 'stylesheet';
					    		element.media = 'screen,projection';
					    		element.href = defaultUrl + '?mode=css_1';

					    		element.onload = function () {
					    			return resolve();
					    		}

					    		document.head.appendChild(element);
				    		}
				    	}
				    } catch (e) {
				    	return resolve();
				    }
				}

				let thUrl = window.location.host.match(/localhost/) ? 'http://themes.localhost/' : 'https://themes.yunnet.ru/';

				x.open('GET', thUrl + themeCredentials);
				return x.send();
			} else {
				return resolve();
			}
		});
	},
	apply: function (credentials, saveOnAccount = true) {
		return new Promise(function () {
			let data = new FormData();

			let thUrl = window.location.host.match(/localhost/) ? 'http://themes.localhost' : 'https://themes.yunnet.ru';

			if (!saveOnAccount && credentials === 'theme1_1') {
				return; // return realtime.handler({event: 'interface_event', data: {action: 'theme_changed', theme: {
				// 	owner_id: 1,
				// 	id: 1,
				// 	data: {
				// 		title: "Dark theme",
				// 		description: "yunNet dark theme",
				// 		url: thUrl + "/theme1_1"
				// 	},
				// 	params: {
				// 		has_api: false,
				// 		has_css: true,
				// 		has_js: true
				// 	},
				// 	settings: {
				// 		is_default: 1,
				// 		is_private: 0
				// 	}
				// }}, last_event_id: realtime.lastEventId}, realtime.handler);
			} else if (!saveOnAccount && credentials === null) {
				return window.location.reload();
			}

			if (credentials) {
				data.append('action', 'apply_theme');
				data.append('credentials', credentials);
			} else {
				data.append('action', 'reset_theme');
			}

			return ui.Request({
				url: '/themes',
				method: 'POST',
				data: data,
				success: function (response) {
					ui.go(window.location.href, true, true, {});

					return settings.get().theming.current_theme = credentials;
				}
			});
		});
	},
	get: function (page = 1, count = 30) {
		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'get_themes');
			data.append('offset', (page * count) - count);
			data.append('count', Number(count) || 30);
			return ui.Request({
				url: '/themes',
				method: 'POST',
				data: data,
				success: function (response) {
					response = JSON.parse(response);
					if (response.error) return reject(new TypeError('Themes fetch failed'));

					return resolve(response.response);
				}
			});
		});
	},
	toggleJSState: function () {
		return new Promise(function (resolve, reject) {
			let data = new FormData();

			data.append('action', 'toggle_js_state');
			return ui.Request({
				data: data,
				method: 'POST',
				url: '/settings',
				success: function (response) {
					response = JSON.parse(response);

					if (response.error) return reject(new TypeError('JS state changing error'));
					return resolve(response.state);
				}
			});
		});	
	},
	menu: {
		updateItems: function (newItems = [1, 2, 3, 4, 5, 6]) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'update_menu_items');
				data.append('items', newItems.join());
				return ui.Request({
					data: data,
					url: '/settings',
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);

						if (response.error) return reject(new TypeError('Unable to update menu items'));
						return resolve(Boolean(response.success));
					}
				});
			});
		}
	},
	codeEditor: {
		build: function () {
			let oldTitle = document.title;

			let element = document.createElement('div');
			element.classList.add('code-editor');

			element.style.backgroundColor = '#282923';
			element.style.position = 'fixed';
			element.style.zIndex = 1024;
			element.style.inset = '0px';
			element.style.width = element.style.height = '100%';

			let mainContainer = document.createElement('div');
			element.appendChild(mainContainer);
			mainContainer.style.width = mainContainer.style.height = '100%';

			let upPanel = document.createElement('div');
			upPanel.style.width = '100%';
			upPanel.style.padding = '15px';
			upPanel.style.background = '#212121';
			upPanel.style.position = 'absolute';
			upPanel.style.zIndex = 1;
			upPanel.style.top = 0;

			let downPanel = document.createElement('div');
			downPanel.style.width = '100%';
			downPanel.style.padding = '15px';
			downPanel.style.background = '#212121';
			downPanel.style.position = 'absolute';
			downPanel.style.zIndex = 1;
			downPanel.style.bottom = 0;

			let contentDiv = document.createElement('div');

			mainContainer.appendChild(upPanel);
			mainContainer.appendChild(contentDiv);
			mainContainer.appendChild(downPanel);

			let upContainer = document.createElement('div');
			upContainer.classList.add('valign-wrapper');
			upPanel.appendChild(upContainer);

			let downContainer = document.createElement('div');
			downContainer.classList.add('valign-wrapper');
			downContainer.style.height = '25px';
			downPanel.appendChild(downContainer);

			let closeItem = document.createElement('a');
			closeItem.href = '#';

			upContainer.appendChild(closeItem);

			closeItem.innerHTML = unt.Icon.CLOSE;
			closeItem.getElementsByTagName('svg')[0].style.fill = 'white';
			closeItem.onclick = function (event) {
				event.preventDefault();

				return element.close();
			}

			let titleDiv = document.createElement('div');
			upContainer.appendChild(titleDiv);

			titleDiv.style.color = 'white';
			titleDiv.style.marginBottom = '6px';
			titleDiv.style.marginLeft = '15px';

			element.getCurrentCode = function () {
				return innerCode.innerText;
			}

			element.getUpPanel = function () {
				return upContainer;
			}
			element.getDownPanel = function () {
				return downContainer;
			}

			upContainer.addButton = function (icon, callback = null, inRight = -1) {
				let buttonDiv = document.createElement('div');

				let button = document.createElement('a');
				buttonDiv.style.cursor = 'pointer';

				upContainer.appendChild(buttonDiv);
				buttonDiv.appendChild(button);

				button.innerHTML = icon;
				button.getElementsByTagName('svg')[0].style.fill = 'white';

				buttonDiv.style.position = 'absolute';
				buttonDiv.style.padding = '15px';

				if (inRight >= 0)
					buttonDiv.style.right = inRight + 'px';

				button.onclick = function (event) {
					if (callback) return callback(event, button);
				}

				let loader = pages.elements.getLoader();
				buttonDiv.appendChild(loader);
				loader.style.display = 'none';

				loader.setArea(22);
				button.setLoading = function (loading) {
					if (loading) {
						button.style.display = 'none';
						loader.style.display = '';
					} else {
						button.style.display = '';
						loader.style.display = 'none';
					}

					return button;
				}

				return element;
			}
			downContainer.addButton = function (icon, callback = null, inRight = -1) {
				let buttonDiv = document.createElement('div');

				let button = document.createElement('a');
				buttonDiv.style.cursor = 'pointer';

				downContainer.appendChild(buttonDiv);
				buttonDiv.appendChild(button);

				button.innerHTML = icon;
				button.getElementsByTagName('svg')[0].style.fill = 'white';

				if (inRight > 0)
					buttonDiv.style.position = 'absolute';

				buttonDiv.style.paddingRight = '15px';

				if (inRight >= 0)
					buttonDiv.style.right = inRight + 'px';

				button.onclick = function (event) {
					if (callback) return callback(event, button);
				}

				let loader = pages.elements.getLoader();
				buttonDiv.appendChild(loader);
				loader.style.display = 'none';

				loader.setArea(22);
				button.setLoading = function (loading) {
					if (loading) {
						button.style.display = 'none';
						loader.style.display = '';
					} else {
						button.style.display = '';
						loader.style.display = 'none';
					}

					return button;
				}

				return element;
			}

			element.setTitle = function (title) {
				if (!title) return false;
				if (typeof title !== "string") return false;

				if (title.isEmpty() || title.length > 128) return false;
				titleDiv.innerText = String(title);

				document.title = title;

				return element;
			}

			element.close = function () {
				element.remove();
				document.title = oldTitle;

				return element;
			}

			contentDiv.classList.add('full_section');

			contentDiv.style.marginTop = '59px';
			contentDiv.style.width = '100%';
			contentDiv.style.height = 'calc(100% - 115px)';
			contentDiv.style.overflow = 'auto';

			let inPre = document.createElement('pre');
			contentDiv.appendChild(inPre);

			let innerCode = document.createElement('code');
			innerCode.setAttribute('contenteditable', 'true');

			let inDiv = document.createElement('div');
			innerCode.appendChild(inDiv);

			inDiv.innerText = '\r';

			inPre.appendChild(innerCode);

			innerCode.onkeydown = function (event) {
				let keyCode = event.keyCode || event.which;
				if (innerCode.getElementsByTagName('div').length === 0) {
					innerCode.innerHTML = '';

					let inDiv = document.createElement('div');
					innerCode.appendChild(inDiv);

					inDiv.innerText = '\r';
				}

				if (keyCode === 8 && innerCode.innerText.length <= 1) {
					event.preventDefault();

					if (!innerCode.innerText.isEmpty() || innerCode.innerText === ' ' || innerCode.innerText === '\n' || innerCode.innerText === '') {
						innerCode.innerHTML = '';

						let inDiv = document.createElement('div');
						innerCode.appendChild(inDiv);

						inDiv.innerText = '\r';
					}
				}

				if (keyCode === 9) {
					event.preventDefault();

					document.execCommand('insertText', false, ' '.repeat(4));
				}
			}

			element.setCode = function (code) {
				innerCode.innerHTML = '';

				let array = String(code).split('\n')

				array.forEach(function (row) {
					let inDiv = document.createElement('div');
					innerCode.appendChild(inDiv);

					inDiv.innerText = row;
				})

				return element;
			}

			innerCode.style.color = 'white';
			innerCode.style.outlineWidth = 0;

			return element;
		}
	}
};