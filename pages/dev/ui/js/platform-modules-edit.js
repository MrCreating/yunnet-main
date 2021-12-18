unt.modules.edit = {
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
			formContainer.appendChild(userImage);
			userImage.src = unt.settings.users.current.photo_url;
			userImage.addEventListener('click', function () {
				return unt.modules.uploads.uploader().then(function (files) {
					unt.modules.upload(files[0]).then(function (attachment) {
						console.log(attachment);
					});
				});
			});

			let credentialsForm = document.createElement('form');
			formContainer.appendChild(credentialsForm);
			credentialsForm.style.width = '100%';
			credentialsForm.style.marginLeft = '15px';
			credentialsForm.classList.add('halign-wrapper');

			let firstName = unt.components.textField(unt.settings.lang.getValue('first_name')).setText(unt.settings.users.current.first_name);
			let lastName = unt.components.textField(unt.settings.lang.getValue('last_name')).setText(unt.settings.users.current.last_name);

			credentialsForm.appendChild(firstName);
			credentialsForm.appendChild(lastName);
		},
		contacts: function (internalData, sectionContainer) {

		}
	}
};