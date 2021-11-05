// default components
const net       = require("net");
const https     = require("https");
const memcached = require("memcached");

/* DEFAULT CONSTANTS */
const __HOMEDIR__ = "/home/unt";
const __DEFPORT__ = 8080;
const connected   = {};

// utils object
const utils = {
	 // DB conenction
	connection: '',

	// Cache connecting
	cache: new memcached('127.0.0.1:11211'),

	// it must be POST data handler but it useless
	resolveData: async function (req) {
		let body;

		return new Promise((res) => {
            let json = "";

            req.on("data", function (a) {
                json += a;
            })

            req.on("end", function () {
                return res(utils.qs.parse(json))
            })
		})
	},

	// async Array.forEach()
	asyncForEach: function (arr, iter) {
		return new Promise (function (res) {
			return res(
				setImmediate(function f(i) {
					iter(arr[i], i);
					
					if (i < arr.length - 1) 
						setImmediate(f, i+1);
					},
				0)
			);
		})
	},

	// async for (a in b)
	forEach: function (object, callback) {
		return Object.keys(object).map(async function(key, index) {
			let value = object[key];
			
			callback(value);
			return true;
		});
	},

	// file system
	fs: require("fs"),

	// url parser
	url: require("url"),

	// querystring parser
	qs: require("querystring")
};

