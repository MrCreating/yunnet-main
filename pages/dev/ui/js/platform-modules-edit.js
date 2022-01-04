unt.modules.edit = {
	flags: {
		inEdit: false
	},
	pages: {
		main: function (internalData, sectionContainer) {
			let cardMainElement = document.createElement('div');
			cardMainElement.classList.add('card');
			sectionContainer.appendChild(cardMainElement);

			let formContainer = document.createElement('div');
			formContainer.classList.add('valign-wrapper');
			formContainer.style.padding = '15px';
			formContainer.style.width = '100%';
			cardMainElement.appendChild(formContainer);

			let userImage = document.createElement('img');
			userImage.classList.add('circle');
			userImage.style.cursor = 'pointer';
			userImage.width = userImage.heigth = 96;
			userImage.style.marginRight = '15px';
			formContainer.appendChild(userImage);
			userImage.src = unt.settings.users.current.photo_url;
			userImage.addEventListener('click', function () {
				return unt.modules.uploads.uploader().then(function (uploader) {
					return uploader.selectFile(function (files) {
						if (files == null) return;

						uploader.upload(files[0]).then(function (attachment) {
							uploader.setPreviewAttachment(attachment);
						});
					}).finish(function (attachment) {
						
					});
				});
			});

			let credentialsForm = document.createElement('form');
			formContainer.appendChild(credentialsForm);
			credentialsForm.style.width = '100%';
			credentialsForm.classList.add('halign-wrapper');

			let firstName = unt.components.textField(unt.settings.lang.getValue('first_name')).setText(unt.settings.users.current.first_name);
			let lastName = unt.components.textField(unt.settings.lang.getValue('last_name')).setText(unt.settings.users.current.last_name);

			credentialsForm.appendChild(firstName);
			credentialsForm.appendChild(lastName);

			let saveButton = unt.components.button(unt.settings.lang.getValue('save'), function () {
				if (firstName.getText() === unt.settings.users.current.first_name && lastName.getText() === unt.settings.users.current.lastName)
					return unt.toast({html: unt.settings.lang.getValue('saved')});

				saveButton.setText(unt.settings.lang.getValue('loading'));
				firstName.disable();
				lastName.disable();
				saveButton.disable();

				unt.modules.edit.flags.inEdit = true;
				unt.tools.Request({
					url: '/edit',
					method: 'POST',
					data: (new POSTData()).append('action', 'save').append('first_name', firstName.getText()).append('last_name', lastName.getText()).build(),
					success: function (response) {
						saveButton.setText(unt.settings.lang.getValue('save'));
						firstName.enable();
						lastName.enable();
						saveButton.enable();

						unt.modules.edit.flags.inEdit = false;
						try {
							response = JSON.parse(response);
							if (response.message)
								return unt.toast({html: response.message});
							else {
								unt.settings.users.current.first_name = firstName.getText();
								unt.settings.users.current.last_name = lastName.getText();

								return unt.toast({html: unt.settings.lang.getValue('saved')});
							}
						} catch (e) {
							unt.toast({html: unt.settings.lang.getValue('upload_error')});
						}
					},
					error: function () {
						saveButton.setText(unt.settings.lang.getValue('save'));
						firstName.enable();
						lastName.enable();
						saveButton.enable();

						unt.modules.edit.flags.inEdit = false;
						unt.toast({html: unt.settings.lang.getValue('upload_error')});
					}
				});
			});
			credentialsForm.appendChild(saveButton);

			if (unt.modules.edit.flags.inEdit) {
				saveButton.setText(unt.settings.lang.getValue('loading'));
				firstName.disable();
				lastName.disable();
				saveButton.disable();
			}
		},
		contacts: function (internalData, sectionContainer) {
			let cardMainElement = document.createElement('div');
			cardMainElement.classList.add('card');
			sectionContainer.appendChild(cardMainElement);

			let formContainer = document.createElement('div');
			formContainer.classList.add('valign-wrapper');
			formContainer.style.padding = '15px';
			formContainer.style.width = '100%';
			cardMainElement.appendChild(formContainer);

			let credentialsForm = document.createElement('form');
			formContainer.appendChild(credentialsForm);
			credentialsForm.style.width = '100%';
			credentialsForm.classList.add('halign-wrapper');

			let screenName = unt.components.textField(unt.settings.lang.getValue('screen_name')).setText(unt.settings.users.current.screen_name ? unt.settings.users.current.screen_name : ('id' + unt.settings.users.current.user_id));
			credentialsForm.appendChild(screenName);

			let saveButton = unt.components.button(unt.settings.lang.getValue('save'), function () {
				if (screenName.getText() === unt.settings.users.current.screen_name)
					return unt.toast({html: unt.settings.lang.getValue('saved')});

				saveButton.setText(unt.settings.lang.getValue('loading'));
				screenName.disable();
				saveButton.disable();

				unt.modules.edit.flags.inEdit = true;
				unt.tools.Request({
					url: '/edit',
					method: 'POST',
					data: (new POSTData()).append('action', 'save').append('screen_name', screenName.getText()).build(),
					success: function (response) {
						saveButton.setText(unt.settings.lang.getValue('save'));
						screenName.enable();
						saveButton.enable();

						unt.modules.edit.flags.inEdit = false;
						try {
							response = JSON.parse(response);
							if (response.message)
								return unt.toast({html: response.message});
							else {
								unt.settings.users.current.screen_name = screenName.getText();
								return unt.toast({html: unt.settings.lang.getValue('saved')});
							}
						} catch (e) {
							unt.toast({html: unt.settings.lang.getValue('upload_error')});
						}
					},
					error: function () {
						saveButton.setText(unt.settings.lang.getValue('save'));
						screenName.enable();
						saveButton.enable();

						unt.modules.edit.flags.inEdit = false;
						unt.toast({html: unt.settings.lang.getValue('upload_error')});
					}
				});
			});
			credentialsForm.appendChild(saveButton);
		}
	}
};