console.log('Loaded internal utils.');

module.exports = {
	buildEvent: function (data) {
		let context = this;

		return new Promise(function (resolve) {
			let events = {};

			function finalize () {
				return resolve(events);
			}

			if (data.user_ids && data.local_ids && data.local_ids.length > 0 && data.user_ids.length > 0 && data.local_ids.length === data.local_ids.length) {
				context.utils.forEach(data.user_ids, function (item, i) {
					let event = JSON.parse(JSON.stringify(data.event));

					let user_id = Number(data.user_ids[i]);
					let local_id = Number(data.local_ids[i]);
					let uid = event.uid || 0;

					if (!context.connections[String(user_id)] || !context.connections[String(user_id)].sessions || context.connections[String(user_id)].sessions.length <= 0) {
						if (i === (data.user_ids.length - 1)) finalize();

						return;
					}

					if (event.event === "new_message" || event.event === 'edit_message') {
						if (event.message && event.event === 'new_message') {
							if (user_id === event.message.from_id) {
								event.message.type = "outcoming_message";
							}
						}
					}

					event.peer_id = local_id;
					if (user_id < 0)
						delete event.bot_peer_id;

					if (user_id > 0 && event.bot_peer_id)
						delete event.peer_id;

					if (event.peer_id === true)
						delete event.peer_id

					if (event.keyboard && user_id < 0)
						delete event.keyboard;

					if (event.payload && user_id > 0)
						delete event.payload;

					if (user_id > 0 && local_id < 0 && uid > 0 && data.owner_id < 0)
						event.bot_peer_id = data.owner_id;

					if (event.peer_id && event.bot_peer_id)
						delete event.peer_id;

					if (event.uid)
						delete event.uid;
					
					event.last_event_id = context.connections[user_id].last_event_id ? (context.connections[user_id].last_event_id + 1) : 1;

					events[user_id] = event;

					if (i === (data.user_ids.length - 1)) finalize();
				})
			} else {
				let result = {};

				result[data.owner_id] = data.event;
			}
		});
	}
}