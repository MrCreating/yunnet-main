const management = {
	actions: {
		settings: null,
		getSettings: function () {
			return new Promise(function (resolve, reject) {
				if (management.actions.settings !== null)
					return resolve(management.actions.settings);

				let data = new FormData();

				data.append('action', 'get_project_settings');
				return ui.Request({
					url: '/dev',
					method: 'POST',
					data: data,
					success: function (response) {
						try {
	                        response = JSON.parse(response);
	                        if (response.error)
	                        	return reject(new Error('Settings fetch failed'));

	                        return resolve(management.actions.settings = response);
	                    } catch (e) {
	                        return reject(new Error('Settings fetch failed'));
	                    }
					}
				});
			});
		},
		toggleProjectClose: function () {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'toggle_project_close');
				return ui.Request({
					url: '/dev',
					method: 'POST',
					data: data,
					success: function (response) {
						try {
	                        response = JSON.parse(response);
	                        if (response.error)
	                        	return reject(new Error('Toggle failed'));

	                        return resolve(management.actions.settings.closed_project = response.response);
	                    } catch (e) {
	                        return reject(new Error('Toggle failed'));
	                    }
					}
				});
			});
		},
		toggleRegisterClose: function () {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'toggle_register_close');
				return ui.Request({
					url: '/dev',
					method: 'POST',
					data: data,
					success: function (response) {
						try {
	                        response = JSON.parse(response);
	                        if (response.error)
	                        	return reject(new Error('Toggle failed'));

	                        return resolve(management.actions.settings.closed_register = response.response);
	                    } catch (e) {
	                        return reject(new Error('Toggle failed'));
	                    }
					}
				});
			});
		}
	},
	users: {
		delete: function (user_id) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'delete_user');
				data.append('user_id', Number(user_id) || 0);

				return ui.Request({
					url: '/dev',
					method: 'POST',
					data: data,
					success: function (response) {
						try {
	                        response = JSON.parse(response);
	                        if (response.error)
	                        	return reject(new Error('Deletion failed'));

	                        return resolve(Boolean(response.success));
	                    } catch (e) {
	                        return reject(new Error('Deletion failed'));
	                    }
					}
				});
			});
		},
		actions: {
			edit: function (user_id, dataObject) {
				return new Promise(function (resolve, reject) {
	                let allowedKeys = ['first_name', 'last_name', 'screen_name', 'photo'];

	                let data = new FormData();

	                data.append('action', 'edit_user');
	                data.append('user_id', Number(user_id) || 0);

	                for (let key in dataObject) {
	                    if (allowedKeys.indexOf(key) !== -1) data.append(key, dataObject[key] || '');
	                }

	                return ui.Request({
	                    url: '/dev',
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
			banUser: function (user_id) {
				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'toggle_ban_state');
					data.append('user_id', Number(user_id) || 0);
					return ui.Request({
						url: '/dev',
						method: 'POST',
						data: data,
						success: function (response) {
							try {
								response = JSON.parse(response);
								if (response.error)
									return reject(new TypeError('Unable to change ban mode.'));

								return resolve(response.state);
							} catch (e) {
								return reject(new TypeError('Unable to change ban mode.'));
							}
						}
					});
				})
			},
			toggleVerification: function (user_id) {
				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'toggle_verification_state');
					data.append('user_id', Number(user_id) || 0);
					return ui.Request({
						url: '/dev',
						method: 'POST',
						data: data,
						success: function (response) {
							try {
								response = JSON.parse(response);
								if (response.error)
									return reject(new TypeError('Unable to change verification mode.'));

								return resolve(response.state);
							} catch (e) {
								return reject(new TypeError('Unable to change verification mode.'));
							}
						}
					});
				})
			},
			toggleOnlineShow: function (user_id) {
				return new Promise(function (resolve, reject) {
					let data = new FormData();

					data.append('action', 'toggle_online_show_state');
					data.append('user_id', Number(user_id) || 0);
					return ui.Request({
						url: '/dev',
						method: 'POST',
						data: data,
						success: function (response) {
							try {
								response = JSON.parse(response);
								if (response.error)
									return reject(new TypeError('Unable to change online show mode.'));

								return resolve(response.state);
							} catch (e) {
								return reject(new TypeError('Unable to change online show mode.'));
							}
						}
					});
				})
			}
		}
	},
	show: function (internalData) {
		if (!settings.users.current) return pages.unauth.authPage();
		if (settings.users.current.is_banned) return pages.unauth.banned();
		if (settings.users.current.user_level < 1) return pages.news();

		document.title = 'yunNet. - ' + settings.lang.getValue("management");
		ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("management") : null;

		let menuBody = pages.elements.menuBody().clear();
		let rightMenu = pages.elements.buildRightMenu();

		let url = window.location.host.match(/localhost/) ? 'http://localhost' : 'https://yunnet.ru';

		let mainItem = rightMenu.addItem(settings.lang.getValue('main'), function () {
			return ui.go(url + '/dev');
		});

		let bugsItem = rightMenu.addItem(settings.lang.getValue('bug_tracker'), function () {
			return ui.go(url + '/dev?section=bugs');
		})

		let filesItem;
		if (settings.users.current.user_level > 3) {
			filesItem = rightMenu.addItem(settings.lang.getValue('files'), function () {
				return ui.go(url + '/dev?section=files');
			});
		}

		let section = (new URLParser(window.location.href)).parse().section || 'main';
		let sections = ['main', 'files', 'bugs'];
		if (sections.indexOf(section) === -1) section = 'main';

		if (section === sections[0]) {
			menuBody.appendChild(pages.elements.createButton(unt.Icon.SETTINGS, settings.lang.getValue('your_access_level') + ': ' + '<b>' + settings.users.current.user_level + '</b>', new Function()));	

			let userInputCard = document.createElement('div');
			menuBody.appendChild(userInputCard);

			userInputCard.classList.add('card');
			userInputCard.classList.add('full_section');

			let dataDiv = document.createElement('div');
			dataDiv.classList.add('valign-wrapper');
			userInputCard.appendChild(dataDiv);

			let linkInput = pages.elements.createInputField(settings.lang.getValue('short_link_or_id'), false);
			linkInput.style.width = '100%';
			dataDiv.appendChild(linkInput);

			let continueButton = pages.elements.createFAB(unt.Icon.ARROW_FWD, function () {
				if (linkInput.getValue().isEmpty()) return;
				let doneId = '';

				let currentUserRequested = linkInput.getValue().split("yunnet.ru/");
				if (!currentUserRequested[1])
					doneId = currentUserRequested[0];
				else
					doneId = currentUserRequested[1];
				if (doneId.isEmpty()) return;

				continueButton.style.display = 'none';
				loader.style.display = '';

				return settings.users.resolveScreenName(doneId).then(function (user) {
					menuBody.innerHTML = '';

					menuBody.appendChild(pages.elements.backArrowButton(settings.lang.getValue('back'), function () {
						return ui.go(window.location.href, false, true);
					}));

					let profileBody_card = document.createElement("div");
					profileBody_card.classList = ["card full_section"];
					menuBody.appendChild(profileBody_card);

					let userInfoDiv = document.createElement("div");
					userInfoDiv.classList = ['valign-wrapper'];
					profileBody_card.appendChild(userInfoDiv);

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

					if (user.photo_object)
						img.setAttribute('attachment', 'photo' + user.photo_object.owner_id + '_' + user.photo_object.id + '_' + user.photo_object.access_key);
					else
						img.setAttribute('attachment', 'photo' + ui.userVisited +  '_1_all');

					img.onclick = function () {
						return photos.show(img, user.photo_object);
					}

					img.src = user.photo_url;

					let onlineString = pages.parsers.getOnlineState(user);
					innerB.innerText = (user.account_type === "user" ? user.first_name + " " + user.last_name : user.name);

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

					let itemsEdit = [];

					if (settings.users.current.user_level >= 2) {
						itemsEdit.push([
							unt.Icon.EDIT, 
							settings.lang.getValue('user_edit'), 
							function () {
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

								headerText.innerText = settings.lang.getValue('user_edit');

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

								let userEditItems = document.createElement('div');
								startWindow.appendChild(userEditItems);

								let userPhoto = document.createElement('img');
								userPhoto.addEventListener('click', function () {
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
																return management.users.actions.edit((user.user_id || (user.bot_id * -1)), {
																	photo: attachmentCredentials
																}).then(function (response) {
																	uploader.setLoading(false);
																	
																	userPhoto.src = response.photo.url.main;

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

										return management.users.actions.edit((user.user_id || (user.bot_id * -1)), {
											photo: ''
										}).then(function (response) {
											item.setLoading(false);
											
											userPhoto.src = 'https://dev.yunnet.ru/images/default.png';

											return uploader.close();
										}).catch(function (error) {
											uploader.setLoading(false);

											return unt.toast({html: (settings.lang.getValue("upload_error"))});
										})
									});

									return uploader.open();
								});

								userPhoto.classList.add('circle');

								userPhoto.height = userPhoto.width = 72;
								userPhoto.src = user.photo_url
								userPhoto.style.marginRight = '15px';

								userEditItems.appendChild(userPhoto);

								let editForm = document.createElement('div');
								userEditItems.classList.add('valign-wrapper');

								userEditItems.appendChild(editForm);
								editForm.style.width = '100%';

								userEditItems.style.padding = '20px';

								let firstNameEdit;
								let lastNameEdit;
								let nameEdit;

								if (user.account_type === 'user') {
									firstNameEdit = pages.elements.createInputField(settings.lang.getValue('first_name'), true).setText(user.first_name).maxLength(64);
									lastNameEdit = pages.elements.createInputField(settings.lang.getValue('last_name'), true).setText(user.last_name).maxLength(64);

									editForm.appendChild(firstNameEdit);
									editForm.appendChild(lastNameEdit);
								} else {
									nameEdit = pages.elements.createInputField(settings.lang.getValue('bot_name'), true).setText(user.name).maxLength(64);

									editForm.appendChild(nameEdit);
								}
								
								let saveMainLoader = pages.elements.getLoader();
								saveMainLoader.classList.remove('center');
								saveMainLoader.style.textAlign = 'end';
								saveMainLoader.style.display = 'none';

								editForm.appendChild(saveMainLoader);

								let saveMainGroup = pages.elements.createFAB(unt.Icon.SAVE, function () {
									saveMainLoader.style.display = '';
									saveMainGroup.style.display = 'none';

									if (firstNameEdit) firstNameEdit.getInput().setAttribute('disabled', true);
									if (lastNameEdit) lastNameEdit.getInput().setAttribute('disabled', true);
									if (nameEdit) nameEdit.getInput().setAttribute('disabled', true);

									let object = {};

									if (firstNameEdit)
										object.first_name = firstNameEdit.getValue();
									if (lastNameEdit)
										object.last_name = lastNameEdit.getValue();
									if (nameEdit)
										object.name = nameEdit.getValue();

									return management.users.actions.edit((user.user_id || (user.bot_id * -1)), object).then(function (response) {
										saveMainLoader.style.display = 'none';
										saveMainGroup.style.display = '';

										if (firstNameEdit) firstNameEdit.getInput().removeAttribute('disabled');
										if (lastNameEdit) lastNameEdit.getInput().removeAttribute('disabled');
										if (nameEdit) nameEdit.getInput().removeAttribute('disabled');

										return unt.toast({html: settings.lang.getValue('saved')});
									}).catch(function (err) {
										saveMainLoader.style.display = 'none';
										saveMainGroup.style.display = '';

										if (firstNameEdit) firstNameEdit.getInput().removeAttribute('disabled');
										if (lastNameEdit) lastNameEdit.getInput().removeAttribute('disabled');
										if (nameEdit) nameEdit.getInput().removeAttribute('disabled');

										let string = settings.lang.getValue('upload_error');
										if (err.errorMessage)
											string = err.errorMessage;

										return unt.toast({html: string});
									})
								});
								saveMainGroup.style.textAlign = 'end';
								saveMainGroup.classList.remove('fixed-action-btn');

								editForm.appendChild(saveMainGroup);

								return startWindow.open();
							}
						]);
					}

					itemsEdit.push([
						unt.Icon.SETTINGS,
						settings.lang.getValue('user_settings'),
						function () {
							let currentUserId = user.user_id || (user.bot_id * -1);						

							if (!user.user_level)
								user.user_level = 0;

							let settingsWindow = pages.elements.createWindow();
							let contentInfo = settingsWindow.getContent();

							let accountGroupFunctions = [];
							let settingsGroupFunctions = [];

							if (((settings.users.current.user_level > user.user_level && settings.users.current.user_level >= 3) || (settings.users.current.user_level === 4)) && settings.users.current.user_id !== user.user_id)
								accountGroupFunctions.push([unt.Icon.DEV, settings.lang.getValue('banned'), function (newState) {
									return management.users.actions.banUser(currentUserId).then(function (newState) {
										user.is_banned = Number(newState);

										accountGroup.selectItem(0, newState);
									}).catch(function (err) {
										accountGroup.selectItem(0, Boolean(user.is_banned));

										return unt.toast({html: settings.lang.getValue('upload_error')})
									})
								}, user.is_banned]);
							if ((settings.users.current.user_level > user.user_level && settings.users.current.user_level >= 1) || (settings.users.current.user_level === 4))
								accountGroupFunctions.push([unt.Icon.PALETTE_ANIM, settings.lang.getValue('verified'), function () {
									return management.users.actions.toggleVerification(currentUserId).then(function (newState) {
										user.is_verified = Number(newState);

										accountGroup.selectItem(1, newState);
									}).catch(function (err) {
										accountGroup.selectItem(1, Boolean(user.is_verified));

										return unt.toast({html: settings.lang.getValue('upload_error')})
									})
								}, user.is_verified]);

							if (user.online) {
								if (user.online && ((settings.users.current.user_level > user.user_level && settings.users.current.user_level >= 4) || (settings.users.current.user_level === 4)))
									settingsGroupFunctions.push([unt.Icon.ACCOUNT, settings.lang.getValue('hidden_online'), function (newState) {
										return management.users.actions.toggleOnlineShow(currentUserId).then(function (newState) {
											user.online.hidden_online = true;

											settingsGroup.selectItem(0, newState);
										}).catch(function (err) {
											settingsGroup.selectItem(0, Boolean(user.online.hidden_online));

											return unt.toast({html: settings.lang.getValue('upload_error')})
										})
									}, user.online.hidden_online]);
							}

							let accountGroup = pages.elements.createSwitchButton(accountGroupFunctions);
							let settingsGroup = pages.elements.createSwitchButton(settingsGroupFunctions);

							if (user.is_banned)
								accountGroup.selectItem(0, true);
							if (user.is_verified)
								accountGroup.selectItem(1, true);
							if (user.online && user.online.hidden_online)
								settingsGroup.selectItem(0, true);

							if (accountGroupFunctions.length > 0)
								contentInfo.appendChild(accountGroup);
							if (settingsGroupFunctions.length > 0)
								contentInfo.appendChild(settingsGroup);

							settingsWindow.getFooter().remove();
						}
					]);

					menuBody.appendChild(pages.elements.createButton(null, null, null, 'black', itemsEdit));

					if (settings.users.current.user_level > 3 && (user.user_level || 0) < settings.users.current.user_level) {
						menuBody.appendChild(pages.elements.createButton('', settings.lang.getValue('user_deletion'), function () {
							let doneString = (user.name ? user.name : (user.first_name + ' ' + user.last_name)) + ' ' + getRandomInt(100000, 999999);

							let confirmWindow = pages.elements.confirm('', settings.lang.getValue('user_deletion_attention').replace('%username%', doneString), function (result, instance, yesBtn, noBtn) {
								if (result) {
									if (deletionConfirm.getValue() !== doneString)
										return deletionConfirm.getInput().classList.add('wrong');
									else
										deletionConfirm.getInput().classList.remove('wrong');

									return management.users.delete(user.user_id || (user.bot_id * -1)).then(function (result) {
										instance.close();

										if (result)
											return ui.go();
									}).catch(function (err) {
										return unt.toast({html: settings.lang.getValue('upload_error')})
									})
								}

								return instance.close();
							}, false).getBody();

							confirmWindow.classList.add('unselectable');

							let deletionConfirm = pages.elements.createInputField(settings.lang.getValue('confirm_deletion'), false).setType('text');
							confirmWindow.appendChild(deletionConfirm);
						}, 'red'));
					}
				}).catch(function (err) {
					continueButton.style.display = '';
					loader.style.display = 'none';

					return unt.toast({html: settings.lang.getValue('upload_error')});
				})
			});
			continueButton.classList.remove('fixed-action-btn');
			continueButton.style.marginLeft = '20px';
			dataDiv.appendChild(continueButton);

			let loader = pages.elements.getLoader();
			loader.style.marginLeft = '20px';
			dataDiv.appendChild(loader);
			loader.style.display = 'none';

			if (settings.users.current && settings.users.current.user_level >= 4) {
				return management.actions.getSettings().then(function (response) {
					let projectSettings = pages.elements.createSwitchButton([
						[
							unt.Icon.DEV,
							settings.lang.getValue('closed_project'),
							function () {
								return management.actions.toggleProjectClose().catch(function (err) {
									return unt.toast({html: settings.lang.getValue('upload_error')});
								})
							}
						],
						[
							unt.Icon.ACCOUNT,
							settings.lang.getValue('closed_register'),
							function () {
								return management.actions.toggleRegisterClose().catch(function (err) {
									return unt.toast({html: settings.lang.getValue('upload_error')});
								})
							}
						]
					]);

					projectSettings.selectItem(0, response.closed_project);
					projectSettings.selectItem(1, response.closed_register);

					menuBody.appendChild(projectSettings);
				}).catch(function (err) {
					return;
				})
			}

			mainItem.select();
		}

		if (section === sections[1]) {
			if (settings.users.current.user_level < 3)
				return ui.go('https://' + window.location.host + '/dev');

			filesItem.select();
			let currentFilePath = '/';

			let currentPathInfo = pages.elements.backArrowButton(currentFilePath, function () {
				let filePathInfoArray = currentFilePath.split('/');
				filePathInfoArray.splice(filePathInfoArray.length - 1, 1);
				filePathInfoArray.splice(filePathInfoArray.length - 1, 1);

				currentFilePath = filePathInfoArray.join('/') + '/';
				if (currentFilePath.isEmpty())
					currentFilePath = '/';

				showFiles(currentFilePath);
			});
			menuBody.appendChild(currentPathInfo);

			let filesDiv = document.createElement('div');

			filesDiv.classList.add('collection');
			filesDiv.classList.add('card');

			filesDiv.style.display = 'none';
			menuBody.appendChild(filesDiv);

			function showFiles (path) {
				if (path === '/')
					currentPathInfo.style.display = 'none';
				else
					currentPathInfo.style.display = '';
				currentPathInfo.setText(currentFilePath);

				filesDiv.innerHTML = '';

				let loader = pages.elements.getLoader();
				filesDiv.appendChild(loader);

				let data = new FormData();

				data.append('action', 'show_project_files');
				data.append('user_id', settings.users.current.user_id);
				data.append('file_path', path);
				return ui.Request({
					url: '/dev',
					method: 'POST',
					data: data,
					success: function (response) {
						response = JSON.parse(response);
						if (response.error) {
							menuBody.appendChild(pages.elements.uploadError());
						}

						response.response.forEach(function (file) {
							let element = document.createElement('div');
							element.classList.add('card');
							element.classList.add('collection-item');
							element.classList.add('avatar');

							let fileIcon = document.createElement('i');
							fileIcon.classList.add('circle');
							element.appendChild(fileIcon);

							element.appendChild(fileIcon);
							filesDiv.appendChild(element);

							let fileName = document.createElement('span');
							fileName.classList.add('title');
							element.appendChild(fileName);

							let b = document.createElement('b');
							fileName.appendChild(b);
							b.innerText = file.name;

							element.onclick = function () {
								if (file.type !== 'directory') return;

								currentFilePath += file.name + '/';
								showFiles(currentFilePath);
							}

							let fileType = document.createElement('div');
							element.appendChild(fileType);
							fileType.innerText = file.type;

							element.style.cursor = 'pointer';
						});

						filesDiv.style.display = '';
						loader.style.display = 'none';
					}
				});
			}

			showFiles(currentFilePath);
		}

		if (section === sections[2]) {
			if (settings.users.current.user_level < 2)
				return ui.go('https://' + window.location.host + '/dev');

			bugsItem.select();

			let tabsItem = pages.elements.createTabs([
				[settings.lang.getValue('bugs_reported'), function () {
					
				}], [settings.lang.getValue('reports_list'), function () {
					
				}], [settings.lang.getValue('text_reports'), function () {
					
				}]
			]);

			menuBody.appendChild(tabsItem);
		}

		rightMenu.append();
	}
};