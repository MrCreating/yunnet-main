const codes = {
	callbacks: {
		Settings: function (selectedSection, menuBody, rightMenu, workedUrl, sections) {
			let currentSettings = settings.get();

			if (selectedSection === sections[0]) {
				menuBody.appendChild(pages.elements.createButton(null, null, null, 'black', [
					[
						unt.Icon.COOKIE,
						settings.lang.getValue('cookies') + ': <b>' + currentSettings.account.balance.cookies + '</b>',
						new Function()
					], [
						unt.Icon.BITE_COOKIE,
						settings.lang.getValue('half_cookies') + ': <b>' + currentSettings.account.balance.half_cookies + '</b>',
						new Function()
					], [
						unt.Icon.SETTINGS,
						settings.lang.getValue('cookies_manage'),
						function () { return ui.go(ui.getUrl() + '/cookies'); }
					]
				]));

				let langChangerDiv = pages.elements.createCollapsible([
					[unt.Icon.PLANET, settings.lang.getValue('languages')]
				]);
				menuBody.appendChild(langChangerDiv);

				let body = langChangerDiv.getBody(0);

				let collectionDiv = document.createElement('div');
				body.appendChild(collectionDiv);
				collectionDiv.classList.add('collection');

				let rus = document.createElement('a');
				rus.classList.add('collection-item');
				collectionDiv.appendChild(rus);
				rus.innerText = 'Русский';
				rus.onclick = function () { return settings.lang.change('ru') }

				let eng = document.createElement('a');
				eng.classList.add('collection-item');
				collectionDiv.appendChild(eng);
				eng.innerText = 'English';
				eng.onclick = function () { return settings.lang.change('en') }
			}
			if (selectedSection === sections[1]) {
				let notesGroup = pages.elements.createSwitchButton([
					[unt.Icon.NOTIFICATIONS, settings.lang.getValue('notifications'), function (event, item) {
						settings.data.toggleNotifications();
					}],
					[unt.Icon.SOUND, settings.lang.getValue('sound'), function (event, item) {
						settings.data.toggleSound();
					}]
				]).selectItem(0, currentSettings.push.notifications).selectItem(1, currentSettings.push.sound);
				menuBody.appendChild(notesGroup);
			}
			if (selectedSection === sections[2]) {
				let callPrivacyGroup = pages.elements.createCollapsible([
					[unt.Icon.MESSAGES, settings.lang.getValue('messages_privacy')],
					[unt.Icon.WALL, settings.lang.getValue('wall_privacy')],
					[unt.Icon.COMMENTS_INL, settings.lang.getValue('who_can_comment_my_posts')]
				]);
				menuBody.appendChild(callPrivacyGroup);

				callPrivacyGroup.getBody(0).appendChild(pages.elements.createSelector('can_write_messages', [
					[
						settings.lang.getValue('all'),
						function () { settings.data.setPrivacy(1, 0) }
					], [
						settings.lang.getValue('only_friends'),
						function () { settings.data.setPrivacy(1, 1) }
					], [
						settings.lang.getValue('nobody'),
						function () { settings.data.setPrivacy(1, 2) }
					]
				]).selectItem(currentSettings.privacy.can_write_messages));

				callPrivacyGroup.getBody(1).appendChild(pages.elements.createSelector('can_write_on_wall', [
					[
						settings.lang.getValue('all'),
						function () { settings.data.setPrivacy(2, 0) }
					], [
						settings.lang.getValue('only_friends'),
						function () { settings.data.setPrivacy(2, 1) }
					], [
						settings.lang.getValue('nobody'),
						function () { settings.data.setPrivacy(2, 2) }
					]
				]).selectItem(currentSettings.privacy.can_write_on_wall));

				callPrivacyGroup.getBody(2).appendChild(pages.elements.createSelector('can_comment_posts', [
					[
						settings.lang.getValue('all'),
						function () { settings.data.setPrivacy(4, 0) }
					], [
						settings.lang.getValue('only_friends'),
						function () { settings.data.setPrivacy(4, 1) }
					], [
						settings.lang.getValue('nobody'),
						function () { settings.data.setPrivacy(4, 2) }
					]
				]).selectItem(currentSettings.privacy.can_comment_posts));

				let chatsGroup = pages.elements.createCollapsible([
					[unt.Icon.CHATS, settings.lang.getValue('chat_privacy')]
				]);
				menuBody.appendChild(chatsGroup);
				chatsGroup.getBody(0).appendChild(pages.elements.createSelector('can_invite_to_chats', [
					[
						settings.lang.getValue('only_friends'),
						function () { settings.data.setPrivacy(3, 0) }
					], [
						settings.lang.getValue('nobody'),
						function () { settings.data.setPrivacy(3, 1) }
					]
				]).selectItem(currentSettings.privacy.can_invite_to_chats));

				let closedProfileItem = pages.elements.createSwitchButton([
					[unt.Icon.LOCKED, settings.lang.getValue('closed_profile'), function (event, item) {
						if (currentSettings.account.is_closed) {
							return settings.data.toggleProfileState();
						} else {
							closedProfileItem.selectItem(0, currentSettings.account.is_closed);

							return pages.elements.confirm('', settings.lang.getValue('closed_profile_attention') + ' ' + settings.lang.getValue('continue') + '?', function (response) {
								if (response) {
									closedProfileItem.selectItem(0, true);

									return settings.data.toggleProfileState();
								}
							})
						}
					}]
				]).selectItem(0, currentSettings.account.is_closed);

				menuBody.appendChild(closedProfileItem);
			}
			if (selectedSection === sections[3]) {
				let activityHistoryButton = pages.elements.createButton(unt.Icon.FINGERPRINT, settings.lang.getValue('activity_history'), function () {
					return ui.go(ui.getUrl() + '/sessions');
				});
				menuBody.appendChild(activityHistoryButton);

				let passwordChanger = pages.elements.createCollapsible([
					[
						unt.Icon.LOCKED,
						settings.lang.getValue('change_password')
					]
				]);

				let colBody = passwordChanger.getBody(0);

				let currentPassword = pages.elements.createInputField(settings.lang.getValue('current_password')).setType('password').maxLength(32);
				let newPassword = pages.elements.createInputField(settings.lang.getValue('new_password')).setType('password').maxLength(32);
				let repeatPassword = pages.elements.createInputField(settings.lang.getValue('repeat_password')).setType('password').maxLength(32);
				colBody.appendChild(currentPassword);
				colBody.appendChild(newPassword);
				colBody.appendChild(repeatPassword);

				let continueButton = pages.elements.createFAB(unt.Icon.ARROW_FWD);
				continueButton.classList.remove('fixed-action-btn');
				continueButton.style.textAlign = 'end';

				let buttonElement = continueButton.getElementsByTagName('a')[0];
				colBody.appendChild(continueButton);
				menuBody.appendChild(passwordChanger);

				buttonElement.classList.add('scale-transition');
				buttonElement.classList.add('scale-out');

				let loader = pages.elements.getLoader();
				colBody.appendChild(loader);

				loader.style.width = '100%';
				loader.style.textAlign = 'end';		
				loader.style.display = 'none';

				currentPassword.oninput = newPassword.oninput = repeatPassword.oninput = function (event) {
					if (!currentPassword.getValue().isEmpty() && !newPassword.getValue().isEmpty() && !repeatPassword.getValue().isEmpty()) {
						buttonElement.classList.remove('scale-out');
					} else {
						buttonElement.classList.add('scale-out');
					}
				}

				continueButton.onclick = function (event) {
					if (newPassword.getValue() !== repeatPassword.getValue()) {
						newPassword.getInput().classList.add('wrong');
						return repeatPassword.getInput().classList.add('wrong');
					} else {
						newPassword.getInput().classList.remove('wrong');
						repeatPassword.getInput().classList.remove('wrong');
					}

					continueButton.style.display = 'none';

					loader.show();
					currentPassword.getInput().classList.remove('wrong');
					return settings.users.verifyPassword(currentPassword.getValue()).then(function (response) {
						if (!response) {
							loader.hide();

							continueButton.style.display = '';
							return currentPassword.getInput().classList.add('wrong');
						}

						return pages.elements.confirm('', settings.lang.getValue('change_password_warning'), function (response) {
							if (response) {
								currentPassword.getInput().disabled = true;
								newPassword.getInput().disabled = true;
								repeatPassword.getInput().disabled = true;

								return settings.users.changePassword(currentPassword.getValue(), newPassword.getValue()).then(function (response) {
									if (!response) {
										throw new TypeError('not changed!');
									}

									loader.hide();

									unt.toast({html: settings.lang.getValue('loading')});
									return setTimeout(function () {
										return window.location.reload();
									}, 1000)
								}).catch(function (err) {
									currentPassword.getInput().disabled = false;
									newPassword.getInput().disabled = false;
									repeatPassword.getInput().disabled = false;

									loader.hide();

									continueButton.style.display = '';
									return unt.toast({html: settings.lang.getValue('upload_error')});
								})
							} else {
								loader.hide();

								continueButton.style.display = '';
							}
						})
					}).catch(function (err) {
						loader.hide();

						continueButton.style.display = '';
						return unt.toast({html: settings.lang.getValue('upload_error')});
					})
				}
			}
			if (selectedSection === sections[4]) {
				let loader = pages.elements.getLoader();
				loader.style.marginTop = '15px';

				menuBody.appendChild(loader);
				let blacklistDiv = document.createElement('div');

				blacklistDiv.classList = ['card collection'];

				menuBody.appendChild(blacklistDiv);
				return settings.users.getBlacklisted(1).then(function (blacklist) {
					if (blacklist.length > 0) {
						blacklist.forEach(function (user) {
							let userItem = pages.elements.userItem(user);
							blacklistDiv.appendChild(userItem);
						})
					} else {
						blacklistDiv.style.display = 'none';

						let alertWindow = pages.elements.alertWindow(unt.Icon.LIST, settings.lang.getValue('blacklist_empty'), settings.lang.getValue('blacklist_empty_text'));
						menuBody.appendChild(alertWindow);
					}

					ui.bindItems();
					return loader.hide();
				}).catch(function (err) {
					blacklistDiv.style.display = 'none';

					let uploadError = pages.elements.uploadError();
					menuBody.appendChild(uploadError);

					loader.hide();
					return ui.bindItems();
				})
			}
			if (selectedSection === sections[5]) {
				let infoDiv = pages.elements.createButton(unt.Icon.INFO, settings.lang.getValue('see_bound_accounts'), new Function());
				menuBody.appendChild(infoDiv);

				let infoLoader = pages.elements.getLoader();
				menuBody.appendChild(infoLoader);

				return settings.accounts.get().then(function (accounts) {
					infoLoader.hide();

					// VK ITEM
					let vkStringText = 'VK (' + settings.lang.getValue('logged_as') + ': ' + accounts[0].first_name + ' ' + accounts[0].last_name + ')';
					if (!accounts[0].bound)
						vkStringText = 'VK (' + settings.lang.getValue('not_logged') + ')';

					let vkItem = pages.elements.createCollapsible([
						[unt.Icon.CHATS, vkStringText, new Function()]
					]);

					let vkItemBody = vkItem.getBody(0);
					let vkItemHeader = vkItem.getHeader(0);

					let vkAccount = accounts[0];
					menuBody.appendChild(vkItem);

					let collectionDiv = document.createElement('div');
					collectionDiv.classList.add('collection');

					vkItemBody.appendChild(collectionDiv);

					if (!vkAccount.bound) {
						let loginButton = document.createElement('a');
						loginButton.classList.add('collection-item');

						loginButton.onclick = function (event) {
							return subPages.accounts.VKManage(accounts[0], event, menuBody);
						}

						loginButton.innerText = settings.lang.getValue('logstart');
						collectionDiv.appendChild(loginButton);
					} else {
						let editButton = document.createElement('a');
						editButton.classList.add('collection-item');

						editButton.onclick = function (event) {
							return subPages.accounts.VKManage(accounts[0], event, menuBody);
						}

						editButton.innerText = settings.lang.getValue('edit');
						collectionDiv.appendChild(editButton);
					}
					/////////////

					ui.bindItems();
					return unt.AutoInit();
				}).catch(function (err) {
					let uploadError = pages.elements.uploadError();

					infoLoader.hide();
					return menuBody.appendChild(uploadError);
				})
			}
			if (selectedSection === sections[6]) {
				return subPages.theming(menuBody, workedUrl);
			}
			if (selectedSection === sections[7]) {
				menuBody.appendChild(pages.elements.createButton(unt.Icon.STATS, settings.lang.getValue('about'), function () {
					return ui.go(ui.getUrl() + '/about');
				}));
				menuBody.appendChild(pages.elements.createButton(unt.Icon.DEV, settings.lang.getValue('devs'), function () {
					return window.location.href = 'https://dev.yunnet.ru/';
				}));
			}
		},
		showManagementPage: function (chatObject, permissions, myAccessLevel, menuBody, loader, chat) {
			if (myAccessLevel >= 9) {
				loader.hide();

				let chatLinkWindow = document.createElement('div');
				chatLinkWindow.classList = ['card full_section'];

				let linkInputField = pages.elements.createInputField(settings.lang.getValue('chat_link'), true).setType('text').setText('...').setReadOnly();
				chatLinkWindow.appendChild(linkInputField);

				chat.getJoinLink().then(function (link) {
					linkInputField.setText(link);
				}).catch(function (err) {
					return;
				});

				let inProcess = false;

				let updateButton = pages.elements.createButton('', settings.lang.getValue('update'), function (event) {
					if (inProcess) return;
					inProcess = true;

					let textItem = updateButton.getElementsByClassName('collapsible-header')[0].getElementsByTagName('div')[0];

					textItem.innerText = settings.lang.getValue('loading');
					chat.updateJoinLink().then(function (link) {
						linkInputField.setText(link);

						textItem.innerText = settings.lang.getValue('update');
						inProcess = false;
					}).catch(function (err) {
						textItem.innerText = settings.lang.getValue('update');
						inProcess = false;

						return unt.toast({html: settings.lang.getValue('upload_error')});
					})
				});

				let permissionsDiv = pages.chats.elements.permissionsWindow(permissions, function (groupName, value) {
					if (permissions[groupName] === undefined) return;
					if (value < 0 || value > 9) return;

					return chat.setPermissions(groupName, value).then(function (response) {
						return;
					}).catch(function (err) {
						return unt.toast({html: settings.lang.getValue("upload_error")})
					});
				});

				menuBody.appendChild(chatLinkWindow);
				menuBody.appendChild(updateButton);
				menuBody.appendChild(permissionsDiv);

				chatLinkWindow.style.marginBottom = 0;
				updateButton.style.marginTop = 0;

				unt.Collapsible.init(permissionsDiv);
			} else {
				return ui.go(ui.getUrl() + '/messages');
			}
		},
		showUserManagementPage: function (userObject, chatObject, myAccessLevel, permissions, menuBody, loader, chat) {
			menuBody = pages.elements.menuBody();
			let workDiv = pages.chats.elements.window(false);

			loader.hide();

			workDiv.getElementsByTagName('img')[0].src = userObject.photo_url;
			workDiv.getElementsByTagName('img')[0].width = workDiv.getElementsByTagName('img')[0].height = 48;

			let textDiv = workDiv.getElementsByClassName('input-field')[0].parentNode;
			workDiv.getElementsByClassName('input-field')[0].remove();

			let userLink = document.createElement('a');
			userLink.classList.add('alink-name');
			textDiv.appendChild(userLink);

			let b = document.createElement('b');
			userLink.appendChild(b);
			b.innerText = userObject.name || userObject.first_name + ' ' + userObject.last_name;
			if (!userObject.account_type) {
				b.innerText = settings.lang.getValue("deleted_account");
				workDiv.getElementsByTagName('img')[0].src = 'https://dev.yunnet.ru/images/default.png';
			}

			userLink.href = userObject.account_type === 'user' ? ('/id' + userObject.user_id) : ('/bot' + userObject.bot_id);
			
			menuBody.appendChild(workDiv);
			ui.bindItems();

			if (userObject.account_type) {
				if (myAccessLevel >= permissions.can_change_levels && myAccessLevel > userObject.chat_info.access_level) {
					let permissionsChanger = pages.chats.elements.permissionsWindow({
						set_user_level: userObject.chat_info.access_level
					}, function (groupName, newValue) {
						return chat.setUserLevel(userObject.user_id || (userObject.bot_id * -1), newValue).catch(function (err) {
							return unt.toast({html: settings.lang.getValue('upload_error')});
						})
					}, true, myAccessLevel - 1);

					menuBody.appendChild(permissionsChanger);
					unt.Collapsible.init(permissionsChanger);
				}
				if (myAccessLevel >= permissions.can_mute && myAccessLevel > userObject.chat_info.access_level) {
					let muteItem = pages.elements.createButton('', settings.lang.getValue(userObject.chat_info.is_muted ? 'unmute' : 'mute'), function (event) {
						let innerTextItem = muteItem.getElementsByClassName('collapsible-header')[0].getElementsByTagName('div')[0];

						let oldText = innerTextItem.innerText;

						innerTextItem.innerText = settings.lang.getValue('loading');
						innerTextItem.style.color = 'gray';

						return chat.toggleWriteAccess(userObject.user_id || (userObject.bot_id * -1)).then(function (response) {
							innerTextItem.style.color = 'black';
							if (response === 1) {
								innerTextItem.innerText = settings.lang.getValue('unmute');
							}
							if (response === 0) {
								innerTextItem.innerText = settings.lang.getValue('mute');
							}
						}).catch(function (err) {
							innerTextItem.innerText = oldText;
							innerTextItem.style.color = 'black';

							return unt.toast({html: settings.lang.getValue('upload_error')});
						})
					});

					menuBody.appendChild(muteItem);
				}
			}
			
			if ((myAccessLevel >= permissions.can_kick && myAccessLevel > userObject.chat_info.access_level) || userObject.chat_info.invited_by === settings.users.current.user_id && myAccessLevel > userObject.chat_info.access_level) {
				menuBody.appendChild(pages.elements.createButton('', settings.lang.getValue('kick_user'), function (event) {
					return pages.elements.confirm('', settings.lang.getValue('kick_confirmation'), function (response) {
						if (response) {
							return chat.removeUser(userObject.user_id || (userObject.bot_id * -1)).then(function (result) {
								return ui.go(ui.getUrl() + '/messages?s=' + chat.peer_id + '&action=info');
							}).catch(function (err) {
								return unt.toast({html: settings.lang.getValue("upload_error")});
							})
						}
					})
				}, 'red'));
			}
		},
		chat: {
			toggleChat: function (chat, actionsDiv, menuBody) {
				let item = actionsDiv.getElementsByClassName('current_state')[0];
				if (item.innerText === settings.lang.getValue("leave_chat")) {
					return pages.elements.confirm('', settings.lang.getValue('leave_confirmation'), function (response) {
						if (response) {
							chat.toggleUser().then(function (result) {
								chat.clearChatCache();

								return ui.go(window.location.href, false, false, false, false);
							}).catch(function (err) {
								return unt.toast({html: settings.lang.getValue("upload_error")});
							})
						}
					})
				} else {
					return chat.toggleUser().then(function (result) {
						chat.clearChatCache();

						return ui.go(window.location.href, false, false, false, false);
					}).catch(function (err) {
						return unt.toast({html: settings.lang.getValue("upload_error")});
					})
				}
			},
			addMember: function (chat) {
				let internalData = {
					subaction: 'invite_user',
					to_chat: chat.peer_id,
					chat: chat,
					members: []
				}

				return ui.go(ui.getUrl() + '/friends', false, false, false, true, internalData);
			}
		},
		chatInfoCallback: function (chatObject, permissions, myAccessLevel, menuBody, loader, chat) {
			menuBody = pages.elements.menuBody();

			let permissionsCurrent = chatObject.metadata.permissions;
			if (!chatObject.metadata.permissions) permissionsCurrent = {};

			let chatDiv = pages.chats.elements.window(true);
			menuBody.appendChild(chatDiv);

			let inputElement = chatDiv.getElementsByTagName('input')[0];
			if (myAccessLevel < permissions.can_change_title || permissionsCurrent.is_leaved || permissionsCurrent.is_kicked) {
				inputElement.setAttribute('disabled', 'true');
			} else {
				inputElement.addEventListener('input', function (event) {
					if (!(inputElement.value.isEmpty() || inputElement.value.length > 64)) {
						chatDiv.getCreateButton().style.display = '';

						return chatDiv.getCreateButton().classList.remove('scale-out');
					}

					chatDiv.getCreateButton().style.display = 'none';
					return chatDiv.getCreateButton().classList.add('scale-out');
				})

				chatDiv.getCreateButton().addEventListener('click', function (event) {
					chatDiv.setLoading(true);
					inputElement.setAttribute('disabled', 'true');

					return chat.setTitle(inputElement.value).then(function (response) {
						chatDiv.setLoading(false);
						inputElement.removeAttribute('disabled');

						chatDiv.getCreateButton().style.display = 'none';
						chatDiv.getCreateButton().classList.add('scale-out');
					}).catch(function (err) {
						chatDiv.setLoading(false);
						inputElement.removeAttribute('disabled');

						return unt.toast({html: settings.lang.getValue('upload_error')});
					})
				})
			}
						
			if (myAccessLevel >= permissions.can_change_photo && !(permissionsCurrent.is_leaved || permissionsCurrent.is_kicked)) chatDiv.getElementsByTagName('img')[0].onclick = function () {
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
											
											chat.updatePhoto(attachmentCredentials).then(function (response) {
												uploader.close();
											}).catch(function (err) {
												uploader.close();

												return unt.toast({html: settings.lang.getValue('upload_error')});
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

					return chat.updatePhoto().then(function (response) {
						uploader.close();
					}).catch(function (err) {
						uploader.close();

						return unt.toast({html: settings.lang.getValue('upload_error')});
					})
				});

				return uploader.open();
			}

			chatDiv.getCreateButton().classList.add('scale-out');
			chatDiv.getCreateButton().style.display = 'none';

			chatDiv.getElementsByTagName('input')[0].value = chatObject.chat_info.data.title;
			chatDiv.getElementsByTagName('img')[0].src = chatObject.chat_info.data.photo_url;

			loader.hide();

			let actionsWithChat = [];

			if (!permissionsCurrent.is_kicked) {
				if (myAccessLevel >= permissions.can_invite && !permissionsCurrent.is_leaved) {
					actionsWithChat.push([settings.lang.getValue("invite_to_chat"), function () {
						return codes.callbacks.chat.addMember(chat, actionsDiv);
					}], 'invite_to_chat');
				}

				if (permissionsCurrent.is_leaved) {
					actionsWithChat.push([settings.lang.getValue("return_to_chat"), function () {
						return codes.callbacks.chat.toggleChat(chat, actionsDiv, menuBody);
					}, 'current_state']);
				} else {
					actionsWithChat.push([settings.lang.getValue("leave_chat"), function () {
						return codes.callbacks.chat.toggleChat(chat, actionsDiv, menuBody);
					}, 'current_state']);
				}
			}

			let actionsDiv = pages.elements.actionsMenu(actionsWithChat, false, true, permissionsCurrent.is_kicked || false);
			menuBody.appendChild(actionsDiv);

			if (chat.chatObject) {
				menuBody.appendChild(pages.elements.createSwitchButton([
					[
						unt.Icon.NOTIFICATIONS,
						settings.lang.getValue('notifications'),
						function () {
							chat.toggleNotifications().then(function () {
								chatObject.metadata.notifications = !chatObject.metadata.notifications;
							});
						},
						chatObject.metadata.notifications
					],
					[
						unt.Icon.MESSAGES,
						settings.lang.getValue('show_pinned_messages'),
						function () {
							chat.togglePinnedMessages().then(function () {
								chatObject.metadata.show_pinned_messages = !chatObject.metadata.show_pinned_messages;
							});
						},
						chatObject.metadata.show_pinned_messages
					]
				]));
			}

			if (!permissionsCurrent.is_kicked)
				unt.Collapsible.init(actionsDiv);

			if (myAccessLevel >= 9 && !permissionsCurrent.is_kicked && !permissionsCurrent.is_leaved) {
				let managementUl = pages.elements.createButton(unt.Icon.SETTINGS, settings.lang.getValue("management"), function (event) {
					return ui.go(ui.getUrl() + '/messages?s=' + chat.peer_id.toString() + '&action=info&mode=management');
				});

				menuBody.appendChild(managementUl);
			}

			if (!permissionsCurrent.is_kicked && !permissionsCurrent.is_leaved) {
				let membersLoader = pages.elements.getLoader();
				menuBody.appendChild(membersLoader);

				let membersDiv = document.createElement('div');
				membersDiv.classList = ['card collection'];

				menuBody.appendChild(membersDiv);
				membersDiv.style.display = 'none';

				ui.bindItems();
				chat.getMembers(1).then(function (members) {
					members.forEach(function (item) {
						let userItem = pages.elements.userItem(item);
						userItem.id = item.user_id || (item.bot_id * -1);

						let p = userItem.getElementsByTagName('p')[0];
						if (item.chat_info.access_level >= 9) {
							p.innerText += ' (' + settings.lang.getValue('chat_creator') + ')'
						} else {
							settings.users.get(item.chat_info.invited_by).then(function (user) {
								p.innerText += ' (' + settings.lang.getValue("invited_by")
									.replace("(а)", user.gender === 2 ? "а" : "")
									.replace('%by%', user.name || (user.first_name + ' ' + user.last_name)) + ')';
							}).catch(function (err) {
								p.innerText += ' (' + settings.lang.getValue("invited_by")
									.replace("(а)", user.gender === 2 ? "а" : "")
									.replace('%by%', settings.lang.getValue("deleted_account")) + ')';
							})
						}

						if (((myAccessLevel >= permissions.can_kick || myAccessLevel >= permissions.can_mute) && myAccessLevel > item.chat_info.access_level) 
							|| item.chat_info.invited_by === settings.users.current.user_id && myAccessLevel > item.chat_info.access_level) {

							let secondaryData = document.createElement('div');
							secondaryData.classList.add('secondary-content');

							let currentLink = userItem.href;
							
							userItem.appendChild(secondaryData);
							userItem.href = '';

							let editItem = document.createElement('div');
							secondaryData.appendChild(editItem);

							editItem.innerHTML = unt.Icon.EDIT;
							userItem.onclick = function (event) {
								event.preventDefault();

								return ui.go(ui.getUrl() + '/messages?s=' + chat.peer_id + '&action=info&mode=edit', false, false, false, true, {
									userObject	: item,
									menuBody: menuBody,
									loader: loader,
									chat: chat
								});
							}
						}

						membersDiv.appendChild(userItem);
					});

					membersLoader.hide();
					membersDiv.style.display = '';
					membersDiv.id = 'membersInfo';

					ui.bindItems();
				}).catch(function (err) {
					membersLoader.hide();

					return unt.toast({html: settings.lang.getValue('upload_error')});
				})
			}
		},
		messageCallback: function (messageElement, chat, actsHeader, chatHeader, event, backCallback) {
			if (event.target.nodeName === 'A' || event.target.nodeName === 'IMG') return;
			if (chat.editMode) return;

			actsHeader.getElementsByClassName('reply')[0].disable();
			actsHeader.getElementsByClassName('fwd')[0].disable();

			let messageId = Number(messageElement.id);
			let idIndex = chat.selectedMessages.indexOf(messageId);
			if (idIndex === -1) {
				if (chat.selectedMessages.length >= 100) return;
				
				chat.selectedMessages.push(messageId);
				messageElement.getElementsByClassName('msg')[0].classList.add('selected');
			} else {
				chat.selectedMessages.splice(idIndex, 1);
						
				messageElement.getElementsByClassName('msg')[0].classList.remove('selected');
			}

			chatHeader.style.display = 'none';
			actsHeader.style.display = '';
			editHeader.style.display = 'none';

			let selectedItems = document.querySelectorAll('.selected');

			let allowToEdit = true;
			if (selectedItems.length === 1) {
				if (selectedItems[0].classList.contains("from-them"))
					allowToEdit = false;
				else
					allowToEdit = true;
			} else {
				allowToEdit = false;
			}

			if (!messageElement.sendError && !messageElement.isSending) {
				actsHeader.getElementsByClassName('reply')[0].enable();
				actsHeader.getElementsByClassName('fwd')[0].enable();

				if (allowToEdit) actsHeader.getElementsByClassName('edit')[0].enable();
				else actsHeader.getElementsByClassName('edit')[0].disable();
			}

			actsHeader.setText(chat.selectedMessages.length + '/100');
			if (chat.selectedMessages.length <= 0) {
				actsHeader.style.display = 'none';
				chatHeader.style.display = '';
				editHeader.style.display = 'none';
			}
		},
		uploadResolve: function (event, uploader) {
			let uploadPercent = (event.loaded / event.total) * 100;

			return uploader.setProgress(uploadPercent);			
		}
	}
};

