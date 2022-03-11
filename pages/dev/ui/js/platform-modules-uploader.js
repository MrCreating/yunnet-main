unt.modules.uploads = {
	uploader: function () {
		return new Promise(function (resolve, reject) {
			let win = unt.components.windows.createImportantWindow({
				cloaseAble: true,
				onClose: function () {
					return resolve(null);
				},
				title: unt.settings.lang.getValue('select_a_file')
			});

			let menu = win.getMenu();

			let container = document.createElement('div');
			container.style.padding = '20px';
			menu.appendChild(container);

			let fileUploadForm = document.createElement('form');
			container.appendChild(fileUploadForm);

			let fileDiv = document.createElement('div');
			fileUploadForm.appendChild(fileDiv);
			fileDiv.classList = ['file-field input-field'];

			let selectButton = document.createElement('div');
			selectButton.classList.add('btn');
			let selectSpan = document.createElement('span');
			selectSpan.innerText = unt.settings.lang.getValue('select_a_file');
			selectButton.appendChild(selectSpan);
			let inputFile = document.createElement('input');
			inputFile.type = 'file';
			inputFile.addEventListener('input', function () {
				if (win.afterSelected) win.afterSelected(this.files);
			});
			inputFile.setAttribute('accept', 'image/*');
			selectButton.appendChild(inputFile);
			fileDiv.appendChild(selectButton);

			let filePathWrapper = document.createElement('div');
			filePathWrapper.classList = ['file-path-wrapper'];
			fileDiv.appendChild(filePathWrapper);
			let inputFilePath = document.createElement('input');
			inputFilePath.classList = ['file-path validate'];
			inputFilePath.type = 'text';
			filePathWrapper.appendChild(inputFilePath);

			let attachmentPreviewDiv = document.createElement('div');
			container.appendChild(attachmentPreviewDiv);

			let startUploadDiv = document.createElement('div');
			startUploadDiv.style.width = '100%';
			startUploadDiv.style.textAlign = 'end';
			container.appendChild(startUploadDiv);

			let continueButton = document.createElement('a');
			continueButton.addEventListener('click', function () {
				win.close();
			});
			continueButton.classList = ['btn waves-effect waves-light'];
			startUploadDiv.appendChild(continueButton);

			let buttonTextDib = document.createElement('div');
			continueButton.appendChild(buttonTextDib);
			buttonTextDib.innerText = unt.settings.lang.getValue('continue');

			let uploadProgressBar = unt.components.loaderElement(true);
			uploadProgressBar.setProgress(0);
			uploadProgressBar.style.marginTop = '40%';
			uploadProgressBar.setArea(20);
			uploadProgressBar.setColor('white');
			uploadProgressBar.hide();
			continueButton.appendChild(uploadProgressBar);

			win.show();

			return resolve({
				setPreviewAttachment: function (attachment) {
					if (attachment.type === 'photo' && attachment.photo) {
						let img = document.createElement('img');
						attachmentPreviewDiv.appendChild(img);
						img.src = attachment.photo.url.main;

						console.log(attachment);
					}

					return this;
				},
				selectFile: function (callback) {
					win.afterSelected = callback;
					return this;
				},
				finish: function (callback) {
					win.finish = callback;
					return this;
				},
				upload: function (file, type = 'image') {
					return new Promise(function (resolve, reject) {
						continueButton.classList.add('disabled');
						buttonTextDib.hide();
						uploadProgressBar.show();
						win.setCloseAble(false);
						inputFile.setAttribute('disabled', 'true');

						function close (result) {
							continueButton.classList.remove('disabled');
							buttonTextDib.show();
							uploadProgressBar.hide();
							win.setCloseAble(true);
							inputFile.removeAttribute('disabled');
							result();
						}

						return unt.tools.Request({
							url: '/upload?action=get&type=' + type,
							method: 'POST',
							success: function (response) {
								try {
									response = JSON.parse(response);
									if (response.error) return close(reject);

									let url = response.url;
									let x = _xmlHttpGet();

									let data = new POSTData();
									data.append('attachment', file);

									x.onreadystatechange = function () {
										if (x.readyState !== 4) return;

										let response = x.responseText;
										try {
											response = JSON.parse(response);
											if (response.error) return close(reject);

											return close(function () {
												resolve(response);
											});
										} catch (e) {
											return close(reject);
										}
									}

									x.upload.onprogress = function () {
										let done = parseInt((event.loaded / event.total) * 100);

										uploadProgressBar.setProgress(done);
									}

									x.open('POST', url);
									return x.send(data.build());
								} catch (e) {
									return close(reject);
								}
							},
							error: function () {
								return close(reject);
							}
						});

						/*let i = 0;
						let t = setInterval(function () {
							if (i >= 10) {
								continueButton.classList.remove('disabled');
								buttonTextDib.show();
								uploadProgressBar.hide();
								win.setCloseAble(true);
								inputFile.removeAttribute('disabled');

								return clearInterval(t);
							}

							uploadProgressBar.setProgress(i * 10);
							i++;
						}, 2000)*/
					});
				}
			});
		});
	}
};