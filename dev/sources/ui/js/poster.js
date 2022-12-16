const posts = {
	data: {
		get: function (wall_id, post_id) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'get');
				return ui.Request({
					url: '/wall' + wall_id.toString() + '_' + post_id.toString(),
					method: 'POST',
					data: data,
					success:  function (response) {
						response = JSON.parse(response);
						if (response.unauth) return settings.raLogin();

						if (response.error)
							return reject(new TypeError("Post fetching error"));

						return resolve(response);
					}
				});
			});
		},
	},
	getAll: function (user_id, offset, count) {
		return new Promise(function (resolve, reject) {
			if (count <= 0 || offset < 0) return reject(new TypeError("Offset or count is invalid"));

			let data = new FormData();
			
			data.append('action', 'get_posts');
			data.append('offset', offset.toString());
			data.append('count', count.toString());

			return ui.Request({
				data: data,
				method: 'POST',
				url: (user_id === 0 ? "/" : (user_id > 0 ? "/id"+user_id : "/bot"+user_id*-1)),
				success: function (response) {
					response = JSON.parse(response);
					if (response.unauth) return settings.raLogin();

					if (response.error) return reject(new TypeError("Post load error"));

					return resolve(response, offset);
				}
			});
		});
	},
	comments: {
		get: function (wall_id, post_id, count = 10, offset = 0) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'get_comments');
				data.append('count', Number(count) || 10);
				data.append('offset', Number(offset) || 0);

				return ui.Request({
					url: '/wall' + wall_id.toString() + '_' + post_id.toString(),
					data: data,
					method: 'POST',
					success: function (response) {
						response = JSON.parse(response);
						if (response.unauth) return settings.raLogin();

						if (response.error) return reject(new TypeError("Comments fetching error"));

						return resolve(response);
					},
				});
			});
		},
		send: function (wall_id, post_id, text = "", attachments = "") {
			return new Promise(function (resolve, reject) {
				if ((text.isEmpty() || text.length > 4096) && attachments.isEmpty())
					return reject(new TypeError("Comment data is incorrect"));

				let data = new FormData();

				data.append('action', 'create_comment');
				data.append('text', text);
				data.append('attachments', attachments);

				return ui.Request({
					data: data,
					method: 'POST',
					url: '/wall' + Number(wall_id).toString() + '_' + Number(post_id).toString(),
					success: function (response) {
						response = JSON.parse(response);
						if (response.unauth) return settings.raLogin();

						if (response.error) return reject(new TypeError("Comment send error"));
						return resolve(response);
					}
				});
			});
		},
		delete: function (wall_id, post_id, comment_id) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();

				data.append('action', 'delete_comment');
				data.append('comment_id', Number(comment_id) || 0);
				return ui.Request({
					method: 'POST',
					data: data,
					url: '/wall' + Number(wall_id) + '_' + Number(post_id),
					success: function (response) {
						try {
							if (response.unauth) return settings.raLogin();

							response = JSON.parse(response);
							if (response.error || !response.success)
								return reject(new TypeError("Comment deletion failed."));

							return resolve(true);
						} catch (e) {
							return reject(e);
						}
					}
				});
			});
		}
	},
	actions: {
		pin: function (post) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();
				data.append('action', 'pin');

				return ui.Request({
					url: '/wall' + Number(post.user_id) + '_' + Number(post.id),
					data: data,
					method: 'POST',
					success:  function (response) {
						try {
							response = JSON.parse(response);
							if (response.unauth) return settings.raLogin();
							
							if (response.result === -1)
								post.is_pinned = 0;
							else 
								post.is_pinned = 1;

							pages.elements.post(post).then(function (element) {
								return resolve(element);
							}).catch(function (e) {
								return resolve(false);
							});
						} catch (e) {
							return resolve(false);
						}
					}
				});
			});
		},
		unpin: function (post) {
			return posts.actions.pin(post);
		},
		delete: function (wall_id, post_id) {
			return new Promise(function (resolve, reject) {
				let data = new FormData();
				data.append('action', 'delete');

				return ui.Request({
					url: '/wall' + Number(wall_id) + '_' + Number(post_id),
					data: data,
					method: 'POST',
					success:  function (response) {
						try {
							response = JSON.parse(response);

							if (response.unauth) return settings.raLogin();
							if (response.error) return resolve(false);

							return resolve(true);
						} catch (e) {
							return resolve(false);
						}
					}
				});
			});
		},
		edit: function (wall_id, post_id, text, attachments = '') {
			return new Promise(function (resolve, reject) {
				if (!settings.users.current) 
					return reject(new TypeError("You are must be logged in to do this"));

				if (String(text).isEmpty() && String(attachments).isEmpty()) return reject(new TypeError("Text or attachments must be provided"));
				if (String(text).length > 8192) return reject(new TypeError("Text is too long")); 

				let data = new FormData();
				data.append('action', 'edit_post');
				data.append('wall_id', Number(wall_id));
				data.append('post_id', Number(post_id));
				data.append('text', String(text));
				data.append('attachments', String(attachments));

				return ui.Request({
					url: '/flex',
					method: 'POST',
					data: data,
					success: function (response) {
						try {
							response = JSON.parse(response);
							if (response.unauth) return settings.raLogin();

							if (response.error) return reject(new TypeError("Post creation error"));

							return resolve(response.response);
						} catch (e) {
							return reject(e);
						}
					}
				});
			});
		},
		create: function (wall_id, text = '', attachments = '') {
			return new Promise(function (resolve, reject) {
				if (!settings.users.current) 
					return reject(new TypeError("You are must be logged in to do this"));

				let user_id = wall_id || settings.users.current.user_id;
				if (String(text).isEmpty() && String(attachments).isEmpty()) return reject(new TypeError("Text or attachments must be provided"));

				if (String(text).length > 8192) return reject(new TypeError("Text is too long")); 

				let data = new FormData();
				data.append('action', 'publish_post');
				data.append('wall_id', Number(user_id));
				data.append('text', String(text));
				data.append('attachments', String(attachments));

				return ui.Request({
					url: '/flex',
					method: 'POST',
					data: data,
					success: function (response) {
						try {
							response = JSON.parse(response);

							if (response.unauth) return settings.raLogin();
							if (response.error) return reject(new TypeError("Post creation error"));

							return resolve(response.response);
						} catch (e) {
							return reject(e);
						}
					}
				});
			});
		}
	}
}

const likes = {
	set: function (credentials, callback) {
		let data = new FormData();

		data.append('action', 'like');
		return ui.Request({
			url: '/' + credentials,
			method: 'POST',
			data: data,
			success: function (response) {
				try {
					response = JSON.parse(response);
					if (response.unauth) return settings.raLogin();

					if (!response.error) {
						let new_count = String(response.count);
						if (new_count === "0" || new_count === "undefined")
							new_count = "";

						if (callback) callback(response.result || 0, new_count);
					}
				} catch (e) {
					if (callback) callback(null, null, e);
				}
			}
		});
	}
}