// server object
const Server = {

	// checking auth state
	isLogged: async function (req, key) {
		return new Promise((resolve) => {
			utils.cache.get(key, function (err, data) {
				if (!data)
					return resolve({state: false});
				
				let user_id = Number(data);
				utils.cache.get('banned_' + user_id, function (err, data) {
					let is_banned = Number(data);
					if (is_banned)
						return resolve({state: false});

					return resolve({state: true, user_id: user_id});
				})
			});
		});
	},

	// default events
	events: {
		connected: JSON.stringify({status: 1})
	},

	// default errors
	errors: {
		auth: JSON.stringify({error: {error_code: 1, error_message: 'Incorrect session key'}}),
		internal: JSON.stringify({error: {error_code: 15, error_message: 'Internal server error'}})
	},

	// connect to LP
	route: async function (req, res) {
		let ip = req.connection.remoteAddress;
		let query;

		if (req.method !== "POST")
			query = utils.url.parse(req.url, true).query;
		else
			query = await utils.resolveData(req);
		
		let key = String(query.key);
		let mode = query.mode;

		req.setMaxListeners(0);
		res.setMaxListeners(0);

		Server.isLogged(req, key).then((result) => {
			if (result.state) {
				return Server.Connect(req, res, result.user_id, key, query).then((result) => {
					if (result) {
						if (query.state === 'sse') {
							return res.write("id: 0\n\n"+"data: "+Server.events.connected+"\n\n");
						}
					};
				});
			};
			
			res.writeHead(200, {
				'Content-Security-Policy': 'upgrade-insecure-requests',
				'Server': 'YunNet',
				'Access-Control-Allow-Origin': '*'
			});
			return res.end(Server.errors.auth);
		});
	},

	// function to be called from rout
	// He may be used for event sending
	Connect: async function (req, res, user_id, key, query) {
		return new Promise(function (resolve) {
			req.setTimeout(24*60*60);
			res.setTimeout(24*60*60);

			req.on("timeout", function () {
				if (query.state !== 'polling')
					utils.cache.del(key);
				
				delete connected['user'+String(user_id)].sockets[String(key)];
				req.removeAllListeners('close');
				req.removeAllListeners('error');

				res.end(JSON.stringify({event: 'timeout'}));
			})
			res.on("timeout", function () {
				if (query.state !== 'polling')
					utils.cache.del(key);
				
				delete connected['user'+String(user_id)].sockets[String(key)];
				req.removeAllListeners('close');
				req.removeAllListeners('error');

				res.end(JSON.stringify({event: 'timeout'}));
			})

			req.on('close', function () {
				if (query.state !== 'polling')
					utils.cache.del(key);
				
				delete connected['user'+String(user_id)].sockets[String(key)];
				req.removeAllListeners('close');
				req.removeAllListeners('error');
			});
			req.on('error', function (e) {
				if (query.state !== 'polling')
					utils.cache.del(key);
				
				delete connected['user'+String(user_id)].sockets[String(key)];
				req.removeAllListeners('close');
				req.removeAllListeners('error');
			});

			let APIMode = String(query.state).toLowerCase();
			switch (APIMode) {
				case 'polling':
					res.APImode = 'polling'; break;
				case 'lp':
					res.APImode = 'polling'; break;
				default:
					res.APImode = 'sse'; break;
			}

			if (!connected['user'+String(user_id)]) {
				connected['user'+String(user_id)] = {};
				connected['user'+String(user_id)].sockets = {};
				
				connected['user'+String(user_id)].events = {};
				connected['user'+String(user_id)].last_event_id = 0;
			}

			connected['user'+String(user_id)].sockets[key] = res;
			let last_event_id = Number(query.last_event_id);

			if (APIMode === 'sse') {
				res.writeHead(200, {
					'Content-Type': 'text/event-stream; charset=utf-8',
    				'Cache-Control': 'no-cache',
    				'Content-Security-Policy': 'upgrade-insecure-requests',
					'Server': 'YunNet',
					'Access-Control-Allow-Origin': '*',
					'Connection': 'keep-alive'
				});
			} else {
				res.writeHead(200, {
					'Server': 'YunNet',
					'Access-Control-Allow-Origin': '*'
				});
			}
			
			if (connected['user'+String(user_id)].last_event_id >= last_event_id && last_event_id > 0) {
				let tmp_counter = last_event_id;

				if (APIMode === "polling") {
					try {
						let event = connected['user'+String(user_id)].events['e'+tmp_counter];

						if (event) {
							return res.end(JSON.stringify(event));
						}
					} catch (e) {

					}
				}
				if (APIMode === "sse") {
					let allow_to_send = true;

					let tmp = setInterval(function () {
						if (!allow_to_send)
							return false;

						allow_to_send = false;
						if (connected['user'+String(user_id)].last_event_id > tmp_counter) {
							let event = connected['user'+String(user_id)].events['e'+tmp_counter];

							if (event) {
								return res.write('id: '+tmp_counter+'\n\n'+'data: '+JSON.stringify(event)+'\n\n');
							}
						
							allow_to_send = true;
							return tmp_counter++;
						}

						allow_to_send = true;
						clearInterval(tmp);

						return resolve(true);
					}, 0);
				}
			}

			return resolve(true);
		});
	},
	event: {
		Add: async function (data) {
			let to_ids         = data.user_ids;
			let lids           = data.lids;
			let event_default  = data.event;
			let uid            = event_default.uid;
			
			delete event_default.uid;
			return utils.asyncForEach(to_ids, async function (item, i) {
				let event = JSON.parse(JSON.stringify(event_default));

				let user_id = Number(to_ids[i]);
				let local_id = Number(lids[i]);

				if (!connected['user'+String(user_id)])
					return false;

				if (event.event === "new_message" || event.event === 'edit_message') {
					if (event.message && event.event === 'new_message') {
						if (user_id === event.message.from_id) {
							event.message.type = "outcoming_message";
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
				}
				if (event.event === "message_delete") {
					event.peer_id = local_id;
					if (user_id < 0)
						delete event.bot_peer_id;

					if (user_id > 0 && event.bot_peer_id)
						delete event.peer_id;

					if (user_id > 0 && event.bot_peer_id)
						delete event.peer_id;

					if (event.peer_id === true)
						delete event.peer_id;

					if (user_id > 0 && local_id < 0 && uid > 0 && data.owner_id < 0)
						event.bot_peer_id = data.owner_id;

					if (event.peer_id && event.bot_peer_id)
						delete event.peer_id;
				}
				if (event.event === "typing") {
					if (event.peer_id === 0) {
						event.peer_id = local_id;
						if (user_id < 0)
							delete event.bot_peer_id;

						if (user_id > 0 && event.bot_peer_id)
							delete event.peer_id;

						if (user_id > 0 && event.bot_peer_id)
							delete event.peer_id;

						if (event.peer_id === true)
							delete event.peer_id;

						if (user_id > 0 && local_id < 0 && uid > 0 && data.owner_id < 0)
							event.bot_peer_id = data.owner_id;

						if (event.peer_id && event.bot_peer_id)
							delete event.peer_id;
					}
				}
				if (event.event === 'chat_event') {
					event.peer_id = local_id;
					if (user_id < 0)
						delete event.bot_peer_id;

					if (user_id > 0 && event.bot_peer_id)
						delete event.peer_id;

					if (user_id > 0 && event.bot_peer_id)
						delete event.peer_id;

					if (event.peer_id === true)
						delete event.peer_id;

					if (user_id > 0 && local_id < 0 && uid > 0 && data.owner_id < 0)
						event.bot_peer_id = data.owner_id;

					if (event.peer_id && event.bot_peer_id)
						delete event.peer_id;
				}

				event.last_event_id = connected['user'+String(user_id)] ? (Number(connected['user'+String(user_id)].last_event_id) + 1) : 1;
				try {
					connected['user'+String(user_id)].last_event_id = event.last_event_id;
				} catch (e) {}

				if (connected['user'+String(user_id)].last_event_id > 1000) {
					try {
						let first_index = event.last_event_id - 1000;
						delete connected['user'+String(user_id)].events['e'+first_index];
					} catch (e) {}
				}

				connected['user'+String(user_id)].events['e'+String(event.last_event_id)] = event;
				return utils.forEach(connected['user'+String(user_id)].sockets, async function (socket) {
					if (socket.APImode === 'sse') {
						return socket.write('id: '+event.last_event_id+'\n\n'+'data: '+JSON.stringify(event)+'\n\n');
					} else {
						return socket.end(JSON.stringify(event));
					}
				});
			})
		}
	}
};

const SERVER = net.createServer(function (socket) {
	socket.setMaxListeners(0);

	socket.on("data", function (data) {
		data = data.toString();
		try {
			data = JSON.parse(data);
		} catch (e) {
			return false;
		}

		return Server.event.Add(data);
	});

	return true;
})

SERVER.setMaxListeners(0);
SERVER.listen(__HOMEDIR__+"/bin/managers/sockets/lp_manager.sock");

// run external server
utils.cache.connect('127.0.0.1:11211', function (e, c) {
	if (e)
		return console.log('\x1b[31m[!]\x1b[0m Failed to start LP!');
	
	utils.fs.readFile('/etc/letsencrypt/live/yunnet.ru/privkey.pem', null, function (err, key) {
		utils.fs.readFile('/etc/letsencrypt/live/yunnet.ru/fullchain.pem', null, function (err, cert) {
			https.createServer({
				key: key,
				cert: cert
			}, Server.route).listen(__DEFPORT__, function () {
				return console.log('\n\x1b[32m[ok]\x1b[0m Started LP Server!');
			});
		});
	});
});