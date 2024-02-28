console.log('Loaded UID manager.');
const mysql = require('mysql');

module.exports = (function () {
	let q = {
		connect: function () {
			q.connection = mysql.createConnection({
				host: 'mysql',
				user: process.env.MYSQL_ROOT_USER,
				password: process.env.MYSQL_ROOT_PASSWORD,
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
			q.connection.query('SELECT IFNULL(MAX(uid), 0) AS max_uid, IFNULL(MIN(uid), 0) AS min_uid FROM messages.members_chat_list', function (err, row, data) {
				if (err) return console.log('Failed to setup UID manager.');

				let last_dialogs_uid = row[0].max_uid;
				let last_chats_uid = row[0].min_uid;

				q.last_dialogs_uid = last_dialogs_uid;
				q.last_chats_uid = last_chats_uid;

				console.log('UID manager setup complete. DID: ' + last_dialogs_uid + ', CID: ' + last_chats_uid);
			});
		},

		getUID: function (to_dialog = true) {
			return to_dialog ? (++q.last_dialogs_uid) : (--q.last_chats_uid);
		},
	};

	q.connect();
	q.setup();

	return q;
})();