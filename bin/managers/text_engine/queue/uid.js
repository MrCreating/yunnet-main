console.log('Loaded UID manager.');
const mysql = require('mysql');

module.exports = (function () {
	let q = {
		connect: function () {
			q.connection = mysql.createConnection({
				host: '212.109.219.153',
				user: 'root',
				password: 'default-prod-unt-user-iA22021981_',
				database: 'users',
				charset: 'LATIN1_SWEDISH_CI'
			});
			
			q.connection.connect();
			q.connection.on('error', function () {
				return q.connect();
			});
		},
		connection: null,

		last_dialogs_uid: 0,
		last_chats_uid: 0,

		setup: function () {
			q.connection.query('SELECT DISTINCT uid FROM messages.members_chat_list WHERE uid > 0 ORDER BY uid DESC LIMIT 1;', function (err, row, data) {
				if (err) return console.log('Failed to setup UID manager.');

				let last_dialogs_uid = row[0].uid;

				q.connection.query('SELECT DISTINCT uid FROM messages.members_chat_list WHERE uid < 0 ORDER BY uid LIMIT 1;', function (err, row, data) {
					if (err) return console.log('Failed to setup UID manager.');

					let last_chats_uid = row[0].uid;

					q.last_dialogs_uid = last_dialogs_uid;
					q.last_chats_uid = last_chats_uid;

					console.log('UID manager setup complete. DID: ' + last_dialogs_uid + ', CID: ' + last_chats_uid);
				});
			});
		},

		getUID: function (to_dialog) {
			return to_dialog ? (++q.last_dialogs_uid) : (++q.last_chats_uid);
		},
	};

	q.connect();
	q.setup();

	return q;
})();