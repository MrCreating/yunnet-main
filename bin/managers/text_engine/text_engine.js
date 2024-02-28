module.exports = {
	dataHandler: function (req, res) {
		let context = this;

		context.post(req, res).then(function (data) {
			if (data.operation === 'get_lid') {
				context.queue.lid.waitLocalId(data.uid).then(function (lid) {
					res.writeHead(200);

					return res.end(String(lid));
				});
			}
			if (data.operation === 'get_uid') {
				res.writeHead(200);
				
				return res.end(String(context.queue.uid.getUID(Boolean(data.to_dialog))));
			}
		}).catch(function (err) {
			console.log('Failed to handle message. Incorrect JSON? Error: \n\n' + err.toString());

			res.writeHead(500);
			res.end();
		});
	},
	post: function (req, res) {
		let result = '';

		return new Promise(function (resolve) {
			req.on('data', function (data) {
				if (data.length > 1e6)
                	req.connection.destroy();

				result += data;
			});

			req.on('end', function () {
            	return resolve(JSON.parse(result));
       		});

       		req.on('error', function () {
       			return resolve({});
       		});
		});
	},
	queue: {
		lid: require('./queue/lid.js'),
		uid: require('./queue/uid.js')
	}
};