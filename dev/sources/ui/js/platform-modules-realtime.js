(unt.modules ? unt.modules : unt.modules = {}).realtime = {
	lastEventId: 0,
	currentUrl: null,
	handlers: [],
	addHandler: function (callback) {
		this.handlers.push(callback);
		return this.handlers.length;
	},
	removeHandler (index) {
		return this.handlers.splice(index, 1)[0];
	},
	connect: function () {
		return new Promise (function (resolve, reject) {
			if (!unt.settings.users.current) return reject(new Error('Failed to LP auth'));

			return unt.tools.Request({
				url: window.location.host.match(/localhost/) ? 'http://lp.localhost' : 'https://lp.yunnet.ru',
				method: 'GET',
				withCredentials: true,
				success: function (response) {
					try {
						response = JSON.parse(response);

						if (Array.isArray(response))
							throw new Error('Failed to LP auth');

						if (response.owner_id !== unt.settings.users.current.user_id)
							throw new Error('Failed to LP validation');

						let url = response.url;
						
						unt.modules.realtime.currentUrl = url;
						return resolve();
					} catch (e) {
						return reject(new Error('Unable to establish connection'));
					}
				},
				error: function (err) {
					return reject(err);
				}
			});
		});
	},
	listen: function (callback) {
		let o = this;

		return new Promise(function (resolve, reject) {
			if (!unt.modules.realtime.currentUrl)
				return reject(new Error('First, connect to LP'));

			return resolve(unt.tools.Request({
				url: unt.modules.realtime.currentUrl + '&last_event_id=' + unt.modules.realtime.lastEventId,
				success: function (response) {
					try {
						response = JSON.parse(response);

						if (response.error)
							return reject(new Error('LP errpr.'));

						response.last_event_id ? unt.modules.realtime.last_event_id = response.last_event_id : null;

						o.handlers.forEach(function (callback) {
							if (typeof callback === 'function')
								callback(response);
						});

						return unt.modules.realtime.listen(callback).then(resolve).catch(reject);
					} catch (e) {
						return reject(e);
					}
				},
				error: function () {
					return unt.modules.realtime.listen(callback).then(resolve).catch(reject);
				}
			}));
		});
	}
}