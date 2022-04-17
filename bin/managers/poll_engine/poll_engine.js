const utils          = require("./tools/utils.js");
const sessionManager = require('./tools/sessionManager.js');
console.log('Public Server loaded.');

module.exports = {
	connections: {},
	utils: utils,

	errors: {
		'-1': {
			error: {
				error_code: -1,
				error_message: 'Only GET or POST methods supported'
			}
		},
		'1': {
			error: {
				error_code: 1,
				error_message: 'Incorrect session key'
			}
		},
		'15': {
			error: {
				error_code: 1,
				error_message: 'Internal server error'
			}
		},
		'20': {
			error: {
				error_code: 20,
				error_message: 'This session is already running'
			}
		},
		'25': {
			error: {
				error_code: 25,
				error_message: 'This session is finished manually'
			}
		}
	},

	dataHandler: async function (req, res) {
		let context = this;
		res.writeHead(200, {
			'Server': 'YunNet',
			'Access-Control-Allow-Origin': '*'
		});

		try {
			let data = await utils.parseQuery(req);

			utils.isLogged.apply(utils, [req, data.key]).then(function (user_id) {
				sessionManager.joinTo.apply(sessionManager, [context, user_id, data.key, data.state === 'sse' ? 'sse' : 'polling', Number(data.last_event_id), req, res]).then(function () {

				}).catch(function (err) {
					res.end(JSON.stringify(context.errors['15']));
				});
			}).catch(function (err) {
				res.end(JSON.stringify(context.errors['1']));
			});
		} catch (e) {
			return res.end(JSON.stringify(this.errors['-1']));
		}
	}
};