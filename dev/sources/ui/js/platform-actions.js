unt.actions.wall = new Object({
	currentId: null,
	postsSaved: {},
	createManager: function (postObject = null) {
		return new Promise(function (resolve, reject) {
			let win = unt.components.windows.createImportantWindow({
				title: unt.settings.lang.getValue('write_a_post')
			});

			let menu = win.getMenu();

			let inputContainer = document.createElement('div');
			inputContainer.style.padding = '0 20px';

			menu.appendChild(inputContainer);

			let postInputDiv = document.createElement("div");
			postInputDiv.classList.add("valign-wrapper");
			inputContainer.appendChild(postInputDiv);

			let authorImage = document.createElement("img");
			authorImage.alt = "";
			authorImage.src = unt.settings.users.current.photo_url;
			authorImage.width = 32;
			authorImage.height = 32;
			authorImage.classList.add("circle");
			postInputDiv.appendChild(authorImage);

			let postTextInput = document.createElement("div");
			postTextInput.style = "width: 100%; margin-left: 15px";
			postTextInput.classList.add("input-field");

			let postTextInputTextArea = document.createElement("textarea");
			postTextInputTextArea.classList.add("materialize-textarea");
			postTextInputTextArea.type = "text";
			postTextInputTextArea.style = "height: 43px; max-height: 250px";
			postTextInputTextArea.id = 'write_a_post';
			let postTextInputTextAreaLabel = document.createElement("label");
			postTextInputTextAreaLabel.setAttribute("for", postTextInputTextArea.id);
			postTextInputTextAreaLabel.innerText = unt.settings.lang.getValue("write_a_post");
			postTextInput.appendChild(postTextInputTextArea);
			postTextInput.appendChild(postTextInputTextAreaLabel);
			postInputDiv.appendChild(postTextInput);

			let attachmentsDiv = document.createElement("div");
			attachmentsDiv.classList.add("valign-wrapper");
			attachmentsDiv.id = "attachments";
			inputContainer.appendChild(attachmentsDiv);

			let toolsDiv = document.createElement("div");
			toolsDiv.id = "tools";
			toolsDiv.classList.add("valign-wrapper");
			inputContainer.appendChild(toolsDiv);
			toolsDiv.style.paddingBottom = '10px';

			let publishSettingsItem = document.createElement("a");
			publishSettingsItem.id = "publish_settings";
			publishSettingsItem.style = 'cursor: pointer';
			publishSettingsItem.innerHTML = unt.icons.settings;
			toolsDiv.appendChild(publishSettingsItem);

			let attachFileItem = document.createElement("a");
			attachFileItem.id = "attach_file";
			attachFileItem.style = "margin-left: 10px; cursor: pointer";
			attachFileItem.innerHTML = unt.icons.attachment;
			toolsDiv.appendChild(attachFileItem);

			let previewPostItem = document.createElement("a");
			previewPostItem.id = "preview_post";
			previewPostItem.style = "margin-left: 10px; cursor: pointer";
			previewPostItem.innerHTML = unt.icons.picture;
			toolsDiv.appendChild(previewPostItem);
			previewPostItem.addEventListener('click', function () {
				let win = unt.components.windows.createWindow({
					title: unt.settings.lang.getValue('preview_post')
				});

				let menu = win.getMenu();

				win.getFooter().remove();

				let post = unt.components.wall.post({
					owner_id: unt.settings.users.current.user_id,
					time: Math.floor(new Date() / 1000),
					text: postTextInputTextArea.value,
					attachments: []
				}, false, true);

				menu.appendChild(post);

				return win.show();
			});

			return win.show();
		});
	},
	getPosts: function (wallId, offset = 0, count = 20) {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/' + (wallId > 0 ? ("id" + wallId) : ("bot" + (wallId * -1))),
				method: 'POST',
				data: (new POSTData()).append('action', 'get_posts').append('offset', Number(offset) || 0).append('count', Number(count) || 20).build(),
				success: function (result) {
					try {
						result = JSON.parse(result);
						if (result.error)
							return reject(new TypeError('Unable to fetch posts'));

						return resolve(result);
					} catch (e) {
						return reject(e);
					}
				},
				error: function (error) {
					return reject(error);
				}
			});
		});
	},
	getPostById: function (wallId, postId) {
		return unt.tools.Request({
			url: '/wall' + Number(wallId) + '_' + Number(postId),
			method: 'POST',
			data: (new POSTData()).append('action', 'get_info').build(),
			success: function (result) {
				try {
					result = JSON.parse(result);
					if (result.error)
						return reject(new TypeError('Unable to fetch post'));

					return resolve(result);
				} catch (e) {
					return reject(e);
				}
			},
			error: function (error) {
				return reject(error);
			}
		});
	},
	getNews: function (offset = 0, count = 30) {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_posts').append('offset', Number(offset) || 0).append('count', Number(count) || 30).build(),
				success: function (result) {
					try {
						result = JSON.parse(result);
						if (result.error)
							return reject(new TypeError('Unable to fetch news'));

						return resolve(result);
					} catch (e) {
						return reject(e);
					}
				},
				error: function (error) {
					return reject(error);
				}
			});
		});
	}
});

unt.actions.friends = new Object({
	get: function (user_id) {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/flex',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_friends').append('section', 'friends').append('user_id', user_id).build(),
				success: function (response) {
					try {
						response = JSON.parse(response);
						if (response.error)
							throw new Error();

						return resolve(response);
					} catch (e) {
						reject(e);
					}
				},
				error: function () {
					reject();
				}
			});
		});
	},
	getSubscribers: function (user_id) {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/flex',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_friends').append('section', 'subscribers').append('user_id', user_id).build(),
				success: function (response) {
					try {
						response = JSON.parse(response);
						if (response.error)
							throw new Error();

						return resolve(response);
					} catch (e) {
						reject(e);
					}
				},
				error: function () {
					reject();
				}
			});
		});
	},
	getOutcoming: function () {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/flex',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_friends').append('section', 'outcoming').append('user_id', unt.settings.users.current.user_id).build(),
				success: function (response) {
					try {
						response = JSON.parse(response);
						if (response.error)
							throw new Error();

						return resolve(response);
					} catch (e) {
						reject(e);
					}
				},
				error: function () {
					reject();
				}
			});
		});
	}
});