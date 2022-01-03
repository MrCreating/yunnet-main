const qs        = require('querystring');
const url       = require('url');
const memcached = require("memcached");
console.log("Utils manager loaded.");

module.exports = {
	cache: new memcached("memcached:11211"),

	forEach: function (arr, iter) {
		return new Promise (function (res) {
			return res(
				setImmediate(function f(i) {
					iter(arr[i], i);

					if (i < (arr.length - 1)) 
						setImmediate(f, i + 1);
					},
				0)
			);
		})
	},
	isLogged: function (req, key) {
		let context = this;

		return new Promise(function (resolve, reject) {
			context.cache.get(key, function (err, data) {
				if (err || !data)
					return reject(new Error('Incorrect session key'));
				
				let user_id = Number(data);
				if (user_id !== 0)
					return resolve(user_id);

				return reject(new Error('Failed to find the user id'));
			});
		});
	},
	parseQuery: function (req) {
		return new Promise(function (resolve, reject) {
			if (req.method !== 'GET' && req.method !== 'POST') return reject(new TypeError('Incorrect method.'));

			let data = url.parse(req.url, true).query;

			if (req.method === 'POST') {
				let result = '';

				req.on('data', function (e) {
					result += e;
				});

				req.on("end", function () {
					let final = qs.parse(result);

					try {
						return resolve(JSON.parse(result));
					} catch (e) {
						return resolve(Object.assign(final, data));
					}
				});
			} else {
				return resolve(data);
			}
		});
	},
	asyncForIn: async function (obj, func) {
		for (let key in obj) {
			func(obj[key], key);
		}
	}
}