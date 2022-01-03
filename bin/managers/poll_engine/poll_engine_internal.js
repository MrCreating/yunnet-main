const utils = require('./tools/utilsInternal.js');
console.log('Accepter Server loaded.');

module.exports = {
	dataHandler: async function (req, res) {
		let context = this;
		try {
			let data = await this.utils.parseQuery(req);

			req.on('timeout', function () {});
			res.on('timeout', function () {});
			req.on('close', function () {});
			req.on('error', function () {});

			let events = await utils.buildEvent.apply(this, [data]);

			this.utils.asyncForIn.apply(this, [events, async function (event, user_id) {
				context.connections[user_id].last_event_id = event.last_event_id || 1;
				context.connections[user_id].events[event.last_event_id] = event;

				if (!context.connections[user_id]) return;
				if (context.connections[user_id].sessions) {
					context.utils.asyncForIn.apply(context, [context.connections[user_id].sessions, function (session) {
						session.end(JSON.stringify(event));
					}]);
				}
			}]);

			res.writeHead(200);
			res.end(JSON.stringify(1));
		} catch (e) {
			return res.end(JSON.stringify(0));
		}
	}
};