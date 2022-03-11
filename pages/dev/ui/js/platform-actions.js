unt.actions.wall = new Object({
	currentId: null,
	postsSaved: {},
	getPosts: function (wallId, offset = 0, count = 20) {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/' + (wallId > 0 ? ("id" + wallId) : ("bot" + (wallId * -1))),
				method: 'POST',
				data: (new POSTData()).append('action', 'get_posts').append('offset', Number(offset) || 0).append('count', Number(count) || 20).build(),
				success: function (result) {
					try {
						result = JSON.parse(result);
						if (result.error)
							return reject(new TypeError('Unable to fetch posts'));

						return resolve(result);
					} catch (e) {
						return reject(e);
					}
				},
				error: function (error) {
					return reject(error);
				}
			});
		});
	},
	getPostById: function (wallId, postId) {
		return unt.tools.Request({
			url: '/wall' + Number(wallId) + '_' + Number(postId),
			method: 'POST',
			data: (new POSTData()).append('action', 'get_info').build(),
			success: function (result) {
				try {
					result = JSON.parse(result);
					if (result.error)
						return reject(new TypeError('Unable to fetch post'));

					return resolve(result);
				} catch (e) {
					return reject(e);
				}
			},
			error: function (error) {
				return reject(error);
			}
		});
	},
	getNews: function (offset = 0, count = 30) {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_posts').append('offset', Number(offset) || 0).append('count', Number(count) || 30).build(),
				success: function (result) {
					try {
						result = JSON.parse(result);
						if (result.error)
							return reject(new TypeError('Unable to fetch news'));

						return resolve(result);
					} catch (e) {
						return reject(e);
					}
				},
				error: function (error) {
					return reject(error);
				}
			});
		});
	}
});

unt.actions.friends = new Object({
	get: function (user_id) {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/flex',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_friends').append('section', 'friends').append('user_id', user_id).build(),
				success: function (response) {
					try {
						response = JSON.parse(response);
						if (response.error)
							throw new Error();

						return resolve(response);
					} catch (e) {
						reject(e);
					}
				},
				error: function () {
					reject();
				}
			});
		});
	},
	getSubscribers: function (user_id) {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/flex',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_friends').append('section', 'subscribers').append('user_id', user_id).build(),
				success: function (response) {
					try {
						response = JSON.parse(response);
						if (response.error)
							throw new Error();

						return resolve(response);
					} catch (e) {
						reject(e);
					}
				},
				error: function () {
					reject();
				}
			});
		});
	},
	getOutcoming: function () {
		return new Promise(function (resolve, reject) {
			return unt.tools.Request({
				url: '/flex',
				method: 'POST',
				data: (new POSTData()).append('action', 'get_friends').append('section', 'outcoming').append('user_id', unt.settings.users.current.user_id).build(),
				success: function (response) {
					try {
						response = JSON.parse(response);
						if (response.error)
							throw new Error();

						return resolve(response);
					} catch (e) {
						reject(e);
					}
				},
				error: function () {
					reject();
				}
			});
		});
	}
});