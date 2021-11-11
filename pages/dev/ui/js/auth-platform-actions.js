unt.dev = new Object({
	apps: new Object({
		getById: function (app_id) {
			return new Promise(function (resolve, reject) {
				return unt.tools.Request({
					url: '/flex',
					data: (new POSTData()).append('action', 'get_app_by_id').append('app_id', app_id || 0).build(),
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);

						if (response.error) return reject(new TypeError('Unable to fetch app info.'));

						return resolve(response);
					}
				});
			});
		}
	})
});