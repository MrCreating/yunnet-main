unt.modules.settings = {
	main: function (menu) {
		document.title = unt.settings.lang.getValue('main');
	},
	notifications: function (menu) {
		document.title = unt.settings.lang.getValue('notifications');

		let pushGroup = unt.components.switchCardButtonsGroup()
						   .addCardButton(unt.icons.notifications, unt.settings.lang.getValue('notifications'), function (event, inp, checked) {
						   		inp.disable();

						   		return unt.settings.notifications.toggle().then(function () {
						   			inp.enable();
						   		}).catch(function () {
						   			inp.enable();
						   			inp.setChecked(unt.settings.current.push.notifications);
						   			return unt.toast({html: unt.settings.lang.getValue('upload_error')});
						   		});
						   })
						   .addCardButton(unt.icons.sound, unt.settings.lang.getValue('sound'), function (event, inp, checked) {
						   		inp.disable();

						   		return unt.settings.sound.toggle().then(function () {
						   			inp.enable();
						   		}).catch(function () {
						   			inp.enable();
						   			inp.setChecked(unt.settings.current.push.sound);
						   			return unt.toast({html: unt.settings.lang.getValue('upload_error')});
						   		});
						   })

		pushGroup.getSwitchCardButton(0).setChecked(unt.settings.current.push.notifications);
		pushGroup.getSwitchCardButton(1).setChecked(unt.settings.current.push.sound);

		menu.appendChild(pushGroup);
	},
	privacy: function (menu) {
		document.title = unt.settings.lang.getValue('privacy');

		menu.appendChild(unt.components.cardButtonsGroup()
							.addCardButton(unt.icons.messagesOk, unt.settings.lang.getValue('messages_privacy'), function () {
								return unt.settings.managers.privacyManager(1);
							})
							.addCardButton(unt.icons.wallPost, unt.settings.lang.getValue('wall_privacy'), function () {
								return unt.settings.managers.privacyManager(2);
							})
							.addCardButton(unt.icons.comments, unt.settings.lang.getValue('who_can_comment_my_posts'), function () {
								return unt.settings.managers.privacyManager(4);
							}));

		menu.appendChild(unt.components.cardButtonsGroup()
							.addCardButton(unt.icons.addPerson, unt.settings.lang.getValue('chat_privacy'), function () {
								return unt.settings.managers.privacyManager(3);
							}));

		menu.appendChild(unt.components.switchCardButton(unt.icons.lock, unt.settings.lang.getValue('closed_profile'), function (event, button) {
			button.setChecked(unt.settings.current.account.is_closed);
			button.disable();

			if (unt.settings.current.account.is_closed) {
				return unt.settings.users.current.profile.toggleClose().then(function (isClsoed) {
					button.enable();
					button.setChecked(isClsoed);
				});
			} else {
				return unt.actions.dialog(unt.settings.lang.getValue('closed_profile'), unt.settings.lang.getValue('closed_profile_attention'), false, !unt.tools.isMobile()).then(function (response) {
					if (response)
						return unt.settings.users.current.profile.toggleClose().then(function (isClsoed) {
							button.enable();
							button.setChecked(isClsoed);
						});
					else
						button.enable();
				})
			}
		}).setChecked(unt.settings.current.account.is_closed));
	},
	security: function (menu) {
		document.title = unt.settings.lang.getValue('security');
	},
	blacklist: function (menu) {
		document.title = unt.settings.lang.getValue('blacklist');

		let loader = unt.components.loaderElement();
		let errorDiv = unt.components.alertBanner(unt.icons.failed, unt.settings.lang.getValue('upload_error'), unt.settings.lang.getValue('unknown_error'));
		let emptyDiv = unt.components.alertBanner(unt.icons.list, unt.settings.lang.getValue('blacklist_empty'), unt.settings.lang.getValue('blacklist_empty_text'));
		let usersInfoDiv = document.createElement('div');

		loader.classList.add('center');
		loader.style.marginTop = '15px';

		errorDiv.hide();
		emptyDiv.hide();
		usersInfoDiv.hide();
		loader.hide();

		menu.appendChild(usersInfoDiv);
		menu.appendChild(loader);
		menu.appendChild(emptyDiv);
		menu.appendChild(errorDiv);

		function load (page = 1) {
			loader.show();

			return unt.settings.blacklist.get().then(function (list) {
				loader.hide();

				if (list.length <= 0 && page === 1)
					return emptyDiv.show();

				list.forEach(function (user) {
					usersInfoDiv.appendChild(unt.components.user(user));
				});

				errorDiv.hide();
				emptyDiv.hide();
				usersInfoDiv.show();
			}).catch(function () {
				loader.hide();
				errorDiv.show();
				usersInfoDiv.hide();
				emptyDiv.hide();
			});
		}

		load();
	},
	accounts: function (menu) {
		document.title = unt.settings.lang.getValue('accounts');
	},
	theming: function (menu) {
		document.title = unt.settings.lang.getValue('theming');

		let mainThemingGroup = unt.components.switchCardButtonsGroup()
								  .addCardButton(unt.icons.palette, unt.settings.lang.getValue('use_new_design'), function (event, input) {
								  	input.setChecked(unt.settings.current.theming.new_design);

								  	unt.actions.dialog('', unt.settings.lang.getValue('roll_back_to_old_design'), false, true).then(function (response) {
								  		if (response) {
								  			return unt.tools.Request({
								  				url: '/settings',
								  				method: 'POST',
								  				data: (new POSTData()).append('action', 'toggle_new_design').build(),
								  				success: function () {
								  					document.body.innerHTML = '';
								  					unt.toast({html: 'Redirecting...'});
								  					setTimeout(function () {
								  						return window.location.reload();
								  					}, 2000);
								  				},
								  				error: function () {
								  					return unt.toast({html: unt.settings.lang.getValue('upload_error')});
								  				}
								  			});
								  		}
								  	})
								  })

		mainThemingGroup.getSwitchCardButton(0).setChecked(unt.settings.current.theming.new_design);

		menu.appendChild(mainThemingGroup);
	},
	about: function (menu) {
		document.title = unt.settings.lang.getValue('about');
	}
};