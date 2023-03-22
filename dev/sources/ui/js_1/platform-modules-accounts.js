unt.modules.accountActions = new Object({
	restore: function (state, restoreDiv, cardDivForm, logoutButton) {
		cardDivForm.innerHTML = '';
		logoutButton.show();
		cardDivForm.style.textAlign = '-webkit-center';

		let headerMessageDiv = document.createElement('b');
		cardDivForm.appendChild(headerMessageDiv);

		let messageDiv = document.createElement('div');
		messageDiv.style.padding = '10px';
		cardDivForm.appendChild(messageDiv);

		let form = document.createElement('form');
		form.action = '/restore';
		form.method = 'POST';
		form.style.padding = '10px';
		form.addEventListener('submit', function (event) {
			return event.preventDefault();
		});
		cardDivForm.appendChild(form);
		headerMessageDiv.innerText = unt.settings.lang.getValue('restore_account');

		let continueButton = document.createElement('button');
		let emailInput, lnInput, passwordInput, repeatPasswordInput;

		if (state === 0) {
			messageDiv.innerText = unt.settings.lang.getValue('input_res_data');

			emailInput = unt.components.textField(unt.settings.lang.getValue('email'));
			emailInput.getInput().type = 'email';
			emailInput.getInput().name = 'email';
			form.appendChild(emailInput);
		}
		if (state === 1) {
			messageDiv.innerText = unt.settings.lang.getValue('input_res_fn');

			lnInput = unt.components.textField(unt.settings.lang.getValue('last_name'));
			lnInput.getInput().type = 'text';
			lnInput.getInput().name = 'last_name';
			form.appendChild(lnInput);
		}
		if (state === 2) {
			let urlInfo = new URLParser();
			let query;

			logoutButton.hide();
			continueButton.hide();
			form.hide();

			if ((query = urlInfo.getQueryValue('query')) !== '') {
				messageDiv.hide();
				headerMessageDiv.innerText = '';
				form.show();

				let loader = unt.components.loaderElement();
				form.appendChild(loader);

				return unt.tools.Request({
					url: '/restore',
					method: 'POST',
					data: (new POSTData()).append('action', 'check_query').append('query', query).build(),
					success: function (response) {
						form.hide();
						messageDiv.show();

						try {
							response = JSON.parse(response);
							if (response.error)
								return messageDiv.innerText = unt.settings.lang.getValue('link_is_bad');

							if (response.stage)
								return unt.modules.accountActions.restore(response.stage, restoreDiv, cardDivForm, logoutButton);

							messageDiv.innerText = unt.settings.lang.getValue('upload_error');
						} catch (e) {
							messageDiv.innerText = unt.settings.lang.getValue('upload_error');
						}
					},
					error: function () {
						form.hide();
						messageDiv.show();

						messageDiv.innerText = unt.settings.lang.getValue('upload_error');
					}
				});
			} else {
				headerMessageDiv.innerText = '';

				messageDiv.innerText = unt.settings.lang.getValue('email_sent');
			}
		}
		if (state === 3) {
			logoutButton.hide();
			headerMessageDiv.hide();

			messageDiv.innerText = unt.settings.lang.getValue('finish_data');

			passwordInput = unt.components.textField(unt.settings.lang.getValue('password'));
			repeatPasswordInput = unt.components.textField(unt.settings.lang.getValue('password2'));
			passwordInput.getInput().type = 'password';
			repeatPasswordInput.getInput().type = 'password';

			form.appendChild(passwordInput);
			form.appendChild(repeatPasswordInput);
		}
		if (state === 4) {
			logoutButton.hide();
			continueButton.hide();
			headerMessageDiv.hide();
			form.hide();

			messageDiv.innerText = unt.settings.lang.getValue('done') + '!';
			setTimeout(function () {
				return window.location.href = '/';
			}, 2000);
		}

		continueButton.classList.add('btn');
		continueButton.classList.add('btn-large');
		continueButton.classList.add('waves-effect');

		let continueTextDiv = document.createElement('div');
		continueButton.appendChild(continueTextDiv);
		continueTextDiv.innerText = unt.settings.lang.getValue('continue');

		let loaderElement = unt.components.loaderElement().setColor('white');
		continueButton.appendChild(loaderElement);
		loaderElement.hide();
		loaderElement.getElementsByTagName('svg')[0].style.marginTop = '20%';

		let inProcess = false;

		continueButton.type = 'submit';
		form.appendChild(continueButton);
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			if (inProcess) return;

			let postData = new POSTData();

			if (state === 0) {
				if (emailInput.getText().isEmpty())
					return emailInput.getInput().classList.add('wrong');
				else
					emailInput.getInput().classList.remove('wrong');

				postData.append('email', emailInput.getText());
			}
			if (state === 1) {
				if (lnInput.getText().isEmpty())
					return lnInput.getInput().classList.add('wrong');
				else
					lnInput.getInput().classList.remove('wrong');

				postData.append('last_name', lnInput.getText());
			}
			if (state === 3) {
				if (passwordInput.getText().isEmpty())
					return passwordInput.getInput().classList.add('wrong');
				else
					passwordInput.getInput().classList.remove('wrong');

				if (repeatPasswordInput.getText().isEmpty())
					return repeatPasswordInput.getInput().classList.add('wrong');
				else
					repeatPasswordInput.getInput().classList.remove('wrong');

				if (passwordInput.getText() !== repeatPasswordInput.getText())
					return unt.toast({html: unt.settings.lang.getValue('found_bad_password')});

				postData.append('password', passwordInput.getText());
				postData.append('repeat_password', repeatPasswordInput.getText());
			}

			inProcess = true;
			continueTextDiv.hide();
			loaderElement.show();

			return unt.tools.Request({
				url: '/restore',
				method: 'POST',
				data: postData.append('action', 'continue').build(),
				success: function (response) {
					inProcess = false;
					loaderElement.hide();
					continueTextDiv.show();

					try {
						response = JSON.parse(response);
						if (response.error)
							return headerMessageDiv.innerText = unt.settings.lang.getValue('upload_error');

						if (state === 0) {
							if (response.error_code === 2)
								return emailInput.getInput().classList.add('wrong');
							else
								emailInput.getInput().classList.remove('wrong');
						}
						if (state === 1) {
							if (response.error_code === 1) {
								lnInput.getInput().classList.add('wrong');

								if (response.error_message)
									return unt.toast({html: response.error_message});
								return;
							} else
								lnInput.getInput().classList.remove('wrong');
						}
						if (state === 3) {
							if (response.error_code === 5)
								return passwordInput.getInput().classList.add('wrong');
							else
								passwordInput.getInput().classList.remove('wrong');

							if (response.error_message)
								unt.toast({html: response.error_message});

							if (response.error_code === 6)
								return repeatPasswordInput.getInput().classList.add('wrong');
							else
								repeatPasswordInput.getInput().classList.remove('wrong');
						}

						if (response.stage)
							return unt.modules.accountActions.restore(response.stage, restoreDiv, cardDivForm, logoutButton);
					} catch (e) {
						return unt.toast({html: unt.settings.lang.getValue('upload_error')});
					}
				},
				error: function () {
					inProcess = false;

					loaderElement.hide();
					continueTextDiv.show();

					return unt.toast({html: unt.settings.lang.getValue('upload_error')});
				}
			});
		});
	},
	register: function (state, registerDiv, cardDivForm, logoutButton) {
		cardDivForm.innerHTML = '';
		logoutButton.show();
		cardDivForm.style.textAlign = '-webkit-center';

		let headerMessageDiv = document.createElement('b');
		cardDivForm.appendChild(headerMessageDiv);

		let messageDiv = document.createElement('div');
		messageDiv.style.padding = '10px';
		cardDivForm.appendChild(messageDiv);

		let form = document.createElement('form');
		form.action = '/register';
		form.method = 'POST';
		form.addEventListener('submit', function (event) {
			return event.preventDefault();
		});
		cardDivForm.appendChild(form);

		let firstNameInput, lastNameInput, emailInput, emailCodeInput, passwordInput, repeatPasswordInput, genderSelector, maleSelected, femaleSelected;
		form.style.padding = '10px';
		let continueButton = document.createElement('button');

		if (state === -1) {
			headerMessageDiv.innerText = unt.settings.lang.getValue('error_closed_register');
			messageDiv.innerText = unt.settings.lang.getValue('error_closed_register_message');
		}
		if (state === 0) {
			headerMessageDiv.innerText = '';
			messageDiv.innerText = unt.settings.lang.getValue('thanks_for_reg');

			firstNameInput = unt.components.textField(unt.settings.lang.getValue('first_name'));
			lastNameInput  = unt.components.textField(unt.settings.lang.getValue('last_name'));
			form.appendChild(firstNameInput);
			form.appendChild(lastNameInput);

			firstNameInput.getInput().setAttribute('name', 'first_name');
			lastNameInput.getInput().setAttribute('name', 'last_name');
			firstNameInput.getInput().addEventListener('input', function (event) {
				firstNameInput.setText(firstNameInput.getText().capitalize());
			});
			lastNameInput.getInput().addEventListener('input', function (event) {
				lastNameInput.setText(lastNameInput.getText().capitalize());
			});
		}
		if (state === 1) {
			messageDiv.innerText = unt.settings.lang.getValue('lets_welcome');

			emailInput = unt.components.textField(unt.settings.lang.getValue('email'));
			emailInput.getInput().type = 'email';
			form.appendChild(emailInput);
		}
		if (state === 2) {
			messageDiv.innerText = unt.settings.lang.getValue('please_activate');

			emailCodeInput = unt.components.textField(unt.settings.lang.getValue('code'));
			emailCodeInput.getInput().type = 'number';
			form.appendChild(emailCodeInput);
		}
		if (state === 3) {
			logoutButton.hide();

			messageDiv.innerText = unt.settings.lang.getValue('finish_data');

			passwordInput = unt.components.textField(unt.settings.lang.getValue('password'));
			repeatPasswordInput  = unt.components.textField(unt.settings.lang.getValue('password2'));
			form.appendChild(passwordInput);
			form.appendChild(repeatPasswordInput);

			passwordInput.getInput().type = 'password';
			repeatPasswordInput.getInput().type = 'password';

			form.appendChild(document.createElement('br'));

			genderSelector = document.createElement('form');
			genderSelector.action = '#';
			form.appendChild(genderSelector);

			form.appendChild(document.createElement('br'));

			let headerText = document.createElement('div');
			genderSelector.appendChild(headerText);
			headerText.innerText = unt.settings.lang.getValue('gen_sel') + ':';

			let maleItem = document.createElement('p');
			let feMaleItem = document.createElement('p');
			genderSelector.appendChild(maleItem);
			genderSelector.appendChild(feMaleItem);

			let maleLabel = document.createElement('label');
			let feMaleLabel = document.createElement('label');
			maleItem.appendChild(maleLabel);
			feMaleItem.appendChild(feMaleLabel);

			maleSelected = document.createElement('input');
			femaleSelected = document.createElement('input');
			maleLabel.appendChild(maleSelected);
			feMaleLabel.appendChild(femaleSelected);

			maleSelected.setAttribute('name', 'gender');
			maleSelected.type = 'radio';
			maleSelected.classList.add('with-gap');

			femaleSelected.setAttribute('name', 'gender');
			femaleSelected.type = 'radio';
			femaleSelected.classList.add('with-gap');

			let maleSpan = document.createElement('span');
			let feMaleSpan = document.createElement('span');

			maleLabel.appendChild(maleSpan);
			feMaleLabel.appendChild(feMaleSpan);
			maleSpan.innerText = unt.settings.lang.getValue('gen_sel_1');
			feMaleSpan.innerText = unt.settings.lang.getValue('gen_sel_2');
		}
		if (state === 4) {
			headerMessageDiv.hide();
			messageDiv.hide();

			logoutButton.hide();
			continueButton.hide();

			let loader = unt.components.loaderElement();
			form.appendChild(loader);

			setTimeout(function () {
				return continueButton.click();
			}, 5000);
		}
		if (state === 5) {
			headerMessageDiv.hide();
			messageDiv.hide();

			logoutButton.hide();
			continueButton.hide();

			let loader = unt.components.loaderElement();
			form.appendChild(loader);
			return window.location.href = '/';
		}

		continueButton.classList.add('btn');
		continueButton.classList.add('btn-large');
		continueButton.classList.add('waves-effect');

		let continueTextDiv = document.createElement('div');
		continueButton.appendChild(continueTextDiv);
		continueTextDiv.innerText = unt.settings.lang.getValue('continue');

		let loaderElement = unt.components.loaderElement().setColor('white');
		continueButton.appendChild(loaderElement);
		loaderElement.hide();
		loaderElement.getElementsByTagName('svg')[0].style.marginTop = '20%';

		let inProcess = false;

		continueButton.type = 'submit';
		form.appendChild(continueButton);
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			if (inProcess) return;

			let postData = new POSTData();

			if (state === 0) {
				if (firstNameInput.getText().isEmpty() || firstNameInput.getText().match(/[^a-zA-Zа-яА-ЯёЁ'-]/ui))
					return firstNameInput.getInput().classList.add('wrong');
				else
					 firstNameInput.getInput().classList.remove('wrong');

				if (lastNameInput.getText().isEmpty() || lastNameInput.getText().match(/[^a-zA-Zа-яА-ЯёЁ'-]/ui))
					return lastNameInput.getInput().classList.add('wrong');
				else
					lastNameInput.getInput().classList.remove('wrong');

				postData.append('first_name', firstNameInput.getText()).append('last_name', lastNameInput.getText());
			}
			if (state === 1) {
				if (emailInput.getText().isEmpty() || !emailInput.getText().match(/^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i))
					return emailInput.classList.add('wrong');
				else
					emailInput.classList.remove('wrong');

				postData.append('email', emailInput.getText());
			}
			if (state === 2) {
				if (Number(emailCodeInput.getText()) < 100000 || Number(emailCodeInput.getText()) > 999999)
					return emailCodeInput.getInput().classList.add('wrong');
				else
					emailCodeInput.getInput().classList.remove('wrong');

				postData.append('email_code', emailCodeInput.getText());
			}
			if (state === 3) {
				if (passwordInput.getText() !== repeatPasswordInput.getText())
					return unt.toast({html: unt.settings.lang.getValue('found_bad_password')});

				let genderSelected = femaleSelected.checked ? 2 : 1;

				postData.append('password', passwordInput.getText()).append('repeat_password', repeatPasswordInput.getText()).append('gender', genderSelected);
			}

			inProcess = true;
			continueTextDiv.hide();
			loaderElement.show();

			return unt.tools.Request({
				url: '/register',
				method: 'POST',
				data: postData.append('action', 'continue').build(),
				success: function (response) {
					inProcess = false;

					loaderElement.hide();
					continueTextDiv.show();

					try {
						response = JSON.parse(response);
						if (response.error)
							return headerMessageDiv.innerText = unt.settings.lang.getValue('upload_error');

						if (state === 0) {
							if (response.error_code === 0)
								return firstNameInput.getInput().classList.add('wrong');
							else
								firstNameInput.getInput().classList.remove('wrong');

							if (response.error_code === 1)
								return lastNameInput.getInput().classList.add('wrong');
							else
								lastNameInput.getInput().classList.remove('wrong');
						}
						if (state === 1) {
							if (response.error_code === 2) {
								emailInput.getInput().classList.add('wrong');

								if (response.error_message)
									return unt.toast({html: response.error_message});
								return;
							} else
								emailInput.getInput().classList.remove('wrong');
						}
						if (state === 3) {
							if (response.error_code === 5)
								return passwordInput.classList.add('wrong');
							else
								passwordInput.classList.remove('wrong');

							if (response.error_code === 6)
								return repeatPasswordInput.classList.add('wrong');
							else
								repeatPasswordInput.classList.remove('wrong');
						}
						if (state === 5) {
							return window.location.href = '/';
						}

						if (response.stage)
							return unt.modules.accountActions.register(response.stage, registerDiv, cardDivForm, logoutButton);
					} catch (e) {
						return unt.toast({html: unt.settings.lang.getValue('upload_error')});
					}
				},
				error: function () {
					inProcess = false;

					loaderElement.hide();
					continueTextDiv.show();

					return unt.toast({html: unt.settings.lang.getValue('upload_error')});
				}
			});
		});
	}
});