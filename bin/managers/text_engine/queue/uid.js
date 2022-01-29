console.log('Loaded UID manager.');
const mysql = require('mysql');

module.exports = (function () {
	let q = {
		connect: function () {
			q.connection = mysql.createConnection({
				host: (process.env.UNT_PRODUCTION === '1' ? 'mysql_prod' : '212.109.219.153'),
				port: (process.env.UNT_PRODUCTION === '1' ? 3306 : 59876),
				user: 'root',
				password: process.env.UNT_PRODUCTION === '1' ? 'default-prod-unt-user-iA22021981_' : 'unt-user-test-pc2021_die',
				database: 'users',
				charset: 'LATIN1_SWEDISH_CI'
			});
			
			q.connection.connect();
			q.connection.on('error', function (e) {
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