const subPages = {
	searchFriends: function (menuBody) {
		let params = {
			onlineOnly: false,
			searchBots: false,
			searchBotsOnly: false
		};

		let inputCard = document.createElement('div');

		inputCard.classList.add('card');
		inputCard.classList.add('full_section');

		let searchDiv = document.createElement('div');
		searchDiv.classList.add('valign-wrapper');

		menuBody.appendChild(inputCard);
		inputCard.appendChild(searchDiv);

		let searchInput = pages.elements.createInputField(settings.lang.getValue('search_text'), false).setType('text').maxLength(256);
		searchDiv.appendChild(searchInput);

		searchInput.style.margin = 0;
		searchInput.style.width = '100%';

		let loader = pages.elements.getLoader();
		let resultDiv = document.createElement('div');

		resultDiv.classList.add('card');
		resultDiv.classList.add('collection');

		let inProcess = false;
		loader.hide();

		let modalWindow = pages.elements.alertWindow(unt.Icon.SEARCH, settings.lang.getValue('no_results'), settings.lang.getValue('no_res').replace('%c%', '0'));
		modalWindow.style.display = 'none';

		let searchButton = pages.elements.createFAB(unt.Icon.SEARCH, function () {
			if (inProcess) return;

			searchInput.getInput().classList.remove('wrong');
			if (searchInput.getValue().isEmpty()) return searchInput.getInput().classList.add('wrong');
			
			inProcess = true;

			searchInput.getInput().disabled = true;
			searchButton.getElementsByTagName('a')[0].classList.add('disabled');

			loader.show();

			modalWindow.style.display = 'none';
			resultDiv.style.display = 'none';
			resultDiv.innerHTML = '';

			return settings.users.search(searchInput.getValue(), params, 0, 30).then(function (result) {
				inProcess = false;

				searchInput.getInput().disabled = false;
				searchButton.getElementsByTagName('a')[0].classList.remove('disabled');

				if (result.length > 0) {
					result.forEach(function (user) {
						let userElement = pages.elements.userItem(user);

						resultDiv.appendChild(userElement);
					});

					resultDiv.style.display = '';
				} else {
					modalWindow.style.display = '';
				}

				ui.bindItems();
				return loader.hide();
			}).catch(function (error) {
				inProcess = false;

				searchInput.getInput().disabled = false;
				searchButton.getElementsByTagName('a')[0].classList.remove('disabled');

				loader.hide();
				return menuBody.appendChild(pages.elements.uploadError());
			});
		});

		searchButton.style.marginLeft = '15px';

		searchButton.classList.remove('fixed-action-btn');
		searchDiv.appendChild(searchButton);

		let settingsItem = document.createElement('div');
		inputCard.appendChild(settingsItem);
		settingsItem.innerHTML = unt.Icon.SETTINGS;
		settingsItem.style.cursor = 'pointer';

		settingsItem.style.marginTop = '15px';
		settingsItem.style.width = 'fit-content';
		settingsItem.onclick = function () {
			if (inProcess) return;

			let settingsWindow = pages.elements.createWindow();

			settingsWindow.getFooter().remove();

			let onlyOnlineChecker = pages.elements.createChecker(settings.lang.getValue('online_only'), function () {
				if (params.searchBotsOnly) {
					params.searchBotsOnly = false;

					onlyBotsChecker.setChecked(false);
				}

				return params.onlineOnly = !params.onlineOnly;
			}).setChecked(params.onlineOnly);

			let botsSearchChecker = pages.elements.createChecker(settings.lang.getValue('search_bots'), function () {
				if (params.searchBotsOnly) {
					params.searchBotsOnly = false;

					onlyBotsChecker.setChecked(false);
				}

				return params.searchBots = !params.searchBots;
			}).setChecked(params.searchBots).setDisabled(true);

			let onlyBotsChecker = pages.elements.createChecker(settings.lang.getValue('search_bots_only'), function () {
				if (!params.searchBotsOnly) {
					params.searchBots = false;
					params.onlineOnly = false;

					onlyOnlineChecker.setChecked(false);
					botsSearchChecker.setChecked(false);
				}
				
				return params.searchBotsOnly = !params.searchBotsOnly;
			}).setChecked(params.searchBotsOnly);

			settingsWindow.getContent().appendChild(onlyOnlineChecker);
			settingsWindow.getContent().appendChild(botsSearchChecker);
			settingsWindow.getContent().appendChild(onlyBotsChecker);
		}

		menuBody.appendChild(resultDiv);
		menuBody.appendChild(modalWindow);
		menuBody.appendChild(loader);
	},
	theming: function (menuBody, workedUrl) {
		menuBody.appendChild(pages.elements.createSwitchButton([
			[
				unt.Icon.DEV,
				settings.lang.getValue('allow_js_themes'),
				function (event, item) {
					if (settings.get().theming.js_allowed) {
						settings.get().theming.js_allowed = false;
						item.checked = false;

						return themes.toggleJSState().then(function (result) {
							settings.get().theming.js_allowed = Boolean(result);

							return item.checked = result;
						}).catch(function (err) {
							return unt.toast({html: settings.lang.getValue('upload_error')});
						})
					} else {
						item.checked = false;
						let passwordField = pages.elements.createInputField(settings.lang.getValue('password'), false).setType('password');
						let confirmWindow = pages.elements.confirm('', settings.lang.getValue('js_warning').replace('%username%', settings.users.current.first_name + ' ' + settings.users.current.last_name), function (result, instance, yesBtn, noBtn) {
							if (result) {
								return settings.users.verifyPassword(passwordField.getValue()).then(function (response) {
									if (!response) return passwordField.getInput().classList.add('wrong');

									passwordField.getInput().classList.remove('wrong');

									instance.close();
									return themes.toggleJSState().then(function (result) {
										settings.get().theming.js_allowed = Boolean(result);

										return item.checked = result;
									}).catch(function (err) {
										return unt.toast({html: settings.lang.getValue('upload_error')});
									})
								})
							} else {
								return instance.close();
							}
						}, false).getBody();

						confirmWindow.appendChild(passwordField);
					}
				}
			],
			[
				unt.Icon.PALETTE,
				settings.lang.getValue('use_new_design'),
				function (event, item) {
					item.checked = !item.checked;
					return pages.elements.confirm('', settings.lang.getValue('change_to_new_design'), function (response) {
						if (response) {
							let data = new FormData();

							data.append('action', 'toggle_new_design');
							return ui.Request({
								url: '/settings',
								method: 'POST',
								data: data,
								success: function () {
									item.checked = !item.checked;
									
									document.body.innerHTML = '';
								  	unt.toast({html: 'Redirecting...'});
								  	setTimeout(function () {
								  		return window.location.reload();
								  	}, 2000);
								},
								error: function () {
									return unt.toast({html: settings.lang.getValue('upload_error')});
								}
							});
						}
					})
				}
			]
		]).selectItem(0, settings.get().theming.js_allowed)
		  .selectItem(1, settings.get().theming.new_design));

		let elementsEditor = pages.elements.createCollapsible([[
			unt.Icon.EDIT,
			settings.lang.getValue('themer')
		]]);

		let elementsEditorBody = elementsEditor.getBody(0);
		let collection = document.createElement('div');
		collection.classList.add('collection');

		let itemValues = ['news', 'notifications', 'friends', 'messages', 'groups', 'archive', 'settings', 'audios'];

		let downButton = pages.elements.createFAB(unt.Icon.ARROW_DOWN);
		let upButton = pages.elements.createFAB(unt.Icon.ARROW_UP);
		let saveButton = pages.elements.createFAB(unt.Icon.SAVE);

		downButton.classList.remove('fixed-action-btn');
		downButton.classList.add('scale-transition');
		downButton.classList.add('scale-out');

		upButton.classList.remove('fixed-action-btn');
		upButton.classList.add('scale-transition');
		upButton.classList.add('scale-out');
		upButton.style.marginLeft = '10px';

		saveButton.classList.remove('fixed-action-btn');
		saveButton.classList.add('scale-transition');
		saveButton.classList.add('scale-out');
		saveButton.style.marginLeft = '10px';

		let buttonsDiv = document.createElement('div');
		buttonsDiv.classList.add('valign-wrapper');
		buttonsDiv.style.marginTop = '10px';

		buttonsDiv.appendChild(downButton);
		buttonsDiv.appendChild(upButton);
		buttonsDiv.appendChild(saveButton);

		upButton.mode = 0;
		downButton.mode = 1;

		settings.get().theming.menu_items.forEach(function (itemId) {
			if (!itemValues[itemId - 1]) return;

			let element = document.createElement('a');
			element.itemId = itemId;

			element.classList.add('collection-item');
			element.innerText = settings.lang.getValue(itemValues[itemId - 1]);
			element.onclick = function (event) {
				collection.querySelectorAll('.selected').forEach(function (item) {
					if (itemId === item.itemId) return;

					item.classList.remove('selected');
					item.classList.remove('active');
				});

				if (element.classList.contains('selected')) {
					element.classList.remove('selected');
					element.classList.remove('active');

					upButton.classList.add('scale-out');
					downButton.classList.add('scale-out');
				} else {
					element.classList.add('selected');
					element.classList.add('active');

					upButton.classList.remove('scale-out');
					downButton.classList.remove('scale-out');
				}
			}

			return collection.appendChild(element);
		});

		downButton.onclick = upButton.onclick = function (event) {
			let currentElement = collection.getElementsByClassName('selected')[0];
			if (!currentElement) return;

			let collectionItems = collection.getElementsByTagName('a');
			for (let index = 0; index < collectionItems.length; index++) {
				let item = collectionItems[index];

				if (item.itemId === currentElement.itemId) {
					let nextElement = collectionItems[(this.mode === 1) ? (index + 1) : (index - 1)];
					
					if (nextElement) {
						currentElement.remove();

						nextElement.insertAdjacentElement((this.mode === 1 ? 'afterend' : 'beforebegin'), currentElement);
						break;
					}
				}
			}

			let newItems = [];
			for (let i = 0; i < collectionItems.length; i++) {
				newItems.push(collectionItems[i].itemId);
			}

			let isOld = false;
			for (let i = 0; i < newItems.length; i++) {
				if (newItems[i] !== settings.get().theming.menu_items[i]) {
					isOld = true;
					break;
				}
			}

			if (!isOld) saveButton.classList.add('scale-out');
			else saveButton.classList.remove('scale-out');
		}

		saveButton.onclick = function () {
			let collectionItems = collection.getElementsByTagName('a');

			let newItems = [];
			for (let i = 0; i < collectionItems.length; i++) {
				newItems.push(collectionItems[i].itemId);
			}

			let isOld = false;
			for (let i = 0; i < newItems.length; i++) {
				if (newItems[i] !== settings.get().theming.menu_items[i]) {
					isOld = true;
					break;
				}
			}

			if (!isOld) return;
			return themes.menu.updateItems(newItems).then(function (result) {
				if (result) return window.location.reload();
			}).catch(function (err) {
				return unt.toast({html: settings.lang.getValue('upload_error')});
			})
		}

		elementsEditorBody.appendChild(collection);
		elementsEditorBody.appendChild(buttonsDiv);
		menuBody.appendChild(elementsEditor);

		menuBody.appendChild(pages.elements.createButton(unt.Icon.PALETTE, settings.lang.getValue('account_themes'), function (event) {
			return ui.go(ui.getUrl() + '/themes');
		}));
	},
	audios: {
		manage: function (currentSection, internalData, rightMenu, menuBody, loader, accounts) {
			loader.hide();

			let currentPage = 1;

			if (currentSection === 'audios') {
				return menuBody.appendChild(pages.elements.alertWindow(unt.Icon.LOCKED, settings.lang.getValue('in_development'), settings.lang.getValue('audios_in_dev')));
			}
			if (currentSection === 'vk_audios') {
				menuBody.appendChild(pages.elements.createButton(unt.Icon.INFO, settings.lang.getValue('logged_as') + ': ' + accounts[0].first_name + ' ' + accounts[0].last_name, new Function()));
				let playerElement = audios.buildPlayerElement(false);

				let isLoading = false;
				menuBody.onscroll = function (event) {
					if (isLoading) return;

					if (window.scrollY === (document.body.scrollHeight - document.body.offsetHeight)) {
						isLoading = true;

						//getAudios(currentPage++	);
					}
				}

				let miniPlayer;
				if (!audios.miniPlayerCreated) {
					miniPlayer = audios.buildPlayerElement(true);

					miniPlayer.style.display = 'none';
					miniPlayer.setup();
				}

				if (!audios.current)
					playerElement.style.display = 'none';
				
				menuBody.appendChild(playerElement);
				let audiosDiv = document.createElement('div');

				audiosDiv.classList.add('card');
				audiosDiv.classList.add('collection');

				menuBody.appendChild(audiosDiv);

				let musicLoader = pages.elements.getLoader();
				menuBody.appendChild(musicLoader);

				audiosDiv.style.display = 'none';

				function getAudios (currentPage = 1) {
					musicLoader.show();

					return audios.get(1, currentPage).then(function (audiosList) {
						if (audiosList.length > 0) {
							audiosList.forEach(function (audio) {
								let audioItem = pages.elements.audioItem(audio, playerElement, miniPlayer);

								return audiosDiv.appendChild(audioItem);
							});

							audiosDiv.style.display = '';
						} else if (currentPage === 1) {
							menuBody.appendChild(pages.elements.alertWindow(unt.Icon.AUDIO_OFF, settings.lang.getValue('no_audio'), settings.lang.getValue('no_audio_text')));
						}

						isLoading = false;
						return musicLoader.hide();
					}).catch(function (err) {
						let uploadError = pages.elements.uploadError();

						musicLoader.hide();
						return menuBody.appendChild(uploadError);
					})
				}

				getAudios();
			}
		}
	},
	accounts: {
		VKManage: function (accountState, event, menuBody) {
			let modal = document.createElement('div');
			modal.classList.add('modal');

			if (ui.isMobile())
				modal.classList.add('bottom-sheet');

			let content = document.createElement('div');
			content.classList.add('modal-content');
			modal.appendChild(content);

			let footer = document.createElement('div');
			footer.classList.add('modal-footer');
			modal.appendChild(footer);

			menuBody.appendChild(modal);
			unt.Modal.init(modal).open();

			if (accountState.bound) {
				footer.remove();

				let collectionDiv = document.createElement('div');
				collectionDiv.classList.add('collection');
				content.appendChild(collectionDiv);

				let logoutItem = document.createElement('a');
				logoutItem.classList.add('collection-item');
				logoutItem.innerText = settings.lang.getValue('logout');
				collectionDiv.appendChild(logoutItem);

				logoutItem.onclick = function () {
					return pages.elements.confirm('', settings.lang.getValue('logout_qq'), function (response) {
						if (response) {
							return settings.accounts.unbound(1).then(function (response) {
								return ui.go(window.location.href, false, true);
							})
						}
					})
				}
			} else {
				let loginDiv = document.createElement('form');
				loginDiv.method = 'POST';

				loginDiv.innerText = settings.lang.getValue('logstart');
				content.appendChild(loginDiv);

				let loginField = pages.elements.createInputField(settings.lang.getValue('login')).setType('text');
				let passwordField = pages.elements.createInputField(settings.lang.getValue('password')).setType('password');

				content.appendChild(loginField);
				content.appendChild(passwordField);

				let continueButton = document.createElement('a');
				continueButton.action = 'submit';
				loginDiv.onsubmit = function (event) {
					return event.preventDefault();
				}

				continueButton.classList = ['disabled btn-flat waves-effect'];
				continueButton.innerText = settings.lang.getValue('continue');
				footer.appendChild(continueButton);

				loginField.getInput().oninput = passwordField.getInput().oninput = function (event) {
					if (loginField.getValue().isEmpty() || passwordField.getValue().isEmpty()) {
						continueButton.classList.add('disabled');
					} else {
						continueButton.classList.remove('disabled');
					}
				}

				continueButton.onclick = function () {
					continueButton.classList.add('disabled');
					loginField.getInput().classList.add('disabled');
					passwordField.getInput().classList.add('disabled');

					return settings.accounts.auth(loginField.getValue(), passwordField.getValue(), 1).then(function (response) {
						continueButton.classList.remove('disabled');
						loginField.getInput().classList.remove('disabled');
						passwordField.getInput().classList.remove('disabled');

						if (response.error === 5) {
							return unt.toast({html: settings.lang.getValue('incorrect_login')});
						}
						if (response.error === 10 || response.error === 20) {
							if (response.error === 20) unt.toast({html: settings.lang.getValue('vk_limits')});

							let login = loginField.getValue();
							let password = passwordField.getValue();

							content.innerHTML = '';

							let codeItem = pages.elements.createInputField(settings.lang.getValue('code')).setType('number');
							content.appendChild(codeItem);

							continueButton.classList.remove('disabled');
							codeItem.getInput().oninput = function () {
								if (codeItem.getValue().isEmpty())
									return continueButton.classList.add('disabled');
								else
									continueButton.classList.remove('disabled');
							}

							continueButton.onclick = function () {
								continueButton.classList.add('disabled');

								return settings.accounts.auth(login, password, 1, codeItem.getValue()).then(function (response) {
									continueButton.classList.remove('disabled');

									if (response.error === 5) {
										return unt.toast({html: settings.lang.getValue('wrong_code')});
									}

									return ui.go(window.location.href, false, true);
								});
							}
						}

						if (response.success) {
							unt.Modal.getInstance(modal).close();

							return ui.go(window.location.href, false, true);
						}
					}).catch(function (err) {
						continueButton.classList.remove('disabled');
						loginField.getInput().classList.remove('disabled');
						passwordField.getInput().classList.remove('disabled');

						return unt.toast({html: settings.lang.getValue('upload_error')});
					})
				}
			}
		}
	},
	themes: {
		installed: function (menuBody, workedUrl, internalData) {
			if (workedUrl.mode === 'edit' && internalData && internalData.theme && internalData.theme.owner_id === settings.users.current.user_id)
				return subPages.themes.editTheme(menuBody, internalData); 

			document.title = 'yunNet. - ' + settings.lang.getValue('installed_themes');
			ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("installed_themes") : null;

			let loader = pages.elements.getLoader();
			loader.style.marginTop = '10px';

			menuBody.appendChild(loader);

			let themesDiv = document.createElement('div');
			themesDiv.classList.add('card');
			themesDiv.classList.add('collection');

			menuBody.appendChild(themesDiv);
			themesDiv.style.display = 'none';

			menuBody.appendChild(pages.elements.createFAB(unt.Icon.EDIT, null, [
				[
					unt.Icon.ADD,
					function () {
						let windowElement = pages.elements.createWindow();

						let footer = windowElement.getFooter();
						let content = windowElement.getContent();

						let windowHeader = document.createElement('div');
						content.appendChild(windowHeader);
						windowHeader.innerText = settings.lang.getValue('create_theme') + ':';

						let button = document.createElement('a');
						button.classList.add('btn-flat');
						button.classList.add('waves-effect');
						button.innerText = settings.lang.getValue('continue');

						footer.appendChild(button);

						content.appendChild(document.createElement('br'));

						let titleInput = pages.elements.createInputField(settings.lang.getValue('theme_title'), false).setType('text').maxLength(64);
						content.appendChild(titleInput);

						let descriptionInput = pages.elements.createInputField(settings.lang.getValue('desc'), false).setType('text').maxLength(128);
						content.appendChild(descriptionInput);

						let privateMode = pages.elements.createChecker(settings.lang.getValue('private_theme'), function () {
							return true;
						})
						content.appendChild(privateMode);

						button.onclick = function () {
							titleInput.getInput().classList.remove('wrong');
							descriptionInput.getInput().classList.remove('wrong');

							if (titleInput.getValue().trim().isEmpty()) return titleInput.getInput().classList.add('wrong');
							if (descriptionInput.getValue().trim().isEmpty()) return descriptionInput.getInput().classList.add('wrong');

							button.classList.add('disabled');
							titleInput.getInput().setAttribute('disabled', '');
							descriptionInput.getInput().setAttribute('disabled', '');
							privateMode.setDisabled(true);
							
							return themes.create(titleInput.getValue().trim(), descriptionInput.getValue().trim(), privateMode.checked()).then(function (theme) {
								windowElement.getInstance().close();

								return ui.go(window.location.href, false, true, false, true);
							}).catch(function (err) {
								button.classList.remove('disabled');
								titleInput.getInput().removeAttribute('disabled');
								descriptionInput.getInput().removeAttribute('disabled');
								privateMode.setDisabled(false);

								return unt.toast({html: settings.lang.getValue('upload_error')});
							});
						}
					}
				], [
					unt.Icon.EXPORT,
					function () {
						let uploader = pages.elements.fileUploader({
							onFileSelected: function (event, files, uploader) {
								uploader.setLoading(true);

								return uploads
									.getURL(uploads.type.THEME)
									.then(function (url) {
										return uploads
											.upload(url, files[0], function (event) {
												return codes.callbacks.uploadResolve(event, uploader);
											})
											.then(function (attachment) {
												uploader.setLoading(false);

												uploader.close();
												return ui.go(window.location.href);
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
							afterClose: function () {},
							fileTypes: '.uth'
						});

						return uploader.open();
					}
				]
			]));

			return themes.get(1, 30).then(function (themesList) {
				themesDiv.appendChild(pages.elements.theme());
				themesList.forEach(function (theme) {
					return themesDiv.appendChild(pages.elements.theme(theme));
				});

				loader.hide();
				return themesDiv.style.display = '';
			}).catch(function (err) {
				let uploadError = pages.elements.uploadError();

				loader.hide();
				return menuBody.appendChild(uploadError);
			})
		},
		repo: function (menuBody) {
			document.title = 'yunNet. - ' + settings.lang.getValue('theme_repos');
			ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("theme_repos") : null;
		},
		editTheme: function (menuBody, internalData) {
			document.title = 'yunNet. - ' + settings.lang.getValue('edit_theme');
			ui.isMobile() ? nav_header_title.innerText = settings.lang.getValue("edit_theme") : null;

			let themeInfoDiv = document.createElement('div');
			themeInfoDiv.classList.add('card');
			themeInfoDiv.classList.add('full_section');
			menuBody.appendChild(themeInfoDiv);

			let themeTitle = pages.elements.createInputField(settings.lang.getValue('theme_title'), true).setType('text').maxLength(64);
			themeInfoDiv.appendChild(themeTitle);
			themeTitle.getInput().value = internalData.theme.data.title;

			let themeDescription = pages.elements.createInputField(settings.lang.getValue('desc'), true).setType('text').maxLength(128);
			themeInfoDiv.appendChild(themeDescription);
			themeDescription.getInput().value = internalData.theme.data.description;

			let privateMode = pages.elements.createChecker(settings.lang.getValue('private_theme'), function () {
				return true;
			}).setChecked(internalData.theme.settings.is_private);
			themeInfoDiv.appendChild(privateMode);

			if (internalData.theme.settings.is_default)
				privateMode.setDisabled(true);

			let loader = pages.elements.getLoader();
			let saveButton = pages.elements.createFAB(unt.Icon.SAVE, function (event) {
				themeTitle.getInput().classList.remove('wrong');
				themeDescription.getInput().classList.remove('wrong');

				if (themeTitle.getValue().isEmpty() || themeTitle.getValue().length > 64) return themeTitle.getInput().classList.add('wrong');
				if (themeDescription.getValue().isEmpty() || themeDescription.getValue().length > 128) return themeTitle.getInput().classList.add('wrong');

				saveButton.style.display = 'none';
				loader.style.display = '';

				privateMode.setDisabled(true);
				themeTitle.setDisabled(true);
				themeDescription.setDisabled(true);

				return themes.data.update(settings.users.current.user_id, internalData.theme.id, themeTitle.getValue().trim(), themeDescription.getValue().trim(), privateMode.checked()).then(function (result) {
					saveButton.style.display = '';
					loader.style.display = 'none';

					if (!internalData.theme.settings.is_default)
						privateMode.setDisabled(false);
					
					themeTitle.setDisabled(false);
					themeDescription.setDisabled(false);

					return unt.toast({html: settings.lang.getValue('saved')});
				}).catch(function (err) {
					saveButton.style.display = '';
					loader.style.display = 'none';

					if (!internalData.theme.settings.is_default)
						privateMode.setDisabled(false);
					
					themeTitle.setDisabled(false);
					themeDescription.setDisabled(false);

					return unt.toast({html: settings.lang.getValue('upload_error')});
				});
			});

			saveButton.style.textAlign = 'end';
			loader.style.display = 'none';
			loader.style.textAlign = 'end';

			saveButton.classList.remove('fixed-action-btn');
			loader.classList.remove('center');

			themeInfoDiv.appendChild(saveButton);
			themeInfoDiv.appendChild(loader);

			let editModes = pages.elements.createButton(null, null, null, null, [
				[
					unt.Icon.PALETTE,
					settings.lang.getValue('edit_css'),
					function () {
						pages.elements.loadingMode();

						return themes.data.getCode(internalData.theme.owner_id, internalData.theme.id, 'css').then(function (code) {
							pages.elements.loadingMode().getInstance().close();

							let codeEditor = themes.codeEditor.build();

							codeEditor.getUpPanel().addButton(unt.Icon.DOWNLOAD, function (event, button) {
								button.setLoading(true);

								return themes.data.export(internalData.theme.owner_id, internalData.theme.id).then(function () {
									button.setLoading(false);
								}).catch(function (error) {
									button.setLoading(false);

									return unt.toast({html: settings.lang.getValue('upload_error')});
								});
							}, 35);
							codeEditor.getUpPanel().addButton(unt.Icon.SAVE, function (event, button) {
								button.setLoading(true);

								let currentCode = codeEditor.getCurrentCode();
								return themes.data.code.update(internalData.theme.owner_id, internalData.theme.id, 'css', currentCode).then(function (response) {
									button.setLoading(false);

									return unt.toast({html: settings.lang.getValue('saved')});
								}).catch(function (error) {
									button.setLoading(false);

									let errorText = settings.lang.getValue('upload_error');
									if (error.errorMessage)
										errorText = error.errorMessage;

									return unt.toast({html: errorText});
								});
							}, 0);

							codeEditor.getDownPanel().addButton(unt.Icon.SETTINGS, function (event, button) {

							});
							codeEditor.getDownPanel().addButton(unt.Icon.PICTURE, function (event, button) {
								let uploader = pages.elements.fileUploader({
									onFileSelected: function (event, files, uploader) {
										uploader.setLoading(true);

										return uploads
											.getURL(uploads.type.IMAGE)
											.then(function (url) {
												return uploads
													.upload(url, files[0], function (event) {
														return codes.callbacks.uploadResolve(event, uploader);
													})
													.then(function (attachment) {
														uploader.setLoading(false);
														uploader.close();

														let doneUrl = attachment.photo.url.main;

														let winItem = pages.elements.createWindow();
														let content = winItem.getContent();

														winItem.getFooter().remove();

														let textDiv = document.createElement('div');
														content.appendChild(textDiv);
														textDiv.innerText = settings.lang.getValue('insert_into_code');

														let codeDiv = document.createElement('div');
														content.appendChild(codeDiv);

														codeDiv.style.marginTop = '15px';

														let codeText = document.createElement('textarea');
														codeText.setAttribute('readonly', 'true');

														codeText.classList.add('materialize-textarea');

														codeDiv.appendChild(codeText);
														codeText.innerText = '--unt-user-background: url(' + doneUrl + ');';

														unt.updateTextFields();
														unt.textareaAutoResize(codeText);
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

								return uploader.open();
							});	

							return document.body.insertAdjacentElement('afterbegin', codeEditor.setTitle(internalData.theme.data.title + ' (CSS)').setCode(code));
						}).catch(function (error) {
							pages.elements.loadingMode().getInstance().close();

							return unt.toast({html: settings.lang.getValue('upload_error')});							
						});
					}
				], [
					unt.Icon.DEV,
					settings.lang.getValue('edit_js'),
					function () {
						pages.elements.loadingMode();

						if (!settings.get().theming.js_allowed) {
							pages.elements.loadingMode().getInstance().close();

							return unt.toast({html: settings.lang.getValue('enable_themes_js')});
						}

						return themes.data.getCode(internalData.theme.owner_id, internalData.theme.id, 'js').then(function (code) {
							pages.elements.loadingMode().getInstance().close();

							let codeEditor = themes.codeEditor.build();
							codeEditor.getUpPanel().addButton(unt.Icon.DOWNLOAD, function (event, button) {
								return themes.data.export(internalData.theme.owner_id, internalData.theme.id).then(function () {
									button.setLoading(false);
								}).catch(function (error) {
									button.setLoading(false);

									return unt.toast({html: settings.lang.getValue('upload_error')});
								});
							}, 35);
							codeEditor.getUpPanel().addButton(unt.Icon.SAVE, function (event, button) {
								button.setLoading(true);

								let currentCode = codeEditor.getCurrentCode();
								return themes.data.code.update(internalData.theme.owner_id, internalData.theme.id, 'js', currentCode).then(function (response) {
									button.setLoading(false);

									return unt.toast({html: settings.lang.getValue('saved')});
								}).catch(function (error) {
									button.setLoading(false);

									let errorText = settings.lang.getValue('upload_error');
									if (error.errorMessage)
										errorText = error.errorMessage;

									return unt.toast({html: errorText});
								});
							}, 0);

							codeEditor.getDownPanel().addButton(unt.Icon.SETTINGS, function (event, button) {

							});

							return document.body.insertAdjacentElement('afterbegin', codeEditor.setTitle(internalData.theme.data.title + ' (JS)').setCode(code));
						}).catch(function (error) {
							pages.elements.loadingMode().getInstance().close();

							return unt.toast({html: settings.lang.getValue('upload_error')});							
						});
					}
				]
			]);

			let exportTheme = pages.elements.createButton(unt.Icon.DOWNLOAD, settings.lang.getValue('export_theme'), function () {
				pages.elements.loadingMode();

				return themes.data.export(internalData.theme.owner_id, internalData.theme.id).then(function () {
					return pages.elements.loadingMode().getInstance().close();
				}).catch(function (error) {
					return unt.toast({html: settings.lang.getValue('upload_error')});
				});
			});

			menuBody.appendChild(editModes);
			menuBody.appendChild(exportTheme);
		}
	},
}