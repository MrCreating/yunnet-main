console.log('Loaded session manager.');

module.exports = {
	joinTo: function (context, user_id, key, state, last_event_id, req, res) {
		let current = this;

		return new Promise(function (resolve, reject) {
			if (!context.connections[user_id]) {
				context.connections[user_id] = {
					sessions: {},
					events: {},
					last_event_id: 0
				}
			} else {
				if (context.connections[user_id].sessions[key]) {
				//	return res.end(JSON.stringify(context.errors['20']));
				}
			}

			context.connections[user_id].sessions[key] = res;

			req.on('timeout', function () {
				current.removeSession(context, user_id, key);
			});
			res.on('timeout', function () {
				current.removeSession(context, user_id, key);
			});
			req.on('close', function () {
				current.removeSession(context, user_id, key);
			});
			req.on('error', function () {
				current.removeSession(context, user_id, key);
			});

			if (last_event_id > 0 && last_event_id < context.connections[user_id].last_event_id) {
				let event = current.getEventById(context, user_id, key, Number(last_event_id) + 1);

				if (event) {
					res.end(JSON.stringify(event));
				}
			}

			return resolve();
		});
	},
	removeSession: function (context, user_id, key, finishConnection = false) {
		if (!context.connections[user_id]) return false;

		if (!context.connections[user_id].sessions[key]) return false;

		if (finishConnection)
			context.connections[user_id].sessions[key].end(JSON.stringify(context.errors['25']));

		delete context.connections[user_id].sessions[key]
		return true;
	},
	getEventById: function (context, user_id, key, event_id) {
		if (!context.connections[user_id]) return null;

		if (!context.connections[user_id].sessions[key]) return null;

		let event = context.connections[user_id].events[event_id];

		if (!event) return null;

		return event;
	}
}