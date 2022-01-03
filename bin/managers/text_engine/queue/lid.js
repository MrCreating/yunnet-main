console.log('Loaded LID manager.');
const cache = require('memcached');
const mysql = require('mysql');

let q = {
	list: {
		inProcess: {},
		queue: {},
	},

	cache: new cache('memcached:11211'),
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

	waitLocalId: function (uid) {
		return new Promise(function (resolve) {
			return q.worker(uid, resolve);
		});
	},
	worker: function (uid, resolver) {
		if (!q.list.queue[uid])
				q.list.queue[uid] = [];

		q.list.queue[uid].push(resolver);
		if (!q.list.inProcess[uid]) {
			q.list.inProcess[uid] = true;

			q.getData(uid).then(function (local_chat_id) {
				q.list.inProcess[uid] = false;

				let resolver = q.list.queue[uid].splice(0, 1)[0];
				resolver(local_chat_id);

				if (q.list.queue[0])
					q.worker(uid, q.list.queue[0]);
			});
		}
	},
	getData: function (uid) {
		return new Promise(function (resolve, reject) {
			q.cache.get('lid_' + uid, function (err, local_chat_id) {
				if (local_chat_id) {
					return q.cache.incr('lid_' + uid, 1, function (err) {
						return resolve(local_chat_id + 1);
					});
				}

				q.connection.query('SELECT local_chat_id FROM messages.chat_engine_1 WHERE uid = '+uid+' ORDER BY local_chat_id DESC LIMIT 1;', function (err, row, data) {
					let mid = row;
					
					try {
						if (row[0] === undefined)
							mid = 1;
						else
							mid = row[0].local_chat_id+1;
					} catch (e) {
						mid = 0;
					}

					return q.cache.add('lid_' + uid, Number(mid), 3600, function (err) {
						return resolve(mid);
					});
				});
			});
		});
	}
};

module.exports = q;
console.log('Checked to DataBase');
q.connect();