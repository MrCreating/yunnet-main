const http       = require("http");
const textEngine = require('./text_engine.js');

process.on('SIGINT', function() {
    console.log("Requested exit.");

	process.exit();
});
console.log('Prepared all data.');

http.createServer(function (req, res) {
	return textEngine.dataHandler.apply(textEngine, [req, res]);
}).listen(80, function () {
	console.log('Started Text Engine successfully.');
});