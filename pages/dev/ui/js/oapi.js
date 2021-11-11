class Unt {
	constructor () {
		throw new TypeError('Not allowed to construct this class');
	}

	static Auth () {
		return new (class Auth {
			#currentParams = {
				app_id: 0,
				permissions: [1, 2, 3, 4],
				access_key: ''
			};

			#initialized = false;

			init (params) {
				if (this.#initialized)
					return this;

				if (typeof params !== "object")
					throw new TypeError("Params is not a valid object");

				for (let key in params) {
					if (this.#currentParams.hasOwnProperty(key))
						this.#currentParams[key] = params[key];
				}

				for (let key in this.$currentParams) {
					if (key === 'app_id') {
						if (isNaN(this.#currentParams[key]))
							throw new TypeError('App id must be an integer');
					}
					if (key === 'permissions') {
						let resultedPermissions = [];
						if (this.#currentParams.permissions.length > 4)
							this.#currentParams.permissions.length = 4;

						this.#currentParams.permissions.forEach(function (permission, index) {
							if (index > 4) return;

							if (isNaN(Number(permission))) return;

							if (Number(permission) < 1 || Number(permission) > 4) return;

							resultedPermissions.push(Number(permission));
						})

						this.#currentParams.permissions = resultedPermissions;
					}
					if (key === 'access_key')
					{
						if (String(key).length !== 75)
							throw new TypeError('Access key is invalid');
					}
				}

				this.#initialized = true;

				if (this.isLogged()) {
					function callAPIMethod (methodName, params = {}) {
						return new Promise(function (resolve, reject) {
							let data = new FormData();
							let token = localStorage.getItem('unt.token.token_' + localStorage.getItem('unt.user.current_id'));

							if (!(typeof params === 'object'))
								return reject(new TypeError('API params must be an object'));

							for (let key in params) {
								data.append(key, params[key]);
							}

							let x = new XMLHttpRequest();
							x.open('POST', 'https://api.yunnet.ru/' + methodName + '?key=' + token);

							x.onreadystatechange = function () {
								if (x.readyState !== 4) return;

								let response = JSON.parse(x.responseText);
								if (response.error) {
									let error = new Error('[' + response.error.error_code + '] ' + response.error.error_message);

									error.errorCode = response.error.error_code;
									error.errorMessage = response.error.error_message;
									error.errorInfo = response.error;
									error.paramsInfo = response.params;

									return reject(error);
								}

								return resolve(response);
							}

							return x.send(data);
						});
					}

					if ('Proxy' in window) {
						this.API = new Proxy({}, {
							get: function (target, name) {
								return new Proxy({firstPart: name}, {
									get: function (target, secondName) {
										return (function (params) {
											return callAPIMethod(target.firstPart + '.' + secondName, params);
										})
									}
								})
							}
						});
					} else {
						this.API = {
							callMethod: callAPIMethod
						};
					}
				}

				return this;
			}

			isLogged () {
				if (!this.#initialized)
					return reject(new TypeError('Auth must be initialized at first.'));

				let token = localStorage.getItem('unt.token.token_' + localStorage.getItem('unt.user.current_id'));

				if (token)
					return true;

				return false;
			}

			getCurrentToken () {
				let authClass = this;

				return new Promise(function (resolve, reject) {
					if (!authClass.#initialized)
						return reject(new TypeError('Auth must be initialized at first.'));

					let token = localStorage.getItem('unt.token.token_' + localStorage.getItem('unt.user.current_id'));
					if (token) {
						return resolve(token);
					} else {
						return authClass.createToken().then(function (result) {
							return resolve(result.getToken());
						}).catch(reject);
					}
				})
			}

			endSession () {
				let thisClass = this;

				if (!thisClass.#initialized)
					return reject(new TypeError('Auth must be initialized at first.'));

				localStorage.clear();

				return true;
			}

			getCurrentUser () {
				if (!this.#initialized)
					return reject(new TypeError('Auth must be initialized at first.'));

				return JSON.parse(localStorage.getItem('unt.token.user_' + localStorage.getItem('unt.user.current_id')));
			}

			createToken () {
				let thisClass = this;
				let permissions = thisClass.#currentParams.permissions;

				return new Promise(function (resolve, reject) {
					if (!thisClass.#initialized)
						return reject(new TypeError('Auth must be initialized at first.'));

					let currentWindow = window.open('https://auth.yunnet.ru/?returns=' + encodeURIComponent(window.location.href) + '&permissions=' + permissions.join(',') + '&app_id=' + Number(thisClass.#currentParams.app_id), 
						'yunNet.', 
						'resizeable,status,location=no,postwindow,width=' + screen.availWidth + ',height=' + screen.availHeight
					);

					if (!currentWindow) {
						let attemptsCount = 3;

						let interval = setInterval(function () {
							currentWindow = window.open('https://auth.yunnet.ru/?returns=' + encodeURIComponent(window.location.href) + '&permissions=' + permissions.join(',') + '&app_id=' + Number(thisClass.#currentParams.app_id), 
								'yunNet.', 
								'resizeable,status,location=no,postwindow,width=' + screen.availWidth + ',height=' + screen.availHeight
							);

							if (!currentWindow)
								attemptsCount -= 1;
							if (attemptsCount <= 0) {
								clearInterval(interval);

								return reject(new TypeError('Unable to start window auth'));
							}

							if (currentWindow)
								continueInit();
						}, 1000);
					} else {
						continueInit();
					}

					function continueInit () {
						let timer = setInterval(function() {
					    	if (currentWindow.closed) {
					        	clearInterval(timer);

					        	let error = new TypeError('Closed auth window');
					        	error.code = -2;

					        	return reject(error);
					    	}
						}, 500);

						window.addEventListener('message', function handlerMessagesOfAuth (event) {
							clearInterval(timer);

							if (event.data.withOutTimeout) {
								currentWindow.close();
							} else {
								setTimeout(function () {
									return currentWindow.close();
								}, 1000);
							}

							window.removeEventListener('message', handlerMessagesOfAuth);
							let result = event.data;
							let status = result.status;

							if (status === -1) {
								let error = new TypeError('Denied auth access');
					        	error.code = -1;
					      		
					      		return reject(error);
							}
							if (status === 1) {
								if ('localStorage' in window) {
									localStorage.setItem(('unt.token.token_' + (event.data.user.account_type === 'user' ? event.data.user.user_id : event.data.user.bot_id * -1)), event.data.token);
									localStorage.setItem(('unt.token.id_' + (event.data.user.account_type === 'user' ? event.data.user.user_id : event.data.user.bot_id * -1)), event.data.id);
									localStorage.setItem(('unt.token.user_' + (event.data.user.account_type === 'user' ? event.data.user.user_id : event.data.user.bot_id * -1)), JSON.stringify(event.data.user));
									localStorage.setItem(('unt.user.current_id'), (event.data.user.account_type === 'user' ? event.data.user.user_id : event.data.user.bot_id * -1));
								}

								let result = new (class AuthResult {
									getToken () {
										return event.data.token;
									}

									getTokenId () {
										return event.data.id;
									}

									getUser () {
										return event.data.user;
									}
								})();

								return resolve(result);
							}
						});
					}
				})
			}
		})
	}
}