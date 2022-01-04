console.log('Loading LP modules...');
const http               = require("http");
const https              = require("https");
const pollEngine         = require('./poll_engine.js');
const pollEngineInternal = require('./poll_engine_internal.js');
const fs                 = require('fs');

process.on('SIGINT', function() {
    console.log("Requested exit.");

	process.exit();
});

console.log('Prepared all data.');

if (process.env.UNT_PRODUCTION === '1') {
	https.createServer({
		key: fs.readFileSync('/home/unt/config/local/config/nginx/privkey.pem'),
		cert: fs.readFileSync('/home/unt/config/local/config/nginx/fullchain.pem')
	}, function (req, res) {
		req.setMaxListeners(0);
		res.setMaxListeners(0);
		req.setTimeout(24*60*60);
		res.setTimeout(24*60*60);

		pollEngine.dataHandler.apply(pollEngine, [req, res]);
	}).listen(80, function () {
		console.log('Started Public server');
	});
} else {
	http.createServer(function (req, res) {
		req.setMaxListeners(0);
		res.setMaxListeners(0);
		req.setTimeout(24*60*60);
		res.setTimeout(24*60*60);

		pollEngine.dataHandler.apply(pollEngine, [req, res]);
	}).listen(80, function () {
		console.log('Started Public NON-PRODUCTION Server');
	});
}

http.createServer(function (req, res) {
	req.setMaxListeners(0);
	res.setMaxListeners(0);
	req.setTimeout(24*60*60);
	res.setTimeout(24*60*60);

	pollEngineInternal.dataHandler.apply(pollEngine, [req, res]);
}).listen(8080, function () {
	console.log('Started Accepting Server');
});