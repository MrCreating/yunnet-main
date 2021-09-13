/**
 *
 * This file contains a manager which responds 
 * of all messages operations (send, create chat, e.tc.)
 *
*/

// this manager listens only unix socket and we need to
// require all modules and create it.
var net   = require("net"),
    cache = require("memcached"),
    mysql = require("mysql2");

let connection;
function c () {
	connection = mysql.createConnection({
		host: '127.0.0.1',
		user: 'root',
		password: 'iA22021981_',
		database: 'users',
		charset: 'LATIN1_SWEDISH_CI'
	});
	
	console.log('\x1b[32m[ok]\x1b[0m Connecting to DB...');
	connection.connect();
	
	connection.on('error', function () {
		console.log('\x1b[32m[ok]\x1b[0m Reconnecting to DB...');
		
		return c();
	})
}
c();

const utils = {
	cache: new cache('127.0.0.1:11211')
};

const Messages = {
	isWorking: {},
	pending: {},
	getLID: function (uid) {
		return new Promise(function (resolve) {
			utils.cache.get('lid_'+uid, function (err, local_chat_id) {
				if (local_chat_id) {
					return utils.cache.incr('lid_'+uid, 1, function (err) {
						return resolve(local_chat_id+1);
					});
				}

				connection.query('SELECT local_chat_id FROM messages.chat_engine_1 WHERE uid = '+uid+' ORDER BY local_chat_id DESC LIMIT 1;', function (err, row, data) {
					let mid = row;
					
					try {
						if (row[0] === undefined)
							mid = 1;
						else
							mid = row[0].local_chat_id+1;
					} catch (e) {
						mid = 0;
					}

					return utils.cache.add('lid_'+uid, Number(mid), 3600, function (err) {
						return resolve(mid);
					});
				});
			});
		});
	},
	Submit: async function (uid, socket) {
		Messages.isWorking['uid'+uid] = true;

		let local_chat_id = await Messages.getLID(uid);
		return new Promise(function (resolve) {
			Messages.isWorking['uid'+uid] = false;

			try {
				socket.write(String(local_chat_id));
				socket.on("error", function () {
					return true;
				})
						
				if (Messages.pending['uid'+uid]) {
					if (Messages.pending['uid'+uid][0]) {
						let socket_new = Messages.pending['uid'+uid][0];
									
						Messages.pending['uid'+uid].splice(0, 1);
						if (Messages.pending['uid'+uid].length <= 0) {
							delete Messages.pending['uid'+uid];
						}
									
						return Messages.Submit(uid, socket_new);
					}
				}
			} catch (e) {
				return false;
			}
		});
	}
}

// now we creating and listen the server
const Server = {
	last_dialogs_uid: 0,
	last_chats_uid: 0
}

utils.cache.connect('127.0.0.1:11211', function (err, cache) {
	if (err)
		return console.log('\x1b[31m[!]\x1b[0m Failed to start TE!');

	connection.query('SELECT DISTINCT uid FROM messages.members_chat_list WHERE uid > 0 ORDER BY uid DESC LIMIT 1;', function (err, row, data) {
		let last_dialogs_uid = row[0].uid;

		connection.query('SELECT DISTINCT uid FROM messages.members_chat_list WHERE uid < 0 ORDER BY uid LIMIT 1;', function (err, row, data) {
			let last_chats_uid = row[0].uid;

			Server.last_dialogs_uid = last_dialogs_uid;
			Server.last_chats_uid   = last_chats_uid;

			const SERVER = net
			   .createServer(function (socket) {
			   		socket.setMaxListeners(0);
			   		socket.on("data", function (data) {
			   			data = JSON.parse(data)

			   			if (data.operation === "get_uid") {
			   				let type    = data.to_dialog;
			   				let new_uid = 0;

			   				if (type) {
			   					new_uid = Server.last_dialogs_uid + 1;
			   					Server.last_dialogs_uid = new_uid;
			   				} else {
			   					new_uid = Server.last_chats_uid - 1;
			   					Server.last_chats_uid = new_uid;
			   				}

			   				return socket.write(String(new_uid));
			   			}

			   			let uid = data.uid;
			   			if (Messages.isWorking['uid'+uid]) {
							if (!Messages.pending['uid'+uid])
								Messages.pending['uid'+uid] = [];
							
							Messages.pending['uid'+uid].push(socket);
						} else {
							Messages.Submit(uid, socket);
						}
			   		})

			   		socket.on("error", function () {
			   			return false;
			   		})
			   })
			
			SERVER.setMaxListeners(0);
			SERVER.listen("/home/unt/bin/managers/sockets/text_engine.sock");
		});
	});
});